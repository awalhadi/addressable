<?php

declare(strict_types=1);

namespace Awalhadi\Addressable\Traits;

trait HasSpatialOperations
{
    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
        string $unit = 'kilometers'
    ): float {
        $earthRadius = match ($unit) {
            'kilometers' => 6371,
            'miles' => 3959,
            'meters' => 6371000,
            default => 6371,
        };

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate distance using Vincenty formula (more accurate for long distances).
     */
    public function calculateDistanceVincenty(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
        string $unit = 'kilometers'
    ): float {
        $earthRadius = match ($unit) {
            'kilometers' => 6371,
            'miles' => 3959,
            'meters' => 6371000,
            default => 6371,
        };

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $a = 6378137.0; // WGS84 semi-major axis
        $b = 6356752.314245; // WGS84 semi-minor axis
        $f = 1 / 298.257223563; // WGS84 flattening

        $L = $lon2 - $lon1;
        $U1 = atan((1 - $f) * tan($lat1));
        $U2 = atan((1 - $f) * tan($lat2));
        $sinU1 = sin($U1);
        $cosU1 = cos($U1);
        $sinU2 = sin($U2);
        $cosU2 = cos($U2);

        $lambda = $L;
        $iterLimit = 100;

        do {
            $sinLambda = sin($lambda);
            $cosLambda = cos($lambda);
            $sinSigma = sqrt(($cosU2 * $sinLambda) * ($cosU2 * $sinLambda) +
                ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) *
                ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda));

            if ($sinSigma == 0) {
                return 0;
            }

            $cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
            $sigma = atan2($sinSigma, $cosSigma);
            $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
            $cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
            $cos2SigmaM = $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;

            if (is_nan($cos2SigmaM)) {
                $cos2SigmaM = 0;
            }

            $C = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
            $lambdaP = $lambda;
            $lambda = $L + (1 - $C) * $f * $sinAlpha *
                ($sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));
        } while (abs($lambda - $lambdaP) > 1e-12 && --$iterLimit > 0);

        if ($iterLimit == 0) {
            return 0;
        }

        $uSq = $cosSqAlpha * ($a * $a - $b * $b) / ($b * $b);
        $A = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
        $B = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
        $deltaSigma = $B * $sinSigma * ($cos2SigmaM + $B / 4 * ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) -
            $B / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));

        $s = $b * $A * ($sigma - $deltaSigma);

        return $s / 1000; // Convert to kilometers
    }

    /**
     * Check if a point is within a polygon (geofencing).
     */
    public function isPointInPolygon(float $lat, float $lon, array $polygon): bool
    {
        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            if ((($polygon[$i][1] > $lat) != ($polygon[$j][1] > $lat)) &&
                ($lon < ($polygon[$j][0] - $polygon[$i][0]) * ($lat - $polygon[$i][1]) /
                    ($polygon[$j][1] - $polygon[$i][1]) + $polygon[$i][0])
            ) {
                $inside = ! $inside;
            }
            $j = $i;
        }

        return $inside;
    }

    /**
     * Create a bounding box for a given center point and radius.
     */
    public function createBoundingBox(float $lat, float $lon, float $radius, string $unit = 'kilometers'): array
    {
        $earthRadius = match ($unit) {
            'kilometers' => 6371,
            'miles' => 3959,
            'meters' => 6371000,
            default => 6371,
        };

        $latDelta = $radius / $earthRadius * (180 / M_PI);
        $lonDelta = $radius / $earthRadius * (180 / M_PI) / cos(deg2rad($lat));

        return [
            'min_lat' => $lat - $latDelta,
            'max_lat' => $lat + $latDelta,
            'min_lon' => $lon - $lonDelta,
            'max_lon' => $lon + $lonDelta,
        ];
    }

    /**
     * Convert decimal degrees to degrees, minutes, seconds format.
     */
    public function decimalToDMS(float $decimal): array
    {
        $degrees = floor($decimal);
        $minutes = floor(($decimal - $degrees) * 60);
        $seconds = round((($decimal - $degrees) * 60 - $minutes) * 60, 2);

        return [
            'degrees' => $degrees,
            'minutes' => $minutes,
            'seconds' => $seconds,
        ];
    }

    /**
     * Convert degrees, minutes, seconds to decimal degrees.
     */
    public function dmsToDecimal(int $degrees, int $minutes, float $seconds): float
    {
        return $degrees + ($minutes / 60) + ($seconds / 3600);
    }

    /**
     * Calculate the midpoint between two coordinates.
     */
    public function calculateMidpoint(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $Bx = cos($lat2) * cos($lon2 - $lon1);
        $By = cos($lat2) * sin($lon2 - $lon1);

        $midLat = atan2(
            sin($lat1) + sin($lat2),
            sqrt((cos($lat1) + $Bx) * (cos($lat1) + $Bx) + $By * $By)
        );

        $midLon = $lon1 + atan2($By, cos($lat1) + $Bx);

        return [
            'latitude' => rad2deg($midLat),
            'longitude' => rad2deg($midLon),
        ];
    }
}
