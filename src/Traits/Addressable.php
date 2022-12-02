<?php
namespace Awalhadi\Addressable\Traits;

use Illuminate\Support\Collection;
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
        static::deleted(function(self $model){
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
    public function findByDistance($latitude, $longitude, $distance = 10, $unit = null)
    {
        // @TODO: this method needs to be refactored!
        $units = [
            'km' => 'kilometers',
            'mile' => 'miles'
        ];
        $distanceType = $units[$unit] ?? 'kilometers';
        // dd($latitude, $longitude, $distance, $distanceType);
        // return self::whereHas('addresses', function($q) use($latitude, $longitude, $distance, $distanceType){
        //     $q->within($distance, $distanceType, $latitude, $longitude);
        // });

        return $this->whereHas('addresses', function($q) use($latitude, $longitude, $distance, $distanceType, ){
            $q->within($distance, $distanceType, $latitude, $longitude);
        });


        // return $this->whereHas('addresses', function($q) use($latitude, $longitude, $distance, $distanceType, ){
        //     $q->within($distance, $distanceType, $latitude, $longitude);
        // });

    }
}
