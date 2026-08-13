<?php use App\Core\View; ?>
<div style="font-family: Arial, sans-serif; color:#222; max-width:600px; margin:auto;">
  <h2><?= View::e($company['company_name']) ?></h2>
  <p>Bonjour,</p>
  <p><?= nl2br(View::e($intro ?? 'Nous vous rappelons que la facture suivante reste impayée à ce jour.')) ?></p>
  <table style="width:100%; border-collapse: collapse; margin:20px 0;">
    <tr><td style="padding:8px; border-bottom:1px solid #eee;">Facture</td><td style="padding:8px; border-bottom:1px solid #eee;"><strong><?= View::e($invoice['invoice_number']) ?></strong></td></tr>
    <tr><td style="padding:8px; border-bottom:1px solid #eee;">Échéance</td><td style="padding:8px; border-bottom:1px solid #eee;"><?= View::date($invoice['due_date']) ?></td></tr>
    <tr><td style="padding:8px; border-bottom:1px solid #eee;">Montant total</td><td style="padding:8px; border-bottom:1px solid #eee;"><?= View::money((float)$invoice['total']) ?></td></tr>
    <tr><td style="padding:8px; border-bottom:1px solid #eee;">Déjà réglé</td><td style="padding:8px; border-bottom:1px solid #eee;"><?= View::money((float)$invoice['amount_paid']) ?></td></tr>
    <tr><td style="padding:8px;"><strong>Reste dû</strong></td><td style="padding:8px;"><strong><?= View::money((float)$invoice['total'] - (float)$invoice['amount_paid']) ?></strong></td></tr>
  </table>
  <p>Cordialement,<br><?= View::e($company['company_name']) ?></p>
</div>
