<?php
require_once 'config.php';

class JWT {
    private static $key = 'your-secret-key'; // 在实际应用中应该使用环境变量存储
    private static $algorithm = 'HS256';

    public static function generateToken($user) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // token有效期1小时

        $payload = array(
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'iat' => $issuedAt,
            'exp' => $expirationTime
        );

        $header = array(
            'alg' => self::$algorithm,
            'typ' => 'JWT'
        );

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', 
            $headerEncoded . "." . $payloadEncoded, 
            self::$key, 
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);

        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }

    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', 
            $headerEncoded . "." . $payloadEncoded, 
            self::$key, 
            true
        );

        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if ($payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
} 