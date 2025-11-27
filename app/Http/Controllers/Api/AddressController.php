<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\RegionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class AddressController extends Controller
{
    public function __construct(
        private readonly RegionResolver $regionResolver
    ) {
    }

    public function index(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Address $address) => $this->formatAddress($address));

        $isEmpty = $addresses->isEmpty();

        return response()->json([
            'message' => $isEmpty ? 'Belum ada alamat.' : 'Daftar alamat.',
            'data' => $addresses,
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $payload = $this->payloadFromValidated($validator->validated(), Auth::id());

        if ($payload['is_default']) {
            $this->unsetOtherPrimaries($payload['user_id']);
        }

        $address = Address::create($payload);

        return response()->json([
            'message' => 'Alamat berhasil ditambahkan.',
            'data' => $this->formatAddress($address->fresh()),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $address = Address::where('user_id', Auth::id())->findOrFail($id);
        $payload = $this->payloadFromValidated($validator->validated(), $address->user_id);

        if ($payload['is_default']) {
            $this->unsetOtherPrimaries($address->user_id, $address->id);
        }

        $address->update($payload);

        return response()->json([
            'message' => 'Alamat berhasil diperbarui.',
            'data' => $this->formatAddress($address->fresh()),
        ]);
    }

    public function destroy(int $id)
    {
        $address = Address::where('user_id', Auth::id())->findOrFail($id);
        $address->delete();

        return response()->json([
            'message' => 'Alamat berhasil dihapus.',
            'data' => null,
        ]);
    }

    private function makeValidator(Request $request): ValidationValidator
    {
        return Validator::make($request->all(), [
            'label' => ['required', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line' => ['required', 'string'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'is_primary' => ['required', 'boolean'],
            'village_id' => ['nullable', 'string'],
            'street_name' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ], [], [
            'phone' => 'nomor telepon',
        ]);
    }

    private function payloadFromValidated(array $validated, int $userId): array
    {
        $payload = [
            'user_id' => $userId,
            'label' => $validated['label'],
            'recipient_name' => $validated['recipient_name'],
            'phone_number' => $validated['phone'],
            'address_line' => $validated['address_line'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'postal_code' => $validated['postal_code'],
            'is_default' => (bool) ($validated['is_primary'] ?? false),
            'village_id' => $validated['village_id'] ?? null,
            'street_name' => $validated['street_name'] ?? null,
            'rt' => $validated['rt'] ?? null,
            'rw' => $validated['rw'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ];

        $region = $payload['village_id']
            ? $this->regionResolver->resolve((string) $payload['village_id'])
            : [];

        if (blank($payload['city'])) {
            $payload['city'] = $region['regency_name'] ?? null;
        }

        if (blank($payload['province'])) {
            $payload['province'] = $region['province_name'] ?? null;
        }

        if (blank($payload['address_line'])) {
            $payload['address_line'] = $this->buildAddressLine($payload, $region);
        }

        return $payload;
    }

    private function unsetOtherPrimaries(int $userId, ?int $exceptId = null): void
    {
        Address::where('user_id', $userId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }

    private function validationErrorResponse(ValidationValidator $validator)
    {
        return response()->json([
            'message' => 'Validasi gagal.',
            'errors' => $validator->errors(),
            'data' => null,
        ], 422);
    }

    private function formatAddress(Address $address): array
    {
        $region = $address->village_id
            ? $this->regionResolver->resolve((string) $address->village_id)
            : [];

        $city = $address->city ?: ($region['regency_name'] ?? null);
        $province = $address->province ?: ($region['province_name'] ?? null);
        $addressLine = $address->address_line ?: $this->buildAddressLine([
            'street_name' => $address->street_name,
            'rt' => $address->rt,
            'rw' => $address->rw,
        ], $region);

        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone_number,
            'phone_number' => $address->phone_number,
            'address_line' => $addressLine,
            'city' => $city,
            'province' => $province,
            'postal_code' => $address->postal_code,
            'is_primary' => (bool) $address->is_default,
            'is_default' => (bool) $address->is_default,
            'village_id' => $address->village_id,
            'village_name' => $region['village_name'] ?? null,
            'district_name' => $region['district_name'] ?? null,
            'regency_name' => $region['regency_name'] ?? null,
            'province_name' => $region['province_name'] ?? null,
            'street_name' => $address->street_name,
            'rt' => $address->rt,
            'rw' => $address->rw,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ];
    }

    private function buildAddressLine(array $address, array $region): ?string
    {
        $parts = array_filter([
            $address['street_name'] ?? null,
            $this->formatRtRw($address['rt'] ?? null, $address['rw'] ?? null),
            $region['village_name'] ?? null,
            $region['district_name'] ?? null,
            $region['regency_name'] ?? null,
            $region['province_name'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    private function formatRtRw(?string $rt, ?string $rw): ?string
    {
        if ($rt && $rw) {
            return sprintf('RT %s / RW %s', $rt, $rw);
        }

        if ($rt) {
            return sprintf('RT %s', $rt);
        }

        if ($rw) {
            return sprintf('RW %s', $rw);
        }

        return null;
    }
}
