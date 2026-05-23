(function($) {

  "use strict";

  var initPreloader = function() {
    $('body').addClass('preloader-site');
    $(window).on('load', function() {
      $('.preloader-wrapper').fadeOut();
      $('body').removeClass('preloader-site');
    });
  }

  var initChocolat = function() {
    if (typeof Chocolat !== 'undefined') {
      Chocolat(document.querySelectorAll('.image-link'), {
        imageSize: 'contain',
        loop: true,
      });
    }
  }

  var initSwiper = function() {
    if (typeof Swiper === 'undefined') {
      return;
    }

    new Swiper('.main-swiper', {
      speed: 500,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
    });

    new Swiper('.category-carousel', {
      slidesPerView: 6,
      spaceBetween: 30,
      speed: 500,
      navigation: {
        nextEl: '.category-carousel-next',
        prevEl: '.category-carousel-prev',
      },
      breakpoints: {
        0: { slidesPerView: 2 },
        768: { slidesPerView: 3 },
        991: { slidesPerView: 4 },
        1500: { slidesPerView: 6 },
      }
    });

    new Swiper('.brand-carousel', {
      slidesPerView: 4,
      spaceBetween: 30,
      speed: 500,
      navigation: {
        nextEl: '.brand-carousel-next',
        prevEl: '.brand-carousel-prev',
      },
      breakpoints: {
        0: { slidesPerView: 2 },
        768: { slidesPerView: 2 },
        991: { slidesPerView: 3 },
        1500: { slidesPerView: 4 },
      }
    });

    new Swiper('.products-carousel', {
      slidesPerView: 5,
      spaceBetween: 30,
      speed: 500,
      navigation: {
        nextEl: '.products-carousel-next',
        prevEl: '.products-carousel-prev',
      },
      breakpoints: {
        0: { slidesPerView: 1 },
        768: { slidesPerView: 3 },
        991: { slidesPerView: 4 },
        1500: { slidesPerView: 6 },
      }
    });
  }

  var initProductQty = function() {
    $('.product-qty').each(function() {
      var $el_product = $(this);

      $el_product.find('.quantity-right-plus').click(function(e) {
        e.preventDefault();
        var quantityInput = $el_product.find('.input-number');
        var quantity = parseInt(quantityInput.val()) || 0;
        quantityInput.val(quantity + 1);
      });

      $el_product.find('.quantity-left-minus').click(function(e) {
        e.preventDefault();
        var quantityInput = $el_product.find('.input-number');
        var quantity = parseInt(quantityInput.val()) || 0;
        if (quantity > 1) {
          quantityInput.val(quantity - 1);
        }
      });
    });
  }

  var bindCartAjaxForms = function() {
    document.querySelectorAll('form.ajax-cart-action').forEach(function(form) {
      form.removeEventListener('submit', handleAjaxCartSubmit);
      form.addEventListener('submit', handleAjaxCartSubmit);
    });
  }

  var handleAjaxCartSubmit = function(event) {
    event.preventDefault();
    var form = event.currentTarget;
    var formData = new FormData(form);

    fetch(form.action, {
      method: form.method || 'POST',
      body: formData,
      credentials: 'same-origin'
    })
      .then(function(response) {
        return response.json();
      })
      .then(function(data) {
        if (!data || data.success !== true) {
          console.warn('Cart update failed or response invalid', data);
          return;
        }

        if (data.cart_panel_html) {
          var cartPanel = document.querySelector('#cart-panel-content');
          if (cartPanel) {
            cartPanel.innerHTML = data.cart_panel_html;
          }
        }

        if (typeof data.cart_count !== 'undefined') {
          document.querySelectorAll('.cart-count').forEach(function(el) {
            el.textContent = data.cart_count;
          });
        }

        if (typeof data.cart_total !== 'undefined') {
          document.querySelectorAll('.cart-total').forEach(function(el) {
            el.textContent = data.cart_total;
          });
        }

        if (typeof toastr !== 'undefined' && data.message) {
          toastr.success(data.message);
        }

        bindCartAjaxForms();
        initProductQty();
      })
      .catch(function(error) {
        console.error('AJAX cart request failed', error);
      });
  }

  $(document).ready(function() {
    initPreloader();
    initSwiper();
    initProductQty();
    bindCartAjaxForms();
    initChocolat();

    if (typeof jarallax !== 'undefined') {
      jarallax(document.querySelectorAll('.jarallax'));
      jarallax(document.querySelectorAll('.jarallax-keep-img'), {
        keepImg: true,
      });
    }
  });

})(jQuery);