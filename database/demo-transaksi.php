<?php
use Modules\Catalog\Models\Book;
use Modules\Circulation\Models\{Loan,LoanDetail};
use Modules\Membership\Models\Member;
use Modules\Circulation\Services\LoanService;
use Modules\Shared\Exceptions\InsufficientStockException;

$svc = app(LoanService::class);
$m = Member::where('active_loans_count',0)->whereHas('role', fn($q)=>$q->where('slug','lecturer'))->first();
$a = Book::where('stock','>=',4)->first();
$b = Book::where('stock','>=',1)->where('id','!=',$a->id)->orderBy('stock')->first();
$b->update(['stock'=>1]);

$snap = fn() => sprintf("loans=%d detail=%d | stok A=%d stok B=%d | anggota: status=%s pinjaman_aktif=%d",
    Loan::count(), LoanDetail::count(), $a->fresh()->stock, $b->fresh()->stock,
    $m->fresh()->status, $m->fresh()->active_loans_count);

echo "ANGGOTA: {$m->name} ({$m->member_number})\nBUKU A : {$a->title}\nBUKU B : {$b->title}\n\n";
echo "[1] KEADAAN AWAL\n    ".$snap()."\n\n";

echo "[2] PINJAM BERHASIL: A x2\n";
$loan = $svc->createLoan($m->id, [['book_id'=>$a->id,'quantity'=>2]], 'Demo');
echo "    nomor: {$loan->loan_number}\n    ".$snap()."\n\n";

echo "[3] PINJAM GAGAL (ROLLBACK): A x1 (cukup) + B x2 (stok hanya 1)\n";
try {
    $svc->createLoan($m->id, [['book_id'=>$a->id,'quantity'=>1],['book_id'=>$b->id,'quantity'=>2]]);
    echo "    TIDAK ROLLBACK!\n";
} catch (InsufficientStockException $e) {
    echo "    Exception: ".$e->getMessage()."\n";
}
echo "    ".$snap()."\n\n";

echo "[4] PENGEMBALIAN\n";
$r = $svc->returnLoan($loan);
echo "    status={$r->status}\n    ".$snap()."\n";
