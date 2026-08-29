<?php use App\Core\View; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h2 class="h5 mb-0">Caisse<?php if (!empty($session['event_title'])): ?> — <?= View::e($session['event_title']) ?><?php endif; ?></h2>
    <span class="text-muted small">Ouverte par <?= View::e($session['opened_by_name'] ?? '') ?> · Fond de départ <?= View::money((float) $session['opening_float']) ?></span>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#movement-modal"><i class="bi bi-cash-coin"></i> Mouvement</button>
    <a href="<?= url('/pos/' . $session['id'] . '/close') ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-lock"></i> Clôturer</a>
    <a href="<?= url('/pos/sessions') ?>" class="btn btn-outline-secondary btn-sm">Historique</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <input type="text" id="product-search" class="form-control mb-3" placeholder="Rechercher un article...">
    <div id="product-grid" class="row g-2">
      <?php foreach ($products as $p): ?>
      <?php $outOfStock = $p['stock_quantity'] !== null && (int) $p['stock_quantity'] <= 0; ?>
      <div class="col-6 col-md-4 product-tile" data-name="<?= View::e(mb_strtolower($p['name'])) ?>" data-category="<?= View::e(mb_strtolower($p['category'])) ?>">
        <button type="button" class="btn btn-outline-dark w-100 h-100 text-start p-2 add-to-cart" <?= $outOfStock ? 'disabled' : '' ?>
          data-id="<?= $p['id'] ?>" data-name="<?= View::e($p['name']) ?>" data-price="<?= (float) $p['unit_price'] ?>" data-stock="<?= $p['stock_quantity'] === null ? '' : (int) $p['stock_quantity'] ?>">
          <div class="fw-semibold small text-truncate"><?= View::e($p['name']) ?></div>
          <div class="text-muted small"><?= View::money((float) $p['unit_price']) ?></div>
          <?php if ($p['stock_quantity'] !== null): ?>
            <div class="small <?= $outOfStock ? 'text-danger' : 'text-success' ?>"><?= $outOfStock ? 'Rupture' : (int) $p['stock_quantity'] . ' en stock' ?></div>
          <?php endif; ?>
        </button>
      </div>
      <?php endforeach; ?>
      <?php if (empty($products)): ?><p class="text-muted small">Aucun article au catalogue. <a href="<?= url('/products/create') ?>">Ajoutez-en un</a>.</p><?php endif; ?>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <h3 class="h6">Article personnalisé</h3>
        <div class="row g-2">
          <div class="col-6"><input type="text" id="custom-desc" class="form-control form-control-sm" placeholder="Description"></div>
          <div class="col-3"><input type="text" id="custom-price" class="form-control form-control-sm" placeholder="Prix"></div>
          <div class="col-3"><button type="button" id="add-custom" class="btn btn-sm btn-outline-primary w-100">Ajouter</button></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card" style="position:sticky; top:1rem;">
      <div class="card-header py-2 fw-semibold">Panier</div>
      <div class="card-body">
        <ul id="cart-list" class="list-group list-group-flush mb-3"></ul>
        <p id="cart-empty" class="text-muted small text-center py-3">Le panier est vide.</p>

        <div class="d-flex justify-content-between fs-5 fw-bold mb-3">
          <span>Total</span><span id="cart-total">0,00 €</span>
        </div>

        <form method="post" action="<?= url('/pos/' . $session['id'] . '/sell') ?>" id="checkout-form">
          <?= csrf_field() ?>
          <input type="hidden" name="cart_json" id="cart-json">

          <div class="mb-2">
            <label class="form-label small mb-1">Moyen de paiement</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="payment_method" id="pay-cash" value="cash" checked>
              <label class="btn btn-outline-secondary btn-sm" for="pay-cash"><i class="bi bi-cash"></i> Espèces</label>
              <input type="radio" class="btn-check" name="payment_method" id="pay-card" value="card">
              <label class="btn btn-outline-secondary btn-sm" for="pay-card"><i class="bi bi-credit-card"></i> Carte</label>
              <input type="radio" class="btn-check" name="payment_method" id="pay-other" value="other">
              <label class="btn btn-outline-secondary btn-sm" for="pay-other">Autre</label>
            </div>
          </div>

          <div class="mb-2">
            <input type="text" name="buyer_name" class="form-control form-control-sm" placeholder="Nom du client (optionnel)">
          </div>
          <div class="mb-3">
            <input type="email" name="buyer_email" class="form-control form-control-sm" placeholder="Email du client (envoi du ticket, optionnel)">
          </div>

          <button type="submit" id="checkout-btn" class="btn btn-primary btn-lg w-100" disabled>Encaisser</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="movement-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= url('/pos/' . $session['id'] . '/movement') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h3 class="modal-title h6">Mouvement de caisse</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2">
            <div class="btn-group w-100">
              <input type="radio" class="btn-check" name="type" id="move-in" value="in" checked>
              <label class="btn btn-outline-success btn-sm" for="move-in">Apport (+)</label>
              <input type="radio" class="btn-check" name="type" id="move-out" value="out">
              <label class="btn btn-outline-danger btn-sm" for="move-out">Retrait (-)</label>
            </div>
          </div>
          <div class="mb-2"><input type="text" name="amount" class="form-control" placeholder="Montant" required></div>
          <div><input type="text" name="reason" class="form-control" placeholder="Motif (optionnel)"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  var cart = [];
  var cartList = document.getElementById('cart-list');
  var cartEmpty = document.getElementById('cart-empty');
  var cartTotal = document.getElementById('cart-total');
  var checkoutBtn = document.getElementById('checkout-btn');
  var cartJson = document.getElementById('cart-json');

  function money(v) {
    return v.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' €';
  }

  function findLine(productId, description) {
    return cart.find(function (l) { return productId ? l.product_id === productId : l.description === description && !l.product_id; });
  }

  function render() {
    cartList.innerHTML = '';
    var total = 0;
    cart.forEach(function (line, idx) {
      total += line.quantity * line.unit_price;
      var li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center px-0 py-2';
      li.innerHTML =
        '<div class="flex-grow-1"><div class="small fw-semibold">' + line.description + '</div>' +
        '<div class="small text-muted">' + money(line.unit_price) + ' × <span class="fw-semibold">' + line.quantity + '</span></div></div>' +
        '<div class="d-flex align-items-center gap-1">' +
        '<button type="button" class="btn btn-sm btn-outline-secondary qty-dec" data-idx="' + idx + '">-</button>' +
        '<button type="button" class="btn btn-sm btn-outline-secondary qty-inc" data-idx="' + idx + '">+</button>' +
        '<button type="button" class="btn btn-sm btn-link text-danger remove-line" data-idx="' + idx + '"><i class="bi bi-trash"></i></button></div>';
      cartList.appendChild(li);
    });
    cartTotal.textContent = money(total);
    cartEmpty.classList.toggle('d-none', cart.length > 0);
    checkoutBtn.disabled = cart.length === 0;
    cartJson.value = JSON.stringify(cart);
  }

  function addLine(productId, description, unitPrice, stock) {
    var existing = findLine(productId, description);
    if (existing) {
      if (stock !== null && existing.quantity + 1 > stock) { alert('Stock insuffisant.'); return; }
      existing.quantity += 1;
    } else {
      if (stock !== null && stock <= 0) { alert('Stock insuffisant.'); return; }
      cart.push({ product_id: productId, description: description, quantity: 1, unit_price: unitPrice, stock: stock });
    }
    render();
  }

  document.querySelectorAll('.add-to-cart').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var stock = btn.dataset.stock === '' ? null : parseInt(btn.dataset.stock, 10);
      addLine(parseInt(btn.dataset.id, 10), btn.dataset.name, parseFloat(btn.dataset.price), stock);
    });
  });

  document.getElementById('add-custom').addEventListener('click', function () {
    var desc = document.getElementById('custom-desc').value.trim();
    var price = parseFloat((document.getElementById('custom-price').value || '0').replace(',', '.')) || 0;
    if (!desc) return;
    addLine(null, desc, price, null);
    document.getElementById('custom-desc').value = '';
    document.getElementById('custom-price').value = '';
  });

  cartList.addEventListener('click', function (e) {
    var incBtn = e.target.closest('.qty-inc');
    var decBtn = e.target.closest('.qty-dec');
    var rmBtn = e.target.closest('.remove-line');
    if (incBtn) {
      var line = cart[incBtn.dataset.idx];
      if (line.stock !== null && line.quantity + 1 > line.stock) { alert('Stock insuffisant.'); return; }
      line.quantity += 1;
    } else if (decBtn) {
      var line2 = cart[decBtn.dataset.idx];
      line2.quantity -= 1;
      if (line2.quantity <= 0) cart.splice(decBtn.dataset.idx, 1);
    } else if (rmBtn) {
      cart.splice(rmBtn.dataset.idx, 1);
    } else {
      return;
    }
    render();
  });

  document.getElementById('product-search').addEventListener('input', function (e) {
    var q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('.product-tile').forEach(function (tile) {
      var match = tile.dataset.name.includes(q) || tile.dataset.category.includes(q);
      tile.classList.toggle('d-none', !match);
    });
  });

  document.getElementById('checkout-form').addEventListener('submit', function (e) {
    if (cart.length === 0) { e.preventDefault(); return; }
    cartJson.value = JSON.stringify(cart);
  });

  render();
})();
</script>
