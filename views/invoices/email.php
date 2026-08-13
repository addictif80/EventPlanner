<?php use App\Core\View; ?>
<div style="font-family: Arial, sans-serif; color:#222; max-width:600px; margin:auto;">
  <h2><?= View::e($company['company_name']) ?></h2>
  <p>Bonjour,</p>
  <p><?= nl2br(View::e($intro ?? 'Veuillez trouver ci-dessous votre facture.')) ?> (Facture <strong><?= View::e($invoice['invoice_number']) ?></strong>, à régler avant le <?= View::date($invoice['due_date']) ?>.)</p>
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
  <p><strong>Total TTC : <?= View::money((float)$invoice['total']) ?></strong></p>
  <?php if (!empty($invoice['notes'])): ?><p><?= nl2br(View::e($invoice['notes'])) ?></p><?php endif; ?>
  <p>Cordialement,<br><?= View::e($company['company_name']) ?></p>
</div>
