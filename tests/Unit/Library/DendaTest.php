<?php

use Modules\Shared\ValueObjects\Denda;

it('tidak ada denda jika dikembalikan tepat waktu', function () {
    $due = new DateTimeImmutable('2026-11-10 10:00:00');

    $denda = Denda::calculate($due, new DateTimeImmutable('2026-11-10 09:00:00'));

    expect($denda->hasFine())->toBeFalse()
        ->and($denda->getDaysLate())->toBe(0)
        ->and($denda->getAmount()->getAmount())->toBe(0.0);
});

it('menghitung denda per hari keterlambatan', function () {
    $due = new DateTimeImmutable('2026-11-10 10:00:00');

    $denda = Denda::calculate($due, new DateTimeImmutable('2026-11-13 10:00:00'));

    expect($denda->getDaysLate())->toBe(3)
        ->and($denda->getAmount()->getAmount())->toBe(3000.0)
        ->and((string) $denda)->toBe('3 hari x 1,000.00 IDR = 3,000.00 IDR');
});

it('membulatkan sebagian hari ke atas', function () {
    $due = new DateTimeImmutable('2026-11-10 10:00:00');

    expect(Denda::calculate($due, new DateTimeImmutable('2026-11-10 10:00:01'))->getDaysLate())->toBe(1)
        ->and(Denda::calculate($due, new DateTimeImmutable('2026-11-11 10:00:01'))->getDaysLate())->toBe(2);
});

it('mendukung tarif kustom', function () {
    $due = new DateTimeImmutable('2026-11-10 10:00:00');

    $denda = Denda::calculate($due, new DateTimeImmutable('2026-11-12 10:00:00'), 2500);

    expect($denda->getAmount()->getAmount())->toBe(5000.0);
});

it('menolak tarif negatif', function () {
    Denda::calculate(new DateTimeImmutable(), new DateTimeImmutable(), -1);
})->throws(InvalidArgumentException::class);
