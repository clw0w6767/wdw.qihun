<?php
class Crypto {
    private static function getKey() {
        $key = getenv('APP_ENCRYPT_KEY');
        if ($key && strlen($key)>=32) return substr(hash('sha256', $key), 0, 32);
        $keyFile = __DIR__.'/../encrypt.key';
        if (file_exists($keyFile)) return substr(hash('sha256', file_get_contents($keyFile)), 0, 32);
        return substr(hash('sha256', 'WEIDA_GO_DEFAULT_KEY_CHANGE_ME'), 0, 32);
    }

    public static function encrypt($plaintext) {
        if (empty($plaintext)) return '';
        $key = self::getKey();
        $ivLen = openssl_cipher_iv_length('aes-256-gcm');
        $iv = openssl_random_pseudo_bytes($ivLen);
        $tag = '';
        $ct = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ct===false) return '';
        return base64_encode($iv.$tag.$ct);
    }

    public static function decrypt($encoded) {
        if (empty($encoded)) return '';
        $data = base64_decode($encoded, true);
        if ($data===false) return false;
        $key = self::getKey();
        $ivLen = openssl_cipher_iv_length('aes-256-gcm');
        $tagLen = 16;
        if (strlen($data) < $ivLen+$tagLen+1) return false;
        $iv = substr($data, 0, $ivLen);
        $tag = substr($data, $ivLen, $tagLen);
        $ct = substr($data, $ivLen+$tagLen);
        return openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}
?>
