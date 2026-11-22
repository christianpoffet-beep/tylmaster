<?php

namespace App\Services\Royalties;

use App\Models\Track;

/**
 * Reads Rebeat's accounting export ("AccountingDataExport-....csv").
 *
 * A note line ("All amounts are in EUR"), an empty line, then the header
 * (id;_artist;_ean;_isrc;_title;_salesdate;_periode;...). One row per track,
 * month, country, shop and subscription type. _cash is what Rebeat credits
 * after its fee (85% of _totalsum).
 */
class RebeatReader
{
    /** Columns without which a file is not a Rebeat export */
    private const REQUIRED = ['_isrc', '_title', '_periode', '_cash'];

    private const MAX_PREAMBLE_LINES = 10;

    /** @var resource */
    private $handle;
    private string $delimiter;
    /** @var array<string, int> column => index */
    private array $columns;

    public function __construct(string $path)
    {
        $handle = @fopen($path, 'r');
        if (!$handle) {
            throw new RoyaltyImportException('Die Datei konnte nicht gelesen werden.');
        }
        $this->handle = $handle;

        for ($i = 0; $i < self::MAX_PREAMBLE_LINES && ($line = fgets($handle)) !== false; $i++) {
            $line = self::utf8(preg_replace('/^\xEF\xBB\xBF/', '', $line));
            if (!str_contains($line, '_isrc') || !str_contains($line, '_cash')) {
                continue;
            }
            $this->delimiter = self::delimiter($line);
            $header = array_map(fn ($c) => trim($c), str_getcsv(rtrim($line, "\r\n"), $this->delimiter, '"', ''));
            $this->columns = array_flip($header);
            if (!array_diff(self::REQUIRED, $header)) {
                return;
            }
            break;
        }

        fclose($handle);
        throw new RoyaltyImportException(
            'Das ist kein Rebeat-Export. Erwartet wird die CSV-Datei aus Rebeat → Accounting → Export («AccountingDataExport-…csv»).'
        );
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Every line as the columns of royalty_lines, all keys always present.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function rows(): \Generator
    {
        while (($fields = fgetcsv($this->handle, null, $this->delimiter, '"', '')) !== false) {
            if ($fields === [null] || count(array_filter($fields, fn ($f) => trim((string) $f) !== '')) === 0) {
                continue;
            }
            $get = fn (string $column) => isset($this->columns[$column], $fields[$this->columns[$column]])
                ? trim(self::utf8((string) $fields[$this->columns[$column]])) : '';

            $period = self::date($get('_periode'));
            if (!$period) {
                continue;
            }

            $externalId = $get('id') !== '' ? $get('id') : md5(implode('|', $fields));

            yield [
                'external_id' => substr($externalId, 0, 64),
                'isrc' => self::isrc($get('_isrc')),
                'ean' => self::ean($get('_ean')),
                'artist' => self::text($get('_artist')),
                'title' => self::text($get('_title')),
                'label' => self::text($get('_cline')),
                'period' => substr($period, 0, 8) . '01',
                'sales_date' => self::date($get('_salesdate')),
                'billing_period' => self::date($get('_periodeabr')),
                'country' => self::text($get('_country'), 80),
                'shop' => self::text($get('_shop'), 80),
                'portal' => self::text($get('_portal'), 80),
                'sales_type' => self::text($get('_salestype'), 30),
                'sales_category' => self::text($get('_salescategory'), 30),
                'stream_type' => self::text($get('_streamtype'), 120),
                'quantity' => (int) round(self::number($get('_count'))),
                'amount_gross' => round(self::number($get('_totalsum')), 6),
                'amount' => round(self::number($get('_cash')), 6),
            ];
        }
    }

    private static function delimiter(string $line): string
    {
        $counts = [';' => substr_count($line, ';'), ',' => substr_count($line, ','), "\t" => substr_count($line, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }

    private static function utf8(string $value): string
    {
        return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    private static function text(string $value, int $max = 255): ?string
    {
        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private static function isrc(string $value): ?string
    {
        $isrc = Track::normalizeIsrc($value);

        return $isrc !== null && strlen($isrc) <= 15 ? $isrc : null;
    }

    /** Excel turns an EAN into "9.01E+12" - such a value is lost, not a number to keep. */
    private static function ean(string $value): ?string
    {
        $digits = preg_replace('/\s+/', '', $value);

        return preg_match('/^\d{8,14}$/', $digits) ? $digits : null;
    }

    /** "31.01.2023 00:00" or "2023-01-31" */
    private static function date(string $value): ?string
    {
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? "{$m[1]}-{$m[2]}-{$m[3]}" : null;
        }

        return null;
    }

    /** "0.003841", "0,003841", "1'234.50", "1.234,50", "3.8E-5" */
    private static function number(string $value): float
    {
        $value = str_replace(["'", ' '], '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace(['.', ','], ['', '.'], $value)
                : str_replace(',', '', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
