\
    <?php
    require_once __DIR__ . '/../app/bootstrap.php';

    use App\Router;
    use App\Database;

    $router = new Router();

    // -------------- Health --------------
    $router->register('GET', '#^/api/health$#', function() {
        try {
            $pdo = Database::get();
            $ok = $pdo->query('SELECT 1')->fetchColumn() == 1;
            return json_success('Tamam', [ 'app' => 'ok', 'db' => $ok ? 'ok' : 'fail', 'time' => date('c') ]);
        } catch (Throwable $e) {
            return json_error('Sunucu hatası', 500, [], ['db' => $e->getMessage()]);
        }
    });

    // -------------- Auth --------------
    $router->register('POST', '#^/api/register$#', function() {
        $data = json_body();
        $errors = validate_register($data);
        if ($errors) return json_error('Validasyon hatası', 422, [], $errors);

        $pdo = Database::get();
        // email benzersiz
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $data['email']]);
        if ($stmt->fetch()) return json_error('Email kullanımda', 422);

        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users(name,email,password,role,created_at,updated_at) VALUES(:name,:email,:password,:role,NOW(),NOW()) RETURNING id');
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => $hash,
            ':role' => 'user'
        ]);

        if (function_exists('log_event')) { log_event('info','user.register',['email'=>$data['email']]); }
        return json_created('Kayıt yapıldı');
    });

    $router->register('POST', '#^/api/login$#', function() {
        $data = json_body();
        if (!isset($data['email'], $data['password'])) return json_error('Geçersiz istek', 400);

        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id,name,email,password,role FROM users WHERE email = :email');
        $stmt->execute([':email' => $data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return json_error('Yetkisiz', 401);

        $hash = $user['password'];
        $ok = password_verify($data['password'], $hash) || hash_equals($hash, crypt($data['password'], $hash)); // $2a uyumluluğu
        if (!$ok) return json_error('Yetkisiz', 401);

        $token = jwt_encode(['uid' => (int)$user['id'], 'role' => $user['role']]);
        if (function_exists('log_event')) { log_event('info','auth.login',['uid'=>(int)$user['id']]); }
        return json_success('Giriş yapıldı', ['token' => $token]);
    });

    $router->register('GET', '#^/api/profile$#', function() {
        $auth = require_auth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id,name,email,role,created_at,updated_at FROM users WHERE id = :id');
        $stmt->execute([':id' => $auth['uid']]);
        $me = $stmt->fetch(PDO::FETCH_ASSOC);
        return json_success('Tamam', $me);
    });

    $router->register('PUT', '#^/api/profile$#', function() {
        $auth = require_auth();
        $data = json_body();
        $fields = [];
        $params = [':id' => $auth['uid']];

        if (isset($data['name']) && is_string($data['name']) && mb_strlen(trim($data['name'])) >= 2) {
            $fields[] = 'name = :name';
            $params[':name'] = trim($data['name']);
        }
        if (isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            // benzersiz kontrolü
            $pdo = Database::get();
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id');
            $chk->execute([':email' => $data['email'], ':id' => $auth['uid']]);
            if ($chk->fetch()) return json_error('Email kullanımda', 422);
            $fields[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        if (isset($data['password']) && is_string($data['password']) && mb_strlen($data['password']) >= 8) {
            $fields[] = 'password = :password';
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (!$fields) return json_error('Geçersiz istek', 400);

        $sql = 'UPDATE users SET ' . implode(',', $fields) . ', updated_at = NOW() WHERE id = :id';
        $pdo = Database::get();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return json_success('Tamam');
    });

    // -------------- Categories --------------
    $router->register('GET', '#^/api/categories$#', function() {
        $pdo = Database::get();
        $rows = $pdo->query('SELECT id,name,description,created_at,updated_at FROM categories ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        return json_success('Tamam', $rows);
    });

    $router->register('POST', '#^/api/categories$#', function() {
        require_admin();
        $data = json_body();
        if (!isset($data['name']) || mb_strlen(trim($data['name'])) < 1) return json_error('Validasyon hatası', 422);
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO categories(name,description,created_at,updated_at) VALUES(:name,:desc,NOW(),NOW()) RETURNING id');
        $stmt->execute([':name' => trim($data['name']), ':desc' => $data['description'] ?? null]);
        $newId = (int)$stmt->fetchColumn();
        if (function_exists('log_event')) { log_event('info','category.create',['id'=>$newId]); }
        return json_created('Tamam', ['id' => $newId], '/api/categories/' . $newId);
    });

    $router->register('PUT', '#^/api/categories/(\d+)$#', function($m) {
        require_admin();
        $id = (int)$m[1];
        $data = json_body();
        $pdo = Database::get();
        $stmt = $pdo->prepare('UPDATE categories SET name = COALESCE(:name,name), description = COALESCE(:desc,description), updated_at = NOW() WHERE id = :id');
        $stmt->execute([':name' => $data['name'] ?? null, ':desc' => $data['description'] ?? null, ':id' => $id]);
        return json_success('Tamam');
    });

    $router->register('DELETE', '#^/api/categories/(\d+)$#', function($m) {
        require_admin();
        $id = (int)$m[1];
        $pdo = Database::get();
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return json_success('Tamam');
    });

    // -------------- Products --------------
    $router->register('GET', '#^/api/products$#', function() {
        $pdo = Database::get();
        $q = $_GET;
        $page = max(1, (int)($q['page'] ?? 1));
        $limit = max(1, min(100, (int)($q['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (isset($q['category_id']) && ctype_digit((string)$q['category_id'])) {
            $where[] = 'p.category_id = :cid';
            $params[':cid'] = (int)$q['category_id'];
        }
        if (isset($q['min_price']) && is_numeric($q['min_price'])) {
            $where[] = 'p.price >= :minp';
            $params[':minp'] = (float)$q['min_price'];
        }
        if (isset($q['max_price']) && is_numeric($q['max_price'])) {
            $where[] = 'p.price <= :maxp';
            $params[':maxp'] = (float)$q['max_price'];
        }
        if (!empty($q['search'])) {
            $where[] = 'p.name ILIKE :search';
            $params[':search'] = '%' . $q['search'] . '%';
        }

        $wsql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products p $wsql");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $sql = "SELECT p.id,p.name,p.description,p.price,p.stock_quantity,p.category_id,c.name AS category_name,p.created_at,p.updated_at
                FROM products p JOIN categories c ON c.id = p.category_id $wsql
                ORDER BY p.id DESC LIMIT :lim OFFSET :off";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_success('Tamam', [
            'items' => $rows,
            'meta' => [ 'page' => $page, 'limit' => $limit, 'total' => $total ]
        ]);
    });

    $router->register('GET', '#^/api/products/(\d+)$#', function($m) {
        $id = (int)$m[1];
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id,name,description,price,stock_quantity,category_id,created_at,updated_at FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return json_error('Bulunamadı', 404);
        return json_success('Tamam', $row);
    });

    $router->register('POST', '#^/api/products$#', function() {
        require_admin();
        $data = json_body();
        $errors = validate_product($data);
        if ($errors) return json_error('Validasyon hatası', 422, [], $errors);
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO products(name,description,price,stock_quantity,category_id,created_at,updated_at) VALUES(:name,:desc,:price,:stock,:cid,NOW(),NOW()) RETURNING id');
        $stmt->execute([
            ':name' => $data['name'],
            ':desc' => $data['description'] ?? null,
            ':price' => (float)$data['price'],
            ':stock' => (int)$data['stock'],
            ':cid' => (int)$data['category_id'],
        ]);
        $newId = (int)$stmt->fetchColumn();
        if (function_exists('log_event')) { log_event('info','category.create',['id'=>$newId]); }
        return json_created('Tamam', ['id' => $newId], '/api/categories/' . $newId);
    });

    $router->register('PUT', '#^/api/products/(\d+)$#', function($m) {
        require_admin();
        $id = (int)$m[1];
        $data = json_body();
        $pdo = Database::get();
        $stmt = $pdo->prepare('UPDATE products SET name = COALESCE(:name,name), description = COALESCE(:desc,description), price = COALESCE(:price,price), stock_quantity = COALESCE(:stock,stock_quantity), category_id = COALESCE(:cid,category_id), updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':name' => $data['name'] ?? null,
            ':desc' => $data['description'] ?? null,
            ':price' => isset($data['price']) ? (float)$data['price'] : null,
            ':stock' => isset($data['stock']) ? (int)$data['stock'] : null,
            ':cid' => isset($data['category_id']) ? (int)$data['category_id'] : null,
            ':id' => $id
        ]);
        return json_success('Tamam');
    });

    $router->register('DELETE', '#^/api/products/(\d+)$#', function($m) {
        require_admin();
        $id = (int)$m[1];
        $pdo = Database::get();
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return json_success('Tamam');
    });

    // -------------- Cart --------------
    function ensure_cart_id(int $userId): int {
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;
        $stmt = $pdo->prepare('INSERT INTO carts(user_id,created_at,updated_at) VALUES(:uid,NOW(),NOW()) RETURNING id');
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    $router->register('GET', '#^/api/cart$#', function() {
        $auth = require_auth();
        $pdo = Database::get();
        $cartId = ensure_cart_id($auth['uid']);
        $stmt = $pdo->prepare('SELECT ci.product_id, ci.quantity, p.name, p.price, (ci.quantity * p.price) AS line_total FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.cart_id = :cid ORDER BY ci.id');
        $stmt->execute([':cid' => $cartId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = 0.0;
        foreach ($items as $it) $total += (float)$it['line_total'];
        return json_success('Tamam', [ 'items' => $items, 'total' => $total ]);
    });

    $router->register('POST', '#^/api/cart/add$#', function() {
        $auth = require_auth();
        $data = json_body();
        if (!isset($data['product_id'])) return json_error('Geçersiz istek', 400);
        $pid = (int)$data['product_id'];
        $qty = max(1, (int)($data['quantity'] ?? 1));
        $pdo = Database::get();
        $cid = ensure_cart_id($auth['uid']);

        // ürün var mı?
        $chk = $pdo->prepare('SELECT id FROM products WHERE id = :id');
        $chk->execute([':id' => $pid]);
        if (!$chk->fetch()) return json_error('Bulunamadı', 404);

        // varsa artır, yoksa ekle
        $sel = $pdo->prepare('SELECT id,quantity FROM cart_items WHERE cart_id = :cid AND product_id = :pid');
        $sel->execute([':cid' => $cid, ':pid' => $pid]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $upd = $pdo->prepare('UPDATE cart_items SET quantity = quantity + :q, updated_at = NOW() WHERE id = :id');
            $upd->execute([':q' => $qty, ':id' => $row['id']]);
        } else {
            $ins = $pdo->prepare('INSERT INTO cart_items(cart_id,product_id,quantity,created_at,updated_at) VALUES(:cid,:pid,:q,NOW(),NOW())');
            $ins->execute([':cid' => $cid, ':pid' => $pid, ':q' => $qty]);
        }
        return json_success('Tamam');
    });

    $router->register('PUT', '#^/api/cart/update$#', function() {
        $auth = require_auth();
        $data = json_body();
        if (!isset($data['product_id'])) return json_error('Geçersiz istek', 400);
        $pid = (int)$data['product_id'];
        $qty = (int)($data['quantity'] ?? 1);
        $pdo = Database::get();
        $cid = ensure_cart_id($auth['uid']);

        if ($qty <= 0) {
            $del = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cid AND product_id = :pid');
            $del->execute([':cid' => $cid, ':pid' => $pid]);
            return json_success('Tamam');
        }

        $upd = $pdo->prepare('UPDATE cart_items SET quantity = :q, updated_at = NOW() WHERE cart_id = :cid AND product_id = :pid');
        $upd->execute([':q' => $qty, ':cid' => $cid, ':pid' => $pid]);
        return json_success('Tamam');
    });

    $router->register('DELETE', '#^/api/cart/remove/(\d+)$#', function($m) {
        $auth = require_auth();
        $pid = (int)$m[1];
        $pdo = Database::get();
        $cid = ensure_cart_id($auth['uid']);
        $del = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cid AND product_id = :pid');
        $del->execute([':cid' => $cid, ':pid' => $pid]);
        return json_success('Tamam');
    });

    $router->register('DELETE', '#^/api/cart/clear$#', function() {
        $auth = require_auth();
        $pdo = Database::get();
        $cid = ensure_cart_id($auth['uid']);
        $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cid')->execute([':cid' => $cid]);
        return json_success('Tamam');
    });

    // -------------- Orders --------------
    $router->register('POST', '#^/api/orders$#', function() {
        $auth = require_auth();
        $pdo = Database::get();
        $cid = ensure_cart_id($auth['uid']);

        $items = $pdo->prepare('SELECT ci.product_id, ci.quantity, p.price, p.stock_quantity FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.cart_id = :cid');
        $items->execute([':cid' => $cid]);
        $rows = $items->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return json_error('Sepet boş', 422);

        // stok kontrol
        foreach ($rows as $r) {
            if ($r['quantity'] > $r['stock_quantity']) return json_error('Stok yetersiz', 422, [], ['product_id' => (int)$r['product_id']]);
        }

        $pdo->beginTransaction();
        try {
            $total = 0.0; foreach ($rows as $r) $total += (float)$r['price'] * (int)$r['quantity'];
            $ord = $pdo->prepare('INSERT INTO orders(user_id,total_amount,status,created_at,updated_at) VALUES(:uid,:total,:st,NOW(),NOW()) RETURNING id');
            $ord->execute([':uid' => $auth['uid'], ':total' => $total, ':st' => 'pending']);
            $oid = (int)$ord->fetchColumn();

            $oi = $pdo->prepare('INSERT INTO order_items(order_id,product_id,quantity,price,created_at,updated_at) VALUES(:oid,:pid,:q,:p,NOW(),NOW())');
            $dec = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - :q, updated_at = NOW() WHERE id = :pid');
            foreach ($rows as $r) {
                $oi->execute([':oid' => $oid, ':pid' => $r['product_id'], ':q' => $r['quantity'], ':p' => $r['price']]);
                $dec->execute([':q' => $r['quantity'], ':pid' => $r['product_id']]);
            }
            // sepeti boşalt
            $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cid')->execute([':cid' => $cid]);
            $pdo->commit();
            if (function_exists('log_event')) { log_event('info','order.create',['order_id'=>$oid,'total'=>$total]); }
            return json_created('Tamam', ['order_id' => $oid, 'status' => 'pending', 'total' => $total], '/api/orders/' . $oid);
        } catch (Throwable $e) {
            $pdo->rollBack();
            return json_error('Sunucu hatası', 500, [], ['err' => $e->getMessage()]);
        }
    });

    $router->register('GET', '#^/api/orders$#', function() {
        $auth = require_auth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id,total_amount,status,created_at,updated_at FROM orders WHERE user_id = :uid ORDER BY id DESC');
        $stmt->execute([':uid' => $auth['uid']]);
        return json_success('Tamam', $stmt->fetchAll(PDO::FETCH_ASSOC));
    });

    $router->register('GET', '#^/api/orders/(\d+)$#', function($m) {
        $auth = require_auth();
        $oid = (int)$m[1];
        $pdo = Database::get();
        $o = $pdo->prepare('SELECT id,user_id,total_amount,status,created_at,updated_at FROM orders WHERE id = :id');
        $o->execute([':id' => $oid]);
        $order = $o->fetch(PDO::FETCH_ASSOC);
        if (!$order || (int)$order['user_id'] !== (int)$auth['uid']) return json_error('Yetkisiz', 401);
        $it = $pdo->prepare('SELECT product_id,quantity,price FROM order_items WHERE order_id = :id');
        $it->execute([':id' => $oid]);
        $items = $it->fetchAll(PDO::FETCH_ASSOC);
        return json_success('Tamam', ['order' => $order, 'items' => $items]);
    });


$router->register('PUT', '#^/api/orders/(\d+)/status$#', function($m) {
    require_admin();
    $oid = (int)$m[1];
    $data = json_body();
    $allowed = ['pending','paid','cancelled','shipped','completed'];
    if (!isset($data['status']) || !in_array($data['status'], $allowed, true)) {
        return json_error('Validasyon hatası', 422, [], ['status' => 'pending|paid|cancelled|shipped|completed']);
    }
    $pdo = Database::get();
    $stmt = $pdo->prepare('UPDATE orders SET status = :st, updated_at = NOW() WHERE id = :id');
    $stmt->execute([':st' => $data['status'], ':id' => $oid]);
    if ($stmt->rowCount() === 0) return json_error('Bulunamadı', 404);
    if (function_exists('log_event')) { log_event('info','order.status.update',['order_id'=>$oid,'status'=>$data['status']]); }
    return json_success('Tamam');
});



    // -------------- Dispatch --------------
    $router->dispatch();
