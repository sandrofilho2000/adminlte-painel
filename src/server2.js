const fs = require('fs');
const http = require('http');
const https = require('https');
const tls = require('tls');
const WebSocket = require('ws');

// -----------------------------------------------------
// CONFIGURAÇÃO POR AMBIENTE
// -----------------------------------------------------
const ENV = (process.env.ENVIRONMENT || process.env.NODE_ENV || 'production').toLowerCase();

const ENV_DEFAULTS = {
  production: {
    useTls: false,                      // TLS termina no Apache/Nginx
    port: 8081,
    host: '127.0.0.1',
    certPath: '/etc/letsencrypt/live/sistemas.confef.org.br-0001/fullchain.pem',
    keyPath: '/etc/letsencrypt/live/sistemas.confef.org.br-0001/privkey.pem',
  },
  development: {
    useTls: false,                      // idem: TLS na frente reversa
    port: 8082,
    host: '127.0.0.1',
    certPath: '/etc/letsencrypt/live/sistemas.confef.org.br-0001/fullchain.pem',
    keyPath: '/etc/letsencrypt/live/sistemas.confef.org.br-0001/privkey.pem',
  }
};

const defaults = ENV_DEFAULTS[ENV] || ENV_DEFAULTS.production;

// Permite override via variáveis WS_*
const useTls = (typeof process.env.WS_TLS !== 'undefined')
  ? (process.env.WS_TLS === '1' || process.env.WS_TLS === 'true')
  : defaults.useTls;

const port = process.env.WS_PORT ? Number(process.env.WS_PORT) : defaults.port;
const host = process.env.WS_HOST || defaults.host;
const certPath = process.env.WS_SSL_CERT || defaults.certPath;
const keyPath = process.env.WS_SSL_KEY || defaults.keyPath;

if (useTls && (!certPath || !keyPath)) {
  throw new Error('WS_TLS=true, mas WS_SSL_CERT ou WS_SSL_KEY não definidos.');
}

// -----------------------------------------------------
// CRIA SERVIDOR HTTP/HTTPS
// -----------------------------------------------------
const server = useTls
  ? https.createServer({
    cert: fs.readFileSync(certPath),
    key: fs.readFileSync(keyPath),
    SNICallback: (servername, cb) => {
      const ctx = tls.createSecureContext({
        cert: fs.readFileSync(certPath),
        key: fs.readFileSync(keyPath),
      });
      cb(null, ctx);
    },
  })
  : http.createServer();

const wss = new WebSocket.Server({ server });

const allowedOriginRegex = process.env.WS_ALLOWED_ORIGIN_REGEX
  ? new RegExp(process.env.WS_ALLOWED_ORIGIN_REGEX, 'i')
  : null;

function isAllowedOrigin(req) {
  const origin = req.headers.origin;
  const pattern = process.env.WS_ALLOWED_ORIGIN_REGEX || '';
  let ok = false;

  try {
    ok = origin && allowedOriginRegex && allowedOriginRegex.test(origin);
  } catch (e) {
    console.log('origin-check-error', e);
  }

  console.log('origin-check', { origin, pattern, ok });

  if (!origin) return false;
  if (!allowedOriginRegex) return false;
  return ok;
}


// -----------------------------------------------------
// LÓGICA DE SESSÃO (seu código, praticamente igual)
// -----------------------------------------------------
const SESSION_DURATION = Number(process.env.SESSION_TIMEOUT || 1800);
const SESSION_GRACE_PERIOD = 30;      // segundos de tolerância para reconexão

// sessionId => { deviceId, timeRemaining, lastActivity, clients: Map(tabId, ws), interval, graceTimeout }
const sessions = new Map();

wss.on('connection', (ws, req) => {
  // Validação de Origin (se quiser aplicar agora)
  if (!isAllowedOrigin(req)) {
    ws.close(4001, 'Origin não permitido');
    return;
  }

  let sessionId;
  let deviceId;
  let tabId;

  ws.on('message', (message) => {
    const data = JSON.parse(message);

    if (data.sessionId && data.deviceId) {
      sessionId = data.sessionId;
      deviceId = data.deviceId;
      tabId = data.tabId || 'defaultTab';

      if (sessions.has(sessionId)) {
        const session = sessions.get(sessionId);

        if (session.graceTimeout) {
          clearTimeout(session.graceTimeout);
          session.graceTimeout = null;
        }

        if (session.deviceId !== deviceId) {
          // Dispositivo diferente: derruba clientes anteriores
          session.clients.forEach((client) => {
            client.send(JSON.stringify({
              action: 'logged_out',
              message: 'Sua sessão foi iniciada em outro dispositivo.'
            }));
            client.close();
          });

          session.deviceId = deviceId;
          session.clients.clear();
          session.clients.set(tabId, ws);
          resetSession(sessionId);
          sendTimeToClient(ws, session.timeRemaining);
        } else {
          // Mesmo dispositivo
          session.clients.set(tabId, ws);
          sendTimeToClient(ws, session.timeRemaining);
        }

        session.lastActivity = Date.now();
      } else {
        // Nova sessão
        const session = {
          deviceId: deviceId,
          timeRemaining: SESSION_DURATION,
          lastActivity: Date.now(),
          clients: new Map(),
          interval: null,
          graceTimeout: null,
        };
        session.clients.set(tabId, ws);
        sessions.set(sessionId, session);
        startSessionTimer(sessionId);
        sendTimeToClient(ws, session.timeRemaining);
      }
    }

    if (data.action === 'session_renewed' && sessions.has(sessionId)) {
      resetSession(sessionId);
    }

    if (data.action === 'logout' && sessionId && sessions.has(sessionId)) {
      const session = sessions.get(sessionId);
      const client = session.clients.get(tabId);

      if (client) {
        client.send(JSON.stringify({ action: 'logout', deviceId: deviceId }));
        client.close(4000, 'User logged out');
      }

      session.clients.delete(tabId);

      if (session.clients.size > 0) {
        session.clients.forEach((client) => {
          client.send(JSON.stringify({ action: 'logout', deviceId: deviceId }));
          client.close(4000, 'User logged out');
        });
        session.clients.clear();
      }

      clearInterval(session.interval);
      if (session.graceTimeout) clearTimeout(session.graceTimeout);
      sessions.delete(sessionId);
    }
  });

  ws.on('close', () => {
    if (sessionId && sessions.has(sessionId)) {
      const session = sessions.get(sessionId);
      session.clients.delete(tabId);

      if (session.clients.size === 0) {
        startSessionGracePeriod(sessionId);
      }
    }
  });
});

function startSessionTimer(sessionId) {
  const session = sessions.get(sessionId);
  if (!session || session.interval) return;

  session.interval = setInterval(() => {
    session.timeRemaining -= 1;

    if (session.timeRemaining <= 0) {
      session.clients.forEach((client) => {
        client.send(JSON.stringify({ action: 'session_expired', deviceId: session.deviceId }));
        client.close();
      });
      clearInterval(session.interval);
      if (session.graceTimeout) clearTimeout(session.graceTimeout);
      sessions.delete(sessionId);
    } else {
      session.clients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
          client.send(JSON.stringify({ sessionTimeRemaining: session.timeRemaining }));
        }
      });
    }

    if (Date.now() - session.lastActivity > SESSION_DURATION * 1000) {
      session.clients.forEach((client) => {
        client.send(JSON.stringify({ action: 'session_expired', deviceId: session.deviceId }));
        client.close();
      });
      clearInterval(session.interval);
      if (session.graceTimeout) clearTimeout(session.graceTimeout);
      sessions.delete(sessionId);
    }
  }, 1000);
}

function startSessionGracePeriod(sessionId) {
  const session = sessions.get(sessionId);
  if (!session || session.graceTimeout) return;

  session.graceTimeout = setTimeout(() => {
    clearInterval(session.interval);
    sessions.delete(sessionId);
  }, SESSION_GRACE_PERIOD * 1000);
}

function resetSession(sessionId) {
  if (!sessions.has(sessionId)) return;

  const session = sessions.get(sessionId);
  session.timeRemaining = SESSION_DURATION;
  session.lastActivity = Date.now();

  session.clients.forEach((client) => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(JSON.stringify({ sessionTimeRemaining: session.timeRemaining }));
    }
  });
}

function sendTimeToClient(ws, timeRemaining) {
  if (ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify({ sessionTimeRemaining: timeRemaining }));
  }
}

server.listen(port, host, () => {
  console.log(
    `[${ENV}] Servidor WebSocket rodando em ${useTls ? 'wss' : 'ws'}://${host}:${port}...`
  );
});
