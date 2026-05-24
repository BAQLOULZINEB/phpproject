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

    document.querySelectorAll('.products-carousel').forEach(function(swiperEl) {
      var section = swiperEl.closest('section');
      new Swiper(swiperEl, {
        slidesPerView: 5,
        spaceBetween: 30,
        speed: 500,
        navigation: {
          nextEl: section ? section.querySelector('.products-carousel-next') : null,
          prevEl: section ? section.querySelector('.products-carousel-prev') : null,
        },
        breakpoints: {
          0: { slidesPerView: 1 },
          768: { slidesPerView: 3 },
          991: { slidesPerView: 4 },
          1500: { slidesPerView: 6 },
        }
      });
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

  var escapeHtml = function(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  var openProductPreviewModal = function(html) {
    var backdrop = document.createElement('div');
    backdrop.className = 'product-preview-backdrop';
    backdrop.innerHTML = html;
    document.body.appendChild(backdrop);
    document.body.style.overflow = 'hidden';

    var closeModal = function() {
      if (backdrop.parentNode) {
        backdrop.parentNode.removeChild(backdrop);
      }
      document.body.style.overflow = '';
    };

    var closeBtn = backdrop.querySelector('.product-preview-close');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', function(ev){ if (ev.target === backdrop) closeModal(); });
    document.addEventListener('keydown', function escHandler(ev){ if (ev.key === 'Escape') { closeModal(); document.removeEventListener('keydown', escHandler); } });

    bindCartAjaxForms();
    initProductQty();
  };

  var handleOpenProductPreview = function(e) {
    e.preventDefault();
    var url = e.currentTarget.href;
    if (!url || url.endsWith('#') || url.indexOf('index.php') !== -1) {
      return;
    }
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(res){ return res.text(); })
      .then(function(html){
        openProductPreviewModal(html);
      })
      .catch(function(err){ console.error('Failed to load product preview', err); });
  };

  var getCardPreviewDetails = function(card) {
    var name = card.querySelector('h3') ? card.querySelector('h3').textContent.trim() : 'Product';
    var price = card.querySelector('.price') ? card.querySelector('.price').textContent.trim() : '$0.00';
    var category = card.querySelector('.qty') ? card.querySelector('.qty').textContent.trim() : '';
    var image = card.querySelector('img') ? card.querySelector('img').src : '';
    var brand = card.querySelector('.brand') ? card.querySelector('.brand').textContent.trim() : '';
    var description = card.dataset.productDescription || 'Discover the premium formula and elegant benefits of this product in the quick view.';
    var id = 100000 + Array.from(name).reduce(function(sum, ch) { return sum + ch.charCodeAt(0); }, 0) % 899999;

    return {
      productId: id,
      name: name,
      price: price,
      category: category,
      brand: brand,
      image: image,
      description: description
    };
  };

  var buildStaticPreviewHtml = function(details) {
    return '\n      <div class="product-preview-modal" role="dialog" aria-modal="true">\n        <button class="product-preview-close" aria-label="Close">&times;</button>\n        <div class="media-image">\n          <img src="' + escapeHtml(details.image) + '" alt="' + escapeHtml(details.name) + '">\n        </div>\n        <div class="meta">\n          <h2>' + escapeHtml(details.name) + '</h2>\n          <div class="category">' + escapeHtml(details.brand ? details.brand + ' · ' : '') + escapeHtml(details.category) + '</div>\n          <div class="price">' + escapeHtml(details.price) + '</div>\n          <div class="rating">★★★★☆ <span class="text-muted">(4.5)</span></div>\n          <div class="availability text-success" style="margin-top:8px;">In stock</div>\n          <div class="desc">' + escapeHtml(details.description) + '</div>\n          <form action="ajouter_panier.php" method="POST" class="ajax-cart-action">\n            <input type="hidden" name="product_id" value="' + escapeHtml(details.productId) + '">\n            <input type="hidden" name="product_name" value="' + escapeHtml(details.name) + '">\n            <input type="hidden" name="product_price" value="' + escapeHtml(details.price.replace(/[^0-9.]/g, '')) + '">\n            <input type="hidden" name="add" value="1">\n            <div class="actions">\n              <input type="number" name="quantity" value="1" min="1" class="form-control w-25">\n              <button type="submit" class="btn btn-primary">Add to Cart</button>\n              <a href="effectuer_commande.php?buy=1&product_id=' + escapeHtml(details.productId) + '" class="btn btn-outline-primary">Buy Now</a>\n            </div>\n          </form>\n        </div>\n      </div>\n    ';
  };

  var handleStaticProductClick = function(e) {
    e.preventDefault();
    var anchor = e.currentTarget;
    var card = anchor.closest('.product-item');
    if (!card) {
      return;
    }
    var details = getCardPreviewDetails(card);
    var html = buildStaticPreviewHtml(details);
    openProductPreviewModal(html);
  };

  var initProductPreviewBindings = function() {
    document.querySelectorAll('a.open-product-preview').forEach(function(a){
      a.removeEventListener('click', handleOpenProductPreview);
      a.addEventListener('click', handleOpenProductPreview);
    });
    document.querySelectorAll('.product-item figure a').forEach(function(a){
      if (!a.classList.contains('open-product-preview')) {
        a.removeEventListener('click', handleStaticProductClick);
        a.addEventListener('click', handleStaticProductClick);
      }
    });
  };

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
    initProductPreviewBindings();
    initChocolat();

    if (typeof jarallax !== 'undefined') {
      jarallax(document.querySelectorAll('.jarallax'));
      jarallax(document.querySelectorAll('.jarallax-keep-img'), {
        keepImg: true,
      });
    }
  });

})(jQuery);