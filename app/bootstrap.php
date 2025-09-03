\
    <?php
    // error reporting + default headers
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    header('Content-Type: application/json; charset=utf-8');
    date_default_timezone_set('Europe/Istanbul');

    // ---- Config (Kendi ortamına göre düzenle) ----
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ecom_db');
    define('DB_USER', 'postgres');
    define('DB_PASS', 'postgres');

    // Güvenlik: üretimde değiştir
    define('JWT_SECRET', 'change-me-please-very-secret');
    // İstenilen sabit (bilinçli "ogrenci")
    define('JWT_ISSUER', 'ogrenci_api');
    // Token geçerliliği: 24 saat
    define('JWT_TTL', 86400);

    // ---- Requires ----
    require_once __DIR__ . '/Database.php';
    require_once __DIR__ . '/Router.php';
    require_once __DIR__ . '/Jwt.php';
    require_once __DIR__ . '/Validation.php';
    require_once __DIR__ . '/helpers.php';
