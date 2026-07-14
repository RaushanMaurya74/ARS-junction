-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ars_junction
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `user_id` (`user_id`,`item_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Pizza','Delicious pizzas with various toppings',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),(2,'Burger','Juicy burgers with fresh ingredients',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),(3,'Indian','Authentic Indian cuisine',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),(4,'Chinese','Traditional Chinese dishes',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),(5,'Italian','Classic Italian pasta and more',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),(6,'Desserts','Sweet treats to satisfy your cravings',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32'),(7,'Beverages','Refreshing drinks and juices',NULL,'2026-07-01 18:17:32','2026-07-01 18:17:32');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'Rahul Kumar','rahul@example.com','Party order enquiry','Can ARS JUNCTION arrange a bulk food order for a family event in Piro?','new','2026-07-01 18:17:32'),(2,'Raushan Maurya','raushanmaurya74@gmail.com','hfhf','all good','new','2026-07-01 18:39:14');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(6,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_vegetarian` tinyint(1) DEFAULT 0,
  `is_spicy` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,1,1,'Margherita Pizza','Classic pizza with tomato sauce, mozzarella, and basil',199.00,'uploads/menu/food_sample_1.jpg',1,0,1,1,'2026-07-01 18:17:32','2026-07-01 23:20:07'),(2,1,1,'Pepperoni Pizza','Pizza topped with pepperoni slices',249.00,'uploads/menu/food_sample_2.jpg',0,1,1,0,'2026-07-01 18:17:32','2026-07-01 23:20:07'),(3,1,6,'Chocolate Brownie','Warm chocolate brownie with vanilla ice cream',149.00,'uploads/menu/food_sample_3.jpg',1,0,1,0,'2026-07-01 18:17:32','2026-07-01 23:20:08'),(4,1,7,'Cold Coffee','Refreshing cold coffee with ice cream',99.00,'uploads/menu/food_sample_4.jpg',1,0,1,0,'2026-07-01 18:17:32','2026-07-01 23:20:08'),(5,2,2,'Classic Cheeseburger','Juicy beef patty with cheese, lettuce, and tomato',179.00,'uploads/menu/food_sample_5.jpg',0,0,1,1,'2026-07-01 18:17:32','2026-07-01 23:20:08'),(6,2,2,'Veggie Burger','Plant-based patty with fresh vegetables',149.00,'uploads/menu/food_sample_6.jpg',1,0,1,1,'2026-07-01 18:17:32','2026-07-01 23:20:09'),(7,2,7,'Strawberry Milkshake','Creamy milkshake with fresh strawberries',119.00,'uploads/menu/food_sample_7.jpg',1,0,1,0,'2026-07-01 18:17:32','2026-07-01 23:20:09'),(8,2,6,'Apple Pie','Warm apple pie with cinnamon',129.00,'uploads/menu/food_sample_8.jpg',1,0,1,0,'2026-07-01 18:17:32','2026-07-01 23:20:09'),(9,3,3,'Butter Chicken','Creamy tomato-based curry with tender chicken pieces',299.00,'uploads/menu/food_sample_9.jpg',0,1,1,1,'2026-07-01 18:17:32','2026-07-01 23:20:10'),(10,3,3,'Paneer Tikka Masala','Grilled cottage cheese cubes in spicy gravy',249.00,'uploads/menu/food_sample_10.jpg',1,1,1,1,'2026-07-01 18:17:32','2026-07-01 23:20:10'),(11,3,3,'Garlic Naan','Soft flatbread with garlic flavor',49.00,NULL,1,0,1,0,'2026-07-01 18:17:32','2026-07-01 18:17:32'),(12,3,7,'Mango Lassi','Refreshing yogurt-based drink with mango pulp',89.00,'uploads/menu/food_sample_12.jpg',1,0,1,0,'2026-07-01 18:17:32','2026-07-01 23:20:10'),(13,4,4,'Kung Pao Chicken','Spicy stir-fried chicken with peanuts and vegetables',269.00,'uploads/menu/food_sample_13.jpg',0,1,1,1,'2026-07-01 18:17:32','2026-07-01 23:20:11'),(14,4,4,'Veg Hakka Noodles','Stir-fried noodles with mixed vegetables',199.00,'uploads/menu/food_sample_14.jpg',1,0,1,1,'2026-07-01 18:17:32','2026-07-01 23:20:11'),(15,4,4,'Manchurian','Deep-fried vegetable balls in savory sauce',229.00,'uploads/menu/food_sample_15.jpg',1,1,1,0,'2026-07-01 18:17:32','2026-07-01 23:20:12'),(16,4,7,'Lemon Iced Tea','Refreshing tea with lemon flavor',79.00,'uploads/menu/food_sample_16.jpg',1,0,1,0,'2026-07-01 18:17:32','2026-07-01 23:20:12');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(6,2) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,1,1,199.00,NULL),(2,1,2,1,249.00,NULL),(3,2,9,1,299.00,NULL),(4,2,11,1,49.00,NULL),(5,3,13,1,269.00,NULL),(6,3,15,3,229.00,NULL),(7,4,1,1,199.00,NULL),(8,5,13,1,269.00,NULL),(9,6,15,3,229.00,NULL),(10,7,1,2,199.00,NULL),(11,8,7,5,119.00,NULL),(12,9,13,2,269.00,NULL),(13,10,2,5,249.00,NULL),(14,11,5,1,179.00,NULL),(15,12,16,1,79.00,NULL),(16,13,4,1,99.00,NULL),(17,14,11,1,49.00,NULL),(18,15,6,1,149.00,NULL),(19,16,5,2,179.00,NULL);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `delivery_boy_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `delivery_phone` varchar(15) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(5,2) DEFAULT 0.00,
  `tax` decimal(6,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','wallet','upi') DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','preparing','on the way','delivered','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `fk_orders_delivery_boy` (`delivery_boy_id`),
  CONSTRAINT `fk_orders_delivery_boy` FOREIGN KEY (`delivery_boy_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,2,4,1,'2026-07-01 18:17:32','Piro Main Road, Piro, Bihar - 802207','9876543210',448.00,20.00,22.40,490.40,'cash','paid','delivered','Please call before delivery.','2026-07-01 18:17:32','2026-07-01 19:54:59'),(2,2,4,3,'2026-07-01 18:17:32','Piro Main Road, Piro, Bihar - 802207','9876543210',348.00,25.00,17.40,390.40,'wallet','paid','delivered','Extra spicy.','2026-07-01 18:17:32','2026-07-01 19:37:42'),(3,3,4,4,'2026-07-01 18:22:47','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',956.00,30.00,47.80,1033.80,'cash','paid','delivered','vdhjg','2026-07-01 18:22:47','2026-07-01 19:55:00'),(4,3,4,1,'2026-07-01 18:35:27','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',199.00,20.00,9.95,228.95,'wallet','paid','delivered','ara','2026-07-01 18:35:27','2026-07-01 19:10:33'),(5,3,4,4,'2026-07-01 18:40:42','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',269.00,30.00,13.45,312.45,'wallet','paid','delivered','','2026-07-01 18:40:42','2026-07-01 19:04:01'),(6,3,4,4,'2026-07-01 18:54:31','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',687.00,30.00,34.35,751.35,'upi','paid','delivered','','2026-07-01 18:54:31','2026-07-01 18:59:03'),(7,3,4,1,'2026-07-01 18:55:03','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',398.00,20.00,19.90,437.90,'upi','paid','delivered','','2026-07-01 18:55:03','2026-07-01 18:58:00'),(8,4,4,2,'2026-07-01 19:02:22','raushanmaurya74@gmail.com','07979730721',595.00,15.00,29.75,639.75,'upi','paid','delivered','','2026-07-01 19:02:22','2026-07-01 19:04:00'),(9,4,4,4,'2026-07-01 19:11:36','raushanmaurya74@gmail.com','07979730721',538.00,30.00,26.90,594.90,'cash','paid','delivered','','2026-07-01 19:11:36','2026-07-01 19:12:30'),(10,3,4,1,'2026-07-01 19:15:35','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',1245.00,20.00,62.25,1327.25,'cash','paid','delivered','','2026-07-01 19:15:35','2026-07-01 19:24:35'),(11,3,4,2,'2026-07-01 19:16:04','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',179.00,15.00,8.95,202.95,'upi','paid','delivered','','2026-07-01 19:16:04','2026-07-01 19:20:25'),(12,3,4,4,'2026-07-01 19:22:11','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',79.00,30.00,3.95,112.95,'upi','paid','delivered','','2026-07-01 19:22:11','2026-07-01 19:24:34'),(13,3,4,1,'2026-07-01 19:27:44','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',99.00,20.00,4.95,123.95,'cash','paid','delivered','','2026-07-01 19:27:44','2026-07-01 19:38:08'),(14,3,4,3,'2026-07-01 19:31:38','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',49.00,25.00,2.45,76.45,'upi','paid','delivered','','2026-07-01 19:31:38','2026-07-01 19:54:15'),(15,3,4,2,'2026-07-01 20:11:12','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',149.00,15.00,7.45,171.45,'upi','paid','delivered','','2026-07-01 20:11:12','2026-07-01 20:14:39'),(16,3,NULL,2,'2026-07-01 20:11:28','AT - PIRO, BHOJPUR, BIHAR, INDIA-802207','07979730721',358.00,15.00,17.90,390.90,'cash','failed','cancelled','','2026-07-01 20:11:28','2026-07-01 20:12:13');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurants`
--

DROP TABLE IF EXISTS `restaurants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurants` (
  `restaurant_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `zip_code` varchar(10) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `delivery_time` int(11) DEFAULT NULL COMMENT 'Average delivery time in minutes',
  `delivery_fee` decimal(5,2) DEFAULT 0.00,
  `minimum_order` decimal(6,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`restaurant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurants`
--

LOCK TABLES `restaurants` WRITE;
/*!40000 ALTER TABLE `restaurants` DISABLE KEYS */;
INSERT INTO `restaurants` VALUES (1,'Pizza Paradise','Best pizzas in town with authentic Italian flavors','123 Pizza Street','Piro','Bihar','802207','9876543210','pizzaparadise@example.com','uploads/restaurants/res_sample_1.jpg',NULL,NULL,30,20.00,100.00,1,'2026-07-01 18:17:32','2026-07-01 23:20:05'),(2,'Burger Junction','Juicy and delicious burgers with fresh ingredients','456 Burger Avenue','Piro','Bihar','802207','8765432109','burgerjunction@example.com','uploads/restaurants/res_sample_2.jpg',NULL,NULL,25,15.00,80.00,1,'2026-07-01 18:17:32','2026-07-01 23:20:05'),(3,'Spice Garden','Authentic Indian cuisine with rich flavors','789 Spice Road','Piro','Bihar','802207','7654321098','spicegarden@example.com','uploads/restaurants/res_sample_3.jpg',NULL,NULL,35,25.00,120.00,1,'2026-07-01 18:17:32','2026-07-01 23:20:06'),(4,'Dragon Wok','Traditional Chinese dishes prepared by expert chefs','101 Dragon Street','Bhojpur','Bihar','802207','6543210987','dragonwok@example.com','uploads/restaurants/res_sample_4.jpg',NULL,NULL,40,30.00,150.00,1,'2026-07-01 18:17:32','2026-07-01 23:20:06');
/*!40000 ALTER TABLE `restaurants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `user_id` (`user_id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,2,1,1,5,'Fresh pizza and quick delivery. ARS JUNCTION made ordering very easy.','2026-07-01 18:17:32'),(2,2,3,2,4,'Great Indian food and helpful support.','2026-07-01 18:17:32'),(3,3,4,NULL,1,'good','2026-07-01 18:41:08'),(4,4,2,NULL,2,'choklha','2026-07-01 19:10:57');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES ('currency_symbol','?'),('delivery_fee_default','50.00'),('site_email','officialarsjunction@gmail.com'),('site_name','ARS Junction'),('site_phone','7979730721'),('tax_rate_default','5.00'),('upi_id','7979730721@rapl'),('facebook_app_id','YOUR_FACEBOOK_APP_ID'),('facebook_app_secret','YOUR_FACEBOOK_APP_SECRET'),('google_client_id','YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'),('google_client_secret','YOUR_GOOGLE_CLIENT_SECRET'),('facebook_login_enabled','1'),('google_login_enabled','1');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `social_id` varchar(100) DEFAULT NULL,
  `social_type` enum('facebook','google','normal') DEFAULT 'normal',
  `is_admin` tinyint(1) DEFAULT 0,
  `is_delivery_boy` tinyint(1) DEFAULT 0,
  `is_online` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','maurya@arsjunction.com','$2y$10$YPC1BHClCpawk9jNcW.faeeXqAY/uYYMDkmHKDZPISls2mevpyE8W','7979730721','AT - PIRO','BHOJPUR','BIHAR','802207',NULL,NULL,'normal',1,0,'2026-07-01 18:17:32','2026-07-01 18:24:53'),(2,'Demo Customer','customer@arsjunction.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','9876543210','Piro Main Road','Piro','Bihar','802207',NULL,NULL,'normal',0,0,'2026-07-01 18:17:32','2026-07-01 18:17:32'),(3,'Raushan Maurya','raushanmaurya74@gmail.com','$2y$10$L0MOPQAzZbT.9DscyXQS6u03rGTe826WjSr6M3k6Eoerz0BS/gSOq','07979730721',NULL,NULL,NULL,NULL,NULL,NULL,'normal',0,0,'2026-07-01 18:21:34','2026-07-01 18:21:34'),(4,'aniket','admin@arsjunction.com','$2y$10$IquN8TmZkSMWaCZjzPu8meh.SXlpXtfQddkP9f1Zo8kyMfjn.lD7C','07979730721','raushanmaurya74@gmail.com','','','',NULL,NULL,'normal',0,1,'2026-07-01 18:43:32','2026-07-01 18:43:32');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-02  4:50:26
