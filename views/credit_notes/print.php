<?php use App\Core\View; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Avoir <?= View::e($creditNote['credit_note_number']) ?></title>
<style>
  body { font-family: Arial, sans-serif; color: #222; margin: 40px; }
  .print-btn { margin-bottom: 20px; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
<h2><?= View::e($company['company_name']) ?></h2>
<h3>Avoir <?= View::e($creditNote['credit_note_number']) ?></h3>
<p>Date : <?= View::date($creditNote['issue_date']) ?><br>
Relatif à la facture : <?= View::e($creditNote['invoice_number']) ?><br>
Client : <?= View::e($creditNote['company_name'] ?: trim($creditNote['first_name'] . ' ' . $creditNote['last_name'])) ?></p>
<p style="font-size:1.3em; margin-top:30px;">Montant de l'avoir : <strong><?= View::money((float)$creditNote['amount']) ?></strong></p>
<?php if (!empty($creditNote['reason'])): ?><p>Motif : <?= View::e($creditNote['reason']) ?></p><?php endif; ?>
</body>
</html>
