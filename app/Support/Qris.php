<?php

namespace App\Support;

/**
 * QRIS mengikuti format EMVCo (TLV: 2 digit tag, 2 digit panjang, nilai) dan diakhiri CRC16 di tag 63.
 * QRIS statis resto dapat diberi nominal (tag 54) supaya tamu tidak mengetik jumlah sendiri.
 */
class Qris
{
    /** @return array<int,array{0:string,1:string}>|null */
    public static function parse(string $payload): ?array
    {
        $out = [];
        for ($i = 0, $n = strlen($payload); $i < $n;) {
            if ($i + 4 > $n || ! ctype_digit(substr($payload, $i, 4))) {
                return null;
            }
            $tag = substr($payload, $i, 2);
            $len = (int) substr($payload, $i + 2, 2);
            if ($i + 4 + $len > $n) {
                return null;
            }
            $out[] = [$tag, substr($payload, $i + 4, $len)];
            $i += 4 + $len;
        }
        return $out;
    }

    public static function crc(string $data): string
    {
        $crc = 0xFFFF;
        foreach (str_split($data) as $c) {
            $crc ^= ord($c) << 8;
            for ($b = 0; $b < 8; $b++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    public static function isValid(string $payload): bool
    {
        $tags = self::parse($payload);
        if (! $tags || $tags[0] !== ['00', '01'] || end($tags)[0] !== '63' || ! str_contains(strtoupper($payload), 'ID.CO.QRIS')) {
            return false;
        }
        return self::crc(substr($payload, 0, -4)) === strtoupper(substr($payload, -4));
    }

    public static function merchantName(string $payload): ?string
    {
        foreach (self::parse($payload) ?? [] as [$tag, $value]) {
            if ($tag === '59') {
                return $value;
            }
        }
        return null;
    }

    public static function withAmount(string $payload, int $amount): string
    {
        $out = '';
        $amountAdded = false;
        foreach (self::parse($payload) ?? [] as [$tag, $value]) {
            if (in_array($tag, ['54', '63'], true)) {
                continue;
            }
            if (! $amountAdded && $tag > '54') {
                $out .= self::field('54', (string) $amount);
                $amountAdded = true;
            }
            // 12 = QR dinamis (sekali pakai, nominal tetap).
            $out .= self::field($tag, $tag === '01' ? '12' : $value);
        }
        if (! $amountAdded) {
            $out .= self::field('54', (string) $amount);
        }
        $out .= '6304';
        return $out.self::crc($out);
    }

    private static function field(string $tag, string $value): string
    {
        return $tag.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }
}
