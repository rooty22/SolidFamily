<?php

namespace App\Models;

use App\Core\Model;

class MonthlySubscription extends Model
{
    protected static string $table = 'monthly_subscriptions';

    public static function forMember(int $memberId): array
    {
        return self::where(['member_id' => $memberId], 'month DESC');
    }

    public static function ensureMonthExists(int $memberId, string $month): array
    {
        $member = Member::find($memberId);
        $shareValue = (float) Setting::get('share_value', 0);
        $dueDay = (int) Setting::get('subscription_due_day', 10);
        $sharesCount = (int) ($member['shares_count'] ?? 0);
        $amountDue = round($sharesCount * $shareValue, 2);

        $existing = self::first(['member_id' => $memberId, 'month' => $month]);
        if ($existing) {
            // A current/future month nobody has paid anything on follows the member's latest share count / share value;
            // otherwise a member who received shares after the row was created would get that month for free.
            $untouched = $existing['status'] === 'unpaid' && (float) $existing['amount_paid'] == 0.0 && $month >= date('Y-m');
            if ($untouched && ((int) $existing['shares_count_snapshot'] !== $sharesCount
                    || (float) $existing['share_value_snapshot'] !== $shareValue
                    || (float) $existing['amount_due'] !== $amountDue)) {
                self::update($existing['id'], [
                    'shares_count_snapshot' => $sharesCount,
                    'share_value_snapshot' => $shareValue,
                    'amount_due' => $amountDue,
                ]);
                return self::find($existing['id']);
            }
            return $existing;
        }

        $id = self::create([
            'member_id' => $memberId,
            'month' => $month,
            'shares_count_snapshot' => $sharesCount,
            'share_value_snapshot' => $shareValue,
            'amount_due' => $amountDue,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'due_date' => month_due_date($month, $dueDay),
        ]);

        return self::find($id);
    }
}
