/* ============================================================
   GadgetZone — admin.js
   Product modal (create/edit), order viewer, sidebar, toasts.
   ============================================================ */
(function () {
  'use strict';

  function qs(s, c) { return (c || document).querySelector(s); }
  function qsa(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  // ---- Modal plumbing -------------------------------------------------------
  function openModal(html) {
    var overlay = qs('#modalOverlay');
    var box = qs('#modalBox');
    if (!overlay || !box) { return; }
    box.innerHTML = html;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    var overlay = qs('#modalOverlay');
    if (!overlay) { return; }
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }
  // Close on backdrop click / Esc.
  document.addEventListener('click', function (e) {
    if (e.target && e.target.id === 'modalOverlay') { closeModal(); }
    if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-modal-close')) { closeModal(); }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeModal(); } });

  // ---- Toast ----------------------------------------------------------------
  var tTimer = null;
  function toast(msg, type) {
    var el = qs('#toast');
    if (!el) { return; }
    el.textContent = msg;
    el.className = 'toast show' + (type ? ' ' + type : '');
    if (tTimer) { clearTimeout(tTimer); }
    tTimer = setTimeout(function () { el.className = 'toast'; }, 2600);
  }

  // ---- Product create / edit ------------------------------------------------
  function productForm(p) {
    p = p || {};
    var cats = window.GZ_CATEGORIES || [];
    var saveUrl = window.GZ_SAVE_URL || '';
    var isEdit = !!p.id;
    var badges = ['', 'NEW', 'HOT', 'SALE'];

    var catOptions = cats.map(function (c) {
      var sel = (String(p.category_id) === String(c.id)) ? ' selected' : '';
      return '<option value="' + c.id + '"' + sel + '>' + esc(c.name) + '</option>';
    }).join('');

    var badgeOptions = badges.map(function (b) {
      var sel = ((p.badge || '') === b) ? ' selected' : '';
      return '<option value="' + b + '"' + sel + '>' + (b || '— none —') + '</option>';
    }).join('');

    var img = p.image_url ? '<img src="' + esc(p.image_url) + '" alt="" id="pImgPreview" class="modal-img-preview">' :
      '<div class="modal-img-preview empty" id="pImgPreview">No image</div>';

    return '' +
      '<div class="modal-head">' +
        '<h3>' + (isEdit ? 'Edit Product' : 'Add Product') + '</h3>' +
        '<button class="modal-x" data-modal-close>&times;</button>' +
      '</div>' +
      '<form method="POST" action="' + esc(saveUrl) + '" enctype="multipart/form-data" class="modal-form">' +
        '<input type="hidden" name="action" value="save">' +
        '<input type="hidden" name="id" value="' + (p.id || '') + '">' +
        '<div class="field"><label>Product name</label>' +
          '<input type="text" name="name" required value="' + esc(p.name || '') + '"></div>' +
        '<div class="form-grid">' +
          '<div class="field"><label>Category</label><select name="category_id" required>' + catOptions + '</select></div>' +
          '<div class="field"><label>Badge</label><select name="badge">' + badgeOptions + '</select></div>' +
          '<div class="field"><label>Price (BDT)</label><input type="number" step="0.01" name="price" required value="' + esc(p.price || '') + '"></div>' +
          '<div class="field"><label>Old price (optional)</label><input type="number" step="0.01" name="old_price" value="' + esc(p.old_price || '') + '"></div>' +
          '<div class="field"><label>Stock</label><input type="number" name="stock" required value="' + (p.stock != null ? esc(p.stock) : '100') + '"></div>' +
          '<div class="field"><label>Featured</label><label class="switch-row"><input type="checkbox" name="featured" value="1"' + (Number(p.featured) ? ' checked' : '') + '> Show on homepage</label></div>' +
        '</div>' +
        '<div class="field"><label>Description</label><textarea name="description" rows="3">' + esc(p.description || '') + '</textarea></div>' +
        '<div class="field">' +
          '<label>Image URL</label>' +
          '<div class="img-url-row">' +
            '<input type="text" id="pImgUrl" name="image_url" value="' + esc(p.image_url || '') + '" placeholder="https://images.unsplash.com/…">' +
            '<button type="button" class="btn btn-ghost sm" id="pUnsplashBtn" title="Open Unsplash search in a new tab">🔍 Find on Unsplash</button>' +
          '</div>' +
          '<small class="muted img-url-hint">หา Unsplash → คลิกขวาที่รูป → <strong>Copy image address</strong> → paste กลับช่องนี้</small>' +
        '</div>' +
        '<div class="field"><label>…or upload from your computer (local only)</label><input type="file" name="image_file" accept="image/*" id="pImgFile"></div>' +
        '<div class="modal-img-wrap">' + img + '</div>' +
        '<div class="modal-foot">' +
          '<button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>' +
          '<button type="submit" class="btn btn-primary">' + (isEdit ? 'Save Changes' : 'Create Product') + '</button>' +
        '</div>' +
      '</form>';
  }

  function setPreviewSrc(src) {
    var prev = qs('#pImgPreview');
    if (!prev) { return; }
    if (prev.tagName === 'IMG') { prev.src = src; }
    else {
      var img = document.createElement('img');
      img.id = 'pImgPreview'; img.className = 'modal-img-preview'; img.src = src;
      prev.parentNode.replaceChild(img, prev);
    }
  }

  function wireImagePreview() {
    // File upload preview (local)
    var file = qs('#pImgFile');
    if (file) {
      file.addEventListener('change', function () {
        var f = file.files && file.files[0];
        if (!f) { return; }
        var reader = new FileReader();
        reader.onload = function (e) { setPreviewSrc(e.target.result); };
        reader.readAsDataURL(f);
      });
    }

    // Image URL live preview — updates on paste / input
    var urlInput = qs('#pImgUrl');
    if (urlInput) {
      var updateFromUrl = function () {
        var v = urlInput.value.trim();
        if (!v) { return; }
        // Warn if user pasted a Unsplash *photo page* URL instead of the direct image URL
        if (/^https?:\/\/unsplash\.com\/photos\//i.test(v)) {
          urlInput.style.borderColor = 'var(--red)';
          if (window.AdminUI && AdminUI.toast) {
            AdminUI.toast('ลิงก์นี้เป็นหน้ารายละเอียดของ Unsplash — โปรด right-click ที่รูป → "Copy image address"', 'error');
          }
          return;
        }
        urlInput.style.borderColor = '';
        if (/^https?:\/\//i.test(v)) { setPreviewSrc(v); }
      };
      urlInput.addEventListener('paste', function () { setTimeout(updateFromUrl, 30); });
      urlInput.addEventListener('input',  updateFromUrl);
      urlInput.addEventListener('blur',   updateFromUrl);
    }

    // "Find on Unsplash" — open search in new tab using current product name
    var unsplashBtn = qs('#pUnsplashBtn');
    if (unsplashBtn) {
      unsplashBtn.addEventListener('click', function () {
        var nameInput = document.querySelector('input[name="name"]');
        var q = (nameInput && nameInput.value.trim()) || 'gadget';
        var url = 'https://unsplash.com/s/photos/' + encodeURIComponent(q);
        window.open(url, '_blank', 'noopener');
      });
    }
  }

  var ProductAdmin = {
    openNew: function () { openModal(productForm({})); wireImagePreview(); },
    openEdit: function (p) { openModal(productForm(p)); wireImagePreview(); }
  };

  // ---- Order viewer ---------------------------------------------------------
  var OrderAdmin = {
    view: function (data) {
      var o = data.order || {};
      var items = data.items || [];
      var rows = items.map(function (it) {
        return '<div class="ord-item">' +
          (it.image ? '<img src="' + esc(it.image) + '" alt="">' : '') +
          '<div class="ord-item-info"><strong>' + esc(it.name) + '</strong>' +
          '<span>' + esc(it.price) + ' × ' + it.qty + '</span></div>' +
          '<span class="ord-item-sub">' + esc(it.sub) + '</span></div>';
      }).join('') || '<p class="muted">No line items recorded.</p>';

      var html = '' +
        '<div class="modal-head"><h3>Order ' + esc(o.order_number) + '</h3>' +
          '<button class="modal-x" data-modal-close>&times;</button></div>' +
        '<div class="ord-meta">' +
          '<div><span>Customer</span><strong>' + esc(o.customer) + '</strong></div>' +
          '<div><span>Email</span><strong>' + esc(o.email || '—') + '</strong></div>' +
          '<div><span>Date</span><strong>' + esc(o.date) + '</strong></div>' +
          '<div><span>Payment</span><strong>' + esc(o.payment) + '</strong></div>' +
          '<div><span>Status</span><strong>' + esc((o.status || '').charAt(0).toUpperCase() + (o.status || '').slice(1)) + '</strong></div>' +
        '</div>' +
        '<div class="ord-section"><h4>Shipping address</h4><p class="muted">' + esc(o.shipping_address || '—') + '</p></div>' +
        (o.notes ? '<div class="ord-section"><h4>Notes</h4><p class="muted">' + esc(o.notes) + '</p></div>' : '') +
        '<div class="ord-section"><h4>Items</h4>' + rows + '</div>' +
        '<div class="ord-total"><span>Total</span><strong>' + esc(o.total) + '</strong></div>' +
        '<div class="modal-foot"><button type="button" class="btn btn-primary" data-modal-close>Close</button></div>';
      openModal(html);
    }
  };

  // ---- Add User modal -------------------------------------------------------
  var UserAdmin = {
    openAdd: function () {
      var url = window.GZ_USERS_URL || '';
      var canPromote = !!window.GZ_CAN_PROMOTE;
      var roleSelect =
        '<select name="role">' +
          '<option value="member">Member</option>' +
          (canPromote ? '<option value="admin">Admin</option>' : '') +
        '</select>';
      var html = '' +
        '<div class="modal-head"><h3>Add User</h3>' +
          '<button class="modal-x" data-modal-close>&times;</button></div>' +
        '<form method="POST" action="' + esc(url) + '" class="modal-form">' +
          '<input type="hidden" name="action" value="add_user">' +
          '<div class="form-grid">' +
            '<div class="field"><label>First name</label><input type="text" name="first_name" required></div>' +
            '<div class="field"><label>Last name</label><input type="text" name="last_name" required></div>' +
          '</div>' +
          '<div class="field"><label>Email</label><input type="email" name="email" required></div>' +
          '<div class="field"><label>Password (min 6)</label><input type="password" name="password" minlength="6" required></div>' +
          '<div class="field"><label>Role</label>' + roleSelect + '</div>' +
          '<div class="modal-foot">' +
            '<button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>' +
            '<button type="submit" class="btn btn-primary">Create User</button>' +
          '</div>' +
        '</form>';
      openModal(html);
    }
  };

  // ---- Sidebar toggle (mobile) ----------------------------------------------
  function wireSidebar() {
    var btn = qs('#adminToggle');
    var bar = qs('#adminSidebar');
    if (btn && bar) {
      btn.addEventListener('click', function () { bar.classList.toggle('open'); });
    }
  }

  // ---- Expose + init --------------------------------------------------------
  window.ProductAdmin = ProductAdmin;
  window.OrderAdmin = OrderAdmin;
  window.UserAdmin = UserAdmin;
  window.AdminUI = { toast: toast, closeModal: closeModal };

  document.addEventListener('DOMContentLoaded', wireSidebar);
})();
