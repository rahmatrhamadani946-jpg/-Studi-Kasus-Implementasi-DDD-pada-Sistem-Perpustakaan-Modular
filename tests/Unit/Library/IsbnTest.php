<?php

use Modules\Shared\ValueObjects\Isbn;

it('menerima ISBN-13 yang valid', function () {
    expect((new Isbn('9780306406157'))->getValue())->toBe('9780306406157');
});

it('menerima ISBN-10 yang valid termasuk check digit X', function () {
    expect(Isbn::isValid('0306406152'))->toBeTrue()
        ->and(Isbn::isValid('080442957X'))->toBeTrue();
});

it('mengabaikan tanda hubung dan spasi', function () {
    expect((new Isbn('978-0-306-40615-7'))->getValue())->toBe('9780306406157')
        ->and((new Isbn('0 306 40615 2'))->getValue())->toBe('0306406152');
});

it('menolak ISBN dengan check digit salah', function () {
    expect(Isbn::isValid('9780306406158'))->toBeFalse()
        ->and(Isbn::isValid('0306406153'))->toBeFalse();
});

it('menolak panjang atau karakter yang salah', function () {
    expect(Isbn::isValid('12345'))->toBeFalse()
        ->and(Isbn::isValid('abcdefghijklm'))->toBeFalse()
        ->and(Isbn::isValid(''))->toBeFalse();
});

it('melempar exception pada ISBN tidak valid', function () {
    new Isbn('123');
})->throws(InvalidArgumentException::class, 'ISBN tidak valid');

it('mengonversi ISBN-10 ke ISBN-13', function () {
    expect((new Isbn('0306406152'))->toIsbn13())->toBe('9780306406157');
});

it('menganggap ISBN-10 dan ISBN-13 buku yang sama sebagai equal', function () {
    expect((new Isbn('0306406152'))->equals(new Isbn('978-0-306-40615-7')))->toBeTrue();
});
