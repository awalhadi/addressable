<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Contracts;

interface GeocodingDriver
{
    /**
     * Geocode a free-form address string into coordinates and optional metadata.
     */
    public function geocode(string $address): ?array;

    /**
     * Reverse geocode coordinates into an address payload.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array;
}
