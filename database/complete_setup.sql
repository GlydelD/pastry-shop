-- ============================================
-- PASTRY SHOP DATABASE - COMPLETE SETUP
-- ============================================
-- This file contains the complete database schema
-- with all required columns and sample data.
-- ============================================

CREATE DATABASE IF NOT EXISTS pastry_shop;
USE pastry_shop;

-- ============================================
-- TABLE: customers
-- ============================================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_username (username),
    INDEX idx_customer_email (email)
);

-- ============================================
-- TABLE: shop_admin
-- ============================================
CREATE TABLE IF NOT EXISTS shop_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin if not exists
INSERT INTO shop_admin (username, password)
SELECT 'shopadmin', 'admin123'
WHERE NOT EXISTS (SELECT 1 FROM shop_admin WHERE username='shopadmin');

-- ============================================
-- TABLE: pastries
-- ============================================
CREATE TABLE IF NOT EXISTS pastries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(50),
    is_featured BOOLEAN DEFAULT 0,
    stock_quantity INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pastries_featured (is_featured),
    INDEX idx_pastries_category (category),
    INDEX idx_pastries_name (name)
);

-- ============================================
-- TABLE: orders
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    delivery_address TEXT,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending','Processing','Completed','Cancelled') DEFAULT 'Pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_orders_status (status),
    INDEX idx_orders_date (order_date),
    INDEX idx_orders_customer (customer_id)
);

-- ============================================
-- TABLE: order_items
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    pastry_id INT NOT NULL,
    pastry_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (pastry_id) REFERENCES pastries(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: cart
-- ============================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    pastry_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (pastry_id) REFERENCES pastries(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (customer_id, pastry_id)
);

-- ============================================
-- TABLE: wishlist
-- ============================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    pastry_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist_item (customer_id, pastry_id),
    INDEX idx_wishlist_customer (customer_id),
    INDEX idx_wishlist_pastry (pastry_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (pastry_id) REFERENCES pastries(id) ON DELETE CASCADE
);

-- ============================================
-- SAMPLE DATA: Insert sample pastries
-- ============================================
INSERT INTO pastries (name, description, price, image_url, category, is_featured, stock_quantity) VALUES
('Chocolate Croissant', 'Buttery croissant filled with rich dark chocolate', 252.00, 'media/pastries/Chocolate Croissant.jpg', 'Croissants', 1, 50),
('Strawberry Tart', 'Fresh strawberries on vanilla custard in a crispy shell', 336.00, 'media/pastries/Strawberry Tart.jpg', 'Tarts', 1, 30),
('Blueberry Muffin', 'Soft muffin bursting with fresh blueberries', 196.00, 'media/pastries/Blueberry Muffin.jpg', 'Muffins', 0, 75),
('Cinnamon Roll', 'Warm cinnamon roll with cream cheese frosting', 280.00, 'media/pastries/Cinnamon Roll.jpg', 'Rolls', 1, 40),
('Apple Danish', 'Flaky pastry with caramelized apple filling', 266.00, 'media/pastries/Apple Danish.jpg', 'Danish', 0, 60),
('Lemon Macaron', 'Delicate French macaron with tangy lemon filling', 168.00, 'media/pastries/Lemon Macaron.jpg', 'Macarons', 1, 100),
('Red Velvet Cupcake', 'Moist red velvet cake with cream cheese frosting', 238.00, 'media/pastries/Red Velvet Cupcake.jpg', 'Cupcakes', 1, 45),
('Almond Croissant', 'Flaky croissant filled with almond cream', 294.00, 'media/pastries/Almond Croissant.jpg', 'Croissants', 1, 35),
('Chocolate Eclair', 'Choux pastry filled with chocolate cream', 308.00, 'media/pastries/Chocolate Eclair.jpg', 'Eclairs', 1, 25),
('Vanilla Donut', 'Classic glazed donut with vanilla icing', 154.00, 'media/pastries/Vanilla Donut.jpg', 'Donuts', 0, 80),
('Classic Croissant', 'Traditional French butter croissant, perfectly flaky', 210.00, 'media/pastries/Classic Croissant.jpg', 'Croissants', 0, 55),
('Raspberry Tart', 'Fresh raspberries with pastry cream in a buttery shell', 350.00, 'media/pastries/Raspberry Tart.jpg', 'Tarts', 1, 28),
('Chocolate Muffin', 'Double chocolate muffin with chocolate chips', 210.00, 'media/pastries/Chocolate Muffin.jpg', 'Muffins', 0, 70),
('Pecan Roll', 'Sweet roll topped with caramelized pecans', 308.00, 'media/pastries/Pecan Roll.jpg', 'Rolls', 1, 32),
('Cheese Danish', 'Flaky Danish pastry with sweet cream cheese filling', 252.00, 'media/pastries/Cheese Danish.jpg', 'Danish', 1, 48),
('Pistachio Macaron', 'Green pistachio macaron with smooth pistachio ganache', 182.00, 'media/pastries/Pistachio Macaron.jpg', 'Macarons', 0, 90),
('Vanilla Cupcake', 'Classic vanilla cupcake with buttercream frosting', 210.00, 'media/pastries/Vanilla Cupcake.jpg', 'Cupcakes', 0, 50),
('Coffee Eclair', 'Light pastry with coffee cream and coffee icing', 294.00, 'media/pastries/Coffee Eclair.jpg', 'Eclairs', 0, 22),
('Chocolate Donut', 'Rich chocolate donut with chocolate glaze', 168.00, 'media/pastries/Chocolate Donut.jpg', 'Donuts', 0, 85),
('Banana Nut Muffin', 'Moist banana muffin with walnuts and cinnamon', 196.00, 'media/pastries/Banana Nut Muffin.jpg', 'Muffins', 0, 65)
ON DUPLICATE KEY UPDATE name = name;

-- ============================================
-- SAMPLE DATA: Insert sample customers
-- ============================================

-- First, ensure all customers exist (January & February 2026)
INSERT INTO customers (username, email, password, full_name, phone, address, profile_picture, created_at) VALUES
-- January "Past" Users (Historical Growth)
('davao_foodie88', 'antonio.lucero@gmail.com', 'customer123!', 'Antonio Lucero', '09177001234', 'House 45, Gladiola St., Marfori Heights, Brgy. 10-A, Davao City', 'uploads/profiles/profile_69973792ef2f95.59023159.png', '2026-01-05 08:30:00'),
('durian_queen', 'elena.vargas@gmail.com', 'customer123', 'Elena Vargas', '09188556789', 'Unit 202, Soller Mansion, Cabaguio Ave, Agdao, Davao City', 'uploads/profiles/profile_69973830d999a1.07707870.png', '2026-01-10 14:15:00'),
('poblacion_denizen', 'miguel.tan@gmail.com', 'customer123', 'Miguel Tan', '09203445566', 'Blk 5 Lot 12, Spring Village, Ma-a, Davao City', 'uploads/profiles/profile_69973792ef2f95.59023159.png', '2026-01-22 09:00:00'),

-- February "Active" Users
('jdoe', 'jdoe@gmail.com', 'customer123', 'Juan Dela Cruz', '09171234567', 'Blk 12 Lot 5, Maa, Davao City, Philippines', 'uploads/profiles/profile_69973792ef2f95.59023159.png', '2026-02-01 10:00:00'),
('msantos', 'msantos@gmail.com', 'customer123', 'Maria Santos', '09182345678', 'Door 3, Rizal Street, Poblacion, Davao City, Philippines', 'uploads/profiles/profile_69973830d999a1.07707870.png', '2026-02-05 14:20:00'),
('rgarcia', 'rgarcia@gmail.com', 'customer123', 'Rafael Garcia', '09193456789', 'Lot 8, Buhangin Road, Buhangin, Davao City, Philippines', 'uploads/profiles/profile_69973792ef2f95.59023159.png', '2026-02-10 09:15:00'),
('lreyes', 'lreyes@gmail.com', 'customer123', 'Liza Reyes', '09204567890', 'Phase 2, Catalunan Grande, Davao City, Philippines', 'uploads/profiles/profile_69973830d999a1.07707870.png', '2026-02-15 11:45:00'),
('tlim', 'tlim@gmail.com', 'customer123', 'Timothy Lim', '09215678901', 'Apartment 4B, Matina Aplaya, Davao City, Philippines', 'uploads/profiles/profile_69973792ef2f95.59023159.png', '2026-02-18 16:30:00')
ON DUPLICATE KEY UPDATE username = username;

-- ============================================
-- 2. ORDER DATA (Trends & Growth)
-- ============================================

-- Historical January Orders (for the line chart starting point)
INSERT INTO orders (id, customer_id, customer_name, delivery_address, total_amount, status, order_date) VALUES
(1, 1, 'Antonio Lucero', 'House 45, Gladiola St., Marfori Heights, Brgy. 10-A, Davao City', 1500.00, 'Completed', '2026-01-15 10:30:00'),
(2, 2, 'Elena Vargas', 'Unit 202, Soller Mansion, Cabaguio Ave, Agdao, Davao City', 2800.00, 'Completed', '2026-01-20 14:45:00'),
(3, 3, 'Miguel Tan', 'Blk 5 Lot 12, Spring Village, Ma-a, Davao City', 3200.00, 'Completed', '2026-01-28 09:15:00');

-- Today's Hourly Trend Orders (February 19, 2026)
INSERT INTO orders (id, customer_id, customer_name, delivery_address, total_amount, status, order_date) VALUES
(4, 4, 'Juan Dela Cruz', 'Blk 12 Lot 5, Maa, Davao City, Philippines', 504.00, 'Completed', CONCAT(CURDATE(), ' 07:15:00')),
(5, 5, 'Maria Santos', 'Door 3, Rizal Street, Poblacion, Davao City, Philippines', 336.00, 'Completed', CONCAT(CURDATE(), ' 08:30:00')),
(6, 6, 'Rafael Garcia', 'Lot 8, Buhangin Road, Buhangin, Davao City, Philippines', 1232.00, 'Completed', CONCAT(CURDATE(), ' 09:05:00')),
(7, 7, 'Liza Reyes', 'Phase 2, Catalunan Grande, Davao City, Philippines', 850.00, 'Processing', CONCAT(CURDATE(), ' 09:45:00')),
(8, 8, 'Timothy Lim', 'Apartment 4B, Matina Aplaya, Davao City, Philippines', 210.00, 'Pending', CONCAT(CURDATE(), ' 11:10:00')),
(9, 4, 'Juan Dela Cruz', 'Blk 12 Lot 5, Maa, Davao City, Philippines', 504.00, 'Completed', CONCAT(CURDATE(), ' 12:05:00'));


-- ============================================
-- 3. ORDER ITEMS (Linking Products to Sales)
-- ============================================

INSERT INTO order_items (order_id, pastry_id, pastry_name, quantity, price, subtotal) VALUES
-- Items for January Orders
(1, 1, 'Chocolate Croissant', 5, 300.00, 1500.00),
(2, 2, 'Strawberry Tart', 8, 350.00, 2800.00),
(3, 9, 'Chocolate Eclair', 10, 320.00, 3200.00),
-- Items for Today's Orders
(4, 1, 'Chocolate Croissant', 2, 252.00, 504.00),
(5, 2, 'Strawberry Tart', 1, 336.00, 336.00),
(6, 9, 'Chocolate Eclair', 4, 308.00, 1232.00),
(7, 4, 'Cinnamon Roll', 3, 280.00, 840.00),
(8, 11, 'Classic Croissant', 1, 210.00, 210.00),
(9, 19, 'Chocolate Donut', 3, 168.00, 504.00);


-- ============================================
-- 4. DASHBOARD LOGIC & STATUS UPDATES
-- ============================================

-- Categories are already set correctly in the INSERT statement above.
-- Keeping specific categories (Tarts, Muffins, etc.) for better dynamic filtering.

-- Trigger Low Stock Alert for Dashboard
UPDATE pastries SET stock_quantity = 3 WHERE name = 'Chocolate Eclair';

-- Set the "Top Treat" Highlight Card
UPDATE pastries SET is_featured = 1 WHERE name = 'Chocolate Eclair';