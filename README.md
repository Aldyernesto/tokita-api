<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Chat Penjual–Pembeli

Endpoint ringkasan (semua perlu autentikasi Sanctum):
- `POST /api/chat/rooms` — buat/reuse ruang buyer–seller per produk. Body: `seller_id`, `product_id`, opsional `initial_message`. Idempotent; jika ruang sudah ada dikembalikan apa adanya. Ruang baru otomatis menambahkan 2 pesan: `product_reference` (payload konteks produk) dan teks sapaan `"Halo, apakah produk ini tersedia?"` (override jika `initial_message` diisi).
- `GET /api/chat/rooms` — daftar ruang chat user sebagai buyer/seller dengan ringkasan last_message, unread_count, dan `product_context`.
- `GET /api/chat/rooms/{room_id}/messages` — detail thread dengan pesan + metadata baca. Mendukung query `per_page` (default 50, maks 100); sekaligus menandai pesan sebagai dibaca untuk pemanggil.
- `POST /api/chat/messages` — kirim pesan ke room. Body: `room_id`, `message`, `type` (`text` | `product_reference`, default `text`). `product_reference` otomatis memakai konteks produk dari room.

Aturan keamanan:
- User hanya bisa mengakses room yang ia ikuti (buyer atau seller produk).
- `seller_id` harus sama dengan pemilik `product_id`; buyer tidak bisa membuat chat ke produknya sendiri.
- Unread dihitung per user dari tabel `chat_message_reads`; setiap fetch detail otomatis mengosongkan unread untuk user tersebut.

Struktur `product_context`:
```json
{
  "product_id": 12,
  "product_name": "Kain Batik",
  "product_image_url": "https://cdn.example.com/img/batik.jpg",
  "product_price": 125000,
  "product_stock": 8,
  "is_available": true
}
```

Contoh respons `POST /api/chat/rooms` (201):
```json
{
  "message": "Ruang chat berhasil dibuat.",
  "data": {
    "room_id": 44,
    "buyer": {
      "user_id": 7,
      "name": "Putri",
      "avatar_url": "https://cdn.example.com/avatars/7.png",
      "is_seller": false
    },
    "seller": {
      "user_id": 2,
      "name": "Toko Batik",
      "avatar_url": "https://cdn.example.com/avatars/2.png",
      "is_seller": true
    },
    "last_message": {
      "id": 111,
      "room_id": 44,
      "sender_id": 7,
      "type": "text",
      "content": "Halo, apakah produk ini tersedia?",
      "payload": null,
      "created_at": "2025-11-23T00:00:00.000000Z",
      "read_status": {
        "is_read": false,
        "read_at": null
      }
    },
    "unread_count": 1,
    "product_context": {
      "product_id": 99,
      "product_name": "Kain Batik",
      "product_image_url": "https://cdn.example.com/img/batik.jpg",
      "product_price": 125000,
      "product_stock": 8,
      "is_available": true
    }
  }
}
```

Contoh respons `GET /api/chat/rooms/{room_id}/messages`:
```json
{
  "message": "Detail chat.",
  "data": {
    "room": {
      "room_id": 44,
      "buyer": { "user_id": 7, "name": "Putri", "avatar_url": null, "is_seller": false },
      "seller": { "user_id": 2, "name": "Toko Batik", "avatar_url": null, "is_seller": true },
      "last_message": { "id": 112, "room_id": 44, "sender_id": 2, "type": "text", "content": "Siap, stok ada ya.", "payload": null, "created_at": "2025-11-23T00:05:00.000000Z", "read_status": { "is_read": true, "read_at": "2025-11-23T00:06:00.000000Z" } },
      "unread_count": 0,
      "product_context": { "...": "..." }
    },
    "messages": [
      { "id": 110, "type": "product_reference", "payload": { "...": "..." }, "read_status": { "is_read": true, "read_at": "2025-11-23T00:01:00.000000Z" }, "created_at": "2025-11-23T00:00:00.000000Z" },
      { "id": 111, "type": "text", "content": "Halo, apakah produk ini tersedia?", "payload": null, "read_status": { "is_read": true, "read_at": "2025-11-23T00:01:00.000000Z" }, "created_at": "2025-11-23T00:00:10.000000Z" },
      { "id": 112, "type": "text", "content": "Siap, stok ada ya.", "payload": null, "read_status": { "is_read": true, "read_at": "2025-11-23T00:06:00.000000Z" }, "created_at": "2025-11-23T00:05:00.000000Z" }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 50,
      "total": 3
    }
  }
}
```

Catatan pengembangan:
- Set environment `DEV_AVATAR_PLACEHOLDER` jika ingin fallback avatar (dev-only) ketika `avatar_url` kosong, sehingga UI tidak memaksa file dari R2.
- Rate limit bawaan group API tetap aktif; naikkan/override jika perlu menahan spam create-room.
