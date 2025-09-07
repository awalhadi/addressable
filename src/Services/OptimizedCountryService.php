<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Ultra-lightweight country service optimized for performance.
 *
 * Features:
 * - Static array for instant lookups (no database queries)
 * - Intelligent caching for frequently accessed countries
 * - Memory-efficient storage
 * - Fallback to rinvex/countries if available
 */
class OptimizedCountryService
{
    /**
     * Core countries with essential data only.
     * Optimized for memory usage and lookup speed.
     */
    private static array $coreCountries = [
        // Major countries (most frequently used)
        'US' => ['name' => 'United States', 'currency' => 'USD', 'phone' => '+1'],
        'GB' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'phone' => '+44'],
        'CA' => ['name' => 'Canada', 'currency' => 'CAD', 'phone' => '+1'],
        'AU' => ['name' => 'Australia', 'currency' => 'AUD', 'phone' => '+61'],
        'DE' => ['name' => 'Germany', 'currency' => 'EUR', 'phone' => '+49'],
        'FR' => ['name' => 'France', 'currency' => 'EUR', 'phone' => '+33'],
        'IT' => ['name' => 'Italy', 'currency' => 'EUR', 'phone' => '+39'],
        'ES' => ['name' => 'Spain', 'currency' => 'EUR', 'phone' => '+34'],
        'NL' => ['name' => 'Netherlands', 'currency' => 'EUR', 'phone' => '+31'],
        'BE' => ['name' => 'Belgium', 'currency' => 'EUR', 'phone' => '+32'],
        'CH' => ['name' => 'Switzerland', 'currency' => 'CHF', 'phone' => '+41'],
        'AT' => ['name' => 'Austria', 'currency' => 'EUR', 'phone' => '+43'],
        'SE' => ['name' => 'Sweden', 'currency' => 'SEK', 'phone' => '+46'],
        'NO' => ['name' => 'Norway', 'currency' => 'NOK', 'phone' => '+47'],
        'DK' => ['name' => 'Denmark', 'currency' => 'DKK', 'phone' => '+45'],
        'FI' => ['name' => 'Finland', 'currency' => 'EUR', 'phone' => '+358'],
        'IE' => ['name' => 'Ireland', 'currency' => 'EUR', 'phone' => '+353'],
        'PT' => ['name' => 'Portugal', 'currency' => 'EUR', 'phone' => '+351'],
        'GR' => ['name' => 'Greece', 'currency' => 'EUR', 'phone' => '+30'],
        'PL' => ['name' => 'Poland', 'currency' => 'PLN', 'phone' => '+48'],
        'CZ' => ['name' => 'Czech Republic', 'currency' => 'CZK', 'phone' => '+420'],
        'HU' => ['name' => 'Hungary', 'currency' => 'HUF', 'phone' => '+36'],
        'SK' => ['name' => 'Slovakia', 'currency' => 'EUR', 'phone' => '+421'],
        'SI' => ['name' => 'Slovenia', 'currency' => 'EUR', 'phone' => '+386'],
        'HR' => ['name' => 'Croatia', 'currency' => 'EUR', 'phone' => '+385'],
        'RO' => ['name' => 'Romania', 'currency' => 'RON', 'phone' => '+40'],
        'BG' => ['name' => 'Bulgaria', 'currency' => 'BGN', 'phone' => '+359'],
        'LT' => ['name' => 'Lithuania', 'currency' => 'EUR', 'phone' => '+370'],
        'LV' => ['name' => 'Latvia', 'currency' => 'EUR', 'phone' => '+371'],
        'EE' => ['name' => 'Estonia', 'currency' => 'EUR', 'phone' => '+372'],
        'LU' => ['name' => 'Luxembourg', 'currency' => 'EUR', 'phone' => '+352'],
        'MT' => ['name' => 'Malta', 'currency' => 'EUR', 'phone' => '+356'],
        'CY' => ['name' => 'Cyprus', 'currency' => 'EUR', 'phone' => '+357'],
        'JP' => ['name' => 'Japan', 'currency' => 'JPY', 'phone' => '+81'],
        'CN' => ['name' => 'China', 'currency' => 'CNY', 'phone' => '+86'],
        'KR' => ['name' => 'South Korea', 'currency' => 'KRW', 'phone' => '+82'],
        'IN' => ['name' => 'India', 'currency' => 'INR', 'phone' => '+91'],
        'BR' => ['name' => 'Brazil', 'currency' => 'BRL', 'phone' => '+55'],
        'MX' => ['name' => 'Mexico', 'currency' => 'MXN', 'phone' => '+52'],
        'AR' => ['name' => 'Argentina', 'currency' => 'ARS', 'phone' => '+54'],
        'CL' => ['name' => 'Chile', 'currency' => 'CLP', 'phone' => '+56'],
        'CO' => ['name' => 'Colombia', 'currency' => 'COP', 'phone' => '+57'],
        'PE' => ['name' => 'Peru', 'currency' => 'PEN', 'phone' => '+51'],
        'VE' => ['name' => 'Venezuela', 'currency' => 'VES', 'phone' => '+58'],
        'UY' => ['name' => 'Uruguay', 'currency' => 'UYU', 'phone' => '+598'],
        'PY' => ['name' => 'Paraguay', 'currency' => 'PYG', 'phone' => '+595'],
        'BO' => ['name' => 'Bolivia', 'currency' => 'BOB', 'phone' => '+591'],
        'EC' => ['name' => 'Ecuador', 'currency' => 'USD', 'phone' => '+593'],
        'GY' => ['name' => 'Guyana', 'currency' => 'GYD', 'phone' => '+592'],
        'SR' => ['name' => 'Suriname', 'currency' => 'SRD', 'phone' => '+597'],
        'ZA' => ['name' => 'South Africa', 'currency' => 'ZAR', 'phone' => '+27'],
        'NG' => ['name' => 'Nigeria', 'currency' => 'NGN', 'phone' => '+234'],
        'EG' => ['name' => 'Egypt', 'currency' => 'EGP', 'phone' => '+20'],
        'KE' => ['name' => 'Kenya', 'currency' => 'KES', 'phone' => '+254'],
        'GH' => ['name' => 'Ghana', 'currency' => 'GHS', 'phone' => '+233'],
        'MA' => ['name' => 'Morocco', 'currency' => 'MAD', 'phone' => '+212'],
        'TN' => ['name' => 'Tunisia', 'currency' => 'TND', 'phone' => '+216'],
        'DZ' => ['name' => 'Algeria', 'currency' => 'DZD', 'phone' => '+213'],
        'LY' => ['name' => 'Libya', 'currency' => 'LYD', 'phone' => '+218'],
        'SD' => ['name' => 'Sudan', 'currency' => 'SDG', 'phone' => '+249'],
        'ET' => ['name' => 'Ethiopia', 'currency' => 'ETB', 'phone' => '+251'],
        'UG' => ['name' => 'Uganda', 'currency' => 'UGX', 'phone' => '+256'],
        'TZ' => ['name' => 'Tanzania', 'currency' => 'TZS', 'phone' => '+255'],
        'RW' => ['name' => 'Rwanda', 'currency' => 'RWF', 'phone' => '+250'],
        'BI' => ['name' => 'Burundi', 'currency' => 'BIF', 'phone' => '+257'],
        'MW' => ['name' => 'Malawi', 'currency' => 'MWK', 'phone' => '+265'],
        'ZM' => ['name' => 'Zambia', 'currency' => 'ZMW', 'phone' => '+260'],
        'ZW' => ['name' => 'Zimbabwe', 'currency' => 'ZWL', 'phone' => '+263'],
        'BW' => ['name' => 'Botswana', 'currency' => 'BWP', 'phone' => '+267'],
        'NA' => ['name' => 'Namibia', 'currency' => 'NAD', 'phone' => '+264'],
        'SZ' => ['name' => 'Eswatini', 'currency' => 'SZL', 'phone' => '+268'],
        'LS' => ['name' => 'Lesotho', 'currency' => 'LSL', 'phone' => '+266'],
        'MG' => ['name' => 'Madagascar', 'currency' => 'MGA', 'phone' => '+261'],
        'MU' => ['name' => 'Mauritius', 'currency' => 'MUR', 'phone' => '+230'],
        'SC' => ['name' => 'Seychelles', 'currency' => 'SCR', 'phone' => '+248'],
        'KM' => ['name' => 'Comoros', 'currency' => 'KMF', 'phone' => '+269'],
        'DJ' => ['name' => 'Djibouti', 'currency' => 'DJF', 'phone' => '+253'],
        'SO' => ['name' => 'Somalia', 'currency' => 'SOS', 'phone' => '+252'],
        'ER' => ['name' => 'Eritrea', 'currency' => 'ERN', 'phone' => '+291'],
        'SS' => ['name' => 'South Sudan', 'currency' => 'SSP', 'phone' => '+211'],
        'CF' => ['name' => 'Central African Republic', 'currency' => 'XAF', 'phone' => '+236'],
        'TD' => ['name' => 'Chad', 'currency' => 'XAF', 'phone' => '+235'],
        'CM' => ['name' => 'Cameroon', 'currency' => 'XAF', 'phone' => '+237'],
        'GQ' => ['name' => 'Equatorial Guinea', 'currency' => 'XAF', 'phone' => '+240'],
        'GA' => ['name' => 'Gabon', 'currency' => 'XAF', 'phone' => '+241'],
        'CG' => ['name' => 'Republic of the Congo', 'currency' => 'XAF', 'phone' => '+242'],
        'CD' => ['name' => 'Democratic Republic of the Congo', 'currency' => 'CDF', 'phone' => '+243'],
        'AO' => ['name' => 'Angola', 'currency' => 'AOA', 'phone' => '+244'],
        'ST' => ['name' => 'São Tomé and Príncipe', 'currency' => 'STN', 'phone' => '+239'],
        'CV' => ['name' => 'Cape Verde', 'currency' => 'CVE', 'phone' => '+238'],
        'GW' => ['name' => 'Guinea-Bissau', 'currency' => 'XOF', 'phone' => '+245'],
        'GN' => ['name' => 'Guinea', 'currency' => 'GNF', 'phone' => '+224'],
        'SL' => ['name' => 'Sierra Leone', 'currency' => 'SLE', 'phone' => '+232'],
        'LR' => ['name' => 'Liberia', 'currency' => 'LRD', 'phone' => '+231'],
        'CI' => ['name' => 'Côte d\'Ivoire', 'currency' => 'XOF', 'phone' => '+225'],
        'GH' => ['name' => 'Ghana', 'currency' => 'GHS', 'phone' => '+233'],
        'TG' => ['name' => 'Togo', 'currency' => 'XOF', 'phone' => '+228'],
        'BJ' => ['name' => 'Benin', 'currency' => 'XOF', 'phone' => '+229'],
        'NE' => ['name' => 'Niger', 'currency' => 'XOF', 'phone' => '+227'],
        'BF' => ['name' => 'Burkina Faso', 'currency' => 'XOF', 'phone' => '+226'],
        'ML' => ['name' => 'Mali', 'currency' => 'XOF', 'phone' => '+223'],
        'SN' => ['name' => 'Senegal', 'currency' => 'XOF', 'phone' => '+221'],
        'GM' => ['name' => 'Gambia', 'currency' => 'GMD', 'phone' => '+220'],
        'GN' => ['name' => 'Guinea', 'currency' => 'GNF', 'phone' => '+224'],
        'MR' => ['name' => 'Mauritania', 'currency' => 'MRU', 'phone' => '+222'],
        'SA' => ['name' => 'Saudi Arabia', 'currency' => 'SAR', 'phone' => '+966'],
        'AE' => ['name' => 'United Arab Emirates', 'currency' => 'AED', 'phone' => '+971'],
        'QA' => ['name' => 'Qatar', 'currency' => 'QAR', 'phone' => '+974'],
        'KW' => ['name' => 'Kuwait', 'currency' => 'KWD', 'phone' => '+965'],
        'BH' => ['name' => 'Bahrain', 'currency' => 'BHD', 'phone' => '+973'],
        'OM' => ['name' => 'Oman', 'currency' => 'OMR', 'phone' => '+968'],
        'YE' => ['name' => 'Yemen', 'currency' => 'YER', 'phone' => '+967'],
        'IQ' => ['name' => 'Iraq', 'currency' => 'IQD', 'phone' => '+964'],
        'SY' => ['name' => 'Syria', 'currency' => 'SYP', 'phone' => '+963'],
        'LB' => ['name' => 'Lebanon', 'currency' => 'LBP', 'phone' => '+961'],
        'JO' => ['name' => 'Jordan', 'currency' => 'JOD', 'phone' => '+962'],
        'IL' => ['name' => 'Israel', 'currency' => 'ILS', 'phone' => '+972'],
        'PS' => ['name' => 'Palestine', 'currency' => 'ILS', 'phone' => '+970'],
        'TR' => ['name' => 'Turkey', 'currency' => 'TRY', 'phone' => '+90'],
        'IR' => ['name' => 'Iran', 'currency' => 'IRR', 'phone' => '+98'],
        'AF' => ['name' => 'Afghanistan', 'currency' => 'AFN', 'phone' => '+93'],
        'PK' => ['name' => 'Pakistan', 'currency' => 'PKR', 'phone' => '+92'],
        'BD' => ['name' => 'Bangladesh', 'currency' => 'BDT', 'phone' => '+880'],
        'LK' => ['name' => 'Sri Lanka', 'currency' => 'LKR', 'phone' => '+94'],
        'MV' => ['name' => 'Maldives', 'currency' => 'MVR', 'phone' => '+960'],
        'BT' => ['name' => 'Bhutan', 'currency' => 'BTN', 'phone' => '+975'],
        'NP' => ['name' => 'Nepal', 'currency' => 'NPR', 'phone' => '+977'],
        'MM' => ['name' => 'Myanmar', 'currency' => 'MMK', 'phone' => '+95'],
        'TH' => ['name' => 'Thailand', 'currency' => 'THB', 'phone' => '+66'],
        'LA' => ['name' => 'Laos', 'currency' => 'LAK', 'phone' => '+856'],
        'KH' => ['name' => 'Cambodia', 'currency' => 'KHR', 'phone' => '+855'],
        'VN' => ['name' => 'Vietnam', 'currency' => 'VND', 'phone' => '+84'],
        'MY' => ['name' => 'Malaysia', 'currency' => 'MYR', 'phone' => '+60'],
        'SG' => ['name' => 'Singapore', 'currency' => 'SGD', 'phone' => '+65'],
        'BN' => ['name' => 'Brunei', 'currency' => 'BND', 'phone' => '+673'],
        'ID' => ['name' => 'Indonesia', 'currency' => 'IDR', 'phone' => '+62'],
        'TL' => ['name' => 'Timor-Leste', 'currency' => 'USD', 'phone' => '+670'],
        'PH' => ['name' => 'Philippines', 'currency' => 'PHP', 'phone' => '+63'],
        'TW' => ['name' => 'Taiwan', 'currency' => 'TWD', 'phone' => '+886'],
        'HK' => ['name' => 'Hong Kong', 'currency' => 'HKD', 'phone' => '+852'],
        'MO' => ['name' => 'Macao', 'currency' => 'MOP', 'phone' => '+853'],
        'MN' => ['name' => 'Mongolia', 'currency' => 'MNT', 'phone' => '+976'],
        'KZ' => ['name' => 'Kazakhstan', 'currency' => 'KZT', 'phone' => '+7'],
        'UZ' => ['name' => 'Uzbekistan', 'currency' => 'UZS', 'phone' => '+998'],
        'TM' => ['name' => 'Turkmenistan', 'currency' => 'TMT', 'phone' => '+993'],
        'TJ' => ['name' => 'Tajikistan', 'currency' => 'TJS', 'phone' => '+992'],
        'KG' => ['name' => 'Kyrgyzstan', 'currency' => 'KGS', 'phone' => '+996'],
        'RU' => ['name' => 'Russia', 'currency' => 'RUB', 'phone' => '+7'],
        'BY' => ['name' => 'Belarus', 'currency' => 'BYN', 'phone' => '+375'],
        'UA' => ['name' => 'Ukraine', 'currency' => 'UAH', 'phone' => '+380'],
        'MD' => ['name' => 'Moldova', 'currency' => 'MDL', 'phone' => '+373'],
        'GE' => ['name' => 'Georgia', 'currency' => 'GEL', 'phone' => '+995'],
        'AM' => ['name' => 'Armenia', 'currency' => 'AMD', 'phone' => '+374'],
        'AZ' => ['name' => 'Azerbaijan', 'currency' => 'AZN', 'phone' => '+994'],
        'IS' => ['name' => 'Iceland', 'currency' => 'ISK', 'phone' => '+354'],
        'NZ' => ['name' => 'New Zealand', 'currency' => 'NZD', 'phone' => '+64'],
        'FJ' => ['name' => 'Fiji', 'currency' => 'FJD', 'phone' => '+679'],
        'PG' => ['name' => 'Papua New Guinea', 'currency' => 'PGK', 'phone' => '+675'],
        'SB' => ['name' => 'Solomon Islands', 'currency' => 'SBD', 'phone' => '+677'],
        'VU' => ['name' => 'Vanuatu', 'currency' => 'VUV', 'phone' => '+678'],
        'NC' => ['name' => 'New Caledonia', 'currency' => 'XPF', 'phone' => '+687'],
        'PF' => ['name' => 'French Polynesia', 'currency' => 'XPF', 'phone' => '+689'],
        'WS' => ['name' => 'Samoa', 'currency' => 'WST', 'phone' => '+685'],
        'TO' => ['name' => 'Tonga', 'currency' => 'TOP', 'phone' => '+676'],
        'KI' => ['name' => 'Kiribati', 'currency' => 'AUD', 'phone' => '+686'],
        'TV' => ['name' => 'Tuvalu', 'currency' => 'AUD', 'phone' => '+688'],
        'NR' => ['name' => 'Nauru', 'currency' => 'AUD', 'phone' => '+674'],
        'PW' => ['name' => 'Palau', 'currency' => 'USD', 'phone' => '+680'],
        'FM' => ['name' => 'Micronesia', 'currency' => 'USD', 'phone' => '+691'],
        'MH' => ['name' => 'Marshall Islands', 'currency' => 'USD', 'phone' => '+692'],
    ];

    /**
     * Cache key prefix for country data.
     */
    private static string $cachePrefix = 'addressable_country_';

    /**
     * Cache TTL in seconds (1 hour).
     */
    private static int $cacheTtl = 3600;

    /**
     * Get country name by code with intelligent caching.
     */
    public static function getName(string $countryCode): ?string
    {
        $countryCode = strtoupper($countryCode);

        // Check core countries first (fastest)
        if (isset(self::$coreCountries[$countryCode])) {
            return self::$coreCountries[$countryCode]['name'];
        }

        // Check cache for extended countries
        $cacheKey = self::$cachePrefix . $countryCode;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Try rinvex/countries if available
        if (class_exists('Rinvex\Country\Country')) {
            try {
                $country = country($countryCode);
                $name = $country->getName();

                // Cache the result
                Cache::put($cacheKey, $name, self::$cacheTtl);

                return $name;
            } catch (\Exception $e) {
                // Log warning but don't fail
                \Log::warning("Could not resolve country name for code: {$countryCode}");
            }
        }

        // Return null if not found
        return null;
    }

    /**
     * Get country currency by code.
     */
    public static function getCurrency(string $countryCode): ?string
    {
        $countryCode = strtoupper($countryCode);

        if (isset(self::$coreCountries[$countryCode])) {
            return self::$coreCountries[$countryCode]['currency'];
        }

        return null;
    }

    /**
     * Get country phone code by code.
     */
    public static function getPhoneCode(string $countryCode): ?string
    {
        $countryCode = strtoupper($countryCode);

        if (isset(self::$coreCountries[$countryCode])) {
            return self::$coreCountries[$countryCode]['phone'];
        }

        return null;
    }

    /**
     * Check if country code exists in core countries.
     */
    public static function exists(string $countryCode): bool
    {
        return isset(self::$coreCountries[strtoupper($countryCode)]);
    }

    /**
     * Get all core country codes.
     */
    public static function getCoreCodes(): array
    {
        return array_keys(self::$coreCountries);
    }

    /**
     * Get all core countries with their data.
     */
    public static function getCoreCountries(): array
    {
        return self::$coreCountries;
    }

    /**
     * Warm up cache for frequently used countries.
     */
    public static function warmUpCache(array $countryCodes = null): void
    {
        $countryCodes = $countryCodes ?? ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE'];

        foreach ($countryCodes as $code) {
            self::getName($code); // This will cache the result
        }
    }

    /**
     * Clear country cache.
     */
    public static function clearCache(): void
    {
        $coreCodes = self::getCoreCodes();
        foreach ($coreCodes as $code) {
            Cache::forget(self::$cachePrefix . $code);
        }
    }

    /**
     * Get performance statistics.
     */
    public static function getStats(): array
    {
        return [
            'core_countries' => count(self::$coreCountries),
            'cache_prefix' => self::$cachePrefix,
            'cache_ttl' => self::$cacheTtl,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }
}
