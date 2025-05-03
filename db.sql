SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+03:00";

INSERT INTO `users` (`email`, `password_hash`, `full_name`, `city`, `district`, `user_type`, `registration_status`, `verification_code`) VALUES
('consumer1@example.com', 'hashed_password_123', 'Ahmet Yılmaz', 'Ankara', 'Çankaya', 'consumer', 'verified', '123456'),
('market1@example.com', 'hashed_password_456', 'ABC Market', 'İstanbul', 'Kadıköy', 'market', 'verified', '654321'),
('consumer2@example.com', 'hashed_password_789', 'Ayşe Demir', 'İzmir', 'Konak', 'consumer', 'unverified', '987654');

INSERT INTO `markets` (`user_id`, `market_name`) VALUES
(2, 'ABC – Birlik Market'),
(3, 'XYZ Market');

INSERT INTO `products` (`market_id`, `title`, `stock`, `normal_price`, `discounted_price`, `expiration_date`, `image`) VALUES
(1, 'Toblerone 100gr', 25, 20.00, 12.00, '2025-05-22', 'toblerone.jpg'),
(1, 'Coca-Cola 1L', 50, 15.00, 10.00, '2025-05-10', 'coca_cola.jpg'),
(2, 'Nutella 350gr', 15, 40.00, 30.00, '2025-06-01', 'nutella.jpg'),
(2, 'Milka 80gr', 30, 15.00, 9.00, '2025-05-15', 'milka.jpg');

INSERT INTO `consumer_cart` (`user_id`, `product_id`, `quantity`) VALUES
(1, 1, 2),
(1, 2, 1),
(3, 3, 1);

INSERT INTO `sessions` (`session_id`, `user_id`, `session_data`, `last_activity`) VALUES
('session1', 1, 'session_data_1', '2025-05-03 10:00:00'),
('session2', 2, 'session_data_2', '2025-05-03 10:05:00'),
('session3', 3, 'session_data_3', '2025-05-03 10:10:00');

COMMIT;