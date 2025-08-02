<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Tests\Unit;

use Awalhadi\Addressable\Models\Address;

test('address model can be instantiated', function () {
    $address = new Address;

    expect($address)->toBeInstanceOf(Address::class);
});

test('address model has correct table name', function () {
    $address = new Address;

    expect($address->getTable())->toBe('addresses');
});

test('address model has fillable attributes', function () {
    $address = new Address;

    expect($address->getFillable())->toBeArray();
    expect($address->getFillable())->toContain('addressable_type');
    expect($address->getFillable())->toContain('addressable_id');
    expect($address->getFillable())->toContain('type');
    expect($address->getFillable())->toContain('label');
    expect($address->getFillable())->toContain('given_name');
    expect($address->getFillable())->toContain('family_name');
    expect($address->getFillable())->toContain('organization');
    expect($address->getFillable())->toContain('phone');
    expect($address->getFillable())->toContain('email');
    expect($address->getFillable())->toContain('street');
    expect($address->getFillable())->toContain('street_2');
    expect($address->getFillable())->toContain('city');
    expect($address->getFillable())->toContain('state');
    expect($address->getFillable())->toContain('postal_code');
    expect($address->getFillable())->toContain('country_code');
    expect($address->getFillable())->toContain('neighborhood');
    expect($address->getFillable())->toContain('district');
    expect($address->getFillable())->toContain('latitude');
    expect($address->getFillable())->toContain('longitude');
    expect($address->getFillable())->toContain('is_primary');
    expect($address->getFillable())->toContain('is_billing');
    expect($address->getFillable())->toContain('is_shipping');
    expect($address->getFillable())->toContain('is_verified');
    expect($address->getFillable())->toContain('metadata');
    expect($address->getFillable())->toContain('verified_at');
});
