<?php

namespace App\Core;

require_once __DIR__ . '/Vendor/fpdf.php';

use FPDF;

/**
 * Renders a POS sale as a downloadable A5-portrait receipt/ticket, with a QR
 * code linking back to the buyer's own self-service page (PosReceiptController)
 * so it stays useful even once printed or forwarded. Same dependency-free
 * FPDF + QrCode toolchain as TicketPdf.
 */
class PosReceiptPdf
{
    private const NAVY = [20, 33, 61];
    private const GRAY = [90, 96, 105];
    private const LIGHT = [246, 247, 249];

    /**
     * @param array $sale {sale_number, created_at, payment_method, buyer_name, total}
     * @param array $items list of {description, quantity, unit_price, total}
     * @param array $company {company_name}
     */
    public static function build(array $sale, array $items, array $company, string $receiptUrl): string
    {
        $pdf = new FPDF('P', 'mm', 'A5');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetTitle(self::latin1('Ticket ' . $sale['sale_number']));
        $pdf->SetCreator(self::latin1($company['company_name'] ?? 'EventPlanner'));
        $pdf->AddPage();

        $w = 148;

        $pdf->SetFillColor(...self::NAVY);
        $pdf->Rect(0, 0, $w, 24, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetXY(10, 6);
        $pdf->Cell($w - 20, 7, self::latin1($company['company_name'] ?? 'EventPlanner'));
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(10, 14);
        $pdf->Cell($w - 20, 5, self::latin1('TICKET DE CAISSE ' . $sale['sale_number']));

        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(10, 29);
        $pdf->Cell($w - 20, 5, self::latin1('Date : ' . self::formatDateTimeFr($sale['created_at'] ?? null)));
        $pdf->SetXY(10, 34);
        $pdf->Cell($w - 20, 5, self::latin1('Paiement : ' . self::paymentLabel($sale['payment_method'] ?? 'cash')));
        if (!empty($sale['buyer_name'])) {
            $pdf->SetXY(10, 39);
            $pdf->Cell($w - 20, 5, self::latin1('Client : ' . $sale['buyer_name']));
        }

        $y = 47;
        $pdf->SetDrawColor(210, 210, 214);
        $pdf->Line(10, $y, $w - 10, $y);
        $y += 4;

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetXY(10, $y);
        $pdf->Cell(70, 5, self::latin1('ARTICLE'));
        $pdf->SetXY(80, $y);
        $pdf->Cell(15, 5, self::latin1('QTE'), 0, 0, 'R');
        $pdf->SetXY(95, $y);
        $pdf->Cell(20, 5, self::latin1('P.U.'), 0, 0, 'R');
        $pdf->SetXY(115, $y);
        $pdf->Cell(23, 5, self::latin1('TOTAL'), 0, 0, 'R');
        $y += 6;

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(...self::NAVY);
        foreach ($items as $item) {
            $pdf->SetXY(10, $y);
            $pdf->Cell(70, 5, self::latin1((string) $item['description']));
            $pdf->SetXY(80, $y);
            $pdf->Cell(15, 5, self::latin1(rtrim(rtrim(number_format((float) $item['quantity'], 2, ',', ''), '0'), ',')), 0, 0, 'R');
            $pdf->SetXY(95, $y);
            $pdf->Cell(20, 5, self::latin1(number_format((float) $item['unit_price'], 2, ',', ' ')), 0, 0, 'R');
            $pdf->SetXY(115, $y);
            $pdf->Cell(23, 5, self::latin1(number_format((float) $item['total'], 2, ',', ' ') . ' EUR'), 0, 0, 'R');
            $y += 6;
        }

        $y += 2;
        $pdf->SetDrawColor(210, 210, 214);
        $pdf->Line(10, $y, $w - 10, $y);
        $y += 5;

        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->SetXY(10, $y);
        $pdf->Cell($w - 20, 7, self::latin1('TOTAL : ' . number_format((float) $sale['total'], 2, ',', ' ') . ' EUR'), 0, 0, 'R');
        $y += 14;

        $qrBoxSize = 34;
        $qrX = ($w - $qrBoxSize) / 2;
        $qr = QrCode::encodeText($receiptUrl, QrCode::ECC_QUARTILE);
        self::drawQr($pdf, $qr, $qrX, $y, $qrBoxSize);
        $y += $qrBoxSize + 4;

        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetXY(10, $y);
        $pdf->MultiCell($w - 20, 4, self::latin1('Scannez ce QR code pour retrouver ce ticket a tout moment depuis votre telephone.'), 0, 'C');

        return $pdf->Output('S');
    }

    private static function drawQr(FPDF $pdf, QrCode $qr, float $x, float $y, float $boxSize): void
    {
        $pdf->SetFillColor(...self::LIGHT);
        $pdf->Rect($x, $y, $boxSize, $boxSize, 'F');
        $pdf->SetDrawColor(210, 210, 214);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($x, $y, $boxSize, $boxSize);

        $quiet = 3;
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

    private static function paymentLabel(string $method): string
    {
        return ['cash' => 'Especes', 'card' => 'Carte bancaire', 'other' => 'Autre'][$method] ?? $method;
    }

    private static function formatDateTimeFr(?string $dateTime): string
    {
        if (empty($dateTime)) {
            return '';
        }
        $ts = strtotime($dateTime);
        return $ts ? date('d/m/Y à H:i', $ts) : '';
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
