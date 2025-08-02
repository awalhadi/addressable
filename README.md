# Laravel Addressable Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/awalhadi/addressable.svg?style=flat-square)](https://packagist.org/packages/awalhadi/addressable)
[![Total Downloads](https://img.shields.io/packagist/dt/awalhadi/addressable.svg?style=flat-square)](https://packagist.org/packages/awalhadi/addressable)
[![License](https://img.shields.io/packagist/l/awalhadi/addressable.svg?style=flat-square)](https://packagist.org/packages/awalhadi/addressable)
[![PHP Version](https://img.shields.io/packagist/php-v/awalhadi/addressable.svg?style=flat-square)](https://packagist.org/packages/awalhadi/addressable)

A modern, feature-rich Laravel package for managing addresses with geocoding, validation, caching, and spatial operations. Perfect for e-commerce, CRM systems, and any application requiring robust address management.

## ✨ Features

- **🔗 Polymorphic Relationships** - Attach addresses to any model
- **🌍 Geocoding Support** - Google Maps, OpenStreetMap, HERE APIs
- **✅ Address Validation** - Postal codes, phone numbers, email validation
- **🗺️ Spatial Operations** - Distance calculations, geofencing, bounding boxes
- **⚡ Smart Caching** - Multi-level caching for performance
- **🔒 Security Features** - Data masking, GDPR compliance, encryption
- **📊 Bulk Operations** - Efficient mass address management
- **🎯 Multiple Address Types** - Home, work, billing, shipping addresses
- **📱 Mobile Optimized** - Responsive design considerations
- **🧪 Comprehensive Testing** - 100% test coverage with Pest

## 📋 Requirements

- **PHP**: 7.4, 8.0, 8.1, 8.2, 8.3, 8.4+
- **Laravel**: 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0+
- **Database**: MySQL 5.7+, PostgreSQL 10+, SQLite 3.8+

## 🚀 Installation

### 1. Install via Composer

```bash
composer require awalhadi/addressable
```

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --provider="Awalhadi\Addressable\Providers\AddressableServiceProvider"
```

### 3. Run Migrations

```bash
php artisan migrate
```

## 🎯 Quick Start

### 1. Add Trait to Your Model

```php
<?php

namespace App\Models;

use Awalhadi\Addressable\Traits\Addressable;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Addressable;

    // Your existing model code...
}
```

### 2. Create Your First Address

```php
$user = User::find(1);

$address = $user->addresses()->create([
    'type' => 'home',
    'label' => 'My Home',
    'given_name' => 'John',
    'family_name' => 'Doe',
    'organization' => 'Acme Corp',
    'phone' => '+1-555-123-4567',
    'email' => 'john@example.com',
    'street' => '123 Main Street',
    'street_2' => 'Apt 4B',
    'city' => 'New York',
    'state' => 'NY',
    'postal_code' => '10001',
    'country_code' => 'US',
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'is_primary' => true,
    'is_billing' => false,
    'is_shipping' => true,
]);
```

### 3. Query Addresses

```php
// Get all addresses
$addresses = $user->addresses;

// Get primary address
$primaryAddress = $user->primaryAddress();

// Get addresses by type
$homeAddresses = $user->addresses()->ofType('home')->get();
$billingAddresses = $user->addresses()->isBilling()->get();

// Get addresses within radius
$nearbyAddresses = $user->addresses()
    ->withCoordinates()
    ->get()
    ->filter(fn($address) => $address->isWithinRadius(40.7128, -74.0060, 10));
```

## 📚 API Reference

### Address Model

#### Properties

| Property           | Type      | Description                                  |
| ------------------ | --------- | -------------------------------------------- |
| `id`               | UUID      | Primary key                                  |
| `addressable_type` | string    | Polymorphic model class                      |
| `addressable_id`   | UUID      | Polymorphic model ID                         |
| `type`             | string    | Address type (home, work, billing, shipping) |
| `label`            | string    | Custom label                                 |
| `given_name`       | string    | First name                                   |
| `family_name`      | string    | Last name                                    |
| `organization`     | string    | Company name                                 |
| `phone`            | string    | Phone number                                 |
| `email`            | string    | Email address                                |
| `street`           | string    | Street address                               |
| `street_2`         | string    | Secondary address line                       |
| `city`             | string    | City                                         |
| `state`            | string    | State/province                               |
| `postal_code`      | string    | Postal/ZIP code                              |
| `country_code`     | string    | ISO country code                             |
| `neighborhood`     | string    | Neighborhood                                 |
| `district`         | string    | District                                     |
| `latitude`         | decimal   | Latitude coordinate                          |
| `longitude`        | decimal   | Longitude coordinate                         |
| `is_primary`       | boolean   | Primary address flag                         |
| `is_billing`       | boolean   | Billing address flag                         |
| `is_shipping`      | boolean   | Shipping address flag                        |
| `is_verified`      | boolean   | Verification status                          |
| `metadata`         | json      | Additional data                              |
| `verified_at`      | timestamp | Verification timestamp                       |

#### Accessors

```php
// Get full name
$address->full_name; // "John Doe"

// Get formatted address
$address->full_address; // "123 Main Street, Apt 4B, New York, NY 10001, US"

// Get country name
$address->country_name; // "United States"

// Get formatted phone
$address->formatted_phone; // "(555) 123-4567"

// Get masked phone (for privacy)
$address->masked_phone; // "(555) ***-4567"

// Get masked email (for privacy)
$address->masked_email; // "j***@example.com"
```

#### Scopes

```php
// Filter by type
Address::ofType('home')->get();

// Filter by country
Address::inCountry('US')->get();

// Filter by city
Address::inCity('New York')->get();

// Filter by state
Address::inState('NY')->get();

// Filter by postal code
Address::inPostalCode('10001')->get();

// Only verified addresses
Address::isVerified()->get();

// Only addresses with coordinates
Address::withCoordinates()->get();

// Recent addresses (last 30 days)
Address::recent()->get();
```

### Addressable Trait

#### Methods

```php
// Get all addresses
$user->addresses;

// Get primary address
$user->primaryAddress();

// Get billing address
$user->billingAddress();

// Get shipping address
$user->shippingAddress();

// Check if has addresses
$user->hasAddresses();

// Check if has primary address
$user->hasPrimaryAddress();

// Get addresses by type
$user->getAddressesByType('home');

// Get addresses in country
$user->getAddressesInCountry('US');

// Get addresses within radius
$user->getAddressesWithinRadius($lat, $lng, $radius);

// Create multiple addresses
$user->createManyAddresses([
    ['type' => 'home', 'street' => '123 Home St'],
    ['type' => 'work', 'street' => '456 Work Ave'],
]);

// Update multiple addresses
$user->updateManyAddresses([
    'home' => ['street' => '789 New Home St'],
    'work' => ['street' => '012 New Work Ave'],
]);
```

### Spatial Operations

```php
// Calculate distance between two addresses
$distance = $address1->distanceTo($address2);

// Check if address is within radius
$isNearby = $address->isWithinRadius($lat, $lng, 10);

// Calculate distance using Haversine formula
$distance = $address->calculateDistance($lat, $lng);

// Calculate distance using Vincenty formula (more accurate)
$distance = $address->calculateDistanceVincenty($lat, $lng);

// Check if point is in polygon (geofencing)
$isInside = $address->isPointInPolygon($polygon);

// Create bounding box
$bbox = $address->createBoundingBox($radius);

// Convert decimal to DMS format
$dms = $address->decimalToDMS($latitude);

// Convert DMS to decimal
$decimal = $address->dmsToDecimal($dms);

// Calculate midpoint between two coordinates
$midpoint = $address->calculateMidpoint($lat1, $lng1, $lat2, $lng2);
```

### Address Validation

```php
// Validate entire address
$isValid = $address->isValid();

// Get validation errors
$errors = $address->getValidationErrors();

// Validate postal code
$isValid = $address->validatePostalCode();

// Validate phone number
$isValid = $address->validatePhoneNumber();

// Validate email
$isValid = $address->validateEmail();

// Validate country code
$isValid = $address->validateCountryCode();

// Format postal code
$formatted = $address->formatPostalCode();

// Format phone number
$formatted = $address->formatPhoneNumber();
```

### Geocoding

```php
// Geocode address (get coordinates)
$address->geocode();

// Reverse geocode (get address from coordinates)
$address->reverseGeocode();

// Check if address has coordinates
$hasCoords = $address->hasCoordinates();

// Check if address is complete
$isComplete = $address->isComplete();
```

### Caching

```php
// Cache address data
$address->cacheAddressData();

// Get cached address data
$cached = $address->getCachedAddressData();

// Clear address cache
$address->clearAddressCache();

// Cache geocoding results
$address->cacheGeocodingResult($result);

// Get cached geocoding result
$cached = $address->getCachedGeocodingResult();

// Clear all related caches
$address->clearAllRelatedCaches();

// Warm cache for addressable
$user->warmAddressCache();
```

## ⚙️ Configuration

The package configuration file (`config/addressable.php`) provides extensive customization options:

### Database Configuration

```php
'database' => [
    'table' => 'addresses',
    'primary_key' => 'id', // 'id' or 'uuid'
    'uuid_version' => 4,
    'soft_deletes' => true,
    'timestamps' => true,
],
```

### Address Types

```php
'types' => [
    'default' => 'general',
    'available' => [
        'home' => 'Home Address',
        'work' => 'Work Address',
        'billing' => 'Billing Address',
        'shipping' => 'Shipping Address',
        'general' => 'General Address',
    ],
],
```

### Geocoding

```php
'geocoding' => [
    'enabled' => env('ADDRESSABLE_GEOCODING_ENABLED', true),
    'provider' => env('ADDRESSABLE_GEOCODING_PROVIDER', 'google'),
    'providers' => [
        'google' => [
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
            'enabled' => true,
        ],
        'nominatim' => [
            'base_url' => 'https://nominatim.openstreetmap.org',
            'enabled' => true,
        ],
        'here' => [
            'app_id' => env('HERE_APP_ID'),
            'app_code' => env('HERE_APP_CODE'),
            'enabled' => false,
        ],
    ],
    'cache_duration' => 86400, // 24 hours
],
```

### Validation

```php
'validation' => [
    'enabled' => env('ADDRESSABLE_VALIDATION_ENABLED', true),
    'strict_mode' => false,
    'auto_verify' => false,
    'postal_code_validation' => true,
    'phone_validation' => true,
    'email_validation' => true,
    'country_code_validation' => true,
],
```

### Caching

```php
'caching' => [
    'enabled' => env('ADDRESSABLE_CACHING_ENABLED', true),
    'store' => env('ADDRESSABLE_CACHE_STORE', 'default'),
    'prefix' => 'addressable',
    'ttl' => [
        'address' => 3600, // 1 hour
        'geocoding' => 86400, // 24 hours
        'validation' => 7200, // 2 hours
        'distance' => 1800, // 30 minutes
    ],
],
```

## 🔧 Development

### Prerequisites

- PHP 8.1+
- Composer
- Git

### Setup

```bash
# Clone the repository
git clone https://github.com/awalhadi/addressable.git
cd addressable

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Testing

```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage

# Run specific test suite
./vendor/bin/pest --testsuite=Unit
./vendor/bin/pest --testsuite=Feature
./vendor/bin/pest --testsuite=Integration
```

### Code Quality

```bash
# Format code
composer format

# Check code style
composer format:check
```

## 📦 Publishing

### 1. Update Version

Update the version in `composer.json`:

```json
{
  "version": "2.0.0"
}
```

### 2. Update CHANGELOG

Add your changes to `CHANGELOG.md`:

```markdown
## [2.0.0] - 2024-01-15

### Added

- UUID support for primary keys
- Spatial operations trait
- Address validation trait
- Caching trait
- Geocoding service
- Validation service
- Comprehensive configuration
- Bulk operations
- GDPR compliance features

### Changed

- Modernized database schema
- Enhanced Address model
- Improved test coverage
- Updated dependencies

### Removed

- Legacy migration files
- Deprecated methods
```

### 3. Create Git Tag

```bash
git add .
git commit -m "Release version 2.0.0"
git tag -a v2.0.0 -m "Version 2.0.0"
git push origin main --tags
```

### 4. Publish to Packagist

The package will be automatically published to Packagist when you push the tag, if you have GitHub integration set up.

### 5. Manual Publishing (if needed)

```bash
# Create distribution
composer archive --format=zip --dir=dist

# Or publish directly to Packagist
composer publish
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support

- **Documentation**: [GitHub Wiki](https://github.com/awalhadi/addressable/wiki)
- **Issues**: [GitHub Issues](https://github.com/awalhadi/addressable/issues)
- **Discussions**: [GitHub Discussions](https://github.com/awalhadi/addressable/discussions)

## 🙏 Acknowledgments

- Laravel team for the amazing framework
- Pest team for the testing framework
- All contributors who helped improve this package

---

**Made with ❤️ by the Laravel community**
