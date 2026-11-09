<?php

namespace Modules\Circulation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Circulation\Models\Loan;

class BookReturned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Loan $loan
    ) {
    }
}
