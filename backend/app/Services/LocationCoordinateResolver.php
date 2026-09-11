<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationCoordinateResolver
{
    private function getNominatimUrl(): string
    {
        return config('services.geocoding.nominatim_url');
    }

    public function resolveAddress(array $address): ?array
    {
        if (isset($address['latitude'], $address['longitude']) && $address['latitude'] !== null && $address['longitude'] !== null) {
            return [
                'latitude' => (float) $address['latitude'],
                'longitude' => (float) $address['longitude'],
            ];
        }

        return $this->resolve(
            (string) ($address['city'] ?? $address['location'] ?? ''),
            (string) ($address['state'] ?? ''),
            (string) ($address['country'] ?? $address['country_code'] ?? '')
        );
    }

    public function resolveLocation(string $location, ?string $countryCode = null): ?array
    {
        $parts = array_map('trim', explode(',', $location));

        return $this->resolve($parts[0] ?? $location, $parts[1] ?? '', $countryCode ?: ($parts[2] ?? ''));
    }

    public function resolve(string $city, string $state = '', string $country = ''): ?array
    {
        $cityKey = $this->key($city);
        $stateKey = $this->key($state);
        $countryCode = $this->countryCode($country);

        $cityMatches = [
            'memphis|tn|US' => [35.1495, -90.0490],
            'austin|tx|US' => [30.2672, -97.7431],
            'little rock|ar|US' => [34.7465, -92.2896],
            'dallas|tx|US' => [32.7767, -96.7970],
            'new york|ny|US' => [40.7128, -74.0060],
            'los angeles|ca|US' => [34.0522, -118.2437],
            'chicago|il|US' => [41.8781, -87.6298],
            'miami|fl|US' => [25.7617, -80.1918],
            'toronto|on|CA' => [43.6532, -79.3832],
            'vancouver|bc|CA' => [49.2827, -123.1207],
            'london||GB' => [51.5072, -0.1276],
            'berlin||DE' => [52.5200, 13.4050],
            'paris||FR' => [48.8566, 2.3522],
            'lagos||NG' => [6.5244, 3.3792],
            'dubai||AE' => [25.2048, 55.2708],
            'mumbai||IN' => [19.0760, 72.8777],
            'delhi||IN' => [28.6139, 77.2090],
            'shanghai||CN' => [31.2304, 121.4737],
            'tokyo||JP' => [35.6762, 139.6503],
            'sao paulo||BR' => [-23.5558, -46.6396],
            'johannesburg||ZA' => [-26.2041, 28.0473],
        ];

        foreach ([
            "{$cityKey}|{$stateKey}|{$countryCode}",
            "{$cityKey}||{$countryCode}",
            "{$cityKey}|{$stateKey}|",
            "{$cityKey}||",
        ] as $candidate) {
            if (isset($cityMatches[$candidate])) {
                return $this->point($cityMatches[$candidate]);
            }
        }

        foreach ($cityMatches as $key => $coords) {
            $parts = explode('|', $key);
            if (($parts[0] ?? '') === $cityKey && ($parts[2] ?? '') === $countryCode) {
                return $this->point($coords);
            }
        }

        $countryCentroids = [
            'US' => [39.8283, -98.5795],
            'CA' => [56.1304, -106.3468],
            'GB' => [55.3781, -3.4360],
            'DE' => [51.1657, 10.4515],
            'FR' => [46.2276, 2.2137],
            'NG' => [9.0820, 8.6753],
            'AE' => [23.4241, 53.8478],
            'IN' => [20.5937, 78.9629],
            'CN' => [35.8617, 104.1954],
            'JP' => [36.2048, 138.2529],
            'BR' => [-14.2350, -51.9253],
            'ZA' => [-30.5595, 22.9375],
        ];

        if (isset($countryCentroids[$countryCode])) {
            return $this->point($countryCentroids[$countryCode]);
        }

        if ($cityKey !== '') {
            return $this->resolveViaNominatim($city, $state, $country);
        }

        return null;
    }

    private function resolveViaNominatim(string $city, string $state, string $country): ?array
    {
        $queryParts = array_filter([$city, $state, $country]);
        $query = implode(', ', $queryParts);

        if (empty($query)) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => config('services.geocoding.user_agent'),
                    'Accept-Language' => 'en',
                ])
                ->get($this->getNominatimUrl(), [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 0,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && count($data) > 0 && isset($data[0]['lat'], $data[0]['lon'])) {
                    return [
                        'latitude' => (float) $data[0]['lat'],
                        'longitude' => (float) $data[0]['lon'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Nominatim geocoding failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function point(array $coords): array
    {
        return ['latitude' => $coords[0], 'longitude' => $coords[1]];
    }

    private function key(string $value): string
    {
        return strtolower(trim($value));
    }

    private function countryCode(string $country): string
    {
        $normalized = strtoupper(trim($country));
        $aliases = [
            'UNITED STATES' => 'US',
            'USA' => 'US',
            'CANADA' => 'CA',
            'UNITED KINGDOM' => 'GB',
            'UK' => 'GB',
            'GERMANY' => 'DE',
            'FRANCE' => 'FR',
            'NIGERIA' => 'NG',
            'UNITED ARAB EMIRATES' => 'AE',
            'UAE' => 'AE',
            'INDIA' => 'IN',
            'CHINA' => 'CN',
            'JAPAN' => 'JP',
            'BRAZIL' => 'BR',
            'SOUTH AFRICA' => 'ZA',
        ];

        return $aliases[$normalized] ?? substr($normalized, 0, 2);
    }
}
