<?php

namespace App\Core;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $viewsPath = dirname(__DIR__, 2) . '/views';
        $file = $viewsPath . '/' . $template . '.php';

        if (!is_file($file)) {
            http_response_code(500);
            echo "Vue introuvable : {$template}";
            return;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = $viewsPath . '/' . $layout . '.php';
        extract($data, EXTR_SKIP);
        require $layoutFile;
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function money(float $amount, string $currency = 'EUR'): string
    {
        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
        $symbol = $symbols[$currency] ?? $currency;
        return number_format($amount, 2, ',', ' ') . ' ' . $symbol;
    }

    public static function date(?string $date, string $format = 'd/m/Y'): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '—';
        }
        $ts = strtotime($date);
        return $ts ? date($format, $ts) : '—';
    }
}
