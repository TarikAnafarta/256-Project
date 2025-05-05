SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+03:00";
--
-- Database: `test`
--

DROP TABLE IF EXISTS `consumer_cart`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `markets`;
DROP TABLE IF EXISTS `users`;
-- --------------------------------------------------------

--
-- Table structure for table `users`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `city`, `district`, `user_type`, `registration_status`, `verification_code`) VALUES
(1, 'consumer1@example.com', 'hashed_password_123', 'Ahmet Yılmaz', 'Ankara', 'Çankaya', 'consumer', 'verified', '123456'),
(2, 'market1@example.com', 'hashed_password_456', 'ABC Market', 'İstanbul', 'Kadıköy', 'market', 'verified', '654321'),
(3, 'consumer2@example.com', 'hashed_password_789', 'Ayşe Demir', 'İzmir', 'Konak', 'consumer', 'unverified', '987654');

--
-- Table structure for table `markets`
--

CREATE TABLE IF NOT EXISTS `markets` (
  `market_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `market_name` varchar(255) NOT NULL,
  PRIMARY KEY (`market_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `markets`
--

INSERT INTO `markets` (`market_id`, `user_id`, `market_name`) VALUES
(1, 2, 'ABC – Birlik Market'),
(2, 3, 'XYZ Market');

--
-- Constraints for table `markets`
--

ALTER TABLE `markets`
  ADD CONSTRAINT `markets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `market_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `stock` int NOT NULL,
  `normal_price` decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) NOT NULL,
  `expiration_date` date NOT NULL,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`product_id`),
  KEY `market_id` (`market_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `market_id`, `title`, `stock`, `normal_price`, `discounted_price`, `expiration_date`, `image`) VALUES
(1, 1, 'Toblerone 100gr', 25, 20.00, 12.00, '2025-05-22', 'toblerone.jpg'),
(2, 1, 'Coca-Cola 1L', 50, 15.00, 10.00, '2025-05-10', 'coca_cola.jpg'),
(3, 2, 'Nutella 350gr', 15, 40.00, 30.00, '2025-06-01', 'nutella.jpg'),
(4, 2, 'Milka 80gr', 30, 15.00, 9.00, '2025-05-15', 'milka.jpg');

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`market_id`) REFERENCES `markets` (`market_id`) ON DELETE CASCADE;

--
-- Table structure for table `consumer_cart`
--

CREATE TABLE IF NOT EXISTS `consumer_cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `consumer_cart`
--

INSERT INTO `consumer_cart` (`cart_id`, `user_id`, `product_id`, `quantity`) VALUES
(1, 1, 1, 2),
(2, 1, 2, 1),
(3, 3, 3, 1);

--
-- Constraints for table `consumer_cart`
--
ALTER TABLE `consumer_cart`
  ADD CONSTRAINT `consumer_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consumer_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `session_id` varchar(255) NOT NULL,
  `user_id` int NOT NULL,
  `session_data` text NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `user_id`, `session_data`, `last_activity`) VALUES
('session1', 1, 'session_data_1', '2025-05-03 07:00:00'),
('session2', 2, 'session_data_2', '2025-05-03 07:05:00'),
('session3', 3, 'session_data_3', '2025-05-03 07:10:00');

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

COMMIT;