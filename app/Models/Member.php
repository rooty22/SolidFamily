<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Validator;

class Member extends Model
{
    protected static string $table = 'members';

    public static function search(string $term): array
    {
        $sql = 'SELECT * FROM members WHERE mobile LIKE ? OR national_id LIKE ? OR name LIKE ? ORDER BY created_at DESC';
        $like = "%{$term}%";
        return self::raw($sql, [$like, $like, $like]);
    }

    /**
     * Bring member input to its canonical stored form: +966 mobile, lower-case email,
     * upper-case IBAN and account numbers without spaces/dashes.
     */
    public static function normalize(array $data): array
    {
        if (isset($data['mobile'])) {
            $data['mobile'] = normalize_mobile((string) $data['mobile']);
        }
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim((string) $data['email']));
        }
        if (isset($data['iban'])) {
            $data['iban'] = strtoupper(preg_replace('/[\s\-]/', '', (string) $data['iban']));
        }
        if (isset($data['bank_account_number'])) {
            $data['bank_account_number'] = strtoupper(preg_replace('/[\s\-]/', '', (string) $data['bank_account_number']));
        }
        return $data;
    }

    /**
     * Validate only the requested member fields. $data must already be passed through normalize().
     * Allowed $fields: name, mobile, email, national_id, birth_date, national_address, bank_account_number,
     * iban, bank_name, password (+ password_confirmation when present), shares_count.
     */
    public static function validate(array $data, array $fields, ?int $ignoreId = null, bool $passwordRequired = true, bool $requireAll = false): Validator
    {
        $v = Validator::make($data);

        foreach ($fields as $field) {
            switch ($field) {
                case 'name':
                    $v->required('name', 'الاسم')->min('name', 2, 'الاسم')->max('name', 150, 'الاسم')->personName('name', 'الاسم');
                    break;
                case 'mobile':
                    $v->required('mobile', 'رقم الجوال')->mobile('mobile', 'رقم الجوال')
                        ->unique('mobile', 'members', 'mobile', 'رقم الجوال', $ignoreId);
                    break;
                case 'email':
                    $v->required('email', 'البريد الإلكتروني')->email('email', 'البريد الإلكتروني')
                        ->unique('email', 'members', 'email', 'البريد الإلكتروني', $ignoreId);
                    break;
                case 'national_id':
                    $v->required('national_id', 'رقم الهوية الوطنية')->nationalId('national_id', 'رقم الهوية الوطنية')
                        ->unique('national_id', 'members', 'national_id', 'رقم الهوية الوطنية', $ignoreId);
                    break;
                case 'birth_date':
                    $requireAll && $v->required('birth_date', 'تاريخ الميلاد');
                    $v->date('birth_date', 'الميلاد');
                    break;
                case 'national_address':
                    $requireAll && $v->required('national_address', 'العنوان الوطني');
                    $v->max('national_address', 255, 'العنوان الوطني');
                    break;
                case 'bank_account_number':
                    $requireAll && $v->required('bank_account_number', 'رقم الحساب البنكي');
                    $v->regex('bank_account_number', '/^[A-Z0-9]{5,34}$/', 'رقم الحساب البنكي يجب أن يكون من 5 إلى 34 حرفاً/رقماً.');
                    break;
                case 'iban':
                    $requireAll && $v->required('iban', 'رقم الآيبان');
                    $v->iban('iban', 'رقم الآيبان');
                    break;
                case 'bank_name':
                    $requireAll && $v->required('bank_name', 'اسم البنك');
                    $v->max('bank_name', 100, 'اسم البنك')
                        ->regex('bank_name', "/^[\\p{L}\\p{M}\\p{N}\\s.&'\\-]+$/u", 'اسم البنك يحتوي على رموز غير مسموحة.');
                    break;
                case 'password':
                    if ($passwordRequired) {
                        $v->required('password', 'كلمة المرور');
                    }
                    $v->min('password', 8, 'كلمة المرور')->max('password', 72, 'كلمة المرور');
                    if (array_key_exists('password_confirmation', $data)) {
                        $v->matches('password_confirmation', 'password', 'تأكيد كلمة المرور');
                    }
                    break;
                case 'shares_count':
                    $v->integer('shares_count', 'عدد الأسهم', 0, 10000);
                    break;
            }
        }

        return $v;
    }
}
