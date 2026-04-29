-- ============================================================
-- Catering Service Management System — Full Schema + Seed
-- ============================================================

CREATE DATABASE IF NOT EXISTS catering_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE catering_db;

-- ── users ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    phone      VARCHAR(20)  DEFAULT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB;

-- ── menu_items ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS menu_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)  NOT NULL,
    description TEXT          DEFAULT NULL,
    price       DECIMAL(10,2) NOT NULL,
    category    VARCHAR(80)   NOT NULL,
    image_url   VARCHAR(500)  DEFAULT NULL,
    available   TINYINT(1)    DEFAULT 1,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- ── packages ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS packages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)  NOT NULL,
    description TEXT          DEFAULT NULL,
    price       DECIMAL(10,2) NOT NULL,
    min_guests  INT           DEFAULT 1,
    max_guests  INT           DEFAULT 500,
    image_url   VARCHAR(500)  DEFAULT NULL,
    available   TINYINT(1)    DEFAULT 1,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── package_items (junction) ────────────────────────────────
CREATE TABLE IF NOT EXISTS package_items (
    package_id INT NOT NULL,
    item_id    INT NOT NULL,
    quantity   INT DEFAULT 1,
    PRIMARY KEY (package_id, item_id),
    FOREIGN KEY (package_id) REFERENCES packages(id)    ON DELETE CASCADE,
    FOREIGN KEY (item_id)    REFERENCES menu_items(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── orders ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    customer_id    INT           NOT NULL,
    event_type     VARCHAR(80)   NOT NULL,
    event_date     DATE          NOT NULL,
    event_location VARCHAR(255)  DEFAULT NULL,
    num_guests     INT           NOT NULL,
    package_id     INT           DEFAULT NULL,
    special_notes  TEXT          DEFAULT NULL,
    total_amount   DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('Pending','Paid','Cancelled')                          DEFAULT 'Pending',
    order_status   ENUM('New','Confirmed','In Progress','Completed','Cancelled') DEFAULT 'New',
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (package_id)  REFERENCES packages(id) ON DELETE SET NULL,
    INDEX idx_customer   (customer_id),
    INDEX idx_event_date (event_date),
    INDEX idx_payment    (payment_status)
) ENGINE=InnoDB;

-- ── order_details (custom items per order) ──────────────────
CREATE TABLE IF NOT EXISTS order_details (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL,
    item_id    INT           NOT NULL,
    quantity   INT           NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id)     ON DELETE CASCADE,
    FOREIGN KEY (item_id)  REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- Password for admin = "password"
-- ============================================================
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin', 'admin@catering.com', '0300-0000000',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Menu items with reliable Unsplash photo URLs
INSERT INTO menu_items (name, description, price, category, image_url) VALUES
('Chicken BBQ',   'Smoky grilled chicken marinated with herbs and spices',    850.00, 'Main Course', 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=400&q=80'),
('Beef Biryani',  'Aromatic slow-cooked beef biryani with saffron rice',     1200.00, 'Main Course', 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=400&q=80'),
('Mutton Karahi', 'Rich spiced mutton karahi cooked in a wok',               1500.00, 'Main Course', 'https://images.unsplash.com/photo-1574894709920-11b28e7367e3?w=400&q=80'),
('Naan',          'Freshly baked traditional naan from clay oven',              60.00, 'Bread',       'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=400&q=80'),
('Raita',         'Creamy yogurt dip with cucumber and spices',                120.00, 'Sides',       'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=400&q=80'),
('Fresh Salad',   'Garden fresh seasonal salad with dressing',                 200.00, 'Sides',       'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&q=80'),
('Kheer',         'Traditional creamy rice pudding with cardamom',             180.00, 'Dessert',     'https://images.unsplash.com/photo-1571167369282-4c6e55c38f97?w=400&q=80'),
('Gulab Jamun',   'Soft milk solid dumplings soaked in rose sugar syrup',      150.00, 'Dessert',     'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400&q=80'),
('Soft Drinks',   'Assorted chilled canned and bottled beverages',             100.00, 'Beverages',   'https://images.unsplash.com/photo-1527960471264-932f39eb5846?w=400&q=80'),
('Fresh Juice',   'Seasonal fresh fruit juices — orange, mango, pomegranate', 150.00, 'Beverages',   'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=400&q=80');

INSERT INTO packages (name, description, price, min_guests, max_guests, image_url) VALUES
('Silver Package',
 'Perfect for intimate gatherings. Includes Chicken BBQ, Naan, Raita, Fresh Salad, and Soft Drinks.',
 950.00, 50, 150,
 'https://images.unsplash.com/photo-1555244162-803834f70033?w=600&q=80'),

('Gold Package',
 'Ideal for weddings. Includes Beef Biryani, Mutton Karahi, Naan, Raita, Salad, Kheer, Gulab Jamun, Fresh Juice.',
 1800.00, 150, 400,
 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80'),

('Platinum Package',
 'Full luxury experience with all menu items, dedicated staff, and premium table setup.',
 2500.00, 300, 1000,
 'https://images.unsplash.com/photo-1529543544282-ea669407fca3?w=600&q=80');

-- Silver → Chicken BBQ(1), Naan(4), Raita(5), Fresh Salad(6), Soft Drinks(9)
INSERT INTO package_items VALUES (1,1,1),(1,4,2),(1,5,1),(1,6,1),(1,9,1);
-- Gold → Beef Biryani(2), Mutton Karahi(3), Naan(4), Raita(5), Salad(6), Kheer(7), Gulab Jamun(8), Juice(10)
INSERT INTO package_items VALUES (2,2,1),(2,3,1),(2,4,2),(2,5,1),(2,6,1),(2,7,1),(2,8,1),(2,10,1);
-- Platinum → All items
INSERT INTO package_items VALUES (3,1,1),(3,2,1),(3,3,1),(3,4,3),(3,5,1),(3,6,1),(3,7,1),(3,8,1),(3,9,1),(3,10,1);
