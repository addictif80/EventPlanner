<?php
use App\Core\View;
$logoUrl = org_logo_url($sale['organization_id'] ?? null, $company ?? []);
$brandColor = preg_match('/^#[0-9a-fA-F]{6}$/', $company['brand_color'] ?? '') ? $company['brand_color'] : '#3b56d9';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include __DIR__ . '/../partials/favicon.php'; ?>
<title>Ticket <?= View::e($sale['sale_number']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>.btn-primary{ --bs-btn-bg: <?= $brandColor ?>; --bs-btn-border-color: <?= $brandColor ?>; --bs-btn-hover-bg: <?= shade_color($brandColor, -12) ?>; --bs-btn-hover-border-color: <?= shade_color($brandColor, -12) ?>; }</style>
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
  <div class="card shadow-sm"><div class="card-body p-4 text-center">
    <?php if ($logoUrl): ?><img src="<?= View::e($logoUrl) ?>" alt="" style="max-height:56px; max-width:200px; margin-bottom:12px;"><?php else: ?><h5 class="mb-3"><?= View::e($company['company_name'] ?? '') ?></h5><?php endif; ?>

    <i class="bi bi-receipt fs-1 text-success"></i>
    <h1 class="h5 mt-2 mb-1">Ticket <?= View::e($sale['sale_number']) ?></h1>
    <p class="text-muted small mb-4"><?= View::date($sale['created_at'], 'd/m/Y à H:i') ?></p>

    <table class="table table-sm text-start">
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?= View::e($it['description']) ?></td>
          <td class="text-muted small text-nowrap"><?= View::e((string)(float)$it['quantity']) ?> × <?= View::money((float)$it['unit_price']) ?></td>
          <td class="text-end"><?= View::money((float)$it['total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot><tr><th colspan="2">Total</th><th class="text-end"><?= View::money((float) $sale['total']) ?></th></tr></tfoot>
    </table>

    <a href="<?= url('/pos-receipt/' . $token . '/download') ?>" class="btn btn-primary w-100 btn-lg mt-2"><i class="bi bi-download me-1"></i>Télécharger mon ticket (PDF)</a>
    <p class="text-muted small mt-3 mb-0">Conservez ce lien ou le QR code reçu en caisse pour retrouver ce ticket à tout moment.</p>
  </div></div>
</div>
</body>
</html>
