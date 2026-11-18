<?php

namespace App\Support;

/**
 * Time-based one-time passwords (RFC 6238): the 6-digit codes of Google Authenticator,
 * Microsoft Authenticator, Authy and similar apps. SHA-1, 6 digits, 30 seconds.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function newSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function code(string $secret, int $step): string
    {
        $key = self::base32Decode($secret);
        $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $step), $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /** The step whose code matches (this one, or one either side for clock drift), or null. */
    public static function matchStep(string $secret, string $code, ?int $now = null): ?int
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) { return null; }
        $current = intdiv($now ?? time(), 30);
        foreach ([0, -1, 1] as $drift) {
            $step = $current + $drift;
            if (hash_equals(self::code($secret, $step), $code)) { return $step; }
        }
        return null;
    }

    public static function uri(string $secret, string $account, string $issuer = 'FNA'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    }

    private static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $c) { $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT); }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) { $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))]; }
        return $out;
    }

    private static function base32Decode(string $text): string
    {
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $text))) as $c) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) { if (strlen($byte) === 8) { $out .= chr(bindec($byte)); } }
        return $out;
    }
}
