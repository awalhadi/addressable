<?php

namespace Awalhadi\Addressable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use Jackpopp\GeoDistance\GeoDistanceTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Address extends Model
{
    use HasFactory, GeoDistanceTrait;

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'label',
        'given_name',
        'family_name',
        'organization',
        'country_code',
        'street',
        'state',
        'city',
        'postal_code',
        'lat',
        'lng',
        'is_primary',
        'is_billing',
        'is_shipping',
    ];


    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'addressable_id'   => 'integer',
        'addressable_type' => 'string',
        'label'            => 'string',
        'given_name'       => 'string',
        'family_name'      => 'string',
        'organization'     => 'string',
        'country_code'     => 'string',
        'street'           => 'string',
        'state'            => 'string',
        'city'             => 'string',
        'postal_code'      => 'string',
        'lat'              => 'float',
        'lng'              => 'float',
        'is_primary'       => 'boolean',
        'is_billing'       => 'boolean',
        'is_shipping'      => 'boolean',
        'deleted_at'       => 'datetime',
    ];


       /**
     * Get the owner model of the address.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo('addressable', 'addressable_type', 'addressable_id', 'id');
    }


    /**
     * Scope primary addresses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsPrimary(Builder $builder): Builder
    {
        return $builder->where('is_primary', true);
    }


    /**
     * Scope billing addresses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsBilling(Builder $builder): Builder
    {
        return $builder->where('is_billing', true);
    }


    /**
     * Scope shipping addresses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsShipping(Builder $builder): Builder
    {
        return $builder->where('is_shipping', true);
    }


    /**
     * Scope addresses by the given country.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string                                $countryCode
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopInCountry(Builder $builder, string $countryCode): Builder
    {
        return $builder->where('country_code', $countryCode);
    }

     /**
     * Scope addresses by the given language.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string                                $languageCode
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInLanguage(Builder $builder, string $languageCode): Builder
    {
        return $builder->where('language_code', $languageCode);
    }


    /**
     * Get full name attribute.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return implode(" ", [$this->given_name, $this->family_name]);
    }


    protected static function boot()
    {
        parent::boot();

        static::saving(function(self $address){
            $geocoding = config('address.geocoding.enabled');
            $geocoding_api_key = config('address.geocoding.api_key');

            if ($geocoding && $geocoding_api_key) {
                $segments[] = $address->street;
                $segments[] = sprintf('%s, %s %s', $address->city, $address->state, $address->postal_code);
                $segments[] = country($address->country_code)->getName();

                $query = str_replace(' ', '+', implode(', ', $segments));
                $geocode = json_decode(file_get_contents(
                    "https://maps.google.com/maps/api/geocode/json?address={$query}&sensor=false&key={$geocoding_api_key}"
                ));

                if (count($geocode->results)) {
                    $address->lat = $geocode->results[0]->geometry->location->lat;
                    $address->lng = $geocode->results[0]->geometry->location->lng;
                }
            }
        });
    }



}
