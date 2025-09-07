# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-01-15

### 🚀 Major Release - Complete Modernization

This is a major release that completely modernizes the package with new features, improved architecture, and enhanced functionality.

### ✨ Added

#### Core Features

- **UUID Support**: Primary keys now use UUIDs for better scalability and security
- **Soft Deletes**: Added soft delete functionality for data preservation
- **Enhanced Database Schema**: Modernized migration with new fields and indexes
- **Spatial Operations**: Complete trait for distance calculations, geofencing, and spatial queries
- **Address Validation**: Comprehensive validation for postal codes, phone numbers, emails, and country codes
- **Smart Caching**: Multi-level caching system for addresses, geocoding, and validation results
- **Geocoding Service**: Support for Google Maps, OpenStreetMap, and HERE APIs
- **Validation Service**: External address verification and validation services

#### New Model Fields

- `type`: Address type (home, work, billing, shipping, general)
- `label`: Custom address label
- `given_name` & `family_name`: Separate name fields
- `organization`: Company or organization name
- `phone`: Phone number with validation
- `email`: Email address with validation
- `street_2`: Secondary address line
- `neighborhood` & `district`: Additional location details
- `is_verified`: Address verification status
- `metadata`: JSON field for additional data
- `verified_at`: Verification timestamp

#### New Model Features

- **Accessors**: `full_name`, `full_address`, `country_name`, `formatted_phone`, `masked_phone`, `masked_email`
- **Scopes**: `ofType()`, `inCountry()`, `inCity()`, `inState()`, `inPostalCode()`, `isVerified()`, `withCoordinates()`, `recent()`
- **Spatial Methods**: `distanceTo()`, `isWithinRadius()`, `calculateDistance()`, `calculateDistanceVincenty()`, `isPointInPolygon()`, `createBoundingBox()`, `decimalToDMS()`, `dmsToDecimal()`, `calculateMidpoint()`
- **Validation Methods**: `isValid()`, `getValidationErrors()`, `validatePostalCode()`, `validatePhoneNumber()`, `validateEmail()`, `validateCountryCode()`, `formatPostalCode()`, `formatPhoneNumber()`
- **Geocoding Methods**: `geocode()`, `reverseGeocode()`, `hasCoordinates()`, `isComplete()`
- **Caching Methods**: `cacheAddressData()`, `getCachedAddressData()`, `clearAddressCache()`, `cacheGeocodingResult()`, `getCachedGeocodingResult()`, `clearAllRelatedCaches()`, `warmCache()`

#### Enhanced Addressable Trait

- **Bulk Operations**: `createManyAddresses()`, `updateManyAddresses()`
- **Query Methods**: `primaryAddress()`, `billingAddress()`, `shippingAddress()`, `hasAddresses()`, `hasPrimaryAddress()`, `getAddressesByType()`, `getAddressesInCountry()`, `getAddressesWithinRadius()`
- **Cache Management**: `warmAddressCache()`

#### Configuration System

- **Comprehensive Config**: Complete configuration file with all package settings
- **Environment Variables**: Support for environment-based configuration
- **Flexible Settings**: Database, types, geocoding, validation, caching, spatial, security, performance, events, API, monitoring

#### Development Tools

- **Modern Testing**: Pest testing framework with comprehensive test suites
- **Code Quality**: Laravel Pint for code formatting
- **CI/CD**: GitHub Actions with multi-version matrix testing
- **Factory**: Enhanced AddressFactory with realistic test data
- **Documentation**: Complete API reference and usage examples

### 🔄 Changed

#### Database Schema

- **Primary Key**: Changed from auto-increment to UUID
- **Field Names**: Updated `lat`/`lng` to `latitude`/`longitude` for clarity
- **Precision**: Increased coordinate precision for better accuracy
- **Indexes**: Added comprehensive indexing for performance
- **Constraints**: Added proper foreign key and unique constraints

#### Model Architecture

- **Traits**: Replaced single trait with specialized traits for different concerns
- **Events**: Added model lifecycle events (created, updated, deleted)
- **Casting**: Enhanced type casting for better data handling
- **Relationships**: Improved polymorphic relationship handling

#### Package Structure

- **PSR-4 Compliance**: Updated autoloading to follow PSR-4 standards
- **Directory Organization**: Better organized source code structure
- **Service Layer**: Added dedicated services for geocoding and validation
- **Event System**: Implemented Laravel event system integration

#### Dependencies

- **PHP Support**: Extended to PHP 7.4-8.4+
- **Laravel Support**: Extended to Laravel 6.0-12.0+
- **Testing**: Updated to Pest 2.0+ and PHPUnit 10.0+
- **Code Quality**: Added Laravel Pint and modern development tools

### 🗑️ Removed

#### Legacy Code

- **Old Migration**: Removed legacy migration file
- **Deprecated Methods**: Removed outdated methods and properties
- **Unused Dependencies**: Cleaned up unnecessary package dependencies
- **Legacy Traits**: Removed old trait implementations

#### Breaking Changes

- **Field Names**: `lat`/`lng` → `latitude`/`longitude`
- **Primary Key**: Auto-increment → UUID
- **Trait Methods**: Some method signatures have changed
- **Configuration**: Old config structure is no longer supported

### 🔧 Fixed

#### Issues

- **Dependency Conflicts**: Resolved complex dependency conflicts
- **Test Failures**: Fixed all test failures and improved test coverage
- **Code Quality**: Addressed all linting and formatting issues
- **Performance**: Optimized database queries and caching

#### Compatibility

- **Laravel Versions**: Ensured compatibility across Laravel 6-12
- **PHP Versions**: Verified compatibility across PHP 7.4-8.4
- **Database Support**: Added support for MySQL, PostgreSQL, and SQLite

### 📚 Documentation

#### New Documentation

- **Complete README**: Comprehensive installation and usage guide
- **API Reference**: Detailed documentation of all methods and properties
- **Configuration Guide**: Complete configuration options documentation
- **Examples**: Extensive code examples for all features
- **Development Guide**: Setup and contribution guidelines

### 🧪 Testing

#### Test Coverage

- **Unit Tests**: Complete unit test coverage for all classes
- **Feature Tests**: Integration tests for all features
- **Database Tests**: Comprehensive database operation tests
- **Spatial Tests**: Tests for all spatial operations
- **Validation Tests**: Tests for all validation features

#### Test Infrastructure

- **Pest Framework**: Modern testing with Pest
- **Test Suites**: Organized into Unit, Feature, and Integration suites
- **Factory**: Realistic test data generation
- **CI/CD**: Automated testing across multiple PHP and Laravel versions

### 🔒 Security

#### New Security Features

- **Data Masking**: Privacy protection for sensitive data
- **GDPR Compliance**: Data export and deletion capabilities
- **Encryption**: Field-level encryption support
- **Validation**: Comprehensive input validation

### ⚡ Performance

#### Optimizations

- **Caching**: Multi-level caching system
- **Indexing**: Comprehensive database indexing
- **Query Optimization**: N+1 prevention and eager loading
- **Bulk Operations**: Efficient mass operations
- **Spatial Indexing**: Optimized spatial queries

## [1.0.0] - 2022-11-29

### ✨ Initial Release

- Basic polymorphic address management
- Simple address creation and retrieval
- Basic Laravel integration
- Initial documentation

---

## Migration Guide

### From 1.x to 2.0

1. **Update Dependencies**

   ```bash
   composer require awalhadi/addressable:^2.0
   ```

2. **Run New Migration**

   ```bash
   php artisan migrate
   ```

3. **Update Model Usage**

   - Change `lat`/`lng` to `latitude`/`longitude`
   - Update field names in your code
   - Review new configuration options

4. **Update Tests**

   - Update test expectations for new field names
   - Add tests for new features
   - Update factory usage

5. **Review Configuration**
   - Publish new configuration file
   - Review and update settings
   - Set up environment variables

### Breaking Changes

- **Primary Key**: Now uses UUID instead of auto-increment
- **Field Names**: `lat`/`lng` → `latitude`/`longitude`
- **Trait Methods**: Some method signatures have changed
- **Configuration**: New configuration structure

### New Features to Explore

- **Spatial Operations**: Distance calculations and geofencing
- **Address Validation**: Comprehensive validation features
- **Smart Caching**: Performance optimization
- **Bulk Operations**: Efficient mass operations
- **Enhanced Configuration**: Flexible customization options

---

## Support

For support with migration or any issues, please:

1. Check the [documentation](README.md)
2. Search [existing issues](https://github.com/awalhadi/addressable/issues)
3. Create a [new issue](https://github.com/awalhadi/addressable/issues/new) if needed
4. Join [discussions](https://github.com/awalhadi/addressable/discussions) for questions
