<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = null): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Fields that hold numbers/codes: Arabic-Indic digits typed on an Arabic keyboard are converted to ASCII. */
    private const DIGIT_FIELDS = [
        'mobile', 'phone', 'national_id', 'code', 'iban', 'bank_account_number', 'amount', 'amount_requested',
        'partial_amount', 'shares_count', 'installments_count', 'installments_months', 'months_count',
        'admin_fee_percent', 'share_value', 'founding_fee_per_share', 'loan_admin_fee_percent', 'max_loan_ratio',
        'subscription_due_day', 'official_phone', 'official_whatsapp', 'start_month', 'partial_month',
        'loan_date', 'payment_date', 'birth_date', 'member_id', 'installment_id', 'loan_request_id',
    ];

    /**
     * Scalars only (array-style injection such as name[]=x is dropped), NUL bytes removed,
     * surrounding whitespace trimmed (except passwords, where spaces may be intentional).
     */
    private function clean(string $key, $value)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = str_replace(chr(0), '', (string) $value);
        if (stripos($key, 'password') === false) {
            $value = trim($value);
        }
        if (in_array($key, self::DIGIT_FIELDS, true)) {
            $value = ascii_digits($value);
        }
        return $value;
    }

    protected function input(string $key, $default = null)
    {
        foreach ([$_POST, $_GET] as $source) {
            if (array_key_exists($key, $source)) {
                $value = $this->clean($key, $source[$key]);
                return $value === null ? $default : $value;
            }
        }
        return $default;
    }

    protected function all(): array
    {
        $out = [];
        foreach (array_merge($_GET, $_POST) as $key => $value) {
            $clean = $this->clean((string) $key, $value);
            if ($clean !== null) {
                $out[$key] = $clean;
            }
        }
        return $out;
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!Session::verifyCsrf($token)) {
            http_response_code(419);
            die('انتهت صلاحية الجلسة، الرجاء إعادة المحاولة.');
        }
    }
}
