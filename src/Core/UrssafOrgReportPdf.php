<?php

namespace App\Core;

require_once __DIR__ . '/Vendor/fpdf.php';

use FPDF;

/**
 * Monthly URSSAF declaration helper PDF for one organization (auto-entrepreneur
 * running their activity through EventPlanner) — lists every payment they
 * received from their own clients that month (App\Models\Payment) and the
 * total cash-basis chiffre d'affaires to declare. Same restraint as
 * ReportController::urssaf() / UrssafPlatformReportPdf: no cotisation rate
 * stated, since rates change yearly.
 */
class UrssafOrgReportPdf
{
    private const NAVY = [20, 33, 61];
    private const GRAY = [90, 96, 105];
    private const LIGHT = [246, 247, 249];

    /** @param array $payments list of {payment_date, first_name, last_name, company_name, invoice_number, amount} */
    public static function build(string $month, array $payments, float $total, string $companyName): string
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetTitle(self::latin1('Rapport URSSAF ' . $month));
        $pdf->SetCreator(self::latin1($companyName ?: 'EventPlanner'));
        $pdf->AddPage();

        $w = 210;

        $pdf->SetFillColor(...self::NAVY);
        $pdf->Rect(0, 0, $w, 26, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->SetXY(15, 7);
        $pdf->Cell($w - 30, 8, self::latin1($companyName ?: 'Mon activité'));
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
            "En micro-entreprise, le chiffre d'affaires a declarer est celui reellement encaisse sur la periode (paiements clients "
            . "enregistres ci-dessous), et non celui facture. Cette page ne calcule aucune cotisation : les taux evoluent chaque annee - "
            . "verifiez le taux en vigueur sur autoentrepreneur.urssaf.fr avant de declarer."
        ));
        $y = $pdf->GetY() + 6;

        // Table header
        $pdf->SetFillColor(...self::LIGHT);
        $pdf->Rect(15, $y, $w - 30, 8, 'F');
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetXY(17, $y + 2);
        $pdf->Cell(28, 5, self::latin1('DATE'));
        $pdf->SetXY(45, $y + 2);
        $pdf->Cell(75, 5, self::latin1('CLIENT'));
        $pdf->SetXY(120, $y + 2);
        $pdf->Cell(43, 5, self::latin1('FACTURE'));
        $pdf->SetXY(178, $y + 2);
        $pdf->Cell(15, 5, self::latin1('MONTANT'), 0, 0, 'R');
        $y += 10;

        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(...self::NAVY);
        foreach ($payments as $p) {
            if ($y > 270) {
                $pdf->AddPage();
                $y = 20;
            }
            $clientName = trim($p['company_name'] ?: trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')));
            $pdf->SetXY(17, $y);
            $pdf->Cell(28, 5, self::latin1(date('d/m/Y', strtotime($p['payment_date']))));
            $pdf->SetXY(45, $y);
            $pdf->Cell(75, 5, self::latin1($clientName ?: 'Client'));
            $pdf->SetXY(120, $y);
            $pdf->Cell(43, 5, self::latin1((string) $p['invoice_number']));
            $pdf->SetXY(163, $y);
            $pdf->Cell(30, 5, self::latin1(self::formatMoney((float) $p['amount'])), 0, 0, 'R');
            $y += 6;
        }

        if (empty($payments)) {
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
