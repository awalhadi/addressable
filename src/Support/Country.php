<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Support;

use Awalhadi\Addressable\Services\CountryService;

class Country
{
    /**
     * Country data.
     */
    private ?array $data = null;

    /**
     * Country service instance.
     */
    private CountryService $countryService;

    /**
     * Create a new Country instance.
     */
    public function __construct(private string $code)
    {
        $this->countryService = app(CountryService::class);
        $this->data = $this->countryService->get($code);
    }

    /**
     * Get country name.
     */
    public function getName(): string
    {
        return $this->data['name'] ?? 'Unknown';
    }

    /**
     * Get country code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get country dial code.
     */
    public function getDialCode(): ?string
    {
        return $this->data['dial_code'] ?? null;
    }

    /**
     * Get country currency.
     */
    public function getCurrency(): ?string
    {
        return $this->data['currency'] ?? null;
    }

    /**
     * Get country continent.
     */
    public function getContinent(): ?string
    {
        return $this->data['continent'] ?? null;
    }

    /**
     * Check if country exists.
     */
    public function exists(): bool
    {
        return $this->data !== null;
    }

    /**
     * Get all country data.
     */
    public function toArray(): array
    {
        return $this->data ?? [];
    }

    /**
     * Convert to string (returns country name).
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Magic getter for country properties.
     */
    public function __get(string $property): mixed
    {
        return match ($property) {
            'name' => $this->getName(),
            'code' => $this->getCode(),
            'dial_code' => $this->getDialCode(),
            'currency' => $this->getCurrency(),
            'continent' => $this->getContinent(),
            default => $this->data[$property] ?? null,
        };
    }

    /**
     * Magic isset for country properties.
     */
    public function __isset(string $property): bool
    {
        return isset($this->data[$property]) || in_array($property, ['name', 'code', 'dial_code', 'currency', 'continent']);
    }
}
