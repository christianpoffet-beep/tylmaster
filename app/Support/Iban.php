<?php

namespace App\Support;

/** IBAN checks for payment files: format and check digits, QR-IBANs, the Swiss clearing number. */
class Iban
{
    public static function normalize(?string $iban): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $iban));

        return $clean !== '' ? $clean : null;
    }

    /** Country, check digits and length plausible, mod 97 = 1 */
    public static function isValid(?string $iban): bool
    {
        $iban = self::normalize($iban);
        if (!$iban || !preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) return false;
        if (in_array(substr($iban, 0, 2), ['CH', 'LI'], true) && strlen($iban) !== 21) return false;

        $digits = '';
        foreach (str_split(substr($iban, 4) . substr($iban, 0, 4)) as $char) {
            $digits .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }
        $rest = 0;
        foreach (str_split($digits, 7) as $chunk) {
            $rest = (int) ($rest . $chunk) % 97;
        }

        return $rest === 1;
    }

    /** QR-IBANs (IID 30000-31999) only take QR bills, no ordinary transfer */
    public static function isQrIban(?string $iban): bool
    {
        $iban = self::normalize($iban);
        if (!$iban || !in_array(substr($iban, 0, 2), ['CH', 'LI'], true)) return false;
        $iid = (int) substr($iban, 4, 5);

        return $iid >= 30000 && $iid <= 31999;
    }

    /** The bank clearing number (IID) of a Swiss or Liechtenstein IBAN */
    public static function iid(?string $iban): ?string
    {
        $iban = self::normalize($iban);

        return $iban && in_array(substr($iban, 0, 2), ['CH', 'LI'], true) ? substr($iban, 4, 5) : null;
    }

    public static function format(?string $iban): ?string
    {
        $iban = self::normalize($iban);

        return $iban ? trim(chunk_split($iban, 4, ' ')) : null;
    }
}
