-- Database creation and structure for ARS JUNCTION Food Ordering Platform

CREATE DATABASE IF NOT EXISTS ars_junction;
USE ars_junction;

-- Users table to store user information
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    zip_code VARCHAR(10),
    profile_image VARCHAR(255),
    social_id VARCHAR(100),
    social_type ENUM('facebook', 'google', 'normal') DEFAULT 'normal',
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Restaurants table
CREATE TABLE restaurants (
    restaurant_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    image VARCHAR(255),
    opening_time TIME,
    closing_time TIME,
    delivery_time INT COMMENT 'Average delivery time in minutes',
    delivery_fee DECIMAL(5,2) DEFAULT 0.00,
    minimum_order DECIMAL(6,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Food categories table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Menu items table
CREATE TABLE menu_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(6,2) NOT NULL,
    image VARCHAR(255),
    is_vegetarian BOOLEAN DEFAULT FALSE,
    is_spicy BOOLEAN DEFAULT FALSE,
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(restaurant_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_address TEXT NOT NULL,
    delivery_phone VARCHAR(15) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(5,2) DEFAULT 0.00,
    tax DECIMAL(6,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'wallet') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'preparing', 'on the way', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(restaurant_id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(6,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
);

-- Cart table
CREATE TABLE cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, item_id)
);

-- Reviews table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    order_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(restaurant_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Insert sample data for food categories
INSERT INTO categories (name, description) VALUES
('Pizza', 'Delicious pizzas with various toppings'),
('Burger', 'Juicy burgers with fresh ingredients'),
('Indian', 'Authentic Indian cuisine'),
('Chinese', 'Traditional Chinese dishes'),
('Italian', 'Classic Italian pasta and more'),
('Desserts', 'Sweet treats to satisfy your cravings'),
('Beverages', 'Refreshing drinks and juices');

-- Insert sample restaurants
INSERT INTO restaurants (name, description, address, city, state, zip_code, phone, email, delivery_time, delivery_fee, minimum_order, is_active) VALUES
('Pizza Paradise', 'Best pizzas in town with authentic Italian flavors', '123 Pizza Street', 'Piro', 'Bihar', '802207', '9876543210', 'pizzaparadise@example.com', 30, 20.00, 100.00, TRUE),
('Burger Junction', 'Juicy and delicious burgers with fresh ingredients', '456 Burger Avenue', 'Piro', 'Bihar', '802207', '8765432109', 'burgerjunction@example.com', 25, 15.00, 80.00, TRUE),
('Spice Garden', 'Authentic Indian cuisine with rich flavors', '789 Spice Road', 'Piro', 'Bihar', '802207', '7654321098', 'spicegarden@example.com', 35, 25.00, 120.00, TRUE),
('Dragon Wok', 'Traditional Chinese dishes prepared by expert chefs', '101 Dragon Street', 'Bhojpur', 'Bihar', '802207', '6543210987', 'dragonwok@example.com', 40, 30.00, 150.00, TRUE);

-- Insert sample menu items
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_vegetarian, is_spicy, is_available, is_featured) VALUES
-- Pizza Paradise items
(1, 1, 'Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella, and basil', 199.00, TRUE, FALSE, TRUE, TRUE),
(1, 1, 'Pepperoni Pizza', 'Pizza topped with pepperoni slices', 249.00, FALSE, TRUE, TRUE, FALSE),
(1, 6, 'Chocolate Brownie', 'Warm chocolate brownie with vanilla ice cream', 149.00, TRUE, FALSE, TRUE, FALSE),
(1, 7, 'Cold Coffee', 'Refreshing cold coffee with ice cream', 99.00, TRUE, FALSE, TRUE, FALSE),

-- Burger Junction items
(2, 2, 'Classic Cheeseburger', 'Juicy beef patty with cheese, lettuce, and tomato', 179.00, FALSE, FALSE, TRUE, TRUE),
(2, 2, 'Veggie Burger', 'Plant-based patty with fresh vegetables', 149.00, TRUE, FALSE, TRUE, TRUE),
(2, 7, 'Strawberry Milkshake', 'Creamy milkshake with fresh strawberries', 119.00, TRUE, FALSE, TRUE, FALSE),
(2, 6, 'Apple Pie', 'Warm apple pie with cinnamon', 129.00, TRUE, FALSE, TRUE, FALSE),

-- Spice Garden items
(3, 3, 'Butter Chicken', 'Creamy tomato-based curry with tender chicken pieces', 299.00, FALSE, TRUE, TRUE, TRUE),
(3, 3, 'Paneer Tikka Masala', 'Grilled cottage cheese cubes in spicy gravy', 249.00, TRUE, TRUE, TRUE, TRUE),
(3, 3, 'Garlic Naan', 'Soft flatbread with garlic flavor', 49.00, TRUE, FALSE, TRUE, FALSE),
(3, 7, 'Mango Lassi', 'Refreshing yogurt-based drink with mango pulp', 89.00, TRUE, FALSE, TRUE, FALSE),

-- Dragon Wok items
(4, 4, 'Kung Pao Chicken', 'Spicy stir-fried chicken with peanuts and vegetables', 269.00, FALSE, TRUE, TRUE, TRUE),
(4, 4, 'Veg Hakka Noodles', 'Stir-fried noodles with mixed vegetables', 199.00, TRUE, FALSE, TRUE, TRUE),
(4, 4, 'Manchurian', 'Deep-fried vegetable balls in savory sauce', 229.00, TRUE, TRUE, TRUE, FALSE),
(4, 7, 'Lemon Iced Tea', 'Refreshing tea with lemon flavor', 79.00, TRUE, FALSE, TRUE, FALSE);

-- Insert sample admin user
INSERT INTO users (name, email, password, phone, address, city, state, zip_code, is_admin) VALUES
('Admin User', 'admin@arsjunction.com', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', '7979730721', 'AT - PIRO', 'BHOJPUR', 'BIHAR', '802207', TRUE);
