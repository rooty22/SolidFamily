<?php

namespace App\Models;

use App\Core\Model;

class FoundingPayment extends Model
{
    protected static string $table = 'founding_payments';

    public static function forMember(int $memberId): array
    {
        return self::where(['member_id' => $memberId], 'payment_date DESC');
    }
}
