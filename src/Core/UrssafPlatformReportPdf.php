<?php

namespace App\Core;

require_once __DIR__ . '/Vendor/fpdf.php';

use FPDF;

/**
 * Monthly URSSAF declaration helper PDF for the platform operator itself
 * (auto-entrepreneur) — not tenant-facing. Lists every real Stripe payment
 * received that month (App\Models\PlatformRevenueTransaction, journaled from
 * the invoice.paid webhook) and the total to declare. Deliberately states no
 * cotisation rate or amount due (rates change yearly and would go stale) —
 * same restraint as ReportController::urssaf() for tenants.
 */
class UrssafPlatformReportPdf
{
    private const NAVY = [20, 33, 61];
    private const GRAY = [90, 96, 105];
    private const LIGHT = [246, 247, 249];

    /** @param array $transactions list of {paid_at, organization_name, description, amount} */
    public static function build(string $month, array $transactions, float $total, string $platformName): string
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetTitle(self::latin1('Rapport URSSAF ' . $month));
        $pdf->SetCreator(self::latin1($platformName));
        $pdf->AddPage();

        $w = 210;

        $pdf->SetFillColor(...self::NAVY);
        $pdf->Rect(0, 0, $w, 26, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->SetXY(15, 7);
        $pdf->Cell($w - 30, 8, self::latin1($platformName));
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(15, 16);
        $pdf->Cell($w - 30, 6, self::latin1('Rapport URSSAF - ' . self::monthLabel($month)));

        $y = 36;
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetXY(15, $y);
        $pdf->Cell($w - 30, 7, self::latin1("Chiffre d'affaires encaisse a declarer : " . self::formatMoney($total)));
        $y += 10;

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetXY(15, $y);
        $pdf->MultiCell($w - 30, 4.5, self::latin1(
            "Ce montant correspond aux encaissements Stripe reels recus sur la periode (abonnements et modules payes par les organisations). "
            . "Cette page ne calcule aucune cotisation : les taux evoluent chaque annee - verifiez le taux en vigueur sur autoentrepreneur.urssaf.fr avant de declarer."
        ));
        $y = $pdf->GetY() + 6;

        // Table header
        $pdf->SetFillColor(...self::LIGHT);
        $pdf->Rect(15, $y, $w - 30, 8, 'F');
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetXY(17, $y + 2);
        $pdf->Cell(30, 5, self::latin1('DATE'));
        $pdf->SetXY(50, $y + 2);
        $pdf->Cell(80, 5, self::latin1('ORGANISATION'));
        $pdf->SetXY(133, $y + 2);
        $pdf->Cell(45, 5, self::latin1('DESCRIPTION'));
        $pdf->SetXY(178, $y + 2);
        $pdf->Cell(15, 5, self::latin1('MONTANT'), 0, 0, 'R');
        $y += 10;

        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(...self::NAVY);
        foreach ($transactions as $t) {
            if ($y > 270) {
                $pdf->AddPage();
                $y = 20;
            }
            $pdf->SetXY(17, $y);
            $pdf->Cell(30, 5, self::latin1(date('d/m/Y', strtotime($t['paid_at']))));
            $pdf->SetXY(50, $y);
            $pdf->Cell(80, 5, self::latin1($t['organization_name'] ?: 'Organisation supprimee'));
            $pdf->SetXY(133, $y);
            $pdf->Cell(45, 5, self::latin1((string) $t['description']));
            $pdf->SetXY(163, $y);
            $pdf->Cell(30, 5, self::latin1(self::formatMoney((float) $t['amount'])), 0, 0, 'R');
            $y += 6;
        }

        if (empty($transactions)) {
            $pdf->SetXY(15, $y);
            $pdf->SetTextColor(...self::GRAY);
            $pdf->Cell($w - 30, 6, self::latin1('Aucun encaissement enregistre sur cette periode.'));
        }

        return $pdf->Output('S');
    }

    private static function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' EUR';
    }

    private static function monthLabel(string $month): string
    {
        $months = ['', 'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
        [$year, $m] = explode('-', $month);
        return $months[(int) $m] . ' ' . $year;
    }

    /** See TicketPdf::latin1() — FPDF's core fonts only support Latin-1/CP1252. */
    private static function latin1(string $text): string
    {
        $text = strtr($text, [
            "\xE2\x80\x94" => '-', "\xE2\x80\x93" => '-',
            "\xE2\x80\x98" => "'", "\xE2\x80\x99" => "'",
            "\xE2\x80\x9C" => '"', "\xE2\x80\x9D" => '"',
            "\xE2\x80\xA6" => '...',
        ]);
        $converted = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        return $converted !== false ? $converted : $text;
    }
}
