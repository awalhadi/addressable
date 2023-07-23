<?php

namespace Awalhadi\Addressable\Traits;

use Awalhadi\Addressable\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;


trait Addressable
{
    /**
     * Register a deleted model event with the dispatcher.
     *
     * @param \Closure|string $callback
     *
     * @return void
     */

    abstract public static function deleted($callback);

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $localKey
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */

    abstract public function morphMany($related, $name, $type = null, $localKey = null);


    /**
     * Boot the addressable trait for the model.
     *
     * @return void
     */

    public static function bootAddressable()
    {
        static::deleted(function (self $model) {
            $model->addresses()->delete();
        });
    }


    /**
     * Get all attached addresses to the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function addresses(): MorphMany
    {
        // return $this->morphMany(config('address.models.address'), 'addressable', 'addressable_type', 'addressable_id');
        return $this->morphMany(Address::class, 'addressable', 'addressable_type', 'addressable_id');
    }

    /**
     * Find addressables by distance.
     *
     * @param string $latitude
     * @param string $longitude
     * @param string $distance
     * @param string $unit
     *
     * @return \Illuminate\Support\Query
     */
    public static function findByDistance($latitude, $longitude, $distance = 10, $unit = null)
    {
        $units = [
            'km' => 'kilometers',
            'mile' => 'miles'
        ];
        $distanceType = $units[$unit] ?? 'kilometers';
        return self::whereHas('addresses', function ($q) use ($latitude, $longitude, $distance, $distanceType) {
            $q->within($distance, $distanceType, $latitude, $longitude);
        });
    }

    /**
     * Search users within a specified radius from a given latitude and longitude.
     *
     * @param float $latitude The latitude of the center point.
     * @param float $longitude The longitude of the center point.
     * @param int $distance The distance in units (e.g., kilometers, miles).
     * @param string|null $unit The unit for the distance (e.g., 'km' for kilometers, 'mile' for miles).
     * @return \Illuminate\Support\Query
     */
    public static function searchByRadius($latitude, $longitude, int $distance = 10, $unit = null)
    {
        $units = [
            'km' => 'kilometers',
            'mile' => 'miles'
        ];
        $distanceType = $units[$unit] ?? 'kilometers';

        return self::whereHas('addresses', function ($q) use ($latitude, $longitude, $distance, $distanceType) {
            $q->within($distance, $distanceType, $latitude, $longitude);
        })
            // ->select(['users.id', 'users.name']) // Specify only the required columns
            ->with('addresses'); // Eager load addresses relationship
    }
}
