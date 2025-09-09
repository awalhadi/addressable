# Addressable Package Optimization Guide

This guide helps you optimize the Addressable package for better performance, especially regarding autoload times and country data management.

## Quick Start

After installing the package, run the optimization command:

```bash
php artisan addressable:optimize
```

This will:
- Clear old caches
- Warm up country data cache
- Validate data integrity
- Optimize performance

## Country Data Management

### Internal Country Data

The package now uses its own internal country data instead of external dependencies:

- **File**: `src/data/countries.json`
- **Format**: Optimized JSON with country code as key
- **Fields**: name, code, dial_code, currency, continent
- **Performance**: Cached in memory and Laravel cache

### Benefits

1. **Faster Autoload**: No external country package dependency
2. **Better Performance**: Optimized data structure and caching
3. **Reduced Memory**: Only loads needed data
4. **Customizable**: Easy to modify country data

## Performance Optimizations

### 1. Caching Strategy

```php
// Countries are cached automatically
$country = country('US'); // Cached result

// Manual cache management
countries()->clearCache();
countries()->refreshCache();
```

### 2. Radius Search Optimization

The package now uses a two-tier approach for radius searches:

```php
// Fast bounding box search (approximate)
$addresses = Address::within(10, 'kilometers', $lat, $lng)->get();

// Precise Haversine calculation (slower but exact)
$addresses = Address::withinExact(10, 'kilometers', $lat, $lng)->get();
```

### 3. Popular Countries Preloading

Configure popular countries for your application:

```php
// config/addressable.php
'countries' => [
    'preload_popular' => true,
    'popular_countries' => [
        'US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES'
    ],
],
```

## Configuration Options

### Cache Settings

```php
'caching' => [
    'enabled' => true,
    'ttl' => [
        'countries' => 86400, // 24 hours
    ],
],
```

### Country Settings

```php
'countries' => [
    'cache_enabled' => true,
    'preload_popular' => true,
    'popular_countries' => ['US', 'GB', 'CA'], // Customize as needed
],
```

## Commands

### Optimize Package

```bash
# Full optimization
php artisan addressable:optimize

# Clear caches only
php artisan addressable:optimize --clear-cache

# Warm caches only
php artisan addressable:optimize --warm-cache

# Optimize countries only
php artisan addressable:optimize --countries
```

```php

$country = country('US');
$name = $country->getName();
```

### After (internal)
```php
// Same API, better performance
$country = country('US');
$name = $country->getName();

// Or use the service directly
$name = countries()->getName('US');
```

## Performance Tips

### 1. Use Appropriate Search Methods

```php
// For approximate searches (faster)
$addresses = $model->getAddressesWithinRadius($lat, $lng, 10);

// For exact searches (slower but precise)
$addresses = $model->getAddressesWithinExactRadius($lat, $lng, 10);
```

### 2. Optimize Database Queries

```php
// Add indexes for better performance
Schema::table('addresses', function (Blueprint $table) {
    $table->index(['latitude', 'longitude']);
    $table->index('country_code');
});
```

### 3. Use Caching Effectively

```php
// Cache expensive operations
$addresses = Cache::remember('user_addresses_' . $userId, 3600, function () use ($userId) {
    return User::find($userId)->addresses()->with('country')->get();
});
```

## Monitoring Performance

### Enable Monitoring

```php
'monitoring' => [
    'enabled' => true,
    'metrics' => [
        'query_performance' => true,
        'spatial_operations' => true,
    ],
],
```


## Troubleshooting

### Slow Autoload Times

1. Run the optimization command:
   ```bash
   php artisan addressable:optimize
   ```

2. Check if caching is enabled:
   ```php
   config('addressable.caching.enabled') // Should be true
   ```

3. Verify cache driver performance:
   ```bash
   php artisan cache:clear
   php artisan config:cache
   ```

### Country Data Issues

1. Validate data integrity:
   ```bash
   php artisan addressable:optimize --countries
   ```

2. Refresh country cache:
   ```php
   countries()->refreshCache();
   ```

3. Check data file exists:
   ```bash
   ls -la vendor/awalhadi/addressable/src/data/countries.json
   ```

## Best Practices

1. **Always run optimization after installation**
2. **Use appropriate search methods for your use case**
3. **Configure popular countries for your region**
4. **Monitor performance in production**
5. **Keep country data updated**
6. **Use database indexes for spatial queries**

## Support

For performance issues or optimization questions, please check:

1. This optimization guide
2. Package documentation
3. GitHub issues
4. Configuration examples
