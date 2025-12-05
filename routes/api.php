<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductSearchController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\Api\ChatRoomController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\FcmController;
use App\Services\FcmService;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}/products', [CategoryController::class, 'show']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/search', ProductSearchController::class);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy']);
    Route::delete('/favorites/by-product/{productId}', [FavoriteController::class, 'destroyByProduct']);

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);
    Route::post('/fcm-token', [FcmController::class, 'updateToken']);

    Route::get('/chat/rooms', [ChatRoomController::class, 'index']);
    Route::post('/chat/rooms', [ChatRoomController::class, 'store']);
    Route::get('/chat/rooms/{roomId}/messages', [ChatRoomController::class, 'messages']);
    Route::post('/chat/messages', [ChatMessageController::class, 'store']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [SocialAuthController::class, 'googleLogin']);
Route::get('/regions/provinces', [RegionController::class, 'getProvinces']);
Route::get('/regions/cities', [RegionController::class, 'getCities']);
Route::get('/regions/districts/{city_kode}', [RegionController::class, 'getDistricts']);
Route::get('/regions/villages/{district_kode}', [RegionController::class, 'getVillages']);
Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::get('/send-test', function (FcmService $fcmService) {
    $userId = optional(User::first())->id ?? 1;

    $result = $fcmService->sendNotification(
        $userId,
        'Halo Tokita!',
        'Ini notifikasi percobaan berhasil masuk!'
    );

    return response()->json([
        'message' => $result,
    ]);
});
