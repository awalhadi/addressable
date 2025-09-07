<?php

if (!function_exists('country')) {
    /**
     * Get country information by code.
     * 
     * @param string $code Country code (ISO 3166-1 alpha-2)
     * @return \Awalhadi\Addressable\Support\Country
     */
    function country(string $code): \Awalhadi\Addressable\Support\Country
    {
        return new \Awalhadi\Addressable\Support\Country($code);
    }
}

if (!function_exists('countries')) {
    /**
     * Get the country service instance.
     * 
     * @return \Awalhadi\Addressable\Services\CountryService
     */
    function countries(): \Awalhadi\Addressable\Services\CountryService
    {
        return app(\Awalhadi\Addressable\Services\CountryService::class);
    }
}