<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Database\Factories;

use Awalhadi\Addressable\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Awalhadi\Addressable\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'addressable_type' => 'App\Models\User',
            'addressable_id' => (string) $this->faker->uuid(),
            'type' => $this->faker->randomElement(['home', 'work', 'billing', 'shipping', 'general']),
            'label' => $this->faker->optional()->sentence(3),
            'given_name' => $this->faker->firstName(),
            'family_name' => $this->faker->lastName(),
            'organization' => $this->faker->optional()->company(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->safeEmail(),
            'street' => $this->faker->streetAddress(),
            'street_2' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country_code' => $this->faker->countryCode(),
            'neighborhood' => $this->faker->optional()->citySuffix(),
            'district' => $this->faker->optional()->cityPrefix(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'is_primary' => $this->faker->boolean(20),
            'is_billing' => $this->faker->boolean(20),
            'is_shipping' => $this->faker->boolean(20),
            'is_verified' => $this->faker->boolean(30),
            'metadata' => $this->faker->optional()->randomElements([
                'apartment' => $this->faker->buildingNumber(),
                'floor' => $this->faker->numberBetween(1, 50),
                'unit' => $this->faker->bothify('##?'),
            ], $this->faker->numberBetween(0, 3), false),
            'verified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the address is a home address.
     */
    public function home(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'home',
        ]);
    }

    /**
     * Indicate that the address is a work address.
     */
    public function work(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'work',
        ]);
    }

    /**
     * Indicate that the address is a billing address.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'billing',
            'is_billing' => true,
        ]);
    }

    /**
     * Indicate that the address is a shipping address.
     */
    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'shipping',
            'is_shipping' => true,
        ]);
    }

    /**
     * Indicate that the address is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
