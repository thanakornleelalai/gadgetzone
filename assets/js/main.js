/* ============================================================
   GadgetZone — main.js
   Storefront interactivity: cart AJAX, quantity steppers,
   deal countdown, reveal-on-scroll, nav toggle, toasts.
   No localStorage is used anywhere (per spec).
   ============================================================ */
(function () {
  'use strict';

  // ---- Base path resolution -------------------------------------------------
  // Prefer the server-rendered <body data-base>; fall back to the path heuristic.
  var _BASE = (document.body && document.body.dataset && typeof document.body.dataset.base === 'string')
    ? document.body.dataset.base
    : (window.location.pathname.indexOf('/gadget') === 0 ? '/gadget' : '');

  function endpoint(path) {
    return _BASE + path;
  }

  // ---- Tiny helpers ---------------------------------------------------------
  function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
  function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

  function postForm(path, data) {
    var body = new URLSearchParams();
    Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    return fetch(endpoint(path), {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: body
    }).then(function (r) { return r.json(); });
  }

  // ---- Toast ----------------------------------------------------------------
  var toastTimer = null;
  function toast(message, type) {
    var el = qs('#toast');
    if (!el) { return; }
    el.textContent = message;
    el.className = 'toast show' + (type ? ' ' + type : '');
    if (toastTimer) { clearTimeout(toastTimer); }
    toastTimer = setTimeout(function () { el.className = 'toast'; }, 2600);
  }

  // ---- Cart badge -----------------------------------------------------------
  function updateCartBadge(count) {
    qsa('.cart-badge').forEach(function (b) {
      b.textContent = count;
      b.style.display = count > 0 ? '' : 'none';
    });
  }

  // ---- Add to cart ----------------------------------------------------------
  function addToCart(id, qty, btn) {
    if (btn) { btn.classList.add('is-loading'); btn.disabled = true; }
    postForm('/pages/cart_action.php', { action: 'add', product_id: id, quantity: qty || 1 })
      .then(function (res) {
        if (res && res.success) {
          updateCartBadge(res.cart_count);
          toast(res.message || 'Added to cart', 'success');
        } else {
          toast((res && res.message) || 'Could not add item', 'error');
        }
      })
      .catch(function () { toast('Network error — please try again', 'error'); })
      .finally(function () {
        if (btn) { btn.classList.remove('is-loading'); btn.disabled = false; }
      });
  }

  // ---- Quantity steppers (generic) -----------------------------------------
  // Works for any .qty-control containing .qty-minus / .qty-input / .qty-plus
  function wireQtySteppers() {
    qsa('.qty-control').forEach(function (ctrl) {
      var input = qs('.qty-input', ctrl);
      var minus = qs('.qty-minus', ctrl);
      var plus  = qs('.qty-plus', ctrl);
      if (!input) { return; }

      function clamp(v) {
        var min = parseInt(input.min || '1', 10);
        var max = parseInt(input.max || '99', 10);
        if (isNaN(v) || v < min) { v = min; }
        if (v > max) { v = max; }
        return v;
      }
      function commit() {
        input.value = clamp(parseInt(input.value, 10));
        // On the cart page the input doubles as a live updater.
        if (input.classList.contains('cart-qty')) { cartLineUpdate(input); }
      }
      if (minus) { minus.addEventListener('click', function () { input.value = clamp(parseInt(input.value, 10) - 1); commit(); }); }
      if (plus)  { plus.addEventListener('click', function () { input.value = clamp(parseInt(input.value, 10) + 1); commit(); }); }
      input.addEventListener('change', commit);
    });
  }

  // ---- Cart page: live line update -----------------------------------------
  function applyTotals(res) {
    var s = qs('#sumSubtotal'); if (s) { s.textContent = res.formatted_subtotal; }
    var sh = qs('#sumShipping'); if (sh) { sh.textContent = res.formatted_shipping; }
    var t = qs('#sumTotal'); if (t) { t.textContent = res.formatted_total; }
    var prog = qs('#shipProgress');
    if (prog) {
      if (res.remaining_for_free > 0) {
        prog.style.display = '';
        var rem = qs('#sumRemaining'); if (rem) { rem.textContent = res.formatted_remaining; }
      } else {
        prog.style.display = 'none';
      }
    }
    updateCartBadge(res.cart_count);
  }

  function cartLineUpdate(input) {
    var id = input.getAttribute('data-id');
    var qty = parseInt(input.value, 10) || 1;
    postForm('/pages/cart_action.php', { action: 'update', product_id: id, quantity: qty })
      .then(function (res) {
        if (!res || !res.success) { return; }
        var row = input.closest('.cart-row');
        if (row && res.item_subtotal) {
          var cell = qs('.ci-subtotal', row);
          if (cell) { cell.textContent = res.item_subtotal; }
        }
        applyTotals(res);
      })
      .catch(function () { toast('Could not update cart', 'error'); });
  }

  // ---- Cart page: AJAX remove (with native form fallback) -------------------
  function wireRemoveForms() {
    qsa('.remove-form').forEach(function (form) {
      form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var idField = form.querySelector('input[name="remove_id"]');
        var id = idField ? idField.value : null;
        if (!id) { form.submit(); return; }
        postForm('/pages/cart_action.php', { action: 'remove', product_id: id })
          .then(function (res) {
            if (!res || !res.success) { form.submit(); return; }
            var row = form.closest('.cart-row');
            if (row) {
              row.classList.add('removing');
              setTimeout(function () {
                row.parentNode.removeChild(row);
                if (qsa('.cart-row').length === 0) { window.location.reload(); }
              }, 280);
            }
            applyTotals(res);
            toast('Item removed', 'success');
          })
          .catch(function () { form.submit(); });
      });
    });
  }

  // ---- Newsletter / coupon (light stubs) -----------------------------------
  function subscribe(ev) {
    if (ev) { ev.preventDefault(); }
    var form = ev ? ev.target : null;
    var emailField = form ? form.querySelector('input[type="email"]') : null;
    if (emailField && !emailField.value) { toast('Enter your email first', 'error'); return false; }
    if (form) { form.reset(); }
    toast('Thanks for subscribing! 🎉', 'success');
    return false;
  }

  function applyCoupon() {
    var input = qs('#couponInput');
    var code = input ? input.value.trim() : '';
    if (!code) { toast('Enter a coupon code', 'error'); return; }
    toast('Coupon “' + code + '” is not valid', 'error');
  }

  // ---- Deal countdown -------------------------------------------------------
  function wireCountdown() {
    var box = qs('#dealCountdown');
    if (!box) { return; }
    var h = qs('[data-cd="h"]', box);
    var m = qs('[data-cd="m"]', box);
    var s = qs('[data-cd="s"]', box);
    // Counts down to the next local midnight, then rolls over.
    function tick() {
      var now = new Date();
      var end = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 0);
      var diff = Math.max(0, Math.floor((end - now) / 1000));
      var hh = Math.floor(diff / 3600);
      var mm = Math.floor((diff % 3600) / 60);
      var ss = diff % 60;
      if (h) { h.textContent = String(hh).padStart(2, '0'); }
      if (m) { m.textContent = String(mm).padStart(2, '0'); }
      if (s) { s.textContent = String(ss).padStart(2, '0'); }
    }
    tick();
    setInterval(tick, 1000);
  }

  // ---- Nav toggle (mobile) --------------------------------------------------
  function wireNav() {
    var btn = qs('#navToggle');
    var nav = qs('#mainNav');
    if (btn && nav) {
      btn.addEventListener('click', function () { nav.classList.toggle('open'); });
    }
  }

  // ---- Reveal on scroll -----------------------------------------------------
  function wireReveal() {
    var items = qsa('.reveal');
    if (!('IntersectionObserver' in window) || !items.length) {
      items.forEach(function (el) { el.classList.add('in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    items.forEach(function (el) { io.observe(el); });
  }

  // ---- Avatar preview (account page) ---------------------------------------
  function wireAvatarPreview() {
    var input = qs('#avatarInput');
    var preview = qs('#avatarPreview');
    if (!input) { return; }
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file || !preview) { return; }
      var reader = new FileReader();
      reader.onload = function (e) {
        if (preview.tagName === 'IMG') {
          preview.src = e.target.result;
        } else {
          // Swap the initials placeholder for an <img>.
          var img = document.createElement('img');
          img.className = preview.className.replace('account-avatar', 'account-avatar-img');
          img.id = 'avatarPreview';
          img.src = e.target.result;
          img.alt = 'avatar';
          preview.parentNode.replaceChild(img, preview);
        }
      };
      reader.readAsDataURL(file);
    });
  }

  // ---- Global click delegation for add-to-cart ------------------------------
  function wireAddButtons() {
    document.addEventListener('click', function (ev) {
      var btn = ev.target.closest ? ev.target.closest('.add-to-cart') : null;
      if (!btn) { return; }
      ev.preventDefault();
      var id = btn.getAttribute('data-id');
      if (!id) { return; }
      var qty = 1;
      if (btn.hasAttribute('data-qty-source')) {
        var holder = btn.closest('.pd-actions') || btn.closest('.product-detail') || document;
        var qInput = qs('.qty-input', holder);
        if (qInput) { qty = parseInt(qInput.value, 10) || 1; }
      }
      addToCart(id, qty, btn);
    });
  }

  // ---- Public API -----------------------------------------------------------
  window.GZ = {
    addToCart: addToCart,
    updateCartBadge: updateCartBadge,
    toast: toast,
    subscribe: subscribe,
    applyCoupon: applyCoupon
  };

  // ---- Init -----------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', function () {
    wireAddButtons();
    wireQtySteppers();
    wireRemoveForms();
    wireCountdown();
    wireNav();
    wireReveal();
    wireAvatarPreview();
  });
})();
