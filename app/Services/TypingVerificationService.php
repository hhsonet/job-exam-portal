<?php

namespace App\Services;

class TypingVerificationService
{
    public static function normalise(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", trim($value));
        if (class_exists('Normalizer')) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_C) ?: $value;
        }

        return $value;
    }

    /**
     * Compare text by Unicode code point so Bangla and other UTF-8 text are
     * scored by characters rather than raw bytes.
     *
     * @return array{similarity: float, status: string, is_correct: bool}
     */
    public static function evaluate(string $expected, string $actual): array
    {
        $expected = self::normalise($expected);
        $actual = self::normalise($actual);
        if ($expected === '') {
            return ['similarity' => 0.0, 'status' => 'Invalid reference', 'is_correct' => false];
        }
        if ($expected === $actual) {
            return ['similarity' => 100.0, 'status' => 'Exact', 'is_correct' => true];
        }

        $expectedChars = preg_split('//u', $expected, -1, PREG_SPLIT_NO_EMPTY) ?: str_split($expected);
        $actualChars = preg_split('//u', $actual, -1, PREG_SPLIT_NO_EMPTY) ?: str_split($actual);
        $expectedLength = count($expectedChars);
        $actualLength = count($actualChars);
        if ($actualLength === 0) {
            return ['similarity' => 0.0, 'status' => 'Incorrect', 'is_correct' => false];
        }

        $previous = range(0, $actualLength);
        foreach ($expectedChars as $expectedIndex => $expectedChar) {
            $current = [$expectedIndex + 1];
            foreach ($actualChars as $actualIndex => $actualChar) {
                $substitutionCost = $expectedChar === $actualChar ? 0 : 1;
                $current[] = min(
                    $current[$actualIndex] + 1,
                    $previous[$actualIndex + 1] + 1,
                    $previous[$actualIndex] + $substitutionCost
                );
            }
            $previous = $current;
        }

        $distance = $previous[$actualLength];
        $longestLength = max($expectedLength, $actualLength);
        $similarity = round(max(0, 1 - ($distance / $longestLength)) * 100, 2);

        return [
            'similarity' => $similarity,
            'status' => $similarity <= 0 ? 'Incorrect' : 'Partial',
            'is_correct' => false,
        ];
    }
}
