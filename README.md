# Laravel Addressable

**Laravel Addressable** is a polymorphic Laravel package, for management different types of address. You can add addresses to any eloquent model with laravel addressable package. Hope it will help and make easier your life

[![Latest Version](https://img.shields.io/github/release/awalhadi/laravel-addressable.svg?style=flat-square)](https://github.com/awalhadi/laravel-addressable/releases)

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

[![Downloads](https://img.shields.io/packagist/dt/awalhadi/laravel-addressable.svg?style=flat-square)](https://packagist.org/packages/awalhadi/laravel-addressable)

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://www.buymeacoffee.com/gbraad)


## Installation

1. Install the package via composer:

   ```shell
   composer require awalhadi/addressable
   ```

2. Execute database migrates by the following

   ```shell
   php artisan migrate
   ```

3. Public Config file
   ```shell
   php artisan vendor:publish --provider="Awalhadi\Addressable\Providers\AddressableServiceProvider" --tag="config"
   ```

---

Setup done

## Start using

Which model you want to add address, simply use `Addressable` Trait in your eloquent model like below

```php

use Awalhadi\Addressable\Traits\Addressable;

class ModelName {
    use Addressable;
}

```

### Manage your addresses

```php
// Get instance of your model
$user = new \App\Models\User::find(1);

// Create a new address
$user->addresses()->create([
    'label'        => 'Default Address',
    'given_name'   => 'A Awal',
    'family_name'  => 'Hadi',
    'organization' => 'ITclan BD',
    'country_code' => 'bd',
    'street'       => '10 Azompur Uttora',
    'state'        => 'Rajshahi',
    'city'         => 'Natore',
    'postal_code'  => '6400',
    'lat'          => '24.4547889',
    'lng'          => '88.9717818',
    'is_primary'   => true,
    'is_billing'   => true,
    'is_shipping' => true,
]);

// Create multiple new addresses
$user->addresses()->createMany([
    [...],
    [...],
    [...],
]);



// Alternative way of address deletion
$user->addresses()->where('id', 123)->first()->delete();
```

### Manage your addressable model

The API is intuitive and very straight forward, so let's give it a quick look:

```php
// Get instance of your model
$user = new \App\Models\User::find(1);

// Get attached addresses collection
$user->addresses;

// Get attached addresses query builder
$user->addresses();


// Find all users within 5 kilometers radius from the lat/lng 31.2467601/29.9020376
$fiveKmAddresses = \App\Models\User::findByDistance('31.2467601', '29.9020376')->get();

$fiveKmAddresses = \App\Models\User::findByDistance('31.2467601', '29.9020376', 5, 'kilometers')->get();

```

`findByDistance` methods except four parameters
`findByDistance(latitude, longitude, distance=10, unit=kilometers)`

## Changelog

## Support

The Generate free qr code:

- [QR Code generator](https://gqrcode.com)

---

👉 If you are interested to step on as the main maintainer of this package, please [reach out to me](https://www.linkedin.com/in/a-awal-hadi/)!

---

## License

This software is released under [The MIT License (MIT)](LICENSE).

(c) 2022 awalhadi, All rights reserved.
