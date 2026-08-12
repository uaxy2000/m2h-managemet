<?php

namespace App\Helpers;

class PhoneFormatter
{
    // [cc => [iso, national_digit_count, groups]]
    // Longer codes listed first so 3-digit codes are tried before 2-digit before 1-digit.
    private static array $countries = [
        // 3-digit codes
        '971' => ['UAE',  9, [2, 3, 4]],
        '966' => ['SA',   9, [2, 3, 4]],
        '974' => ['QA',   8, [4, 4]],
        '972' => ['IL',   9, [2, 3, 4]],
        '961' => ['LB',   8, [2, 3, 3]],
        '357' => ['CY',   8, [4, 4]],
        '356' => ['MT',   8, [4, 4]],
        '353' => ['IE',  10, [2, 4, 4]],
        '352' => ['LU',   9, [3, 3, 3]],
        '351' => ['PT',   9, [3, 3, 3]],
        '358' => ['FI',   9, [2, 3, 4]],
        '370' => ['LT',   8, [4, 4]],
        '371' => ['LV',   8, [4, 4]],
        '372' => ['EE',   8, [4, 4]],
        '381' => ['RS',   8, [2, 3, 3]],
        '385' => ['HR',   8, [2, 3, 3]],
        '386' => ['SI',   8, [2, 3, 3]],
        '387' => ['BA',   8, [2, 3, 3]],
        '389' => ['MK',   8, [2, 3, 3]],
        '359' => ['BG',   9, [2, 3, 4]],
        '373' => ['MD',   8, [2, 3, 3]],
        '374' => ['AM',   8, [2, 3, 3]],
        '375' => ['BY',   9, [2, 3, 4]],
        '380' => ['UA',   9, [2, 3, 4]],
        '382' => ['ME',   8, [2, 3, 3]],
        '420' => ['CZ',   9, [3, 3, 3]],
        '421' => ['SK',   9, [3, 3, 3]],
        '423' => ['LI',   7, [3, 4]],

        // 2-digit codes
        '90' => ['TR',   10, [3, 3, 2, 2]],
        '30' => ['GR',   10, [3, 3, 4]],
        '44' => ['GB',   10, [4, 6]],
        '49' => ['DE',   10, [3, 4, 3]],
        '33' => ['FR',    9, [1, 2, 2, 2, 2]],
        '39' => ['IT',   10, [3, 4, 3]],
        '34' => ['ES',    9, [3, 3, 3]],
        '31' => ['NL',    9, [1, 4, 4]],
        '32' => ['BE',    9, [3, 2, 2, 2]],
        '41' => ['CH',    9, [2, 3, 4]],
        '43' => ['AT',    9, [3, 3, 3]],
        '46' => ['SE',    9, [2, 3, 4]],
        '47' => ['NO',    8, [4, 4]],
        '48' => ['PL',    9, [3, 3, 3]],
        '36' => ['HU',    9, [2, 3, 4]],
        '40' => ['RO',    9, [2, 3, 4]],
        '45' => ['DK',    8, [2, 2, 2, 2]],
        '61' => ['AU',    9, [1, 4, 4]],
        '64' => ['NZ',    9, [2, 3, 4]],
        '81' => ['JP',   10, [3, 4, 3]],
        '82' => ['KR',   10, [2, 4, 4]],
        '86' => ['CN',   11, [3, 4, 4]],
        '91' => ['IN',   10, [5, 5]],
        '92' => ['PK',   10, [3, 3, 4]],
        '62' => ['ID',    9, [3, 4, 4]],
        '63' => ['PH',   10, [3, 4, 3]],
        '65' => ['SG',    8, [4, 4]],
        '66' => ['TH',    9, [2, 3, 4]],
        '20' => ['EG',   10, [2, 4, 4]],
        '27' => ['ZA',    9, [2, 3, 4]],

        // 1-digit codes
        '1' => ['US',    10, [3, 3, 4]],
        '7' => ['RU',    10, [3, 3, 2, 2]],
    ];

    private static array $flags = [
        'TR' => '🇹🇷', 'GR' => '🇬🇷', 'GB' => '🇬🇧', 'DE' => '🇩🇪',
        'FR' => '🇫🇷', 'IT' => '🇮🇹', 'ES' => '🇪🇸', 'NL' => '🇳🇱',
        'BE' => '🇧🇪', 'CH' => '🇨🇭', 'AT' => '🇦🇹', 'SE' => '🇸🇪',
        'NO' => '🇳🇴', 'PL' => '🇵🇱', 'HU' => '🇭🇺', 'RO' => '🇷🇴',
        'DK' => '🇩🇰', 'AU' => '🇦🇺', 'NZ' => '🇳🇿', 'JP' => '🇯🇵',
        'KR' => '🇰🇷', 'CN' => '🇨🇳', 'IN' => '🇮🇳', 'PK' => '🇵🇰',
        'EG' => '🇪🇬', 'ZA' => '🇿🇦', 'UAE' => '🇦🇪', 'SA' => '🇸🇦',
        'US' => '🇺🇸', 'RU' => '🇷🇺', 'CY' => '🇨🇾', 'IL' => '🇮🇱',
        'LB' => '🇱🇧', 'QA' => '🇶🇦', 'IE' => '🇮🇪', 'PT' => '🇵🇹',
        'BG' => '🇧🇬', 'UA' => '🇺🇦', 'CZ' => '🇨🇿', 'SK' => '🇸🇰',
        'RS' => '🇷🇸', 'HR' => '🇭🇷', 'SI' => '🇸🇮', 'BA' => '🇧🇦',
        'LT' => '🇱🇹', 'LV' => '🇱🇻', 'EE' => '🇪🇪', 'FI' => '🇫🇮',
        'MT' => '🇲🇹', 'LU' => '🇱🇺', 'SG' => '🇸🇬', 'TH' => '🇹🇭',
        'ID' => '🇮🇩', 'PH' => '🇵🇭', 'LK' => '🇱🇰',
        'AM' => '🇦🇲', 'BY' => '🇧🇾', 'MD' => '🇲🇩',
        'ME' => '🇲🇪', 'MK' => '🇲🇰', 'LI' => '🇱🇮',
    ];

    /**
     * @return array{formatted: string, valid: bool, iso: string|null, flag: string|null}
     */
    public static function format(string $raw): array
    {
        if (trim($raw) === '') {
            return ['formatted' => $raw, 'valid' => false, 'iso' => null, 'flag' => null];
        }

        // Normalize: remove spaces, dashes, parens, dots, slashes
        $cleaned = preg_replace('/[\s\-\.\(\)\/]/', '', $raw);

        // Convert 00xx to +xx
        if (str_starts_with($cleaned, '00')) {
            $cleaned = '+' . substr($cleaned, 2);
        }

        if (!str_starts_with($cleaned, '+')) {
            return ['formatted' => $raw, 'valid' => false, 'iso' => null, 'flag' => null];
        }

        $digits = substr($cleaned, 1); // digits after +

        // Try longest country code first (3-digit → 2-digit → 1-digit)
        foreach ([3, 2, 1] as $ccLen) {
            $cc = substr($digits, 0, $ccLen);

            if (!isset(self::$countries[$cc])) {
                continue;
            }

            [$iso, $nationalLen, $groups] = self::$countries[$cc];
            $national = substr($digits, $ccLen);
            $valid    = strlen($national) === $nationalLen;

            // Group the national number by the pattern
            $parts = [];
            $pos   = 0;
            foreach ($groups as $len) {
                $chunk = substr($national, $pos, $len);
                if ($chunk !== '') {
                    $parts[] = $chunk;
                }
                $pos += $len;
            }

            $formatted = '+' . $cc . ' ' . implode(' ', $parts);
            $flag      = self::$flags[$iso] ?? null;

            return compact('formatted', 'valid', 'iso', 'flag');
        }

        // Unknown country code
        return ['formatted' => $raw, 'valid' => false, 'iso' => null, 'flag' => null];
    }
}
