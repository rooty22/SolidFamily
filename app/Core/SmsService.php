<?php

namespace App\Core;

use App\Models\Setting;

class SmsService
{
    /**
     * Send an SMS message using the configured gateway.
     *
     * @param string $mobile E.164 normalized mobile number (e.g. +9665xxxxxxxx)
     * @param string $message The message text to send
     * @return array ['success' => bool, 'message' => string, 'provider' => string, 'raw' => mixed]
     */
    public static function send(string $mobile, string $message): array
    {
        $mode = site_setting('otp_mode', 'demo');
        if ($mode === 'demo') {
            return [
                'success' => true,
                'message' => 'تمت المحاكاة بنجاح (وضع ديمو تجريبي)',
                'provider' => 'demo',
                'raw' => ['code_simulated' => true],
            ];
        }

        if ($mode === 'disabled') {
            return [
                'success' => true,
                'message' => 'التحقق معطل بالنظام',
                'provider' => 'none',
                'raw' => [],
            ];
        }

        $provider = site_setting('sms_provider', 'taqnyat');
        $sender = site_setting('sms_sender_name', site_name());
        $apiKey = site_setting('sms_api_key', '');

        // Standardize mobile format
        $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);

        switch ($provider) {
            case 'taqnyat':
                return self::sendTaqnyat($cleanMobile, $message, $sender, $apiKey);

            case 'unifonic':
                $appSid = site_setting('sms_app_sid', '');
                return self::sendUnifonic($cleanMobile, $message, $sender, $appSid);

            case '4jawaly':
                $username = site_setting('sms_username', '');
                $password = site_setting('sms_password', '');
                return self::send4jawaly($cleanMobile, $message, $sender, $apiKey, $username, $password);

            case 'msegat':
                $username = site_setting('sms_username', '');
                return self::sendMsegat($cleanMobile, $message, $sender, $username, $apiKey);

            case 'twilio':
                $sid = site_setting('sms_app_sid', '');
                return self::sendTwilio($mobile, $message, $sender, $sid, $apiKey);

            case 'custom':
                $url = site_setting('sms_custom_url', '');
                return self::sendCustom($mobile, $message, $sender, $apiKey, $url);

            default:
                return [
                    'success' => false,
                    'message' => 'مزود الخدمة المحدد غير معروف (' . $provider . ')',
                    'provider' => $provider,
                ];
        }
    }

    private static function sendTaqnyat(string $mobile, string $message, string $sender, string $token): array
    {
        if (empty($token)) {
            return ['success' => false, 'message' => 'لم يتم إدخال مفتاح API لمزود تقنيات (Taqnyat)', 'provider' => 'taqnyat'];
        }

        $endpoint = 'https://api.taqnyat.sa/v1/messages';
        $payload = json_encode([
            'recipients' => [$mobile],
            'body' => $message,
            'sender' => $sender,
        ], JSON_UNESCAPED_UNICODE);

        return self::httpPostJson($endpoint, $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ], 'taqnyat');
    }

    private static function sendUnifonic(string $mobile, string $message, string $sender, string $appSid): array
    {
        if (empty($appSid)) {
            return ['success' => false, 'message' => 'لم يتم إدخال AppSid لمزود يوني فونيك (Unifonic)', 'provider' => 'unifonic'];
        }

        $endpoint = 'https://el.cloud.unifonic.com/rest/SMS/messages';
        $data = [
            'AppSid' => $appSid,
            'Recipient' => $mobile,
            'Body' => $message,
            'SenderID' => $sender,
        ];

        return self::httpPostForm($endpoint, $data, 'unifonic');
    }

    private static function send4jawaly(string $mobile, string $message, string $sender, string $apiKey, string $username, string $password): array
    {
        if (empty($apiKey) && (empty($username) || empty($password))) {
            return ['success' => false, 'message' => 'بيانات اعتماد فور جوالي غير مكتملة', 'provider' => '4jawaly'];
        }

        $endpoint = 'https://api-v2.4jawaly.com/api/v1/account/area/sms/send';
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if (!empty($apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        } else {
            $headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
        }

        $payload = json_encode([
            'messages' => [
                [
                    'text' => $message,
                    'numbers' => [$mobile],
                    'sender' => $sender,
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);

        return self::httpPostJson($endpoint, $payload, $headers, '4jawaly');
    }

    private static function sendMsegat(string $mobile, string $message, string $sender, string $username, string $apiKey): array
    {
        if (empty($apiKey) || empty($username)) {
            return ['success' => false, 'message' => 'بيانات حساب مسجات (Msegat) غير مكتملة', 'provider' => 'msegat'];
        }

        $endpoint = 'https://www.msegat.com/gw/sendsms.php';
        $payload = json_encode([
            'userName' => $username,
            'apiKey' => $apiKey,
            'numbers' => $mobile,
            'userSender' => $sender,
            'msg' => $message,
        ], JSON_UNESCAPED_UNICODE);

        return self::httpPostJson($endpoint, $payload, ['Content-Type: application/json'], 'msegat');
    }

    private static function sendTwilio(string $mobile, string $message, string $fromNumber, string $sid, string $token): array
    {
        if (empty($sid) || empty($token)) {
            return ['success' => false, 'message' => 'بيانات حساب Twilio غير مكتملة (SID أو Token)', 'provider' => 'twilio'];
        }

        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        $data = [
            'To' => $mobile,
            'From' => $fromNumber,
            'Body' => $message,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_USERPWD => "{$sid}:{$token}",
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => 'فشل الاتصال بـ Twilio: ' . $err, 'provider' => 'twilio'];
        }

        $success = ($code >= 200 && $code < 300);
        return [
            'success' => $success,
            'message' => $success ? 'تم إرسال الرسالة بنجاح عبر Twilio' : "خطأ من Twilio (كود: {$code})",
            'provider' => 'twilio',
            'raw' => json_decode((string)$res, true) ?: $res,
        ];
    }

    private static function sendCustom(string $mobile, string $message, string $sender, string $apiKey, string $url): array
    {
        if (empty($url)) {
            return ['success' => false, 'message' => 'لم يتم إدخال رابط الـ API المخصص', 'provider' => 'custom'];
        }

        $url = str_replace(
            ['{mobile}', '{message}', '{sender}', '{key}'],
            [urlencode($mobile), urlencode($message), urlencode($sender), urlencode($apiKey)],
            $url
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        if (!empty($apiKey)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey]);
        }
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => 'فشل الاتصال بالرابط المخصص: ' . $err, 'provider' => 'custom'];
        }

        $success = ($code >= 200 && $code < 300);
        return [
            'success' => $success,
            'message' => $success ? 'تم إرسال الطلب بنجاح للرابط المخصص' : "خطأ بالاستجابة (كود: {$code})",
            'provider' => 'custom',
            'raw' => $res,
        ];
    }

    private static function httpPostJson(string $url, string $jsonPayload, array $headers, string $provider): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => 'فشل الاتصال بالبوابة: ' . $err, 'provider' => $provider];
        }

        $success = ($code >= 200 && $code < 300);
        return [
            'success' => $success,
            'message' => $success ? "تم الإرسال بنجاح عبر بوابة {$provider}" : "فشل الإرسال من البوابة (كود: {$code})",
            'provider' => $provider,
            'raw' => json_decode((string)$res, true) ?: $res,
        ];
    }

    private static function httpPostForm(string $url, array $data, string $provider): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => 'فشل الاتصال بالبوابة: ' . $err, 'provider' => $provider];
        }

        $success = ($code >= 200 && $code < 300);
        return [
            'success' => $success,
            'message' => $success ? "تم الإرسال بنجاح عبر {$provider}" : "فشل الإرسال من البوابة (كود: {$code})",
            'provider' => $provider,
            'raw' => json_decode((string)$res, true) ?: $res,
        ];
    }
}
