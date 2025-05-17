SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+03:00";

-- Drop tables in the correct order
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `consumer_cart`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `markets`;
DROP TABLE IF EXISTS `users`;

-- 1) Users
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `user_type` enum('consumer','market') NOT NULL,
  `registration_status` enum('verified','unverified') NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users`
  (`user_id`,`email`,`password_hash`,`full_name`,`city`,`district`,`user_type`,`registration_status`,`verification_code`)
VALUES
  (1,'consumer1@example.com','hashed_password_123','Ahmet Yılmaz','Ankara','Çankaya','consumer','verified','123456'),
  (2,'market1@example.com','hashed_password_456','ABC Market','İstanbul','Kadıköy','market','verified','654321'),
  (3,'consumer2@example.com','hashed_password_789','Ayşe Demir','İzmir','Konak','consumer','unverified','987654');

-- 2) Markets
CREATE TABLE IF NOT EXISTS `markets` (
  `market_id` int NOT NULL AUTO_INCREMENT,
  `user_id`   int NOT NULL,
  `market_name` varchar(255) NOT NULL,
  PRIMARY KEY (`market_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `markets_ibfk_1`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO `markets` (`market_id`,`user_id`,`market_name`) VALUES
  (1,2,'ABC – Birlik Market'),
  (2,3,'XYZ Market');

-- 3) Products
CREATE TABLE IF NOT EXISTS `products` (
  `product_id`       int NOT NULL AUTO_INCREMENT,
  `market_id`        int NOT NULL,
  `title`            varchar(255) NOT NULL,
  `stock`            int NOT NULL,
  `normal_price`     decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) NOT NULL,
  `expiration_date`  date NOT NULL,
  `image`            varchar(255) NOT NULL,
  PRIMARY KEY (`product_id`),
  KEY `market_id` (`market_id`),
  CONSTRAINT `products_ibfk_1`
    FOREIGN KEY (`market_id`) REFERENCES `markets` (`market_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Seed products for Market 1, all using milka.jpg
INSERT INTO `products` (`market_id`,`title`,`stock`,`normal_price`,`discounted_price`,`expiration_date`,`image`) VALUES
(1,'Süt (1L)',50,10.00,7.50,'2025-05-25','sut.jpg'),
(1,'Taze Kaşar Peyniri (400g)',20,45.00,32.00,'2025-06-01','tazekasar.jpg'),
(1,'Yumurta (10lu)',100,25.00,18.00,'2025-05-20','yumurta.jpg'),
(1,'Tam Buğday Ekmek',30,8.00,5.00,'2025-05-18','tambugdayekmek.jpg'),
(1,'Beyaz Peynir (300g)',25,30.00,22.00,'2025-05-30','beyazpeynir.jpg'),
(1,'Yoğurt (500g)',40,15.00,11.00,'2025-06-05','yogurt.jpg'),
(1,'Tavuk Göğsü (kg)',15,90.00,70.00,'2025-05-28','tavukgogsu.jpg'),
(1,'Muz (kg)',60,20.00,14.00,'2025-05-22','muz.jpg'),
(1,'Elma (kg)',50,18.00,12.00,'2025-05-24','elma.jpg'),
(1,'Zeytin (200g)',35,20.00,14.00,'2025-06-10','zeytin.jpg'),
(1,'Salatalık (kg)',40,12.00,8.00,'2025-05-21','salatalik.jpg'),    
(1,'Domates (kg)',45,15.00,10.00,'2025-05-23','domates.jpg');

-- Seed products for Market 2, all using nutella.jpg
INSERT INTO `products` (`market_id`,`title`,`stock`,`normal_price`,`discounted_price`,`expiration_date`,`image`) VALUES
(2,'Coca-Cola 2L',60,25.00,18.00,'2025-05-20','cocacola.jpg'),
(2,'Fanta 1.5L',50,20.00,14.00,'2025-05-22','fanta.jpg'),
(2,'Pepsi 1L',55,18.00,12.50,'2025-05-19','pepsi.jpg'),
(2,'Kivi (kg)',30,30.00,22.00,'2025-05-27','kivi.jpg'),
(2,'Şeftali (kg)',40,22.00,16.00,'2025-05-26','seftali.jpg'),
(2,'Karışık Kuruyemiş (400g)',20,50.00,35.00,'2025-06-15','kuruyemis.jpg'),
(2,'Bisküvi Çeşitleri (paket)',80,12.00,8.00,'2025-07-01','puskevit.jpg'),
(2,'Çikolatalı Gofret (12lı)',60,24.00,17.00,'2025-06-05','gofret.jpg'),
(2,'Petek Bal (250g)',25,60.00,45.00,'2026-01-01','petekbal.jpg'),
(2,'Tereyağı (500g)',35,80.00,60.00,'2025-06-10','tereyagı.jpg'),
(2,'Margarin (250g)',50,15.00,10.00,'2025-06-08','margarin.jpg'),
(2,'Zeytinyağı (1L)',30,120.00,90.00,'2026-02-01','zeytinyagı.jpg');

-- 4) Consumer Cart
CREATE TABLE IF NOT EXISTS `consumer_cart` (
  `cart_id`    int NOT NULL AUTO_INCREMENT,
  `user_id`    int NOT NULL,
  `product_id` int NOT NULL,
  `quantity`   int NOT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `user_id`    (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `consumer_cart_ibfk_1`
    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`user_id`)    ON DELETE CASCADE,
  CONSTRAINT `consumer_cart_ibfk_2`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Enforce uniqueness in the cart so adding same product increments quantity
ALTER TABLE `consumer_cart`
  ADD UNIQUE KEY `user_product` (`user_id`, `product_id`);

-- 5) Sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `session_id`    varchar(255) NOT NULL,
  `user_id`       int NOT NULL,
  `session_data`  text NOT NULL,
  `last_activity` timestamp NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sessions_ibfk_1`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

COMMIT;