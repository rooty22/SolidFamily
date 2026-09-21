<?php

namespace App\Core;

/**
 * Chainable validator. Every rule except required() ignores empty values (combine with required()),
 * and the FIRST error recorded for a field is the one kept.
 */
class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        foreach ($data as $field => $value) {
            if (!is_scalar($value) && $value !== null) {
                $this->errors[$field] = 'قيمة غير صالحة.';
            }
        }
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    private function fail(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';
        return is_scalar($value) ? (string) $value : '';
    }

    public function required(string $field, string $label): self
    {
        if (trim($this->value($field)) === '') {
            $this->fail($field, "حقل {$label} مطلوب.");
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && (!filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value) > 150)) {
            $this->fail($field, "صيغة {$label} غير صحيحة.");
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && !is_numeric($value)) {
            $this->fail($field, "حقل {$label} يجب أن يكون رقما.");
        }
        return $this;
    }

    /** Whole number within [min, max]. */
    public function integer(string $field, string $label, int $min = 0, int $max = PHP_INT_MAX): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        if (!preg_match('/^\d{1,15}$/', $value) || (int) $value < $min || (int) $value > $max) {
            $this->fail($field, "حقل {$label} يجب أن يكون رقماً صحيحاً بين {$min} و{$max}.");
        }
        return $this;
    }

    /** Positive/decimal amount with at most 2 decimals, within [min, max]. */
    public function decimal(string $field, string $label, float $min = 0, float $max = 1e9): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $value) || (float) $value < $min || (float) $value > $max) {
            $this->fail($field, "حقل {$label} يجب أن يكون مبلغاً صحيحاً بين {$min} و{$max} (بحد أقصى خانتين عشريتين).");
        }
        return $this;
    }

    public function min(string $field, int $length, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && mb_strlen($value) < $length) {
            $this->fail($field, "حقل {$label} يجب ألا يقل عن {$length} أحرف.");
        }
        return $this;
    }

    public function max(string $field, int $length, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && mb_strlen($value) > $length) {
            $this->fail($field, "حقل {$label} يجب ألا يزيد عن {$length} حرف.");
        }
        return $this;
    }

    public function matches(string $field, string $otherField, string $label): self
    {
        if ($this->value($field) !== $this->value($otherField)) {
            $this->fail($field, "حقل {$label} غير متطابق.");
        }
        return $this;
    }

    public function unique(string $field, string $table, string $column, string $label, $ignoreId = null): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        $sql = "SELECT COUNT(*) as c FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        if ($ignoreId) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetch()['c'] > 0) {
            $this->fail($field, "{$label} مستخدم بالفعل.");
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->fail($field, "قيمة {$label} غير صحيحة.");
        }
        return $this;
    }

    public function regex(string $field, string $pattern, string $message): self
    {
        $value = $this->value($field);
        if ($value !== '' && !preg_match($pattern, $value)) {
            $this->fail($field, $message);
        }
        return $this;
    }

    /** A real calendar date (Y-m-d) between 1900-01-01 and today (or up to $maxFutureDays ahead). */
    public function date(string $field, string $label, int $maxFutureDays = 0): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        $d = \DateTime::createFromFormat('!Y-m-d', $value);
        $valid = $d && $d->format('Y-m-d') === $value;
        if (!$valid || $d < new \DateTime('1900-01-01') || $d > new \DateTime("today +{$maxFutureDays} days")) {
            $this->fail($field, "تاريخ {$label} غير صحيح.");
        }
        return $this;
    }

    /** A real calendar month (Y-m), from 2000-01 up to $maxFutureMonths months ahead. */
    public function month(string $field, string $label, int $maxFutureMonths = 60): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        $d = \DateTime::createFromFormat('!Y-m', $value);
        $valid = $d && $d->format('Y-m') === $value;
        if (!$valid || $d < new \DateTime('2000-01-01') || $d > new \DateTime("first day of this month +{$maxFutureMonths} months")) {
            $this->fail($field, "شهر {$label} غير صحيح (الصيغة YYYY-MM).");
        }
        return $this;
    }

    /** Canonical mobile as produced by normalize_mobile(): "+" followed by 9-15 digits. */
    public function mobile(string $field, string $label): self
    {
        return $this->regex($field, '/^\+[1-9]\d{8,14}$/', "صيغة {$label} غير صحيحة (مثال: 05xxxxxxxx أو +9665xxxxxxxx).");
    }

    public function nationalId(string $field, string $label): self
    {
        return $this->regex($field, '/^[12]\d{9}$/', "{$label} يجب أن يكون 10 أرقام ويبدأ بـ 1 أو 2.");
    }

    /** Letters (any script), spaces and . ' - only. Rejects digits, symbols and markup. */
    public function personName(string $field, string $label): self
    {
        return $this->regex($field, "/^[\\p{L}\\p{M}][\\p{L}\\p{M}\\s.'\\-]*$/u", "حقل {$label} يجب أن يحتوي على حروف فقط.");
    }

    public function iban(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $value) || !self::ibanChecksum($value)) {
            $this->fail($field, "صيغة {$label} غير صحيحة.");
        }
        return $this;
    }

    private static function ibanChecksum(string $iban): bool
    {
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $ch) {
            $numeric .= ctype_alpha($ch) ? (string) (ord($ch) - 55) : $ch;
        }
        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) ($remainder . $chunk) % 97;
        }
        return $remainder === 1;
    }

    /** http(s) URL; with $allowPath a site-relative path like "uploads/logo.png" is accepted too. */
    public function url(string $field, string $label, bool $allowPath = false): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        $isUrl = (bool) filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $value);
        $isPath = $allowPath && preg_match('#^[A-Za-z0-9_\-./]+$#', $value) && !str_contains($value, '..');
        if (!$isUrl && !$isPath) {
            $this->fail($field, "رابط {$label} غير صحيح (يجب أن يبدأ بـ http:// أو https://).");
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return (string) (current($this->errors) ?: '');
    }
}
