<?php

declare(strict_types=1);

use Awalhadi\Addressable\Models\Address;
use Awalhadi\Addressable\Traits\Addressable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

class TestAddressableModel extends Model
{
    use Addressable;

    protected $table = 'test_models';
    protected $fillable = ['name'];
}

beforeEach(function () {
    // Create the test table
    Schema::create('test_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
});

test('model can use addressable trait', function () {
    $model = new TestAddressableModel();

    expect(method_exists($model, 'addresses'))->toBeTrue();
    expect(method_exists($model, 'primaryAddress'))->toBeTrue();
    expect(method_exists($model, 'billingAddress'))->toBeTrue();
    expect(method_exists($model, 'shippingAddress'))->toBeTrue();
    expect(method_exists($model, 'hasAddresses'))->toBeTrue();
    expect(method_exists($model, 'hasPrimaryAddress'))->toBeTrue();
    expect(method_exists($model, 'getAddressesByType'))->toBeTrue();
    expect(method_exists($model, 'getAddressesInCountry'))->toBeTrue();
    expect(method_exists($model, 'getAddressesWithinRadius'))->toBeTrue();
    expect(method_exists($model, 'createManyAddresses'))->toBeTrue();
    expect(method_exists($model, 'updateManyAddresses'))->toBeTrue();
    expect(method_exists($model, 'warmAddressCache'))->toBeTrue();
});

test('model can have addresses', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    $address = $model->addresses()->create([
        'type' => 'home',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '123 Test St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
    ]);

    expect($address)->toBeInstanceOf(Address::class);
    expect($address->addressable_id)->toBe((string) $model->id);
    expect($address->addressable_type)->toBe(TestAddressableModel::class);
});

test('can get primary address', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    $primaryAddress = $model->addresses()->create([
        'type' => 'home',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '123 Primary St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
        'is_primary' => true,
    ]);

    $retrievedAddress = $model->primaryAddress();

    expect($retrievedAddress)->toBeInstanceOf(Address::class);
    expect($retrievedAddress->id)->toBe($primaryAddress->id);
    expect($retrievedAddress->is_primary)->toBeTrue();
});

test('can get billing address', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    $billingAddress = $model->addresses()->create([
        'type' => 'billing',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '456 Billing St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
        'is_billing' => true,
    ]);

    $retrievedAddress = $model->billingAddress();

    expect($retrievedAddress)->toBeInstanceOf(Address::class);
    expect($retrievedAddress->id)->toBe($billingAddress->id);
    expect($retrievedAddress->is_billing)->toBeTrue();
});

test('can get shipping address', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    $shippingAddress = $model->addresses()->create([
        'type' => 'shipping',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '789 Shipping St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
        'is_shipping' => true,
    ]);

    $retrievedAddress = $model->shippingAddress();

    expect($retrievedAddress)->toBeInstanceOf(Address::class);
    expect($retrievedAddress->id)->toBe($shippingAddress->id);
    expect($retrievedAddress->is_shipping)->toBeTrue();
});

test('has addresses check', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    expect($model->hasAddresses())->toBeFalse();

    $model->addresses()->create([
        'type' => 'home',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '123 Test St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
    ]);

    expect($model->hasAddresses())->toBeTrue();
});

test('has primary address check', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    expect($model->hasPrimaryAddress())->toBeFalse();

    $model->addresses()->create([
        'type' => 'home',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '123 Test St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
        'is_primary' => true,
    ]);

    expect($model->hasPrimaryAddress())->toBeTrue();
});

test('can get addresses by type', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    $homeAddress = $model->addresses()->create([
        'type' => 'home',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '123 Home St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
    ]);

    $workAddress = $model->addresses()->create([
        'type' => 'work',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '456 Work St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
    ]);

    $homeAddresses = $model->getAddressesByType('home');

    expect($homeAddresses)->toHaveCount(1);
    expect($homeAddresses->first()->id)->toBe($homeAddress->id);
    expect($homeAddresses->first()->type)->toBe('home');
});

test('can get addresses in country', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    $usAddress = $model->addresses()->create([
        'type' => 'home',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '123 US St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
    ]);

    $caAddress = $model->addresses()->create([
        'type' => 'work',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '456 CA St',
        'city' => 'Test City',
        'state' => 'ON',
        'postal_code' => 'A1A1A1',
        'country_code' => 'CA',
    ]);

    $usAddresses = $model->getAddressesInCountry('US');

    expect($usAddresses)->toHaveCount(1);
    expect($usAddresses->first()->id)->toBe($usAddress->id);
    expect($usAddresses->first()->country_code)->toBe('US');
});

test('can create many addresses', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    $addressesData = [
        [
            'type' => 'home',
        'given_name' => 'John',
        'family_name' => 'Doe',
            'street' => '123 Home St',
            'city' => 'Test City',
            'state' => 'TS',
            'postal_code' => '12345',
            'country_code' => 'US',
        ],
        [
            'type' => 'work',
        'given_name' => 'John',
        'family_name' => 'Doe',
            'street' => '456 Work St',
            'city' => 'Test City',
            'state' => 'TS',
            'postal_code' => '12345',
            'country_code' => 'US',
        ],
    ];

    $createdAddresses = $model->createManyAddresses($addressesData);

    expect($createdAddresses)->toHaveCount(2);
    expect($createdAddresses->first()->type)->toBe('home');
    expect($createdAddresses->last()->type)->toBe('work');
});

test('can update many addresses', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    $homeAddress = $model->addresses()->create([
        'type' => 'home',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '123 Old Home St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
    ]);

    $workAddress = $model->addresses()->create([
        'type' => 'work',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street' => '456 Old Work St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
    ]);

    $updateData = [
        'home' => ['street' => '123 New Home St'],
        'work' => ['street' => '456 New Work St'],
    ];

    $result = $model->updateManyAddresses($updateData);

    expect($result)->toBeTrue();

    $homeAddress->refresh();
    $workAddress->refresh();

    expect($homeAddress->street)->toBe('123 New Home St');
    expect($workAddress->street)->toBe('456 New Work St');
});

test('returns null for missing addresses', function () {
    $model = TestAddressableModel::create(['name' => 'Test User']);

    expect($model->primaryAddress())->toBeNull();
    expect($model->billingAddress())->toBeNull();
    expect($model->shippingAddress())->toBeNull();
});
