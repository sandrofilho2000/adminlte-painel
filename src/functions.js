/* $(document).on({
    ajaxStart: function(){
        $("body").addClass("loading"); 
    },
    ajaxStop: function(){ 
        $("body").removeClass("loading"); 
    }    
});
 */



/**
 * Obtém os dados do item selecionado por elemento da linha clicado. 
 * Ex.:
 * $('#tableLista tbody').on('click', '.linkEngrenagem', function(){
 *      selecionarItemTabela(tableLista, $(this));
 * });
 * @param {type} table variável com a instância da DataTable
 * @param {type} link linhas da tabela clicada, elemento tr
 * @returns {itemSelecionado}
 */

if (typeof csrfToken !== 'undefined') {
    $.ajaxSetup({
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-CSRF-Token', csrfToken);
        }
    });
} else {
    console.warn('csrfToken não definido. Requisições AJAX seguirão sem X-CSRF-Token.');
}

const controle = "/webconfef/controle/controle_default.php"
const campos_alterados = []

const WEBCONFEF_COLUMN_ORDER_STORAGE_PREFIX = "webconfef:datatable:colorder"
const WEBCONFEF_COLUMN_ORDER_PERSISTENCE_FLAG = "__webconfefColumnOrderPersistenceInstalled"

function getWebconfefColumnOrderStorageKey(settings) {
    const tableNode = settings && settings.nTable ? settings.nTable : null
    const tableId = tableNode && tableNode.id ? String(tableNode.id).trim() : ""

    if (!tableId) {
        return null
    }

    return `${WEBCONFEF_COLUMN_ORDER_STORAGE_PREFIX}:${window.location.pathname}:${tableId}`
}

function normalizeWebconfefColumnOrder(rawOrder, columnCount) {
    if (!Array.isArray(rawOrder) || rawOrder.length !== columnCount) {
        return null
    }

    const order = rawOrder.map(function (value) {
        return Number(value)
    })

    if (order.some(function (value) {
        return !Number.isInteger(value) || value < 0 || value >= columnCount
    })) {
        return null
    }

    const unique = new Set(order)
    if (unique.size !== columnCount) {
        return null
    }

    return order
}

function saveWebconfefColumnOrder(tableApi) {
    if (!tableApi || !tableApi.colReorder || typeof tableApi.colReorder.order !== "function") {
        return
    }

    const settings = tableApi.settings()[0]
    const storageKey = getWebconfefColumnOrderStorageKey(settings)
    if (!storageKey) {
        return
    }

    const order = normalizeWebconfefColumnOrder(tableApi.colReorder.order(), tableApi.columns().count())
    if (!order) {
        return
    }

    try {
        window.localStorage.setItem(storageKey, JSON.stringify(order))
    } catch (erro) {
    }
}

function loadWebconfefColumnOrder(storageKey, columnCount) {
    if (!storageKey) {
        return null
    }

    try {
        const raw = window.localStorage.getItem(storageKey)
        if (!raw) {
            return null
        }

        const parsed = JSON.parse(raw)
        return normalizeWebconfefColumnOrder(parsed, columnCount)
    } catch (erro) {
        return null
    }
}

function applyWebconfefColumnOrderPersistence(tableApi) {
    if (!tableApi || !tableApi.colReorder || typeof tableApi.colReorder.order !== "function") {
        return
    }

    const settings = tableApi.settings()[0]
    if (!settings || settings._webconfefColumnOrderPersistenceBound) {
        return
    }

    const storageKey = getWebconfefColumnOrderStorageKey(settings)
    if (!storageKey) {
        return
    }

    const savedOrder = loadWebconfefColumnOrder(storageKey, tableApi.columns().count())
    if (savedOrder) {
        const currentOrder = normalizeWebconfefColumnOrder(tableApi.colReorder.order(), tableApi.columns().count())
        const sameOrder = currentOrder && currentOrder.length === savedOrder.length && currentOrder.every(function (value, index) {
            return value === savedOrder[index]
        })

        if (!sameOrder) {
            try {
                tableApi.colReorder.order(savedOrder, true)
            } catch (erro) {
            }
        }
    }

    settings._webconfefColumnOrderPersistenceBound = true
    tableApi.off("column-reorder.webconfefColumnOrder")
    tableApi.on("column-reorder.webconfefColumnOrder", function () {
        saveWebconfefColumnOrder(tableApi)
    })
}

function installWebconfefColumnOrderPersistence() {
    if (window[WEBCONFEF_COLUMN_ORDER_PERSISTENCE_FLAG]) {
        return
    }

    if (!window.jQuery || !$.fn || !$.fn.dataTable || !$.fn.dataTable.Api || !$.fn.dataTable.defaults) {
        return
    }

    window[WEBCONFEF_COLUMN_ORDER_PERSISTENCE_FLAG] = true
    $.fn.dataTable.defaults.colReorder = true

    $(document)
        .off("init.dt.webconfefColumnOrderPersistence")
        .on("init.dt.webconfefColumnOrderPersistence", function (event, settings) {
            if (!settings || !settings.nTable || !settings.nTable.id) {
                return
            }

            window.setTimeout(function () {
                try {
                    applyWebconfefColumnOrderPersistence(new $.fn.dataTable.Api(settings))
                } catch (erro) {
                }
            }, 0)
        })

    try {
        $.fn.dataTable.tables({ api: true }).every(function () {
            applyWebconfefColumnOrderPersistence(this)
        })
    } catch (erro) {
    }
}

installWebconfefColumnOrderPersistence()

function ensureOverlay() {
    let overlay = document.getElementById("global-overlay")
    if (!overlay) {
        overlay = document.createElement("div")
        overlay.id = "global-overlay"

        overlay.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"></span>
            </div>
        `

        document.body.appendChild(overlay)

        const style = document.createElement("style")
        style.innerHTML = `
            #global-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.4);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }

            #global-overlay.active {
                display: flex;
            }
        `
        document.head.appendChild(style)
    }
    return overlay
}

function selecionarItemTabela(table, link) {
    var itemSelecionado = table.row($(link).parents('tr')).data();
    if (typeof itemSelecionado === 'undefined') {
        itemSelecionado = table.row($(link).parents('tr').prev('tr')).data();
    }
    tableSelecionada = table;
    return itemSelecionado;
}

function selecionarLinhaTabela(table, link) {
    var row = table.row($(link).parents('tr'));
    return row;
}

function atualizarDadosTabela(table, dados) {
    table.clear().rows.add(dados).draw();
}

function isNumeric(num) {
    return !isNaN(num)
}

function formatarData(dataStr) {
    dataStr = dataStr.split(" ")
    dataStr = dataStr[0]
    const [ano, mes, dia] = dataStr.split('-')
    return `${dia}/${mes}/${ano}`
}

function formatarDataHora(dataStr) {
    if (!dataStr) return ''

    const [data, horaCompleta = '00:00:00'] = dataStr.replace('T', ' ').split(' ')
    const [ano, mes, dia] = data.split('-')
    const hora = horaCompleta.split('.')[0] // remove milissegundos, se existir

    return `${dia}/${mes}/${ano} ${hora}`
}

function destroyTable(id) {
    id = "#" + id.replaceAll("#", "")
    if (!$.fn.DataTable.isDataTable(id)) return

    const tableEl = document.querySelector(id)
    if (!tableEl || !tableEl.parentNode) return

    try {
        const dataTable = $(id).DataTable()
        dataTable.clear()
        dataTable.destroy(false)
    } catch (error) {
        return
    }
}

function formatarClassificacaoContabil(valor) {
    if (!valor) return '';
    valor = valor.toString().replace(/\D/g, '');
    if (valor.length !== 11) return valor;
    return valor.replace(/^(\d)(\d)(\d)(\d)(\d{2})(\d{2})(\d{3})$/, '$1.$2.$3.$4.$5.$6.$7');
}

/**
 * Carrega o formulario com dados do objeto, desde que as propriedades do objeto tenham nome iguais aos nomes dos campos do formulario
 * @param {string} formId - id do formulario a ser carregado
 * @param {object} obj - objeto com os dados a serem carregados no formulário
 * @returns {undefined}
 */
function carregarForm(formId, obj, callback) {
    var name, value, type, mask, field;
    for (var i in obj) {
        name = i;
        value = obj[i];
        //campo no formato array
        if ($('#' + formId + ' [name="' + name + '[]"]').length) {
            name = name + '[]';
        }
        if (!$('#' + formId + ' [name="' + name + '"]').length || $('#' + formId + ' [name="' + name + '"]').hasClass('nao-carregar-edicao')) {
            continue;
        }
        type = $('#' + formId + ' [name="' + name + '"]').attr('type');
        if (typeof type === 'undefined') {
            type = $('#' + formId + ' [name="' + name + '"]').prop('nodeName');
        }
        field = $('#' + formId + ' [name="' + name + '"]');

        switch (type) {
            case 'SELECT':
                if (field.hasClass('selectpicker')) {
                    field.selectpicker('val', value);
                    if (field.find('option:selected').length === 0) {
                        field.selectpicker('val', JSON.parse(value));
                    }
                }
                else {
                    //$('#'+ formId +' [name="'+ name +'"]').selectpicker('val', value);//.trigger('change');
                    field.val(value);//.trigger('change');
                }
                break;
            case 'radio':
            case 'checkbox':
                var $checkboxes = $('#' + formId + ' input[name="' + name + '"][type="checkbox"]');
                if ($checkboxes.length > 1) {
                    var wanted = new Set();
                    if (Array.isArray(value)) {
                        value.forEach(function (v) { wanted.add(String(v)); });
                    } else if (value && typeof value === 'object') {
                        Object.values(value).forEach(function (v) { wanted.add(String(v)); });
                    } else if (typeof value === 'string' && value.indexOf(',') > -1) {
                        value.split(',').map(function (v) { return v.trim(); }).filter(Boolean).forEach(function (v) { wanted.add(v); });
                    } else if (value !== undefined && value !== null) {
                        wanted.add(String(value));
                    }
                    $checkboxes.each(function () {
                        var $cb = $(this);
                        $cb.prop('checked', wanted.has(String($cb.val())));
                    });
                } else {
                    var valBool = false;
                    if (Array.isArray(value)) {
                        valBool = value.length > 0;
                    } else if (value && typeof value === 'object') {
                        valBool = Object.keys(value).length > 0;
                    } else if (typeof value === 'string') {
                        var s = value.toLowerCase().trim();
                        valBool = (s === '1' || s === 'true' || s === 'on' || s === 'yes');
                    } else if (typeof value === 'number') {
                        valBool = value !== 0;
                    } else if (typeof value === 'boolean') {
                        valBool = value;
                    }
                    $checkboxes.prop('checked', valBool);
                }
                break;

            case 'hidden':
            case 'text':
            case 'email':
            case 'color':
            case 'number':
            case 'TEXTAREA':
            case 'date':
            case 'datetime-local':
                if (field.hasClass('formato-data') && moment(value).isValid()) {
                    field.val(moment(value).format('DD/MM/YYYY'));
                }
                else if (field.hasClass('datepicker') && moment(value).isValid()) {
                    mask = field.attr('formato-data');
                    field.datepicker('setDate', moment(value).format(mask));
                }
                else if (typeof field.attr('data-datetimepicker') === 'string') {
                    mask = field.attr('data-datetimepicker');
                    field.val(moment(value).format(mask));
                }
                else if (field.hasClass('moeda')) {
                    field.val(value.replace('.', ','));
                }
                else {
                    field.val(value);
                }
                break;

            default:
                field.val(value);
        }

    }

    formId = formId.replaceAll("#")
    var pkName = $('#' + formId + ' input[name="id_objeto"]').attr('data-pkName');
    //console.log('pkName: '+ pkName);
    //console.log('typeof pkName: '+ typeof pkName);
    //console.log('length: '+ $('#'+ formId +' input[name="id_objeto"]').length);
    if (typeof pkName !== 'undefined' && $('#' + formId + ' input[name="id_objeto"]').length) {
        //console.log('carregando id_objeto com a propriedade '+ pkName +' de valor '+ obj[pkName]);
        //console.log(obj);
        $('#' + formId + ' input[name="id_objeto"]').val(obj[pkName]);
    }
    //aplica a mascara nos campos moeda que utilizam a propriedade 
    $('#' + formId + ' input[data-maskmoney]').trigger('mask.maskMoney');

    if (typeof callback === 'function') {
        callback();
    }
}
/**
 * Considerando o padrao de blocos em fieldsets, oculta os fieldsets visiveis para exibir um especifico
 * @param {type} fieldsetId id do fieldset a ser exibido
 * @returns {undefined}
 */
function exibirFieldset(fieldsetId) {
    $('fieldset:visible').fadeOut(function () {
        $('#' + fieldsetId).fadeIn();
    });
}
/**
 * Reajusta a largura das colunas de modo que fique nas dimensoes definidas inicialmente
 */
function tableReajustarColunas(table, time) {
    var delay = (typeof time === 'undefined') ? '500' : time;
    window.setTimeout(function () {
        table.columns.adjust().draw();
    }, delay);
}
/**
 * Funcao padrao de envio de dados por formulario para incluir ou alterar
 * @param {type} frmId
 * @param {type} instanceTable
 * @param {type} callbackSuccess
 * @param {type} acaoName
 * @returns {Boolean}
 */


function gravarFrm(frmId, instanceTable, callback, acaoName) {
    const form = (typeof frmId === 'undefined') ? $('#frmDefault') : $('#' + frmId);
    var id_objeto = form.find('[name=id_objeto]').val();
    var acao = form.find('[name=acao]').val();
    if (typeof acaoName !== 'undefined') {
        acao = acaoName;
    }
    if (form.find('input[name=acao]').val() === 'execute') {
        acao = 'execute';
    }
    //console.log('id_objeto: '+ form.find('input[name=id_objeto]').val());
    //return false;
    form.find('input[name=acao]').val(acao);
    form.find('button[type=submit]').attr('disabled', 'disabled');
    form.find('img.loading').fadeIn();
    form.find('div.msg').html('');
    if (typeof instanceTable !== 'undefined') {
        table = instanceTable;
    }
    if (typeof controle === 'undefined') {
        /* alertSW('Arquivo de controle da requisição está idefinido', 'error'); */
        return false;
    }
    $.ajax({
        url: controle,
        method: 'post',
        data: form.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.tipo === 'success' && isEmpty(form.find('[name=id_objeto]').val())) {
                limparForm(frmId);
                //form.find('input[name=acao]').val(acao);
            }
            if (typeof callback === 'function') {
                callback(result);
            }
            if (result.tipo === 'success' && typeof table !== 'undefined' && !isEmpty(table)) {
                table.ajax.reload(null, false);
            }
            //exibirMensagemResultante(form.find('div.msg'), result);
            /* alertSWl(result.texto, result.tipo); */
        },
        complete: function () {
            form.find('button[type=submit]').removeAttr('disabled');
            form.find('img.loading').fadeOut();
            try {
                if (typeof table !== 'undefined' && !isEmpty(table)) {
                    window.setTimeout(function () {
                        //setStatusButtonsDataTableDefault(table);
                        tableReajustarColunas(table);
                    }, 1000);
                }
            } catch (e) {
                console.log(e);
            }
        },
        error: function (erro, er) {
            var msg = 'Erro ' + erro.status + ' - ' + erro.statusText + ' (Tipo de erro: ' + er + ')';
            alert(msg);
        }
    });
    return false;
}

async function gravarFrmAsync(frmId, instanceTable, callback, acaoName) {
    const form = (typeof frmId === 'undefined') ? $('#frmDefault') : $('#' + frmId);
    var id_objeto = form.find('[name=id_objeto]').val();
    var acao = isEmpty(id_objeto) ? 'incluir' : 'salvar';

    if (typeof acaoName !== 'undefined') {
        acao = acaoName;
    }
    if (form.find('input[name=acao]').val() === 'execute') {
        acao = 'execute';
    }

    form.find('input[name=acao]').val(acao);
    form.find('button[type=submit]').attr('disabled', 'disabled');

    if (typeof instanceTable !== 'undefined') {
        table = instanceTable;
    }
    if (typeof controle === 'undefined') {
        return false;
    }

    try {
        const result = await new Promise((resolve, reject) => {
            $.ajax({
                url: controle,
                method: 'post',
                data: form.serialize(),
                dataType: 'json',
                success: function (result) {
                    if (result.tipo === 'success' && isEmpty(form.find('[name=id_objeto]').val())) {
                        limparForm(frmId);
                    }
                    if (typeof callback === 'function') {
                        callback(result);
                    }
                    if (result.tipo === 'success' && typeof table !== 'undefined' && !isEmpty(table)) {
                        table.ajax.reload(null, false);
                    }
                    resolve(result);
                },
                complete: function () {
                    form.find('button[type=submit]').removeAttr('disabled');
                    try {
                        if (typeof table !== 'undefined' && !isEmpty(table)) {
                            window.setTimeout(function () {
                                tableReajustarColunas(table);
                            }, 1000);
                        }
                    } catch (e) {
                        console.log(e);
                    }
                },
                error: function (erro, er) {
                    var msg = 'Erro ' + erro.status + ' - ' + erro.statusText + ' (Tipo de erro: ' + er + ')';
                    alert(msg);
                    reject(msg);
                }
            });
        });

        return result;
    } catch (err) {
        console.error("Erro no gravarFrmAsync:", err);
        return false;
    }
}


function frmCarregarPk(frmId, obj) {
    let pkName = $('#' + frmId).find('input[name="id_objeto"]').attr('data-pkName');
    if (!obj.hasOwnProperty(pkName)) {
        /* alertSWl('Propriedade não encontrada no objeto. ('+ pkName +')', 'error'); */
    }
    let id = obj[pkName];
    $('#' + frmId).find('input[name="id_objeto"]').val(id);
}

function isEmpty(valor) {
    if (valor === null || valor === 'null' || $.trim(valor) === '' || valor === 'undefined') {
        return true;
    }
    return false;
}

function ifEmpty(valor, alternativo) {
    if (valor === null || valor === 'null' || $.trim(valor) === '') {
        return alternativo;
    }
    return valor;
}

function limparCamposResetaveis(form) {
    form.find('.resetavel').val('');
    form.find('.selectpicker').selectpicker('val', '');
    form.find('input[type=checkbox]:visible').prop('checked', false);
    form.find('input[type=radio]:visible').prop('checked', false);
}

function limparForm(frmId, callback) {
    const form = (typeof frmId === 'undefined') ? $('#frmDefault') : $('#' + frmId);
    form[0].reset();
    form.find('input.resetavel').val('');
    form.find('input[name=id_objeto]').val('');
    try {
        form.find('select').val('');
        form.find('.selectpicker').selectpicker('val', '');
        form.find('.selectpicker').selectpicker('refresh');
        form.find('.msg').html('');
    } catch (e) {

    }
    if (callback === 'function') {
        callback();
    }
}

function fecharModal() {
    /*s$('div.modal').fadeOut(function(){
        $(this).find('div.modal-container').hide();
    });*/
    $('div.modal:visible').find('div.modal-container').slideUp(200, function () {
        $('div.modal:visible').fadeOut();
    });
}

function abrirModal(id) {
    $('#' + id).fadeIn(function () {
        $(this).find('div.modal-container').slideDown(250);
    });
}

function exibirMsgPendenciaForm(target, msg, input) {
    var msgObj = {
        tipo: 'error',
        texto: msg,
        html: '<div class="alert alert-danger">' + msg + '</danger>'
    };
    var time = 8000;
    if (typeof input !== 'undefined') {
        exibirPendenciaCampo(input);
        /*input.addClass('is-invalid');
        window.setTimeout(function(){
            input.removeClass('is-invalid');
        }, time);*/
    }
    exibirMensagemResultante(target, msgObj, null, null, time);
}

function exibirPendenciaCampo(input) {
    input.addClass('is-invalid');
    window.setTimeout(function () {
        input.removeClass('is-invalid');
    }, 8000);
}

function exibirGritterResultante(objJson) {
    var icone = 'disquete.png';
    if (objJson.tipo !== 'success') {
        icone = 'exclamacao.png';
    }
    gerarGritter('Resultado', objJson.texto, icone);
    return objJson.tipo;
}

function gerarGritter(title, text, type, time) {
    var icone = (type === 'success') ? 'disquete.png' : 'exclamacao.png';
    var image;
    try {
        image = $('img[alt="logo-csa"]').attr('src').replace('logo-2.png', icone);
    }
    catch (e) {
        console.log(e);
    }
    if (typeof time === 'undefined') {
        time = 8000;
    }
    $.gritter.add({
        title: title,
        text: text,
        image: image,
        /*class_name: 'gritter-light',*/
        time: time
    });
    $('#modal-message').remove();
}

function gerarGritterResultante(obj, dir_root_image, time) {
    let icone = isEmpty(dir_root_image) ? '' : dir_root_image;
    icone += (obj.tipo === 'success') ? 'img/disquete.png' : 'img/exclamacao.png';
    if (typeof time === 'undefined') {
        time = 4000;
    }
    $.gritter.add({
        title: 'Resultado',
        text: obj.texto,
        image: icone,
        time: time
    });
}

function alertSwResult(result) {
    /* alertSWl(result.texto, result.tipo); */
}

function alertSwAddText(text) {
    window.setTimeout(function () {
        $('#swal2-html-container').append(text);
    }, 100);
}

function alertSwReplace(text) {
    window.setTimeout(function () {
        $('#swal2-html-container').html(text);
    }, 100);
}
/**
 * Abreviar nomes sem abreviar o primeiro e último nome
 * @param {type} fullName
 * @param {type} limit
 * @returns {unresolved}
 */
function abreviarNome(fullName, limit) {
    fullName = fullName.trim();
    if (fullName.length > limit) {
        var nomes = fullName.split(' ');
        var indiceAtual = nomes.length - 2;
        var proibirAbreviacao = ['DA', 'DE', 'DO', 'DOS', 'E', 'da', 'de', 'do', 'dos', 'e'];
        while (indiceAtual > 0) {
            if (proibirAbreviacao.indexOf(nomes[indiceAtual]) === -1) {
                nomes[indiceAtual] = nomes[indiceAtual].substr(0, 1) + '.';
            }
            fullName = nomes.join(' ');
            if (fullName.length <= limit) {
                return fullName;
            }
            indiceAtual--;

        }
    }
    return fullName;
}

function primeiroUltimoNome(fullname) {
    var names = fullname.split(' ');
    var saida = names[0];
    var last = names.length - 1;
    saida += ' ' + names[last];
    return saida;
}

function nl2br(str, is_xhtml) {
    if (typeof str === 'undefined' || str === null) {
        return '';
    }
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

function alterarItem(texto, dados, url, callbackSuccess) {
    confirmSW(texto, function () {
        requisicaoAjax(dados, function (result) {
            /* alertSWl(result.texto, result.tipo); */
            if (typeof callbackSuccess === 'function' && result.tipo === 'success') {
                callbackSuccess(result);
            }
        }, null, null, url);
    }, function () {
        /* alertSWl('Ok, nenhuma alteração foi realizada.', 'info'); */
    });
}

function showOverlay() {
    ensureOverlay().classList.add("active")
}

function hideOverlay() {
    const overlay = document.getElementById("global-overlay")
    if (overlay) overlay.classList.remove("active")
}


function requestAjax(dados, callback, loading = true) {
    if (loading) {
        showOverlay()
    }

    const url = controle;
    const isFormData = dados instanceof FormData;
    const $controles = $("input[type=submit], input[type=button], button[type=submit], button[type=button]").not(".logout-btn");

    $controles.each(function () {
        const $controle = $(this)
        const contador = Number($controle.data("requestAjaxDisableCount") || 0)

        if (contador === 0) {
            $controle.data("requestAjaxWasDisabled", $controle.prop("disabled"))
        }

        $controle.data("requestAjaxDisableCount", contador + 1)
        $controle.prop("disabled", true)
    })

    const token = (typeof csrfToken !== 'undefined' && csrfToken) || $('meta[name="csrf-token"]').attr('content') || '';

    return $.ajax({
        url: url,
        method: 'post',
        data: dados,
        dataType: 'json',
        processData: !isFormData,
        contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
        headers: token ? { 'X-CSRF-Token': token } : {},
        success: function (result) {
            if (result && typeof result.message !== 'undefined') {
                alert(result.message);
            }
            if (typeof callback === 'function') {
                callback(result);
            }
        },
        error: function (erro, er) {
            let msg;
            if (erro.status == 403) {
                msg = 'Erro desconhecido, tente novamente em instantes.';
            }
            else if (erro.status == 0) {
                msg = 'Erro de rede ou URL inválida. Verifique a conexão ou o caminho da requisição.';
            }
            else if (erro.status == 500 && erro.responseJSON && erro.responseJSON.erro) {
                msg = erro.responseJSON.erro;
            }
            else {
                msg = 'Erro ' + erro.status + ' - ' + erro.statusText + ' (Tipo de erro: ' + er + ')';
            }
            alert(msg);
        },
        complete: function () {
            $controles.each(function () {
                const $controle = $(this)
                const contador = Number($controle.data("requestAjaxDisableCount") || 0)

                if (contador > 1) {
                    $controle.data("requestAjaxDisableCount", contador - 1)
                    return
                }

                $controle.prop("disabled", Boolean($controle.data("requestAjaxWasDisabled")))
                $controle.removeData("requestAjaxWasDisabled")
                $controle.removeData("requestAjaxDisableCount")
            })
            hideOverlay()
        }
    });
}


function requestAjaxAsync(dados, loading = true) {
    return new Promise((resolve, reject) => {
        requestAjax(dados, resolve, loading);
    });
}

function validaTamanhoTotalArquivos($form, limiteMB = 60) {
    let totalSize = 0

    $form.find('input[type="file"]').each(function () {
        if (this.files && this.files.length > 0) {
            for (const file of this.files) {
                totalSize += file.size
            }
        } else {
            const $modal = $(this).closest('.modal-pdf')
            const arquivo = $modal.data("arquivo")
            if (arquivo) totalSize += arquivo.size
        }
    })

    const limiteBytes = limiteMB * 1024 * 1024

    if (totalSize > limiteBytes) {
        const totalMB = (totalSize / 1024 / 1024).toFixed(2)
        alert(`O tamanho total dos arquivos (${totalMB}MB) não pode ultrapassar ${limiteMB}MB.`)
        return false
    }

    return true
}

function requisicaoAjaxArquivo(dados, callback, url) {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    for (const key in dados) {
        formData.append(key, dados[key]);
    }

    xhr.open('POST', url, true);
    xhr.responseType = 'blob'; // importante para PDFs

    xhr.onload = function () {
        if (xhr.status === 200) {
            const pdfBlob = xhr.response; // ✅ Agora a variável está corretamente definida
            callback(pdfBlob);
        } else {
            alert('Erro ao obter contrato: ' + xhr.status);
        }
    };

    xhr.onerror = function () {
        alert('Erro de rede ao obter contrato.');
    };

    xhr.send(formData);
}

function atualizarLinhasDataTable(table, dados, columnName, propertyName) {
    alert('revisar, parece não estar funcionando');
    var obj;
    for (var i = 0; i < dados.length; i++) {
        obj = dados[i];
        table.rows().every(function (rowIdx, tableLoop, rowLoop) {
            var newData = this.data();
            console.log(newData[columnName] + ' === ' + obj[propertyName]);
            if (newData[columnName] === obj[propertyName]) {
                newData[columnName] = obj[propertyName];
                table.row(rowIdx).data(newData).draw();
            }
        });
    }
}

function ordenar(a, b) {
    if (typeof campoOrdenacao === 'undefined') {
        /* alertSWl('Uso incorreto da função <b>ordenar</p>. É necessário definir o valor na variável <b>campoOrdenacao</b> para que a função saiba qual propriedade deve ser comparada para a ordenação.', 'error'); */
        return false;
    }
    if (a[campoOrdenacao] < b[campoOrdenacao]) {
        return -1;
    }
    if (a[campoOrdenacao] > b[campoOrdenacao]) {
        return 1;
    }
    return 0;
}

function scrollToElement(id) {
    let container = $('body');
    let scrollTo = $('#' + id);
    // Calculating new position of scrollbar
    let position = scrollTo.offset().top - container.offset().top + container.scrollTop();
    // Setting the value of scrollbar
    container.scrollTop(position);
}

function desmarcarCheckbox(checkbox) {
    if (checkbox.is(':checked')) {
        checkbox.click();
    }
}

function updateSlimscroll(element) {
    let height = window.innerHeight - 150;
    element.slimscroll({
        height: height
    });
}

function removerFormatoMoeda(valor) {
    if (typeof valor === 'string') {
        valor = valor.replace('R$ ', '').replace('.', '').replace(',', '.');
        return parseFloat(valor).toFixed(2);
    }
    else {
        if (typeof valor === 'number') {
            return parseFloat(valor).toFixed(2);
        }
        else {
            return 0.00;
        }
    }
}

function formatMoeda(valor) {
    return Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(valor);
}

function appendSubtituloPagina(texto) {
    $('#sub-titulo-pagina').append('<i class="bi bi-chevron-double-right mr-2 f-s-16"></i><span class="text-primary">' + texto + '</span>');
}

function adicionarSubtituloPagina(texto) {
    $('#sub-titulo-pagina').html('<i class="bi bi-chevron-double-right mr-2 f-s-16"></i><span class="text-primary">' + texto + '</span>');
}

function removerSubtitulo() {
    $('#sub-titulo-pagina').text('');
}

function alterarTituloPagina(html) {
    $('#titulo-pagina').html(html);
}

function setTituloPagina(texto) {
    $('#titulo-pagina').text(texto);
    removerSubtitulo();
}

function getPopover(texto, posicao) {
    let pos = (typeof posicao === 'undefined') ? 'left' : posicao;
    let saida = ' data-toggle="popover" data-placement="' + pos + '" data-content="' + texto + '"';
    return saida;
}

function gravarParametro(name, value) {
    let dados = {
        objeto: 'Parametro',
        metodo: 'gravar',
        tx_nome: name,
        tx_valor: value
    };
    requestAjax(dados, function (response) {
        alertSwResult(response);
    });
    return false;
}

function getParametro(name, callback) {
    let dados = {
        objeto: 'Parametro',
        'filtros[IGUAL][tx_nome]': name
    };
    requestAjax(dados, function (response) {
        let saida = null;
        if (response.tipo === 'success' && response.dados.length) {
            saida = JSON.parse(response.dados[0]['tx_valor']);
        }
        if (typeof (callback) === 'function') {
            callback(saida);
        }
    });
}

function capitalizeFirstLetter(val) {
    return String(val).charAt(0).toUpperCase() + String(val).slice(1);
}

function gerarId(prefix = '') {
    const timestamp = Date.now().toString(36);
    const random = Math.floor(Math.random() * 1e6).toString(36);
    return `${prefix}${timestamp}_${random}`;
}

function gerarNumId() {
    const random = Math.floor(Math.random() * 1e6);
    return random;
}


function ParseFloat(str) {
    str = str.toString();
    if (str.split(".").length != 1) {
        str = str.slice(0, (str.indexOf(".")) + 2 + 1);
    }
    return Number(str);
}

function passToCurrency(num = 0) {
    if (typeof num === "string") {
        if (num.includes("R$ ")) {
            return num
        }
    }
    num = Number(num).toFixed(2)
    if (isNaN(num)) {
        return 0
    }

    var str_split = String(num).split('.');
    let valor = num ? String(num) : '0';
    valor = valor.replaceAll('.', '');
    valor = valor.replaceAll(',', '.');
    valor = valor.replaceAll('R$', '');
    valor = Number(valor.trim());

    /*     if (str_split.slice(-1)[0].length == 3) {
          valor = valor / 1000;
        } */

    if (str_split.slice(-1)[0].length == 2) {
        valor = valor / 100;
    }

    if (str_split.slice(-1)[0].length == 1) {
        valor = valor / 10;
    }

    let newValor = valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

    return newValor;
};

function passToNumber(str = "0") {
    str = String(str)
    str = str.replace(/[^\d.,]/g, '')

    let number = 0
    let str_has_commas = str.split(",").length > 1
    let str_has_dots = str.split(".").length > 1

    if (str_has_commas && !str_has_dots) {
        number = Number(str.replaceAll(",", "."))
    }
    else if (!str_has_commas && str_has_dots) {
        number = ParseFloat(str)
    }
    else if (str_has_commas && str_has_dots) {
        number = str.replaceAll(".", "*")
        number = number.replaceAll(",", ".")
        number = number.replaceAll("*", "")
    }
    else {
        number = str // aqui trata "200", "5000", etc
    }

    number = Number(number)
    number = ParseFloat(number)
    return number
}

function slugify(text) {
    return text
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
}

function tempoDecorrido(dataString) {
    const agora = new Date();
    const data = new Date(dataString.replace(' ', 'T'));

    const diffMs = agora - data;

    const segundos = Math.floor(diffMs / 1000);
    const minutos = Math.floor(segundos / 60);
    const horas = Math.floor(minutos / 60);
    const dias = Math.floor(horas / 24);
    const semanas = Math.floor(dias / 7);
    const meses = Math.floor(dias / 30);
    const anos = Math.floor(dias / 365);

    if (segundos < 10) return "agora mesmo";
    if (segundos < 60) return `${segundos} segundos`;
    if (minutos < 60) return `${minutos} minuto${minutos > 1 ? 's' : ''}`;
    if (horas < 24) return `${horas} hora${horas > 1 ? 's' : ''}`;
    if (dias < 7) return `${dias} dia${dias > 1 ? 's' : ''}`;
    if (semanas < 5) return `${semanas} semana${semanas > 1 ? 's' : ''}`;
    if (meses < 12) return `${meses} mês${meses > 1 ? 'es' : ''}`;
    return `${anos} ano${anos > 1 ? 's' : ''}`;
}

const COLOR_MAP = {
    success: "#28a745",
    info: "#17a2b8",
    warning: "#ffc107",
    danger: "#dc3545",
    maroon: "#d81b60",
    primary: "#007bff",
    secondary: "#6c757d",
    "gray-dark": "#343a40",
    gray_dark: "#343a40",
    orange: "#ff851b"
}

const RANDOM_TITLES = [
    "Atualização concluída",
    "Nova atividade",
    "Ação necessária",
    "Lembrete importante",
    "Status alterado",
    "Sincronização finalizada"
]

const RANDOM_MESSAGES = [
    "Há um novo evento aguardando revisão.",
    "Um item foi atualizado por outro usuário.",
    "Verifique os detalhes para continuar o fluxo.",
    "Existe um prazo próximo para esta tarefa.",
    "Uma confirmação foi registrada com sucesso.",
    "O processo foi movido para a próxima etapa."
]

const RANDOM_BUTTONS = [
    { label: "Abrir", url: "#", target: "_self" },
    { label: "Ver detalhes", url: "#", target: "_self" },
    { label: "Acompanhar", url: "#", target: "_self" },
    { label: "Revisar", url: "#", target: "_self" }
]

const RANDOM_COLORS = Object.keys(COLOR_MAP)
const CONTAINER_ID = "global-app-toast-container"
const STYLE_ID = "global-app-toast-style"
const DEFAULT_DURATION = 5500
const NOTIFICATION_SOUND_STORAGE_KEY = "app_toast_notifications_muted"
const SECOND_MS = 1000
const MINUTE_MS = 60 * SECOND_MS
const HOUR_MS = 60 * MINUTE_MS
const DAY_MS = 24 * HOUR_MS
const MONTH_MS = 30 * DAY_MS
const YEAR_MS = 365 * DAY_MS
const NOTIFICATION_START_DELAY_MS = 2 * SECOND_MS
const NOTIFICATION_POLL_INTERVAL_MS = 45 * SECOND_MS
const NOTIFICATION_TITLE_BLINK_DURATION_MS = 1 * SECOND_MS
const NOTIFICATION_TITLE_BLINK_INTERVAL_MS = 1000
const NOTIFICATION_SOUND_URL = "/webconfef/src/notification.mp3"
const NOTIFICATION_HIGHLIGHT_SOUND_URL = "/webconfef/src/notificacao_destaque.mp3"
const NOTIFICATION_SOUND_VOLUME = 0.2
const TOAST_TITLE_MAX_LENGTH = 90
const TOAST_MESSAGE_MAX_LENGTH = 320
const NOTIFICATION_HIGHLIGHT_MODAL_ID = "global-notification-highlight-modal"
const NOTIFICATION_HIGHLIGHT_STYLE_ID = "global-notification-highlight-style"
const NOTIFICATION_HIGHLIGHT_CARD_VARIANTS = [
    "card-primary",
    "card-secondary",
    "card-success",
    "card-info",
    "card-warning",
    "card-danger",
    "card-light",
    "card-dark",
    "card-maroon"
]
const notificacoesExibidas = new Set()
let notificacoesPollingTimer = null
let notificacoesStartTimeout = null
let notificacoesRequestEmAndamento = false
let notificacoesInteracaoInicialRegistrada = false
let notificacoesMutadas = lerPreferenciaMuteNotificacoes()
let notificacoesDestaqueFila = []
let notificacaoDestaqueAtual = null
let notificacaoDestaqueFechando = false
let notificacaoDestaqueBodyOverflowAnterior = ""
let notificationToastAudio = null
let notificationDestaqueAudio = null
let notificationTitleOriginal = ""
let notificationTitleBlinkTimer = null
let notificationTitleRestoreTimer = null
let notificationTitleBlinkShowingAlert = false

function escapeHtml(value) {
    return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;")
}

function truncateWithEllipsis(value, maxLength) {
    const text = String(value || "")
    const limit = Number(maxLength)

    if (!Number.isFinite(limit) || limit <= 0 || text.length <= limit) {
        return text
    }

    return `${text.slice(0, Math.max(0, limit - 3)).trimEnd()}...`
}

function randomItem(list) {
    if (!Array.isArray(list) || !list.length) {
        return null
    }
    const idx = Math.floor(Math.random() * list.length)
    return list[idx]
}

function ensureStyle() {
    if (document.getElementById(STYLE_ID)) {
        return
    }

    const style = document.createElement("style")
    style.id = STYLE_ID
    style.textContent = `
            #${CONTAINER_ID} {
                position: fixed;
                right: 16px;
                bottom: 16px;
                z-index: 1090;
                width: min(360px, calc(100vw - 24px));
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
            }

            #${CONTAINER_ID} .app-toast {
                background: #fff;
                border-radius: 4px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                border: 1px solid rgba(0, 0, 0, 0.1);
                overflow: hidden;
                opacity: 0;
                transform: translateY(12px);
                transition: opacity .25s ease, transform .25s ease;
                pointer-events: auto;
            }

            #${CONTAINER_ID} .app-toast.show {
                opacity: 1;
                transform: translateY(0);
            }

            #${CONTAINER_ID} .app-toast-inner {
                display: flex;
                min-height: 72px;
            }

            #${CONTAINER_ID} .app-toast-accent {
                width: 5px;
                flex: 0 0 5px;
            }

            #${CONTAINER_ID} .app-toast-content {
                flex: 1;
                padding: 10px 12px;
            }

            #${CONTAINER_ID} .app-toast-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 6px;
            }

            #${CONTAINER_ID} .app-toast-header-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-left: 10px;
                flex-shrink: 0;
            }

            #${CONTAINER_ID} .app-toast-heading {
                display: flex;
                flex-direction: column;
                min-width: 0;
            }

            #${CONTAINER_ID} .app-toast-title {
                color: #495057;
                font-weight: 700;
                font-size: 14px;
                line-height: 1.2;
            }

            #${CONTAINER_ID} .app-toast-time {
                color: #6c757d;
                font-size: 11px;
                line-height: 1.2;
                margin-top: 2px;
            }

            #${CONTAINER_ID} .app-toast-message {
                color: #495057;
                font-size: 13px;
                line-height: 1.35;
            }

            #${CONTAINER_ID} .app-toast-actions {
                margin-top: 10px;
            }

            #${CONTAINER_ID} .app-toast-close {
                background: transparent;
                border: 0;
                color: #6c757d;
                font-size: 18px;
                line-height: 1;
                cursor: pointer;
                padding: 0;
                margin-left: 10px;
            }

            #${CONTAINER_ID} .app-toast-sound-toggle {
                background: transparent;
                border: 0;
                color: #6c757d;
                font-size: 11px;
                font-weight: 600;
                line-height: 1.2;
                cursor: pointer;
                padding: 0;
                text-transform: uppercase;
                letter-spacing: 0.02em;
            }

            #${CONTAINER_ID} .app-toast-sound-toggle.is-muted {
                color: #dc3545;
            }

            #bell-header {
                gap: 12px;
            }

            #bell-header .app-bell-header-text {
                flex: 1;
                min-width: 0;
                white-space: normal;
                text-align: left;
            }

            #bell-header .app-bell-toast-toggle {
                flex-shrink: 0;
                padding-left: 2.2rem;
                min-height: 1.1rem;
            }

            #bell-header .app-bell-toast-toggle .custom-control-label {
                margin-bottom: 0;
                color: #6c757d;
                cursor: pointer;
                user-select: none;
                white-space: nowrap;
            }

            #bell-list .app-bell-notification-item {
                position: relative;
                white-space: normal;
                padding-left: 14px;
            }

            html body #bell-list .dropdown-item:active {
                background-color: transparent !important;
                color: #212529 !important;
            }

            #bell-list .app-bell-notification-item::before {
                content: "";
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: var(--notification-accent, ${COLOR_MAP.primary});
                border-radius: 2px 0 0 2px;
            }
        `

    document.head.appendChild(style)
}

function ensureContainer() {
    let container = document.getElementById(CONTAINER_ID)
    if (!container) {
        container = document.createElement("div")
        container.id = CONTAINER_ID
        document.body.appendChild(container)
    }
    return container
}

function ensureHighlightStyle() {
    let style = document.getElementById(NOTIFICATION_HIGHLIGHT_STYLE_ID)
    if (!style) {
        style = document.createElement("style")
        style.id = NOTIFICATION_HIGHLIGHT_STYLE_ID
        document.head.appendChild(style)
    }

    style.textContent = `
            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} {
                position: fixed;
                inset: 0;
                z-index: 2150;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: rgba(15, 23, 42, 0.58);
                opacity: 0;
                pointer-events: none;
                transition: opacity .2s ease;
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID}.is-visible {
                opacity: 1;
                pointer-events: auto;
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} [data-notification-highlight-card],
            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} .notification-highlight-card {
                max-height: min(90vh, 900px);
                overflow: hidden;
                transform: translateY(16px) scale(0.98);
                opacity: 0;
                transition: opacity .2s ease, transform .2s ease;
                display: flex;
                flex-direction: column;
                position: relative;
                min-width: 0;
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID}.is-visible [data-notification-highlight-card],
            #${NOTIFICATION_HIGHLIGHT_MODAL_ID}.is-visible .notification-highlight-card {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} .notification-highlight-card .card-body {
                flex: 1 1 auto;
                min-height: 0;
                overflow: auto;
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} .notification-highlight-card .card-body img {
                max-width: 100%;
                height: auto;
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} .notification-highlight-card .card-body table {
                max-width: 100%;
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} .notification-highlight-card .card-body p:last-child {
                margin-bottom: 0;
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} .notification-highlight-actions.d-none {
                display: none !important;
            }

            #${NOTIFICATION_HIGHLIGHT_MODAL_ID} .notification-highlight-card .card-body .notification-highlight-plain-text {
                margin-bottom: 0;
            }

            @media (max-width: 576px) {
                #${NOTIFICATION_HIGHLIGHT_MODAL_ID} {
                    padding: 12px;
                }

                #${NOTIFICATION_HIGHLIGHT_MODAL_ID} [data-notification-highlight-card],
                #${NOTIFICATION_HIGHLIGHT_MODAL_ID} .notification-highlight-card {
                    width: calc(100vw - 24px);
                }
            }
        `
}

function obterClasseCardDestaque(cor) {
    const valor = String(cor || "").trim().toLowerCase()
    if (!valor) {
        return "card-primary"
    }

    return valor.indexOf("card-") === 0 ? valor : `card-${valor}`
}

function obterClasseBotaoDestaque(cor) {
    const valor = String(cor || "").trim().toLowerCase()
    if (!valor) {
        return "btn-primary"
    }

    return valor.indexOf("btn-") === 0 ? valor : `btn-${valor}`
}

function aplicarClassesVariantes(elemento, classesBase, classeNova) {
    if (!elemento) {
        return
    }

    classesBase.forEach(function (classe) {
        elemento.classList.remove(classe)
    })

    if (classeNova) {
        elemento.classList.add(classeNova)
    }
}

function getColor(colorName) {
    if (!colorName) {
        return COLOR_MAP.primary
    }

    return COLOR_MAP[colorName] || colorName
}

function getTimestamp(value) {
    if (value instanceof Date) {
        const dateTs = value.getTime()
        return Number.isFinite(dateTs) ? dateTs : Date.now()
    }

    if (Number.isFinite(Number(value))) {
        return Number(value)
    }

    if (typeof value === "string" && value.trim()) {
        const rawValue = value.trim()
        const normalizedValue = rawValue.indexOf(" ") !== -1 ? rawValue.replace(" ", "T") : rawValue

        const parsedNormalized = Date.parse(normalizedValue)
        if (Number.isFinite(parsedNormalized)) {
            return parsedNormalized
        }

        const parsedRaw = Date.parse(rawValue)
        if (Number.isFinite(parsedRaw)) {
            return parsedRaw
        }
    }

    return Date.now()
}

function formatRelativeTime(value) {
    const createdAt = getTimestamp(value)
    const now = Date.now()
    const diff = Math.max(0, now - createdAt)

    if (diff < 5 * SECOND_MS) {
        return "agora"
    }

    if (diff < MINUTE_MS) {
        const seconds = Math.floor(diff / SECOND_MS)
        return `há ${seconds} ${seconds === 1 ? "segundo" : "segundos"}`
    }

    if (diff < HOUR_MS) {
        const minutes = Math.floor(diff / MINUTE_MS)
        return `há ${minutes} ${minutes === 1 ? "minuto" : "minutos"}`
    }

    if (diff < DAY_MS) {
        const hours = Math.floor(diff / HOUR_MS)
        return `há ${hours} ${hours === 1 ? "hora" : "horas"}`
    }

    if (diff < MONTH_MS) {
        const days = Math.floor(diff / DAY_MS)
        return `há ${days} ${days === 1 ? "dia" : "dias"}`
    }

    if (diff < YEAR_MS) {
        const months = Math.floor(diff / MONTH_MS)
        return `há ${months} ${months === 1 ? "mês" : "meses"}`
    }

    const years = Math.floor(diff / YEAR_MS)
    return `há ${years} ${years === 1 ? "ano" : "anos"}`
}

function randomPastTimestamp() {
    const ranges = [
        { max: 55, sizeMs: SECOND_MS },
        { max: 59, sizeMs: MINUTE_MS },
        { max: 23, sizeMs: HOUR_MS },
        { max: 29, sizeMs: DAY_MS },
        { max: 11, sizeMs: MONTH_MS }
    ]

    const picked = randomItem(ranges)
    const amount = Math.max(1, Math.floor(Math.random() * picked.max) + 1)
    return Date.now() - amount * picked.sizeMs
}

function lerPreferenciaMuteNotificacoes() {
    try {
        return window.localStorage.getItem(NOTIFICATION_SOUND_STORAGE_KEY) === "1"
    } catch (erro) {
        return false
    }
}

function notificacoesEstaoMutadas() {
    return Boolean(notificacoesMutadas)
}

function atualizarBotoesMuteNotificacao() {
    const container = document.getElementById(CONTAINER_ID)
    const muted = notificacoesEstaoMutadas()
    const label = muted ? "Ativar toasts" : "Silenciar toasts"

    if (container) {
        Array.from(container.querySelectorAll("[data-toast-sound-toggle]")).forEach(function (button) {
            button.textContent = label
            button.setAttribute("aria-pressed", muted ? "true" : "false")
            button.classList.toggle("is-muted", muted)
        })
    }

    const bellToastToggle = document.getElementById("bell-toast-toggle")
    if (bellToastToggle) {
        bellToastToggle.checked = muted
        bellToastToggle.setAttribute("aria-checked", muted ? "true" : "false")
        bellToastToggle.setAttribute("title", muted ? "Toasts silenciados" : "Toasts ativos")
    }
}

function fecharTodosOsToasts() {
    const container = document.getElementById(CONTAINER_ID)
    if (!container) {
        return
    }

    Array.from(container.querySelectorAll(".app-toast")).forEach(function (toastElement) {
        dismissToast(toastElement)
    })
}

function persistirMuteNotificacoes(muted) {
    try {
        window.localStorage.setItem(NOTIFICATION_SOUND_STORAGE_KEY, muted ? "1" : "0")
    } catch (erro) {
    }
}

function buildToastElement(options) {
    const toastId = `app-toast-${Date.now()}-${Math.floor(Math.random() * 100000)}`
    const color = getColor(options.color || options.variant)
    const title = options.title ? escapeHtml(truncateWithEllipsis(options.title, TOAST_TITLE_MAX_LENGTH)) : ""
    const message = options.message ? escapeHtml(truncateWithEllipsis(options.message, TOAST_MESSAGE_MAX_LENGTH)) : ""
    const createdAtText = escapeHtml(formatRelativeTime(options.createdAt))
    const buttonLabel = options.button && options.button.label ? escapeHtml(options.button.label) : ""
    const buttonUrl = options.button && options.button.url ? escapeHtml(options.button.url) : "#"
    const buttonTarget = options.button && options.button.target ? escapeHtml(options.button.target) : "_self"
    const soundButtonLabel = notificacoesMutadas ? "Ativar toasts" : "Silenciar toasts"
    const soundButtonClass = notificacoesMutadas ? "app-toast-sound-toggle is-muted" : "app-toast-sound-toggle"
    const soundButtonPressed = notificacoesMutadas ? "true" : "false"

    const toast = document.createElement("div")
    toast.className = "app-toast"
    toast.id = toastId

    const titleHtml = title ? `<span class="app-toast-title">${title}</span>` : ""
    const headerHtml = `
            <div class="app-toast-header">
                <div class="app-toast-heading">
                    ${titleHtml}
                    <small class="app-toast-time">${createdAtText}</small>
                </div>
                <div class="app-toast-header-actions">
                    <button type="button" class="${soundButtonClass}" data-toast-sound-toggle="1" aria-pressed="${soundButtonPressed}">${soundButtonLabel}</button>
                    <button type="button" class="app-toast-close" data-toast-close="${toastId}" aria-label="Fechar">&times;</button>
                </div>
            </div>
        `

    const messageHtml = message ? `<div class="app-toast-message">${message}</div>` : ""
    const buttonHtml = buttonLabel
        ? `<div class="app-toast-actions"><a href="${buttonUrl}" target="${buttonTarget}" class="btn btn-sm btn-outline-secondary">${buttonLabel}</a></div>`
        : ""

    toast.innerHTML = `
            <div class="app-toast-inner">
                <div class="app-toast-accent" style="background:${color};"></div>
                <div class="app-toast-content">
                    ${headerHtml}
                    ${messageHtml}
                    ${buttonHtml}
                </div>
            </div>
        `

    return toast
}

function dismissToast(toastElement) {
    if (!toastElement) {
        return
    }

    toastElement.classList.remove("show")
    window.setTimeout(function () {
        if (toastElement && toastElement.parentNode) {
            toastElement.parentNode.removeChild(toastElement)
        }
    }, 260)
}

function show(options = {}) {
    if (notificacoesEstaoMutadas()) {
        return null
    }

    ensureStyle()
    const container = ensureContainer()
    const toast = buildToastElement(options || {})
    container.insertBefore(toast, container.firstChild)

    requestAnimationFrame(function () {
        toast.classList.add("show")
    })

    const closeButton = toast.querySelector(`[data-toast-close="${toast.id}"]`)
    if (closeButton) {
        closeButton.addEventListener("click", function () {
            dismissToast(toast)
        })
    }

    const soundButton = toast.querySelector("[data-toast-sound-toggle]")
    if (soundButton) {
        soundButton.addEventListener("click", function () {
            alternarMuteNotificacoes()
        })
    }

    atualizarBotoesMuteNotificacao()

    const autoHide = options.autoHide !== false
    const duration = Number(options.duration)
    const timeout = Number.isFinite(duration) && duration > 0 ? duration : DEFAULT_DURATION

    if (autoHide) {
        window.setTimeout(function () {
            dismissToast(toast)
        }, timeout)
    }

    return toast.id
}

function randomPayload() {
    const includeTitle = Math.random() > 0.1
    const includeMessage = Math.random() > 0.15
    const includeButton = Math.random() > 0.35

    const payload = {
        color: randomItem(RANDOM_COLORS),
        duration: 5000 + Math.floor(Math.random() * 2000),
        createdAt: randomPastTimestamp()
    }

    if (includeTitle) {
        payload.title = randomItem(RANDOM_TITLES)
    }

    if (includeMessage) {
        payload.message = randomItem(RANDOM_MESSAGES)
    }

    if (includeButton) {
        payload.button = randomItem(RANDOM_BUTTONS)
    }

    return payload
}

function burst(total = 4, delay = 5000) {
    const count = Number.isFinite(Number(total)) ? Number(total) : 4
    const spacing = Number.isFinite(Number(delay)) ? Number(delay) : 5000

    for (let i = 0; i < count; i += 1) {
        window.setTimeout(function () {
            show(randomPayload())
        }, i * spacing)
    }
}

function obterTokenCsrfAtual() {
    return (typeof csrfToken !== "undefined" && csrfToken) || $('meta[name="csrf-token"]').attr("content") || ""
}

function obterChaveBaseNotificacao(notificacao) {
    if (!notificacao || typeof notificacao !== "object") {
        return ""
    }

    const chavesPossiveis = [
        notificacao.id_notificacao_usuario,
        notificacao.id,
        notificacao.id_notificacao,
        notificacao.id_usuario ? `${notificacao.id_usuario}:${notificacao.criado_em || ""}:${notificacao.titulo || ""}` : ""
    ]

    for (let i = 0; i < chavesPossiveis.length; i += 1) {
        const chave = String(chavesPossiveis[i] || "").trim()
        if (chave) {
            return chave
        }
    }

    return ""
}

function obterChaveNotificacao(notificacao) {
    const chaveBase = obterChaveBaseNotificacao(notificacao)
    if (!chaveBase) {
        return ""
    }

    const possuiControleToast =
        typeof notificacao.toast_exibicoes !== "undefined" ||
        typeof notificacao.toast_ultima_exibicao_em !== "undefined" ||
        typeof notificacao.toast_proxima_exibicao_em !== "undefined" ||
        typeof notificacao.toast_total_exibicoes !== "undefined"

    if (!possuiControleToast) {
        return chaveBase
    }

    const versaoToast = [
        notificacao.toast_exibicoes,
        notificacao.toast_ultima_exibicao_em,
        notificacao.toast_proxima_exibicao_em
    ].map(function (item) {
        return item === null || item === undefined ? "" : String(item).trim()
    }).join("|")

    return versaoToast ? `${chaveBase}:${versaoToast}` : chaveBase
}

function limparControleExibicaoNotificacao(notificacao) {
    const chaveBase = obterChaveBaseNotificacao(notificacao)
    if (!chaveBase || !notificacoesExibidas || typeof notificacoesExibidas.delete !== "function") {
        return
    }

    Array.from(notificacoesExibidas).forEach(function (chave) {
        if (chave === chaveBase || String(chave).indexOf(`${chaveBase}:`) === 0) {
            notificacoesExibidas.delete(chave)
        }
    })
}

function montarBotaoNotificacao(notificacao) {
    const url = String(notificacao && notificacao.botao_url ? notificacao.botao_url : "").trim()
    const label = String(notificacao && notificacao.botao_label ? notificacao.botao_label : "").trim()

    if (!url) {
        return null
    }

    return {
        label: label || "Abrir",
        url,
        target: "_self"
    }
}

function normalizarNotificacaoParaToast(notificacao) {
    return {
        title: notificacao && notificacao.titulo ? notificacao.titulo : "",
        message: notificacao && notificacao.texto ? notificacao.texto : "",
        color: notificacao && notificacao.cor ? notificacao.cor : "primary",
        createdAt: notificacao && notificacao.criado_em ? notificacao.criado_em : Date.now(),
        button: montarBotaoNotificacao(notificacao),
        duration: DEFAULT_DURATION
    }
}

function notificacaoTemDestaque(notificacao) {
    if (!notificacao || typeof notificacao !== "object") {
        return false
    }

    const valor = notificacao.exibir_em_destaque
    const normalizado = String(valor || "").trim().toLowerCase()
    return valor === true || normalizado === "1" || normalizado === "true"
}

function obterConteudoNotificacaoDestaque(notificacao) {
    const texto = String(notificacao && notificacao.texto ? notificacao.texto : "").trim()
    if (texto) {
        return `<div class="notification-highlight-plain-text">${escapeHtml(texto).replace(/\r\n|\r|\n/g, "<br>")}</div>`
    }

    const htmlEmail = String(notificacao && notificacao.html_email ? notificacao.html_email : "").trim()
    if (htmlEmail) {
        return htmlEmail
    }

    return "<p>Sem conteudo disponivel.</p>"
}

function normalizarNotificacaoParaDestaque(notificacao) {
    const botao = montarBotaoNotificacao(notificacao)
    const idNotificacaoUsuario = Number(
        notificacao && notificacao.id_notificacao_usuario
            ? notificacao.id_notificacao_usuario
            : notificacao && notificacao.id
                ? notificacao.id
                : 0
    )
    const toastExibicoes = Number(notificacao && notificacao.toast_exibicoes !== undefined ? notificacao.toast_exibicoes : 0)
    const toastTotalExibicoes = Number(notificacao && notificacao.toast_total_exibicoes !== undefined ? notificacao.toast_total_exibicoes : 1)
    const toastUltimaExibicaoEm = notificacao && notificacao.toast_ultima_exibicao_em ? String(notificacao.toast_ultima_exibicao_em) : null
    const toastProximaExibicaoEm = notificacao && notificacao.toast_proxima_exibicao_em ? String(notificacao.toast_proxima_exibicao_em) : null

    return {
        id: obterChaveNotificacao(notificacao),
        id_notificacao_usuario: Number.isFinite(idNotificacaoUsuario) ? idNotificacaoUsuario : 0,
        title: notificacao && notificacao.titulo ? notificacao.titulo : "Notificacao destacada",
        cor: notificacao && notificacao.cor ? String(notificacao.cor).trim() : "primary",
        bodyHtml: obterConteudoNotificacaoDestaque(notificacao),
        ctaLabel: botao ? botao.label : "",
        ctaUrl: botao ? botao.url : "",
        ctaTarget: botao ? botao.target : "_self",
        toast_exibicoes: Number.isFinite(toastExibicoes) ? Math.max(0, toastExibicoes) : 0,
        toast_total_exibicoes: Number.isFinite(toastTotalExibicoes) ? Math.max(1, toastTotalExibicoes) : 1,
        toast_ultima_exibicao_em: toastUltimaExibicaoEm,
        toast_proxima_exibicao_em: toastProximaExibicaoEm,
        disparada_como_toast: notificacao && notificacao.disparada_como_toast ? Number(notificacao.disparada_como_toast) : 0
    }
}

function obterOuCriarModalNotificacaoDestaque() {
    let modal = document.getElementById(NOTIFICATION_HIGHLIGHT_MODAL_ID)
    if (modal) {
        return modal
    }

    modal = document.createElement("div")
    modal.id = NOTIFICATION_HIGHLIGHT_MODAL_ID
    modal.className = "notification-highlight-modal"
    modal.setAttribute("aria-hidden", "true")
    modal.innerHTML = `
        <div class="card card-primary notification-highlight-card" data-notification-highlight-card>
            <div class="card-header">
                <h3 class="card-title" data-notification-highlight-title></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" aria-label="Fechar" data-notification-highlight-close>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="card-body" data-notification-highlight-text></div>

            <div class="card-footer clearfix d-none" data-notification-highlight-actions>
                <a class="btn btn-primary float-left d-none" data-notification-highlight-cta href="#" target="_self" rel="noopener">Abrir</a>
            </div>
        </div>
    `

    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            fecharNotificacaoDestaque(true)
        }
    })

    const card = modal.querySelector("[data-notification-highlight-card]")
    if (card) {
        card.addEventListener("click", function (event) {
            event.stopPropagation()
        })
    }

    const closeButton = modal.querySelector("[data-notification-highlight-close]")
    if (closeButton) {
        closeButton.addEventListener("click", function (event) {
            event.preventDefault()
            event.stopPropagation()
            fecharNotificacaoDestaque(true)
        })
    }

    document.body.appendChild(modal)
    return modal
}

function aplicarNotificacaoDestaqueNoModal(notificacao) {
    const modal = obterOuCriarModalNotificacaoDestaque()
    const card = modal.querySelector("[data-notification-highlight-card], .notification-highlight-card")
    const titleElement = modal.querySelector("[data-notification-highlight-title], .notification-highlight-title, .card-title")
    const textElement = modal.querySelector("[data-notification-highlight-text]")
    const actionsElement = modal.querySelector("[data-notification-highlight-actions]")
    const ctaElement = modal.querySelector("[data-notification-highlight-cta]")

    if (card) {
        aplicarClassesVariantes(card, NOTIFICATION_HIGHLIGHT_CARD_VARIANTS, obterClasseCardDestaque(notificacao.cor))
    }

    if (titleElement) {
        titleElement.textContent = notificacao.title || "Notificacao destacada"
    }

    if (textElement) {
        textElement.innerHTML = notificacao.bodyHtml || "<p>Sem conteudo disponivel.</p>"
    }

    if (ctaElement) {
        const ctaLabel = String(notificacao.ctaLabel || "Abrir").trim()
        const ctaUrl = String(notificacao.ctaUrl || "").trim()
        const ctaTarget = String(notificacao.ctaTarget || "_self").trim() || "_self"
        const ctaClasse = obterClasseBotaoDestaque(notificacao.cor)

        ctaElement.onclick = null
        ctaElement.className = `btn ${ctaClasse} float-left`

        if (ctaUrl) {
            ctaElement.textContent = ctaLabel || "Abrir"
            ctaElement.setAttribute("href", ctaUrl)
            ctaElement.setAttribute("target", ctaTarget)
            ctaElement.setAttribute("rel", ctaTarget === "_blank" ? "noopener noreferrer" : "noopener")
            ctaElement.classList.remove("d-none")

            if (actionsElement) {
                actionsElement.classList.remove("d-none")
            }

            ctaElement.onclick = function (event) {
                if (event) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                const destino = ctaUrl
                if (ctaTarget === "_blank") {
                    window.open(destino, "_blank", "noopener,noreferrer")
                    fecharNotificacaoDestaque(true)
                    return
                }

                fecharNotificacaoDestaque(true)
                window.setTimeout(function () {
                    window.location.href = destino
                }, 120)
            }
        } else {
            ctaElement.classList.add("d-none")
            ctaElement.removeAttribute("href")
            ctaElement.removeAttribute("target")
            ctaElement.removeAttribute("rel")

            if (actionsElement) {
                actionsElement.classList.add("d-none")
            }
        }
    }
}

function mostrarNotificacaoDestaque(notificacao) {
    if (!notificacao) {
        return
    }

    ensureHighlightStyle()
    const modal = obterOuCriarModalNotificacaoDestaque()

    notificacaoDestaqueAtual = notificacao
    notificacaoDestaqueFechando = false
    aplicarNotificacaoDestaqueNoModal(notificacao)

    notificacaoDestaqueBodyOverflowAnterior = document.body.style.overflow
    document.body.style.overflow = "hidden"

    modal.classList.add("is-visible")
    modal.setAttribute("aria-hidden", "false")

    window.setTimeout(function () {
        const closeButton = modal.querySelector("[data-notification-highlight-close]")
        if (closeButton && typeof closeButton.focus === "function") {
            closeButton.focus()
        }
    }, 0)
}

function processarFilaNotificacaoDestaque() {
    if (notificacaoDestaqueAtual || notificacaoDestaqueFechando) {
        return
    }

    const proxima = notificacoesDestaqueFila.shift()
    if (!proxima) {
        return
    }

    mostrarNotificacaoDestaque(proxima)
}

function enfileirarNotificacaoDestaque(notificacao) {
    notificacoesDestaqueFila.unshift(normalizarNotificacaoParaDestaque(notificacao))
}

function marcarNotificacaoDestaqueComoLida(notificacao) {
    if (typeof $ === "undefined" || typeof controle === "undefined" || !controle) {
        return null
    }

    if (!notificacao || !notificacao.id_notificacao_usuario) {
        return null
    }

    const token = obterTokenCsrfAtual()
    return $.ajax({
        url: controle,
        method: "post",
        dataType: "json",
        data: {
            objeto: "Notificacoes",
            metodo: "marcarNotificacaoComoLida",
            id_notificacao_usuario: notificacao.id_notificacao_usuario
        },
        headers: token ? { 'X-CSRF-Token': token } : {}
    })
}

function esconderNotificacaoDestaque() {
    const modal = document.getElementById(NOTIFICATION_HIGHLIGHT_MODAL_ID)
    if (modal) {
        modal.classList.remove("is-visible")
        modal.setAttribute("aria-hidden", "true")
    }

    document.body.style.overflow = notificacaoDestaqueBodyOverflowAnterior || ""
}

function fecharNotificacaoDestaque(marcarLida = true) {
    if (!notificacaoDestaqueAtual || notificacaoDestaqueFechando) {
        return
    }

    const atual = notificacaoDestaqueAtual
    notificacaoDestaqueFechando = true
    notificacaoDestaqueAtual = null

    esconderNotificacaoDestaque()
    notificacaoDestaqueFechando = false
    processarFilaNotificacaoDestaque()

    const concluir = function () {
        if (!notificacaoDestaqueAtual) {
            renderBellNotifications()
        }
    }

    // Mantem o destaque ativo ate a ultima exibicao agendada.
    const totalExibicoes = Math.max(1, Number(atual.toast_total_exibicoes) || 1)
    const exibicoesRealizadas = Math.max(0, Number(atual.toast_exibicoes) || 0)
    const deveMarcarLida = marcarLida && exibicoesRealizadas >= totalExibicoes

    if (!deveMarcarLida) {
        limparControleExibicaoNotificacao(atual)
        concluir()
        return
    }

    const requisicao = marcarNotificacaoDestaqueComoLida(atual)
    if (requisicao && typeof requisicao.always === "function") {
        requisicao.always(concluir)
        return
    }

    concluir()
}


function definirMuteNotificacoes(muted) {
    notificacoesMutadas = Boolean(muted)
    persistirMuteNotificacoes(notificacoesMutadas)

    if (notificacoesMutadas) {
        ;[notificationToastAudio, notificationDestaqueAudio].forEach(function (audio) {
            if (!audio) {
                return
            }

            try {
                audio.pause()
                audio.currentTime = 0
            } catch (erro) {
            }
        })
    }

    if (notificacoesMutadas) {
        restaurarTituloOriginalNotificacoes()
        fecharTodosOsToasts()
    } else {
        prepararAudioNotificacaoAposInteracao()
    }

    atualizarBotoesMuteNotificacao()
}

function alternarMuteNotificacoes() {
    definirMuteNotificacoes(!notificacoesEstaoMutadas())
}

function obterAudioNotificacao(url = NOTIFICATION_SOUND_URL) {
    if (typeof Audio !== "function") {
        return null
    }

    const isHighlight = String(url || "") === NOTIFICATION_HIGHLIGHT_SOUND_URL
    let audio = isHighlight ? notificationDestaqueAudio : notificationToastAudio

    if (!audio) {
        audio = new Audio(url || NOTIFICATION_SOUND_URL)
        audio.preload = "auto"

        if (isHighlight) {
            notificationDestaqueAudio = audio
        } else {
            notificationToastAudio = audio
        }
    }

    audio.volume = NOTIFICATION_SOUND_VOLUME
    return audio
}

function tocarSomNotificacao(url = NOTIFICATION_SOUND_URL) {
    if (notificacoesEstaoMutadas()) {
        return
    }

    const audio = obterAudioNotificacao(url)
    if (!audio) {
        return
    }

    try {
        audio.pause()
        audio.currentTime = 0
    } catch (erro) {
    }

    const playPromise = audio.play()
    if (playPromise && typeof playPromise.catch === "function") {
        playPromise.catch(function () { })
    }
}

function prepararAudioNotificacaoAposInteracao() {
    if (notificacoesEstaoMutadas()) {
        return
    }

    const audio = obterAudioNotificacao()
    if (!audio) {
        return
    }

    const volumeOriginal = audio.volume
    audio.volume = 0

    const restaurarAudio = function () {
        try {
            audio.pause()
            audio.currentTime = 0
        } catch (erro) {
        }

        audio.volume = volumeOriginal
    }

    try {
        const playPromise = audio.play()
        if (playPromise && typeof playPromise.then === "function") {
            playPromise.then(function () {
                restaurarAudio()
            }).catch(function () {
                audio.volume = volumeOriginal
            })
            return
        }
    } catch (erro) {
    }

    restaurarAudio()
}

function obterMensagemTituloNotificacoes(totalNovasNotificacoes) {
    const total = Math.max(1, Number(totalNovasNotificacoes) || 1)
    return `Você tem ${total} nova${total === 1 ? "" : "s"} notificação${total === 1 ? "" : "es"}`
}

function restaurarTituloOriginalNotificacoes() {
    if (notificationTitleBlinkTimer !== null) {
        window.clearInterval(notificationTitleBlinkTimer)
        notificationTitleBlinkTimer = null
    }

    if (notificationTitleRestoreTimer !== null) {
        window.clearTimeout(notificationTitleRestoreTimer)
        notificationTitleRestoreTimer = null
    }

    if (notificationTitleOriginal) {
        document.title = notificationTitleOriginal
    }

    notificationTitleBlinkShowingAlert = false
}

function iniciarPiscaTituloNotificacoes(totalNovasNotificacoes) {
    const mensagemNotificacao = obterMensagemTituloNotificacoes(totalNovasNotificacoes)

    if (notificationTitleBlinkTimer === null) {
        notificationTitleOriginal = document.title
    }

    restaurarTituloOriginalNotificacoes()
    notificationTitleOriginal = notificationTitleOriginal || document.title
    notificationTitleBlinkShowingAlert = true
    document.title = mensagemNotificacao

    notificationTitleBlinkTimer = window.setInterval(function () {
        notificationTitleBlinkShowingAlert = !notificationTitleBlinkShowingAlert
        document.title = notificationTitleBlinkShowingAlert ? mensagemNotificacao : notificationTitleOriginal
    }, NOTIFICATION_TITLE_BLINK_INTERVAL_MS)

    notificationTitleRestoreTimer = window.setTimeout(function () {
        restaurarTituloOriginalNotificacoes()
    }, NOTIFICATION_TITLE_BLINK_DURATION_MS)
}

function exibirNotificacoesRecentes(listaNotificacoes = []) {
    const notificacoes = Array.isArray(listaNotificacoes) ? listaNotificacoes.slice() : []
    let totalNovasNotificacoes = 0
    let temToastNovo = false
    let temDestaqueNovo = false

    notificacoes.reverse().forEach(function (notificacao) {
        const chave = obterChaveNotificacao(notificacao)
        if (!chave || notificacoesExibidas.has(chave)) {
            return
        }

        notificacoesExibidas.add(chave)
        totalNovasNotificacoes += 1

        if (notificacaoTemDestaque(notificacao)) {
            enfileirarNotificacaoDestaque(notificacao)
            temDestaqueNovo = true
            return
        }

        if (!notificacoesEstaoMutadas()) {
            show(normalizarNotificacaoParaToast(notificacao))
            temToastNovo = true
        }
    })

    if (totalNovasNotificacoes > 0 && !notificacoesEstaoMutadas()) {
        iniciarPiscaTituloNotificacoes(totalNovasNotificacoes)

        const urlSom = temDestaqueNovo
            ? NOTIFICATION_HIGHLIGHT_SOUND_URL
            : (temToastNovo ? NOTIFICATION_SOUND_URL : null)

        if (urlSom) {
            tocarSomNotificacao(urlSom)
        }
    }

    processarFilaNotificacaoDestaque()
}

function buscarNotificacoesRecentes() {
    if (notificacoesRequestEmAndamento || typeof $ === "undefined" || typeof controle === "undefined" || !controle) {
        return
    }

    notificacoesRequestEmAndamento = true

    const requisicao = requestAjax(
        {
            'objeto': "Notificacoes",
            'metodo': "getNotificacoesRecentes"
        }, function (result) {
            exibirNotificacoesRecentes(result)
            renderBellNotifications();
        },
        false
    )

    if (requisicao && typeof requisicao.always === "function") {
        requisicao.always(function () {
            notificacoesRequestEmAndamento = false
        })
        return
    }

    notificacoesRequestEmAndamento = false
}

function iniciarPollingNotificacoes() {
    if (notificacoesPollingTimer !== null) {
        return
    }

    buscarNotificacoesRecentes()
    notificacoesPollingTimer = window.setInterval(function () {
        buscarNotificacoesRecentes()
    }, NOTIFICATION_POLL_INTERVAL_MS)
}

function agendarInicioPollingNotificacoes() {
    if (notificacoesPollingTimer !== null || notificacoesStartTimeout !== null) {
        return
    }

    notificacoesStartTimeout = window.setTimeout(function () {
        notificacoesStartTimeout = null
        iniciarPollingNotificacoes()
    }, NOTIFICATION_START_DELAY_MS)
}

function removerListenersInteracaoNotificacoes() {
    window.removeEventListener("pointerdown", tratarPrimeiraInteracaoNotificacoes)
    window.removeEventListener("mousedown", tratarPrimeiraInteracaoNotificacoes)
    window.removeEventListener("touchstart", tratarPrimeiraInteracaoNotificacoes)
    window.removeEventListener("keydown", tratarPrimeiraInteracaoNotificacoes)
    window.removeEventListener("scroll", tratarPrimeiraInteracaoNotificacoes)
}

function tratarPrimeiraInteracaoNotificacoes() {
    if (notificacoesInteracaoInicialRegistrada) {
        return
    }

    notificacoesInteracaoInicialRegistrada = true
    removerListenersInteracaoNotificacoes()
    prepararAudioNotificacaoAposInteracao()
    agendarInicioPollingNotificacoes()
}

function iniciarPollingNotificacoesQuandoDomEstiverPronto() {
    if (notificacoesInteracaoInicialRegistrada || notificacoesPollingTimer !== null || notificacoesStartTimeout !== null) {
        return
    }

    window.addEventListener("pointerdown", tratarPrimeiraInteracaoNotificacoes, { passive: true })
    window.addEventListener("mousedown", tratarPrimeiraInteracaoNotificacoes, { passive: true })
    window.addEventListener("touchstart", tratarPrimeiraInteracaoNotificacoes, { passive: true })
    window.addEventListener("keydown", tratarPrimeiraInteracaoNotificacoes)
    window.addEventListener("scroll", tratarPrimeiraInteracaoNotificacoes)
}

function obterTextoCabecalhoNotificacoes(total) {
    const totalNumerico = Math.max(0, Number(total) || 0)
    return totalNumerico === 1 ? "1 notificação não lida" : `${totalNumerico} notificações não lidas`
}

function renderBellHeader(total) {
    const header = document.getElementById("bell-header")
    if (!header) {
        return
    }

    const muted = notificacoesEstaoMutadas()

    header.innerHTML = `
        <span class="app-bell-header-text">${escapeHtml(obterTextoCabecalhoNotificacoes(total))}</span>
        <div class="custom-control custom-switch app-bell-toast-toggle mb-0">
            <input type="checkbox" class="custom-control-input" id="bell-toast-toggle" ${muted ? "checked" : ""} aria-label="Silenciar notificações toast">
            <label class="custom-control-label text-xs" for="bell-toast-toggle">Silenciar</label>
        </div>
    `

    const bellToastToggle = document.getElementById("bell-toast-toggle")
    if (bellToastToggle) {
        bellToastToggle.addEventListener("change", function (event) {
            definirMuteNotificacoes(Boolean(event.target.checked))
        })
    }

    atualizarBotoesMuteNotificacao()
}

function renderBellNotifications() {
    ensureStyle()

    const container = document.getElementById('bell-list');
    const badge = document.getElementById('bell-badge');
    const header = document.getElementById('bell-header');

    if (!container || !badge || !header) {
        return;
    }

    const itens = [];

    requestAjax(
        {
            'objeto': "Notificacoes",
            'metodo': "getNotificacoes"
        }, function (result) {
            const { notificacoes, total } = result

            setTimeout(() => {
                notificacoes.forEach((item, index) => {
                    const { id, id_notificacao_usuario, titulo, texto, cor, botao_url, criado_em, lida } = item
                    let texto_curto = texto.length > 100 ? texto.substr(0, 100) + '...' : texto
                    let isLida = !!lida

                    itens.push(`
                        <a href="${escapeHtml(botao_url || '#')}" data-url="${escapeHtml(botao_url || '')}" data-id="${escapeHtml(id_notificacao_usuario)}"
                        onclick="return lerNotificacao(this.dataset.url, this.dataset.id)"
                        class="dropdown-item app-bell-notification-item ${isLida ? 'lida' : 'nao-lida'}"
                        style="--notification-accent: ${getColor(cor)};">
                        
                            <div class="notification-accent-bar"></div>
    
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="text-sm mr-2 ${isLida ? '' : 'font-weight-bold'}">${titulo}</span>
                                <small style="white-space: nowrap" class="text-muted text-xs">${String(tempoDecorrido(criado_em))}</small>
                            </div>
    
                            <div class="text-muted text-sm mt-1">${texto_curto}</div>
                        </a>
    
                        ${index < notificacoes.length - 1 ? '<div class="dropdown-divider"></div>' : ''}
                    `)
                })

                container.innerHTML = itens.join('');
                badge.textContent = total;
                renderBellHeader(total);
            }, 1000)
        },
        false
    )

}


function lerNotificacao(url, id) {
    console.log("notificacao clicada:", { url, id })

    const destino = String(url || "").trim()
    const notificacaoId = String(id || "").trim()

    if (!notificacaoId) {
        return false
    }

    requestAjax(
        {
            'objeto': "Notificacoes",
            'metodo': "lerNotificacao",
            'id_notificacao_usuario': notificacaoId
        }, function () {
            if (destino && destino !== "#") {
                window.location.href = destino
                return
            }

            if (typeof renderTblNotificacoes === "function") {
                renderTblNotificacoes()
            }

            if (typeof contarNaoLidas === "function") {
                contarNaoLidas()
            }

            if (typeof renderBellNotifications === "function") {
                renderBellNotifications()
            }
        },
        false
    )

    return false
}


; (function (global) {
    if (global.AppToast) {
        return
    }

    global.AppToast = {
        show,
        burst,
        randomPayload,
        buscarNotificacoesRecentes,
        iniciarPollingNotificacoes,
        notificacoesEstaoMutadas,
        definirMuteNotificacoes
    }

    iniciarPollingNotificacoesQuandoDomEstiverPronto()

    const bellDropdown = document.getElementById('bell-dropdown');
    if (bellDropdown) {
        renderBellNotifications();
    }

})(window)