-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 17, 2026 at 07:09 AM
-- Server version: 8.4.3
-- PHP Version: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `point_of_sales`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auditable_id` bigint UNSIGNED DEFAULT NULL,
  `target_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `before` json DEFAULT NULL,
  `after` json DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `event`, `module`, `auditable_type`, `auditable_id`, `target_label`, `description`, `before`, `after`, `meta`, `ip_address`, `user_agent`, `created_at`) VALUES
(58, 1, 'product.updated', 'products', 'App\\Models\\Product', 67, 'Add Print', 'Data produk diperbarui.', '{\"sku\": \"PHB-0006\", \"stock\": 50, \"title\": \"Add Print\", \"barcode\": \"PHB-0006\", \"buy_price\": 3000, \"sell_price\": 5000, \"category_id\": 12}', '{\"sku\": \"PHB-0006\", \"stock\": 50, \"title\": \"Add Print\", \"barcode\": \"PHB-0006\", \"buy_price\": 3000, \"sell_price\": 5000, \"category_id\": 12}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 02:53:49'),
(59, 1, 'product.updated', 'products', 'App\\Models\\Product', 69, 'Card Package', 'Data produk diperbarui.', '{\"sku\": \"PHB-0008\", \"stock\": 50, \"title\": \"Card Package\", \"barcode\": \"PHB-0008\", \"buy_price\": 18000, \"sell_price\": 30000, \"category_id\": 14}', '{\"sku\": \"PHB-0008\", \"stock\": 50, \"title\": \"Card Package\", \"barcode\": \"PHB-0008\", \"buy_price\": 18000, \"sell_price\": 30000, \"category_id\": 14}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 02:55:48'),
(60, 1, 'product.updated', 'products', 'App\\Models\\Product', 70, 'Newspaper print', 'Data produk diperbarui.', '{\"sku\": \"PHB-0009\", \"stock\": 50, \"title\": \"Newspaper print\", \"barcode\": \"PHB-0009\", \"buy_price\": 6000, \"sell_price\": 10000, \"category_id\": 15}', '{\"sku\": \"PHB-0009\", \"stock\": 50, \"title\": \"Newspaper print\", \"barcode\": \"PHB-0009\", \"buy_price\": 6000, \"sell_price\": 10000, \"category_id\": 15}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 02:57:25'),
(61, 1, 'product.updated', 'products', 'App\\Models\\Product', 69, 'Card Package', 'Data produk diperbarui.', '{\"sku\": \"PHB-0008\", \"stock\": 50, \"title\": \"Card Package\", \"barcode\": \"PHB-0008\", \"buy_price\": 18000, \"sell_price\": 30000, \"category_id\": 14}', '{\"sku\": \"PHB-0008\", \"stock\": 50, \"title\": \"Card Package\", \"barcode\": \"PHB-0008\", \"buy_price\": 18000, \"sell_price\": 30000, \"category_id\": 14}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 02:59:36'),
(62, 1, 'auth.logout', 'auth', 'App\\Models\\User', 1, 'Admin', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 03:17:44'),
(63, 2, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": true, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 03:18:09'),
(64, 2, 'store.setting.updated', 'store_settings', NULL, NULL, 'Store Profile', 'Profil toko diperbarui.', '{\"store_city\": \"Yogyakarta\", \"store_name\": \"Popsnap\", \"store_email\": \"halo@popsnap.test\", \"store_phone\": \"0851-8669-8837\", \"store_address\": \"Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283\', \'description\' => \'Alamat lengkap toko\", \"store_website\": \"https://www.instagram.com/popsnapstudio\", \"store_logo_changed\": false}', '{\"store_city\": \"Yogyakarta\", \"store_name\": \"Popsnap\", \"store_email\": \"halo@popsnap.test\", \"store_phone\": \"0851-8669-8837\", \"store_address\": \"Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283\', \'description\' => \'Alamat lengkap toko\", \"store_website\": \"https://www.instagram.com/popsnapstudio\", \"store_logo_changed\": true}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 03:19:04'),
(65, 2, 'store.setting.updated', 'store_settings', NULL, NULL, 'Store Profile', 'Profil toko diperbarui.', '{\"store_city\": \"Yogyakarta\", \"store_name\": \"Popsnap\", \"store_email\": \"halo@popsnap.test\", \"store_phone\": \"0851-8669-8837\", \"store_address\": \"Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283\', \'description\' => \'Alamat lengkap toko\", \"store_website\": \"https://www.instagram.com/popsnapstudio\", \"store_logo_changed\": false}', '{\"store_city\": \"Yogyakarta\", \"store_name\": \"Popsnap\", \"store_email\": \"halo@popsnap.test\", \"store_phone\": \"0851-8669-8837\", \"store_address\": \"Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283\', \'description\' => \'Alamat lengkap toko\", \"store_website\": \"https://www.instagram.com/popsnapstudio\", \"store_logo_changed\": true}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 03:20:24'),
(66, 2, 'store.setting.updated', 'store_settings', NULL, NULL, 'Store Profile', 'Profil toko diperbarui.', '{\"store_city\": \"Yogyakarta\", \"store_name\": \"Popsnap\", \"store_email\": \"halo@popsnap.test\", \"store_phone\": \"0851-8669-8837\", \"store_address\": \"Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283\', \'description\' => \'Alamat lengkap toko\", \"store_website\": \"https://www.instagram.com/popsnapstudio\", \"store_logo_changed\": false}', '{\"store_city\": \"Yogyakarta\", \"store_name\": \"Popsnap\", \"store_email\": \"halo@popsnap.test\", \"store_phone\": \"0851-8669-8837\", \"store_address\": \"Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283\', \'description\' => \'Alamat lengkap toko\", \"store_website\": \"https://www.instagram.com/popsnapstudio\", \"store_logo_changed\": true}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 03:21:13'),
(67, 2, 'auth.logout', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 03:21:56'),
(68, 2, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": true, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 19:56:21'),
(69, 2, 'auth.logout', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 19:56:48'),
(70, 1, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 1, 'Admin', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": true, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 19:57:52'),
(71, 1, 'product.updated', 'products', 'App\\Models\\Product', 68, 'Keychain', 'Data produk diperbarui.', '{\"sku\": \"PHB-0007\", \"stock\": 50, \"title\": \"Keychain\", \"barcode\": \"PHB-0007\", \"buy_price\": 9000, \"sell_price\": 15000, \"category_id\": 13}', '{\"sku\": \"PHB-0007\", \"stock\": 50, \"title\": \"Keychain\", \"barcode\": \"PHB-0007\", \"buy_price\": 9000, \"sell_price\": 15000, \"category_id\": 13}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 20:28:04'),
(72, 1, 'product.updated', 'products', 'App\\Models\\Product', 71, 'Frame 4R', 'Data produk diperbarui.', '{\"sku\": \"PHB-0010\", \"stock\": 50, \"title\": \"Frame 4R\", \"barcode\": \"PHB-0010\", \"buy_price\": 15000, \"sell_price\": 25000, \"category_id\": 16}', '{\"sku\": \"PHB-0010\", \"stock\": 50, \"title\": \"Frame 4R\", \"barcode\": \"PHB-0010\", \"buy_price\": 15000, \"sell_price\": 25000, \"category_id\": 16}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 20:33:30'),
(73, 1, 'product.updated', 'products', 'App\\Models\\Product', 66, 'Celebration Package', 'Data produk diperbarui.', '{\"sku\": \"PHB-0005\", \"stock\": 50, \"title\": \"Celebration Package\", \"barcode\": \"PHB-0005\", \"buy_price\": 99000, \"sell_price\": 165000, \"category_id\": 11}', '{\"sku\": \"PHB-0005\", \"stock\": 50, \"title\": \"Celebration Package\", \"barcode\": \"PHB-0005\", \"buy_price\": 99000, \"sell_price\": 165000, \"category_id\": 11}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 20:36:07'),
(74, 1, 'product.updated', 'products', 'App\\Models\\Product', 65, 'CCTV SnapBox', 'Data produk diperbarui.', '{\"sku\": \"PHB-0004\", \"stock\": 50, \"title\": \"Big Box\", \"barcode\": \"PHB-0004\", \"buy_price\": 21000, \"sell_price\": 35000, \"category_id\": 8}', '{\"sku\": \"PHB-0004\", \"stock\": 50, \"title\": \"CCTV SnapBox\", \"barcode\": \"PHB-0004\", \"buy_price\": 21000, \"sell_price\": 35000, \"category_id\": 8}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 20:43:46'),
(75, 1, 'product.updated', 'products', 'App\\Models\\Product', 65, 'CCTV SnapBox', 'Data produk diperbarui.', '{\"sku\": \"PHB-0004\", \"stock\": 50, \"title\": \"CCTV SnapBox\", \"barcode\": \"PHB-0004\", \"buy_price\": 21000, \"sell_price\": 35000, \"category_id\": 8}', '{\"sku\": \"PHB-0004\", \"stock\": 50, \"title\": \"CCTV SnapBox\", \"barcode\": \"PHB-0004\", \"buy_price\": 21000, \"sell_price\": 35000, \"category_id\": 10}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-11 21:17:19'),
(76, 1, 'product.updated', 'products', 'App\\Models\\Product', 64, 'Emma\'s Room', 'Data produk diperbarui.', '{\"sku\": \"PHB-0003\", \"stock\": 50, \"title\": \"Emma\'s Room\", \"barcode\": \"PHB-0003\", \"buy_price\": 21000, \"sell_price\": 35000, \"category_id\": 8}', '{\"sku\": \"PHB-0003\", \"stock\": 50, \"title\": \"Emma\'s Room\", \"barcode\": \"PHB-0003\", \"buy_price\": 21000, \"sell_price\": 35000, \"category_id\": 10}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-11 21:17:27'),
(77, 1, 'product.updated', 'products', 'App\\Models\\Product', 63, 'Vintage Room', 'Data produk diperbarui.', '{\"sku\": \"PHB-0002\", \"stock\": 49, \"title\": \"Vintage Room\", \"barcode\": \"PHB-0002\", \"buy_price\": 21000, \"sell_price\": 35000, \"category_id\": 8}', '{\"sku\": \"PHB-0002\", \"stock\": 49, \"title\": \"Vintage Room\", \"barcode\": \"PHB-0002\", \"buy_price\": 21000, \"sell_price\": 35000, \"category_id\": 10}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-11 21:18:09'),
(78, 1, 'auth.logout', 'auth', 'App\\Models\\User', 1, 'Admin', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:27:28'),
(79, 2, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": true, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:27:36'),
(80, 2, 'store.setting.updated', 'store_settings', NULL, NULL, 'Store Profile', 'Profil toko diperbarui.', '{\"store_city\": \"Yogyakarta\", \"store_name\": \"Popsnap\", \"store_email\": \"halo@popsnap.test\", \"store_phone\": \"0851-8669-8837\", \"store_address\": \"Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283\", \"store_website\": \"https://www.instagram.com/popsnapstudio\", \"store_logo_changed\": false}', '{\"store_city\": \"Yogyakarta\", \"store_name\": \"Popsnap\", \"store_email\": \"halo@popsnap.test\", \"store_phone\": \"0851-8669-8837\", \"store_address\": \"Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283\", \"store_website\": \"https://www.instagram.com/popsnapstudio\", \"store_logo_changed\": true}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:27:54'),
(81, 2, 'auth.logout', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:28:14'),
(82, 2, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:37:59'),
(83, 2, 'auth.logout', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:38:08'),
(84, NULL, 'security.bot_guard_blocked', 'security', NULL, NULL, 'login', 'Permintaan auth publik diblokir oleh bot guard.', NULL, NULL, '{\"route\": null, \"method\": \"POST\", \"reason\": \"submitted_too_fast\", \"severity\": \"warning\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:45:30'),
(85, 2, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:45:33'),
(86, 2, 'auth.logout', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 00:46:01'),
(87, 2, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 01:15:39'),
(88, 2, 'auth.logout', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 01:35:27'),
(89, 1, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 1, 'Admin', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 01:35:36'),
(90, 1, 'auth.logout', 'auth', 'App\\Models\\User', 1, 'Admin', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 02:04:43'),
(91, 2, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 02:04:49'),
(92, 2, 'auth.logout', 'auth', 'App\\Models\\User', 2, 'Cashier', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 02:10:03'),
(93, 1, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 1, 'Admin', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": true, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 02:10:09'),
(94, 1, 'cashier_shift.opened', 'cashier_shifts', 'App\\Models\\CashierShift', 5, 'CashierShift #5', 'Shift kasir dibuka.', NULL, '{\"status\": \"open\", \"actual_cash\": null, \"opening_cash\": 10000, \"expected_cash\": 10000, \"cash_difference\": null, \"transactions_count\": 0, \"sales_returns_count\": 0}', '{\"opened_by\": 1, \"cashier_id\": 1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 22:23:33'),
(95, 1, 'security.privileged_action_challenged', 'security', NULL, NULL, 'settings.bank-accounts.store', 'Aksi sensitif memerlukan konfirmasi password ulang.', NULL, NULL, '{\"route\": \"settings.bank-accounts.store\", \"method\": \"POST\", \"severity\": \"high\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 22:28:36'),
(96, 1, 'auth.password_confirmed', 'auth', 'App\\Models\\User', 1, 'Admin', 'Password berhasil dikonfirmasi ulang.', NULL, NULL, '{\"route\": null, \"severity\": \"info\", \"challenge\": {\"route\": \"settings.bank-accounts.store\", \"method\": \"POST\", \"intended\": \"http://127.0.0.1:8000/dashboard/settings/bank-accounts/create\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 22:28:46'),
(97, 1, 'security.privileged_action_confirmed', 'security', 'App\\Models\\User', 1, 'Admin', 'Aksi sensitif diotorisasi setelah konfirmasi password.', NULL, NULL, '{\"severity\": \"high\", \"challenge\": {\"route\": \"settings.bank-accounts.store\", \"method\": \"POST\", \"intended\": \"http://127.0.0.1:8000/dashboard/settings/bank-accounts/create\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 22:28:46'),
(98, 1, 'bank_account.created', 'bank_accounts', 'App\\Models\\BankAccount', 4, 'BankAccount #4', 'Rekening bank ditambahkan.', NULL, '{\"bank_name\": \"wew\", \"is_active\": true, \"sort_order\": 1, \"account_name\": \"sfsf\", \"account_number_masked\": \"*****5425\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 22:29:21'),
(99, 1, 'bank_account.updated', 'bank_accounts', 'App\\Models\\BankAccount', 4, 'BankAccount #4', 'Rekening bank diperbarui.', '{\"bank_name\": \"wew\", \"is_active\": true, \"sort_order\": 1, \"account_name\": \"sfsf\", \"account_number_masked\": \"*****5425\"}', '{\"bank_name\": \"wew\", \"is_active\": true, \"sort_order\": 1, \"account_name\": \"sfsf\", \"account_number_masked\": \"*****5425\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 22:29:41'),
(100, NULL, 'auth.login_failed', 'auth', NULL, NULL, NULL, 'Login gagal.', NULL, NULL, '{\"email\": \"operator-cs@gmail.com\", \"route\": null, \"severity\": \"warning\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 21:24:27'),
(101, 1, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 1, 'Admin', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 21:24:36'),
(102, 1, 'auth.logout', 'auth', 'App\\Models\\User', 1, 'Admin', 'Logout berhasil.', NULL, NULL, '{\"route\": \"logout\", \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:04:21'),
(103, 1, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 1, 'Admin', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:04:27'),
(104, 1, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 1, 'Admin', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-15 23:06:47'),
(105, 1, 'security.privileged_action_challenged', 'security', NULL, NULL, 'settings.payments.update', 'Aksi sensitif memerlukan konfirmasi password ulang.', NULL, NULL, '{\"route\": \"settings.payments.update\", \"method\": \"PUT\", \"severity\": \"high\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:09:07'),
(106, 1, 'auth.password_confirmed', 'auth', 'App\\Models\\User', 1, 'Admin', 'Password berhasil dikonfirmasi ulang.', NULL, NULL, '{\"route\": null, \"severity\": \"info\", \"challenge\": {\"route\": \"settings.payments.update\", \"method\": \"PUT\", \"intended\": \"http://127.0.0.1:8000/dashboard/settings/payments\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:09:15'),
(107, 1, 'security.privileged_action_confirmed', 'security', 'App\\Models\\User', 1, 'Admin', 'Aksi sensitif diotorisasi setelah konfirmasi password.', NULL, NULL, '{\"severity\": \"high\", \"challenge\": {\"route\": \"settings.payments.update\", \"method\": \"PUT\", \"intended\": \"http://127.0.0.1:8000/dashboard/settings/payments\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:09:15'),
(108, 1, 'payment.setting.updated', 'payment_settings', 'App\\Models\\PaymentSetting', 1, 'PaymentSetting #1', 'Konfigurasi payment gateway diperbarui.', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": \"empty\", \"xendit_secret_key\": \"empty\", \"midtrans_client_key\": \"empty\", \"midtrans_production\": false, \"midtrans_server_key\": \"empty\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": \"empty\"}', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": null, \"xendit_secret_key\": null, \"midtrans_client_key\": null, \"midtrans_production\": false, \"midtrans_server_key\": \"configured\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:09:44'),
(109, 1, 'payment.setting.updated', 'payment_settings', 'App\\Models\\PaymentSetting', 1, 'PaymentSetting #1', 'Konfigurasi payment gateway diperbarui.', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": \"empty\", \"xendit_secret_key\": \"empty\", \"midtrans_client_key\": \"empty\", \"midtrans_production\": false, \"midtrans_server_key\": \"configured\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": \"empty\"}', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": null, \"xendit_secret_key\": null, \"midtrans_client_key\": null, \"midtrans_production\": false, \"midtrans_server_key\": \"unchanged\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:15:06'),
(110, 1, 'payment.setting.updated', 'payment_settings', 'App\\Models\\PaymentSetting', 1, 'PaymentSetting #1', 'Konfigurasi payment gateway diperbarui.', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": \"empty\", \"xendit_secret_key\": \"empty\", \"midtrans_client_key\": \"empty\", \"midtrans_production\": false, \"midtrans_server_key\": \"configured\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": \"empty\"}', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": null, \"xendit_secret_key\": null, \"midtrans_client_key\": null, \"midtrans_production\": false, \"midtrans_server_key\": \"unchanged\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:20:17'),
(111, 1, 'payment.setting.updated', 'payment_settings', 'App\\Models\\PaymentSetting', 1, 'PaymentSetting #1', 'Konfigurasi payment gateway diperbarui.', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": \"empty\", \"xendit_secret_key\": \"empty\", \"midtrans_client_key\": \"empty\", \"midtrans_production\": false, \"midtrans_server_key\": \"configured\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": \"empty\"}', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": null, \"xendit_secret_key\": null, \"midtrans_client_key\": null, \"midtrans_production\": false, \"midtrans_server_key\": \"unchanged\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:20:51'),
(112, 1, 'security.privileged_action_challenged', 'security', NULL, NULL, 'settings.payments.update', 'Aksi sensitif memerlukan konfirmasi password ulang.', NULL, NULL, '{\"route\": \"settings.payments.update\", \"method\": \"PUT\", \"severity\": \"high\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:24:20'),
(113, 1, 'auth.password_confirmed', 'auth', 'App\\Models\\User', 1, 'Admin', 'Password berhasil dikonfirmasi ulang.', NULL, NULL, '{\"route\": null, \"severity\": \"info\", \"challenge\": {\"route\": \"settings.payments.update\", \"method\": \"PUT\", \"intended\": \"http://127.0.0.1:8000/dashboard/settings/payments\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:24:29'),
(114, 1, 'security.privileged_action_confirmed', 'security', 'App\\Models\\User', 1, 'Admin', 'Aksi sensitif diotorisasi setelah konfirmasi password.', NULL, NULL, '{\"severity\": \"high\", \"challenge\": {\"route\": \"settings.payments.update\", \"method\": \"PUT\", \"intended\": \"http://127.0.0.1:8000/dashboard/settings/payments\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:24:29'),
(115, 1, 'payment.setting.updated', 'payment_settings', 'App\\Models\\PaymentSetting', 1, 'PaymentSetting #1', 'Konfigurasi payment gateway diperbarui.', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": \"empty\", \"xendit_secret_key\": \"empty\", \"midtrans_client_key\": \"empty\", \"midtrans_production\": false, \"midtrans_server_key\": \"configured\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": \"empty\"}', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": null, \"xendit_secret_key\": null, \"midtrans_client_key\": null, \"midtrans_production\": false, \"midtrans_server_key\": \"unchanged\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:24:47'),
(116, 1, 'payment.setting.updated', 'payment_settings', 'App\\Models\\PaymentSetting', 1, 'PaymentSetting #1', 'Konfigurasi payment gateway diperbarui.', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": \"empty\", \"xendit_secret_key\": \"empty\", \"midtrans_client_key\": \"empty\", \"midtrans_production\": false, \"midtrans_server_key\": \"configured\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": \"empty\"}', '{\"xendit_enabled\": false, \"default_gateway\": \"cash\", \"midtrans_enabled\": false, \"xendit_production\": false, \"xendit_public_key\": null, \"xendit_secret_key\": null, \"midtrans_client_key\": null, \"midtrans_production\": false, \"midtrans_server_key\": \"unchanged\", \"bank_transfer_enabled\": true, \"xendit_callback_token\": null}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:25:17'),
(117, 1, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 1, 'Admin', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": false, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 23:37:42'),
(118, 1, 'auth.login_succeeded', 'auth', 'App\\Models\\User', 1, 'Admin', 'Login berhasil.', NULL, NULL, '{\"route\": null, \"remember\": true, \"severity\": \"info\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 04:49:54');

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` bigint UNSIGNED NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_number`, `account_name`, `logo`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'BCA', '0149988776', 'Popsnap', NULL, 1, 0, '2026-06-10 00:44:25', '2026-06-10 00:44:25'),
(4, 'wew', '234235425', 'sfsf', 'bank-logos/HAz6N81xTdXRkLyCR43yPSQQc73cDpI1hZZym0gB.jpg', 1, 1, '2026-06-12 22:29:21', '2026-06-12 22:29:41');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('da4b9237bacccdf19c0760cab7aec4a8359010b0', 'i:1;', 1781078149),
('da4b9237bacccdf19c0760cab7aec4a8359010b0:timer', 'i:1781078149;', 1781078149),
('operator-cs@gmail.com|127.0.0.1', 'i:1;', 1781583927),
('operator-cs@gmail.com|127.0.0.1:timer', 'i:1781583927;', 1781583927),
('spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:74:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:16:\"dashboard-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:29;i:1;i:30;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:12:\"users-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:29;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"users-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:29;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"users-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:29;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:12:\"users-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:29;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"roles-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:29;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:12:\"roles-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:29;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:12:\"roles-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:29;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:12:\"roles-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:29;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:18:\"permissions-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:3;i:1;i:29;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:18:\"permissions-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:3;i:1;i:29;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:18:\"permissions-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:3;i:1;i:29;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:18:\"permissions-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:3;i:1;i:29;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:17:\"categories-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:4;i:1;i:29;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:17:\"categories-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:4;i:1;i:29;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:15:\"categories-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:4;i:1;i:29;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:17:\"categories-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:4;i:1;i:29;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:15:\"products-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:5;i:1;i:29;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:15:\"products-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:5;i:1;i:29;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:13:\"products-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:5;i:1;i:29;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:15:\"products-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:5;i:1;i:29;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:20:\"pricing-rules-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:6;i:1;i:29;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:20:\"pricing-rules-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:6;i:1;i:29;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:20:\"pricing-rules-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:6;i:1;i:29;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:20:\"pricing-rules-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:6;i:1;i:29;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:16:\"customers-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:7;i:1;i:29;i:2;i:30;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:16:\"customers-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:7;i:1;i:29;i:2;i:30;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:14:\"customers-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:7;i:1;i:29;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:16:\"customers-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:7;i:1;i:29;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:24:\"customer-vouchers-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:8;i:1;i:29;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:24:\"customer-vouchers-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:8;i:1;i:29;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:24:\"customer-vouchers-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:8;i:1;i:29;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:24:\"customer-vouchers-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:8;i:1;i:29;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:24:\"customer-segments-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:9;i:1;i:29;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:24:\"customer-segments-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:9;i:1;i:29;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:24:\"customer-segments-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:9;i:1;i:29;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:24:\"customer-segments-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:9;i:1;i:29;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:20:\"crm-campaigns-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:10;i:1;i:29;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:20:\"crm-campaigns-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:10;i:1;i:29;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:20:\"crm-campaigns-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:10;i:1;i:29;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:20:\"crm-campaigns-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:10;i:1;i:29;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:20:\"crm-reminders-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:11;i:1;i:29;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:19:\"transactions-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:12;i:1;i:29;i:2;i:30;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:28:\"transactions-confirm-payment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:12;i:1;i:13;i:2;i:29;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:18:\"receivables-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:14;i:1;i:29;i:2;i:30;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:15:\"receivables-pay\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:14;i:1;i:29;i:2;i:30;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:15:\"payables-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:15;i:1;i:29;i:2;i:30;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:12:\"payables-pay\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:15;i:1;i:29;i:2;i:30;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:16:\"suppliers-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:16;i:1;i:29;i:2;i:30;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:14:\"reports-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:17;i:1;i:29;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:14:\"profits-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:18;i:1;i:29;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:23:\"payment-settings-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:19;i:1;i:29;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:23:\"payment-settings-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:19;i:1;i:20;i:2;i:29;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:20:\"stock-opnames-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:21;i:1;i:29;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:20:\"stock-opnames-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:21;i:1;i:29;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:22:\"stock-opnames-finalize\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:21;i:1;i:29;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:22:\"stock-mutations-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:22;i:1;i:29;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:20:\"sales-returns-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:23;i:1;i:29;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:20:\"sales-returns-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:23;i:1;i:29;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:22:\"sales-returns-complete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:23;i:1;i:29;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:21:\"cashier-shifts-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:24;i:1;i:29;i:2;i:30;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:19:\"cashier-shifts-open\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:24;i:1;i:29;i:2;i:30;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:20:\"cashier-shifts-close\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:24;i:1;i:29;i:2;i:30;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:26:\"cashier-shifts-force-close\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:24;i:1;i:29;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:17:\"audit-logs-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:25;i:1;i:29;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:22:\"purchase-orders-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:26;i:1;i:29;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:22:\"purchase-orders-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:26;i:1;i:29;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:22:\"purchase-orders-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:26;i:1;i:29;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:22:\"purchase-orders-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:26;i:1;i:29;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:23:\"goods-receivings-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:27;i:1;i:29;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:23:\"goods-receivings-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:27;i:1;i:29;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:23:\"supplier-returns-access\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:28;i:1;i:29;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:23:\"supplier-returns-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:28;i:1;i:29;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:23:\"supplier-returns-update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:28;i:1;i:29;}}}s:5:\"roles\";a:30:{i:0;a:3:{s:1:\"a\";i:29;s:1:\"b\";s:11:\"super-admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:30;s:1:\"b\";s:7:\"cashier\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:12:\"users-access\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:12:\"roles-access\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:18:\"permissions-access\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:17:\"categories-access\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"products-access\";s:1:\"c\";s:3:\"web\";}i:7;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:20:\"pricing-rules-access\";s:1:\"c\";s:3:\"web\";}i:8;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:16:\"customers-access\";s:1:\"c\";s:3:\"web\";}i:9;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:24:\"customer-vouchers-access\";s:1:\"c\";s:3:\"web\";}i:10;a:3:{s:1:\"a\";i:9;s:1:\"b\";s:24:\"customer-segments-access\";s:1:\"c\";s:3:\"web\";}i:11;a:3:{s:1:\"a\";i:10;s:1:\"b\";s:20:\"crm-campaigns-access\";s:1:\"c\";s:3:\"web\";}i:12;a:3:{s:1:\"a\";i:11;s:1:\"b\";s:20:\"crm-reminders-access\";s:1:\"c\";s:3:\"web\";}i:13;a:3:{s:1:\"a\";i:12;s:1:\"b\";s:19:\"transactions-access\";s:1:\"c\";s:3:\"web\";}i:14;a:3:{s:1:\"a\";i:13;s:1:\"b\";s:28:\"transactions-confirm-payment\";s:1:\"c\";s:3:\"web\";}i:15;a:3:{s:1:\"a\";i:14;s:1:\"b\";s:18:\"receivables-access\";s:1:\"c\";s:3:\"web\";}i:16;a:3:{s:1:\"a\";i:15;s:1:\"b\";s:15:\"payables-access\";s:1:\"c\";s:3:\"web\";}i:17;a:3:{s:1:\"a\";i:16;s:1:\"b\";s:16:\"suppliers-access\";s:1:\"c\";s:3:\"web\";}i:18;a:3:{s:1:\"a\";i:17;s:1:\"b\";s:14:\"reports-access\";s:1:\"c\";s:3:\"web\";}i:19;a:3:{s:1:\"a\";i:18;s:1:\"b\";s:14:\"profits-access\";s:1:\"c\";s:3:\"web\";}i:20;a:3:{s:1:\"a\";i:19;s:1:\"b\";s:23:\"payment-settings-access\";s:1:\"c\";s:3:\"web\";}i:21;a:3:{s:1:\"a\";i:20;s:1:\"b\";s:23:\"payment-settings-update\";s:1:\"c\";s:3:\"web\";}i:22;a:3:{s:1:\"a\";i:21;s:1:\"b\";s:20:\"stock-opnames-access\";s:1:\"c\";s:3:\"web\";}i:23;a:3:{s:1:\"a\";i:22;s:1:\"b\";s:22:\"stock-mutations-access\";s:1:\"c\";s:3:\"web\";}i:24;a:3:{s:1:\"a\";i:23;s:1:\"b\";s:20:\"sales-returns-access\";s:1:\"c\";s:3:\"web\";}i:25;a:3:{s:1:\"a\";i:24;s:1:\"b\";s:21:\"cashier-shifts-access\";s:1:\"c\";s:3:\"web\";}i:26;a:3:{s:1:\"a\";i:25;s:1:\"b\";s:17:\"audit-logs-access\";s:1:\"c\";s:3:\"web\";}i:27;a:3:{s:1:\"a\";i:26;s:1:\"b\";s:22:\"purchase-orders-access\";s:1:\"c\";s:3:\"web\";}i:28;a:3:{s:1:\"a\";i:27;s:1:\"b\";s:23:\"goods-receivings-access\";s:1:\"c\";s:3:\"web\";}i:29;a:3:{s:1:\"a\";i:28;s:1:\"b\";s:23:\"supplier-returns-access\";s:1:\"c\";s:3:\"web\";}}}', 1781335656);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint UNSIGNED NOT NULL,
  `cashier_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `qty` int NOT NULL,
  `price` bigint NOT NULL,
  `hold_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hold_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `held_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashier_shifts`
--

CREATE TABLE `cashier_shifts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `opened_by` bigint UNSIGNED NOT NULL,
  `closed_by` bigint UNSIGNED DEFAULT NULL,
  `opened_at` timestamp NOT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `opening_cash` bigint UNSIGNED NOT NULL DEFAULT '0',
  `expected_cash` bigint UNSIGNED NOT NULL DEFAULT '0',
  `actual_cash` bigint UNSIGNED DEFAULT NULL,
  `cash_sales_total` bigint UNSIGNED NOT NULL DEFAULT '0',
  `non_cash_sales_total` bigint UNSIGNED NOT NULL DEFAULT '0',
  `cash_refund_total` bigint UNSIGNED NOT NULL DEFAULT '0',
  `non_cash_refund_total` bigint UNSIGNED NOT NULL DEFAULT '0',
  `transactions_count` int UNSIGNED NOT NULL DEFAULT '0',
  `sales_returns_count` int UNSIGNED NOT NULL DEFAULT '0',
  `cash_difference` bigint DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `close_notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `image`, `name`, `description`, `created_at`, `updated_at`) VALUES
(10, 'SVS4wLXLq452TIftkVbqYZaIkpDBDh3fjGam0eRA.jpg', 'PhotoBox', 'Temukan berbagai pilihan photobox menarik untuk menyimpan dan menampilkan momen spesial Anda dengan gaya yang unik dan personal.', '2026-06-10 02:22:17', '2026-06-10 02:22:17'),
(11, 'BessjAZiPKeX0RTxBMbC8yDImfvElAKWrGSPP34J.jpg', 'Paket PhotoBox', 'Paket PhotoBox', '2026-06-10 02:32:05', '2026-06-10 02:32:05'),
(12, '8Dm3UMSGNef6cc2mBIqjenEv93Co4Vu0GAtjNZLu.jpg', 'Cetakan Tambahan', 'Cetakan Tambahan', '2026-06-10 02:32:24', '2026-06-10 02:32:24'),
(13, 'pvzPPH62YEeKwvpgqfmZAyru3RfqrVKMCDGU1ESM.jpg', 'Aksesoris Photo', 'Aksesoris Photo', '2026-06-10 02:33:04', '2026-06-10 02:33:04'),
(14, 'HG3qsAGUqGjajGmcWIooG405ly4VJf4IXaEUW37r.jpg', 'Paket Kartu', 'Paket Kartu', '2026-06-10 02:33:39', '2026-06-10 02:33:39'),
(15, 'qcbzyHRBWe0CBhP6w8gGklOBHsF1iydjjyPMWfqm.jpg', 'Cetakan Khusus', 'Cetakan Khusus', '2026-06-10 02:33:49', '2026-06-10 02:33:49'),
(16, 'dm7T6SR8FCfhpR2UFvSHdR72nNuafOYETDux1g7y.jpg', 'Bingkai Foto', 'Bingkai Foto', '2026-06-10 02:34:01', '2026-06-10 02:34:01');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_telp` bigint NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_loyalty_member` tinyint(1) NOT NULL DEFAULT '0',
  `member_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loyalty_tier` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `loyalty_points` int UNSIGNED NOT NULL DEFAULT '0',
  `loyalty_total_spent` bigint UNSIGNED NOT NULL DEFAULT '0',
  `loyalty_transaction_count` int UNSIGNED NOT NULL DEFAULT '0',
  `loyalty_member_since` timestamp NULL DEFAULT NULL,
  `last_purchase_at` timestamp NULL DEFAULT NULL,
  `province_id` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regency_id` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regency_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_id` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `village_id` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `village_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_campaigns`
--

CREATE TABLE `customer_campaigns` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `context_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audience_filters` json DEFAULT NULL,
  `audience_snapshot` json DEFAULT NULL,
  `message_template` text COLLATE utf8mb4_unicode_ci,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_campaign_logs`
--

CREATE TABLE `customer_campaign_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_campaign_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `transaction_id` bigint UNSIGNED DEFAULT NULL,
  `receivable_id` bigint UNSIGNED DEFAULT NULL,
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payload` json DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_credits`
--

CREATE TABLE `customer_credits` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `sales_return_id` bigint UNSIGNED NOT NULL,
  `amount` bigint NOT NULL,
  `balance` bigint NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_segments`
--

CREATE TABLE `customer_segments` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `auto_rule_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rule_config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_segments`
--

INSERT INTO `customer_segments` (`id`, `name`, `slug`, `type`, `is_active`, `description`, `auto_rule_type`, `rule_config`, `created_at`, `updated_at`) VALUES
(1, 'High Spender', 'high_spender', 'auto', 1, 'Pelanggan dengan total belanja tinggi.', 'spending', '{\"min_total_spent\": 1500000}', '2026-06-10 01:56:03', '2026-06-10 01:56:03'),
(2, 'Frequent Buyer', 'frequent_buyer', 'auto', 1, 'Pelanggan yang aktif berbelanja dengan frekuensi tinggi.', 'purchase_frequency', '{\"recent_days\": 45, \"min_transaction_count\": 5}', '2026-06-10 01:56:03', '2026-06-11 21:14:12'),
(3, 'Inactive Customer', 'inactive_customer', 'auto', 1, 'Pelanggan yang sudah lama tidak melakukan pembelian ulang.', 'purchase_frequency', '{\"inactivity_days_min\": 30, \"min_transaction_count\": 1}', '2026-06-10 01:56:03', '2026-06-10 01:56:03'),
(4, 'Credit Customer', 'credit_customer', 'auto', 1, 'Pelanggan yang masih memiliki piutang aktif.', 'receivable_behavior', '{\"require_outstanding_receivable\": true}', '2026-06-10 01:56:03', '2026-06-10 01:56:03'),
(5, 'Overdue Customer', 'overdue_customer', 'auto', 1, 'Pelanggan dengan piutang jatuh tempo atau overdue.', 'receivable_behavior', '{\"overdue_only\": true}', '2026-06-10 01:56:03', '2026-06-10 01:56:03');

-- --------------------------------------------------------

--
-- Table structure for table `customer_segment_memberships`
--

CREATE TABLE `customer_segment_memberships` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `customer_segment_id` bigint UNSIGNED NOT NULL,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `matched_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_vouchers`
--

CREATE TABLE `customer_vouchers` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed_amount',
  `discount_value` decimal(15,2) NOT NULL,
  `minimum_order` bigint UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `used_transaction_id` bigint UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_vouchers`
--

INSERT INTO `customer_vouchers` (`id`, `customer_id`, `code`, `name`, `discount_type`, `discount_value`, `minimum_order`, `is_active`, `is_used`, `starts_at`, `expires_at`, `used_at`, `used_transaction_id`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'VCR-ANDI10', 'Voucher Loyal Gold', 'fixed_amount', 10000.00, 75000, 1, 0, '2026-06-03 00:44:25', '2026-07-10 00:44:25', NULL, NULL, NULL, NULL, '2026-06-10 00:44:25', '2026-06-10 00:44:25'),
(3, 5, 'VCR-EKO25', 'Voucher Platinum', 'fixed_amount', 25000.00, 150000, 1, 0, '2026-06-03 00:44:25', '2026-07-10 00:44:25', NULL, NULL, NULL, NULL, '2026-06-10 00:44:25', '2026-06-10 00:44:25');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receivings`
--

CREATE TABLE `goods_receivings` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_order_id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED DEFAULT NULL,
  `document_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `received_by` bigint UNSIGNED DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receiving_items`
--

CREATE TABLE `goods_receiving_items` (
  `id` bigint UNSIGNED NOT NULL,
  `goods_receiving_id` bigint UNSIGNED NOT NULL,
  `purchase_order_item_id` bigint UNSIGNED DEFAULT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `qty_received` int NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `indonesia_cities`
--

CREATE TABLE `indonesia_cities` (
  `id` bigint UNSIGNED NOT NULL,
  `code` char(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `indonesia_cities`
--

INSERT INTO `indonesia_cities` (`id`, `code`, `province_code`, `name`, `meta`, `created_at`, `updated_at`) VALUES
(1, '1101', 'AC', 'Kabupaten Simeulue', 'Kabupaten Simeulue', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(2, '1102', 'AC', 'Kabupaten Aceh Singkil', 'Kabupaten Aceh Singkil', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(3, '1103', 'AC', 'Kabupaten Aceh Selatan', 'Kabupaten Aceh Selatan', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(4, '1104', 'AC', 'Kabupaten Aceh Tenggara', 'Kabupaten Aceh Tenggara', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(5, '1105', 'AC', 'Kabupaten Aceh Timur', 'Kabupaten Aceh Timur', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(6, '1106', 'AC', 'Kabupaten Aceh Tengah', 'Kabupaten Aceh Tengah', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(7, '1107', 'AC', 'Kabupaten Aceh Barat', 'Kabupaten Aceh Barat', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(8, '1108', 'AC', 'Kabupaten Aceh Besar', 'Kabupaten Aceh Besar', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(9, '1109', 'AC', 'Kabupaten Pidie', 'Kabupaten Pidie', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(10, '1110', 'AC', 'Kabupaten Bireuen', 'Kabupaten Bireuen', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(11, '1111', 'AC', 'Kabupaten Aceh Utara', 'Kabupaten Aceh Utara', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(12, '1112', 'AC', 'Kabupaten Aceh Barat Daya', 'Kabupaten Aceh Barat Daya', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(13, '1113', 'AC', 'Kabupaten Gayo Lues', 'Kabupaten Gayo Lues', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(14, '1114', 'AC', 'Kabupaten Aceh Tamiang', 'Kabupaten Aceh Tamiang', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(15, '1115', 'AC', 'Kabupaten Nagan Raya', 'Kabupaten Nagan Raya', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(16, '1116', 'AC', 'Kabupaten Aceh Jaya', 'Kabupaten Aceh Jaya', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(17, '1117', 'AC', 'Kabupaten Bener Meriah', 'Kabupaten Bener Meriah', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(18, '1118', 'AC', 'Kabupaten Pidie Jaya', 'Kabupaten Pidie Jaya', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(19, '1171', 'AC', 'Kota Banda Aceh', 'Kota Banda Aceh', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(20, '1172', 'AC', 'Kota Sabang', 'Kota Sabang', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(21, '1173', 'AC', 'Kota Langsa', 'Kota Langsa', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(22, '1174', 'AC', 'Kota Lhokseumawe', 'Kota Lhokseumawe', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(23, '1175', 'AC', 'Kota Subulussalam', 'Kota Subulussalam', '2026-06-12 04:35:08', '2026-06-12 04:35:08'),
(24, '1201', 'SU', 'Kabupaten Nias', 'Kabupaten Nias', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(25, '1202', 'SU', 'Kabupaten Mandailing Natal', 'Kabupaten Mandailing Natal', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(26, '1203', 'SU', 'Kabupaten Tapanuli Selatan', 'Kabupaten Tapanuli Selatan', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(27, '1204', 'SU', 'Kabupaten Tapanuli Tengah', 'Kabupaten Tapanuli Tengah', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(28, '1205', 'SU', 'Kabupaten Tapanuli Utara', 'Kabupaten Tapanuli Utara', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(29, '1206', 'SU', 'Kabupaten Toba', 'Kabupaten Toba', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(30, '1207', 'SU', 'Kabupaten Labuhanbatu', 'Kabupaten Labuhanbatu', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(31, '1208', 'SU', 'Kabupaten Asahan', 'Kabupaten Asahan', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(32, '1209', 'SU', 'Kabupaten Simalungun', 'Kabupaten Simalungun', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(33, '1210', 'SU', 'Kabupaten Dairi', 'Kabupaten Dairi', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(34, '1211', 'SU', 'Kabupaten Karo', 'Kabupaten Karo', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(35, '1212', 'SU', 'Kabupaten Deli Serdang', 'Kabupaten Deli Serdang', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(36, '1213', 'SU', 'Kabupaten Langkat', 'Kabupaten Langkat', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(37, '1214', 'SU', 'Kabupaten Nias Selatan', 'Kabupaten Nias Selatan', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(38, '1215', 'SU', 'Kabupaten Humbang Hasundutan', 'Kabupaten Humbang Hasundutan', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(39, '1216', 'SU', 'Kabupaten Pakpak Bharat', 'Kabupaten Pakpak Bharat', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(40, '1217', 'SU', 'Kabupaten Samosir', 'Kabupaten Samosir', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(41, '1218', 'SU', 'Kabupaten Serdang Bedagai', 'Kabupaten Serdang Bedagai', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(42, '1219', 'SU', 'Kabupaten Batu Bara', 'Kabupaten Batu Bara', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(43, '1220', 'SU', 'Kabupaten Padang Lawas Utara', 'Kabupaten Padang Lawas Utara', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(44, '1221', 'SU', 'Kabupaten Padang Lawas', 'Kabupaten Padang Lawas', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(45, '1222', 'SU', 'Kabupaten Labuhanbatu Selatan', 'Kabupaten Labuhanbatu Selatan', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(46, '1223', 'SU', 'Kabupaten Labuhanbatu Utara', 'Kabupaten Labuhanbatu Utara', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(47, '1224', 'SU', 'Kabupaten Nias Utara', 'Kabupaten Nias Utara', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(48, '1225', 'SU', 'Kabupaten Nias Barat', 'Kabupaten Nias Barat', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(49, '1271', 'SU', 'Kota Sibolga', 'Kota Sibolga', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(50, '1272', 'SU', 'Kota Tanjungbalai', 'Kota Tanjungbalai', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(51, '1273', 'SU', 'Kota Pematangsiantar', 'Kota Pematangsiantar', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(52, '1274', 'SU', 'Kota Tebing Tinggi', 'Kota Tebing Tinggi', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(53, '1275', 'SU', 'Kota Medan', 'Kota Medan', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(54, '1276', 'SU', 'Kota Binjai', 'Kota Binjai', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(55, '1277', 'SU', 'Kota Padangsidimpuan', 'Kota Padangsidimpuan', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(56, '1278', 'SU', 'Kota Gunungsitoli', 'Kota Gunungsitoli', '2026-06-12 04:35:40', '2026-06-12 04:35:40'),
(57, '1301', 'SB', 'Kabupaten Pesisir Selatan', 'Kabupaten Pesisir Selatan', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(58, '1302', 'SB', 'Kabupaten Solok', 'Kabupaten Solok', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(59, '1303', 'SB', 'Kabupaten Sijunjung', 'Kabupaten Sijunjung', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(60, '1304', 'SB', 'Kabupaten Tanah Datar', 'Kabupaten Tanah Datar', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(61, '1305', 'SB', 'Kabupaten Padang Pariaman', 'Kabupaten Padang Pariaman', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(62, '1306', 'SB', 'Kabupaten Agam', 'Kabupaten Agam', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(63, '1307', 'SB', 'Kabupaten Lima Puluh Kota', 'Kabupaten Lima Puluh Kota', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(64, '1308', 'SB', 'Kabupaten Pasaman', 'Kabupaten Pasaman', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(65, '1309', 'SB', 'Kabupaten Kepulauan Mentawai', 'Kabupaten Kepulauan Mentawai', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(66, '1310', 'SB', 'Kabupaten Dharmasraya', 'Kabupaten Dharmasraya', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(67, '1311', 'SB', 'Kabupaten Solok Selatan', 'Kabupaten Solok Selatan', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(68, '1312', 'SB', 'Kabupaten Pasaman Barat', 'Kabupaten Pasaman Barat', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(69, '1371', 'SB', 'Kota Padang', 'Kota Padang', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(70, '1372', 'SB', 'Kota Solok', 'Kota Solok', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(71, '1373', 'SB', 'Kota Sawahlunto', 'Kota Sawahlunto', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(72, '1374', 'SB', 'Kota Padang Panjang', 'Kota Padang Panjang', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(73, '1375', 'SB', 'Kota Bukittinggi', 'Kota Bukittinggi', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(74, '1376', 'SB', 'Kota Payakumbuh', 'Kota Payakumbuh', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(75, '1377', 'SB', 'Kota Pariaman', 'Kota Pariaman', '2026-06-12 04:36:15', '2026-06-12 04:36:15'),
(76, '1401', 'RI', 'Kabupaten Kuantan Singingi', 'Kabupaten Kuantan Singingi', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(77, '1402', 'RI', 'Kabupaten Indragiri Hulu', 'Kabupaten Indragiri Hulu', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(78, '1403', 'RI', 'Kabupaten Indragiri Hilir', 'Kabupaten Indragiri Hilir', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(79, '1404', 'RI', 'Kabupaten Pelalawan', 'Kabupaten Pelalawan', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(80, '1405', 'RI', 'Kabupaten Siak', 'Kabupaten Siak', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(81, '1406', 'RI', 'Kabupaten Kampar', 'Kabupaten Kampar', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(82, '1407', 'RI', 'Kabupaten Rokan Hulu', 'Kabupaten Rokan Hulu', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(83, '1408', 'RI', 'Kabupaten Bengkalis', 'Kabupaten Bengkalis', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(84, '1409', 'RI', 'Kabupaten Rokan Hilir', 'Kabupaten Rokan Hilir', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(85, '1410', 'RI', 'Kabupaten Kepulauan Meranti', 'Kabupaten Kepulauan Meranti', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(86, '1471', 'RI', 'Kota Pekanbaru', 'Kota Pekanbaru', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(87, '1473', 'RI', 'Kota Dumai', 'Kota Dumai', '2026-06-12 04:36:46', '2026-06-12 04:36:46'),
(88, '2101', 'KR', 'Kabupaten Karimun', 'Kabupaten Karimun', '2026-06-12 04:36:57', '2026-06-12 04:36:57'),
(89, '2102', 'KR', 'Kabupaten Bintan', 'Kabupaten Bintan', '2026-06-12 04:36:57', '2026-06-12 04:36:57'),
(90, '2103', 'KR', 'Kabupaten Natuna', 'Kabupaten Natuna', '2026-06-12 04:36:57', '2026-06-12 04:36:57'),
(91, '2104', 'KR', 'Kabupaten Lingga', 'Kabupaten Lingga', '2026-06-12 04:36:57', '2026-06-12 04:36:57'),
(92, '2105', 'KR', 'Kabupaten Kepulauan Anambas', 'Kabupaten Kepulauan Anambas', '2026-06-12 04:36:57', '2026-06-12 04:36:57'),
(93, '2171', 'KR', 'Kota Batam', 'Kota Batam', '2026-06-12 04:36:57', '2026-06-12 04:36:57'),
(94, '2172', 'KR', 'Kota Tanjungpinang', 'Kota Tanjungpinang', '2026-06-12 04:36:57', '2026-06-12 04:36:57'),
(95, '1501', 'JA', 'Kabupaten Kerinci', 'Kabupaten Kerinci', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(96, '1502', 'JA', 'Kabupaten Merangin', 'Kabupaten Merangin', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(97, '1503', 'JA', 'Kabupaten Sarolangun', 'Kabupaten Sarolangun', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(98, '1504', 'JA', 'Kabupaten Batanghari', 'Kabupaten Batanghari', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(99, '1505', 'JA', 'Kabupaten Muaro Jambi', 'Kabupaten Muaro Jambi', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(100, '1506', 'JA', 'Kabupaten Tanjung Jabung Timur', 'Kabupaten Tanjung Jabung Timur', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(101, '1507', 'JA', 'Kabupaten Tanjung Jabung Barat', 'Kabupaten Tanjung Jabung Barat', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(102, '1508', 'JA', 'Kabupaten Tebo', 'Kabupaten Tebo', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(103, '1509', 'JA', 'Kabupaten Bungo', 'Kabupaten Bungo', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(104, '1571', 'JA', 'Kota Jambi', 'Kota Jambi', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(105, '1572', 'JA', 'Kota Sungai Penuh', 'Kota Sungai Penuh', '2026-06-12 04:37:14', '2026-06-12 04:37:14'),
(106, '1601', 'SS', 'Kabupaten Ogan Komering Ulu', 'Kabupaten Ogan Komering Ulu', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(107, '1602', 'SS', 'Kabupaten Ogan Komering Ilir', 'Kabupaten Ogan Komering Ilir', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(108, '1603', 'SS', 'Kabupaten Muara Enim', 'Kabupaten Muara Enim', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(109, '1604', 'SS', 'Kabupaten Lahat', 'Kabupaten Lahat', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(110, '1605', 'SS', 'Kabupaten Musi Rawas', 'Kabupaten Musi Rawas', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(111, '1606', 'SS', 'Kabupaten Musi Banyuasin', 'Kabupaten Musi Banyuasin', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(112, '1607', 'SS', 'Kabupaten Banyuasin', 'Kabupaten Banyuasin', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(113, '1608', 'SS', 'Kabupaten Ogan Komering Ulu Selatan', 'Kabupaten Ogan Komering Ulu Selatan', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(114, '1609', 'SS', 'Kabupaten Ogan Komering Ulu Timur', 'Kabupaten Ogan Komering Ulu Timur', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(115, '1610', 'SS', 'Kabupaten Ogan Ilir', 'Kabupaten Ogan Ilir', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(116, '1611', 'SS', 'Kabupaten Empat Lawang', 'Kabupaten Empat Lawang', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(117, '1612', 'SS', 'Kabupaten Penukal Abab Lematang Ilir', 'Kabupaten Penukal Abab Lematang Ilir', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(118, '1613', 'SS', 'Kabupaten Musi Rawas Utara', 'Kabupaten Musi Rawas Utara', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(119, '1671', 'SS', 'Kota Palembang', 'Kota Palembang', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(120, '1672', 'SS', 'Kota Prabumulih', 'Kota Prabumulih', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(121, '1673', 'SS', 'Kota Pagar Alam', 'Kota Pagar Alam', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(122, '1674', 'SS', 'Kota Lubuklinggau', 'Kota Lubuklinggau', '2026-06-12 04:37:28', '2026-06-12 04:37:28'),
(123, '1901', 'BB', 'Kabupaten Bangka', 'Kabupaten Bangka', '2026-06-12 04:37:53', '2026-06-12 04:37:53'),
(124, '1902', 'BB', 'Kabupaten Belitung', 'Kabupaten Belitung', '2026-06-12 04:37:53', '2026-06-12 04:37:53'),
(125, '1903', 'BB', 'Kabupaten Bangka Selatan', 'Kabupaten Bangka Selatan', '2026-06-12 04:37:53', '2026-06-12 04:37:53'),
(126, '1904', 'BB', 'Kabupaten Bangka Tengah', 'Kabupaten Bangka Tengah', '2026-06-12 04:37:53', '2026-06-12 04:37:53'),
(127, '1905', 'BB', 'Kabupaten Bangka Barat', 'Kabupaten Bangka Barat', '2026-06-12 04:37:53', '2026-06-12 04:37:53'),
(128, '1906', 'BB', 'Kabupaten Belitung Timur', 'Kabupaten Belitung Timur', '2026-06-12 04:37:53', '2026-06-12 04:37:53'),
(129, '1971', 'BB', 'Kota Pangkal Pinang', 'Kota Pangkal Pinang', '2026-06-12 04:37:53', '2026-06-12 04:37:53'),
(130, '1701', 'BE', 'Kabupaten Bengkulu Selatan', 'Kabupaten Bengkulu Selatan', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(131, '1702', 'BE', 'Kabupaten Rejang Lebong', 'Kabupaten Rejang Lebong', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(132, '1703', 'BE', 'Kabupaten Bengkulu Utara', 'Kabupaten Bengkulu Utara', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(133, '1704', 'BE', 'Kabupaten Kaur', 'Kabupaten Kaur', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(134, '1705', 'BE', 'Kabupaten Seluma', 'Kabupaten Seluma', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(135, '1706', 'BE', 'Kabupaten Mukomuko', 'Kabupaten Mukomuko', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(136, '1707', 'BE', 'Kabupaten Lebong', 'Kabupaten Lebong', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(137, '1708', 'BE', 'Kabupaten Kepahiang', 'Kabupaten Kepahiang', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(138, '1709', 'BE', 'Kabupaten Bengkulu Tengah', 'Kabupaten Bengkulu Tengah', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(139, '1771', 'BE', 'Kota Bengkulu', 'Kota Bengkulu', '2026-06-12 04:38:02', '2026-06-12 04:38:02'),
(140, '1801', 'LA', 'Kabupaten Lampung Barat', 'Kabupaten Lampung Barat', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(141, '1802', 'LA', 'Kabupaten Tanggamus', 'Kabupaten Tanggamus', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(142, '1803', 'LA', 'Kabupaten Lampung Selatan', 'Kabupaten Lampung Selatan', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(143, '1804', 'LA', 'Kabupaten Lampung Timur', 'Kabupaten Lampung Timur', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(144, '1805', 'LA', 'Kabupaten Lampung Tengah', 'Kabupaten Lampung Tengah', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(145, '1806', 'LA', 'Kabupaten Lampung Utara', 'Kabupaten Lampung Utara', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(146, '1807', 'LA', 'Kabupaten Way Kanan', 'Kabupaten Way Kanan', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(147, '1808', 'LA', 'Kabupaten Tulang Bawang', 'Kabupaten Tulang Bawang', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(148, '1809', 'LA', 'Kabupaten Pesawaran', 'Kabupaten Pesawaran', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(149, '1810', 'LA', 'Kabupaten Pringsewu', 'Kabupaten Pringsewu', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(150, '1811', 'LA', 'Kabupaten Mesuji', 'Kabupaten Mesuji', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(151, '1812', 'LA', 'Kabupaten Tulang Bawang Barat', 'Kabupaten Tulang Bawang Barat', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(152, '1813', 'LA', 'Kabupaten Pesisir Barat', 'Kabupaten Pesisir Barat', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(153, '1871', 'LA', 'Kota Bandar Lampung', 'Kota Bandar Lampung', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(154, '1872', 'LA', 'Kota Metro', 'Kota Metro', '2026-06-12 04:38:18', '2026-06-12 04:38:18'),
(155, '3101', 'JK', 'Kabupaten Kepulauan Seribu', 'Kabupaten Kepulauan Seribu', '2026-06-12 04:38:34', '2026-06-12 04:38:34'),
(156, '3171', 'JK', 'Kota Administrasi Jakarta Pusat', 'Kota Administrasi Jakarta Pusat', '2026-06-12 04:38:34', '2026-06-12 04:38:34'),
(157, '3172', 'JK', 'Kota Administrasi Jakarta Utara', 'Kota Administrasi Jakarta Utara', '2026-06-12 04:38:34', '2026-06-12 04:38:34'),
(158, '3173', 'JK', 'Kota Administrasi Jakarta Barat', 'Kota Administrasi Jakarta Barat', '2026-06-12 04:38:34', '2026-06-12 04:38:34'),
(159, '3174', 'JK', 'Kota Administrasi Jakarta Selatan', 'Kota Administrasi Jakarta Selatan', '2026-06-12 04:38:34', '2026-06-12 04:38:34'),
(160, '3175', 'JK', 'Kota Administrasi Jakarta Timur', 'Kota Administrasi Jakarta Timur', '2026-06-12 04:38:34', '2026-06-12 04:38:34'),
(161, '3201', 'JB', 'Kabupaten Bogor', 'Kabupaten Bogor', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(162, '3202', 'JB', 'Kabupaten Sukabumi', 'Kabupaten Sukabumi', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(163, '3203', 'JB', 'Kabupaten Cianjur', 'Kabupaten Cianjur', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(164, '3204', 'JB', 'Kabupaten Bandung', 'Kabupaten Bandung', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(165, '3205', 'JB', 'Kabupaten Garut', 'Kabupaten Garut', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(166, '3206', 'JB', 'Kabupaten Tasikmalaya', 'Kabupaten Tasikmalaya', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(167, '3207', 'JB', 'Kabupaten Ciamis', 'Kabupaten Ciamis', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(168, '3208', 'JB', 'Kabupaten Kuningan', 'Kabupaten Kuningan', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(169, '3209', 'JB', 'Kabupaten Cirebon', 'Kabupaten Cirebon', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(170, '3210', 'JB', 'Kabupaten Majalengka', 'Kabupaten Majalengka', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(171, '3211', 'JB', 'Kabupaten Sumedang', 'Kabupaten Sumedang', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(172, '3212', 'JB', 'Kabupaten Indramayu', 'Kabupaten Indramayu', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(173, '3213', 'JB', 'Kabupaten Subang', 'Kabupaten Subang', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(174, '3214', 'JB', 'Kabupaten Purwakarta', 'Kabupaten Purwakarta', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(175, '3215', 'JB', 'Kabupaten Karawang', 'Kabupaten Karawang', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(176, '3216', 'JB', 'Kabupaten Bekasi', 'Kabupaten Bekasi', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(177, '3217', 'JB', 'Kabupaten Bandung Barat', 'Kabupaten Bandung Barat', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(178, '3218', 'JB', 'Kabupaten Pangandaran', 'Kabupaten Pangandaran', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(179, '3271', 'JB', 'Kota Bogor', 'Kota Bogor', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(180, '3272', 'JB', 'Kota Sukabumi', 'Kota Sukabumi', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(181, '3273', 'JB', 'Kota Bandung', 'Kota Bandung', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(182, '3274', 'JB', 'Kota Cirebon', 'Kota Cirebon', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(183, '3275', 'JB', 'Kota Bekasi', 'Kota Bekasi', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(184, '3276', 'JB', 'Kota Depok', 'Kota Depok', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(185, '3277', 'JB', 'Kota Cimahi', 'Kota Cimahi', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(186, '3278', 'JB', 'Kota Tasikmalaya', 'Kota Tasikmalaya', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(187, '3279', 'JB', 'Kota Banjar', 'Kota Banjar', '2026-06-12 04:38:48', '2026-06-12 04:38:48'),
(188, '3301', 'JT', 'Kabupaten Cilacap', 'Kabupaten Cilacap', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(189, '3302', 'JT', 'Kabupaten Banyumas', 'Kabupaten Banyumas', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(190, '3303', 'JT', 'Kabupaten Purbalingga', 'Kabupaten Purbalingga', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(191, '3304', 'JT', 'Kabupaten Banjarnegara', 'Kabupaten Banjarnegara', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(192, '3305', 'JT', 'Kabupaten Kebumen', 'Kabupaten Kebumen', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(193, '3306', 'JT', 'Kabupaten Purworejo', 'Kabupaten Purworejo', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(194, '3307', 'JT', 'Kabupaten Wonosobo', 'Kabupaten Wonosobo', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(195, '3308', 'JT', 'Kabupaten Magelang', 'Kabupaten Magelang', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(196, '3309', 'JT', 'Kabupaten Boyolali', 'Kabupaten Boyolali', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(197, '3310', 'JT', 'Kabupaten Klaten', 'Kabupaten Klaten', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(198, '3311', 'JT', 'Kabupaten Sukoharjo', 'Kabupaten Sukoharjo', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(199, '3312', 'JT', 'Kabupaten Wonogiri', 'Kabupaten Wonogiri', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(200, '3313', 'JT', 'Kabupaten Karanganyar', 'Kabupaten Karanganyar', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(201, '3314', 'JT', 'Kabupaten Sragen', 'Kabupaten Sragen', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(202, '3315', 'JT', 'Kabupaten Grobogan', 'Kabupaten Grobogan', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(203, '3316', 'JT', 'Kabupaten Blora', 'Kabupaten Blora', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(204, '3317', 'JT', 'Kabupaten Rembang', 'Kabupaten Rembang', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(205, '3318', 'JT', 'Kabupaten Pati', 'Kabupaten Pati', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(206, '3319', 'JT', 'Kabupaten Kudus', 'Kabupaten Kudus', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(207, '3320', 'JT', 'Kabupaten Jepara', 'Kabupaten Jepara', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(208, '3321', 'JT', 'Kabupaten Demak', 'Kabupaten Demak', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(209, '3322', 'JT', 'Kabupaten Semarang', 'Kabupaten Semarang', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(210, '3323', 'JT', 'Kabupaten Temanggung', 'Kabupaten Temanggung', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(211, '3324', 'JT', 'Kabupaten Kendal', 'Kabupaten Kendal', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(212, '3325', 'JT', 'Kabupaten Batang', 'Kabupaten Batang', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(213, '3326', 'JT', 'Kabupaten Pekalongan', 'Kabupaten Pekalongan', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(214, '3327', 'JT', 'Kabupaten Pemalang', 'Kabupaten Pemalang', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(215, '3328', 'JT', 'Kabupaten Tegal', 'Kabupaten Tegal', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(216, '3329', 'JT', 'Kabupaten Brebes', 'Kabupaten Brebes', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(217, '3371', 'JT', 'Kota Magelang', 'Kota Magelang', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(218, '3372', 'JT', 'Kota Surakarta', 'Kota Surakarta', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(219, '3373', 'JT', 'Kota Salatiga', 'Kota Salatiga', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(220, '3374', 'JT', 'Kota Semarang', 'Kota Semarang', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(221, '3375', 'JT', 'Kota Pekalongan', 'Kota Pekalongan', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(222, '3376', 'JT', 'Kota Tegal', 'Kota Tegal', '2026-06-12 04:39:11', '2026-06-12 04:39:11'),
(223, '3401', 'YO', 'Kabupaten Kulon Progo', 'Kabupaten Kulon Progo', '2026-06-12 04:39:31', '2026-06-12 04:39:31'),
(224, '3402', 'YO', 'Kabupaten Bantul', 'Kabupaten Bantul', '2026-06-12 04:39:31', '2026-06-12 04:39:31'),
(225, '3403', 'YO', 'Kabupaten Gunungkidul', 'Kabupaten Gunungkidul', '2026-06-12 04:39:31', '2026-06-12 04:39:31'),
(226, '3404', 'YO', 'Kabupaten Sleman', 'Kabupaten Sleman', '2026-06-12 04:39:31', '2026-06-12 04:39:31'),
(227, '3471', 'YO', 'Kota Yogyakarta', 'Kota Yogyakarta', '2026-06-12 04:39:31', '2026-06-12 04:39:31'),
(228, '3501', 'JI', 'Kabupaten Pacitan', 'Kabupaten Pacitan', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(229, '3502', 'JI', 'Kabupaten Ponorogo', 'Kabupaten Ponorogo', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(230, '3503', 'JI', 'Kabupaten Trenggalek', 'Kabupaten Trenggalek', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(231, '3504', 'JI', 'Kabupaten Tulungagung', 'Kabupaten Tulungagung', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(232, '3505', 'JI', 'Kabupaten Blitar', 'Kabupaten Blitar', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(233, '3506', 'JI', 'Kabupaten Kediri', 'Kabupaten Kediri', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(234, '3507', 'JI', 'Kabupaten Malang', 'Kabupaten Malang', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(235, '3508', 'JI', 'Kabupaten Lumajang', 'Kabupaten Lumajang', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(236, '3509', 'JI', 'Kabupaten Jember', 'Kabupaten Jember', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(237, '3510', 'JI', 'Kabupaten Banyuwangi', 'Kabupaten Banyuwangi', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(238, '3511', 'JI', 'Kabupaten Bondowoso', 'Kabupaten Bondowoso', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(239, '3512', 'JI', 'Kabupaten Situbondo', 'Kabupaten Situbondo', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(240, '3513', 'JI', 'Kabupaten Probolinggo', 'Kabupaten Probolinggo', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(241, '3514', 'JI', 'Kabupaten Pasuruan', 'Kabupaten Pasuruan', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(242, '3515', 'JI', 'Kabupaten Sidoarjo', 'Kabupaten Sidoarjo', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(243, '3516', 'JI', 'Kabupaten Mojokerto', 'Kabupaten Mojokerto', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(244, '3517', 'JI', 'Kabupaten Jombang', 'Kabupaten Jombang', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(245, '3518', 'JI', 'Kabupaten Nganjuk', 'Kabupaten Nganjuk', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(246, '3519', 'JI', 'Kabupaten Madiun', 'Kabupaten Madiun', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(247, '3520', 'JI', 'Kabupaten Magetan', 'Kabupaten Magetan', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(248, '3521', 'JI', 'Kabupaten Ngawi', 'Kabupaten Ngawi', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(249, '3522', 'JI', 'Kabupaten Bojonegoro', 'Kabupaten Bojonegoro', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(250, '3523', 'JI', 'Kabupaten Tuban', 'Kabupaten Tuban', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(251, '3524', 'JI', 'Kabupaten Lamongan', 'Kabupaten Lamongan', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(252, '3525', 'JI', 'Kabupaten Gresik', 'Kabupaten Gresik', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(253, '3526', 'JI', 'Kabupaten Bangkalan', 'Kabupaten Bangkalan', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(254, '3527', 'JI', 'Kabupaten Sampang', 'Kabupaten Sampang', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(255, '3528', 'JI', 'Kabupaten Pamekasan', 'Kabupaten Pamekasan', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(256, '3529', 'JI', 'Kabupaten Sumenep', 'Kabupaten Sumenep', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(257, '3571', 'JI', 'Kota Kediri', 'Kota Kediri', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(258, '3572', 'JI', 'Kota Blitar', 'Kota Blitar', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(259, '3573', 'JI', 'Kota Malang', 'Kota Malang', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(260, '3574', 'JI', 'Kota Probolinggo', 'Kota Probolinggo', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(261, '3575', 'JI', 'Kota Pasuruan', 'Kota Pasuruan', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(262, '3576', 'JI', 'Kota Mojokerto', 'Kota Mojokerto', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(263, '3577', 'JI', 'Kota Madiun', 'Kota Madiun', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(264, '3578', 'JI', 'Kota Surabaya', 'Kota Surabaya', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(265, '3579', 'JI', 'Kota Batu', 'Kota Batu', '2026-06-12 04:39:44', '2026-06-12 04:39:44'),
(266, '3601', 'BT', 'Kabupaten Pandeglang', 'Kabupaten Pandeglang', '2026-06-12 04:40:31', '2026-06-12 04:40:31'),
(267, '3602', 'BT', 'Kabupaten Lebak', 'Kabupaten Lebak', '2026-06-12 04:40:31', '2026-06-12 04:40:31'),
(268, '3603', 'BT', 'Kabupaten Tangerang', 'Kabupaten Tangerang', '2026-06-12 04:40:31', '2026-06-12 04:40:31'),
(269, '3604', 'BT', 'Kabupaten Serang', 'Kabupaten Serang', '2026-06-12 04:40:31', '2026-06-12 04:40:31'),
(270, '3671', 'BT', 'Kota Tangerang', 'Kota Tangerang', '2026-06-12 04:40:31', '2026-06-12 04:40:31'),
(271, '3672', 'BT', 'Kota Cilegon', 'Kota Cilegon', '2026-06-12 04:40:31', '2026-06-12 04:40:31'),
(272, '3673', 'BT', 'Kota Serang', 'Kota Serang', '2026-06-12 04:40:31', '2026-06-12 04:40:31'),
(273, '3674', 'BT', 'Kota Tangerang Selatan', 'Kota Tangerang Selatan', '2026-06-12 04:40:31', '2026-06-12 04:40:31'),
(274, '5101', 'BA', 'Kabupaten Jembrana', 'Kabupaten Jembrana', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(275, '5102', 'BA', 'Kabupaten Tabanan', 'Kabupaten Tabanan', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(276, '5103', 'BA', 'Kabupaten Badung', 'Kabupaten Badung', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(277, '5104', 'BA', 'Kabupaten Gianyar', 'Kabupaten Gianyar', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(278, '5105', 'BA', 'Kabupaten Klungkung', 'Kabupaten Klungkung', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(279, '5106', 'BA', 'Kabupaten Bangli', 'Kabupaten Bangli', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(280, '5107', 'BA', 'Kabupaten Karangasem', 'Kabupaten Karangasem', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(281, '5108', 'BA', 'Kabupaten Buleleng', 'Kabupaten Buleleng', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(282, '5171', 'BA', 'Kota Denpasar', 'Kota Denpasar', '2026-06-12 04:40:43', '2026-06-12 04:40:43'),
(283, '5201', 'NB', 'Kabupaten Lombok Barat', 'Kabupaten Lombok Barat', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(284, '5202', 'NB', 'Kabupaten Lombok Tengah', 'Kabupaten Lombok Tengah', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(285, '5203', 'NB', 'Kabupaten Lombok Timur', 'Kabupaten Lombok Timur', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(286, '5204', 'NB', 'Kabupaten Sumbawa', 'Kabupaten Sumbawa', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(287, '5205', 'NB', 'Kabupaten Dompu', 'Kabupaten Dompu', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(288, '5206', 'NB', 'Kabupaten Bima', 'Kabupaten Bima', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(289, '5207', 'NB', 'Kabupaten Sumbawa Barat', 'Kabupaten Sumbawa Barat', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(290, '5208', 'NB', 'Kabupaten Lombok Utara', 'Kabupaten Lombok Utara', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(291, '5271', 'NB', 'Kota Mataram', 'Kota Mataram', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(292, '5272', 'NB', 'Kota Bima', 'Kota Bima', '2026-06-12 04:40:56', '2026-06-12 04:40:56'),
(293, '5301', 'NT', 'Kabupaten Sumba Barat', 'Kabupaten Sumba Barat', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(294, '5302', 'NT', 'Kabupaten Sumba Timur', 'Kabupaten Sumba Timur', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(295, '5303', 'NT', 'Kabupaten Kupang', 'Kabupaten Kupang', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(296, '5304', 'NT', 'Kabupaten Timor Tengah Selatan', 'Kabupaten Timor Tengah Selatan', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(297, '5305', 'NT', 'Kabupaten Timor Tengah Utara', 'Kabupaten Timor Tengah Utara', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(298, '5306', 'NT', 'Kabupaten Belu', 'Kabupaten Belu', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(299, '5307', 'NT', 'Kabupaten Alor', 'Kabupaten Alor', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(300, '5308', 'NT', 'Kabupaten Lembata', 'Kabupaten Lembata', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(301, '5309', 'NT', 'Kabupaten Flores Timur', 'Kabupaten Flores Timur', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(302, '5310', 'NT', 'Kabupaten Sikka', 'Kabupaten Sikka', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(303, '5311', 'NT', 'Kabupaten Ende', 'Kabupaten Ende', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(304, '5312', 'NT', 'Kabupaten Ngada', 'Kabupaten Ngada', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(305, '5313', 'NT', 'Kabupaten Manggarai', 'Kabupaten Manggarai', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(306, '5314', 'NT', 'Kabupaten Rote Ndao', 'Kabupaten Rote Ndao', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(307, '5315', 'NT', 'Kabupaten Manggarai Barat', 'Kabupaten Manggarai Barat', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(308, '5316', 'NT', 'Kabupaten Sumba Tengah', 'Kabupaten Sumba Tengah', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(309, '5317', 'NT', 'Kabupaten Sumba Barat Daya', 'Kabupaten Sumba Barat Daya', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(310, '5318', 'NT', 'Kabupaten Nagekeo', 'Kabupaten Nagekeo', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(311, '5319', 'NT', 'Kabupaten Manggarai Timur', 'Kabupaten Manggarai Timur', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(312, '5320', 'NT', 'Kabupaten Sabu Raijua', 'Kabupaten Sabu Raijua', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(313, '5321', 'NT', 'Kabupaten Malaka', 'Kabupaten Malaka', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(314, '5371', 'NT', 'Kota Kupang', 'Kota Kupang', '2026-06-12 04:41:19', '2026-06-12 04:41:19'),
(315, '6101', 'KB', 'Kabupaten Sambas', 'Kabupaten Sambas', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(316, '6102', 'KB', 'Kabupaten Bengkayang', 'Kabupaten Bengkayang', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(317, '6103', 'KB', 'Kabupaten Landak', 'Kabupaten Landak', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(318, '6104', 'KB', 'Kabupaten Mempawah', 'Kabupaten Mempawah', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(319, '6105', 'KB', 'Kabupaten Sanggau', 'Kabupaten Sanggau', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(320, '6106', 'KB', 'Kabupaten Ketapang', 'Kabupaten Ketapang', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(321, '6107', 'KB', 'Kabupaten Sintang', 'Kabupaten Sintang', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(322, '6108', 'KB', 'Kabupaten Kapuas Hulu', 'Kabupaten Kapuas Hulu', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(323, '6109', 'KB', 'Kabupaten Sekadau', 'Kabupaten Sekadau', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(324, '6110', 'KB', 'Kabupaten Melawi', 'Kabupaten Melawi', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(325, '6111', 'KB', 'Kabupaten Kayong Utara', 'Kabupaten Kayong Utara', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(326, '6112', 'KB', 'Kabupaten Kubu Raya', 'Kabupaten Kubu Raya', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(327, '6171', 'KB', 'Kota Pontianak', 'Kota Pontianak', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(328, '6172', 'KB', 'Kota Singkawang', 'Kota Singkawang', '2026-06-12 04:41:30', '2026-06-12 04:41:30'),
(329, '6201', 'KT', 'Kabupaten Kotawaringin Barat', 'Kabupaten Kotawaringin Barat', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(330, '6202', 'KT', 'Kabupaten Kotawaringin Timur', 'Kabupaten Kotawaringin Timur', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(331, '6203', 'KT', 'Kabupaten Kapuas', 'Kabupaten Kapuas', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(332, '6204', 'KT', 'Kabupaten Barito Selatan', 'Kabupaten Barito Selatan', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(333, '6205', 'KT', 'Kabupaten Barito Utara', 'Kabupaten Barito Utara', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(334, '6206', 'KT', 'Kabupaten Sukamara', 'Kabupaten Sukamara', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(335, '6207', 'KT', 'Kabupaten Lamandau', 'Kabupaten Lamandau', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(336, '6208', 'KT', 'Kabupaten Seruyan', 'Kabupaten Seruyan', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(337, '6209', 'KT', 'Kabupaten Katingan', 'Kabupaten Katingan', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(338, '6210', 'KT', 'Kabupaten Pulang Pisau', 'Kabupaten Pulang Pisau', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(339, '6211', 'KT', 'Kabupaten Gunung Mas', 'Kabupaten Gunung Mas', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(340, '6212', 'KT', 'Kabupaten Barito Timur', 'Kabupaten Barito Timur', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(341, '6213', 'KT', 'Kabupaten Murung Raya', 'Kabupaten Murung Raya', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(342, '6271', 'KT', 'Kota Palangka Raya', 'Kota Palangka Raya', '2026-06-12 04:41:47', '2026-06-12 04:41:47'),
(343, '6301', 'KS', 'Kabupaten Tanah Laut', 'Kabupaten Tanah Laut', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(344, '6302', 'KS', 'Kabupaten Kotabaru', 'Kabupaten Kotabaru', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(345, '6303', 'KS', 'Kabupaten Banjar', 'Kabupaten Banjar', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(346, '6304', 'KS', 'Kabupaten Barito Kuala', 'Kabupaten Barito Kuala', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(347, '6305', 'KS', 'Kabupaten Tapin', 'Kabupaten Tapin', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(348, '6306', 'KS', 'Kabupaten Hulu Sungai Selatan', 'Kabupaten Hulu Sungai Selatan', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(349, '6307', 'KS', 'Kabupaten Hulu Sungai Tengah', 'Kabupaten Hulu Sungai Tengah', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(350, '6308', 'KS', 'Kabupaten Hulu Sungai Utara', 'Kabupaten Hulu Sungai Utara', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(351, '6309', 'KS', 'Kabupaten Tabalong', 'Kabupaten Tabalong', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(352, '6310', 'KS', 'Kabupaten Tanah Bumbu', 'Kabupaten Tanah Bumbu', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(353, '6311', 'KS', 'Kabupaten Balangan', 'Kabupaten Balangan', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(354, '6371', 'KS', 'Kota Banjarmasin', 'Kota Banjarmasin', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(355, '6372', 'KS', 'Kota Banjarbaru', 'Kota Banjarbaru', '2026-06-12 04:41:58', '2026-06-12 04:41:58'),
(356, '6401', 'KI', 'Kabupaten Paser', 'Kabupaten Paser', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(357, '6402', 'KI', 'Kabupaten Kutai Barat', 'Kabupaten Kutai Barat', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(358, '6403', 'KI', 'Kabupaten Kutai Kartanegara', 'Kabupaten Kutai Kartanegara', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(359, '6404', 'KI', 'Kabupaten Kutai Timur', 'Kabupaten Kutai Timur', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(360, '6405', 'KI', 'Kabupaten Berau', 'Kabupaten Berau', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(361, '6409', 'KI', 'Kabupaten Penajam Paser Utara', 'Kabupaten Penajam Paser Utara', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(362, '6411', 'KI', 'Kabupaten Mahakam Ulu', 'Kabupaten Mahakam Ulu', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(363, '6471', 'KI', 'Kota Balikpapan', 'Kota Balikpapan', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(364, '6472', 'KI', 'Kota Samarinda', 'Kota Samarinda', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(365, '6474', 'KI', 'Kota Bontang', 'Kota Bontang', '2026-06-12 04:42:14', '2026-06-12 04:42:14'),
(366, '6501', 'KU', 'Kabupaten Bulungan', 'Kabupaten Bulungan', '2026-06-12 04:42:35', '2026-06-12 04:42:35'),
(367, '6502', 'KU', 'Kabupaten Malinau', 'Kabupaten Malinau', '2026-06-12 04:42:35', '2026-06-12 04:42:35'),
(368, '6503', 'KU', 'Kabupaten Nunukan', 'Kabupaten Nunukan', '2026-06-12 04:42:35', '2026-06-12 04:42:35'),
(369, '6504', 'KU', 'Kabupaten Tana Tidung', 'Kabupaten Tana Tidung', '2026-06-12 04:42:35', '2026-06-12 04:42:35'),
(370, '6571', 'KU', 'Kota Tarakan', 'Kota Tarakan', '2026-06-12 04:42:35', '2026-06-12 04:42:35'),
(371, '7101', 'SA', 'Kabupaten Bolaang Mongondow', 'Kabupaten Bolaang Mongondow', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(372, '7102', 'SA', 'Kabupaten Minahasa', 'Kabupaten Minahasa', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(373, '7103', 'SA', 'Kabupaten Kepulauan Sangihe', 'Kabupaten Kepulauan Sangihe', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(374, '7104', 'SA', 'Kabupaten Kepulauan Talaud', 'Kabupaten Kepulauan Talaud', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(375, '7105', 'SA', 'Kabupaten Minahasa Selatan', 'Kabupaten Minahasa Selatan', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(376, '7106', 'SA', 'Kabupaten Minahasa Utara', 'Kabupaten Minahasa Utara', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(377, '7107', 'SA', 'Kabupaten Bolaang Mongondow Utara', 'Kabupaten Bolaang Mongondow Utara', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(378, '7108', 'SA', 'Kabupaten Siau Tagulandang Biaro', 'Kabupaten Siau Tagulandang Biaro', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(379, '7109', 'SA', 'Kabupaten Minahasa Tenggara', 'Kabupaten Minahasa Tenggara', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(380, '7110', 'SA', 'Kabupaten Bolaang Mongondow Selatan', 'Kabupaten Bolaang Mongondow Selatan', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(381, '7111', 'SA', 'Kabupaten Bolaang Mongondow Timur', 'Kabupaten Bolaang Mongondow Timur', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(382, '7171', 'SA', 'Kota Manado', 'Kota Manado', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(383, '7172', 'SA', 'Kota Bitung', 'Kota Bitung', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(384, '7173', 'SA', 'Kota Tomohon', 'Kota Tomohon', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(385, '7174', 'SA', 'Kota Kotamobagu', 'Kota Kotamobagu', '2026-06-12 04:42:43', '2026-06-12 04:42:43'),
(386, '7501', 'GO', 'Kabupaten Boalemo', 'Kabupaten Boalemo', '2026-06-12 04:42:55', '2026-06-12 04:42:55'),
(387, '7502', 'GO', 'Kabupaten Gorontalo', 'Kabupaten Gorontalo', '2026-06-12 04:42:55', '2026-06-12 04:42:55'),
(388, '7503', 'GO', 'Kabupaten Pohuwato', 'Kabupaten Pohuwato', '2026-06-12 04:42:55', '2026-06-12 04:42:55'),
(389, '7504', 'GO', 'Kabupaten Bone Bolango', 'Kabupaten Bone Bolango', '2026-06-12 04:42:55', '2026-06-12 04:42:55'),
(390, '7505', 'GO', 'Kabupaten Gorontalo Utara', 'Kabupaten Gorontalo Utara', '2026-06-12 04:42:55', '2026-06-12 04:42:55'),
(391, '7571', 'GO', 'Kota Gorontalo', 'Kota Gorontalo', '2026-06-12 04:42:55', '2026-06-12 04:42:55'),
(392, '7201', 'ST', 'Kabupaten Banggai', 'Kabupaten Banggai', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(393, '7202', 'ST', 'Kabupaten Poso', 'Kabupaten Poso', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(394, '7203', 'ST', 'Kabupaten Donggala', 'Kabupaten Donggala', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(395, '7204', 'ST', 'Kabupaten Toli-Toli', 'Kabupaten Toli-Toli', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(396, '7205', 'ST', 'Kabupaten Buol', 'Kabupaten Buol', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(397, '7206', 'ST', 'Kabupaten Morowali', 'Kabupaten Morowali', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(398, '7207', 'ST', 'Kabupaten Banggai Kepulauan', 'Kabupaten Banggai Kepulauan', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(399, '7208', 'ST', 'Kabupaten Parigi Moutong', 'Kabupaten Parigi Moutong', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(400, '7209', 'ST', 'Kabupaten Tojo Una-Una', 'Kabupaten Tojo Una-Una', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(401, '7210', 'ST', 'Kabupaten Sigi', 'Kabupaten Sigi', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(402, '7211', 'ST', 'Kabupaten Banggai Laut', 'Kabupaten Banggai Laut', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(403, '7212', 'ST', 'Kabupaten Morowali Utara', 'Kabupaten Morowali Utara', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(404, '7271', 'ST', 'Kota Palu', 'Kota Palu', '2026-06-12 04:43:03', '2026-06-12 04:43:03'),
(405, '7301', 'SN', 'Kabupaten Kepulauan Selayar', 'Kabupaten Kepulauan Selayar', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(406, '7302', 'SN', 'Kabupaten Bulukumba', 'Kabupaten Bulukumba', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(407, '7303', 'SN', 'Kabupaten Bantaeng', 'Kabupaten Bantaeng', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(408, '7304', 'SN', 'Kabupaten Jeneponto', 'Kabupaten Jeneponto', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(409, '7305', 'SN', 'Kabupaten Takalar', 'Kabupaten Takalar', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(410, '7306', 'SN', 'Kabupaten Gowa', 'Kabupaten Gowa', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(411, '7307', 'SN', 'Kabupaten Sinjai', 'Kabupaten Sinjai', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(412, '7308', 'SN', 'Kabupaten Maros', 'Kabupaten Maros', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(413, '7309', 'SN', 'Kabupaten Pangkajene dan Kepulauan', 'Kabupaten Pangkajene dan Kepulauan', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(414, '7310', 'SN', 'Kabupaten Barru', 'Kabupaten Barru', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(415, '7311', 'SN', 'Kabupaten Bone', 'Kabupaten Bone', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(416, '7312', 'SN', 'Kabupaten Soppeng', 'Kabupaten Soppeng', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(417, '7313', 'SN', 'Kabupaten Wajo', 'Kabupaten Wajo', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(418, '7314', 'SN', 'Kabupaten Sidenreng Rappang', 'Kabupaten Sidenreng Rappang', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(419, '7315', 'SN', 'Kabupaten Pinrang', 'Kabupaten Pinrang', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(420, '7316', 'SN', 'Kabupaten Enrekang', 'Kabupaten Enrekang', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(421, '7317', 'SN', 'Kabupaten Luwu', 'Kabupaten Luwu', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(422, '7318', 'SN', 'Kabupaten Tana Toraja', 'Kabupaten Tana Toraja', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(423, '7322', 'SN', 'Kabupaten Luwu Utara', 'Kabupaten Luwu Utara', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(424, '7325', 'SN', 'Kabupaten Luwu Timur', 'Kabupaten Luwu Timur', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(425, '7326', 'SN', 'Kabupaten Toraja Utara', 'Kabupaten Toraja Utara', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(426, '7371', 'SN', 'Kota Makassar', 'Kota Makassar', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(427, '7372', 'SN', 'Kota Parepare', 'Kota Parepare', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(428, '7373', 'SN', 'Kota Palopo', 'Kota Palopo', '2026-06-12 04:43:23', '2026-06-12 04:43:23'),
(429, '7401', 'SG', 'Kabupaten Buton', 'Kabupaten Buton', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(430, '7402', 'SG', 'Kabupaten Muna', 'Kabupaten Muna', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(431, '7403', 'SG', 'Kabupaten Konawe', 'Kabupaten Konawe', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(432, '7404', 'SG', 'Kabupaten Kolaka', 'Kabupaten Kolaka', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(433, '7405', 'SG', 'Kabupaten Konawe Selatan', 'Kabupaten Konawe Selatan', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(434, '7406', 'SG', 'Kabupaten Bombana', 'Kabupaten Bombana', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(435, '7407', 'SG', 'Kabupaten Wakatobi', 'Kabupaten Wakatobi', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(436, '7408', 'SG', 'Kabupaten Kolaka Utara', 'Kabupaten Kolaka Utara', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(437, '7409', 'SG', 'Kabupaten Buton Utara', 'Kabupaten Buton Utara', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(438, '7410', 'SG', 'Kabupaten Konawe Utara', 'Kabupaten Konawe Utara', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(439, '7411', 'SG', 'Kabupaten Kolaka Timur', 'Kabupaten Kolaka Timur', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(440, '7412', 'SG', 'Kabupaten Konawe Kepulauan', 'Kabupaten Konawe Kepulauan', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(441, '7413', 'SG', 'Kabupaten Muna Barat', 'Kabupaten Muna Barat', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(442, '7414', 'SG', 'Kabupaten Buton Tengah', 'Kabupaten Buton Tengah', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(443, '7415', 'SG', 'Kabupaten Buton Selatan', 'Kabupaten Buton Selatan', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(444, '7471', 'SG', 'Kota Kendari', 'Kota Kendari', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(445, '7472', 'SG', 'Kota Baubau', 'Kota Baubau', '2026-06-12 04:43:35', '2026-06-12 04:43:35'),
(446, '7601', 'SR', 'Kabupaten Majene', 'Kabupaten Majene', '2026-06-12 04:43:47', '2026-06-12 04:43:47'),
(447, '7602', 'SR', 'Kabupaten Polewali Mandar', 'Kabupaten Polewali Mandar', '2026-06-12 04:43:47', '2026-06-12 04:43:47'),
(448, '7603', 'SR', 'Kabupaten Mamasa', 'Kabupaten Mamasa', '2026-06-12 04:43:47', '2026-06-12 04:43:47'),
(449, '7604', 'SR', 'Kabupaten Mamuju', 'Kabupaten Mamuju', '2026-06-12 04:43:47', '2026-06-12 04:43:47'),
(450, '7605', 'SR', 'Kabupaten Pasangkayu', 'Kabupaten Pasangkayu', '2026-06-12 04:43:47', '2026-06-12 04:43:47'),
(451, '7606', 'SR', 'Kabupaten Mamuju Tengah', 'Kabupaten Mamuju Tengah', '2026-06-12 04:43:47', '2026-06-12 04:43:47');
INSERT INTO `indonesia_cities` (`id`, `code`, `province_code`, `name`, `meta`, `created_at`, `updated_at`) VALUES
(452, '8101', 'MA', 'Kabupaten Maluku Tengah', 'Kabupaten Maluku Tengah', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(453, '8102', 'MA', 'Kabupaten Maluku Tenggara', 'Kabupaten Maluku Tenggara', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(454, '8103', 'MA', 'Kabupaten Kepulauan Tanimbar', 'Kabupaten Kepulauan Tanimbar', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(455, '8104', 'MA', 'Kabupaten Buru', 'Kabupaten Buru', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(456, '8105', 'MA', 'Kabupaten Seram Bagian Timur', 'Kabupaten Seram Bagian Timur', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(457, '8106', 'MA', 'Kabupaten Seram Bagian Barat', 'Kabupaten Seram Bagian Barat', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(458, '8107', 'MA', 'Kabupaten Kepulauan Aru', 'Kabupaten Kepulauan Aru', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(459, '8108', 'MA', 'Kabupaten Maluku Barat Daya', 'Kabupaten Maluku Barat Daya', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(460, '8109', 'MA', 'Kabupaten Buru Selatan', 'Kabupaten Buru Selatan', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(461, '8171', 'MA', 'Kota Ambon', 'Kota Ambon', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(462, '8172', 'MA', 'Kota Tual', 'Kota Tual', '2026-06-12 04:44:09', '2026-06-12 04:44:09'),
(463, '8201', 'MU', 'Kabupaten Halmahera Barat', 'Kabupaten Halmahera Barat', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(464, '8202', 'MU', 'Kabupaten Halmahera Tengah', 'Kabupaten Halmahera Tengah', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(465, '8203', 'MU', 'Kabupaten Halmahera Utara', 'Kabupaten Halmahera Utara', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(466, '8204', 'MU', 'Kabupaten Halmahera Selatan', 'Kabupaten Halmahera Selatan', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(467, '8205', 'MU', 'Kabupaten Kepulauan Sula', 'Kabupaten Kepulauan Sula', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(468, '8206', 'MU', 'Kabupaten Halmahera Timur', 'Kabupaten Halmahera Timur', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(469, '8207', 'MU', 'Kabupaten Pulau Morotai', 'Kabupaten Pulau Morotai', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(470, '8208', 'MU', 'Kabupaten Pulau Taliabu', 'Kabupaten Pulau Taliabu', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(471, '8271', 'MU', 'Kota Ternate', 'Kota Ternate', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(472, '8272', 'MU', 'Kota Tidore Kepulauan', 'Kota Tidore Kepulauan', '2026-06-12 04:44:20', '2026-06-12 04:44:20'),
(473, '9103', 'PA', 'Kabupaten Jayapura', 'Kabupaten Jayapura', '2026-06-12 04:47:24', '2026-06-12 04:47:24'),
(474, '9105', 'PA', 'Kabupaten Kepulauan Yapen', 'Kabupaten Kepulauan Yapen', '2026-06-12 04:47:24', '2026-06-12 04:47:24'),
(475, '9106', 'PA', 'Kabupaten Biak Numfor', 'Kabupaten Biak Numfor', '2026-06-12 04:47:24', '2026-06-12 04:47:24'),
(476, '9110', 'PA', 'Kabupaten Sarmi', 'Kabupaten Sarmi', '2026-06-12 04:47:24', '2026-06-12 04:47:24'),
(477, '9111', 'PA', 'Kabupaten Keerom', 'Kabupaten Keerom', '2026-06-12 04:47:24', '2026-06-12 04:47:24'),
(478, '9115', 'PA', 'Kabupaten Waropen', 'Kabupaten Waropen', '2026-06-12 04:47:24', '2026-06-12 04:47:24'),
(479, '9171', 'PA', 'Kota Jayapura', 'Kota Jayapura', '2026-06-12 04:47:24', '2026-06-12 04:47:24'),
(480, '9201', 'PB', 'Kabupaten Fakfak', 'Kabupaten Fakfak', '2026-06-12 04:47:33', '2026-06-12 04:47:33'),
(481, '9202', 'PB', 'Kabupaten Kaimana', 'Kabupaten Kaimana', '2026-06-12 04:47:33', '2026-06-12 04:47:33'),
(482, '9203', 'PB', 'Kabupaten Teluk Wondama', 'Kabupaten Teluk Wondama', '2026-06-12 04:47:33', '2026-06-12 04:47:33'),
(483, '9204', 'PB', 'Kabupaten Teluk Bintuni', 'Kabupaten Teluk Bintuni', '2026-06-12 04:47:33', '2026-06-12 04:47:33'),
(484, '9205', 'PB', 'Kabupaten Manokwari', 'Kabupaten Manokwari', '2026-06-12 04:47:33', '2026-06-12 04:47:33'),
(485, '9211', 'PB', 'Kabupaten Manokwari Selatan', 'Kabupaten Manokwari Selatan', '2026-06-12 04:47:33', '2026-06-12 04:47:33'),
(486, '9212', 'PB', 'Kabupaten Pegunungan Arfak', 'Kabupaten Pegunungan Arfak', '2026-06-12 04:47:33', '2026-06-12 04:47:33'),
(487, '9301', 'PS', 'Kabupaten Merauke', 'Kabupaten Merauke', '2026-06-12 04:47:44', '2026-06-12 04:47:44'),
(488, '9302', 'PS', 'Kabupaten Boven Digoel', 'Kabupaten Boven Digoel', '2026-06-12 04:47:44', '2026-06-12 04:47:44'),
(489, '9303', 'PS', 'Kabupaten Mappi', 'Kabupaten Mappi', '2026-06-12 04:47:44', '2026-06-12 04:47:44'),
(490, '9304', 'PS', 'Kabupaten Asmat', 'Kabupaten Asmat', '2026-06-12 04:47:44', '2026-06-12 04:47:44'),
(491, '9401', 'PT', 'Kabupaten Nabire', 'Kabupaten Nabire', '2026-06-12 04:47:53', '2026-06-12 04:47:53'),
(492, '9402', 'PT', 'Kabupaten Paniai', 'Kabupaten Paniai', '2026-06-12 04:47:53', '2026-06-12 04:47:53'),
(493, '9403', 'PT', 'Kabupaten Mimika', 'Kabupaten Mimika', '2026-06-12 04:47:53', '2026-06-12 04:47:53'),
(494, '9404', 'PT', 'Kabupaten Puncak Jaya', 'Kabupaten Puncak Jaya', '2026-06-12 04:47:53', '2026-06-12 04:47:53'),
(495, '9405', 'PT', 'Kabupaten Dogiyai', 'Kabupaten Dogiyai', '2026-06-12 04:47:53', '2026-06-12 04:47:53'),
(496, '9406', 'PT', 'Kabupaten Deiyai', 'Kabupaten Deiyai', '2026-06-12 04:47:53', '2026-06-12 04:47:53'),
(497, '9407', 'PT', 'Kabupaten Intan Jaya', 'Kabupaten Intan Jaya', '2026-06-12 04:47:53', '2026-06-12 04:47:53'),
(498, '9408', 'PT', 'Kabupaten Puncak', 'Kabupaten Puncak', '2026-06-12 04:47:53', '2026-06-12 04:47:53'),
(499, '9501', 'PP', 'Kabupaten Jayawijaya', 'Kabupaten Jayawijaya', '2026-06-12 04:48:02', '2026-06-12 04:48:02'),
(500, '9502', 'PP', 'Kabupaten Pegunungan Bintang', 'Kabupaten Pegunungan Bintang', '2026-06-12 04:48:02', '2026-06-12 04:48:02'),
(501, '9503', 'PP', 'Kabupaten Yahukimo', 'Kabupaten Yahukimo', '2026-06-12 04:48:02', '2026-06-12 04:48:02'),
(502, '9504', 'PP', 'Kabupaten Tolikara', 'Kabupaten Tolikara', '2026-06-12 04:48:02', '2026-06-12 04:48:02'),
(503, '9505', 'PP', 'Kabupaten Mamberamo Tengah', 'Kabupaten Mamberamo Tengah', '2026-06-12 04:48:02', '2026-06-12 04:48:02'),
(504, '9506', 'PP', 'Kabupaten Yalimo', 'Kabupaten Yalimo', '2026-06-12 04:48:02', '2026-06-12 04:48:02'),
(505, '9507', 'PP', 'Kabupaten Lanny Jaya', 'Kabupaten Lanny Jaya', '2026-06-12 04:48:02', '2026-06-12 04:48:02'),
(506, '9508', 'PP', 'Kabupaten Nduga', 'Kabupaten Nduga', '2026-06-12 04:48:02', '2026-06-12 04:48:02'),
(507, '9601', 'PD', 'Kabupaten Sorong', 'Kabupaten Sorong', '2026-06-12 04:48:09', '2026-06-12 04:48:09'),
(508, '9602', 'PD', 'Kabupaten Sorong Selatan', 'Kabupaten Sorong Selatan', '2026-06-12 04:48:09', '2026-06-12 04:48:09'),
(509, '9603', 'PD', 'Kabupaten Raja Ampat', 'Kabupaten Raja Ampat', '2026-06-12 04:48:09', '2026-06-12 04:48:09'),
(510, '9604', 'PD', 'Kabupaten Tambrauw', 'Kabupaten Tambrauw', '2026-06-12 04:48:09', '2026-06-12 04:48:09'),
(511, '9605', 'PD', 'Kabupaten Maybrat', 'Kabupaten Maybrat', '2026-06-12 04:48:09', '2026-06-12 04:48:09'),
(512, '9671', 'PD', 'Kota Sorong', 'Kota Sorong', '2026-06-12 04:48:09', '2026-06-12 04:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `indonesia_districts`
--

CREATE TABLE `indonesia_districts` (
  `id` bigint UNSIGNED NOT NULL,
  `code` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city_code` char(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `indonesia_provinces`
--

CREATE TABLE `indonesia_provinces` (
  `id` bigint UNSIGNED NOT NULL,
  `code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `indonesia_provinces`
--

INSERT INTO `indonesia_provinces` (`id`, `code`, `name`, `meta`, `created_at`, `updated_at`) VALUES
(1, 'AC', 'Aceh', 'Aceh', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(2, 'SU', 'Sumatera Utara', 'Sumatera Utara', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(3, 'SB', 'Sumatera Barat', 'Sumatera Barat', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(4, 'RI', 'Riau', 'Riau', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(5, 'KR', 'Kepulauan Riau', 'Kepulauan Riau', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(6, 'JA', 'Jambi', 'Jambi', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(7, 'SS', 'Sumatera Selatan', 'Sumatera Selatan', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(8, 'BB', 'Kepulauan Bangka Belitung', 'Kepulauan Bangka Belitung', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(9, 'BE', 'Bengkulu', 'Bengkulu', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(10, 'LA', 'Lampung', 'Lampung', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(11, 'JK', 'DKI Jakarta', 'DKI Jakarta', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(12, 'JB', 'Jawa Barat', 'Jawa Barat', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(13, 'JT', 'Jawa Tengah', 'Jawa Tengah', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(14, 'YO', 'DI Yogyakarta', 'DI Yogyakarta', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(15, 'JI', 'Jawa Timur', 'Jawa Timur', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(16, 'BT', 'Banten', 'Banten', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(17, 'BA', 'Bali', 'Bali', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(18, 'NB', 'Nusa Tenggara Barat', 'Nusa Tenggara Barat', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(19, 'NT', 'Nusa Tenggara Timur', 'Nusa Tenggara Timur', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(20, 'KB', 'Kalimantan Barat', 'Kalimantan Barat', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(21, 'KT', 'Kalimantan Tengah', 'Kalimantan Tengah', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(22, 'KS', 'Kalimantan Selatan', 'Kalimantan Selatan', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(23, 'KI', 'Kalimantan Timur', 'Kalimantan Timur', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(24, 'KU', 'Kalimantan Utara', 'Kalimantan Utara', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(25, 'SA', 'Sulawesi Utara', 'Sulawesi Utara', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(26, 'ST', 'Sulawesi Tengah', 'Sulawesi Tengah', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(27, 'SG', 'Sulawesi Tenggara', 'Sulawesi Tenggara', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(28, 'SR', 'Sulawesi Barat', 'Sulawesi Barat', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(29, 'SN', 'Sulawesi Selatan', 'Sulawesi Selatan', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(30, 'GO', 'Gorontalo', 'Gorontalo', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(31, 'MA', 'Maluku', 'Maluku', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(32, 'MU', 'Maluku Utara', 'Maluku Utara', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(33, 'PA', 'Papua', 'Papua', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(34, 'PB', 'Papua Barat', 'Papua Barat', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(35, 'PS', 'Papua Selatan', 'Papua Selatan', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(36, 'PT', 'Papua Tengah', 'Papua Tengah', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(37, 'PP', 'Papua Pegunungan', 'Papua Pegunungan', '2026-06-12 04:30:41', '2026-06-12 04:30:41'),
(38, 'PD', 'Papua Barat Daya', 'Papua Barat Daya', '2026-06-12 04:30:41', '2026-06-12 04:30:41');

-- --------------------------------------------------------

--
-- Table structure for table `indonesia_villages`
--

CREATE TABLE `indonesia_villages` (
  `id` bigint UNSIGNED NOT NULL,
  `code` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `district_code` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_point_histories`
--

CREATE TABLE `loyalty_point_histories` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `transaction_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `points_delta` int NOT NULL DEFAULT '0',
  `balance_after` int UNSIGNED NOT NULL DEFAULT '0',
  `amount_delta` bigint UNSIGNED NOT NULL DEFAULT '0',
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2016_08_03_072729_create_provinces_table', 1),
(5, '2016_08_03_072750_create_cities_table', 1),
(6, '2016_08_03_072804_create_districts_table', 1),
(7, '2016_08_03_072819_create_villages_table', 1),
(8, '2024_06_13_082620_create_permission_tables', 1),
(9, '2024_06_13_091315_add_avatar_field_to_users_table', 1),
(10, '2024_06_13_125039_create_customers_table', 1),
(11, '2024_06_13_130507_create_categories_table', 1),
(12, '2024_06_13_131744_create_products_table', 1),
(13, '2024_06_13_132800_create_transactions_table', 1),
(14, '2024_06_13_133940_create_transaction_details_table', 1),
(15, '2024_06_13_133948_create_carts_table', 1),
(16, '2024_06_13_133955_create_profits_table', 1),
(17, '2025_11_19_172334_create_payment_settings_table', 1),
(18, '2025_11_19_172346_add_payment_columns_to_transactions_table', 1),
(19, '2025_12_23_140000_add_hold_columns_to_carts_table', 1),
(20, '2025_12_25_200000_create_settings_table', 1),
(21, '2025_12_25_230000_create_bank_accounts_table', 1),
(22, '2025_12_25_230100_add_bank_transfer_columns', 1),
(23, '2025_12_26_010000_add_shipping_cost_to_transactions', 1),
(24, '2025_12_28_105534_add_sku_field_to_products_table', 1),
(25, '2025_12_30_000000_add_user_avatar_column_if_missing', 1),
(26, '2025_12_31_000000_create_product_notification_reads_table', 1),
(27, '2025_12_31_010000_add_store_settings_defaults', 1),
(28, '2025_12_31_020000_add_region_columns_to_customers_table', 1),
(29, '2026_01_01_000000_create_receivables_tables', 1),
(30, '2026_01_02_000000_create_suppliers_and_payables_tables', 1),
(31, '2026_04_05_000000_add_xendit_callback_token_to_payment_settings_table', 1),
(32, '2026_05_01_231134_create_stock_opname_tables', 1),
(33, '2026_05_02_000000_create_sales_return_tables', 1),
(34, '2026_05_02_010000_create_cashier_shifts_table', 1),
(35, '2026_05_02_010100_add_cashier_shift_references', 1),
(36, '2026_05_02_120000_create_audit_logs_table', 1),
(37, '2026_05_02_120000_encrypt_payment_setting_secrets', 1),
(38, '2026_05_04_000001_create_purchase_orders_table', 1),
(39, '2026_05_04_000002_create_goods_receivings_table', 1),
(40, '2026_05_04_000003_add_purchase_order_id_to_payables_table', 1),
(41, '2026_05_04_000004_add_collection_notes_to_receivables_table', 1),
(42, '2026_05_05_000001_create_supplier_returns_table', 1),
(43, '2026_05_05_000100_create_pricing_rules_table', 1),
(44, '2026_05_05_000101_add_pricing_columns_to_transaction_details_table', 1),
(45, '2026_05_05_000102_add_loyalty_columns_to_customers_transactions_and_pricing_rules', 1),
(46, '2026_05_05_000103_create_loyalty_point_histories_table', 1),
(47, '2026_05_05_000104_create_customer_vouchers_table', 1),
(48, '2026_05_05_000105_extend_pricing_rules_for_advanced_promotions', 1),
(49, '2026_05_05_000106_create_pricing_rule_qty_breaks_table', 1),
(50, '2026_05_05_000107_create_pricing_rule_bundle_items_table', 1),
(51, '2026_05_05_000108_create_pricing_rule_buy_get_items_table', 1),
(52, '2026_05_05_000109_add_loyalty_settings_defaults', 1),
(53, '2026_05_06_000110_create_customer_segments_table', 1),
(54, '2026_05_06_000111_create_customer_segment_memberships_table', 1),
(55, '2026_05_06_000112_create_customer_campaigns_table', 1),
(56, '2026_05_06_000113_create_customer_campaign_logs_table', 1),
(57, '2026_06_12_034515_add_soft_deletes_to_customers_and_products_table', 2),
(58, '2026_06_13_000001_add_qris_to_payment_settings', 3);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_permissions`
--

INSERT INTO `model_has_permissions` (`permission_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 1),
(3, 'App\\Models\\User', 1),
(4, 'App\\Models\\User', 1),
(5, 'App\\Models\\User', 1),
(6, 'App\\Models\\User', 1),
(7, 'App\\Models\\User', 1),
(8, 'App\\Models\\User', 1),
(9, 'App\\Models\\User', 1),
(10, 'App\\Models\\User', 1),
(11, 'App\\Models\\User', 1),
(12, 'App\\Models\\User', 1),
(13, 'App\\Models\\User', 1),
(14, 'App\\Models\\User', 1),
(15, 'App\\Models\\User', 1),
(16, 'App\\Models\\User', 1),
(17, 'App\\Models\\User', 1),
(18, 'App\\Models\\User', 1),
(19, 'App\\Models\\User', 1),
(20, 'App\\Models\\User', 1),
(21, 'App\\Models\\User', 1),
(22, 'App\\Models\\User', 1),
(23, 'App\\Models\\User', 1),
(24, 'App\\Models\\User', 1),
(25, 'App\\Models\\User', 1),
(26, 'App\\Models\\User', 1),
(27, 'App\\Models\\User', 1),
(28, 'App\\Models\\User', 1),
(29, 'App\\Models\\User', 1),
(30, 'App\\Models\\User', 1),
(31, 'App\\Models\\User', 1),
(32, 'App\\Models\\User', 1),
(33, 'App\\Models\\User', 1),
(34, 'App\\Models\\User', 1),
(35, 'App\\Models\\User', 1),
(36, 'App\\Models\\User', 1),
(37, 'App\\Models\\User', 1),
(38, 'App\\Models\\User', 1),
(39, 'App\\Models\\User', 1),
(40, 'App\\Models\\User', 1),
(41, 'App\\Models\\User', 1),
(42, 'App\\Models\\User', 1),
(43, 'App\\Models\\User', 1),
(44, 'App\\Models\\User', 1),
(45, 'App\\Models\\User', 1),
(46, 'App\\Models\\User', 1),
(47, 'App\\Models\\User', 1),
(48, 'App\\Models\\User', 1),
(49, 'App\\Models\\User', 1),
(50, 'App\\Models\\User', 1),
(51, 'App\\Models\\User', 1),
(52, 'App\\Models\\User', 1),
(53, 'App\\Models\\User', 1),
(54, 'App\\Models\\User', 1),
(55, 'App\\Models\\User', 1),
(56, 'App\\Models\\User', 1),
(57, 'App\\Models\\User', 1),
(58, 'App\\Models\\User', 1),
(59, 'App\\Models\\User', 1),
(60, 'App\\Models\\User', 1),
(61, 'App\\Models\\User', 1),
(62, 'App\\Models\\User', 1),
(63, 'App\\Models\\User', 1),
(64, 'App\\Models\\User', 1),
(65, 'App\\Models\\User', 1),
(66, 'App\\Models\\User', 1),
(67, 'App\\Models\\User', 1),
(68, 'App\\Models\\User', 1),
(69, 'App\\Models\\User', 1),
(70, 'App\\Models\\User', 1),
(71, 'App\\Models\\User', 1),
(72, 'App\\Models\\User', 1),
(73, 'App\\Models\\User', 1),
(74, 'App\\Models\\User', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(29, 'App\\Models\\User', 1),
(30, 'App\\Models\\User', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payables`
--

CREATE TABLE `payables` (
  `id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED DEFAULT NULL,
  `document_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `due_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `purchase_order_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payable_payments`
--

CREATE TABLE `payable_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `payable_id` bigint UNSIGNED NOT NULL,
  `paid_at` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `method` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `default_gateway` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `bank_transfer_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `qris_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `qris_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qris_merchant_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qris_nmid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doku_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `doku_client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doku_secret_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doku_production` tinyint(1) NOT NULL DEFAULT '0',
  `flip_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `flip_secret_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flip_validation_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flip_production` tinyint(1) NOT NULL DEFAULT '0',
  `midtrans_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `midtrans_server_key` text COLLATE utf8mb4_unicode_ci,
  `midtrans_client_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midtrans_production` tinyint(1) NOT NULL DEFAULT '0',
  `xendit_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `xendit_secret_key` text COLLATE utf8mb4_unicode_ci,
  `xendit_public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xendit_callback_token` text COLLATE utf8mb4_unicode_ci,
  `xendit_production` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `default_gateway`, `bank_transfer_enabled`, `qris_enabled`, `qris_image_path`, `qris_merchant_name`, `qris_nmid`, `doku_enabled`, `doku_client_id`, `doku_secret_key`, `doku_production`, `flip_enabled`, `flip_secret_key`, `flip_validation_token`, `flip_production`, `midtrans_enabled`, `midtrans_server_key`, `midtrans_client_key`, `midtrans_production`, `xendit_enabled`, `xendit_secret_key`, `xendit_public_key`, `xendit_callback_token`, `xendit_production`, `created_at`, `updated_at`) VALUES
(1, 'cash', 1, 1, 'qris/WsyRdgHAsEKuW10CoXUj4YVV5nWCN8nUUpab2roG.jpg', 'Popsnap', 'admin@gmail.com', 0, NULL, NULL, 0, 0, NULL, NULL, 0, 0, 'eyJpdiI6IkVtdmZxV0VnMzFxSXRRUTFUUkg2TVE9PSIsInZhbHVlIjoicDNBc2lDNlIxNVZxQlo1S0s2L2FjUT09IiwibWFjIjoiYWRhMWY0MDBkZGE0MTUwZDkzMDIxNDI0ODQ2NGU5MGE5NGFhODNmMjYwOTQ3MTJjMWYwNDhiMjViNjdmNjVkMCIsInRhZyI6IiJ9', NULL, 0, 0, NULL, NULL, NULL, 0, '2026-06-10 00:44:06', '2026-06-15 23:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(2, 'users-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(3, 'users-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(4, 'users-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(5, 'users-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(6, 'roles-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(7, 'roles-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(8, 'roles-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(9, 'roles-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(10, 'permissions-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(11, 'permissions-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(12, 'permissions-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(13, 'permissions-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(14, 'categories-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(15, 'categories-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(16, 'categories-edit', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(17, 'categories-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(18, 'products-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(19, 'products-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(20, 'products-edit', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(21, 'products-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(22, 'pricing-rules-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(23, 'pricing-rules-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(24, 'pricing-rules-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(25, 'pricing-rules-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(26, 'customers-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(27, 'customers-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(28, 'customers-edit', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(29, 'customers-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(30, 'customer-vouchers-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(31, 'customer-vouchers-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(32, 'customer-vouchers-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(33, 'customer-vouchers-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(34, 'customer-segments-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(35, 'customer-segments-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(36, 'customer-segments-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(37, 'customer-segments-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(38, 'crm-campaigns-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(39, 'crm-campaigns-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(40, 'crm-campaigns-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(41, 'crm-campaigns-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(42, 'crm-reminders-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(43, 'transactions-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(44, 'transactions-confirm-payment', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(45, 'receivables-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(46, 'receivables-pay', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(47, 'payables-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(48, 'payables-pay', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(49, 'suppliers-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(50, 'reports-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(51, 'profits-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(52, 'payment-settings-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(53, 'payment-settings-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(54, 'stock-opnames-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(55, 'stock-opnames-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(56, 'stock-opnames-finalize', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(57, 'stock-mutations-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(58, 'sales-returns-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(59, 'sales-returns-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(60, 'sales-returns-complete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(61, 'cashier-shifts-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(62, 'cashier-shifts-open', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(63, 'cashier-shifts-close', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(64, 'cashier-shifts-force-close', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(65, 'audit-logs-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(66, 'purchase-orders-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(67, 'purchase-orders-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(68, 'purchase-orders-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(69, 'purchase-orders-delete', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(70, 'goods-receivings-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(71, 'goods-receivings-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(72, 'supplier-returns-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(73, 'supplier-returns-create', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(74, 'supplier-returns-update', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `pricing_rules`
--

CREATE TABLE `pricing_rules` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard_discount',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int UNSIGNED NOT NULL DEFAULT '0',
  `target_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `customer_scope` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `eligible_loyalty_tiers` json DEFAULT NULL,
  `discount_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` decimal(15,2) NOT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `preview_quantity_multiplier` int UNSIGNED NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pricing_rule_bundle_items`
--

CREATE TABLE `pricing_rule_bundle_items` (
  `id` bigint UNSIGNED NOT NULL,
  `pricing_rule_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pricing_rule_buy_get_items`
--

CREATE TABLE `pricing_rule_buy_get_items` (
  `id` bigint UNSIGNED NOT NULL,
  `pricing_rule_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `role` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pricing_rule_qty_breaks`
--

CREATE TABLE `pricing_rule_qty_breaks` (
  `id` bigint UNSIGNED NOT NULL,
  `pricing_rule_id` bigint UNSIGNED NOT NULL,
  `min_qty` int UNSIGNED NOT NULL,
  `discount_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` decimal(15,2) NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `buy_price` bigint NOT NULL,
  `sell_price` bigint NOT NULL,
  `stock` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `image`, `barcode`, `sku`, `title`, `description`, `buy_price`, `sell_price`, `stock`, `created_at`, `updated_at`, `deleted_at`) VALUES
(62, 10, 'x9HlVXVsQv2hdr9zxS63XbwHCkKidGzRCPXdU763.jpg', 'PHB-0001', 'PHB-0001', 'Fisheye Box', 'Fisheye Box - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 18000, 30000, 50, '2026-06-10 09:22:46', '2026-06-10 02:27:59', NULL),
(63, 10, 'vpbpkLR7mStkFiTq5e69dXpDZUkqGvaXUY1ApBBw.jpg', 'PHB-0002', 'PHB-0002', 'Vintage Room', 'Vintage Room - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 21000, 35000, 48, '2026-06-10 09:22:46', '2026-06-16 19:50:40', NULL),
(64, 10, 'PhotoBox.jpg', 'PHB-0003', 'PHB-0003', 'Emma\'s Room', 'Emma\'s Room - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 21000, 35000, 46, '2026-06-10 09:22:46', '2026-06-16 10:53:29', NULL),
(65, 10, 'Qv7WpHeUeWol5zm2TfrxDcLkEXDGf6HoAjKREPzT.jpg', 'PHB-0004', 'PHB-0004', 'CCTV SnapBox', 'Big Box - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 21000, 35000, 49, '2026-06-10 09:22:46', '2026-06-16 10:53:29', NULL),
(66, 11, '0FTsODR68VobcF6JbTJ3RGet1azir8wCss0E9qXo.jpg', 'PHB-0005', 'PHB-0005', 'Celebration Package', 'Celebration Package - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 99000, 165000, 50, '2026-06-10 09:22:46', '2026-06-10 20:36:07', NULL),
(67, 12, '6YCiwh9MirzBnvbwwfbfJQeI49tC2x57iIrLAYtJ.jpg', 'PHB-0006', 'PHB-0006', 'Add Print', 'Add Print - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 3000, 5000, 50, '2026-06-10 09:22:46', '2026-06-10 02:53:49', NULL),
(68, 13, 'NWIIhfSSRVpibtBHcEWTHqS08lYd52vMFYfk5h9s.jpg', 'PHB-0007', 'PHB-0007', 'Keychain', 'Keychain - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 9000, 15000, 50, '2026-06-10 09:22:46', '2026-06-10 20:28:04', NULL),
(69, 14, '2qxTE0xVOfaIuNUEamf0KG0JE5vEchheQT5MPKKD.jpg', 'PHB-0008', 'PHB-0008', 'Card Package', 'Card Package - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 18000, 30000, 50, '2026-06-10 09:22:46', '2026-06-10 02:59:36', NULL),
(70, 15, '7lKM7ODWdZ6oIfO4hbPhFnVtWOmqu8EB07DbkWbL.jpg', 'PHB-0009', 'PHB-0009', 'Newspaper print', 'Newspaper print - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 6000, 10000, 50, '2026-06-10 09:22:46', '2026-06-10 02:57:25', NULL),
(71, 16, 'gaXUkAkPA3d868L2GKfId8hegKdvUxRyo2psljl0.jpg', 'PHB-0010', 'PHB-0010', 'Frame 4R', 'Frame 4R - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.', 15000, 25000, 50, '2026-06-10 09:22:46', '2026-06-10 20:33:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_notification_reads`
--

CREATE TABLE `product_notification_reads` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profits`
--

CREATE TABLE `profits` (
  `id` bigint UNSIGNED NOT NULL,
  `transaction_id` bigint UNSIGNED NOT NULL,
  `total` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED DEFAULT NULL,
  `document_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `ordered_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_order_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `qty_ordered` int NOT NULL DEFAULT '0',
  `qty_received` int NOT NULL DEFAULT '0',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receivables`
--

CREATE TABLE `receivables` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `transaction_id` bigint UNSIGNED DEFAULT NULL,
  `invoice` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `due_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `note` text COLLATE utf8mb4_unicode_ci,
  `collection_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receivable_payments`
--

CREATE TABLE `receivable_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `receivable_id` bigint UNSIGNED NOT NULL,
  `paid_at` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `method` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'users-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(2, 'roles-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(3, 'permissions-access', 'web', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(4, 'categories-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(5, 'products-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(6, 'pricing-rules-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(7, 'customers-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(8, 'customer-vouchers-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(9, 'customer-segments-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(10, 'crm-campaigns-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(11, 'crm-reminders-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(12, 'transactions-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(13, 'transactions-confirm-payment', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(14, 'receivables-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(15, 'payables-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(16, 'suppliers-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(17, 'reports-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(18, 'profits-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(19, 'payment-settings-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(20, 'payment-settings-update', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(21, 'stock-opnames-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(22, 'stock-mutations-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(23, 'sales-returns-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(24, 'cashier-shifts-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(25, 'audit-logs-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(26, 'purchase-orders-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(27, 'goods-receivings-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(28, 'supplier-returns-access', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(29, 'super-admin', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06'),
(30, 'cashier', 'web', '2026-06-10 00:44:06', '2026-06-10 00:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 2),
(7, 2),
(8, 2),
(9, 2),
(10, 3),
(11, 3),
(12, 3),
(13, 3),
(14, 4),
(15, 4),
(16, 4),
(17, 4),
(18, 5),
(19, 5),
(20, 5),
(21, 5),
(22, 6),
(23, 6),
(24, 6),
(25, 6),
(26, 7),
(27, 7),
(28, 7),
(29, 7),
(30, 8),
(31, 8),
(32, 8),
(33, 8),
(34, 9),
(35, 9),
(36, 9),
(37, 9),
(38, 10),
(39, 10),
(40, 10),
(41, 10),
(42, 11),
(43, 12),
(44, 12),
(44, 13),
(45, 14),
(46, 14),
(47, 15),
(48, 15),
(49, 16),
(50, 17),
(51, 18),
(52, 19),
(53, 19),
(53, 20),
(54, 21),
(55, 21),
(56, 21),
(57, 22),
(58, 23),
(59, 23),
(60, 23),
(61, 24),
(62, 24),
(63, 24),
(64, 24),
(65, 25),
(66, 26),
(67, 26),
(68, 26),
(69, 26),
(70, 27),
(71, 27),
(72, 28),
(73, 28),
(74, 28),
(1, 29),
(2, 29),
(3, 29),
(4, 29),
(5, 29),
(6, 29),
(7, 29),
(8, 29),
(9, 29),
(10, 29),
(11, 29),
(12, 29),
(13, 29),
(14, 29),
(15, 29),
(16, 29),
(17, 29),
(18, 29),
(19, 29),
(20, 29),
(21, 29),
(22, 29),
(23, 29),
(24, 29),
(25, 29),
(26, 29),
(27, 29),
(28, 29),
(29, 29),
(30, 29),
(31, 29),
(32, 29),
(33, 29),
(34, 29),
(35, 29),
(36, 29),
(37, 29),
(38, 29),
(39, 29),
(40, 29),
(41, 29),
(42, 29),
(43, 29),
(44, 29),
(45, 29),
(46, 29),
(47, 29),
(48, 29),
(49, 29),
(50, 29),
(51, 29),
(52, 29),
(53, 29),
(54, 29),
(55, 29),
(56, 29),
(57, 29),
(58, 29),
(59, 29),
(60, 29),
(61, 29),
(62, 29),
(63, 29),
(64, 29),
(65, 29),
(66, 29),
(67, 29),
(68, 29),
(69, 29),
(70, 29),
(71, 29),
(72, 29),
(73, 29),
(74, 29),
(1, 30),
(26, 30),
(27, 30),
(43, 30),
(45, 30),
(46, 30),
(47, 30),
(48, 30),
(49, 30),
(61, 30),
(62, 30),
(63, 30);

-- --------------------------------------------------------

--
-- Table structure for table `sales_returns`
--

CREATE TABLE `sales_returns` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `cashier_id` bigint UNSIGNED DEFAULT NULL,
  `cashier_shift_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `return_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'refund_cash',
  `refund_amount` bigint NOT NULL DEFAULT '0',
  `credited_amount` bigint NOT NULL DEFAULT '0',
  `total_return_amount` bigint NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_items`
--

CREATE TABLE `sales_return_items` (
  `id` bigint UNSIGNED NOT NULL,
  `sales_return_id` bigint UNSIGNED NOT NULL,
  `transaction_detail_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `qty_sold` int NOT NULL,
  `qty_returned_before` int NOT NULL DEFAULT '0',
  `qty_return` int NOT NULL,
  `unit_price` bigint NOT NULL,
  `subtotal` bigint NOT NULL,
  `return_reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `restock_to_inventory` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('8kiA4ltS2oeZhcMiqM6l4LNcxjY7mk13mzB8TrKQ', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiWTRzT0c4NDBlZDVHR2xHMGF5MFRNYjdxQmdROE9LenJaTDVtTnpLUCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQvc2V0dGluZ3MvcGF5bWVudHMiO3M6NToicm91dGUiO3M6MjI6InNldHRpbmdzLnBheW1lbnRzLmVkaXQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6ODoic2VjdXJpdHkiO2E6MTp7czoxODoic2Vzc2lvbl9zdGFydGVkX2F0IjtpOjE3ODE1OTAwMDc7fX0=', 1781590017),
('C2po8j9Qm8sF4fZ0zVTsipHQ5UAXskx0vWyRYhBu', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWkw2d1NTYmRiVkl2NlVrdnVPZmRaNzR5a0R3d2lVa1MxQ01ic0lpRyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781591765),
('HAbszlEQQSfyoeclBUOoxsDLZRPsif8zXVcTa2yC', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiWFFPbmNoWWhqNlFWUHpIbjlWY0ZKUzFXZHNJVGowdzhPWXJtWHM2SSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjg6InNlY3VyaXR5IjthOjE6e3M6MTg6InNlc3Npb25fc3RhcnRlZF9hdCI7aToxNzgxNTg5ODY3O31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo0NDoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2Rhc2hib2FyZC90cmFuc2FjdGlvbnMiO3M6NToicm91dGUiO3M6MTg6InRyYW5zYWN0aW9ucy5pbmRleCI7fXM6MzoidXJsIjthOjA6e31zOjQ6ImF1dGgiO2E6MTp7czoyMToicGFzc3dvcmRfY29uZmlybWVkX2F0IjtpOjE3ODE1OTEwNjk7fX0=', 1781591501),
('JbJYcKHtLJs46njZEfl2sQhLYzMNfOoMVKuMjooL', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVUpFakN1ZTcyMWVTclM0a3NoQ2w1ZWJYZ1FaNUZRVlhBeGdLQ0xoYyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjg6InNlY3VyaXR5IjthOjE6e3M6MTg6InNlc3Npb25fc3RhcnRlZF9hdCI7aToxNzgxNjY0Njk5O31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1781664699),
('Ngvj5qe2tEuBTIF7uFSjLyhe0hqXs5XRlGXCIBTT', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiNUNUdlA1SWpBUkZOYTR3ZlNzb1ZTVWhxamFVVjVFODJ1OU03R09LcCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjg6InNlY3VyaXR5IjthOjE6e3M6MTg6InNlc3Npb25fc3RhcnRlZF9hdCI7aToxNzgxNTkxODYyO319', 1781591862),
('szlZjQHxFuLwV5kAw9n7BDJQdGYPvHkso2Q3XHSV', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiVHlLOHVCa09LbFN2a21PNFRxaUR4UWV2ZXp0S2djRk9YWFkyUU4xeCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjg6InNlY3VyaXR5IjthOjE6e3M6MTg6InNlc3Npb25fc3RhcnRlZF9hdCI7aToxNzgxNjMyMzQxO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo0NDoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2Rhc2hib2FyZC90cmFuc2FjdGlvbnMiO3M6NToicm91dGUiO3M6MTg6InRyYW5zYWN0aW9ucy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1781632418),
('Ubc8IcWgT6n6hwFMch9wFcjZrYWuBIxJ98iKAt0a', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQUNYd0JKeVdDMFBGdjh1c3p1S25DQ1pEcm5MRVRjSk5NSUNKTmxzciI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQvdHJhbnNhY3Rpb25zIjtzOjU6InJvdXRlIjtzOjE4OiJ0cmFuc2FjdGlvbnMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6ODoic2VjdXJpdHkiO2E6MTp7czoxODoic2Vzc2lvbl9zdGFydGVkX2F0IjtpOjE3ODE2MTA1OTQ7fX0=', 1781611947);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'monthly_sales_target', '15000000', 'Target penjualan bulanan', '2026-06-10 00:43:59', '2026-06-10 00:44:25'),
(2, 'store_name', 'Popsnap', 'Nama toko', '2026-06-10 00:43:59', '2026-06-10 00:44:25'),
(3, 'store_logo', 'store/1x5fjA88i3VJBkvXMIujNZQZjtBTeFDqoAJAm9xo.png', 'Logo toko', '2026-06-10 00:43:59', '2026-06-12 00:27:54'),
(4, 'store_address', 'Jl. Ring Road Utara No.2, RW.4, Ngringin, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283', 'Alamat toko', '2026-06-10 00:43:59', '2026-06-10 03:19:04'),
(5, 'store_phone', '0851-8669-8837', 'Telepon toko', '2026-06-10 00:43:59', '2026-06-10 03:19:04'),
(6, 'store_email', 'halo@popsnap.test', 'Email toko', '2026-06-10 00:43:59', '2026-06-10 00:44:25'),
(7, 'store_website', 'https://www.instagram.com/popsnapstudio', 'Website toko', '2026-06-10 00:43:59', '2026-06-10 03:19:04'),
(8, 'store_city', 'Yogyakarta', 'Kota/Kabupaten toko', '2026-06-10 00:43:59', '2026-06-10 00:44:25'),
(9, 'loyalty_enable_earn', '1', 'Aktifkan perolehan poin loyalty', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(10, 'loyalty_enable_redeem', '1', 'Aktifkan redeem poin loyalty', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(11, 'loyalty_earn_rate_amount', '10000', 'Nominal belanja untuk mendapatkan 1 poin', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(12, 'loyalty_redeem_point_value', '100', 'Nilai rupiah untuk 1 poin redeem', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(13, 'loyalty_tier_regular_threshold', '0', 'Ambang total belanja tier Regular', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(14, 'loyalty_tier_silver_threshold', '500000', 'Ambang total belanja tier Silver', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(15, 'loyalty_tier_gold_threshold', '1500000', 'Ambang total belanja tier Gold', '2026-06-10 00:44:05', '2026-06-10 00:44:05'),
(16, 'loyalty_tier_platinum_threshold', '3000000', 'Ambang total belanja tier Platinum', '2026-06-10 00:44:05', '2026-06-10 00:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `stock_mutations`
--

CREATE TABLE `stock_mutations` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `mutation_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int NOT NULL,
  `stock_before` int NOT NULL,
  `stock_after` int NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_opnames`
--

CREATE TABLE `stock_opnames` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `finalized_by` bigint UNSIGNED DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_opname_items`
--

CREATE TABLE `stock_opname_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_opname_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `system_stock` int NOT NULL,
  `physical_stock` int DEFAULT NULL,
  `difference` int DEFAULT NULL,
  `adjustment_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_returns`
--

CREATE TABLE `supplier_returns` (
  `id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED DEFAULT NULL,
  `goods_receiving_id` bigint UNSIGNED DEFAULT NULL,
  `payable_id` bigint UNSIGNED DEFAULT NULL,
  `document_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `returned_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_return_items`
--

CREATE TABLE `supplier_return_items` (
  `id` bigint UNSIGNED NOT NULL,
  `supplier_return_id` bigint UNSIGNED NOT NULL,
  `goods_receiving_item_id` bigint UNSIGNED DEFAULT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `qty_returned` int NOT NULL DEFAULT '0',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `cashier_id` bigint UNSIGNED NOT NULL,
  `cashier_shift_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `bank_account_id` bigint UNSIGNED DEFAULT NULL,
  `invoice` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cash` bigint NOT NULL,
  `change` bigint NOT NULL,
  `discount` bigint NOT NULL,
  `loyalty_points_earned` int UNSIGNED NOT NULL DEFAULT '0',
  `loyalty_points_redeemed` int UNSIGNED NOT NULL DEFAULT '0',
  `loyalty_discount_total` bigint UNSIGNED NOT NULL DEFAULT '0',
  `customer_voucher_discount` bigint UNSIGNED NOT NULL DEFAULT '0',
  `customer_voucher_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_voucher_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` bigint NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_url` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` bigint UNSIGNED NOT NULL,
  `transaction_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `qty` int NOT NULL,
  `base_unit_price` bigint NOT NULL DEFAULT '0',
  `unit_price` bigint NOT NULL DEFAULT '0',
  `price` bigint NOT NULL,
  `discount_total` bigint NOT NULL DEFAULT '0',
  `pricing_rule_id` bigint UNSIGNED DEFAULT NULL,
  `pricing_rule_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing_rule_kind` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing_group_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing_group_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `avatar`) VALUES
(1, 'Admin', 'admin@gmail.com', '2026-06-10 08:21:08', '$2y$12$vPSWXf55nGe.yQ6cfKdxCu9oEJSFxdlzde2VBizqP46bVw1sBobeu', 'zmVFe6TgWquGb1KMeyMkmQ5ddXzbeXRGtGzC3Pdi0q2y3wNRfrtNGEqXqij1', '2026-06-10 00:44:06', '2026-06-10 00:44:06', NULL),
(2, 'Cashier', 'cashier@gmail.com', '2026-06-10 10:18:18', '$2y$12$id290i4/1K3LwOMWFvz0B.dGnGwT0/SbkPqUAxcnUiVM8LwDqPzse', '8aAIdU0glYNGi3ZeK8npqoTWjTo2RTdfxrugfc3TcXnBFpg2M2ujH25fq4P9', '2026-06-10 00:44:06', '2026-06-10 00:44:06', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`),
  ADD KEY `audit_logs_module_event_index` (`module`,`event`),
  ADD KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  ADD KEY `audit_logs_created_at_index` (`created_at`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carts_product_id_foreign` (`product_id`),
  ADD KEY `carts_hold_id_index` (`hold_id`),
  ADD KEY `carts_cashier_id_hold_id_index` (`cashier_id`,`hold_id`);

--
-- Indexes for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_shifts_opened_by_foreign` (`opened_by`),
  ADD KEY `cashier_shifts_closed_by_foreign` (`closed_by`),
  ADD KEY `cashier_shifts_user_id_status_index` (`user_id`,`status`),
  ADD KEY `cashier_shifts_status_opened_at_index` (`status`,`opened_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_member_code_unique` (`member_code`),
  ADD KEY `customers_is_loyalty_member_loyalty_tier_index` (`is_loyalty_member`,`loyalty_tier`);

--
-- Indexes for table `customer_campaigns`
--
ALTER TABLE `customer_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_campaigns_context_key_unique` (`context_key`),
  ADD KEY `customer_campaigns_created_by_foreign` (`created_by`),
  ADD KEY `customer_campaigns_type_status_index` (`type`,`status`);

--
-- Indexes for table `customer_campaign_logs`
--
ALTER TABLE `customer_campaign_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_campaign_logs_transaction_id_foreign` (`transaction_id`),
  ADD KEY `customer_campaign_logs_receivable_id_foreign` (`receivable_id`),
  ADD KEY `customer_campaign_logs_customer_campaign_id_status_index` (`customer_campaign_id`,`status`),
  ADD KEY `customer_campaign_logs_customer_id_status_index` (`customer_id`,`status`);

--
-- Indexes for table `customer_credits`
--
ALTER TABLE `customer_credits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_credits_customer_id_foreign` (`customer_id`),
  ADD KEY `customer_credits_sales_return_id_foreign` (`sales_return_id`);

--
-- Indexes for table `customer_segments`
--
ALTER TABLE `customer_segments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_segments_slug_unique` (`slug`),
  ADD KEY `customer_segments_type_is_active_index` (`type`,`is_active`);

--
-- Indexes for table `customer_segment_memberships`
--
ALTER TABLE `customer_segment_memberships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cust_seg_membership_unique` (`customer_id`,`customer_segment_id`),
  ADD KEY `cust_seg_membership_source_idx` (`customer_segment_id`,`source`);

--
-- Indexes for table `customer_vouchers`
--
ALTER TABLE `customer_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_vouchers_code_unique` (`code`),
  ADD KEY `customer_vouchers_used_transaction_id_foreign` (`used_transaction_id`),
  ADD KEY `customer_vouchers_created_by_foreign` (`created_by`),
  ADD KEY `customer_vouchers_customer_id_is_active_is_used_index` (`customer_id`,`is_active`,`is_used`),
  ADD KEY `customer_vouchers_code_is_active_index` (`code`,`is_active`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `goods_receivings`
--
ALTER TABLE `goods_receivings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `goods_receivings_document_number_unique` (`document_number`),
  ADD KEY `goods_receivings_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `goods_receivings_supplier_id_foreign` (`supplier_id`),
  ADD KEY `goods_receivings_received_by_foreign` (`received_by`);

--
-- Indexes for table `goods_receiving_items`
--
ALTER TABLE `goods_receiving_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `goods_receiving_items_goods_receiving_id_foreign` (`goods_receiving_id`),
  ADD KEY `goods_receiving_items_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  ADD KEY `goods_receiving_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `indonesia_cities`
--
ALTER TABLE `indonesia_cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `indonesia_cities_code_unique` (`code`),
  ADD KEY `indonesia_cities_province_code_foreign` (`province_code`);

--
-- Indexes for table `indonesia_districts`
--
ALTER TABLE `indonesia_districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `indonesia_districts_code_unique` (`code`),
  ADD KEY `indonesia_districts_city_code_foreign` (`city_code`);

--
-- Indexes for table `indonesia_provinces`
--
ALTER TABLE `indonesia_provinces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `indonesia_provinces_code_unique` (`code`);

--
-- Indexes for table `indonesia_villages`
--
ALTER TABLE `indonesia_villages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `indonesia_villages_code_unique` (`code`),
  ADD KEY `indonesia_villages_district_code_foreign` (`district_code`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loyalty_point_histories`
--
ALTER TABLE `loyalty_point_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loyalty_point_histories_transaction_id_foreign` (`transaction_id`),
  ADD KEY `loyalty_point_histories_customer_id_created_at_index` (`customer_id`,`created_at`),
  ADD KEY `loyalty_point_histories_type_created_at_index` (`type`,`created_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payables`
--
ALTER TABLE `payables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payables_supplier_id_foreign` (`supplier_id`),
  ADD KEY `payables_document_number_index` (`document_number`),
  ADD KEY `payables_purchase_order_id_foreign` (`purchase_order_id`);

--
-- Indexes for table `payable_payments`
--
ALTER TABLE `payable_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payable_payments_payable_id_foreign` (`payable_id`),
  ADD KEY `payable_payments_bank_account_id_foreign` (`bank_account_id`),
  ADD KEY `payable_payments_user_id_foreign` (`user_id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pricing_rules_product_id_foreign` (`product_id`),
  ADD KEY `pricing_rules_category_id_foreign` (`category_id`),
  ADD KEY `pricing_rules_created_by_foreign` (`created_by`),
  ADD KEY `pricing_rules_is_active_priority_index` (`is_active`,`priority`),
  ADD KEY `pricing_rules_target_type_product_id_category_id_index` (`target_type`,`product_id`,`category_id`),
  ADD KEY `pricing_rules_customer_scope_starts_at_ends_at_index` (`customer_scope`,`starts_at`,`ends_at`),
  ADD KEY `pricing_rules_kind_is_active_priority_index` (`kind`,`is_active`,`priority`);

--
-- Indexes for table `pricing_rule_bundle_items`
--
ALTER TABLE `pricing_rule_bundle_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pricing_rule_bundle_items_product_id_foreign` (`product_id`),
  ADD KEY `pricing_rule_bundle_items_pricing_rule_id_sort_order_index` (`pricing_rule_id`,`sort_order`);

--
-- Indexes for table `pricing_rule_buy_get_items`
--
ALTER TABLE `pricing_rule_buy_get_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pricing_rule_buy_get_items_product_id_foreign` (`product_id`),
  ADD KEY `pricing_rule_buy_get_items_pricing_rule_id_role_sort_order_index` (`pricing_rule_id`,`role`,`sort_order`);

--
-- Indexes for table `pricing_rule_qty_breaks`
--
ALTER TABLE `pricing_rule_qty_breaks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pricing_rule_qty_breaks_pricing_rule_id_min_qty_index` (`pricing_rule_id`,`min_qty`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_barcode_unique` (`barcode`),
  ADD UNIQUE KEY `products_sku_unique` (`sku`),
  ADD KEY `products_category_id_foreign` (`category_id`);

--
-- Indexes for table `product_notification_reads`
--
ALTER TABLE `product_notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_notification_reads_user_id_product_id_unique` (`user_id`,`product_id`),
  ADD KEY `product_notification_reads_product_id_foreign` (`product_id`);

--
-- Indexes for table `profits`
--
ALTER TABLE `profits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profits_transaction_id_foreign` (`transaction_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_document_number_unique` (`document_number`),
  ADD KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_orders_created_by_foreign` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_order_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `receivables`
--
ALTER TABLE `receivables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receivables_customer_id_foreign` (`customer_id`),
  ADD KEY `receivables_transaction_id_foreign` (`transaction_id`),
  ADD KEY `receivables_invoice_index` (`invoice`);

--
-- Indexes for table `receivable_payments`
--
ALTER TABLE `receivable_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receivable_payments_receivable_id_foreign` (`receivable_id`),
  ADD KEY `receivable_payments_bank_account_id_foreign` (`bank_account_id`),
  ADD KEY `receivable_payments_user_id_foreign` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sales_returns_code_unique` (`code`),
  ADD KEY `sales_returns_customer_id_foreign` (`customer_id`),
  ADD KEY `sales_returns_cashier_id_foreign` (`cashier_id`),
  ADD KEY `sales_returns_transaction_id_status_index` (`transaction_id`,`status`),
  ADD KEY `sales_returns_cashier_shift_id_foreign` (`cashier_shift_id`);

--
-- Indexes for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_return_items_sales_return_id_foreign` (`sales_return_id`),
  ADD KEY `sales_return_items_transaction_detail_id_foreign` (`transaction_detail_id`),
  ADD KEY `sales_return_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `stock_mutations`
--
ALTER TABLE `stock_mutations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_mutations_product_id_foreign` (`product_id`),
  ADD KEY `stock_mutations_created_by_foreign` (`created_by`),
  ADD KEY `stock_mutations_reference_type_reference_id_index` (`reference_type`,`reference_id`);

--
-- Indexes for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_opnames_code_unique` (`code`),
  ADD KEY `stock_opnames_created_by_foreign` (`created_by`),
  ADD KEY `stock_opnames_finalized_by_foreign` (`finalized_by`);

--
-- Indexes for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_opname_items_stock_opname_id_product_id_unique` (`stock_opname_id`,`product_id`),
  ADD KEY `stock_opname_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplier_returns_document_number_unique` (`document_number`),
  ADD KEY `supplier_returns_supplier_id_foreign` (`supplier_id`),
  ADD KEY `supplier_returns_goods_receiving_id_foreign` (`goods_receiving_id`),
  ADD KEY `supplier_returns_payable_id_foreign` (`payable_id`),
  ADD KEY `supplier_returns_created_by_foreign` (`created_by`);

--
-- Indexes for table `supplier_return_items`
--
ALTER TABLE `supplier_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_return_items_supplier_return_id_foreign` (`supplier_return_id`),
  ADD KEY `supplier_return_items_goods_receiving_item_id_foreign` (`goods_receiving_item_id`),
  ADD KEY `supplier_return_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transactions_cashier_id_foreign` (`cashier_id`),
  ADD KEY `transactions_customer_id_foreign` (`customer_id`),
  ADD KEY `transactions_bank_account_id_foreign` (`bank_account_id`),
  ADD KEY `transactions_cashier_shift_id_foreign` (`cashier_shift_id`);

--
-- Indexes for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_details_transaction_id_foreign` (`transaction_id`),
  ADD KEY `transaction_details_product_id_foreign` (`product_id`),
  ADD KEY `transaction_details_pricing_rule_id_foreign` (`pricing_rule_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_campaigns`
--
ALTER TABLE `customer_campaigns`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_campaign_logs`
--
ALTER TABLE `customer_campaign_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_credits`
--
ALTER TABLE `customer_credits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_segments`
--
ALTER TABLE `customer_segments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer_segment_memberships`
--
ALTER TABLE `customer_segment_memberships`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_vouchers`
--
ALTER TABLE `customer_vouchers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receivings`
--
ALTER TABLE `goods_receivings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `goods_receiving_items`
--
ALTER TABLE `goods_receiving_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `indonesia_cities`
--
ALTER TABLE `indonesia_cities`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=530;

--
-- AUTO_INCREMENT for table `indonesia_districts`
--
ALTER TABLE `indonesia_districts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `indonesia_provinces`
--
ALTER TABLE `indonesia_provinces`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `indonesia_villages`
--
ALTER TABLE `indonesia_villages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_point_histories`
--
ALTER TABLE `loyalty_point_histories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `payables`
--
ALTER TABLE `payables`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payable_payments`
--
ALTER TABLE `payable_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pricing_rule_bundle_items`
--
ALTER TABLE `pricing_rule_bundle_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pricing_rule_buy_get_items`
--
ALTER TABLE `pricing_rule_buy_get_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pricing_rule_qty_breaks`
--
ALTER TABLE `pricing_rule_qty_breaks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `product_notification_reads`
--
ALTER TABLE `product_notification_reads`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profits`
--
ALTER TABLE `profits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `receivables`
--
ALTER TABLE `receivables`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `receivable_payments`
--
ALTER TABLE `receivable_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `sales_returns`
--
ALTER TABLE `sales_returns`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `stock_mutations`
--
ALTER TABLE `stock_mutations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_return_items`
--
ALTER TABLE `supplier_return_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `carts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD CONSTRAINT `cashier_shifts_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cashier_shifts_opened_by_foreign` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cashier_shifts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_campaigns`
--
ALTER TABLE `customer_campaigns`
  ADD CONSTRAINT `customer_campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_campaign_logs`
--
ALTER TABLE `customer_campaign_logs`
  ADD CONSTRAINT `customer_campaign_logs_customer_campaign_id_foreign` FOREIGN KEY (`customer_campaign_id`) REFERENCES `customer_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_campaign_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customer_campaign_logs_receivable_id_foreign` FOREIGN KEY (`receivable_id`) REFERENCES `receivables` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customer_campaign_logs_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_credits`
--
ALTER TABLE `customer_credits`
  ADD CONSTRAINT `customer_credits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_credits_sales_return_id_foreign` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_segment_memberships`
--
ALTER TABLE `customer_segment_memberships`
  ADD CONSTRAINT `customer_segment_memberships_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_segment_memberships_customer_segment_id_foreign` FOREIGN KEY (`customer_segment_id`) REFERENCES `customer_segments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_vouchers`
--
ALTER TABLE `customer_vouchers`
  ADD CONSTRAINT `customer_vouchers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customer_vouchers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_vouchers_used_transaction_id_foreign` FOREIGN KEY (`used_transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `goods_receivings`
--
ALTER TABLE `goods_receivings`
  ADD CONSTRAINT `goods_receivings_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receivings_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `goods_receivings_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `goods_receiving_items`
--
ALTER TABLE `goods_receiving_items`
  ADD CONSTRAINT `goods_receiving_items_goods_receiving_id_foreign` FOREIGN KEY (`goods_receiving_id`) REFERENCES `goods_receivings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receiving_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `goods_receiving_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `indonesia_cities`
--
ALTER TABLE `indonesia_cities`
  ADD CONSTRAINT `indonesia_cities_province_code_foreign` FOREIGN KEY (`province_code`) REFERENCES `indonesia_provinces` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `indonesia_districts`
--
ALTER TABLE `indonesia_districts`
  ADD CONSTRAINT `indonesia_districts_city_code_foreign` FOREIGN KEY (`city_code`) REFERENCES `indonesia_cities` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `indonesia_villages`
--
ALTER TABLE `indonesia_villages`
  ADD CONSTRAINT `indonesia_villages_district_code_foreign` FOREIGN KEY (`district_code`) REFERENCES `indonesia_districts` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `loyalty_point_histories`
--
ALTER TABLE `loyalty_point_histories`
  ADD CONSTRAINT `loyalty_point_histories_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loyalty_point_histories_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payables`
--
ALTER TABLE `payables`
  ADD CONSTRAINT `payables_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payables_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payable_payments`
--
ALTER TABLE `payable_payments`
  ADD CONSTRAINT `payable_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payable_payments_payable_id_foreign` FOREIGN KEY (`payable_id`) REFERENCES `payables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payable_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD CONSTRAINT `pricing_rules_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pricing_rules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pricing_rules_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pricing_rule_bundle_items`
--
ALTER TABLE `pricing_rule_bundle_items`
  ADD CONSTRAINT `pricing_rule_bundle_items_pricing_rule_id_foreign` FOREIGN KEY (`pricing_rule_id`) REFERENCES `pricing_rules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pricing_rule_bundle_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pricing_rule_buy_get_items`
--
ALTER TABLE `pricing_rule_buy_get_items`
  ADD CONSTRAINT `pricing_rule_buy_get_items_pricing_rule_id_foreign` FOREIGN KEY (`pricing_rule_id`) REFERENCES `pricing_rules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pricing_rule_buy_get_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pricing_rule_qty_breaks`
--
ALTER TABLE `pricing_rule_qty_breaks`
  ADD CONSTRAINT `pricing_rule_qty_breaks_pricing_rule_id_foreign` FOREIGN KEY (`pricing_rule_id`) REFERENCES `pricing_rules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_notification_reads`
--
ALTER TABLE `product_notification_reads`
  ADD CONSTRAINT `product_notification_reads_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_notification_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profits`
--
ALTER TABLE `profits`
  ADD CONSTRAINT `profits_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receivables`
--
ALTER TABLE `receivables`
  ADD CONSTRAINT `receivables_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `receivables_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `receivable_payments`
--
ALTER TABLE `receivable_payments`
  ADD CONSTRAINT `receivable_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `receivable_payments_receivable_id_foreign` FOREIGN KEY (`receivable_id`) REFERENCES `receivables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receivable_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD CONSTRAINT `sales_returns_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_returns_cashier_shift_id_foreign` FOREIGN KEY (`cashier_shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_returns_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD CONSTRAINT `sales_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_return_items_sales_return_id_foreign` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_return_items_transaction_detail_id_foreign` FOREIGN KEY (`transaction_detail_id`) REFERENCES `transaction_details` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_mutations`
--
ALTER TABLE `stock_mutations`
  ADD CONSTRAINT `stock_mutations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_mutations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  ADD CONSTRAINT `stock_opnames_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_opnames_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  ADD CONSTRAINT `stock_opname_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_opname_items_stock_opname_id_foreign` FOREIGN KEY (`stock_opname_id`) REFERENCES `stock_opnames` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_returns`
--
ALTER TABLE `supplier_returns`
  ADD CONSTRAINT `supplier_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supplier_returns_goods_receiving_id_foreign` FOREIGN KEY (`goods_receiving_id`) REFERENCES `goods_receivings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_returns_payable_id_foreign` FOREIGN KEY (`payable_id`) REFERENCES `payables` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supplier_returns_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `supplier_return_items`
--
ALTER TABLE `supplier_return_items`
  ADD CONSTRAINT `supplier_return_items_goods_receiving_item_id_foreign` FOREIGN KEY (`goods_receiving_item_id`) REFERENCES `goods_receiving_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supplier_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `supplier_return_items_supplier_return_id_foreign` FOREIGN KEY (`supplier_return_id`) REFERENCES `supplier_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_cashier_shift_id_foreign` FOREIGN KEY (`cashier_shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_pricing_rule_id_foreign` FOREIGN KEY (`pricing_rule_id`) REFERENCES `pricing_rules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaction_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `transaction_details_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
