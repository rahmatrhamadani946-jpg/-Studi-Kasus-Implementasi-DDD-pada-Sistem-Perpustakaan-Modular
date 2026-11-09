# Tugas Pertemuan 6 — Sistem Perpustakaan Modular (DDD)

Tiga bounded context di `app/Modules/`:

| Context | Isi |
|---|---|
| **Catalog** | Book (aggregate root), Author, Publisher, BookCategory (self-referencing) |
| **Membership** | Member (aggregate root), MemberRole (batas pinjam & lama pinjam), masa keanggotaan |
| **Circulation** | Loan (aggregate root), LoanDetail (snapshot judul & ISBN), Fine (denda) |
| **Shared Kernel** | `Isbn`, `Denda`, `Money` (value object); `BookServiceInterface`, `MemberServiceInterface` (contract) |

Circulation **tidak** mengakses model Catalog/Membership: semuanya lewat contract.

## Menjalankan
```bash
composer install            # bila vendor bermasalah
npm install                 # opsional (hanya untuk composer run dev / Vite)
php artisan migrate:fresh --seed
php artisan library:query-benchmark     # perbandingan jumlah query (N+1 vs eager loading)
php artisan test --filter=Library       # semua pengujian tugas
php artisan test tests/Unit/Library     # pengujian value object
php artisan serve
```
Login contoh: `admin@example.com` / `password123` (admin), `user@example.com` / `password123`.

## Endpoint (JSON, perlu login; ✱ = admin)
```
GET   /library/books?q=&category_id=&available=1     GET /library/books/{id}
POST  ✱ /library/books         DELETE ✱ /library/books/{id}
GET   ✱ /library/books-trashed PATCH  ✱ /library/books/{id}/restore
GET   /library/categories      POST ✱ /library/categories
GET   /library/members  /library/members/{id}        POST/DELETE/restore ✱ (sama seperti books)
GET   /library/loans?status=borrowed|returned|overdue
POST  /library/loans           {"member_id":1,"items":[{"book_id":1,"quantity":1}]}
POST  /library/loans/{id}/return
DELETE ✱ /library/loans/{id}   PATCH ✱ /library/loans/{id}/restore
```

## Pemetaan ke kriteria tugas
- **Relasi lanjutan**: `Book::categories()` (pivot `added_at`), `BookCategory::parent()/children()`, `Loan::details()`
- **Query optimization**: eager loading di semua controller, scope `available/search/byCategory`, `php artisan library:query-benchmark`
- **Transaction**: `Circulation/Services/LoanService.php` (`createLoan`, `returnLoan`)
- **Soft deletes + restore admin**: Book, Member, Loan
- **Domain event**: `BookBorrowed` -> listener `LogBookBorrowed` (log di `storage/logs/laravel.log`)
- **Value object**: `Isbn` (validasi ISBN-10/13 + check digit), `Denda` (hitung hari & nominal otomatis)
