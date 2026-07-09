<?php
Controller::setPermissao("00014");
Controller::setPageTitle("Portais de CREF's");
Controller::setApenasConfef(true);
Controller::setFileJavascript("/admin/crefs/portais/js/main.js?v=$v");

$estadosConselho = [
    'BR' => 'Conselho Federal',
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AP' => 'Amapa',
    'AM' => 'Amazonas',
    'BA' => 'Bahia',
    'CE' => 'Ceara',
    'DF' => 'Distrito Federal',
    'ES' => 'Espirito Santo',
    'GO' => 'Goias',
    'MA' => 'Maranhao',
    'MT' => 'Mato Grosso',
    'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais',
    'PA' => 'Para',
    'PB' => 'Paraiba',
    'PR' => 'Parana',
    'PE' => 'Pernambuco',
    'PI' => 'Piaui',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondonia',
    'RR' => 'Roraima',
    'SC' => 'Santa Catarina',
    'SP' => 'Sao Paulo',
    'SE' => 'Sergipe',
    'TO' => 'Tocantins',
];
?>

<style>
    #areaLogoPortal {
        background-image:
            linear-gradient(45deg, #d9dee3 25%, transparent 25%),
            linear-gradient(-45deg, #d9dee3 25%, transparent 25%),
            linear-gradient(45deg, transparent 75%, #d9dee3 75%),
            linear-gradient(-45deg, transparent 75%, #d9dee3 75%);
        background-position: 0 0, 0 8px, 8px -8px, -8px 0;
        background-size: 16px 16px;
    }
</style>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Cadastrar Portais</h3>
    </div>

    <form id="formPortais" method="post" action="#">
        <div class="card-body">
            <input type="hidden" name="objeto" value="PortaisCrefs">
            <input type="hidden" name="metodo" value="salvarPortalCref">
            <input type="hidden" name="id" value="">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="estado_conselho">Estado conselho</label>
                        <select class="form-control" id="estado_conselho" name="estado_conselho" required>
                            <option value="" selected disabled>Selecione...</option>
                            <?php foreach ($estadosConselho as $sigla => $nome): ?>
                                <option value="<?= htmlspecialchars($sigla, ENT_QUOTES, 'UTF-8') ?>">
                                    (<?= htmlspecialchars($sigla, ENT_QUOTES, 'UTF-8') ?>) <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="dt_inclusao">Data de inclusao</label>
                        <input type="datetime-local" class="form-control" id="dt_inclusao" name="dt_inclusao">
                    </div>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-group mb-md-3">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" value="1" checked>
                            <label class="custom-control-label" for="ativo">Ativo</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-0">
                        <label for="logo_portal">Logo</label>
                        <div
                            class="border rounded bg-light d-flex align-items-center justify-content-center text-center p-4"
                            id="areaLogoPortal"
                            role="button"
                            tabindex="0"
                            style="min-height: 180px; border-style: dashed !important;">
                            <div id="conteudoLogoPortal">
                                <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                <div class="font-weight-bold">Arraste, cole ou selecione uma imagem</div>
                                <div class="text-muted small">PNG, JPG, JPEG, GIF ou WEBP</div>
                            </div>
                            <img
                                src=""
                                alt="Previa da logo"
                                id="previewLogoPortal"
                                class="img-fluid d-none"
                                style="max-height: 150px;">
                        </div>
                        <input type="file" class="d-none" id="logo_portal" name="logo_portal" accept="image/*">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <button type="reset" class="btn btn-secondary mr-2" id="botaoLimparPortal">Limpar</button>
            <button type="submit" class="btn btn-primary" id="botaoSalvarPortal">
                <i class="fas fa-plus mr-1"></i> Salvar portal
            </button>
        </div>
    </form>
</div>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Portais cadastrados</h3>
    </div>
    <div class="card-body">
        <table id="tabelaPortaisCrefs" class="table table-striped table-bordered responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>CREF</th>
                    <th>Ativo</th>
                    <th>Data de cadastro</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-primary" id="salvarAlteracoesRotasSite">
            <i class="fas fa-save mr-1"></i> Salvar alteracoes
        </button>
    </div>
</div>
