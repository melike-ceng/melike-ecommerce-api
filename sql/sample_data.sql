\
    -- Örnek kullanıcılar (bcrypt, DB tarafında crypt)
    INSERT INTO users(name,email,password,role)
    VALUES
     ('Admin', 'admin@test.com', crypt('admin123', gen_salt('bf', 10)), 'admin'),
     ('Melike Kullanıcı', 'user@test.com', crypt('user123', gen_salt('bf', 10)), 'user');

    -- Kategoriler
    INSERT INTO categories(name,description) VALUES
     ('Elektronik','Telefon, kulaklık, aksesuar'),
     ('Giyim','Tişört, hoodie, şapka'),
     ('Kitaplar','Roman, kişisel gelişim, teknik');

    -- Ürünler (her kategoriden en az 5)
    -- Elektronik
    INSERT INTO products(name,description,price,stock_quantity,category_id) VALUES
     ('Akıllı Telefon A1','64GB, 4GB RAM', 8999.90, 10, (SELECT id FROM categories WHERE name='Elektronik')),
     ('Kulaklık X','BT 5.0, mikrofonlu', 799.90, 50, (SELECT id FROM categories WHERE name='Elektronik')),
     ('Powerbank 10K','Hızlı şarj destekli', 399.90, 40, (SELECT id FROM categories WHERE name='Elektronik')),
     ('USB-C Kablo','1m, örgü', 99.90, 100, (SELECT id FROM categories WHERE name='Elektronik')),
     ('Bluetooth Hoparlör','Suya dayanıklı', 599.90, 25, (SELECT id FROM categories WHERE name='Elektronik'));

    -- Giyim
    INSERT INTO products(name,description,price,stock_quantity,category_id) VALUES
     ('Basic Tişört','%100 pamuk', 149.90, 70, (SELECT id FROM categories WHERE name='Giyim')),
     ('Oversize Hoodie','Polar iç', 399.90, 35, (SELECT id FROM categories WHERE name='Giyim')),
     ('Keten Şapka','Ayarlanabilir', 129.90, 60, (SELECT id FROM categories WHERE name='Giyim')),
     ('Jogger Pantolon','Esnek kumaş', 299.90, 45, (SELECT id FROM categories WHERE name='Giyim')),
     ('Spor Çorap (3lü)','Nefes alır', 89.90, 150, (SELECT id FROM categories WHERE name='Giyim'));

    -- Kitaplar
    INSERT INTO products(name,description,price,stock_quantity,category_id) VALUES
     ('Roman 101','Sürükleyici bir hikaye', 99.90, 80, (SELECT id FROM categories WHERE name='Kitaplar')),
     ('Algoritmalar Kolay','Başlangıç seviyesi', 159.90, 30, (SELECT id FROM categories WHERE name='Kitaplar')),
     ('Veri Tabanına Giriş','Temeller', 179.90, 20, (SELECT id FROM categories WHERE name='Kitaplar')),
     ('Kişisel Gelişim Seti','3 kitap', 249.90, 25, (SELECT id FROM categories WHERE name='Kitaplar')),
     ('Frontend 101','HTML/CSS/JS', 139.90, 35, (SELECT id FROM categories WHERE name='Kitaplar'));
