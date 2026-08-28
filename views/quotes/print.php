<?php use App\Core\Auth; use App\Core\View; $logoUrl = org_logo_url(Auth::organizationId(), $company); ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Devis <?= View::e($quote['quote_number']) ?></title>
<style>
  body { font-family: Arial, sans-serif; color: #222; margin: 40px; }
  .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
  .company h2 { margin: 0 0 6px; }
  table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  th, td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; }
  th { background: #f5f5f5; }
  .totals { width: 300px; margin-left: auto; margin-top: 20px; }
  .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
  .totals .grand { font-weight: bold; font-size: 1.2em; border-top: 2px solid #333; margin-top: 6px; padding-top: 8px; }
  .print-btn { margin-bottom: 20px; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Imprimer / Enregistrer en PDF</button>

<div class="header">
  <div class="company">
    <?php if ($logoUrl): ?><img src="<?= View::e($logoUrl) ?>" alt="" style="max-height:60px; max-width:220px; margin-bottom:8px;"><?php endif; ?>
    <h2><?= View::e($company['company_name']) ?></h2>
    <p><?= View::e($company['address']) ?><br>
    <?= View::e($company['postal_code']) ?> <?= View::e($company['city']) ?><br>
    <?= View::e($company['email']) ?> — <?= View::e($company['phone']) ?><br>
    <?php if (!empty($company['siret'])): ?>SIRET : <?= View::e($company['siret']) ?><?php endif; ?></p>
  </div>
  <div class="client">
    <h3>Devis <?= View::e($quote['quote_number']) ?></h3>
    <p>Date : <?= View::date($quote['issue_date']) ?><br>
    Valable jusqu'au : <?= View::date($quote['valid_until']) ?></p>
    <p><strong>Client</strong><br>
    <?= View::e($quote['company_name'] ?: trim($quote['first_name'] . ' ' . $quote['last_name'])) ?><br>
    <?= View::e($quote['address']) ?><br>
    <?= View::e($quote['postal_code']) ?> <?= View::e($quote['city']) ?></p>
  </div>
</div>

<table>
  <thead><tr><th>Description</th><th>Qté</th><th>Prix unitaire</th><th>Total</th></tr></thead>
  <tbody>
    <?php foreach ($items as $item): ?>
    <tr>
      <td><?= View::e($item['description']) ?></td>
      <td><?= View::e((string)$item['quantity']) ?></td>
      <td><?= View::money((float)$item['unit_price']) ?></td>
      <td><?= View::money((float)$item['total']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="totals">
  <div><span>Sous-total</span><span><?= View::money((float)$quote['subtotal']) ?></span></div>
  <div><span>TVA (<?= View::e((string)$quote['tax_rate']) ?>%)</span><span><?= View::money((float)$quote['tax_amount']) ?></span></div>
  <div class="grand"><span>Total TTC</span><span><?= View::money((float)$quote['total']) ?></span></div>
</div>

<?php if (!empty($quote['notes'])): ?>
  <p style="margin-top:30px;"><?= nl2br(View::e($quote['notes'])) ?></p>
<?php endif; ?>
</body>
</html>
