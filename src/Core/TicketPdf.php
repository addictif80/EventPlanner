<?php

namespace App\Core;

require_once __DIR__ . '/Vendor/fpdf.php';

use FPDF;

/**
 * Renders event tickets as a PDF (one A5-landscape "boarding pass" style page
 * per ticket), each carrying a scannable QR code of the ticket's check-in
 * code. Built on the vendored FPDF library (see Vendor/fpdf.php) and the
 * dependency-free QrCode encoder — no Composer, no GD, no external service.
 */
class TicketPdf
{
    private const NAVY = [20, 33, 61];
    private const GOLD = [180, 140, 30];
    private const GRAY = [90, 96, 105];
    private const LIGHT = [246, 247, 249];

    /**
     * @param array $event {title, event_date, location, venue_name}
     * @param array $company {company_name}
     * @param array $tickets list of {code, holder_name, category_name}
     */
    public static function build(array $event, array $company, array $tickets): string
    {
        $pdf = new FPDF('L', 'mm', 'A5');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetTitle(self::latin1('Billet - ' . $event['title']));
        $pdf->SetCreator(self::latin1($company['company_name'] ?? 'EventPlanner'));

        foreach ($tickets as $ticket) {
            self::renderTicketPage($pdf, $event, $company, $ticket);
        }

        return $pdf->Output('S');
    }

    private static function renderTicketPage(FPDF $pdf, array $event, array $company, array $ticket): void
    {
        $pdf->AddPage();
        $w = 210;
        $h = 148;

        // Outer frame
        $pdf->SetDrawColor(...self::NAVY);
        $pdf->SetLineWidth(0.6);
        $pdf->Rect(4, 4, $w - 8, $h - 8);

        // Header band
        $pdf->SetFillColor(...self::NAVY);
        $pdf->Rect(4, 4, $w - 8, 20, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->SetXY(10, 9);
        $pdf->Cell(140, 8, self::latin1(mb_strtoupper($company['company_name'] ?? 'EventPlanner', 'UTF-8')));
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(10, 16);
        $pdf->Cell(140, 5, self::latin1('BILLET D\'ENTREE OFFICIEL'));

        // Thin gold accent under header
        $pdf->SetDrawColor(...self::GOLD);
        $pdf->SetLineWidth(0.8);
        $pdf->Line(4, 24, $w - 4, 24);

        // Vertical divider before the QR panel
        $pdf->SetDrawColor(210, 210, 214);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(138, 28, 138, $h - 10);

        // --- Left column: event & holder details ---
        $left = 12;
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->SetXY($left, 32);
        $pdf->MultiCell(122, 7, self::latin1($event['title'] ?? ''));

        $y = $pdf->GetY() + 2;
        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetFont('Helvetica', '', 10);

        $venue = trim(($event['venue_name'] ?? '') ?: ($event['location'] ?? ''));
        $lines = array_filter([
            self::formatDateFr($event['event_date'] ?? null),
            $venue !== '' ? $venue : null,
        ]);
        foreach ($lines as $line) {
            $pdf->SetXY($left, $y);
            $pdf->Cell(122, 5, self::latin1($line));
            $y += 5.5;
        }

        $y += 4;
        self::labelValue($pdf, $left, $y, 'PORTEUR', $ticket['holder_name'] ?: 'A renseigner sur place');
        $y += 14;
        self::labelValue($pdf, $left, $y, 'CATEGORIE', $ticket['category_name'] ?? 'Standard');

        // --- Right column: QR code panel ---
        $qrBoxX = 145;
        $qrBoxSize = 58;
        $qr = QrCode::encodeText($ticket['code'], QrCode::ECC_QUARTILE);
        self::drawQr($pdf, $qr, $qrBoxX, 30, $qrBoxSize);

        $pdf->SetFont('Courier', 'B', 11);
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetXY($qrBoxX, 30 + $qrBoxSize + 3);
        $pdf->Cell($qrBoxSize, 6, self::latin1($ticket['code']), 0, 0, 'C');

        // Footer disclaimer + dashed tear line
        $tearY = $h - 14;
        self::dashedLine($pdf, 6, $tearY, $w - 6, $tearY);
        $pdf->SetFont('Helvetica', 'I', 7.5);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetXY(10, $tearY + 2.5);
        $pdf->MultiCell($w - 20, 3.6, self::latin1(
            "Presentez ce billet (imprime ou sur smartphone) a l'entree pour le scan du QR code. "
            . 'Billet strictement personnel et non cessible, valable une seule fois.'
        ));
    }

    private static function labelValue(FPDF $pdf, float $x, float $y, string $label, string $value): void
    {
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(...self::GOLD);
        $pdf->SetXY($x, $y);
        $pdf->Cell(122, 4, self::latin1($label));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetXY($x, $y + 4.5);
        $pdf->Cell(122, 6, self::latin1($value));
    }

    private static function drawQr(FPDF $pdf, QrCode $qr, float $x, float $y, float $boxSize): void
    {
        $pdf->SetFillColor(...self::LIGHT);
        $pdf->Rect($x, $y, $boxSize, $boxSize, 'F');
        $pdf->SetDrawColor(210, 210, 214);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($x, $y, $boxSize, $boxSize);

        $quiet = 6; // mm of breathing room inside the box
        $available = $boxSize - $quiet * 2;
        $moduleSize = $available / $qr->size;
        $offsetX = $x + ($boxSize - $qr->size * $moduleSize) / 2;
        $offsetY = $y + ($boxSize - $qr->size * $moduleSize) / 2;

        $pdf->SetFillColor(0, 0, 0);
        for ($row = 0; $row < $qr->size; $row++) {
            $runStart = null;
            for ($col = 0; $col <= $qr->size; $col++) {
                $dark = $col < $qr->size && $qr->getModule($col, $row);
                if ($dark && $runStart === null) {
                    $runStart = $col;
                } elseif (!$dark && $runStart !== null) {
                    $pdf->Rect($offsetX + $runStart * $moduleSize, $offsetY + $row * $moduleSize, ($col - $runStart) * $moduleSize, $moduleSize, 'F');
                    $runStart = null;
                }
            }
        }
    }

    private static function dashedLine(FPDF $pdf, float $x1, float $y, float $x2, float $y2, float $dash = 2.2, float $gap = 1.6): void
    {
        $pdf->SetDrawColor(190, 190, 196);
        $pdf->SetLineWidth(0.25);
        $total = $x2 - $x1;
        $x = $x1;
        while ($x < $x2) {
            $end = min($x + $dash, $x2);
            $pdf->Line($x, $y, $end, $y2);
            $x = $end + $gap;
        }
    }

    private static function formatDateFr(?string $date): ?string
    {
        if (empty($date) || $date === '0000-00-00') {
            return null;
        }
        $ts = strtotime($date);
        if (!$ts) {
            return null;
        }
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = ['', 'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
        return $days[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    /**
     * FPDF's core fonts only support the Latin-1/CP1252 range, not raw UTF-8.
     * Typographic punctuation outside Latin-1 (em/en dash, curly quotes) is
     * normalized to its plain ASCII equivalent first so it renders as a
     * dash/quote instead of the "?" mb_convert_encoding would otherwise emit.
     */
    private static function latin1(string $text): string
    {
        $text = strtr($text, [
            "\xE2\x80\x94" => '-', // em dash —
            "\xE2\x80\x93" => '-', // en dash –
            "\xE2\x80\x98" => "'", "\xE2\x80\x99" => "'", // ‘ ’
            "\xE2\x80\x9C" => '"', "\xE2\x80\x9D" => '"', // “ ”
            "\xE2\x80\xA6" => '...', // …
        ]);
        $converted = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        return $converted !== false ? $converted : $text;
    }
}
