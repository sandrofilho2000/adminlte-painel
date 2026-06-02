document.addEventListener('DOMContentLoaded', function () {
    var swiper = new Swiper('.doc-swiper', {
        slidesPerView: 1,
        spaceBetween: 4,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        },
    });

    let noticiasWrapper = $('#noticias-wrapper');
    let clippingWrapper = $('#clipping-wrapper');

    // Inicializa os carrosséis após a adição dos itens
    var noticiasCard = new Swiper(".noticiasSwiper", {
        spaceBetween: 20,
        slidesPerView: 1,
        slidesPerGroup: 1,
        breakpoints: {
            '520': {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            '648': {
                slidesPerView: 3,
                spaceBetween: 20,
            },
        },
        on: {
            init: function () {
                // Remove any height applied to swiper-slide within card-noticias
                $('.card-noticias.swiper-slide').css('height', '');
            },
            resize: function () {
                // Ensure height is removed on resize as well
                $('.card-noticias.swiper-slide').css('height', '');
            }
        }
    });

    var clippingCard = new Swiper(".clippingSwiper", {
        spaceBetween: 50,
        slidesPerView: 1,
        slidesPerGroup: 1,
        breakpoints: {
            '520': {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            '648': {
                slidesPerView: 3,
                spaceBetween: 20,
            },
        }
    });
});

$(document).ready(function () {


    $.ajax({
        url: '/includes/api/api_quantos.php',
        type: 'GET',
        success: function (data) {
            if (data.error) {
                console.error(data.error);
                return;
            }

            $('#quantos-somos-fisica').text(data.quantos_somos_pf.toLocaleString('pt-BR'));
            $('#quantos-somos-juridica').text(data.quantos_somos_pj.toLocaleString('pt-BR'));

            window.addEventListener('scroll', function () {
                let elemento = document.querySelector('.somos-texto');
                let elementoPosicao = elemento.getBoundingClientRect().top;

                if (elementoPosicao < window.innerHeight && !elemento.dataset.visivel) {
                    animarValorCONFEF(document.getElementById('quantos-somos-fisica'), 0, data.quantos_somos_pf, 2000, 10000);
                    animarValorCONFEF(document.getElementById('quantos-somos-juridica'), 0, data.quantos_somos_pj, 2000, 5000);
                    elemento.dataset.visivel = true;
                }
            });
        }
    });

    // Inicializar o FancyBox
    $("[data-fancybox]").fancybox({
        iframe: {
            css: {
                width: '80%',
                height: '90%'
            }
        }
    });
    let carrouselLista = $('#carrousel-lista');

    startCarrousel({
        autoPlay: true,
        intervalo: 5000
    });

});
