<?php

namespace App\Libraries;

class TypingAnswerPdf
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;

    public static function make(array $data): string
    {
        $answer = trim((string) ($data['answer'] ?? ''));
        $lines = $answer === '' ? ['Not answered'] : self::wrap($answer, 78);
        $pages = array_chunk($lines, 48);
        $streams = [];

        foreach ($pages as $pageLines) {
            $stream = '';
            self::text($stream, 48, 790, 'UIU RECRUITMENT PORTAL', 10, 'F2', [0.10, 0.34, 0.86]);
            self::text($stream, 48, 752, 'Typing test answer', 22, 'F2', [0.04, 0.12, 0.23]);
            $stream .= "q 0.88 0.90 0.94 RG 0.8 w 48 735 m 547 735 l S Q\n";
            self::text($stream, 48, 708, 'Submission', 8, 'F2', [0.35, 0.42, 0.52]);
            self::text($stream, 125, 708, (string) ($data['submissionId'] ?? ''), 10, 'F1', [0.04, 0.12, 0.23]);
            self::text($stream, 48, 687, 'Question', 8, 'F2', [0.35, 0.42, 0.52]);
            self::text($stream, 125, 687, (string) ($data['questionNumber'] ?? ''), 10, 'F1', [0.04, 0.12, 0.23]);
            self::text($stream, 48, 666, 'Similarity', 8, 'F2', [0.35, 0.42, 0.52]);
            self::text($stream, 125, 666, (string) ($data['similarity'] ?? '0') . '% - ' . (string) ($data['status'] ?? 'Not evaluated'), 10, 'F2', [0.04, 0.12, 0.23]);
            self::text($stream, 48, 620, 'Applicant answer', 11, 'F2', [0.04, 0.12, 0.23]);
            $y = 590;
            foreach ($pageLines as $line) {
                self::text($stream, 48, $y, $line, 10, 'F1', [0.10, 0.12, 0.17]);
                $y -= 14;
            }
            self::text($stream, 48, 42, 'Generated from the secured submission record.', 8, 'F1', [0.42, 0.47, 0.55]);
            $streams[] = $stream;
        }

        return self::buildPdf($streams);
    }

    private static function buildPdf(array $streams): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];
        $pageObjects = [];
        $nextObject = 5;
        foreach ($streams as $stream) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $pageObjects[] = $pageObject;
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
            $objects[$contentObject] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        }

        $kids = implode(' ', array_map(static fn (int $number): string => $number . ' 0 R', $pageObjects));
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageObjects) . ' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($number = 1; $number <= count($objects); $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

        return $pdf;
    }

    private static function text(string &$stream, int $x, int $y, string $value, int $size, string $font, array $color): void
    {
        $stream .= sprintf("q %.3f %.3f %.3f rg BT /%s %d Tf %d %d Td (%s) Tj ET Q\n", $color[0], $color[1], $color[2], $font, $size, $x, $y, self::escape($value));
    }

    private static function wrap(string $value, int $width): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', $value) ?: [$value] as $line) {
            $wrapped = wordwrap(self::ascii($line), $width, "\n", true);
            $lines = array_merge($lines, explode("\n", $wrapped));
        }

        return $lines ?: [''];
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
