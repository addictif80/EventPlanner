Third-party code vendored directly (no Composer, per this project's zero-dependency
deployment policy — see the root README) rather than pulled in as a package.

## FPDF (`fpdf.php`, `font/*.json`)

FPDF by Olivier Plathey — https://www.fpdf.org

Released under the FPDF License (a permissive, zlib/libpng-style license):
free to use, modify, and redistribute, including in commercial and closed-source
applications, without royalty. See https://www.fpdf.org/en/script/license.txt for
the exact terms.

Used here to render event ticket PDFs (`src/Core/TicketPdf.php`).

## QR code encoder

`src/Core/QrCode.php` is a PHP port of the "QR Code generator library" by
Project Nayuki — https://www.nayuki.io/page/qr-code-generator-library —
released under the MIT License. The port keeps the same encoding algorithm
(segment building, Reed-Solomon error correction, masking) trimmed to the
numeric/alphanumeric/byte modes this app needs; Kanji mode is not implemented,
matching the reference library's own scope.
