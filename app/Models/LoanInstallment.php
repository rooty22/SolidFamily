<?php

namespace App\Models;

use App\Core\Model;

class LoanInstallment extends Model
{
    protected static string $table = 'loan_installments';

    public static function forLoan(int $loanId): array
    {
        return self::where(['loan_id' => $loanId], 'installment_number ASC');
    }
}
