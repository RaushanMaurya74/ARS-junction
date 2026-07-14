-- ARS JUNCTION PostgreSQL Schema and Seed Data for Supabase
-- Compatible with PostgreSQL 12+

-- -------------------------------------------------------------
-- 1. Compatibility Utilities
-- -------------------------------------------------------------

-- Create a compatible rand() function to mimic MySQL's RAND()
CREATE OR REPLACE FUNCTION rand()
RETURNS double precision AS $$
BEGIN
    RETURN random();
END;
$$ LANGUAGE plpgsql;

-- Trigger function to automatically update the updated_at column
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- -------------------------------------------------------------
-- 2. Tables Schema Definition
-- -------------------------------------------------------------

-- Table: users
DROP TABLE IF EXISTS users CASCADE;
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    name varchar(100) NOT NULL,
    email varchar(100) UNIQUE NOT NULL,
    password varchar(255) NOT NULL,
    phone varchar(15) DEFAULT NULL,
    address text DEFAULT NULL,
    city varchar(50) DEFAULT NULL,
    state varchar(50) DEFAULT NULL,
    zip_code varchar(10) DEFAULT NULL,
    profile_image varchar(255) DEFAULT NULL,
    social_id varchar(100) DEFAULT NULL,
    social_type varchar(20) DEFAULT 'normal' CHECK (social_type IN ('facebook', 'google', 'normal')),
    is_admin smallint DEFAULT 0,
    is_delivery_boy smallint DEFAULT 0,
    is_online smallint DEFAULT 0,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER tr_users_updated_at
BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Table: categories
DROP TABLE IF EXISTS categories CASCADE;
CREATE TABLE categories (
    category_id SERIAL PRIMARY KEY,
    name varchar(50) NOT NULL,
    description text DEFAULT NULL,
    image varchar(255) DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER tr_categories_updated_at
BEFORE UPDATE ON categories
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Table: restaurants
DROP TABLE IF EXISTS restaurants CASCADE;
CREATE TABLE restaurants (
    restaurant_id SERIAL PRIMARY KEY,
    name varchar(100) NOT NULL,
    description text DEFAULT NULL,
    address text NOT NULL,
    city varchar(50) NOT NULL,
    state varchar(50) NOT NULL,
    zip_code varchar(10) NOT NULL,
    phone varchar(15) NOT NULL,
    email varchar(100) DEFAULT NULL,
    image varchar(255) DEFAULT NULL,
    opening_time time DEFAULT NULL,
    closing_time time DEFAULT NULL,
    delivery_time int DEFAULT NULL, -- Average delivery time in minutes
    delivery_fee decimal(5,2) DEFAULT 0.00,
    minimum_order decimal(6,2) DEFAULT 0.00,
    is_active smallint DEFAULT 1,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER tr_restaurants_updated_at
BEFORE UPDATE ON restaurants
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Table: menu_items
DROP TABLE IF EXISTS menu_items CASCADE;
CREATE TABLE menu_items (
    item_id SERIAL PRIMARY KEY,
    restaurant_id int NOT NULL,
    category_id int NOT NULL,
    name varchar(100) NOT NULL,
    description text DEFAULT NULL,
    price decimal(6,2) NOT NULL,
    image varchar(255) DEFAULT NULL,
    is_vegetarian smallint DEFAULT 0,
    is_spicy smallint DEFAULT 0,
    is_available smallint DEFAULT 1,
    is_featured smallint DEFAULT 0,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants (restaurant_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE CASCADE
);

CREATE TRIGGER tr_menu_items_updated_at
BEFORE UPDATE ON menu_items
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Table: cart
DROP TABLE IF EXISTS cart CASCADE;
CREATE TABLE cart (
    cart_id SERIAL PRIMARY KEY,
    user_id int NOT NULL,
    item_id int NOT NULL,
    quantity int NOT NULL DEFAULT 1,
    notes text DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, item_id),
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items (item_id) ON DELETE CASCADE
);

CREATE TRIGGER tr_cart_updated_at
BEFORE UPDATE ON cart
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Table: orders
DROP TABLE IF EXISTS orders CASCADE;
CREATE TABLE orders (
    order_id SERIAL PRIMARY KEY,
    user_id int NOT NULL,
    delivery_boy_id int DEFAULT NULL,
    restaurant_id int NOT NULL,
    order_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at timestamp DEFAULT NULL,
    delivery_address text NOT NULL,
    delivery_phone varchar(15) NOT NULL,
    subtotal decimal(10,2) NOT NULL,
    delivery_fee decimal(5,2) DEFAULT 0.00,
    tax decimal(6,2) DEFAULT 0.00,
    total_amount decimal(10,2) NOT NULL,
    payment_method varchar(20) DEFAULT 'cash' CHECK (payment_method IN ('cash', 'card', 'wallet', 'upi')),
    payment_status varchar(20) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'paid', 'failed')),
    order_status varchar(20) DEFAULT 'pending' CHECK (order_status IN ('pending', 'confirmed', 'preparing', 'on the way', 'delivered', 'cancelled')),
    notes text DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_boy_id) REFERENCES users (user_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants (restaurant_id) ON DELETE CASCADE
);

CREATE TRIGGER tr_orders_updated_at
BEFORE UPDATE ON orders
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Table: order_items
DROP TABLE IF EXISTS order_items CASCADE;
CREATE TABLE order_items (
    order_item_id SERIAL PRIMARY KEY,
    order_id int NOT NULL,
    item_id int NOT NULL,
    quantity int NOT NULL,
    price decimal(6,2) NOT NULL,
    notes text DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items (item_id) ON DELETE CASCADE
);

-- Table: reviews
DROP TABLE IF EXISTS reviews CASCADE;
CREATE TABLE reviews (
    review_id SERIAL PRIMARY KEY,
    user_id int NOT NULL,
    restaurant_id int NOT NULL,
    order_id int DEFAULT NULL,
    rating int NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment text DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants (restaurant_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE SET NULL
);

-- Table: site_settings
DROP TABLE IF EXISTS site_settings CASCADE;
CREATE TABLE site_settings (
    setting_key varchar(50) PRIMARY KEY,
    setting_value text NOT NULL
);

-- Table: contact_messages
DROP TABLE IF EXISTS contact_messages CASCADE;
CREATE TABLE contact_messages (
    message_id SERIAL PRIMARY KEY,
    name varchar(100) NOT NULL,
    email varchar(100) NOT NULL,
    subject varchar(150) NOT NULL,
    message text NOT NULL,
    status varchar(20) DEFAULT 'new' CHECK (status IN ('new', 'read', 'replied')),
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table: delivery_pincodes
DROP TABLE IF EXISTS delivery_pincodes CASCADE;
CREATE TABLE delivery_pincodes (
    pincode_id SERIAL PRIMARY KEY,
    pincode varchar(10) NOT NULL UNIQUE,
    area_name varchar(100) NOT NULL,
    delivery_charge decimal(10,2) NOT NULL DEFAULT 0.00,
    is_active smallint NOT NULL DEFAULT 1,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER tr_delivery_pincodes_updated_at
BEFORE UPDATE ON delivery_pincodes
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();


-- -------------------------------------------------------------
-- 3. Data Seeding
-- -------------------------------------------------------------

-- Seeds: categories
INSERT INTO categories (category_id, name, description, image, created_at, updated_at) VALUES 
(1,'Pizza','Delicious pizzas with various toppings',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),
(2,'Burger','Juicy burgers with fresh ingredients',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),
(3,'Indian','Authentic Indian cuisine',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),
(4,'Chinese','Traditional Chinese dishes',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),
(5,'Italian','Classic Italian pasta and more',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),
(6,'Desserts','Sweet treats to satisfy your cravings',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),
(7,'Beverages','Refreshing drinks and juices',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32');

-- Seeds: restaurants
INSERT INTO restaurants (restaurant_id, name, description, address, city, state, zip_code, phone, email, image, opening_time, closing_time, delivery_time, delivery_fee, minimum_order, is_active, created_at, updated_at) VALUES 
(1,'Pizza Paradise','Best pizzas in town with authentic Italian flavors','123 Pizza Street','Piro','Bihar','802207','9876543210','pizzaparadise@example.com','uploads/restaurants/res_sample_1.jpg',NULL,NULL,30,20.00,100.00,1,'2026-07-01 18:17:32','2026-07-01 23:20:05'),
(2,'Burger Junction','Juicy and delicious burgers with fresh ingredients','456 Burger Avenue','Piro','Bihar','802207','8765432109','burgerjunction@example.com','uploads/restaurants/res_sample_2.jpg',NULL,NULL,25,15.00,80.00,1,'2026-07-01 18:17:32','2026-07-01 23:20:05'),
(3,'Spice Garden','Authentic Indian cuisine with rich flavors','789 Spice Road','Piro','Bihar','802207','7654321098','spicegarden@example.com','uploads/restaurants/res_sample_3.jpg',NULL,NULL,35,25.00,120.00,1,'2026-07-01 18:17:32','2026-07-01 23:20:06'),
(4,'Dragon Wok','Traditional Chinese dishes prepared by expert chefs','101 Dragon Street','Bhojpur','Bihar','802207','6543210987','dragonwok@example.com','uploads/restaurants/res_sample_4.jpg',NULL,NULL,40,30.00,150.00,1,'2026-07-01 18:17:32','2026-07-01 23:20:06');

-- Seeds: menu_items
INSERT INTO menu_items (item_id, restaurant_id, category_id, name, description, price, image, is_vegetarian, is_spicy, is_available, is_featured, created_at, updated_at) VALUES 
(1, 1, 1, 'Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella, and basil', 199.00, 'uploads/menu/food_sample_1.jpg', 1, 0, 1, 1, '2026-07-01 18:17:32', '2026-07-01 23:20:07'),
(2, 1, 1, 'Pepperoni Pizza', 'Pizza topped with pepperoni slices', 249.00, 'uploads/menu/food_sample_2.jpg', 0, 1, 1, 0, '2026-07-01 18:17:32', '2026-07-01 23:20:07'),
(3, 1, 6, 'Chocolate Brownie', 'Warm chocolate brownie with vanilla ice cream', 149.00, 'uploads/menu/food_sample_3.jpg', 1, 0, 1, 0, '2026-07-01 18:17:32', '2026-07-01 23:20:08'),
(4, 1, 7, 'Cold Coffee', 'Refreshing cold coffee with ice cream', 99.00, 'uploads/menu/food_sample_4.jpg', 1, 0, 1, 0, '2026-07-01 18:17:32', '2026-07-01 23:20:08'),
(5, 2, 2, 'Classic Cheeseburger', 'Juicy beef patty with cheese, lettuce, and tomato', 179.00, 'uploads/menu/food_sample_5.jpg', 0, 0, 1, 1, '2026-07-01 18:17:32', '2026-07-01 23:20:08'),
(6, 2, 2, 'Veggie Burger', 'Plant-based patty with fresh vegetables', 149.00, 'uploads/menu/food_sample_6.jpg', 1, 0, 1, 1, '2026-07-01 18:17:32', '2026-07-01 23:20:09'),
(7, 2, 7, 'Strawberry Milkshake', 'Creamy milkshake with fresh strawberries', 119.00, 'uploads/menu/food_sample_7.jpg', 1, 0, 1, 0, '2026-07-01 18:17:32', '2026-07-01 23:20:09'),
(8, 2, 6, 'Apple Pie', 'Warm apple pie with cinnamon', 129.00, 'uploads/menu/food_sample_8.jpg', 1, 0, 1, 0, '2026-07-01 18:17:32', '2026-07-01 23:20:09'),
(9, 3, 3, 'Butter Chicken', 'Creamy tomato-based curry with tender chicken pieces', 299.00, 'uploads/menu/food_sample_9.jpg', 0, 1, 1, 1, '2026-07-01 18:17:32', '2026-07-01 23:20:10'),
(10, 3, 3, 'Paneer Tikka Masala', 'Grilled cottage cheese cubes in spicy gravy', 249.00, 'uploads/menu/food_sample_10.jpg', 1, 1, 1, 1, '2026-07-01 18:17:32', '2026-07-01 23:20:10'),
(11, 3, 3, 'Garlic Naan', 'Soft flatbread with garlic flavor', 49.00, NULL, 1, 0, 1, 0, '2026-07-01 18:17:32', '2026-07-01 18:17:32'),
(12, 3, 7, 'Mango Lassi', 'Refreshing yogurt-based drink with mango pulp', 89.00, 'uploads/menu/food_sample_12.jpg', 1, 0, 1, 0, '2026-07-01 18:17:32', '2026-07-01 23:20:10'),
(13, 4, 4, 'Kung Pao Chicken', 'Spicy stir-fried chicken with peanuts and vegetables', 269.00, 'uploads/menu/food_sample_13.jpg', 0, 1, 1, 1, '2026-07-01 18:17:32', '2026-07-01 23:20:11'),
(14, 4, 4, 'Veg Hakka Noodles', 'Stir-fried noodles with mixed vegetables', 199.00, 'uploads/menu/food_sample_14.jpg', 1, 0, 1, 1, '2026-07-01 18:17:32', '2026-07-01 23:20:11'),
(15, 4, 4, 'Manchurian', 'Deep-fried vegetable balls in savory sauce', 229.00, 'uploads/menu/food_sample_15.jpg', 1, 1, 1, 0, '2026-07-01 18:17:32', '2026-07-01 23:20:12'),
(16, 4, 7, 'Lemon Iced Tea', 'Refreshing tea with lemon flavor', 79.00, 'uploads/menu/food_sample_16.jpg', 1, 0, 1, 0, '2026-07-01 18:17:32', '2026-07-01 23:20:12');

-- Seeds: users
INSERT INTO users (user_id, name, email, password, phone, address, city, state, zip_code, profile_image, social_id, social_type, is_admin, is_delivery_boy, created_at, updated_at) VALUES 
(1,'Admin User','maurya@arsjunction.com','$2y$10$YPC1BHClCpawk9jNcW.faeeXqAY/uYYMDkmHKDZPISls2mevpyE8W','7979730721','AT - PIRO','BHOJPUR','BIHAR','802207',NULL,NULL,'normal',1,0,'2026-07-01 18:17:32','2026-07-01 18:24:53'),
(2,'Demo Customer','customer@arsjunction.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','9876543210','Piro Main Road','Piro','Bihar','802207',NULL,NULL,'normal',0,0,'2026-07-01 18:17:32','2026-07-01 18:17:32'),
(3,'Raushan Maurya','raushanmaurya74@gmail.com','$2y$10$L0MOPQAzZbT.9DscyXQS6u03rGTe826WjSr6M3k6Eoerz0BS/gSOq','07979730721',NULL,NULL,NULL,NULL,NULL,NULL,'normal',0,0,'2026-07-01 18:21:34','2026-07-01 18:21:34'),
(4,'aniket','admin@arsjunction.com','$2y$10$IquN8TmZkSMWaCZjzPu8meh.SXlpXtfQddkP9f1Zo8kyMfjn.lD7C','07979730721','raushanmaurya74@gmail.com','','','',NULL,NULL,'normal',0,1,'2026-07-01 18:43:32','2026-07-01 18:43:32');

-- Seeds: orders
INSERT INTO orders (order_id, user_id, delivery_boy_id, restaurant_id, order_date, delivery_address, delivery_phone, subtotal, delivery_fee, tax, total_amount, payment_method, payment_status, order_status, notes, created_at, updated_at) VALUES 
(1,2,4,1,'2026-07-01 18:17:32','Piro Main Road, Piro, Bihar - 802207','9876543210',448.00,20.00,22.40,490.40,'cash','paid','delivered','Please call before delivery.','2026-07-01 18:17:32','2026-07-01 19:54:59'),
(2,2,4,3,'2026-07-01 18:17:32','Piro Main Road, Piro, Bihar - 802207','9876543210',348.00,25.00,17.40,390.40,'wallet','paid','delivered','Extra spicy.','2026-07-01 18:17:32','2026-07-01 19:37:42'),
(3,3,4,4,'2026-07-01 18:22:47','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',956.00,30.00,47.80,1033.80,'cash','paid','delivered','vdhjg','2026-07-01 18:22:47','2026-07-01 19:55:00'),
(4,3,4,1,'2026-07-01 18:35:27','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',199.00,20.00,9.95,228.95,'wallet','paid','delivered','ara','2026-07-01 18:35:27','2026-07-01 19:10:33'),
(5,3,4,4,'2026-07-01 18:40:42','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',269.00,30.00,13.45,312.45,'wallet','paid','delivered','','2026-07-01 18:40:42','2026-07-01 19:04:01'),
(6,3,4,4,'2026-07-01 18:54:31','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',687.00,30.00,34.35,751.35,'upi','paid','delivered','','2026-07-01 18:54:31','2026-07-01 18:59:03'),
(7,3,4,1,'2026-07-01 18:55:03','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',398.00,20.00,19.90,437.90,'upi','paid','delivered','','2026-07-01 18:55:03','2026-07-01 18:58:00'),
(8,4,4,2,'2026-07-01 19:02:22','raushanmaurya74@gmail.com','07979730721',595.00,15.00,29.75,639.75,'upi','paid','delivered','','2026-07-01 19:02:22','2026-07-01 19:04:00'),
(9,4,4,4,'2026-07-01 19:11:36','raushanmaurya74@gmail.com','07979730721',538.00,30.00,26.90,594.90,'cash','paid','delivered','','2026-07-01 19:11:36','2026-07-01 19:12:30'),
(10,3,4,1,'2026-07-01 19:15:35','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',1245.00,20.00,62.25,1327.25,'cash','paid','delivered','','2026-07-01 19:15:35','2026-07-01 19:24:35'),
(11,3,4,2,'2026-07-01 19:16:04','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',179.00,15.00,8.95,202.95,'upi','paid','delivered','','2026-07-01 19:16:04','2026-07-01 19:20:25'),
(12,3,4,4,'2026-07-01 19:22:11','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',79.00,30.00,3.95,112.95,'upi','paid','delivered','','2026-07-01 19:22:11','2026-07-01 19:24:34'),
(13,3,4,1,'2026-07-01 19:27:44','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',99.00,20.00,4.95,123.95,'cash','paid','delivered','','2026-07-01 19:27:44','2026-07-01 19:38:08'),
(14,3,4,3,'2026-07-01 19:31:38','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',49.00,25.00,2.45,76.45,'upi','paid','delivered','','2026-07-01 19:31:38','2026-07-01 19:54:15'),
(15,3,4,2,'2026-07-01 20:11:12','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',149.00,15.00,7.45,171.45,'upi','paid','delivered','','2026-07-01 20:11:12','2026-07-01 20:14:39'),
(16,3,NULL,2,'2026-07-01 20:11:28','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',358.00,15.00,17.90,390.90,'cash','failed','cancelled','','2026-07-01 20:11:28','2026-07-01 20:12:13');

-- Seeds: order_items
INSERT INTO order_items (order_item_id, order_id, item_id, quantity, price, notes) VALUES 
(1,1,1,1,199.00,NULL),
(2,1,2,1,249.00,NULL),
(3,2,9,1,299.00,NULL),
(4,2,11,1,49.00,NULL),
(5,3,13,1,269.00,NULL),
(6,3,15,3,229.00,NULL),
(7,4,1,1,199.00,NULL),
(8,5,13,1,269.00,NULL),
(9,6,15,3,229.00,NULL),
(10,7,1,2,199.00,NULL),
(11,8,7,5,119.00,NULL),
(12,9,13,2,269.00,NULL),
(13,10,2,5,249.00,NULL),
(14,11,5,1,179.00,NULL),
(15,12,16,1,79.00,NULL),
(16,13,4,1,99.00,NULL),
(17,14,11,1,49.00,NULL),
(18,15,6,1,149.00,NULL),
(19,16,5,2,179.00,NULL);

-- Seeds: reviews
INSERT INTO reviews (review_id, user_id, restaurant_id, order_id, rating, comment, created_at) VALUES 
(1,2,1,1,5,'Fresh pizza and quick delivery. ARS JUNCTION made ordering very easy.','2026-07-01 18:17:32'),
(2,2,3,2,4,'Great Indian food and helpful support.','2026-07-01 18:17:32'),
(3,3,4,NULL,1,'good','2026-07-01 18:41:08'),
(4,4,2,NULL,2,'choklha','2026-07-01 19:10:57');

-- Seeds: site_settings
INSERT INTO site_settings (setting_key, setting_value) VALUES 
('currency_symbol','₹'),
('delivery_fee_default','50.00'),
('site_email','officialarsjunction@gmail.com'),
('site_name','ARS Junction'),
('site_phone','7979730721'),
('tax_rate_default','5.00'),
('upi_id','7979730721@rapl'),
('facebook_app_id','YOUR_FACEBOOK_APP_ID'),
('facebook_app_secret','YOUR_FACEBOOK_APP_SECRET'),
('google_client_id','YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'),
('google_client_secret','YOUR_GOOGLE_CLIENT_SECRET'),
('facebook_login_enabled','1'),
('google_login_enabled','1');

-- Seeds: contact_messages
INSERT INTO contact_messages (message_id, name, email, subject, message, status, created_at) VALUES 
(1,'Rahul Kumar','rahul@example.com','Party order enquiry','Can ARS JUNCTION arrange a bulk food order for a family event in Piro?','new','2026-07-01 18:17:32'),
(2,'Raushan Maurya','raushanmaurya74@gmail.com','hfhf','all good','new','2026-07-01 18:39:14');

-- Seeds: delivery_pincodes
INSERT INTO delivery_pincodes (pincode, area_name, delivery_charge, is_active) VALUES
('802207', 'Piro Main Area', 20.00, 1),
('802201', 'Ara City Junction', 40.00, 1),
('802202', 'Charpokhari Bazaar', 30.00, 1),
('802206', 'Tarari Block', 30.00, 1),
('802208', 'Bihsi Village Area', 25.00, 1),
('110001', 'Connaught Place (Delhi Dev Mock)', 50.00, 1),
('400001', 'Fort Mumbai (Mock Delivery)', 60.00, 1);


-- -------------------------------------------------------------
-- 4. Adjust Sequences
-- -------------------------------------------------------------

-- Set sequences to start after the highest seeded IDs
SELECT setval('users_user_id_seq', COALESCE((SELECT MAX(user_id)+1 FROM users), 1), false);
SELECT setval('categories_category_id_seq', COALESCE((SELECT MAX(category_id)+1 FROM categories), 1), false);
SELECT setval('restaurants_restaurant_id_seq', COALESCE((SELECT MAX(restaurant_id)+1 FROM restaurants), 1), false);
SELECT setval('menu_items_item_id_seq', COALESCE((SELECT MAX(item_id)+1 FROM menu_items), 1), false);
SELECT setval('cart_cart_id_seq', COALESCE((SELECT MAX(cart_id)+1 FROM cart), 1), false);
SELECT setval('orders_order_id_seq', COALESCE((SELECT MAX(order_id)+1 FROM orders), 1), false);
SELECT setval('order_items_order_item_id_seq', COALESCE((SELECT MAX(order_item_id)+1 FROM order_items), 1), false);
SELECT setval('reviews_review_id_seq', COALESCE((SELECT MAX(review_id)+1 FROM reviews), 1), false);
SELECT setval('contact_messages_message_id_seq', COALESCE((SELECT MAX(message_id)+1 FROM contact_messages), 1), false);
SELECT setval('delivery_pincodes_pincode_id_seq', COALESCE((SELECT MAX(pincode_id)+1 FROM delivery_pincodes), 1), false);
