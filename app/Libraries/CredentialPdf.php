<?php

namespace App\Libraries;

class CredentialPdf
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;

    public static function make(array $data): string
    {
        $stream = '';
        $cardWidth = 262;
        $cardHeight = 384;
        $positions = [
            [28, 430],
            [305, 430],
            [28, 28],
            [305, 28],
        ];

        foreach ($positions as [$x, $y]) {
            self::drawCard($stream, $x, $y, $cardWidth, $cardHeight, $data);
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
            6 => '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream",
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($number = 1; $number <= count($objects); $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF\n";

        return $pdf;
    }

    private static function drawCard(string &$stream, int $x, int $y, int $width, int $height, array $data): void
    {
        $stream .= "q 0.82 0.86 0.91 RG 1 w {$x} {$y} {$width} {$height} re S Q\n";
        $left = $x + 22;
        $right = $x + $width - 22;
        $top = $y + $height - 28;

        self::text($stream, $left, $top, 'UIU RECRUITMENT PORTAL', 9, 'F2', [0.10, 0.34, 0.86]);
        self::text($stream, $left, $top - 27, 'Applicant Credentials', 17, 'F2', [0.04, 0.12, 0.23]);
        $stream .= "q 0.88 0.90 0.94 RG 0.8 w {$left} " . ($top - 42) . " m {$right} " . ($top - 42) . " l S Q\n";

        $cursor = $top - 67;
        self::field($stream, $left, $cursor, 'APPLICANT NAME', (string) ($data['name'] ?? ''));
        $cursor -= 45;
        self::field($stream, $left, $cursor, 'APPLICANT ID', (string) ($data['applicantId'] ?? ''));
        $cursor -= 45;
        self::field($stream, $left, $cursor, 'POSITION', (string) ($data['position'] ?? ''));

        $boxY = $cursor - 133;
        $stream .= "q 0.95 0.97 1 rg 0.78 0.86 0.96 RG 0.8 w {$left} {$boxY} " . ($right - $left) . " 91 re B Q\n";
        self::text($stream, $left + 12, $boxY + 73, 'SIGN IN DETAILS', 9, 'F2', [0.10, 0.34, 0.86]);
        self::text($stream, $left + 12, $boxY + 50, 'Username', 8, 'F1', [0.35, 0.42, 0.52]);
        self::text($stream, $left + 76, $boxY + 50, (string) ($data['username'] ?? ''), 11, 'F2', [0.04, 0.12, 0.23]);
        self::text($stream, $left + 12, $boxY + 25, 'Password', 8, 'F1', [0.35, 0.42, 0.52]);
        self::text($stream, $left + 76, $boxY + 25, (string) ($data['password'] ?? ''), 11, 'F2', [0.04, 0.12, 0.23]);

        $siteY = $boxY - 15;
        self::text($stream, $left, $siteY, 'SITE URL', 8, 'F2', [0.35, 0.42, 0.52]);
        $siteLines = self::wrap((string) ($data['siteUrl'] ?? ''), 39);
        foreach ($siteLines as $index => $line) {
            self::text($stream, $left, $siteY - 16 - ($index * 13), $line, 9, 'F1', [0.04, 0.12, 0.23]);
        }

        self::text($stream, $left, $y + 27, 'Keep this page confidential.', 8, 'F1', [0.42, 0.47, 0.55]);
    }

    private static function field(string &$stream, int $x, int $y, string $label, string $value): void
    {
        self::text($stream, $x, $y, $label, 8, 'F2', [0.35, 0.42, 0.52]);
        self::text($stream, $x, $y - 16, self::truncate($value, 39), 11, 'F1', [0.04, 0.12, 0.23]);
    }

    private static function text(string &$stream, int $x, int $y, string $value, int $size, string $font, array $color): void
    {
        $stream .= sprintf("q %.3f %.3f %.3f rg BT /%s %d Tf %d %d Td (%s) Tj ET Q\n", $color[0], $color[1], $color[2], $font, $size, $x, $y, self::escape($value));
    }

    private static function wrap(string $value, int $width): array
    {
        $value = self::ascii($value);
        return explode("\n", wordwrap($value, $width, "\n", true));
    }

    private static function truncate(string $value, int $length): string
    {
        $value = self::ascii($value);
        return strlen($value) > $length ? substr($value, 0, $length - 3) . '...' : $value;
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], self::ascii($value));
    }

    private static function ascii(string $value): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '?', $value) : $converted;
    }
}
