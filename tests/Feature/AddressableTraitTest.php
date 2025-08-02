<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Tests\Feature;

use Awalhadi\Addressable\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestUser extends Model
{
    use \Awalhadi\Addressable\Traits\Addressable;

    protected $table = 'test_users';

    protected $fillable = ['name', 'email'];
}

test('model can use addressable trait', function () {
    // Create test table
    Schema::create('test_users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });

    $user = TestUser::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($user)->toBeInstanceOf(TestUser::class);
    expect($user->addresses())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class);
});

test('model can have addresses', function () {
    // Create test table
    Schema::create('test_users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });

    $user = TestUser::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $address = $user->addresses()->create([
        'type' => 'home',
        'label' => 'Home Address',
        'given_name' => 'Jane',
        'family_name' => 'Doe',
        'organization' => 'Test Corp',
        'country_code' => 'US',
        'street' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'is_primary' => true,
    ]);

    expect($address)->toBeInstanceOf(Address::class);
    expect($user->addresses)->toHaveCount(1);
    expect($user->addresses->first()->street)->toBe('123 Main St');
});
