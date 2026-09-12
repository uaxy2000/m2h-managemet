<?php

if (!function_exists('countryFlag')) {
    /**
     * Returns the flag emoji for a country name, or empty string if not found.
     */
    function countryFlag(string $name): string
    {
        static $map = [
            'albania'             => 'AL',
            'andorra'             => 'AD',
            'argentina'           => 'AR',
            'australia'           => 'AU',
            'austria'             => 'AT',
            'bahrain'             => 'BH',
            'belgium'             => 'BE',
            'brazil'              => 'BR',
            'bulgaria'            => 'BG',
            'canada'              => 'CA',
            'china'               => 'CN',
            'croatia'             => 'HR',
            'cyprus'              => 'CY',
            'czechia'             => 'CZ',
            'czech republic'      => 'CZ',
            'denmark'             => 'DK',
            'egypt'               => 'EG',
            'estonia'             => 'EE',
            'finland'             => 'FI',
            'france'              => 'FR',
            'germany'             => 'DE',
            'greece'              => 'GR',
            'hungary'             => 'HU',
            'iceland'             => 'IS',
            'india'               => 'IN',
            'ireland'             => 'IE',
            'israel'              => 'IL',
            'italy'               => 'IT',
            'japan'               => 'JP',
            'jordan'              => 'JO',
            'kuwait'              => 'KW',
            'latvia'              => 'LV',
            'lebanon'             => 'LB',
            'liechtenstein'       => 'LI',
            'lithuania'           => 'LT',
            'luxembourg'          => 'LU',
            'malta'               => 'MT',
            'mexico'              => 'MX',
            'monaco'              => 'MC',
            'montenegro'          => 'ME',
            'morocco'             => 'MA',
            'netherlands'         => 'NL',
            'new zealand'         => 'NZ',
            'nigeria'             => 'NG',
            'north macedonia'     => 'MK',
            'norway'              => 'NO',
            'oman'                => 'OM',
            'pakistan'            => 'PK',
            'poland'              => 'PL',
            'portugal'            => 'PT',
            'qatar'               => 'QA',
            'romania'             => 'RO',
            'russia'              => 'RU',
            'san marino'          => 'SM',
            'saudi arabia'        => 'SA',
            'serbia'              => 'RS',
            'singapore'           => 'SG',
            'slovakia'            => 'SK',
            'slovenia'            => 'SI',
            'south africa'        => 'ZA',
            'south korea'         => 'KR',
            'spain'               => 'ES',
            'sweden'              => 'SE',
            'switzerland'         => 'CH',
            'turkey'              => 'TR',
            'türkiye'             => 'TR',
            'turkiye'             => 'TR',
            'ukraine'             => 'UA',
            'united arab emirates'=> 'AE',
            'uae'                 => 'AE',
            'united kingdom'      => 'GB',
            'uk'                  => 'GB',
            'united states'       => 'US',
            'usa'                 => 'US',
        ];

        $key = mb_strtolower(trim($name), 'UTF-8');
        $iso = $map[$key] ?? null;

        if (!$iso) {
            return '';
        }

        $flag = '';
        foreach (str_split(strtoupper($iso)) as $char) {
            $flag .= mb_chr(ord($char) - ord('A') + 0x1F1E6, 'UTF-8');
        }

        return $flag;
    }
}
