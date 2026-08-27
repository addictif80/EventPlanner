<?php use App\Core\View; ?>
<p>Bonjour <?= View::e(\App\Models\Guest::displayName($guest)) ?>,</p>
<p>Voici votre billet pour <strong><?= View::e($event['title']) ?></strong>, en pièce jointe au format PDF.</p>
<table style="width:100%; border-collapse: collapse; margin:20px 0;">
  <?php if (!empty($event['event_date'])): ?>
  <tr><td style="padding:8px 8px 8px 0; color:#8a909c; width:120px;">Date</td><td style="padding:8px;"><?= View::date($event['event_date']) ?></td></tr>
  <?php endif; ?>
  <?php $venue = trim(($event['venue_name'] ?? '') ?: ($event['location'] ?? '')); if ($venue !== ''): ?>
  <tr><td style="padding:8px 8px 8px 0; color:#8a909c;">Lieu</td><td style="padding:8px;"><?= View::e($venue) ?></td></tr>
  <?php endif; ?>
  <?php foreach ($tickets as $t): ?>
  <tr><td style="padding:8px 8px 8px 0; color:#8a909c;">Billet</td><td style="padding:8px;"><?= View::e($t['category_name'] ?? 'Standard') ?> — code <strong><?= View::e($t['code']) ?></strong></td></tr>
  <?php endforeach; ?>
</table>
<p>Présentez ce billet — imprimé ou directement depuis votre smartphone — à l'entrée : son QR code sera scanné pour valider votre accès.</p>
<p>Nous avons hâte de vous accueillir.</p>
