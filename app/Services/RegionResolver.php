<?php

namespace App\Services;

use App\Models\Wilayah;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RegionResolver
{
    private const BASE_URL = 'https://emsifa.github.io/api-wilayah-indonesia/api';

    /**
     * Resolve region detail based on village ID.
     */
    public function resolve(?string $villageId): array
    {
        if (blank($villageId)) {
            return [];
        }

        $villageId = preg_replace('/[^0-9]/', '', (string) $villageId);

        $village = $this->fetch("village/{$villageId}.json");

        if ($village) {
            $districtId = $village['district_id'] ?? null;
            $district = $districtId ? $this->fetch("district/{$districtId}.json") : null;
            $regencyId = $district['regency_id'] ?? ($districtId ? substr($districtId, 0, 4) : null);
            $regency = $regencyId ? $this->fetch("regency/{$regencyId}.json") : null;
            $provinceId = $regency['province_id'] ?? ($regencyId ? substr($regencyId, 0, 2) : null);
            $province = $provinceId ? $this->fetch("province/{$provinceId}.json") : null;
        } else {
            $districtId = substr($villageId, 0, 7);
            $district = null;
            $regencyId = substr($villageId, 0, 4);
            $provinceId = substr($villageId, 0, 2);
            $regency = $this->lookupLocalWilayah($regencyId);
            $province = $this->lookupLocalWilayah($provinceId);
        }

        return [
            'village_id' => $villageId,
            'village_name' => $village['name'] ?? null,
            'district_id' => $district['id'] ?? $districtId,
            'district_name' => $district['name'] ?? null,
            'regency_id' => $regency['id'] ?? $regencyId,
            'regency_name' => $regency['name'] ?? null,
            'province_id' => $province['id'] ?? $provinceId,
            'province_name' => $province['name'] ?? null,
        ];
    }

    private function fetch(string $path): ?array
    {
        return Cache::remember("region:{$path}", now()->addDay(), function () use ($path) {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get(self::BASE_URL . '/' . $path);

            return $response->ok() ? $response->json() : null;
        });
    }

    private function lookupLocalWilayah(?string $numericCode): ?array
    {
        if (!$numericCode) {
            return null;
        }

        $dotCode = $this->toDotCode($numericCode);

        $wilayah = Cache::remember("wilayah:{$dotCode}", now()->addDay(), function () use ($dotCode) {
            return Wilayah::where('kode', $dotCode)->first();
        });

        if (!$wilayah) {
            return null;
        }

        return [
            'id' => $numericCode,
            'name' => $wilayah->nama,
        ];
    }

    private function toDotCode(string $numeric): string
    {
        if (strlen($numeric) <= 2) {
            return $numeric;
        }

        return substr($numeric, 0, 2) . '.' . substr($numeric, 2);
    }
}
