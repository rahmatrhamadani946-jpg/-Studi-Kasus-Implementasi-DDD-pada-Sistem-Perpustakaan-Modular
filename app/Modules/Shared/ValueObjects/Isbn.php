<?php

namespace Modules\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object ISBN dengan validasi format dan check digit.
 * Menerima ISBN-10 maupun ISBN-13 (boleh memakai tanda hubung/spasi).
 */
class Isbn
{
    private string $value; // tanpa tanda hubung, huruf besar

    public function __construct(string $value)
    {
        $normalized = self::normalize($value);

        if (!self::isValid($normalized)) {
            throw new InvalidArgumentException("ISBN tidak valid: {$value}");
        }

        $this->value = $normalized;
    }

    public static function isValid(string $value): bool
    {
        $isbn = self::normalize($value);

        return match (strlen($isbn)) {
            10 => self::isValidIsbn10($isbn),
            13 => self::isValidIsbn13($isbn),
            default => false,
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isIsbn13(): bool
    {
        return strlen($this->value) === 13;
    }

    /**
     * Bentuk kanonik ISBN-13 (ISBN-10 dikonversi dengan prefix 978).
     */
    public function toIsbn13(): string
    {
        if ($this->isIsbn13()) {
            return $this->value;
        }

        $base = '978' . substr($this->value, 0, 9);

        return $base . self::isbn13CheckDigit($base);
    }

    public function equals(Isbn $other): bool
    {
        return $this->toIsbn13() === $other->toIsbn13();
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function normalize(string $value): string
    {
        return strtoupper(str_replace(['-', ' '], '', trim($value)));
    }

    private static function isValidIsbn10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dX]$/', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = $isbn[$i] === 'X' ? 10 : (int) $isbn[$i];
            $sum += $digit * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    private static function isValidIsbn13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }

        return self::isbn13CheckDigit(substr($isbn, 0, 12)) === (int) $isbn[12];
    }

    private static function isbn13CheckDigit(string $first12): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $first12[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }
}
