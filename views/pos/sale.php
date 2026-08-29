<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Vente <?= View::e($sale['sale_number']) ?></h2>
  <a href="<?= url('/pos/' . $sale['pos_session_id']) ?>" class="btn btn-primary btn-sm">Nouvelle vente</a>
</div>

<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>Vente enregistrée — <?= View::money((float) $sale['total']) ?><?php if (!empty($sale['buyer_email'])): ?> — ticket envoyé à <?= View::e($sale['buyer_email']) ?><?php endif; ?></div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h3 class="h6">Détail</h3>
      <table class="table table-sm">
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr><td><?= View::e($it['description']) ?></td><td class="text-end"><?= View::e((string)(float)$it['quantity']) ?> × <?= View::money((float)$it['unit_price']) ?></td><td class="text-end"><?= View::money((float)$it['total']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="2">Total</th><th class="text-end"><?= View::money((float) $sale['total']) ?></th></tr></tfoot>
      </table>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body text-center">
      <h3 class="h6">Ticket client</h3>
      <p class="text-muted small">Faites scanner ce QR code par le client pour qu'il retrouve et télécharge son ticket sur son téléphone, en autonomie.</p>
      <div id="pos-qr" class="d-flex justify-content-center my-3"></div>
      <p class="small"><a href="<?= View::e($receiptUrl) ?>" target="_blank" rel="noopener"><?= View::e($receiptUrl) ?></a></p>
      <a href="<?= url('/pos/sales/' . $sale['id'] . '/pdf') ?>" class="btn btn-outline-dark btn-sm"><i class="bi bi-download"></i> Télécharger / imprimer le ticket</a>
    </div></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('pos-qr'), { text: <?= json_encode($receiptUrl) ?>, width: 160, height: 160 });
</script>
