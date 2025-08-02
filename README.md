# Awalhadi Addressable

Laravel Eloquent address management library. Manage address more than one model.

## Requirements

- PHP 7.4 or higher
- Laravel 6.0 or higher

## Installation

```bash
composer require awalhadi/addressable
```

## Quick Start

1. Publish the migration:

```bash
php artisan vendor:publish --provider="Awalhadi\Addressable\Providers\AddressableServiceProvider"
```

2. Run the migration:

```bash
php artisan migrate
```

3. Add the trait to your model:

```php
use Awalhadi\Addressable\Traits\Addressable;

class User extends Model
{
    use Addressable;

    // ... your model code
}
```

4. Use the address functionality:

```php
$user = User::find(1);

// Add an address
$user->addresses()->create([
    'type' => 'home',
    'address' => '123 Main St',
    'city' => 'New York',
    'state' => 'NY',
    'postal_code' => '10001',
    'country' => 'US',
]);

// Get addresses
$addresses = $user->addresses;
$primaryAddress = $user->addresses()->isPrimary()->first();
```

## Development

### Prerequisites

- PHP 7.4+
- Composer
- Git

### Setup

1. Clone the repository:

```bash
git clone <repository-url>
cd packages/awalhadi/addressable
```

2. Install dependencies:

```bash
composer install
```

3. Run tests:

```bash
# Run all tests
composer test

# Run tests in parallel
composer test:parallel

# Run tests with coverage
composer test:coverage

# Run specific test suite
./vendor/bin/pest --testsuite=Unit
./vendor/bin/pest --testsuite=Feature
./vendor/bin/pest --testsuite=Integration
```

4. Format code:

```bash
# Format code
composer format

# Check formatting
composer format:check
```

### Testing

The package uses Pest for testing with the following test suites:

- **Unit**: Tests for individual classes and methods
- **Feature**: Tests for features and integrations
- **Integration**: Tests for database and external service integrations

### Code Quality

- **PHPUnit/Pest**: Testing framework
- **Laravel Pint**: Code formatting
- **GitHub Actions**: CI/CD with multi-version matrix testing

### Supported Versions

| PHP Version | Laravel Version                 | Status |
| ----------- | ------------------------------- | ------ |
| 7.4         | 6._, 7._, 8._, 9._, 10._, 11._  | ✅     |
| 8.0         | 6._, 7._, 8._, 9._, 10._, 11._  | ✅     |
| 8.1         | 6._, 7._, 8._, 9._, 10._, 11._  | ✅     |
| 8.2         | 7._, 8._, 9._, 10._, 11._, 12._ | ✅     |
| 8.3         | 8._, 9._, 10._, 11._, 12.\*     | ✅     |
| 8.4         | 10._, 11._, 12.\*               | ✅     |

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
