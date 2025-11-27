<?php

namespace App\Http\Controllers\Api; // Pastikan namespace-nya benar

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Kita panggil Model User
use Illuminate\Support\Facades\Hash; // Kita panggil "Pangacak" Password
use Illuminate\Support\Facades\Validator; // Kita panggil "Satpam" Validasi
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Fungsi untuk mendaftarkan user baru (Register).
     */
    public function register(Request $request)
    {
        // 1. Validasi (Cek datanya benar atau tidak)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // Cek email harus unik
            'password' => 'required|string|min:8|confirmed', // 'confirmed' berarti harus ada 'password_confirmation'
        ]);

        // Jika validasi gagal, kirim respon error 422
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Buat User (Jika Lolos Validasi)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Password WAJIB di-Hash (diacak)
        ]);

        // 3. Kirim Respon Sukses (HTTP 201 - Created)
        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
{
    // 1. Validasi Input
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    // Jika validasi gagal
    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // 2. Coba Lakukan Login
    $credentials = $request->only('email', 'password');

    if (!Auth::attempt($credentials)) {
        // Jika email/password salah
        return response()->json([
            'status' => 'error',
            'message' => 'Email atau password salah.'
        ], 401); // 401 Unauthorized
    }

    // 3. Jika Berhasil Login
    $user = Auth::user(); // Ambil data user yang berhasil login

    // Buat Token (tiket masuk) untuk user ini
    // Kita akan pakai Laravel Sanctum yang simpel
    $token = $user->createToken('auth_token')->plainTextToken;

    // 4. Kirim Respon Sukses beserta Token
    return response()->json([
        'status' => 'success',
        'message' => 'Login berhasil',
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user
    ]);
}
}