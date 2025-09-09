<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Models;

use Awalhadi\Addressable\Contracts\GeocodingDriver;
use Awalhadi\Addressable\Events\AddressCreated;
use Awalhadi\Addressable\Events\AddressDeleted;
use Awalhadi\Addressable\Events\AddressUpdated;
use Awalhadi\Addressable\Services\CountryService;
use Awalhadi\Addressable\Services\ValidationService;
use Awalhadi\Addressable\Traits\HasAddressCaching;
use Awalhadi\Addressable\Traits\HasAddressValidation;
use Awalhadi\Addressable\Traits\HasSpatialOperations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Address extends Model
{
    use HasAddressCaching;
    use HasAddressValidation;
    use HasFactory;
    use HasSpatialOperations;
    use HasUuids;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'type',
        'label',
        'given_name',
        'family_name',
        'organization',
        'phone',
        'email',
        'street',
        'street_2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'neighborhood',
        'district',
        'latitude',
        'longitude',
        'is_primary',
        'is_billing',
        'is_shipping',
        'is_verified',
        'metadata',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'addressable_id' => 'string',
        'type'           => 'string',
        'label'          => 'string',
        'given_name'     => 'string',
        'family_name'    => 'string',
        'organization'   => 'string',
        'phone'          => 'string',
        'email'          => 'string',
        'street'         => 'string',
        'street_2'       => 'string',
        'city'           => 'string',
        'state'          => 'string',
        'postal_code'    => 'string',
        'country_code'   => 'string',
        'neighborhood'   => 'string',
        'district'       => 'string',
        'latitude'       => 'decimal:8',
        'longitude'      => 'decimal:8',
        'is_primary'     => 'boolean',
        'is_billing'     => 'boolean',
        'is_shipping'    => 'boolean',
        'is_verified'    => 'boolean',
        'metadata'       => 'array',
        'verified_at'    => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    /**
     * The attributes that should be appended to the model.
     */
    protected $appends = [
        'full_address',
        'full_name',
        'country_name',
        'formatted_phone',
        'masked_phone',
        'masked_email',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'metadata',
    ];

    /**
     * The event map for the model.
     */
    protected $dispatchesEvents = [
        'created' => AddressCreated::class,
        'updated' => AddressUpdated::class,
        'deleted' => AddressDeleted::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Awalhadi\Addressable\Database\Factories\AddressFactory::new();
    }

    /**
     * Get the owner model of the address.
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo('addressable', 'addressable_type', 'addressable_id', 'id');
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->given_name, $this->family_name])));
    }

    /**
     * Get the full address attribute.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->street_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country_name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the country name.
     */
    public function getCountryNameAttribute(): ?string
    {
        // If country_name is already set in the database, return it
        if ($this->attributes['country_name'] ?? null) {
            return $this->attributes['country_name'];
        }

        // If no country_code, return null
        if (! $this->country_code) {
            return null;
        }

        // Try lightweight country service first
        $countryService = app(CountryService::class);
        $countryName = $countryService->getName($this->country_code);
        if ($countryName) {
            return $countryName;
        }

        // Return country code as fallback
        return $this->country_code;
    }

    /**
     * Get the formatted phone number.
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        // Basic phone formatting - can be enhanced with libphonenumber
        return $this->phone;
    }

    /**
     * Get the masked phone number for logging.
     */
    public function getMaskedPhoneAttribute(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $length = strlen($this->phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($this->phone, 0, 2).str_repeat('*', $length - 4).substr($this->phone, -2);
    }

    /**
     * Get the masked email for logging.
     */
    public function getMaskedEmailAttribute(): ?string
    {
        if (! $this->email) {
            return null;
        }

        $parts = explode('@', $this->email);
        if (count($parts) !== 2) {
            return $this->email;
        }

        $username = $parts[0];
        $domain = $parts[1];

        if (strlen($username) <= 2) {
            $maskedUsername = str_repeat('*', strlen($username));
        } else {
            $maskedUsername = substr($username, 0, 1).str_repeat('*', strlen($username) - 2).substr($username, -1);
        }

        return $maskedUsername.'@'.$domain;
    }

    /**
     * Check if the address has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Check if the address is complete.
     */
    public function isComplete(): bool
    {
        return ! empty($this->street) && ! empty($this->city) && ! empty($this->country_code);
    }

    /**
     * Scope primary addresses.
     */
    public function scopeIsPrimary(Builder $builder): Builder
    {
        return $builder->where('is_primary', true);
    }

    /**
     * Scope billing addresses.
     */
    public function scopeIsBilling(Builder $builder): Builder
    {
        return $builder->where('is_billing', true);
    }

    /**
     * Scope shipping addresses.
     */
    public function scopeIsShipping(Builder $builder): Builder
    {
        return $builder->where('is_shipping', true);
    }

    /**
     * Scope verified addresses.
     */
    public function scopeIsVerified(Builder $builder): Builder
    {
        return $builder->where('is_verified', true);
    }

    /**
     * Scope addresses by type.
     */
    public function scopeOfType(Builder $builder, string $type): Builder
    {
        return $builder->where('type', $type);
    }

    /**
     * Scope addresses by country.
     */
    public function scopeInCountry(Builder $builder, string $countryCode): Builder
    {
        return $builder->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope addresses with coordinates.
     */
    public function scopeWithCoordinates(Builder $builder): Builder
    {
        return $builder->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /**
     * Scope addresses by city.
     */
    public function scopeInCity(Builder $builder, string $city): Builder
    {
        return $builder->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope addresses by state.
     */
    public function scopeInState(Builder $builder, string $state): Builder
    {
        return $builder->where('state', 'like', "%{$state}%");
    }

    /**
     * Scope addresses by postal code.
     */
    public function scopeInPostalCode(Builder $builder, string $postalCode): Builder
    {
        return $builder->where('postal_code', 'like', "%{$postalCode}%");
    }

    /**
     * Scope addresses created in the last days.
     */
    public function scopeRecent(Builder $builder, int $days = 30): Builder
    {
        return $builder->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope to get addresses within a certain distance from coordinates.
     */
    public function scopeWithin(Builder $builder, float $distance, string $unit, float $latitude, float $longitude): Builder
    {
        $earthRadius = match ($unit) {
            'kilometers' => 6371,
            'miles' => 3959,
            'meters' => 6371000,
            default => 6371,
        };

        $latDelta = $distance / $earthRadius * (180 / M_PI);
        $lonDelta = $distance / $earthRadius * (180 / M_PI) / cos(deg2rad($latitude));

        return $builder->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('longitude', [$longitude - $lonDelta, $longitude + $lonDelta])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $address) {
            // Set default type if not provided
            if (empty($address->type)) {
                $address->type = config('addressable.types.home.default') ? 'home' : 'general';
            }

            // Auto-geocode if enabled and coordinates are missing
            if (config('addressable.geocoding.enabled') && ! $address->hasCoordinates() && $address->isComplete()) {
                $address->geocode();
            }
        });

        static::saving(function (self $address) {
            // Ensure only one primary address per addressable
            if ($address->is_primary) {
                $address->addressable->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_primary' => false]);
            }

            // Validate postal code if enabled
            if (config('addressable.validation.postal_code.enabled') && $address->postal_code) {
                $address->validatePostalCode();
            }
        });

        static::updated(function (self $address) {
            // Clear related caches on update
            $address->clearAddressCache();
        });

        static::deleted(function (self $address) {
            // Clear related caches
            $address->clearAddressCache();
        });
    }

    /**
     * Geocode the address.
     */
    public function geocode(): bool
    {
        if (! $this->isComplete()) {
            return false;
        }

        try {
            $geocodingService = app(GeocodingDriver::class);
            $coordinates = $geocodingService->geocode($this->full_address);

            if ($coordinates) {
                $this->latitude = $coordinates['latitude'];
                $this->longitude = $coordinates['longitude'];

                return true;
            }
        } catch (\Exception $e) {
            Log::warning("Geocoding failed for address {$this->id}: ".$e->getMessage());
        }

        return false;
    }

    /**
     * Verify the address.
     */
    public function verify(): bool
    {
        try {
            $validationService = app(ValidationService::class);
            $isValid = $validationService->verifyAddress($this);

            if ($isValid) {
                $this->is_verified = true;
                $this->verified_at = Carbon::now();
                $this->save();
            }

            return $isValid;
        } catch (\Exception $e) {
            Log::warning("Address verification failed for address {$this->id}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Calculate distance to another address.
     */
    public function distanceTo(self $address, ?string $unit = null): ?float
    {
        if (! $this->hasCoordinates() || ! $address->hasCoordinates()) {
            return null;
        }

        $unit = $unit ?? config('addressable.spatial.default_unit', 'kilometers');

        return $this->calculateDistance(
            (float) $this->latitude,
            (float) $this->longitude,
            (float) $address->latitude,
            (float) $address->longitude,
            $unit
        );
    }

    /**
     * Check if this address is within a certain radius of another address.
     */
    public function isWithinRadius(self $address, float $radius, ?string $unit = null): bool
    {
        $distance = $this->distanceTo($address, $unit);

        return $distance !== null && $distance <= $radius;
    }

    /**
     * Validate the entire address.
     */
    public function isValid(): bool
    {
        $errors = $this->getValidationErrors();

        return empty($errors);
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
    public function formatPostalCode(): ?string
    {
        if (! $this->postal_code || ! $this->country_code) {
            return $this->postal_code;
        }

        $postalCode = trim($this->postal_code);

        return match ($this->country_code) {
            'US' => $this->formatUSPostalCode($postalCode),
            'CA' => $this->formatCAPostalCode($postalCode),
            'GB' => strtoupper($postalCode),
            default => $postalCode,
        };
    }

    /**
     * Format US postal code.
     */
    private function formatUSPostalCode(string $postalCode): string
    {
        $postalCode = preg_replace('/[^0-9-]/', '', $postalCode);

        if (strlen($postalCode) === 9) {
            return substr($postalCode, 0, 5).'-'.substr($postalCode, 5);
        }

        return $postalCode;
    }

    /**
     * Format Canadian postal code.
     */
    private function formatCAPostalCode(string $postalCode): string
    {
        $postalCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $postalCode));

        if (strlen($postalCode) === 6) {
            return substr($postalCode, 0, 3).' '.substr($postalCode, 3);
        }

        return $postalCode;
    }

    /**
     * Format phone number according to country standards.
     */
    public function formatPhoneNumber(): ?string
    {
        if (! $this->phone || ! $this->country_code) {
            return $this->phone;
        }

        $phone = preg_replace('/[^0-9+]/', '', $this->phone);

        return match ($this->country_code) {
            'US', 'CA' => $this->formatUSPhoneNumber($phone),
            'GB' => $this->formatGBPhoneNumber($phone),
            default => $phone,
        };
    }

    /**
     * Format US/Canada phone number.
     */
    private function formatUSPhoneNumber(string $phone): string
    {
        if (strlen($phone) === 10) {
            return '('.substr($phone, 0, 3).') '.substr($phone, 3, 3).'-'.substr($phone, 6);
        }

        if (strlen($phone) === 11 && $phone[0] === '1') {
            return '+1 ('.substr($phone, 1, 3).') '.substr($phone, 4, 3).'-'.substr($phone, 7);
        }

        return $phone;
    }

    /**
     * Format UK phone number.
     */
    private function formatGBPhoneNumber(string $phone): string
    {
        if (strlen($phone) === 10) {
            return substr($phone, 0, 4).' '.substr($phone, 4, 3).' '.substr($phone, 7);
        }

        return $phone;
    }

    /**
     * Reverse geocode the address from coordinates.
     */
    public function reverseGeocode(): bool
    {
        try {
            if (! $this->hasCoordinates()) {
                return false;
            }

            $geocodingService = app(GeocodingDriver::class);
            $result = $geocodingService->reverseGeocode((float) $this->latitude, (float) $this->longitude);

            if ($result) {
                $this->fill($result);
                $this->save();

                return true;
            }
        } catch (\Exception $e) {
            Log::warning("Reverse geocoding failed for address {$this->id}: ".$e->getMessage());
        }

        return false;
    }

    /**
     * Get the address as an array for API responses.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $this->label,
            'given_name' => $this->given_name,
            'family_name' => $this->family_name,
            'organization' => $this->organization,
            'phone' => $this->masked_phone,
            'email' => $this->masked_email,
            'street' => $this->street,
            'street_2' => $this->street_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,
            'country_name' => $this->country_name,
            'neighborhood' => $this->neighborhood,
            'district' => $this->district,
            'full_address' => $this->full_address,
            'full_name' => $this->full_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_primary' => $this->is_primary,
            'is_billing' => $this->is_billing,
            'is_shipping' => $this->is_shipping,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
