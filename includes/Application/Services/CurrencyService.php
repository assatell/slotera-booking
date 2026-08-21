<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

final class CurrencyService
{
    /** @return array<string, array{name:string,symbol:string}> */
    public static function currencies(): array
    {
        return [
            'AED'=>['name'=>'United Arab Emirates dirham','symbol'=>'د.إ'], 'AFN'=>['name'=>'Afghan afghani','symbol'=>'؋'], 'ALL'=>['name'=>'Albanian lek','symbol'=>'L'], 'AMD'=>['name'=>'Armenian dram','symbol'=>'֏'],
            'ANG'=>['name'=>'Netherlands Antillean guilder','symbol'=>'ƒ'], 'AOA'=>['name'=>'Angolan kwanza','symbol'=>'Kz'], 'ARS'=>['name'=>'Argentine peso','symbol'=>'$'], 'AUD'=>['name'=>'Australian dollar','symbol'=>'$'],
            'AWG'=>['name'=>'Aruban florin','symbol'=>'ƒ'], 'AZN'=>['name'=>'Azerbaijani manat','symbol'=>'₼'], 'BAM'=>['name'=>'Bosnia and Herzegovina convertible mark','symbol'=>'KM'], 'BBD'=>['name'=>'Barbadian dollar','symbol'=>'$'],
            'BDT'=>['name'=>'Bangladeshi taka','symbol'=>'৳'], 'BGN'=>['name'=>'Bulgarian lev','symbol'=>'лв'], 'BHD'=>['name'=>'Bahraini dinar','symbol'=>'BD'], 'BIF'=>['name'=>'Burundian franc','symbol'=>'FBu'],
            'BMD'=>['name'=>'Bermudian dollar','symbol'=>'$'], 'BND'=>['name'=>'Brunei dollar','symbol'=>'$'], 'BOB'=>['name'=>'Bolivian boliviano','symbol'=>'Bs'], 'BOV'=>['name'=>'Bolivian Mvdol','symbol'=>'BOV'],
            'BRL'=>['name'=>'Brazilian real','symbol'=>'R$'], 'BSD'=>['name'=>'Bahamian dollar','symbol'=>'$'], 'BTN'=>['name'=>'Bhutanese ngultrum','symbol'=>'Nu.'], 'BWP'=>['name'=>'Botswana pula','symbol'=>'P'],
            'BYN'=>['name'=>'Belarusian ruble','symbol'=>'Br'], 'BZD'=>['name'=>'Belize dollar','symbol'=>'$'], 'CAD'=>['name'=>'Canadian dollar','symbol'=>'$'], 'CDF'=>['name'=>'Congolese franc','symbol'=>'FC'],
            'CHE'=>['name'=>'WIR euro','symbol'=>'CHE'], 'CHF'=>['name'=>'Swiss franc','symbol'=>'CHF'], 'CHW'=>['name'=>'WIR franc','symbol'=>'CHW'], 'CLF'=>['name'=>'Unidad de Fomento','symbol'=>'CLF'],
            'CLP'=>['name'=>'Chilean peso','symbol'=>'$'], 'CNY'=>['name'=>'Chinese yuan','symbol'=>'¥'], 'COP'=>['name'=>'Colombian peso','symbol'=>'$'], 'COU'=>['name'=>'Unidad de Valor Real','symbol'=>'COU'],
            'CRC'=>['name'=>'Costa Rican colón','symbol'=>'₡'], 'CUP'=>['name'=>'Cuban peso','symbol'=>'$'], 'CVE'=>['name'=>'Cape Verdean escudo','symbol'=>'Esc'], 'CZK'=>['name'=>'Czech koruna','symbol'=>'Kč'],
            'DJF'=>['name'=>'Djiboutian franc','symbol'=>'Fdj'], 'DKK'=>['name'=>'Danish krone','symbol'=>'kr'], 'DOP'=>['name'=>'Dominican peso','symbol'=>'$'], 'DZD'=>['name'=>'Algerian dinar','symbol'=>'د.ج'],
            'EGP'=>['name'=>'Egyptian pound','symbol'=>'£'], 'ERN'=>['name'=>'Eritrean nakfa','symbol'=>'Nfk'], 'ETB'=>['name'=>'Ethiopian birr','symbol'=>'Br'], 'EUR'=>['name'=>'Euro','symbol'=>'€'],
            'FJD'=>['name'=>'Fijian dollar','symbol'=>'$'], 'FKP'=>['name'=>'Falkland Islands pound','symbol'=>'£'], 'GBP'=>['name'=>'Pound sterling','symbol'=>'£'], 'GEL'=>['name'=>'Georgian lari','symbol'=>'₾'],
            'GHS'=>['name'=>'Ghanaian cedi','symbol'=>'₵'], 'GIP'=>['name'=>'Gibraltar pound','symbol'=>'£'], 'GMD'=>['name'=>'Gambian dalasi','symbol'=>'D'], 'GNF'=>['name'=>'Guinean franc','symbol'=>'FG'],
            'GTQ'=>['name'=>'Guatemalan quetzal','symbol'=>'Q'], 'GYD'=>['name'=>'Guyanese dollar','symbol'=>'$'], 'HKD'=>['name'=>'Hong Kong dollar','symbol'=>'$'], 'HNL'=>['name'=>'Honduran lempira','symbol'=>'L'],
            'HTG'=>['name'=>'Haitian gourde','symbol'=>'G'], 'HUF'=>['name'=>'Hungarian forint','symbol'=>'Ft'], 'IDR'=>['name'=>'Indonesian rupiah','symbol'=>'Rp'], 'ILS'=>['name'=>'Israeli new shekel','symbol'=>'₪'],
            'INR'=>['name'=>'Indian rupee','symbol'=>'₹'], 'IQD'=>['name'=>'Iraqi dinar','symbol'=>'ع.د'], 'IRR'=>['name'=>'Iranian rial','symbol'=>'﷼'], 'ISK'=>['name'=>'Icelandic króna','symbol'=>'kr'],
            'JMD'=>['name'=>'Jamaican dollar','symbol'=>'$'], 'JOD'=>['name'=>'Jordanian dinar','symbol'=>'JD'], 'JPY'=>['name'=>'Japanese yen','symbol'=>'¥'], 'KES'=>['name'=>'Kenyan shilling','symbol'=>'KSh'],
            'KGS'=>['name'=>'Kyrgyzstani som','symbol'=>'с'], 'KHR'=>['name'=>'Cambodian riel','symbol'=>'៛'], 'KMF'=>['name'=>'Comorian franc','symbol'=>'CF'], 'KPW'=>['name'=>'North Korean won','symbol'=>'₩'],
            'KRW'=>['name'=>'South Korean won','symbol'=>'₩'], 'KWD'=>['name'=>'Kuwaiti dinar','symbol'=>'KD'], 'KYD'=>['name'=>'Cayman Islands dollar','symbol'=>'$'], 'KZT'=>['name'=>'Kazakhstani tenge','symbol'=>'₸'],
            'LAK'=>['name'=>'Lao kip','symbol'=>'₭'], 'LBP'=>['name'=>'Lebanese pound','symbol'=>'ل.ل'], 'LKR'=>['name'=>'Sri Lankan rupee','symbol'=>'Rs'], 'LRD'=>['name'=>'Liberian dollar','symbol'=>'$'],
            'LSL'=>['name'=>'Lesotho loti','symbol'=>'L'], 'LYD'=>['name'=>'Libyan dinar','symbol'=>'LD'], 'MAD'=>['name'=>'Moroccan dirham','symbol'=>'د.م.'], 'MDL'=>['name'=>'Moldovan leu','symbol'=>'L'],
            'MGA'=>['name'=>'Malagasy ariary','symbol'=>'Ar'], 'MKD'=>['name'=>'Macedonian denar','symbol'=>'ден'], 'MMK'=>['name'=>'Myanmar kyat','symbol'=>'K'], 'MNT'=>['name'=>'Mongolian tögrög','symbol'=>'₮'],
            'MOP'=>['name'=>'Macanese pataca','symbol'=>'MOP$'], 'MRU'=>['name'=>'Mauritanian ouguiya','symbol'=>'UM'], 'MUR'=>['name'=>'Mauritian rupee','symbol'=>'₨'], 'MVR'=>['name'=>'Maldivian rufiyaa','symbol'=>'Rf'],
            'MWK'=>['name'=>'Malawian kwacha','symbol'=>'MK'], 'MXN'=>['name'=>'Mexican peso','symbol'=>'$'], 'MXV'=>['name'=>'Mexican Unidad de Inversion','symbol'=>'MXV'], 'MYR'=>['name'=>'Malaysian ringgit','symbol'=>'RM'],
            'MZN'=>['name'=>'Mozambican metical','symbol'=>'MT'], 'NAD'=>['name'=>'Namibian dollar','symbol'=>'$'], 'NGN'=>['name'=>'Nigerian naira','symbol'=>'₦'], 'NIO'=>['name'=>'Nicaraguan córdoba','symbol'=>'C$'],
            'NOK'=>['name'=>'Norwegian krone','symbol'=>'kr'], 'NPR'=>['name'=>'Nepalese rupee','symbol'=>'₨'], 'NZD'=>['name'=>'New Zealand dollar','symbol'=>'$'], 'OMR'=>['name'=>'Omani rial','symbol'=>'ر.ع.'],
            'PAB'=>['name'=>'Panamanian balboa','symbol'=>'B/.'], 'PEN'=>['name'=>'Peruvian sol','symbol'=>'S/'], 'PGK'=>['name'=>'Papua New Guinean kina','symbol'=>'K'], 'PHP'=>['name'=>'Philippine peso','symbol'=>'₱'],
            'PKR'=>['name'=>'Pakistani rupee','symbol'=>'₨'], 'PLN'=>['name'=>'Polish złoty','symbol'=>'zł'], 'PYG'=>['name'=>'Paraguayan guaraní','symbol'=>'₲'], 'QAR'=>['name'=>'Qatari riyal','symbol'=>'ر.ق'],
            'RON'=>['name'=>'Romanian leu','symbol'=>'lei'], 'RSD'=>['name'=>'Serbian dinar','symbol'=>'дин'], 'RUB'=>['name'=>'Russian ruble','symbol'=>'₽'], 'RWF'=>['name'=>'Rwandan franc','symbol'=>'FRw'],
            'SAR'=>['name'=>'Saudi riyal','symbol'=>'ر.س'], 'SBD'=>['name'=>'Solomon Islands dollar','symbol'=>'$'], 'SCR'=>['name'=>'Seychellois rupee','symbol'=>'₨'], 'SDG'=>['name'=>'Sudanese pound','symbol'=>'ج.س'],
            'SEK'=>['name'=>'Swedish krona','symbol'=>'kr'], 'SGD'=>['name'=>'Singapore dollar','symbol'=>'$'], 'SHP'=>['name'=>'Saint Helena pound','symbol'=>'£'], 'SLE'=>['name'=>'Sierra Leonean leone','symbol'=>'Le'],
            'SOS'=>['name'=>'Somali shilling','symbol'=>'Sh'], 'SRD'=>['name'=>'Surinamese dollar','symbol'=>'$'], 'SSP'=>['name'=>'South Sudanese pound','symbol'=>'£'], 'STN'=>['name'=>'São Tomé and Príncipe dobra','symbol'=>'Db'],
            'SVC'=>['name'=>'Salvadoran colón','symbol'=>'₡'], 'SYP'=>['name'=>'Syrian pound','symbol'=>'£'], 'SZL'=>['name'=>'Swazi lilangeni','symbol'=>'L'], 'THB'=>['name'=>'Thai baht','symbol'=>'฿'],
            'TJS'=>['name'=>'Tajikistani somoni','symbol'=>'ЅМ'], 'TMT'=>['name'=>'Turkmenistani manat','symbol'=>'m'], 'TND'=>['name'=>'Tunisian dinar','symbol'=>'DT'], 'TOP'=>['name'=>'Tongan paʻanga','symbol'=>'T$'],
            'TRY'=>['name'=>'Turkish lira','symbol'=>'₺'], 'TTD'=>['name'=>'Trinidad and Tobago dollar','symbol'=>'$'], 'TWD'=>['name'=>'New Taiwan dollar','symbol'=>'$'], 'TZS'=>['name'=>'Tanzanian shilling','symbol'=>'TSh'],
            'UAH'=>['name'=>'Ukrainian hryvnia','symbol'=>'₴'], 'UGX'=>['name'=>'Ugandan shilling','symbol'=>'USh'], 'USD'=>['name'=>'United States dollar','symbol'=>'$'], 'USN'=>['name'=>'US dollar next day','symbol'=>'USN'],
            'UYI'=>['name'=>'Uruguay Peso en Unidades Indexadas','symbol'=>'UYI'], 'UYU'=>['name'=>'Uruguayan peso','symbol'=>'$'], 'UYW'=>['name'=>'Unidad Previsional','symbol'=>'UYW'], 'UZS'=>['name'=>'Uzbekistani som','symbol'=>'soʻm'],
            'VED'=>['name'=>'Venezuelan digital bolívar','symbol'=>'Bs.D'], 'VES'=>['name'=>'Venezuelan sovereign bolívar','symbol'=>'Bs.'], 'VND'=>['name'=>'Vietnamese đồng','symbol'=>'₫'], 'VUV'=>['name'=>'Vanuatu vatu','symbol'=>'VT'],
            'WST'=>['name'=>'Samoan tālā','symbol'=>'T'], 'XAF'=>['name'=>'Central African CFA franc','symbol'=>'FCFA'], 'XCD'=>['name'=>'East Caribbean dollar','symbol'=>'$'], 'XOF'=>['name'=>'West African CFA franc','symbol'=>'CFA'],
            'XPF'=>['name'=>'CFP franc','symbol'=>'₣'], 'YER'=>['name'=>'Yemeni rial','symbol'=>'﷼'], 'ZAR'=>['name'=>'South African rand','symbol'=>'R'], 'ZMW'=>['name'=>'Zambian kwacha','symbol'=>'ZK'], 'ZWL'=>['name'=>'Zimbabwean dollar','symbol'=>'$'],
        ];
    }

    public static function normalize(string $currency): string
    {
        $currency = strtoupper(sanitize_text_field($currency));
        return array_key_exists($currency, self::currencies()) ? $currency : 'EUR';
    }

    public static function symbol(string $currency): string
    {
        $currency = self::normalize($currency);
        return self::currencies()[$currency]['symbol'] ?? $currency;
    }

    public static function format($amount, string $currency = 'EUR', string $position = 'right'): string
    {
        $currency = self::normalize($currency);
        $position = self::normalize_position($position);
        $symbol = self::symbol($currency);
        $settings = get_option('sltr_settings', []);
        $settings = is_array($settings) ? $settings : [];
        $decimal_separator = self::normalize_separator((string) ($settings['payment_decimal_separator'] ?? '.'), '.');
        $thousands_separator = self::normalize_separator((string) ($settings['payment_thousands_separator'] ?? ' '), ' ');
        $decimals = self::decimals($currency);
        $formatted = number_format((float) $amount, $decimals, $decimal_separator, $thousands_separator);

        if ($position === 'left') { return $symbol . $formatted; }
        if ($position === 'left_space') { return $symbol . ' ' . $formatted; }
        if ($position === 'right_space') { return $formatted . ' ' . $symbol; }
        return $formatted . $symbol;
    }

    public static function format_for_locale($amount, string $currency = 'EUR', string $position = 'right', string $locale = ''): string
    {
        $currency = self::normalize($currency);
        $position = self::normalize_position($position);
        $symbol = self::symbol($currency);
        [$decimal_separator, $thousands_separator] = self::locale_separators($locale);
        $formatted = number_format((float) $amount, self::decimals($currency), $decimal_separator, $thousands_separator);

        if ($position === 'left') { return $symbol . $formatted; }
        if ($position === 'left_space') { return $symbol . ' ' . $formatted; }
        if ($position === 'right_space') { return $formatted . ' ' . $symbol; }
        return $formatted . $symbol;
    }

    /** @return array{0:string,1:string} */
    private static function locale_separators(string $locale): array
    {
        $locale = str_replace('-', '_', strtolower(trim($locale)));
        $language = substr($locale, 0, 2);

        // Localized invoice output follows the customer's booking locale.
        if (in_array($language, ['bg','cs','da','de','el','es','et','fi','fr','hr','hu','is','it','lt','lv','mt','nl','no','pl','pt','ro','ru','sk','sl','sv'], true)) {
            $thousands = in_array($language, ['de','es','it','nl'], true) ? '.' : ' ';
            return [',', $thousands];
        }

        return ['.', ','];
    }

    public static function unit_suffix(string $price_unit): string
    {
        $price_unit = sanitize_key($price_unit);
        $labels = [
            'per_day' => __(' / day', 'slotera-booking'),
            'per_night' => __(' / night', 'slotera-booking'),
            'per_hour' => __(' / hour', 'slotera-booking'),
        ];

        return $labels[$price_unit] ?? '';
    }

    public static function format_with_unit($amount, string $currency = 'EUR', string $position = 'right', string $price_unit = ''): string
    {
        return self::format($amount, $currency, $position) . self::unit_suffix($price_unit);
    }

    public static function normalize_position(string $position): string
    {
        $position = strtolower(sanitize_key($position));
        return in_array($position, ['left', 'right', 'left_space', 'right_space'], true) ? $position : 'right_space';
    }

    public static function decimals(string $currency): int
    {
        $currency = self::normalize($currency);
        if (in_array($currency, ['BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'], true)) { return 0; }
        if (in_array($currency, ['BHD','IQD','JOD','KWD','LYD','OMR','TND'], true)) { return 3; }
        return 2;
    }

    public static function normalize_separator(string $separator, string $fallback): string
    {
        return in_array($separator, ['.', ',', ' ', ''], true) ? $separator : $fallback;
    }
}
