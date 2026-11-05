<?php

namespace App\Models;

use App\Core\Model;

class LoanRequest extends Model
{
    protected static string $table = 'loan_requests';

    public static function withMember(array $conditions = []): array
    {
        [$whereSql, $params] = self::joinWhere($conditions);
        $sql = 'SELECT loan_requests.*, members.name as member_name, members.mobile as member_mobile, members.serial_no as member_serial, members.uid as member_uid,
                       loans.id AS loan_id, loans.status AS loan_status
                FROM loan_requests JOIN members ON members.id = loan_requests.member_id
                LEFT JOIN loans ON loans.loan_request_id = loan_requests.id'
                . ($whereSql ? " WHERE $whereSql" : '') . ' ORDER BY loan_requests.created_at ASC';
        return self::raw($sql, $params);
    }

    /**
     * The waiting list for disbursement: requests the admin approved whose loan has not been issued yet, in the order
     * they were approved. Returns request id => position (1, 2, 3 ...). A request that gets approved later always takes
     * the next free number; a request whose loan is issued leaves the list and everybody behind it moves up.
     */
    public static function waitingPositions(): array
    {
        $rows = self::raw(
            "SELECT lr.id FROM loan_requests lr
             LEFT JOIN loans l ON l.loan_request_id = lr.id
             WHERE lr.status = 'approved' AND l.id IS NULL
             ORDER BY COALESCE(lr.reviewed_at, lr.created_at) ASC, lr.id ASC"
        );
        $positions = [];
        foreach ($rows as $i => $r) {
            $positions[(int) $r['id']] = $i + 1;
        }
        return $positions;
    }

    /**
     * The loan that is still being repaid on the share this request is for. While it exists no second loan can be issued
     * on that share, so an approved request simply waits (keeping its number) until the loan is settled.
     */
    public static function blockingLoan(array $request): ?array
    {
        if (empty($request['lot_id']) || !ShareLot::ONE_OPEN_LOAN_PER_LOT) {
            return null;
        }
        return ShareLot::openLoan((int) $request['lot_id']);
    }

    public static function queuePosition(int $requestId): int
    {
        $request = self::find($requestId);
        if (!$request) {
            return 0;
        }

        // When approved: position in the waiting queue for disbursement (requests approved by admin without an issued loan yet)
        if ($request['status'] === 'approved') {
            $approved = self::raw(
                "SELECT lr.id FROM loan_requests lr
                 LEFT JOIN loans l ON l.loan_request_id = lr.id
                 WHERE lr.status = 'approved' AND l.id IS NULL
                 ORDER BY COALESCE(lr.reviewed_at, lr.created_at) ASC, lr.id ASC"
            );
            foreach ($approved as $index => $r) {
                if ((int) $r['id'] === $requestId) {
                    return $index + 1;
                }
            }
            return 0;
        }

        // When pending: position for admin review queue
        if ($request['status'] === 'pending') {
            $pending = self::where(['status' => 'pending'], 'created_at ASC');
            foreach ($pending as $index => $r) {
                if ((int) $r['id'] === $requestId) {
                    return $index + 1;
                }
            }
        }

        return 0;
    }

    protected static function joinWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }
        $parts = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $key = str_replace(['.', ' '], '_', $column);
            $parts[] = "$column = :$key";
            $params[$key] = $value;
        }
        return [implode(' AND ', $parts), $params];
    }
}
