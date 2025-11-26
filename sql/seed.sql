-- Cafe Manager Database Setup
-- Import this file into phpMyAdmin or run via MySQL command line

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS cafe_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE cafe_manager;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	email VARCHAR(150) NOT NULL UNIQUE,
	password_hash VARCHAR(255) NOT NULL,
	role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
	login_attempts INT NOT NULL DEFAULT 0,
	lock_until DATETIME NULL,
	created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE menu_items (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(150) NOT NULL,
	description TEXT NOT NULL,
	price DECIMAL(8,2) NOT NULL,
	image VARCHAR(255) NULL,
	available TINYINT(1) NOT NULL DEFAULT 1,
	created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL,
	total DECIMAL(10,2) NOT NULL,
	status ENUM('pending','preparing','delivered') NOT NULL DEFAULT 'pending',
	created_at DATETIME NOT NULL,
	CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_id INT NOT NULL,
	menu_item_id INT NOT NULL,
	quantity INT NOT NULL,
	price DECIMAL(8,2) NOT NULL,
	CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
	CONSTRAINT fk_oi_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_status_history (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_id INT NOT NULL,
	status ENUM('pending','preparing','delivered') NOT NULL,
	changed_by_user_id INT NOT NULL,
	changed_at DATETIME NOT NULL,
	CONSTRAINT fk_osh_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
	CONSTRAINT fk_osh_user FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE feedback (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL,
	order_id INT NULL,
	rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
	comment TEXT,
	created_at DATETIME NOT NULL,
	CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_feedback_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed admin user (password: Admin@123)
-- Note: If this hash doesn't work, run setup_admin.php after importing the database
-- The hash below is a placeholder - setup_admin.php will generate the correct one
INSERT INTO users (name, email, password_hash, role, created_at) VALUES
('Administrator', 'admin@local.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW());
-- IMPORTANT: After importing, run setup_admin.php to ensure the admin password works correctly

-- Seed menu items
INSERT INTO menu_items (name, description, price, image, available, created_at) VALUES
('Espresso', 'Strong and bold espresso shot.', 2.50, NULL, 1, NOW()),
('Latte', 'Smooth latte with milk foam.', 3.75, NULL, 1, NOW()),
('Sandwich', 'Fresh sandwich with veggies.', 5.50, NULL, 1, NOW()),
('Cake', 'Slice of daily special cake.', 3.25, NULL, 1, NOW()),
('Tea', 'Hot brewed tea.', 2.00, NULL, 1, NOW()),
('Smoothie', 'Fruit smoothie blend.', 4.50, NULL, 1, NOW());

-- Optional sample orders
INSERT INTO orders (user_id, total, status, created_at) VALUES (1, 8.00, 'pending', NOW());
SET @oid := LAST_INSERT_ID();
INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES
(@oid, 1, 1, 2.50),
(@oid, 2, 1, 3.75),
(@oid, 5, 1, 2.00);
INSERT INTO order_status_history (order_id, status, changed_by_user_id, changed_at) VALUES
(@oid, 'pending', 1, NOW());

-- Optional feedback
INSERT INTO feedback (user_id, order_id, rating, comment, created_at) VALUES
(1, @oid, 5, 'Great coffee!', NOW()),
(1, NULL, 4, 'Nice ambiance.', NOW());

