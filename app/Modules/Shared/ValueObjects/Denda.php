<?php

namespace Modules\Shared\ValueObjects;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Value Object Denda (keterlambatan pengembalian).
 * Jumlah hari terlambat dan nominal denda dihitung otomatis dari
 * tanggal jatuh tempo, tanggal pengembalian, dan tarif per hari.
 */
class Denda
{
    public const TARIF_PER_HARI = 1000.0; // IDR

    private function __construct(
        private int $daysLate,
        private Money $ratePerDay
    ) {
    }

    public static function calculate(
        DateTimeInterface $dueAt,
        DateTimeInterface $returnedAt,
        float $ratePerDay = self::TARIF_PER_HARI
    ): self {
        if ($ratePerDay < 0) {
            throw new InvalidArgumentException('Tarif denda tidak boleh negatif');
        }

        $seconds = $returnedAt->getTimestamp() - $dueAt->getTimestamp();

        // Keterlambatan dihitung per hari berjalan (sebagian hari dibulatkan ke atas)
        $daysLate = $seconds <= 0 ? 0 : (int) ceil($seconds / 86400);

        return new self($daysLate, new Money($ratePerDay));
    }

    public function getDaysLate(): int
    {
        return $this->daysLate;
    }

    public function getRatePerDay(): Money
    {
        return $this->ratePerDay;
    }

    public function getAmount(): Money
    {
        return $this->ratePerDay->multiply($this->daysLate);
    }

    public function hasFine(): bool
    {
        return $this->daysLate > 0;
    }

    public function __toString(): string
    {
        return $this->daysLate . ' hari x ' . $this->ratePerDay . ' = ' . $this->getAmount();
    }
}
