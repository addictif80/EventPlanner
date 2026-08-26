<?php

namespace App\Core;

/**
 * Wraps the inner HTML of any outgoing email (quotes, invoices, reminders,
 * guest invites, contracts, surveys, tickets...) in one consistent, sober
 * shell, so every email the app sends shares the same elegant design instead
 * of each controller hand-rolling its own inline styles. Table-based layout
 * and inline styles throughout, since email clients don't reliably support
 * external/embedded stylesheets.
 */
class EmailDesign
{
    public static function wrap(string $innerHtml, string $senderName): string
    {
        $year = date('Y');
        $sender = View::e($senderName);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$sender}</title>
</head>
<body style="margin:0; padding:0; background-color:#eef0f3; font-family:Georgia,'Times New Roman',serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border:1px solid #e2e4e9;">
<tr>
<td style="background-color:#14213d; padding:22px 32px; border-top:3px solid #b48c1e;">
<span style="font-family:Georgia,'Times New Roman',serif; color:#ffffff; font-size:18px; letter-spacing:0.5px;">{$sender}</span>
</td>
</tr>
<tr>
<td style="padding:36px 32px; font-family:Helvetica,Arial,sans-serif; color:#2b2f38; font-size:14px; line-height:1.65;">
{$innerHtml}
</td>
</tr>
<tr>
<td style="padding:18px 32px; background-color:#f7f8fa; border-top:1px solid #e2e4e9; font-family:Helvetica,Arial,sans-serif; color:#8a909c; font-size:11px; line-height:1.5;">
Cet email vous a été envoyé par {$sender}. &copy; {$year}
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}
