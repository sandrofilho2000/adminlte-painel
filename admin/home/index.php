<?php

Controller::setPageTitle('Painel - Sistema');
$pageDescription = 'Página inicial do Painel com notícias, clipping, publicações e informações do Sistema.';

$Banner = new Classes\Banner();
$banners = $Banner->getBannersAtivos();

$Noticias = new Classes\Noticias();

$noticias = $Noticias->getNoticiasOuClipping('NOTÍCIA');
$clipping = $Noticias->getNoticiasOuClipping('CLIPPING');
Controller::setFileJavascript('/home/js/main.js');
Controller::setFileStyle('/home/css/home.css');

$publicacoes = new Classes\Publicacoes();
$publicacoes = $publicacoes->getPublicacoesDestaque();

$videos = new Classes\Videos();
$videos = $videos->getVideosPrimeirosAtivos();

/* $publicacoes = [
  array(
    "id" => 41,
    "imagem" => "doc1.JPG",
  ),
  array(
    "id" => 37,
    "imagem" => "doc2.JPG",
  ),
  array(
    "id" => 38,
    "imagem" => "doc3.JPG",
  ),
  array(
    "id" => 40,
    "imagem" => "doc4.JPG",
  ),
  array(
    "id" => 39,
    "imagem" => "doc5.JPG",
  ),
  array(
    "id" => 36,
    "imagem" => "doc6.JPG",
  ),
] */

?>

<!--BANNER CARR0SSEL-->
<div class="carrousel">
  <div class="carrousel-lista" id="carrousel-lista">
    <?php foreach ($banners as $banner): ?>
      <div class="carrousel-item">
        <a href="<?= $banner->link ?>">
          <img class="" src="<?= $banner->caminho_imagem ?>" alt="<?= $banner->Descricao ?>">
        </a>
      </div>
    <?php endforeach; ?>
  </div>
  <button class="carrousel-button-next absolute top-[45%] right-0 text-2xl text-gray-400 lg:text-6xl mr-4"><i class="ri-arrow-right-wide-line"></i></button>
  <button class="carrousel-button-prev absolute top-[45%] left-0 text-2xl text-gray-400 lg:text-6xl ml-4"><i class="ri-arrow-left-wide-fill"></i></button>
  <div class="carrousel-nav"></div>
</div>

<!--CONTEUDO PRINCIPAL-->

<!--SEÇÃO DE NOTICIAS-->
<section class="py-8">
  <!--TITULO DA SECAO-->
  <div class="container mx-auto flex flex-col">
    <div class="section_titulo">
      <h2>Notícias</h2>
    </div>
    <div class="noticiasSwiper swiper-container overflow-hidden mt-8">
      <div class="swiper-wrapper" id="noticias-wrapper">
        <?php foreach ($noticias as $noticia): ?>
          <a href="/comunicacao/noticias/<?= $noticia->id ?>" class="swiper-slide">
            <div class="swiper-slide card-noticias mx-auto rounded overflow-hidden shadow-lg border-2">
              <img class="w-full object-cover object-center lg:h-auto" src="/adminlte-painel/img/noticias/<?= $noticia->nm_imagem ?>" alt="Notícia">
              <div class="card-content px-3 lg:px-[1.8rem] py-3 lg:py-[1.2rem]">
                <span class="inline-block mb-2 lg:mb-4 bg-[#419837] text-[0.7rem] text-white p-[0.25rem] rounded-sm font-bold"><?= $noticia->nm_tags ?></span>
                <div class="font-extrabold text-xs lg:text-base mb-1 lg:mb-2"><?= $noticia->nm_titulo ?></div>
                <p class="teste-de text-gray-700 line-clamp-4 text-[0.7rem] lg:text-xs text-justify"></p>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <a href="/comunicacao/noticias/" class="secao_botao self-center w-fit mt-[60px]">mais notícias</a>
  </div>
</section>

<!--SEÇÃO SOMOS-------->
<section class="secao-somos">
  <div class="secao-somos-logo"></div>
  <div
    class="somos-texto">
    <p class="somos-texto-titulo">Hoje Somos</p>
    <div class="somos-body">
      <div class="somos-contador">
        <span>Pessoa Física</span>
        <p id="quantos-somos-fisica"></p>
      </div>
      <div class="somos-contador">
        <span>Pessoa Jurídica</span>
        <p id="quantos-somos-juridica"></p>
      </div>
    </div>
  </div>
</section>

<!--SEÇÃO VIDEOS---------->
<section class="flex flex-col items-center py-[40px] bg-[#ECECEC]">
  <div class="section_titulo">
    <h2>Assista e baixe aqui os principais vídeos do Painel</h2>
  </div>
  <div class="grid grid-cols-2 grid-rows-2 lg:grid-cols-3 gap-x-4 gap-y-4 w-[100vw] h-[550px] mx-auto container mt-8 px-4">

    <?php foreach ($videos as $indice => $video): ?>
      <div class="rounded-md <?= $indice === 0 ? 'lg:row-span-2 col-span-2' : '' ?>">
        <iframe
          class="rounded-lg"
          width="100%"
          height="100%"
          src="<?= htmlspecialchars($video->url) ?>"
          title="<?= htmlspecialchars($video->nome) ?>"
          allowfullscreen>
        </iframe>
      </div>
    <?php endforeach; ?>
    <!-- <div class="lg:row-span-2 col-span-2 rounded-md">
      <iframe class="rounded-lg" width="100%" height="100%" src="https://www.youtube.com/embed/ANk2ZO6H1og?si=DgK_kV7NM-ydNhI6"> </iframe>
    </div>
    <div class="rounded-md">
      <iframe class="rounded-lg" width="100%" height="100%" src="https://www.youtube.com/embed/j6h7mbw00Z8"> </iframe>
    </div>
    <div class="rounded-md">
      <iframe class="rounded-lg" width="100%" height="100%" src="https://www.youtube.com/embed/QfANPxN8eHI?si=hKBlbdUyPRANcRPp"> </iframe>
    </div> -->
  </div>
  <a href="#" target="_blank"><button class="secao_botao mt-[60px]">Outros Vídeos</button></a>
</section>

<!--CLIPPING SECTION-->
<section class="section py-8">
  <div class="section-content">
    <div class="section_titulo">
      <h2>clipping</h2>
    </div>
    <div class="clippingSwiper clipping grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 mt-8 gap-4 overflow-hidden">
      <div class="swiper-wrapper" id="clipping-wrapper">
        <?php foreach ($clipping as $noticia): ?>
          <?php
          $dataClipping = !empty($noticia->dt_inclusao)
            ? date('d/m/Y', strtotime($noticia->dt_inclusao))
            : '';
          $textoClipping = trim(strip_tags((string) ($noticia->nm_texto ?? '')));
          $palavrasClipping = preg_split('/\s+/', $textoClipping, -1, PREG_SPLIT_NO_EMPTY);
          $resumoClipping = implode(' ', array_slice($palavrasClipping ?: [], 0, 20));
          ?>
          <a href="/comunicacao/clipping/<?php echo htmlspecialchars((string) $noticia->id, ENT_QUOTES, 'UTF-8'); ?>" class="swiper-slide">
            <div class="card-clipping rounded-t-[4px] rounded-b-[16px] mx-auto overflow-hidden shadow-lg border-2 text-center">
              <img class="w-full object-cover" style="height: 220px; min-height: 220px; max-height: 220px; overflow: hidden;" src="/adminlte-painel/img/noticias/<?php echo htmlspecialchars((string) $noticia->nm_imagem, ENT_QUOTES, 'UTF-8'); ?>" alt="Clipping">
              <div class="card-content px-3 py-3 lg:px-2 lg:py-3 pb-12 flex flex-col justify-between h-full">

                <div>
                  <div class="font-bold text-base text-left">
                    <?php echo htmlspecialchars((string) $noticia->nm_titulo, ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                  <p class="text-gray-700 text-[0.6rem] lg:text-xs text-justify">
                    <?php echo htmlspecialchars($dataClipping, ENT_QUOTES, 'UTF-8'); ?><br><br>
                  </p>
                  <p class="text-gray-700 text-[0.6rem] lg:text-xs text-justify">
                    <?php echo htmlspecialchars($resumoClipping, ENT_QUOTES, 'UTF-8'); ?> [...]
                  </p>
                </div>
                <div class="card-footer relative bottom-0 left-0 w-full mt-2">
                  <button class="hover:text-[#175761] hover:bg-white transition-all duration-200 ease-out inline-block w-full text-center py-2 text-white text-sm sm:text-base lg:text-lg font-extrabold bg-[#175761]">Saiba mais</button>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <a href="/comunicacao/clipping/" class="secao_botao self-center w-fit mt-[60px]">veja mais</a>
  </div>
</section>

<!--AREA INFOS-->
<!-- <section class="flex flex-col items-center py-12 text-gray-600 bg-[#ECECEC]">
  <h2 class="info_titulo tracking-wider lg:text-[2rem] sm:text-[1.5rem] text-[1.3rem] font-extrabold">Principais Documentos</h2>
  <div class="swiper-container doc_secao doc-swiper mt-[25px] px-4">
    <div class="swiper-wrapper">

      <?php foreach ($publicacoes as $publicacao): ?>
        <div class="swiper-slide">
          <a href="/includes/api/comunicacao/download_publicacoes.php?id=<?= htmlspecialchars((string) $publicacao->id, ENT_QUOTES, 'UTF-8') ?>" class="fancybox" data-fancybox data-type="iframe">
            <div class="max-w-[300px] bg-[#6F6F6F] text-white px-5 py-3 rounded mx-auto">
              <img class="w-full h-full object-cover" src="data:image/jpeg;base64,<?= htmlspecialchars((string) $publicacao->imagem, ENT_QUOTES, 'UTF-8') ?>" alt="Documento">
            </div>
          </a>
        </div>
      <?php endforeach; ?>

    </div>
    <!-- Adicione a navega��o se desejar -->
    <div class="swiper-pagination"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
  </div>
</section> -->
