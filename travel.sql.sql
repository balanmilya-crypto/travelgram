-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2026 at 10:07 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travel_agencies`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_type` enum('tour','scholarship') NOT NULL,
  `item_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `participants` int(11) DEFAULT 1,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_number`, `user_id`, `item_type`, `item_id`, `office_id`, `booking_date`, `participants`, `total_price`, `status`, `payment_status`, `payment_method`, `special_requests`, `created_at`, `updated_at`) VALUES
(4, 'BKG-20260406-69D2DC086D9E7', 6, 'tour', 12, 3, '2026-04-06', 2, 6000.00, 'pending', 'unpaid', 'cash', 'i want to be next to the window', '2026-04-05 22:02:48', '2026-04-05 22:02:48'),
(5, 'BKG-20260406-69D2DC9FE8752', 13, 'tour', 15, 6, '2026-04-06', 3, 6000.00, 'pending', 'unpaid', 'cash', '', '2026-04-05 22:05:19', '2026-04-05 22:05:19'),
(6, 'BKG-20260406-69D2DD3546655', 12, 'tour', 9, 4, '2026-04-06', 4, 12000.00, 'confirmed', 'unpaid', 'cash', '', '2026-04-05 22:07:49', '2026-04-08 16:40:33'),
(7, 'BKG-20260407-69D5236EC310C', 6, 'tour', 14, 3, '2026-04-07', 2, 8000.00, 'confirmed', 'unpaid', 'cash', '', '2026-04-07 15:31:58', '2026-04-07 15:33:07'),
(8, 'BKG-20260408-69D67F7B46B2E', 7, 'tour', 12, 3, '2026-04-08', 8, 24000.00, 'pending', 'unpaid', 'cash', '', '2026-04-08 16:16:59', '2026-04-08 16:16:59'),
(9, 'BKG-20260411-69DA0C48DDDF3', 6, 'tour', 6, 6, '2026-04-11', 4, 8000.00, 'pending', 'unpaid', 'cash', '', '2026-04-11 08:54:32', '2026-04-11 08:54:32'),
(11, 'BKG-20260411-69DA1B8E27A8F', 7, 'tour', 6, 6, '2026-04-11', 7, 14000.00, 'pending', 'unpaid', 'cash', '', '2026-04-11 09:59:42', '2026-04-11 09:59:42'),
(12, 'BKG-20260424-69EB52DB3DBE1', 14, 'tour', 9, 4, '2026-04-24', 3, 9000.00, 'pending', 'unpaid', 'cash', '', '2026-04-24 11:24:11', '2026-04-24 11:24:11'),
(13, 'BKG-20260517-6A09F703B5924', 7, 'tour', 9, 4, '2026-05-17', 1, 3000.00, 'pending', 'unpaid', 'cash', '', '2026-05-17 17:12:35', '2026-05-17 17:12:35'),
(14, 'BKG-20260626-6A3E5D47CE6B9', 14, 'tour', 16, 4, '2026-06-26', 2, 6000.00, 'pending', 'unpaid', 'cash', '', '2026-06-26 11:06:47', '2026-06-26 11:06:47');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `country_id`, `name`) VALUES
(1, 1, 'Istanbul'),
(2, 1, 'Antalya'),
(3, 1, 'Cappadocia'),
(4, 2, 'Cairo'),
(5, 2, 'Luxor'),
(6, 2, 'Sharm El Sheikh'),
(7, 3, 'Dubai'),
(8, 3, 'Abu Dhabi'),
(9, 4, 'Rome'),
(10, 4, 'Florence'),
(11, 4, 'Venice'),
(12, 5, 'Paris'),
(13, 5, 'Nice'),
(14, 5, 'Lyon'),
(15, 6, 'Barcelona'),
(16, 6, 'Madrid'),
(17, 6, 'Seville'),
(18, 7, 'Athens'),
(19, 7, 'Santorini'),
(20, 7, 'Thessaloniki'),
(21, 8, 'Bangkok'),
(22, 8, 'Phuket'),
(23, 8, 'Chiang Mai'),
(24, 9, 'Tokyo'),
(25, 9, 'Kyoto'),
(26, 9, 'Osaka'),
(27, 10, 'New York'),
(28, 10, 'Los Angeles'),
(29, 10, 'Las Vegas'),
(30, 11, 'London'),
(31, 11, 'Edinburgh'),
(32, 11, 'Manchester'),
(33, 12, 'Berlin'),
(34, 12, 'Munich'),
(35, 12, 'Hamburg'),
(36, 13, 'Marrakech'),
(37, 13, 'Fez'),
(38, 13, 'Casablanca'),
(39, 14, 'Amman'),
(40, 14, 'Petra'),
(41, 14, 'Aqaba'),
(45, 16, 'Rio de Janeiro'),
(46, 16, 'São Paulo'),
(47, 16, 'Salvador'),
(48, 17, 'Sydney'),
(49, 17, 'Melbourne'),
(50, 17, 'Brisbane'),
(51, 18, 'Toronto'),
(52, 18, 'Vancouver'),
(53, 18, 'Montreal'),
(54, 19, 'Mexico City'),
(55, 19, 'Cancún'),
(56, 19, 'Guadalajara'),
(57, 20, 'Delhi'),
(58, 20, 'Mumbai'),
(59, 20, 'Jaipur'),
(60, 3, 'Sharjah'),
(61, 21, 'Kazan'),
(62, 21, 'Saint Petersburg'),
(63, 21, 'Moscow'),
(64, 22, 'Chengdu'),
(65, 22, 'Shanghai'),
(66, 22, 'Beijing'),
(67, 23, 'Yogyakarta'),
(68, 23, 'Bali'),
(69, 23, 'Jakarta'),
(70, 24, 'Da Nang'),
(71, 24, 'Ho Chi Minh City'),
(72, 24, 'Hanoi'),
(73, 25, 'Jeju'),
(74, 25, 'Busan'),
(75, 25, 'Seoul'),
(76, 26, 'Faro'),
(77, 26, 'Porto'),
(78, 26, 'Lisbon'),
(79, 27, 'The Hague'),
(80, 27, 'Rotterdam'),
(81, 27, 'Amsterdam'),
(82, 28, 'Interlaken'),
(83, 28, 'Geneva'),
(84, 28, 'Zurich'),
(85, 29, 'Malmö'),
(86, 29, 'Gothenburg'),
(87, 29, 'Stockholm'),
(88, 30, 'Tromsø'),
(89, 30, 'Bergen'),
(90, 30, 'Oslo'),
(91, 31, 'Latakia'),
(92, 31, 'Aleppo'),
(93, 31, 'Damascus'),
(94, 32, 'Valencia'),
(95, 32, 'Maracaibo'),
(96, 32, 'Caracas'),
(124, 33, 'Buenos Aires'),
(125, 33, 'Cordoba'),
(126, 33, 'Mendoza');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `content`, `parent_id`, `created_at`, `updated_at`) VALUES
(9, 6, 'الرحلة واووو', NULL, '2026-03-29 10:23:18', '2026-03-29 10:23:18'),
(10, 6, 'mmmmmmmmmm', 9, '2026-03-29 11:16:38', '2026-03-29 11:16:38'),
(12, 6, 'ةةة', 10, '2026-03-29 11:54:57', '2026-03-29 11:54:57'),
(13, 6, 'ةةةةة', 9, '2026-03-31 10:08:44', '2026-03-31 10:08:44'),
(15, 6, 'nicee', NULL, '2026-03-31 15:34:18', '2026-03-31 15:34:18'),
(16, 6, 'lovee', NULL, '2026-04-01 10:39:05', '2026-04-01 10:39:05'),
(17, 7, 'واووو', NULL, '2026-04-04 16:55:57', '2026-04-04 16:55:57'),
(18, 7, 'beautiful tour', NULL, '2026-04-05 10:50:11', '2026-04-05 10:50:11'),
(20, 12, 'woooow', NULL, '2026-04-05 21:37:48', '2026-04-05 21:37:48'),
(21, 12, 'soo beautiful', NULL, '2026-04-05 21:38:17', '2026-04-05 21:38:17'),
(22, 6, 'cutee', NULL, '2026-04-05 21:39:21', '2026-04-05 21:39:21'),
(23, 13, 'broo', NULL, '2026-04-05 21:42:22', '2026-04-05 21:42:22'),
(24, 6, 'i\'m excitedd', NULL, '2026-04-05 22:04:05', '2026-04-05 22:04:05'),
(25, 13, 'and me', 24, '2026-04-05 22:04:54', '2026-04-05 22:04:54'),
(26, 12, 'nicee', NULL, '2026-04-05 22:07:29', '2026-04-05 22:07:29'),
(27, 6, 'انا كثير متحمسس', NULL, '2026-04-07 15:31:46', '2026-04-07 15:31:46'),
(28, 7, 'حلوو', NULL, '2026-04-07 16:19:01', '2026-04-07 16:19:01'),
(29, 7, 'واوو', 28, '2026-04-07 16:19:11', '2026-04-07 16:19:11'),
(30, 7, 'i miss you', NULL, '2026-04-07 16:20:29', '2026-04-07 16:20:29'),
(31, 13, 'and me', 30, '2026-04-07 16:21:09', '2026-04-07 16:21:09'),
(33, 13, 'i like this place', NULL, '2026-04-08 16:10:08', '2026-04-08 16:10:08'),
(34, 7, 'Wooow', NULL, '2026-04-08 16:15:57', '2026-04-08 16:15:57'),
(35, 7, 'thanks', 20, '2026-04-08 16:22:27', '2026-04-08 16:22:27'),
(36, 6, 'njbhvhv', NULL, '2026-04-11 08:52:35', '2026-04-11 08:52:35'),
(38, 6, 'klmklnkn', NULL, '2026-04-11 08:59:51', '2026-04-11 08:59:51'),
(39, 6, 'kmkn', 31, '2026-04-11 08:59:57', '2026-04-11 08:59:57'),
(40, 12, 'نفسي زور الاهرامات', NULL, '2026-04-15 11:49:02', '2026-04-15 11:49:02'),
(41, 12, 'واوو', NULL, '2026-04-15 18:20:36', '2026-04-15 18:20:36'),
(42, 7, 'في انتظار الرحلة', NULL, '2026-04-22 14:14:47', '2026-04-22 14:14:47'),
(43, 7, 'wooow', NULL, '2026-04-23 17:38:43', '2026-04-23 17:38:43'),
(46, 7, 'jjgkk', NULL, '2026-05-17 17:11:21', '2026-05-17 17:11:21');

-- --------------------------------------------------------

--
-- Table structure for table `comment_reactions`
--

CREATE TABLE `comment_reactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `reaction_type_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comment_reactions`
--

INSERT INTO `comment_reactions` (`id`, `user_id`, `comment_id`, `reaction_type_id`, `created_at`) VALUES
(8, 6, 9, 2, '2026-03-29 10:23:44'),
(10, 7, 18, 2, '2026-04-05 10:50:14'),
(11, 13, 24, 2, '2026-04-05 22:04:37'),
(12, 8, 24, 2, '2026-04-05 22:08:30'),
(13, 8, 25, 2, '2026-04-05 22:08:32'),
(14, 7, 28, 2, '2026-04-07 16:19:15'),
(15, 7, 29, 2, '2026-04-07 16:19:18'),
(17, 12, 34, 2, '2026-04-10 14:53:28'),
(18, 6, 34, 2, '2026-04-11 08:52:39'),
(24, 12, 40, 2, '2026-04-15 11:49:06'),
(25, 9, 26, 2, '2026-04-17 14:41:35'),
(26, 7, 40, 6, '2026-04-22 14:22:48'),
(28, 7, 42, 2, '2026-04-22 14:29:06'),
(32, 7, 34, 3, '2026-04-22 14:40:01'),
(34, 7, 36, 6, '2026-04-22 14:40:52'),
(36, 8, 27, 2, '2026-04-22 14:48:12'),
(37, 7, 26, 2, '2026-04-22 17:23:01'),
(38, 14, 26, 3, '2026-04-24 11:23:45'),
(39, 14, 36, 2, '2026-06-26 11:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `flag` varchar(255) DEFAULT NULL,
  `images` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`, `flag`, `images`) VALUES
(1, 'Turkey', 'turkey.jpg', 't.jpg'),
(2, 'Egypt', 'egypt.jpg', 'e.jpg'),
(3, 'United Arab Emirates', 'uae.jpg', 'uae.jpg'),
(4, 'Italy', 'italy.jpg', 'i.jpg'),
(5, 'France', 'france.jpg', 'f.jpg'),
(6, 'Spain', 'spain.jpg', 'sp.jpg'),
(7, 'Greece', 'greece.jpg', 'gr.jpg'),
(8, 'Thailand', 'thailand.jpg', 'th.jpg'),
(9, 'Japan', 'japan.jpg', 'j.jpg'),
(10, 'USA', 'usa.jpg', 'us.jpg'),
(11, 'United Kingdom', 'uk.jpg', 'uk.jpg'),
(12, 'Germany', 'germany.jpg', 'ge.jpg'),
(13, 'Morocco', 'morocco.jpg', 'mo.jpg'),
(14, 'Jordan', 'jordan.jpg', 'jo.jpg'),
(16, 'Brazil', 'brazil.jpg', 'b.jpg'),
(17, 'Australia', 'australia.jpg', 'au.jpg'),
(18, 'Canada', 'canada.jpg', 'c.jpg'),
(19, 'Mexico', 'mexico.jpg', 'me.jpg'),
(20, 'India', 'india.jpg', 'in.jpg'),
(21, 'Russia', 'russia.jpg', 'ru.jpg'),
(22, 'China', 'china.jpg', 'ch.jpg'),
(23, 'Indonesia', 'indonesia.jpg', 'id.jpg'),
(24, 'Vietnam', 'vietnam.jpg', 'vi.jpg'),
(25, 'South Korea', 'south_korea.jpg', 'sk.jpg'),
(26, 'Portugal', 'portugal.jpg', 'po.jpg'),
(27, 'Netherlands', 'netherlands.jpg', 'ne.jpg'),
(28, 'Switzerland', 'switzerland.jpg', 'sw.jpg'),
(29, 'Sweden', 'sweden.jpg', 'swe.jpg'),
(30, 'Norway', 'norway.jpg', 'no.jpg'),
(31, 'Syria', 'syria.jpg', 'sy.jpg'),
(32, 'Venezuela', 'venezuela.jpg', 've.jpg'),
(33, 'Argentina', 'argentina.jpg', 'ar.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_conversations`
--

CREATE TABLE `deleted_conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `other_user_id` int(11) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL COMMENT 'المستخدم الذي يتابع',
  `followable_type` enum('user','office') NOT NULL COMMENT 'نوع الجهة المُتابَعَة (user أو office)',
  `followable_id` int(11) NOT NULL COMMENT 'معرف الجهة المُتابَعَة (id من جدول users أو offices)',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`id`, `follower_id`, `followable_type`, `followable_id`, `created_at`) VALUES
(2, 12, 'user', 7, '2026-04-06 00:38:24'),
(3, 12, 'user', 6, '2026-04-06 00:38:27'),
(4, 6, 'user', 12, '2026-04-06 00:38:59'),
(6, 13, 'user', 12, '2026-04-06 00:42:00'),
(7, 13, 'user', 7, '2026-04-06 00:42:04'),
(8, 13, 'user', 6, '2026-04-06 00:42:06'),
(10, 7, 'user', 12, '2026-04-06 00:42:57'),
(11, 7, 'user', 6, '2026-04-06 00:43:11'),
(12, 6, 'user', 13, '2026-04-07 18:48:05'),
(13, 7, 'user', 13, '2026-04-08 19:23:05'),
(14, 6, 'user', 7, '2026-04-11 12:00:19'),
(15, 7, 'user', 0, '2026-04-15 13:46:04'),
(17, 7, 'office', 3, '2026-04-15 13:48:44'),
(18, 12, 'office', 6, '2026-04-15 14:43:37'),
(19, 14, 'user', 13, '2026-04-24 21:22:56'),
(20, 13, 'user', 14, '2026-04-24 21:23:49');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `stars` tinyint(4) DEFAULT 3 CHECK (`stars` between 1 and 5),
  `country_id` int(11) NOT NULL,
  `city_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `name`, `description`, `stars`, `country_id`, `city_id`) VALUES
(1, 'Çırağan Palace Kempinski', 'Luxury Ottoman palace hotel on Bosphorus', 5, 1, 1),
(2, 'Rixos Downtown Antalya', 'Modern hotel with sea views', 5, 1, 2),
(3, 'Museum Hotel', 'Cave hotel with panoramic views', 5, 1, 3),
(4, 'The Nile Ritz-Carlton', 'Overlooking the Nile', 5, 2, 4),
(5, 'Sofitel Winter Palace', 'Historic hotel in Luxor', 5, 2, 5),
(6, 'Four Seasons Sharm', 'Beachfront resort', 5, 2, 6),
(7, 'Atlantis The Palm', 'Iconic resort on Palm Jumeirah', 5, 3, 7),
(8, 'The St. Regis Saadiyat', 'Beachfront luxury', 5, 3, 8),
(9, 'Hotel de Russie', 'Elegant hotel near Piazza del Popolo', 5, 4, 9),
(10, 'Four Seasons Florence', 'Renaissance palace', 5, 4, 10),
(11, 'The Gritti Palace', 'Venetian palazzo on Grand Canal', 5, 4, 11),
(12, 'Le Meurice', 'Palatial hotel near Louvre', 5, 5, 12),
(13, 'Hotel Negresco', 'Iconic belle époque hotel', 5, 5, 13),
(14, 'Villa Florentine', 'Luxury hotel with panoramic views', 5, 5, 14),
(15, 'Hotel Arts Barcelona', 'Modern high-rise with sea views', 5, 6, 15),
(16, 'Four Seasons Madrid', 'Central luxury', 5, 6, 16),
(17, 'Hotel Alfonso XIII', 'Historic hotel in Seville', 5, 6, 17),
(18, 'Grande Bretagne', 'Classic luxury in Athens', 5, 7, 18),
(19, 'Katikies Hotel', 'Iconic white-washed Santorini hotel', 5, 7, 19),
(20, 'Makedonia Palace', 'Seafront hotel in Thessaloniki', 5, 7, 20),
(21, 'Mandarin Oriental Bangkok', 'Legendary riverside hotel', 5, 8, 21),
(22, 'Rosewood Phuket', 'Luxury beach resort', 5, 8, 22),
(23, '137 Pillars House', 'Boutique colonial-style', 5, 8, 23),
(24, 'The Ritz-Carlton Tokyo', 'High-end luxury in Roppongi', 5, 9, 24),
(25, 'Hiiragiya Ryokan', 'Traditional ryokan in Kyoto', 5, 9, 25),
(26, 'St. Regis Osaka', 'Luxury hotel in Namba', 5, 9, 26),
(27, 'The Plaza', 'Iconic New York hotel', 5, 10, 27),
(28, 'The Beverly Hills Hotel', 'Pink palace in LA', 5, 10, 28),
(29, 'Wynn Las Vegas', 'Luxury resort on the Strip', 5, 10, 29),
(30, 'The Ritz London', 'Grand hotel in Piccadilly', 5, 11, 30),
(31, 'The Balmoral', 'Edinburgh landmark', 5, 11, 31),
(32, 'The Lowry Hotel', 'Modern luxury in Manchester', 5, 11, 32),
(33, 'Hotel Adlon Kempinski', 'Famous Berlin hotel', 5, 12, 33),
(34, 'Bayerischer Hof', 'Luxury hotel in Munich', 5, 12, 34),
(35, 'Fairmont Hotel Vier Jahreszeiten', 'Historic Hamburg hotel', 5, 12, 35),
(36, 'La Mamounia', 'Legendary Marrakech hotel', 5, 13, 36),
(37, 'Palais Faraj', 'Palace hotel in Fez', 5, 13, 37),
(38, 'Four Seasons Casablanca', 'Beachfront luxury', 5, 13, 38),
(39, 'Four Seasons Amman', 'Central luxury', 5, 14, 39),
(40, 'Mövenpick Resort Petra', 'Near Petra entrance', 5, 14, 40),
(41, 'Mövenpick Resort Aqaba', 'Red Sea resort', 5, 14, 41),
(45, 'Belmond Copacabana Palace', 'Iconic Rio hotel', 5, 16, 45),
(46, 'Hotel Unique', 'Boutique design in São Paulo', 5, 16, 46),
(47, 'Fera Palace Hotel', 'Historic hotel in Salvador', 5, 16, 47),
(48, 'Park Hyatt Sydney', 'Opera House views', 5, 17, 48),
(49, 'Crown Towers Melbourne', 'Luxury casino hotel', 5, 17, 49),
(50, 'The Calile', 'Boutique Brisbane', 5, 17, 50),
(51, 'Fairmont Royal York', 'Historic Toronto hotel', 5, 18, 51),
(52, 'Fairmont Pacific Rim', 'Modern Vancouver luxury', 5, 18, 52),
(53, 'Le Mount Stephen', 'Boutique in Montreal', 5, 18, 53),
(54, 'Four Seasons Mexico City', 'Central luxury', 5, 19, 54),
(55, 'Nizuc Resort & Spa', 'Luxury Cancun resort', 5, 19, 55),
(56, 'Hotel Guadalajara', 'Historic center hotel', 5, 19, 56),
(57, 'The Oberoi New Delhi', 'Luxury in Delhi', 5, 20, 57),
(58, 'The Taj Mahal Palace', 'Iconic Mumbai hotel', 5, 20, 58),
(59, 'Rambagh Palace', 'Jaipur royal palace', 5, 20, 59),
(60, 'Four Seasons Bosphorus', 'Luxury hotel on European side', 5, 1, 1),
(61, 'Sultanahmet Palace', 'Historic hotel near Blue Mosque', 4, 1, 1),
(62, 'Maxx Royal Belek', 'Ultra all-inclusive resort', 5, 1, 2),
(63, 'Akra Antalya', 'Modern sea-view hotel', 5, 1, 2),
(64, 'Argos in Cappadocia', 'Luxury cave hotel', 5, 1, 3),
(65, 'Kayakapi Premium Caves', 'Historic cave suites', 5, 1, 3),
(66, 'Marriott Mena House', 'Pyramids view hotel', 5, 2, 4),
(67, 'Kempinski Nile Hotel', 'Modern luxury in Garden City', 5, 2, 4),
(68, 'Al Moudira Hotel', 'Palace-style in Luxor', 5, 2, 5),
(69, 'Hilton Luxor Resort', 'Nile front resort', 5, 2, 5),
(70, 'Rixos Sharm El Sheikh', 'Premium all-inclusive', 5, 2, 6),
(71, 'Baron Resort', 'Luxury beachfront', 5, 2, 6),
(72, 'Burj Al Arab', 'Iconic sail-shaped hotel', 5, 3, 7),
(73, 'Armani Hotel Dubai', 'Designer luxury in Burj Khalifa', 5, 3, 7),
(74, 'Emirates Palace', 'Luxury palace hotel', 5, 3, 8),
(75, 'Conrad Abu Dhabi', 'Modern luxury', 5, 3, 8),
(76, 'Palazzo Manfredi', 'Colosseum view hotel', 5, 4, 9),
(77, 'Hassler Roma', 'Luxury atop Spanish Steps', 5, 4, 9),
(78, 'St. Regis Florence', 'Florence palazzo hotel', 5, 4, 10),
(79, 'Villa Cora', '19th century villa', 5, 4, 10),
(80, 'Bauer Palazzo', 'Venice grand canal hotel', 5, 4, 11),
(81, 'Ca\' Sagredo', 'Historic palazzo hotel', 5, 4, 11),
(82, 'Four Seasons George V', 'Palatial hotel in Paris', 5, 5, 12),
(83, 'Ritz Paris', 'Legendary Place Vendôme', 5, 5, 12),
(84, 'Hotel Le Negresco', 'Iconic Nice hotel', 5, 5, 13),
(85, 'Hyatt Regency Nice', 'Sea front modern', 5, 5, 13),
(86, 'InterContinental Lyon', 'Historic hotel on Rhône', 5, 5, 14),
(87, 'Sofitel Lyon Bellecour', 'Central luxury', 5, 5, 14),
(88, 'Majestic Hotel & Spa', 'Barcelona luxury', 5, 6, 15),
(89, 'W Barcelona', 'Beachfront sail-shaped', 5, 6, 15),
(90, 'Rosewood Villa Magna', 'Madrid exclusive', 5, 6, 16),
(91, 'Gran Meliá Palacio de los Duques', 'Royal palace hotel', 5, 6, 16),
(92, 'Hotel Unuk', 'Boutique in Seville', 5, 6, 17),
(93, 'Bécquer Hotel', 'Modern central Seville', 4, 6, 17),
(94, 'Hotel Grande Bretagne', 'Syntagma square luxury', 5, 7, 18),
(95, 'Electra Metropolis', 'Rooftop Acropolis view', 5, 7, 18),
(96, 'Canaves Oia', 'Santorini luxury suites', 5, 7, 19),
(97, 'Grace Hotel Santorini', 'Sunset view hotel', 5, 7, 19),
(98, 'Daios Cove', 'Thessaloniki resort', 5, 7, 20),
(99, 'The Met Hotel', 'Modern design hotel', 5, 7, 20),
(100, 'Siam Kempinski', 'Luxury in Bangkok', 5, 8, 21),
(101, 'The Peninsula Bangkok', 'Riverside classic', 5, 8, 21),
(102, 'Anantara Layan', 'Phuket beach resort', 5, 8, 22),
(103, 'Keemala', 'Unique rainforest villas', 5, 8, 22),
(104, 'Four Seasons Chiang Mai', 'Resort luxury', 5, 8, 23),
(105, 'Rachamankha', 'Boutique Chiang Mai', 5, 8, 23),
(106, 'Aman Tokyo', 'Ultra-luxury in Otemachi', 5, 9, 24),
(107, 'Palace Hotel Tokyo', 'Imperial palace view', 5, 9, 24),
(108, 'The Ritz-Carlton Kyoto', 'Luxury in Higashiyama', 5, 9, 25),
(109, 'Four Seasons Kyoto', 'Temple garden hotel', 5, 9, 25),
(110, 'Conrad Osaka', 'High-rise luxury', 5, 9, 26),
(111, 'Hotel Nikko Osaka', 'Central modern', 4, 9, 26),
(112, 'The Ritz-Carlton Central Park', 'NYC luxury', 5, 10, 27),
(113, 'The Peninsula New York', 'Fifth Avenue elegance', 5, 10, 27),
(114, 'Chateau Marmont', 'Legendary LA hotel', 5, 10, 28),
(115, 'Shutters on the Beach', 'Santa Monica beachfront', 5, 10, 28),
(116, 'Bellagio Las Vegas', 'Iconic fountain resort', 5, 10, 29),
(117, 'The Venetian Resort', 'All-suite luxury', 5, 10, 29),
(118, 'Claridge\'s', 'Art Deco luxury London', 5, 11, 30),
(119, 'The Savoy', 'River Thames iconic', 5, 11, 30),
(120, 'The Gleneagles Townhouse', 'Edinburgh chic', 5, 11, 31),
(121, 'Waldorf Astoria Edinburgh', 'The Caledonian luxury', 5, 11, 31),
(122, 'The Edwardian Manchester', 'Modern luxury', 5, 11, 32),
(123, 'Hotel Gotham', 'Boutique style Manchester', 5, 11, 32),
(124, 'Hotel de Rome', 'Berlin luxury', 5, 12, 33),
(125, 'The Ritz-Carlton Berlin', 'Potsdamer Platz', 5, 12, 33),
(126, 'Mandarin Oriental Munich', 'Central luxury', 5, 12, 34),
(127, 'Hotel Vier Jahreszeiten', 'Lake view in Munich', 5, 12, 34),
(128, 'The Fontenay', 'Modern luxury Hamburg', 5, 12, 35),
(129, 'Hotel Atlantic', 'Historic Hamburg hotel', 5, 12, 35),
(130, 'Royal Mansour Marrakech', 'Palace hotel', 5, 13, 36),
(131, 'Four Seasons Marrakech', 'Luxury garden resort', 5, 13, 36),
(132, 'Palais Amani', 'Fez riad luxury', 5, 13, 37),
(133, 'Riad Fes', 'Traditional palace hotel', 5, 13, 37),
(134, 'Banyan Tree Tamouda Bay', 'Casablanca beach resort', 5, 13, 38),
(135, 'Hyatt Regency Casablanca', 'Central modern luxury', 5, 13, 38),
(136, 'St. Regis Amman', 'Luxury in Al Qasr', 5, 14, 39),
(137, 'W Amman', 'Modern luxury hotel', 5, 14, 39),
(138, 'Petra Marriott', 'Valley view hotel', 5, 14, 40),
(139, 'Hilton Petra', 'Near historic site', 5, 14, 40),
(140, 'InterContinental Aqaba', 'Red Sea resort', 5, 14, 41),
(141, 'Kempinski Hotel Aqaba', 'Beachfront luxury', 5, 14, 41),
(142, 'Fairmont Rio', 'Copacabana beachfront', 5, 16, 45),
(143, 'Santa Teresa Hotel', 'Boutique in Rio', 5, 16, 45),
(144, 'Hotel Fasano São Paulo', 'Italian luxury', 5, 16, 46),
(145, 'Tivoli Mofarrej', 'Jardins neighborhood', 5, 16, 46),
(146, 'Zank by Toque', 'Boutique Salvador', 5, 16, 47),
(147, 'Vila Galé Salvador', 'Beach resort', 5, 16, 47),
(148, 'Shangri-La Sydney', 'Opera house views', 5, 17, 48),
(149, 'Four Seasons Sydney', 'Circular Quay luxury', 5, 17, 48),
(150, 'Crown Towers Melbourne', 'Casino luxury', 5, 17, 49),
(151, 'The Langham Melbourne', 'Southbank elegance', 5, 17, 49),
(152, 'The Emporium Hotel', 'Brisbane rooftop pool', 5, 17, 50),
(153, 'W Brisbane', 'Modern riverfront', 5, 17, 50),
(154, 'The St. Regis Toronto', 'Downtown luxury', 5, 18, 51),
(155, 'Four Seasons Toronto', 'Yorkville elegance', 5, 18, 51),
(156, 'Rosewood Hotel Georgia', 'Vancouver historic', 5, 18, 52),
(157, 'Fairmont Waterfront', 'Cruise port views', 5, 18, 52),
(158, 'Hotel Nelligan', 'Old Montreal boutique', 5, 18, 53),
(159, 'Four Seasons Montreal', 'Golden Square Mile', 5, 18, 53),
(160, 'Four Seasons Mexico City', 'Reforma luxury', 5, 19, 54),
(161, 'St. Regis Mexico City', 'Paseo de la Reforma', 5, 19, 54),
(162, 'Le Blanc Spa Resort', 'Adults only Cancun', 5, 19, 55),
(163, 'Grand Fiesta Americana Coral Beach', 'Cancun beachfront', 5, 19, 55),
(164, 'Hotel Demetria', 'Boutique Guadalajara', 5, 19, 56),
(165, 'Hyatt Regency Andares', 'Modern luxury Guadalajara', 5, 19, 56),
(166, 'The Leela Palace', 'Delhi luxury', 5, 20, 57),
(167, 'ITC Maurya', 'Diplomatic enclave', 5, 20, 57),
(168, 'The Oberoi Mumbai', 'Ocean drive luxury', 5, 20, 58),
(169, 'Four Seasons Mumbai', 'Worli sea view', 5, 20, 58),
(170, 'Jai Mahal Palace', 'Jaipur heritage', 5, 20, 59),
(171, 'Fairmont Jaipur', 'Pink city luxury', 5, 20, 59),
(172, 'Sheraton Sharjah Beach Resort', 'Beachfront resort with pool', 5, 3, 60),
(173, 'Coral Beach Resort Sharjah', 'Family-friendly beach hotel', 4, 3, 60),
(174, 'Al Bustan Hotel', 'Business and leisure hotel', 4, 3, 60),
(175, 'Ibis Kazan', 'Budget modern hotel', 4, 21, 61),
(176, 'Kazan Palace by Tasigo', 'Upscale Tatar hospitality', 5, 21, 61),
(177, 'Ramada Kazan City Centre', 'Comfortable business hotel', 4, 21, 61),
(178, 'Sofitel St. Petersburg', 'French elegance near St. Isaac', 5, 21, 62),
(179, 'Four Seasons Lion Palace', 'Royal residence turned hotel', 5, 21, 62),
(180, 'Belmond Grand Hotel Europe', 'Historic 5-star hotel', 5, 21, 62),
(181, 'Lotte Hotel Moscow', 'Modern luxury with spa', 5, 21, 63),
(182, 'Ararat Park Hyatt', 'Elegant hotel close to Bolshoi', 5, 21, 63),
(183, 'Four Seasons Moscow', 'Luxury near Red Square', 5, 21, 63),
(184, 'Holiday Inn Chengdu', 'Reliable mid-range', 4, 22, 64),
(185, 'Niccolo Chengdu', 'Modern luxury', 5, 22, 64),
(186, 'Temple House', 'Designer heritage hotel', 5, 22, 64),
(187, 'Jin Jiang Tower', 'Business hotel', 4, 22, 65),
(188, 'Waldorf Astoria Shanghai', 'Historic riverside hotel', 5, 22, 65),
(189, 'The Peninsula Shanghai', 'Art deco luxury on Bund', 5, 22, 65),
(190, 'Novotel Peace Beijing', 'Central business hotel', 4, 22, 66),
(191, 'Rosewood Beijing', 'Contemporary elegance', 5, 22, 66),
(192, 'Mandarin Oriental Wangfujing', 'Luxury overlooking Forbidden City', 5, 22, 66),
(193, 'Melia Purosani', 'Central hotel with garden', 4, 23, 67),
(194, 'Hyatt Regency Yogyakarta', 'Resort near Borobudur', 5, 23, 67),
(195, 'Phoenix Hotel Yogyakarta', 'Colonial style', 5, 23, 67),
(196, 'Grand Hyatt Bali', 'Resort with gardens', 5, 23, 68),
(197, 'The Legian Seminyak', 'Beachfront suites', 5, 23, 68),
(198, 'Four Seasons Resort Bali at Jimbaran Bay', 'Beachfront luxury', 5, 23, 68),
(199, 'Ibis Jakarta Tamarin', 'Budget central', 3, 23, 69),
(200, 'The Ritz-Carlton Jakarta', 'Luxury in Pacific Place', 5, 23, 69),
(201, 'Hotel Indonesia Kempinski', 'Iconic 5-star', 5, 23, 69),
(202, 'Danang Golden Bay', 'Rooftop infinity pool', 4, 24, 70),
(203, 'Hyatt Regency Danang', 'Beachfront resort', 5, 24, 70),
(204, 'InterContinental Danang Sun Peninsula', 'Resort on private bay', 5, 24, 70),
(205, 'Liberty Central Saigon', 'Mid-range central', 4, 24, 71),
(206, 'Reverie Saigon', 'Italian-designed luxury', 5, 24, 71),
(207, 'Park Hyatt Saigon', 'Luxury near Opera House', 5, 24, 71),
(208, 'Hanoi La Siesta Hotel', 'Boutique old quarter', 4, 24, 72),
(209, 'JW Marriott Hotel Hanoi', 'Modern business luxury', 5, 24, 72),
(210, 'Sofitel Legend Metropole Hanoi', 'Historic luxury', 5, 24, 72),
(211, 'Maison Glad Jeju', 'Convenient mid-range', 4, 25, 73),
(212, 'Lotte Jeju Resort', 'Family-friendly', 5, 25, 73),
(213, 'Jeju Shinhwa World Marriott', 'Resort complex', 5, 25, 73),
(214, 'Haeundae Grand Hotel', 'Mid-range beach', 4, 25, 74),
(215, 'Westin Josun Busan', 'Beachfront classic', 5, 25, 74),
(216, 'Park Hyatt Busan', 'Sea views from Haeundae', 5, 25, 74),
(217, 'Nine Tree Premier Myeongdong', 'Modern comfort', 4, 25, 75),
(218, 'Lotte Hotel Seoul', 'Central 5-star', 5, 25, 75),
(219, 'The Shilla Seoul', 'Luxury with museum', 5, 25, 75),
(220, 'Hotel Faro', 'Central budget', 3, 26, 76),
(221, 'Eva Senses', 'Harbour view', 4, 26, 76),
(222, 'Pousada Palácio de Estoi', 'Palace hotel', 5, 26, 76),
(223, 'HF Ipanema Park', 'Modern comfort', 4, 26, 77),
(224, 'Pestana Vintage Porto', 'Riverside historic', 5, 26, 77),
(225, 'The Yeatman', 'Wine spa luxury', 5, 26, 77),
(226, 'Tivoli Avenida Liberdade', 'Classic central hotel', 5, 26, 78),
(227, 'Memmo Alfama', 'Boutique in old town', 5, 26, 78),
(228, 'Four Seasons Hotel Ritz Lisbon', 'Luxury with park view', 5, 26, 78),
(229, 'Novotel Den Haag', 'Family friendly', 4, 27, 79),
(230, 'Crowne Plaza The Hague', 'Business comfort', 4, 27, 79),
(231, 'Hotel Des Indes', 'Historic 5-star', 5, 27, 79),
(232, 'CitizenM Rotterdam', 'Budget design', 3, 27, 80),
(233, 'nhow Rotterdam', 'Modern art hotel', 4, 27, 80),
(234, 'Mainport Hotel', 'Design luxury on river', 5, 27, 80),
(235, 'Hotel Okura Amsterdam', 'Japanese luxury', 5, 27, 81),
(236, 'Conservatorium Hotel', 'Music conservatory turned hotel', 5, 27, 81),
(237, 'Waldorf Astoria Amsterdam', 'Canal palace luxury', 5, 27, 81),
(238, 'Youth Hostel Interlaken', 'Budget mountain view', 2, 28, 82),
(239, 'Hotel Interlaken', 'Historic charm', 4, 28, 82),
(240, 'Victoria-Jungfrau Grand Hotel', 'Spa resort with mountain views', 5, 28, 82),
(241, 'Hotel Cornavin', 'Near station', 4, 28, 83),
(242, 'Hotel President Wilson', 'Modern luxury', 5, 28, 83),
(243, 'Four Seasons Hotel des Bergues', 'Lake Geneva luxury', 5, 28, 83),
(244, 'Hotel St. Gotthard', 'Central historic', 4, 28, 84),
(245, 'The Dolder Grand', 'Castle-like spa resort', 5, 28, 84),
(246, 'Baur au Lac', 'Legendary lakefront', 5, 28, 84),
(247, 'Comfort Hotel Malmö', 'Budget central', 3, 29, 85),
(248, 'Malmö Live', 'Modern conference hotel', 4, 29, 85),
(249, 'Story Hotel Malmö', 'Design boutique', 4, 29, 85),
(250, 'Clarion Hotel Post', 'Converted post office', 4, 29, 86),
(251, 'Elite Park Avenue', 'Art deco elegance', 4, 29, 86),
(252, 'Upper House', 'Skyscraper luxury', 5, 29, 86),
(253, 'Scandic Continental', 'Modern central', 4, 29, 87),
(254, 'Hotel Skeppsholmen', 'Island boutique', 5, 29, 87),
(255, 'Grand Hôtel Stockholm', 'Old world luxury', 5, 29, 87),
(256, 'Enter Amalie Hotel', 'Budget comfort', 3, 30, 88),
(257, 'Clarion Collection Hotel Aurora', 'Northern lights theme', 4, 30, 88),
(258, 'Radisson Blu Hotel Tromsø', 'Arctic sea views', 4, 30, 88),
(259, 'Scandic Ørnen', 'Modern near station', 4, 30, 89),
(260, 'Clarion Hotel Admiral', 'Waterfront historic', 4, 30, 89),
(261, 'Opus XVI', 'Edvard Grieg heritage', 5, 30, 89),
(262, 'Radisson Blu Plaza', 'Tallest hotel in Oslo', 4, 30, 90),
(263, 'Grand Hotel Oslo', 'Historic landmark', 5, 30, 90),
(264, 'The Thief', 'Design hotel on Tjuvholmen', 5, 30, 90),
(265, 'Al Sharq Hotel', 'Budget option', 3, 31, 91),
(266, 'Afamia Hotel', 'Sea view', 4, 31, 91),
(267, 'Latakia Rotana Beach Resort', 'Beachfront resort', 5, 31, 91),
(268, 'Dar Zamaria', 'Traditional courtyard', 3, 31, 92),
(269, 'Aleppo Palace Hotel', 'Central comfort', 4, 31, 92),
(270, 'Sheraton Aleppo', 'International standard', 4, 31, 92),
(271, 'Beit al Mamlouka', 'Boutique heritage', 4, 31, 93),
(272, 'Dama Rose Hotel', 'Modern upscale', 4, 31, 93),
(273, 'Four Seasons Damascus', 'Luxury in central', 5, 31, 93),
(274, 'Hotel El Rodeo', 'Budget option', 3, 32, 94),
(275, 'Hotel Nicolas', 'Mid-range central', 4, 32, 94),
(276, 'Hesperia Valencia', 'Modern business', 4, 32, 94),
(277, 'Hotel Paseo', 'Central budget', 3, 32, 95),
(278, 'Hotel Del Lago', 'Lakefront comfort', 4, 32, 95),
(279, 'Kristoff Hotel', 'Upscale lake view', 4, 32, 95),
(280, 'Eurobuilding Express', 'Business hotel', 4, 32, 96),
(281, 'JW Marriott Caracas', 'Modern luxury', 5, 32, 96),
(282, 'Caracas Palace Hotel', 'Historic landmark', 5, 32, 96),
(302, 'Hotel Madero', 'Modern design in Puerto Madero', 4, 33, 124),
(303, 'Four Seasons Buenos Aires', 'Elegant with garden', 5, 33, 124),
(304, 'Alvear Palace Hotel', 'Luxury in Recoleta', 5, 33, 124),
(305, 'Holiday Inn Cordoba', 'Business comfort', 3, 33, 125),
(306, 'Azur Real Boutique Hotel', 'Boutique with spa', 4, 33, 125),
(307, 'Sofitel La Cañada', 'French elegance', 5, 33, 125),
(308, 'Mod Hotels', 'Modern budget', 3, 33, 126),
(309, 'Diplomatic Hotel', 'Classic 4-star', 4, 33, 126),
(310, 'Park Hyatt Mendoza', 'Luxury with casino', 5, 33, 126),
(311, 'lutetia paris', NULL, 3, 5, 12);

-- --------------------------------------------------------

--
-- Table structure for table `landmarks`
--

CREATE TABLE `landmarks` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `country_id` int(11) NOT NULL,
  `city_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landmarks`
--

INSERT INTO `landmarks` (`id`, `name`, `description`, `country_id`, `city_id`) VALUES
(1, 'Hagia Sophia', 'Historic museum-mosque', 1, 1),
(2, 'Duden Waterfalls', 'Natural waterfalls', 1, 2),
(3, 'Göreme Open Air Museum', 'Rock-cut churches', 1, 3),
(4, 'Pyramids of Giza', 'Ancient wonder', 2, 4),
(5, 'Karnak Temple', 'Massive temple complex', 2, 5),
(6, 'Naama Bay', 'Resort area', 2, 6),
(7, 'Burj Khalifa', 'World’s tallest building', 3, 7),
(8, 'Sheikh Zayed Grand Mosque', 'Grand mosque', 3, 8),
(9, 'Colosseum', 'Ancient amphitheater', 4, 9),
(10, 'Florence Cathedral', 'Duomo', 4, 10),
(11, 'St. Mark’s Square', 'Famous square', 4, 11),
(12, 'Eiffel Tower', 'Iconic tower', 5, 12),
(13, 'Promenade des Anglais', 'Seaside promenade', 5, 13),
(14, 'Basilique Notre-Dame de Fourvière', 'Basilica', 5, 14),
(15, 'Sagrada Família', 'Gaudí masterpiece', 6, 15),
(16, 'Royal Palace of Madrid', 'Official residence', 6, 16),
(17, 'Plaza de España', 'Historic square', 6, 17),
(18, 'Acropolis of Athens', 'Ancient citadel', 7, 18),
(19, 'Santorini Caldera', 'Volcanic caldera', 7, 19),
(20, 'White Tower of Thessaloniki', 'Historic tower', 7, 20),
(21, 'Grand Palace', 'Royal residence', 8, 21),
(22, 'Big Buddha Phuket', 'Large Buddha statue', 8, 22),
(23, 'Wat Phra Singh', 'Temple', 8, 23),
(24, 'Tokyo Tower', 'Communications tower', 9, 24),
(25, 'Kinkaku-ji', 'Golden Pavilion', 9, 25),
(26, 'Osaka Castle', 'Historic castle', 9, 26),
(27, 'Statue of Liberty', 'Liberty statue', 10, 27),
(28, 'Hollywood Sign', 'Famous sign', 10, 28),
(29, 'Las Vegas Strip', 'Entertainment boulevard', 10, 29),
(30, 'Big Ben', 'Clock tower', 11, 30),
(31, 'Edinburgh Castle', 'Historic fortress', 11, 31),
(32, 'Manchester Town Hall', 'Gothic building', 11, 32),
(33, 'Brandenburg Gate', 'Neoclassical monument', 12, 33),
(34, 'Neuschwanstein Castle', 'Fairytale castle', 12, 34),
(35, 'Miniatur Wunderland', 'Model railway', 12, 35),
(36, 'Jemaa el-Fnaa', 'Famous square', 13, 36),
(37, 'Bou Inania Madrasa', 'Historic school', 13, 37),
(38, 'Hassan II Mosque', 'Grand mosque', 13, 38),
(39, 'Citadel of Amman', 'Ancient hilltop', 14, 39),
(40, 'Petra', 'Rose city', 14, 40),
(41, 'Aqaba Fort', 'Mamluk fort', 14, 41),
(45, 'Christ the Redeemer', 'Iconic statue', 16, 45),
(46, 'Ibirapuera Park', 'Urban park', 16, 46),
(47, 'Pelourinho', 'Historic center', 16, 47),
(48, 'Sydney Opera House', 'Performing arts centre', 17, 48),
(49, 'Great Ocean Road', 'Scenic drive', 17, 49),
(50, 'South Bank Parklands', 'Riverfront park', 17, 50),
(51, 'CN Tower', 'Communications tower', 18, 51),
(52, 'Stanley Park', 'Urban park', 18, 52),
(53, 'Old Montreal', 'Historic district', 18, 53),
(54, 'Zócalo', 'Main square', 19, 54),
(55, 'Chichén Itzá', 'Mayan ruins', 19, 55),
(56, 'Guadalajara Cathedral', 'Cathedral', 19, 56),
(57, 'Red Fort', 'Mughal fortress', 20, 57),
(58, 'Gateway of India', 'Arch monument', 20, 58),
(59, 'Hawa Mahal', 'Palace of winds', 20, 59),
(60, 'Topkapi Palace', 'Ottoman imperial palace', 1, 1),
(61, 'Grand Bazaar', 'Historic covered market', 1, 1),
(62, 'Hadrian\'s Gate', 'Ancient Roman gate', 1, 2),
(63, 'Konyaalti Beach', 'Popular pebble beach', 1, 2),
(64, 'Uchisar Castle', 'Rock fortress with views', 1, 3),
(65, 'Pigeon Valley', 'Scenic hiking valley', 1, 3),
(66, 'Egyptian Museum', 'Ancient artifacts', 2, 4),
(67, 'Khan el-Khalili', 'Famous bazaar', 2, 4),
(68, 'Valley of the Kings', 'Pharaohs tombs', 2, 5),
(69, 'Temple of Hatshepsut', 'Unique terraced temple', 2, 5),
(70, 'Ras Mohammed National Park', 'Marine reserve', 2, 6),
(71, 'Old Market Sharm', 'Local souq', 2, 6),
(72, 'Dubai Mall', 'World\'s largest mall', 3, 7),
(73, 'Palm Jumeirah', 'Artificial archipelago', 3, 7),
(74, 'Louvre Abu Dhabi', 'Art museum', 3, 8),
(75, 'Ferrari World', 'Theme park', 3, 8),
(76, 'Roman Forum', 'Ancient ruins', 4, 9),
(77, 'Trevi Fountain', 'Baroque fountain', 4, 9),
(78, 'Uffizi Gallery', 'Renaissance art', 4, 10),
(79, 'Ponte Vecchio', 'Medieval bridge', 4, 10),
(80, 'Rialto Bridge', 'Iconic bridge', 4, 11),
(81, 'Doge\'s Palace', 'Gothic palace', 4, 11),
(82, 'Louvre Museum', 'World famous museum', 5, 12),
(83, 'Arc de Triomphe', 'Historic monument', 5, 12),
(84, 'Castle Hill', 'Park with waterfall', 5, 13),
(85, 'Russian Cathedral', 'Orthodox church', 5, 13),
(86, 'Vieux Lyon', 'Renaissance district', 5, 14),
(87, 'Parc de la Tête d\'Or', 'Large urban park', 5, 14),
(88, 'Park Güell', 'Gaudí park', 6, 15),
(89, 'Las Ramblas', 'Famous boulevard', 6, 15),
(90, 'Prado Museum', 'Art museum', 6, 16),
(91, 'Retiro Park', 'Central park', 6, 16),
(92, 'Seville Cathedral', 'Gothic cathedral', 6, 17),
(93, 'Alcázar of Seville', 'Royal palace', 6, 17),
(94, 'Parthenon', 'Temple on Acropolis', 7, 18),
(95, 'Plaka District', 'Old neighborhood', 7, 18),
(96, 'Red Beach', 'Famous red sand beach', 7, 19),
(97, 'Ancient Thera', 'Archaeological site', 7, 19),
(98, 'Aristotelous Square', 'Central square', 7, 20),
(99, 'Ladadika District', 'Lively area', 7, 20),
(100, 'Wat Arun', 'Temple of Dawn', 8, 21),
(101, 'Chatuchak Market', 'Weekend market', 8, 21),
(102, 'Phi Phi Islands', 'Day trip islands', 8, 22),
(103, 'Old Phuket Town', 'Sino-Portuguese architecture', 8, 22),
(104, 'Doi Suthep', 'Mountain temple', 8, 23),
(105, 'Night Bazaar', 'Evening market', 8, 23),
(106, 'Shibuya Crossing', 'Famous scramble', 9, 24),
(107, 'Senso-ji Temple', 'Asakusa temple', 9, 24),
(108, 'Fushimi Inari', 'Thousand torii gates', 9, 25),
(109, 'Arashiyama Bamboo Grove', 'Bamboo forest', 9, 25),
(110, 'Osaka Aquarium Kaiyukan', 'Large aquarium', 9, 26),
(111, 'Shitenno-ji Temple', 'Ancient Buddhist temple', 9, 26),
(112, 'Times Square', 'Neon-lit intersection', 10, 27),
(113, 'Central Park', 'Urban park', 10, 27),
(114, 'Griffith Observatory', 'Views of LA', 10, 28),
(115, 'Santa Monica Pier', 'Beach pier', 10, 28),
(116, 'Fremont Street', 'Downtown experience', 10, 29),
(117, 'High Roller', 'Giant observation wheel', 10, 29),
(118, 'London Eye', 'Giant ferris wheel', 11, 30),
(119, 'Tower of London', 'Historic castle', 11, 30),
(120, 'Royal Mile', 'Historic street', 11, 31),
(121, 'Arthur\'s Seat', 'Volcanic hill', 11, 31),
(122, 'Old Trafford', 'Famous stadium', 11, 32),
(123, 'Science and Industry Museum', 'Interactive museum', 11, 32),
(124, 'Reichstag Building', 'German parliament', 12, 33),
(125, 'East Side Gallery', 'Berlin Wall art', 12, 33),
(126, 'Marienplatz', 'Central square', 12, 34),
(127, 'English Garden', 'Large urban park', 12, 34),
(128, 'Elbe Philharmonic Hall', 'Concert venue', 12, 35),
(129, 'St. Michael\'s Church', 'Baroque church', 12, 35),
(130, 'Majorelle Garden', 'Botanical garden', 13, 36),
(131, 'Bahia Palace', '19th century palace', 13, 36),
(132, 'Medina of Fez', 'UNESCO old city', 13, 37),
(133, 'Bab Bou Jeloud', 'Decorative gate', 13, 37),
(134, 'Corniche Casablanca', 'Seafront promenade', 13, 38),
(135, 'Royal Palace', 'Official residence', 13, 38),
(136, 'Roman Theater', 'Ancient amphitheater', 14, 39),
(137, 'Rainbow Street', 'Vibrant street', 14, 39),
(138, 'Little Petra', 'Smaller archaeological site', 14, 40),
(139, 'Wadi Rum', 'Desert valley', 14, 40),
(140, 'Aqaba Marine Park', 'Diving spot', 14, 41),
(141, 'Ayla Oasis', 'Modern resort', 14, 41),
(142, 'Sugarloaf Mountain', 'Cable car views', 16, 45),
(143, 'Copacabana Beach', 'Famous beach', 16, 45),
(144, 'São Paulo Museum of Art', 'MASP collection', 16, 46),
(145, 'Paulista Avenue', 'Main avenue', 16, 46),
(146, 'Elevador Lacerda', 'Historic elevator', 16, 47),
(147, 'Santo Antônio Church', 'Baroque church', 16, 47),
(148, 'Bondi Beach', 'Famous beach', 17, 48),
(149, 'Darling Harbour', 'Waterfront precinct', 17, 48),
(150, 'Royal Botanic Gardens', 'Melbourne gardens', 17, 49),
(151, 'Federation Square', 'Cultural hub', 17, 49),
(152, 'Story Bridge', 'Historic bridge', 17, 50),
(153, 'Lone Pine Koala Sanctuary', 'Koala park', 17, 50),
(154, 'Niagara Falls', 'Famous waterfalls', 18, 51),
(155, 'Royal Ontario Museum', 'ROM museum', 18, 51),
(156, 'Granville Island', 'Public market', 18, 52),
(157, 'Capilano Suspension Bridge', 'Suspension bridge', 18, 52),
(158, 'Mount Royal Park', 'Designed by Olmsted', 18, 53),
(159, 'Notre-Dame Basilica', 'Historic church', 18, 53),
(160, 'Teotihuacan Pyramids', 'Ancient pyramids', 19, 54),
(161, 'Chapultepec Castle', 'Historic castle', 19, 54),
(162, 'Tulum Ruins', 'Mayan coastal ruins', 19, 55),
(163, 'Xcaret Park', 'Eco theme park', 19, 55),
(164, 'Instituto Cultural Cabañas', 'World heritage', 19, 56),
(165, 'Tlaquepaque', 'Arts and crafts town', 19, 56),
(166, 'Qutub Minar', 'Victory tower', 20, 57),
(167, 'India Gate', 'War memorial', 20, 57),
(168, 'Marine Drive', 'Queen\'s necklace', 20, 58),
(169, 'Elephanta Caves', 'Rock-cut temples', 20, 58),
(170, 'Amer Fort', 'Hilltop fort', 20, 59),
(171, 'City Palace Jaipur', 'Royal palace', 20, 59),
(172, 'Sharjah Museum of Islamic Civilization', 'Extensive Islamic art and culture', 3, 60),
(173, 'Al Noor Mosque', 'Beautiful Ottoman-style mosque', 3, 60),
(174, 'Al Qasba', 'Water canal with entertainment and dining', 3, 60),
(175, 'Baumana Street', 'Pedestrian cultural street', 21, 61),
(176, 'Qol Sharif Mosque', 'Main mosque of Kazan', 21, 61),
(177, 'Kazan Kremlin', 'UNESCO fortress', 21, 61),
(178, 'Nevsky Prospekt', 'Main avenue', 21, 62),
(179, 'Peterhof Palace', 'Grand palace with fountains', 21, 62),
(180, 'Hermitage Museum', 'World-class art museum', 21, 62),
(181, 'Bolshoi Theatre', 'World-famous ballet and opera', 21, 63),
(182, 'Moscow Kremlin', 'Historic fortified complex', 21, 63),
(183, 'Red Square', 'Historic square with St. Basil Cathedral', 21, 63),
(184, 'Wuhou Shrine', 'Temple of Zhuge Liang', 22, 64),
(185, 'Jinli Ancient Street', 'Traditional street', 22, 64),
(186, 'Research Base of Giant Panda', 'Panda breeding center', 22, 64),
(187, 'Oriental Pearl Tower', 'Iconic TV tower', 22, 65),
(188, 'Yu Garden', 'Classical Chinese garden', 22, 65),
(189, 'The Bund', 'Famous waterfront', 22, 65),
(190, 'Temple of Heaven', 'Religious complex', 22, 66),
(191, 'Great Wall of China', 'Ancient wall at Badaling', 22, 66),
(192, 'Forbidden City', 'Imperial palace complex', 22, 66),
(193, 'Malioboro Street', 'Vibrant shopping street', 23, 67),
(194, 'Prambanan Temple', 'Hindu temple complex', 23, 67),
(195, 'Borobudur Temple', 'Buddhist temple', 23, 67),
(196, 'Tegalalang Rice Terrace', 'Famous rice paddies', 23, 68),
(197, 'Ubud Monkey Forest', 'Nature reserve', 23, 68),
(198, 'Tanah Lot Temple', 'Sea temple', 23, 68),
(199, 'Istiqlal Mosque', 'Largest mosque in SE Asia', 23, 69),
(200, 'Kota Tua', 'Old Batavia heritage', 23, 69),
(201, 'Monas National Monument', 'Independence monument', 23, 69),
(202, 'My Khe Beach', 'Beautiful sandy beach', 24, 70),
(203, 'Marble Mountains', 'Five marble hills', 24, 70),
(204, 'Golden Bridge', 'Bridge held by giant hands', 24, 70),
(205, 'Cu Chi Tunnels', 'War historical site', 24, 71),
(206, 'Notre Dame Cathedral', 'French colonial church', 24, 71),
(207, 'Ben Thanh Market', 'Famous market', 24, 71),
(208, 'Ho Chi Minh Mausoleum', 'Presidential site', 24, 72),
(209, 'Old Quarter', 'Historic street grid', 24, 72),
(210, 'Hoan Kiem Lake', 'Scenic lake with temple', 24, 72),
(211, 'Seongsan Ilchulbong', 'Sunrise peak', 25, 73),
(212, 'Manjanggul Cave', 'Lava tube cave', 25, 73),
(213, 'Hallasan National Park', 'Volcanic mountain', 25, 73),
(214, 'Jagalchi Fish Market', 'Seafood market', 25, 74),
(215, 'Gamcheon Culture Village', 'Colorful hillside village', 25, 74),
(216, 'Haeundae Beach', 'Famous beach', 25, 74),
(217, 'Bukchon Hanok Village', 'Traditional houses', 25, 75),
(218, 'N Seoul Tower', 'Observation tower', 25, 75),
(219, 'Gyeongbokgung Palace', 'Royal palace', 25, 75),
(220, 'Cathedral of Faro', 'Historic cathedral', 26, 76),
(221, 'Faro Old Town', 'Walled historic center', 26, 76),
(222, 'Ria Formosa', 'Natural lagoon system', 26, 76),
(223, 'Porto Cathedral', 'Romanesque cathedral', 26, 77),
(224, 'Livraria Lello', 'Famous bookstore', 26, 77),
(225, 'Dom Luís I Bridge', 'Iconic bridge', 26, 77),
(226, 'Alfama District', 'Old Moorish quarter', 26, 78),
(227, 'Jerónimos Monastery', 'Manueline monastery', 26, 78),
(228, 'Belém Tower', 'UNESCO tower', 26, 78),
(229, 'Scheveningen Beach', 'Seaside resort', 27, 79),
(230, 'Mauritshuis', 'Art museum', 27, 79),
(231, 'Binnenhof', 'Dutch parliament', 27, 79),
(232, 'Cube Houses', 'Innovative architecture', 27, 80),
(233, 'Markthal', 'Market hall', 27, 80),
(234, 'Erasmus Bridge', 'Modern bridge', 27, 80),
(235, 'Canal Ring', 'UNESCO canals', 27, 81),
(236, 'Van Gogh Museum', 'Art museum', 27, 81),
(237, 'Anne Frank House', 'Museum and historic house', 27, 81),
(238, 'Lake Thun', 'Alpine lake', 28, 82),
(239, 'Harder Kulm', 'Viewpoint', 28, 82),
(240, 'Jungfraujoch', 'Top of Europe', 28, 82),
(241, 'St. Peter\'s Cathedral', 'Reformation landmark', 28, 83),
(242, 'Lake Geneva', 'Crescent-shaped lake', 28, 83),
(243, 'Jet d\'Eau', 'Large fountain', 28, 83),
(244, 'Swiss National Museum', 'Cultural history', 28, 84),
(245, 'Old Town (Altstadt)', 'Historic center', 28, 84),
(246, 'Zurich Lake', 'Scenic lake', 28, 84),
(247, 'Ribersborg Beach', 'Urban beach', 29, 85),
(248, 'Malmö Castle', 'Renaissance fortress', 29, 85),
(249, 'Turning Torso', 'Skyscraper', 29, 85),
(250, 'Universeum', 'Science center', 29, 86),
(251, 'Gothenburg Archipelago', 'Island archipelago', 29, 86),
(252, 'Liseberg', 'Amusement park', 29, 86),
(253, 'Skansen', 'Open-air museum', 29, 87),
(254, 'Vasa Museum', '17th century ship', 29, 87),
(255, 'Gamla Stan', 'Old town', 29, 87),
(256, 'Northern Lights Cathedral', 'Cultural center', 30, 88),
(257, 'Polar Museum', 'Arctic hunting history', 30, 88),
(258, 'Arctic Cathedral', 'Modern church', 30, 88),
(259, 'Fish Market', 'Seafood market', 30, 89),
(260, 'Fløyen Mountain', 'Cable car view', 30, 89),
(261, 'Bryggen Wharf', 'UNESCO Hanseatic', 30, 89),
(262, 'Oslo Opera House', 'Marble building', 30, 90),
(263, 'Fram Museum', 'Polar exploration ship', 30, 90),
(264, 'Viking Ship Museum', 'Viking vessels', 30, 90),
(265, 'Ugarit Ruins', 'Ancient city ruins', 31, 91),
(266, 'Saladin Castle', 'Crusader fortress', 31, 91),
(267, 'Latakia Corniche', 'Seafront promenade', 31, 91),
(268, 'Al-Madina Souq', 'Covered market', 31, 92),
(269, 'Great Mosque of Aleppo', 'Historic mosque', 31, 92),
(270, 'Citadel of Aleppo', 'Medieval fortress', 31, 92),
(271, 'Mount Qasioun', 'Mountain overlook', 31, 93),
(272, 'Al-Hamidiyah Souq', 'Historic market', 31, 93),
(273, 'Umayyad Mosque', 'Grand mosque', 31, 93),
(274, 'Plaza de Toros', 'Bullring', 32, 94),
(275, 'Botanical Garden', 'Garden', 32, 94),
(276, 'Valencia Aquarium', 'Aquarium park', 32, 94),
(277, 'General Rafael Urdaneta Bridge', 'Iconic bridge', 32, 95),
(278, 'Basilica of Our Lady of Chiquinquirá', 'Religious site', 32, 95),
(279, 'Lake Maracaibo', 'Large lake', 32, 95),
(280, 'National Pantheon', 'Burial place of heroes', 32, 96),
(281, 'Plaza Bolívar', 'Main square', 32, 96),
(282, 'Ávila National Park', 'Mountain park', 32, 96),
(302, 'La Boca & Caminito', 'Colorful street and tango', 33, 124),
(303, 'Teatro Colón', 'World-famous opera house', 33, 124),
(304, 'Casa Rosada', 'Presidential palace', 33, 124),
(305, 'Plaza San Martin', 'Main square', 33, 125),
(306, 'Sierras de Cordoba', 'Mountain range', 33, 125),
(307, 'Manzana Jesuitica', 'UNESCO Jesuit block', 33, 125),
(308, 'Bodegas (Wineries)', 'Famous wine route', 33, 126),
(309, 'Parque General San Martin', 'Large hilltop park', 33, 126),
(310, 'Aconcagua', 'Highest peak in Americas', 33, 126);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` varchar(50) DEFAULT 'user',
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `recipient_id`, `recipient_type`, `message`, `attachment`, `is_read`, `created_at`) VALUES
(1, 9, 7, 'user', 'هاي نور', NULL, 1, '2026-04-07 10:12:18'),
(2, 7, 9, 'user', 'هلا', NULL, 1, '2026-04-07 10:12:57'),
(3, 9, 7, 'user', 'كيفك', NULL, 1, '2026-04-07 10:17:10'),
(4, 7, 9, 'user', 'تمام وانت', NULL, 1, '2026-04-07 11:10:09'),
(5, 9, 7, 'user', 'تمام الحمدالله', NULL, 1, '2026-04-07 11:16:00'),
(6, 7, 9, 'user', 'خير في شي ؟', NULL, 1, '2026-04-07 11:19:09'),
(7, 9, 5, 'user', 'مرحبا ادمن', NULL, 1, '2026-04-07 11:20:11'),
(8, 5, 9, 'user', 'هلا سلطان الغالي', NULL, 1, '2026-04-07 11:20:35'),
(9, 9, 5, 'user', 'لك كيفك', NULL, 1, '2026-04-07 11:22:27'),
(10, 7, 9, 'user', 'شغلت بالي', NULL, 1, '2026-04-07 11:35:43'),
(11, 7, 5, 'user', 'هاي ادمن', NULL, 1, '2026-04-07 14:07:15'),
(12, 12, 6, 'user', 'هايي سيمو', NULL, 1, '2026-04-08 17:09:22'),
(13, 12, 6, 'user', 'كيفك', NULL, 1, '2026-04-08 17:09:28'),
(14, 6, 12, 'user', 'igh', NULL, 1, '2026-04-11 09:01:28'),
(15, 9, 7, 'user', 'لا ولشي فصل النت الله لا يشغلك بال', NULL, 1, '2026-04-11 09:10:12'),
(16, 7, 9, 'user', 'لو بهمك كنت عرفت', NULL, 0, '2026-04-11 09:16:04'),
(18, 7, 6, 'user', 'هاي سيمو', NULL, 0, '2026-04-15 11:24:15'),
(19, 7, 12, 'user', 'هاي يارا', NULL, 0, '2026-04-15 11:24:28'),
(20, 7, 14, 'user', 'هاي سوسو', NULL, 1, '2026-04-15 11:29:55'),
(21, 5, 9, 'user', 'تمام الحمدالله انت كيفك', NULL, 0, '2026-04-17 16:32:18'),
(22, 5, 7, 'user', 'يا هلا نور', NULL, 0, '2026-04-17 16:32:28'),
(23, 14, 7, 'user', 'هلا كيفك', NULL, 1, '2026-04-17 17:13:27'),
(24, 7, 14, 'user', 'تمام و انتي', NULL, 1, '2026-04-17 17:18:12'),
(25, 14, 7, 'user', 'تمام', NULL, 0, '2026-04-17 17:19:08'),
(26, 14, 7, 'user', 'بدي اسالك', NULL, 0, '2026-04-17 17:19:15'),
(27, 8, 5, 'user', 'مرحبا ادمن', NULL, 1, '2026-04-23 12:03:01'),
(28, 5, 8, 'user', 'هلا', NULL, 0, '2026-04-23 16:48:24'),
(30, 5, 8, 'user', 'كيفك', NULL, 0, '2026-04-23 16:48:43'),
(31, 14, 13, 'user', 'هاي يزن', NULL, 1, '2026-04-24 11:25:09'),
(32, 14, 13, 'user', 'ليك شحلو', 'uploads/messenger/1777030321_0b4bf099509fc1a5d3944725727d1713.jpg', 1, '2026-04-24 11:32:01'),
(33, 13, 14, 'user', 'واووو بتجننن', NULL, 1, '2026-04-24 11:32:25'),
(34, 14, 13, 'user', '', 'uploads/messenger/1777031912_voice.webm', 1, '2026-04-24 11:58:32');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data`, `is_read`, `created_at`) VALUES
(1, 6, 'booking_status', 'Booking Status Updated', 'Your booking for tour \'usa\' has been updated to: Confirmed.', NULL, 1, '2026-03-31 14:16:05'),
(2, 6, 'booking_status', 'Booking Status Updated', 'Your booking for tour \'usa\' has been updated to: Confirmed.', NULL, 1, '2026-03-31 14:16:09'),
(32, 9, 'office_approved', 'Office Approved', 'Your office registration has been approved. You can now login to your account.', '{\"office_id\":4}', 1, '2026-04-05 20:16:03'),
(33, 11, 'office_approved', 'Office Approved', 'Your office registration has been approved. You can now login to your account.', '{\"office_id\":6}', 1, '2026-04-05 20:24:30'),
(34, 7, 'new_follower', 'متابعة جديدة', 'المستخدم yara بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:38:24'),
(35, 6, 'new_follower', 'متابعة جديدة', 'المستخدم yara بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:38:27'),
(36, 12, 'new_follower', 'متابعة جديدة', 'المستخدم sami ahmed بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:38:59'),
(37, 7, 'new_follower', 'متابعة جديدة', 'المستخدم sami ahmed بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:39:08'),
(38, 12, 'new_follower', 'متابعة جديدة', 'المستخدم yazan بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:42:00'),
(39, 7, 'new_follower', 'متابعة جديدة', 'المستخدم yazan بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:42:04'),
(40, 6, 'new_follower', 'متابعة جديدة', 'المستخدم yazan بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:42:06'),
(41, 13, 'new_follower', 'متابعة جديدة', 'المستخدم noor بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:42:54'),
(42, 12, 'new_follower', 'متابعة جديدة', 'المستخدم noor بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:42:57'),
(43, 6, 'new_follower', 'متابعة جديدة', 'المستخدم noor بدأ بمتابعتك.', NULL, 1, '2026-04-05 21:43:11'),
(61, 8, 'new_booking', '???? New Booking(s) Received', 'You have 1 new booking(s) for your tours. Click below to review.', NULL, 1, '2026-04-07 15:32:40'),
(62, 6, 'booking_status', 'Booking Status Updated', 'Your booking for tour \'Venezuela\' has been updated to: Confirmed.', NULL, 1, '2026-04-07 15:33:07'),
(63, 13, 'new_follower', 'متابعة جديدة', 'المستخدم sami ahmed بدأ بمتابعتك.', NULL, 0, '2026-04-07 15:48:05'),
(64, 13, 'new_follower', 'متابعة جديدة', 'المستخدم noor بدأ بمتابعتك.', NULL, 0, '2026-04-08 16:23:05'),
(65, 12, 'booking_status', 'Booking Status Updated', 'Your booking for tour \'Russia\' has been updated to: Confirmed.', NULL, 0, '2026-04-08 16:40:33'),
(66, 7, 'new_follower', 'متابعة جديدة', 'المستخدم sami ahmed بدأ بمتابعتك.', NULL, 1, '2026-04-11 09:00:19'),
(68, 11, 'new_follower', 'New Follower', 'yara started following your office.', NULL, 0, '2026-04-15 11:43:37'),
(71, 5, 'new_tour_pending', '???? New Tour Pending Approval', 'Office \'travel\' has added a new tour: \'لات  نةم\'. It is waiting for your approval.', NULL, 1, '2026-04-22 17:08:39'),
(72, 5, 'pending_offices', '⚠️ Pending Office Requests', 'There are 1 office(s) waiting for your approval.', NULL, 1, '2026-04-23 11:48:12'),
(73, 5, 'pending_offices', '⚠️ Pending Office Requests', 'There are 1 office(s) waiting for your approval.', NULL, 0, '2026-04-23 11:57:47'),
(74, 5, 'new_tour_pending', '???? New Tour Pending Approval', 'Office \'travel\' has added a new tour: \'ىتنننننننننننننننن\'. It is waiting for your approval.', NULL, 0, '2026-04-23 12:03:37'),
(75, 8, 'new_booking', '???? New Booking(s) Received', 'You have 1 new booking(s).', NULL, 0, '2026-04-23 17:23:07'),
(76, 13, 'new_follower', 'New Follower', 'samira started following you.', NULL, 0, '2026-04-24 18:22:56'),
(77, 14, 'new_follower', 'New Follower', 'User yazan started following you.', NULL, 1, '2026-04-24 18:23:49'),
(78, 5, 'new_tour_pending', '???? New Tour Pending Approval', 'Office \'travel\' has added a new tour: \'mmmm\'. It is waiting for your approval.', NULL, 0, '2026-05-17 17:21:16');

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `type` enum('tourism','scholarship','both') DEFAULT 'both',
  `contact_email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `warning_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`, `description`, `logo`, `type`, `contact_email`, `address`, `approved`, `featured`, `created_at`, `updated_at`, `warning_count`) VALUES
(3, 'travel', 'We provide the best services and work 24 non-stop to meet your needs.', 'uploads/offices/1775414881_69d2ae618b734.jpg', 'tourism', 'travel1@gmail.com', '123 Collins Street, Melbourne VIC 3000, Australia', 1, 0, '2026-04-05 18:48:01', '2026-04-05 19:29:34', 0),
(4, 'tourism', 'We offer top‑quality services around the clock, always ready to fulfill your requirements.', 'uploads/offices/1775415312_69d2b0103dac8.jpg', 'tourism', 'tourism@gmail.com', '123, Beyoğlu, 34433 İstanbul, Turkey', 1, 1, '2026-04-05 18:55:12', '2026-04-17 16:27:59', 0),
(5, 'travel & tour ', 'We provide high-quality services 24 hours a day, 7 days a week, to ensure your needs are met at any time.', 'uploads/offices/1775415532_69d2b0ec8d7a6.jpg', 'tourism', 'traveltour@gmail.com', '15 Rue de Rivoli, 75001 Paris, France', 0, 0, '2026-04-05 18:58:52', '2026-04-05 18:58:52', 0),
(6, 'plane', 'We offer comprehensive travel services, hotel bookings, and guided tours at competitive prices, with a professional team available 24/7 to ensure safe and enjoyable journeys.', 'uploads/offices/1775420636_69d2c4dcb41df.jpg', 'tourism', 'plane@gamail.com', ' 405, Al Thuraya Tower, Dubai Media City, Dubai, UAE', 1, 0, '2026-04-05 20:23:56', '2026-04-05 20:24:30', 0);

-- --------------------------------------------------------

--
-- Table structure for table `office_last_viewed`
--

CREATE TABLE `office_last_viewed` (
  `user_id` int(11) NOT NULL,
  `comments_last_viewed` datetime DEFAULT NULL,
  `ratings_last_viewed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `type` enum('post','reel','story') DEFAULT 'post',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `content`, `image`, `video`, `type`, `created_at`, `updated_at`) VALUES
(3, 7, '', 'uploads/posts/1775042467_69ccffa39f03e.jpg', '', 'post', '2026-04-01 11:21:07', '2026-04-01 11:21:07'),
(6, 6, 'my new car', 'uploads/posts/1775424773_69d2d50552e52.jpg', '', 'post', '2026-04-05 21:32:53', '2026-04-05 21:32:53'),
(7, 7, 'nicee day', 'uploads/posts/1775424852_69d2d55442150.jpg', '', 'post', '2026-04-05 21:34:12', '2026-04-05 21:34:12'),
(8, 12, 'I love it', 'uploads/posts/1775425010_69d2d5f2be09a.jpg', '', 'post', '2026-04-05 21:36:50', '2026-04-05 21:36:50'),
(9, 12, 'I miss this place', '', 'uploads/posts/1775425041_69d2d611394e6.mp4', 'reel', '2026-04-05 21:37:21', '2026-04-05 21:37:21'),
(10, 13, '', 'uploads/posts/1775425286_69d2d70617a96.jpg', '', 'post', '2026-04-05 21:41:26', '2026-04-05 21:41:26'),
(11, 6, '', 'uploads/posts/1775897871_69da0d0f7de06.jpg', '', 'post', '2026-04-11 08:57:51', '2026-04-11 08:57:51'),
(13, 13, '', '', 'uploads/posts/1777054291_69ebb25319c9f.mp4', 'story', '2026-04-24 18:11:31', '2026-04-24 18:11:31'),
(14, 13, '', '', 'uploads/posts/1777054647_69ebb3b764693.mp4', 'story', '2026-04-24 18:17:27', '2026-04-24 18:17:27'),
(15, 14, '', 'uploads/posts/1777054948_69ebb4e468d55.jpg', '', 'story', '2026-04-24 18:22:28', '2026-04-24 18:22:28'),
(16, 14, '', '', 'uploads/posts/1777054962_69ebb4f2e221e.mp4', 'story', '2026-04-24 18:22:42', '2026-04-24 18:22:42'),
(17, 14, '', '', 'uploads/posts/1782472192_6a3e5e00df268.mp4', 'story', '2026-06-26 11:09:52', '2026-06-26 11:09:52');

-- --------------------------------------------------------

--
-- Table structure for table `post_comments`
--

CREATE TABLE `post_comments` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `commentable_type` enum('post','tour','scholarship') NOT NULL,
  `commentable_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_comments`
--

INSERT INTO `post_comments` (`id`, `comment_id`, `commentable_type`, `commentable_id`, `created_at`) VALUES
(7, 9, 'tour', 4, '2026-03-29 10:23:18'),
(8, 10, 'tour', 4, '2026-03-29 11:16:38'),
(10, 12, 'tour', 4, '2026-03-29 11:54:57'),
(11, 13, 'tour', 4, '2026-03-31 10:08:44'),
(13, 15, 'post', 2, '2026-03-31 15:34:18'),
(14, 16, 'post', 2, '2026-04-01 10:39:05'),
(15, 17, 'post', 1, '2026-04-04 16:55:57'),
(16, 18, 'tour', 1, '2026-04-05 10:50:11'),
(18, 20, 'post', 7, '2026-04-05 21:37:48'),
(19, 21, 'post', 6, '2026-04-05 21:38:17'),
(20, 22, 'post', 7, '2026-04-05 21:39:21'),
(21, 23, 'post', 6, '2026-04-05 21:42:22'),
(22, 24, 'tour', 12, '2026-04-05 22:04:05'),
(23, 25, 'tour', 12, '2026-04-05 22:04:54'),
(24, 26, 'tour', 9, '2026-04-05 22:07:29'),
(25, 27, 'tour', 14, '2026-04-07 15:31:46'),
(26, 28, 'tour', 7, '2026-04-07 16:19:01'),
(27, 29, 'tour', 7, '2026-04-07 16:19:11'),
(28, 30, 'post', 10, '2026-04-07 16:20:29'),
(29, 31, 'post', 10, '2026-04-07 16:21:09'),
(31, 33, 'tour', 13, '2026-04-08 16:10:08'),
(32, 34, 'tour', 6, '2026-04-08 16:15:57'),
(33, 35, 'post', 7, '2026-04-08 16:22:27'),
(34, 36, 'tour', 6, '2026-04-11 08:52:35'),
(36, 38, 'post', 10, '2026-04-11 08:59:51'),
(37, 39, 'post', 10, '2026-04-11 08:59:57'),
(38, 40, 'tour', 11, '2026-04-15 11:49:02'),
(39, 41, 'tour', 14, '2026-04-15 18:20:36'),
(40, 42, 'tour', 19, '2026-04-22 14:14:47'),
(41, 43, 'tour', 12, '2026-04-23 17:38:43'),
(44, 46, 'tour', 9, '2026-05-17 17:11:21');

-- --------------------------------------------------------

--
-- Table structure for table `post_reactions`
--

CREATE TABLE `post_reactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `reaction_type_id` int(11) NOT NULL COMMENT '1 = like, (يمكن إضافة أنواع أخرى لاحقاً)',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_reactions`
--

INSERT INTO `post_reactions` (`id`, `user_id`, `post_id`, `reaction_type_id`, `created_at`) VALUES
(1, 6, 2, 1, '2026-03-31 18:34:20'),
(4, 7, 1, 1, '2026-04-04 19:23:10'),
(5, 6, 1, 1, '2026-04-05 22:14:49'),
(6, 12, 7, 1, '2026-04-06 00:37:39'),
(7, 12, 6, 1, '2026-04-06 00:38:01'),
(8, 12, 3, 1, '2026-04-06 00:38:21'),
(9, 6, 9, 1, '2026-04-06 00:38:57'),
(10, 6, 8, 1, '2026-04-06 00:39:03'),
(11, 6, 7, 1, '2026-04-06 00:39:06'),
(12, 13, 9, 1, '2026-04-06 00:41:59'),
(13, 7, 10, 1, '2026-04-06 00:43:03'),
(14, 7, 6, 1, '2026-04-06 00:43:08'),
(17, 7, 9, 1, '2026-04-08 19:23:37'),
(18, 6, 11, 1, '2026-04-11 11:57:55'),
(19, 6, 10, 1, '2026-04-11 12:00:01'),
(22, 7, 11, 1, '2026-04-22 17:13:11'),
(23, 13, 10, 1, '2026-04-24 20:09:06');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rateable_type` enum('office','tour','scholarship','university') NOT NULL,
  `rateable_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `rateable_type`, `rateable_id`, `rating`, `created_at`, `updated_at`) VALUES
(4, 6, 'tour', 12, 4, '2026-04-05 22:03:09', '2026-04-05 22:03:09'),
(5, 12, 'tour', 6, 5, '2026-04-05 22:07:08', '2026-04-05 22:07:08'),
(6, 12, 'tour', 9, 5, '2026-04-05 22:07:21', '2026-04-05 22:07:21'),
(7, 6, 'tour', 14, 4, '2026-04-07 15:31:29', '2026-04-07 15:31:29'),
(8, 13, 'tour', 13, 5, '2026-04-08 16:09:38', '2026-04-08 16:09:38'),
(9, 7, 'tour', 13, 4, '2026-04-08 16:13:18', '2026-04-08 16:13:18'),
(10, 6, 'tour', 6, 5, '2026-04-11 08:52:28', '2026-04-11 08:52:28'),
(12, 7, 'tour', 12, 5, '2026-04-23 17:38:24', '2026-04-23 17:38:24'),
(13, 14, 'tour', 9, 4, '2026-04-24 11:23:38', '2026-04-24 11:23:38'),
(14, 14, 'tour', 6, 4, '2026-06-26 11:04:54', '2026-06-26 11:04:54');

-- --------------------------------------------------------

--
-- Table structure for table `reaction_types`
--

CREATE TABLE `reaction_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reaction_types`
--

INSERT INTO `reaction_types` (`id`, `name`, `icon`, `created_at`) VALUES
(1, 'like', '👍', '2026-03-15 15:10:58'),
(2, 'love', '❤️', '2026-03-15 15:10:58'),
(3, 'care', '🤗', '2026-03-15 15:10:58'),
(4, 'haha', '😂', '2026-03-15 15:10:58'),
(5, 'wow', '😲', '2026-03-15 15:10:58'),
(6, 'sad', '😢', '2026-03-15 15:10:58'),
(7, 'angry', '😠', '2026-03-15 15:10:58');

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `country_id` int(11) NOT NULL,
  `city_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `description`, `country_id`, `city_id`) VALUES
(1, 'Mikla', 'Modern Turkish rooftop', 1, 1),
(2, 'Seraser', 'Fine dining in Antalya', 1, 2),
(3, 'Ziggy\'s', 'Cave restaurant with views', 1, 3),
(4, 'El Fishawy', 'Famous café in Khan el-Khalili', 2, 4),
(5, 'Sofra', 'Traditional Egyptian', 2, 5),
(6, 'Fayrouz', 'Lebanese by the sea', 2, 6),
(7, 'Al Hadheerah', 'Arabian desert dining', 3, 7),
(8, 'Liwan', 'Emirati cuisine', 3, 8),
(9, 'La Pergola', 'Michelin-starred panoramic', 4, 9),
(10, 'Trattoria Mario', 'Florentine steak', 4, 10),
(11, 'Osteria alle Testiere', 'Seafood near Rialto', 4, 11),
(12, 'Le Jules Verne', 'Eiffel Tower restaurant', 5, 12),
(13, 'La Petite Maison', 'Niçoise cuisine', 5, 13),
(14, 'Les Trois Dômes', 'Gastronomic in Lyon', 5, 14),
(15, 'Tickets', 'Avant-garde tapas', 6, 15),
(16, 'Botín', 'Oldest restaurant in world', 6, 16),
(17, 'Eslava', 'Seville tapas bar', 6, 17),
(18, 'Dionysos Zonar\'s', 'Acropolis view', 7, 18),
(19, 'The Athenian House', 'Santorini fine dining', 7, 19),
(20, 'Kitchen Bar', 'Thessaloniki modern', 7, 20),
(21, 'Gaggan Anand', 'Progressive Indian', 8, 21),
(22, 'Blue Elephant', 'Royal Thai cuisine', 8, 22),
(23, 'Dash! Restaurant', 'Northern Thai', 8, 23),
(24, 'Sukiyabashi Jiro', 'Famous sushi', 9, 24),
(25, 'Gion Karyo', 'Kaiseki in Gion', 9, 25),
(26, 'Kushikatsu Daruma', 'Osaka kushikatsu', 9, 26),
(27, 'Le Bernardin', 'Seafood fine dining', 10, 27),
(28, 'Nobu Los Angeles', 'Fusion Japanese', 10, 28),
(29, 'Joël Robuchon', 'French in Las Vegas', 10, 29),
(30, 'Dinner by Heston Blumenthal', 'Historic British', 11, 30),
(31, 'The Witchery', 'Gothic Edinburgh', 11, 31),
(32, 'The French', 'Manchester fine dining', 11, 32),
(33, 'Tim Raue', 'Asian-inspired Berlin', 12, 33),
(34, 'Tantris', 'Classic Munich', 12, 34),
(35, 'The Table', 'Hamburg gourmet', 12, 35),
(36, 'Le Jardin', 'Marrakech garden restaurant', 13, 36),
(37, 'Café Clock', 'Fez cultural café', 13, 37),
(38, 'Rick\'s Café', 'Casablanca iconic', 13, 38),
(39, 'Fakhreldin', 'Jordanian fine dining', 14, 39),
(40, 'Petra Kitchen', 'Local cuisine experience', 14, 40),
(41, 'Ali Baba', 'Aqaba seafood', 14, 41),
(45, 'Aprazível', 'Santa Teresa Brazilian', 16, 45),
(46, 'D.O.M.', 'Amazonian fine dining', 16, 46),
(47, 'Casa de Tereza', 'Bahian cuisine', 16, 47),
(48, 'Quay', 'Harbour view fine dining', 17, 48),
(49, 'Attica', 'Melbourne culinary', 17, 49),
(50, 'Gerard\'s Bistro', 'Middle Eastern Brisbane', 17, 50),
(51, 'Canoe', 'Toronto panoramic', 18, 51),
(52, 'Hawksworth', 'Vancouver fine dining', 18, 52),
(53, 'Toqué!', 'Montreal gastronomy', 18, 53),
(54, 'Pujol', 'Mexico City innovative', 19, 54),
(55, 'Hartwood', 'Yucatan rustic', 19, 55),
(56, 'Alcalde', 'Guadalajara modern', 19, 56),
(57, 'Indian Accent', 'Modern Indian', 20, 57),
(58, 'Trishna', 'Seafood in Mumbai', 20, 58),
(59, 'Suvarna Mahal', 'Royal Rajasthani', 20, 59),
(60, 'Nusr-Et Steakhouse', 'Steakhouse famous worldwide', 1, 1),
(61, 'Karaköy Lokantası', 'Modern Turkish meyhane', 1, 1),
(62, '7 Mehmet', 'Panoramic Antalya cuisine', 1, 2),
(63, 'Vanilla Lounge', 'International fine dining', 1, 2),
(64, 'Seten Restaurant', 'Cave dining with view', 1, 3),
(65, 'Topdeck Cave', 'Traditional Cappadocia', 1, 3),
(66, 'Kebabgy', 'Modern Egyptian grill', 2, 4),
(67, 'Naguib Mahfouz Café', 'Literary themed restaurant', 2, 4),
(68, 'Al-Moudira', 'Palace dining in Luxor', 2, 5),
(69, 'Sunset Lounge', 'Nile view restaurant', 2, 5),
(70, 'Tamr Henna', 'Bedouin style Sharm', 2, 6),
(71, 'Fares Seafood', 'Fresh seafood by Red Sea', 2, 6),
(72, 'Zuma Dubai', 'Japanese izakaya style', 3, 7),
(73, 'Pierchic', 'Overwater seafood', 3, 7),
(74, 'Hakkasan Abu Dhabi', 'Cantonese fine dining', 3, 8),
(75, 'Tori No Su', 'Japanese robatayaki', 3, 8),
(76, 'Roscioli', 'Roman cuisine with bakery', 4, 9),
(77, 'Armando al Pantheon', 'Traditional Roman', 4, 9),
(78, 'Osteria del Cinghiale', 'Wild boar specialties', 4, 10),
(79, 'Enoteca Pinchiorri', 'Michelin starred', 4, 10),
(80, 'Al Covo', 'Venetian seafood', 4, 11),
(81, 'Trattoria da Fiore', 'Traditional Venetian', 4, 11),
(82, 'L\'Ambroisie', 'Classic French', 5, 12),
(83, 'Le Comptoir du Relais', 'Bistro chic', 5, 12),
(84, 'Jan', 'South African fusion in Nice', 5, 13),
(85, 'Le Chantecler', 'Gastronomic Nice', 5, 13),
(86, 'Mère Brazier', 'Historic Lyon bouchon', 5, 14),
(87, 'Takao Takano', 'Japanese-French fusion', 5, 14),
(88, 'Disfrutar', 'Creative Mediterranean', 6, 15),
(89, 'Cal Pep', 'Traditional tapas', 6, 15),
(90, 'Sobrino de Botín', 'Oldest restaurant', 6, 16),
(91, 'Casa Lucio', 'Famous huevos rotos', 6, 16),
(92, 'Abantal', 'Andalusian avant-garde', 6, 17),
(93, 'La Azotea', 'Seville rooftop', 6, 17),
(94, 'Spondi', 'Fine dining near Acropolis', 7, 18),
(95, 'Mani Mani', 'Traditional Mani cuisine', 7, 18),
(96, 'Selene', 'Santorini gastronomy', 7, 19),
(97, 'Metaxi Mas', 'Authentic Santorini tavern', 7, 19),
(98, 'Mouragio', 'Seaside Thessaloniki', 7, 20),
(99, 'Full tou Meze', 'Traditional meze', 7, 20),
(100, 'Sühring', 'German fine dining Bangkok', 8, 21),
(101, 'Issaya Siamese Club', 'Traditional Thai villa', 8, 21),
(102, 'Raya', 'Phuket traditional', 8, 22),
(103, 'Suay Restaurant', 'Creative Phuket', 8, 22),
(104, 'David\'s Kitchen', 'Northern Thai cuisine', 8, 23),
(105, 'The Service 1921', 'Colonial style Chiang Mai', 8, 23),
(106, 'Narisawa', 'Innovative biosphere cuisine', 9, 24),
(107, 'Sushi Saito', 'Michelin sushi', 9, 24),
(108, 'Kikunoi', 'Kaiseki Kyoto', 9, 25),
(109, 'Hyotei', 'Traditional tea house', 9, 25),
(110, 'Fujiya 1935', 'Osaka modern kaiseki', 9, 26),
(111, 'Hajime', 'Osaka avant-garde', 9, 26),
(112, 'Eleven Madison Park', 'Plant-based fine dining', 10, 27),
(113, 'Peter Luger', 'Classic steakhouse', 10, 27),
(114, 'Providence', 'Seafood LA', 10, 28),
(115, 'Bestia', 'Italian LA', 10, 28),
(116, 'é by José Andrés', 'Spanish tasting menu', 10, 29),
(117, 'Sparrow + Wolf', 'Global small plates', 10, 29),
(118, 'The Ledbury', 'Modern British', 11, 30),
(119, 'St. John', 'Nose-to-tail dining', 11, 30),
(120, 'The Kitchin', 'Scottish seasonal', 11, 31),
(121, 'Tower Restaurant', 'Edinburgh castle view', 11, 31),
(122, 'Mana', 'Manchester plant-based', 11, 32),
(123, 'Hawksmoor Manchester', 'Steakhouse', 11, 32),
(124, 'Rutz', 'Berlin modern German', 12, 33),
(125, 'Nobelhart & Schmutzig', 'Regional Berlin', 12, 33),
(126, 'Atelier', 'Munich gourmet', 12, 34),
(127, 'Brenner Grill', 'Munich steakhouse', 12, 34),
(128, 'Küchengarten', 'Hamburg local cuisine', 12, 35),
(129, 'Fischereihafen', 'Seafood Hamburg', 12, 35),
(130, 'Nómad', 'Marrakech rooftop', 13, 36),
(131, 'Dar Yacout', 'Traditional palace dining', 13, 36),
(132, 'The Ruined Garden', 'Fez medina garden', 13, 37),
(133, 'Dar Hatim', 'Fez traditional', 13, 37),
(134, 'Le Cabestan', 'Casablanca ocean view', 13, 38),
(135, 'La Sqala', 'Casablanca garden cafe', 13, 38),
(136, 'Tawaheen Al Hawa', 'Amman traditional', 14, 39),
(137, 'Sufra', 'Jordanian home cooking', 14, 39),
(138, 'Al Qantarah', 'Petra fine dining', 14, 40),
(139, 'My Mom\'s Recipe', 'Petra homestyle', 14, 40),
(140, 'Fish in Aqaba', 'Red Sea seafood', 14, 41),
(141, 'Rakwet Kanaan', 'Aqaba local cuisine', 14, 41),
(142, 'Fasano', 'Italian-Brazilian fine dining', 16, 45),
(143, 'Olympe', 'Brazilian contemporary', 16, 45),
(144, 'Maní', 'São Paulo garden restaurant', 16, 46),
(145, 'A Casa do Porco', 'Pork specialties', 16, 46),
(146, 'Amado', 'Bahian seafood', 16, 47),
(147, 'Casa do Amarelindo', 'Colonial Salvador', 16, 47),
(148, 'Bennelong', 'Sydney Opera House', 17, 48),
(149, 'Tetsuya\'s', 'Japanese-Australian fusion', 17, 48),
(150, 'Brae', 'Regional Victoria', 17, 49),
(151, 'Vue de Monde', 'Melbourne panoramic', 17, 49),
(152, 'Urbane', 'Brisbane fine dining', 17, 50),
(153, 'The Long Apron', 'Sunshine Coast style', 17, 50),
(154, 'Alo', 'Toronto tasting menu', 18, 51),
(155, 'Buca', 'Italian Toronto', 18, 51),
(156, 'St. Lawrence', 'Vancouver oysters', 18, 52),
(157, 'Kissa Tanto', 'Japanese-Italian', 18, 52),
(158, 'Le Mousso', 'Montreal creative', 18, 53),
(159, 'Joe Beef', 'Montreal classic', 18, 53),
(160, 'Quintonil', 'Mexico City modern', 19, 54),
(161, 'Sud777', 'Mexican avant-garde', 19, 54),
(162, 'Mocorito', 'Cancún seafood', 19, 55),
(163, 'Porfirio\'s', 'Cancún steakhouse', 19, 55),
(164, 'Lula Bistro', 'Guadalajara contemporary', 19, 56),
(165, 'Hueso', 'Guadalajara unique decor', 19, 56),
(166, 'Bukhara', 'Northwest frontier', 20, 57),
(167, 'Karim\'s', 'Mughlai Delhi', 20, 57),
(168, 'Masala Library', 'Mumbai molecular', 20, 58),
(169, 'Gajalee', 'Mumbai seafood', 20, 58),
(170, 'Rambagh Palace', 'Royal Jaipur dining', 20, 59),
(171, 'Niros', 'Jaipur traditional', 20, 59),
(172, 'Bait Al Mandi', NULL, 3, 60),
(173, 'Al Qubtan', NULL, 3, 60),
(174, 'The Cheesecake Factory', NULL, 3, 60),
(175, 'Dom Tatarskoy Kukhni', NULL, 21, 61),
(176, 'Chak-Chak Cafe', NULL, 21, 61),
(177, 'Rubin', NULL, 21, 61),
(178, 'Cococo', NULL, 21, 62),
(179, 'Clean Plates Society', NULL, 21, 62),
(180, 'Mansarda', NULL, 21, 62),
(181, 'Pushkin', NULL, 21, 63),
(182, 'Twins Garden', NULL, 21, 63),
(183, 'White Rabbit', NULL, 21, 63),
(184, 'Chen Mapo Tofu', NULL, 22, 64),
(185, 'Huo Yan Sichuan Kitchen', NULL, 22, 64),
(186, 'Yulin Chuan Chuan', NULL, 22, 64),
(187, 'Jesse Restaurant', NULL, 22, 65),
(188, 'Fu He Hui', NULL, 22, 65),
(189, 'Ultraviolet by Paul Pairet', NULL, 22, 65),
(190, 'Black Sesame Kitchen', NULL, 22, 66),
(191, 'Da Dong Roast Duck', NULL, 22, 66),
(192, 'Quanjude Peking Duck', NULL, 22, 66),
(193, 'Kesuma Restaurant', NULL, 23, 67),
(194, 'Gadjah Wong', NULL, 23, 67),
(195, 'The House of Raminten', NULL, 23, 67),
(196, 'Warung Babi Guling Ibu Oka', NULL, 23, 68),
(197, 'Swept Away', NULL, 23, 68),
(198, 'Locavore', NULL, 23, 68),
(199, 'Plataran Menteng', NULL, 23, 69),
(200, 'Seribu Rasa', NULL, 23, 69),
(201, 'Nusa Indonesian Gastronomy', NULL, 23, 69),
(202, 'Waterfront Danang', NULL, 24, 70),
(203, 'Madame Lan', NULL, 24, 70),
(204, 'La Maison 1888', NULL, 24, 70),
(205, 'Ngon Restaurant', NULL, 24, 71),
(206, 'Anan Saigon', NULL, 24, 71),
(207, 'The Deck Saigon', NULL, 24, 71),
(208, 'La Verticale', NULL, 24, 72),
(209, 'Bun Cha Huong Lien', NULL, 24, 72),
(210, 'Tung\'s Kitchen', NULL, 24, 72),
(211, 'Ollae Guksu', NULL, 25, 73),
(212, 'Jeju Dongmun Night Market', NULL, 25, 73),
(213, 'Black Pork Street', NULL, 25, 73),
(214, 'Milak Raw Fish Town', NULL, 25, 74),
(215, 'Gijang Crab Village', NULL, 25, 74),
(216, 'Samwon Garden', NULL, 25, 74),
(217, 'Gwangjang Market Food Stalls', NULL, 25, 75),
(218, 'Mingles', NULL, 25, 75),
(219, 'Jungsik', NULL, 25, 75),
(220, 'Taska', NULL, 26, 76),
(221, 'O Castelo', NULL, 26, 76),
(222, 'A Ria', NULL, 26, 76),
(223, 'O Gaveto', NULL, 26, 77),
(224, 'Casa Guedes', NULL, 26, 77),
(225, 'DOP', NULL, 26, 77),
(226, 'Time Out Market', NULL, 26, 78),
(227, 'Cervejaria Ramiro', NULL, 26, 78),
(228, 'Belcanto', NULL, 26, 78),
(229, 'Kijkduin', NULL, 27, 79),
(230, 'SET Genieten', NULL, 27, 79),
(231, 'Callas', NULL, 27, 79),
(232, 'Bazar', NULL, 27, 80),
(233, 'Heroine', NULL, 27, 80),
(234, 'Fitzgerald', NULL, 27, 80),
(235, 'Moeders', NULL, 27, 81),
(236, 'Foodhallen', NULL, 27, 81),
(237, 'De Librije', NULL, 27, 81),
(238, 'Baren', NULL, 28, 82),
(239, 'Husi Bierhaus', NULL, 28, 82),
(240, 'Taverne', NULL, 28, 82),
(241, 'Le Chat-Botte', NULL, 28, 83),
(242, 'Cafe du Soleil', NULL, 28, 83),
(243, 'Bayview', NULL, 28, 83),
(244, 'Hiltl', NULL, 28, 84),
(245, 'Zeughauskeller', NULL, 28, 84),
(246, 'Kronenhalle', NULL, 28, 84),
(247, 'Saltic', NULL, 29, 85),
(248, 'Bastard', NULL, 29, 85),
(249, 'Vollmers', NULL, 29, 85),
(250, 'Fiskekrogen', NULL, 29, 86),
(251, 'Sjömagasinet', NULL, 29, 86),
(252, 'Thörnströms Kök', NULL, 29, 86),
(253, 'Tradition', NULL, 29, 87),
(254, 'Oaxen Slip', NULL, 29, 87),
(255, 'Frantzén', NULL, 29, 87),
(256, 'Hildr Gastro Bar', NULL, 30, 88),
(257, 'Fiskekompaniet', NULL, 30, 88),
(258, 'Smak', NULL, 30, 88),
(259, 'Bare Vestland', NULL, 30, 89),
(260, 'Fisketorget', NULL, 30, 89),
(261, 'Lysverket', NULL, 30, 89),
(262, 'Ekebergrestauranten', NULL, 30, 90),
(263, 'Kontrast', NULL, 30, 90),
(264, 'Maaemo', NULL, 30, 90),
(265, 'Cafe Lattakia', NULL, 31, 91),
(266, 'Rotana Beach', NULL, 31, 91),
(267, 'Al Khayyam', NULL, 31, 91),
(268, 'Al-Jazar', NULL, 31, 92),
(269, 'Al-Sultan', NULL, 31, 92),
(270, 'Sissi', NULL, 31, 92),
(271, 'Beit Sitti', NULL, 31, 93),
(272, 'Abu Kamal', NULL, 31, 93),
(273, 'Naranj', NULL, 31, 93),
(274, 'El Rodeo', NULL, 32, 94),
(275, 'La Huerta', NULL, 32, 94),
(276, 'Finca La Cuarta', NULL, 32, 94),
(277, 'Palacio de los Mariscos', NULL, 32, 95),
(278, 'El Olivo', NULL, 32, 95),
(279, 'La Casa de la Cachapa', NULL, 32, 95),
(280, 'Pimiento', NULL, 32, 96),
(281, 'El Coche', NULL, 32, 96),
(282, 'Alta Vista', NULL, 32, 96),
(302, 'Tegui', NULL, 33, 124),
(303, 'El Preferido de Palermo', NULL, 33, 124),
(304, 'Don Julio', NULL, 33, 124),
(305, 'La Parrilla del Patio', NULL, 33, 125),
(306, 'Alcazar', NULL, 33, 125),
(307, 'El Celta', NULL, 33, 125),
(308, 'Siete Cocinas', NULL, 33, 126),
(309, 'La Marchigiana', NULL, 33, 126),
(310, 'Azafran', NULL, 33, 126),
(311, 'nnjknkj k', NULL, 17, 48);

-- --------------------------------------------------------

--
-- Table structure for table `tours`
--

CREATE TABLE `tours` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `duration_days` int(11) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `booking_deadline` date DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tours`
--

INSERT INTO `tours` (`id`, `office_id`, `title`, `description`, `price`, `currency`, `duration_days`, `max_participants`, `start_date`, `end_date`, `booking_deadline`, `payment_type`, `main_image`, `status`, `views`, `created_at`, `updated_at`) VALUES
(6, 6, 'Norway', 'Be many', 2000.00, 'USD', 14, 25, '2026-05-05', '2026-05-24', '2026-04-30', 'cash', 'uploads/tours/1775421700_69d2c9048251d.jpg', 'approved', 0, '2026-04-05 20:41:40', '2026-04-22 16:48:05'),
(7, 6, 'Brazil', '', 2500.00, 'USD', 7, 20, '2026-04-05', '2026-04-12', '2026-03-31', 'bank', 'uploads/tours/1775422324_69d2cb743f340.jpg', 'approved', 0, '2026-04-05 20:52:04', '2026-04-22 16:38:38'),
(8, 4, 'Norway', 'Be Many', 2700.00, 'USD', 10, 10, '2026-11-11', '2026-11-21', '2026-11-01', 'online', 'uploads/tours/1775664332_69d67ccc10400.jpg', 'approved', 0, '2026-04-05 20:57:21', '2026-04-22 16:38:35'),
(9, 4, 'Russia', '', 3000.00, 'EUR', 15, 20, '2026-07-15', '2026-07-30', '2026-07-04', 'bank', 'uploads/tours/1775664038_69d67ba675d88.jpg', 'approved', 0, '2026-04-05 21:02:16', '2026-04-22 16:38:31'),
(11, 4, 'Egypt', '', 1500.00, 'EGP', 15, 10, '2026-04-15', '2026-04-30', '2026-04-09', NULL, 'uploads/tours/1775664084_69d67bd4b45a3.jpg', 'approved', 0, '2026-04-05 21:08:00', '2026-04-22 16:37:51'),
(12, 3, 'Canada', '', 3000.00, 'USD', 15, 10, '2026-05-05', '2026-05-20', '2026-04-30', 'online', 'uploads/tours/1775423452_69d2cfdc53daa.jpg', 'approved', 0, '2026-04-05 21:10:52', '2026-04-22 16:38:29'),
(13, 3, 'Italy', '', 3500.00, 'EUR', 10, 12, '2026-04-10', '2026-04-20', '2026-04-06', 'online', 'uploads/tours/1775664466_69d67d5255d41.jpg', 'approved', 0, '2026-04-05 21:15:55', '2026-04-22 16:38:25'),
(14, 3, 'Venezuela', '', 4000.00, 'USD', 30, 10, '2026-06-01', '2026-06-30', '2026-05-27', 'cash', 'uploads/tours/1775664369_69d67cf162bbd.jpg', 'approved', 0, '2026-04-05 21:19:09', '2026-04-22 16:38:22'),
(15, 6, 'Syria', '', 2000.00, 'USD', 15, 15, '2026-04-30', '2026-05-15', '2026-04-25', 'online', 'uploads/tours/1775664506_69d67d7ae6517.jpg', 'approved', 0, '2026-04-05 21:23:26', '2026-04-22 16:38:20'),
(16, 4, 'USA', '', 3000.00, 'USD', 10, 14, '2026-10-01', '2026-10-10', '2026-09-26', 'cash', 'uploads/tours/1775424523_69d2d40ba9969.jpg', 'approved', 0, '2026-04-05 21:28:43', '2026-04-22 16:38:16'),
(19, 4, 'vaitnam', '', 2000.00, 'USD', 10, 10, '2026-04-20', '2026-04-30', '2026-04-15', 'cash', 'uploads/tours/1776279352_69dfdf38d4ee4.jpg', 'approved', 0, '2026-04-15 18:55:52', '2026-04-22 16:37:58'),
(20, 4, 'paris', '', 3000.00, 'USD', 10, 10, '2026-04-20', '2026-04-30', '2026-04-15', 'cash', 'uploads/tours/1776281546_69dfe7caee09a.jpg', 'approved', 0, '2026-04-15 19:32:26', '2026-04-22 16:37:55'),
(21, 3, 'Indonesia', '', 3000.00, 'USD', 10, 10, '2026-07-20', '2026-07-30', '2026-07-15', 'online', 'uploads/tours/1776870061_69e8e2ad7e52b.jpg', 'approved', 0, '2026-04-22 15:01:01', '2026-04-22 16:37:46'),
(30, 3, 'ىتنننننننننننننننن', '', 2999.89, 'USD', 10, 10, '2026-07-20', '2026-07-30', '2026-07-15', 'card', 'uploads/tours/1776945817_69ea0a9998cea.jpg', 'pending', 0, '2026-04-23 12:03:37', '2026-04-23 12:03:37'),
(31, 3, 'mmmm', '', 200.00, 'EUR', 12, 20, NULL, NULL, NULL, NULL, '', 'pending', 0, '2026-05-17 17:21:16', '2026-05-17 17:21:16');

-- --------------------------------------------------------

--
-- Table structure for table `tour_city`
--

CREATE TABLE `tour_city` (
  `tour_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `days` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tour_city`
--

INSERT INTO `tour_city` (`tour_id`, `city_id`, `days`) VALUES
(6, 90, 1),
(7, 45, 1),
(8, 89, 1),
(9, 61, 1),
(11, 4, 1),
(12, 51, 1),
(13, 9, 1),
(14, 94, 1),
(15, 93, 1),
(16, 27, 1),
(19, 71, 1),
(20, 12, 1),
(21, 68, 1),
(30, 126, 1),
(31, 47, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tour_hotel`
--

CREATE TABLE `tour_hotel` (
  `tour_id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tour_hotel`
--

INSERT INTO `tour_hotel` (`tour_id`, `hotel_id`) VALUES
(6, 263),
(6, 264),
(7, 45),
(8, 260),
(9, 177),
(11, 67),
(12, 155),
(13, 9),
(14, 274),
(15, 273),
(16, 113),
(19, 205),
(20, 311),
(21, 196),
(30, 308),
(31, 146),
(31, 147);

-- --------------------------------------------------------

--
-- Table structure for table `tour_landmark`
--

CREATE TABLE `tour_landmark` (
  `tour_id` int(11) NOT NULL,
  `landmark_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tour_landmark`
--

INSERT INTO `tour_landmark` (`tour_id`, `landmark_id`) VALUES
(6, 262),
(6, 264),
(7, 143),
(8, 260),
(9, 175),
(9, 176),
(11, 4),
(12, 154),
(13, 76),
(14, 276),
(15, 273),
(16, 27),
(16, 113),
(19, 207),
(20, 12),
(21, 196),
(30, 308);

-- --------------------------------------------------------

--
-- Table structure for table `tour_restaurant`
--

CREATE TABLE `tour_restaurant` (
  `tour_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tour_restaurant`
--

INSERT INTO `tour_restaurant` (`tour_id`, `restaurant_id`) VALUES
(6, 263),
(6, 264),
(7, 45),
(8, 259),
(9, 177),
(11, 67),
(12, 155),
(13, 9),
(14, 275),
(15, 272),
(16, 112),
(19, 206),
(20, 82),
(21, 197),
(30, 310);

-- --------------------------------------------------------

--
-- Table structure for table `tour_stations`
--

CREATE TABLE `tour_stations` (
  `id` int(11) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `station_type` enum('hotel','restaurant','landmark','custom') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `custom_name` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `station_order` int(11) DEFAULT 0,
  `extra_info` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tour_stations`
--

INSERT INTO `tour_stations` (`id`, `tour_id`, `station_type`, `reference_id`, `custom_name`, `image_path`, `description`, `station_order`, `extra_info`, `created_at`) VALUES
(8, 6, 'hotel', 263, 'Grand Hotel Oslo', 'uploads/stations/1775422144_69d2cac0821bd.jpg', '7 night', 1, '\"One of the most luxurious hotels in Norway.', '2026-04-05 20:49:04'),
(9, 6, 'restaurant', 263, 'Kontrast', 'uploads/stations/1775422144_69d2cac082928.jpg', 'For breakfast, lunch, and dinner', 2, 'it contains all kinds of delicious dishes.', '2026-04-05 20:49:04'),
(10, 6, 'landmark', 263, 'Fram Museum', 'uploads/stations/1775422144_69d2cac08338a.jpg', 'One of the largest museums in the world', 3, '', '2026-04-05 20:49:04'),
(11, 6, 'landmark', 262, 'Oslo Opera House', 'uploads/stations/1775422144_69d2cac083d2f.jpg', ' To go back to the good old days.', 4, '', '2026-04-05 20:49:04'),
(12, 7, 'restaurant', 143, 'Olympe', 'uploads/stations/1775422421_69d2cbd5b6e9f.jpg', '', 1, '', '2026-04-05 20:53:41'),
(13, 7, 'hotel', 142, 'Fairmont Rio', 'uploads/stations/1775422421_69d2cbd5b7577.jpg', '', 2, '', '2026-04-05 20:53:41'),
(14, 7, 'landmark', 143, 'Copacabana Beach', 'uploads/stations/1775422421_69d2cbd5b7a88.jpg', '', 3, '', '2026-04-05 20:53:41'),
(15, 8, 'hotel', 260, 'Clarion Hotel Admiral', 'uploads/stations/1775422769_69d2cd31ac7e2.jpg', 'Overnight ', 1, '', '2026-04-05 20:59:29'),
(16, 8, 'restaurant', 259, 'Bare Vestland', 'uploads/stations/1775422769_69d2cd31ad9d9.jpg', '', 2, '', '2026-04-05 20:59:29'),
(17, 8, 'landmark', 260, 'Fløyen Mountain', 'uploads/stations/1775422769_69d2cd31b0897.jpg', '', 3, '', '2026-04-05 20:59:29'),
(18, 9, 'hotel', 177, 'Ramada Kazan City Centre', 'uploads/stations/1775423043_69d2ce4309628.jpg', 'Overnight', 1, '', '2026-04-05 21:04:03'),
(19, 9, 'restaurant', 177, 'Rubin', 'uploads/stations/1775423043_69d2ce430a121.jpg', '', 2, '', '2026-04-05 21:04:03'),
(20, 9, 'landmark', 176, 'Qol Sharif Mosque', 'uploads/stations/1775423043_69d2ce430b7a6.jpg', '', 3, '', '2026-04-05 21:04:03'),
(21, 9, 'landmark', 175, 'Baumana Street', 'uploads/stations/1775423043_69d2ce430bda2.jpg', '', 4, '', '2026-04-05 21:04:03'),
(22, 11, 'hotel', 67, 'Kempinski Nile Hotel', 'uploads/stations/1775423336_69d2cf684fa27.jpg', '', 1, '', '2026-04-05 21:08:56'),
(23, 11, 'restaurant', 67, 'Naguib Mahfouz Café', 'uploads/stations/1775423336_69d2cf6851c8c.jpg', '', 2, '', '2026-04-05 21:08:56'),
(24, 11, 'landmark', 4, 'Pyramids of Giza', 'uploads/stations/1775423336_69d2cf685246f.jpg', '', 3, '', '2026-04-05 21:08:56'),
(25, 12, 'hotel', 155, 'Four Seasons Toronto', 'uploads/stations/1775423553_69d2d041c5eb2.jpg', '', 1, '', '2026-04-05 21:12:33'),
(26, 12, 'restaurant', 155, 'Buca', 'uploads/stations/1775423553_69d2d041c67a7.jpg', '', 2, '', '2026-04-05 21:12:33'),
(27, 12, 'landmark', 154, 'Niagara Falls', 'uploads/stations/1775423553_69d2d041c6dc2.jpg', '', 3, '', '2026-04-05 21:12:33'),
(28, 13, 'restaurant', 9, 'La Pergola', 'uploads/stations/1775423822_69d2d14e93c65.jpg', '', 1, '', '2026-04-05 21:17:02'),
(29, 13, 'hotel', 9, 'Hotel de Russie', 'uploads/stations/1775423822_69d2d14e94d2d.jpg', '', 2, '', '2026-04-05 21:17:02'),
(30, 13, 'landmark', 76, 'Roman Forum', 'uploads/stations/1775423822_69d2d14e961f4.jpg', '', 3, '', '2026-04-05 21:17:02'),
(31, 14, 'hotel', 274, 'Hotel El Rodeo', 'uploads/stations/1775424020_69d2d21436455.jpg', '', 1, '', '2026-04-05 21:20:20'),
(32, 14, 'restaurant', 275, 'La Huerta', 'uploads/stations/1775424020_69d2d21437443.jpg', '', 2, '', '2026-04-05 21:20:20'),
(33, 14, 'landmark', 276, 'Valencia Aquarium', 'uploads/stations/1775424020_69d2d21437aaf.jpg', '', 3, '', '2026-04-05 21:20:20'),
(34, 15, 'hotel', 273, 'Four Seasons Damascus', 'uploads/stations/1775664526_69d67d8e391e6.jpg', '', 1, '', '2026-04-05 21:24:37'),
(35, 15, 'restaurant', 272, 'Abu Kamal', 'uploads/stations/1775424277_69d2d31515018.jpg', '', 2, '', '2026-04-05 21:24:37'),
(36, 15, 'landmark', 273, 'Umayyad Mosque', 'uploads/stations/1775664552_69d67da8f104a.jpg', '', 3, '', '2026-04-05 21:24:37'),
(37, 16, 'landmark', 27, 'Statue of Liberty', 'uploads/stations/1775424682_69d2d4aa90992.jpg', '', 1, '', '2026-04-05 21:31:22'),
(38, 16, 'hotel', 112, 'The Ritz-Carlton Central Park', 'uploads/stations/1775424682_69d2d4aa91051.jpg', '', 2, '', '2026-04-05 21:31:22'),
(39, 16, 'restaurant', 113, 'Peter Luger', 'uploads/stations/1775424682_69d2d4aa925f2.jpg', '', 3, '', '2026-04-05 21:31:22'),
(40, 16, 'landmark', 112, 'Times Square', 'uploads/stations/1775424682_69d2d4aa92a6b.jpg', '', 4, '', '2026-04-05 21:31:22'),
(42, 20, 'hotel', 311, 'lutetia paris', 'uploads/stations/1776281691_69dfe85b551c6.jpg', '', 1, '', '2026-04-15 19:34:51'),
(43, 20, 'landmark', 12, 'Eiffel Tower', 'uploads/stations/1776281691_69dfe85b56040.jpg', '', 3, '', '2026-04-15 19:34:51'),
(44, 20, 'landmark', 83, 'Arc de Triomphe', 'uploads/stations/1776281691_69dfe85b566f0.jpg', '', 4, '', '2026-04-15 19:34:51'),
(45, 21, 'hotel', 196, 'Grand Hyatt Bali', 'uploads/stations/1776870153_69e8e30932ef1.jpg', '', 1, '', '2026-04-22 15:02:33'),
(46, 21, 'restaurant', 197, 'Swept Away', 'uploads/stations/1776870153_69e8e30933853.jpg', '', 2, '', '2026-04-22 15:02:33'),
(47, 21, 'landmark', 196, 'Tegalalang Rice Terrace', 'uploads/stations/1776870153_69e8e30933eca.jpg', '', 3, '', '2026-04-22 15:02:33'),
(53, 30, 'restaurant', 309, 'La Marchigiana', 'uploads/stations/1776945829_69ea0aa59592a.jpg', '', 1, '', '2026-04-23 12:03:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','office_admin','user') DEFAULT 'user',
  `office_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `warning_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `avatar`, `role`, `office_id`, `is_active`, `warning_count`, `created_at`, `updated_at`, `last_login`) VALUES
(5, 'milya', 'milyaballan1@gmail.com', '$2y$10$ksAwdm1WGYLYzgNv652qFOVA8o8MYFqHYLIC0EtHKygqaLNHhqBAe', '0984155287', '', 'super_admin', NULL, 1, 0, '2026-03-23 08:15:36', '2026-07-01 10:32:30', '2026-07-01 10:32:30'),
(6, 'sami ahmed', 'sami@gmail.com', '$2y$10$O0XA47.qStx8wx/Kj.WkW.DRLwOQ3CIRk8kPXvrr15X1Hp6J6B8hy', '0984155287', '../uploads/avatars/1775424730_69d2d4da738d0.jpg', 'user', NULL, 1, 0, '2026-03-29 10:07:50', '2026-04-11 08:52:17', '2026-04-11 08:52:17'),
(7, 'noor', 'noor12@gmail.com', '$2y$10$jzEb/XZoD52QPlbks.ObP.TtXWGjcqRs8alsQdboG5MTZ8CebmhEG', '0984155287', '../uploads/avatars/1775424829_69d2d53d31915.jpg', 'user', NULL, 1, 0, '2026-04-01 11:04:43', '2026-05-17 17:18:16', '2026-05-17 17:18:16'),
(8, 'layan ballan ', 'layanballan@gmail.com', '$2y$10$cWE4/9KvqixIKno3sWWSiu7UhKt9CAS0w6NmAe5ZkEA.JkSHLoPkq', '0984729016', '', 'office_admin', 3, 1, 0, '2026-04-05 18:48:01', '2026-07-01 10:31:32', '2026-07-01 10:31:32'),
(9, 'sultan abu latif', 'sultan@gmail.com', '$2y$10$U1dnAhMV2ZlKnMASS9PQbOcbWbHdpeV/q4AeM6y26z/pJX57mCYrm', '0968261498', '', 'office_admin', 4, 1, 0, '2026-04-05 18:55:12', '2026-04-17 14:35:55', '2026-04-17 14:35:55'),
(10, 'sanaa sharf aldin', 'sanaasharf@gmail.com', '$2y$10$yULhcdVeBYpk/7uuT7ycaeDpa5ePZ.AT0rGkB8GPSolCpfcb35RVO', '0984155287', '', 'office_admin', 5, 0, 0, '2026-04-05 18:58:52', '2026-04-05 18:58:52', NULL),
(11, 'lia neam ', 'lianeam@gmail.com', '$2y$10$srBqAJ4D.QxyM2EcJFr2JOdLslAO1xiPoCwPE.cBuzVWW2yw092a2', '0986923287', '', 'office_admin', 6, 1, 0, '2026-04-05 20:23:56', '2026-04-08 16:08:00', '2026-04-08 16:08:00'),
(12, 'yara', 'yara@gmail.com', '$2y$10$YF.RFAF09pwdJomPuEdfJe2kRkMvcPkNgV/9EeYORMgvozVMD/7hS', '0984155287', '../uploads/avatars/1775424950_69d2d5b600d9b.jpg', 'user', NULL, 1, 0, '2026-04-05 21:35:50', '2026-04-15 18:14:14', '2026-04-15 18:14:14'),
(13, 'yazan', 'yazan@agmail.com', '$2y$10$94tIfKCBBN1crm6fdYixteQLtDIC8KxsDr0hmZP7l083ub3AOgX2W', '0982314567', '../uploads/avatars/1775425254_69d2d6e6227c1.jpg', 'user', NULL, 1, 0, '2026-04-05 21:40:54', '2026-09-05 17:27:53', '2026-09-05 17:27:53'),
(14, 'samira', 'balanmilya@gmail.com', '$2y$10$ZcWMzPzPhvQcJt11HH5NMetxyEpyU31xRXu8GVksRfiSyq49LMQuC', '0984155287', '../uploads/avatars/1776162578_69de171297492.jpg', 'user', NULL, 1, 0, '2026-04-14 10:29:38', '2026-07-01 10:28:57', '2026-07-01 10:28:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_number` (`booking_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `item_type` (`item_type`,`item_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `comment_reactions`
--
ALTER TABLE `comment_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_comment_reaction` (`user_id`,`comment_id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `reaction_type_id` (`reaction_type_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_conversations`
--
ALTER TABLE `deleted_conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_deletion` (`user_id`,`other_user_id`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`followable_type`,`followable_id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `landmarks`
--
ALTER TABLE `landmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `office_last_viewed`
--
ALTER TABLE `office_last_viewed`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `commentable_type` (`commentable_type`,`commentable_id`);

--
-- Indexes for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`user_id`,`post_id`,`reaction_type_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_rate` (`user_id`,`rateable_type`,`rateable_id`);

--
-- Indexes for table `reaction_types`
--
ALTER TABLE `reaction_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `tours`
--
ALTER TABLE `tours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `office_id` (`office_id`);

--
-- Indexes for table `tour_city`
--
ALTER TABLE `tour_city`
  ADD PRIMARY KEY (`tour_id`,`city_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `tour_hotel`
--
ALTER TABLE `tour_hotel`
  ADD PRIMARY KEY (`tour_id`,`hotel_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `tour_landmark`
--
ALTER TABLE `tour_landmark`
  ADD PRIMARY KEY (`tour_id`,`landmark_id`),
  ADD KEY `landmark_id` (`landmark_id`);

--
-- Indexes for table `tour_restaurant`
--
ALTER TABLE `tour_restaurant`
  ADD PRIMARY KEY (`tour_id`,`restaurant_id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `tour_stations`
--
ALTER TABLE `tour_stations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tour_id` (`tour_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `office_id` (`office_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `comment_reactions`
--
ALTER TABLE `comment_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `deleted_conversations`
--
ALTER TABLE `deleted_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `follows`
--
ALTER TABLE `follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=312;

--
-- AUTO_INCREMENT for table `landmarks`
--
ALTER TABLE `landmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=311;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `post_reactions`
--
ALTER TABLE `post_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `reaction_types`
--
ALTER TABLE `reaction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=312;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `tour_stations`
--
ALTER TABLE `tour_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_reactions`
--
ALTER TABLE `comment_reactions`
  ADD CONSTRAINT `comment_reactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_reactions_ibfk_2` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_reactions_ibfk_3` FOREIGN KEY (`reaction_type_id`) REFERENCES `reaction_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotels`
--
ALTER TABLE `hotels`
  ADD CONSTRAINT `hotels_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hotels_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `landmarks`
--
ALTER TABLE `landmarks`
  ADD CONSTRAINT `landmarks_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `landmarks_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `restaurants_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tours`
--
ALTER TABLE `tours`
  ADD CONSTRAINT `tours_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tour_city`
--
ALTER TABLE `tour_city`
  ADD CONSTRAINT `tour_city_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tour_city_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tour_hotel`
--
ALTER TABLE `tour_hotel`
  ADD CONSTRAINT `tour_hotel_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tour_hotel_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tour_landmark`
--
ALTER TABLE `tour_landmark`
  ADD CONSTRAINT `tour_landmark_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tour_landmark_ibfk_2` FOREIGN KEY (`landmark_id`) REFERENCES `landmarks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tour_restaurant`
--
ALTER TABLE `tour_restaurant`
  ADD CONSTRAINT `tour_restaurant_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tour_restaurant_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tour_stations`
--
ALTER TABLE `tour_stations`
  ADD CONSTRAINT `tour_stations_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
