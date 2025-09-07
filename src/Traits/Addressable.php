<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Traits;

use Awalhadi\Addressable\Models\Address;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Addressable
{
    /**
     * Register a deleted model event with the dispatcher.
     */
    abstract public static function deleted($callback);

    /**
     * Define a polymorphic one-to-many relationship.
     */
    abstract public function morphMany($related, $name, $type = null, $localKey = null);

    /**
     * Boot the addressable trait for the model.
     */
    public static function bootAddressable(): void
    {
        static::deleted(function (self $model) {
            $model->addresses()->delete();
        });
    }

    /**
     * Get all attached addresses to the model.
     */
    public function addresses(): MorphMany
    {
        // return $this->morphMany(config('address.models.address'), 'addressable', 'addressable_type', 'addressable_id');
        return $this->morphMany(Address::class, 'addressable', 'addressable_type', 'addressable_id');
    }

    /**
     * Get the primary address.
     */
    public function primaryAddress(): ?Address
    {
        return $this->addresses()?->isPrimary()?->first() ?? null;
    }

    /**
     * Get the billing address.
     */
    public function billingAddress(): ?Address
    {
        return $this->addresses()?->isBilling()?->first() ?? null;
    }

    /**
     * Get the shipping address.
     */
    public function shippingAddress(): ?Address
    {
        return $this->addresses()?->isShipping()?->first() ?? null;
    }

    /**
     * Check if the model has any addresses.
     */
    public function hasAddresses(): bool
    {
        return $this->addresses()?->exists() ?? false;
    }

    /**
     * Check if the model has a primary address.
     */
    public function hasPrimaryAddress(): bool
    {
        return $this->addresses()?->isPrimary()?->exists() ?? false;
    }

    /**
     * Get addresses by type.
     */
    public function getAddressesByType(string $type): Collection
    {
        return $this->addresses()?->ofType($type)?->get() ?? [];
    }

    /**
     * Get addresses in a specific country.
     */
    public function getAddressesInCountry(string $countryCode): Collection
    {
        return $this->addresses()?->inCountry($countryCode)?->get() ?? [];
    }

    /**
     * Get addresses within a specified radius.
     */
    public function getAddressesWithinRadius(float $latitude, float $longitude, float $radius, string $unit = 'kilometers'): Collection
    {
        return $this->addresses()
            ->withCoordinates()
            ->get()
            ->filter(fn (Address $address) => $address->calculateDistance($latitude, $longitude, $address->latitude, $address->longitude, $unit) <= $radius);
    }

    /**
     * Create multiple addresses at once.
     */
    public function createManyAddresses(array $addresses): \Illuminate\Database\Eloquent\Collection
    {
        $createdAddresses = new \Illuminate\Database\Eloquent\Collection();

        foreach ($addresses as $addressData) {
            $createdAddresses->push($this->addresses()->create($addressData));
        }

        return $createdAddresses;
    }

    /**
     * Update multiple addresses by type.
     */
    public function updateManyAddresses(array $addressUpdates): bool
    {
        foreach ($addressUpdates as $type => $data) {
            $this->addresses()->ofType($type)->update($data);
        }

        return true;
    }

    /**
     * Warm up the address cache for this model.
     */
    public function warmAddressCache(): bool
    {
        $addresses = $this->addresses()->get();

        foreach ($addresses as $address) {
            $address->cacheAddress();
        }

        return true;
    }

    /**
     * Find addressables by distance.
     */
    public static function findByDistance(float $latitude, float $longitude, float $distance = 10, ?string $unit = null)
    {
        $distanceType = match ($unit) {
            'km' => 'kilometers',
            'mile' => 'miles',
            default => 'kilometers',
        };

        return self::whereHas('addresses', function ($query) use ($latitude, $longitude, $distance, $distanceType) {
            $query->within($distance, $distanceType, $latitude, $longitude);
        });
    }

    /**
     * Search addressables within a specified radius.
     */
    public static function searchByRadius(float $latitude, float $longitude, float $distance = 10, ?string $unit = null)
    {
        $distanceType = match ($unit) {
            'km' => 'kilometers',
            'mile' => 'miles',
            default => 'kilometers',
        };

        return self::whereHas('addresses', fn ($query) => $query->within($distance, $distanceType, $latitude, $longitude))
            ->with('addresses');
    }


}
