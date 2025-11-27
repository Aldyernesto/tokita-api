<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RegionController extends Controller
{
    public function getProvinces()
    {
        return Wilayah::where('kode', '32')->get();
    }

    public function getCities()
    {
        return Wilayah::where('kode', '32.05')->get();
    }

    public function getDistricts(string $city_kode)
    {
        $cacheKey = "districts:{$city_kode}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($city_kode) {
            $regencyId = $this->normalizeCode($city_kode, 4);

            $response = Http::timeout(10)
                ->acceptJson()
                ->get("https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$regencyId}.json");

            if ($response->failed()) {
                abort(502, 'Gagal mengambil data kecamatan. Coba lagi beberapa saat.');
            }

            return collect($response->json() ?? [])->map(function (array $district) {
                return [
                    'kode' => $this->formatDistrictCode($district['id']),
                    'nama' => Str::title(Str::lower($district['name'])),
                ];
            });
        });
    }

    public function getVillages(string $district_kode)
    {
        $cacheKey = "villages:{$district_kode}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($district_kode) {
            $districtId = $this->normalizeCode($district_kode, 7);

            $response = Http::timeout(10)
                ->acceptJson()
                ->get("https://emsifa.github.io/api-wilayah-indonesia/api/villages/{$districtId}.json");

            if ($response->failed()) {
                abort(502, 'Gagal mengambil data desa/kelurahan. Coba lagi beberapa saat.');
            }

            return collect($response->json() ?? [])->map(function (array $village) {
                return [
                    'kode' => $this->formatVillageCode($village['id']),
                    'nama' => Str::title(Str::lower($village['name'])),
                ];
            });
        });
    }

    private function normalizeCode(string $code, int $expectedLength): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $code) ?? '';

        if (strlen($normalized) < $expectedLength) {
            $normalized = str_pad($normalized, $expectedLength, '0', STR_PAD_RIGHT);
        }

        return substr($normalized, 0, $expectedLength);
    }

    private function formatDistrictCode(string $rawId): string
    {
        $digits = str_pad(preg_replace('/[^0-9]/', '', $rawId) ?? '', 7, '0', STR_PAD_LEFT);

        return substr($digits, 0, 2) . '.' .
            substr($digits, 2, 2) . '.' .
            substr($digits, 4, 3);
    }

    private function formatVillageCode(string $rawId): string
    {
        $digits = str_pad(preg_replace('/[^0-9]/', '', $rawId) ?? '', 10, '0', STR_PAD_LEFT);

        return substr($digits, 0, 2) . '.' .
            substr($digits, 2, 2) . '.' .
            substr($digits, 4, 3) . '.' .
            substr($digits, 7, 3);
    }
}
