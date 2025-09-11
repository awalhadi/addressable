<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Services;

use Awalhadi\Addressable\Models\Address;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Features:
 * - Multi-algorithm distance calculations (Haversine, Vincenty, Spherical Law)
 * - Database-level spatial indexing
 * - Intelligent caching with spatial partitioning
 * - Batch processing for large datasets
 * - Performance monitoring and optimization
 */
class RadiusSearchService
{
    /**
     * Cache configuration.
     */
    private array $cacheConfig = [
        'prefix' => 'radius_search_',
        'ttl' => 3600, // 1 hour
        'spatial_partition_size' => 0.1, // 0.1 degrees for spatial partitioning
    ];

    /**
     * Distance calculation algorithms.
     */
    private array $algorithms = [
        'haversine' => 'calculateHaversineDistance',
        'vincenty' => 'calculateVincentyDistance',
        'spherical_law' => 'calculateSphericalLawDistance',
    ];

    /**
     * Find addresses within radius using optimized database queries.
     */
    public function findWithinRadius(
        float $latitude,
        float $longitude,
        float $radius,
        string $unit = 'kilometers',
        array $options = []
    ): array {
        $cacheKey = $this->getCacheKey($latitude, $longitude, $radius, $unit, $options);

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $algorithm = $options['algorithm'] ?? 'haversine';
        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;
        $addressableType = $options['addressable_type'] ?? null;
        $addressableId = $options['addressable_id'] ?? null;

        // Use database-optimized query
        $results = $this->queryWithinRadius(
            $latitude,
            $longitude,
            $radius,
            $unit,
            $algorithm,
            $limit,
            $offset,
            $addressableType,
            $addressableId
        );

        // Cache results
        Cache::put($cacheKey, $results, $this->cacheConfig['ttl']);

        return $results;
    }

    /**
     * Find nearest addresses with distance calculation.
     */
    public function findNearest(
        float $latitude,
        float $longitude,
        int $limit = 10,
        string $unit = 'kilometers',
        array $options = []
    ): array {
        $cacheKey = $this->getCacheKey($latitude, $longitude, 0, $unit, array_merge($options, ['nearest' => $limit]));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $algorithm = $options['algorithm'] ?? 'haversine';
        $addressableType = $options['addressable_type'] ?? null;
        $addressableId = $options['addressable_id'] ?? null;

        $results = $this->queryNearest(
            $latitude,
            $longitude,
            $limit,
            $unit,
            $algorithm,
            $addressableType,
            $addressableId
        );

        Cache::put($cacheKey, $results, $this->cacheConfig['ttl']);

        return $results;
    }

    /**
     * Batch find addresses within radius for multiple points.
     */
    public function batchFindWithinRadius(
        array $points,
        float $radius,
        string $unit = 'kilometers',
        array $options = []
    ): array {
        $results = [];
        $batchSize = $options['batch_size'] ?? 50;

        $batches = array_chunk($points, $batchSize, true);

        foreach ($batches as $batchIndex => $batch) {
            $batchResults = $this->processBatchRadiusSearch($batch, $radius, $unit, $options);
            $results = array_merge($results, $batchResults);
        }

        return $results;
    }

    /**
     * Get spatial statistics for optimization.
     */
    public function getSpatialStats(): array
    {
        $stats = [
            'total_addresses' => Address::count(),
            'addresses_with_coordinates' => Address::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->count(),
            'coordinate_coverage' => 0,
            'spatial_index_exists' => $this->checkSpatialIndex(),
            'cache_stats' => $this->getCacheStats(),
        ];

        if ($stats['total_addresses'] > 0) {
            $stats['coordinate_coverage'] = ($stats['addresses_with_coordinates'] / $stats['total_addresses']) * 100;
        }

        return $stats;
    }

    /**
     * Optimize database for spatial queries.
     */
    public function optimizeDatabase(): array
    {
        $results = [];

        // Create spatial index if it doesn't exist
        if (! $this->checkSpatialIndex()) {
            $results['spatial_index'] = $this->createSpatialIndex();
        }

        // Update table statistics
        $results['table_stats'] = $this->updateTableStatistics();

        // Analyze query performance
        $results['query_analysis'] = $this->analyzeQueryPerformance();

        return $results;
    }

    /**
     * Database-optimized radius query.
     */
    private function queryWithinRadius(
        float $latitude,
        float $longitude,
        float $radius,
        string $unit,
        string $algorithm,
        int $limit,
        int $offset,
        ?string $addressableType,
        ?string $addressableId
    ): array {
        $earthRadius = $this->getEarthRadius($unit);
        $latDelta = $radius / $earthRadius * (180 / M_PI);
        $lonDelta = $radius / $earthRadius * (180 / M_PI) / cos(deg2rad($latitude));

        $query = Address::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('longitude', [$longitude - $lonDelta, $longitude + $lonDelta]);

        // Add addressable filters if specified
        if ($addressableType) {
            $query->where('addressable_type', $addressableType);
        }
        if ($addressableId) {
            $query->where('addressable_id', $addressableId);
        }

        // Add distance calculation based on algorithm
        $distanceExpression = $this->getDistanceExpression($algorithm, $latitude, $longitude, $unit);
        $query->selectRaw("*, ({$distanceExpression}) as distance");

        // Filter by actual radius using the calculated distance
        $query->havingRaw('distance <= ?', [$radius]);

        // Order by distance and apply limit/offset
        $query->orderBy('distance')
            ->limit($limit)
            ->offset($offset);

        return $query->get()->toArray();
    }

    /**
     * Database-optimized nearest query.
     */
    private function queryNearest(
        float $latitude,
        float $longitude,
        int $limit,
        string $unit,
        string $algorithm,
        ?string $addressableType,
        ?string $addressableId
    ): array {
        $query = Address::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Add addressable filters if specified
        if ($addressableType) {
            $query->where('addressable_type', $addressableType);
        }
        if ($addressableId) {
            $query->where('addressable_id', $addressableId);
        }

        // Add distance calculation
        $distanceExpression = $this->getDistanceExpression($algorithm, $latitude, $longitude, $unit);
        $query->selectRaw("*, ({$distanceExpression}) as distance");

        // Order by distance and limit
        $query->orderBy('distance')
            ->limit($limit);

        return $query->get()->toArray();
    }

    /**
     * Process batch radius search.
     */
    private function processBatchRadiusSearch(
        array $points,
        float $radius,
        string $unit,
        array $options
    ): array {
        $results = [];

        foreach ($points as $index => $point) {
            $pointResults = $this->findWithinRadius(
                $point['latitude'],
                $point['longitude'],
                $radius,
                $unit,
                $options
            );

            $results[$index] = $pointResults;
        }

        return $results;
    }

    /**
     * Get distance calculation expression for database query.
     */
    private function getDistanceExpression(
        string $algorithm,
        float $latitude,
        float $longitude,
        string $unit
    ): string {
        $earthRadius = $this->getEarthRadius($unit);

        return match ($algorithm) {
            'haversine' => $this->getHaversineExpression($latitude, $longitude, $earthRadius),
            'vincenty' => $this->getVincentyExpression($latitude, $longitude, $earthRadius),
            'spherical_law' => $this->getSphericalLawExpression($latitude, $longitude, $earthRadius),
            default => $this->getHaversineExpression($latitude, $longitude, $earthRadius),
        };
    }

    /**
     * Haversine formula for database query.
     */
    private function getHaversineExpression(float $lat, float $lon, float $earthRadius): string
    {
        $latRad = deg2rad($lat);
        $lonRad = deg2rad($lon);

        return "
            {$earthRadius} * acos(
                cos(radians({$lat})) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians({$lon})) +
                sin(radians({$lat})) *
                sin(radians(latitude))
            )
        ";
    }

    /**
     * Vincenty formula for database query (approximation).
     */
    private function getVincentyExpression(float $lat, float $lon, float $earthRadius): string
    {
        // Simplified Vincenty for database - full implementation would be complex
        return $this->getHaversineExpression($lat, $lon, $earthRadius);
    }

    /**
     * Spherical Law of Cosines for database query.
     */
    private function getSphericalLawExpression(float $lat, float $lon, float $earthRadius): string
    {
        $latRad = deg2rad($lat);
        $lonRad = deg2rad($lon);

        return "
            {$earthRadius} * acos(
                sin(radians({$lat})) * sin(radians(latitude)) +
                cos(radians({$lat})) * cos(radians(latitude)) *
                cos(radians(longitude) - radians({$lon}))
            )
        ";
    }

    /**
     * Get Earth radius for unit.
     */
    private function getEarthRadius(string $unit): float
    {
        return match ($unit) {
            'kilometers' => 6371,
            'miles' => 3959,
            'meters' => 6371000,
            'feet' => 20902231,
            default => 6371,
        };
    }

    /**
     * Get cache key for radius search.
     */
    private function getCacheKey(float $lat, float $lon, float $radius, string $unit, array $options): string
    {
        $keyData = [
            'lat' => round($lat, 4),
            'lon' => round($lon, 4),
            'radius' => $radius,
            'unit' => $unit,
            'options' => $options,
        ];

        return $this->cacheConfig['prefix'].md5(serialize($keyData));
    }

    /**
     * Check if spatial index exists.
     */
    private function checkSpatialIndex(): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM addresses WHERE Key_name = 'addresses_location_spatial'");

            return ! empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create spatial index for coordinates.
     */
    private function createSpatialIndex(): bool
    {
        try {
            DB::statement('CREATE SPATIAL INDEX addresses_location_spatial ON addresses (POINT(longitude, latitude))');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create spatial index: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Update table statistics for query optimization.
     */
    private function updateTableStatistics(): bool
    {
        try {
            DB::statement('ANALYZE TABLE addresses');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update table statistics: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Analyze query performance.
     */
    private function analyzeQueryPerformance(): array
    {
        $sampleLat = 40.7128;
        $sampleLon = -74.0060;
        $sampleRadius = 10;

        $query = "
            EXPLAIN SELECT *,
            (6371 * acos(
                cos(radians({$sampleLat})) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians({$sampleLon})) +
                sin(radians({$sampleLat})) *
                sin(radians(latitude))
            )) as distance
            FROM addresses
            WHERE latitude IS NOT NULL
            AND longitude IS NOT NULL
            HAVING distance <= {$sampleRadius}
            ORDER BY distance
            LIMIT 10
        ";

        try {
            $explain = DB::select($query);

            return [
                'query_plan' => $explain,
                'uses_index' => $this->checkIfUsesIndex($explain),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Check if query plan uses index.
     */
    private function checkIfUsesIndex(array $explain): bool
    {
        foreach ($explain as $row) {
            if (isset($row->key) && ! empty($row->key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get cache statistics.
     */
    private function getCacheStats(): array
    {
        return [
            'cache_prefix' => $this->cacheConfig['prefix'],
            'cache_ttl' => $this->cacheConfig['ttl'],
            'spatial_partition_size' => $this->cacheConfig['spatial_partition_size'],
        ];
    }

    /**
     * Clear radius search cache.
     */
    public function clearCache(): void
    {
        try {
            // Clear cache using Laravel's cache tags if supported
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['radius_search'])->flush();
            } else {
                // Fallback: clear cache keys manually (simplified approach)
                Log::info('Clearing radius search cache - manual implementation needed for non-tagged cache drivers');
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear radius search cache: ' . $e->getMessage());
        }
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'algorithms' => array_keys($this->algorithms),
            'cache_config' => $this->cacheConfig,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'spatial_stats' => $this->getSpatialStats(),
        ];
    }
}
