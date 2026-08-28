<?php use App\Core\Auth; use App\Core\View; $logoUrl = org_logo_url(Auth::organizationId(), $company); ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Bon de commande <?= View::e($po['po_number']) ?></title>
<style>
  body { font-family: Arial, sans-serif; color: #222; margin: 40px; }
  .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
  table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  th, td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; }
  th { background: #f5f5f5; }
  .print-btn { margin-bottom: 20px; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
<div class="header">
  <div><?php if ($logoUrl): ?><img src="<?= View::e($logoUrl) ?>" alt="" style="max-height:60px; max-width:220px; display:block; margin-bottom:6px;"><?php endif; ?><h2><?= View::e($company['company_name']) ?></h2><p><?= View::e($company['address']) ?><br><?= View::e($company['postal_code']) ?> <?= View::e($company['city']) ?></p></div>
  <div><h3>Bon de commande <?= View::e($po['po_number']) ?></h3><p>Date : <?= View::date($po['issue_date']) ?><br>Fournisseur : <?= View::e($po['provider_name']) ?></p></div>
</div>
<table>
  <thead><tr><th>Description</th><th>Qté</th><th>Prix unitaire</th><th>Total</th></tr></thead>
  <tbody>
    <?php foreach ($items as $item): ?>
    <tr><td><?= View::e($item['description']) ?></td><td><?= View::e((string)$item['quantity']) ?></td><td><?= View::money((float)$item['unit_price']) ?></td><td><?= View::money((float)$item['total']) ?></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
<p style="text-align:right; font-size:1.2em; margin-top:20px;"><strong>Total : <?= View::money((float)$po['total']) ?></strong></p>
</body>
</html>
