<?php

namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function forMember(int $memberId): array
    {
        return self::raw(
            'SELECT * FROM notifications WHERE target_type = "all" OR target_member_id = ? ORDER BY created_at DESC',
            [$memberId]
        );
    }

    /**
     * Create a system notification for one member. With a $dedupeKey the notification is created at most once per
     * (member, key) - used by bin/send_reminders.php. Returns false when it already existed.
     */
    public static function systemNotify(int $memberId, string $title, string $body, ?string $dedupeKey = null): bool
    {
        $data = [
            'title' => $title,
            'body' => $body,
            'target_type' => 'specific',
            'target_member_id' => $memberId,
            'category' => 'system',
            'status' => 'sent',
            'created_by' => null,
        ];

        if ($dedupeKey !== null) {
            if (self::first(['target_member_id' => $memberId, 'dedupe_key' => $dedupeKey])) {
                return false;
            }
            $data['dedupe_key'] = $dedupeKey;
        }

        self::create($data);
        return true;
    }

    public static function withTargetName(array $filters = []): array
    {
        $sql = 'SELECT notifications.*, members.name as target_member_name, admins.name as created_by_name
                FROM notifications
                LEFT JOIN members ON members.id = notifications.target_member_id
                LEFT JOIN admins ON admins.id = notifications.created_by
                WHERE 1=1';
        $params = [];

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(notifications.created_at) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(notifications.created_at) <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['target_type'])) {
            $sql .= ' AND notifications.target_type = :target_type';
            $params['target_type'] = $filters['target_type'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND notifications.status = :status';
            $params['status'] = $filters['status'];
        }

        $sql .= ' ORDER BY notifications.created_at DESC';

        return self::raw($sql, $params);
    }
}
