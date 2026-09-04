<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\ModuleAccess;
use App\Core\View;
use App\Models\Event;

class ReportController
{
    public static function profitability(): void
    {
        ModuleAccess::requireModule('reports');
        $rows = Event::profitabilityForAll();

        View::render('reports/profitability', [
            'title' => 'Rentabilité par événement',
            'rows' => $rows,
        ]);
    }

    public static function index(): void
    {
        ModuleAccess::requireModule('reports');
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
             FROM payments
             WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND organization_id = ?
             GROUP BY ym ORDER BY ym ASC"
        );
        $stmt->execute([$orgId]);
        $revenueByMonth = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(et.name, 'Non catégorisé') AS label, SUM(p.amount) AS total
             FROM payments p
             JOIN invoices i ON i.id = p.invoice_id
             LEFT JOIN events e ON e.id = i.event_id
             LEFT JOIN event_types et ON et.id = e.event_type_id
             WHERE p.organization_id = ?
             GROUP BY label ORDER BY total DESC"
        );
        $stmt->execute([$orgId]);
        $revenueByEventType = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(NULLIF(c.company_name, ''), CONCAT(c.first_name, ' ', c.last_name)) AS label, SUM(p.amount) AS total
             FROM payments p JOIN invoices i ON i.id = p.invoice_id JOIN clients c ON c.id = i.client_id
             WHERE p.organization_id = ?
             GROUP BY c.id ORDER BY total DESC LIMIT 8"
        );
        $stmt->execute([$orgId]);
        $topClients = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT p.name AS label, SUM(ep.cost) AS total
             FROM event_providers ep JOIN providers p ON p.id = ep.provider_id
             WHERE ep.cost IS NOT NULL AND ep.organization_id = ?
             GROUP BY p.id ORDER BY total DESC LIMIT 8"
        );
        $stmt->execute([$orgId]);
        $topProviders = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT status, COUNT(*) AS c FROM quotes WHERE organization_id = ? GROUP BY status");
        $stmt->execute([$orgId]);
        $quoteStats = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        $sentOrDecided = ($quoteStats['sent'] ?? 0) + ($quoteStats['accepted'] ?? 0) + ($quoteStats['refused'] ?? 0) + ($quoteStats['expired'] ?? 0);
        $conversionRate = $sentOrDecided > 0 ? round((($quoteStats['accepted'] ?? 0) / $sentOrDecided) * 100, 1) : 0;

        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(due_date, '%Y-%m') AS ym, SUM(total - amount_paid) AS total
             FROM invoices
             WHERE status IN ('sent', 'partially_paid', 'overdue') AND due_date IS NOT NULL AND organization_id = ?
             GROUP BY ym ORDER BY ym ASC LIMIT 12"
        );
        $stmt->execute([$orgId]);
        $forecast = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT AVG(rating) FROM satisfaction_surveys WHERE rating IS NOT NULL AND organization_id = ?");
        $stmt->execute([$orgId]);
        $satisfactionAvg = $stmt->fetchColumn();

        View::render('reports/index', [
            'title' => 'Rapports & statistiques',
            'revenueByMonth' => $revenueByMonth,
            'revenueByEventType' => $revenueByEventType,
            'topClients' => $topClients,
            'topProviders' => $topProviders,
            'quoteStats' => $quoteStats,
            'conversionRate' => $conversionRate,
            'forecast' => $forecast,
            'satisfactionAvg' => $satisfactionAvg ? round((float) $satisfactionAvg, 1) : null,
        ]);
    }

    /**
     * Aide à la déclaration URSSAF (micro-entreprise / auto-entrepreneur) :
     * le chiffre d'affaires déclarable est celui réellement ENCAISSÉ sur la
     * période (base de trésorerie), pas celui facturé — cette page calcule
     * donc les mêmes montants que revenueByMonth ci-dessus (payments.payment_date),
     * simplement présentés par mois et par trimestre pour une année choisie.
     * Elle ne calcule volontairement aucun montant de cotisations : les taux
     * évoluent chaque année et une valeur ici deviendrait vite fausse —
     * seul le chiffre d'affaires encaissé (le montant réellement à déclarer)
     * est fourni, avec un renvoi vers l'URSSAF pour le taux en vigueur.
     */
    public static function urssaf(): void
    {
        ModuleAccess::requireModule('reports');
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $availableYearsStmt = $pdo->prepare('SELECT DISTINCT YEAR(payment_date) AS y FROM payments WHERE organization_id = ? ORDER BY y DESC');
        $availableYearsStmt->execute([$orgId]);
        $availableYears = array_column($availableYearsStmt->fetchAll(), 'y');
        if (empty($availableYears)) {
            $availableYears = [(int) date('Y')];
        }

        $year = (int) ($_GET['year'] ?? date('Y'));
        if (!in_array($year, $availableYears, true)) {
            $year = $availableYears[0];
        }

        $stmt = $pdo->prepare(
            "SELECT MONTH(payment_date) AS m, SUM(amount) AS total
             FROM payments
             WHERE YEAR(payment_date) = ? AND organization_id = ?
             GROUP BY m"
        );
        $stmt->execute([$year, $orgId]);
        $byMonth = array_fill(1, 12, 0.0);
        foreach ($stmt->fetchAll() as $row) {
            $byMonth[(int) $row['m']] = (float) $row['total'];
        }

        $byQuarter = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
        foreach ($byMonth as $month => $total) {
            $byQuarter[(int) ceil($month / 3)] += $total;
        }

        View::render('reports/urssaf', [
            'title' => 'Aide à la déclaration URSSAF',
            'company' => \App\Models\CompanySettings::get(),
            'year' => $year,
            'availableYears' => $availableYears,
            'byMonth' => $byMonth,
            'byQuarter' => $byQuarter,
            'yearTotal' => array_sum($byMonth),
        ]);
    }

    public static function urssafExport(): void
    {
        ModuleAccess::requireModule('reports');
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $year = (int) ($_GET['year'] ?? date('Y'));

        $stmt = $pdo->prepare(
            "SELECT MONTH(payment_date) AS m, SUM(amount) AS total
             FROM payments WHERE YEAR(payment_date) = ? AND organization_id = ? GROUP BY m"
        );
        $stmt->execute([$year, $orgId]);
        $byMonth = array_fill(1, 12, 0.0);
        foreach ($stmt->fetchAll() as $row) {
            $byMonth[(int) $row['m']] = (float) $row['total'];
        }

        $monthNames = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="chiffre-affaires-encaisse-' . $year . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Mois', 'Trimestre', 'Chiffre d\'affaires encaissé'], ';');
        foreach ($byMonth as $month => $total) {
            fputcsv($out, [$monthNames[$month] . ' ' . $year, 'T' . (int) ceil($month / 3), number_format($total, 2, ',', '')], ';');
        }
        fputcsv($out, ['Total ' . $year, '', number_format(array_sum($byMonth), 2, ',', '')], ';');
        fclose($out);
    }
}
