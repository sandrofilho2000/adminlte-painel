(function ($) {
  'use strict';

  const salesCanvas = document.getElementById('sales-chart');
  if (salesCanvas && window.Chart) {
    new Chart(salesCanvas, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [
          {
            label: 'Digital Goods',
            data: [28, 42, 38, 56, 72, 68, 86],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.15)',
            fill: true,
            tension: 0.35,
            pointRadius: 3
          },
          {
            label: 'Physical Goods',
            data: [18, 26, 34, 30, 48, 52, 61],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'bottom'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.06)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  }

  const browserCanvas = document.getElementById('browser-chart');
  if (browserCanvas && window.Chart) {
    new Chart(browserCanvas, {
      type: 'doughnut',
      data: {
        labels: ['Chrome', 'Firefox', 'Safari', 'Edge', 'Other'],
        datasets: [
          {
            data: [48, 18, 16, 12, 6],
            backgroundColor: ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6c757d']
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }

  const stateVisits = {
    'br-01': 820,
    'br-02': 940,
    'br-03': 610,
    'br-04': 1180,
    'br-05': 3480,
    'br-06': 2210,
    'br-07': 1970,
    'br-08': 1360,
    'br-09': 2640,
    'br-10': 1290,
    'br-11': 1530,
    'br-12': 1120,
    'br-13': 5120,
    'br-14': 1720,
    'br-15': 980,
    'br-16': 2870,
    'br-17': 2360,
    'br-18': 760,
    'br-19': 4310,
    'br-20': 890,
    'br-21': 3010,
    'br-22': 690,
    'br-23': 420,
    'br-24': 2420,
    'br-25': 8390,
    'br-26': 640,
    'br-27': 570
  };

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      const script = document.createElement('script');
      script.src = src;
      script.onload = resolve;
      script.onerror = reject;
      document.body.appendChild(script);
    });
  }

  function ensureBrazilMap() {
    const pluginUrl = 'https://cdn.jsdelivr.net/npm/jqvmap@1.5.1/dist/jquery.vmap.min.js';
    const brazilMapUrl = 'https://cdn.jsdelivr.net/npm/jqvmap@1.5.1/dist/maps/jquery.vmap.brazil.js';
    const pluginReady = $.fn.vectorMap ? Promise.resolve() : loadScript(pluginUrl);

    return pluginReady.then(function () {
      return loadScript(brazilMapUrl);
    });
  }

  function drawBrazilMap($map) {
    $map.empty();
    $map.vectorMap({
      map: 'brazil_br',
      backgroundColor: 'transparent',
      borderColor: '#f4f4f4',
      borderOpacity: 0.8,
      borderWidth: 1,
      color: '#d2d6de',
      enableZoom: true,
      hoverColor: '#17a2b8',
      hoverOpacity: 0.7,
      normalizeFunction: 'polynomial',
      selectedColor: '#007bff',
      showTooltip: true,
      values: stateVisits,
      scaleColors: ['#c8e6c9', '#28a745'],
      onLabelShow: function (event, label, code) {
        if (stateVisits[code]) {
          label.text(label.text() + ': ' + stateVisits[code].toLocaleString('pt-BR') + ' visitas');
        }
      }
    });
  }

  function renderBrazilVisitorsMap(attempt) {
    const $map = $('#world-map-markers');

    if (!$map.length) {
      return;
    }

    if (!$map.width() && attempt < 10) {
      setTimeout(function () {
        renderBrazilVisitorsMap(attempt + 1);
      }, 150);
      return;
    }

    if ($.fn.vectorMap) {
      try {
        drawBrazilMap($map);
        return;
      } catch (error) {
        $map.empty();
      }
    }

    ensureBrazilMap().then(function () {
      drawBrazilMap($map);
    });
  }

  if (document.readyState === 'complete') {
    renderBrazilVisitorsMap(0);
  } else {
    $(window).on('load', function () {
      renderBrazilVisitorsMap(0);
    });
  }
})(jQuery);
