<?php

namespace Modules\Products\Exceptions;

use Modules\Shared\Exceptions\InsufficientStockException as BaseInsufficientStockException;

/**
 * Dipertahankan agar kode lama (ProductController) tetap berfungsi.
 * Implementasi utamanya kini berada di Shared Kernel supaya bisa dipakai
 * lintas bounded context (mis. OrderService di Sales Context).
 */
class InsufficientStockException extends BaseInsufficientStockException
{
}
