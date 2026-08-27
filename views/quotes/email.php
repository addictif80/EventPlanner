<?php
use App\Core\Auth;
use App\Core\View;
$logoUrl = org_logo_url(Auth::organizationId(), $company);
$brandColor = preg_match('/^#[0-9a-fA-F]{6}$/', $company['brand_color'] ?? '') ? $company['brand_color'] : '#3b56d9';
?>
<div style="font-family: Arial, sans-serif; color:#222; max-width:600px; margin:auto;">
  <?php if ($logoUrl): ?>
    <img src="<?= View::e($logoUrl) ?>" alt="<?= View::e($company['company_name']) ?>" style="max-height:56px; max-width:220px; margin-bottom:12px;">
  <?php else: ?>
    <h2><?= View::e($company['company_name']) ?></h2>
  <?php endif; ?>
  <p>Bonjour,</p>
  <p><?= nl2br(View::e($intro ?? 'Veuillez trouver ci-dessous le récapitulatif de votre devis.')) ?> (Devis <strong><?= View::e($quote['quote_number']) ?></strong>, valable jusqu'au <?= View::date($quote['valid_until']) ?>.)</p>
  <table style="width:100%; border-collapse: collapse; margin:20px 0;">
    <thead><tr style="background:#f5f5f5;"><th style="text-align:left; padding:8px;">Description</th><th style="padding:8px;">Qté</th><th style="padding:8px;">Prix unitaire</th><th style="padding:8px;">Total</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td style="padding:8px; border-bottom:1px solid #eee;"><?= View::e($item['description']) ?></td>
        <td style="padding:8px; border-bottom:1px solid #eee;"><?= View::e((string)$item['quantity']) ?></td>
        <td style="padding:8px; border-bottom:1px solid #eee;"><?= View::money((float)$item['unit_price']) ?></td>
        <td style="padding:8px; border-bottom:1px solid #eee;"><?= View::money((float)$item['total']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p><strong style="color:<?= View::e($brandColor) ?>;">Total TTC : <?= View::money((float)$quote['total']) ?></strong></p>
  <?php if (!empty($quote['notes'])): ?><p><?= nl2br(View::e($quote['notes'])) ?></p><?php endif; ?>
  <p>Cordialement,<br><?= View::e($company['company_name']) ?></p>
  <p style="color:#999; font-size:11px; margin-top:24px;">Propulsé par EventPlanner</p>
</div>
