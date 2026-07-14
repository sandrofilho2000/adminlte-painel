<?php

use Classes\ConselhosRegionais;

Controller::setPermissao("00014");
Controller::setPageTitle("Portais de CREF's");
Controller::setApenasConfef(true);
Controller::setFileJavascript("/admin/crefs/portais/js/main.js?v=$v");
Controller::setFileStyle("/admin/crefs/portais/css/styles.css?v=$v");

$conselhosRegionais = ConselhosRegionais::listar();
?>

<style>

</style>

<div class="card card-primary card-outline" id="cartaoFormularioPortal">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title" id="tituloFormularioPortal">Cadastrar portal</h3>
        <span class="badge badge-light ml-auto" id="indicadorModoFormularioPortal">
            <i class="fas fa-plus-circle mr-1"></i> Modo de criação
        </span>
    </div>

    <form id="formPortais" method="post" action="#">
        <div class="card-body">
            <input type="hidden" name="objeto" value="Portais">
            <input type="hidden" name="metodo" value="criaPortalCref">
            <input type="hidden" name="id" value="">

            <ul class="nav nav-tabs mb-3" id="abasFormularioPortal" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="abaPortalTab" data-toggle="tab" href="#abaPortal" role="tab" aria-controls="abaPortal" aria-selected="true">Portal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="abaIdentificacaoPortalTab" data-toggle="tab" href="#abaIdentificacaoPortal" role="tab" aria-controls="abaIdentificacaoPortal" aria-selected="false">Identificação</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="abaEnderecoPortalTab" data-toggle="tab" href="#abaEnderecoPortal" role="tab" aria-controls="abaEnderecoPortal" aria-selected="false">Endereço</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="abaContatoPortalTab" data-toggle="tab" href="#abaContatoPortal" role="tab" aria-controls="abaContatoPortal" aria-selected="false">Contato e presença digital</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="abaLogoPortalTab" data-toggle="tab" href="#abaLogoPortal" role="tab" aria-controls="abaLogoPortal" aria-selected="false">Logo</a>
                </li>
            </ul>

            <div class="tab-content" id="conteudoAbasFormularioPortal">
            <div class="tab-pane fade show active" id="abaPortal" role="tabpanel" aria-labelledby="abaPortalTab">
                <div class="row">
                    <div class="col-md-11">
                        <div class="form-group">
                            <label for="estado_conselho">Estado conselho</label>
                            <select class="form-control" id="estado_conselho" name="estado_conselho" required>
                                <option value="" selected disabled>Selecione...</option>
                                <?php foreach ($conselhosRegionais as $sigla => $nome): ?>
                                    <option value="<?= htmlspecialchars($sigla, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <div class="form-group mb-md-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" value="1" checked>
                                <label class="custom-control-label" for="ativo">Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="abaIdentificacaoPortal" role="tabpanel" aria-labelledby="abaIdentificacaoPortalTab">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cnpj">CNPJ</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" maxlength="18" placeholder="00.000.000/0000-00" inputmode="numeric" required>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="255" placeholder="contato@cref.org.br" required>
                        </div>
                    </div>
                </div>

            </div>

            <div class="tab-pane fade" id="abaEnderecoPortal" role="tabpanel" aria-labelledby="abaEnderecoPortalTab">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="endereco">Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" maxlength="255" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="numero">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" maxlength="20">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="cep">CEP</label>
                            <input type="text" class="form-control" id="cep" name="cep" maxlength="9" placeholder="00000-000" inputmode="numeric" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="complemento">Complemento</label>
                            <input type="text" class="form-control" id="complemento" name="complemento" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="bairro">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" maxlength="100" required>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label for="estado">UF</label>
                            <input type="text" class="form-control text-uppercase" id="estado" name="estado" maxlength="2" pattern="[A-Za-z]{2}" required>
                        </div>
                    </div>
                </div>

            </div>

            <div class="tab-pane fade" id="abaContatoPortal" role="tabpanel" aria-labelledby="abaContatoPortalTab">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" maxlength="15" placeholder="(00) 00000-0000" inputmode="tel" required>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="transparencia">Portal da Transparência</label>
                            <input type="url" class="form-control" id="transparencia" name="transparencia" maxlength="255" placeholder="https://transparencia.cref.org.br">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="facebook">Facebook</label>
                            <input type="url" class="form-control" id="facebook" name="facebook" maxlength="255" placeholder="https://facebook.com/...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="instagram">Instagram</label>
                            <input type="url" class="form-control" id="instagram" name="instagram" maxlength="255" placeholder="https://instagram.com/...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="linkedin">LinkedIn</label>
                            <input type="url" class="form-control" id="linkedin" name="linkedin" maxlength="255" placeholder="https://linkedin.com/company/...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="youtube">YouTube</label>
                            <input type="url" class="form-control" id="youtube" name="youtube" maxlength="255" placeholder="https://youtube.com/@...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="spotify">Spotify</label>
                            <input type="url" class="form-control" id="spotify" name="spotify" maxlength="255" placeholder="https://open.spotify.com/...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="twitter">Twitter/X</label>
                            <input type="url" class="form-control" id="twitter" name="twitter" maxlength="255" placeholder="https://x.com/...">
                        </div>
                    </div>
                </div>

            </div>

            <div class="tab-pane fade" id="abaLogoPortal" role="tabpanel" aria-labelledby="abaLogoPortalTab">
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
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="botaoRemoverFundoLogoPortal">
                                <i class="fas fa-magic mr-1"></i> Remover fundo
                            </button>
                            <input type="file" class="d-none" id="logo_portal" name="logo_portal" accept="image/*">
                        </div>
                    </div>
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
        <table id="tabelaPortais" class="table table-striped table-bordered responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Logo</th>
                    <th>CREF</th>
                    <th>CNPJ</th>
                    <th>Cidade/UF</th>
                    <th>E-mail</th>
                    <th>Ativo</th>
                    <th>Data de cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-primary" id="salvarAlteracoesRotasSite">
            <i class="fas fa-save mr-1"></i> Salvar alterações
        </button>
    </div>
</div>
