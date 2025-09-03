# Melike – E‑Ticaret API (Native PHP + PostgreSQL)

Minimal e-ticaret REST API’si. Framework yok (Native PHP), DB: PostgreSQL. JWT ile auth.

## Gereksinimler
- PHP 8.1+
- PostgreSQL 13+
- (Windows/Laragon ya da PHP built-in server)

## Hızlı Kurulum
1. **Veritabanı oluştur** (pgAdmin veya psql):
   ```sql
   CREATE DATABASE ecom_db;
   ```
2. **Şemayı ve örnek veriyi yükle** (pgAdmin >> ecom_db >> Query Tool):
   ```sql
   \i path/to/sql/schema.sql;
   \i path/to/sql/sample_data.sql;
   ```
   > Not: `schema.sql` içinde `pgcrypto` eklentisini açar; `sample_data.sql` admin/user’ı **bcrypt** ile (DB tarafında) oluşturur.

3. **app/bootstrap.php** içinde veritabanı bilgilerini düzenle:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ecom_db');
   define('DB_USER', 'postgres'); // kendi kullanıcı adın
   define('DB_PASS', 'postgres'); // kendi şifren
   // Güvenlik: Üretimde bunu uzun rastgele bir gizli değerle değiştir
   define('JWT_SECRET', 'change-me-please-very-secret');
   ```

4. **Sunucuyu başlat**:
   - Laragon kullanıyorsan: `public/` kök olacak şekilde sanal host ayarla veya
   - PHP built-in: proje kökünde
     ```bash
     php -S localhost:8000 -t public
     ```

5. **Sağlık kontrolü**:
   - `GET http://localhost:8000/api/health` → `{ success: true, message: "Tamam", data: { app:"ok", db:"ok" } }`

6. **Postman** (opsiyonel ama önerilir):
   - `postman/Melike-ECommerce.postman_collection.json` dosyasını içe aktar.

## Varsayılan Kullanıcılar
- **Admin**: `admin@test.com` / `admin123`
- **Kullanıcı**: `user@test.com` / `user123`

> Parolalar DB’de **bcrypt** ile hashlidir. Login sırasında `password_verify` ile kontrol edilir.

## API
- Base URL: `/api`
- Response formatı (tüm endpointler):
  ```json
  { "success": true, "message": "Tamam", "data": {}, "errors": [] }
  ```

### Auth
- `POST /api/register` (name, email, password)
- `POST /api/login` (email, password) → JWT
- `GET /api/profile` (Auth)
- `PUT /api/profile` (Auth)

### Kategori (admin)
- `GET /api/categories`
- `POST /api/categories` (Auth: admin)
- `PUT /api/categories/{id}` (Auth: admin)
- `DELETE /api/categories/{id}` (Auth: admin)

### Ürünler
- `GET /api/products?search=&category_id=&min_price=&max_price=&page=&limit=`
- `GET /api/products/{id}`
- `POST /api/products` (Auth: admin)
- `PUT /api/products/{id}` (Auth: admin)
- `DELETE /api/products/{id}` (Auth: admin)

### Sepet (kullanıcı)
- `GET /api/cart` (Auth)
- `POST /api/cart/add` (Auth) — body: { product_id, quantity }
- `PUT /api/cart/update` (Auth) — body: { product_id, quantity }
- `DELETE /api/cart/remove/{product_id}` (Auth)
- `DELETE /api/cart/clear` (Auth)

### Sipariş (kullanıcı)
- `POST /api/orders` (Auth) — sepettekilerden sipariş oluşturur, stok kontrol eder
- `GET /api/orders` (Auth)
- `GET /api/orders/{id}` (Auth)

## Notlar (Melike’nin küçük notları)
- pgAdmin’de ilk bağlanırken kullanıcı/rol karışıklığı yaşarsan: Sunucu → `localhost:5432` → kullanıcı adın `postgres` (ya da kurarken verdiğin), DB: `ecom_db`.
- Şema dosyasında `CREATE EXTENSION IF NOT EXISTS pgcrypto;` var; örnek kullanıcılar `crypt(..., gen_salt('bf'))` ile **bcrypt** hashlenir.
- Test kolaylığı için mesajlar kısa tutuldu ("Tamam", "Kayıt yapıldı", "Giriş yapıldı").

## Lisans
MIT
## Bonuslar
- **201 Created** durum kodu: Oluşturma uçları 201 döner.
- **Sipariş durumu güncelleme (admin)**: `PUT /api/orders/{id}/status` (body: `{ "status": "paid|cancelled|shipped|completed|pending" }`)
- **Mini logging**: `storage/logs/app.log` dosyasına JSON satır olarak olay kaydı.

