-- สร้างฐานข้อมูล PhoneXpress MySQL
-- ไฟล์นี้ใช้สำหรับสร้างโครงสร้างฐานข้อมูลใน phpMyAdmin

CREATE DATABASE IF NOT EXISTS phonexpress 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE phonexpress;

-- ตาราง users (ข้อมูลผู้ใช้)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(10),
    role ENUM('user', 'admin') DEFAULT 'user',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- ตาราง sessions (จัดการ Session การเข้าสู่ระบบ)
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- ตาราง products (สินค้า iPhone)
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    model VARCHAR(100) NOT NULL,
    name VARCHAR(200) NOT NULL,
    color VARCHAR(50) NOT NULL,
    storage VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    specifications JSON,
    image_path VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product (model, color, storage)
);

-- ตาราง cart (ตะกร้าสินค้า)
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- ตาราง orders (คำสั่งซื้อ)
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- ตาราง order_items (รายการสินค้าในคำสั่งซื้อ)
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ตาราง user_preferences (การตั้งค่าผู้ใช้)
CREATE TABLE user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key)
);

-- ตาราง activity_logs (บันทึกกิจกรรม)
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- เพิ่มข้อมูลตัวอย่าง iPhone products
INSERT INTO products (model, name, color, storage, price, description, image_path, stock_quantity, is_featured) VALUES
('iphone16promaxblack', 'iPhone 16 Pro Max', 'Black', '256GB', 48900.00, 'iPhone 16 Pro Max สีดำ ความจุ 256GB', 'assets/image/iphone/iphone16-promax-Black.png', 10, TRUE),
('iphone16promaxdeserttitanium', 'iPhone 16 Pro Max', 'Desert Titanium', '256GB', 48900.00, 'iPhone 16 Pro Max สี Desert Titanium ความจุ 256GB', 'assets/image/iphone/iPhone 16 Pro Max Desert Titanium.png', 8, TRUE),
('iphone16promaxnaturaltitanium', 'iPhone 16 Pro Max', 'Natural Titanium', '256GB', 48900.00, 'iPhone 16 Pro Max สี Natural Titanium ความจุ 256GB', 'assets/image/iphone/iPhone 16 Pro Max Natural Natural Titanium.png', 12, TRUE),
('iphone16promaxbluetitanium', 'iPhone 16 Pro Max', 'Blue Titanium', '256GB', 48900.00, 'iPhone 16 Pro Max สี Blue Titanium ความจุ 256GB', 'assets/image/iphone/iPhone 15 Pro Max Blue Titanium.png', 5, TRUE),
('iphone15promaxblack', 'iPhone 15 Pro Max', 'Black Titanium', '256GB', 45900.00, 'iPhone 15 Pro Max สี Black Titanium ความจุ 256GB', 'assets/image/iphone/iPhone 15 Pro Max black.png', 15, TRUE),
('iphone15pronaturaltitanium', 'iPhone 15 Pro', 'Natural Titanium', '128GB', 35900.00, 'iPhone 15 Pro สี Natural Titanium ความจุ 128GB', 'assets/image/iphone/iphone 15 pro natural titanium.png', 20, TRUE),
('iphone15prowhitetitanium', 'iPhone 15 Pro', 'White Titanium', '128GB', 35900.00, 'iPhone 15 Pro สี White Titanium ความจุ 128GB', 'assets/image/iphone/iphone 15 pro white titanium.png', 18, TRUE),
('iphone15black', 'iPhone 15', 'Black', '128GB', 32900.00, 'iPhone 15 สีดำ ความจุ 128GB', 'assets/image/iphone/iphone 15 black.png', 25, FALSE),
('iphone15blue', 'iPhone 15', 'Blue', '128GB', 32900.00, 'iPhone 15 สีน้ำเงิน ความจุ 128GB', 'assets/image/iphone/iphone 15 blue.png', 22, FALSE),
('iphone15pink', 'iPhone 15', 'Pink', '128GB', 32900.00, 'iPhone 15 สีชมพู ความจุ 128GB', 'assets/image/iphone/iphone 15 pink.png', 30, FALSE),
('iphone14blue', 'iPhone 14', 'Blue', '128GB', 28900.00, 'iPhone 14 สีน้ำเงิน ความจุ 128GB', 'assets/image/iphone/iPhone 14 blue.png', 35, FALSE),
('iphone14midnight', 'iPhone 14', 'Midnight', '128GB', 28900.00, 'iPhone 14 สี Midnight ความจุ 128GB', 'assets/image/iphone/iphone 14 midnight.png', 40, FALSE),
('iphone14purple', 'iPhone 14', 'Purple', '128GB', 28900.00, 'iPhone 14 สีม่วง ความจุ 128GB', 'assets/image/iphone/iPhone 14 purple.png', 28, FALSE),
('iphonese3midnight', 'iPhone SE (3rd gen)', 'Midnight', '64GB', 15900.00, 'iPhone SE รุ่นที่ 3 สี Midnight ความจุ 64GB', 'assets/image/iphone/iphone se 3rd gen midnight.png', 50, FALSE),
('iphonese3red', 'iPhone SE (3rd gen)', 'Red', '64GB', 15900.00, 'iPhone SE รุ่นที่ 3 สีแดง ความจุ 64GB', 'assets/image/iphone/iphone se 3rd gen red.png', 45, FALSE),
('iphonese3starlight', 'iPhone SE (3rd gen)', 'Starlight', '64GB', 15900.00, 'iPhone SE รุ่นที่ 3 สี Starlight ความจุ 64GB', 'assets/image/iphone/iphone se 3rd gen starlight.png', 42, FALSE);

-- สร้าง Admin user (username: admin, password: admin123)
INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES
('admin', 'admin@phonexpress.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'PhoneXpress', 'admin');

-- สร้าง Demo user (username: demo, password: demo123)
INSERT INTO users (username, email, password_hash, first_name, last_name, phone) VALUES
('demo', 'demo@phonexpress.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ใช้', 'ทดสอบ', '0812345678');

-- View สำหรับดูข้อมูลผู้ใช้พร้อมสถิติ
CREATE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    u.email,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    u.phone,
    u.role,
    u.created_at,
    u.last_login,
    u.is_active,
    COUNT(DISTINCT o.id) as total_orders,
    COALESCE(SUM(o.total_amount), 0) as total_spent,
    COUNT(DISTINCT c.id) as cart_items
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
LEFT JOIN cart c ON u.id = c.user_id
GROUP BY u.id;

-- View สำหรับดูสถิติสินค้า
CREATE VIEW product_stats AS
SELECT 
    p.id,
    p.model,
    p.name,
    p.color,
    p.storage,
    p.price,
    p.stock_quantity,
    p.is_featured,
    p.is_active,
    COUNT(DISTINCT oi.order_id) as total_orders,
    SUM(oi.quantity) as total_sold,
    SUM(oi.total_price) as total_revenue
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
GROUP BY p.id;

-- View สำหรับดูข้อมูลคำสั่งซื้อแบบละเอียด
CREATE VIEW order_details AS
SELECT 
    o.id,
    o.order_number,
    CONCAT(u.first_name, ' ', u.last_name) as customer_name,
    u.email as customer_email,
    u.phone as customer_phone,
    o.total_amount,
    o.status,
    o.payment_status,
    o.payment_method,
    o.created_at,
    o.shipped_at,
    o.delivered_at,
    COUNT(oi.id) as total_items,
    GROUP_CONCAT(
        CONCAT(p.name, ' (', oi.quantity, ')') 
        SEPARATOR ', '
    ) as items_summary
FROM orders o
JOIN users u ON o.user_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN products p ON oi.product_id = p.id
GROUP BY o.id;

-- บันทึกข้อมูลเริ่มต้นสำหรับการทดสอบ
INSERT INTO cart (user_id, product_id, quantity) VALUES
(2, 1, 1),
(2, 5, 2);

-- สร้างคำสั่งซื้อตัวอย่าง
INSERT INTO orders (user_id, order_number, total_amount, status, payment_status, payment_method, shipping_address) VALUES
(2, 'PX250101001', 48900.00, 'delivered', 'paid', 'credit_card', '123 ถนนสุขุมวิท กรุงเทพฯ 10110');

INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES
(1, 1, 1, 48900.00, 48900.00);

-- สร้าง Activity Logs ตัวอย่าง
INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES
(1, 'admin_login', 'Admin logged in to system', '127.0.0.1'),
(2, 'user_register', 'New user registered', '127.0.0.1'),
(2, 'user_login', 'User logged in', '127.0.0.1'),
(2, 'add_to_cart', 'Added iPhone 16 Pro Max to cart', '127.0.0.1'),
(2, 'place_order', 'Placed order PX250101001', '127.0.0.1');
