<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Traits;

trait HasAddressValidation
{
    /**
     * Postal code validation patterns for major countries.
     */
    protected array $postalCodePatterns = [
        'US' => '/^\d{5}([-\s]\d{4})?$/', // 12345, 12345-6789, or 12345 6789
        'CA' => '/^[A-Za-z]\d[A-Za-z][\s]?\d[A-Za-z]\d$/', // A1A 1A1 or A1A1A1
        'GB' => '/^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/i', // A1 1AA, A11 1AA, AA1 1AA, AA11 1AA
        'DE' => '/^\d{5}$/', // 12345
        'FR' => '/^\d{5}$/', // 12345
        'IT' => '/^\d{5}$/', // 12345
        'ES' => '/^\d{5}$/', // 12345
        'NL' => '/^\d{4} ?[A-Z]{2}$/i', // 1234 AB
        'BE' => '/^\d{4}$/', // 1234
        'CH' => '/^\d{4}$/', // 1234
        'AT' => '/^\d{4}$/', // 1234
        'AU' => '/^\d{4}$/', // 1234
        'JP' => '/^\d{3}-\d{4}$/', // 123-4567
        'CN' => '/^\d{6}$/', // 123456
        'IN' => '/^\d{6}$/', // 123456
        'BR' => '/^\d{5}-\d{3}$/', // 12345-678
        'MX' => '/^\d{5}$/', // 12345
        'AR' => '/^\d{4}$/', // 1234
        'CL' => '/^\d{7}$/', // 1234567
        'CO' => '/^\d{6}$/', // 123456
        'PE' => '/^\d{5}$/', // 12345
        'VE' => '/^\d{4}$/', // 1234
        'ZA' => '/^\d{4}$/', // 1234
        'EG' => '/^\d{5}$/', // 12345
        'NG' => '/^\d{6}$/', // 123456
        'KE' => '/^\d{5}$/', // 12345
        'GH' => '/^\d{5}$/', // 12345
        'MA' => '/^\d{5}$/', // 12345
        'TN' => '/^\d{4}$/', // 1234
        'DZ' => '/^\d{5}$/', // 12345
        'SA' => '/^\d{5}$/', // 12345
        'AE' => '/^\d{3}$/', // 123
        'QA' => '/^\d{5}$/', // 12345
        'KW' => '/^\d{5}$/', // 12345
        'BH' => '/^\d{3,4}$/', // 123 or 1234
        'OM' => '/^\d{3}$/', // 123
        'JO' => '/^\d{5}$/', // 12345
        'LB' => '/^\d{4,5}$/', // 1234 or 12345
        'SY' => '/^\d{5}$/', // 12345
        'IQ' => '/^\d{5}$/', // 12345
        'IR' => '/^\d{5}-\d{5}$/', // 12345-12345
        'TR' => '/^\d{5}$/', // 12345
        'IL' => '/^\d{5,7}$/', // 12345 or 1234567
        'PK' => '/^\d{5}$/', // 12345
        'AF' => '/^\d{4}$/', // 1234
        'BD' => '/^\d{4}$/', // 1234
        'LK' => '/^\d{5}$/', // 12345
        'NP' => '/^\d{5}$/', // 12345
        'MM' => '/^\d{5}$/', // 12345
        'TH' => '/^\d{5}$/', // 12345
        'VN' => '/^\d{6}$/', // 123456
        'PH' => '/^\d{4}$/', // 1234
        'MY' => '/^\d{5}$/', // 12345
        'SG' => '/^\d{6}$/', // 123456
        'ID' => '/^\d{5}$/', // 12345
        'KR' => '/^\d{5}$/', // 12345
        'TW' => '/^\d{3,5}$/', // 123 or 12345
        'HK' => '/^\d{6}$/', // 123456
        'MO' => '/^\d{4}$/', // 1234
    ];

    /**
     * Phone number validation patterns for major countries.
     */
    protected array $phonePatterns = [
        'US' => '/^\+?1?[-.\s]?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})$/',
        'CA' => '/^\+?1?[-.\s]?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})$/',
        'GB' => '/^\+?44\s?([0-9]{2,5})\s?([0-9]{3,4})\s?([0-9]{3,4})$/',
        'DE' => '/^\+?49\s?([0-9]{3,4})\s?([0-9]{3,4})\s?([0-9]{2,4})$/',
        'FR' => '/^\+?33\s?([0-9]{1})\s?([0-9]{2})\s?([0-9]{2})\s?([0-9]{2})\s?([0-9]{2})$/',
        'IT' => '/^\+?39\s?([0-9]{3})\s?([0-9]{3})\s?([0-9]{4})$/',
        'ES' => '/^\+?34\s?([0-9]{3})\s?([0-9]{3})\s?([0-9]{3})$/',
        'AU' => '/^\+?61\s?([0-9]{2})\s?([0-9]{4})\s?([0-9]{4})$/',
        'JP' => '/^\+?81\s?([0-9]{1,2})\s?([0-9]{4})\s?([0-9]{4})$/',
        'CN' => '/^\+?86\s?([0-9]{3})\s?([0-9]{4})\s?([0-9]{4})$/',
        'IN' => '/^\+?91\s?([0-9]{5})\s?([0-9]{5})$/',
        'BR' => '/^\+?55\s?([0-9]{2})\s?([0-9]{4,5})\s?([0-9]{4})$/',
        'MX' => '/^\+?52\s?([0-9]{2})\s?([0-9]{4})\s?([0-9]{4})$/',
    ];

    /**
     * Validate postal code for the given country.
     */
    public function validatePostalCode(?string $postalCode = null, ?string $countryCode = null): bool
    {
        $postalCode = $postalCode ?? $this->postal_code;
        $countryCode = $countryCode ?? $this->country_code;

        if (is_null($postalCode) || is_null($countryCode)) {
            return true; // Allow null postal codes
        }

        if (empty($postalCode) || empty($countryCode) || trim($postalCode) === '' || trim($countryCode) === '') {
            return false; // Empty strings and whitespace-only strings are invalid
        }

        $countryCode = strtoupper($countryCode);

        if (! isset($this->postalCodePatterns[$countryCode])) {
            return true; // No validation pattern for this country
        }

        return (bool) preg_match($this->postalCodePatterns[$countryCode], $postalCode);
    }

    /**
     * Validate phone number for the given country.
     */
    public function validatePhoneNumber(?string $phone = null, ?string $countryCode = null): bool
    {
        $phone = $phone ?? $this->phone;
        $countryCode = $countryCode ?? $this->country_code;

        if (is_null($phone) || is_null($countryCode)) {
            return true; // Allow null phone numbers
        }

        if (empty($phone) || empty($countryCode) || trim($phone) === '' || trim($countryCode) === '') {
            return false; // Empty strings and whitespace-only strings are invalid
        }

        $countryCode = strtoupper($countryCode);

        if (! isset($this->phonePatterns[$countryCode])) {
            return true; // No validation pattern for this country
        }

        return (bool) preg_match($this->phonePatterns[$countryCode], $phone);
    }

    /**
     * Validate email address.
     */
    public function validateEmail(?string $email = null): bool
    {
        $email = $email ?? $this->email;

        if (is_null($email)) {
            return true; // Allow null emails
        }

        if (empty($email) || trim($email) === '') {
            return false; // Empty strings and whitespace-only strings are invalid
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate country code.
     */
    public function validateCountryCode(?string $countryCode = null): bool
    {
        $countryCode = $countryCode ?? $this->country_code;

        if (empty($countryCode)) {
            return false;
        }

        try {
            return countries()->exists($countryCode);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate the complete address.
     */
    public function validateAddress(): array
    {
        $errors = [];

        // Required fields validation
        if (empty($this->given_name)) {
            $errors['given_name'] = 'Given name is required';
        }

        if (empty($this->street)) {
            $errors['street'] = 'Street address is required';
        }

        if (empty($this->city)) {
            $errors['city'] = 'City is required';
        }

        // Country code is optional, only validate if provided

        // Country code validation
        if (! empty($this->country_code) && ! $this->validateCountryCode()) {
            $errors['country_code'] = 'Invalid country code';
        }

        // Postal code validation
        if (config('addressable.validation.postal_code_validation', true) && ! $this->validatePostalCode()) {
            $errors['postal_code'] = 'Invalid postal code format for the specified country';
        }

        // Phone validation
        if (config('addressable.validation.phone_validation', true) && ! $this->validatePhoneNumber()) {
            $errors['phone'] = 'Invalid phone number format for the specified country';
        }

        // Email validation
        if (config('addressable.validation.email_validation', true) && ! $this->validateEmail()) {
            $errors['email'] = 'Invalid email address format';
        }

        // Country code validation for empty strings
        if (empty($this->country_code) && $this->country_code !== null) {
            $errors['country_code'] = 'Country code cannot be empty';
        }

        return $errors;
    }

    /**
     * Check if the address is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validateAddress());
    }

    /**
     * Get validation errors for the address.
     */
    public function getValidationErrors(): array
    {
        return $this->validateAddress();
    }

    /**
     * Format postal code according to country standards.
     */
    public function formatPostalCode(?string $postalCode = null, ?string $countryCode = null): ?string
    {
        $postalCode = $postalCode ?? $this->postal_code;
        $countryCode = $countryCode ?? $this->country_code;

        if (empty($postalCode) || empty($countryCode)) {
            return $postalCode;
        }

        $countryCode = strtoupper($countryCode);
        $formatted = trim($postalCode);

        // Apply country-specific formatting
        switch ($countryCode) {
            case 'US':
                // Format as 12345-6789
                $formatted = preg_replace('/^(\d{5})(\d{4})$/', '$1-$2', $formatted);

                break;
            case 'CA':
                // Format as A1A 1A1
                $formatted = strtoupper($formatted);
                $formatted = preg_replace('/^([A-Z]\d[A-Z])(\d[A-Z]\d)$/', '$1 $2', $formatted);

                break;
            case 'GB':
                // Format as A1 1AA
                $formatted = strtoupper($formatted);

                break;
            case 'NL':
                // Format as 1234 AB
                $formatted = strtoupper($formatted);
                $formatted = preg_replace('/^(\d{4})([A-Z]{2})$/i', '$1 $2', $formatted);

                break;
            case 'BR':
                // Format as 12345-678
                $formatted = preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $formatted);

                break;
            case 'JP':
                // Format as 123-4567
                $formatted = preg_replace('/^(\d{3})(\d{4})$/', '$1-$2', $formatted);

                break;
            case 'IR':
                // Format as 12345-12345
                $formatted = preg_replace('/^(\d{5})(\d{5})$/', '$1-$2', $formatted);

                break;
        }

        return $formatted;
    }

    /**
     * Format phone number according to country standards.
     */
    public function formatPhoneNumber(?string $phone = null, ?string $countryCode = null): ?string
    {
        $phone = $phone ?? $this->phone;
        $countryCode = $countryCode ?? $this->country_code;

        if (empty($phone) || empty($countryCode)) {
            return $phone;
        }

        $countryCode = strtoupper($countryCode);
        $formatted = preg_replace('/[^\d+]/', '', $phone);

        // Apply country-specific formatting
        switch ($countryCode) {
            case 'US':
            case 'CA':
                // Format as (123) 456-7890
                if (preg_match('/^1?(\d{3})(\d{3})(\d{4})$/', $formatted, $matches)) {
                    $formatted = "({$matches[1]}) {$matches[2]}-{$matches[3]}";
                }

                break;
            case 'GB':
                // Format as 01234 567890
                if (preg_match('/^44?(\d{4,5})(\d{6})$/', $formatted, $matches)) {
                    $formatted = "0{$matches[1]} {$matches[2]}";
                }

                break;
            case 'DE':
                // Format as 0123 456789
                if (preg_match('/^49?(\d{3,4})(\d{3,4})(\d{2,4})$/', $formatted, $matches)) {
                    $formatted = "0{$matches[1]} {$matches[2]} {$matches[3]}";
                }

                break;
        }

        return $formatted;
    }
}
