\
    <?php
    function validate_register(array $d): array {
        $e = [];
        if (!isset($d['name']) || mb_strlen(trim($d['name'])) < 2) $e['name'] = 'en az 2 karakter';
        if (!isset($d['email']) || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'geçersiz';
        if (!isset($d['password']) || mb_strlen($d['password']) < 8) $e['password'] = 'en az 8 karakter';
        return $e;
    }

    function validate_product(array $d): array {
        $e = [];
        if (!isset($d['name']) || mb_strlen(trim($d['name'])) < 3) $e['name'] = 'en az 3 karakter';
        if (!isset($d['price']) || !is_numeric($d['price']) || (float)$d['price'] <= 0) $e['price'] = 'pozitif olmalı';
        if (!isset($d['stock']) || !is_numeric($d['stock']) || (int)$d['stock'] < 0) $e['stock'] = 'negatif olmaz';
        if (!isset($d['category_id']) || !is_numeric($d['category_id'])) $e['category_id'] = 'geçersiz';
        return $e;
    }
