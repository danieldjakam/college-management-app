-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : sam. 13 sep. 2025 à 14:06
-- Version du serveur : 10.11.11-MariaDB-0+deb12u1
-- Version de PHP : 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `c0admin`
--

-- --------------------------------------------------------

--
-- Structure de la table `academic_periods`
--

CREATE TABLE `academic_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `order` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `academic_system_config`
--

CREATE TABLE `academic_system_config` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('semester','trimester') NOT NULL DEFAULT 'trimester',
  `periods_count` int(11) NOT NULL DEFAULT 3,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `supervisor_id` bigint(20) UNSIGNED NOT NULL,
  `school_class_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `scanned_at` time NOT NULL,
  `is_present` tinyint(1) NOT NULL DEFAULT 1,
  `event_type` enum('entry','exit') NOT NULL DEFAULT 'entry',
  `parent_notified` tinyint(1) NOT NULL DEFAULT 0,
  `notified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `card_templates`
--

CREATE TABLE `card_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `class_payment_amounts`
--

CREATE TABLE `class_payment_amounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `payment_tranche_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `class_payment_amounts`
--

INSERT INTO `class_payment_amounts` (`id`, `class_id`, `payment_tranche_id`, `amount`, `is_required`, `created_at`, `updated_at`) VALUES
(73, 13, 2, 31000.00, 1, '2025-08-04 02:56:10', '2025-08-04 02:56:10'),
(74, 13, 3, 42000.00, 1, '2025-08-04 02:56:10', '2025-08-04 02:56:10'),
(75, 13, 4, 20000.00, 1, '2025-08-04 02:56:10', '2025-08-04 02:56:10'),
(76, 13, 5, 10000.00, 1, '2025-08-04 02:56:10', '2025-08-04 02:56:10'),
(77, 14, 2, 31000.00, 1, '2025-08-04 02:57:43', '2025-08-04 02:57:43'),
(78, 14, 3, 42000.00, 1, '2025-08-04 02:57:43', '2025-08-04 02:57:43'),
(79, 14, 4, 20000.00, 1, '2025-08-04 02:57:43', '2025-08-04 02:57:43'),
(80, 14, 5, 10000.00, 1, '2025-08-04 02:57:43', '2025-08-04 02:57:43'),
(81, 15, 2, 31000.00, 1, '2025-08-04 02:59:49', '2025-08-04 02:59:49'),
(82, 15, 3, 42000.00, 1, '2025-08-04 02:59:49', '2025-08-04 02:59:49'),
(83, 15, 4, 20000.00, 1, '2025-08-04 02:59:49', '2025-08-04 02:59:49'),
(84, 15, 5, 10000.00, 1, '2025-08-04 02:59:49', '2025-08-04 02:59:49'),
(85, 16, 2, 31000.00, 1, '2025-08-04 03:01:56', '2025-08-04 03:01:56'),
(86, 16, 3, 57000.00, 1, '2025-08-04 03:01:56', '2025-08-04 03:01:56'),
(87, 16, 4, 20000.00, 1, '2025-08-04 03:01:56', '2025-08-04 03:01:56'),
(88, 16, 5, 10000.00, 1, '2025-08-04 03:01:56', '2025-08-04 03:01:56'),
(105, 21, 2, 31000.00, 1, '2025-08-04 07:19:38', '2025-08-04 07:19:38'),
(106, 21, 3, 57000.00, 1, '2025-08-04 07:19:38', '2025-08-04 07:19:38'),
(107, 21, 4, 20000.00, 1, '2025-08-04 07:19:38', '2025-08-04 07:19:38'),
(108, 21, 5, 10000.00, 1, '2025-08-04 07:19:38', '2025-08-04 07:19:38'),
(109, 22, 2, 31000.00, 1, '2025-08-04 07:25:19', '2025-08-04 07:25:19'),
(110, 22, 3, 72000.00, 1, '2025-08-04 07:25:19', '2025-08-04 07:25:19'),
(111, 22, 4, 20000.00, 1, '2025-08-04 07:25:19', '2025-08-04 07:25:19'),
(112, 22, 5, 10000.00, 1, '2025-08-04 07:25:19', '2025-08-04 07:25:19'),
(113, 23, 2, 31000.00, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08'),
(114, 23, 3, 72000.00, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08'),
(115, 23, 4, 20000.00, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08'),
(116, 23, 5, 10000.00, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08'),
(117, 24, 2, 31000.00, 1, '2025-08-04 09:43:45', '2025-08-04 09:43:45'),
(118, 24, 3, 47000.00, 1, '2025-08-04 09:43:45', '2025-08-04 09:43:45'),
(119, 24, 4, 20000.00, 1, '2025-08-04 09:43:45', '2025-08-04 09:43:45'),
(120, 24, 5, 10000.00, 1, '2025-08-04 09:43:45', '2025-08-04 09:43:45'),
(121, 25, 2, 31000.00, 1, '2025-08-04 09:45:02', '2025-08-04 09:45:02'),
(122, 25, 3, 47000.00, 1, '2025-08-04 09:45:02', '2025-08-04 09:45:02'),
(123, 25, 4, 20000.00, 1, '2025-08-04 09:45:02', '2025-08-04 09:45:02'),
(124, 25, 5, 10000.00, 1, '2025-08-04 09:45:02', '2025-08-04 09:45:02'),
(125, 26, 2, 31000.00, 1, '2025-08-04 09:45:59', '2025-08-04 09:45:59'),
(126, 26, 3, 47000.00, 1, '2025-08-04 09:45:59', '2025-08-04 09:45:59'),
(127, 26, 4, 20000.00, 1, '2025-08-04 09:45:59', '2025-08-04 09:45:59'),
(128, 26, 5, 10000.00, 1, '2025-08-04 09:45:59', '2025-08-04 09:45:59'),
(129, 27, 2, 31000.00, 1, '2025-08-04 09:47:04', '2025-08-04 09:47:04'),
(130, 27, 3, 62000.00, 1, '2025-08-04 09:47:04', '2025-08-04 09:47:04'),
(131, 27, 4, 20000.00, 1, '2025-08-04 09:47:04', '2025-08-04 09:47:04'),
(132, 27, 5, 10000.00, 1, '2025-08-04 09:47:04', '2025-08-04 09:47:04'),
(133, 28, 2, 31000.00, 1, '2025-08-04 11:49:24', '2025-08-04 11:49:24'),
(134, 28, 3, 67000.00, 1, '2025-08-04 11:49:24', '2025-08-04 11:49:24'),
(135, 28, 4, 20000.00, 1, '2025-08-04 11:49:24', '2025-08-04 11:49:24'),
(136, 28, 5, 10000.00, 1, '2025-08-04 11:49:24', '2025-08-04 11:49:24'),
(137, 29, 2, 21000.00, 1, '2025-08-04 12:47:00', '2025-08-04 12:47:00'),
(138, 29, 3, 70000.00, 1, '2025-08-04 12:47:00', '2025-08-04 12:47:00'),
(139, 29, 4, 25000.00, 1, '2025-08-04 12:47:00', '2025-08-04 12:47:00'),
(140, 29, 5, 10000.00, 1, '2025-08-04 12:47:00', '2025-08-04 12:47:00'),
(141, 30, 2, 21000.00, 1, '2025-08-04 12:55:09', '2025-08-04 12:59:48'),
(142, 30, 3, 70000.00, 1, '2025-08-04 12:55:09', '2025-08-04 12:55:09'),
(143, 30, 4, 25000.00, 1, '2025-08-04 12:55:09', '2025-08-04 12:55:09'),
(144, 30, 5, 10000.00, 1, '2025-08-04 12:55:09', '2025-08-04 12:55:09'),
(145, 31, 2, 21000.00, 1, '2025-08-04 12:56:54', '2025-08-04 12:56:54'),
(146, 31, 3, 70000.00, 1, '2025-08-04 12:56:54', '2025-08-04 12:56:54'),
(147, 31, 4, 25000.00, 1, '2025-08-04 12:56:54', '2025-08-04 12:56:54'),
(148, 31, 5, 10000.00, 1, '2025-08-04 12:56:54', '2025-08-04 12:56:54'),
(149, 32, 2, 21000.00, 1, '2025-08-04 13:02:07', '2025-08-04 13:02:07'),
(150, 32, 3, 70000.00, 1, '2025-08-04 13:02:07', '2025-08-04 13:02:07'),
(151, 32, 4, 25000.00, 1, '2025-08-04 13:02:07', '2025-08-04 13:02:07'),
(152, 32, 5, 10000.00, 1, '2025-08-04 13:02:07', '2025-08-04 13:02:07'),
(153, 33, 2, 31000.00, 1, '2025-08-04 13:03:19', '2025-08-04 13:03:19'),
(154, 33, 3, 70000.00, 1, '2025-08-04 13:03:19', '2025-08-04 13:03:19'),
(155, 33, 4, 30000.00, 1, '2025-08-04 13:03:19', '2025-08-04 13:03:19'),
(156, 33, 5, 10000.00, 1, '2025-08-04 13:03:19', '2025-08-04 13:03:19'),
(157, 34, 2, 41000.00, 1, '2025-08-04 13:05:04', '2025-08-04 13:05:04'),
(158, 34, 3, 70000.00, 1, '2025-08-04 13:05:04', '2025-08-04 13:05:04'),
(159, 34, 4, 30000.00, 1, '2025-08-04 13:05:04', '2025-08-04 13:05:04'),
(160, 34, 5, 10000.00, 1, '2025-08-04 13:05:04', '2025-08-04 13:05:04'),
(161, 35, 2, 41000.00, 1, '2025-08-04 13:07:27', '2025-08-04 13:07:27'),
(162, 35, 3, 70000.00, 1, '2025-08-04 13:07:27', '2025-08-04 13:07:27'),
(163, 35, 4, 30000.00, 1, '2025-08-04 13:07:27', '2025-08-04 13:07:27'),
(164, 35, 5, 10000.00, 1, '2025-08-04 13:07:27', '2025-08-04 13:07:27'),
(165, 36, 2, 31000.00, 1, '2025-08-04 13:39:42', '2025-08-04 13:39:42'),
(166, 36, 3, 67000.00, 1, '2025-08-04 13:39:42', '2025-08-04 13:39:42'),
(167, 36, 4, 20000.00, 1, '2025-08-04 13:39:42', '2025-08-04 13:39:42'),
(168, 36, 5, 10000.00, 1, '2025-08-04 13:39:42', '2025-08-04 13:39:42'),
(169, 37, 2, 31000.00, 1, '2025-08-04 13:43:07', '2025-08-04 13:43:07'),
(170, 37, 3, 67000.00, 1, '2025-08-04 13:43:07', '2025-08-04 13:43:07'),
(171, 37, 4, 20000.00, 1, '2025-08-04 13:43:07', '2025-08-04 13:43:07'),
(172, 37, 5, 10000.00, 1, '2025-08-04 13:43:07', '2025-08-04 13:43:07'),
(173, 38, 2, 31000.00, 1, '2025-08-04 13:45:11', '2025-08-04 13:45:11'),
(174, 38, 3, 82000.00, 1, '2025-08-04 13:45:11', '2025-08-04 13:45:11'),
(175, 38, 4, 20000.00, 1, '2025-08-04 13:45:11', '2025-08-04 13:45:11'),
(176, 38, 5, 10000.00, 1, '2025-08-04 13:45:11', '2025-08-04 13:45:11'),
(177, 39, 2, 31000.00, 1, '2025-08-04 13:48:18', '2025-08-04 13:48:18'),
(178, 39, 3, 67000.00, 1, '2025-08-04 13:48:18', '2025-08-04 13:48:18'),
(179, 39, 4, 20000.00, 1, '2025-08-04 13:48:18', '2025-08-04 13:48:18'),
(180, 39, 5, 10000.00, 1, '2025-08-04 13:48:18', '2025-08-04 13:48:18'),
(181, 40, 2, 31000.00, 1, '2025-08-04 13:50:14', '2025-08-04 13:50:14'),
(182, 40, 3, 67000.00, 1, '2025-08-04 13:50:14', '2025-08-04 13:50:14'),
(183, 40, 4, 20000.00, 1, '2025-08-04 13:50:14', '2025-08-04 13:50:14'),
(184, 40, 5, 10000.00, 1, '2025-08-04 13:50:14', '2025-08-04 13:50:14'),
(185, 41, 2, 31000.00, 1, '2025-08-05 10:15:38', '2025-08-05 10:15:38'),
(186, 41, 3, 67000.00, 1, '2025-08-05 10:15:38', '2025-08-05 10:15:38'),
(187, 41, 4, 20000.00, 1, '2025-08-05 10:15:38', '2025-08-05 10:15:38'),
(188, 41, 5, 10000.00, 1, '2025-08-05 10:15:38', '2025-08-05 10:15:38'),
(189, 42, 2, 31000.00, 1, '2025-08-05 10:16:56', '2025-08-05 10:16:56'),
(190, 42, 3, 82000.00, 1, '2025-08-05 10:16:56', '2025-08-05 10:16:56'),
(191, 42, 4, 20000.00, 1, '2025-08-05 10:16:56', '2025-08-05 10:16:56'),
(192, 42, 5, 10000.00, 1, '2025-08-05 10:16:56', '2025-08-05 10:16:56'),
(193, 43, 2, 31000.00, 1, '2025-08-05 12:05:47', '2025-08-05 12:05:47'),
(194, 43, 3, 47000.00, 1, '2025-08-05 12:05:47', '2025-08-07 11:52:16'),
(195, 43, 4, 20000.00, 1, '2025-08-05 12:05:47', '2025-08-05 12:05:47'),
(196, 43, 5, 10000.00, 1, '2025-08-05 12:05:47', '2025-08-05 12:05:47'),
(197, 44, 2, 31000.00, 1, '2025-08-05 12:06:41', '2025-08-05 12:06:41'),
(198, 44, 3, 47000.00, 1, '2025-08-05 12:06:41', '2025-08-05 12:06:41'),
(199, 44, 4, 20000.00, 1, '2025-08-05 12:06:41', '2025-08-05 12:06:41'),
(200, 44, 5, 10000.00, 1, '2025-08-05 12:06:41', '2025-08-05 12:06:41'),
(201, 45, 2, 31000.00, 1, '2025-08-05 12:07:23', '2025-08-05 12:07:23'),
(202, 45, 3, 47000.00, 1, '2025-08-05 12:07:23', '2025-08-05 12:07:23'),
(203, 45, 4, 20000.00, 1, '2025-08-05 12:07:23', '2025-08-05 12:07:23'),
(204, 45, 5, 10000.00, 1, '2025-08-05 12:07:23', '2025-08-05 12:07:23'),
(205, 46, 2, 31000.00, 1, '2025-08-05 12:08:02', '2025-08-05 12:08:02'),
(206, 46, 3, 47000.00, 1, '2025-08-05 12:08:02', '2025-08-05 12:08:02'),
(207, 46, 4, 20000.00, 1, '2025-08-05 12:08:02', '2025-08-05 12:08:02'),
(208, 46, 5, 10000.00, 1, '2025-08-05 12:08:02', '2025-08-05 12:08:02'),
(209, 47, 2, 31000.00, 1, '2025-08-05 12:08:43', '2025-08-05 12:08:43'),
(210, 47, 3, 62000.00, 1, '2025-08-05 12:08:43', '2025-08-05 12:08:43'),
(211, 47, 4, 20000.00, 1, '2025-08-05 12:08:43', '2025-08-05 12:08:43'),
(212, 47, 5, 10000.00, 1, '2025-08-05 12:08:43', '2025-08-05 12:08:43'),
(213, 48, 2, 41000.00, 1, '2025-08-05 12:25:57', '2025-08-05 12:25:57'),
(214, 48, 3, 52000.00, 1, '2025-08-05 12:25:57', '2025-08-05 12:25:57'),
(215, 48, 4, 20000.00, 1, '2025-08-05 12:25:57', '2025-08-05 12:25:57'),
(216, 48, 5, 10000.00, 1, '2025-08-05 12:25:57', '2025-08-05 12:25:57'),
(221, 50, 2, 41000.00, 1, '2025-08-05 12:28:28', '2025-08-05 12:28:28'),
(222, 50, 3, 52000.00, 1, '2025-08-05 12:28:28', '2025-08-05 12:28:28'),
(223, 50, 4, 20000.00, 1, '2025-08-05 12:28:28', '2025-08-05 12:28:28'),
(224, 50, 5, 10000.00, 1, '2025-08-05 12:28:28', '2025-08-05 12:28:28'),
(229, 52, 2, 41000.00, 1, '2025-08-05 12:31:27', '2025-08-05 12:31:27'),
(230, 52, 3, 77000.00, 1, '2025-08-05 12:31:27', '2025-08-05 12:31:27'),
(231, 52, 4, 20000.00, 1, '2025-08-05 12:31:27', '2025-08-05 12:31:27'),
(232, 52, 5, 10000.00, 1, '2025-08-05 12:31:27', '2025-08-05 12:31:27'),
(233, 53, 2, 41000.00, 1, '2025-08-05 12:32:26', '2025-08-05 12:32:26'),
(234, 53, 3, 77000.00, 1, '2025-08-05 12:32:26', '2025-08-05 12:32:26'),
(235, 53, 4, 20000.00, 1, '2025-08-05 12:32:26', '2025-08-05 12:32:26'),
(236, 53, 5, 10000.00, 1, '2025-08-05 12:32:26', '2025-08-05 12:32:26'),
(241, 55, 2, 41000.00, 1, '2025-08-05 12:34:13', '2025-08-05 12:34:13'),
(242, 55, 3, 82000.00, 1, '2025-08-05 12:34:13', '2025-08-08 12:09:28'),
(243, 55, 4, 20000.00, 1, '2025-08-05 12:34:13', '2025-08-05 12:34:13'),
(244, 55, 5, 10000.00, 1, '2025-08-05 12:34:13', '2025-08-05 12:34:13'),
(249, 57, 2, 41000.00, 1, '2025-08-05 12:36:28', '2025-08-05 12:36:28'),
(250, 57, 3, 82000.00, 1, '2025-08-05 12:36:28', '2025-08-05 12:36:28'),
(251, 57, 4, 20000.00, 1, '2025-08-05 12:36:28', '2025-08-05 12:36:28'),
(252, 57, 5, 10000.00, 1, '2025-08-05 12:36:28', '2025-08-05 12:36:28'),
(253, 58, 2, 41000.00, 1, '2025-08-05 12:38:37', '2025-08-05 12:38:37'),
(254, 58, 3, 77000.00, 1, '2025-08-05 12:38:37', '2025-08-27 10:15:42'),
(255, 58, 4, 20000.00, 1, '2025-08-05 12:38:37', '2025-08-05 12:38:37'),
(256, 58, 5, 10000.00, 1, '2025-08-05 12:38:37', '2025-08-05 12:38:37'),
(261, 60, 2, 41000.00, 1, '2025-08-05 12:41:00', '2025-08-05 12:41:00'),
(262, 60, 3, 82000.00, 1, '2025-08-05 12:41:00', '2025-08-05 12:41:00'),
(263, 60, 4, 20000.00, 1, '2025-08-05 12:41:00', '2025-08-05 12:41:00'),
(264, 60, 5, 10000.00, 1, '2025-08-05 12:41:00', '2025-08-05 12:41:00'),
(277, 64, 2, 21000.00, 1, '2025-08-05 12:59:48', '2025-08-05 12:59:48'),
(278, 64, 3, 70000.00, 1, '2025-08-05 12:59:48', '2025-08-05 12:59:48'),
(279, 64, 4, 25000.00, 1, '2025-08-05 12:59:48', '2025-08-05 12:59:48'),
(280, 64, 5, 10000.00, 1, '2025-08-05 12:59:48', '2025-08-05 12:59:48'),
(281, 65, 2, 31000.00, 1, '2025-08-05 13:01:06', '2025-08-05 13:01:06'),
(282, 65, 3, 70000.00, 1, '2025-08-05 13:01:06', '2025-08-05 13:01:06'),
(283, 65, 4, 30000.00, 1, '2025-08-05 13:01:06', '2025-08-05 13:01:06'),
(284, 65, 5, 10000.00, 1, '2025-08-05 13:01:06', '2025-08-05 13:01:06'),
(285, 66, 2, 41000.00, 1, '2025-08-05 13:03:23', '2025-08-05 13:03:23'),
(286, 66, 3, 70000.00, 1, '2025-08-05 13:03:23', '2025-08-05 13:03:23'),
(287, 66, 4, 30000.00, 1, '2025-08-05 13:03:23', '2025-08-05 13:03:23'),
(288, 66, 5, 10000.00, 1, '2025-08-05 13:03:23', '2025-08-05 13:03:23'),
(289, 67, 2, 41000.00, 1, '2025-08-05 13:05:15', '2025-08-05 13:05:15'),
(290, 67, 3, 70000.00, 1, '2025-08-05 13:05:15', '2025-08-05 13:05:15'),
(291, 67, 4, 30000.00, 1, '2025-08-05 13:05:15', '2025-08-05 13:05:15'),
(292, 67, 5, 10000.00, 1, '2025-08-05 13:05:15', '2025-08-05 13:05:15'),
(293, 68, 2, 41000.00, 1, '2025-08-06 06:55:28', '2025-08-06 06:55:28'),
(294, 68, 3, 72000.00, 1, '2025-08-06 06:55:28', '2025-08-06 06:55:28'),
(295, 68, 4, 20000.00, 1, '2025-08-06 06:55:28', '2025-08-06 06:55:28'),
(296, 68, 5, 10000.00, 1, '2025-08-06 06:55:28', '2025-08-06 06:55:28'),
(297, 69, 2, 41000.00, 1, '2025-08-06 06:57:38', '2025-08-06 06:57:38'),
(298, 69, 3, 72000.00, 1, '2025-08-06 06:57:38', '2025-08-06 06:57:38'),
(299, 69, 4, 20000.00, 1, '2025-08-06 06:57:38', '2025-08-06 06:57:38'),
(300, 69, 5, 10000.00, 1, '2025-08-06 06:57:38', '2025-08-06 06:57:38'),
(301, 70, 2, 41000.00, 1, '2025-08-06 06:58:37', '2025-08-06 06:58:37'),
(302, 70, 3, 72000.00, 1, '2025-08-06 06:58:37', '2025-08-06 06:58:37'),
(303, 70, 4, 20000.00, 1, '2025-08-06 06:58:37', '2025-08-06 06:58:37'),
(304, 70, 5, 10000.00, 1, '2025-08-06 06:58:37', '2025-08-06 06:58:37'),
(305, 71, 2, 41000.00, 1, '2025-08-06 06:59:28', '2025-08-06 06:59:28'),
(306, 71, 3, 72000.00, 1, '2025-08-06 06:59:28', '2025-08-06 06:59:28'),
(307, 71, 4, 20000.00, 1, '2025-08-06 06:59:28', '2025-08-06 06:59:28'),
(308, 71, 5, 10000.00, 1, '2025-08-06 06:59:28', '2025-08-06 06:59:28'),
(309, 72, 2, 41000.00, 1, '2025-08-06 07:01:45', '2025-08-06 07:01:45'),
(310, 72, 3, 92000.00, 1, '2025-08-06 07:01:45', '2025-08-06 07:01:45'),
(311, 72, 4, 20000.00, 1, '2025-08-06 07:01:45', '2025-08-06 07:01:45'),
(312, 72, 5, 10000.00, 1, '2025-08-06 07:01:45', '2025-08-06 07:01:45'),
(313, 73, 2, 41000.00, 1, '2025-08-06 07:02:44', '2025-08-06 07:02:44'),
(314, 73, 3, 92000.00, 1, '2025-08-06 07:02:44', '2025-08-06 07:02:44'),
(315, 73, 4, 20000.00, 1, '2025-08-06 07:02:44', '2025-08-06 07:02:44'),
(316, 73, 5, 10000.00, 1, '2025-08-06 07:02:44', '2025-08-06 07:02:44'),
(317, 74, 2, 41000.00, 1, '2025-08-06 07:03:45', '2025-08-06 07:03:45'),
(318, 74, 3, 77000.00, 1, '2025-08-06 07:03:45', '2025-08-20 10:25:17'),
(319, 74, 4, 20000.00, 1, '2025-08-06 07:03:45', '2025-08-06 07:03:45'),
(320, 74, 5, 10000.00, 1, '2025-08-06 07:03:45', '2025-08-06 07:03:45'),
(321, 75, 2, 41000.00, 1, '2025-08-06 07:06:25', '2025-08-06 07:06:25'),
(322, 75, 3, 77000.00, 1, '2025-08-06 07:06:25', '2025-08-06 07:07:13'),
(323, 75, 4, 20000.00, 1, '2025-08-06 07:06:25', '2025-08-06 07:06:25'),
(324, 75, 5, 10000.00, 1, '2025-08-06 07:06:25', '2025-08-06 07:06:25');

-- --------------------------------------------------------

--
-- Structure de la table `class_scholarships`
--

CREATE TABLE `class_scholarships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_class_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Nom de la bourse',
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL COMMENT 'Montant de la bourse en FCFA',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_tranche_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `class_scholarships`
--

INSERT INTO `class_scholarships` (`id`, `school_class_id`, `name`, `description`, `amount`, `is_active`, `created_at`, `updated_at`, `payment_tranche_id`) VALUES
(2, 13, 'Excellence', NULL, 20000.00, 1, '2025-08-05 20:36:43', '2025-08-05 20:36:43', 3),
(3, 24, 'Bourse1', NULL, 20000.00, 1, '2025-08-06 05:52:15', '2025-08-06 05:52:15', 3),
(4, 29, 'bourse2', NULL, 20000.00, 1, '2025-08-06 05:52:37', '2025-08-06 05:52:37', 3),
(5, 36, 'bb', NULL, 20000.00, 1, '2025-08-06 05:52:57', '2025-08-06 05:52:57', 3),
(6, 43, 'bbbb', NULL, 20000.00, 1, '2025-08-06 05:53:25', '2025-08-06 05:53:25', 3),
(7, 39, 'bou', NULL, 20000.00, 1, '2025-08-06 05:53:53', '2025-08-06 05:53:53', 3);

-- --------------------------------------------------------

--
-- Structure de la table `class_series`
--

CREATE TABLE `class_series` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 50,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `main_teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `school_year_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `class_series`
--

INSERT INTO `class_series` (`id`, `class_id`, `name`, `code`, `capacity`, `is_active`, `created_at`, `updated_at`, `main_teacher_id`, `school_year_id`) VALUES
(15, 13, '6ème A', NULL, 86, 1, '2025-08-04 02:56:10', '2025-09-03 09:16:14', NULL, NULL),
(16, 13, '6ème B', NULL, 80, 1, '2025-08-04 02:56:10', '2025-09-03 09:16:14', NULL, NULL),
(17, 14, '5ème A', NULL, 88, 1, '2025-08-04 02:57:43', '2025-09-03 09:21:40', NULL, NULL),
(18, 14, '5ème B', NULL, 65, 1, '2025-08-04 02:57:43', '2025-09-03 09:21:40', NULL, NULL),
(19, 15, '4ème ALL', NULL, 72, 1, '2025-08-04 02:59:49', '2025-09-03 09:20:57', NULL, NULL),
(20, 15, '4ème ESP', NULL, 62, 1, '2025-08-04 02:59:49', '2025-09-03 09:20:57', NULL, NULL),
(21, 16, '3ème ESP', NULL, 109, 1, '2025-08-04 03:01:56', '2025-09-03 09:18:37', NULL, NULL),
(22, 16, '3ème ALL', NULL, 106, 1, '2025-08-04 03:01:56', '2025-09-03 09:18:37', NULL, NULL),
(33, 21, '2nd C', NULL, 41, 1, '2025-08-04 05:16:21', '2025-09-03 09:22:13', NULL, NULL),
(34, 21, '2nd A4 ALL', NULL, 47, 1, '2025-08-04 07:16:32', '2025-09-03 09:22:13', NULL, NULL),
(35, 22, '1ère A4 ESP', NULL, 39, 1, '2025-08-04 07:18:25', '2025-09-03 09:22:43', NULL, NULL),
(36, 22, '1ère A4 ALL', NULL, 67, 1, '2025-08-04 07:18:25', '2025-09-03 09:22:43', NULL, NULL),
(37, 22, '1ère D', NULL, 60, 1, '2025-08-04 07:18:25', '2025-09-03 09:22:43', NULL, NULL),
(38, 22, '1ère C', NULL, 60, 1, '2025-08-04 07:18:25', '2025-09-03 09:23:07', NULL, NULL),
(39, 23, 'Tle A4 All', NULL, 70, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08', NULL, NULL),
(40, 23, 'Tle ESP', NULL, 71, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08', NULL, NULL),
(41, 23, 'Tle C', NULL, 60, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08', NULL, NULL),
(42, 23, 'Tle D', NULL, 60, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08', NULL, NULL),
(43, 24, 'SEME 1 A', NULL, 60, 1, '2025-08-04 09:43:45', '2025-09-03 09:23:55', NULL, NULL),
(44, 24, 'SEME 1 B', NULL, 60, 1, '2025-08-04 09:43:45', '2025-09-03 09:23:55', NULL, NULL),
(45, 25, 'SEME 2 A', NULL, 60, 1, '2025-08-04 09:45:02', '2025-09-03 09:24:32', NULL, NULL),
(46, 25, 'SEME 2 B', NULL, 60, 1, '2025-08-04 09:45:02', '2025-09-03 09:24:32', NULL, NULL),
(47, 26, 'SEME 3 A', NULL, 60, 1, '2025-08-04 09:45:59', '2025-09-03 09:25:56', NULL, NULL),
(48, 26, 'SEME 3 B', NULL, 60, 1, '2025-08-04 09:45:59', '2025-09-03 09:25:56', NULL, NULL),
(49, 27, 'SEME 4 A', NULL, 60, 1, '2025-08-04 09:47:04', '2025-09-03 09:27:55', NULL, NULL),
(50, 27, 'SEME 4 B', NULL, 60, 1, '2025-08-04 09:47:04', '2025-09-03 09:27:55', NULL, NULL),
(51, 28, 'ESF 3 A', NULL, 60, 1, '2025-08-04 11:49:24', '2025-09-03 09:35:18', NULL, NULL),
(52, 28, 'ESF 3 B', NULL, 57, 1, '2025-08-04 11:49:24', '2025-09-03 09:35:18', NULL, NULL),
(53, 29, 'FORM ONE A', NULL, 60, 1, '2025-08-04 12:47:00', '2025-09-03 09:41:42', NULL, NULL),
(54, 29, 'FORM ONE B', NULL, 55, 1, '2025-08-04 12:47:00', '2025-09-03 09:41:42', NULL, NULL),
(55, 30, 'FORM TWO A', NULL, 60, 1, '2025-08-04 12:55:09', '2025-09-03 09:42:00', NULL, NULL),
(56, 30, 'FORM TWO B', NULL, 60, 1, '2025-08-04 12:55:09', '2025-09-03 09:42:00', NULL, NULL),
(57, 31, 'FORM THREE A', NULL, 60, 1, '2025-08-04 12:56:54', '2025-09-03 09:42:26', NULL, NULL),
(58, 31, 'FORM THREE B', NULL, 60, 1, '2025-08-04 12:56:54', '2025-09-03 09:42:26', NULL, NULL),
(59, 32, 'FORM FOUR ARTS A', NULL, 60, 1, '2025-08-04 13:02:07', '2025-09-03 09:44:05', NULL, NULL),
(60, 32, 'FORM FOUR ARTS B', NULL, 60, 1, '2025-08-04 13:02:07', '2025-09-03 09:44:05', NULL, NULL),
(61, 33, 'FORM FIVE (science ) A', NULL, 60, 1, '2025-08-04 13:03:19', '2025-09-03 09:44:32', NULL, NULL),
(62, 33, 'FORM FIVE (science ) B', NULL, 60, 1, '2025-08-04 13:03:19', '2025-09-03 09:44:32', NULL, NULL),
(63, 34, 'UPPER SIXTH ( ARTS ) A', NULL, 60, 1, '2025-08-04 13:05:04', '2025-09-03 09:44:55', NULL, NULL),
(64, 34, 'UPPER SIXTH ( ARTS ) B', NULL, 60, 1, '2025-08-04 13:05:04', '2025-09-03 09:44:55', NULL, NULL),
(65, 35, 'LOWER SIXTH (ARTS ) A', NULL, 60, 1, '2025-08-04 13:07:27', '2025-09-03 09:45:19', NULL, NULL),
(66, 35, 'LOWER SIXTH (ARTS ) B', NULL, 60, 1, '2025-08-04 13:07:27', '2025-09-03 09:45:19', NULL, NULL),
(67, 36, 'ESF 1 A', NULL, 60, 1, '2025-08-04 13:39:42', '2025-09-03 09:39:19', NULL, NULL),
(68, 36, 'ESF 1 B', NULL, 60, 1, '2025-08-04 13:39:42', '2025-09-03 09:39:19', NULL, NULL),
(69, 37, 'ESF 2 A', NULL, 60, 1, '2025-08-04 13:43:07', '2025-09-03 09:39:38', NULL, NULL),
(70, 38, 'ESF 4 A', NULL, 60, 1, '2025-08-04 13:45:11', '2025-09-03 09:39:53', NULL, NULL),
(71, 39, 'COME 1 A', NULL, 60, 1, '2025-08-04 13:48:18', '2025-09-03 09:40:12', NULL, NULL),
(72, 40, 'COME 2 A', NULL, 60, 1, '2025-08-04 13:50:14', '2025-09-03 09:40:37', NULL, NULL),
(73, 41, 'COME 3 A', NULL, 60, 1, '2025-08-05 10:15:38', '2025-09-03 09:40:54', NULL, NULL),
(74, 41, 'COME 3 B', NULL, 57, 1, '2025-08-05 10:15:38', '2025-09-03 09:40:54', NULL, NULL),
(75, 42, 'COME 4 A', NULL, 60, 1, '2025-08-05 10:16:56', '2025-09-03 09:41:14', NULL, NULL),
(76, 42, 'COME 4 B', NULL, 60, 1, '2025-08-05 10:16:56', '2025-09-03 09:41:14', NULL, NULL),
(77, 43, 'ESCOM 1 A', NULL, 55, 1, '2025-08-05 12:05:47', '2025-09-03 09:29:25', NULL, NULL),
(78, 44, 'ESCOM 2 A', NULL, 60, 1, '2025-08-05 12:06:41', '2025-09-03 09:30:15', NULL, NULL),
(79, 45, 'ESCOM 2 A', NULL, 60, 1, '2025-08-05 12:07:23', '2025-09-03 09:30:44', NULL, NULL),
(80, 46, 'ESCOM 3 A', NULL, 60, 1, '2025-08-05 12:08:02', '2025-09-03 09:33:14', NULL, NULL),
(81, 47, 'ESCOM 4 A', NULL, 60, 1, '2025-08-05 12:08:43', '2025-09-03 09:33:34', NULL, NULL),
(82, 48, '2nd STT A', NULL, 60, 1, '2025-08-05 12:25:57', '2025-09-03 09:48:36', NULL, NULL),
(84, 50, '2nd F8 A', NULL, 60, 1, '2025-08-05 12:28:28', '2025-09-03 09:49:40', NULL, NULL),
(86, 52, '1er ACC A', NULL, 60, 1, '2025-08-05 12:31:27', '2025-09-03 09:49:59', NULL, NULL),
(87, 53, '1er CG A', NULL, 60, 1, '2025-08-05 12:32:26', '2025-09-03 09:50:14', NULL, NULL),
(89, 55, '1er F8 A', NULL, 60, 1, '2025-08-05 12:34:13', '2025-09-03 09:50:41', NULL, NULL),
(91, 57, 'Tle ACC A', NULL, 60, 1, '2025-08-05 12:36:28', '2025-09-03 09:50:58', NULL, NULL),
(92, 58, 'Tle CG A', NULL, 60, 1, '2025-08-05 12:38:37', '2025-09-03 09:51:17', NULL, NULL),
(94, 60, 'Tle F8 A', NULL, 60, 1, '2025-08-05 12:41:00', '2025-09-03 09:51:32', NULL, NULL),
(98, 64, 'FORM FOUR SCIENCE  A', NULL, 60, 1, '2025-08-05 12:59:48', '2025-09-03 09:45:44', NULL, NULL),
(99, 65, 'FORM FIVE (ARTS) A', NULL, 60, 1, '2025-08-05 13:01:06', '2025-09-03 09:46:06', NULL, NULL),
(100, 66, 'LOWER SIXTH (science ) A', NULL, 60, 1, '2025-08-05 13:03:23', '2025-09-03 09:46:23', NULL, NULL),
(101, 67, 'UPPER SIXTH (science) A', NULL, 60, 1, '2025-08-05 13:05:15', '2025-09-03 09:46:38', NULL, NULL),
(102, 68, '2nd IH A', NULL, 60, 1, '2025-08-06 06:55:28', '2025-09-03 09:47:00', NULL, NULL),
(103, 69, '2nd ESF A', NULL, 60, 1, '2025-08-06 06:57:38', '2025-09-03 09:47:12', NULL, NULL),
(104, 70, '1er IH A', NULL, 60, 1, '2025-08-06 06:58:37', '2025-09-03 09:47:29', NULL, NULL),
(105, 71, '1er ESF A', NULL, 60, 1, '2025-08-06 06:59:28', '2025-09-03 09:47:46', NULL, NULL),
(106, 72, 'Tle IH A', NULL, 60, 1, '2025-08-06 07:01:45', '2025-09-03 09:48:03', NULL, NULL),
(107, 73, 'Tle ESF A', NULL, 60, 1, '2025-08-06 07:02:44', '2025-09-03 09:48:16', NULL, NULL),
(108, 74, 'Tle ACA A', NULL, 60, 1, '2025-08-06 07:03:45', '2025-09-03 09:51:50', NULL, NULL),
(109, 75, '1ere ACA A', NULL, 60, 1, '2025-08-06 07:06:25', '2025-09-03 09:52:04', NULL, NULL),
(110, 21, '2nd A4 ESP', NULL, 35, 1, '2025-08-20 10:22:12', '2025-08-20 10:22:12', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `class_series_subjects`
--

CREATE TABLE `class_series_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_series_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `coefficient` decimal(5,2) NOT NULL DEFAULT 1.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `daily_attendance_states`
--

CREATE TABLE `daily_attendance_states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_series_id` bigint(20) UNSIGNED NOT NULL,
  `supervisor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `entry_state` enum('not_done','in_progress','completed') NOT NULL DEFAULT 'not_done',
  `exit_state` enum('not_done','in_progress','completed') NOT NULL DEFAULT 'not_done',
  `entry_completed_at` timestamp NULL DEFAULT NULL,
  `exit_completed_at` timestamp NULL DEFAULT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `daily_attendance_states`
--

INSERT INTO `daily_attendance_states` (`id`, `class_series_id`, `supervisor_id`, `attendance_date`, `entry_state`, `exit_state`, `entry_completed_at`, `exit_completed_at`, `school_year_id`, `created_at`, `updated_at`) VALUES
(1, 22, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-08 22:06:05', '2025-09-08 22:06:05'),
(2, 21, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-08 22:06:13', '2025-09-08 22:06:13'),
(3, 18, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:26:41', '2025-09-09 06:26:41'),
(4, 17, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:26:45', '2025-09-09 06:26:45'),
(5, 99, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:28:10', '2025-09-09 06:28:10'),
(6, 61, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:28:15', '2025-09-09 06:28:15'),
(7, 62, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:28:23', '2025-09-09 06:28:23'),
(8, 59, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:28:29', '2025-09-09 06:28:29'),
(9, 60, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:28:34', '2025-09-09 06:28:34'),
(10, 53, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:28:42', '2025-09-09 06:28:42'),
(11, 36, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:30:38', '2025-09-09 06:30:38'),
(12, 15, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:33:51', '2025-09-09 06:33:51'),
(13, 16, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:36:06', '2025-09-09 06:36:06'),
(14, 19, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:36:16', '2025-09-09 06:36:16'),
(15, 20, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:36:25', '2025-09-09 06:36:25'),
(16, 35, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:38:15', '2025-09-09 06:38:15'),
(17, 38, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:38:24', '2025-09-09 06:38:24'),
(18, 37, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:38:29', '2025-09-09 06:38:29'),
(19, 39, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:38:41', '2025-09-09 06:38:41'),
(20, 41, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:38:48', '2025-09-09 06:38:48'),
(21, 42, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:38:54', '2025-09-09 06:38:54'),
(22, 40, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:39:01', '2025-09-09 06:39:01'),
(23, 77, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:39:42', '2025-09-09 06:39:42'),
(24, 78, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:39:55', '2025-09-09 06:39:55'),
(25, 80, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:40:13', '2025-09-09 06:40:13'),
(26, 81, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:40:22', '2025-09-09 06:40:22'),
(27, 43, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:40:33', '2025-09-09 06:40:33'),
(28, 44, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:40:43', '2025-09-09 06:40:43'),
(29, 45, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:40:52', '2025-09-09 06:40:52'),
(30, 47, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:41:06', '2025-09-09 06:41:06'),
(31, 49, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:41:24', '2025-09-09 06:41:24'),
(32, 105, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:41:49', '2025-09-09 06:41:49'),
(33, 104, NULL, '2025-09-09', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-09 06:42:02', '2025-09-09 06:42:02'),
(34, 36, NULL, '2025-09-10', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-10 06:01:53', '2025-09-10 06:01:53'),
(35, 36, NULL, '2025-09-11', 'not_done', 'not_done', NULL, NULL, 1, '2025-09-10 23:50:51', '2025-09-10 23:50:51');

-- --------------------------------------------------------

--
-- Structure de la table `demandes_explication`
--

CREATE TABLE `demandes_explication` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `emetteur_id` bigint(20) UNSIGNED NOT NULL,
  `destinataire_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priorite` enum('basse','normale','haute','urgente') NOT NULL DEFAULT 'normale',
  `statut` enum('brouillon','envoyee','lue','repondue','cloturee') NOT NULL DEFAULT 'brouillon',
  `type` enum('financier','absence','retard','disciplinaire','autre') NOT NULL DEFAULT 'autre',
  `date_envoi` datetime DEFAULT NULL,
  `date_lecture` datetime DEFAULT NULL,
  `date_reponse` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `reponse` text DEFAULT NULL,
  `pieces_jointes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pieces_jointes`)),
  `notes_internes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6c757d',
  `head_teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `documentary_fees`
--

CREATE TABLE `documentary_fees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `fee_type` enum('frais_dossier') NOT NULL DEFAULT 'frais_dossier',
  `description` varchar(255) DEFAULT NULL,
  `fee_amount` decimal(10,2) NOT NULL COMMENT 'Montant des frais de dossier',
  `penalty_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Montant de la pénalité',
  `total_amount` decimal(10,2) NOT NULL COMMENT 'Montant total (fee_amount + penalty_amount)',
  `payment_date` date NOT NULL,
  `versement_date` date DEFAULT NULL,
  `validation_date` datetime DEFAULT NULL,
  `payment_method` enum('cash','cheque','transfer','mobile_money') NOT NULL DEFAULT 'cash',
  `reference_number` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','validated','cancelled') NOT NULL DEFAULT 'pending',
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `validated_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_extension` varchar(10) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `document_type` varchar(255) NOT NULL DEFAULT 'general',
  `folder_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `visibility` varchar(255) NOT NULL DEFAULT 'private',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `download_count` int(11) NOT NULL DEFAULT 0,
  `last_downloaded_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `document_folders`
--

CREATE TABLE `document_folders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `folder_type` varchar(255) NOT NULL DEFAULT 'custom',
  `color` varchar(7) NOT NULL DEFAULT '#007bff',
  `icon` varchar(255) NOT NULL DEFAULT 'folder',
  `parent_folder_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `is_system_folder` tinyint(1) NOT NULL DEFAULT 0,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_roles`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `document_folders`
--

INSERT INTO `document_folders` (`id`, `name`, `description`, `folder_type`, `color`, `icon`, `parent_folder_id`, `created_by`, `is_system_folder`, `is_private`, `allowed_roles`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Dossiers Étudiants', 'Documents relatifs aux étudiants (bulletins, certificats, etc.)', 'student', '#28a745', 'person-lines-fill', NULL, 1, 1, 0, '[\"admin\",\"accountant\",\"teacher\"]', 1, '2025-08-11 08:35:55', '2025-08-11 08:35:55'),
(2, 'Administration', 'Documents administratifs et de gestion', 'administration', '#dc3545', 'building', NULL, 1, 1, 0, '[\"admin\",\"accountant\"]', 2, '2025-08-11 08:35:55', '2025-08-11 08:35:55'),
(3, 'Documents Personnels', 'Espace personnel pour vos documents privés', 'custom', '#6f42c1', 'person-badge', NULL, 1, 1, 1, NULL, 3, '2025-08-11 08:35:55', '2025-08-11 08:35:55'),
(4, 'Partage', 'Documents partagés entre le personnel', 'custom', '#17a2b8', 'share', NULL, 1, 1, 0, '[\"admin\",\"accountant\",\"teacher\"]', 4, '2025-08-11 08:35:55', '2025-08-11 08:35:55');

-- --------------------------------------------------------

--
-- Structure de la table `document_permissions`
--

CREATE TABLE `document_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `permissionable_type` varchar(255) NOT NULL,
  `permissionable_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `permission_type` varchar(255) NOT NULL DEFAULT 'view',
  `granted_at` timestamp NULL DEFAULT NULL,
  `granted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `employees_payroll`
--

CREATE TABLE `employees_payroll` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `matricule` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `poste` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `salaire_base` decimal(10,0) NOT NULL DEFAULT 0,
  `primes_fixes` decimal(10,0) NOT NULL DEFAULT 0,
  `deductions_fixes` decimal(10,0) NOT NULL DEFAULT 0,
  `mode_paiement` enum('especes','cheque','virement') NOT NULL DEFAULT 'especes',
  `telephone_whatsapp` varchar(255) DEFAULT NULL,
  `statut` enum('actif','suspendu','conge') NOT NULL DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('interrogation','devoir','composition','tp','controle') NOT NULL,
  `sequence_id` bigint(20) UNSIGNED NOT NULL,
  `trimester_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `series_subject_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT 20.00,
  `coefficient` decimal(3,2) NOT NULL DEFAULT 1.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evaluation_configs`
--

CREATE TABLE `evaluation_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `level_id` bigint(20) UNSIGNED NOT NULL,
  `evaluation_mode` enum('1ds_1comp','2ds_1comp') NOT NULL,
  `ds1_percentage` decimal(5,2) NOT NULL,
  `ds2_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `composition_percentage` decimal(5,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evaluation_configs`
--

INSERT INTO `evaluation_configs` (`id`, `school_year_id`, `level_id`, `evaluation_mode`, `ds1_percentage`, `ds2_percentage`, `composition_percentage`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 13, '2ds_1comp', 25.00, 25.00, 50.00, NULL, 1, '2025-09-02 23:15:41', '2025-09-02 23:15:41'),
(2, 1, 14, '2ds_1comp', 25.00, 25.00, 50.00, NULL, 1, '2025-09-02 23:15:56', '2025-09-02 23:15:56'),
(3, 1, 15, '2ds_1comp', 25.00, 25.00, 50.00, NULL, 1, '2025-09-02 23:16:11', '2025-09-02 23:16:11'),
(4, 1, 19, '2ds_1comp', 25.00, 25.00, 50.00, NULL, 1, '2025-09-02 23:16:23', '2025-09-02 23:16:23'),
(5, 1, 20, '1ds_1comp', 50.00, 0.00, 50.00, NULL, 0, '2025-09-02 23:16:48', '2025-09-02 23:17:39'),
(6, 1, 20, '2ds_1comp', 25.00, 25.00, 50.00, NULL, 1, '2025-09-02 23:17:39', '2025-09-02 23:17:39'),
(7, 1, 21, '2ds_1comp', 25.00, 25.00, 50.00, NULL, 1, '2025-09-02 23:17:56', '2025-09-02 23:17:56'),
(8, 1, 22, '2ds_1comp', 25.00, 25.00, 50.00, NULL, 1, '2025-09-02 23:18:16', '2025-09-02 23:18:16');

-- --------------------------------------------------------

--
-- Structure de la table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `geolocation_zones`
--

CREATE TABLE `geolocation_zones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `radius` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `grades`
--

CREATE TABLE `grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `evaluation_id` bigint(20) UNSIGNED NOT NULL,
  `sequence_id` bigint(20) UNSIGNED NOT NULL,
  `trimester_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `series_subject_id` bigint(20) UNSIGNED NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT 20.00,
  `coefficient` decimal(3,2) NOT NULL DEFAULT 1.00,
  `weighted_score` decimal(8,2) DEFAULT NULL,
  `is_absent` tinyint(1) NOT NULL DEFAULT 0,
  `is_excused` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `grading_scales`
--

CREATE TABLE `grading_scales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `level_id` bigint(20) UNSIGNED DEFAULT NULL,
  `grade_code` varchar(5) NOT NULL,
  `grade_label` varchar(50) NOT NULL,
  `min_score` decimal(5,2) NOT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `appreciation` text NOT NULL,
  `color_code` varchar(7) NOT NULL DEFAULT '#6c757d',
  `passing_threshold` decimal(5,2) DEFAULT NULL,
  `is_passing_grade` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(255) NOT NULL,
  `categorie` varchar(255) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `quantite_min` int(11) NOT NULL DEFAULT 0,
  `etat` enum('Excellent','Bon','Moyen','Mauvais','Hors service') NOT NULL DEFAULT 'Bon',
  `localisation` varchar(255) NOT NULL,
  `responsable` varchar(255) NOT NULL,
  `date_achat` date DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT 0.00,
  `numero_serie` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inventory_item_tags`
--

CREATE TABLE `inventory_item_tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inventory_item_id` bigint(20) UNSIGNED NOT NULL,
  `inventory_tag_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inventory_item_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('in','out','adjustment') NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movement_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inventory_tags`
--

CREATE TABLE `inventory_tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL DEFAULT '#6c757d',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `levels`
--

CREATE TABLE `levels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `section_id` bigint(20) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `levels`
--

INSERT INTO `levels` (`id`, `name`, `section_id`, `description`, `order`, `is_active`, `created_at`, `updated_at`) VALUES
(13, 'ENSEIGNEMENT GENERAL PREMIER CYCLE', 5, NULL, 1, 1, '2025-08-04 02:51:55', '2025-08-04 05:08:14'),
(14, 'ENSEIGNEMENT GENERAL DEUXIEME CYCLE', 5, NULL, 2, 1, '2025-08-04 02:52:19', '2025-08-04 05:15:13'),
(15, 'ENSEIGNEMENT COMMERCIAL PREMIER CYCLE(SEME /ESCOM)', 6, NULL, 3, 1, '2025-08-04 02:52:42', '2025-08-04 05:15:22'),
(19, 'ENSEIGNEMENT COMMERCIAL DEUXIEME CYCLE(SEME /ESCOM) (F8/STT/SES/CG/ACC/SIG)', 6, NULL, 4, 1, '2025-08-04 09:51:47', '2025-08-04 09:51:47'),
(20, 'ENSEIGNEMENT TECHNIQUE INDUSTRIEL PREMIER CYCLE (ESF/ IH)', 6, NULL, 5, 1, '2025-08-04 09:52:59', '2025-08-04 09:52:59'),
(21, 'ENSEIGNEMENT TECHNIQUE INDUSTRIEL DEUXIEME CYCLE( ESF / IH ) + BP', 6, NULL, 6, 1, '2025-08-04 09:55:05', '2025-08-06 06:48:33'),
(22, 'ENSEIGNEMENT ANGLOPHONE', 7, NULL, 7, 1, '2025-08-04 09:58:01', '2025-08-04 09:58:17');

-- --------------------------------------------------------

--
-- Structure de la table `main_teachers`
--

CREATE TABLE `main_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `school_class_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_07_28_100819_create_sections_table', 1),
(5, '2025_07_28_111808_create_payment_tranches_table', 1),
(6, '2025_07_28_111830_create_levels_table', 1),
(7, '2025_07_28_111842_create_school_classes_table', 1),
(8, '2025_07_28_111906_create_class_series_table', 1),
(9, '2025_07_28_111918_create_class_payment_amounts_table', 1),
(10, '2025_07_28_111930_create_students_table', 1),
(11, '2025_07_28_220034_create_school_years_table', 1),
(12, '2025_07_28_220108_add_new_fields_to_students_table', 1),
(13, '2025_07_28_221813_make_legacy_student_fields_nullable', 1),
(14, '2025_07_28_223110_add_order_to_students_table', 1),
(15, '2025_07_29_022402_add_photo_to_students_table', 1),
(16, '2025_07_29_073706_add_working_school_year_to_users_table', 1),
(17, '2025_07_29_133242_create_payments_table', 1),
(18, '2025_07_29_133248_create_payment_details_table', 1),
(19, '2025_07_29_211149_add_rame_tranche_to_all_classes', 1),
(20, '2025_07_29_211243_add_is_rame_physical_to_payments_table', 1),
(21, '2025_07_29_211916_add_default_amount_to_payment_tranches', 1),
(22, '2025_07_29_212201_remove_rame_from_class_payment_amounts', 1),
(23, '2025_07_29_220152_add_student_status_to_students_table', 1),
(24, '2025_07_29_220215_create_school_settings_table', 1),
(25, '2025_07_29_220240_create_class_scholarships_table', 1),
(26, '2025_07_29_220258_add_deadline_to_payment_tranches', 1),
(27, '2025_07_29_220435_add_discount_info_to_payments_table', 1),
(28, '2025_07_30_190227_add_primary_color_to_school_settings_table', 1),
(29, '2025_07_30_231950_add_payment_tranche_id_to_class_scholarships_table', 1),
(30, '2025_07_31_070000_create_needs_table', 1),
(31, '2025_07_31_070100_add_whatsapp_to_school_settings', 1),
(32, '2025_07_31_071051_add_is_active_to_users_table', 1),
(33, '2025_07_31_072949_add_contact_to_users_table', 1),
(34, '2025_07_31_075230_add_photo_to_users_table', 1),
(35, '2025_07_31_083210_add_ultramsg_fields_to_school_settings', 1),
(36, '2025_07_31_122410_create_supervisor_class_assignments_table', 1),
(37, '2025_07_31_122429_create_attendances_table', 1),
(38, '2025_07_31_140000_create_subjects_table', 1),
(39, '2025_07_31_140100_create_teachers_table', 1),
(40, '2025_07_31_140200_create_class_series_subjects_table', 1),
(41, '2025_07_31_140300_create_teacher_subjects_table', 1),
(42, '2025_07_31_140400_add_main_teacher_to_class_series_table', 1),
(43, '2025_07_31_150000_create_series_subjects_table', 1),
(44, '2025_07_31_150100_create_teacher_assignments_table', 1),
(45, '2025_07_31_150200_create_main_teachers_table', 1),
(46, '2025_07_31_225600_add_entry_exit_to_attendances_table', 1),
(47, '2025_08_01_060405_simplify_class_payment_amounts_table', 1),
(48, '2025_08_01_090630_add_versement_and_validation_dates_to_payments_table', 1),
(49, '2025_08_01_145017_add_reduction_context_to_payment_details_table', 1),
(50, '2025_08_01_212000_create_student_rame_status_table', 1),
(51, '2025_08_04_055205_remove_unique_constraint_from_attendances_table', 2),
(52, '2025_08_04_055731_force_remove_unique_constraint_attendances', 2),
(53, '2025_08_05_131720_add_scholarship_enabled_to_students_table', 2),
(54, '2025_08_08_000000_create_inventory_items_table', 3),
(55, '2025_08_09_014433_create_inventory_movements_table', 3),
(56, '2025_08_09_090349_create_inventory_tags_table', 3),
(57, '2025_08_09_094801_add_deposit_date_to_student_rame_status_table', 3),
(58, '2025_08_09_101116_create_document_folders_table', 3),
(59, '2025_08_09_101122_create_documents_table', 3),
(60, '2025_08_09_101129_create_document_permissions_table', 3),
(61, '2025_08_09_101220_seed_default_document_folders', 3),
(62, '2025_08_13_150000_add_comptable_superieur_role', 4),
(63, '2025_01_15_120000_create_teacher_attendances_table', 5),
(64, '2025_01_15_121000_add_qr_code_to_teachers_table', 5),
(65, '2025_08_17_072554_create_staff_attendances_table', 6),
(66, '2025_08_17_072803_add_qr_code_to_users_table', 6),
(67, '2025_08_17_175544_remove_unique_constraint_from_staff_attendances', 6),
(68, '2025_08_17_194156_create_departments_table', 6),
(69, '2025_08_17_194246_add_department_id_to_teachers_table', 6),
(70, '2025_08_18_065545_add_type_personnel_to_teachers_table', 6),
(71, '2025_08_18_113508_update_users_role_column', 6),
(72, '2025_08_18_115241_add_qualification_to_users_table', 6),
(73, '2025_08_18_142541_add_mother_fields_to_students_table', 6),
(74, '2025_08_19_221209_create_documentary_fees_table', 7),
(75, '2025_08_20_124623_add_principal_name_to_school_settings_table', 8),
(76, '2025_08_21_134953_create_demandes_explication_table', 9),
(77, '2025_08_21_192503_create_employees_payroll_table', 10),
(78, '2025_08_21_192533_create_payroll_periods_table', 10),
(79, '2025_08_21_192553_create_salary_cuts_table', 10),
(80, '2025_08_21_192611_create_payslips_table', 10),
(81, '2025_08_21_192629_create_payroll_whatsapp_notifications_table', 10),
(82, '2025_09_01_101943_add_bibliothecaire_to_staff_attendances_staff_type', 11),
(83, '2025_09_01_104515_add_scanned_qr_code_to_staff_attendances', 11),
(84, '2025_09_02_211622_create_academic_system_config_table', 12),
(85, '2025_09_02_211647_create_academic_periods_table', 12),
(86, '2025_09_02_234602_create_evaluation_configs_table', 12),
(87, '2025_09_03_002344_create_grading_scales_table', 12),
(88, '2025_09_03_141330_create_trimesters_table', 13),
(89, '2025_09_03_141357_create_sequences_table', 13),
(90, '2025_09_03_141422_create_evaluations_table', 13),
(91, '2025_09_03_141447_create_grades_table', 13),
(92, '2025_09_03_231056_create_geolocation_zones_table', 13),
(93, '2025_09_04_204812_add_is_completed_to_sequences_table', 13),
(94, '2025_09_04_212656_create_parent_guardians_table', 13),
(95, '2025_09_04_212738_create_parent_notifications_table', 13),
(96, '2025_09_04_212848_create_parent_student_relationships_table', 13),
(97, '2025_09_04_213440_create_personal_access_tokens_table', 13),
(98, '2025_09_05_060606_add_admin_id_to_parent_notifications_table', 13),
(99, '2025_09_05_100000_create_schedules_table', 13),
(100, '2025_09_06_142706_create_student_attendances_table', 13),
(101, '2025_01_09_create_daily_attendance_states_table', 14),
(102, '2025_09_07_160619_create_tasks_table', 15),
(103, '2025_01_10_create_staff_attendance_classes_table', 16),
(104, '2025_09_09_103947_add_class_id_to_staff_attendances_table', 17),
(105, '2025_09_09_121943_update_staff_type_enum_in_staff_attendances', 17),
(106, '2025_09_09_140413_create_staff_attendance_classes_table', 17),
(107, '2025_09_09_204101_add_secretaire_to_staff_type_enum_in_staff_attendances', 17),
(108, '2025_09_11_020023_add_staff_identifier_to_users_table', 18),
(109, '2025_09_11_111355_add_teacher_identifier_to_teachers_table', 19),
(110, '2025_09_11_131423_create_card_templates_table', 19),
(111, '2025_09_11_180810_add_whatsapp_fields_to_parent_notifications_table', 19),
(112, '2025_09_12_005931_fix_needs_user_constraint', 19),
(113, '2025_09_12_005957_fix_dangerous_cascade_constraints', 19);

-- --------------------------------------------------------

--
-- Structure de la table `needs`
--

CREATE TABLE `needs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `whatsapp_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `needs`
--

INSERT INTO `needs` (`id`, `name`, `description`, `amount`, `status`, `user_id`, `approved_by`, `rejection_reason`, `approved_at`, `whatsapp_sent`, `created_at`, `updated_at`) VALUES
(1, 'CREDIT DE COMMUNICATION', 'CREDIT DE COMMUNICATION POUR ETABIR LES EMPLOI DE TEMPS', 8000.00, 'rejected', 3, 1, 'C’était une simulation', '2025-08-09 07:04:11', 1, '2025-08-06 09:47:05', '2025-08-09 07:04:11'),
(2, 'Budget pour achat du materiel technique', 'Peinture  eau 17500F\nDiluant 1000*6 = 6000F\nRouleaux 700*3= 2100F\nCiment 4500*5 = 25000F\nNB deca gérer', 50600.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 12:25:26', '2025-08-12 11:39:36'),
(3, 'Matériel pédagogique', 'Achat de 10 stylos pour recrutement élève\nNB déjà gérer', 2000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 12:29:01', '2025-08-08 12:29:02'),
(4, 'Besoin pédagogique', 'Frais de déplacement et photocopie de bordereaux dans quatre centre d\'examen\nNB déjà gérer', 6000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 12:32:13', '2025-08-08 12:32:14'),
(5, 'Besoin technique', '11 feuilles de tôle de 2m 5500*11 = 60500F\n04 lattes de 5m 2000*4 = 8000F\ntransport 1500F \nNB Déjà gère', 70000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 12:41:08', '2025-08-08 12:41:09'),
(6, 'Besoin technique', 'Installation de la climatisation dans le bureau du principal 74000F\n1,5m tapis de mur 3000F\n02 paquets de toc 3000F\n04 sacs de ciment 5500*4 = 22000F\nNB déjà gère', 102000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 12:49:32', '2025-08-08 12:49:32'),
(7, 'Besoin pédagogique', 'Achat du web cam laptop\nNB déjà gérer', 10000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 12:52:00', '2025-08-08 12:52:00'),
(8, 'Besoin technique', 'O1 paquet de baquettes 16500F\n06 disques à couper 1000*6 = 6000F\n01 disques à meuler 2000F\nNB Déjà gérer', 24500.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 12:55:16', '2025-08-08 12:55:17'),
(9, 'Besoin administratif', 'Recharge du crédit de communication orange 2500F \nMTN 2500F dans le téléphone du bureau pour appel des enseignants \nNB déjà géré', 5000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 13:01:50', '2025-08-08 13:01:50'),
(10, 'Besoin technique', 'Travaux de maçonnerie \n02/08/2025  avance maçon 25000\n07/08/2025 main d\'œuvre main maçon 40000\nNB Déjà gère', 65000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 13:15:54', '2025-08-08 13:15:54'),
(11, 'Besoin pédagogique', 'Transport pour aller a mboppi chercher les prix des articles\nNB déjà gérer', 3000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 13:17:13', '2025-08-12 11:37:54'),
(12, 'Besoin administratif', 'Transport pour estuaire yassa pour apporter la Starling et l\'imprimante \nNB déjà gérer', 2000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-08 13:18:57', '2025-08-12 11:37:29'),
(13, 'Besoin technique', 'Devis de réflexion pour 24 bancs\n15 lattes 2000*15 = 30,000f\n05 planches 5000*2 = 25,000f\n02 paquets de pointe 5000*2 = 10,000f\nmachine 20,000f\nTransport 15,000f\nTOTAL 150,000f\nNB Déjà gérer', 150000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-09 08:38:42', '2025-08-12 11:40:15'),
(14, 'Besoin technique', 'Mettre l\'antirouille sur 03 balcons ( reste du devis) de M ROMEO\n02 antirouilles 3000*6 = 6000F\n02 diluants 1500* = 3000f\n02 rouleaux 500*2 = 1000F\nT 10000F', 10000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-12 08:39:28', '2025-08-12 11:35:30'),
(15, 'Besoin pédagogique', 'Préparation de la rentrée scolaire : assemblée général\n200 sous chemises 4000f a raison de 2000f le paquet de 100\n02 correctors Bic 400f \n01 paquets de marqueurs ( bleu, rouge, noir, vert) 2500f\nStylo vert 02 1500*2= 300\nStylo rouge 02 1500*2= 300\nStylo noir 02 1500*2= 300\nTotal = 7800f\nNB déjà gérer', 7800.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-12 11:56:23', '2025-08-12 11:56:23'),
(16, 'Besoin pédagogique et administratif', 'Liste du matériel pour débuter la rentrée scolaire\n09 cartons de Craies blanches 21000*8 = 189,000F  OK\n01 carton de craies de couleurs  35000* = 35,000F OK\n20 paquets de chemises 3000*20 =60,000F OK\n20 paquets sous chemises 2500*20 = 50,000F OK \n04 paquets de marqueurs Bic ( bleu,rouge,noir) 04*2500= 10,000F OK\n06 paquets de stylos infiniTy 19,800 OK\n02 règles de 100 cm 2*1000 = 2000F OK\n80 cahiers de texte 2000*80 = 160,000F OK\n80 cahier d\'appel 1800*80 = 144,000F OK\n05 cahiers de 200 pages (registre) 05*1000 = 5,OOOF OK\n100 serpillières 25,000F le cartons OK\n50 raclettes 50*1500 = 75,000F OK\n41 sceau  41*1000 = 41,000F OK\n05 pelle à main 5*500 = 2500F OK\n02 râteaux 02*1000= 2000f OK\n30 balais traditionnel 300*30 = 9000F OK\n04 balais a manche 1500*4 = 6000 OK\n02 machettes 1500*2 = 3000F OK\n02 houes 2000*2 = 4000F OK\nO1 paquets de papier Carbonne 3000 OK\n01  paquets de papier  Cartonner 2500F  OK\n01  paquets de papier transparent 2500F OK\n02 paquets de trombone 1000*2 = 2000F OK\n04 Agrafeuses 8/4 1000*4= 4000F OK\n01 paquet de souligner 1000F OK\n04  Agrafeuses 24/6   4000F OK\n01 ballot de papier hygiénique 6500F OK\n04 paquets agrafe  8/4 1500*4 = 6000F OK\n04 paquets agrafe 24/6 6000F OK\n01 paquet encre rouge 2500F OK\n02 boites de punaises 500*2 = 1000F OK\n02 paires de ciseaux  500*2 = 1000F OK\n08 cartons pour archive 500*8 = 4000F OK\n04 cahier de 200 page 400*4 = 1600F OK\n04 cle USB 4giga 2000*4= 8000F OK\nO1 paquet de crayon 1000 OK\n05 regle de 30 cm 1000F OK\n10 gomme 1000f ok\n07 tailles crayons 1400 OK\n01 paquet de corrector 2500F OK\n05 boite de colle en 1L OK\n04 scotchs 4000F OK\n02 paquet de fronde petit 11200F OK\n01 paquet fronde grosse 10,000F OK\n10 paquets enveloppe A4 2000*10 = 20,000F OK\n10 paquets enveloppe A5 1250*10= 12500F OK\n10 paquets enveloppe A3 3500*10 = 35000F OK\n\nTotal =997,500F\nNB DEJA GERER', 997500.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-12 13:44:36', '2025-09-09 12:13:53'),
(19, 'Besoin technique', '01 paquet d\'attaches 2500F\n04 écrous                 1500F\n01 régulateur de tension 15000F\nNB : urgent\nNB déjà gérer', 19000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-14 09:11:48', '2025-08-15 06:00:51'),
(24, 'Besoin pédagoque', 'Transport pour aller déposer le règlement intérieur a la délégation \nUrgent\nNB DEJA GERER', 2000.00, 'pending', 3, NULL, NULL, NULL, 0, '2025-08-18 09:02:00', '2025-08-26 13:02:19'),
(32, 'Besoin pour la starling', 'Il faut recharger la Starling d\'ESTUAIRE DOUALA  cette semaine\nNB DEJA GERER', 60000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-19 11:50:50', '2025-08-20 09:09:38'),
(33, 'Besoin administratif', 'Contribution du SEDUC LT prévu pour le 27/08/2025 a douala  30,000F transport 5000f \nil a demander 55,000f mais il est venu me restituer 20,000f \nNB DEJA GERER', 35000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:17:12', '2025-08-20 09:17:12'),
(34, 'transport', 'Déplacement de M kamgang chris pour Yaoundé aller retour\nNB DEJA GERER', 20000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:24:24', '2025-08-20 09:24:25'),
(35, 'Besoin technique', 'achat des tubes LED regrette et ampoule\nNB DEJA GERER', 31300.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:27:41', '2025-08-20 09:27:41'),
(36, 'Besoin technique', 'Main d\'oeuvre macon travaux de terre sceller les fers au sol et poteau et de la toiture solder \nNB DEJA GERER', 40000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:35:18', '2025-08-20 09:35:18'),
(37, 'MATERIEL', 'LA vente de cable pour bus\nNB DEJA GERER', 10000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:37:03', '2025-08-20 09:37:04'),
(38, 'Besoin technique', 'ARDOISINE\n10 boites d\'ardoisine noir 4500*10 = 45000\n4 feuilles de contreplaquer 3300*4= 13200\n15l de diluant 1000*15 =                      15000\n05 rouleaux 700*5 =                          3500\n01 planche pour règle                         7000\n01 paquet de pointe toc                     1500\n01 paquet de pointe de 30              5000\nTransport                                        3000\nTOTAL  93,200   \nNB DEJA  GERER', 93200.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 11:38:41', '2025-08-26 11:38:41'),
(39, 'Besoin technique', 'DEVIS POUR LE SELAGE DU PORTAIL\n06 disques a couper 1000*6= 6000\n03 antirouilles 3000*3= 9000\n02 Diluant 1500*2= 3000\n02 rouleaux 500*2 = 1000\nTOTAL = 24,000f\nNB DEJA GERER', 24000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 11:47:32', '2025-09-05 06:44:26'),
(40, 'Besoin technique', 'DEVIS peinture dans les bureaux assistant du promoteur, guerite, bibliothèque\n04 pots de peinture à eau 17500*4 = 70,000\n06 pots de peinture à huile noir 15000\n06 pots de teinture 1500*6 = 9000\n02l de diluant                 1500*2 =3000\n04 bande adhésive        700*4= 2800\n04 rouleaux à eau 1000*4= 4000\n02 rouleaux a huile 700*2= 1400\nTOTAL = 105,000F', 105000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 12:11:43', '2025-08-26 12:11:44'),
(41, 'Besoin technique', 'TRAVAUX REGRETTES DANS LES BUREAUX CHEF DE TRAVAUX,ASSISTANT PROMOTEUR,GUERITE\n04 regrettes complet 4800*4= 19200\n02 ampoules 2500*2=5000\nTransport 2000F', 26200.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 12:17:58', '2025-08-26 12:17:58'),
(42, 'Besoin technique', 'TRAVAUX DES SEPARATIONS DANS LES SALLES DE CLASSE\n102 feuilles de contreplaquer 3300*102= 336,600\n20 lattes de 5m 20*2000= 40000\n01 paquet de pointe de 80                 = 5000\n02 paquets de pointe de 30    5000*2 = 10,000\n04 marteaux                2500*4= 10,000\nNB GERER DE MOTIER', 401600.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 12:23:36', '2025-08-27 13:34:26'),
(43, 'Besoin technique', 'Travaux plombier raccordement fuite et remplacement robinet d\'arrêt \n02 robinet d\'arrêt 3500*2= 7000\n04 coudes 500*4= 2000\n04 embout filtrer 400*4= 1600\n02 coude à compresser 900*2= 1600\n01 tubes colle pegofort 2000\n01 lefler 1000\nMain d\'oeuvre 5000\nTOTAL = 20200', 20200.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 12:32:15', '2025-08-26 12:32:15'),
(44, 'Besoin technique', 'plombier WC\nDEJA DONNER 20,000f pour sa main d\'œuvre pour le débouchage toilettes et curage\n\n06 robinets puisage   2500*6= 15000\n04 robinets presser 500*4= 2000\n04 robinets entêtons  800*4= 3200\n04 raccorde 700*4= 2800\n01 tube colle pegofort 2000\n02 lefler 1000*2= 2000\n03 coudes à compresser 900*3= 2700\nMain d\'oeuvre 9000\nTOTAL 36500', 36500.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 12:46:24', '2025-08-26 12:46:25'),
(45, 'BON DE COMMANDE', 'TENUE DE TRAVAIL AGENT D\'ENTRETIEN \nGabardine  lourd en grande largeur couleur marron pour 04 tenues( 3*4=12) 12*2200 = 26,400\n Gabardine couleur jaune pour 03 tenues (3*3=9m) 9*2200=19,800\nAccessoires     5000\nBoutonnière (par tenue) 200*7= 1400\nTransport 3000\nMain d\'œuvre 10,000', 65600.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 12:58:24', '2025-08-26 12:58:24'),
(46, 'Besoin technique', 'Main d\'œuvre agent ENEO\nNB DEJA GERE', 20000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 13:27:30', '2025-08-26 13:27:30'),
(47, 'Besoin administratif', 'Achat d\'un paquet de carnet de reçu \nNB DEJA GERER', 4000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 13:29:39', '2025-08-26 13:29:39'),
(48, 'Besoin pédagogique', 'Enroulement du conseil de classe sur la plate forme carte scolaire des élèves par M NJIKI ULRICH LANDRY INFORMATICIEN CPBD\n Doit être disponible avant le 29 aout', 15000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-27 08:38:52', '2025-08-27 08:39:19'),
(49, 'besoin pédagogique', 'Besoin des punaises pour afficher les annonces\n05 boites  05*500=2500', 2500.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-27 10:07:49', '2025-08-27 10:07:50'),
(50, 'besoin pédagogique', 'Validation du règlement intérieur ( motivation 10,000F) transport 2000f\nNB DEJA GERER', 12000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-27 12:54:10', '2025-08-27 13:32:32'),
(51, 'Besoin technique', 'Nettoyage des toilettes\n10L acide 2000*10= 20000\n05L cresyle 2000*5= 10000\n02 savon en liquide de 5l 3500*2= 7000\nMotivation des enfants 3 pour 4 jours 30000\nNB DEJA GERER', 67000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-02 07:36:09', '2025-09-05 06:41:45'),
(52, 'Besoin technique', 'Devis estimatif pour les châteaux d\'eau flotteur\n01 contacteur D40 15000\n01 disjoncteur 2pole 16A 5000\nMain d\'oeuvre 10000\nNB DEJA GERER', 30000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-02 07:38:50', '2025-09-05 06:43:03'),
(53, 'BESOIN ADMINISTRATIF', 'Achat des cartouches d\'encre pour l\'imprimante de Mlle Elong \nNB DEJA GERER', 15000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-05 06:58:01', '2025-09-05 06:58:02'),
(54, 'BESOIN ADMINISTRATIF', 'sortie de 10000f pour arranger le clavier de la machine de Mlle manuela \nNB DEJA GERER', 10000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-05 06:59:59', '2025-09-05 07:00:00'),
(55, 'BESOIN PEDAGOGIQUE', 'frais de participation du personnel au college alfred saker ( APPS) 10000f\ntransport 2000f\nNB DEJA GERER', 12000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-05 07:10:38', '2025-09-05 07:10:39'),
(56, 'Besoin administratif', 'Contribution au premier assemblée général du premier trimestre 2025/2026\nNB DEJA GERER', 750000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-05 08:05:57', '2025-09-05 08:05:58'),
(58, 'Besoin administratif', '* Transport pour aller a ndogbong récupérer les tables, chaises et bureau et rallonge a estuaire pour yassa 10,000F\n* Transport pour aller récupérer les casques a bonamoussadi puis aller a bependa expédier, puis aller a ndogbong récupérer les tables, chaises, et rallonge  5000F\n* Frais d\'expédition des casques et câbles 1000F\n* transport de M ETONA CHRIST pour ces différents déplacement ( pour 6/09/2025)  2000f\nNB DEJA GERER', 18000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-05 14:19:09', '2025-09-05 14:19:10'),
(59, 'BESOIN PEDAGOGIQUE', 'BON DE COMMANDE : CARNET DE LIVRAISON\n* Carnet de livraison section francophone 1200\n*  Carnet de livraison section Anglophone 300', 400000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-05 14:30:43', '2025-09-09 10:14:22'),
(60, 'Besoin pédagogique', 'Viste des inspecteurs pour contrôle et vérification des inscrits dans le collège\nNB DEJA GERER', 30000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-09 12:13:16', '2025-09-09 12:13:17'),
(61, 'BESOIN PEDAGOGIQUE', 'Transport pour aller récupérer le règlement intérieur a la délégation\nNB DEJA GERER', 2000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-11 09:33:21', '2025-09-11 09:33:22'),
(62, 'BESOIN ADMINISTRATIF', 'Achat du chargeur pour le téléphone de service\nNB DEJA GERER', 1500.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-11 09:42:49', '2025-09-11 09:42:50'),
(63, 'Besoin administratif', 'Transport de maître kondji\nNB DÉJA GERER', 5000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-11 09:46:07', '2025-09-11 09:46:08'),
(64, 'Besoin administratif', 'Transport pour les différents déplacement de M ETONA \nNB DÉJA GÉRER', 12000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-11 09:47:48', '2025-09-11 09:47:49'),
(65, 'Besoin administratif', 'Recharge de crédit de communication dans les deux puces du téléphone de estuaire \n2500 orange \n2500 MTN \nNB DÉJA GÉRER', 5000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-11 09:49:57', '2025-09-11 09:49:58'),
(66, 'Besoin technique', 'Bon de commande des cachets \nCenseur EST\nAssistant du promoteur \nConseiller pédagogique \nCenseur ESG\nCaisse', 48000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-11 09:51:55', '2025-09-11 09:51:56'),
(67, 'Besoin technique', 'Désinstallation, entretien général et installation des 04 climatiseurs dans les bureaux \nRemplacement d’un condensateur 30,000f\nMatériel fixation 6000f\nCiment blanc 4000f\nTuyaux pvc 1500*3=4 500 \nTuyaux évacuation d’eau 1500f\nComplément de gaz 15000f\nColle otelas 3000f\nEntretien général des climatiseurs 4*10000=40 000 \nMain d’œuvre 50,000f', 154000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-11 10:04:21', '2025-09-11 10:04:22');

-- --------------------------------------------------------

--
-- Structure de la table `parent_guardians`
--

CREATE TABLE `parent_guardians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `pin_code` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `profession` varchar(255) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parent_notifications`
--

CREATE TABLE `parent_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'normal',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_sent` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parent_student_relationships`
--

CREATE TABLE `parent_student_relationships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `relationship_type` varchar(255) NOT NULL,
  `is_primary_contact` tinyint(1) NOT NULL DEFAULT 0,
  `can_pick_up` tinyint(1) NOT NULL DEFAULT 1,
  `emergency_contact` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `versement_date` date DEFAULT NULL COMMENT 'Date de versement (pour calcul des réductions)',
  `validation_date` timestamp NULL DEFAULT NULL COMMENT 'Date de validation du paiement (automatique)',
  `payment_method` varchar(255) NOT NULL DEFAULT 'cash',
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `receipt_number` varchar(255) NOT NULL,
  `is_rame_physical` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indique si la rame a été fournie physiquement au lieu du paiement en espèces',
  `has_scholarship` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indique si ce paiement bénéficie d''une bourse',
  `scholarship_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Montant de la bourse appliquée',
  `has_reduction` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indique si ce paiement bénéficie d''une réduction',
  `reduction_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Montant de la réduction appliquée',
  `discount_reason` varchar(255) DEFAULT NULL COMMENT 'Motif du rabais (bourse/réduction)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `school_year_id`, `total_amount`, `payment_date`, `versement_date`, `validation_date`, `payment_method`, `reference_number`, `notes`, `created_by_user_id`, `receipt_number`, `is_rame_physical`, `has_scholarship`, `scholarship_amount`, `has_reduction`, `reduction_amount`, `discount_reason`, `created_at`, `updated_at`) VALUES
(30, 13, 1, 60000.00, '2025-08-04', '2025-08-04', '2025-08-04 11:25:20', 'card', NULL, NULL, 12, 'REC26250804012dQYSI', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-04 11:25:20', '2025-08-04 11:25:20'),
(32, 12, 1, 118000.00, '2025-08-04', '2025-07-25', '2025-08-04 11:39:36', 'card', NULL, NULL, 12, 'REC26250804010YNrBt', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-04 11:39:36', '2025-08-04 11:39:36'),
(35, 17, 1, 31000.00, '2025-08-04', '2025-07-28', '2025-08-04 12:58:15', 'card', NULL, NULL, 12, 'REC26250804012OgRo8', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-04 12:58:15', '2025-08-04 12:58:15'),
(36, 18, 1, 31000.00, '2025-08-04', '2025-08-04', '2025-08-04 13:17:40', 'card', NULL, NULL, 12, 'REC26250804013iZzYD', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-04 13:17:40', '2025-08-04 13:17:40'),
(37, 19, 1, 31000.00, '2025-08-04', '2025-07-28', '2025-08-04 13:31:31', 'card', NULL, NULL, 3, 'REC26250804014TRGce', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-04 13:31:31', '2025-08-04 13:31:31'),
(38, 20, 1, 31000.00, '2025-08-04', '2025-07-28', '2025-08-04 14:01:53', 'card', NULL, NULL, 3, 'REC26250804015fJR1g', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-04 14:01:53', '2025-08-04 14:01:53'),
(40, 22, 1, 31000.00, '2025-08-06', '2025-08-08', '2025-08-06 08:33:14', 'card', NULL, NULL, 3, 'REC26250806576783', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 08:33:14', '2025-08-06 08:33:14'),
(41, 23, 1, 106000.00, '2025-08-06', '2025-08-04', '2025-08-06 08:51:04', 'card', NULL, NULL, 3, 'REC26250806666620', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-06 08:51:04', '2025-08-06 08:51:04'),
(42, 24, 1, 41000.00, '2025-08-06', '2025-08-06', '2025-08-06 09:13:42', 'card', NULL, NULL, 3, 'REC26250806594614', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 09:13:42', '2025-08-06 09:13:42'),
(43, 25, 1, 31000.00, '2025-08-06', '2025-08-05', '2025-08-06 09:33:42', 'card', NULL, NULL, 3, 'REC26250806028631', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 09:33:42', '2025-08-06 09:33:42'),
(44, 26, 1, 41000.00, '2025-08-06', '2025-08-05', '2025-08-06 09:43:13', 'card', NULL, NULL, 3, 'REC26250806112335', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 09:43:13', '2025-08-06 09:43:13'),
(48, 30, 1, 41000.00, '2025-08-06', '2025-08-04', '2025-08-06 10:34:12', 'card', NULL, NULL, 3, 'REC26250806511616', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 10:34:12', '2025-08-06 10:34:12'),
(49, 31, 1, 50000.00, '2025-08-06', '2025-08-04', '2025-08-06 10:43:37', 'card', NULL, NULL, 3, 'REC26250806155971', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 10:43:37', '2025-08-06 10:43:37'),
(50, 32, 1, 60000.00, '2025-08-06', '2025-07-25', '2025-08-06 11:59:46', 'card', NULL, NULL, 12, 'REC26250806929146', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 11:59:46', '2025-08-06 11:59:46'),
(51, 32, 1, 50000.00, '2025-08-06', '2025-08-06', '2025-08-06 12:14:31', 'card', NULL, NULL, 12, 'REC26250806722098', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 12:14:31', '2025-08-06 12:14:31'),
(53, 34, 1, 86000.00, '2025-08-06', '2025-08-06', '2025-08-06 13:38:42', 'card', NULL, NULL, 12, 'REC26250806110039', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 13:38:42', '2025-08-06 13:38:42'),
(54, 36, 1, 31000.00, '2025-08-06', '2025-08-06', '2025-08-06 14:14:43', 'card', NULL, NULL, 12, 'REC26250806992373', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 14:14:43', '2025-08-06 14:14:43'),
(55, 37, 1, 31000.00, '2025-08-06', '2025-08-06', '2025-08-06 14:36:45', 'card', NULL, NULL, 12, 'REC26250806796515', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 14:36:45', '2025-08-06 14:36:45'),
(56, 38, 1, 100000.00, '2025-08-06', '2025-08-06', '2025-08-06 14:46:16', 'card', NULL, NULL, 12, 'REC26250806460336', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 14:46:16', '2025-08-06 14:46:16'),
(57, 39, 1, 31000.00, '2025-08-06', '2025-07-25', '2025-08-06 15:00:20', 'card', NULL, NULL, 12, 'REC26250806068573', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-06 15:00:20', '2025-08-06 15:00:20'),
(59, 41, 1, 31000.00, '2025-08-07', '2025-08-06', '2025-08-07 11:12:15', 'card', NULL, NULL, 12, 'REC26250807943221', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-07 11:12:15', '2025-08-07 11:12:15'),
(60, 42, 1, 21000.00, '2025-08-07', '2025-08-06', '2025-08-07 11:23:48', 'card', NULL, NULL, 3, 'REC26250807121172', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-07 11:23:48', '2025-08-07 11:23:48'),
(61, 43, 1, 41000.00, '2025-08-07', '2025-08-06', '2025-08-07 11:29:17', 'card', NULL, NULL, 3, 'REC26250807394674', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-07 11:29:17', '2025-08-07 11:29:17'),
(64, 45, 1, 50000.00, '2025-08-07', '2025-08-07', '2025-08-07 11:34:46', 'card', NULL, NULL, 3, 'REC26250807379402', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-07 11:34:46', '2025-08-07 11:34:46'),
(65, 46, 1, 31000.00, '2025-08-07', '2025-08-07', '2025-08-07 11:43:26', 'card', NULL, NULL, 3, 'REC26250807702749', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-07 11:43:26', '2025-08-07 11:43:26'),
(67, 48, 1, 31000.00, '2025-08-07', '2025-08-07', '2025-08-07 11:49:14', 'card', NULL, NULL, 3, 'REC26250807729321', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-07 11:49:14', '2025-08-07 11:49:14'),
(68, 49, 1, 103000.00, '2025-08-07', '2025-07-25', '2025-08-07 12:03:03', 'card', NULL, NULL, 3, 'REC26250807342792', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-07 12:03:03', '2025-08-07 12:03:03'),
(70, 51, 1, 50000.00, '2025-08-07', '2025-08-07', '2025-08-07 12:47:52', 'card', NULL, NULL, 12, 'REC26250807194728', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-07 12:47:52', '2025-08-07 12:47:52'),
(71, 52, 1, 50000.00, '2025-08-07', '2025-08-07', '2025-08-07 12:59:02', 'cash', NULL, NULL, 12, 'REC26250807095674', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-07 12:59:02', '2025-08-07 12:59:02'),
(73, 54, 1, 106000.00, '2025-08-07', '2025-08-07', '2025-08-07 13:39:46', 'card', NULL, NULL, 12, 'REC26250807782542', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-07 13:39:46', '2025-08-07 13:39:46'),
(79, 61, 1, 83000.00, '2025-08-11', '2025-07-25', '2025-08-11 06:49:36', 'card', '37123000003', NULL, 3, 'REC26250811566456', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-11 06:49:36', '2025-08-11 06:49:36'),
(81, 62, 1, 21000.00, '2025-08-12', '2025-08-09', '2025-08-12 06:11:49', 'card', NULL, NULL, 3, 'REC26250812154834', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-12 06:11:49', '2025-08-12 06:11:49'),
(84, 65, 1, 41000.00, '2025-08-12', '2025-07-29', '2025-08-12 07:17:33', 'card', NULL, NULL, 3, 'REC26250812080267', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-12 07:17:33', '2025-08-12 07:17:33'),
(85, 66, 1, 106000.00, '2025-08-12', '2025-08-07', '2025-08-12 10:51:43', 'card', NULL, NULL, 3, 'REC26250812790514', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-12 10:51:43', '2025-08-12 10:51:43'),
(86, 67, 1, 31000.00, '2025-08-12', '2025-08-08', '2025-08-12 10:57:17', 'card', NULL, NULL, 3, 'REC26250812757510', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-12 10:57:17', '2025-08-12 10:57:17'),
(88, 69, 1, 31000.00, '2025-08-15', '2025-08-13', '2025-08-15 06:07:19', 'cash', NULL, NULL, 3, 'REC26250815982653', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-15 06:07:19', '2025-08-15 06:07:19'),
(91, 72, 1, 70000.00, '2025-08-15', '2025-08-11', '2025-08-15 06:29:28', 'card', NULL, NULL, 3, 'REC26250815961931', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-15 06:29:28', '2025-08-15 06:29:28'),
(92, 72, 1, 31000.00, '2025-08-15', '2025-08-01', '2025-08-15 06:30:08', 'card', NULL, NULL, 3, 'REC26250815102179', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-15 06:30:08', '2025-08-15 06:30:08'),
(93, 73, 1, 60000.00, '2025-08-15', '2025-08-13', '2025-08-15 06:35:41', 'cash', NULL, NULL, 3, 'REC26250815246317', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-15 06:35:41', '2025-08-15 06:35:41'),
(94, 76, 1, 41000.00, '2025-08-19', '2025-08-18', '2025-08-19 13:02:12', 'cash', NULL, NULL, 15, 'REC26250819733581', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 13:02:12', '2025-08-19 13:02:12'),
(95, 77, 1, 41000.00, '2025-08-19', '2025-08-18', '2025-08-19 14:05:28', 'cash', NULL, NULL, 15, 'REC26250819570583', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:05:28', '2025-08-19 14:05:28'),
(97, 79, 1, 105000.00, '2025-08-19', '2025-08-18', '2025-08-19 14:15:13', 'cash', NULL, NULL, 15, 'REC26250819981686', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:15:13', '2025-08-19 14:15:13'),
(98, 80, 1, 41000.00, '2025-08-19', '2025-08-18', '2025-08-19 14:18:24', 'cash', NULL, NULL, 15, 'REC26250819870015', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:18:24', '2025-08-19 14:18:24'),
(99, 81, 1, 63000.00, '2025-08-19', '2025-08-18', '2025-08-19 14:21:50', 'cash', NULL, NULL, 15, 'REC26250819590360', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:21:50', '2025-08-19 14:21:50'),
(100, 82, 1, 31000.00, '2025-08-19', '2025-08-18', '2025-08-19 14:27:32', 'cash', NULL, NULL, 15, 'REC26250819623462', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:27:32', '2025-08-19 14:27:32'),
(101, 83, 1, 31000.00, '2025-08-19', '2025-08-18', '2025-08-19 14:35:16', 'cash', NULL, NULL, 15, 'REC26250819967802', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:35:16', '2025-08-19 14:35:16'),
(102, 84, 1, 92000.00, '2025-08-19', '2025-08-18', '2025-08-19 14:38:58', 'cash', NULL, NULL, 15, 'REC26250819740489', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:38:58', '2025-08-19 14:38:58'),
(104, 86, 1, 21000.00, '2025-08-19', '2025-08-15', '2025-08-19 14:47:58', 'cash', NULL, NULL, 15, 'REC26250819880352', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:47:58', '2025-08-19 14:47:58'),
(105, 87, 1, 44000.00, '2025-08-19', '2025-08-16', '2025-08-19 14:52:48', 'cash', NULL, NULL, 15, 'REC26250819580447', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 14:52:48', '2025-08-19 14:52:48'),
(106, 88, 1, 70000.00, '2025-08-19', '2025-08-15', '2025-08-19 15:00:48', 'cash', NULL, NULL, 15, 'REC26250819489207', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-19 15:00:48', '2025-08-19 15:00:48'),
(107, 89, 1, 92700.00, '2025-08-20', '2025-08-15', '2025-08-20 06:26:24', 'cash', NULL, NULL, 15, 'REC26250820361552', 0, 0, 0.00, 1, 10300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 06:26:24', '2025-08-20 06:26:24'),
(108, 90, 1, 115200.00, '2025-08-20', '2025-08-15', '2025-08-20 06:31:43', 'cash', NULL, NULL, 15, 'REC26250820145077', 0, 0, 0.00, 1, 12800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 06:31:43', '2025-08-20 06:31:43'),
(109, 91, 1, 31000.00, '2025-08-20', '2025-08-14', '2025-08-20 07:23:35', 'cash', NULL, NULL, 15, 'REC26250820041668', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 07:23:35', '2025-08-20 07:23:35'),
(110, 92, 1, 31000.00, '2025-08-20', '2025-08-14', '2025-08-20 07:28:16', 'cash', NULL, NULL, 15, 'REC26250820161454', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 07:28:16', '2025-08-20 07:28:16'),
(111, 93, 1, 135900.00, '2025-08-20', '2025-08-14', '2025-08-20 07:44:25', 'cash', NULL, NULL, 15, 'REC26250820336074', 0, 0, 0.00, 1, 15100.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 07:44:25', '2025-08-20 07:44:25'),
(112, 94, 1, 126900.00, '2025-08-20', '2025-08-14', '2025-08-20 07:48:04', 'cash', NULL, NULL, 15, 'REC26250820708510', 0, 0, 0.00, 1, 14100.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 07:48:04', '2025-08-20 07:48:04'),
(113, 95, 1, 31000.00, '2025-08-20', '2025-08-14', '2025-08-20 07:51:58', 'cash', NULL, NULL, 15, 'REC26250820630609', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 07:51:58', '2025-08-20 07:51:58'),
(114, 96, 1, 119700.00, '2025-08-20', '2025-08-14', '2025-08-20 07:55:23', 'cash', NULL, NULL, 15, 'REC26250820230110', 0, 0, 0.00, 1, 13300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 07:55:23', '2025-08-20 07:55:23'),
(115, 97, 1, 31000.00, '2025-08-20', '2025-08-14', '2025-08-20 08:00:35', 'cash', NULL, NULL, 15, 'REC26250820179281', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 08:00:35', '2025-08-20 08:00:35'),
(116, 98, 1, 128700.00, '2025-08-20', '2025-08-14', '2025-08-20 08:09:56', 'cash', NULL, NULL, 15, 'REC26250820671447', 0, 0, 0.00, 1, 14300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 08:09:56', '2025-08-20 08:09:56'),
(117, 99, 1, 73000.00, '2025-08-20', '2025-08-15', '2025-08-20 08:24:54', 'cash', NULL, NULL, 15, 'REC26250820257359', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 08:24:54', '2025-08-20 08:24:54'),
(118, 100, 1, 21000.00, '2025-08-20', '2025-08-14', '2025-08-20 09:27:31', 'cash', NULL, NULL, 15, 'REC26250820543524', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 09:27:31', '2025-08-20 09:27:31'),
(119, 101, 1, 31000.00, '2025-08-20', '2025-08-13', '2025-08-20 09:31:34', 'cash', NULL, NULL, 15, 'REC26250820957829', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 09:31:34', '2025-08-20 09:31:34'),
(120, 101, 1, 42000.00, '2025-08-20', '2025-08-20', '2025-08-20 09:31:54', 'cash', NULL, NULL, 15, 'REC26250820536519', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 09:31:54', '2025-08-20 09:31:54'),
(121, 102, 1, 110700.00, '2025-08-20', '2025-08-14', '2025-08-20 09:39:30', 'cash', NULL, NULL, 15, 'REC26250820484530', 0, 0, 0.00, 1, 12300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 09:39:30', '2025-08-20 09:39:30'),
(122, 104, 1, 31000.00, '2025-08-20', '2025-08-14', '2025-08-20 09:52:26', 'cash', NULL, NULL, 15, 'REC26250820991666', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 09:52:26', '2025-08-20 09:52:26'),
(123, 105, 1, 41000.00, '2025-08-20', '2025-08-14', '2025-08-20 10:02:59', 'cash', NULL, NULL, 15, 'REC26250820395490', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 10:02:59', '2025-08-20 10:02:59'),
(124, 106, 1, 146700.00, '2025-08-20', '2025-08-14', '2025-08-20 10:06:09', 'cash', NULL, NULL, 15, 'REC26250820791719', 0, 0, 0.00, 1, 16300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 10:06:09', '2025-08-20 10:06:09'),
(125, 107, 1, 146700.00, '2025-08-20', '2025-08-14', '2025-08-20 10:11:07', 'cash', NULL, NULL, 15, 'REC26250820820423', 0, 0, 0.00, 1, 16300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 10:11:07', '2025-08-20 10:11:07'),
(126, 108, 1, 106200.00, '2025-08-20', '2025-08-14', '2025-08-20 10:22:55', 'cash', NULL, NULL, 15, 'REC26250820058316', 0, 0, 0.00, 1, 11800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 10:22:55', '2025-08-20 10:22:55'),
(127, 109, 1, 92700.00, '2025-08-20', '2025-08-14', '2025-08-20 10:59:56', 'cash', NULL, NULL, 15, 'REC26250820114387', 0, 0, 0.00, 1, 10300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 10:59:56', '2025-08-20 10:59:56'),
(128, 110, 1, 31000.00, '2025-08-20', '2025-08-14', '2025-08-20 11:02:53', 'cash', NULL, NULL, 15, 'REC26250820999529', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 11:02:54', '2025-08-20 11:02:54'),
(129, 111, 1, 41000.00, '2025-08-20', '2025-08-14', '2025-08-20 11:05:28', 'cash', NULL, NULL, 15, 'REC26250820725526', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 11:05:28', '2025-08-20 11:05:28'),
(130, 112, 1, 31000.00, '2025-08-20', '2025-08-14', '2025-08-20 11:09:03', 'cash', NULL, NULL, 15, 'REC26250820719925', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 11:09:03', '2025-08-20 11:09:03'),
(131, 114, 1, 97200.00, '2025-08-20', '2025-08-13', '2025-08-20 11:18:42', 'cash', NULL, NULL, 15, 'REC26250820192483', 0, 0, 0.00, 1, 10800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 11:18:42', '2025-08-20 11:18:42'),
(132, 115, 1, 45000.00, '2025-08-20', '2025-08-13', '2025-08-20 11:22:22', 'cash', NULL, NULL, 15, 'REC26250820476957', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 11:22:22', '2025-08-20 11:22:22'),
(133, 116, 1, 31000.00, '2025-08-20', '2025-08-13', '2025-08-20 11:25:45', 'cash', NULL, NULL, 15, 'REC26250820322395', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 11:25:45', '2025-08-20 11:25:45'),
(134, 117, 1, 126900.00, '2025-08-20', '2025-08-13', '2025-08-20 11:30:36', 'cash', NULL, NULL, 15, 'REC26250820608182', 0, 0, 0.00, 1, 14100.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 11:30:36', '2025-08-20 11:30:36'),
(135, 118, 1, 31000.00, '2025-08-20', '2025-08-13', '2025-08-20 11:34:11', 'cash', NULL, NULL, 15, 'REC26250820664895', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 11:34:11', '2025-08-20 11:34:11'),
(136, 119, 1, 31000.00, '2025-08-20', '2025-08-13', '2025-08-20 11:37:28', 'cash', NULL, NULL, 15, 'REC26250820825829', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-20 11:37:28', '2025-08-20 11:37:28'),
(137, 120, 1, 31000.00, '2025-08-20', '2025-08-13', '2025-08-20 12:23:54', 'cash', NULL, NULL, 15, 'REC26250820040771', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 12:23:54', '2025-08-20 12:23:54'),
(138, 121, 1, 41000.00, '2025-08-20', '2025-08-13', '2025-08-20 12:26:52', 'cash', NULL, NULL, 15, 'REC26250820481449', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 12:26:52', '2025-08-20 12:26:52'),
(139, 122, 1, 113400.00, '2025-08-20', '2025-08-13', '2025-08-20 12:30:17', 'cash', NULL, NULL, 15, 'REC26250820132871', 0, 0, 0.00, 1, 12600.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 12:30:17', '2025-08-20 12:30:17'),
(140, 123, 1, 60000.00, '2025-08-20', '2025-08-13', '2025-08-20 13:52:52', 'cash', NULL, NULL, 15, 'REC26250820245331', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-20 13:52:52', '2025-08-20 13:52:52'),
(141, 124, 1, 119700.00, '2025-08-20', '2025-08-12', '2025-08-20 14:02:07', 'cash', NULL, NULL, 15, 'REC26250820790160', 0, 0, 0.00, 1, 13300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 14:02:07', '2025-08-20 14:02:07'),
(142, 125, 1, 135900.00, '2025-08-20', '2025-08-12', '2025-08-20 14:06:02', 'cash', NULL, NULL, 15, 'REC26250820672030', 0, 0, 0.00, 1, 15100.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 14:06:02', '2025-08-20 14:06:02'),
(143, 126, 1, 113400.00, '2025-08-20', '2025-08-12', '2025-08-20 14:10:15', 'cash', NULL, NULL, 15, 'REC26250820337480', 0, 0, 0.00, 1, 12600.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-20 14:10:15', '2025-08-20 14:10:15'),
(144, 127, 1, 126000.00, '2025-08-26', '2025-08-25', '2025-08-26 11:09:59', 'cash', NULL, NULL, 16, 'REC26250826558970', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(146, 103, 1, 133200.00, '2025-08-27', '2025-08-14', '2025-08-27 09:25:47', 'cash', NULL, NULL, 15, 'REC26250827495067', 0, 0, 0.00, 1, 14800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-27 09:25:47', '2025-08-27 09:25:47'),
(147, 129, 1, 31000.00, '2025-08-27', '2025-08-26', '2025-08-27 09:28:48', 'cash', NULL, NULL, 15, 'REC26250827129434', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 09:28:48', '2025-08-27 09:28:48'),
(148, 130, 1, 40000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:00:42', 'cash', NULL, NULL, 15, 'REC26250827562536', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:00:42', '2025-08-27 10:00:42'),
(149, 131, 1, 153000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:04:07', 'cash', NULL, NULL, 15, 'REC26250827854368', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:04:07', '2025-08-27 10:04:07'),
(150, 133, 1, 95000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:14:12', 'cash', NULL, NULL, 15, 'REC26250827737297', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:14:12', '2025-08-27 10:14:12'),
(151, 134, 1, 41000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:17:03', 'cash', NULL, NULL, 15, 'REC26250827871577', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:17:03', '2025-08-27 10:17:03'),
(152, 132, 1, 133200.00, '2025-08-27', '2025-08-13', '2025-08-27 10:18:17', 'cash', NULL, NULL, 15, 'REC26250827337587', 0, 0, 0.00, 1, 14800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-27 10:18:17', '2025-08-27 10:18:17'),
(153, 135, 1, 123000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:21:37', 'cash', NULL, NULL, 15, 'REC26250827095292', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:21:37', '2025-08-27 10:21:37'),
(154, 136, 1, 85000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:25:18', 'cash', NULL, NULL, 15, 'REC26250827058676', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:25:18', '2025-08-27 10:25:18'),
(155, 137, 1, 31000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:28:14', 'cash', NULL, NULL, 15, 'REC26250827106083', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:28:14', '2025-08-27 10:28:14'),
(156, 138, 1, 133000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:31:29', 'cash', NULL, NULL, 15, 'REC26250827993297', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:31:29', '2025-08-27 10:31:29'),
(157, 139, 1, 41000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:34:32', 'cash', NULL, NULL, 15, 'REC26250827370720', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:34:32', '2025-08-27 10:34:32'),
(158, 140, 1, 31000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:37:09', 'cash', NULL, NULL, 15, 'REC26250827033030', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:37:09', '2025-08-27 10:37:09'),
(159, 141, 1, 31000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:41:14', 'cash', NULL, NULL, 15, 'REC26250827783687', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:41:14', '2025-08-27 10:41:14'),
(160, 142, 1, 31000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:43:50', 'cash', NULL, NULL, 15, 'REC26250827120314', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:43:50', '2025-08-27 10:43:50'),
(163, 144, 1, 73000.00, '2025-08-27', '2025-08-26', '2025-08-27 11:09:27', 'cash', NULL, NULL, 15, 'REC26250827677830', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 11:09:27', '2025-08-27 11:09:27'),
(164, 145, 1, 41000.00, '2025-08-27', '2025-08-26', '2025-08-27 11:15:04', 'cash', NULL, NULL, 15, 'REC26250827367884', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 11:15:04', '2025-08-27 11:15:04'),
(165, 146, 1, 31000.00, '2025-08-27', '2025-08-26', '2025-08-27 11:22:36', 'cash', NULL, NULL, 15, 'REC26250827108952', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 11:22:36', '2025-08-27 11:22:36'),
(166, 147, 1, 31000.00, '2025-08-27', '2025-08-12', '2025-08-27 11:29:26', 'cash', NULL, NULL, 15, 'REC26250827083807', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 11:29:26', '2025-08-27 11:29:26'),
(167, 148, 1, 31000.00, '2025-08-27', '2025-08-18', '2025-08-27 11:39:46', 'cash', NULL, NULL, 15, 'REC26250827924732', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 11:39:46', '2025-08-27 11:39:46'),
(168, 149, 1, 110700.00, '2025-08-27', '2025-08-12', '2025-08-27 12:16:47', 'cash', NULL, NULL, 15, 'REC26250827730825', 0, 0, 0.00, 1, 12300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-27 12:16:47', '2025-08-27 12:16:47'),
(169, 150, 1, 70000.00, '2025-08-27', '2025-08-12', '2025-08-27 12:22:59', 'cash', NULL, NULL, 15, 'REC26250827132818', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 12:22:59', '2025-08-27 12:22:59'),
(170, 151, 1, 88000.00, '2025-08-27', '2025-08-12', '2025-08-27 12:26:35', 'cash', NULL, NULL, 15, 'REC26250827933662', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 12:26:35', '2025-08-27 12:26:35'),
(171, 152, 1, 21000.00, '2025-08-27', '2025-08-12', '2025-08-27 12:39:42', 'cash', NULL, NULL, 15, 'REC26250827132338', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 12:39:42', '2025-08-27 12:39:42'),
(172, 153, 1, 73000.00, '2025-08-27', '2025-08-12', '2025-08-27 12:42:59', 'cash', NULL, NULL, 15, 'REC26250827074798', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 12:42:59', '2025-08-27 12:42:59'),
(173, 154, 1, 40000.00, '2025-08-27', '2025-08-12', '2025-08-27 12:46:49', 'cash', NULL, NULL, 15, 'REC26250827368678', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 12:46:49', '2025-08-27 12:46:49'),
(174, 155, 1, 21000.00, '2025-08-27', '2025-08-12', '2025-08-27 12:51:42', 'cash', NULL, NULL, 15, 'REC26250827282862', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 12:51:42', '2025-08-27 12:51:42'),
(175, 156, 1, 31000.00, '2025-08-27', '2025-08-12', '2025-08-27 12:59:42', 'cash', NULL, NULL, 15, 'REC26250827980516', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 12:59:42', '2025-08-27 12:59:42'),
(176, 157, 1, 31000.00, '2025-08-27', '2025-08-12', '2025-08-27 13:08:18', 'cash', NULL, NULL, 15, 'REC26250827560272', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 13:08:18', '2025-08-27 13:08:18'),
(177, 158, 1, 31000.00, '2025-08-27', '2025-08-12', '2025-08-27 13:13:19', 'cash', NULL, NULL, 15, 'REC26250827201619', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 13:13:19', '2025-08-27 13:13:19'),
(178, 159, 1, 83000.00, '2025-08-27', '2025-08-21', '2025-08-27 13:18:47', 'cash', NULL, NULL, 15, 'REC26250827623330', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 13:18:47', '2025-08-27 13:18:47'),
(179, 160, 1, 31000.00, '2025-08-27', '2025-08-21', '2025-08-27 13:24:33', 'cash', NULL, NULL, 15, 'REC26250827492385', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-27 13:24:33', '2025-08-27 13:24:33'),
(180, 161, 1, 21000.00, '2025-08-27', '2025-08-21', '2025-08-27 13:27:17', 'cash', NULL, NULL, 15, 'REC26250827512374', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 13:27:17', '2025-08-27 13:27:17'),
(181, 162, 1, 98000.00, '2025-08-27', '2025-08-21', '2025-08-27 13:30:23', 'cash', NULL, NULL, 15, 'REC26250827270878', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 13:30:23', '2025-08-27 13:30:23'),
(182, 163, 1, 21000.00, '2025-08-27', '2025-08-21', '2025-08-27 13:42:01', 'cash', NULL, NULL, 15, 'REC26250827452576', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 13:42:01', '2025-08-27 13:42:01'),
(183, 164, 1, 96000.00, '2025-08-27', '2025-08-19', '2025-08-27 13:45:29', 'cash', NULL, NULL, 15, 'REC26250827645126', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 13:45:29', '2025-08-27 13:45:29'),
(184, 26, 1, 112000.00, '2025-08-27', '2025-08-22', '2025-08-27 13:47:55', 'cash', NULL, NULL, 15, 'REC26250827169985', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 13:47:55', '2025-08-27 13:47:55'),
(185, 165, 1, 128700.00, '2025-08-27', '2025-08-12', '2025-08-27 13:53:47', 'cash', NULL, NULL, 15, 'REC26250827973117', 0, 0, 0.00, 1, 14300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-27 13:53:47', '2025-08-27 13:53:47'),
(186, 166, 1, 46000.00, '2025-08-27', '2025-08-20', '2025-08-27 13:59:20', 'cash', NULL, NULL, 15, 'REC26250827917181', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 13:59:20', '2025-08-27 13:59:20'),
(187, 167, 1, 46000.00, '2025-08-28', '2025-08-20', '2025-08-28 06:19:22', 'cash', NULL, NULL, 15, 'REC26250828593323', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 06:19:22', '2025-08-28 06:19:22'),
(188, 168, 1, 143000.00, '2025-08-28', '2025-08-21', '2025-08-28 06:23:15', 'cash', NULL, NULL, 15, 'REC26250828381124', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 06:23:15', '2025-08-28 06:23:15'),
(189, 169, 1, 21000.00, '2025-08-28', '2025-08-21', '2025-08-28 06:30:15', 'cash', NULL, NULL, 15, 'REC26250828430140', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 06:30:15', '2025-08-28 06:30:15'),
(190, 170, 1, 56000.00, '2025-08-28', '2025-08-12', '2025-08-28 07:18:40', 'cash', NULL, NULL, 15, 'REC26250828789395', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 07:18:40', '2025-08-28 07:18:40'),
(191, 171, 1, 31000.00, '2025-08-28', '2025-08-12', '2025-08-28 07:21:50', 'cash', NULL, NULL, 15, 'REC26250828235191', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 07:21:50', '2025-08-28 07:21:50'),
(192, 172, 1, 83000.00, '2025-08-28', '2025-08-12', '2025-08-28 07:25:38', 'cash', NULL, NULL, 15, 'REC26250828556962', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 07:25:38', '2025-08-28 07:25:38'),
(193, 173, 1, 83000.00, '2025-08-28', '2025-08-12', '2025-08-28 07:28:55', 'cash', NULL, NULL, 15, 'REC26250828147952', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 07:28:55', '2025-08-28 07:28:55'),
(194, 174, 1, 110700.00, '2025-08-28', '2025-08-12', '2025-08-28 07:33:34', 'cash', NULL, NULL, 15, 'REC26250828501303', 0, 0, 0.00, 1, 12300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 07:33:34', '2025-08-28 07:33:34'),
(195, 175, 1, 92700.00, '2025-08-28', '2025-08-12', '2025-08-28 07:35:58', 'cash', NULL, NULL, 15, 'REC26250828156478', 0, 0, 0.00, 1, 10300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 07:35:58', '2025-08-28 07:35:58'),
(196, 176, 1, 135900.00, '2025-08-28', '2025-08-12', '2025-08-28 07:39:21', 'cash', NULL, NULL, 15, 'REC26250828130042', 0, 0, 0.00, 1, 15100.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 07:39:21', '2025-08-28 07:39:21'),
(197, 177, 1, 110700.00, '2025-08-28', '2025-08-08', '2025-08-28 07:42:35', 'cash', NULL, NULL, 15, 'REC26250828747286', 0, 0, 0.00, 1, 12300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 07:42:35', '2025-08-28 07:42:35'),
(198, 178, 1, 110700.00, '2025-08-28', '2025-07-28', '2025-08-28 07:48:22', 'cash', NULL, NULL, 15, 'REC26250828825587', 0, 0, 0.00, 1, 12300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 07:48:22', '2025-08-28 07:48:22'),
(199, 179, 1, 137700.00, '2025-08-28', '2025-08-12', '2025-08-28 07:53:35', 'cash', NULL, NULL, 15, 'REC26250828459520', 0, 0, 0.00, 1, 15300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 07:53:35', '2025-08-28 07:53:35'),
(200, 180, 1, 92700.00, '2025-08-28', '2025-08-12', '2025-08-28 07:57:35', 'cash', NULL, NULL, 15, 'REC26250828515515', 0, 0, 0.00, 1, 10300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 07:57:35', '2025-08-28 07:57:35'),
(201, 181, 1, 55000.00, '2025-08-28', '2025-08-12', '2025-08-28 08:05:11', 'cash', NULL, NULL, 15, 'REC26250828154445', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 08:05:11', '2025-08-28 08:05:11'),
(202, 182, 1, 128700.00, '2025-08-28', '2025-08-12', '2025-08-28 08:08:36', 'cash', NULL, NULL, 15, 'REC26250828721949', 0, 0, 0.00, 1, 14300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 08:08:36', '2025-08-28 08:08:36'),
(203, 183, 1, 45000.00, '2025-08-28', '2025-08-12', '2025-08-28 08:11:41', 'cash', NULL, NULL, 15, 'REC26250828831191', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 08:11:41', '2025-08-28 08:11:41'),
(204, 184, 1, 137700.00, '2025-08-28', '2025-08-12', '2025-08-28 08:14:02', 'cash', NULL, NULL, 15, 'REC26250828307924', 0, 0, 0.00, 1, 15300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-28 08:14:02', '2025-08-28 08:14:02'),
(205, 185, 1, 21000.00, '2025-08-28', '2025-08-25', '2025-08-28 08:17:32', 'cash', NULL, NULL, 15, 'REC26250828713320', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 08:17:32', '2025-08-28 08:17:32'),
(206, 186, 1, 133000.00, '2025-08-28', '2025-08-23', '2025-08-28 08:27:30', 'cash', NULL, NULL, 15, 'REC26250828814178', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 08:27:30', '2025-08-28 08:27:30'),
(207, 187, 1, 41000.00, '2025-08-28', '2025-08-25', '2025-08-28 08:33:47', 'cash', NULL, NULL, 15, 'REC26250828705178', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 08:33:47', '2025-08-28 08:33:47'),
(208, 188, 1, 31000.00, '2025-08-28', '2025-08-25', '2025-08-28 10:13:12', 'cash', NULL, NULL, 15, 'REC26250828176148', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:13:12', '2025-08-28 10:13:12'),
(209, 189, 1, 21000.00, '2025-08-28', '2025-08-25', '2025-08-28 10:17:46', 'cash', NULL, NULL, 15, 'REC26250828072892', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:17:46', '2025-08-28 10:17:46'),
(210, 190, 1, 98000.00, '2025-08-28', '2025-08-25', '2025-08-28 10:21:06', 'cash', NULL, NULL, 15, 'REC26250828962421', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:21:06', '2025-08-28 10:21:06'),
(211, 191, 1, 41000.00, '2025-08-28', '2025-08-25', '2025-08-28 10:26:46', 'cash', NULL, NULL, 15, 'REC26250828130243', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:26:46', '2025-08-28 10:26:46'),
(212, 192, 1, 46000.00, '2025-08-28', '2025-08-22', '2025-08-28 10:29:43', 'cash', NULL, NULL, 15, 'REC26250828090619', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:29:43', '2025-08-28 10:29:43'),
(213, 193, 1, 57000.00, '2025-08-28', '2025-08-19', '2025-08-28 10:33:35', 'cash', NULL, NULL, 15, 'REC26250828608205', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:33:35', '2025-08-28 10:33:35'),
(214, 194, 1, 80000.00, '2025-08-28', '2025-08-19', '2025-08-28 10:38:18', 'cash', NULL, NULL, 15, 'REC26250828280034', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:38:18', '2025-08-28 10:38:18'),
(215, 195, 1, 153000.00, '2025-08-28', '2025-08-19', '2025-08-28 10:40:52', 'cash', NULL, NULL, 15, 'REC26250828722115', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:40:52', '2025-08-28 10:40:52'),
(216, 196, 1, 153000.00, '2025-08-28', '2025-08-19', '2025-08-28 10:43:53', 'cash', NULL, NULL, 15, 'REC26250828992459', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:43:53', '2025-08-28 10:43:53'),
(217, 197, 1, 41000.00, '2025-08-28', '2025-08-20', '2025-08-28 10:47:06', 'cash', NULL, NULL, 15, 'REC26250828717145', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:47:06', '2025-08-28 10:47:06'),
(218, 198, 1, 21000.00, '2025-08-28', '2025-08-25', '2025-08-28 10:49:54', 'cash', NULL, NULL, 15, 'REC26250828578655', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:49:54', '2025-08-28 10:49:54'),
(219, 199, 1, 31000.00, '2025-08-28', '2025-08-25', '2025-08-28 10:54:03', 'cash', NULL, NULL, 15, 'REC26250828532640', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:54:03', '2025-08-28 10:54:03'),
(220, 200, 1, 123000.00, '2025-08-28', '2025-08-25', '2025-08-28 10:57:27', 'cash', NULL, NULL, 15, 'REC26250828690507', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 10:57:27', '2025-08-28 10:57:27'),
(221, 201, 1, 73000.00, '2025-08-28', '2025-08-25', '2025-08-28 11:02:42', 'cash', NULL, NULL, 15, 'REC26250828601002', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-28 11:02:42', '2025-08-28 11:02:42'),
(222, 202, 1, 21000.00, '2025-08-28', '2025-08-25', '2025-08-28 11:05:40', 'cash', NULL, NULL, 15, 'REC26250828467916', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:05:40', '2025-08-28 11:05:40'),
(223, 203, 1, 108000.00, '2025-08-28', '2025-08-25', '2025-08-28 11:09:39', 'cash', NULL, NULL, 15, 'REC26250828754557', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:09:39', '2025-08-28 11:09:39'),
(224, 204, 1, 113000.00, '2025-08-28', '2025-08-25', '2025-08-28 11:12:06', 'cash', NULL, NULL, 15, 'REC26250828454664', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:12:06', '2025-08-28 11:12:06'),
(225, 205, 1, 41000.00, '2025-08-28', '2025-08-25', '2025-08-28 11:14:44', 'cash', NULL, NULL, 15, 'REC26250828583017', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:14:44', '2025-08-28 11:14:44'),
(226, 206, 1, 21000.00, '2025-08-28', '2025-08-25', '2025-08-28 11:17:23', 'cash', NULL, NULL, 15, 'REC26250828259624', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:17:23', '2025-08-28 11:17:23'),
(227, 207, 1, 41000.00, '2025-08-28', '2025-08-22', '2025-08-28 11:22:38', 'cash', NULL, NULL, 15, 'REC26250828234641', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:22:38', '2025-08-28 11:22:38'),
(228, 208, 1, 31000.00, '2025-08-28', '2025-08-22', '2025-08-28 11:26:08', 'cash', NULL, NULL, 15, 'REC26250828022985', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:26:08', '2025-08-28 11:26:08'),
(229, 209, 1, 133000.00, '2025-08-28', '2025-08-22', '2025-08-28 11:29:13', 'cash', NULL, NULL, 15, 'REC26250828232494', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:29:13', '2025-08-28 11:29:13'),
(230, 210, 1, 31000.00, '2025-08-28', '2025-08-22', '2025-08-28 11:31:47', 'cash', NULL, NULL, 15, 'REC26250828280436', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:31:47', '2025-08-28 11:31:47'),
(231, 211, 1, 31000.00, '2025-08-28', '2025-08-22', '2025-08-28 11:35:51', 'cash', NULL, NULL, 15, 'REC26250828494455', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:35:51', '2025-08-28 11:35:51'),
(232, 212, 1, 103000.00, '2025-08-28', '2025-08-22', '2025-08-28 11:40:02', 'cash', NULL, NULL, 15, 'REC26250828819130', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:40:02', '2025-08-28 11:40:02'),
(233, 213, 1, 31000.00, '2025-08-28', '2025-08-19', '2025-08-28 11:47:56', 'cash', NULL, NULL, 15, 'REC26250828284771', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-28 11:47:56', '2025-08-28 11:47:56'),
(234, 214, 1, 31000.00, '2025-08-28', '2025-08-13', '2025-08-28 11:53:50', 'cash', NULL, NULL, 15, 'REC26250828996333', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-28 11:53:50', '2025-08-28 11:53:50'),
(235, 215, 1, 42000.00, '2025-08-28', '2025-08-13', '2025-08-28 11:59:19', 'cash', NULL, NULL, 15, 'REC26250828940304', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-28 11:59:19', '2025-08-28 11:59:19'),
(236, 216, 1, 31000.00, '2025-08-29', '2025-08-29', '2025-08-29 10:58:48', 'cash', NULL, NULL, 15, 'REC26250829887200', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-29 10:58:48', '2025-08-29 10:58:48'),
(237, 217, 1, 73000.00, '2025-08-29', '2025-08-19', '2025-08-29 13:27:42', 'cash', NULL, NULL, 16, 'REC26250829923975', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-29 13:27:42', '2025-08-29 13:27:42'),
(238, 218, 1, 53000.00, '2025-08-29', '2025-08-19', '2025-08-29 13:36:44', 'cash', NULL, NULL, 16, 'REC26250829274443', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-29 13:36:44', '2025-08-29 13:36:44'),
(239, 219, 1, 41000.00, '2025-08-30', '2025-08-29', '2025-08-30 05:50:51', 'cash', NULL, NULL, 16, 'REC26250830153579', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 05:50:51', '2025-08-30 05:50:51'),
(240, 220, 1, 31000.00, '2025-08-30', '2025-08-29', '2025-08-30 05:55:21', 'cash', NULL, NULL, 16, 'REC26250830579953', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 05:55:21', '2025-08-30 05:55:21'),
(241, 221, 1, 31000.00, '2025-08-30', '2025-08-29', '2025-08-30 05:59:27', 'cash', NULL, NULL, 16, 'REC26250830237810', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 05:59:27', '2025-08-30 05:59:27'),
(242, 222, 1, 41000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:04:06', 'cash', NULL, NULL, 16, 'REC26250830907543', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:04:06', '2025-08-30 06:04:06'),
(243, 223, 1, 41000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:04:40', 'cash', NULL, NULL, 15, 'REC26250830577350', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:04:40', '2025-08-30 06:04:40'),
(244, 224, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:07:24', 'cash', NULL, NULL, 15, 'REC26250830736265', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:07:24', '2025-08-30 06:07:24'),
(245, 225, 1, 100000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:09:48', 'cash', NULL, NULL, 16, 'REC26250830908847', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:09:48', '2025-08-30 06:09:48'),
(246, 226, 1, 111000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:10:15', 'cash', NULL, NULL, 15, 'REC26250830751898', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:10:15', '2025-08-30 06:10:15'),
(247, 227, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:13:00', 'cash', NULL, NULL, 15, 'REC26250830272613', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:13:00', '2025-08-30 06:13:00'),
(248, 228, 1, 31000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:13:54', 'cash', NULL, NULL, 16, 'REC26250830383083', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:13:54', '2025-08-30 06:13:54'),
(249, 229, 1, 31000.00, '2025-08-30', '2025-08-19', '2025-08-30 06:16:08', 'cash', NULL, NULL, 15, 'REC26250830584574', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:16:08', '2025-08-30 06:16:08'),
(250, 230, 1, 31000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:18:25', 'cash', NULL, NULL, 16, 'REC26250830147789', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:18:25', '2025-08-30 06:18:25'),
(251, 231, 1, 44000.00, '2025-08-30', '2025-08-25', '2025-08-30 06:20:41', 'cash', NULL, NULL, 15, 'REC26250830400648', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:20:41', '2025-08-30 06:20:41'),
(252, 232, 1, 151000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:22:39', 'cash', NULL, NULL, 16, 'REC26250830770255', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:22:39', '2025-08-30 06:22:39'),
(253, 233, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:23:22', 'cash', NULL, NULL, 15, 'REC26250830889042', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:23:22', '2025-08-30 06:23:22'),
(254, 235, 1, 51000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:26:19', 'cash', NULL, NULL, 15, 'REC26250830458636', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:26:19', '2025-08-30 06:26:19'),
(255, 234, 1, 83000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:26:44', 'cash', NULL, NULL, 16, 'REC26250830456660', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:26:44', '2025-08-30 06:26:44'),
(256, 236, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:29:09', 'cash', NULL, NULL, 15, 'REC26250830552540', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-30 06:29:09', '2025-08-30 06:29:09'),
(257, 237, 1, 118000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:30:16', 'cash', NULL, NULL, 16, 'REC26250830871322', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:30:16', '2025-08-30 06:30:16'),
(258, 238, 1, 98000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:32:01', 'cash', NULL, NULL, 15, 'REC26250830523682', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-30 06:32:01', '2025-08-30 06:32:01'),
(259, 239, 1, 41000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:34:32', 'cash', NULL, NULL, 16, 'REC26250830269657', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:34:32', '2025-08-30 06:34:32'),
(260, 240, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:34:50', 'cash', NULL, NULL, 15, 'REC26250830816633', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:34:50', '2025-08-30 06:34:50'),
(261, 241, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 06:38:45', 'cash', NULL, NULL, 15, 'REC26250830215590', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:38:45', '2025-08-30 06:38:45'),
(262, 242, 1, 31000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:42:03', 'cash', NULL, NULL, 16, 'REC26250830107361', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:42:03', '2025-08-30 06:42:03'),
(263, 243, 1, 133000.00, '2025-08-30', '2025-08-28', '2025-08-30 06:42:22', 'cash', NULL, NULL, 15, 'REC26250830782028', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:42:22', '2025-08-30 06:42:22'),
(264, 244, 1, 21000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:45:10', 'cash', NULL, NULL, 16, 'REC26250830650528', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:45:10', '2025-08-30 06:45:10'),
(265, 245, 1, 118000.00, '2025-08-30', '2025-08-28', '2025-08-30 06:45:24', 'cash', NULL, NULL, 15, 'REC26250830005730', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:45:24', '2025-08-30 06:45:24'),
(266, 246, 1, 31000.00, '2025-08-30', '2025-08-28', '2025-08-30 06:48:32', 'cash', NULL, NULL, 15, 'REC26250830107505', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:48:32', '2025-08-30 06:48:32'),
(267, 247, 1, 31000.00, '2025-08-30', '2025-08-29', '2025-08-30 06:48:40', 'cash', NULL, NULL, 16, 'REC26250830391343', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:48:40', '2025-08-30 06:48:40'),
(268, 248, 1, 31000.00, '2025-08-30', '2025-08-28', '2025-08-30 06:50:51', 'cash', NULL, NULL, 15, 'REC26250830298282', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:50:51', '2025-08-30 06:50:51'),
(269, 249, 1, 31000.00, '2025-08-30', '2025-08-20', '2025-08-30 06:54:04', 'cash', NULL, NULL, 16, 'REC26250830406022', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:54:04', '2025-08-30 06:54:04'),
(270, 250, 1, 108000.00, '2025-08-30', '2025-08-28', '2025-08-30 06:54:05', 'cash', NULL, NULL, 15, 'REC26250830382056', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 06:54:05', '2025-08-30 06:54:05'),
(271, 251, 1, 31000.00, '2025-08-30', '2025-08-28', '2025-08-30 06:57:38', 'cash', NULL, NULL, 15, 'REC26250830521270', 0, 1, 20000.00, 0, 0.00, NULL, '2025-08-30 06:57:38', '2025-08-30 06:57:38'),
(272, 252, 1, 119700.00, '2025-08-30', '2025-08-12', '2025-08-30 06:58:56', 'cash', NULL, NULL, 16, 'REC26250830319185', 0, 0, 0.00, 1, 13300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-30 06:58:56', '2025-08-30 06:58:56'),
(273, 253, 1, 31000.00, '2025-08-30', '2025-08-28', '2025-08-30 07:00:44', 'cash', NULL, NULL, 15, 'REC26250830697011', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:00:44', '2025-08-30 07:00:44'),
(275, 255, 1, 31000.00, '2025-08-30', '2025-08-28', '2025-08-30 07:03:47', 'cash', NULL, NULL, 15, 'REC26250830909241', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:03:47', '2025-08-30 07:03:47'),
(276, 256, 1, 31000.00, '2025-08-30', '2025-08-28', '2025-08-30 07:07:00', 'cash', NULL, NULL, 15, 'REC26250830055963', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:07:00', '2025-08-30 07:07:00'),
(277, 257, 1, 41000.00, '2025-08-30', '2025-08-28', '2025-08-30 07:09:47', 'cash', NULL, NULL, 15, 'REC26250830470124', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:09:47', '2025-08-30 07:09:47'),
(278, 258, 1, 100000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:11:46', 'cash', NULL, NULL, 16, 'REC26250830984111', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:11:46', '2025-08-30 07:11:46'),
(279, 259, 1, 103000.00, '2025-08-30', '2025-08-28', '2025-08-30 07:13:26', 'cash', NULL, NULL, 15, 'REC26250830888506', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:13:26', '2025-08-30 07:13:26'),
(280, 260, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:15:05', 'cash', NULL, NULL, 16, 'REC26250830610525', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:15:05', '2025-08-30 07:15:05'),
(281, 261, 1, 117000.00, '2025-08-30', '2025-08-28', '2025-08-30 07:17:26', 'cash', NULL, NULL, 15, 'REC26250830657299', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:17:26', '2025-08-30 07:17:26'),
(282, 262, 1, 41000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:19:43', 'cash', NULL, NULL, 16, 'REC26250830312984', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:19:43', '2025-08-30 07:19:43'),
(283, 263, 1, 100000.00, '2025-08-30', '2025-08-28', '2025-08-30 07:21:24', 'cash', NULL, NULL, 15, 'REC26250830348981', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:21:24', '2025-08-30 07:21:24'),
(284, 264, 1, 113400.00, '2025-08-30', '2025-07-25', '2025-08-30 07:23:43', 'cash', NULL, NULL, 15, 'REC26250830850845', 0, 0, 0.00, 1, 12600.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-08-30 07:23:43', '2025-08-30 07:23:43'),
(285, 265, 1, 100000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:26:08', 'cash', NULL, NULL, 16, 'REC26250830255940', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:26:08', '2025-08-30 07:26:08'),
(286, 266, 1, 31000.00, '2025-08-30', '2025-07-25', '2025-08-30 07:27:05', 'cash', NULL, NULL, 15, 'REC26250830080541', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:27:05', '2025-08-30 07:27:05'),
(287, 267, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:29:44', 'cash', NULL, NULL, 16, 'REC26250830023990', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:29:44', '2025-08-30 07:29:44'),
(288, 269, 1, 85000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:38:07', 'cash', NULL, NULL, 16, 'REC26250830885374', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:38:07', '2025-08-30 07:38:07'),
(289, 270, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:42:15', 'cash', NULL, NULL, 16, 'REC26250830328074', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:42:15', '2025-08-30 07:42:15'),
(290, 271, 1, 21000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:47:58', 'cash', NULL, NULL, 16, 'REC26250830211233', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:47:58', '2025-08-30 07:47:58'),
(291, 272, 1, 94500.00, '2025-08-30', '2025-08-27', '2025-08-30 07:52:11', 'cash', NULL, NULL, 16, 'REC26250830285293', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:52:11', '2025-08-30 07:52:11'),
(292, 273, 1, 40000.00, '2025-08-30', '2025-08-27', '2025-08-30 07:56:18', 'cash', NULL, NULL, 16, 'REC26250830173153', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:56:18', '2025-08-30 07:56:18'),
(293, 274, 1, 41000.00, '2025-08-30', '2025-08-30', '2025-08-30 07:59:08', 'cash', NULL, NULL, 15, 'REC26250830976025', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 07:59:08', '2025-08-30 07:59:08'),
(294, 275, 1, 123000.00, '2025-08-30', '2025-08-27', '2025-08-30 08:00:55', 'cash', NULL, NULL, 16, 'REC26250830450997', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 08:00:55', '2025-08-30 08:00:55'),
(295, 254, 1, 31000.00, '2025-08-30', '2025-08-27', '2025-08-30 08:20:20', 'cash', NULL, NULL, 15, 'REC26250830001847', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 08:20:20', '2025-08-30 08:20:20'),
(296, 276, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 08:26:30', 'cash', NULL, NULL, 15, 'REC26250830419671', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 08:26:30', '2025-08-30 08:26:30'),
(297, 277, 1, 133000.00, '2025-08-30', '2025-08-30', '2025-08-30 08:36:10', 'cash', NULL, NULL, 15, 'REC26250830175185', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 08:36:10', '2025-08-30 08:36:10'),
(298, 278, 1, 78000.00, '2025-08-30', '2025-08-30', '2025-08-30 08:39:11', 'cash', NULL, NULL, 15, 'REC26250830734632', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 08:39:11', '2025-08-30 08:39:11'),
(299, 279, 1, 41000.00, '2025-08-30', '2025-08-29', '2025-08-30 08:53:44', 'cash', NULL, NULL, 15, 'REC26250830106976', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 08:53:44', '2025-08-30 08:53:44'),
(300, 280, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 08:56:44', 'cash', NULL, NULL, 15, 'REC26250830607474', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 08:56:44', '2025-08-30 08:56:44'),
(301, 281, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 08:58:36', 'cash', NULL, NULL, 15, 'REC26250830762462', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 08:58:36', '2025-08-30 08:58:36'),
(302, 282, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 09:00:44', 'cash', NULL, NULL, 15, 'REC26250830021822', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 09:00:44', '2025-08-30 09:00:44'),
(303, 283, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 09:02:51', 'cash', NULL, NULL, 15, 'REC26250830143594', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 09:02:51', '2025-08-30 09:02:51'),
(304, 284, 1, 31000.00, '2025-08-30', '2025-08-20', '2025-08-30 09:14:49', 'cash', NULL, NULL, 16, 'REC26250830403921', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 09:14:49', '2025-08-30 09:14:49'),
(305, 285, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 09:16:44', 'cash', NULL, NULL, 15, 'REC26250830871447', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 09:16:44', '2025-08-30 09:16:44'),
(306, 286, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 09:38:31', 'cash', NULL, NULL, 15, 'REC26250830524461', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 09:38:31', '2025-08-30 09:38:31'),
(307, 287, 1, 78000.00, '2025-08-30', '2025-08-30', '2025-08-30 09:59:33', 'cash', NULL, NULL, 15, 'REC26250830980016', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 09:59:33', '2025-08-30 09:59:33'),
(308, 288, 1, 78000.00, '2025-08-30', '2025-08-30', '2025-08-30 10:01:40', 'cash', NULL, NULL, 15, 'REC26250830876062', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 10:01:40', '2025-08-30 10:01:40'),
(309, 289, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 10:20:08', 'cash', NULL, NULL, 15, 'REC26250830664735', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 10:20:08', '2025-08-30 10:20:08');
INSERT INTO `payments` (`id`, `student_id`, `school_year_id`, `total_amount`, `payment_date`, `versement_date`, `validation_date`, `payment_method`, `reference_number`, `notes`, `created_by_user_id`, `receipt_number`, `is_rame_physical`, `has_scholarship`, `scholarship_amount`, `has_reduction`, `reduction_amount`, `discount_reason`, `created_at`, `updated_at`) VALUES
(310, 290, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 10:22:16', 'cash', NULL, NULL, 15, 'REC26250830754373', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 10:22:16', '2025-08-30 10:22:16'),
(311, 10, 1, 92700.00, '2025-09-01', '2025-07-30', '2025-09-01 05:04:42', 'cash', NULL, NULL, 15, 'REC26250901984348', 0, 0, 0.00, 1, 10300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-01 05:04:42', '2025-09-01 05:04:42'),
(312, 291, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 05:38:34', 'cash', NULL, NULL, 15, 'REC26250901332080', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 05:38:34', '2025-09-01 05:38:34'),
(313, 6, 1, 106200.00, '2025-09-01', '2025-07-29', '2025-09-01 05:48:39', 'cash', NULL, NULL, 15, 'REC26250901301022', 0, 0, 0.00, 1, 11800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-01 05:48:39', '2025-09-01 05:48:39'),
(314, 292, 1, 50000.00, '2025-09-01', '2025-09-01', '2025-09-01 06:02:54', 'cash', NULL, NULL, 15, 'REC26250901539924', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 06:02:54', '2025-09-01 06:02:54'),
(315, 8, 1, 92700.00, '2025-09-01', '2025-07-30', '2025-09-01 06:12:10', 'cash', NULL, NULL, 15, 'REC26250901904077', 0, 0, 0.00, 1, 10300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-01 06:12:10', '2025-09-01 06:12:10'),
(316, 11, 1, 31000.00, '2025-09-01', '2025-07-31', '2025-09-01 06:17:29', 'cash', NULL, NULL, 15, 'REC26250901057334', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 06:17:29', '2025-09-01 06:17:29'),
(317, 293, 1, 103000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:12:19', 'cash', NULL, NULL, 16, 'REC26250901133993', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:12:19', '2025-09-01 07:12:19'),
(318, 294, 1, 103000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:17:01', 'cash', NULL, NULL, 16, 'REC26250901240443', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:17:01', '2025-09-01 07:17:01'),
(319, 295, 1, 70000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:21:53', 'cash', NULL, NULL, 16, 'REC26250901355597', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:21:53', '2025-09-01 07:21:53'),
(320, 296, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:27:45', 'cash', NULL, NULL, 16, 'REC26250901482038', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:27:45', '2025-09-01 07:27:45'),
(321, 297, 1, 91000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:37:39', 'cash', NULL, NULL, 16, 'REC26250901173134', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:37:39', '2025-09-01 07:37:39'),
(322, 298, 1, 153000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:40:10', 'cash', NULL, NULL, 15, 'REC26250901095679', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:40:10', '2025-09-01 07:40:10'),
(323, 299, 1, 53000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:46:35', 'cash', NULL, NULL, 16, 'REC26250901729571', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:46:35', '2025-09-01 07:46:35'),
(324, 300, 1, 41000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:52:48', 'cash', NULL, NULL, 16, 'REC26250901163656', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:52:48', '2025-09-01 07:52:48'),
(325, 301, 1, 73000.00, '2025-09-01', '2025-09-01', '2025-09-01 07:57:41', 'cash', NULL, NULL, 15, 'REC26250901351112', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 07:57:41', '2025-09-01 07:57:41'),
(326, 302, 1, 41000.00, '2025-09-01', '2025-09-01', '2025-09-01 08:02:06', 'cash', NULL, NULL, 16, 'REC26250901583625', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 08:02:06', '2025-09-01 08:02:06'),
(327, 303, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 08:07:54', 'cash', NULL, NULL, 16, 'REC26250901623517', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 08:07:54', '2025-09-01 08:07:54'),
(328, 304, 1, 51000.00, '2025-09-01', '2025-09-01', '2025-09-01 08:12:34', 'cash', NULL, NULL, 16, 'REC26250901020905', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 08:12:34', '2025-09-01 08:12:34'),
(329, 305, 1, 100000.00, '2025-09-01', '2025-09-01', '2025-09-01 08:26:04', 'cash', NULL, NULL, 16, 'REC26250901024948', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 08:26:04', '2025-09-01 08:26:04'),
(330, 306, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 08:52:57', 'cash', NULL, NULL, 16, 'REC26250901188948', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 08:52:57', '2025-09-01 08:52:57'),
(331, 307, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 08:57:08', 'cash', NULL, NULL, 16, 'REC26250901897599', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 08:57:08', '2025-09-01 08:57:08'),
(332, 308, 1, 60000.00, '2025-09-01', '2025-09-01', '2025-09-01 09:02:46', 'cash', NULL, NULL, 16, 'REC26250901037476', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 09:02:46', '2025-09-01 09:02:46'),
(333, 309, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 09:20:08', 'cash', NULL, NULL, 16, 'REC26250901023261', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 09:20:08', '2025-09-01 09:20:08'),
(334, 310, 1, 41000.00, '2025-09-01', '2025-09-01', '2025-09-01 09:40:56', 'cash', NULL, NULL, 16, 'REC26250901246218', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 09:40:56', '2025-09-01 09:40:56'),
(335, 311, 1, 146700.00, '2025-09-01', '2025-08-04', '2025-09-01 10:07:39', 'cash', NULL, NULL, 15, 'REC26250901889098', 0, 0, 0.00, 1, 16300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-01 10:07:39', '2025-09-01 10:07:39'),
(336, 312, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 10:12:53', 'cash', NULL, NULL, 16, 'REC26250901696218', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 10:12:53', '2025-09-01 10:12:53'),
(337, 313, 1, 88000.00, '2025-09-01', '2025-09-01', '2025-09-01 10:24:29', 'cash', NULL, NULL, 16, 'REC26250901826292', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 10:24:29', '2025-09-01 10:24:29'),
(338, 314, 1, 95000.00, '2025-09-01', '2025-09-01', '2025-09-01 10:42:13', 'cash', NULL, NULL, 16, 'REC26250901234716', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 10:42:13', '2025-09-01 10:42:13'),
(339, 315, 1, 100000.00, '2025-09-01', '2025-09-01', '2025-09-01 10:46:10', 'cash', NULL, NULL, 16, 'REC26250901180889', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 10:46:10', '2025-09-01 10:46:10'),
(340, 316, 1, 100000.00, '2025-09-01', '2025-09-01', '2025-09-01 10:57:31', 'cash', NULL, NULL, 16, 'REC26250901698282', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 10:57:31', '2025-09-01 10:57:31'),
(341, 317, 1, 135000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:06:36', 'cash', NULL, NULL, 16, 'REC26250901570928', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:06:36', '2025-09-01 12:06:36'),
(342, 4, 1, 31000.00, '2025-09-01', '2025-07-31', '2025-09-01 12:06:58', 'cash', NULL, NULL, 15, 'REC26250901298796', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:06:58', '2025-09-01 12:06:58'),
(343, 318, 1, 41000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:11:25', 'cash', NULL, NULL, 16, 'REC26250901513274', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:11:25', '2025-09-01 12:11:25'),
(344, 319, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:18:14', 'cash', NULL, NULL, 15, 'REC26250901527241', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:18:14', '2025-09-01 12:18:14'),
(345, 323, 1, 46000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:32:55', 'cash', NULL, NULL, 15, 'REC26250901688884', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:32:55', '2025-09-01 12:32:55'),
(346, 324, 1, 80000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:46:57', 'cash', NULL, NULL, 15, 'REC26250901739384', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:46:57', '2025-09-01 12:46:57'),
(347, 322, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:47:15', 'cash', NULL, NULL, 16, 'REC26250901567981', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:47:15', '2025-09-01 12:47:15'),
(348, 321, 1, 31000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:48:55', 'cash', NULL, NULL, 16, 'REC26250901421597', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:48:55', '2025-09-01 12:48:55'),
(349, 320, 1, 21000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:50:23', 'cash', NULL, NULL, 16, 'REC26250901890471', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:50:23', '2025-09-01 12:50:23'),
(350, 325, 1, 41000.00, '2025-09-01', '2025-09-01', '2025-09-01 12:54:46', 'cash', NULL, NULL, 15, 'REC26250901644089', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 12:54:46', '2025-09-01 12:54:46'),
(351, 326, 1, 21000.00, '2025-09-01', '2025-09-01', '2025-09-01 13:05:16', 'cash', NULL, NULL, 15, 'REC26250901827048', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 13:05:16', '2025-09-01 13:05:16'),
(352, 3, 1, 31000.00, '2025-09-01', '2025-07-31', '2025-09-01 13:12:37', 'cash', NULL, NULL, 15, 'REC26250901630615', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-01 13:12:37', '2025-09-01 13:12:37'),
(354, 328, 1, 113400.00, '2025-09-02', '2025-08-13', '2025-09-02 04:27:40', 'cash', NULL, NULL, 15, 'REC26250902878015', 0, 0, 0.00, 1, 12600.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:27:40', '2025-09-02 04:27:40'),
(355, 329, 1, 92700.00, '2025-09-02', '2025-08-14', '2025-09-02 04:32:47', 'cash', NULL, NULL, 15, 'REC26250902348120', 0, 0, 0.00, 1, 10300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:32:47', '2025-09-02 04:32:47'),
(356, 330, 1, 137700.00, '2025-09-02', '2025-07-25', '2025-09-02 04:35:55', 'cash', NULL, NULL, 15, 'REC26250902767978', 0, 0, 0.00, 1, 15300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:35:55', '2025-09-02 04:35:55'),
(357, 331, 1, 137700.00, '2025-09-02', '2025-08-06', '2025-09-02 04:39:17', 'cash', NULL, NULL, 15, 'REC26250902597736', 0, 0, 0.00, 1, 15300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:39:17', '2025-09-02 04:39:17'),
(358, 332, 1, 92700.00, '2025-09-02', '2025-08-05', '2025-09-02 04:42:52', 'cash', NULL, NULL, 15, 'REC26250902784262', 0, 0, 0.00, 1, 10300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:42:52', '2025-09-02 04:42:52'),
(359, 333, 1, 115200.00, '2025-09-02', '2025-08-04', '2025-09-02 04:46:05', 'cash', NULL, NULL, 15, 'REC26250902127420', 0, 0, 0.00, 1, 12800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:46:05', '2025-09-02 04:46:05'),
(360, 334, 1, 133200.00, '2025-09-02', '2025-07-28', '2025-09-02 04:49:55', 'cash', NULL, NULL, 15, 'REC26250902563519', 0, 0, 0.00, 1, 14800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:49:55', '2025-09-02 04:49:55'),
(361, 335, 1, 119700.00, '2025-09-02', '2025-08-07', '2025-09-02 04:54:18', 'cash', NULL, NULL, 15, 'REC26250902944079', 0, 0, 0.00, 1, 13300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:54:18', '2025-09-02 04:54:18'),
(362, 336, 1, 146700.00, '2025-09-02', '2025-08-09', '2025-09-02 04:59:04', 'cash', NULL, NULL, 15, 'REC26250902771032', 0, 0, 0.00, 1, 16300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 04:59:04', '2025-09-02 04:59:04'),
(363, 337, 1, 97200.00, '2025-09-02', '2025-07-28', '2025-09-02 05:03:26', 'cash', NULL, NULL, 15, 'REC26250902556044', 0, 0, 0.00, 1, 10800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 05:03:26', '2025-09-02 05:03:26'),
(364, 338, 1, 113400.00, '2025-09-02', '2025-07-25', '2025-09-02 05:06:59', 'cash', NULL, NULL, 15, 'REC26250902022894', 0, 0, 0.00, 1, 12600.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 05:06:59', '2025-09-02 05:06:59'),
(365, 339, 1, 110700.00, '2025-09-02', '2025-08-05', '2025-09-02 05:15:08', 'cash', NULL, NULL, 15, 'REC26250902619876', 0, 0, 0.00, 1, 12300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 05:15:08', '2025-09-02 05:15:08'),
(366, 340, 1, 31000.00, '2025-09-02', '2025-08-04', '2025-09-02 05:23:48', 'cash', NULL, NULL, 15, 'REC26250902886062', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 05:23:48', '2025-09-02 05:23:48'),
(367, 341, 1, 83000.00, '2025-09-02', '2025-08-08', '2025-09-02 05:27:08', 'cash', NULL, NULL, 15, 'REC26250902999340', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(368, 342, 1, 110700.00, '2025-09-02', '2025-08-07', '2025-09-02 05:31:03', 'cash', NULL, NULL, 15, 'REC26250902316116', 0, 0, 0.00, 1, 12300.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 05:31:03', '2025-09-02 05:31:03'),
(369, 343, 1, 106200.00, '2025-09-02', '2025-08-09', '2025-09-02 05:34:18', 'cash', NULL, NULL, 15, 'REC26250902265546', 0, 0, 0.00, 1, 11800.00, 'Réduction 10% - Paiement intégral avant échéance', '2025-09-02 05:34:18', '2025-09-02 05:34:18'),
(370, 344, 1, 80000.00, '2025-09-02', '2025-09-02', '2025-09-02 05:44:35', 'cash', NULL, NULL, 15, 'REC26250902651937', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 05:44:35', '2025-09-02 05:44:35'),
(371, 345, 1, 73000.00, '2025-09-02', '2025-09-02', '2025-09-02 05:47:47', 'cash', NULL, NULL, 15, 'REC26250902115952', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 05:47:47', '2025-09-02 05:47:47'),
(372, 346, 1, 103000.00, '2025-09-02', '2025-09-02', '2025-09-02 05:50:54', 'cash', NULL, NULL, 15, 'REC26250902827581', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 05:50:54', '2025-09-02 05:50:54'),
(373, 347, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 06:24:39', 'cash', NULL, NULL, 15, 'REC26250902128687', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 06:24:39', '2025-09-02 06:24:39'),
(374, 348, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 06:40:58', 'cash', NULL, NULL, 15, 'REC26250902761957', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 06:40:58', '2025-09-02 06:40:58'),
(375, 349, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 06:42:51', 'cash', NULL, NULL, 15, 'REC26250902078182', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 06:42:51', '2025-09-02 06:42:51'),
(376, 350, 1, 53000.00, '2025-09-02', '2025-09-02', '2025-09-02 06:51:10', 'cash', NULL, NULL, 16, 'REC26250902948119', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 06:51:10', '2025-09-02 06:51:10'),
(377, 351, 1, 93000.00, '2025-09-02', '2025-09-02', '2025-09-02 06:55:31', 'cash', NULL, NULL, 16, 'REC26250902911151', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 06:55:31', '2025-09-02 06:55:31'),
(378, 352, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 07:06:23', 'cash', NULL, NULL, 16, 'REC26250902440829', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 07:06:23', '2025-09-02 07:06:23'),
(379, 353, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 07:26:21', 'cash', NULL, NULL, 16, 'REC26250902639575', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 07:26:21', '2025-09-02 07:26:21'),
(380, 354, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 07:44:33', 'cash', NULL, NULL, 16, 'REC26250902795155', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 07:44:33', '2025-09-02 07:44:33'),
(381, 355, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 07:48:29', 'cash', NULL, NULL, 16, 'REC26250902291375', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 07:48:29', '2025-09-02 07:48:29'),
(382, 356, 1, 50000.00, '2025-09-02', '2025-09-02', '2025-09-02 07:55:47', 'cash', NULL, NULL, 16, 'REC26250902783225', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 07:55:47', '2025-09-02 07:55:47'),
(383, 357, 1, 143000.00, '2025-09-02', '2025-09-02', '2025-09-02 07:56:11', 'cash', NULL, NULL, 15, 'REC26250902927438', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 07:56:11', '2025-09-02 07:56:11'),
(384, 358, 1, 50000.00, '2025-09-02', '2025-09-02', '2025-09-02 08:02:23', 'cash', NULL, NULL, 16, 'REC26250902203848', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 08:02:23', '2025-09-02 08:02:23'),
(385, 359, 1, 70000.00, '2025-09-02', '2025-09-02', '2025-09-02 08:07:52', 'cash', NULL, NULL, 15, 'REC26250902650286', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 08:07:52', '2025-09-02 08:07:52'),
(386, 360, 1, 50000.00, '2025-09-02', '2025-09-02', '2025-09-02 08:20:55', 'cash', NULL, NULL, 15, 'REC26250902221265', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 08:20:55', '2025-09-02 08:20:55'),
(387, 361, 1, 56000.00, '2025-09-02', '2025-09-02', '2025-09-02 08:40:17', 'cash', NULL, NULL, 16, 'REC26250902082432', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 08:40:17', '2025-09-02 08:40:17'),
(388, 362, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 08:48:30', 'cash', NULL, NULL, 16, 'REC26250902059842', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 08:48:30', '2025-09-02 08:48:30'),
(389, 97, 1, 50000.00, '2025-09-02', '2025-09-02', '2025-09-02 08:57:13', 'cash', NULL, NULL, 15, 'REC26250902602918', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 08:57:13', '2025-09-02 08:57:13'),
(390, 363, 1, 101000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:06:45', 'cash', NULL, NULL, 15, 'REC26250902698201', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:06:45', '2025-09-02 09:06:45'),
(391, 364, 1, 133000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:15:32', 'cash', NULL, NULL, 15, 'REC26250902113676', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:15:32', '2025-09-02 09:15:32'),
(392, 365, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:17:41', 'cash', NULL, NULL, 16, 'REC26250902658500', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:17:41', '2025-09-02 09:17:41'),
(393, 366, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:35:12', 'cash', NULL, NULL, 15, 'REC26250902984645', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:35:12', '2025-09-02 09:35:12'),
(394, 367, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:36:21', 'cash', NULL, NULL, 16, 'REC26250902439206', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:36:21', '2025-09-02 09:36:21'),
(395, 368, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:37:16', 'cash', NULL, NULL, 15, 'REC26250902799394', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:37:16', '2025-09-02 09:37:16'),
(396, 369, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:39:15', 'cash', NULL, NULL, 15, 'REC26250902428599', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-02 09:39:15', '2025-09-02 09:39:15'),
(397, 370, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:39:54', 'cash', NULL, NULL, 16, 'REC26250902206055', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:39:54', '2025-09-02 09:39:54'),
(398, 371, 1, 50000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:45:26', 'cash', NULL, NULL, 15, 'REC26250902096344', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:45:26', '2025-09-02 09:45:26'),
(399, 372, 1, 50000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:48:48', 'cash', NULL, NULL, 15, 'REC26250902307408', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:48:48', '2025-09-02 09:48:48'),
(400, 373, 1, 60000.00, '2025-09-02', '2025-09-02', '2025-09-02 09:59:36', 'cash', NULL, NULL, 15, 'REC26250902525374', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 09:59:36', '2025-09-02 09:59:36'),
(401, 374, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 10:01:03', 'cash', NULL, NULL, 16, 'REC26250902473690', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 10:01:03', '2025-09-02 10:01:03'),
(402, 375, 1, 40000.00, '2025-09-02', '2025-09-02', '2025-09-02 10:02:12', 'cash', NULL, NULL, 15, 'REC26250902519527', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 10:02:12', '2025-09-02 10:02:12'),
(403, 376, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 10:04:34', 'cash', NULL, NULL, 16, 'REC26250902887247', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 10:04:34', '2025-09-02 10:04:34'),
(404, 377, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 10:06:50', 'cash', NULL, NULL, 15, 'REC26250902501788', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 10:06:50', '2025-09-02 10:06:50'),
(405, 378, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 10:09:50', 'cash', NULL, NULL, 15, 'REC26250902181972', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 10:09:50', '2025-09-02 10:09:50'),
(406, 379, 1, 70000.00, '2025-09-02', '2025-09-02', '2025-09-02 10:11:42', 'cash', NULL, NULL, 16, 'REC26250902060284', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 10:11:42', '2025-09-02 10:11:42'),
(407, 380, 1, 71000.00, '2025-09-02', '2025-09-02', '2025-09-02 10:14:23', 'cash', NULL, NULL, 15, 'REC26250902813143', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 10:14:23', '2025-09-02 10:14:23'),
(408, 381, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 10:21:29', 'cash', NULL, NULL, 15, 'REC26250902819913', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 10:21:29', '2025-09-02 10:21:29'),
(409, 382, 1, 108000.00, '2025-09-02', '2025-09-02', '2025-09-02 11:35:15', 'cash', NULL, NULL, 15, 'REC26250902906123', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 11:35:15', '2025-09-02 11:35:15'),
(410, 383, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 11:37:40', 'cash', NULL, NULL, 15, 'REC26250902768476', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 11:37:40', '2025-09-02 11:37:40'),
(411, 384, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 11:41:13', 'cash', NULL, NULL, 15, 'REC26250902390748', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 11:41:13', '2025-09-02 11:41:13'),
(412, 385, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 11:43:17', 'cash', NULL, NULL, 15, 'REC26250902964930', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 11:43:17', '2025-09-02 11:43:17'),
(413, 386, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 11:45:16', 'cash', NULL, NULL, 15, 'REC26250902377847', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 11:45:16', '2025-09-02 11:45:16'),
(414, 387, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 11:57:46', 'cash', NULL, NULL, 15, 'REC26250902840480', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 11:57:46', '2025-09-02 11:57:46'),
(415, 388, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 12:02:14', 'cash', NULL, NULL, 15, 'REC26250902017376', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 12:02:14', '2025-09-02 12:02:14'),
(416, 389, 1, 31000.00, '2025-09-02', '2025-09-02', '2025-09-02 12:06:24', 'cash', NULL, NULL, 15, 'REC26250902186385', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 12:06:24', '2025-09-02 12:06:24'),
(417, 390, 1, 70000.00, '2025-09-02', '2025-09-02', '2025-09-02 12:11:59', 'cash', NULL, NULL, 15, 'REC26250902826579', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 12:11:59', '2025-09-02 12:11:59'),
(418, 391, 1, 42000.00, '2025-09-02', '2025-09-02', '2025-09-02 13:15:01', 'cash', NULL, NULL, 16, 'REC26250902728962', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 13:15:01', '2025-09-02 13:15:01'),
(419, 392, 1, 43000.00, '2025-09-02', '2025-09-02', '2025-09-02 13:18:11', 'cash', NULL, NULL, 16, 'REC26250902141633', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 13:18:11', '2025-09-02 13:18:11'),
(420, 393, 1, 41000.00, '2025-09-02', '2025-09-02', '2025-09-02 13:27:10', 'cash', NULL, NULL, 15, 'REC26250902152197', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 13:27:10', '2025-09-02 13:27:10'),
(421, 394, 1, 45000.00, '2025-09-03', '2025-09-03', '2025-09-03 05:58:45', 'cash', NULL, NULL, 15, 'REC26250903603480', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 05:58:45', '2025-09-03 05:58:45'),
(422, 395, 1, 83000.00, '2025-09-03', '2025-09-03', '2025-09-03 06:08:41', 'cash', NULL, NULL, 15, 'REC26250903871414', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-03 06:08:41', '2025-09-03 06:08:41'),
(423, 396, 1, 41000.00, '2025-09-03', '2025-09-03', '2025-09-03 06:10:45', 'cash', NULL, NULL, 15, 'REC26250903798635', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 06:10:45', '2025-09-03 06:10:45'),
(424, 397, 1, 45000.00, '2025-09-03', '2025-09-03', '2025-09-03 06:17:25', 'cash', NULL, NULL, 15, 'REC26250903774377', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 06:17:25', '2025-09-03 06:17:25'),
(425, 398, 1, 48000.00, '2025-09-03', '2025-09-03', '2025-09-03 06:31:59', 'cash', NULL, NULL, 15, 'REC26250903303409', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 06:31:59', '2025-09-03 06:31:59'),
(426, 399, 1, 21000.00, '2025-09-03', '2025-09-03', '2025-09-03 07:13:35', 'cash', NULL, NULL, 15, 'REC26250903877384', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 07:13:35', '2025-09-03 07:13:35'),
(427, 400, 1, 70000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:06:38', 'cash', NULL, NULL, 15, 'REC26250903507363', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:06:38', '2025-09-03 08:06:38'),
(428, 401, 1, 45000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:11:43', 'cash', NULL, NULL, 15, 'REC26250903610629', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:11:43', '2025-09-03 08:11:43'),
(429, 402, 1, 54000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:15:38', 'cash', NULL, NULL, 15, 'REC26250903165255', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:15:38', '2025-09-03 08:15:38'),
(430, 403, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:28:32', 'cash', NULL, NULL, 15, 'REC26250903593459', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:28:32', '2025-09-03 08:28:32'),
(431, 72, 1, 20000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:35:36', 'cash', NULL, NULL, 15, 'REC26250903725893', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:35:36', '2025-09-03 08:35:36'),
(432, 404, 1, 55000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:39:22', 'cash', NULL, NULL, 15, 'REC26250903717241', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:39:22', '2025-09-03 08:39:22'),
(433, 405, 1, 55000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:41:22', 'cash', NULL, NULL, 15, 'REC26250903368958', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:41:22', '2025-09-03 08:41:22'),
(434, 406, 1, 41000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:48:10', 'cash', NULL, NULL, 15, 'REC26250903984068', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:48:10', '2025-09-03 08:48:10'),
(435, 407, 1, 56000.00, '2025-09-03', '2025-09-03', '2025-09-03 08:54:59', 'cash', NULL, NULL, 15, 'REC26250903379544', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 08:54:59', '2025-09-03 08:54:59'),
(436, 408, 1, 41000.00, '2025-09-03', '2025-09-03', '2025-09-03 09:04:18', 'cash', NULL, NULL, 15, 'REC26250903008421', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:04:18', '2025-09-03 09:04:18'),
(437, 409, 1, 41000.00, '2025-09-03', '2025-09-03', '2025-09-03 09:06:16', 'cash', NULL, NULL, 15, 'REC26250903347541', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:06:16', '2025-09-03 09:06:16'),
(438, 410, 1, 55000.00, '2025-09-03', '2025-09-03', '2025-09-03 09:09:14', 'cash', NULL, NULL, 15, 'REC26250903207289', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:09:14', '2025-09-03 09:09:14'),
(439, 412, 1, 41000.00, '2025-09-03', '2025-09-03', '2025-09-03 09:22:42', 'cash', NULL, NULL, 15, 'REC26250903636329', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:22:42', '2025-09-03 09:22:42'),
(440, 411, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 09:23:39', 'cash', NULL, NULL, 15, 'REC26250903925879', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:23:39', '2025-09-03 09:23:39'),
(441, 413, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 09:28:12', 'cash', NULL, NULL, 15, 'REC26250903059550', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:28:12', '2025-09-03 09:28:12'),
(442, 414, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 09:30:51', 'cash', NULL, NULL, 15, 'REC26250903933213', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:30:51', '2025-09-03 09:30:51'),
(443, 415, 1, 50000.00, '2025-09-03', '2025-09-03', '2025-09-03 09:35:51', 'cash', NULL, NULL, 15, 'REC26250903780763', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-03 09:35:51', '2025-09-03 09:35:51'),
(444, 417, 1, 36500.00, '2025-09-03', '2025-09-03', '2025-09-03 09:50:00', 'cash', NULL, NULL, 16, 'REC26250903200311', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:50:00', '2025-09-03 09:50:00'),
(445, 418, 1, 46500.00, '2025-09-03', '2025-09-03', '2025-09-03 09:55:14', 'cash', NULL, NULL, 16, 'REC26250903143275', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 09:55:14', '2025-09-03 09:55:14'),
(446, 416, 1, 95000.00, '2025-09-03', '2025-09-03', '2025-09-03 10:01:10', 'cash', NULL, NULL, 15, 'REC26250903889362', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 10:01:10', '2025-09-03 10:01:10'),
(447, 419, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 10:06:19', 'cash', NULL, NULL, 16, 'REC26250903813785', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 10:06:19', '2025-09-03 10:06:19'),
(448, 420, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 10:11:37', 'cash', NULL, NULL, 16, 'REC26250903668497', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 10:11:37', '2025-09-03 10:11:37'),
(449, 421, 1, 91000.00, '2025-09-03', '2025-09-03', '2025-09-03 10:18:09', 'cash', NULL, NULL, 16, 'REC26250903857326', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 10:18:09', '2025-09-03 10:18:09'),
(450, 422, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 10:32:25', 'cash', NULL, NULL, 16, 'REC26250903894787', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 10:32:25', '2025-09-03 10:32:25'),
(451, 423, 1, 50000.00, '2025-09-03', '2025-09-03', '2025-09-03 10:35:23', 'cash', NULL, NULL, 16, 'REC26250903532435', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 10:35:23', '2025-09-03 10:35:23'),
(452, 424, 1, 103000.00, '2025-09-03', '2025-09-03', '2025-09-03 10:38:11', 'cash', NULL, NULL, 16, 'REC26250903665677', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 10:38:11', '2025-09-03 10:38:11'),
(453, 425, 1, 100000.00, '2025-09-03', '2025-09-03', '2025-09-03 10:49:54', 'cash', NULL, NULL, 16, 'REC26250903152847', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 10:49:54', '2025-09-03 10:49:54'),
(454, 426, 1, 40000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:00:38', 'cash', NULL, NULL, 16, 'REC26250903949482', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:00:38', '2025-09-03 11:00:38'),
(455, 427, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:15:45', 'cash', NULL, NULL, 16, 'REC26250903116540', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:15:45', '2025-09-03 11:15:45'),
(456, 428, 1, 71000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:28:28', 'cash', NULL, NULL, 16, 'REC26250903112778', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:28:28', '2025-09-03 11:28:28'),
(457, 429, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:39:15', 'cash', NULL, NULL, 16, 'REC26250903582269', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:39:15', '2025-09-03 11:39:15'),
(458, 430, 1, 50000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:45:36', 'cash', NULL, NULL, 16, 'REC26250903662463', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:45:36', '2025-09-03 11:45:36'),
(459, 431, 1, 41000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:45:46', 'cash', NULL, NULL, 15, 'REC26250903386701', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:45:46', '2025-09-03 11:45:46'),
(460, 433, 1, 21000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:51:08', 'cash', NULL, NULL, 15, 'REC26250903101385', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:51:08', '2025-09-03 11:51:08'),
(461, 432, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:52:04', 'cash', NULL, NULL, 16, 'REC26250903480349', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:52:04', '2025-09-03 11:52:04'),
(462, 434, 1, 40000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:57:24', 'cash', NULL, NULL, 15, 'REC26250903432877', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:57:24', '2025-09-03 11:57:24'),
(463, 435, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 11:58:39', 'cash', NULL, NULL, 16, 'REC26250903438413', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 11:58:39', '2025-09-03 11:58:39'),
(464, 436, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 12:05:34', 'cash', NULL, NULL, 16, 'REC26250903661362', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 12:05:34', '2025-09-03 12:05:34'),
(465, 437, 1, 40000.00, '2025-09-03', '2025-09-03', '2025-09-03 12:11:11', 'cash', NULL, NULL, 16, 'REC26250903367021', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 12:11:11', '2025-09-03 12:11:11'),
(466, 438, 1, 21000.00, '2025-09-03', '2025-09-03', '2025-09-03 12:19:32', 'cash', NULL, NULL, 15, 'REC26250903666696', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 12:19:32', '2025-09-03 12:19:32'),
(467, 439, 1, 41000.00, '2025-09-03', '2025-09-03', '2025-09-03 12:21:42', 'cash', NULL, NULL, 15, 'REC26250903215215', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 12:21:42', '2025-09-03 12:21:42'),
(468, 440, 1, 50000.00, '2025-09-03', '2025-09-03', '2025-09-03 12:26:09', 'cash', NULL, NULL, 16, 'REC26250903466728', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 12:26:09', '2025-09-03 12:26:09'),
(469, 441, 1, 90000.00, '2025-09-03', '2025-09-03', '2025-09-03 12:59:53', 'cash', NULL, NULL, 16, 'REC26250903191703', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 12:59:53', '2025-09-03 12:59:53'),
(470, 442, 1, 93000.00, '2025-09-03', '2025-09-03', '2025-09-03 13:08:50', 'cash', NULL, NULL, 16, 'REC26250903628039', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 13:08:50', '2025-09-03 13:08:50'),
(471, 443, 1, 31000.00, '2025-09-03', '2025-09-03', '2025-09-03 13:16:43', 'cash', NULL, NULL, 16, 'REC26250903284956', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-03 13:16:43', '2025-09-03 13:16:43'),
(472, 444, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 05:45:32', 'cash', NULL, NULL, 15, 'REC26250905839056', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 05:45:32', '2025-09-05 05:45:32'),
(473, 445, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 05:48:03', 'cash', NULL, NULL, 15, 'REC26250905265966', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 05:48:03', '2025-09-05 05:48:03'),
(474, 446, 1, 46500.00, '2025-09-05', '2025-09-05', '2025-09-05 05:57:30', 'cash', NULL, NULL, 15, 'REC26250905961994', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 05:57:30', '2025-09-05 05:57:30'),
(475, 447, 1, 21000.00, '2025-09-05', '2025-09-05', '2025-09-05 06:09:23', 'cash', NULL, NULL, 15, 'REC26250905427684', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 06:09:23', '2025-09-05 06:09:23'),
(476, 448, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 06:15:41', 'cash', NULL, NULL, 15, 'REC26250905064400', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 06:15:41', '2025-09-05 06:15:41'),
(477, 449, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 06:20:35', 'cash', NULL, NULL, 15, 'REC26250905770675', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 06:20:35', '2025-09-05 06:20:35'),
(478, 450, 1, 118000.00, '2025-09-05', '2025-09-05', '2025-09-05 06:34:49', 'cash', NULL, NULL, 15, 'REC26250905572393', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 06:34:49', '2025-09-05 06:34:49'),
(479, 453, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 06:45:15', 'cash', NULL, NULL, 15, 'REC26250905987067', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 06:45:15', '2025-09-05 06:45:15'),
(480, 451, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 06:46:10', 'cash', NULL, NULL, 15, 'REC26250905456982', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 06:46:10', '2025-09-05 06:46:10'),
(481, 452, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 06:47:28', 'cash', NULL, NULL, 15, 'REC26250905949354', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 06:47:28', '2025-09-05 06:47:28'),
(482, 454, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 07:03:33', 'cash', NULL, NULL, 15, 'REC26250905407530', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 07:03:33', '2025-09-05 07:03:33'),
(483, 455, 1, 143000.00, '2025-09-05', '2025-09-05', '2025-09-05 07:29:28', 'cash', NULL, NULL, 15, 'REC26250905015670', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 07:29:28', '2025-09-05 07:29:28'),
(484, 456, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 07:48:48', 'cash', NULL, NULL, 15, 'REC26250905864433', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 07:48:48', '2025-09-05 07:48:48'),
(485, 457, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 07:50:59', 'cash', NULL, NULL, 15, 'REC26250905736146', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 07:50:59', '2025-09-05 07:50:59'),
(486, 458, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 07:55:00', 'cash', NULL, NULL, 15, 'REC26250905297592', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 07:55:00', '2025-09-05 07:55:00'),
(487, 459, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 07:58:57', 'cash', NULL, NULL, 15, 'REC26250905030713', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 07:58:57', '2025-09-05 07:58:57'),
(488, 460, 1, 163000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:15:07', 'cash', NULL, NULL, 15, 'REC26250905788334', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:15:07', '2025-09-05 08:15:07'),
(489, 461, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:23:02', 'cash', NULL, NULL, 15, 'REC26250905512211', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:23:02', '2025-09-05 08:23:02'),
(490, 462, 1, 45000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:27:01', 'cash', NULL, NULL, 15, 'REC26250905004781', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:27:01', '2025-09-05 08:27:01'),
(491, 463, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:32:28', 'cash', NULL, NULL, 15, 'REC26250905545170', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:32:28', '2025-09-05 08:32:28'),
(492, 464, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:34:22', 'cash', NULL, NULL, 15, 'REC26250905167130', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:34:22', '2025-09-05 08:34:22'),
(493, 465, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:41:06', 'cash', NULL, NULL, 15, 'REC26250905632986', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:41:06', '2025-09-05 08:41:06'),
(494, 467, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:52:01', 'cash', NULL, NULL, 15, 'REC26250905730204', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:52:01', '2025-09-05 08:52:01'),
(495, 468, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:53:47', 'cash', NULL, NULL, 15, 'REC26250905674274', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:53:47', '2025-09-05 08:53:47'),
(496, 469, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:57:21', 'cash', NULL, NULL, 15, 'REC26250905805404', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-05 08:57:21', '2025-09-05 08:57:21'),
(497, 466, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 08:58:32', 'cash', NULL, NULL, 15, 'REC26250905863886', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 08:58:32', '2025-09-05 08:58:32'),
(498, 470, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:02:26', 'cash', NULL, NULL, 15, 'REC26250905856763', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:02:26', '2025-09-05 09:02:26'),
(499, 472, 1, 140000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:10:12', 'cash', NULL, NULL, 15, 'REC26250905476636', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:10:12', '2025-09-05 09:10:12'),
(500, 473, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:12:50', 'cash', NULL, NULL, 15, 'REC26250905361681', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:12:50', '2025-09-05 09:12:50'),
(501, 471, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:13:54', 'cash', NULL, NULL, 15, 'REC26250905900471', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:13:54', '2025-09-05 09:13:54'),
(502, 474, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:22:46', 'cash', NULL, NULL, 15, 'REC26250905029254', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:22:46', '2025-09-05 09:22:46'),
(503, 475, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:24:44', 'cash', NULL, NULL, 15, 'REC26250905502507', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:24:44', '2025-09-05 09:24:44'),
(504, 476, 1, 21000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:29:40', 'cash', NULL, NULL, 15, 'REC26250905648227', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:29:40', '2025-09-05 09:29:40'),
(505, 477, 1, 21000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:31:33', 'cash', NULL, NULL, 15, 'REC26250905691475', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:31:33', '2025-09-05 09:31:33'),
(506, 478, 1, 86000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:35:08', 'cash', NULL, NULL, 15, 'REC26250905838226', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:35:08', '2025-09-05 09:35:08'),
(507, 479, 1, 21000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:40:29', 'cash', NULL, NULL, 15, 'REC26250905141405', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:40:29', '2025-09-05 09:40:29'),
(508, 480, 1, 21000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:45:59', 'cash', NULL, NULL, 15, 'REC26250905452420', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:45:59', '2025-09-05 09:45:59'),
(509, 481, 1, 88000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:48:55', 'cash', NULL, NULL, 15, 'REC26250905212160', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:48:55', '2025-09-05 09:48:55'),
(510, 482, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:52:25', 'cash', NULL, NULL, 15, 'REC26250905130380', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:52:25', '2025-09-05 09:52:25'),
(511, 483, 1, 73000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:55:16', 'cash', NULL, NULL, 15, 'REC26250905539144', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:55:16', '2025-09-05 09:55:16'),
(512, 484, 1, 88000.00, '2025-09-05', '2025-09-05', '2025-09-05 09:56:58', 'cash', NULL, NULL, 15, 'REC26250905541789', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 09:56:58', '2025-09-05 09:56:58'),
(513, 485, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:01:10', 'cash', NULL, NULL, 15, 'REC26250905251077', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:01:10', '2025-09-05 10:01:10'),
(514, 486, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:04:22', 'cash', NULL, NULL, 15, 'REC26250905876760', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:04:22', '2025-09-05 10:04:22'),
(515, 487, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:07:06', 'cash', NULL, NULL, 15, 'REC26250905379993', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:07:06', '2025-09-05 10:07:06'),
(516, 488, 1, 46500.00, '2025-09-05', '2025-09-05', '2025-09-05 10:10:27', 'cash', NULL, NULL, 15, 'REC26250905107841', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:10:27', '2025-09-05 10:10:27'),
(517, 489, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:12:18', 'cash', NULL, NULL, 15, 'REC26250905547673', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:12:18', '2025-09-05 10:12:18'),
(518, 490, 1, 100000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:21:01', 'cash', NULL, NULL, 15, 'REC26250905424982', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:21:01', '2025-09-05 10:21:01'),
(519, 491, 1, 50000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:23:20', 'cash', NULL, NULL, 15, 'REC26250905986352', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:23:20', '2025-09-05 10:23:20'),
(520, 492, 1, 163000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:27:18', 'cash', NULL, NULL, 15, 'REC26250905416030', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:27:18', '2025-09-05 10:27:18'),
(521, 493, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:29:25', 'cash', NULL, NULL, 15, 'REC26250905782152', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:29:25', '2025-09-05 10:29:25'),
(522, 494, 1, 21000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:33:14', 'cash', NULL, NULL, 15, 'REC26250905339847', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:33:14', '2025-09-05 10:33:14'),
(523, 495, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:36:44', 'cash', NULL, NULL, 15, 'REC26250905586427', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:36:44', '2025-09-05 10:36:44'),
(524, 496, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:49:29', 'cash', NULL, NULL, 15, 'REC26250905319868', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:49:29', '2025-09-05 10:49:29'),
(525, 497, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:53:04', 'cash', NULL, NULL, 15, 'REC26250905852442', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:53:04', '2025-09-05 10:53:04'),
(526, 498, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 10:56:41', 'cash', NULL, NULL, 15, 'REC26250905284582', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 10:56:41', '2025-09-05 10:56:41'),
(527, 499, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 11:13:48', 'cash', NULL, NULL, 15, 'REC26250905543973', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 11:13:48', '2025-09-05 11:13:48'),
(528, 500, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 11:17:20', 'cash', NULL, NULL, 15, 'REC26250905520686', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 11:17:20', '2025-09-05 11:17:20'),
(529, 501, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 11:46:39', 'cash', NULL, NULL, 15, 'REC26250905539615', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 11:46:39', '2025-09-05 11:46:39'),
(530, 502, 1, 41000.00, '2025-09-05', '2025-09-05', '2025-09-05 11:51:51', 'cash', NULL, NULL, 15, 'REC26250905997068', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 11:51:51', '2025-09-05 11:51:51'),
(531, 503, 1, 40000.00, '2025-09-05', '2025-09-05', '2025-09-05 11:56:10', 'cash', NULL, NULL, 15, 'REC26250905440493', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 11:56:10', '2025-09-05 11:56:10'),
(532, 504, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 11:59:18', 'cash', NULL, NULL, 15, 'REC26250905929199', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 11:59:18', '2025-09-05 11:59:18'),
(533, 505, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 12:02:28', 'cash', NULL, NULL, 15, 'REC26250905714619', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 12:02:28', '2025-09-05 12:02:28'),
(534, 506, 1, 153000.00, '2025-09-05', '2025-09-05', '2025-09-05 12:16:17', 'cash', NULL, NULL, 15, 'REC26250905994676', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 12:16:17', '2025-09-05 12:16:17'),
(535, 507, 1, 103000.00, '2025-09-05', '2025-09-05', '2025-09-05 12:31:37', 'cash', NULL, NULL, 15, 'REC26250905968441', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 12:31:37', '2025-09-05 12:31:37'),
(536, 508, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 12:34:10', 'cash', NULL, NULL, 15, 'REC26250905523747', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 12:34:10', '2025-09-05 12:34:10'),
(537, 509, 1, 50000.00, '2025-09-05', '2025-09-05', '2025-09-05 12:38:17', 'cash', NULL, NULL, 15, 'REC26250905127344', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 12:38:17', '2025-09-05 12:38:17'),
(538, 510, 1, 123000.00, '2025-09-05', '2025-09-05', '2025-09-05 12:45:13', 'cash', NULL, NULL, 15, 'REC26250905721635', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 12:45:13', '2025-09-05 12:45:13'),
(539, 511, 1, 31000.00, '2025-09-05', '2025-09-05', '2025-09-05 12:53:23', 'cash', NULL, NULL, 15, 'REC26250905063663', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 12:53:23', '2025-09-05 12:53:23'),
(540, 512, 1, 50000.00, '2025-09-05', '2025-09-05', '2025-09-05 13:02:18', 'cash', NULL, NULL, 15, 'REC26250905819010', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 13:02:18', '2025-09-05 13:02:18'),
(541, 513, 1, 100000.00, '2025-09-05', '2025-09-05', '2025-09-05 13:05:16', 'cash', NULL, NULL, 15, 'REC26250905716221', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-05 13:05:16', '2025-09-05 13:05:16'),
(542, 514, 1, 53000.00, '2025-09-06', '2025-09-06', '2025-09-06 06:54:38', 'cash', NULL, NULL, 16, 'REC26250906413272', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 06:54:38', '2025-09-06 06:54:38'),
(543, 515, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:00:37', 'cash', NULL, NULL, 16, 'REC26250906023277', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:00:37', '2025-09-06 07:00:37'),
(544, 516, 1, 21000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:12:21', 'cash', NULL, NULL, 16, 'REC26250906616549', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:12:21', '2025-09-06 07:12:21'),
(545, 517, 1, 41000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:19:01', 'cash', NULL, NULL, 16, 'REC26250906238274', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:19:01', '2025-09-06 07:19:01'),
(546, 518, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:23:49', 'cash', NULL, NULL, 16, 'REC26250906281734', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:23:49', '2025-09-06 07:23:49'),
(547, 519, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:30:46', 'cash', NULL, NULL, 16, 'REC26250906076556', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:30:46', '2025-09-06 07:30:46'),
(548, 520, 1, 50000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:35:53', 'cash', NULL, NULL, 16, 'REC26250906034826', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:35:53', '2025-09-06 07:35:53'),
(549, 521, 1, 41000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:41:27', 'cash', NULL, NULL, 16, 'REC26250906756838', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:41:27', '2025-09-06 07:41:27'),
(550, 522, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:51:15', 'cash', NULL, NULL, 16, 'REC26250906961426', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:51:15', '2025-09-06 07:51:15'),
(551, 523, 1, 41000.00, '2025-09-06', '2025-09-06', '2025-09-06 07:56:42', 'cash', NULL, NULL, 16, 'REC26250906105184', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 07:56:42', '2025-09-06 07:56:42'),
(552, 524, 1, 41000.00, '2025-09-06', '2025-09-06', '2025-09-06 08:02:07', 'cash', NULL, NULL, 16, 'REC26250906853334', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 08:02:07', '2025-09-06 08:02:07'),
(553, 525, 1, 21000.00, '2025-09-06', '2025-09-06', '2025-09-06 08:09:15', 'cash', NULL, NULL, 16, 'REC26250906487352', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 08:09:15', '2025-09-06 08:09:15'),
(554, 526, 1, 46500.00, '2025-09-06', '2025-09-06', '2025-09-06 08:13:53', 'cash', NULL, NULL, 16, 'REC26250906179481', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 08:13:53', '2025-09-06 08:13:53'),
(555, 527, 1, 60000.00, '2025-09-06', '2025-09-06', '2025-09-06 08:19:57', 'cash', NULL, NULL, 16, 'REC26250906117100', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 08:19:57', '2025-09-06 08:19:57'),
(556, 528, 1, 68000.00, '2025-09-06', '2025-09-06', '2025-09-06 08:27:52', 'cash', NULL, NULL, 16, 'REC26250906146355', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 08:27:52', '2025-09-06 08:27:52'),
(557, 529, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 08:37:41', 'cash', NULL, NULL, 16, 'REC26250906576520', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 08:37:41', '2025-09-06 08:37:41'),
(558, 530, 1, 60000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:03:20', 'cash', NULL, NULL, 16, 'REC26250906934878', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:03:20', '2025-09-06 09:03:20'),
(559, 531, 1, 41000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:03:29', 'cash', NULL, NULL, 15, 'REC26250906248085', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:03:29', '2025-09-06 09:03:29'),
(560, 532, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:10:30', 'cash', NULL, NULL, 15, 'REC26250906043922', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:10:30', '2025-09-06 09:10:30'),
(561, 533, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:10:56', 'cash', NULL, NULL, 16, 'REC26250906581071', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:10:56', '2025-09-06 09:10:56'),
(562, 534, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:34:29', 'cash', NULL, NULL, 15, 'REC26250906078684', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:34:29', '2025-09-06 09:34:29'),
(563, 535, 1, 40000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:39:02', 'cash', NULL, NULL, 15, 'REC26250906277764', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:39:02', '2025-09-06 09:39:02');
INSERT INTO `payments` (`id`, `student_id`, `school_year_id`, `total_amount`, `payment_date`, `versement_date`, `validation_date`, `payment_method`, `reference_number`, `notes`, `created_by_user_id`, `receipt_number`, `is_rame_physical`, `has_scholarship`, `scholarship_amount`, `has_reduction`, `reduction_amount`, `discount_reason`, `created_at`, `updated_at`) VALUES
(564, 536, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:43:40', 'cash', NULL, NULL, 15, 'REC26250906870982', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:43:40', '2025-09-06 09:43:40'),
(565, 537, 1, 41000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:47:25', 'cash', NULL, NULL, 15, 'REC26250906542308', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:47:25', '2025-09-06 09:47:25'),
(566, 538, 1, 111000.00, '2025-09-06', '2025-09-06', '2025-09-06 09:54:39', 'cash', NULL, NULL, 16, 'REC26250906039226', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 09:54:39', '2025-09-06 09:54:39'),
(567, 539, 1, 50000.00, '2025-09-06', '2025-09-06', '2025-09-06 10:02:49', 'cash', NULL, NULL, 16, 'REC26250906514634', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 10:02:49', '2025-09-06 10:02:49'),
(568, 540, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 10:09:41', 'cash', NULL, NULL, 16, 'REC26250906581447', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 10:09:41', '2025-09-06 10:09:41'),
(569, 541, 1, 31000.00, '2025-09-06', '2025-09-06', '2025-09-06 11:09:28', 'cash', NULL, NULL, 15, 'REC26250906980470', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 11:09:28', '2025-09-06 11:09:28'),
(570, 542, 1, 93000.00, '2025-09-06', '2025-09-06', '2025-09-06 11:14:07', 'cash', NULL, NULL, 15, 'REC26250906276676', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-06 11:14:07', '2025-09-06 11:14:07'),
(571, 543, 1, 40000.00, '2025-09-09', '2025-09-09', '2025-09-09 04:21:35', 'cash', NULL, NULL, 15, 'REC26250909111522', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 04:21:35', '2025-09-09 04:21:35'),
(572, 544, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 05:42:23', 'cash', NULL, NULL, 16, 'REC26250909298065', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 05:42:23', '2025-09-09 05:42:23'),
(573, 545, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 05:51:35', 'cash', NULL, NULL, 16, 'REC26250909137652', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 05:51:35', '2025-09-09 05:51:35'),
(574, 546, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 05:56:10', 'cash', NULL, NULL, 16, 'REC26250909907546', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 05:56:10', '2025-09-09 05:56:10'),
(575, 547, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:08:38', 'cash', NULL, NULL, 16, 'REC26250909453523', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:08:38', '2025-09-09 06:08:38'),
(576, 548, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:11:44', 'cash', NULL, NULL, 16, 'REC26250909220769', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:11:44', '2025-09-09 06:11:44'),
(577, 549, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:16:05', 'cash', NULL, NULL, 15, 'REC26250909882257', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:16:05', '2025-09-09 06:16:05'),
(578, 550, 1, 70000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:17:17', 'cash', NULL, NULL, 16, 'REC26250909966179', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:17:17', '2025-09-09 06:17:17'),
(579, 551, 1, 70000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:20:56', 'cash', NULL, NULL, 16, 'REC26250909973570', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:20:56', '2025-09-09 06:20:56'),
(580, 552, 1, 45000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:27:42', 'cash', NULL, NULL, 16, 'REC26250909787065', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:27:42', '2025-09-09 06:27:42'),
(581, 553, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:32:08', 'cash', NULL, NULL, 16, 'REC26250909630748', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:32:08', '2025-09-09 06:32:08'),
(582, 554, 1, 151000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:32:43', 'cash', NULL, NULL, 15, 'REC26250909992074', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:32:43', '2025-09-09 06:32:43'),
(584, 556, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:39:09', 'cash', NULL, NULL, 15, 'REC26250909259905', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:39:09', '2025-09-09 06:39:09'),
(585, 557, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:43:39', 'cash', NULL, NULL, 16, 'REC26250909030438', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:43:39', '2025-09-09 06:43:39'),
(586, 558, 1, 21000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:45:32', 'cash', NULL, NULL, 15, 'REC26250909390549', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:45:32', '2025-09-09 06:45:32'),
(587, 559, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:50:20', 'cash', NULL, NULL, 15, 'REC26250909901523', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:50:20', '2025-09-09 06:50:20'),
(588, 560, 1, 21000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:53:09', 'cash', NULL, NULL, 15, 'REC26250909821540', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:53:09', '2025-09-09 06:53:09'),
(589, 561, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:54:46', 'cash', NULL, NULL, 16, 'REC26250909721542', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:54:46', '2025-09-09 06:54:46'),
(590, 562, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 06:57:39', 'cash', NULL, NULL, 15, 'REC26250909444432', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 06:57:39', '2025-09-09 06:57:39'),
(591, 563, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:02:44', 'cash', NULL, NULL, 15, 'REC26250909545540', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:02:44', '2025-09-09 07:02:44'),
(592, 564, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:06:07', 'cash', NULL, NULL, 16, 'REC26250909813428', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:06:07', '2025-09-09 07:06:07'),
(593, 565, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:09:56', 'cash', NULL, NULL, 16, 'REC26250909609946', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:09:56', '2025-09-09 07:09:56'),
(594, 566, 1, 45000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:11:11', 'cash', NULL, NULL, 15, 'REC26250909892583', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-09 07:11:11', '2025-09-09 07:11:11'),
(595, 567, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:20:53', 'cash', NULL, NULL, 16, 'REC26250909607591', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:20:53', '2025-09-09 07:20:53'),
(596, 568, 1, 21000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:23:32', 'cash', NULL, NULL, 15, 'REC26250909431891', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:23:32', '2025-09-09 07:23:32'),
(597, 569, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:27:53', 'cash', NULL, NULL, 16, 'REC26250909634168', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-09 07:27:53', '2025-09-09 07:27:53'),
(598, 570, 1, 35000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:35:34', 'cash', NULL, NULL, 16, 'REC26250909762102', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:35:34', '2025-09-09 07:35:34'),
(599, 571, 1, 42000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:41:04', 'cash', NULL, NULL, 16, 'REC26250909678898', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:41:04', '2025-09-09 07:41:04'),
(600, 572, 1, 50000.00, '2025-09-09', '2025-09-04', '2025-09-09 07:45:49', 'cash', NULL, NULL, 15, 'REC26250909262619', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:45:49', '2025-09-09 07:45:49'),
(601, 573, 1, 40000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:48:37', 'cash', NULL, NULL, 16, 'REC26250909299689', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:48:37', '2025-09-09 07:48:37'),
(602, 574, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:49:11', 'cash', NULL, NULL, 15, 'REC26250909185477', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:49:11', '2025-09-09 07:49:11'),
(603, 575, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:53:54', 'cash', NULL, NULL, 16, 'REC26250909173505', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:53:54', '2025-09-09 07:53:54'),
(604, 576, 1, 50000.00, '2025-09-09', '2025-09-09', '2025-09-09 07:58:43', 'cash', NULL, NULL, 16, 'REC26250909897933', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 07:58:43', '2025-09-09 07:58:43'),
(605, 577, 1, 21000.00, '2025-09-09', '2025-09-08', '2025-09-09 08:07:05', 'cash', NULL, NULL, 15, 'REC26250909543786', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:07:05', '2025-09-09 08:07:05'),
(606, 578, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:07:29', 'cash', NULL, NULL, 16, 'REC26250909997355', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:07:29', '2025-09-09 08:07:29'),
(607, 579, 1, 51000.00, '2025-09-09', '2025-09-08', '2025-09-09 08:10:33', 'cash', NULL, NULL, 15, 'REC26250909557089', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:10:33', '2025-09-09 08:10:33'),
(608, 580, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:13:32', 'cash', NULL, NULL, 16, 'REC26250909845842', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:13:32', '2025-09-09 08:13:32'),
(609, 582, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:22:56', 'cash', NULL, NULL, 15, 'REC26250909063530', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:22:56', '2025-09-09 08:22:56'),
(610, 583, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:25:10', 'cash', NULL, NULL, 15, 'REC26250909785642', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:25:10', '2025-09-09 08:25:10'),
(611, 584, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:27:30', 'cash', NULL, NULL, 15, 'REC26250909934756', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:27:30', '2025-09-09 08:27:30'),
(612, 581, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:39:51', 'cash', NULL, NULL, 16, 'REC26250909588286', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:39:51', '2025-09-09 08:39:51'),
(613, 585, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:44:17', 'cash', NULL, NULL, 15, 'REC26250909041079', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:44:17', '2025-09-09 08:44:17'),
(614, 586, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:46:42', 'cash', NULL, NULL, 15, 'REC26250909411174', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:46:42', '2025-09-09 08:46:42'),
(615, 587, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 08:56:44', 'cash', NULL, NULL, 16, 'REC26250909611109', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 08:56:44', '2025-09-09 08:56:44'),
(616, 588, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 09:11:31', 'cash', NULL, NULL, 15, 'REC26250909201945', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 09:11:31', '2025-09-09 09:11:31'),
(617, 589, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 09:26:55', 'cash', NULL, NULL, 15, 'REC26250909646286', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 09:26:55', '2025-09-09 09:26:55'),
(618, 590, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 09:35:58', 'cash', NULL, NULL, 15, 'REC26250909436123', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 09:35:58', '2025-09-09 09:35:58'),
(619, 591, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 09:37:38', 'cash', NULL, NULL, 15, 'REC26250909667686', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 09:37:38', '2025-09-09 09:37:38'),
(620, 592, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 09:39:48', 'cash', NULL, NULL, 15, 'REC26250909152303', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 09:39:48', '2025-09-09 09:39:48'),
(621, 593, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 09:44:55', 'cash', NULL, NULL, 15, 'REC26250909258573', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 09:44:55', '2025-09-09 09:44:55'),
(622, 594, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 09:55:32', 'cash', NULL, NULL, 15, 'REC26250909247227', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 09:55:32', '2025-09-09 09:55:32'),
(623, 595, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 09:57:19', 'cash', NULL, NULL, 15, 'REC26250909984667', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 09:57:19', '2025-09-09 09:57:19'),
(625, 599, 1, 40000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:04:10', 'cash', NULL, NULL, 16, 'REC26250909132450', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:04:10', '2025-09-09 10:04:10'),
(626, 598, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:04:23', 'cash', NULL, NULL, 15, 'REC26250909540639', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:04:23', '2025-09-09 10:04:23'),
(627, 600, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:07:22', 'cash', NULL, NULL, 16, 'REC26250909542968', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-09 10:07:22', '2025-09-09 10:07:22'),
(628, 601, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:10:32', 'cash', NULL, NULL, 15, 'REC26250909177570', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:10:32', '2025-09-09 10:10:32'),
(629, 602, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:14:45', 'cash', NULL, NULL, 16, 'REC26250909612146', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:14:45', '2025-09-09 10:14:45'),
(630, 603, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:16:41', 'cash', NULL, NULL, 15, 'REC26250909736798', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:16:41', '2025-09-09 10:16:41'),
(631, 604, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:18:49', 'cash', NULL, NULL, 15, 'REC26250909008124', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:18:49', '2025-09-09 10:18:49'),
(632, 605, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:20:50', 'cash', NULL, NULL, 15, 'REC26250909565702', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:20:50', '2025-09-09 10:20:50'),
(633, 606, 1, 50000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:26:55', 'cash', NULL, NULL, 16, 'REC26250909715198', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:26:55', '2025-09-09 10:26:55'),
(634, 607, 1, 70000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:29:40', 'cash', NULL, NULL, 15, 'REC26250909692428', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:29:40', '2025-09-09 10:29:40'),
(635, 608, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:30:04', 'cash', NULL, NULL, 16, 'REC26250909717056', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:30:04', '2025-09-09 10:30:04'),
(636, 609, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:34:08', 'cash', NULL, NULL, 16, 'REC26250909924215', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:34:08', '2025-09-09 10:34:08'),
(637, 610, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:43:07', 'cash', NULL, NULL, 16, 'REC26250909831940', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:43:07', '2025-09-09 10:43:07'),
(638, 611, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:47:12', 'cash', NULL, NULL, 16, 'REC26250909652677', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:47:12', '2025-09-09 10:47:12'),
(639, 612, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 10:51:48', 'cash', NULL, NULL, 16, 'REC26250909256295', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 10:51:48', '2025-09-09 10:51:48'),
(640, 613, 1, 50000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:01:06', 'cash', NULL, NULL, 16, 'REC26250909622167', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:01:06', '2025-09-09 11:01:06'),
(641, 614, 1, 45000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:05:32', 'cash', NULL, NULL, 16, 'REC26250909912528', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:05:32', '2025-09-09 11:05:32'),
(642, 615, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:18:03', 'cash', NULL, NULL, 15, 'REC26250909227102', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:18:03', '2025-09-09 11:18:03'),
(643, 616, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:19:46', 'cash', NULL, NULL, 16, 'REC26250909644705', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:19:46', '2025-09-09 11:19:46'),
(644, 617, 1, 21000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:23:37', 'cash', NULL, NULL, 15, 'REC26250909441070', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:23:37', '2025-09-09 11:23:37'),
(645, 618, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:25:02', 'cash', NULL, NULL, 16, 'REC26250909634832', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:25:02', '2025-09-09 11:25:02'),
(646, 619, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:28:32', 'cash', NULL, NULL, 16, 'REC26250909500959', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:28:32', '2025-09-09 11:28:32'),
(647, 620, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:34:26', 'cash', NULL, NULL, 16, 'REC26250909530305', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:34:26', '2025-09-09 11:34:26'),
(648, 621, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:37:50', 'cash', NULL, NULL, 15, 'REC26250909516874', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:37:50', '2025-09-09 11:37:50'),
(649, 622, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:39:10', 'cash', NULL, NULL, 16, 'REC26250909952394', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:39:10', '2025-09-09 11:39:10'),
(650, 623, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:43:26', 'cash', NULL, NULL, 16, 'REC26250909597239', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 11:43:26', '2025-09-09 11:43:26'),
(651, 624, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 11:50:56', 'cash', NULL, NULL, 16, 'REC26250909649781', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-09 11:50:56', '2025-09-09 11:50:56'),
(652, 625, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:00:58', 'cash', NULL, NULL, 16, 'REC26250909320647', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:00:58', '2025-09-09 12:00:58'),
(653, 626, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:07:04', 'cash', NULL, NULL, 16, 'REC26250909090123', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:07:04', '2025-09-09 12:07:04'),
(654, 627, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:14:08', 'cash', NULL, NULL, 16, 'REC26250909501812', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:14:08', '2025-09-09 12:14:08'),
(655, 628, 1, 85000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:17:08', 'cash', NULL, NULL, 16, 'REC26250909433260', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:17:08', '2025-09-09 12:17:08'),
(656, 629, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:24:51', 'cash', NULL, NULL, 16, 'REC26250909741748', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-09 12:24:51', '2025-09-09 12:24:51'),
(657, 630, 1, 56000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:24:54', 'cash', NULL, NULL, 15, 'REC26250909405317', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:24:54', '2025-09-09 12:24:54'),
(658, 631, 1, 55000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:36:30', 'cash', NULL, NULL, 16, 'REC26250909024691', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:36:30', '2025-09-09 12:36:30'),
(659, 632, 1, 55000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:43:14', 'cash', NULL, NULL, 16, 'REC26250909975998', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:43:14', '2025-09-09 12:43:14'),
(660, 633, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:47:31', 'cash', NULL, NULL, 16, 'REC26250909908611', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:47:31', '2025-09-09 12:47:31'),
(661, 634, 1, 31000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:54:19', 'cash', NULL, NULL, 16, 'REC26250909145914', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:54:19', '2025-09-09 12:54:19'),
(662, 635, 1, 103000.00, '2025-09-09', '2025-09-09', '2025-09-09 12:59:23', 'cash', NULL, NULL, 16, 'REC26250909164071', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 12:59:23', '2025-09-09 12:59:23'),
(663, 636, 1, 50000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:06:14', 'cash', NULL, NULL, 16, 'REC26250909985268', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:06:14', '2025-09-09 13:06:14'),
(664, 637, 1, 68000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:11:24', 'cash', NULL, NULL, 15, 'REC26250909381607', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:11:24', '2025-09-09 13:11:24'),
(665, 638, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:12:32', 'cash', NULL, NULL, 16, 'REC26250909944681', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:12:32', '2025-09-09 13:12:32'),
(666, 639, 1, 21000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:22:22', 'cash', NULL, NULL, 16, 'REC26250909859422', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:22:22', '2025-09-09 13:22:22'),
(667, 640, 1, 41000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:32:22', 'cash', NULL, NULL, 16, 'REC26250909850240', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:32:22', '2025-09-09 13:32:22'),
(668, 641, 1, 31000.00, '2025-09-09', '2025-09-08', '2025-09-09 13:32:45', 'cash', NULL, NULL, 15, 'REC26250909336646', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:32:45', '2025-09-09 13:32:45'),
(669, 642, 1, 70000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:37:40', 'cash', NULL, NULL, 16, 'REC26250909947731', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:37:40', '2025-09-09 13:37:40'),
(670, 643, 1, 51000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:42:38', 'cash', NULL, NULL, 16, 'REC26250909493578', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:42:38', '2025-09-09 13:42:38'),
(671, 644, 1, 50000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:48:11', 'cash', NULL, NULL, 16, 'REC26250909807917', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:48:11', '2025-09-09 13:48:11'),
(672, 645, 1, 82500.00, '2025-09-09', '2025-08-26', '2025-09-09 13:51:40', 'cash', NULL, NULL, 15, 'REC26250909537883', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:51:40', '2025-09-09 13:51:40'),
(673, 646, 1, 61000.00, '2025-09-09', '2025-09-09', '2025-09-09 13:53:10', 'cash', NULL, NULL, 16, 'REC26250909157117', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-09 13:53:10', '2025-09-09 13:53:10'),
(675, 648, 1, 70000.00, '2025-09-10', '2025-09-10', '2025-09-10 05:01:04', 'cash', NULL, NULL, 16, 'REC26250910923182', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:01:04', '2025-09-10 05:01:04'),
(676, 649, 1, 53000.00, '2025-09-10', '2025-09-10', '2025-09-10 05:10:59', 'cash', NULL, NULL, 16, 'REC26250910088191', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:10:59', '2025-09-10 05:10:59'),
(677, 650, 1, 123000.00, '2025-09-10', '2025-09-08', '2025-09-10 05:17:00', 'cash', NULL, NULL, 15, 'REC26250910686558', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:17:00', '2025-09-10 05:17:00'),
(678, 651, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 05:20:51', 'cash', NULL, NULL, 15, 'REC26250910765683', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:20:51', '2025-09-10 05:20:51'),
(679, 597, 1, 31000.00, '2025-09-10', '2025-09-09', '2025-09-10 05:23:27', 'cash', NULL, NULL, 15, 'REC26250910672564', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:23:27', '2025-09-10 05:23:27'),
(680, 652, 1, 100000.00, '2025-09-10', '2025-09-10', '2025-09-10 05:24:06', 'cash', NULL, NULL, 16, 'REC26250910835712', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:24:06', '2025-09-10 05:24:06'),
(681, 653, 1, 70000.00, '2025-09-10', '2025-09-08', '2025-09-10 05:34:08', 'cash', NULL, NULL, 15, 'REC26250910184093', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:34:08', '2025-09-10 05:34:08'),
(682, 654, 1, 60000.00, '2025-09-10', '2025-09-10', '2025-09-10 05:34:39', 'cash', NULL, NULL, 16, 'REC26250910973049', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:34:39', '2025-09-10 05:34:39'),
(683, 655, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 05:38:21', 'cash', NULL, NULL, 15, 'REC26250910743542', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 05:38:21', '2025-09-10 05:38:21'),
(684, 656, 1, 100000.00, '2025-09-10', '2025-09-08', '2025-09-10 06:50:50', 'cash', NULL, NULL, 15, 'REC26250910090762', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 06:50:50', '2025-09-10 06:50:50'),
(685, 658, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 06:54:58', 'cash', NULL, NULL, 15, 'REC26250910527950', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 06:54:58', '2025-09-10 06:54:58'),
(686, 659, 1, 106000.00, '2025-09-10', '2025-09-10', '2025-09-10 06:57:38', 'cash', NULL, NULL, 16, 'REC26250910429997', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 06:57:38', '2025-09-10 06:57:38'),
(687, 660, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 06:58:31', 'cash', NULL, NULL, 15, 'REC26250910950408', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 06:58:31', '2025-09-10 06:58:31'),
(688, 662, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:03:53', 'cash', NULL, NULL, 16, 'REC26250910960869', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:03:53', '2025-09-10 07:03:53'),
(689, 663, 1, 50000.00, '2025-09-10', '2025-09-09', '2025-09-10 07:05:13', 'cash', NULL, NULL, 15, 'REC26250910316411', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:05:13', '2025-09-10 07:05:13'),
(690, 664, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:08:56', 'cash', NULL, NULL, 15, 'REC26250910137148', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:08:56', '2025-09-10 07:08:56'),
(691, 665, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:09:20', 'cash', NULL, NULL, 16, 'REC26250910590134', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:09:20', '2025-09-10 07:09:20'),
(692, 661, 1, 50000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:11:24', 'cash', NULL, NULL, 15, 'REC26250910121612', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:11:24', '2025-09-10 07:11:24'),
(693, 666, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:13:57', 'cash', NULL, NULL, 16, 'REC26250910501385', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:13:57', '2025-09-10 07:13:57'),
(694, 667, 1, 45000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:21:36', 'cash', NULL, NULL, 15, 'REC26250910588877', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:21:36', '2025-09-10 07:21:36'),
(695, 669, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:23:30', 'cash', NULL, NULL, 16, 'REC26250910032085', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:23:30', '2025-09-10 07:23:30'),
(696, 670, 1, 70000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:24:39', 'cash', NULL, NULL, 15, 'REC26250910615837', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:24:39', '2025-09-10 07:24:39'),
(697, 671, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:28:24', 'cash', NULL, NULL, 16, 'REC26250910094779', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:28:24', '2025-09-10 07:28:24'),
(698, 672, 1, 21000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:29:59', 'cash', NULL, NULL, 15, 'REC26250910268633', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:29:59', '2025-09-10 07:29:59'),
(699, 668, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:31:00', 'cash', NULL, NULL, 15, 'REC26250910000398', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:31:00', '2025-09-10 07:31:00'),
(700, 673, 1, 75000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:33:31', 'cash', NULL, NULL, 16, 'REC26250910737180', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:33:31', '2025-09-10 07:33:31'),
(701, 674, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:37:54', 'cash', NULL, NULL, 15, 'REC26250910768027', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:37:54', '2025-09-10 07:37:54'),
(702, 675, 1, 40000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:40:57', 'cash', NULL, NULL, 16, 'REC26250910644206', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:40:57', '2025-09-10 07:40:57'),
(703, 657, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:42:10', 'cash', NULL, NULL, 16, 'REC26250910607498', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:42:10', '2025-09-10 07:42:10'),
(704, 676, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:42:20', 'cash', NULL, NULL, 15, 'REC26250910763101', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:42:20', '2025-09-10 07:42:20'),
(705, 677, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:49:29', 'cash', NULL, NULL, 16, 'REC26250910213998', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:49:29', '2025-09-10 07:49:29'),
(706, 678, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:54:25', 'cash', NULL, NULL, 15, 'REC26250910653565', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:54:25', '2025-09-10 07:54:25'),
(707, 679, 1, 46000.00, '2025-09-10', '2025-09-10', '2025-09-10 07:55:00', 'cash', NULL, NULL, 16, 'REC26250910383589', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 07:55:00', '2025-09-10 07:55:00'),
(708, 680, 1, 151000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:00:01', 'cash', NULL, NULL, 15, 'REC26250910948002', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:00:01', '2025-09-10 08:00:01'),
(709, 681, 1, 50000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:02:02', 'cash', NULL, NULL, 16, 'REC26250910573716', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:02:02', '2025-09-10 08:02:02'),
(710, 682, 1, 56000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:04:49', 'cash', NULL, NULL, 15, 'REC26250910870971', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:04:49', '2025-09-10 08:04:49'),
(711, 683, 1, 60000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:06:31', 'cash', NULL, NULL, 16, 'REC26250910089327', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:06:31', '2025-09-10 08:06:31'),
(712, 684, 1, 70000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:10:12', 'cash', NULL, NULL, 16, 'REC26250910966028', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:10:12', '2025-09-10 08:10:12'),
(713, 685, 1, 41000.00, '2025-09-10', '2025-09-08', '2025-09-10 08:13:09', 'cash', NULL, NULL, 15, 'REC26250910290839', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:13:09', '2025-09-10 08:13:09'),
(714, 686, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 08:15:49', 'cash', NULL, NULL, 15, 'REC26250910797900', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:15:49', '2025-09-10 08:15:49'),
(715, 687, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:16:36', 'cash', NULL, NULL, 16, 'REC26250910420812', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:16:36', '2025-09-10 08:16:36'),
(716, 688, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:39:59', 'cash', NULL, NULL, 15, 'REC26250910967611', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:39:59', '2025-09-10 08:39:59'),
(717, 689, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:43:02', 'cash', NULL, NULL, 15, 'REC26250910630531', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:43:02', '2025-09-10 08:43:02'),
(718, 690, 1, 50000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:45:15', 'cash', NULL, NULL, 16, 'REC26250910219759', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:45:15', '2025-09-10 08:45:15'),
(719, 691, 1, 60000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:53:49', 'cash', NULL, NULL, 16, 'REC26250910306147', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:53:49', '2025-09-10 08:53:49'),
(720, 692, 1, 28000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:55:53', 'cash', NULL, NULL, 15, 'REC26250910314821', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:55:53', '2025-09-10 08:55:53'),
(721, 693, 1, 28000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:58:09', 'cash', NULL, NULL, 15, 'REC26250910891257', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:58:09', '2025-09-10 08:58:09'),
(722, 694, 1, 21000.00, '2025-09-10', '2025-09-10', '2025-09-10 08:58:45', 'cash', NULL, NULL, 16, 'REC26250910379968', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 08:58:45', '2025-09-10 08:58:45'),
(723, 695, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:00:11', 'cash', NULL, NULL, 15, 'REC26250910632096', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:00:11', '2025-09-10 09:00:11'),
(725, 698, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:11:48', 'cash', NULL, NULL, 15, 'REC26250910792157', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:11:48', '2025-09-10 09:11:48'),
(726, 699, 1, 91000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:15:53', 'cash', NULL, NULL, 16, 'REC26250910905030', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:15:53', '2025-09-10 09:15:53'),
(727, 700, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:16:39', 'cash', NULL, NULL, 15, 'REC26250910885386', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:16:39', '2025-09-10 09:16:39'),
(728, 701, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:19:09', 'cash', NULL, NULL, 16, 'REC26250910803396', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:19:09', '2025-09-10 09:19:09'),
(729, 702, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:22:55', 'cash', NULL, NULL, 16, 'REC26250910032390', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:22:55', '2025-09-10 09:22:55'),
(730, 703, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:28:10', 'cash', NULL, NULL, 16, 'REC26250910794882', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:28:10', '2025-09-10 09:28:10'),
(731, 704, 1, 100000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:30:04', 'cash', NULL, NULL, 15, 'REC26250910924283', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:30:04', '2025-09-10 09:30:04'),
(732, 617, 1, 55000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:32:31', 'cash', NULL, NULL, 15, 'REC26250910553301', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:32:31', '2025-09-10 09:32:31'),
(733, 705, 1, 45000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:33:54', 'cash', NULL, NULL, 16, 'REC26250910806049', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:33:54', '2025-09-10 09:33:54'),
(734, 706, 1, 88000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:41:47', 'cash', NULL, NULL, 16, 'REC26250910858040', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:41:47', '2025-09-10 09:41:47'),
(735, 707, 1, 75000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:43:14', 'cash', NULL, NULL, 15, 'REC26250910492647', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:43:14', '2025-09-10 09:43:14'),
(736, 708, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:46:28', 'cash', NULL, NULL, 16, 'REC26250910024220', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:46:28', '2025-09-10 09:46:28'),
(737, 710, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 09:54:04', 'cash', NULL, NULL, 16, 'REC26250910303872', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 09:54:04', '2025-09-10 09:54:04'),
(738, 696, 1, 50000.00, '2025-09-10', '2025-09-08', '2025-09-10 10:03:20', 'cash', NULL, NULL, 15, 'REC26250910238427', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:03:20', '2025-09-10 10:03:20'),
(739, 712, 1, 100000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:05:50', 'cash', NULL, NULL, 15, 'REC26250910586797', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:05:50', '2025-09-10 10:05:50'),
(740, 713, 1, 126000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:06:23', 'cash', NULL, NULL, 16, 'REC26250910422065', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:06:23', '2025-09-10 10:06:23'),
(741, 711, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:08:55', 'cash', NULL, NULL, 16, 'REC26250910412868', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:08:55', '2025-09-10 10:08:55'),
(742, 714, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:09:03', 'cash', NULL, NULL, 15, 'REC26250910998387', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:09:03', '2025-09-10 10:09:03'),
(743, 709, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:09:56', 'cash', NULL, NULL, 16, 'REC26250910496832', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:09:56', '2025-09-10 10:09:56'),
(744, 715, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:19:41', 'cash', NULL, NULL, 16, 'REC26250910305725', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:19:41', '2025-09-10 10:19:41'),
(745, 716, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:23:09', 'cash', NULL, NULL, 16, 'REC26250910895918', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:23:09', '2025-09-10 10:23:09'),
(746, 718, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:30:56', 'cash', NULL, NULL, 16, 'REC26250910374862', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:30:56', '2025-09-10 10:30:56'),
(747, 717, 1, 71000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:34:09', 'cash', NULL, NULL, 15, 'REC26250910627605', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:34:09', '2025-09-10 10:34:09'),
(748, 719, 1, 90000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:39:23', 'cash', NULL, NULL, 15, 'REC26250910215072', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:39:23', '2025-09-10 10:39:23'),
(749, 720, 1, 82000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:43:08', 'cash', NULL, NULL, 16, 'REC26250910217782', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:43:08', '2025-09-10 10:43:08'),
(750, 721, 1, 50000.00, '2025-09-10', '2025-09-08', '2025-09-10 10:46:57', 'cash', NULL, NULL, 15, 'REC26250910418831', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:46:57', '2025-09-10 10:46:57'),
(751, 722, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:48:10', 'cash', NULL, NULL, 16, 'REC26250910261301', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:48:10', '2025-09-10 10:48:10'),
(752, 723, 1, 50000.00, '2025-09-10', '2025-09-08', '2025-09-10 10:49:22', 'cash', NULL, NULL, 15, 'REC26250910684969', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:49:22', '2025-09-10 10:49:22'),
(753, 724, 1, 70000.00, '2025-09-10', '2025-09-08', '2025-09-10 10:51:36', 'cash', NULL, NULL, 15, 'REC26250910097359', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:51:36', '2025-09-10 10:51:36'),
(754, 725, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:53:02', 'cash', NULL, NULL, 16, 'REC26250910257163', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:53:02', '2025-09-10 10:53:02'),
(755, 726, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 10:53:19', 'cash', NULL, NULL, 15, 'REC26250910726907', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:53:19', '2025-09-10 10:53:19'),
(756, 727, 1, 132000.00, '2025-09-10', '2025-09-08', '2025-09-10 10:55:38', 'cash', NULL, NULL, 15, 'REC26250910603775', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:55:38', '2025-09-10 10:55:38'),
(757, 728, 1, 53000.00, '2025-09-10', '2025-09-10', '2025-09-10 10:57:30', 'cash', NULL, NULL, 16, 'REC26250910843718', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 10:57:30', '2025-09-10 10:57:30'),
(758, 729, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:00:34', 'cash', NULL, NULL, 15, 'REC26250910133929', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:00:34', '2025-09-10 11:00:34'),
(759, 730, 1, 52000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:02:53', 'cash', NULL, NULL, 15, 'REC26250910309352', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:02:53', '2025-09-10 11:02:53'),
(760, 731, 1, 40000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:04:09', 'cash', NULL, NULL, 16, 'REC26250910657382', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:04:09', '2025-09-10 11:04:09'),
(761, 733, 1, 21000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:08:53', 'cash', NULL, NULL, 15, 'REC26250910185114', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:08:53', '2025-09-10 11:08:53'),
(762, 734, 1, 40000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:09:20', 'cash', NULL, NULL, 16, 'REC26250910589422', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:09:20', '2025-09-10 11:09:20'),
(763, 735, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:11:47', 'cash', NULL, NULL, 15, 'REC26250910532368', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:11:47', '2025-09-10 11:11:47'),
(764, 732, 1, 41000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:13:04', 'cash', NULL, NULL, 15, 'REC26250910696507', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:13:04', '2025-09-10 11:13:04'),
(765, 736, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:14:53', 'cash', NULL, NULL, 16, 'REC26250910523919', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:14:53', '2025-09-10 11:14:53'),
(766, 737, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:16:29', 'cash', NULL, NULL, 15, 'REC26250910950012', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:16:29', '2025-09-10 11:16:29'),
(767, 738, 1, 50000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:21:55', 'cash', NULL, NULL, 16, 'REC26250910164634', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:21:55', '2025-09-10 11:21:55'),
(769, 740, 1, 51000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:26:32', 'cash', NULL, NULL, 16, 'REC26250910733021', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:26:32', '2025-09-10 11:26:32'),
(770, 741, 1, 62000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:28:23', 'cash', NULL, NULL, 15, 'REC26250910762119', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:28:23', '2025-09-10 11:28:23'),
(771, 742, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:33:41', 'cash', NULL, NULL, 16, 'REC26250910646847', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:33:41', '2025-09-10 11:33:41'),
(772, 743, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:39:22', 'cash', NULL, NULL, 16, 'REC26250910435036', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:39:22', '2025-09-10 11:39:22'),
(774, 745, 1, 50000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:48:54', 'cash', NULL, NULL, 15, 'REC26250910744572', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:48:54', '2025-09-10 11:48:54'),
(775, 746, 1, 50000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:50:28', 'cash', NULL, NULL, 16, 'REC26250910096299', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:50:28', '2025-09-10 11:50:28'),
(776, 747, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:53:45', 'cash', NULL, NULL, 15, 'REC26250910603899', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-10 11:53:45', '2025-09-10 11:53:45'),
(777, 748, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 11:55:20', 'cash', NULL, NULL, 16, 'REC26250910588006', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:55:20', '2025-09-10 11:55:20'),
(778, 749, 1, 41000.00, '2025-09-10', '2025-09-08', '2025-09-10 11:58:35', 'cash', NULL, NULL, 15, 'REC26250910313345', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 11:58:35', '2025-09-10 11:58:35'),
(779, 750, 1, 21000.00, '2025-09-10', '2025-09-08', '2025-09-10 12:03:16', 'cash', NULL, NULL, 15, 'REC26250910926526', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:03:16', '2025-09-10 12:03:16'),
(780, 751, 1, 71000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:03:45', 'cash', NULL, NULL, 16, 'REC26250910023057', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:03:45', '2025-09-10 12:03:45'),
(781, 752, 1, 50000.00, '2025-09-10', '2025-09-08', '2025-09-10 12:06:59', 'cash', NULL, NULL, 15, 'REC26250910408923', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:06:59', '2025-09-10 12:06:59'),
(782, 753, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:10:11', 'cash', NULL, NULL, 16, 'REC26250910469139', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:10:11', '2025-09-10 12:10:11'),
(783, 754, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:15:51', 'cash', NULL, NULL, 16, 'REC26250910332124', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:15:51', '2025-09-10 12:15:51'),
(784, 755, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:21:17', 'cash', NULL, NULL, 16, 'REC26250910257967', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:21:17', '2025-09-10 12:21:17'),
(785, 756, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:22:59', 'cash', NULL, NULL, 15, 'REC26250910593240', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:22:59', '2025-09-10 12:22:59'),
(786, 757, 1, 41000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:25:07', 'cash', NULL, NULL, 15, 'REC26250910114478', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:25:07', '2025-09-10 12:25:07'),
(787, 758, 1, 163000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:30:12', 'cash', NULL, NULL, 16, 'REC26250910389057', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:30:12', '2025-09-10 12:30:12'),
(788, 759, 1, 41000.00, '2025-09-10', '2025-09-08', '2025-09-10 12:30:20', 'cash', NULL, NULL, 15, 'REC26250910851162', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:30:20', '2025-09-10 12:30:20'),
(789, 760, 1, 60000.00, '2025-09-10', '2025-09-08', '2025-09-10 12:32:26', 'cash', NULL, NULL, 15, 'REC26250910493613', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:32:26', '2025-09-10 12:32:26'),
(790, 761, 1, 153000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:33:53', 'cash', NULL, NULL, 16, 'REC26250910948339', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:33:53', '2025-09-10 12:33:53'),
(791, 762, 1, 73000.00, '2025-09-10', '2025-09-08', '2025-09-10 12:35:26', 'cash', NULL, NULL, 15, 'REC26250910995517', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:35:26', '2025-09-10 12:35:26'),
(792, 763, 1, 31000.00, '2025-09-10', '2025-09-08', '2025-09-10 12:37:48', 'cash', NULL, NULL, 15, 'REC26250910397975', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:37:48', '2025-09-10 12:37:48'),
(793, 764, 1, 21000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:39:41', 'cash', NULL, NULL, 16, 'REC26250910846247', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:39:41', '2025-09-10 12:39:41'),
(794, 765, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:45:24', 'cash', NULL, NULL, 16, 'REC26250910543075', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:45:24', '2025-09-10 12:45:24'),
(795, 766, 1, 45000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:50:18', 'cash', NULL, NULL, 16, 'REC26250910141951', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:50:18', '2025-09-10 12:50:18'),
(796, 767, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 12:57:02', 'cash', NULL, NULL, 16, 'REC26250910672551', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 12:57:02', '2025-09-10 12:57:02'),
(797, 768, 1, 30000.00, '2025-09-10', '2025-09-10', '2025-09-10 13:01:55', 'cash', NULL, NULL, 16, 'REC26250910575562', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 13:01:55', '2025-09-10 13:01:55'),
(798, 769, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 13:06:50', 'cash', NULL, NULL, 16, 'REC26250910904000', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 13:06:50', '2025-09-10 13:06:50'),
(799, 770, 1, 31000.00, '2025-09-10', '2025-09-10', '2025-09-10 13:16:23', 'cash', NULL, NULL, 16, 'REC26250910428100', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 13:16:23', '2025-09-10 13:16:23'),
(800, 771, 1, 81000.00, '2025-09-10', '2025-09-10', '2025-09-10 13:20:07', 'cash', NULL, NULL, 16, 'REC26250910307447', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 13:20:07', '2025-09-10 13:20:07'),
(801, 772, 1, 46000.00, '2025-09-10', '2025-09-10', '2025-09-10 13:53:24', 'cash', NULL, NULL, 15, 'REC26250910598980', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-10 13:53:24', '2025-09-10 13:53:24'),
(802, 773, 1, 65000.00, '2025-09-11', '2025-09-11', '2025-09-11 08:40:29', 'cash', NULL, NULL, 16, 'REC26250911166249', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 08:40:29', '2025-09-11 08:40:29'),
(803, 774, 1, 50000.00, '2025-09-11', '2025-09-11', '2025-09-11 08:49:23', 'cash', NULL, NULL, 16, 'REC26250911965382', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 08:49:23', '2025-09-11 08:49:23'),
(804, 775, 1, 31000.00, '2025-09-11', '2025-09-11', '2025-09-11 08:54:43', 'cash', NULL, NULL, 16, 'REC26250911335518', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 08:54:43', '2025-09-11 08:54:43'),
(805, 776, 1, 133000.00, '2025-09-11', '2025-09-11', '2025-09-11 09:01:41', 'cash', NULL, NULL, 16, 'REC26250911899983', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 09:01:41', '2025-09-11 09:01:41'),
(806, 777, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 09:10:44', 'cash', NULL, NULL, 16, 'REC26250911690605', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 09:10:44', '2025-09-11 09:10:44'),
(807, 778, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 09:18:16', 'cash', NULL, NULL, 16, 'REC26250911475262', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 09:18:16', '2025-09-11 09:18:16'),
(808, 779, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 09:23:15', 'cash', NULL, NULL, 16, 'REC26250911048572', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 09:23:15', '2025-09-11 09:23:15'),
(809, 780, 1, 45000.00, '2025-09-11', '2025-09-11', '2025-09-11 09:29:29', 'cash', NULL, NULL, 16, 'REC26250911027081', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 09:29:29', '2025-09-11 09:29:29'),
(810, 781, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 09:32:38', 'cash', NULL, NULL, 16, 'REC26250911342128', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 09:32:38', '2025-09-11 09:32:38'),
(811, 782, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 09:38:47', 'cash', NULL, NULL, 16, 'REC26250911304593', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 09:38:47', '2025-09-11 09:38:47'),
(812, 783, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 09:41:39', 'cash', NULL, NULL, 16, 'REC26250911434097', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 09:41:39', '2025-09-11 09:41:39'),
(813, 784, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 10:27:17', 'cash', NULL, NULL, 15, 'REC26250911616924', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 10:27:17', '2025-09-11 10:27:17'),
(814, 785, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 10:31:30', 'cash', NULL, NULL, 15, 'REC26250911560693', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 10:31:30', '2025-09-11 10:31:30'),
(815, 786, 1, 103000.00, '2025-09-11', '2025-09-11', '2025-09-11 10:39:36', 'cash', NULL, NULL, 16, 'REC26250911984823', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 10:39:36', '2025-09-11 10:39:36'),
(816, 787, 1, 70000.00, '2025-09-11', '2025-09-11', '2025-09-11 10:40:04', 'cash', NULL, NULL, 15, 'REC26250911530213', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 10:40:04', '2025-09-11 10:40:04'),
(817, 788, 1, 90000.00, '2025-09-11', '2025-09-11', '2025-09-11 10:56:10', 'cash', NULL, NULL, 16, 'REC26250911720830', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 10:56:10', '2025-09-11 10:56:10'),
(818, 789, 1, 31000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:01:25', 'cash', NULL, NULL, 16, 'REC26250911209933', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:01:25', '2025-09-11 11:01:25'),
(819, 790, 1, 78000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:06:55', 'cash', NULL, NULL, 16, 'REC26250911022854', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:06:55', '2025-09-11 11:06:55'),
(820, 791, 1, 60000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:12:08', 'cash', NULL, NULL, 15, 'REC26250911229229', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:12:08', '2025-09-11 11:12:08'),
(821, 792, 1, 21000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:21:16', 'cash', NULL, NULL, 16, 'REC26250911423740', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:21:16', '2025-09-11 11:21:16'),
(822, 793, 1, 31000.00, '2025-09-11', '2025-09-04', '2025-09-11 11:26:37', 'cash', NULL, NULL, 15, 'REC26250911089067', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:26:37', '2025-09-11 11:26:37'),
(823, 794, 1, 40000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:28:15', 'cash', NULL, NULL, 16, 'REC26250911165814', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:28:15', '2025-09-11 11:28:15'),
(824, 795, 1, 70000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:31:45', 'cash', NULL, NULL, 16, 'REC26250911169174', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:31:45', '2025-09-11 11:31:45'),
(825, 796, 1, 40000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:34:23', 'cash', NULL, NULL, 16, 'REC26250911218684', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:34:23', '2025-09-11 11:34:23'),
(826, 797, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:44:57', 'cash', NULL, NULL, 16, 'REC26250911225601', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:44:57', '2025-09-11 11:44:57'),
(827, 798, 1, 60000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:50:14', 'cash', NULL, NULL, 16, 'REC26250911309987', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:50:14', '2025-09-11 11:50:14');
INSERT INTO `payments` (`id`, `student_id`, `school_year_id`, `total_amount`, `payment_date`, `versement_date`, `validation_date`, `payment_method`, `reference_number`, `notes`, `created_by_user_id`, `receipt_number`, `is_rame_physical`, `has_scholarship`, `scholarship_amount`, `has_reduction`, `reduction_amount`, `discount_reason`, `created_at`, `updated_at`) VALUES
(828, 800, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:53:45', 'cash', NULL, NULL, 15, 'REC26250911776436', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:53:45', '2025-09-11 11:53:45'),
(829, 801, 1, 50000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:56:28', 'cash', NULL, NULL, 16, 'REC26250911331745', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:56:28', '2025-09-11 11:56:28'),
(830, 799, 1, 31000.00, '2025-09-11', '2025-09-11', '2025-09-11 11:58:41', 'cash', NULL, NULL, 15, 'REC26250911128434', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 11:58:41', '2025-09-11 11:58:41'),
(831, 802, 1, 50000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:02:24', 'cash', NULL, NULL, 16, 'REC26250911014102', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:02:24', '2025-09-11 12:02:24'),
(832, 803, 1, 50000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:05:30', 'cash', NULL, NULL, 16, 'REC26250911274012', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:05:30', '2025-09-11 12:05:30'),
(833, 351, 1, 50000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:12:03', 'cash', NULL, NULL, 16, 'REC26250911450848', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:12:03', '2025-09-11 12:12:03'),
(834, 805, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:16:52', 'cash', NULL, NULL, 16, 'REC26250911576976', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:16:52', '2025-09-11 12:16:52'),
(835, 806, 1, 91000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:22:15', 'cash', NULL, NULL, 16, 'REC26250911144369', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:22:15', '2025-09-11 12:22:15'),
(836, 807, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:28:35', 'cash', NULL, NULL, 16, 'REC26250911562631', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:28:35', '2025-09-11 12:28:35'),
(837, 808, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:32:34', 'cash', NULL, NULL, 15, 'REC26250911859378', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:32:34', '2025-09-11 12:32:34'),
(838, 809, 1, 90000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:36:53', 'cash', NULL, NULL, 16, 'REC26250911772010', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:36:53', '2025-09-11 12:36:53'),
(839, 810, 1, 70000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:45:03', 'cash', NULL, NULL, 16, 'REC26250911351828', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:45:03', '2025-09-11 12:45:03'),
(840, 811, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:48:52', 'cash', NULL, NULL, 16, 'REC26250911635445', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:48:52', '2025-09-11 12:48:52'),
(841, 812, 1, 31000.00, '2025-09-11', '2025-09-11', '2025-09-11 12:56:06', 'cash', NULL, NULL, 16, 'REC26250911347788', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 12:56:06', '2025-09-11 12:56:06'),
(842, 813, 1, 31000.00, '2025-09-11', '2025-09-11', '2025-09-11 13:04:36', 'cash', NULL, NULL, 16, 'REC26250911794931', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 13:04:36', '2025-09-11 13:04:36'),
(843, 814, 1, 31000.00, '2025-09-11', '2025-09-11', '2025-09-11 13:10:57', 'cash', NULL, NULL, 16, 'REC26250911541345', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 13:10:57', '2025-09-11 13:10:57'),
(844, 815, 1, 31000.00, '2025-09-11', '2025-09-08', '2025-09-11 13:16:35', 'cash', NULL, NULL, 15, 'REC26250911188038', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 13:16:35', '2025-09-11 13:16:35'),
(845, 816, 1, 21000.00, '2025-09-11', '2025-09-11', '2025-09-11 13:40:38', 'cash', NULL, NULL, 16, 'REC26250911359438', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 13:40:38', '2025-09-11 13:40:38'),
(846, 817, 1, 41000.00, '2025-09-11', '2025-09-11', '2025-09-11 13:47:52', 'cash', NULL, NULL, 16, 'REC26250911963631', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 13:47:52', '2025-09-11 13:47:52'),
(847, 818, 1, 31000.00, '2025-09-11', '2025-09-11', '2025-09-11 14:13:29', 'cash', NULL, NULL, 16, 'REC26250911016025', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-11 14:13:29', '2025-09-11 14:13:29'),
(848, 819, 1, 80000.00, '2025-09-12', '2025-08-25', '2025-09-12 04:22:27', 'cash', NULL, NULL, 15, 'REC26250912581851', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 04:22:27', '2025-09-12 04:22:27'),
(849, 820, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 05:15:46', 'cash', NULL, NULL, 16, 'REC26250912106110', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 05:15:46', '2025-09-12 05:15:46'),
(850, 821, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 05:23:21', 'cash', NULL, NULL, 16, 'REC26250912788111', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 05:23:21', '2025-09-12 05:23:21'),
(851, 822, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 05:43:32', 'cash', NULL, NULL, 16, 'REC26250912340221', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 05:43:32', '2025-09-12 05:43:32'),
(852, 822, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 05:44:34', 'cash', NULL, NULL, 16, 'REC26250912922790', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 05:44:34', '2025-09-12 05:44:34'),
(853, 823, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 05:50:37', 'cash', NULL, NULL, 16, 'REC26250912026082', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 05:50:37', '2025-09-12 05:50:37'),
(854, 824, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 05:57:53', 'cash', NULL, NULL, 16, 'REC26250912710509', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 05:57:53', '2025-09-12 05:57:53'),
(855, 825, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 06:07:53', 'cash', NULL, NULL, 16, 'REC26250912913448', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:07:53', '2025-09-12 06:07:53'),
(856, 826, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 06:11:18', 'cash', NULL, NULL, 16, 'REC26250912190252', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:11:18', '2025-09-12 06:11:18'),
(857, 827, 1, 21000.00, '2025-09-12', '2025-09-04', '2025-09-12 06:14:05', 'cash', NULL, NULL, 16, 'REC26250912920145', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:14:05', '2025-09-12 06:14:05'),
(858, 828, 1, 21000.00, '2025-09-12', '2025-09-04', '2025-09-12 06:19:03', 'cash', NULL, NULL, 16, 'REC26250912463993', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:19:03', '2025-09-12 06:19:03'),
(859, 829, 1, 21000.00, '2025-09-12', '2025-09-12', '2025-09-12 06:26:40', 'cash', NULL, NULL, 16, 'REC26250912771813', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:26:40', '2025-09-12 06:26:40'),
(860, 830, 1, 52000.00, '2025-09-12', '2025-09-12', '2025-09-12 06:31:04', 'cash', NULL, NULL, 16, 'REC26250912203141', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:31:04', '2025-09-12 06:31:04'),
(861, 831, 1, 91000.00, '2025-09-12', '2025-09-04', '2025-09-12 06:38:23', 'cash', NULL, NULL, 16, 'REC26250912058060', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:38:23', '2025-09-12 06:38:23'),
(862, 832, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 06:44:25', 'cash', NULL, NULL, 16, 'REC26250912259882', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:44:25', '2025-09-12 06:44:25'),
(863, 833, 1, 63000.00, '2025-09-12', '2025-09-04', '2025-09-12 06:50:14', 'cash', NULL, NULL, 16, 'REC26250912922705', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:50:14', '2025-09-12 06:50:14'),
(864, 736, 1, 50000.00, '2025-09-12', '2025-09-12', '2025-09-12 06:51:52', 'cash', NULL, NULL, 16, 'REC26250912257957', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:51:52', '2025-09-12 06:51:52'),
(865, 834, 1, 48000.00, '2025-09-12', '2025-09-12', '2025-09-12 06:56:20', 'cash', NULL, NULL, 16, 'REC26250912778954', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:56:20', '2025-09-12 06:56:20'),
(866, 835, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 06:59:13', 'cash', NULL, NULL, 16, 'REC26250912645958', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 06:59:13', '2025-09-12 06:59:13'),
(867, 836, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 07:04:43', 'cash', NULL, NULL, 16, 'REC26250912800354', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:04:43', '2025-09-12 07:04:43'),
(868, 837, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 07:07:43', 'cash', NULL, NULL, 16, 'REC26250912805461', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:07:43', '2025-09-12 07:07:43'),
(869, 838, 1, 80000.00, '2025-09-12', '2025-09-12', '2025-09-12 07:12:21', 'cash', NULL, NULL, 16, 'REC26250912645540', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:12:21', '2025-09-12 07:12:21'),
(870, 839, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 07:20:00', 'cash', NULL, NULL, 16, 'REC26250912429046', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:20:00', '2025-09-12 07:20:00'),
(871, 840, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 07:29:42', 'cash', NULL, NULL, 16, 'REC26250912599276', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:29:42', '2025-09-12 07:29:42'),
(872, 841, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 07:35:50', 'cash', NULL, NULL, 16, 'REC26250912309487', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:35:50', '2025-09-12 07:35:50'),
(873, 842, 1, 50000.00, '2025-09-12', '2025-09-12', '2025-09-12 07:47:28', 'cash', NULL, NULL, 16, 'REC26250912756906', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:47:28', '2025-09-12 07:47:28'),
(874, 843, 1, 123000.00, '2025-09-12', '2025-09-04', '2025-09-12 07:54:32', 'cash', NULL, NULL, 16, 'REC26250912550826', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:54:32', '2025-09-12 07:54:32'),
(875, 844, 1, 21000.00, '2025-09-12', '2025-09-04', '2025-09-12 07:59:04', 'cash', NULL, NULL, 16, 'REC26250912498423', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 07:59:04', '2025-09-12 07:59:04'),
(877, 846, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:02:09', 'cash', NULL, NULL, 16, 'REC26250912940547', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:02:09', '2025-09-12 08:02:09'),
(878, 847, 1, 83000.00, '2025-09-12', '2025-07-25', '2025-09-12 08:02:18', 'cash', NULL, NULL, 15, 'REC26250912619248', 0, 1, 20000.00, 0, 0.00, NULL, '2025-09-12 08:02:18', '2025-09-12 08:02:18'),
(879, 848, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:05:20', 'cash', NULL, NULL, 16, 'REC26250912030585', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:05:20', '2025-09-12 08:05:20'),
(880, 849, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:07:33', 'cash', NULL, NULL, 15, 'REC26250912829366', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:07:33', '2025-09-12 08:07:33'),
(882, 851, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:09:35', 'cash', NULL, NULL, 15, 'REC26250912993113', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:09:35', '2025-09-12 08:09:35'),
(883, 852, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:11:22', 'cash', NULL, NULL, 15, 'REC26250912546637', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:11:22', '2025-09-12 08:11:22'),
(884, 853, 1, 47000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:12:22', 'cash', NULL, NULL, 16, 'REC26250912832790', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:12:22', '2025-09-12 08:12:22'),
(885, 854, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:13:43', 'cash', NULL, NULL, 15, 'REC26250912498333', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:13:43', '2025-09-12 08:13:43'),
(886, 422, 1, 25000.00, '2025-09-12', '2025-09-12', '2025-09-12 08:15:42', 'cash', NULL, NULL, 15, 'REC26250912357526', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:15:42', '2025-09-12 08:15:42'),
(887, 423, 1, 25000.00, '2025-09-12', '2025-09-12', '2025-09-12 08:16:33', 'cash', NULL, NULL, 15, 'REC26250912351412', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:16:33', '2025-09-12 08:16:33'),
(889, 856, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:29:38', 'cash', NULL, NULL, 15, 'REC26250912801216', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:29:38', '2025-09-12 08:29:38'),
(890, 857, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:31:34', 'cash', NULL, NULL, 15, 'REC26250912211163', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:31:34', '2025-09-12 08:31:34'),
(891, 858, 1, 61000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:33:24', 'cash', NULL, NULL, 15, 'REC26250912396224', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:33:24', '2025-09-12 08:33:24'),
(892, 859, 1, 41000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:42:42', 'cash', NULL, NULL, 15, 'REC26250912404122', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:42:42', '2025-09-12 08:42:42'),
(893, 860, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 08:48:25', 'cash', NULL, NULL, 16, 'REC26250912760889', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:48:25', '2025-09-12 08:48:25'),
(894, 267, 1, 47000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:48:30', 'cash', NULL, NULL, 15, 'REC26250912584834', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:48:30', '2025-09-12 08:48:30'),
(895, 87, 1, 50000.00, '2025-09-12', '2025-09-12', '2025-09-12 08:50:25', 'cash', NULL, NULL, 15, 'REC26250912912672', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:50:25', '2025-09-12 08:50:25'),
(896, 861, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:53:14', 'cash', NULL, NULL, 16, 'REC26250912504637', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:53:14', '2025-09-12 08:53:14'),
(897, 862, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:55:15', 'cash', NULL, NULL, 15, 'REC26250912800191', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:55:15', '2025-09-12 08:55:15'),
(898, 863, 1, 61000.00, '2025-09-12', '2025-09-04', '2025-09-12 08:55:56', 'cash', NULL, NULL, 16, 'REC26250912447607', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 08:55:56', '2025-09-12 08:55:56'),
(899, 864, 1, 51000.00, '2025-09-12', '2025-09-04', '2025-09-12 09:05:52', 'cash', NULL, NULL, 16, 'REC26250912090710', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 09:05:52', '2025-09-12 09:05:52'),
(900, 867, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 09:26:49', 'cash', NULL, NULL, 16, 'REC26250912106066', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 09:26:49', '2025-09-12 09:26:49'),
(901, 866, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 09:29:43', 'cash', NULL, NULL, 16, 'REC26250912460878', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 09:29:43', '2025-09-12 09:29:43'),
(902, 865, 1, 21000.00, '2025-09-12', '2025-09-12', '2025-09-12 09:32:49', 'cash', NULL, NULL, 16, 'REC26250912530710', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 09:32:49', '2025-09-12 09:32:49'),
(903, 868, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 10:18:16', 'cash', NULL, NULL, 16, 'REC26250912640533', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 10:18:16', '2025-09-12 10:18:16'),
(904, 869, 1, 96500.00, '2025-09-12', '2025-09-12', '2025-09-12 10:22:08', 'cash', NULL, NULL, 16, 'REC26250912246317', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 10:22:08', '2025-09-12 10:22:08'),
(905, 870, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 10:29:00', 'cash', NULL, NULL, 16, 'REC26250912783267', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 10:29:00', '2025-09-12 10:29:00'),
(906, 871, 1, 56500.00, '2025-09-12', '2025-09-12', '2025-09-12 10:39:46', 'cash', NULL, NULL, 16, 'REC26250912380060', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 10:39:46', '2025-09-12 10:39:46'),
(907, 872, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 10:52:20', 'cash', NULL, NULL, 16, 'REC26250912978540', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 10:52:20', '2025-09-12 10:52:20'),
(908, 873, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 10:56:07', 'cash', NULL, NULL, 16, 'REC26250912037270', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 10:56:07', '2025-09-12 10:56:07'),
(909, 874, 1, 41000.00, '2025-09-12', '2025-09-12', '2025-09-12 11:05:05', 'cash', NULL, NULL, 16, 'REC26250912655081', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 11:05:05', '2025-09-12 11:05:05'),
(910, 875, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 11:10:33', 'cash', NULL, NULL, 16, 'REC26250912506113', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 11:10:33', '2025-09-12 11:10:33'),
(911, 876, 1, 21000.00, '2025-09-12', '2025-09-12', '2025-09-12 11:20:01', 'cash', NULL, NULL, 16, 'REC26250912149252', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 11:20:01', '2025-09-12 11:20:01'),
(912, 877, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 11:23:46', 'cash', NULL, NULL, 16, 'REC26250912669384', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 11:23:46', '2025-09-12 11:23:46'),
(913, 878, 1, 31000.00, '2025-09-12', '2025-09-10', '2025-09-12 11:33:46', 'cash', NULL, NULL, 15, 'REC26250912315199', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 11:33:46', '2025-09-12 11:33:46'),
(914, 296, 1, 40000.00, '2025-09-12', '2025-09-12', '2025-09-12 11:36:08', 'cash', NULL, NULL, 15, 'REC26250912599608', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 11:36:08', '2025-09-12 11:36:08'),
(915, 879, 1, 103000.00, '2025-09-12', '2025-09-12', '2025-09-12 12:12:27', 'cash', NULL, NULL, 16, 'REC26250912495652', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 12:12:27', '2025-09-12 12:12:27'),
(916, 880, 1, 46000.00, '2025-09-12', '2025-09-12', '2025-09-12 12:25:20', 'cash', NULL, NULL, 16, 'REC26250912861100', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 12:25:20', '2025-09-12 12:25:20'),
(917, 881, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 12:29:41', 'cash', NULL, NULL, 16, 'REC26250912747266', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 12:29:41', '2025-09-12 12:29:41'),
(918, 882, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 12:32:09', 'cash', NULL, NULL, 16, 'REC26250912645295', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 12:32:09', '2025-09-12 12:32:09'),
(919, 883, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 12:39:41', 'cash', NULL, NULL, 16, 'REC26250912206075', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 12:39:41', '2025-09-12 12:39:41'),
(920, 884, 1, 50000.00, '2025-09-12', '2025-09-12', '2025-09-12 12:44:31', 'cash', NULL, NULL, 15, 'REC26250912006167', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 12:44:31', '2025-09-12 12:44:31'),
(921, 885, 1, 31000.00, '2025-09-12', '2025-09-12', '2025-09-12 12:53:32', 'cash', NULL, NULL, 16, 'REC26250912897941', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 12:53:32', '2025-09-12 12:53:32'),
(922, 812, 1, 19000.00, '2025-09-12', '2025-09-08', '2025-09-12 13:19:16', 'cash', NULL, NULL, 15, 'REC26250912725377', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 13:19:16', '2025-09-12 13:19:16'),
(923, 886, 1, 31000.00, '2025-09-12', '2025-09-04', '2025-09-12 13:21:21', 'cash', NULL, NULL, 16, 'REC26250912823393', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-12 13:21:21', '2025-09-12 13:21:21');

-- --------------------------------------------------------

--
-- Structure de la table `payment_details`
--

CREATE TABLE `payment_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `payment_tranche_id` bigint(20) UNSIGNED NOT NULL,
  `amount_allocated` decimal(10,2) NOT NULL,
  `previous_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `new_total_amount` decimal(10,2) NOT NULL,
  `required_amount_at_time` decimal(10,2) NOT NULL DEFAULT 0.00,
  `was_reduced` tinyint(1) NOT NULL DEFAULT 0,
  `reduction_context` text DEFAULT NULL,
  `is_fully_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `payment_details`
--

INSERT INTO `payment_details` (`id`, `payment_id`, `payment_tranche_id`, `amount_allocated`, `previous_amount`, `new_total_amount`, `required_amount_at_time`, `was_reduced`, `reduction_context`, `is_fully_paid`, `created_at`, `updated_at`) VALUES
(34, 30, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-04 11:25:20', '2025-08-04 11:25:20'),
(35, 30, 3, 29000.00, 0.00, 29000.00, 42000.00, 0, NULL, 0, '2025-08-04 11:25:20', '2025-08-04 11:25:20'),
(37, 32, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-04 11:39:36', '2025-08-04 11:39:36'),
(38, 32, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-08-04 11:39:36', '2025-08-04 11:39:36'),
(39, 32, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-04 11:39:36', '2025-08-04 11:39:36'),
(40, 32, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-04 11:39:36', '2025-08-04 11:39:36'),
(49, 35, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-04 12:58:15', '2025-08-04 12:58:15'),
(50, 36, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-04 13:17:40', '2025-08-04 13:17:40'),
(51, 37, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-04 13:31:31', '2025-08-04 13:31:31'),
(52, 38, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-04 14:01:53', '2025-08-04 14:01:53'),
(54, 40, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-06 08:33:14', '2025-08-06 08:33:14'),
(55, 41, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-06 08:51:04', '2025-08-06 08:51:04'),
(56, 41, 3, 50000.00, 0.00, 50000.00, 50000.00, 0, NULL, 1, '2025-08-06 08:51:04', '2025-08-06 08:51:04'),
(57, 41, 4, 25000.00, 0.00, 25000.00, 25000.00, 0, NULL, 1, '2025-08-06 08:51:04', '2025-08-06 08:51:04'),
(58, 41, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-06 08:51:04', '2025-08-06 08:51:04'),
(59, 42, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-06 09:13:42', '2025-08-06 09:13:42'),
(60, 43, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-06 09:33:42', '2025-08-06 09:33:42'),
(61, 44, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-06 09:43:13', '2025-08-06 09:43:13'),
(74, 48, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-06 10:34:12', '2025-08-06 10:34:12'),
(75, 49, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-06 10:43:37', '2025-08-06 10:43:37'),
(76, 49, 3, 9000.00, 0.00, 9000.00, 70000.00, 0, NULL, 0, '2025-08-06 10:43:37', '2025-08-06 10:43:37'),
(77, 50, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-06 11:59:46', '2025-08-06 11:59:46'),
(78, 50, 3, 19000.00, 0.00, 19000.00, 77000.00, 0, NULL, 0, '2025-08-06 11:59:46', '2025-08-06 11:59:46'),
(79, 51, 3, 50000.00, 19000.00, 69000.00, 77000.00, 0, NULL, 0, '2025-08-06 12:14:31', '2025-08-06 12:14:31'),
(84, 53, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-06 13:38:42', '2025-08-06 13:38:42'),
(85, 53, 3, 45000.00, 0.00, 45000.00, 77000.00, 0, NULL, 0, '2025-08-06 13:38:42', '2025-08-06 13:38:42'),
(86, 54, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-06 14:14:44', '2025-08-06 14:14:44'),
(87, 55, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-06 14:36:45', '2025-08-06 14:36:45'),
(88, 56, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-06 14:46:16', '2025-08-06 14:46:16'),
(89, 56, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, NULL, 1, '2025-08-06 14:46:16', '2025-08-06 14:46:16'),
(90, 56, 4, 7000.00, 0.00, 7000.00, 20000.00, 0, NULL, 0, '2025-08-06 14:46:16', '2025-08-06 14:46:16'),
(91, 57, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-06 15:00:20', '2025-08-06 15:00:20'),
(96, 59, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-07 11:12:15', '2025-08-07 11:12:15'),
(97, 60, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-07 11:23:48', '2025-08-07 11:23:48'),
(98, 61, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-07 11:29:17', '2025-08-07 11:29:17'),
(103, 64, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-07 11:34:46', '2025-08-07 11:34:46'),
(104, 64, 3, 19000.00, 0.00, 19000.00, 22000.00, 0, NULL, 0, '2025-08-07 11:34:46', '2025-08-07 11:34:46'),
(105, 65, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-07 11:43:26', '2025-08-07 11:43:26'),
(110, 67, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-07 11:49:14', '2025-08-07 11:49:14'),
(111, 68, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-07 12:03:03', '2025-08-07 12:03:03'),
(112, 68, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-08-07 12:03:03', '2025-08-07 12:03:03'),
(113, 68, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-07 12:03:03', '2025-08-07 12:03:03'),
(114, 68, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-07 12:03:03', '2025-08-07 12:03:03'),
(118, 70, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-07 12:47:52', '2025-08-07 12:47:52'),
(119, 70, 3, 19000.00, 0.00, 19000.00, 22000.00, 0, NULL, 0, '2025-08-07 12:47:52', '2025-08-07 12:47:52'),
(120, 71, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-07 12:59:02', '2025-08-07 12:59:02'),
(121, 71, 3, 19000.00, 0.00, 19000.00, 57000.00, 0, NULL, 0, '2025-08-07 12:59:02', '2025-08-07 12:59:02'),
(126, 73, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-07 13:39:46', '2025-08-07 13:39:46'),
(127, 73, 3, 50000.00, 0.00, 50000.00, 50000.00, 0, NULL, 1, '2025-08-07 13:39:46', '2025-08-07 13:39:46'),
(128, 73, 4, 25000.00, 0.00, 25000.00, 25000.00, 0, NULL, 1, '2025-08-07 13:39:46', '2025-08-07 13:39:46'),
(129, 73, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-07 13:39:46', '2025-08-07 13:39:46'),
(149, 79, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-11 06:49:36', '2025-08-11 06:49:36'),
(150, 79, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-11 06:49:36', '2025-08-11 06:49:36'),
(151, 79, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-11 06:49:36', '2025-08-11 06:49:36'),
(152, 79, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-11 06:49:36', '2025-08-11 06:49:36'),
(154, 81, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-12 06:11:49', '2025-08-12 06:11:49'),
(163, 84, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-12 07:17:33', '2025-08-12 07:17:33'),
(164, 85, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-12 10:51:43', '2025-08-12 10:51:43'),
(165, 85, 3, 50000.00, 0.00, 50000.00, 50000.00, 0, NULL, 1, '2025-08-12 10:51:43', '2025-08-12 10:51:43'),
(166, 85, 4, 25000.00, 0.00, 25000.00, 25000.00, 0, NULL, 1, '2025-08-12 10:51:43', '2025-08-12 10:51:43'),
(167, 85, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-12 10:51:43', '2025-08-12 10:51:43'),
(168, 86, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-12 10:57:17', '2025-08-12 10:57:17'),
(173, 88, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-15 06:07:20', '2025-08-15 06:07:20'),
(182, 91, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-15 06:29:28', '2025-08-15 06:29:28'),
(183, 91, 3, 39000.00, 0.00, 39000.00, 72000.00, 0, NULL, 0, '2025-08-15 06:29:28', '2025-08-15 06:29:28'),
(184, 92, 3, 31000.00, 39000.00, 70000.00, 72000.00, 0, NULL, 0, '2025-08-15 06:30:08', '2025-08-15 06:30:08'),
(185, 93, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-15 06:35:41', '2025-08-15 06:35:41'),
(186, 93, 3, 29000.00, 0.00, 29000.00, 57000.00, 0, NULL, 0, '2025-08-15 06:35:41', '2025-08-15 06:35:41'),
(187, 94, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-19 13:02:12', '2025-08-19 13:02:12'),
(188, 95, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-19 14:05:28', '2025-08-19 14:05:28'),
(190, 97, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-19 14:15:13', '2025-08-19 14:15:13'),
(191, 97, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-08-19 14:15:14', '2025-08-19 14:15:14'),
(192, 97, 4, 17000.00, 0.00, 17000.00, 20000.00, 0, NULL, 0, '2025-08-19 14:15:14', '2025-08-19 14:15:14'),
(193, 98, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-19 14:18:24', '2025-08-19 14:18:24'),
(194, 99, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-19 14:21:50', '2025-08-19 14:21:50'),
(195, 99, 3, 22000.00, 0.00, 22000.00, 70000.00, 0, NULL, 0, '2025-08-19 14:21:50', '2025-08-19 14:21:50'),
(196, 100, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-19 14:27:32', '2025-08-19 14:27:32'),
(197, 101, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-19 14:35:16', '2025-08-19 14:35:16'),
(198, 102, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-19 14:38:58', '2025-08-19 14:38:58'),
(199, 102, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-08-19 14:38:58', '2025-08-19 14:38:58'),
(200, 102, 4, 1000.00, 0.00, 1000.00, 25000.00, 0, NULL, 0, '2025-08-19 14:38:58', '2025-08-19 14:38:58'),
(202, 104, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-19 14:47:58', '2025-08-19 14:47:58'),
(203, 105, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-19 14:52:48', '2025-08-19 14:52:48'),
(204, 105, 3, 13000.00, 0.00, 13000.00, 57000.00, 0, NULL, 0, '2025-08-19 14:52:48', '2025-08-19 14:52:48'),
(205, 106, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-19 15:00:48', '2025-08-19 15:00:48'),
(206, 106, 3, 29000.00, 0.00, 29000.00, 52000.00, 0, NULL, 0, '2025-08-19 15:00:48', '2025-08-19 15:00:48'),
(207, 107, 2, 20700.00, 0.00, 20700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20700 FCFA', 1, '2025-08-20 06:26:24', '2025-09-12 08:18:05'),
(208, 107, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Correction migration - Ancien système (haut→bas): 42000.00 FCFA', 1, '2025-08-20 06:26:24', '2025-09-12 08:18:05'),
(209, 107, 4, 20000.00, 0.00, 20000.00, 19700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 06:26:24', '2025-09-12 08:18:05'),
(210, 108, 2, 18200.00, 0.00, 18200.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 18200 FCFA', 1, '2025-08-20 06:31:43', '2025-09-12 08:18:05'),
(211, 108, 3, 67000.00, 0.00, 67000.00, 67000.00, 0, 'Correction migration - Ancien système (haut→bas): 67000.00 FCFA', 1, '2025-08-20 06:31:43', '2025-09-12 08:18:05'),
(212, 108, 4, 20000.00, 0.00, 20000.00, 17200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 06:31:43', '2025-09-12 08:18:05'),
(213, 109, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 07:23:35', '2025-08-20 07:23:35'),
(214, 110, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 07:28:16', '2025-08-20 07:28:16'),
(215, 111, 2, 25900.00, 0.00, 25900.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 25900 FCFA', 1, '2025-08-20 07:44:25', '2025-09-12 08:18:05'),
(216, 111, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-08-20 07:44:25', '2025-09-12 08:18:05'),
(217, 111, 4, 30000.00, 0.00, 30000.00, 24900.00, 1, 'Correction migration - Ancien système (haut→bas): 30000.00 FCFA', 1, '2025-08-20 07:44:25', '2025-09-12 08:18:05'),
(218, 112, 2, 16900.00, 0.00, 16900.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 16900 FCFA', 1, '2025-08-20 07:48:04', '2025-09-12 08:18:05'),
(219, 112, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-08-20 07:48:04', '2025-09-12 08:18:05'),
(220, 112, 4, 30000.00, 0.00, 30000.00, 25900.00, 1, 'Correction migration - Ancien système (haut→bas): 30000.00 FCFA', 1, '2025-08-20 07:48:04', '2025-09-12 08:18:05'),
(221, 113, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 07:51:58', '2025-08-20 07:51:58'),
(222, 114, 2, 17700.00, 0.00, 17700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 17700 FCFA', 1, '2025-08-20 07:55:23', '2025-09-12 08:18:05'),
(223, 114, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Correction migration - Ancien système (haut→bas): 72000.00 FCFA', 1, '2025-08-20 07:55:23', '2025-09-12 08:18:05'),
(224, 114, 4, 20000.00, 0.00, 20000.00, 16700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 07:55:23', '2025-09-12 08:18:05'),
(225, 115, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 08:00:35', '2025-08-20 08:00:35'),
(226, 116, 2, 26700.00, 0.00, 26700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 26700 FCFA', 1, '2025-08-20 08:09:56', '2025-09-12 08:18:05'),
(227, 116, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Correction migration - Ancien système (haut→bas): 72000.00 FCFA', 1, '2025-08-20 08:09:56', '2025-09-12 08:18:05'),
(228, 116, 4, 20000.00, 0.00, 20000.00, 15700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 08:09:56', '2025-09-12 08:18:05'),
(229, 117, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 08:24:54', '2025-08-20 08:24:54'),
(230, 117, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-20 08:24:54', '2025-08-20 08:24:54'),
(231, 117, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-20 08:24:54', '2025-08-20 08:24:54'),
(232, 118, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-20 09:27:31', '2025-08-20 09:27:31'),
(233, 119, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 09:31:34', '2025-08-20 09:31:34'),
(234, 120, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-20 09:31:54', '2025-08-20 09:31:54'),
(235, 120, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-20 09:31:54', '2025-08-20 09:31:54'),
(236, 121, 2, 28700.00, 0.00, 28700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 28700 FCFA', 1, '2025-08-20 09:39:30', '2025-09-12 08:18:05'),
(237, 121, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Correction migration - Ancien système (haut→bas): 52000.00 FCFA', 1, '2025-08-20 09:39:30', '2025-09-12 08:18:05'),
(238, 121, 4, 20000.00, 0.00, 20000.00, 17700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 09:39:30', '2025-09-12 08:18:05'),
(239, 122, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 09:52:26', '2025-08-20 09:52:26'),
(240, 123, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-20 10:02:59', '2025-08-20 10:02:59'),
(241, 124, 2, 24700.00, 0.00, 24700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 24700 FCFA', 1, '2025-08-20 10:06:09', '2025-09-12 08:18:05'),
(242, 124, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, 'Correction migration - Ancien système (haut→bas): 92000.00 FCFA', 1, '2025-08-20 10:06:09', '2025-09-12 08:18:05'),
(243, 124, 4, 20000.00, 0.00, 20000.00, 13700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 10:06:09', '2025-09-12 08:18:05'),
(244, 125, 2, 24700.00, 0.00, 24700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 24700 FCFA', 1, '2025-08-20 10:11:07', '2025-09-12 08:18:05'),
(245, 125, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, 'Correction migration - Ancien système (haut→bas): 92000.00 FCFA', 1, '2025-08-20 10:11:07', '2025-09-12 08:18:05'),
(246, 125, 4, 20000.00, 0.00, 20000.00, 13700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 10:11:07', '2025-09-12 08:18:05'),
(247, 126, 2, 19200.00, 0.00, 19200.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 19200 FCFA', 1, '2025-08-20 10:22:55', '2025-09-12 08:18:05'),
(248, 126, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, 'Correction migration - Ancien système (haut→bas): 57000.00 FCFA', 1, '2025-08-20 10:22:55', '2025-09-12 08:18:05'),
(249, 126, 4, 20000.00, 0.00, 20000.00, 18200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 10:22:55', '2025-09-12 08:18:05'),
(250, 127, 2, 20700.00, 0.00, 20700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20700 FCFA', 1, '2025-08-20 10:59:56', '2025-09-12 08:18:05'),
(251, 127, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Correction migration - Ancien système (haut→bas): 42000.00 FCFA', 1, '2025-08-20 10:59:56', '2025-09-12 08:18:05'),
(252, 127, 4, 20000.00, 0.00, 20000.00, 19700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 10:59:56', '2025-09-12 08:18:05'),
(253, 128, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:02:54', '2025-08-20 11:02:54'),
(254, 129, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-20 11:05:28', '2025-08-20 11:05:28'),
(255, 130, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:09:03', '2025-08-20 11:09:03'),
(256, 131, 2, 20200.00, 0.00, 20200.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20200 FCFA', 1, '2025-08-20 11:18:42', '2025-09-12 08:18:05'),
(257, 131, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, 'Correction migration - Ancien système (haut→bas): 47000.00 FCFA', 1, '2025-08-20 11:18:42', '2025-09-12 08:18:05'),
(258, 131, 4, 20000.00, 0.00, 20000.00, 19200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 11:18:42', '2025-09-12 08:18:05'),
(259, 132, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:22:22', '2025-08-20 11:22:22'),
(260, 132, 3, 14000.00, 0.00, 14000.00, 22000.00, 0, NULL, 0, '2025-08-20 11:22:22', '2025-08-20 11:22:22'),
(261, 133, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:25:45', '2025-08-20 11:25:45'),
(262, 134, 2, 16900.00, 0.00, 16900.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 16900 FCFA', 1, '2025-08-20 11:30:36', '2025-09-12 08:18:05'),
(263, 134, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-08-20 11:30:36', '2025-09-12 08:18:05'),
(264, 134, 4, 30000.00, 0.00, 30000.00, 25900.00, 1, 'Correction migration - Ancien système (haut→bas): 30000.00 FCFA', 1, '2025-08-20 11:30:36', '2025-09-12 08:18:05'),
(265, 135, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:34:11', '2025-08-20 11:34:11'),
(266, 136, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:37:28', '2025-08-20 11:37:28'),
(267, 137, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 12:23:54', '2025-08-20 12:23:54'),
(268, 138, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-20 12:26:52', '2025-08-20 12:26:52'),
(269, 139, 2, 8400.00, 0.00, 8400.00, 21000.00, 0, 'Correction migration - Ancien système (haut→bas): 8400 FCFA', 1, '2025-08-20 12:30:17', '2025-09-12 08:18:05'),
(270, 139, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-08-20 12:30:17', '2025-09-12 08:18:05'),
(271, 139, 4, 25000.00, 0.00, 25000.00, 22400.00, 1, 'Correction migration - Ancien système (haut→bas): 25000.00 FCFA', 1, '2025-08-20 12:30:17', '2025-09-12 08:18:05'),
(272, 140, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 13:52:52', '2025-08-20 13:52:52'),
(273, 140, 3, 29000.00, 0.00, 29000.00, 57000.00, 0, NULL, 0, '2025-08-20 13:52:52', '2025-08-20 13:52:52'),
(274, 141, 2, 17700.00, 0.00, 17700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 17700 FCFA', 1, '2025-08-20 14:02:07', '2025-09-12 08:18:05'),
(275, 141, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Correction migration - Ancien système (haut→bas): 72000.00 FCFA', 1, '2025-08-20 14:02:07', '2025-09-12 08:18:05'),
(276, 141, 4, 20000.00, 0.00, 20000.00, 16700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-20 14:02:07', '2025-09-12 08:18:05'),
(277, 142, 2, 25900.00, 0.00, 25900.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 25900 FCFA', 1, '2025-08-20 14:06:02', '2025-09-12 08:18:05'),
(278, 142, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-08-20 14:06:02', '2025-09-12 08:18:05'),
(279, 142, 4, 30000.00, 0.00, 30000.00, 24900.00, 1, 'Correction migration - Ancien système (haut→bas): 30000.00 FCFA', 1, '2025-08-20 14:06:02', '2025-09-12 08:18:05'),
(280, 143, 2, 8400.00, 0.00, 8400.00, 21000.00, 0, 'Correction migration - Ancien système (haut→bas): 8400 FCFA', 1, '2025-08-20 14:10:15', '2025-09-12 08:18:05'),
(281, 143, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-08-20 14:10:15', '2025-09-12 08:18:05'),
(282, 143, 4, 25000.00, 0.00, 25000.00, 22400.00, 1, 'Correction migration - Ancien système (haut→bas): 25000.00 FCFA', 1, '2025-08-20 14:10:15', '2025-09-12 08:18:05'),
(283, 144, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(284, 144, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(285, 144, 4, 25000.00, 0.00, 25000.00, 25000.00, 0, NULL, 1, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(286, 144, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(290, 146, 2, 26200.00, 0.00, 26200.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 26200 FCFA', 1, '2025-08-27 09:25:47', '2025-09-12 08:18:05'),
(291, 146, 3, 77000.00, 0.00, 77000.00, 77000.00, 0, 'Correction migration - Ancien système (haut→bas): 77000.00 FCFA', 1, '2025-08-27 09:25:47', '2025-09-12 08:18:05'),
(292, 146, 4, 20000.00, 0.00, 20000.00, 15200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-27 09:25:47', '2025-09-12 08:18:05'),
(293, 147, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 09:28:48', '2025-08-27 09:28:48'),
(294, 148, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:00:42', '2025-08-27 10:00:42'),
(295, 148, 3, 9000.00, 0.00, 9000.00, 82000.00, 0, NULL, 0, '2025-08-27 10:00:42', '2025-08-27 10:00:42'),
(296, 149, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-27 10:04:07', '2025-08-27 10:04:07'),
(297, 149, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-08-27 10:04:07', '2025-08-27 10:04:07'),
(298, 149, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-27 10:04:07', '2025-08-27 10:04:07'),
(299, 149, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-27 10:04:07', '2025-08-27 10:04:07'),
(300, 150, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-27 10:14:12', '2025-08-27 10:14:12'),
(301, 150, 3, 54000.00, 0.00, 54000.00, 77000.00, 0, NULL, 0, '2025-08-27 10:14:12', '2025-08-27 10:14:12'),
(302, 151, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-27 10:17:03', '2025-08-27 10:17:03'),
(303, 152, 2, 26200.00, 0.00, 26200.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 26200 FCFA', 1, '2025-08-27 10:18:17', '2025-09-12 08:18:05'),
(304, 152, 3, 77000.00, 0.00, 77000.00, 77000.00, 0, 'Correction migration - Ancien système (haut→bas): 77000.00 FCFA', 1, '2025-08-27 10:18:17', '2025-09-12 08:18:05'),
(305, 152, 4, 20000.00, 0.00, 20000.00, 15200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-27 10:18:17', '2025-09-12 08:18:05'),
(306, 153, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:21:37', '2025-08-27 10:21:37'),
(307, 153, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-27 10:21:37', '2025-08-27 10:21:37'),
(308, 153, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-27 10:21:37', '2025-08-27 10:21:37'),
(309, 154, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:25:18', '2025-08-27 10:25:18'),
(310, 154, 3, 54000.00, 0.00, 54000.00, 72000.00, 0, NULL, 0, '2025-08-27 10:25:18', '2025-08-27 10:25:18'),
(311, 155, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:28:14', '2025-08-27 10:28:14'),
(312, 156, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:31:30', '2025-08-27 10:31:30'),
(313, 156, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-27 10:31:30', '2025-08-27 10:31:30'),
(314, 156, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-27 10:31:30', '2025-08-27 10:31:30'),
(315, 156, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-27 10:31:30', '2025-08-27 10:31:30'),
(316, 157, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-27 10:34:32', '2025-08-27 10:34:32'),
(317, 158, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:37:09', '2025-08-27 10:37:09'),
(318, 159, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:41:14', '2025-08-27 10:41:14'),
(319, 160, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:43:50', '2025-08-27 10:43:50'),
(325, 163, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 11:09:27', '2025-08-27 11:09:27'),
(326, 163, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-27 11:09:27', '2025-08-27 11:09:27'),
(327, 163, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-27 11:09:27', '2025-08-27 11:09:27'),
(328, 164, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-27 11:15:04', '2025-08-27 11:15:04'),
(329, 165, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 11:22:36', '2025-08-27 11:22:36'),
(330, 166, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 11:29:26', '2025-08-27 11:29:26'),
(331, 167, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 11:39:46', '2025-08-27 11:39:46'),
(332, 168, 2, 28700.00, 0.00, 28700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 28700 FCFA', 1, '2025-08-27 12:16:47', '2025-09-12 08:18:05'),
(333, 168, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Correction migration - Ancien système (haut→bas): 52000.00 FCFA', 1, '2025-08-27 12:16:47', '2025-09-12 08:18:05'),
(334, 168, 4, 20000.00, 0.00, 20000.00, 17700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-27 12:16:47', '2025-09-12 08:18:05'),
(335, 169, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-27 12:22:59', '2025-08-27 12:22:59'),
(336, 169, 3, 29000.00, 0.00, 29000.00, 52000.00, 0, NULL, 0, '2025-08-27 12:22:59', '2025-08-27 12:22:59'),
(337, 170, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 12:26:35', '2025-08-27 12:26:35'),
(338, 170, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-08-27 12:26:35', '2025-08-27 12:26:35'),
(339, 171, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-27 12:39:42', '2025-08-27 12:39:42'),
(340, 172, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 12:42:59', '2025-08-27 12:42:59'),
(341, 172, 3, 27000.00, 0.00, 27000.00, 27000.00, 0, NULL, 1, '2025-08-27 12:42:59', '2025-08-27 12:42:59'),
(342, 172, 4, 15000.00, 0.00, 15000.00, 20000.00, 0, NULL, 0, '2025-08-27 12:42:59', '2025-08-27 12:42:59'),
(343, 173, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 12:46:49', '2025-08-27 12:46:49'),
(344, 173, 3, 9000.00, 0.00, 9000.00, 22000.00, 0, NULL, 0, '2025-08-27 12:46:49', '2025-08-27 12:46:49'),
(345, 174, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-27 12:51:42', '2025-08-27 12:51:42'),
(346, 175, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 12:59:43', '2025-08-27 12:59:43'),
(347, 176, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 13:08:18', '2025-08-27 13:08:18'),
(348, 177, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 13:13:19', '2025-08-27 13:13:19'),
(349, 178, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 13:18:47', '2025-08-27 13:18:47'),
(350, 178, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-08-27 13:18:47', '2025-08-27 13:18:47'),
(351, 178, 4, 10000.00, 0.00, 10000.00, 20000.00, 0, NULL, 0, '2025-08-27 13:18:47', '2025-08-27 13:18:47'),
(352, 179, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 13:24:33', '2025-08-27 13:24:33'),
(353, 180, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-27 13:27:17', '2025-08-27 13:27:17'),
(354, 181, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 13:30:23', '2025-08-27 13:30:23'),
(355, 181, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-08-27 13:30:23', '2025-08-27 13:30:23'),
(356, 181, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-27 13:30:23', '2025-08-27 13:30:23'),
(357, 182, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-27 13:42:01', '2025-08-27 13:42:01'),
(358, 183, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-27 13:45:29', '2025-08-27 13:45:29'),
(359, 183, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-08-27 13:45:29', '2025-08-27 13:45:29'),
(360, 183, 4, 5000.00, 0.00, 5000.00, 25000.00, 0, NULL, 0, '2025-08-27 13:45:29', '2025-08-27 13:45:29'),
(361, 184, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-08-27 13:47:55', '2025-08-27 13:47:55'),
(362, 184, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-27 13:47:55', '2025-08-27 13:47:55'),
(363, 184, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-27 13:47:55', '2025-08-27 13:47:55'),
(364, 185, 2, 16700.00, 0.00, 16700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 16700 FCFA', 1, '2025-08-27 13:53:47', '2025-09-12 08:18:05'),
(365, 185, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Correction migration - Ancien système (haut→bas): 82000.00 FCFA', 1, '2025-08-27 13:53:47', '2025-09-12 08:18:05'),
(366, 185, 4, 20000.00, 0.00, 20000.00, 15700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-27 13:53:47', '2025-09-12 08:18:05'),
(367, 186, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-27 13:59:20', '2025-08-27 13:59:20'),
(368, 186, 3, 5000.00, 0.00, 5000.00, 82000.00, 0, NULL, 0, '2025-08-27 13:59:20', '2025-08-27 13:59:20'),
(369, 187, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 06:19:22', '2025-08-28 06:19:22'),
(370, 187, 3, 5000.00, 0.00, 5000.00, 92000.00, 0, NULL, 0, '2025-08-28 06:19:22', '2025-08-28 06:19:22'),
(371, 188, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 06:23:15', '2025-08-28 06:23:15'),
(372, 188, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-28 06:23:15', '2025-08-28 06:23:15'),
(373, 188, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-28 06:23:15', '2025-08-28 06:23:15'),
(374, 188, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-28 06:23:15', '2025-08-28 06:23:15'),
(375, 189, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-28 06:30:15', '2025-08-28 06:30:15'),
(376, 190, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 07:18:40', '2025-08-28 07:18:40'),
(377, 190, 3, 15000.00, 0.00, 15000.00, 72000.00, 0, NULL, 0, '2025-08-28 07:18:40', '2025-08-28 07:18:40'),
(378, 191, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 07:21:50', '2025-08-28 07:21:50'),
(379, 192, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 07:25:38', '2025-08-28 07:25:38'),
(380, 192, 3, 52000.00, 0.00, 52000.00, 72000.00, 0, NULL, 0, '2025-08-28 07:25:38', '2025-08-28 07:25:38'),
(381, 193, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 07:28:55', '2025-08-28 07:28:55'),
(382, 193, 3, 52000.00, 0.00, 52000.00, 72000.00, 0, NULL, 0, '2025-08-28 07:28:55', '2025-08-28 07:28:55'),
(383, 194, 2, 28700.00, 0.00, 28700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 28700 FCFA', 1, '2025-08-28 07:33:34', '2025-09-12 08:18:05'),
(384, 194, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Correction migration - Ancien système (haut→bas): 52000.00 FCFA', 1, '2025-08-28 07:33:34', '2025-09-12 08:18:05'),
(385, 194, 4, 20000.00, 0.00, 20000.00, 17700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-28 07:33:34', '2025-09-12 08:18:05'),
(386, 195, 2, 20700.00, 0.00, 20700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20700 FCFA', 1, '2025-08-28 07:35:58', '2025-09-12 08:18:05'),
(387, 195, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Correction migration - Ancien système (haut→bas): 42000.00 FCFA', 1, '2025-08-28 07:35:58', '2025-09-12 08:18:05'),
(388, 195, 4, 20000.00, 0.00, 20000.00, 19700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-28 07:35:58', '2025-09-12 08:18:05'),
(389, 196, 2, 25900.00, 0.00, 25900.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 25900 FCFA', 1, '2025-08-28 07:39:21', '2025-09-12 08:18:05'),
(390, 196, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-08-28 07:39:21', '2025-09-12 08:18:05'),
(391, 196, 4, 30000.00, 0.00, 30000.00, 24900.00, 1, 'Correction migration - Ancien système (haut→bas): 30000.00 FCFA', 1, '2025-08-28 07:39:21', '2025-09-12 08:18:05'),
(392, 197, 2, 18700.00, 0.00, 18700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 18700 FCFA', 1, '2025-08-28 07:42:35', '2025-09-12 08:18:05'),
(393, 197, 3, 62000.00, 0.00, 62000.00, 62000.00, 0, 'Correction migration - Ancien système (haut→bas): 62000.00 FCFA', 1, '2025-08-28 07:42:35', '2025-09-12 08:18:05'),
(394, 197, 4, 20000.00, 0.00, 20000.00, 17700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-28 07:42:35', '2025-09-12 08:18:05'),
(395, 198, 2, 18700.00, 0.00, 18700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 18700 FCFA', 1, '2025-08-28 07:48:22', '2025-09-12 08:18:05'),
(396, 198, 3, 62000.00, 0.00, 62000.00, 62000.00, 0, 'Correction migration - Ancien système (haut→bas): 62000.00 FCFA', 1, '2025-08-28 07:48:22', '2025-09-12 08:18:05'),
(397, 198, 4, 20000.00, 0.00, 20000.00, 17700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-28 07:48:22', '2025-09-12 08:18:05'),
(398, 199, 2, 25700.00, 0.00, 25700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 25700 FCFA', 1, '2025-08-28 07:53:35', '2025-09-12 08:18:05'),
(399, 199, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Correction migration - Ancien système (haut→bas): 82000.00 FCFA', 1, '2025-08-28 07:53:35', '2025-09-12 08:18:05'),
(400, 199, 4, 20000.00, 0.00, 20000.00, 14700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-28 07:53:35', '2025-09-12 08:18:05'),
(401, 200, 2, 20700.00, 0.00, 20700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20700 FCFA', 1, '2025-08-28 07:57:35', '2025-09-12 08:18:05'),
(402, 200, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Correction migration - Ancien système (haut→bas): 42000.00 FCFA', 1, '2025-08-28 07:57:35', '2025-09-12 08:18:05'),
(403, 200, 4, 20000.00, 0.00, 20000.00, 19700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-28 07:57:35', '2025-09-12 08:18:05'),
(404, 201, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 08:05:11', '2025-08-28 08:05:11'),
(405, 201, 3, 24000.00, 0.00, 24000.00, 72000.00, 0, NULL, 0, '2025-08-28 08:05:11', '2025-08-28 08:05:11'),
(406, 202, 2, 16700.00, 0.00, 16700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 16700 FCFA', 1, '2025-08-28 08:08:36', '2025-09-12 08:18:05'),
(407, 202, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Correction migration - Ancien système (haut→bas): 82000.00 FCFA', 1, '2025-08-28 08:08:36', '2025-09-12 08:18:05'),
(408, 202, 4, 20000.00, 0.00, 20000.00, 15700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-28 08:08:36', '2025-09-12 08:18:05'),
(409, 203, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 08:11:41', '2025-08-28 08:11:41'),
(410, 203, 3, 4000.00, 0.00, 4000.00, 82000.00, 0, NULL, 0, '2025-08-28 08:11:41', '2025-08-28 08:11:41'),
(411, 204, 2, 25700.00, 0.00, 25700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 25700 FCFA', 1, '2025-08-28 08:14:02', '2025-09-12 08:18:05'),
(412, 204, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Correction migration - Ancien système (haut→bas): 82000.00 FCFA', 1, '2025-08-28 08:14:02', '2025-09-12 08:18:05'),
(413, 204, 4, 20000.00, 0.00, 20000.00, 14700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-28 08:14:02', '2025-09-12 08:18:05'),
(414, 205, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-28 08:17:32', '2025-08-28 08:17:32'),
(415, 206, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 08:27:30', '2025-08-28 08:27:30'),
(416, 206, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-28 08:27:30', '2025-08-28 08:27:30'),
(417, 206, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-28 08:27:30', '2025-08-28 08:27:30'),
(418, 206, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-28 08:27:30', '2025-08-28 08:27:30'),
(419, 207, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 08:33:47', '2025-08-28 08:33:47'),
(420, 208, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 10:13:12', '2025-08-28 10:13:12'),
(421, 209, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-28 10:17:46', '2025-08-28 10:17:46'),
(422, 210, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 10:21:06', '2025-08-28 10:21:06'),
(423, 210, 3, 67000.00, 0.00, 67000.00, 67000.00, 0, NULL, 1, '2025-08-28 10:21:06', '2025-08-28 10:21:06'),
(424, 211, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 10:26:46', '2025-08-28 10:26:46'),
(425, 212, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 10:29:43', '2025-08-28 10:29:43'),
(426, 212, 3, 15000.00, 0.00, 15000.00, 42000.00, 0, NULL, 0, '2025-08-28 10:29:43', '2025-08-28 10:29:43'),
(427, 213, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 10:33:35', '2025-08-28 10:33:35'),
(428, 213, 3, 26000.00, 0.00, 26000.00, 62000.00, 0, NULL, 0, '2025-08-28 10:33:35', '2025-08-28 10:33:35'),
(429, 214, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 10:38:18', '2025-08-28 10:38:18'),
(430, 214, 3, 49000.00, 0.00, 49000.00, 72000.00, 0, NULL, 0, '2025-08-28 10:38:18', '2025-08-28 10:38:18'),
(431, 215, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 10:40:52', '2025-08-28 10:40:52'),
(432, 215, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-08-28 10:40:52', '2025-08-28 10:40:52'),
(433, 215, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-28 10:40:52', '2025-08-28 10:40:52'),
(434, 215, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-28 10:40:52', '2025-08-28 10:40:52'),
(435, 216, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 10:43:53', '2025-08-28 10:43:53'),
(436, 216, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-08-28 10:43:54', '2025-08-28 10:43:54'),
(437, 216, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-28 10:43:54', '2025-08-28 10:43:54'),
(438, 216, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-28 10:43:54', '2025-08-28 10:43:54'),
(439, 217, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 10:47:06', '2025-08-28 10:47:06'),
(440, 218, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-28 10:49:54', '2025-08-28 10:49:54'),
(441, 219, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 10:54:03', '2025-08-28 10:54:03'),
(442, 220, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 10:57:27', '2025-08-28 10:57:27'),
(443, 220, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-08-28 10:57:27', '2025-08-28 10:57:27'),
(444, 221, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:02:42', '2025-08-28 11:02:42'),
(445, 221, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-28 11:02:42', '2025-08-28 11:02:42'),
(446, 221, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-28 11:02:42', '2025-08-28 11:02:42'),
(447, 222, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-28 11:05:40', '2025-08-28 11:05:40'),
(448, 223, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:09:39', '2025-08-28 11:09:39'),
(449, 223, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-08-28 11:09:39', '2025-08-28 11:09:39'),
(450, 223, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-28 11:09:39', '2025-08-28 11:09:39'),
(451, 223, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-28 11:09:39', '2025-08-28 11:09:39'),
(452, 224, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 11:12:06', '2025-08-28 11:12:06'),
(453, 224, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-28 11:12:06', '2025-08-28 11:12:06'),
(454, 225, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 11:14:44', '2025-08-28 11:14:44'),
(455, 226, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-28 11:17:23', '2025-08-28 11:17:23'),
(456, 227, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 11:22:38', '2025-08-28 11:22:38'),
(457, 228, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:26:08', '2025-08-28 11:26:08'),
(458, 229, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:29:13', '2025-08-28 11:29:13'),
(459, 229, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-28 11:29:13', '2025-08-28 11:29:13'),
(460, 229, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-28 11:29:13', '2025-08-28 11:29:13'),
(461, 229, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-28 11:29:13', '2025-08-28 11:29:13'),
(462, 230, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:31:47', '2025-08-28 11:31:47'),
(463, 231, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:35:51', '2025-08-28 11:35:51'),
(464, 232, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:40:02', '2025-08-28 11:40:02'),
(465, 232, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-28 11:40:02', '2025-08-28 11:40:02'),
(466, 233, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:47:56', '2025-08-28 11:47:56'),
(467, 234, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 11:53:51', '2025-08-28 11:53:51'),
(468, 235, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-28 11:59:19', '2025-08-28 11:59:19'),
(469, 235, 3, 21000.00, 0.00, 21000.00, 50000.00, 0, NULL, 0, '2025-08-28 11:59:19', '2025-08-28 11:59:19'),
(470, 236, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-29 10:58:48', '2025-08-29 10:58:48'),
(471, 237, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-29 13:27:42', '2025-08-29 13:27:42'),
(472, 237, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-29 13:27:42', '2025-08-29 13:27:42'),
(473, 237, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-29 13:27:42', '2025-08-29 13:27:42'),
(474, 238, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-29 13:36:44', '2025-08-29 13:36:44'),
(475, 238, 3, 22000.00, 0.00, 22000.00, 42000.00, 0, NULL, 0, '2025-08-29 13:36:44', '2025-08-29 13:36:44'),
(476, 239, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 05:50:51', '2025-08-30 05:50:51'),
(477, 239, 3, 10000.00, 0.00, 10000.00, 42000.00, 0, NULL, 0, '2025-08-30 05:50:51', '2025-08-30 05:50:51'),
(478, 240, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 05:55:21', '2025-08-30 05:55:21'),
(479, 241, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 05:59:27', '2025-08-30 05:59:27'),
(480, 242, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 06:04:06', '2025-08-30 06:04:06'),
(481, 243, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:04:40', '2025-08-30 06:04:40'),
(482, 243, 3, 10000.00, 0.00, 10000.00, 42000.00, 0, NULL, 0, '2025-08-30 06:04:40', '2025-08-30 06:04:40'),
(483, 244, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:07:24', '2025-08-30 06:07:24'),
(484, 245, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:09:48', '2025-08-30 06:09:48'),
(485, 245, 3, 69000.00, 0.00, 69000.00, 72000.00, 0, NULL, 0, '2025-08-30 06:09:48', '2025-08-30 06:09:48'),
(486, 246, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 06:10:15', '2025-08-30 06:10:15'),
(487, 246, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-08-30 06:10:15', '2025-08-30 06:10:15'),
(488, 247, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:13:00', '2025-08-30 06:13:00'),
(489, 248, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:13:54', '2025-08-30 06:13:54'),
(490, 249, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:16:08', '2025-08-30 06:16:08'),
(491, 250, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:18:25', '2025-08-30 06:18:25'),
(492, 251, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-30 06:20:41', '2025-08-30 06:20:41'),
(493, 251, 3, 23000.00, 0.00, 23000.00, 70000.00, 0, NULL, 0, '2025-08-30 06:20:41', '2025-08-30 06:20:41'),
(494, 252, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 06:22:39', '2025-08-30 06:22:39'),
(495, 252, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-08-30 06:22:39', '2025-08-30 06:22:39'),
(496, 252, 4, 30000.00, 0.00, 30000.00, 30000.00, 0, NULL, 1, '2025-08-30 06:22:39', '2025-08-30 06:22:39'),
(497, 252, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-30 06:22:39', '2025-08-30 06:22:39'),
(498, 253, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:23:22', '2025-08-30 06:23:22'),
(499, 254, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 06:26:19', '2025-08-30 06:26:19'),
(500, 254, 3, 10000.00, 0.00, 10000.00, 52000.00, 0, NULL, 0, '2025-08-30 06:26:19', '2025-08-30 06:26:19'),
(501, 255, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:26:44', '2025-08-30 06:26:44'),
(502, 255, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-08-30 06:26:44', '2025-08-30 06:26:44'),
(503, 255, 4, 10000.00, 0.00, 10000.00, 20000.00, 0, NULL, 0, '2025-08-30 06:26:44', '2025-08-30 06:26:44'),
(504, 256, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:29:09', '2025-08-30 06:29:09'),
(505, 257, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 06:30:16', '2025-08-30 06:30:16'),
(506, 257, 3, 77000.00, 0.00, 77000.00, 77000.00, 0, NULL, 1, '2025-08-30 06:30:16', '2025-08-30 06:30:16'),
(507, 258, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:32:01', '2025-08-30 06:32:01'),
(508, 258, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-08-30 06:32:01', '2025-08-30 06:32:01'),
(509, 258, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-30 06:32:01', '2025-08-30 06:32:01'),
(510, 259, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 06:34:32', '2025-08-30 06:34:32'),
(511, 260, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:34:50', '2025-08-30 06:34:50'),
(512, 261, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:38:45', '2025-08-30 06:38:45'),
(513, 262, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:42:03', '2025-08-30 06:42:03'),
(514, 263, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:42:22', '2025-08-30 06:42:22'),
(515, 263, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-30 06:42:22', '2025-08-30 06:42:22'),
(516, 263, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-30 06:42:22', '2025-08-30 06:42:22'),
(517, 263, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-30 06:42:22', '2025-08-30 06:42:22'),
(518, 264, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-30 06:45:10', '2025-08-30 06:45:10'),
(519, 265, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 06:45:24', '2025-08-30 06:45:24'),
(520, 265, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-30 06:45:24', '2025-08-30 06:45:24'),
(521, 265, 4, 5000.00, 0.00, 5000.00, 20000.00, 0, NULL, 0, '2025-08-30 06:45:24', '2025-08-30 06:45:24'),
(522, 266, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:48:32', '2025-08-30 06:48:32'),
(523, 267, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:48:40', '2025-08-30 06:48:40'),
(524, 268, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:50:51', '2025-08-30 06:50:51'),
(525, 269, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:54:04', '2025-08-30 06:54:04'),
(526, 270, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:54:05', '2025-08-30 06:54:05'),
(527, 270, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-08-30 06:54:05', '2025-08-30 06:54:05'),
(528, 270, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-30 06:54:05', '2025-08-30 06:54:05'),
(529, 270, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-30 06:54:05', '2025-08-30 06:54:05'),
(530, 271, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 06:57:38', '2025-08-30 06:57:38'),
(531, 272, 2, 17700.00, 0.00, 17700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 17700 FCFA', 1, '2025-08-30 06:58:56', '2025-09-12 08:18:05'),
(532, 272, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Correction migration - Ancien système (haut→bas): 72000.00 FCFA', 1, '2025-08-30 06:58:56', '2025-09-12 08:18:05'),
(533, 272, 4, 20000.00, 0.00, 20000.00, 16700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-08-30 06:58:56', '2025-09-12 08:18:05'),
(534, 273, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:00:44', '2025-08-30 07:00:44'),
(536, 275, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:03:47', '2025-08-30 07:03:47'),
(537, 276, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:07:00', '2025-08-30 07:07:00'),
(538, 277, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 07:09:47', '2025-08-30 07:09:47'),
(539, 278, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:11:46', '2025-08-30 07:11:46'),
(540, 278, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-08-30 07:11:46', '2025-08-30 07:11:46'),
(541, 278, 4, 12000.00, 0.00, 12000.00, 20000.00, 0, NULL, 0, '2025-08-30 07:11:46', '2025-08-30 07:11:46'),
(542, 279, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:13:26', '2025-08-30 07:13:26'),
(543, 279, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-30 07:13:26', '2025-08-30 07:13:26');
INSERT INTO `payment_details` (`id`, `payment_id`, `payment_tranche_id`, `amount_allocated`, `previous_amount`, `new_total_amount`, `required_amount_at_time`, `was_reduced`, `reduction_context`, `is_fully_paid`, `created_at`, `updated_at`) VALUES
(544, 280, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:15:05', '2025-08-30 07:15:05'),
(545, 281, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 07:17:26', '2025-08-30 07:17:26'),
(546, 281, 3, 76000.00, 0.00, 76000.00, 82000.00, 0, NULL, 0, '2025-08-30 07:17:26', '2025-08-30 07:17:26'),
(547, 282, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 07:19:43', '2025-08-30 07:19:43'),
(548, 283, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:21:24', '2025-08-30 07:21:24'),
(549, 283, 3, 69000.00, 0.00, 69000.00, 72000.00, 0, NULL, 0, '2025-08-30 07:21:24', '2025-08-30 07:21:24'),
(550, 284, 2, 8400.00, 0.00, 8400.00, 21000.00, 0, 'Correction migration - Ancien système (haut→bas): 8400 FCFA', 1, '2025-08-30 07:23:43', '2025-09-12 08:18:05'),
(551, 284, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-08-30 07:23:43', '2025-09-12 08:18:05'),
(552, 284, 4, 25000.00, 0.00, 25000.00, 22400.00, 1, 'Correction migration - Ancien système (haut→bas): 25000.00 FCFA', 1, '2025-08-30 07:23:43', '2025-09-12 08:18:05'),
(553, 285, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:26:08', '2025-08-30 07:26:08'),
(554, 285, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-08-30 07:26:08', '2025-08-30 07:26:08'),
(555, 285, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-30 07:26:08', '2025-08-30 07:26:08'),
(556, 285, 5, 7000.00, 0.00, 7000.00, 10000.00, 0, NULL, 0, '2025-08-30 07:26:08', '2025-08-30 07:26:08'),
(557, 286, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:27:05', '2025-08-30 07:27:05'),
(558, 287, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:29:44', '2025-08-30 07:29:44'),
(559, 288, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:38:07', '2025-08-30 07:38:07'),
(560, 288, 3, 54000.00, 0.00, 54000.00, 72000.00, 0, NULL, 0, '2025-08-30 07:38:07', '2025-08-30 07:38:07'),
(561, 289, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:42:15', '2025-08-30 07:42:15'),
(562, 290, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-30 07:47:58', '2025-08-30 07:47:58'),
(563, 291, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:52:11', '2025-08-30 07:52:11'),
(564, 291, 3, 63500.00, 0.00, 63500.00, 72000.00, 0, NULL, 0, '2025-08-30 07:52:11', '2025-08-30 07:52:11'),
(565, 292, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-30 07:56:18', '2025-08-30 07:56:18'),
(566, 292, 3, 19000.00, 0.00, 19000.00, 70000.00, 0, NULL, 0, '2025-08-30 07:56:18', '2025-08-30 07:56:18'),
(567, 293, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 07:59:08', '2025-08-30 07:59:08'),
(568, 294, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 08:00:55', '2025-08-30 08:00:55'),
(569, 294, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, NULL, 1, '2025-08-30 08:00:55', '2025-08-30 08:00:55'),
(570, 294, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-30 08:00:55', '2025-08-30 08:00:55'),
(571, 294, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-30 08:00:55', '2025-08-30 08:00:55'),
(572, 295, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 08:20:20', '2025-08-30 08:20:20'),
(573, 296, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 08:26:30', '2025-08-30 08:26:30'),
(574, 297, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 08:36:10', '2025-08-30 08:36:10'),
(575, 297, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, NULL, 1, '2025-08-30 08:36:10', '2025-08-30 08:36:10'),
(576, 298, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 08:39:11', '2025-08-30 08:39:11'),
(577, 298, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-08-30 08:39:11', '2025-08-30 08:39:11'),
(578, 299, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 08:53:44', '2025-08-30 08:53:44'),
(579, 300, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 08:56:44', '2025-08-30 08:56:44'),
(580, 301, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 08:58:36', '2025-08-30 08:58:36'),
(581, 302, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 09:00:44', '2025-08-30 09:00:44'),
(582, 303, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 09:02:51', '2025-08-30 09:02:51'),
(583, 304, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 09:14:49', '2025-08-30 09:14:49'),
(584, 305, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 09:16:44', '2025-08-30 09:16:44'),
(585, 306, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 09:38:31', '2025-08-30 09:38:31'),
(586, 307, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 09:59:33', '2025-08-30 09:59:33'),
(587, 307, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-08-30 09:59:33', '2025-08-30 09:59:33'),
(588, 308, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 10:01:40', '2025-08-30 10:01:40'),
(589, 308, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-08-30 10:01:40', '2025-08-30 10:01:40'),
(590, 309, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 10:20:08', '2025-08-30 10:20:08'),
(591, 310, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 10:22:16', '2025-08-30 10:22:16'),
(592, 311, 2, 20700.00, 0.00, 20700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20700 FCFA', 1, '2025-09-01 05:04:42', '2025-09-12 08:18:05'),
(593, 311, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Correction migration - Ancien système (haut→bas): 42000.00 FCFA', 1, '2025-09-01 05:04:43', '2025-09-12 08:18:05'),
(594, 311, 4, 20000.00, 0.00, 20000.00, 19700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-01 05:04:43', '2025-09-12 08:18:05'),
(595, 312, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 05:38:34', '2025-09-01 05:38:34'),
(596, 313, 2, 19200.00, 0.00, 19200.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 19200 FCFA', 1, '2025-09-01 05:48:39', '2025-09-12 08:18:05'),
(597, 313, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, 'Correction migration - Ancien système (haut→bas): 57000.00 FCFA', 1, '2025-09-01 05:48:39', '2025-09-12 08:18:05'),
(598, 313, 4, 20000.00, 0.00, 20000.00, 18200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-01 05:48:39', '2025-09-12 08:18:05'),
(599, 314, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 06:02:54', '2025-09-01 06:02:54'),
(600, 314, 3, 9000.00, 0.00, 9000.00, 82000.00, 0, NULL, 0, '2025-09-01 06:02:54', '2025-09-01 06:02:54'),
(601, 315, 2, 20700.00, 0.00, 20700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20700 FCFA', 1, '2025-09-01 06:12:10', '2025-09-12 08:18:05'),
(602, 315, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Correction migration - Ancien système (haut→bas): 42000.00 FCFA', 1, '2025-09-01 06:12:10', '2025-09-12 08:18:05'),
(603, 315, 4, 20000.00, 0.00, 20000.00, 19700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-01 06:12:10', '2025-09-12 08:18:05'),
(604, 316, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 06:17:29', '2025-09-01 06:17:29'),
(605, 317, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 07:12:19', '2025-09-01 07:12:19'),
(606, 317, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-01 07:12:19', '2025-09-01 07:12:19'),
(607, 317, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-01 07:12:19', '2025-09-01 07:12:19'),
(608, 317, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-01 07:12:19', '2025-09-01 07:12:19'),
(609, 318, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 07:17:01', '2025-09-01 07:17:01'),
(610, 318, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-01 07:17:01', '2025-09-01 07:17:01'),
(611, 318, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-01 07:17:01', '2025-09-01 07:17:01'),
(612, 318, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-01 07:17:01', '2025-09-01 07:17:01'),
(613, 319, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 07:21:53', '2025-09-01 07:21:53'),
(614, 319, 3, 39000.00, 0.00, 39000.00, 82000.00, 0, NULL, 0, '2025-09-01 07:21:53', '2025-09-01 07:21:53'),
(615, 320, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 07:27:45', '2025-09-01 07:27:45'),
(616, 321, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-01 07:37:39', '2025-09-01 07:37:39'),
(617, 321, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-09-01 07:37:39', '2025-09-01 07:37:39'),
(618, 322, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 07:40:10', '2025-09-01 07:40:10'),
(619, 322, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-09-01 07:40:10', '2025-09-01 07:40:10'),
(620, 322, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-01 07:40:10', '2025-09-01 07:40:10'),
(621, 322, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-01 07:40:10', '2025-09-01 07:40:10'),
(622, 323, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 07:46:35', '2025-09-01 07:46:35'),
(623, 323, 3, 12000.00, 0.00, 12000.00, 82000.00, 0, NULL, 0, '2025-09-01 07:46:35', '2025-09-01 07:46:35'),
(624, 324, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 07:52:48', '2025-09-01 07:52:48'),
(625, 325, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 07:57:41', '2025-09-01 07:57:41'),
(626, 325, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-01 07:57:41', '2025-09-01 07:57:41'),
(627, 326, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 08:02:06', '2025-09-01 08:02:06'),
(628, 327, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 08:07:54', '2025-09-01 08:07:54'),
(629, 328, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-01 08:12:34', '2025-09-01 08:12:34'),
(630, 328, 3, 30000.00, 0.00, 30000.00, 70000.00, 0, NULL, 0, '2025-09-01 08:12:34', '2025-09-01 08:12:34'),
(631, 329, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 08:26:04', '2025-09-01 08:26:04'),
(632, 329, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, NULL, 1, '2025-09-01 08:26:04', '2025-09-01 08:26:04'),
(633, 329, 4, 7000.00, 0.00, 7000.00, 20000.00, 0, NULL, 0, '2025-09-01 08:26:04', '2025-09-01 08:26:04'),
(634, 330, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 08:52:57', '2025-09-01 08:52:57'),
(635, 331, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 08:57:08', '2025-09-01 08:57:08'),
(636, 332, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 09:02:46', '2025-09-01 09:02:46'),
(637, 332, 3, 19000.00, 0.00, 19000.00, 72000.00, 0, NULL, 0, '2025-09-01 09:02:46', '2025-09-01 09:02:46'),
(638, 333, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 09:20:08', '2025-09-01 09:20:08'),
(639, 334, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 09:40:56', '2025-09-01 09:40:56'),
(640, 335, 2, 24700.00, 0.00, 24700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 24700 FCFA', 1, '2025-09-01 10:07:39', '2025-09-12 08:18:05'),
(641, 335, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, 'Correction migration - Ancien système (haut→bas): 92000.00 FCFA', 1, '2025-09-01 10:07:39', '2025-09-12 08:18:05'),
(642, 335, 4, 20000.00, 0.00, 20000.00, 13700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-01 10:07:39', '2025-09-12 08:18:05'),
(643, 336, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 10:12:53', '2025-09-01 10:12:53'),
(644, 337, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 10:24:29', '2025-09-01 10:24:29'),
(645, 337, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-09-01 10:24:29', '2025-09-01 10:24:29'),
(646, 338, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 10:42:13', '2025-09-01 10:42:13'),
(647, 338, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-09-01 10:42:13', '2025-09-01 10:42:13'),
(648, 338, 4, 17000.00, 0.00, 17000.00, 20000.00, 0, NULL, 0, '2025-09-01 10:42:13', '2025-09-01 10:42:13'),
(649, 339, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 10:46:10', '2025-09-01 10:46:10'),
(650, 339, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, NULL, 1, '2025-09-01 10:46:10', '2025-09-01 10:46:10'),
(651, 339, 4, 7000.00, 0.00, 7000.00, 20000.00, 0, NULL, 0, '2025-09-01 10:46:10', '2025-09-01 10:46:10'),
(652, 340, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 10:57:31', '2025-09-01 10:57:31'),
(653, 340, 3, 59000.00, 0.00, 59000.00, 72000.00, 0, NULL, 0, '2025-09-01 10:57:31', '2025-09-01 10:57:31'),
(654, 341, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 12:06:36', '2025-09-01 12:06:36'),
(655, 341, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-09-01 12:06:36', '2025-09-01 12:06:36'),
(656, 341, 4, 12000.00, 0.00, 12000.00, 20000.00, 0, NULL, 0, '2025-09-01 12:06:36', '2025-09-01 12:06:36'),
(657, 342, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 12:06:58', '2025-09-01 12:06:58'),
(658, 343, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 12:11:25', '2025-09-01 12:11:25'),
(659, 344, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 12:18:14', '2025-09-01 12:18:14'),
(660, 345, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 12:32:55', '2025-09-01 12:32:55'),
(661, 345, 3, 15000.00, 0.00, 15000.00, 47000.00, 0, NULL, 0, '2025-09-01 12:32:55', '2025-09-01 12:32:55'),
(662, 346, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 12:46:57', '2025-09-01 12:46:57'),
(663, 346, 3, 39000.00, 0.00, 39000.00, 52000.00, 0, NULL, 0, '2025-09-01 12:46:57', '2025-09-01 12:46:57'),
(664, 347, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 12:47:15', '2025-09-01 12:47:15'),
(665, 348, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 12:48:55', '2025-09-01 12:48:55'),
(666, 349, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-01 12:50:23', '2025-09-01 12:50:23'),
(667, 350, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 12:54:46', '2025-09-01 12:54:46'),
(668, 351, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-01 13:05:16', '2025-09-01 13:05:16'),
(669, 352, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 13:12:37', '2025-09-01 13:12:37'),
(673, 354, 2, 8400.00, 0.00, 8400.00, 21000.00, 0, 'Correction migration - Ancien système (haut→bas): 8400 FCFA', 1, '2025-09-02 04:27:40', '2025-09-12 08:18:05'),
(674, 354, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-09-02 04:27:40', '2025-09-12 08:18:05'),
(675, 354, 4, 25000.00, 0.00, 25000.00, 22400.00, 1, 'Correction migration - Ancien système (haut→bas): 25000.00 FCFA', 1, '2025-09-02 04:27:40', '2025-09-12 08:18:05'),
(676, 355, 2, 20700.00, 0.00, 20700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20700 FCFA', 1, '2025-09-02 04:32:47', '2025-09-12 08:18:05'),
(677, 355, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Correction migration - Ancien système (haut→bas): 42000.00 FCFA', 1, '2025-09-02 04:32:47', '2025-09-12 08:18:05'),
(678, 355, 4, 20000.00, 0.00, 20000.00, 19700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 04:32:47', '2025-09-12 08:18:05'),
(679, 356, 2, 25700.00, 0.00, 25700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 25700 FCFA', 1, '2025-09-02 04:35:55', '2025-09-12 08:18:05'),
(680, 356, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Correction migration - Ancien système (haut→bas): 82000.00 FCFA', 1, '2025-09-02 04:35:55', '2025-09-12 08:18:05'),
(681, 356, 4, 20000.00, 0.00, 20000.00, 14700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 04:35:55', '2025-09-12 08:18:05'),
(682, 357, 2, 25700.00, 0.00, 25700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 25700 FCFA', 1, '2025-09-02 04:39:17', '2025-09-12 08:18:05'),
(683, 357, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Correction migration - Ancien système (haut→bas): 82000.00 FCFA', 1, '2025-09-02 04:39:17', '2025-09-12 08:18:05'),
(684, 357, 4, 20000.00, 0.00, 20000.00, 14700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 04:39:17', '2025-09-12 08:18:05'),
(685, 358, 2, 20700.00, 0.00, 20700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20700 FCFA', 1, '2025-09-02 04:42:52', '2025-09-12 08:18:05'),
(686, 358, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Correction migration - Ancien système (haut→bas): 42000.00 FCFA', 1, '2025-09-02 04:42:52', '2025-09-12 08:18:05'),
(687, 358, 4, 20000.00, 0.00, 20000.00, 19700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 04:42:52', '2025-09-12 08:18:05'),
(688, 359, 2, 18200.00, 0.00, 18200.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 18200 FCFA', 1, '2025-09-02 04:46:05', '2025-09-12 08:18:05'),
(689, 359, 3, 67000.00, 0.00, 67000.00, 67000.00, 0, 'Correction migration - Ancien système (haut→bas): 67000.00 FCFA', 1, '2025-09-02 04:46:05', '2025-09-12 08:18:05'),
(690, 359, 4, 20000.00, 0.00, 20000.00, 17200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 04:46:05', '2025-09-12 08:18:05'),
(691, 360, 2, 26200.00, 0.00, 26200.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 26200 FCFA', 1, '2025-09-02 04:49:55', '2025-09-12 08:18:06'),
(692, 360, 3, 77000.00, 0.00, 77000.00, 77000.00, 0, 'Correction migration - Ancien système (haut→bas): 77000.00 FCFA', 1, '2025-09-02 04:49:55', '2025-09-12 08:18:06'),
(693, 360, 4, 20000.00, 0.00, 20000.00, 15200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 04:49:55', '2025-09-12 08:18:06'),
(694, 361, 2, 17700.00, 0.00, 17700.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 17700 FCFA', 1, '2025-09-02 04:54:18', '2025-09-12 08:18:06'),
(695, 361, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Correction migration - Ancien système (haut→bas): 72000.00 FCFA', 1, '2025-09-02 04:54:18', '2025-09-12 08:18:06'),
(696, 361, 4, 20000.00, 0.00, 20000.00, 16700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 04:54:18', '2025-09-12 08:18:06'),
(697, 362, 2, 24700.00, 0.00, 24700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 24700 FCFA', 1, '2025-09-02 04:59:04', '2025-09-12 08:18:06'),
(698, 362, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, 'Correction migration - Ancien système (haut→bas): 92000.00 FCFA', 1, '2025-09-02 04:59:04', '2025-09-12 08:18:06'),
(699, 362, 4, 20000.00, 0.00, 20000.00, 13700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 04:59:04', '2025-09-12 08:18:06'),
(700, 363, 2, 20200.00, 0.00, 20200.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 20200 FCFA', 1, '2025-09-02 05:03:26', '2025-09-12 08:18:06'),
(701, 363, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, 'Correction migration - Ancien système (haut→bas): 47000.00 FCFA', 1, '2025-09-02 05:03:26', '2025-09-12 08:18:06'),
(702, 363, 4, 20000.00, 0.00, 20000.00, 19200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 05:03:26', '2025-09-12 08:18:06'),
(703, 364, 2, 8400.00, 0.00, 8400.00, 21000.00, 0, 'Correction migration - Ancien système (haut→bas): 8400 FCFA', 1, '2025-09-02 05:06:59', '2025-09-12 08:18:06'),
(704, 364, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Correction migration - Ancien système (haut→bas): 70000.00 FCFA', 1, '2025-09-02 05:06:59', '2025-09-12 08:18:06'),
(705, 364, 4, 25000.00, 0.00, 25000.00, 22400.00, 1, 'Correction migration - Ancien système (haut→bas): 25000.00 FCFA', 1, '2025-09-02 05:06:59', '2025-09-12 08:18:06'),
(706, 365, 2, 28700.00, 0.00, 28700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 28700 FCFA', 1, '2025-09-02 05:15:08', '2025-09-12 08:18:06'),
(707, 365, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Correction migration - Ancien système (haut→bas): 52000.00 FCFA', 1, '2025-09-02 05:15:08', '2025-09-12 08:18:06'),
(708, 365, 4, 20000.00, 0.00, 20000.00, 17700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 05:15:08', '2025-09-12 08:18:06'),
(709, 366, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 05:23:48', '2025-09-02 05:23:48'),
(710, 367, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(711, 367, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(712, 367, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(713, 367, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(714, 368, 2, 28700.00, 0.00, 28700.00, 41000.00, 0, 'Correction migration - Ancien système (haut→bas): 28700 FCFA', 1, '2025-09-02 05:31:03', '2025-09-12 08:18:06'),
(715, 368, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Correction migration - Ancien système (haut→bas): 52000.00 FCFA', 1, '2025-09-02 05:31:03', '2025-09-12 08:18:06'),
(716, 368, 4, 20000.00, 0.00, 20000.00, 17700.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 05:31:03', '2025-09-12 08:18:06'),
(717, 369, 2, 19200.00, 0.00, 19200.00, 31000.00, 0, 'Correction migration - Ancien système (haut→bas): 19200 FCFA', 1, '2025-09-02 05:34:18', '2025-09-12 08:18:06'),
(718, 369, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, 'Correction migration - Ancien système (haut→bas): 57000.00 FCFA', 1, '2025-09-02 05:34:18', '2025-09-12 08:18:06'),
(719, 369, 4, 20000.00, 0.00, 20000.00, 18200.00, 1, 'Correction migration - Ancien système (haut→bas): 20000.00 FCFA', 1, '2025-09-02 05:34:18', '2025-09-12 08:18:06'),
(720, 370, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 05:44:35', '2025-09-02 05:44:35'),
(721, 370, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-09-02 05:44:35', '2025-09-02 05:44:35'),
(722, 370, 4, 2000.00, 0.00, 2000.00, 20000.00, 0, NULL, 0, '2025-09-02 05:44:35', '2025-09-02 05:44:35'),
(723, 371, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 05:47:47', '2025-09-02 05:47:47'),
(724, 371, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-02 05:47:47', '2025-09-02 05:47:47'),
(725, 372, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 05:50:54', '2025-09-02 05:50:54'),
(726, 372, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-02 05:50:54', '2025-09-02 05:50:54'),
(727, 373, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 06:24:39', '2025-09-02 06:24:39'),
(728, 374, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 06:40:58', '2025-09-02 06:40:58'),
(729, 375, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 06:42:51', '2025-09-02 06:42:51'),
(730, 376, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 06:51:10', '2025-09-02 06:51:10'),
(731, 376, 3, 22000.00, 0.00, 22000.00, 42000.00, 0, NULL, 0, '2025-09-02 06:51:10', '2025-09-02 06:51:10'),
(732, 377, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 06:55:31', '2025-09-02 06:55:31'),
(733, 377, 3, 52000.00, 0.00, 52000.00, 72000.00, 0, NULL, 0, '2025-09-02 06:55:31', '2025-09-02 06:55:31'),
(734, 378, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 07:06:23', '2025-09-02 07:06:23'),
(735, 379, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 07:26:21', '2025-09-02 07:26:21'),
(736, 380, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 07:44:33', '2025-09-02 07:44:33'),
(737, 381, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 07:48:29', '2025-09-02 07:48:29'),
(738, 382, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 07:55:47', '2025-09-02 07:55:47'),
(739, 382, 3, 9000.00, 0.00, 9000.00, 82000.00, 0, NULL, 0, '2025-09-02 07:55:47', '2025-09-02 07:55:47'),
(740, 383, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 07:56:11', '2025-09-02 07:56:11'),
(741, 383, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-02 07:56:11', '2025-09-02 07:56:11'),
(742, 383, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-02 07:56:11', '2025-09-02 07:56:11'),
(743, 383, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-02 07:56:11', '2025-09-02 07:56:11'),
(744, 384, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 08:02:23', '2025-09-02 08:02:23'),
(745, 384, 3, 19000.00, 0.00, 19000.00, 72000.00, 0, NULL, 0, '2025-09-02 08:02:23', '2025-09-02 08:02:23'),
(746, 385, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-02 08:07:52', '2025-09-02 08:07:52'),
(747, 385, 3, 49000.00, 0.00, 49000.00, 70000.00, 0, NULL, 0, '2025-09-02 08:07:52', '2025-09-02 08:07:52'),
(748, 386, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 08:20:55', '2025-09-02 08:20:55'),
(749, 386, 3, 19000.00, 0.00, 19000.00, 72000.00, 0, NULL, 0, '2025-09-02 08:20:55', '2025-09-02 08:20:55'),
(750, 387, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 08:40:17', '2025-09-02 08:40:17'),
(751, 387, 3, 25000.00, 0.00, 25000.00, 72000.00, 0, NULL, 0, '2025-09-02 08:40:17', '2025-09-02 08:40:17'),
(752, 388, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 08:48:30', '2025-09-02 08:48:30'),
(753, 389, 3, 50000.00, 0.00, 50000.00, 72000.00, 0, NULL, 0, '2025-09-02 08:57:13', '2025-09-02 08:57:13'),
(754, 390, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 09:06:45', '2025-09-02 09:06:45'),
(755, 390, 3, 60000.00, 0.00, 60000.00, 70000.00, 0, NULL, 0, '2025-09-02 09:06:45', '2025-09-02 09:06:45'),
(756, 391, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 09:15:32', '2025-09-02 09:15:32'),
(757, 391, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, NULL, 1, '2025-09-02 09:15:32', '2025-09-02 09:15:32'),
(758, 392, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 09:17:41', '2025-09-02 09:17:41'),
(759, 393, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 09:35:12', '2025-09-02 09:35:12'),
(760, 394, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 09:36:21', '2025-09-02 09:36:21'),
(761, 395, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 09:37:16', '2025-09-02 09:37:16'),
(762, 396, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 09:39:15', '2025-09-02 09:39:15'),
(763, 397, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 09:39:54', '2025-09-02 09:39:54'),
(764, 398, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 09:45:26', '2025-09-02 09:45:26'),
(765, 398, 3, 19000.00, 0.00, 19000.00, 72000.00, 0, NULL, 0, '2025-09-02 09:45:26', '2025-09-02 09:45:26'),
(766, 399, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 09:48:48', '2025-09-02 09:48:48'),
(767, 399, 3, 19000.00, 0.00, 19000.00, 72000.00, 0, NULL, 0, '2025-09-02 09:48:48', '2025-09-02 09:48:48'),
(768, 400, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 09:59:36', '2025-09-02 09:59:36'),
(769, 400, 3, 19000.00, 0.00, 19000.00, 82000.00, 0, NULL, 0, '2025-09-02 09:59:36', '2025-09-02 09:59:36'),
(770, 401, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 10:01:03', '2025-09-02 10:01:03'),
(771, 402, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-02 10:02:12', '2025-09-02 10:02:12'),
(772, 402, 3, 19000.00, 0.00, 19000.00, 70000.00, 0, NULL, 0, '2025-09-02 10:02:12', '2025-09-02 10:02:12'),
(773, 403, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 10:04:34', '2025-09-02 10:04:34'),
(774, 404, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 10:06:50', '2025-09-02 10:06:50'),
(775, 405, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 10:09:50', '2025-09-02 10:09:50'),
(776, 406, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 10:11:42', '2025-09-02 10:11:42'),
(777, 406, 3, 29000.00, 0.00, 29000.00, 82000.00, 0, NULL, 0, '2025-09-02 10:11:42', '2025-09-02 10:11:42'),
(778, 407, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 10:14:23', '2025-09-02 10:14:23'),
(779, 407, 3, 30000.00, 0.00, 30000.00, 52000.00, 0, NULL, 0, '2025-09-02 10:14:23', '2025-09-02 10:14:23'),
(780, 408, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 10:21:29', '2025-09-02 10:21:29'),
(781, 409, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 11:35:15', '2025-09-02 11:35:15'),
(782, 409, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-09-02 11:35:15', '2025-09-02 11:35:15'),
(783, 409, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-02 11:35:15', '2025-09-02 11:35:15'),
(784, 409, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-02 11:35:15', '2025-09-02 11:35:15'),
(785, 410, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 11:37:40', '2025-09-02 11:37:40'),
(786, 411, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 11:41:13', '2025-09-02 11:41:13'),
(787, 412, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 11:43:17', '2025-09-02 11:43:17'),
(788, 413, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 11:45:16', '2025-09-02 11:45:16'),
(789, 414, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 11:57:46', '2025-09-02 11:57:46'),
(790, 415, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 12:02:14', '2025-09-02 12:02:14'),
(791, 416, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 12:06:24', '2025-09-02 12:06:24'),
(792, 417, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-02 12:11:59', '2025-09-02 12:11:59'),
(793, 417, 3, 49000.00, 0.00, 49000.00, 70000.00, 0, NULL, 0, '2025-09-02 12:11:59', '2025-09-02 12:11:59'),
(794, 418, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 13:15:01', '2025-09-02 13:15:01'),
(795, 418, 3, 11000.00, 0.00, 11000.00, 67000.00, 0, NULL, 0, '2025-09-02 13:15:01', '2025-09-02 13:15:01'),
(796, 419, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 13:18:11', '2025-09-02 13:18:11'),
(797, 419, 3, 12000.00, 0.00, 12000.00, 67000.00, 0, NULL, 0, '2025-09-02 13:18:11', '2025-09-02 13:18:11'),
(798, 420, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-02 13:27:10', '2025-09-02 13:27:10'),
(799, 421, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-03 05:58:45', '2025-09-03 05:58:45'),
(800, 421, 3, 24000.00, 0.00, 24000.00, 70000.00, 0, NULL, 0, '2025-09-03 05:58:45', '2025-09-03 05:58:45'),
(801, 422, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 06:08:41', '2025-09-03 06:08:41'),
(802, 422, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-09-03 06:08:41', '2025-09-03 06:08:41'),
(803, 422, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-03 06:08:41', '2025-09-03 06:08:41'),
(804, 422, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-03 06:08:41', '2025-09-03 06:08:41'),
(805, 423, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-03 06:10:45', '2025-09-03 06:10:45'),
(806, 424, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 06:17:25', '2025-09-03 06:17:25'),
(807, 424, 3, 14000.00, 0.00, 14000.00, 67000.00, 0, NULL, 0, '2025-09-03 06:17:25', '2025-09-03 06:17:25'),
(808, 425, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-03 06:31:59', '2025-09-03 06:31:59'),
(809, 425, 3, 7000.00, 0.00, 7000.00, 77000.00, 0, NULL, 0, '2025-09-03 06:31:59', '2025-09-03 06:31:59'),
(810, 426, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-03 07:13:35', '2025-09-03 07:13:35'),
(811, 427, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 08:06:38', '2025-09-03 08:06:38'),
(812, 427, 3, 39000.00, 0.00, 39000.00, 42000.00, 0, NULL, 0, '2025-09-03 08:06:38', '2025-09-03 08:06:38'),
(813, 428, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 08:11:43', '2025-09-03 08:11:43'),
(814, 428, 3, 14000.00, 0.00, 14000.00, 42000.00, 0, NULL, 0, '2025-09-03 08:11:43', '2025-09-03 08:11:43'),
(815, 429, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 08:15:38', '2025-09-03 08:15:38'),
(816, 429, 3, 23000.00, 0.00, 23000.00, 42000.00, 0, NULL, 0, '2025-09-03 08:15:38', '2025-09-03 08:15:38'),
(817, 430, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 08:28:32', '2025-09-03 08:28:32'),
(818, 431, 3, 2000.00, 70000.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-03 08:35:36', '2025-09-03 08:35:36'),
(819, 431, 4, 18000.00, 0.00, 18000.00, 20000.00, 0, NULL, 0, '2025-09-03 08:35:36', '2025-09-03 08:35:36'),
(820, 432, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 08:39:22', '2025-09-03 08:39:22'),
(821, 432, 3, 24000.00, 0.00, 24000.00, 42000.00, 0, NULL, 0, '2025-09-03 08:39:22', '2025-09-03 08:39:22'),
(822, 433, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 08:41:22', '2025-09-03 08:41:22'),
(823, 433, 3, 24000.00, 0.00, 24000.00, 42000.00, 0, NULL, 0, '2025-09-03 08:41:22', '2025-09-03 08:41:22'),
(824, 434, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 08:48:10', '2025-09-03 08:48:10'),
(825, 434, 3, 10000.00, 0.00, 10000.00, 67000.00, 0, NULL, 0, '2025-09-03 08:48:10', '2025-09-03 08:48:10'),
(826, 435, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 08:54:59', '2025-09-03 08:54:59'),
(827, 435, 3, 25000.00, 0.00, 25000.00, 42000.00, 0, NULL, 0, '2025-09-03 08:54:59', '2025-09-03 08:54:59'),
(828, 436, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-03 09:04:18', '2025-09-03 09:04:18'),
(829, 437, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-03 09:06:16', '2025-09-03 09:06:16'),
(830, 438, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 09:09:14', '2025-09-03 09:09:14'),
(831, 438, 3, 24000.00, 0.00, 24000.00, 57000.00, 0, NULL, 0, '2025-09-03 09:09:14', '2025-09-03 09:09:14'),
(832, 439, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 09:22:42', '2025-09-03 09:22:42'),
(833, 439, 3, 10000.00, 0.00, 10000.00, 72000.00, 0, NULL, 0, '2025-09-03 09:22:42', '2025-09-03 09:22:42'),
(834, 440, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 09:23:39', '2025-09-03 09:23:39'),
(835, 441, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 09:28:12', '2025-09-03 09:28:12'),
(836, 442, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 09:30:51', '2025-09-03 09:30:51'),
(837, 443, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 09:35:51', '2025-09-03 09:35:51'),
(838, 443, 3, 19000.00, 0.00, 19000.00, 22000.00, 0, NULL, 0, '2025-09-03 09:35:51', '2025-09-03 09:35:51'),
(839, 444, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 09:50:00', '2025-09-03 09:50:00'),
(840, 444, 3, 5500.00, 0.00, 5500.00, 42000.00, 0, NULL, 0, '2025-09-03 09:50:00', '2025-09-03 09:50:00'),
(841, 445, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 09:55:14', '2025-09-03 09:55:14'),
(842, 445, 3, 15500.00, 0.00, 15500.00, 72000.00, 0, NULL, 0, '2025-09-03 09:55:14', '2025-09-03 09:55:14'),
(843, 446, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-03 10:01:10', '2025-09-03 10:01:10'),
(844, 446, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, NULL, 1, '2025-09-03 10:01:10', '2025-09-03 10:01:10'),
(845, 446, 4, 2000.00, 0.00, 2000.00, 20000.00, 0, NULL, 0, '2025-09-03 10:01:10', '2025-09-03 10:01:10'),
(846, 447, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 10:06:19', '2025-09-03 10:06:19'),
(847, 448, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 10:11:37', '2025-09-03 10:11:37'),
(848, 449, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-03 10:18:09', '2025-09-03 10:18:09'),
(849, 449, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-09-03 10:18:09', '2025-09-03 10:18:09'),
(850, 450, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 10:32:25', '2025-09-03 10:32:25'),
(851, 451, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-03 10:35:23', '2025-09-03 10:35:23'),
(852, 451, 3, 29000.00, 0.00, 29000.00, 70000.00, 0, NULL, 0, '2025-09-03 10:35:23', '2025-09-03 10:35:23'),
(853, 452, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 10:38:11', '2025-09-03 10:38:11'),
(854, 452, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-03 10:38:11', '2025-09-03 10:38:11'),
(855, 453, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 10:49:54', '2025-09-03 10:49:54'),
(856, 453, 3, 67000.00, 0.00, 67000.00, 67000.00, 0, NULL, 1, '2025-09-03 10:49:54', '2025-09-03 10:49:54'),
(857, 453, 4, 2000.00, 0.00, 2000.00, 20000.00, 0, NULL, 0, '2025-09-03 10:49:54', '2025-09-03 10:49:54'),
(858, 454, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 11:00:38', '2025-09-03 11:00:38'),
(859, 454, 3, 9000.00, 0.00, 9000.00, 42000.00, 0, NULL, 0, '2025-09-03 11:00:38', '2025-09-03 11:00:38'),
(860, 455, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 11:15:45', '2025-09-03 11:15:45'),
(861, 456, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 11:28:28', '2025-09-03 11:28:28'),
(862, 456, 3, 40000.00, 0.00, 40000.00, 47000.00, 0, NULL, 0, '2025-09-03 11:28:28', '2025-09-03 11:28:28'),
(863, 457, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 11:39:15', '2025-09-03 11:39:15'),
(864, 458, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-03 11:45:36', '2025-09-03 11:45:36'),
(865, 458, 3, 29000.00, 0.00, 29000.00, 70000.00, 0, NULL, 0, '2025-09-03 11:45:36', '2025-09-03 11:45:36'),
(866, 459, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-03 11:45:46', '2025-09-03 11:45:46'),
(867, 460, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-03 11:51:08', '2025-09-03 11:51:08'),
(868, 461, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 11:52:04', '2025-09-03 11:52:04'),
(869, 462, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 11:57:24', '2025-09-03 11:57:24'),
(870, 462, 3, 9000.00, 0.00, 9000.00, 72000.00, 0, NULL, 0, '2025-09-03 11:57:24', '2025-09-03 11:57:24'),
(871, 463, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 11:58:39', '2025-09-03 11:58:39'),
(872, 464, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 12:05:34', '2025-09-03 12:05:34'),
(873, 465, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 12:11:11', '2025-09-03 12:11:11'),
(874, 465, 3, 9000.00, 0.00, 9000.00, 57000.00, 0, NULL, 0, '2025-09-03 12:11:11', '2025-09-03 12:11:11'),
(875, 466, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-03 12:19:32', '2025-09-03 12:19:32'),
(876, 467, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-03 12:21:42', '2025-09-03 12:21:42'),
(877, 468, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 12:26:09', '2025-09-03 12:26:09'),
(878, 468, 3, 19000.00, 0.00, 19000.00, 67000.00, 0, NULL, 0, '2025-09-03 12:26:09', '2025-09-03 12:26:09'),
(879, 469, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 12:59:53', '2025-09-03 12:59:53'),
(880, 469, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-03 12:59:53', '2025-09-03 12:59:53'),
(881, 469, 4, 17000.00, 0.00, 17000.00, 20000.00, 0, NULL, 0, '2025-09-03 12:59:53', '2025-09-03 12:59:53'),
(882, 470, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-03 13:08:50', '2025-09-03 13:08:50'),
(883, 470, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, NULL, 1, '2025-09-03 13:08:50', '2025-09-03 13:08:50'),
(884, 471, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-03 13:16:43', '2025-09-03 13:16:43'),
(885, 472, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 05:45:32', '2025-09-05 05:45:32'),
(886, 473, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 05:48:03', '2025-09-05 05:48:03'),
(887, 474, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 05:57:30', '2025-09-05 05:57:30'),
(888, 474, 3, 15500.00, 0.00, 15500.00, 72000.00, 0, NULL, 0, '2025-09-05 05:57:30', '2025-09-05 05:57:30'),
(889, 475, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-05 06:09:23', '2025-09-05 06:09:23'),
(890, 476, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 06:15:41', '2025-09-05 06:15:41'),
(891, 477, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 06:20:35', '2025-09-05 06:20:35'),
(892, 478, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 06:34:49', '2025-09-05 06:34:49'),
(893, 478, 3, 77000.00, 0.00, 77000.00, 77000.00, 0, NULL, 1, '2025-09-05 06:34:49', '2025-09-05 06:34:49'),
(894, 479, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 06:45:15', '2025-09-05 06:45:15'),
(895, 480, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 06:46:10', '2025-09-05 06:46:10'),
(896, 481, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 06:47:28', '2025-09-05 06:47:28'),
(897, 482, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 07:03:33', '2025-09-05 07:03:33'),
(898, 483, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 07:29:28', '2025-09-05 07:29:28'),
(899, 483, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-05 07:29:28', '2025-09-05 07:29:28'),
(900, 483, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-05 07:29:28', '2025-09-05 07:29:28'),
(901, 483, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-05 07:29:28', '2025-09-05 07:29:28'),
(902, 484, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 07:48:48', '2025-09-05 07:48:48'),
(903, 485, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 07:50:59', '2025-09-05 07:50:59'),
(904, 486, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 07:55:00', '2025-09-05 07:55:00'),
(905, 487, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 07:58:57', '2025-09-05 07:58:57'),
(906, 488, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 08:15:07', '2025-09-05 08:15:07'),
(907, 488, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, NULL, 1, '2025-09-05 08:15:07', '2025-09-05 08:15:07'),
(908, 488, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-05 08:15:07', '2025-09-05 08:15:07'),
(909, 488, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-05 08:15:07', '2025-09-05 08:15:07'),
(910, 489, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 08:23:02', '2025-09-05 08:23:02'),
(911, 490, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 08:27:01', '2025-09-05 08:27:01'),
(912, 490, 3, 14000.00, 0.00, 14000.00, 57000.00, 0, NULL, 0, '2025-09-05 08:27:01', '2025-09-05 08:27:01'),
(913, 491, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 08:32:28', '2025-09-05 08:32:28'),
(914, 492, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 08:34:22', '2025-09-05 08:34:22'),
(915, 493, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 08:41:06', '2025-09-05 08:41:06'),
(916, 494, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 08:52:01', '2025-09-05 08:52:01'),
(917, 495, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 08:53:47', '2025-09-05 08:53:47'),
(918, 496, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 08:57:21', '2025-09-05 08:57:21'),
(919, 497, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 08:58:32', '2025-09-05 08:58:32'),
(920, 498, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 09:02:26', '2025-09-05 09:02:26'),
(921, 499, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 09:10:12', '2025-09-05 09:10:12'),
(922, 499, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-09-05 09:10:12', '2025-09-05 09:10:12'),
(923, 499, 4, 17000.00, 0.00, 17000.00, 20000.00, 0, NULL, 0, '2025-09-05 09:10:12', '2025-09-05 09:10:12'),
(924, 500, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 09:12:50', '2025-09-05 09:12:50'),
(925, 501, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 09:13:54', '2025-09-05 09:13:54'),
(926, 502, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 09:22:46', '2025-09-05 09:22:46'),
(927, 503, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 09:24:44', '2025-09-05 09:24:44'),
(928, 504, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-05 09:29:40', '2025-09-05 09:29:40'),
(929, 505, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-05 09:31:33', '2025-09-05 09:31:33'),
(930, 506, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-05 09:35:08', '2025-09-05 09:35:08'),
(931, 506, 3, 65000.00, 0.00, 65000.00, 70000.00, 0, NULL, 0, '2025-09-05 09:35:08', '2025-09-05 09:35:08'),
(932, 507, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-05 09:40:29', '2025-09-05 09:40:29'),
(933, 508, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-05 09:45:59', '2025-09-05 09:45:59'),
(934, 509, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 09:48:55', '2025-09-05 09:48:55'),
(935, 509, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-09-05 09:48:55', '2025-09-05 09:48:55'),
(936, 510, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 09:52:25', '2025-09-05 09:52:25'),
(937, 511, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 09:55:16', '2025-09-05 09:55:16'),
(938, 511, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-05 09:55:16', '2025-09-05 09:55:16'),
(939, 512, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 09:56:58', '2025-09-05 09:56:58'),
(940, 512, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-09-05 09:56:58', '2025-09-05 09:56:58'),
(941, 513, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 10:01:10', '2025-09-05 10:01:10'),
(942, 514, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 10:04:22', '2025-09-05 10:04:22'),
(943, 515, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 10:07:06', '2025-09-05 10:07:06'),
(944, 516, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 10:10:27', '2025-09-05 10:10:27'),
(945, 516, 3, 15500.00, 0.00, 15500.00, 42000.00, 0, NULL, 0, '2025-09-05 10:10:27', '2025-09-05 10:10:27'),
(946, 517, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 10:12:18', '2025-09-05 10:12:18'),
(947, 518, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 10:21:01', '2025-09-05 10:21:01'),
(948, 518, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-09-05 10:21:01', '2025-09-05 10:21:01'),
(949, 518, 4, 12000.00, 0.00, 12000.00, 20000.00, 0, NULL, 0, '2025-09-05 10:21:01', '2025-09-05 10:21:01'),
(950, 519, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 10:23:20', '2025-09-05 10:23:20'),
(951, 519, 3, 19000.00, 0.00, 19000.00, 47000.00, 0, NULL, 0, '2025-09-05 10:23:20', '2025-09-05 10:23:20'),
(952, 520, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 10:27:18', '2025-09-05 10:27:18'),
(953, 520, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, NULL, 1, '2025-09-05 10:27:18', '2025-09-05 10:27:18'),
(954, 520, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-05 10:27:18', '2025-09-05 10:27:18'),
(955, 520, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-05 10:27:18', '2025-09-05 10:27:18'),
(956, 521, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 10:29:25', '2025-09-05 10:29:25'),
(957, 522, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-05 10:33:14', '2025-09-05 10:33:14'),
(958, 523, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 10:36:44', '2025-09-05 10:36:44'),
(959, 524, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 10:49:29', '2025-09-05 10:49:29'),
(960, 525, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 10:53:04', '2025-09-05 10:53:04'),
(961, 526, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 10:56:41', '2025-09-05 10:56:41'),
(962, 527, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 11:13:48', '2025-09-05 11:13:48'),
(963, 528, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 11:17:20', '2025-09-05 11:17:20'),
(964, 529, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 11:46:39', '2025-09-05 11:46:39'),
(965, 530, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 11:51:52', '2025-09-05 11:51:52'),
(966, 531, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 11:56:10', '2025-09-05 11:56:10'),
(967, 531, 3, 9000.00, 0.00, 9000.00, 62000.00, 0, NULL, 0, '2025-09-05 11:56:10', '2025-09-05 11:56:10'),
(968, 532, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 11:59:18', '2025-09-05 11:59:18'),
(969, 533, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 12:02:28', '2025-09-05 12:02:28'),
(970, 534, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 12:16:18', '2025-09-05 12:16:18'),
(971, 534, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-09-05 12:16:18', '2025-09-05 12:16:18'),
(972, 534, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-05 12:16:18', '2025-09-05 12:16:18'),
(973, 534, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-05 12:16:18', '2025-09-05 12:16:18'),
(974, 535, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 12:31:37', '2025-09-05 12:31:37'),
(975, 535, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-05 12:31:37', '2025-09-05 12:31:37'),
(976, 536, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 12:34:10', '2025-09-05 12:34:10'),
(977, 537, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 12:38:17', '2025-09-05 12:38:17'),
(978, 537, 3, 9000.00, 0.00, 9000.00, 82000.00, 0, NULL, 0, '2025-09-05 12:38:17', '2025-09-05 12:38:17'),
(979, 538, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-05 12:45:13', '2025-09-05 12:45:13');
INSERT INTO `payment_details` (`id`, `payment_id`, `payment_tranche_id`, `amount_allocated`, `previous_amount`, `new_total_amount`, `required_amount_at_time`, `was_reduced`, `reduction_context`, `is_fully_paid`, `created_at`, `updated_at`) VALUES
(980, 538, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-09-05 12:45:13', '2025-09-05 12:45:13'),
(981, 539, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 12:53:23', '2025-09-05 12:53:23'),
(982, 540, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 13:02:18', '2025-09-05 13:02:18'),
(983, 540, 3, 19000.00, 0.00, 19000.00, 42000.00, 0, NULL, 0, '2025-09-05 13:02:18', '2025-09-05 13:02:18'),
(984, 541, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-05 13:05:16', '2025-09-05 13:05:16'),
(985, 541, 3, 69000.00, 0.00, 69000.00, 72000.00, 0, NULL, 0, '2025-09-05 13:05:16', '2025-09-05 13:05:16'),
(986, 542, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 06:54:38', '2025-09-06 06:54:38'),
(987, 542, 3, 22000.00, 0.00, 22000.00, 42000.00, 0, NULL, 0, '2025-09-06 06:54:38', '2025-09-06 06:54:38'),
(988, 543, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 07:00:37', '2025-09-06 07:00:37'),
(989, 544, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-06 07:12:21', '2025-09-06 07:12:21'),
(990, 545, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-06 07:19:01', '2025-09-06 07:19:01'),
(991, 546, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 07:23:49', '2025-09-06 07:23:49'),
(992, 547, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 07:30:46', '2025-09-06 07:30:46'),
(993, 548, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 07:35:53', '2025-09-06 07:35:53'),
(994, 548, 3, 19000.00, 0.00, 19000.00, 42000.00, 0, NULL, 0, '2025-09-06 07:35:53', '2025-09-06 07:35:53'),
(995, 549, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-06 07:41:27', '2025-09-06 07:41:27'),
(996, 550, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 07:51:15', '2025-09-06 07:51:15'),
(997, 551, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-06 07:56:42', '2025-09-06 07:56:42'),
(998, 552, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-06 08:02:07', '2025-09-06 08:02:07'),
(999, 553, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-06 08:09:15', '2025-09-06 08:09:15'),
(1000, 554, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 08:13:53', '2025-09-06 08:13:53'),
(1001, 554, 3, 15500.00, 0.00, 15500.00, 47000.00, 0, NULL, 0, '2025-09-06 08:13:53', '2025-09-06 08:13:53'),
(1002, 555, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 08:19:57', '2025-09-06 08:19:57'),
(1003, 555, 3, 29000.00, 0.00, 29000.00, 72000.00, 0, NULL, 0, '2025-09-06 08:19:57', '2025-09-06 08:19:57'),
(1004, 556, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 08:27:52', '2025-09-06 08:27:52'),
(1005, 556, 3, 37000.00, 0.00, 37000.00, 67000.00, 0, NULL, 0, '2025-09-06 08:27:52', '2025-09-06 08:27:52'),
(1006, 557, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 08:37:41', '2025-09-06 08:37:41'),
(1007, 558, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-06 09:03:20', '2025-09-06 09:03:20'),
(1008, 558, 3, 19000.00, 0.00, 19000.00, 52000.00, 0, NULL, 0, '2025-09-06 09:03:20', '2025-09-06 09:03:20'),
(1009, 559, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-06 09:03:29', '2025-09-06 09:03:29'),
(1010, 560, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 09:10:30', '2025-09-06 09:10:30'),
(1011, 561, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 09:10:56', '2025-09-06 09:10:56'),
(1012, 562, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 09:34:29', '2025-09-06 09:34:29'),
(1013, 563, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 09:39:02', '2025-09-06 09:39:02'),
(1014, 563, 3, 9000.00, 0.00, 9000.00, 47000.00, 0, NULL, 0, '2025-09-06 09:39:02', '2025-09-06 09:39:02'),
(1015, 564, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 09:43:40', '2025-09-06 09:43:40'),
(1016, 565, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-06 09:47:25', '2025-09-06 09:47:25'),
(1017, 566, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-06 09:54:39', '2025-09-06 09:54:39'),
(1018, 566, 3, 70000.00, 0.00, 70000.00, 82000.00, 0, NULL, 0, '2025-09-06 09:54:39', '2025-09-06 09:54:39'),
(1019, 567, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 10:02:49', '2025-09-06 10:02:49'),
(1020, 567, 3, 19000.00, 0.00, 19000.00, 42000.00, 0, NULL, 0, '2025-09-06 10:02:49', '2025-09-06 10:02:49'),
(1021, 568, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 10:09:41', '2025-09-06 10:09:41'),
(1022, 569, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 11:09:28', '2025-09-06 11:09:28'),
(1023, 570, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-06 11:14:07', '2025-09-06 11:14:07'),
(1024, 570, 3, 62000.00, 0.00, 62000.00, 72000.00, 0, NULL, 0, '2025-09-06 11:14:07', '2025-09-06 11:14:07'),
(1025, 571, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 04:21:35', '2025-09-09 04:21:35'),
(1026, 571, 3, 9000.00, 0.00, 9000.00, 72000.00, 0, NULL, 0, '2025-09-09 04:21:35', '2025-09-09 04:21:35'),
(1027, 572, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 05:42:23', '2025-09-09 05:42:23'),
(1028, 573, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 05:51:35', '2025-09-09 05:51:35'),
(1029, 574, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 05:56:10', '2025-09-09 05:56:10'),
(1030, 575, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 06:08:38', '2025-09-09 06:08:38'),
(1031, 576, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 06:11:44', '2025-09-09 06:11:44'),
(1032, 577, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 06:16:05', '2025-09-09 06:16:05'),
(1033, 578, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 06:17:17', '2025-09-09 06:17:17'),
(1034, 578, 3, 39000.00, 0.00, 39000.00, 42000.00, 0, NULL, 0, '2025-09-09 06:17:17', '2025-09-09 06:17:17'),
(1035, 579, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 06:20:56', '2025-09-09 06:20:56'),
(1036, 579, 3, 39000.00, 0.00, 39000.00, 67000.00, 0, NULL, 0, '2025-09-09 06:20:56', '2025-09-09 06:20:56'),
(1037, 580, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 06:27:42', '2025-09-09 06:27:42'),
(1038, 580, 3, 4000.00, 0.00, 4000.00, 52000.00, 0, NULL, 0, '2025-09-09 06:27:42', '2025-09-09 06:27:42'),
(1039, 581, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 06:32:08', '2025-09-09 06:32:08'),
(1040, 582, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 06:32:44', '2025-09-09 06:32:44'),
(1041, 582, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-09-09 06:32:44', '2025-09-09 06:32:44'),
(1042, 582, 4, 30000.00, 0.00, 30000.00, 30000.00, 0, NULL, 1, '2025-09-09 06:32:44', '2025-09-09 06:32:44'),
(1043, 582, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-09 06:32:44', '2025-09-09 06:32:44'),
(1045, 584, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 06:39:09', '2025-09-09 06:39:09'),
(1046, 585, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 06:43:39', '2025-09-09 06:43:39'),
(1047, 585, 3, 10000.00, 0.00, 10000.00, 72000.00, 0, NULL, 0, '2025-09-09 06:43:39', '2025-09-09 06:43:39'),
(1048, 586, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-09 06:45:32', '2025-09-09 06:45:32'),
(1049, 587, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 06:50:20', '2025-09-09 06:50:20'),
(1050, 588, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-09 06:53:09', '2025-09-09 06:53:09'),
(1051, 589, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 06:54:46', '2025-09-09 06:54:46'),
(1052, 590, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 06:57:39', '2025-09-09 06:57:39'),
(1053, 591, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 07:02:44', '2025-09-09 07:02:44'),
(1054, 592, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 07:06:07', '2025-09-09 07:06:07'),
(1055, 593, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 07:09:56', '2025-09-09 07:09:56'),
(1056, 594, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 07:11:11', '2025-09-09 07:11:11'),
(1057, 594, 3, 14000.00, 0.00, 14000.00, 27000.00, 0, NULL, 0, '2025-09-09 07:11:11', '2025-09-09 07:11:11'),
(1058, 595, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 07:20:53', '2025-09-09 07:20:53'),
(1059, 596, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-09 07:23:32', '2025-09-09 07:23:32'),
(1060, 597, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 07:27:53', '2025-09-09 07:27:53'),
(1061, 598, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 07:35:34', '2025-09-09 07:35:34'),
(1062, 598, 3, 4000.00, 0.00, 4000.00, 67000.00, 0, NULL, 0, '2025-09-09 07:35:34', '2025-09-09 07:35:34'),
(1063, 599, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 07:41:04', '2025-09-09 07:41:04'),
(1064, 599, 3, 1000.00, 0.00, 1000.00, 52000.00, 0, NULL, 0, '2025-09-09 07:41:04', '2025-09-09 07:41:04'),
(1065, 600, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 07:45:49', '2025-09-09 07:45:49'),
(1066, 600, 3, 19000.00, 0.00, 19000.00, 57000.00, 0, NULL, 0, '2025-09-09 07:45:49', '2025-09-09 07:45:49'),
(1067, 601, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 07:48:37', '2025-09-09 07:48:37'),
(1068, 601, 3, 9000.00, 0.00, 9000.00, 57000.00, 0, NULL, 0, '2025-09-09 07:48:37', '2025-09-09 07:48:37'),
(1069, 602, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 07:49:11', '2025-09-09 07:49:11'),
(1070, 603, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 07:53:54', '2025-09-09 07:53:54'),
(1071, 604, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 07:58:43', '2025-09-09 07:58:43'),
(1072, 604, 3, 9000.00, 0.00, 9000.00, 52000.00, 0, NULL, 0, '2025-09-09 07:58:43', '2025-09-09 07:58:43'),
(1073, 605, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-09 08:07:05', '2025-09-09 08:07:05'),
(1074, 606, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:07:30', '2025-09-09 08:07:30'),
(1075, 607, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:10:33', '2025-09-09 08:10:33'),
(1076, 607, 3, 20000.00, 0.00, 20000.00, 42000.00, 0, NULL, 0, '2025-09-09 08:10:33', '2025-09-09 08:10:33'),
(1077, 608, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:13:32', '2025-09-09 08:13:32'),
(1078, 609, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:22:56', '2025-09-09 08:22:56'),
(1079, 610, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:25:10', '2025-09-09 08:25:10'),
(1080, 611, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:27:30', '2025-09-09 08:27:30'),
(1081, 612, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:39:51', '2025-09-09 08:39:51'),
(1082, 613, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:44:17', '2025-09-09 08:44:17'),
(1083, 614, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-09 08:46:42', '2025-09-09 08:46:42'),
(1084, 614, 3, 10000.00, 0.00, 10000.00, 70000.00, 0, NULL, 0, '2025-09-09 08:46:42', '2025-09-09 08:46:42'),
(1085, 615, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 08:56:44', '2025-09-09 08:56:44'),
(1086, 616, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 09:11:31', '2025-09-09 09:11:31'),
(1087, 617, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 09:26:55', '2025-09-09 09:26:55'),
(1088, 618, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 09:35:58', '2025-09-09 09:35:58'),
(1089, 619, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 09:37:38', '2025-09-09 09:37:38'),
(1090, 620, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 09:39:48', '2025-09-09 09:39:48'),
(1091, 621, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 09:44:55', '2025-09-09 09:44:55'),
(1092, 622, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 09:55:32', '2025-09-09 09:55:32'),
(1093, 623, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 09:57:19', '2025-09-09 09:57:19'),
(1096, 625, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 10:04:10', '2025-09-09 10:04:10'),
(1097, 625, 3, 9000.00, 0.00, 9000.00, 57000.00, 0, NULL, 0, '2025-09-09 10:04:10', '2025-09-09 10:04:10'),
(1098, 626, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 10:04:23', '2025-09-09 10:04:23'),
(1099, 627, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 10:07:22', '2025-09-09 10:07:22'),
(1100, 628, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 10:10:32', '2025-09-09 10:10:32'),
(1101, 629, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 10:14:45', '2025-09-09 10:14:45'),
(1102, 630, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 10:16:41', '2025-09-09 10:16:41'),
(1103, 631, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 10:18:49', '2025-09-09 10:18:49'),
(1104, 632, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 10:20:50', '2025-09-09 10:20:50'),
(1105, 633, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 10:26:55', '2025-09-09 10:26:55'),
(1106, 633, 3, 19000.00, 0.00, 19000.00, 67000.00, 0, NULL, 0, '2025-09-09 10:26:55', '2025-09-09 10:26:55'),
(1107, 634, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 10:29:40', '2025-09-09 10:29:40'),
(1108, 634, 3, 29000.00, 0.00, 29000.00, 52000.00, 0, NULL, 0, '2025-09-09 10:29:40', '2025-09-09 10:29:40'),
(1109, 635, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 10:30:04', '2025-09-09 10:30:04'),
(1110, 636, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 10:34:08', '2025-09-09 10:34:08'),
(1111, 637, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 10:43:07', '2025-09-09 10:43:07'),
(1112, 638, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 10:47:12', '2025-09-09 10:47:12'),
(1113, 639, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 10:51:48', '2025-09-09 10:51:48'),
(1114, 640, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 11:01:06', '2025-09-09 11:01:06'),
(1115, 640, 3, 9000.00, 0.00, 9000.00, 52000.00, 0, NULL, 0, '2025-09-09 11:01:06', '2025-09-09 11:01:06'),
(1116, 641, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 11:05:32', '2025-09-09 11:05:32'),
(1117, 641, 3, 4000.00, 0.00, 4000.00, 70000.00, 0, NULL, 0, '2025-09-09 11:05:32', '2025-09-09 11:05:32'),
(1118, 642, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 11:18:03', '2025-09-09 11:18:03'),
(1119, 643, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 11:19:46', '2025-09-09 11:19:46'),
(1120, 644, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-09 11:23:37', '2025-09-09 11:23:37'),
(1121, 645, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 11:25:02', '2025-09-09 11:25:02'),
(1122, 646, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 11:28:32', '2025-09-09 11:28:32'),
(1123, 647, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 11:34:26', '2025-09-09 11:34:26'),
(1124, 648, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 11:37:50', '2025-09-09 11:37:50'),
(1125, 649, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 11:39:10', '2025-09-09 11:39:10'),
(1126, 650, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 11:43:26', '2025-09-09 11:43:26'),
(1127, 651, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 11:50:56', '2025-09-09 11:50:56'),
(1128, 652, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 12:00:58', '2025-09-09 12:00:58'),
(1129, 653, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 12:07:04', '2025-09-09 12:07:04'),
(1130, 654, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 12:14:08', '2025-09-09 12:14:08'),
(1131, 655, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 12:17:08', '2025-09-09 12:17:08'),
(1132, 655, 3, 54000.00, 0.00, 54000.00, 57000.00, 0, NULL, 0, '2025-09-09 12:17:08', '2025-09-09 12:17:08'),
(1133, 656, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-09 12:24:51', '2025-09-09 12:24:51'),
(1134, 656, 3, 10000.00, 0.00, 10000.00, 50000.00, 0, NULL, 0, '2025-09-09 12:24:51', '2025-09-09 12:24:51'),
(1135, 657, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 12:24:54', '2025-09-09 12:24:54'),
(1136, 657, 3, 15000.00, 0.00, 15000.00, 82000.00, 0, NULL, 0, '2025-09-09 12:24:54', '2025-09-09 12:24:54'),
(1137, 658, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 12:36:30', '2025-09-09 12:36:30'),
(1138, 658, 3, 14000.00, 0.00, 14000.00, 52000.00, 0, NULL, 0, '2025-09-09 12:36:30', '2025-09-09 12:36:30'),
(1139, 659, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 12:43:14', '2025-09-09 12:43:14'),
(1140, 659, 3, 24000.00, 0.00, 24000.00, 72000.00, 0, NULL, 0, '2025-09-09 12:43:14', '2025-09-09 12:43:14'),
(1141, 660, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 12:47:31', '2025-09-09 12:47:31'),
(1142, 661, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 12:54:19', '2025-09-09 12:54:19'),
(1143, 662, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 12:59:23', '2025-09-09 12:59:23'),
(1144, 662, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-09 12:59:23', '2025-09-09 12:59:23'),
(1145, 662, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-09 12:59:23', '2025-09-09 12:59:23'),
(1146, 662, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-09 12:59:23', '2025-09-09 12:59:23'),
(1147, 663, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 13:06:14', '2025-09-09 13:06:14'),
(1148, 663, 3, 9000.00, 0.00, 9000.00, 82000.00, 0, NULL, 0, '2025-09-09 13:06:14', '2025-09-09 13:06:14'),
(1149, 664, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 13:11:24', '2025-09-09 13:11:24'),
(1150, 664, 3, 37000.00, 0.00, 37000.00, 67000.00, 0, NULL, 0, '2025-09-09 13:11:24', '2025-09-09 13:11:24'),
(1151, 665, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 13:12:32', '2025-09-09 13:12:32'),
(1152, 666, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-09 13:22:22', '2025-09-09 13:22:22'),
(1153, 667, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 13:32:22', '2025-09-09 13:32:22'),
(1154, 668, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 13:32:45', '2025-09-09 13:32:45'),
(1155, 669, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 13:37:40', '2025-09-09 13:37:40'),
(1156, 669, 3, 29000.00, 0.00, 29000.00, 82000.00, 0, NULL, 0, '2025-09-09 13:37:40', '2025-09-09 13:37:40'),
(1157, 670, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 13:42:38', '2025-09-09 13:42:38'),
(1158, 670, 3, 10000.00, 0.00, 10000.00, 72000.00, 0, NULL, 0, '2025-09-09 13:42:38', '2025-09-09 13:42:38'),
(1159, 671, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 13:48:11', '2025-09-09 13:48:11'),
(1160, 671, 3, 19000.00, 0.00, 19000.00, 47000.00, 0, NULL, 0, '2025-09-09 13:48:11', '2025-09-09 13:48:11'),
(1161, 672, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-09 13:51:40', '2025-09-09 13:51:40'),
(1162, 672, 3, 51500.00, 0.00, 51500.00, 57000.00, 0, NULL, 0, '2025-09-09 13:51:40', '2025-09-09 13:51:40'),
(1163, 673, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-09 13:53:10', '2025-09-09 13:53:10'),
(1164, 673, 3, 20000.00, 0.00, 20000.00, 52000.00, 0, NULL, 0, '2025-09-09 13:53:10', '2025-09-09 13:53:10'),
(1166, 675, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 05:01:04', '2025-09-10 05:01:04'),
(1167, 675, 3, 39000.00, 0.00, 39000.00, 42000.00, 0, NULL, 0, '2025-09-10 05:01:04', '2025-09-10 05:01:04'),
(1168, 676, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 05:10:59', '2025-09-10 05:10:59'),
(1169, 676, 3, 12000.00, 0.00, 12000.00, 82000.00, 0, NULL, 0, '2025-09-10 05:10:59', '2025-09-10 05:10:59'),
(1170, 677, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 05:17:00', '2025-09-10 05:17:00'),
(1171, 677, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-09-10 05:17:00', '2025-09-10 05:17:00'),
(1172, 678, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 05:20:51', '2025-09-10 05:20:51'),
(1173, 679, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 05:23:27', '2025-09-10 05:23:27'),
(1174, 680, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 05:24:06', '2025-09-10 05:24:06'),
(1175, 680, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-09-10 05:24:06', '2025-09-10 05:24:06'),
(1176, 680, 4, 9000.00, 0.00, 9000.00, 25000.00, 0, NULL, 0, '2025-09-10 05:24:06', '2025-09-10 05:24:06'),
(1177, 681, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 05:34:08', '2025-09-10 05:34:08'),
(1178, 681, 3, 39000.00, 0.00, 39000.00, 72000.00, 0, NULL, 0, '2025-09-10 05:34:08', '2025-09-10 05:34:08'),
(1179, 682, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 05:34:39', '2025-09-10 05:34:39'),
(1180, 682, 3, 29000.00, 0.00, 29000.00, 57000.00, 0, NULL, 0, '2025-09-10 05:34:39', '2025-09-10 05:34:39'),
(1181, 683, 2, 31000.00, 0.00, 31000.00, 41000.00, 0, NULL, 0, '2025-09-10 05:38:21', '2025-09-10 05:38:21'),
(1182, 684, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 06:50:50', '2025-09-10 06:50:50'),
(1183, 684, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-09-10 06:50:50', '2025-09-10 06:50:50'),
(1184, 684, 4, 9000.00, 0.00, 9000.00, 25000.00, 0, NULL, 0, '2025-09-10 06:50:50', '2025-09-10 06:50:50'),
(1185, 685, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 06:54:58', '2025-09-10 06:54:58'),
(1186, 686, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 06:57:38', '2025-09-10 06:57:38'),
(1187, 686, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-10 06:57:38', '2025-09-10 06:57:38'),
(1188, 686, 4, 3000.00, 0.00, 3000.00, 20000.00, 0, NULL, 0, '2025-09-10 06:57:38', '2025-09-10 06:57:38'),
(1189, 687, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 06:58:31', '2025-09-10 06:58:31'),
(1190, 688, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:03:53', '2025-09-10 07:03:53'),
(1191, 689, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:05:13', '2025-09-10 07:05:13'),
(1192, 689, 3, 9000.00, 0.00, 9000.00, 52000.00, 0, NULL, 0, '2025-09-10 07:05:13', '2025-09-10 07:05:13'),
(1193, 690, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:08:56', '2025-09-10 07:08:56'),
(1194, 691, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:09:20', '2025-09-10 07:09:20'),
(1195, 692, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:11:24', '2025-09-10 07:11:24'),
(1196, 692, 3, 9000.00, 0.00, 9000.00, 92000.00, 0, NULL, 0, '2025-09-10 07:11:24', '2025-09-10 07:11:24'),
(1197, 693, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:13:57', '2025-09-10 07:13:57'),
(1198, 694, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:21:36', '2025-09-10 07:21:36'),
(1199, 694, 3, 4000.00, 0.00, 4000.00, 82000.00, 0, NULL, 0, '2025-09-10 07:21:36', '2025-09-10 07:21:36'),
(1200, 695, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:23:30', '2025-09-10 07:23:30'),
(1201, 696, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:24:39', '2025-09-10 07:24:39'),
(1202, 696, 3, 29000.00, 0.00, 29000.00, 72000.00, 0, NULL, 0, '2025-09-10 07:24:39', '2025-09-10 07:24:39'),
(1203, 697, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:28:24', '2025-09-10 07:28:24'),
(1204, 698, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 07:29:59', '2025-09-10 07:29:59'),
(1205, 699, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:31:00', '2025-09-10 07:31:00'),
(1206, 700, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:33:31', '2025-09-10 07:33:31'),
(1207, 700, 3, 44000.00, 0.00, 44000.00, 67000.00, 0, NULL, 0, '2025-09-10 07:33:31', '2025-09-10 07:33:31'),
(1208, 701, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:37:54', '2025-09-10 07:37:54'),
(1209, 702, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:40:57', '2025-09-10 07:40:57'),
(1210, 702, 3, 9000.00, 0.00, 9000.00, 67000.00, 0, NULL, 0, '2025-09-10 07:40:57', '2025-09-10 07:40:57'),
(1211, 703, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:42:10', '2025-09-10 07:42:10'),
(1212, 704, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:42:20', '2025-09-10 07:42:20'),
(1213, 705, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 07:49:29', '2025-09-10 07:49:29'),
(1214, 706, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:54:25', '2025-09-10 07:54:25'),
(1215, 707, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 07:55:00', '2025-09-10 07:55:00'),
(1216, 707, 3, 5000.00, 0.00, 5000.00, 82000.00, 0, NULL, 0, '2025-09-10 07:55:00', '2025-09-10 07:55:00'),
(1217, 708, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 08:00:01', '2025-09-10 08:00:01'),
(1218, 708, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-09-10 08:00:01', '2025-09-10 08:00:01'),
(1219, 708, 4, 30000.00, 0.00, 30000.00, 30000.00, 0, NULL, 1, '2025-09-10 08:00:01', '2025-09-10 08:00:01'),
(1220, 708, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-10 08:00:01', '2025-09-10 08:00:01'),
(1221, 709, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 08:02:02', '2025-09-10 08:02:02'),
(1222, 709, 3, 19000.00, 0.00, 19000.00, 67000.00, 0, NULL, 0, '2025-09-10 08:02:02', '2025-09-10 08:02:02'),
(1223, 710, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 08:04:49', '2025-09-10 08:04:49'),
(1224, 710, 3, 15000.00, 0.00, 15000.00, 82000.00, 0, NULL, 0, '2025-09-10 08:04:49', '2025-09-10 08:04:49'),
(1225, 711, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 08:06:31', '2025-09-10 08:06:31'),
(1226, 711, 3, 29000.00, 0.00, 29000.00, 42000.00, 0, NULL, 0, '2025-09-10 08:06:31', '2025-09-10 08:06:31'),
(1227, 712, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 08:10:12', '2025-09-10 08:10:12'),
(1228, 712, 3, 29000.00, 0.00, 29000.00, 82000.00, 0, NULL, 0, '2025-09-10 08:10:12', '2025-09-10 08:10:12'),
(1229, 713, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 08:13:09', '2025-09-10 08:13:09'),
(1230, 714, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 08:15:49', '2025-09-10 08:15:49'),
(1231, 715, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 08:16:36', '2025-09-10 08:16:36'),
(1232, 716, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 08:39:59', '2025-09-10 08:39:59'),
(1233, 717, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 08:43:02', '2025-09-10 08:43:02'),
(1234, 718, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 08:45:15', '2025-09-10 08:45:15'),
(1235, 718, 3, 9000.00, 0.00, 9000.00, 52000.00, 0, NULL, 0, '2025-09-10 08:45:15', '2025-09-10 08:45:15'),
(1236, 719, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 08:53:49', '2025-09-10 08:53:49'),
(1237, 719, 3, 29000.00, 0.00, 29000.00, 57000.00, 0, NULL, 0, '2025-09-10 08:53:49', '2025-09-10 08:53:49'),
(1238, 720, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 08:55:53', '2025-09-10 08:55:53'),
(1239, 720, 3, 7000.00, 0.00, 7000.00, 70000.00, 0, NULL, 0, '2025-09-10 08:55:53', '2025-09-10 08:55:53'),
(1240, 721, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 08:58:09', '2025-09-10 08:58:09'),
(1241, 721, 3, 7000.00, 0.00, 7000.00, 70000.00, 0, NULL, 0, '2025-09-10 08:58:09', '2025-09-10 08:58:09'),
(1242, 722, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 08:58:45', '2025-09-10 08:58:45'),
(1243, 723, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 09:00:11', '2025-09-10 09:00:11'),
(1245, 725, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 09:11:48', '2025-09-10 09:11:48'),
(1246, 726, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 09:15:53', '2025-09-10 09:15:53'),
(1247, 726, 3, 50000.00, 0.00, 50000.00, 72000.00, 0, NULL, 0, '2025-09-10 09:15:53', '2025-09-10 09:15:53'),
(1248, 727, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 09:16:39', '2025-09-10 09:16:39'),
(1249, 728, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 09:19:09', '2025-09-10 09:19:09'),
(1250, 729, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 09:22:55', '2025-09-10 09:22:55'),
(1251, 730, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 09:28:10', '2025-09-10 09:28:10'),
(1252, 731, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 09:30:04', '2025-09-10 09:30:04'),
(1253, 731, 3, 67000.00, 0.00, 67000.00, 67000.00, 0, NULL, 1, '2025-09-10 09:30:04', '2025-09-10 09:30:04'),
(1254, 731, 4, 2000.00, 0.00, 2000.00, 20000.00, 0, NULL, 0, '2025-09-10 09:30:04', '2025-09-10 09:30:04'),
(1255, 732, 3, 55000.00, 0.00, 55000.00, 70000.00, 0, NULL, 0, '2025-09-10 09:32:31', '2025-09-10 09:32:31'),
(1256, 733, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 09:33:54', '2025-09-10 09:33:54'),
(1257, 733, 3, 4000.00, 0.00, 4000.00, 82000.00, 0, NULL, 0, '2025-09-10 09:33:54', '2025-09-10 09:33:54'),
(1258, 734, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 09:41:47', '2025-09-10 09:41:47'),
(1259, 734, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-09-10 09:41:47', '2025-09-10 09:41:47'),
(1260, 735, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 09:43:14', '2025-09-10 09:43:14'),
(1261, 735, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-10 09:43:14', '2025-09-10 09:43:14'),
(1262, 735, 4, 2000.00, 0.00, 2000.00, 20000.00, 0, NULL, 0, '2025-09-10 09:43:14', '2025-09-10 09:43:14'),
(1263, 736, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 09:46:28', '2025-09-10 09:46:28'),
(1264, 737, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 09:54:04', '2025-09-10 09:54:04'),
(1265, 738, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:03:20', '2025-09-10 10:03:20'),
(1266, 738, 3, 9000.00, 0.00, 9000.00, 70000.00, 0, NULL, 0, '2025-09-10 10:03:20', '2025-09-10 10:03:20'),
(1267, 739, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:05:50', '2025-09-10 10:05:50'),
(1268, 739, 3, 59000.00, 0.00, 59000.00, 72000.00, 0, NULL, 0, '2025-09-10 10:05:50', '2025-09-10 10:05:50'),
(1269, 740, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 10:06:23', '2025-09-10 10:06:23'),
(1270, 740, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-09-10 10:06:23', '2025-09-10 10:06:23'),
(1271, 740, 4, 25000.00, 0.00, 25000.00, 25000.00, 0, NULL, 1, '2025-09-10 10:06:23', '2025-09-10 10:06:23'),
(1272, 740, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-10 10:06:23', '2025-09-10 10:06:23'),
(1273, 741, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:08:55', '2025-09-10 10:08:55'),
(1274, 742, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:09:04', '2025-09-10 10:09:04'),
(1275, 743, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:09:56', '2025-09-10 10:09:56'),
(1276, 744, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 10:19:41', '2025-09-10 10:19:41'),
(1277, 745, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 10:23:09', '2025-09-10 10:23:09'),
(1278, 746, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:30:56', '2025-09-10 10:30:56'),
(1279, 747, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:34:09', '2025-09-10 10:34:09'),
(1280, 747, 3, 30000.00, 0.00, 30000.00, 82000.00, 0, NULL, 0, '2025-09-10 10:34:09', '2025-09-10 10:34:09'),
(1281, 748, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 10:39:23', '2025-09-10 10:39:23'),
(1282, 748, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-09-10 10:39:23', '2025-09-10 10:39:23'),
(1283, 748, 4, 2000.00, 0.00, 2000.00, 20000.00, 0, NULL, 0, '2025-09-10 10:39:23', '2025-09-10 10:39:23'),
(1284, 749, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:43:08', '2025-09-10 10:43:08'),
(1285, 749, 3, 41000.00, 0.00, 41000.00, 82000.00, 0, NULL, 0, '2025-09-10 10:43:08', '2025-09-10 10:43:08'),
(1286, 750, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:46:57', '2025-09-10 10:46:57'),
(1287, 750, 3, 9000.00, 0.00, 9000.00, 70000.00, 0, NULL, 0, '2025-09-10 10:46:57', '2025-09-10 10:46:57'),
(1288, 751, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 10:48:10', '2025-09-10 10:48:10'),
(1289, 752, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 10:49:22', '2025-09-10 10:49:22'),
(1290, 752, 3, 19000.00, 0.00, 19000.00, 42000.00, 0, NULL, 0, '2025-09-10 10:49:22', '2025-09-10 10:49:22'),
(1291, 753, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:51:36', '2025-09-10 10:51:36'),
(1292, 753, 3, 29000.00, 0.00, 29000.00, 92000.00, 0, NULL, 0, '2025-09-10 10:51:36', '2025-09-10 10:51:36'),
(1293, 754, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:53:02', '2025-09-10 10:53:02'),
(1294, 755, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 10:53:19', '2025-09-10 10:53:19'),
(1295, 756, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 10:55:38', '2025-09-10 10:55:38'),
(1296, 756, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-10 10:55:38', '2025-09-10 10:55:38'),
(1297, 756, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-10 10:55:38', '2025-09-10 10:55:38'),
(1298, 756, 5, 9000.00, 0.00, 9000.00, 10000.00, 0, NULL, 0, '2025-09-10 10:55:38', '2025-09-10 10:55:38'),
(1299, 757, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 10:57:30', '2025-09-10 10:57:30'),
(1300, 757, 3, 12000.00, 0.00, 12000.00, 82000.00, 0, NULL, 0, '2025-09-10 10:57:30', '2025-09-10 10:57:30'),
(1301, 758, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:00:34', '2025-09-10 11:00:34'),
(1302, 759, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 11:02:53', '2025-09-10 11:02:53'),
(1303, 759, 3, 11000.00, 0.00, 11000.00, 52000.00, 0, NULL, 0, '2025-09-10 11:02:53', '2025-09-10 11:02:53'),
(1304, 760, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:04:09', '2025-09-10 11:04:09'),
(1305, 760, 3, 9000.00, 0.00, 9000.00, 72000.00, 0, NULL, 0, '2025-09-10 11:04:09', '2025-09-10 11:04:09'),
(1306, 761, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 11:08:53', '2025-09-10 11:08:53'),
(1307, 762, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:09:20', '2025-09-10 11:09:20'),
(1308, 762, 3, 9000.00, 0.00, 9000.00, 72000.00, 0, NULL, 0, '2025-09-10 11:09:20', '2025-09-10 11:09:20'),
(1309, 763, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:11:47', '2025-09-10 11:11:47'),
(1310, 764, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 11:13:04', '2025-09-10 11:13:04'),
(1311, 765, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:14:53', '2025-09-10 11:14:53'),
(1312, 766, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:16:29', '2025-09-10 11:16:29'),
(1313, 767, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:21:55', '2025-09-10 11:21:55'),
(1314, 767, 3, 19000.00, 0.00, 19000.00, 82000.00, 0, NULL, 0, '2025-09-10 11:21:55', '2025-09-10 11:21:55'),
(1316, 769, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:26:32', '2025-09-10 11:26:32'),
(1317, 769, 3, 20000.00, 0.00, 20000.00, 72000.00, 0, NULL, 0, '2025-09-10 11:26:32', '2025-09-10 11:26:32'),
(1318, 770, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:28:23', '2025-09-10 11:28:23'),
(1319, 770, 3, 31000.00, 0.00, 31000.00, 72000.00, 0, NULL, 0, '2025-09-10 11:28:23', '2025-09-10 11:28:23'),
(1320, 771, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 11:33:41', '2025-09-10 11:33:41'),
(1321, 772, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 11:39:22', '2025-09-10 11:39:22'),
(1323, 774, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:48:54', '2025-09-10 11:48:54'),
(1324, 774, 3, 19000.00, 0.00, 19000.00, 57000.00, 0, NULL, 0, '2025-09-10 11:48:54', '2025-09-10 11:48:54'),
(1325, 775, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 11:50:28', '2025-09-10 11:50:28'),
(1326, 775, 3, 9000.00, 0.00, 9000.00, 72000.00, 0, NULL, 0, '2025-09-10 11:50:28', '2025-09-10 11:50:28'),
(1327, 776, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 11:53:45', '2025-09-10 11:53:45'),
(1328, 777, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 11:55:20', '2025-09-10 11:55:20'),
(1329, 778, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 11:58:35', '2025-09-10 11:58:35'),
(1330, 779, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 12:03:16', '2025-09-10 12:03:16'),
(1331, 780, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 12:03:45', '2025-09-10 12:03:45'),
(1332, 780, 3, 30000.00, 0.00, 30000.00, 92000.00, 0, NULL, 0, '2025-09-10 12:03:45', '2025-09-10 12:03:45'),
(1333, 781, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 12:06:59', '2025-09-10 12:06:59'),
(1334, 781, 3, 19000.00, 0.00, 19000.00, 42000.00, 0, NULL, 0, '2025-09-10 12:06:59', '2025-09-10 12:06:59'),
(1335, 782, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 12:10:11', '2025-09-10 12:10:11'),
(1336, 783, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 12:15:51', '2025-09-10 12:15:51'),
(1337, 784, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 12:21:17', '2025-09-10 12:21:17'),
(1338, 785, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 12:22:59', '2025-09-10 12:22:59'),
(1339, 786, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 12:25:07', '2025-09-10 12:25:07'),
(1340, 787, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 12:30:12', '2025-09-10 12:30:12'),
(1341, 787, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, NULL, 1, '2025-09-10 12:30:12', '2025-09-10 12:30:12'),
(1342, 787, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-10 12:30:12', '2025-09-10 12:30:12'),
(1343, 787, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-10 12:30:12', '2025-09-10 12:30:12'),
(1344, 788, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 12:30:20', '2025-09-10 12:30:20'),
(1345, 788, 3, 20000.00, 0.00, 20000.00, 70000.00, 0, NULL, 0, '2025-09-10 12:30:20', '2025-09-10 12:30:20'),
(1346, 789, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 12:32:26', '2025-09-10 12:32:26'),
(1347, 789, 3, 29000.00, 0.00, 29000.00, 72000.00, 0, NULL, 0, '2025-09-10 12:32:26', '2025-09-10 12:32:26'),
(1348, 790, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 12:33:53', '2025-09-10 12:33:53'),
(1349, 790, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, NULL, 1, '2025-09-10 12:33:53', '2025-09-10 12:33:53'),
(1350, 790, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-10 12:33:53', '2025-09-10 12:33:53'),
(1351, 790, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-10 12:33:53', '2025-09-10 12:33:53'),
(1352, 791, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 12:35:27', '2025-09-10 12:35:27'),
(1353, 791, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, NULL, 1, '2025-09-10 12:35:27', '2025-09-10 12:35:27'),
(1354, 792, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 12:37:48', '2025-09-10 12:37:48'),
(1355, 793, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 12:39:41', '2025-09-10 12:39:41'),
(1356, 794, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 12:45:24', '2025-09-10 12:45:24'),
(1357, 795, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-10 12:50:18', '2025-09-10 12:50:18'),
(1358, 795, 3, 24000.00, 0.00, 24000.00, 70000.00, 0, NULL, 0, '2025-09-10 12:50:18', '2025-09-10 12:50:18'),
(1359, 796, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 12:57:02', '2025-09-10 12:57:02'),
(1360, 797, 2, 30000.00, 0.00, 30000.00, 31000.00, 0, NULL, 0, '2025-09-10 13:01:55', '2025-09-10 13:01:55'),
(1361, 798, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 13:06:50', '2025-09-10 13:06:50'),
(1362, 799, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 13:16:23', '2025-09-10 13:16:23'),
(1363, 800, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-10 13:20:07', '2025-09-10 13:20:07'),
(1364, 800, 3, 40000.00, 0.00, 40000.00, 92000.00, 0, NULL, 0, '2025-09-10 13:20:07', '2025-09-10 13:20:07'),
(1365, 801, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-10 13:53:24', '2025-09-10 13:53:24'),
(1366, 801, 3, 15000.00, 0.00, 15000.00, 72000.00, 0, NULL, 0, '2025-09-10 13:53:24', '2025-09-10 13:53:24'),
(1367, 802, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-11 08:40:29', '2025-09-11 08:40:29'),
(1368, 802, 3, 44000.00, 0.00, 44000.00, 70000.00, 0, NULL, 0, '2025-09-11 08:40:29', '2025-09-11 08:40:29'),
(1369, 803, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 08:49:23', '2025-09-11 08:49:23'),
(1370, 803, 3, 19000.00, 0.00, 19000.00, 42000.00, 0, NULL, 0, '2025-09-11 08:49:23', '2025-09-11 08:49:23'),
(1371, 804, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 08:54:43', '2025-09-11 08:54:43'),
(1372, 805, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 09:01:41', '2025-09-11 09:01:41'),
(1373, 805, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-11 09:01:41', '2025-09-11 09:01:41'),
(1374, 805, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-11 09:01:41', '2025-09-11 09:01:41'),
(1375, 806, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 09:10:44', '2025-09-11 09:10:44'),
(1376, 807, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 09:18:16', '2025-09-11 09:18:16'),
(1377, 808, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 09:23:15', '2025-09-11 09:23:15'),
(1378, 809, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 09:29:29', '2025-09-11 09:29:29'),
(1379, 809, 3, 4000.00, 0.00, 4000.00, 70000.00, 0, NULL, 0, '2025-09-11 09:29:29', '2025-09-11 09:29:29'),
(1380, 810, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 09:32:38', '2025-09-11 09:32:38'),
(1381, 811, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 09:38:47', '2025-09-11 09:38:47'),
(1382, 812, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 09:41:39', '2025-09-11 09:41:39'),
(1383, 813, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 10:27:17', '2025-09-11 10:27:17'),
(1384, 814, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 10:31:30', '2025-09-11 10:31:30'),
(1385, 815, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 10:39:36', '2025-09-11 10:39:36'),
(1386, 815, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-11 10:39:36', '2025-09-11 10:39:36'),
(1387, 816, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 10:40:04', '2025-09-11 10:40:04'),
(1388, 816, 3, 39000.00, 0.00, 39000.00, 57000.00, 0, NULL, 0, '2025-09-11 10:40:04', '2025-09-11 10:40:04'),
(1389, 817, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 10:56:10', '2025-09-11 10:56:10'),
(1390, 817, 3, 49000.00, 0.00, 49000.00, 70000.00, 0, NULL, 0, '2025-09-11 10:56:10', '2025-09-11 10:56:10'),
(1391, 818, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 11:01:25', '2025-09-11 11:01:25'),
(1392, 819, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 11:06:55', '2025-09-11 11:06:55'),
(1393, 819, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-09-11 11:06:55', '2025-09-11 11:06:55'),
(1394, 820, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 11:12:08', '2025-09-11 11:12:08'),
(1395, 820, 3, 19000.00, 0.00, 19000.00, 82000.00, 0, NULL, 0, '2025-09-11 11:12:08', '2025-09-11 11:12:08'),
(1396, 821, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-11 11:21:16', '2025-09-11 11:21:16'),
(1397, 822, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 11:26:37', '2025-09-11 11:26:37'),
(1398, 823, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 11:28:15', '2025-09-11 11:28:15'),
(1399, 823, 3, 9000.00, 0.00, 9000.00, 62000.00, 0, NULL, 0, '2025-09-11 11:28:15', '2025-09-11 11:28:15'),
(1400, 824, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-11 11:31:45', '2025-09-11 11:31:45'),
(1401, 824, 3, 49000.00, 0.00, 49000.00, 70000.00, 0, NULL, 0, '2025-09-11 11:31:45', '2025-09-11 11:31:45'),
(1402, 825, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 11:34:23', '2025-09-11 11:34:23'),
(1403, 825, 3, 9000.00, 0.00, 9000.00, 67000.00, 0, NULL, 0, '2025-09-11 11:34:23', '2025-09-11 11:34:23'),
(1404, 826, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 11:44:57', '2025-09-11 11:44:57'),
(1405, 827, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 11:50:14', '2025-09-11 11:50:14'),
(1406, 827, 3, 29000.00, 0.00, 29000.00, 57000.00, 0, NULL, 0, '2025-09-11 11:50:14', '2025-09-11 11:50:14'),
(1407, 828, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 11:53:45', '2025-09-11 11:53:45'),
(1408, 829, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 11:56:28', '2025-09-11 11:56:28'),
(1409, 829, 3, 19000.00, 0.00, 19000.00, 47000.00, 0, NULL, 0, '2025-09-11 11:56:28', '2025-09-11 11:56:28'),
(1410, 830, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 11:58:41', '2025-09-11 11:58:41'),
(1411, 831, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 12:02:24', '2025-09-11 12:02:24'),
(1412, 831, 3, 19000.00, 0.00, 19000.00, 42000.00, 0, NULL, 0, '2025-09-11 12:02:24', '2025-09-11 12:02:24'),
(1413, 832, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 12:05:30', '2025-09-11 12:05:30'),
(1414, 832, 3, 9000.00, 0.00, 9000.00, 82000.00, 0, NULL, 0, '2025-09-11 12:05:30', '2025-09-11 12:05:30'),
(1415, 833, 3, 20000.00, 52000.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-11 12:12:03', '2025-09-11 12:12:03'),
(1416, 833, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-11 12:12:03', '2025-09-11 12:12:03'),
(1417, 833, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-11 12:12:03', '2025-09-11 12:12:03'),
(1418, 834, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 12:16:52', '2025-09-11 12:16:52'),
(1419, 835, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 12:22:15', '2025-09-11 12:22:15'),
(1420, 835, 3, 50000.00, 0.00, 50000.00, 82000.00, 0, NULL, 0, '2025-09-11 12:22:15', '2025-09-11 12:22:15'),
(1421, 836, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 12:28:35', '2025-09-11 12:28:35'),
(1422, 837, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 12:32:34', '2025-09-11 12:32:34'),
(1423, 838, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 12:36:53', '2025-09-11 12:36:53'),
(1424, 838, 3, 49000.00, 0.00, 49000.00, 92000.00, 0, NULL, 0, '2025-09-11 12:36:53', '2025-09-11 12:36:53'),
(1425, 839, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 12:45:03', '2025-09-11 12:45:03'),
(1426, 839, 3, 39000.00, 0.00, 39000.00, 57000.00, 0, NULL, 0, '2025-09-11 12:45:03', '2025-09-11 12:45:03'),
(1427, 840, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 12:48:52', '2025-09-11 12:48:52'),
(1428, 841, 2, 31000.00, 0.00, 31000.00, 41000.00, 0, NULL, 0, '2025-09-11 12:56:06', '2025-09-11 12:56:06'),
(1429, 842, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 13:04:36', '2025-09-11 13:04:36'),
(1430, 843, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 13:10:57', '2025-09-11 13:10:57'),
(1431, 844, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 13:16:35', '2025-09-11 13:16:35'),
(1432, 845, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-11 13:40:38', '2025-09-11 13:40:38'),
(1433, 846, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-11 13:47:52', '2025-09-11 13:47:52'),
(1434, 847, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-11 14:13:29', '2025-09-11 14:13:29'),
(1435, 848, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 04:22:27', '2025-09-12 04:22:27'),
(1436, 848, 3, 39000.00, 0.00, 39000.00, 70000.00, 0, NULL, 0, '2025-09-12 04:22:27', '2025-09-12 04:22:27'),
(1437, 849, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 05:15:46', '2025-09-12 05:15:46'),
(1438, 850, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 05:23:21', '2025-09-12 05:23:21'),
(1439, 851, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 05:43:32', '2025-09-12 05:43:32'),
(1440, 852, 3, 31000.00, 0.00, 31000.00, 82000.00, 0, NULL, 0, '2025-09-12 05:44:34', '2025-09-12 05:44:34'),
(1441, 853, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 05:50:37', '2025-09-12 05:50:37'),
(1442, 854, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 05:57:53', '2025-09-12 05:57:53'),
(1443, 855, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 06:07:53', '2025-09-12 06:07:53'),
(1444, 856, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 06:11:18', '2025-09-12 06:11:18'),
(1445, 857, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-12 06:14:05', '2025-09-12 06:14:05'),
(1446, 858, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-12 06:19:03', '2025-09-12 06:19:03'),
(1447, 859, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-12 06:26:40', '2025-09-12 06:26:40');
INSERT INTO `payment_details` (`id`, `payment_id`, `payment_tranche_id`, `amount_allocated`, `previous_amount`, `new_total_amount`, `required_amount_at_time`, `was_reduced`, `reduction_context`, `is_fully_paid`, `created_at`, `updated_at`) VALUES
(1448, 860, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 06:31:04', '2025-09-12 06:31:04'),
(1449, 860, 3, 21000.00, 0.00, 21000.00, 62000.00, 0, NULL, 0, '2025-09-12 06:31:04', '2025-09-12 06:31:04'),
(1450, 861, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 06:38:23', '2025-09-12 06:38:23'),
(1451, 861, 3, 60000.00, 0.00, 60000.00, 82000.00, 0, NULL, 0, '2025-09-12 06:38:23', '2025-09-12 06:38:23'),
(1452, 862, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 06:44:25', '2025-09-12 06:44:25'),
(1453, 863, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 06:50:14', '2025-09-12 06:50:14'),
(1454, 863, 3, 32000.00, 0.00, 32000.00, 42000.00, 0, NULL, 0, '2025-09-12 06:50:14', '2025-09-12 06:50:14'),
(1455, 864, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-09-12 06:51:52', '2025-09-12 06:51:52'),
(1456, 864, 4, 3000.00, 0.00, 3000.00, 20000.00, 0, NULL, 0, '2025-09-12 06:51:52', '2025-09-12 06:51:52'),
(1457, 865, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 06:56:20', '2025-09-12 06:56:20'),
(1458, 865, 3, 7000.00, 0.00, 7000.00, 52000.00, 0, NULL, 0, '2025-09-12 06:56:20', '2025-09-12 06:56:20'),
(1459, 866, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 06:59:13', '2025-09-12 06:59:13'),
(1460, 867, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 07:04:43', '2025-09-12 07:04:43'),
(1461, 868, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 07:07:43', '2025-09-12 07:07:43'),
(1462, 869, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 07:12:21', '2025-09-12 07:12:21'),
(1463, 869, 3, 39000.00, 0.00, 39000.00, 52000.00, 0, NULL, 0, '2025-09-12 07:12:21', '2025-09-12 07:12:21'),
(1464, 870, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 07:20:00', '2025-09-12 07:20:00'),
(1465, 871, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 07:29:42', '2025-09-12 07:29:42'),
(1466, 872, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 07:35:50', '2025-09-12 07:35:50'),
(1467, 873, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 07:47:28', '2025-09-12 07:47:28'),
(1468, 873, 3, 19000.00, 0.00, 19000.00, 57000.00, 0, NULL, 0, '2025-09-12 07:47:28', '2025-09-12 07:47:28'),
(1469, 874, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 07:54:32', '2025-09-12 07:54:32'),
(1470, 874, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, NULL, 1, '2025-09-12 07:54:32', '2025-09-12 07:54:32'),
(1471, 874, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-12 07:54:32', '2025-09-12 07:54:32'),
(1472, 874, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-12 07:54:32', '2025-09-12 07:54:32'),
(1473, 875, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-12 07:59:04', '2025-09-12 07:59:04'),
(1477, 877, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:02:09', '2025-09-12 08:02:09'),
(1478, 878, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 08:02:18', '2025-09-12 08:02:18'),
(1479, 878, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-09-12 08:02:18', '2025-09-12 08:02:18'),
(1480, 878, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-12 08:02:18', '2025-09-12 08:02:18'),
(1481, 878, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-12 08:02:18', '2025-09-12 08:02:18'),
(1482, 879, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:05:20', '2025-09-12 08:05:20'),
(1483, 880, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 08:07:33', '2025-09-12 08:07:33'),
(1486, 882, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:09:35', '2025-09-12 08:09:35'),
(1487, 883, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:11:22', '2025-09-12 08:11:22'),
(1488, 884, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:12:22', '2025-09-12 08:12:22'),
(1489, 884, 3, 6000.00, 0.00, 6000.00, 82000.00, 0, NULL, 0, '2025-09-12 08:12:22', '2025-09-12 08:12:22'),
(1490, 885, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:13:43', '2025-09-12 08:13:43'),
(1491, 886, 3, 25000.00, 0.00, 25000.00, 57000.00, 0, NULL, 0, '2025-09-12 08:15:42', '2025-09-12 08:15:42'),
(1492, 887, 3, 25000.00, 29000.00, 54000.00, 70000.00, 0, NULL, 0, '2025-09-12 08:16:33', '2025-09-12 08:16:33'),
(1494, 107, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1495, 108, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1496, 111, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1497, 112, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1498, 114, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1499, 116, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1500, 121, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1501, 124, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1502, 125, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1503, 126, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1504, 127, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1505, 131, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1506, 134, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1507, 139, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1508, 141, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1509, 142, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1510, 143, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1511, 146, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1512, 152, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1513, 168, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1514, 185, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1515, 194, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1516, 195, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1517, 196, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1518, 197, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1519, 198, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1520, 199, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1521, 200, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1522, 202, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1523, 204, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1524, 272, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1525, 284, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1526, 311, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1527, 313, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1528, 315, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1529, 335, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1530, 354, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1531, 355, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1532, 356, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1533, 357, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1534, 358, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1535, 359, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1536, 360, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1537, 361, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1538, 362, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1539, 363, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1540, 364, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1541, 365, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1542, 368, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1543, 369, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, 'Correction migration - Ancien système (haut→bas): 10000.00 FCFA', 1, '2025-09-12 08:21:07', '2025-09-12 08:21:07'),
(1544, 889, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:29:38', '2025-09-12 08:29:38'),
(1545, 890, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:31:34', '2025-09-12 08:31:34'),
(1546, 891, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-12 08:33:24', '2025-09-12 08:33:24'),
(1547, 891, 3, 40000.00, 0.00, 40000.00, 70000.00, 0, NULL, 0, '2025-09-12 08:33:24', '2025-09-12 08:33:24'),
(1548, 892, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:42:42', '2025-09-12 08:42:42'),
(1549, 893, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:48:25', '2025-09-12 08:48:25'),
(1550, 894, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, NULL, 1, '2025-09-12 08:48:30', '2025-09-12 08:48:30'),
(1551, 895, 3, 44000.00, 13000.00, 57000.00, 57000.00, 0, NULL, 1, '2025-09-12 08:50:25', '2025-09-12 08:50:25'),
(1552, 895, 4, 6000.00, 0.00, 6000.00, 20000.00, 0, NULL, 0, '2025-09-12 08:50:25', '2025-09-12 08:50:25'),
(1553, 896, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 08:53:14', '2025-09-12 08:53:14'),
(1554, 897, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 08:55:15', '2025-09-12 08:55:15'),
(1555, 898, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 08:55:56', '2025-09-12 08:55:56'),
(1556, 898, 3, 20000.00, 0.00, 20000.00, 70000.00, 0, NULL, 0, '2025-09-12 08:55:56', '2025-09-12 08:55:56'),
(1557, 899, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 09:05:52', '2025-09-12 09:05:52'),
(1558, 899, 3, 20000.00, 0.00, 20000.00, 67000.00, 0, NULL, 0, '2025-09-12 09:05:52', '2025-09-12 09:05:52'),
(1559, 900, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 09:26:49', '2025-09-12 09:26:49'),
(1560, 901, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 09:29:43', '2025-09-12 09:29:43'),
(1561, 902, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-12 09:32:49', '2025-09-12 09:32:49'),
(1562, 903, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 10:18:16', '2025-09-12 10:18:16'),
(1563, 904, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 10:22:08', '2025-09-12 10:22:08'),
(1564, 904, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, NULL, 1, '2025-09-12 10:22:08', '2025-09-12 10:22:08'),
(1565, 904, 4, 3500.00, 0.00, 3500.00, 20000.00, 0, NULL, 0, '2025-09-12 10:22:08', '2025-09-12 10:22:08'),
(1566, 905, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 10:29:00', '2025-09-12 10:29:00'),
(1567, 906, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 10:39:46', '2025-09-12 10:39:46'),
(1568, 906, 3, 25500.00, 0.00, 25500.00, 70000.00, 0, NULL, 0, '2025-09-12 10:39:46', '2025-09-12 10:39:46'),
(1569, 907, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 10:52:20', '2025-09-12 10:52:20'),
(1570, 908, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 10:56:07', '2025-09-12 10:56:07'),
(1571, 909, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 11:05:05', '2025-09-12 11:05:05'),
(1572, 910, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 11:10:33', '2025-09-12 11:10:33'),
(1573, 911, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-12 11:20:01', '2025-09-12 11:20:01'),
(1574, 912, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 11:23:46', '2025-09-12 11:23:46'),
(1575, 913, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 11:33:46', '2025-09-12 11:33:46'),
(1576, 914, 3, 40000.00, 0.00, 40000.00, 62000.00, 0, NULL, 0, '2025-09-12 11:36:08', '2025-09-12 11:36:08'),
(1577, 915, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 12:12:27', '2025-09-12 12:12:27'),
(1578, 915, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-09-12 12:12:27', '2025-09-12 12:12:27'),
(1579, 916, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-09-12 12:25:20', '2025-09-12 12:25:20'),
(1580, 916, 3, 25000.00, 0.00, 25000.00, 70000.00, 0, NULL, 0, '2025-09-12 12:25:20', '2025-09-12 12:25:20'),
(1581, 917, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 12:29:41', '2025-09-12 12:29:41'),
(1582, 918, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 12:32:09', '2025-09-12 12:32:09'),
(1583, 919, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 12:39:41', '2025-09-12 12:39:41'),
(1584, 920, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 12:44:31', '2025-09-12 12:44:31'),
(1585, 920, 3, 19000.00, 0.00, 19000.00, 47000.00, 0, NULL, 0, '2025-09-12 12:44:31', '2025-09-12 12:44:31'),
(1586, 921, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 12:53:32', '2025-09-12 12:53:32'),
(1587, 922, 2, 10000.00, 31000.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-12 13:19:16', '2025-09-12 13:19:16'),
(1588, 922, 3, 9000.00, 0.00, 9000.00, 70000.00, 0, NULL, 0, '2025-09-12 13:19:16', '2025-09-12 13:19:16'),
(1589, 923, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-12 13:21:21', '2025-09-12 13:21:21');

-- --------------------------------------------------------

--
-- Structure de la table `payment_tranches`
--

CREATE TABLE `payment_tranches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `default_amount` decimal(10,2) DEFAULT NULL COMMENT 'Montant par défaut pour cette tranche (utilisé pour les tranches globales comme RAME)',
  `use_default_amount` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indique si cette tranche utilise le montant par défaut au lieu des montants par classe',
  `deadline` date DEFAULT NULL COMMENT 'Date limite de paiement pour cette tranche',
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `payment_tranches`
--

INSERT INTO `payment_tranches` (`id`, `name`, `description`, `default_amount`, `use_default_amount`, `deadline`, `order`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Inscription', 'Frais d\'inscription annuelle', NULL, 0, NULL, 1, 1, '2025-08-03 18:00:24', '2025-08-03 18:00:24'),
(3, '1ère Tranche', 'Première tranche de scolarité', NULL, 0, NULL, 2, 1, '2025-08-03 18:00:24', '2025-08-03 18:00:24'),
(4, '2ème Tranche', 'Deuxième tranche de scolarité', NULL, 0, NULL, 3, 1, '2025-08-03 18:00:24', '2025-08-03 18:00:24'),
(5, '3ème Tranche', 'Troisième tranche de scolarité', NULL, 0, NULL, 4, 1, '2025-08-03 18:00:24', '2025-08-03 18:00:24');

-- --------------------------------------------------------

--
-- Structure de la table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mois` int(11) NOT NULL,
  `annee` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `date_paie` date DEFAULT NULL,
  `statut` enum('ouverte','calculee','validee','payee') NOT NULL DEFAULT 'ouverte',
  `notifications_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payroll_whatsapp_notifications`
--

CREATE TABLE `payroll_whatsapp_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_period_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('salary_cut','salary_available','payslip','other') NOT NULL DEFAULT 'other',
  `message` text NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `statut` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payslips`
--

CREATE TABLE `payslips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `period_id` bigint(20) UNSIGNED NOT NULL,
  `salaire_base` decimal(10,0) NOT NULL DEFAULT 0,
  `primes_mensuelles` decimal(10,0) NOT NULL DEFAULT 0,
  `deductions_mensuelles` decimal(10,0) NOT NULL DEFAULT 0,
  `montant_coupures` decimal(10,0) NOT NULL DEFAULT 0,
  `salaire_brut` decimal(10,0) NOT NULL DEFAULT 0,
  `salaire_net` decimal(10,0) NOT NULL DEFAULT 0,
  `mode_paiement` enum('especes','cheque','virement') NOT NULL,
  `statut` enum('brouillon','valide','paye') NOT NULL DEFAULT 'brouillon',
  `retire` tinyint(1) NOT NULL DEFAULT 0,
  `date_retrait` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `salary_cuts`
--

CREATE TABLE `salary_cuts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `period_id` bigint(20) UNSIGNED NOT NULL,
  `montant_coupe` decimal(10,0) NOT NULL,
  `motif` text NOT NULL,
  `date_coupure` date NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `statut` enum('active','annulee') NOT NULL DEFAULT 'active',
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject` varchar(255) NOT NULL,
  `teacher_name` varchar(255) DEFAULT NULL,
  `room` varchar(255) DEFAULT NULL,
  `academic_year` varchar(255) NOT NULL DEFAULT '2024-2025',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `school_classes`
--

CREATE TABLE `school_classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `level_id` bigint(20) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `school_classes`
--

INSERT INTO `school_classes` (`id`, `name`, `level_id`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(13, '6em', 13, NULL, 1, '2025-08-04 02:56:10', '2025-08-04 02:56:10'),
(14, '5ème', 13, NULL, 1, '2025-08-04 02:57:43', '2025-09-03 09:21:40'),
(15, '4ème', 13, NULL, 1, '2025-08-04 02:59:49', '2025-09-03 09:20:57'),
(16, '3ème', 13, NULL, 1, '2025-08-04 03:01:56', '2025-09-03 09:18:37'),
(21, '2nd', 14, NULL, 1, '2025-08-04 05:16:21', '2025-08-04 05:16:21'),
(22, '1ère', 14, NULL, 1, '2025-08-04 07:18:25', '2025-09-03 09:22:43'),
(23, 'Tle', 14, NULL, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08'),
(24, 'SEME 1', 15, NULL, 1, '2025-08-04 09:43:45', '2025-08-06 13:47:55'),
(25, 'SEME 2', 15, NULL, 1, '2025-08-04 09:45:02', '2025-08-06 13:48:11'),
(26, 'SEME 3', 15, NULL, 1, '2025-08-04 09:45:59', '2025-08-06 13:48:26'),
(27, 'SEME 4', 15, NULL, 1, '2025-08-04 09:47:04', '2025-08-06 13:48:42'),
(28, 'ESF 3', 20, NULL, 1, '2025-08-04 11:49:24', '2025-08-06 13:46:47'),
(29, 'FORM ONE', 22, NULL, 1, '2025-08-04 12:47:00', '2025-08-14 22:56:27'),
(30, 'FORM TWO', 22, NULL, 1, '2025-08-04 12:55:09', '2025-08-14 22:56:42'),
(31, 'FORM THREE', 22, NULL, 1, '2025-08-04 12:56:54', '2025-08-14 22:56:54'),
(32, 'FORM FOUR ARTS', 22, NULL, 1, '2025-08-04 13:02:07', '2025-08-14 22:57:33'),
(33, 'FORM FIVE (science )', 22, NULL, 1, '2025-08-04 13:03:19', '2025-08-14 22:57:09'),
(34, 'UPPER SIXTH ( ARTS )', 22, NULL, 1, '2025-08-04 13:05:04', '2025-08-05 13:02:30'),
(35, 'LOWER SIXTH (ARTS )', 22, NULL, 1, '2025-08-04 13:07:27', '2025-08-05 13:04:26'),
(36, 'ESF 1', 20, NULL, 1, '2025-08-04 13:39:42', '2025-08-06 13:47:02'),
(37, 'ESF 2', 20, NULL, 1, '2025-08-04 13:43:07', '2025-08-06 13:47:16'),
(38, 'ESF 4', 20, NULL, 1, '2025-08-04 13:45:11', '2025-08-06 13:47:34'),
(39, 'COME 1', 20, NULL, 1, '2025-08-04 13:48:18', '2025-08-06 13:45:29'),
(40, 'COME 2', 20, NULL, 1, '2025-08-04 13:50:14', '2025-08-06 13:45:44'),
(41, 'COME 3', 20, NULL, 1, '2025-08-05 10:15:38', '2025-08-06 13:46:04'),
(42, 'COME 4', 20, NULL, 1, '2025-08-05 10:16:56', '2025-08-06 13:46:17'),
(43, 'ESCOM 1', 15, NULL, 1, '2025-08-05 12:05:47', '2025-08-06 13:48:58'),
(44, 'ESCOM 2', 15, NULL, 1, '2025-08-05 12:06:41', '2025-08-06 13:49:12'),
(45, 'ESCOM 2', 15, NULL, 1, '2025-08-05 12:07:23', '2025-08-06 13:49:38'),
(46, 'ESCOM 3', 15, NULL, 1, '2025-08-05 12:08:02', '2025-08-06 13:50:02'),
(47, 'ESCOM 4', 15, NULL, 1, '2025-08-05 12:08:43', '2025-08-06 13:50:30'),
(48, '2nd STT', 19, NULL, 1, '2025-08-05 12:25:57', '2025-08-05 12:25:57'),
(50, '2nd F8', 19, NULL, 1, '2025-08-05 12:28:28', '2025-08-05 12:28:28'),
(52, '1er ACC', 19, NULL, 1, '2025-08-05 12:31:27', '2025-08-05 12:31:27'),
(53, '1er CG', 19, NULL, 1, '2025-08-05 12:32:26', '2025-08-05 12:32:26'),
(55, '1er F8', 19, NULL, 1, '2025-08-05 12:34:13', '2025-08-05 12:34:13'),
(57, 'Tle ACC', 19, NULL, 1, '2025-08-05 12:36:28', '2025-08-05 12:36:28'),
(58, 'Tle CG', 19, NULL, 1, '2025-08-05 12:38:36', '2025-08-05 12:38:36'),
(60, 'Tle F8', 19, NULL, 1, '2025-08-05 12:41:00', '2025-08-05 12:41:00'),
(64, 'FORM FOUR science', 22, NULL, 1, '2025-08-05 12:59:48', '2025-08-14 22:58:22'),
(65, 'FORM FIVE (ARTS)', 22, NULL, 1, '2025-08-05 13:01:06', '2025-08-14 22:58:03'),
(66, 'LOWER SIXTH (science )', 22, NULL, 1, '2025-08-05 13:03:23', '2025-08-05 13:03:23'),
(67, 'UPPER SIXTH (science)', 22, NULL, 1, '2025-08-05 13:05:15', '2025-08-05 13:05:15'),
(68, '2nd IH', 21, NULL, 1, '2025-08-06 06:55:28', '2025-08-06 06:55:28'),
(69, '2nd ESF', 21, NULL, 1, '2025-08-06 06:57:38', '2025-08-06 06:57:38'),
(70, '1er IH', 21, NULL, 1, '2025-08-06 06:58:37', '2025-08-06 06:58:37'),
(71, '1er ESF', 21, NULL, 1, '2025-08-06 06:59:28', '2025-08-06 06:59:28'),
(72, 'Tle IH', 21, NULL, 1, '2025-08-06 07:01:45', '2025-08-06 07:01:45'),
(73, 'Tle ESF', 21, NULL, 1, '2025-08-06 07:02:44', '2025-08-06 07:02:44'),
(74, 'Tle ACA', 19, NULL, 1, '2025-08-06 07:03:45', '2025-08-06 07:03:45'),
(75, '1ere ACA', 19, NULL, 1, '2025-08-06 07:06:25', '2025-08-06 07:06:25');

-- --------------------------------------------------------

--
-- Structure de la table `school_settings`
--

CREATE TABLE `school_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_name` varchar(255) NOT NULL DEFAULT 'COLLÈGE POLYVALENT BILINGUE DE DOUALA',
  `school_motto` varchar(255) DEFAULT NULL,
  `school_address` text DEFAULT NULL,
  `school_phone` varchar(255) DEFAULT NULL,
  `school_email` varchar(255) DEFAULT NULL,
  `school_website` varchar(255) DEFAULT NULL,
  `school_logo` varchar(255) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'FCFA',
  `bank_name` varchar(255) NOT NULL DEFAULT 'C4ED',
  `country` varchar(255) NOT NULL DEFAULT 'Cameroun',
  `city` varchar(255) NOT NULL DEFAULT 'Douala',
  `footer_text` text DEFAULT NULL,
  `scholarship_deadline` date DEFAULT NULL COMMENT 'Date limite pour bénéficier des bourses',
  `reduction_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Pourcentage de réduction pour anciens étudiants',
  `primary_color` varchar(7) NOT NULL DEFAULT '#007bff',
  `principal_name` varchar(255) DEFAULT NULL COMMENT 'Nom du principal/directeur de l''établissement',
  `whatsapp_notification_number` varchar(255) DEFAULT NULL,
  `whatsapp_notifications_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_api_url` varchar(255) DEFAULT NULL,
  `whatsapp_instance_id` varchar(255) DEFAULT NULL,
  `whatsapp_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `school_settings`
--

INSERT INTO `school_settings` (`id`, `school_name`, `school_motto`, `school_address`, `school_phone`, `school_email`, `school_website`, `school_logo`, `currency`, `bank_name`, `country`, `city`, `footer_text`, `scholarship_deadline`, `reduction_percentage`, `primary_color`, `principal_name`, `whatsapp_notification_number`, `whatsapp_notifications_enabled`, `whatsapp_api_url`, `whatsapp_instance_id`, `whatsapp_token`, `created_at`, `updated_at`) VALUES
(1, 'COLLÈGE POLYVALENT BILINGUE  DE DOUALA', NULL, 'B.P. 4100, Douala, Cameroun', '233 43 25 47', 'contact@cpb-douala.com', 'https://cpb-douala.com/', 'logos/i76wD5ne1K1rv8C3cOkBkYOsDKWFHlreYfc7cTzu.png', 'FCFA', 'FIGEC', 'Cameroun', 'Douala', 'Vos dossiers ne seront transmis qu\'après paiement de la totalité des frais de scolarité sollicités', '2025-08-15', 10.00, '#6f42c1', 'Stephane Foyet', '+23769118389', 1, 'https://api.ultramsg.com/instance97191/', '97191', 'vdehri5ktxhl653x', '2025-08-03 17:55:23', '2025-09-12 01:29:39');

-- --------------------------------------------------------

--
-- Structure de la table `school_years`
--

CREATE TABLE `school_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `school_years`
--

INSERT INTO `school_years` (`id`, `name`, `start_date`, `end_date`, `is_current`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '2025-2026', '2025-09-05', '2026-07-30', 1, 1, '2025-08-04 02:40:28', '2025-08-04 02:40:28');

-- --------------------------------------------------------

--
-- Structure de la table `sections`
--

CREATE TABLE `sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sections`
--

INSERT INTO `sections` (`id`, `name`, `description`, `is_active`, `order`, `created_at`, `updated_at`) VALUES
(5, 'SECTIONS FRANCOPHONE', NULL, 1, 1, '2025-08-04 02:42:40', '2025-08-16 18:58:00'),
(6, 'ENSEIGNEMENT TECHNIQUE', NULL, 1, 2, '2025-08-04 02:43:48', '2025-08-04 05:11:57'),
(7, 'SECTIONS ANGLOPHNES', NULL, 1, 3, '2025-08-04 02:46:03', '2025-08-04 05:12:32');

-- --------------------------------------------------------

--
-- Structure de la table `sequences`
--

CREATE TABLE `sequences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `number` int(11) NOT NULL,
  `trimester_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `series_subjects`
--

CREATE TABLE `series_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_class_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `coefficient` decimal(3,1) NOT NULL DEFAULT 1.0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('3GHPYP7M5aWWW6Q9gFQDOPRpgq1q5UPBD7s2sJEJ', NULL, '69.59.197.164', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTTJScUJnM2UzdTdmckdjVzhxUE1IUnRCb1c0Vnp2NkxzdHBUaVRvSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757575777),
('4559PIEfmsINmO7t3xtLWTieRZX1c9TeeWJebuGn', NULL, '143.105.152.84', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYXpyOFJMQldzNUdUMW5OZGlwd0x1Z1EySGJleHVBR3k3TnlER280byI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757578182),
('65gweViJFzTh2qiwVZ86dAmVCMU67m4tZd0Z5IrV', NULL, '143.105.152.84', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUXhhS2pHSmJFT0tHaWxGQ0NDbXllNGRtOVQ5MzFSUXRZTjNvM1d5SiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757578191),
('6iB711SxD6QPoajhCn4RHFVo39SxVhLxFyWtgr9r', NULL, '5.62.43.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSXdYc1F1RG9kRnpTazlMQjBOVUZ2eFI3bUlyN0VpV0h6SVkwWWRZcyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vaW5kZXgucGhwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757421656),
('7W7PunrdgiGJecyioL865CBeebWd2Ez8JGJ0Ps9F', NULL, '5.62.43.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWWJzMGFtZHc2clZpd2FOa0RKRzc1YkZpR3BvckNuNlFlYjFtdEdoOCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757421656),
('88H8mypv2DELDUIOkvg3HZnFIQM8tsUSvdneAnRc', NULL, '34.34.151.237', 'python-requests/2.32.5', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicDl4SXREbGVOYlQ1VElpaVN0UW13eTgzOUpqc245eHFpMEk0czVuRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757516223),
('9xYk8qfdCIHxThruAX4AqoIO2sX6JeeE5UpYuduX', 62, '143.105.152.149', 'Dart/3.9 (dart:io)', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiQmNXS0VoMElBejBMZUZueWZRbTZBQ1p4bnNDeUY4YU92dEpKeUhXMyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757593279),
('c8bAX1IiOGnFwvZQbbrjlzcwYjb03LgBcLgeuNVM', NULL, '84.37.34.179', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVWZHSEVQb1dyUkZVR2dUUVIxcmJTNExUdWxieHBLZkc4Y3dhRTFWQiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757578706),
('cHWKQGrsVIWgiA4onFmpPy7FFkUNjMcasZQP8Mox', NULL, '45.148.10.245', 'Go-http-client/1.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSElIRmhFY0E4YkJNVjlsbUhvaUJtUFVLWDg4WHlwdFl3dEpOSk1qeCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757367391),
('GdOJscBQ9Ng78uqe2MllawpHS3KxVpp8CuZUVKTC', NULL, '143.105.152.21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicVJ0UmI2c1EyUU9QUWhBZHc0T1R1V1RkZ21NdGIwbHRQWmVqSlRBMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757420438),
('JTz0ucs2ba305czkM10GPLvcDiCCH5sbrzwdRhki', NULL, '143.105.152.210', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiM0lGb1g3Znc4d1V0cGt3TVRlcUpQMVk1dWxkVzJua3h5SlZkY29leCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757460672),
('KHaHY8XJ2ZmDooxESOoNj86wTT20zRpy6UzAyWZH', NULL, '102.244.222.80', 'curl/8.7.1', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiWExNa0drR0tZa1hQeUhNaWx2SFRNM3QxRGd1TDR6RTJXNEwydE5wZSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757575186),
('Mi8P5EOB6j9xLMFD3R1PWDM0IScYoqckxxsHyakh', NULL, '95.211.43.175', 'python-requests/2.32.3', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidVhKdjhLbXZzeEJaUDVFY1B0YlRGMU5iQ2Nlb0dCVDM1QjlEYmhpOSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vPyUzQ3BsYXklM0V3aXRobWUlM0MlMkYlM0U9Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757534625),
('NzEtkzGLlXqkNktjb7gDYoQ9iU2CaiGGKhqhhfB0', NULL, '102.244.222.80', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZHFyVmVESWFYaDlvZ2xSR3VTUm9lMndVb0hXRVlENHFVV0dpR3F6ZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757562151),
('P65g9HGj54F4NEwj0guoEcT6pgkbdWMdNCqCdaKH', NULL, '95.211.43.175', 'python-requests/2.32.3', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieUhzdkNNaFdqTDVJd2lEYnJXTThPbHphZEE2UzBxSjdkRnNPTmQ2USI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vPyUzQ3BsYXklM0V3aXRobWUlM0MlMkYlM0U9Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757719017),
('PGvj7nBk2N8TVbG1HE5XYdgMSAPujZzSnr8860Bc', NULL, '95.211.43.175', 'python-requests/2.32.3', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUHQ1a1pCUkFnSlkwbkxISWNOMTBycUNSbnhlRU44ZUU3OGdkbHRKcSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vPyUzQ3BsYXklM0V3aXRobWUlM0MlMkYlM0U9Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757463864),
('pvRlK9iaLMJP59ZY8ByPCqrMlmS464vTRYoaDEbn', NULL, '45.130.83.246', 'python-requests/2.32.3', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieEp2czhKbGlsSEY1MUpJRGpjVFJBQ29PZm5KeU42Ukc4cmVRYW05byI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vPyUzQ3BsYXklM0V3aXRobWUlM0MlMkYlM0U9Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757396518),
('QN3qn3K7a22DIT5NbykOR89AoJuLYVDmPd3nlhyp', NULL, '34.34.151.237', 'python-requests/2.32.5', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ0N0MHFjQ1d5b3BQc1g2ZGt5Q1BwNXFHRFo3b3RYd01scVZoU2tPUCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757516223),
('rttvMbjw91u8hNujyTIhbBB7kIqYIVqzWOfQmSIZ', NULL, '102.244.222.80', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoia2U2MVBLWEljd3NLNVVyb0dBelpocTM0alpKNklHcWxmdE1WNFRGSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757575771),
('SAPcwNLiULFFUu9vraWCn2M1kfwN2GkkFentzGqq', NULL, '102.244.222.80', 'curl/8.7.1', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiMDBRTk5qNWc4RFluaXA4eEVQcncyRERxb1JRUVdiRTZZaGpSTjIzVCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757561217),
('SlDDiuG6fxJVCeikVNkXAFKTQhaB7JVClZVK5HqG', NULL, '143.105.152.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYnhjeGxzTnVwbHdGWUpMSWFpbGpnQmlqRm5QcEY0b2dSNXdXVGNIbCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757692543),
('t9mg7oH7lIWdhXIsoJeXSOOsUWKMLgbI4n2WXq6p', NULL, '34.34.151.237', 'python-requests/2.32.5', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieGpJbkRwY1NQNVpNajdhb1pyaWNZaE56ckoxRXZ6Z1BJVjZLTnR5RCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757516223),
('tnyh4dj1rNbV9l3Wggav1Gec1kCEhJkcBuePcHdG', NULL, '34.34.151.237', 'python-requests/2.32.5', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTUpIaVc3OHI2WWNmZzhhSjUwcTJ4VFR0Q3hlSlIzNFdlbmdCMnRzWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757516223),
('ttJ6H1G7T79bhVPqrOEhlq5QpiYCG6YSh6XbH1rd', NULL, '167.172.81.17', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko; Google Web Preview) Chrome/27.0.1453 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMlVBNW1lZ2R1VWkzU29YNEtJMnlmclUwYThvekNSRWwwUmk1Wkt2RCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757550676),
('tZSslTZns2EnFXuFWayZmv930btPZQ6YCD3EoPX4', 65, '143.105.152.164', 'Dart/3.9 (dart:io)', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiQ3VRTFo2NW00ajg3VHlla3hCaW1NZE9jR2JpdldYYlM5ZU04d1BwdSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1757505229),
('ZLV1scjwWH5WpHXyU0pU6eKP7ekEqOvQSeLeGJY0', NULL, '5.62.43.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMkFCRHh4VUdsTTlnUDVicUZ3MkNWZnJTTE9BNVhSVEFDMkp6Y1NXNiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1757421654);

-- --------------------------------------------------------

--
-- Structure de la table `staff_attendances`
--

CREATE TABLE `staff_attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `supervisor_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL COMMENT 'Date de présence',
  `scanned_at` timestamp NOT NULL COMMENT 'Moment précis du scan QR',
  `scanned_qr_code` varchar(100) DEFAULT NULL COMMENT 'Le code QR exact qui a été scanné',
  `is_present` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Présent ou absent',
  `event_type` enum('entry','exit','auto') NOT NULL DEFAULT 'auto' COMMENT 'Type d''événement: entrée, sortie ou automatique',
  `staff_type` varchar(50) NOT NULL,
  `class_id` bigint(20) UNSIGNED DEFAULT NULL,
  `work_hours` decimal(5,2) DEFAULT NULL COMMENT 'Heures de travail effectuées',
  `late_minutes` int(11) NOT NULL DEFAULT 0 COMMENT 'Minutes de retard',
  `early_departure_minutes` int(11) NOT NULL DEFAULT 0 COMMENT 'Minutes de départ anticipé',
  `notes` text DEFAULT NULL COMMENT 'Notes ou observations',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `staff_attendances`
--

INSERT INTO `staff_attendances` (`id`, `user_id`, `supervisor_id`, `school_year_id`, `attendance_date`, `scanned_at`, `scanned_qr_code`, `is_present`, `event_type`, `staff_type`, `class_id`, `work_hours`, `late_minutes`, `early_departure_minutes`, `notes`, `created_at`, `updated_at`) VALUES
(1, 81, 19, 1, '2025-09-09', '2025-09-09 03:10:53', 'STAFF_81', 1, 'entry', 'teacher', NULL, NULL, 0, 0, NULL, '2025-09-09 03:10:53', '2025-09-09 03:10:53'),
(2, 25, 19, 1, '2025-09-09', '2025-09-09 15:44:01', 'STAFF_25', 1, 'entry', 'teacher', NULL, NULL, 554, 0, NULL, '2025-09-09 15:44:01', '2025-09-09 15:44:01'),
(3, 75, 19, 1, '2025-09-10', '2025-09-10 03:50:30', 'STAFF_75', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-10 03:50:30', '2025-09-10 03:50:30'),
(4, 65, 19, 1, '2025-09-10', '2025-09-10 03:50:42', 'STAFF_65', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-10 03:50:42', '2025-09-10 03:50:42'),
(5, 63, 19, 1, '2025-09-10', '2025-09-10 03:50:48', 'STAFF_63', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-10 03:50:48', '2025-09-10 03:50:48'),
(6, 87, 19, 1, '2025-09-10', '2025-09-10 03:53:58', 'STAFF_56', 1, 'entry', 'permanent', NULL, NULL, 0, 0, NULL, '2025-09-10 03:53:58', '2025-09-10 03:53:58'),
(7, 62, 19, 1, '2025-09-10', '2025-09-10 03:54:39', 'STAFF_62', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-10 03:54:39', '2025-09-10 03:54:39'),
(8, 15, 19, 1, '2025-09-10', '2025-09-10 03:55:21', 'STAFF_15', 1, 'entry', 'secretaire', NULL, NULL, 0, 0, NULL, '2025-09-10 03:55:21', '2025-09-10 03:55:21'),
(9, 66, 19, 1, '2025-09-10', '2025-09-10 03:56:06', 'STAFF_66', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-10 03:56:06', '2025-09-10 03:56:06'),
(10, 59, 19, 1, '2025-09-10', '2025-09-10 03:59:05', 'STAFF_59', 1, 'entry', 'teacher', NULL, NULL, 0, 0, NULL, '2025-09-10 03:59:05', '2025-09-10 03:59:05'),
(11, 81, 19, 1, '2025-09-10', '2025-09-10 03:59:14', 'STAFF_81', 1, 'entry', 'teacher', NULL, NULL, 0, 0, NULL, '2025-09-10 03:59:14', '2025-09-10 03:59:14'),
(12, 3, 19, 1, '2025-09-10', '2025-09-10 04:02:44', 'STAFF_3', 1, 'entry', 'accountant', NULL, NULL, 0, 0, NULL, '2025-09-10 04:02:44', '2025-09-10 04:02:44'),
(13, 61, 19, 1, '2025-09-10', '2025-09-10 04:06:29', 'STAFF_61', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-10 04:06:29', '2025-09-10 04:06:29'),
(14, 74, 19, 1, '2025-09-10', '2025-09-10 04:07:09', 'STAFF_74', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-10 04:07:09', '2025-09-10 04:07:09'),
(15, 76, 19, 1, '2025-09-10', '2025-09-10 04:07:50', 'STAFF_76', 1, 'entry', 'admin', NULL, NULL, 0, 0, NULL, '2025-09-10 04:07:50', '2025-09-10 04:07:50'),
(16, 97, 19, 1, '2025-09-10', '2025-09-10 04:20:48', 'TCH_83', 1, 'entry', 'semi_permanent', NULL, NULL, 0, 0, 'Classes: SEME 4', '2025-09-10 04:20:48', '2025-09-10 04:20:48'),
(17, 73, 19, 1, '2025-09-10', '2025-09-10 05:02:00', 'STAFF_73', 1, 'entry', 'teacher', NULL, NULL, 0, 0, NULL, '2025-09-10 05:02:00', '2025-09-10 05:02:00'),
(18, 16, 19, 1, '2025-09-10', '2025-09-10 05:31:40', 'STAFF_16', 1, 'entry', 'secretaire', NULL, NULL, 0, 0, NULL, '2025-09-10 05:31:40', '2025-09-10 05:31:40'),
(19, 25, 1, 1, '2025-09-10', '2025-09-10 04:13:02', 'STAFF_25', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(20, 27, 1, 1, '2025-09-10', '2025-09-10 06:01:21', 'STAFF_27', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(21, 28, 1, 1, '2025-09-10', '2025-09-10 05:04:20', 'STAFF_28', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(22, 29, 1, 1, '2025-09-10', '2025-09-10 05:26:58', 'STAFF_29', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(23, 30, 1, 1, '2025-09-10', '2025-09-10 05:16:36', 'STAFF_30', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(24, 31, 1, 1, '2025-09-10', '2025-09-10 06:09:15', 'STAFF_31', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(25, 33, 1, 1, '2025-09-10', '2025-09-10 04:16:29', 'STAFF_33', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(26, 34, 1, 1, '2025-09-10', '2025-09-10 04:30:03', 'STAFF_34', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(27, 36, 1, 1, '2025-09-10', '2025-09-10 04:59:54', 'STAFF_36', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(28, 37, 1, 1, '2025-09-10', '2025-09-10 05:43:57', 'STAFF_37', 1, 'auto', 'vacataire', NULL, 8.00, 0, 0, 'Présence automatique générée - Personnel vacataire', '2025-09-10 05:51:54', '2025-09-10 05:51:54'),
(29, 12, 19, 1, '2025-09-10', '2025-09-10 06:16:53', 'STAFF_12', 1, 'entry', 'accountant', NULL, NULL, 0, 0, NULL, '2025-09-10 06:16:53', '2025-09-10 06:16:53'),
(30, 101, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'TCH_60', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant vacataire (ENTRÉE)', '2025-09-10 08:52:37', '2025-09-10 08:52:37'),
(31, 85, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'TCH_80', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant vacataire (ENTRÉE)', '2025-09-10 08:52:38', '2025-09-10 08:52:38'),
(32, 52, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'STAFF_52', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant vacataire (ENTRÉE)', '2025-09-10 08:52:39', '2025-09-10 08:52:39'),
(33, 92, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'TCH_32', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant vacataire (ENTRÉE)', '2025-09-10 08:52:39', '2025-09-10 08:52:39'),
(34, 33, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'STAFF_33', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant vacataire (ENTRÉE)', '2025-09-10 08:52:40', '2025-09-10 08:52:40'),
(35, 91, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'TCH_113', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant vacataire (ENTRÉE)', '2025-09-10 08:52:40', '2025-09-10 08:52:40'),
(36, 29, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'STAFF_29', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant (ENTRÉE)', '2025-09-10 09:12:38', '2025-09-10 09:12:38'),
(37, 86, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'TCH_106', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant (ENTRÉE)', '2025-09-10 09:12:38', '2025-09-10 09:12:38'),
(38, 96, 1, 1, '2025-09-10', '2025-09-10 05:30:00', 'TCH_97', 1, 'entry', 'teacher', NULL, 8.00, 0, 0, 'Présence automatique générée - Enseignant (ENTRÉE)', '2025-09-10 09:12:39', '2025-09-10 09:12:39'),
(39, 39, 19, 1, '2025-09-10', '2025-09-10 11:54:58', 'TCH_14', 1, 'entry', 'vacataire', NULL, NULL, 324, 0, NULL, '2025-09-10 11:54:58', '2025-09-10 11:54:58'),
(40, 39, 19, 1, '2025-09-10', '2025-09-10 11:55:18', 'TCH_14', 0, 'exit', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-10 11:55:18', '2025-09-10 11:55:18'),
(41, 95, 19, 1, '2025-09-11', '2025-09-10 23:29:47', 'TCH_115', 1, 'entry', 'semi_permanent', NULL, NULL, 0, 0, NULL, '2025-09-10 23:29:47', '2025-09-10 23:29:47'),
(42, 109, 19, 1, '2025-09-11', '2025-09-10 23:39:45', 'TCH_107', 1, 'entry', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-10 23:39:45', '2025-09-10 23:39:45'),
(43, 62, 62, 1, '2025-09-11', '2025-09-11 12:56:28', 'STAFF_62', 1, 'entry', 'supervisor', NULL, NULL, 386, 0, NULL, '2025-09-11 12:56:28', '2025-09-11 12:56:28'),
(44, 66, 66, 1, '2025-09-11', '2025-09-11 13:50:26', 'STAFF_66', 1, 'entry', 'supervisor', NULL, NULL, 440, 0, NULL, '2025-09-11 13:50:26', '2025-09-11 13:50:26'),
(45, 66, 66, 1, '2025-09-11', '2025-09-11 13:50:58', 'STAFF_66', 0, 'exit', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-11 13:50:58', '2025-09-11 13:50:58'),
(46, 65, 65, 1, '2025-09-11', '2025-09-11 13:51:18', 'STAFF_65', 1, 'entry', 'supervisor', NULL, NULL, 441, 0, NULL, '2025-09-11 13:51:18', '2025-09-11 13:51:18'),
(47, 65, 65, 1, '2025-09-11', '2025-09-11 13:51:40', 'STAFF_65', 0, 'exit', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-11 13:51:40', '2025-09-11 13:51:40'),
(48, 111, 66, 1, '2025-09-12', '2025-09-12 04:35:39', 'TCH_59', 1, 'entry', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-12 04:35:39', '2025-09-12 04:35:39'),
(49, 112, 66, 1, '2025-09-12', '2025-09-12 04:37:41', 'TCH_70', 1, 'entry', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-12 04:37:41', '2025-09-12 04:37:41'),
(50, 66, 66, 1, '2025-09-12', '2025-09-12 04:37:53', 'STAFF_66', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-12 04:37:53', '2025-09-12 04:37:53'),
(51, 113, 66, 1, '2025-09-12', '2025-09-12 04:41:24', 'TCH_85', 1, 'entry', 'semi_permanent', NULL, NULL, 0, 0, NULL, '2025-09-12 04:41:24', '2025-09-12 04:41:24'),
(52, 105, 66, 1, '2025-09-12', '2025-09-12 04:42:55', 'TCH_40', 1, 'entry', 'permanent', NULL, NULL, 0, 0, NULL, '2025-09-12 04:42:55', '2025-09-12 04:42:55'),
(53, 39, 65, 1, '2025-09-12', '2025-09-12 04:43:39', 'TCH_14', 1, 'entry', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-12 04:43:39', '2025-09-12 04:43:39'),
(54, 62, 66, 1, '2025-09-12', '2025-09-12 04:45:19', 'STAFF_62', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-12 04:45:19', '2025-09-12 04:45:19'),
(55, 110, 65, 1, '2025-09-12', '2025-09-12 04:48:57', 'TCH_38', 1, 'entry', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-12 04:48:57', '2025-09-12 04:48:57'),
(56, 61, 65, 1, '2025-09-12', '2025-09-12 04:49:18', 'STAFF_61', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-12 04:49:18', '2025-09-12 04:49:18'),
(57, 75, 66, 1, '2025-09-12', '2025-09-12 04:55:12', 'STAFF_75', 1, 'entry', 'supervisor', NULL, NULL, 0, 0, NULL, '2025-09-12 04:55:12', '2025-09-12 04:55:12'),
(58, 53, 66, 1, '2025-09-12', '2025-09-12 04:56:55', 'TCH_26', 1, 'entry', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-12 04:56:55', '2025-09-12 04:56:55'),
(59, 104, 62, 1, '2025-09-12', '2025-09-12 04:57:53', 'TCH_112', 1, 'entry', 'semi_permanent', NULL, NULL, 0, 0, NULL, '2025-09-12 04:57:53', '2025-09-12 04:57:53'),
(60, 96, 62, 1, '2025-09-12', '2025-09-12 04:58:40', 'TCH_97', 1, 'entry', 'semi_permanent', NULL, NULL, 0, 0, NULL, '2025-09-12 04:58:40', '2025-09-12 04:58:40'),
(61, 58, 62, 1, '2025-09-12', '2025-09-12 05:00:31', 'STAFF_58', 1, 'entry', 'teacher', NULL, NULL, 0, 0, NULL, '2025-09-12 05:00:31', '2025-09-12 05:00:31'),
(62, 33, 66, 1, '2025-09-12', '2025-09-12 07:29:30', 'TCH_9', 1, 'entry', 'vacataire', NULL, NULL, 59, 0, NULL, '2025-09-12 07:29:30', '2025-09-12 07:29:30'),
(63, 43, 66, 1, '2025-09-12', '2025-09-12 07:30:47', 'TCH_18', 1, 'entry', 'vacataire', NULL, NULL, 60, 0, NULL, '2025-09-12 07:30:47', '2025-09-12 07:30:47'),
(64, 99, 66, 1, '2025-09-12', '2025-09-12 08:11:38', 'TCH_29', 1, 'entry', 'vacataire', NULL, NULL, 101, 0, NULL, '2025-09-12 08:11:38', '2025-09-12 08:11:38'),
(65, 74, 63, 1, '2025-09-12', '2025-09-12 08:44:51', 'STAFF_74', 1, 'entry', 'supervisor', NULL, NULL, 134, 0, NULL, '2025-09-12 08:44:51', '2025-09-12 08:44:51'),
(66, 112, 66, 1, '2025-09-12', '2025-09-12 11:26:37', 'TCH_70', 0, 'exit', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-12 11:26:37', '2025-09-12 11:26:37'),
(67, 99, 65, 1, '2025-09-12', '2025-09-12 12:01:54', 'TCH_29', 0, 'exit', 'vacataire', NULL, NULL, 0, 0, NULL, '2025-09-12 12:01:54', '2025-09-12 12:01:54');

-- --------------------------------------------------------

--
-- Structure de la table `staff_attendance_classes`
--

CREATE TABLE `staff_attendance_classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_attendance_id` bigint(20) UNSIGNED NOT NULL,
  `school_class_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `staff_attendance_classes`
--

INSERT INTO `staff_attendance_classes` (`id`, `staff_attendance_id`, `school_class_id`, `created_at`, `updated_at`) VALUES
(1, 16, 27, '2025-09-10 04:20:48', '2025-09-10 04:20:48');

-- --------------------------------------------------------

--
-- Structure de la table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(255) DEFAULT NULL,
  `gender` enum('M','F') DEFAULT NULL,
  `parent_name` varchar(255) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `mother_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `subname` varchar(255) DEFAULT NULL,
  `class_series_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `student_status` enum('new','old') NOT NULL DEFAULT 'new' COMMENT 'Statut de l''étudiant: nouveau ou ancien',
  `phone_number` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `birthday_place` varchar(255) DEFAULT NULL,
  `sex` enum('m','f') DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `profession` varchar(255) DEFAULT NULL,
  `status` enum('new','old') NOT NULL DEFAULT 'new',
  `is_new` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `school_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_number` varchar(255) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `has_scholarship_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indique si l''étudiant peut bénéficier des bourses de sa classe'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `students`
--

INSERT INTO `students` (`id`, `name`, `first_name`, `last_name`, `date_of_birth`, `place_of_birth`, `gender`, `parent_name`, `parent_phone`, `parent_email`, `mother_name`, `mother_phone`, `address`, `photo`, `subname`, `class_series_id`, `email`, `student_status`, `phone_number`, `birthday`, `birthday_place`, `sex`, `father_name`, `profession`, `status`, `is_new`, `is_active`, `created_at`, `updated_at`, `school_year_id`, `student_number`, `order`, `has_scholarship_enabled`) VALUES
(3, 'BAHA NDJOM JEAN MARIE', 'JEAN MARIE', 'BAHA NDJOM', '2017-02-02', 'Douala', 'M', 'BAHA', '+237690581731', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 07:47:58', '2025-09-02 22:30:01', 1, '20250002', 7, 0),
(4, 'AISSATOU DJOUVOULDA AISSATOU', 'AISSATOU', 'AISSATOU DJOUVOULDA', '2013-10-03', 'Douala', 'F', 'AISSATOUT', '+237699998727', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 07:50:41', '2025-09-01 12:06:30', 1, '20250003', 3, 0),
(6, 'DJAMEN SANI ROISSY KARLE', 'ROISSY KARLE', 'DJAMEN SANI', '2011-05-15', 'DOUALA', 'M', 'DJAMEN', '+237652409600', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 08:05:06', '2025-09-01 05:52:11', 1, '20250005', 1, 0),
(8, 'APINA KAMDEM JACQUES LEDOUX', 'JACQUES LEDOUX', 'APINA KAMDEM', '2009-04-14', 'Douala', 'M', 'APINA PASCAL', '+237696608079', NULL, NULL, NULL, NULL, NULL, NULL, 20, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 08:26:17', '2025-08-04 09:25:06', 1, '20250007', 2, 0),
(10, 'APINA APINA CHRISTIAN PASCAL', 'CHRISTIAN PASCAL', 'APINA APINA', '2011-10-12', 'DOUALA', 'M', 'APINA', '+237696608079', NULL, NULL, NULL, NULL, NULL, NULL, 20, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 09:23:49', '2025-08-04 09:25:06', 1, '20250009', 1, 0),
(11, 'SANTIA MINNA MANUELLA', 'MANUELLA', 'SANTIA MINNA', '2012-08-25', 'GUIBI', 'F', 'SANTIA', '+237699998727', NULL, NULL, NULL, NULL, NULL, NULL, 20, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 09:28:56', '2025-08-04 09:28:56', 1, '20250010', 3, 0),
(12, 'ALI ALHADJI ADAMOU', 'ADAMOU', 'ALI ALHADJI', '2009-05-12', 'DOUALA', 'M', 'ADAMOU', '+237655605530', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 10:11:13', '2025-08-04 10:11:13', 1, '20250011', 2, 0),
(13, 'TYUE EYOLE VICTOR RICHARD', 'VICTOR RICHARD', 'TYUE EYOLE', '2012-09-19', 'BANGONG', 'M', 'EYOLE LINUS', '656925922', NULL, NULL, NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 11:22:39', '2025-08-04 12:37:02', 1, '20250012', 1, 0),
(17, 'ANGELIQUE VERONIQUE ESTHER ZAMATONGUI IVONNE', 'ZAMATONGUI IVONNE', 'ANGELIQUE VERONIQUE ESTHER', '2006-06-22', 'BANGUI', 'F', 'ZAMA MATHIEU', '+237651933730', NULL, NULL, NULL, NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 12:54:58', '2025-08-04 12:56:58', 1, '20250015', 2, 0),
(18, 'MBOLO TCHOUDIKOA EVRAD HARDEN', 'EVRAD HARDEN', 'MBOLO TCHOUDIKOA', '2014-12-17', 'DOUALA', 'M', 'TCHOUDIKOA EBECAF EDDY', '+237674950037', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 13:15:11', '2025-09-02 22:30:01', 1, '20250016', 27, 0),
(19, 'ZAMA NKONGBO ANGELA', 'ANGELA', 'ZAMA NKONGBO', '2006-06-22', 'BAMGUI', 'F', 'ZAMA MATHIEU', '697620655', NULL, NULL, NULL, NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 13:30:30', '2025-08-04 13:30:30', 1, '20250017', 3, 0),
(20, 'ZAMA DEGRENDE BONHEUR DAVID', 'BONHEUR DAVID', 'ZAMA DEGRENDE', '2009-07-01', 'Bangui', 'M', 'Zama Mathieu', '697620655', NULL, NULL, NULL, NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 14:00:02', '2025-08-04 14:00:02', 1, '20250018', 1, 0),
(22, 'KATOUA DJOCOTNA OBET', 'DJOCOTNA OBET', 'KATOUA', '2008-07-07', 'GOBO', 'M', 'DJOCOTNA PROSPER', '+23691235539', NULL, NULL, NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 08:30:11', '2025-08-06 08:30:11', 1, '25A00001', 2, 0),
(23, 'SEVIDZEM ADEL NYUYKONGMO', 'ADEL NYUYKONGMO', 'SEVIDZEM', '2014-05-30', 'KUMBO', 'F', 'NGAH ELIAS', '+237653264071', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 08:47:52', '2025-08-06 08:50:22', 1, '25A00002', 1, 1),
(24, 'MBEDE CHYREL ARNOLD', 'CHYREL ARNOLD', 'MBEDE', '2008-10-27', 'YAOUNDE', 'M', 'MBOUTOUH ERIC', '+237692360421', NULL, NULL, NULL, NULL, 'students/photos/student_25A00003_1754513402.png', NULL, 86, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 09:06:15', '2025-08-11 07:01:13', 1, '25A00003', 1, 0),
(25, 'NDANA DJOGUIRA SILVESTRE', 'SILVESTRE', 'NDANA DJOGUIRA', '2006-12-13', 'NDJAMENA', 'M', 'NDANA FELIX', '+237688675350', NULL, NULL, NULL, NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 09:32:33', '2025-08-06 09:32:33', 1, '25A00004', 2, 0),
(26, 'YEUMOU ANGE', 'ANGE', 'YEUMOU', '2009-09-30', 'DOUALA', 'F', 'YEUMO ARNAUD CHARLY', '+237699632295', NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 09:42:05', '2025-08-06 09:42:05', 1, '25A00005', 1, 0),
(30, 'ABADA EKOUMA NELIE FAYELLE', 'NELIE FAYELLE', 'ABADA EKOUMA', '2010-09-27', 'NDAMVO', 'F', 'EKOUMA AMBASSA', '+237676597753', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 10:32:44', '2025-08-06 10:32:44', 1, '25A00009', 2, 0),
(31, 'JAIDZELA VERMA', 'VERMA', 'JAIDZELA', '2006-11-14', 'KUMBO', 'M', 'NGAH ELIAS', '+237653264071', NULL, NULL, NULL, NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 10:39:45', '2025-08-06 10:39:45', 1, '25A00010', 1, 0),
(32, 'ESSOME BOCK JEANNETTE FRANCINE', 'JEANNETTE FRANCINE', 'ESSOME BOCK', '2007-04-28', 'DOUALA', 'M', 'BOCK TACKE SAMUEL LEDOU', '690881229', NULL, 'METEE MARTHE SEVERINE', NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 11:36:39', '2025-09-03 09:00:21', 1, '25A00011', 2, 0),
(34, 'MBONG BOAL AMBO CECILE LAURENTINE', 'CECILE LAURENTINE', 'MBONG BOAL AMBO', '2008-05-07', 'KON', 'F', 'ASSOL ALICE', '677572470', NULL, NULL, NULL, NULL, NULL, NULL, 86, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 13:32:53', '2025-08-06 14:30:15', 1, '25A00013', 2, 0),
(36, 'WELLIMUM MBOUTOUH NICOLES', 'NICOLES', 'WELLIMUM MBOUTOUH', '2012-05-22', 'YAOUNDE', 'M', 'MBOUTOUH ERIC', '691360421', NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 14:13:17', '2025-08-06 14:13:17', 1, '25A00014', 1, 0),
(37, 'TANGMO DOUANLA ANGE INDIRA', 'ANGE INDIRA', 'TANGMO DOUANLA', '2012-05-16', 'MBOUDA', 'F', 'DOUANLA SERGE', '695776742', NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 14:35:35', '2025-08-06 14:50:07', 1, '25A00015', 2, 0),
(38, 'KENBANG DJIMELI YANN AIME', 'YANN AIME', 'KENBANG DJIMELI', '2011-09-29', 'BAMBI', 'M', 'DJIMELI BEAUCLAIR', '655275664', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 14:44:32', '2025-08-06 14:44:32', 1, '25A00016', 3, 0),
(39, 'DJEPANG ATATWA AMANDA GABRILLA', 'AMANDA GABRILLA', 'DJEPANG ATATWA', '2012-03-13', 'DOUALA', 'F', 'ATATWA JOSEE', '699159523', NULL, NULL, NULL, NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 14:58:16', '2025-08-06 14:58:16', 1, '25A00017', 1, 0),
(41, 'Douanla Arthur Joel', 'Joel', 'Douanla Arthur', '2011-01-02', 'Mbouda', 'M', 'Douanla serge', '695776742/695360102', NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:10:54', '2025-08-07 11:10:54', 1, '25A00019', 3, 0),
(42, 'ESSOH MOUMKOUM EMMANUEL', 'MOUMKOUM EMMANUEL', 'ESSOH', '2014-06-14', 'FOUMBOT', 'M', 'MOUMKOUM ARNMOS', '+237658556255', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:18:11', '2025-08-07 11:20:47', 1, '25A00020', 2, 1),
(43, 'NTADA MOUMKOUM RAISSA', 'MOUMKOUM RAISSA', 'NTADA', '2011-11-12', 'FOUMBOT', 'F', 'MOUMKOUM ARNMOS', '658556255', NULL, NULL, NULL, NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:27:13', '2025-08-07 11:27:13', 1, '25A00021', 1, 0),
(45, 'KAMGA NIEMEJI CHRIST MESSI', 'CHRIST MESSI', 'KAMGA NIEMEJI', '2014-10-23', 'DOUALA', 'M', 'NIEMEJI STEPHANE ERNESS', '+237679805772', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:33:01', '2025-09-02 22:30:01', 1, '25A00023', 22, 1),
(46, 'TOGODNE PODWE EMMANUELLE', 'PODWE EMMANUELLE', 'TOGODNE', '2014-06-24', 'DOUALA', 'M', 'PODWE', '+237699308696', NULL, NULL, NULL, NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:42:34', '2025-09-12 11:19:49', 1, '25A00024', 40, 0),
(48, 'MEDOM YOUSSI ELISABETH LAURE', 'ELISABETH LAURE', 'MEDOM YOUSSI', '2015-10-13', 'DOUALA', 'F', 'YOUSSI EMMANUEL', '696663063', NULL, 'TCHUENCHE TENTCHOUENG STEPHANIE SANDRINE', NULL, NULL, NULL, NULL, 77, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:47:35', '2025-08-27 12:55:32', 1, '25A00026', 1, 0),
(49, 'ABOUBAKAR ALHADJI ADAMOU', 'ALHADJI ADAMOU', 'ABOUBAKAR', '2012-03-14', 'YAOUNDE', 'M', 'ADAMOU MOUHAMADOU', '655605530', NULL, NULL, NULL, NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 12:01:47', '2025-08-07 12:01:47', 1, '25A00027', 1, 0),
(51, 'Ngassa tchoua Paul Cyril', 'Paul Cyril', 'Ngassa tchoua', '2014-03-22', 'Douala', 'M', 'Tchouami njiontchou Bertrand', '677529567', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 12:46:09', '2025-09-02 22:30:01', 1, '25A00029', 35, 1),
(52, 'Watat  tchoua Axel joel', 'Axel joel', 'Watat  tchoua', '2012-07-27', 'Douala', 'M', 'Tchouami  njiontchou Bertrand', '677529567', NULL, NULL, NULL, NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 12:58:09', '2025-08-07 12:58:09', 1, '25A00030', 1, 0),
(54, 'Foko shammah Emmanuel', 'Emmanuel', 'Foko shammah', '2015-05-31', 'Douala', 'M', 'Foko sammuel', '697444836', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 13:38:11', '2025-08-07 13:38:11', 1, '25A00032', 3, 1),
(61, 'NYA TCHAKOUNTE PRINCESSE ARIANE', 'PRINCESSE ARIANE', 'NYA TCHAKOUNTE', '2014-06-29', 'DOUALA', 'F', 'TCHAKOUNTE BRUNO', '653746947', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-11 06:48:12', '2025-09-02 22:30:01', 1, '25A00037', 41, 1),
(62, 'ASSAGA THONG DITERLINE LAURENTINE', 'DITERLINE LAURENTINE', 'ASSAGA THONG', '2013-12-29', 'DOUALA', 'F', 'THONG PHILIPPE CLEMENT', '696752501', NULL, 'NGO NYEMB GENEVIEVE SYLVIE', '693245916', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-12 06:11:10', '2025-08-29 10:25:23', 1, '25A00038', 4, 1),
(65, 'AFANA NDONGO INGRID ELISABETH', 'INGRID ELISABETH', 'AFANA NDONGO', '2009-11-30', 'DOUALA', 'F', 'NDONGO SIMON', '699972503', NULL, NULL, NULL, NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-12 07:16:45', '2025-09-02 22:30:54', 1, '25A00041', 1, 0),
(66, 'FOKO SHAMMAH DANIELLE', 'DANIELLE', 'FOKO SHAMMAH', '2013-06-27', 'DOUALA', 'F', 'FOKO SAMMUEL', '673876973', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-12 10:50:20', '2025-08-12 10:50:20', 1, '25A00042', 5, 1),
(67, 'NKOLO BEYALA GISLENE', 'GISLENE', 'NKOLO BEYALA', '2008-07-04', 'DOUALA', 'F', 'NKOLO JOSEPH DESIRE', '695300417', NULL, 'MBALLA MARLISE LOUISETTE', '.', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-12 10:55:59', '2025-08-28 08:21:56', 1, '25A00043', 1, 0),
(69, 'KEUMOU ANGE', 'ANGE', 'KEUMOU', '2014-10-20', 'DOUALA', 'F', 'NFEUFEN OUSSENI', '690904210', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-15 06:06:31', '2025-09-02 22:30:01', 1, '25A00045', 24, 1),
(72, 'MBOA ELISABETH KENDRA', 'ELISABETH KENDRA', 'MBOA', '2009-01-21', 'DOUALA', 'F', 'KOUKA CHRISTIAN EMMANUEL', '698777843', NULL, NULL, NULL, NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-15 06:25:56', '2025-08-15 06:25:56', 1, '25A00048', 3, 0),
(73, 'MBELAMA MANAOUDA', 'MANAOUDA', 'MBELAMA', '2010-09-10', 'BAO-TASAÏ', 'M', 'MANAOUDA GABRIEL', '699367288', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-15 06:34:36', '2025-08-15 06:34:36', 1, '25A00049', 3, 0),
(76, 'MUSSIMA NBWANGA RHODES PRUNELLE', 'RHODES PRUNELLE', 'MUSSIMA NBWANGA', '2008-06-20', 'DOUALA', 'F', 'NBWANGA OSCAR LIBERTE', '.', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 10:00:03', '2025-08-19 10:00:03', 1, '25A00051', 5, 0),
(77, 'EANG ANGE MURIELLE', 'ANGE MURIELLE', 'EANG', '2008-09-29', 'MBOT MAKAK', 'F', 'EANG JEAN PAUL', '699736235', NULL, NULL, NULL, NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:04:31', '2025-09-02 22:30:54', 1, '25A00052', 5, 0),
(79, 'YAMAPI TCHASSI LA COMPTESSE DIVINE', 'LA COMPTESSE DIVINE', 'YAMAPI TCHASSI', '2012-02-20', 'DOUALA', 'F', 'SANI TCHASSI JOSEPH DURANT', '696427010', NULL, NULL, NULL, NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:14:40', '2025-08-19 14:14:40', 1, '25A00054', 1, 0),
(80, 'DONGMO DANIE AIMEE', 'DANIE AIMEE', 'DONGMO', '2011-08-25', 'BAFOU', 'F', 'NGUEFACK MARC', '675981670', NULL, NULL, NULL, NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:17:49', '2025-08-19 14:17:49', 1, '25A00055', 1, 0),
(81, 'BERTHE KHADIDJA AMOU', 'KHADIDJA AMOU', 'BERTHE', '2009-09-25', 'DOUALA', 'F', 'BERTHE SALLAHA', '.', NULL, NULL, NULL, NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:21:12', '2025-08-19 14:21:12', 1, '25A00056', 2, 0),
(82, 'KAMENI KEN BRIGHT MELVIN', 'BRIGHT MELVIN', 'KAMENI KEN', '2014-06-06', 'DOUALA', 'M', 'KAMENI TCHONAMANI NARCISSE HERVE', '672671898', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:26:29', '2025-09-02 22:30:01', 1, '25A00057', 20, 0),
(83, 'KAMENI KAMAGO ANAYA ELFRIED', 'ANAYA ELFRIED', 'KAMENI KAMAGO', '2013-02-02', 'DOUALA', 'F', 'KAMENI TCHOUAMANI NARCISSE HERVE', '677445610', NULL, NULL, NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:34:38', '2025-08-19 14:34:38', 1, '25A00058', 3, 0),
(84, 'DIEDIFFO LOÏC DJOKAEFF', 'LOÏC DJOKAEFF', 'DIEDIFFO', '2010-09-20', 'MBOUDA', 'M', 'DIEDIFFO SHAGUE ERIC LAMBERT', '671086946', NULL, NULL, NULL, NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:38:30', '2025-08-19 14:38:30', 1, '25A00059', 2, 0),
(86, 'EDJANGHA TETIO MARCK RICK FREYD', 'MARCK RICK FREYD', 'EDJANGHA TETIO', '2015-12-25', 'NYETE', 'M', 'TETIO NGUETSA MELVIS DUCLAIR', '697476049', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:47:19', '2025-08-19 14:47:19', 1, '25A00060', 6, 1),
(87, 'JUINE DARELLE', 'DARELLE', 'JUINE', '2009-09-13', 'DOUALA', 'F', 'ALOMENWING WILSON NDIANG FOH', '.', NULL, NULL, NULL, NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:51:56', '2025-08-19 14:51:56', 1, '25A00061', 3, 0),
(88, 'NYABEYEU TCHETMI JORDANNE', 'JORDANNE', 'NYABEYEU TCHETMI', '2009-12-02', 'DOUALA', 'F', 'NYABEYEU NKOMTCHOUA DAGOBERT', '670597980', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:59:58', '2025-08-19 14:59:58', 1, '25A00062', 6, 0),
(89, 'FOTSO KEGNE JORANE KINSLEY', 'JORANE KINSLEY', 'FOTSO KEGNE', '2012-05-23', 'BAFOUSSAM', 'M', 'OUAGNE FOTSO', '691839008', NULL, NULL, NULL, NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 06:25:49', '2025-08-20 06:25:49', 1, '25A00063', 5, 0),
(90, 'NGUIAMBOP PRINCESSE KARNI', 'PRINCESSE KARNI', 'NGUIAMBOP', '2013-01-30', 'DOUALA', 'F', 'NDEPPAFI MOUANAH ISIDORE', '652033637', NULL, NULL, NULL, NULL, NULL, NULL, 73, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 06:30:16', '2025-08-20 06:30:16', 1, '25A00064', 1, 0),
(91, 'BILOUNGA MANUELA RAMATOU', 'MANUELA RAMATOU', 'BILOUNGA', '2012-03-10', 'YAOUNDE', 'F', 'HAMISSOU HAMZA', '655426778', NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:22:20', '2025-08-20 07:22:20', 1, '25A00065', 4, 0),
(92, 'BIKONDO KOUOH FALESKA OLIVIA', 'FALESKA OLIVIA', 'BIKONDO KOUOH', '2011-07-15', 'YAOUNDE', 'F', 'KOUOH MARC ANDRE PASCAL', '695840755', NULL, 'MOLO BESSALA JOSEPHINE PRUDENCE', '675406012', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:27:43', '2025-09-09 12:59:48', 1, '25A00066', 2, 0),
(93, 'PRECIOUS QUINTA KEYONYUI', 'KEYONYUI', 'PRECIOUS QUINTA', '2009-02-08', 'BAMUNKA URBAN HEALTH CENTRE', 'F', 'ELVIS NGWALEH', '650653602', NULL, 'BOWONG BERTILLA NGAH', NULL, NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:43:42', '2025-09-09 14:11:27', 1, '25A00067', 3, 0),
(94, 'ELTEN CHRIS FUAYEH NGWALEH', 'FUAYEH NGWALEH', 'ELTEN CHRIS', '2011-05-21', 'BAMUNKA URBAN H/CENTRE', 'M', 'ELVIS NGWALEH', '650653602', NULL, 'BOWONG BERTILA NGAH', NULL, NULL, NULL, NULL, 61, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:47:35', '2025-09-09 14:11:08', 1, '25A00068', 1, 0),
(95, 'MEVOA ESSILA PRUDENCE AUDREY', 'PRUDENCE AUDREY', 'MEVOA ESSILA', '2008-04-22', 'YAOUNDE', 'F', 'KOUOH MARC ANDRE', '695840755', NULL, 'MOLO BESSALA JOSEPHINE PRUDENCE', '675406012', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:51:28', '2025-09-09 12:57:32', 1, '25A00069', 1, 0),
(96, 'PIEJION MAGNE PRINCESSE CHERIDANN', 'PRINCESSE CHERIDANN', 'PIEJION MAGNE', '2008-01-05', 'DOUALA', 'F', 'PIEJION ROBERT', '677406414', NULL, NULL, NULL, NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:54:53', '2025-08-20 07:54:53', 1, '25A00070', 4, 0),
(97, 'TAGNI TCHETGNIA EMMANUELLE DOMINIQUE', 'EMMANUELLE DOMINIQUE', 'TAGNI TCHETGNIA', '2008-04-01', 'DOUALA', 'F', 'TAGNI TOMDOMNOU HEBRAD JOEL', '699917047', NULL, NULL, NULL, NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:59:49', '2025-08-20 07:59:49', 1, '25A00071', 2, 0),
(98, 'MENGAPTCHE DEUSSIDJI PATRICIA FORTUNE', 'PATRICIA FORTUNE', 'MENGAPTCHE DEUSSIDJI', '2010-05-27', 'BANA', 'F', 'DEUSSIDJI MONTHE MARTIAL', '694019010', NULL, NULL, NULL, NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 08:06:29', '2025-08-20 08:06:29', 1, '25A00072', 2, 0),
(99, 'FOLONG JOYS PERLITA', 'JOYS PERLITA', 'FOLONG', '2014-09-14', 'DOUALA', 'F', 'MEGOUE MICHAEL', '673047805', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 08:24:13', '2025-09-02 22:30:01', 1, '25A00073', 15, 1),
(100, 'GANKAM MAYET GABRIELLE COLLETE', 'GABRIELLE COLLETE', 'GANKAM MAYET', '2014-02-13', 'BONABERI-DOUALA', 'F', 'GANKAM DJONONSI SERGE PATRICK', '671223045', NULL, 'TOUKAM TIEUBO DORIANE CHRISTELLE', '.', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:26:53', '2025-09-09 12:43:07', 1, '25A00074', 7, 1),
(101, 'NDIKI TCHOUPE DAVID EMMANUEL', 'DAVID EMMANUEL', 'NDIKI TCHOUPE', '2013-03-02', 'YAOUNDE', 'M', 'TCHOUPE BRICE', '653469995', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:31:00', '2025-09-02 22:30:01', 1, '25A00075', 34, 1),
(102, 'DAYON PIEJION NEHEMIE GRACE', 'NEHEMIE GRACE', 'DAYON PIEJION', '2010-09-17', 'DOUALA', 'F', 'PIEJION ROBERT', '677406414', NULL, NULL, NULL, NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:38:48', '2025-08-20 09:38:48', 1, '25A00076', 1, 0),
(103, 'OUMBENOU MANUELLA ELENHORE', 'MANUELLA ELENHORE', 'OUMBENOU', '2012-05-15', 'DOUALA', 'F', 'OUMBENOU JEAN PIERRE', '675056614', NULL, NULL, NULL, NULL, NULL, NULL, 108, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:43:05', '2025-08-20 09:43:05', 1, '25A00077', 1, 0),
(104, 'LOUGHA BAYIHA HELENE GRACIELLA', 'HELENE GRACIELLA', 'LOUGHA BAYIHA', '2013-09-06', 'DOUALA', 'F', 'BAYIHA YEBGA GUY NESTOR', '696913377', NULL, NULL, NULL, NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:51:01', '2025-08-20 09:51:01', 1, '25A00078', 2, 1),
(105, 'WANDJI NJIKE ASHLEY BRENDA', 'ASHLEY BRENDA', 'WANDJI NJIKE', '2009-03-10', 'DOUALA', 'F', 'NJIKE TAMBIA CARLOS', '699938438', NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:02:10', '2025-08-20 10:02:10', 1, '25A00079', 4, 0),
(106, 'KENGNE NKAKEH MORGANE CHLOE', 'MORGANE CHLOE', 'KENGNE NKAKEH', '2005-05-31', 'YAOUNDE', 'F', 'KAMBOU RODOLPHE', '.', NULL, NULL, NULL, NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:05:36', '2025-08-20 10:05:36', 1, '25A00080', 3, 0),
(107, 'TCHAKOUTIO TENDON PATRICIA NOELLE', 'PATRICIA NOELLE', 'TCHAKOUTIO TENDON', '2005-01-03', 'BAZOU', 'F', 'TENDON MARTIN', '699801798', NULL, NULL, NULL, NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:10:44', '2025-08-20 10:10:44', 1, '25A00081', 4, 0),
(108, 'BAGOUP MBIABOUO EMILIENE ROSETTE', 'EMILIENE ROSETTE', 'BAGOUP MBIABOUO', '2010-07-19', 'MBOUO', 'F', 'MBIABOUO NZOUTOM DANIEL DORE', '699801798', NULL, 'DEMGNE ADELINE ADELE', '699138861', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:22:21', '2025-09-10 07:47:56', 1, '25A00082', 4, 0),
(109, 'YOBA MBIABOUO SILAS ABED-NEGO', 'SILAS ABED-NEGO', 'YOBA MBIABOUO', '2012-04-04', 'NKONGSAMBA', 'M', 'MBIABOUO NZOUTOM DANIEL DORE', '699801798', NULL, 'DEMGNE ADELINE ADELE', '699138861', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:59:26', '2025-09-10 07:48:56', 1, '25A00083', 3, 0),
(110, 'NGUEMO DJIONKOU PRINCESS DIVINE', 'PRINCESS DIVINE', 'NGUEMO DJIONKOU', '2015-09-30', 'LOUM', 'F', 'DJIONKOU ROMUED BIENVENU', '653406687', NULL, NULL, NULL, NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:02:26', '2025-08-20 11:02:26', 1, '25A00084', 3, 1),
(111, 'MENYE MARIE THERESE ROSE', 'MARIE THERESE ROSE', 'MENYE', '2008-01-10', 'YAOUNDE', 'F', 'NADJIBE CLEMENT', '690229637', NULL, NULL, NULL, NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:04:58', '2025-08-20 11:04:58', 1, '25A00085', 2, 0),
(112, 'NTOUOMAMO MOUNIR EL MADHI', 'EL MADHI', 'NTOUOMAMO MOUNIR', '2013-06-02', 'DOUALA', 'M', 'NTOUOMAMO YALOUBA', '698799565', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:08:23', '2025-09-02 22:30:01', 1, '25A00086', 40, 1),
(114, 'MAKEUNE ELOKO LUCIA PASCAL', 'LUCIA PASCAL', 'MAKEUNE ELOKO', '2013-05-28', 'DOUALA', 'F', 'ELOKO ACHILLE', '699029198', NULL, NULL, NULL, NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:18:13', '2025-08-20 11:18:13', 1, '25A00088', 1, 0),
(115, 'AMBIAGA BRIGITTE', 'BRIGITTE', 'AMBIAGA', '2012-06-19', 'BEGNI-BOKITO', 'F', 'BOGNOMO OLI', '675356061', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:21:55', '2025-09-02 22:30:01', 1, '25A00089', 4, 1),
(116, 'ADJAWO MABIEME MIRABELLE', 'MIRABELLE', 'ADJAWO MABIEME', '2013-10-05', 'CSI DE DJAPOSTEN', 'F', 'MABIEME CHARLY CONSTANT', '696955544', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:25:15', '2025-09-02 22:30:01', 1, '25A00090', 2, 1),
(117, 'BAGNEKI BALEMAGNA MORGAN EDELL', 'MORGAN EDELL', 'BAGNEKI BALEMAGNA', '2010-04-25', 'DOUALA', 'M', 'BALEMAGNA BETONDE VALENTIN', '674527325', NULL, NULL, NULL, NULL, NULL, NULL, 61, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:30:05', '2025-08-20 11:30:05', 1, '25A00091', 2, 0),
(118, 'BIKELE MBALLA DANIEL RICHESSE', 'DANIEL RICHESSE', 'BIKELE MBALLA', '2011-01-11', 'DOUALA', 'M', 'MBALLA DIDIER', '699676334', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:33:45', '2025-09-02 22:30:01', 1, '25A00092', 9, 1),
(119, 'BESSALA KOUOH JOSEPH STEPHANE', 'JOSEPH STEPHANE', 'BESSALA KOUOH', '2014-10-19', 'DOUALA', 'M', 'KOUOH MARC ANDRE PASCAL', '695840755', NULL, 'MOLO BESSALA JOSEPHINE PRUDENCE', '675406012', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:36:51', '2025-09-09 12:58:32', 1, '25A00093', 8, 1),
(120, 'ASSOAK AMPI ALEXANDRE MATHIEU', 'ALEXANDRE MATHIEU', 'ASSOAK AMPI', '2009-08-16', 'BERTOUA', 'M', '.', '657246949', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 12:23:19', '2025-08-20 12:23:19', 1, '25A00094', 5, 0),
(121, 'BESSOUE KOUNOU YANNICELLE ALIDA', 'YANNICELLE ALIDA', 'BESSOUE KOUNOU', '2008-04-06', 'MBANGASSINA', 'F', 'BESSOUE TSANGO DJIOUKOU', '695483200', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 12:26:26', '2025-08-20 12:26:26', 1, '25A00095', 7, 0),
(122, 'NDOUMBE MINKA NDOLOM LESHA', 'LESHA', 'NDOUMBE MINKA NDOLOM', '2012-05-22', 'DOUALA', 'F', 'NDOLOM JACQUES DIDIER', '699973557', NULL, NULL, NULL, NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 12:29:48', '2025-08-20 12:29:48', 1, '25A00096', 1, 0),
(123, 'DEJBAÏ MIGUEL', 'MIGUEL', 'DEJBAÏ', '2009-03-02', 'BAO-TASSAÏ', 'F', 'MANAOUDA GABRIEL', '699367288', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 13:51:57', '2025-08-20 13:51:57', 1, '25A00097', 6, 0),
(124, 'ARBOUTOU WAYA KANKAO FRANCIS', 'FRANCIS', 'ARBOUTOU WAYA KANKAO', '2009-03-14', 'FOTOKOL', 'M', 'BADORA KANKAO', '699172812', NULL, NULL, NULL, NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 14:00:46', '2025-08-20 14:00:46', 1, '25A00098', 1, 0),
(125, 'ETUKA SANDJO HARRY STEWART TRESOR', 'HARRY STEWART TRESOR', 'ETUKA SANDJO', '2013-11-03', 'DOUALA', 'M', 'SANDJO ETUKA MARTIAL BORIS', '.', NULL, NULL, NULL, NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 14:05:14', '2025-08-20 14:05:14', 1, '25A00099', 1, 0),
(126, 'NGOUMEZO MADADJEU LEANA ZEJOU', 'LEANA ZEJOU', 'NGOUMEZO MADADJEU', '2014-05-04', 'TRENTO', 'F', '.', '677700420', NULL, NULL, NULL, NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 14:09:20', '2025-08-20 14:09:20', 1, '25A00100', 3, 0),
(127, 'LUCK\'S BRIGHT VONYUI NGAH', 'VONYUI NGAH', 'LUCK\'S BRIGHT', '2013-01-22', 'BAMUNKA URBAN HEALTH CENTRE', 'M', 'ELVIS NGWALEH', '671162880', NULL, 'BOWONG BERTILLA NGAH', '650653602', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-26 11:07:40', '2025-09-09 14:11:53', 1, '25A00101', 1, 0),
(129, 'NGOUG BARANE EMILIE SHERYL', 'EMILIE SHERYL', 'NGOUG BARANE', '2013-04-14', 'YAOUNDE', 'F', 'NGOUG MANANG IVAN', '674429572', NULL, 'BEDIAM SUZANNE ALBERTINE', '656370690', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 09:28:03', '2025-08-27 09:28:03', 1, '25A00103', 4, 0),
(130, 'YONKEU FEUMBA OBRILE DIVINE', 'OBRILE DIVINE', 'YONKEU FEUMBA', '2011-06-04', 'DOUALA', 'M', 'FEUMBA AIME', '678711144', NULL, 'YAMBIA PAULETTE', NULL, NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 09:58:37', '2025-08-27 09:58:37', 1, '25A00104', 1, 0),
(131, 'EKOUN DJENKOUA JOYCE', 'JOYCE', 'EKOUN DJENKOUA', '2010-06-09', 'DOUALA', 'F', 'DJENKOUA', '.', NULL, 'ELANGA', '674833936', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:03:26', '2025-08-27 10:03:26', 1, '25A00105', 5, 0),
(132, 'NZOPPA ESSOME LAGRACE DIVINE', 'LAGRACE DIVINE', 'NZOPPA ESSOME', '2008-03-16', 'DOUALA', 'F', 'SANNY JEAN PIERRE', '.', NULL, 'NGONGANG WANDJI', '698404080', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:10:57', '2025-08-27 10:11:13', 1, '25A00106', 1, 0),
(133, 'KAMENI SINDJUI GABRIELLE VENUS VIVICA', 'GABRIELLE VENUS VIVICA', 'KAMENI SINDJUI', '2010-01-07', 'YAOUNDE 5e', 'F', 'SINDJUI MEDJENGOUE BERTRAND', '677448382', NULL, 'KENMOE MIMBE YOLANDE', '691622048', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:13:41', '2025-08-27 10:13:41', 1, '25A00107', 2, 0),
(134, 'TULE GBANDIO MARIE DORCAS FERNANDA', 'MARIE DORCAS FERNANDA', 'TULE GBANDIO', '2008-02-01', 'BERTOUA', 'F', 'GBANDIO MEKANDA MAXIME PAULIN', '.', NULL, 'NNONO MBIDA MIREILLE', '659032955', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:16:33', '2025-08-27 10:16:33', 1, '25A00108', 3, 0),
(135, 'EBA\'A ONGOLO PHILOMENE', 'PHILOMENE', 'EBA\'A ONGOLO', '2010-06-22', 'YAOUNDE', 'F', 'ONGOLO FERDINAND', '679024202', NULL, '.', '693676972', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:20:20', '2025-08-27 10:20:59', 1, '25A00109', 2, 0),
(136, 'NGIDJOÏ CHRISTINA EVA', 'CHRISTINA EVA', 'NGIDJOÏ', '2008-05-31', 'DOUALA', 'F', 'NGIDJOIL JEAN CLAUDE EMMANUEL', '.', NULL, 'MBOND JOHANNA FRANCESCA', '698464088', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:24:42', '2025-08-27 10:24:42', 1, '25A00110', 5, 0),
(137, 'NGOUEKAM EPHRASIL', 'EPHRASIL', 'NGOUEKAM', '2005-01-06', 'DABOMBE', 'F', 'SONGWOUA LUCAS', '.', NULL, 'SCAPLA MEKONTSO MARCELINE', '691920319', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:27:43', '2025-08-27 10:27:43', 1, '25A00111', 1, 0),
(138, 'BELINGA EKOTTO MADELEINE AUDREY', 'MADELEINE AUDREY', 'BELINGA EKOTTO', '2010-01-22', 'DOUALA', 'F', 'BELINGA PAUL ERIC', NULL, NULL, 'BIKONO ADRIENNE CATHERINE', '699533175', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:30:55', '2025-08-27 10:30:55', 1, '25A00112', 6, 0),
(139, 'TCHATCHOU TEMATIO PRINCINYTA DINALY', 'PRINCINYTA DINALY', 'TCHATCHOU TEMATIO', '2009-09-27', 'BALEVENG', 'F', 'TCHATCHOU LUC RAMADAN', NULL, NULL, 'DJEUTEM KONFO MAJOLIE', '657893656', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:34:04', '2025-08-27 10:34:04', 1, '25A00113', 8, 0),
(140, 'TCHATCHOU BELJOLIE MORELE', 'BELJOLIE MORELE', 'TCHATCHOU', '2012-09-10', 'BALEVENG', 'F', 'TCHATCHOU LUC RAMADAN', NULL, NULL, 'DJEUTEM KONFO MAJOLIE', '657893656', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:36:39', '2025-08-27 10:36:39', 1, '25A00114', 4, 0),
(141, 'NGANGUE ETTEGYA JEANNETTE MERVEILLE', 'JEANNETTE MERVEILLE', 'NGANGUE ETTEGYA', '2013-05-10', 'DOUALA', 'F', 'EPESSE EBONGUE LEOPOLD DELOR', '696504576', NULL, 'DIMOUAMOUA DORETTE MIREILLE', '675272860', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:40:40', '2025-08-27 10:44:51', 1, '25A00115', 4, 0),
(142, 'SENGUE ETTEGYA ESTHER GRACE', 'ESTHER GRACE', 'SENGUE ETTEGYA', '2013-09-10', 'DOUALA', 'F', 'EPESSE EBONGUE LEOPOLD DELOR', '696504576', NULL, 'DIMOUAMOUA DORETTE MIREILLE', '675272860', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:43:23', '2025-08-27 10:43:23', 1, '25A00116', 5, 0),
(144, 'EKOMI NNA ANGE GABRIELLE', 'ANGE GABRIELLE', 'EKOMI NNA', '2014-09-16', 'EBOLOWA', 'M', 'EKOMI NNA SAPEUR', '690530087', NULL, 'BIKOMO MEBARA ULRICH DANA', '675859920', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:00:32', '2025-09-02 22:30:01', 1, '25A00118', 13, 1),
(145, 'NGO NTEP ANNE SEGOLENE', 'ANNE SEGOLENE', 'NGO NTEP', '2007-06-02', 'YAOUNDE', 'F', 'NTEP BENJAMIN', '699367007', NULL, 'NGO NGAMBI ANNE NICOLE', '674815569', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:14:07', '2025-08-27 11:14:07', 1, '25A00119', 3, 0),
(146, 'NGOUG MANANG EMMANUEL MARVIN', 'EMMANUEL MARVIN', 'NGOUG MANANG', '2013-04-14', 'YAOUNDE', 'M', 'NGOUG MANANG IVAN', NULL, NULL, 'BEDIAM SUZANNE ALBERTINE', '656370690', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:21:18', '2025-08-27 11:21:18', 1, '25A00120', 5, 0),
(147, 'KATCHO NZIMENI MARYPHEV JANAI', 'MARYPHEV JANAI', 'KATCHO NZIMENI', '2014-08-06', 'BAFANG', 'F', 'NZIMENI WATANA LANDRY', '691513891', NULL, 'GUEGA LUCRECE VERAH', '699735483', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:28:17', '2025-08-27 11:28:17', 1, '25A00121', 6, 0),
(148, 'MPACKO NKOUWANG MELVINE GRACE', 'MELVINE GRACE', 'MPACKO NKOUWANG', '2015-08-15', 'DOUALA', 'F', 'NKOUWANG CYRELLE JORDAN', '.', NULL, 'ESSEBE EHAWEL EWANE CHARLOTTE', '671561034', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:38:35', '2025-09-02 22:30:01', 1, '25A00122', 33, 0),
(149, 'FANSU DONGMA LYNN', 'LYNN', 'FANSU DONGMA', '2010-07-16', 'BAFANG', 'F', 'DONGMA SIMPLICE', '695894257', NULL, 'MEKAMGUEN KOUAMO', '650133365', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:14:21', '2025-08-27 12:14:21', 1, '25A00123', 9, 0),
(150, 'PIDA HABIBA ZENABOU', 'ZENABOU', 'PIDA HABIBA', '2010-04-09', 'DOUALA', 'F', 'PIDA NTONGA BERNARD', '.', NULL, 'HABIBA MOHAMAN', '686263083', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:22:18', '2025-08-27 12:22:18', 1, '25A00124', 10, 0),
(151, 'DJOB A HOLA BARRACK', 'BARRACK', 'DJOB A HOLA', '2011-08-03', 'DOUALA', 'M', 'PONDI BRUNO', '686778580', NULL, 'NDZENGUE EKANI ANASTASIE', '696113693', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:26:01', '2025-08-27 12:26:01', 1, '25A00125', 5, 0),
(152, 'MAKUEATE TSAFACK LUCRESS', 'LUCRESS', 'MAKUEATE TSAFACK', '2013-01-11', 'DOUALA', 'F', 'TSAFACK GUY ERIC', '677724022', NULL, 'KIAMPI ELWIGE', '651439356', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:37:56', '2025-08-27 12:39:13', 1, '25A00126', 8, 1),
(153, 'ZE ATANGANA MARIE PAULE SAMIRA', 'MARIE PAULE SAMIRA', 'ZE ATANGANA', '2014-07-07', 'YAOUNDE', 'F', 'ATANGANA ATANGANA EMMANUEL', '.', NULL, 'TSAMA AVA MARIE JUSTINE', '656940269', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:42:25', '2025-09-12 11:19:21', 1, '25A00127', 38, 1),
(154, 'MOHAMED SALI', 'SALI', 'MOHAMED', '2012-06-20', 'YAOUNDE', 'M', 'SALI NDEKGOUA', '696933493', NULL, 'ZAKIATOU AMADOU', '658230076', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:46:08', '2025-09-02 22:30:01', 1, '25A00128', 30, 1),
(155, 'BOGNOUA DJOUATSA DJAMILA LINE', 'DJAMILA LINE', 'BOGNOUA DJOUATSA', '2015-11-08', 'SANTCHOU', 'F', 'KENGNI DJOUATSA NELSON DIDERO', '652042995', NULL, 'KENFACK CLAURETTE', '671849805', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:50:53', '2025-08-27 12:50:53', 1, '25A00129', 9, 1),
(156, 'KEGNE TELLA PATRICIA ORNELLA', 'PATRICIA ORNELLA', 'KEGNE TELLA', '2013-01-04', 'BAFOUSSAM', 'F', 'TELLA PIERRE EMMANUELLA', NULL, NULL, 'SANDGEU FOKA ALLIANCE', '650872506', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:59:12', '2025-08-27 12:59:12', 1, '25A00130', 23, 1),
(157, 'PANIWELE M\'MANDOA MANUELE LAURA', 'MANUELE LAURA', 'PANIWELE M\'MANDOA', '2013-02-11', 'DOUALA', 'F', 'M\'MANDOA MICHEL', '.', NULL, 'BETIBIGUE YOLA JOSEPHINE', '654394794', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:07:51', '2025-09-12 11:20:44', 1, '25A00131', 43, 1),
(158, 'EKONDO MMANDOA RODRIGUE', 'RODRIGUE', 'EKONDO MMANDOA', '2011-10-18', 'DOUALA', 'M', 'MMANDOA MICHEL', '.', NULL, 'BETIBIGUE YOLA JOSEPHINE', '654394794', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:12:44', '2025-09-02 22:30:01', 1, '25A00132', 14, 1),
(159, 'DJEKA EBENGUE GABRIEL STEEVEN', 'GABRIEL STEEVEN', 'DJEKA EBENGUE', '2013-08-11', 'DOUALA', 'M', 'EBENGUE DJEKA PIERRE BLONDIN', '691640489', NULL, 'NGO NYOGOCK ODILE YOLLANDE', '656332742', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:17:35', '2025-09-02 22:30:01', 1, '25A00133', 12, 0),
(160, 'MALENOUE NJANTA FRANCESCA BRESDELLE', 'FRANCESCA BRESDELLE', 'MALENOUE NJANTA', '2014-07-08', 'DOUALA', 'F', 'NJANTA FRANCKY BRUNEL', '651934682', NULL, 'MATCHOUM WEMBE CYNTHIA JOUVENCELLE', '655741851', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:23:28', '2025-09-02 22:30:01', 1, '25A00134', 25, 1),
(161, 'MBAIN GERALDINE', 'GERALDINE', 'MBAIN', '2010-06-26', 'DOUALA', 'F', 'SEKEM PATRICK', NULL, NULL, 'VEBESSE ERNESTINE', '683619300', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:26:40', '2025-08-27 13:26:40', 1, '25A00135', 2, 0),
(162, 'MBELLE NJIMENI MARIE SYNDI', 'MARIE SYNDI', 'MBELLE NJIMENI', '2010-12-03', 'MTE DE KEKEM', 'F', 'MBELE', NULL, NULL, 'BOUAGUET PHILOMENE', '679748153', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:29:54', '2025-08-27 13:29:54', 1, '25A00136', 7, 0),
(163, 'MACHETEH CLAUDINE', 'CLAUDINE', 'MACHETEH', '2012-02-12', 'DOUALA', 'F', 'SEKEM PATRICK', NULL, NULL, 'VEBESSE ERNESTINE', '683619300', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:41:38', '2025-08-27 13:41:38', 1, '25A00137', 4, 0),
(164, 'TSANGA DIVIN GEDEON WILFRED', 'DIVIN GEDEON WILFRED', 'TSANGA', '2014-07-07', 'LIMBE', 'M', '.', NULL, NULL, 'MEKOLO JULIENNE', '676323879', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:45:02', '2025-08-27 13:45:02', 1, '25A00138', 10, 0),
(165, 'DJAMPOU TIOGUEP STELLA DANIELLA', 'STELLA DANIELLA', 'DJAMPOU TIOGUEP', '2012-02-04', 'DOUALA', 'F', 'TIOGUEP NJOUKWE ROLLAND', NULL, NULL, 'DAKAM ROSELINE', '699570588', NULL, NULL, NULL, 70, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:53:13', '2025-08-27 13:53:13', 1, '25A00139', 1, 0),
(166, 'NGA MANGA MARIE ESTHER', 'MARIE ESTHER', 'NGA MANGA', '2007-12-12', 'BENEBALOT', 'F', '.', '656415695', NULL, 'NGA MOUGOU MARIE MADELEINE', '696427010', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:58:42', '2025-09-02 22:30:54', 1, '25A00140', 11, 0),
(167, 'MASSOUSSI MARLYSE MICHELE', 'MARLYSE MICHELE', 'MASSOUSSI', '2009-03-30', 'DOUALA-CAMEROUN', 'F', 'TIMAMO ELVIS DUBOIS', '650524354', NULL, 'NGO NLEND DELBOISE MARIE DAMIEN', '659786549', NULL, NULL, NULL, 107, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 06:18:28', '2025-08-28 06:18:28', 1, '25A00141', 1, 0),
(168, 'KAMENI LOUISE GAELLE', 'LOUISE GAELLE', 'KAMENI', '2007-02-18', 'BAMOIG', 'F', 'POLAHA FERNAND', '697074057', NULL, 'DJALIALEU MARIE FRANCOISE', '.', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 06:22:07', '2025-08-28 06:22:07', 1, '25A00142', 4, 0),
(169, 'NDAM AÏCHA', 'AÏCHA', 'NDAM', '2011-10-04', 'DOUALA', 'F', 'NDAM ZOUNKARENEKE', '.', NULL, 'MOUKAM YOLANDE', '671563087', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 06:28:21', '2025-08-28 06:28:21', 1, '25A00143', 2, 0),
(170, 'GUILIGON COLETTE', 'COLETTE', 'GUILIGON', '2006-02-06', 'BAFIA', 'F', '.', NULL, NULL, 'BASSA ELISABETH', '653146394', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:18:15', '2025-08-28 07:18:15', 1, '25A00144', 1, 0),
(171, 'BIKOE BIKOE PAUL TIVE LOIC', 'PAUL TIVE LOIC', 'BIKOE BIKOE', '2007-05-10', 'OKOLA', 'M', 'BIKOE PAUL', '.', NULL, 'KOOH SIMONE', '676695025', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:21:26', '2025-08-28 07:21:26', 1, '25A00145', 2, 0),
(172, 'HONA LEUNKEU YOANN MARCK ARTHUR', 'YOANN MARCK ARTHUR', 'HONA LEUNKEU', '2010-03-26', 'DOUALA-CAMEROUN', 'M', 'HONA GUSTAVE CALVIN', NULL, NULL, 'NGO NDJENG FRIDE CHARLOTTE', '699766338', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:24:55', '2025-08-28 07:24:55', 1, '25A00146', 2, 0),
(173, 'HONA AKOMO WARREN GUSTAVE', 'WARREN GUSTAVE', 'HONA AKOMO', '2009-02-03', 'YAOUNDE', 'M', 'AKOMO MARIE JOSEPH', '.', NULL, 'NGO NDJEND JEANNETTE ALICE', '699766338', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:28:23', '2025-08-28 07:28:23', 1, '25A00147', 3, 0),
(174, 'GUEWAC KAKAPI BORIS', 'BORIS', 'GUEWAC KAKAPI', '2005-05-21', 'DOUALA', 'M', 'KAKAPI HONORE', '675865925', NULL, 'KEMGNE HONORINE', NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:33:01', '2025-08-28 07:33:01', 1, '25A00148', 11, 0),
(175, 'DONONA KANKAO BORIS', 'BORIS', 'DONONA KANKAO', '2004-01-01', 'DOUALA', 'M', 'BADORA KANKAO', '699172812', NULL, 'PSADI ODETTE', '.', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:35:23', '2025-08-28 07:35:23', 1, '25A00149', 7, 0),
(176, 'DZO KENGNE CHRIST MOREL', 'CHRIST MOREL', 'DZO KENGNE', '2009-08-31', 'DOUALA', 'M', 'KENGNE MARTIN SIMPLICE', '699615821', NULL, 'DJUMA NOUTOUG FRANCOISE LAURE', '656551512', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:38:54', '2025-08-28 07:38:54', 1, '25A00150', 1, 0),
(177, 'ATEZO TEDAH LINE MICHELLE', 'LINE MICHELLE', 'ATEZO TEDAH', '2004-01-01', 'DOUALA', 'F', 'TEDAH GILBERT', '677411037', NULL, 'MEUKEU MARIE ROSINE', '695593306', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:42:06', '2025-08-28 07:42:06', 1, '25A00151', 1, 0),
(178, 'NGAMI NDJEUPA PRINCESSE', 'PRINCESSE', 'NGAMI NDJEUPA', '2012-05-08', 'DOUALA', 'F', '.', NULL, NULL, 'SINZI TCHOU JOSIANE', '690602490', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:47:52', '2025-08-28 07:47:52', 1, '25A00152', 2, 0),
(179, 'NKWEN BAYIHA MADELEINE ANNAELLE', 'MADELEINE ANNAELLE', 'NKWEN BAYIHA', '2006-10-30', 'DOUALA', 'F', 'BAYIHA EUGENE', '694928158', NULL, 'NGO MPONGO SOM NADEGE', '699024400', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:53:09', '2025-09-02 22:30:54', 1, '25A00153', 12, 0),
(180, 'NGO LIHEP BAYIHA ALBERTINE PATIENCE', 'ALBERTINE PATIENCE', 'NGO LIHEP BAYIHA', '2013-12-25', 'DOUALA', 'F', 'BAYIHA EUGENE', '694928158', NULL, 'NGO MPONG SON NADEGE', '699024400', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:56:59', '2025-08-28 07:56:59', 1, '25A00154', 4, 0),
(181, 'SEN EMILIENNE ANAÏS', 'EMILIENNE ANAÏS', 'SEN', '2009-01-05', 'LYGI', 'F', 'TCHONNANG ROMUAL', '670258044', NULL, 'LEUKAM YVETTE', '675215769', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:04:16', '2025-08-28 08:04:16', 1, '25A00155', 4, 0),
(182, 'MBOUAMGUE MANUELLA STELLA', 'MANUELLA STELLA', 'MBOUAMGUE', '2010-01-01', 'DOUALA', 'F', '.', '651710146', NULL, 'EMENE KELENG NADEGE', '696689202', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:08:01', '2025-08-28 08:08:01', 1, '25A00156', 2, 0),
(183, 'CHRIST NJEM NJEM', 'NJEM NJEM', 'CHRIST', '2006-07-26', 'DOUALA', 'M', 'NDOGJOUE GILBERT', '656456516', NULL, 'NGO NTAMAK JULIENNE SOLANGE', '652601382', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:11:08', '2025-08-28 08:11:08', 1, '25A00157', 6, 0),
(184, 'MAFOGANG NAOUSSI IVANA PATIENCE', 'IVANA PATIENCE', 'MAFOGANG NAOUSSI', '2008-10-20', 'BAMOUGOUM', 'F', 'NAOUSSI BLAISE', '674302865', NULL, 'MATOUKAM MARIE CLAIRE', '652217494', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:13:37', '2025-09-02 22:30:54', 1, '25A00158', 10, 0),
(185, 'AMINATOU SAMSIA AMINATOU', 'AMINATOU', 'AMINATOU SAMSIA', '2010-10-17', 'NGAOUNDERE', 'F', 'MOUHAMATOU', '.', NULL, 'DIARA ZARA', '695838590', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:16:56', '2025-08-28 08:16:56', 1, '25A00159', 3, 0),
(186, 'KUETE DONTIO DANIELLA', 'DANIELLA', 'KUETE DONTIO', '2008-02-24', 'BERTOUA', 'F', 'DONTIO LONTSI ELOGE', '677633557', NULL, 'CATE ODILE', '.', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:26:20', '2025-08-28 08:26:20', 1, '25A00160', 5, 0),
(187, 'SEUTCHUI NJOMEGNI ERIKA DAPHNELLE', 'ERIKA DAPHNELLE', 'SEUTCHUI NJOMEGNI', '2011-03-09', 'DOUALA', 'F', 'NJOMEGNI EMELIN', '652276119', NULL, 'YAMENJI GAELLE', '675601467', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:32:54', '2025-08-28 08:32:54', 1, '25A00161', 7, 0),
(188, 'TCHAMO KOUWO YVANNA MONTINIE', 'YVANNA MONTINIE', 'TCHAMO KOUWO', '2006-10-19', 'DOUALA', 'F', 'KOUWO HERVE YALMIR', '693321099', NULL, 'KOUMO', '677132535', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:12:30', '2025-08-28 10:12:30', 1, '25A00162', 3, 0),
(189, 'NGALANI MANKA\'A  LUM BIH RANNY', 'RANNY', 'NGALANI MANKA\'A  LUM BIH', '2014-03-30', 'FOUMBOT', 'F', 'NGALANI MAXCELL', '653569658', NULL, 'PAULINE LUM', '676684943', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:17:13', '2025-08-28 10:17:13', 1, '25A00163', 5, 0),
(190, 'BEKONO ZANG VICTOIRE SARAH', 'VICTOIRE SARAH', 'BEKONO ZANG', '2013-08-10', 'YAOUNDE', 'F', 'ZANG ABESSOLO', '.', NULL, 'MEKONG ROSETTE', '691813835', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:20:30', '2025-08-28 10:20:30', 1, '25A00164', 5, 0),
(191, 'YATTABONG TAMWO WILFRIED', 'WILFRIED', 'YATTABONG TAMWO', '2009-04-20', 'DOUALA', 'M', 'YATTABONG TAMWO STANISLAS', '675440903', NULL, 'TEDONGMOUO SOLANGE MONIQUE', '678681049', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:26:13', '2025-08-28 10:26:13', 1, '25A00165', 8, 0),
(192, 'ADIDOMA AIME DARNEL', 'AIME DARNEL', 'ADIDOMA', '2012-06-27', 'DOUALA', 'M', 'BEMELINGUE MARIUS', '677280087', NULL, 'AMATAT OGA', '679697212', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:29:13', '2025-09-01 08:04:21', 1, '25A00166', 8, 0),
(193, 'YAFEU BLONDELLE MEDURA', 'BLONDELLE MEDURA', 'YAFEU', '2009-12-01', 'DOUALA', 'F', '.', '675189631', NULL, 'TCHOUANKEP EPHRASIE', '657141394', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:32:54', '2025-08-28 10:32:54', 1, '25A00167', 3, 0),
(194, 'KOMBOU MORELLE LUCRESSE', 'MORELLE LUCRESSE', 'KOMBOU', '2007-08-11', 'LOUM', 'F', '.', '657141394', NULL, 'TCHOUANKEP EPHRASIE', '675189631', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:37:51', '2025-08-28 10:37:51', 1, '25A00168', 7, 0),
(195, 'SOMO OUTOUEN JEANNE STEPHANIE', 'JEANNE STEPHANIE', 'SOMO OUTOUEN', '2009-07-14', 'DOUALA', 'F', 'SOMO ANDRE', '699595567', NULL, 'ENGANEMBEN SYLVIE', '696318990', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:40:23', '2025-08-28 10:40:23', 1, '25A00169', 9, 0),
(196, 'BALEBA BEATRICE DARLA CHARMANTINE', 'BEATRICE DARLA CHARMANTINE', 'BALEBA', '2004-03-14', 'DOUALA', 'F', 'BALEBA PAUL', '658595858', NULL, 'NGO TEMGA LEONCE SOPHIE', '697758427', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:43:26', '2025-09-02 22:30:54', 1, '25A00170', 3, 0);
INSERT INTO `students` (`id`, `name`, `first_name`, `last_name`, `date_of_birth`, `place_of_birth`, `gender`, `parent_name`, `parent_phone`, `parent_email`, `mother_name`, `mother_phone`, `address`, `photo`, `subname`, `class_series_id`, `email`, `student_status`, `phone_number`, `birthday`, `birthday_place`, `sex`, `father_name`, `profession`, `status`, `is_new`, `is_active`, `created_at`, `updated_at`, `school_year_id`, `student_number`, `order`, `has_scholarship_enabled`) VALUES
(197, 'DAMA ALEXANDRA', 'ALEXANDRA', 'DAMA', '2010-12-04', 'NDJOLE', 'F', 'ESSOMO ARMAND DANIEL', '698895831', NULL, 'FONA VERONIQUE', '699108118', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:46:39', '2025-08-28 10:46:39', 1, '25A00171', 2, 0),
(198, 'NANYEP KOUWO DANIEL', 'DANIEL', 'NANYEP KOUWO', '2013-01-19', 'DOUALA', 'M', 'KOUWO HERVE YALMIR', '693321099', NULL, 'KOUWO', '677132535', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:49:23', '2025-08-28 10:49:23', 1, '25A00172', 11, 0),
(199, 'ANGUISSA ZOBO ANGEL LEA', 'ANGEL LEA', 'ANGUISSA ZOBO', '2013-02-22', 'YAOUNDE', 'F', 'ZOBO TSANGA', '.', NULL, 'NKODO ONDOA MAPIE DOMINIQUE', '658311145', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:53:30', '2025-09-02 22:30:01', 1, '25A00173', 5, 0),
(200, 'METSEGOUOC KUE YVANA', 'YVANA', 'METSEGOUOC KUE', '2010-01-12', 'DOUALA', 'F', 'SANDJO KUE JOSEPH', '696478149', NULL, 'MAUTCHA\'A ALICE', '698595286', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:56:58', '2025-08-28 10:56:58', 1, '25A00174', 10, 0),
(201, 'KAMGA KUATE PRINCE SIDONNE', 'PRINCE SIDONNE', 'KAMGA KUATE', '2014-11-17', 'MOMBO', 'M', 'KUATE KAMGA WILLIAM SALVADOR', '674367453', NULL, 'KELLE PELAGIE', NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:02:03', '2025-09-02 22:30:01', 1, '25A00175', 21, 1),
(202, 'NGALANI NDEUSI TREASURE', 'TREASURE', 'NGALANI NDEUSI', '2011-09-23', 'MUYUKA-FAKO', 'F', 'NGALANI MAXCELL II', '653569658', NULL, 'PAULINE LUM', '676684943', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:05:08', '2025-08-28 11:05:08', 1, '25A00176', 4, 0),
(203, 'SANA LATTA', 'LATTA', 'SANA', '2013-10-18', 'DOUALA-CAMEROUN', 'F', 'SANA ATALA', '.', NULL, 'OUEDRAOGO ZENEBOU', '698084423', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:09:12', '2025-08-28 11:09:12', 1, '25A00177', 8, 0),
(204, 'SIMEU FANLEU LUCRESSE PAVELLE', 'LUCRESSE PAVELLE', 'SIMEU FANLEU', '2007-07-07', 'DOUALA', 'F', 'FANLEU EMMANUEL', '652600044', NULL, 'YANDEU CHANCELINE', '698240025', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:11:37', '2025-08-28 11:11:37', 1, '25A00178', 5, 0),
(205, 'SIDJUI MBIANDA STEPHIE FARELLE', 'STEPHIE FARELLE', 'SIDJUI MBIANDA', '2009-08-21', 'DOUALA', 'F', 'MBIANDA NGASSAM FIDELE', '699217280', NULL, 'KAGOUE TCHAMGUE JEANNETTE', '679752095', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:14:19', '2025-08-28 11:14:19', 1, '25A00179', 12, 0),
(206, 'NDEME GUEMPAGA PAUL DAVID', 'PAUL DAVID', 'NDEME GUEMPAGA', '2011-02-25', 'DOUALA', 'M', 'PASCAL ALPHONSE', '697130129', NULL, 'AMALIGA MIREILLE', '683820414', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:16:57', '2025-08-28 11:16:57', 1, '25A00180', 3, 0),
(207, 'DJIESSEU TCHAPTCHET ADRIEN DARYL', 'ADRIEN DARYL', 'DJIESSEU TCHAPTCHET', '2008-05-05', 'BONABERI-DOUALA', 'M', 'TCHAPTCHET ARMAND JOËL', '671518033', NULL, 'TCHUISSEU CHRISTINE ALLIANCE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:22:13', '2025-09-02 22:30:54', 1, '25A00181', 4, 0),
(208, 'NGO KANA VICTOIRE ALEXANDRA', 'VICTOIRE ALEXANDRA', 'NGO KANA', '2009-05-07', 'EDEA', 'F', 'KANA MAKON ALEXANDRE DUMAS', '650919926', NULL, 'BINYET CLAUDIA CARINE', '694883623', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:25:38', '2025-08-28 11:25:38', 1, '25A00182', 3, 0),
(209, 'AYAGALA APINA GLORIA PRINCESSE', 'GLORIA PRINCESSE', 'AYAGALA APINA', '2007-09-29', 'DOUALA', 'F', 'APINA PASCAL', '696427010', NULL, 'SEKE A WANNKOUM RAMATOU', '694334135', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:28:46', '2025-08-28 11:28:46', 1, '25A00183', 4, 0),
(210, 'DJIOSSEU TCHOMTA EVRARD JUNIOR', 'EVRARD JUNIOR', 'DJIOSSEU TCHOMTA', '2011-01-21', 'DOUALA', 'M', 'TCHOMTA JACOB', '696041485', NULL, 'KOUATA DJIOSSEU OLIVE LAURE', '673083481', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:31:21', '2025-08-28 11:31:21', 1, '25A00184', 6, 0),
(211, 'MATAFO NGUIMGO DOMINICK DERIN', 'DOMINICK DERIN', 'MATAFO NGUIMGO', '2012-12-23', 'YAOUNDE', 'M', 'NGUIMGO PIERRE MARCIAL', '691959961', NULL, 'TIDANG NKWETTE BEANNETTE', '690775004', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:35:25', '2025-08-28 11:35:25', 1, '25A00185', 5, 0),
(212, 'YAADOO MAMIDOU FABIEN', 'FABIEN', 'YAADOO MAMIDOU', '2008-10-15', 'DOUALA', 'M', 'MAMIDOU MBARBOLA', '699846320', NULL, 'FASSA MADELEINE', '676818493', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:39:23', '2025-08-28 11:39:23', 1, '25A00186', 6, 0),
(213, 'AKA\'AYELE ANGO FRESHNEL EKANA', 'FRESHNEL EKANA', 'AKA\'AYELE ANGO', '2012-05-12', 'EBOLOWA', 'F', 'ANGO ANGO FELIX', '698120421', NULL, 'MBOZO\'O NNA LAURENE JOELLE', '.', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:47:25', '2025-08-28 11:47:25', 1, '25A00187', 3, 0),
(214, 'MASSOCK LUMIERE DIVINE', 'LUMIERE DIVINE', 'MASSOCK', '2014-06-12', 'DOUALA', 'F', 'MASSOCK PATRICE', '696587295', NULL, 'NYEMB ETOMBE KOLOTTO PAULINE', '.', NULL, NULL, NULL, 71, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:53:20', '2025-08-28 11:53:20', 1, '25A00188', 1, 1),
(215, 'DALLE NDOUMBE NDOLOM KANDIS MARIVONE', 'KANDIS MARIVONE', 'DALLE NDOUMBE NDOLOM', '2016-02-08', 'DOUALA', 'F', 'NDOLOM JACQUES DIDIER', '699973557', NULL, 'NSENGUE NDEMA ROSE', '694086212', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:58:34', '2025-08-28 11:58:50', 1, '25A00189', 12, 1),
(216, 'NGAUSS PALLA ADOLPHE RENE', 'ADOLPHE RENE', 'NGAUSS PALLA', '2008-07-27', 'DOUALA', 'M', 'NGAUSS NDOUNG GERARD', '677627962', NULL, 'NGANOMA DOROTHEE', '677345626', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-29 10:58:20', '2025-08-29 10:58:20', 1, '25A00190', 5, 0),
(217, 'DJAOWE JUNIOR', 'JUNIOR', 'DJAOWE', '2012-04-17', 'DOUKOULA', 'M', 'DJOMAILA ROGER', NULL, NULL, 'BADONIWA ELEINE', '658061078', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-29 13:24:02', '2025-09-02 22:30:01', 1, '25A00191', 11, 1),
(218, 'MBALLA ANE TATIANA DANIELLA', 'ANE TATIANA DANIELLA', 'MBALLA', '2014-01-18', 'DOUALA', 'F', 'MOMHA HONDA JOSEPH RAOUL', NULL, NULL, 'OLOMO EBENGUE MIREILLE CLARISSE', '698542044', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-29 13:35:57', '2025-09-02 22:30:01', 1, '25A00192', 26, 0),
(219, 'WAFO KOUOMEGNE FABRICE VAILLANT', 'FABRICE VAILLANT', 'WAFO KOUOMEGNE', '2008-04-14', 'DOUALA', 'M', 'KOUOMEGNE PIERRE', '654338827', NULL, 'DJUIGNE MADELEINE', '657998790', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 05:49:48', '2025-09-12 11:19:30', 1, '25A00193', 39, 0),
(220, 'DELIVRANCE ROBERT', 'ROBERT', 'DELIVRANCE', '2011-03-12', 'YAGOUA', 'M', 'ARONA GEREMI', '676902705', NULL, 'AINA', '694345639', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 05:54:29', '2025-08-30 05:54:29', 1, '25A00194', 9, 0),
(221, 'NOHA OMBOUTOU DURANT', 'DURANT', 'NOHA OMBOUTOU', '2008-12-17', 'BANDJOUN', 'M', 'OMBOUTOU JEAN', NULL, NULL, 'MOTOUOM DOROTHEE', '691702055', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 05:58:42', '2025-08-30 05:58:42', 1, '25A00195', 7, 0),
(222, 'GUEMDJO VANESSA', 'VANESSA', 'GUEMDJO', '2009-10-11', 'BAHAM', 'F', 'NOUBISSI JOSEPH', '677751013', NULL, 'DJUINGNE MARTINE', '675157833', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:03:19', '2025-08-30 06:03:19', 1, '25A00196', 4, 0),
(223, 'NGUIDJOL ADRIEN RYAN', 'ADRIEN RYAN', 'NGUIDJOL', '2011-01-20', 'DOUALA', 'M', 'BAKANG JACQUES', '694808497', NULL, 'NGO LOGA ANGELE MARIE ELISEE', '698290393', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:04:06', '2025-09-02 22:30:01', 1, '25A00197', 37, 0),
(224, 'KAMANI LEUMENI MARC NATHAN', 'MARC NATHAN', 'KAMANI LEUMENI', '2014-02-26', 'BATCHAM', 'M', 'LEUMENI BERNARD RODRIGUE', '696594220', NULL, 'TSAMENE DOUANLA CLARISSE AIMEE', '676570561', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:06:55', '2025-09-02 22:30:01', 1, '25A00198', 19, 0),
(225, 'MONTHE NDZINGA CHLOE MAEVA', 'CHLOE MAEVA', 'MONTHE NDZINGA', '2009-10-30', 'DOUALA', 'F', 'MONTHE JEAN BAUDOUIN', '697105520', NULL, 'ABOGO AMBANI ANTOINETTE', '680325010', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:08:41', '2025-08-30 06:08:41', 1, '25A00199', 3, 0),
(226, 'DONGMO LOYIE FADEL', 'FADEL', 'DONGMO LOYIE', '2003-07-02', 'FONGO-TONGO', 'M', 'ZATSA CHRETIEN', '670052957', NULL, 'TEUTENG SUZANNE', NULL, NULL, NULL, NULL, 63, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:09:42', '2025-08-30 06:09:42', 1, '25A00200', 1, 0),
(227, 'FRANCK CHRISTIAN MAKANDA', 'MAKANDA', 'FRANCK CHRISTIAN', '2013-07-15', 'DOUALA', 'M', 'MAKANDA MAKANDA FELIX', '696540135', NULL, 'WONSO ELISABETH MARION', '698896502', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:12:34', '2025-09-02 22:30:01', 1, '25A00201', 16, 0),
(228, 'JOEL JUNIOR BENOIT MOUNLOM', 'BENOIT MOUNLOM', 'JOEL JUNIOR', '2009-10-21', 'DOUALA', 'M', '.', NULL, NULL, 'NGO MOUNLOM MADELEINE CLEO', '682645898', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:13:07', '2025-08-30 06:13:07', 1, '25A00202', 8, 0),
(229, 'NSAM ATANGANA ULRICH FORLAN', 'ULRICH FORLAN', 'NSAM ATANGANA', '2010-11-01', 'DOUALA', 'M', 'ATANGANA ETOUNDI CHARLES BERTRAND', '670700220', NULL, 'EYENGA NGAN NADINE', '672008359', NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:15:22', '2025-08-30 06:15:22', 1, '25A00203', 2, 0),
(230, 'FOKA KENZA ESTHER CARA', 'ESTHER CARA', 'FOKA KENZA', '2013-03-08', 'DOUALA', 'F', 'FOKA KOAGNE FRANKLIN', NULL, NULL, 'DIKABO EKWALLA REGINE', '699720427', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:17:22', '2025-08-30 06:17:22', 1, '25A00204', 6, 0),
(231, 'MIRIENE MBURLI', 'MBURLI', 'MIRIENE', '2012-02-26', 'WAT HEALTH POST', 'F', 'TANTOH DIEUDONNE SHEY', NULL, NULL, 'TANTOH NADESH MANSAH', '678067017', NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:20:18', '2025-08-30 06:24:14', 1, '25A00205', 2, 0),
(232, 'NGUEWA BEGUIDE EMERAUDE', 'EMERAUDE', 'NGUEWA BEGUIDE', '2010-06-28', 'OMBESSA', 'F', 'FRANCIS BEGUIDE', '678937115', NULL, 'DJOUKA MADJOU SEGOLENE', NULL, NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:22:03', '2025-08-30 06:22:03', 1, '25A00206', 2, 0),
(233, 'ANGE FELIX MAKANDA', 'MAKANDA', 'ANGE FELIX', '2010-07-21', 'DOUALA', 'M', 'MAKANDA MAKANDA FELIX', '696540135', NULL, 'WONSO ELISABETH MARION', '698896502', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:22:55', '2025-08-30 06:22:55', 1, '25A00207', 9, 0),
(234, 'NGOTCHEU SANDJON ARMEL STAN', 'ARMEL STAN', 'NGOTCHEU SANDJON', '2012-09-17', 'DOUALA', 'M', 'ETOUNDI POUANI CLEMENT ERIC', '673107489', NULL, 'DJIKI SANDJO MICHAELLE CLAUDIA', '699597348', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:25:46', '2025-08-30 06:25:46', 1, '25A00208', 10, 0),
(235, 'DJUIDJE NGOUNOU FORTUNE', 'FORTUNE', 'DJUIDJE NGOUNOU', '2012-03-26', 'DOUALA', 'F', 'WABO KAMGA RODRIGUE', '694222394', NULL, 'MAWA REBECCA', '679335645', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:25:50', '2025-08-30 06:25:50', 1, '25A00209', 2, 0),
(236, 'EDOUBE ANNE MARIE PRISCA', 'ANNE MARIE PRISCA', 'EDOUBE', '2014-05-23', 'DEHANE', 'F', 'KAHE SERGES RENAUD', '698601198', NULL, 'EPASSI JOSEPHINE', '679110884', NULL, NULL, NULL, 71, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:28:35', '2025-08-30 06:28:35', 1, '25A00210', 2, 1),
(237, 'ASSOL YELLA JONES', 'YELLA JONES', 'ASSOL', '2010-06-26', 'YAOUNDE', 'F', '.', NULL, NULL, 'NGO MATIP VICTORINE', '676300062', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:29:25', '2025-08-30 06:29:25', 1, '25A00211', 2, 0),
(238, 'NGOMBELLE CELINE MERVEILLE', 'CELINE MERVEILLE', 'NGOMBELLE', '2014-11-20', 'DOUALA', 'F', 'HEN JULES FRANCOIS', NULL, NULL, 'SOPPE CHARLOTTE BRIGITTE', '677049579', NULL, NULL, NULL, 71, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:31:33', '2025-08-30 06:31:33', 1, '25A00212', 3, 1),
(239, 'BAADI ABDOUL AZIZ', 'ABDOUL AZIZ', 'BAADI', '2007-03-15', 'DOUALA', 'M', 'KOINA MAMOUDOU', '690824242', NULL, 'MAIMOUNOU OUMAROU', NULL, NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:32:51', '2025-09-02 22:30:54', 1, '25A00213', 2, 0),
(240, 'NTOOGUE MARIA JESSICA', 'MARIA JESSICA', 'NTOOGUE', '2014-07-10', 'DOUALA', 'F', 'NTOOGUE GILLES EBENEZER', '650666754', NULL, 'MINYEM ZINI GISELE', '679197411', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:34:23', '2025-09-02 22:30:01', 1, '25A00214', 38, 0),
(241, 'NTOOGUE SIMEON WILFRIED', 'SIMEON WILFRIED', 'NTOOGUE', '2014-07-10', 'DOUALA', 'M', 'NTOOGUE GILLES EBENEZER', '650666754', NULL, 'MINYEM ZINI GISELE', '679197411', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:38:20', '2025-09-02 22:30:01', 1, '25A00215', 39, 0),
(242, 'DANIELLE LIZ MAELLE OSSENDE .', '.', 'DANIELLE LIZ MAELLE OSSENDE', '2008-09-10', 'YAOUNDE', 'F', 'OSSENDE KAROL AURELIEN', '691113654', NULL, 'NGO ELOUGA CATHERINE', NULL, NULL, NULL, NULL, 41, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:41:17', '2025-08-30 06:41:17', 1, '25A00216', 1, 0),
(243, 'IBRAHIMA LAMINE', 'LAMINE', 'IBRAHIMA', '2006-08-31', 'BERTOUA', 'M', 'MOUHAMADOU LAMINE', '699037059', NULL, 'DJOULEYHATOU ALIM', '690585156', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:41:48', '2025-08-30 06:41:48', 1, '25A00217', 7, 0),
(244, 'MARIE JOSEPH THEOPHANE NGAH OSSENDE', 'NGAH OSSENDE', 'MARIE JOSEPH THEOPHANE', '2012-08-05', 'YAOUNDE', 'F', 'OSSENDE KAROL AURELIEN', '691113654', NULL, 'NGO  ELOUGA CATHERINE', NULL, NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:44:34', '2025-08-30 06:44:34', 1, '25A00218', 5, 0),
(245, 'DJUIDJIE CHOUPO SANDRA CHIMENE', 'SANDRA CHIMENE', 'DJUIDJIE CHOUPO', '2009-05-28', 'KAYO-BANDJOUN', 'F', 'CHOUPO YVE', '677646381', NULL, 'KENGNE SIDONIE', '656888665', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:44:55', '2025-08-30 06:44:55', 1, '25A00219', 5, 0),
(246, 'AÏSSATOU YAYA', 'YAYA', 'AÏSSATOU', '2008-07-07', 'DIBONG', 'F', 'YAYA MOSSI', NULL, NULL, 'NGO MBENOUN ESTHER', '658349169', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:47:56', '2025-08-30 06:48:13', 1, '25A00220', 2, 0),
(247, 'HELENE DOROTHEE OSSENDE .', '.', 'HELENE DOROTHEE OSSENDE', '2014-04-05', 'YAOUNDE', 'F', 'OSSENDE KAROL AURELIEN', '691113654', NULL, 'NGO ELOUGA CATHERINE', '698963214', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:48:04', '2025-08-30 06:48:04', 1, '25A00221', 11, 0),
(248, 'HASSANA YAYA', 'YAYA', 'HASSANA', '2005-11-08', 'DIBANG', 'F', 'YAYA MOSSI', NULL, NULL, 'NGO MBENOUN ESTHER', '658349169', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:50:24', '2025-08-30 06:50:24', 1, '25A00222', 4, 0),
(249, 'MEFANG GWLADYS DIANE', 'DIANE', 'MEFANG GWLADYS', '2007-04-09', 'YAOUNDE', 'F', '.', NULL, NULL, 'WOUMO JOSEPHINE', '696948157', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:53:12', '2025-08-30 06:53:12', 1, '25A00223', 8, 0),
(250, 'KWETCHOU GATOU SCHEKINA GREECY', 'SCHEKINA GREECY', 'KWETCHOU GATOU', '2013-07-15', 'DOUALA', 'F', 'GATORO ERIC CLOTIN', NULL, NULL, 'NJADJA MARGUERITE', '697541480', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:53:36', '2025-08-30 06:53:36', 1, '25A00224', 9, 0),
(251, 'ACHTA HASSIM', 'HASSIM', 'ACHTA', '2013-06-07', 'NGAOUNDERE', 'F', 'HASSI TAHIR', '653530405', NULL, 'HADJA KORE MOUSTAPHA', '670159751', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:57:12', '2025-09-02 22:30:01', 1, '25A00225', 1, 1),
(252, 'KENGNI TIOGUEP JOELLE', 'JOELLE', 'KENGNI TIOGUEP', '2008-08-03', 'DOUALA', 'F', 'TIOGUEP ROLLAND DIDIER', NULL, NULL, 'DAKAM ROSELINE', '699570588', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:58:05', '2025-08-30 06:58:05', 1, '25A00226', 5, 0),
(253, 'MARYAMOU YAYA', 'YAYA', 'MARYAMOU', '2012-03-31', 'MINDOUROU', 'F', 'YAYA MOSSI', NULL, NULL, 'NGO MBENOUN ESTHER', '658349169', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:00:07', '2025-08-30 07:04:43', 1, '25A00227', 6, 0),
(254, 'KAMEN NGUINA MAI PRINCESSE INNA', 'PRINCESSE INNA', 'KAMEN NGUINA MAI', '2012-03-31', 'DOUALA', 'F', 'NGUINA NGONO ERNEST', NULL, NULL, 'ABBE MAI IBRAHIM', '650545735', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:02:03', '2025-08-30 07:02:03', 1, '25A00228', 6, 0),
(255, 'OUSSENA YAYA', 'YAYA', 'OUSSENA', '2005-11-08', 'DIBANG', 'F', 'YAYA MOSSI', NULL, NULL, 'NGO MBENOUN ESTHER', '658349169', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:03:15', '2025-08-30 07:03:15', 1, '25A00229', 3, 0),
(256, 'INIYEMI OBAMA JESSICA DANIELLA', 'JESSICA DANIELLA', 'INIYEMI OBAMA', '2012-08-02', 'DOUALA', 'F', 'SAMBA OBAMA PIERRE ROMEO', '677636142', NULL, 'LENANEN WAMBA LUCIE', '690468328', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:06:39', '2025-08-30 07:06:39', 1, '25A00230', 2, 0),
(257, 'NDOUM BISSOHONG EVELINE DIVINE', 'EVELINE DIVINE', 'NDOUM BISSOHONG', '2008-03-18', 'DOUALA', 'F', 'NSOGAN CYRILLE', '675300593', NULL, 'BISSOHONG LAURIETTE MERCIEL', '699930677', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:09:16', '2025-08-30 07:09:16', 1, '25A00231', 13, 0),
(258, 'NGUENFO JOSEPHINE', 'JOSEPHINE', 'NGUENFO', '2011-08-04', 'DSCHANG', 'F', '.', NULL, NULL, 'KEMOGNE KANGUET', '653808204', NULL, NULL, NULL, 34, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:10:55', '2025-08-30 07:10:55', 1, '25A00232', 1, 0),
(259, 'HAFSATOU EL SANI', 'EL SANI', 'HAFSATOU', '2009-07-18', 'DOUALA', 'F', 'MAHAMADOU SANI', '696180077', NULL, 'AWA OUSSEINI', '693201674', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:13:00', '2025-08-30 07:13:00', 1, '25A00233', 6, 0),
(260, 'MIMESSE NTOUOU MARIE IVANA', 'MARIE IVANA', 'MIMESSE NTOUOU', '2007-11-17', 'AMBAM', 'F', 'ESSONO XAVIER', '677774183', NULL, '.', NULL, NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:14:22', '2025-08-30 07:14:22', 1, '25A00234', 4, 0),
(261, 'HAWAOU ALI', 'ALI', 'HAWAOU', '2007-04-17', 'BERTOUA', 'F', 'ALI IYAWA', '699037059', NULL, 'MAHAMOU', '690585156', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:16:47', '2025-08-30 07:16:47', 1, '25A00235', 11, 0),
(262, 'MELI MADELEINE IGORETTE', 'MADELEINE IGORETTE', 'MELI', '2007-06-30', 'BANGANG', 'F', 'WABO DJONKAM FRANCKLINM', '695006785', NULL, 'DJIFACK PAULINE', '673188371', NULL, NULL, NULL, 104, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:19:04', '2025-08-30 07:19:04', 1, '25A00236', 1, 0),
(263, 'MANGA JUIKO LORS MORENA', 'LORS MORENA', 'MANGA JUIKO', '2010-03-25', 'MFOU', 'F', 'TCHUALCK HAPPI GISLAIN', '654454443', NULL, 'TCHUETCHOUA SUZANNE FRIDE', '655737670', NULL, NULL, NULL, 41, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:20:55', '2025-08-30 07:20:55', 1, '25A00237', 2, 0),
(264, 'MOHAMADOU MANSOUR', 'MANSOUR', 'MOHAMADOU', '2004-01-01', 'DOUALA', 'M', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:23:15', '2025-08-30 07:23:15', 1, '25A00238', 6, 0),
(265, 'MONTHE EYENGA BERTHE ASHLEY', 'BERTHE ASHLEY', 'MONTHE EYENGA', '2014-11-05', 'DOUALA', 'F', 'MONTHE PADJI JEAN BEAUDOUIN', '697105520', NULL, 'ABOGO AMBANI ANTOINETTE', '680325010', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:25:26', '2025-09-02 22:30:01', 1, '25A00239', 31, 0),
(266, 'KWEDI NGUEGNA ANGE', 'ANGE', 'KWEDI NGUEGNA', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:26:36', '2025-08-30 07:26:36', 1, '25A00240', 5, 0),
(267, 'LE ROI DAVID WANG .', '.', 'LE ROI DAVID WANG', '2012-09-15', 'NANGA', 'M', 'WANG GEORGES RAOUL', '693744613', NULL, 'NATEUCBE', NULL, NULL, NULL, NULL, 77, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:29:00', '2025-08-30 07:29:00', 1, '25A00241', 2, 0),
(268, 'ALBERTO VIANNEY YEBEGA', 'YEBEGA', 'ALBERTO VIANNEY', '2008-06-23', 'DOUALA', 'M', 'YEBEGA ALBERT', NULL, NULL, 'NGO BIKOND', '699829076', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:33:10', '2025-08-30 07:33:10', 1, '25A00242', 8, 0),
(269, 'ALBERTO VIANNEY YEBEGA', 'YEBEGA', 'ALBERTO VIANNEY', '2008-06-23', 'DOUALA', 'M', 'YEBEGA ALBERT', NULL, NULL, 'NGO BIKOND', '699829076', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:37:29', '2025-08-30 07:37:29', 1, '25A00243', 9, 0),
(270, 'SIMO TETO AUDRE MEGANE', 'AUDRE MEGANE', 'SIMO TETO', '2003-07-01', 'DOUALA', 'F', 'TETO JULES PASCAL', '699992698', NULL, 'MECHE CHRISTIANE', '699925280', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:41:16', '2025-08-30 07:41:16', 1, '25A00244', 10, 0),
(271, 'MAIMUNA IBRAHIM', 'IBRAHIM', 'MAIMUNA', '2013-01-02', 'ESSU', 'F', 'IBRAHIM MOHAMED', '699992698', NULL, 'HAPSATOU HABUBAKAR', '699625280', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:47:21', '2025-08-30 07:47:21', 1, '25A00245', 4, 0),
(272, 'NGO LIMEN SABINE', 'SABINE', 'NGO LIMEN', '2005-03-04', 'LOGMANDENG-NDOM', 'F', 'LIMEN DOMINIQUE', '678538455', NULL, 'NGO TONYE HELENE', '690442104', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:51:31', '2025-08-30 07:51:31', 1, '25A00246', 5, 0),
(273, 'BAYIHA EPANDA GENEVIEVE', 'GENEVIEVE', 'BAYIHA EPANDA', '2010-02-01', '.', 'F', 'EPANDA JEAN', '699307388', NULL, 'BAJILA GENEVIEVE', '699691855', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:55:38', '2025-08-30 07:55:38', 1, '25A00247', 7, 0),
(274, 'ESSANGUI LEA ALEXIA', 'LEA ALEXIA', 'ESSANGUI', '2008-06-27', 'MBANGA', 'F', 'NGODY MBONGUE MATHURIN', NULL, NULL, 'MOUKOURI Epse MBONGUE SANDRINE', '699454728', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:58:46', '2025-08-30 07:58:46', 1, '25A00248', 3, 0),
(275, 'YOUBI KAMDEM FORLAN', 'FORLAN', 'YOUBI KAMDEM', '2010-09-30', 'DOUALA', 'M', 'KAMDEM YOUBI ALAIN', '672463510', NULL, 'MANE MIREILLE', NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:00:11', '2025-08-30 08:00:11', 1, '25A00249', 14, 0),
(276, 'HAMOUA ABOUBAKAR ABDOUL-RAZACK', 'ABDOUL-RAZACK', 'HAMOUA ABOUBAKAR', '2012-08-15', 'IDOOL', 'M', 'IYA MOHAMAN BELLO', '675253631', NULL, 'ADDA HAPSATOU', '697703575', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:24:57', '2025-09-02 22:30:01', 1, '25A00250', 17, 0),
(277, 'MBASSI MURIELLE DORIANE', 'MURIELLE DORIANE', 'MBASSI', '2008-05-17', 'DOUALA', 'F', 'MBASSI EDOUMA ABDON', '697104866', NULL, 'MOKA AMBADIANG JUDITH PATIENCE', '699185216', NULL, NULL, NULL, 107, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:35:41', '2025-08-30 08:35:41', 1, '25A00251', 2, 0),
(278, 'TONKA MOKA NATIVIDAD', 'NATIVIDAD', 'TONKA MOKA', '2012-12-24', 'LUBA-GUINEE EQUATORIAL', 'F', '.', NULL, NULL, 'MOKA OKODOMBE MONIQUE BEPENGERE', '699185216', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:38:51', '2025-08-30 08:38:51', 1, '25A00252', 3, 0),
(279, 'WAFO KOUOMEGNE FABRICE VAILLANT', 'FABRICE VAILLANT', 'WAFO KOUOMEGNE', '2008-04-14', 'DOUALA', 'M', 'KOUOMEGNE PIERRE', '654338827', NULL, 'DJUIGNE MADELEINE', '657998780', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:52:52', '2025-08-30 08:52:52', 1, '25A00253', 15, 0),
(280, 'MOUHAMED ABDOU BOUBA', 'BOUBA', 'MOUHAMED ABDOU', '2014-08-08', 'DOUALA', 'M', 'ABDOU BOUBA', '699870592', NULL, 'DJARA DAOUDA', '671313107', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:56:16', '2025-09-02 22:30:01', 1, '25A00254', 32, 0),
(281, 'YAYA ABDOU', 'ABDOU', 'YAYA', '2011-01-10', 'WAZA', 'M', '.', '699870592', NULL, 'DJORA DAOUDA', '671313107', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:58:16', '2025-08-30 08:58:16', 1, '25A00255', 12, 0),
(282, 'ABDOU BOUBA BACHIR', 'BACHIR', 'ABDOU BOUBA', '2013-05-08', 'DOUALA', 'M', 'ABDOU BOUBA', '699870592', NULL, 'DJARA DAOUDA', '671313107', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:00:13', '2025-08-30 09:00:13', 1, '25A00256', 7, 0),
(283, 'DIDJA ABDOU .', '.', 'DIDJA ABDOU', '2008-08-18', 'WAZA', 'F', '.', '699870592', NULL, 'DJARA DAOUDA', '671313107', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:02:30', '2025-08-30 09:02:30', 1, '25A00257', 10, 0),
(284, 'MEFANG GWLADYS DIANE', 'DIANE', 'MEFANG GWLADYS', '2007-04-09', 'YAOUNDE', 'F', '.', NULL, NULL, 'WOUMO JOSEPHINE', '696948157', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:14:02', '2025-08-30 09:14:02', 1, '25A00258', 7, 0),
(285, 'ZANG OWONO PATRICK ENZO', 'PATRICK ENZO', 'ZANG OWONO', '2007-03-14', 'DOUALA', 'M', 'POOK JOSEPH RICHARD', '699252023', NULL, 'MIMBE THERESE', '677155031', NULL, NULL, NULL, 110, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:16:27', '2025-08-30 09:16:27', 1, '25A00259', 1, 0),
(286, 'DZUIKUI TCHINDA DUCHESSE LUCIANE', 'DUCHESSE LUCIANE', 'DZUIKUI TCHINDA', '2015-06-04', 'BABETE', 'F', 'TCHINDA ARMAND', '677424549', NULL, 'MAKEM TCHINDA ROSINE', '654988942', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:38:13', '2025-08-30 09:38:13', 1, '25A00260', 10, 0),
(287, 'KEUMAYOU TCHANA MAYELLE ARCHANGE', 'MAYELLE ARCHANGE', 'KEUMAYOU TCHANA', '2014-06-29', 'DOUALA', 'F', 'TCHANA RODRIGUE', '694472827', NULL, 'MOUTO EPISSI AGNES', '656718831', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:58:59', '2025-08-30 09:58:59', 1, '25A00261', 11, 0),
(288, 'WONDJA EPISSI ANAYEL', 'ANAYEL', 'WONDJA EPISSI', '2013-01-04', 'MANJO', 'F', '.', '694472827', NULL, 'KALATI ROSE', '656718831', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 10:01:18', '2025-08-30 10:01:18', 1, '25A00262', 12, 0),
(289, 'APOUZA MESSIRENI BRIANA', 'BRIANA', 'APOUZA MESSIRENI', '2013-02-15', 'DOUALA', 'F', 'MESSIRENI', '.', NULL, 'NGOUMPECHIO LAURA', '675816390', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 10:19:37', '2025-09-02 22:30:01', 1, '25A00263', 6, 0),
(290, 'NOBOP MESSIRENI DAVILA', 'DAVILA', 'NOBOP MESSIRENI', '2011-03-23', 'DOUALA', 'F', 'MESSIRENI', NULL, NULL, 'NGOUMPECHIO LAURA', '675816390', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 10:21:55', '2025-08-30 10:21:55', 1, '25A00264', 8, 0),
(291, 'MESSINA GEORETTE BRITANIE', 'GEORETTE BRITANIE', 'MESSINA', '2014-03-28', 'ATOK', 'F', 'NIEPANG NKOT CLOVIS', NULL, NULL, 'ENGAMB NDJOH  TATIANA LAURE', '698892798', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 05:38:08', '2025-09-02 22:30:01', 1, '25A00265', 28, 0),
(292, 'NOUGUEP MANOELA PRINCESSE', 'MANOELA PRINCESSE', 'NOUGUEP', '2009-12-19', 'DOUALA', 'F', 'SOUOP SERGE', '677751429', NULL, 'DOMGAP LEOLODINE', '677488891', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 06:02:29', '2025-09-01 06:02:29', 1, '25A00266', 12, 0),
(293, 'NDOUENGAM EXTHER SARINA', 'EXTHER SARINA', 'NDOUENGAM', '2012-04-28', 'DOUALA', 'F', 'NDOUENGAM  COLLINS BRUNO', NULL, NULL, 'MEGNANG ABIDIAS VICTORINE NADEGE', 'MEGNANG NADEGE', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:10:18', '2025-09-01 07:10:18', 1, '25A00267', 9, 0),
(294, 'NZIKOUO NDOUENGAM MIRABELLE RHODE', 'MIRABELLE RHODE', 'NZIKOUO NDOUENGAM', '2014-07-01', 'DOUALA', 'F', 'NDOUENGAM COLLINS BRUNO', NULL, NULL, 'MEGNANG ABIDIAS VICTORINE NADEGE', '699250088', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:15:51', '2025-09-02 22:30:01', 1, '25A00268', 42, 0),
(295, 'EMAGUE AYINA BRAYAN', 'BRAYAN', 'EMAGUE AYINA', '2010-11-10', 'SOUZA GARE', 'M', 'EMAGUE JEAN GUY', NULL, NULL, 'AYINA AYINA COLLETTE', '658670639', NULL, NULL, NULL, 70, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:21:20', '2025-09-01 07:21:20', 1, '25A00269', 2, 0),
(296, 'EVELE AHMET', 'AHMET', 'EVELE', '2009-07-08', 'DOUALA', 'M', 'ABDOU AFIDI', '699343566', NULL, 'ZENBOU ABDOULAYE', '.', NULL, NULL, NULL, 81, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:26:23', '2025-09-01 07:26:23', 1, '25A00270', 1, 0),
(297, 'MAYACKI JOVANNA KAELLA', 'JOVANNA KAELLA', 'MAYACKI', '2010-04-02', 'DOUALA', 'F', 'MAYACKI JEAN CALVI N', '677550384', NULL, 'NGOYEM ADELE GERMAINE', '.', NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:36:50', '2025-09-01 07:43:16', 1, '25A00271', 3, 0),
(298, 'NTSAMA ONGUENE BENEDICTE', 'BENEDICTE', 'NTSAMA ONGUENE', '2007-08-12', 'DOUALA', 'F', 'ONGUENE SEVERIN', '696384593', NULL, 'NGONO FRANCOISE', '696384593', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:39:11', '2025-09-02 22:30:54', 1, '25A00272', 13, 0),
(299, 'POMOU DEUNGA CLAUDE AUDREE PRINCESSE', 'CLAUDE AUDREE PRINCESSE', 'POMOU DEUNGA', '2006-03-21', 'DOUALA', 'F', 'NJOPTCHOUANG BLAISE', '691286418', NULL, 'DJAMBOU JUSTINE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:45:35', '2025-09-02 22:30:54', 1, '25A00273', 14, 0),
(300, 'MAKOUNE MANDJI DAINA DIVINE', 'DAINA DIVINE', 'MAKOUNE MANDJI', '2012-10-08', 'BASSOUGOUM', 'F', 'MANDJI HENRI JOEL', '674471322', NULL, 'MAGAM SIGHE CHANCELLE GAELLE', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:50:55', '2025-09-01 07:50:55', 1, '25A00274', 16, 0),
(301, 'MINKEU NANA DORCAS JESSICA', 'DORCAS JESSICA', 'MINKEU NANA', '2013-09-23', 'MANJO', 'F', 'NANA CESAIR', '676920227', NULL, 'MBAKOP NATHALIE', '650475223', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:57:05', '2025-09-01 07:57:05', 1, '25A00275', 10, 0),
(302, 'ATEBA BANDOLO TRACY SYLVANA', 'TRACY SYLVANA', 'ATEBA BANDOLO', '2011-09-29', 'NGAOUNDERE', 'F', 'ATEBA', '693312261', NULL, 'MADANY SALE', '675943201', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:01:20', '2025-09-01 08:01:20', 1, '25A00276', 17, 0),
(303, 'BOSSOUNG PEFOUHO BLESSING SHARON', 'BLESSING SHARON', 'BOSSOUNG PEFOUHO', '2015-01-02', 'BABADJOU', 'F', 'PEFOUHO SEME DANY', '656813995', NULL, 'MENEGHA LOCDJINO ALLIANCE', '.', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:07:24', '2025-09-02 22:30:01', 1, '25A00277', 10, 0),
(304, 'AYANGMA NTSOH PRINCE CABREL', 'PRINCE CABREL', 'AYANGMA NTSOH', '2014-07-08', 'DOUALA', 'M', 'ONANINA PIERRE PASCAL', '676046421', NULL, 'BOSONG MARTHE VIVIANE', '.', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:11:43', '2025-09-01 08:11:43', 1, '25A00278', 13, 0),
(305, 'MPOT DIDERLINE', 'DIDERLINE', 'MPOT', '2011-11-05', 'BERTOUA', 'F', 'KARDE PIERRE', '696087033', NULL, 'ALONDO MARIE GULLE', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:25:19', '2025-09-01 08:25:19', 1, '25A00279', 18, 0),
(306, 'AKONO MEBA DIVIN', 'DIVIN', 'AKONO MEBA', '2012-11-04', 'NKOLOTOUTOU', 'F', 'MEBA EFORA THIERRY DAVID', '.', NULL, 'EBOUTOU AKONO SANDRINE', '650865739', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:52:23', '2025-09-01 09:03:26', 1, '25A00280', 13, 0),
(307, 'MAKOK ONDOA AGATHE LESLIE', 'AGATHE LESLIE', 'MAKOK ONDOA', '2012-01-23', 'DOUALA', 'F', 'ONDOA ANDRE MARIE MAVE', '682193243', NULL, 'MAKOK MARIE FRANCOISE', '.', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:56:30', '2025-09-01 08:56:30', 1, '25A00281', 7, 0),
(308, 'UM BITJEL MARCELIN ROMARIC', 'MARCELIN ROMARIC', 'UM BITJEL', '2011-11-16', 'BOUMNYEBEL', 'M', 'BASSAMA EMMANUEL', '695384967', NULL, 'NGO NSEGBE PEBORAH L\'OR', '.', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 09:02:15', '2025-09-01 09:02:15', 1, '25A00282', 3, 0),
(309, 'ESSOME CHEWO ANGE VANELLE', 'ANGE VANELLE', 'ESSOME CHEWO', '2013-06-21', 'DOUALA', 'F', 'ESSOME ESSOME CHRISTIAN JOEL', '.', NULL, 'GUIADEM SIDOINE', '657614230', NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 09:19:32', '2025-09-01 09:19:32', 1, '25A00283', 3, 0),
(310, 'BIBOUM BONDJE MARINA PAOLA DELPHINE', 'MARINA PAOLA DELPHINE', 'BIBOUM BONDJE', '2008-01-05', 'DOUALA', 'F', '.', '.', NULL, 'ANNE NICAISE BONDJE', '+33605912130', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 09:40:11', '2025-09-01 09:40:11', 1, '25A00284', 19, 0),
(311, 'KEMDJO TOCHE MILEINE CABREL', 'MILEINE CABREL', 'KEMDJO TOCHE', '2008-03-05', 'DOUALA', 'F', 'TOCHE AIME PHILIPPE', '699412647', NULL, 'TOCHE SYLVIE FLORE', '665000000', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:07:09', '2025-09-01 10:07:09', 1, '25A00285', 5, 0),
(312, 'SITCHOM JOACHIM', 'JOACHIM', 'SITCHOM', '2015-11-22', 'DOUALA', 'M', 'SITCHOM YANNICK STEPHANE', '682224688', NULL, 'MAKUSSU PASCALINE', '699577289', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:11:40', '2025-09-12 11:20:35', 1, '25A00286', 42, 0),
(313, 'AWE NGOMNA STEPHANE', 'STEPHANE', 'AWE NGOMNA', '2009-12-19', 'DOUALA', 'M', 'NGOMNA JEAN PAUL', '699313870', NULL, 'NDIKWA SUZANNE', '658462011', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:23:47', '2025-09-01 10:23:47', 1, '25A00287', 11, 0),
(314, 'LIBAM MALLONG BENOIT BRICE', 'BENOIT BRICE', 'LIBAM MALLONG', '2012-04-03', 'DOUALA', 'M', 'BAYIHA BAKIDI  BASILE', '691819821', NULL, 'NGO MALLONG ADELE GHANDI', '656577620', NULL, NULL, NULL, 47, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:41:20', '2025-09-01 10:41:20', 1, '25A00288', 2, 0),
(315, 'NGO MALLONG BAYIHA MARIE RAPHAELLE', 'BAYIHA MARIE RAPHAELLE', 'NGO MALLONG', '2010-11-30', 'DOUALA', 'F', 'BAYIHA BAKIDI BASILE', '691819821', NULL, 'NGO MALLONG ADELE GHANDI', '656577620', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:45:22', '2025-09-01 10:50:39', 1, '25A00289', 20, 0),
(316, 'DONG DONG TOSTANIE NICAISE', 'TOSTANIE NICAISE', 'DONG DONG', '2011-02-07', 'DONENKENG', 'F', 'DONG ANGON PAUL CREPAIN', '659723813', NULL, 'MEFOUMA ABAMA JOSEPHINE', '.', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:56:56', '2025-09-01 10:56:56', 1, '25A00290', 6, 0),
(317, 'SANDJON LINDA VIRGINIE GLOIRE', 'LINDA VIRGINIE GLOIRE', 'SANDJON', '2007-09-08', 'YAOUNDE', 'F', 'YABOU', '655488332', NULL, 'SIMO FANNY', '.', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:04:54', '2025-09-01 12:04:54', 1, '25A00291', 13, 0),
(318, 'ZEINAB LATIFA GAMBO', 'LATIFA GAMBO', 'ZEINAB', '2010-08-17', 'DOUALA', 'F', 'GAMBO CHAÏBOU IBRAHIM', '694752505', NULL, 'HAWA MAMADOU', '655627890', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:10:35', '2025-09-10 04:04:49', 1, '25A00292', 3, 0),
(319, 'LEMBOIGNY SENGOUA SHAMA JOY', 'SHAMA JOY', 'LEMBOIGNY SENGOUA', '2012-11-09', 'DOUALA', 'F', 'SENGOUA HERMANN', '676024631', NULL, 'BAHONO AMELIE LAURE', '676873942', NULL, NULL, NULL, 78, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:18:00', '2025-09-01 12:18:00', 1, '25A00293', 1, 0),
(320, 'TAGOUFO KENNE ANGE MERVEILLE', 'ANGE MERVEILLE', 'TAGOUFO KENNE', '2014-01-17', 'NGAOUNDERE', 'F', 'TAGOUFO ALAIN VALERE', '699903619', NULL, 'TAYONT LONCHI ESTHER', '672007557', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:19:23', '2025-09-01 12:19:23', 1, '25A00294', 14, 0),
(321, 'TIOTSOP VANELLE DANIELLA', 'VANELLE DANIELLA', 'TIOTSOP', '2015-03-16', 'BALENG', 'F', 'WOBNDJOH SAMUEL', '699903619', NULL, 'NTSAPI NZEUMEKEM SANDRA', '672007557', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:24:25', '2025-09-12 11:20:27', 1, '25A00295', 41, 0),
(322, 'TAGOUFO FOPA DANIEL', 'DANIEL', 'TAGOUFO FOPA', '2012-09-30', 'NGAOUNDERE', 'M', 'TAGOUFO ALAIN VALERE', '699903619', NULL, 'TAYONT LONCHI PULCHERIE', '672007557', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:27:44', '2025-09-01 12:27:44', 1, '25A00296', 3, 0),
(323, 'KEGNIA EMMANUEL CHRIST', 'EMMANUEL CHRIST', 'KEGNIA', '2014-01-18', 'DOUALA', 'M', 'NYABEYE PANGOU FIRMIN', '696108651', NULL, 'KENGNIA ARMANDA', '650660336', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:32:23', '2025-09-01 12:32:23', 1, '25A00297', 13, 0),
(324, 'MESSACK SANDRA', 'SANDRA', 'MESSACK', '2006-08-08', 'BALENG -KONTI', 'F', 'NEABIN JEAN-BAPTISTE', '671850647', NULL, 'MADJUI EMMERENCE', '691086039', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:46:31', '2025-09-01 12:46:31', 1, '25A00298', 21, 0),
(325, 'BIKIE NGUELE MARIE JEANNE', 'MARIE JEANNE', 'BIKIE NGUELE', '2009-01-28', 'YAOUNDE', 'F', 'NDI NGUELE RICHARD', '.', NULL, 'NDZIE MVOND BERNADETTE ESTELLE', '656531950', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:54:29', '2025-09-01 12:54:29', 1, '25A00299', 4, 0),
(326, 'MOUKAM YOUALEU MICHEL JASON', 'MICHEL JASON', 'MOUKAM YOUALEU', '2012-07-20', 'DOUALA', 'M', 'MOUKAM MICHEL MAGLOIRE', '670633015', NULL, 'DEUDJUI IVETTE', '696669915', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 13:04:48', '2025-09-01 13:04:48', 1, '25A00300', 6, 0),
(328, 'KAMBOU ARCHANGE JOYCE', 'ARCHANGE JOYCE', 'KAMBOU', '2014-05-15', 'DOUALA', 'F', 'FEUKAM SAGOU RODRIGUE MICHEL', '672816719', NULL, 'SAGOU BERJINETTE', '693497342', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:27:08', '2025-09-02 04:27:08', 1, '25A00301', 8, 0),
(329, 'MBEUHEM ABESSANG ARON KLOE', 'ARON KLOE', 'MBEUHEM ABESSANG', '2013-07-17', 'TONGANG', 'M', 'MBEUHEM KAMTE NARCISSE', '670223268', NULL, 'MIGUIM ABESSANG CHRISTELLE', '679211528', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:32:20', '2025-09-02 04:32:20', 1, '25A00302', 11, 0),
(330, 'BENJON TSAFACK JOSEPHINE', 'JOSEPHINE', 'BENJON TSAFACK', '2008-10-28', 'DOUALA', 'F', 'BENJON', '673045514', NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:35:30', '2025-09-02 04:35:30', 1, '25A00303', 14, 0),
(331, 'EBONG TCHUISSEU ANDREA KRISTY', 'ANDREA KRISTY', 'EBONG TCHUISSEU', '2009-09-19', 'DOUALA', 'F', 'TCHUISSEU NKONDAH FREDERIC', '696521354', NULL, 'MBITCHA ANNE BERTHE', '672968269', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:38:42', '2025-09-02 22:30:54', 1, '25A00304', 6, 0),
(332, 'KOUMA BOULI RYAN DARIEL', 'RYAN DARIEL', 'KOUMA BOULI', '2011-01-05', 'DOUALA', 'M', 'BOULI PIERRE PAUL', '686824254', NULL, 'EKANDO MIRIOLLE FLAVIE', '675202112', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:42:27', '2025-09-02 04:42:27', 1, '25A00305', 8, 0),
(333, 'NDZANA NGAH JEANNE ESTHER', 'JEANNE ESTHER', 'NDZANA NGAH', '2008-07-06', 'DOUALA', 'F', 'NGAH FREDERIC', '653977709', NULL, 'NGANA MENAMAGA ANTOINETTE FLORE', '.', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:45:31', '2025-09-02 04:45:31', 1, '25A00306', 8, 0),
(334, 'TITCHO YENGOUA MARTHE LAUREINA', 'MARTHE LAUREINA', 'TITCHO YENGOUA', '2010-04-30', 'BAHAM', 'F', 'TITCHO', '659812944', NULL, NULL, NULL, NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:49:29', '2025-09-02 04:49:29', 1, '25A00307', 3, 0),
(335, 'SANI ALHADJI ADAMOU', 'ALHADJI ADAMOU', 'SANI', '2008-01-19', 'YAOUNDE', 'M', 'ADAMOU MOUHAMADOU', '655605530', NULL, 'AMINATOU ILIASSOU', NULL, NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:53:51', '2025-09-02 04:53:51', 1, '25A00308', 11, 0),
(336, 'NZEUMENI DAHEU FLORINDA', 'FLORINDA', 'NZEUMENI DAHEU', '2006-06-11', 'DOUALA', 'F', 'NZEUMENI DIMITRIC', '696941326', NULL, 'NTANIMI NJINOU AFLIDETTE', '673606254', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:57:55', '2025-09-02 04:57:55', 1, '25A00309', 6, 0),
(337, 'MFEUGUE FOE SIMONIE', 'SIMONIE', 'MFEUGUE FOE', '2010-03-10', 'DOUALA', 'F', 'FOE PATRICE', '691030491', NULL, 'NDOMTCHENG Epse FOE DENISE', NULL, NULL, NULL, NULL, 47, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:03:04', '2025-09-02 07:44:20', 1, '25A00310', 3, 0),
(338, 'NKIE URSBRIGHT ESEGEMU', 'ESEGEMU', 'NKIE URSBRIGHT', '2012-03-10', 'DOUALA', 'F', 'NKIE', '699797990', NULL, NULL, NULL, NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:06:29', '2025-09-02 05:06:29', 1, '25A00311', 4, 0),
(339, 'NGO NLEND CHRISTIANETTE', 'CHRISTIANETTE', 'NGO NLEND', '2025-07-11', 'DOUALA', 'F', '.', '682887448', NULL, 'NGO TONYE THERESE', '656587017', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:14:35', '2025-09-02 05:14:35', 1, '25A00312', 22, 0),
(340, 'EYENGA EKOUMA YOLANDE RISPA', 'YOLANDE RISPA', 'EYENGA EKOUMA', '2012-07-29', 'NDAMVO', 'F', 'EKOUMA AMBASSA', '676597753', NULL, 'MBANA EBOGO MARCELLE', '655334911', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:20:35', '2025-09-02 05:20:35', 1, '25A00313', 9, 0),
(341, 'MEVA\'A JUNIOR', 'JUNIOR', 'MEVA\'A', '2012-12-29', 'MBANKOMO', 'M', 'MOTO ABATHE JEAN CLAUDE', '657371919', NULL, 'NYANGONO CHRISTELLE', '682744482', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:26:39', '2025-09-02 22:30:01', 1, '25A00314', 29, 1),
(342, 'SIANTOU DJIETCHEU YOLANN PETRINA', 'YOLANN PETRINA', 'SIANTOU DJIETCHEU', '2011-03-23', 'NGOUSSO-YAOUNDE', 'F', 'DJIETCHEU NGOUAMBE SERAPHIN', '698327483', NULL, 'BOUEMANI SICKAM CHIMAINE BERLINE', '695066177', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:30:39', '2025-09-02 05:30:39', 1, '25A00315', 23, 0),
(343, 'KWAKEP NZEUMENI AURELIEN WILFRIED', 'AURELIEN WILFRIED', 'KWAKEP NZEUMENI', '2010-05-10', 'DOUALA', 'M', 'NZEUMENI DIMITRIC', '673606254', NULL, 'NTAMINI NJINOU AFLIDETTE', '696941326', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:33:47', '2025-09-02 05:33:47', 1, '25A00316', 9, 0),
(344, 'YOTA PRINCESSE NASIRA', 'PRINCESSE NASIRA', 'YOTA', '2014-03-18', 'BALATCHI', 'F', 'TIOYO MICHEL', NULL, NULL, 'DOUANLA TOBOUO ROSINE', '670061951', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:44:09', '2025-09-02 05:44:09', 1, '25A00317', 14, 0),
(345, 'NGO LIKENG MIREILLE', 'MIREILLE', 'NGO LIKENG', '2014-09-09', 'ESEKA', 'F', 'ANGOH FREDY', NULL, NULL, 'PAGBE ELOMA CHRISTELLE', '693095778', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:47:12', '2025-09-02 22:30:01', 1, '25A00318', 36, 0),
(346, 'ELOMA JEAN EMMANUEL YVAN', 'JEAN EMMANUEL YVAN', 'ELOMA', '2008-12-07', 'YAOUNDE', 'M', 'ANGOH FREDY', NULL, NULL, 'PAGBE ELOMA CHRISTELLE', '693095778', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:50:36', '2025-09-02 05:50:36', 1, '25A00319', 8, 0),
(347, 'MEFANG OLIVIA', 'OLIVIA', 'MEFANG', '2008-02-16', 'DIMAKO', 'F', 'YOUDOM NGOMSI', '698066798', NULL, 'AZONG LILIE STELLA', '677608495', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:23:53', '2025-09-02 06:23:53', 1, '25A00320', 4, 0),
(348, 'BEA LOUISE CHELISSA', 'LOUISE CHELISSA', 'BEA', '2010-10-26', 'BOT-MAKAK', 'F', 'TENLEP EMMANUEL', '699582328', NULL, 'MVONDO BIWOLIE', NULL, NULL, NULL, NULL, 73, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:40:42', '2025-09-02 06:40:42', 1, '25A00321', 2, 0),
(349, 'NGAN NGAN PAUL  FRIJOLITO', 'PAUL  FRIJOLITO', 'NGAN NGAN', '2004-01-01', 'DOUALA', 'M', 'TENLEP EMMANUEL', '699582328', NULL, 'MVONDO BIWOLIE', NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:42:35', '2025-09-02 06:42:35', 1, '25A00322', 14, 0),
(350, 'NGANDO JEAN DANIEL', 'JEAN DANIEL', 'NGANDO', '2012-03-18', 'YAOUNDE', 'M', 'NGANDO JEAN', '697692851', NULL, 'ATAMA ROSINE', '695058807', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:50:31', '2025-09-02 06:50:31', 1, '25A00323', 15, 0),
(351, 'MALE CELESTIN NIDELE', 'NIDELE', 'MALE CELESTIN', '2008-04-16', 'BAMESSO', 'F', 'PEUBOU ALEXANDRE', '695286548', NULL, 'DJUINE CELINE', '.', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:54:16', '2025-09-02 06:54:16', 1, '25A00324', 6, 0),
(352, 'FINKAM EVANA PASCALINE', 'EVANA PASCALINE', 'FINKAM', '2004-01-11', 'DOUALA', 'F', 'FOKAM EMMANUEL', '.', NULL, 'TCHUINMEGNE BEATRICE', '659992595', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:05:35', '2025-09-02 22:30:54', 1, '25A00325', 7, 0),
(353, 'HAKO KAMENI CAROLE', 'CAROLE', 'HAKO KAMENI', '2005-10-08', 'DOUALA', 'F', 'KAMENI DENIS', '677624190', NULL, 'DJOMALEU AGATHE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:24:49', '2025-09-02 22:30:54', 1, '25A00326', 9, 0),
(354, 'YIMGNIA MERVEILLE LAURE', 'MERVEILLE LAURE', 'YIMGNIA', '2007-04-08', 'BANTOUM', 'F', 'KWATCHET ROSTAND', '.', NULL, 'PETGA DIANE', '678260979', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:44:01', '2025-09-02 07:44:01', 1, '25A00327', 15, 0);
INSERT INTO `students` (`id`, `name`, `first_name`, `last_name`, `date_of_birth`, `place_of_birth`, `gender`, `parent_name`, `parent_phone`, `parent_email`, `mother_name`, `mother_phone`, `address`, `photo`, `subname`, `class_series_id`, `email`, `student_status`, `phone_number`, `birthday`, `birthday_place`, `sex`, `father_name`, `profession`, `status`, `is_new`, `is_active`, `created_at`, `updated_at`, `school_year_id`, `student_number`, `order`, `has_scholarship_enabled`) VALUES
(355, 'NDZIE EBODE CECILE MARIE REINE', 'CECILE MARIE REINE', 'NDZIE EBODE', '2011-08-27', 'DOUALA', 'F', 'EBODE ONANA', '.', NULL, 'BELLA NDZANA', '656094860', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:47:42', '2025-09-02 07:47:42', 1, '25A00328', 10, 0),
(356, 'ONBASSILEK SOKMAK DANYELLE', 'DANYELLE', 'ONBASSILEK SOKMAK', '2010-11-01', 'DOUALA', 'F', 'SOKMAK', '679609752', NULL, 'DJIFAK', '653958707', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:55:13', '2025-09-02 07:55:13', 1, '25A00329', 16, 0),
(357, 'KENFACK ELAUGE NAOMIE', 'ELAUGE NAOMIE', 'KENFACK', '2007-08-05', 'BAMENDOU', 'F', 'LEMEKOUTE CHRISTOPHE', '698770202', NULL, 'KEUGNE ELISABETH', '651300336', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:55:47', '2025-09-02 07:55:47', 1, '25A00330', 7, 0),
(358, 'MAGNE KEMNEUGNE LINE MEGANE', 'LINE MEGANE', 'MAGNE KEMNEUGNE', '2008-05-15', 'DOUALA', 'F', 'KEMNEUGNE', '650975003', NULL, 'DJIKOM BLANDINE', '678150005', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 08:01:48', '2025-09-02 08:01:48', 1, '25A00331', 12, 0),
(359, 'DJANKOU NJANG RONNY', 'RONNY', 'DJANKOU NJANG', '2011-11-20', 'DOUALA', 'M', 'DJANKOU TCHUISSAC GILDAS', '679645765', NULL, 'AGHASI MARIE NOEL APONLEN', '656587062', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 08:07:32', '2025-09-02 08:07:32', 1, '25A00332', 9, 0),
(360, 'KALKE SAMUEL FRITZ', 'SAMUEL FRITZ', 'KALKE', '2009-06-04', 'DOUALA', 'M', 'KALKE SAMUEL', '676908115', NULL, 'NTYAME EKO\'O ARMELLE', '699828238', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 08:20:39', '2025-09-02 08:20:39', 1, '25A00333', 6, 0),
(361, 'EKENGUE MADELEINE GAELLE', 'MADELEINE GAELLE', 'EKENGUE', '2006-01-05', 'DOUALA', 'F', 'ELOUNDA SAMUEL', '.', NULL, 'MITINI EMMA JOSEPHINE', '691175842', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 08:39:39', '2025-09-02 08:39:39', 1, '25A00334', 7, 0),
(362, 'NZEUGANG NGUEBIAPSSI JESSICA AUDREY', 'JESSICA AUDREY', 'NZEUGANG NGUEBIAPSSI', '2008-03-06', 'DOUALA', 'F', 'NGUEBIAPSSI ROBERT', '.', NULL, 'NGANYOU CARINE', '697435457', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 08:47:59', '2025-09-02 08:47:59', 1, '25A00335', 7, 0),
(363, 'MELINGUI MOKAM KOUAM DIANA', 'DIANA', 'MELINGUI MOKAM KOUAM', '2009-10-04', 'BANDJOUM', 'F', 'KOUAM TIENOU RODRIGUE', '694821448', NULL, 'EYENGA NADINE', '675300205', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 08:57:32', '2025-09-02 08:57:32', 1, '25A00336', 3, 0),
(364, 'ATOKET ELOMBAT YVANA', 'YVANA', 'ATOKET ELOMBAT', '2007-04-16', 'SANTCHOU', 'F', 'ASSOUKOUING ELVIS', NULL, NULL, 'ABAMOT MELANIE', '695071151', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:15:16', '2025-09-02 09:15:16', 1, '25A00337', 7, 0),
(365, 'KAMBU MOUAFFO FLORINDA BRISTOL', 'FLORINDA BRISTOL', 'KAMBU MOUAFFO', '2007-01-01', 'MELONG', 'F', 'MOUAFFO SIMPLICE', '.', NULL, 'MAFOTSIN MANYA RAMELLE', '673044614', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:17:10', '2025-09-02 09:17:10', 1, '25A00338', 17, 0),
(366, 'FAROUK HAMADOU SAÏDOU', 'HAMADOU SAÏDOU', 'FAROUK', '2009-02-12', 'DOUALA', 'M', 'HAMADOU SAÏDOU', '695608820', NULL, 'FATOUMATA ZARRA', '694924734', NULL, NULL, NULL, 41, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:34:57', '2025-09-02 09:34:57', 1, '25A00339', 3, 0),
(367, 'EMINE ANDRE YOHAN', 'ANDRE YOHAN', 'EMINE', '2013-03-22', 'DOUALA', 'M', 'DISSO DE KOBNOM THIERRY OLIVIER', '.', NULL, 'MABARI AGUY AUDREY', '691990091', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:35:54', '2025-09-02 09:35:54', 1, '25A00340', 16, 0),
(368, 'SAJIDA HAMADOU SAÏDOU', 'HAMADOU SAÏDOU', 'SAJIDA', '2011-01-26', 'DOUALA', 'F', 'HAMADOU SAÏDOU', '695608820', NULL, 'FATOUMATA ZARA', '694924734', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:36:54', '2025-09-02 09:39:52', 1, '25A00341', 12, 0),
(369, 'HASSANATOU ALIYA HAMADOU SAÏDOU', 'HAMADOU SAÏDOU', 'HASSANATOU ALIYA', '2013-10-27', 'DOUALA', 'F', 'HAMADOU SAÏDOU', '695608820', NULL, 'FATOUMATA ZARRA', '694924734', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:38:57', '2025-09-02 22:30:01', 1, '25A00342', 18, 1),
(370, 'DADJI SANDJO JOSEPHINE KIMORA', 'JOSEPHINE KIMORA', 'DADJI SANDJO', '2014-11-11', 'DOUALA', 'F', 'SANDJO PAHO RAOUL', '650611368', NULL, 'BOMA BANEN OLGA', '653269365', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:39:23', '2025-09-02 09:39:23', 1, '25A00343', 15, 0),
(371, 'NGAMEGNE FEUNANG RICHENEL', 'RICHENEL', 'NGAMEGNE FEUNANG', '2009-03-12', 'DOUALA', 'F', 'FEUNANG DOUGLAS', '697745111', NULL, 'NGUEPNANG FEUNANG BLANDINE', '697190166', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:45:09', '2025-09-02 09:45:09', 1, '25A00344', 4, 0),
(372, 'KAPMEGNE FEUNANG DARIC FRED', 'DARIC FRED', 'KAPMEGNE FEUNANG', '2009-03-12', 'DOUALA', 'M', 'FEUNANG DOUGLAS BRICE', '697190166', NULL, 'NGUEPNANG FEUNANG BLANDINE', '697745111', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:47:54', '2025-09-02 09:49:39', 1, '25A00345', 5, 0),
(373, 'HADIDJATOU OUMMOUL', 'OUMMOUL', 'HADIDJATOU', '2006-11-03', 'BANYO', 'F', 'BABA ABDOUL BAGUI', '696660023', NULL, 'NGO PEHA MARLISE VALERIE', '694040648', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 09:59:16', '2025-09-02 22:30:54', 1, '25A00346', 8, 0),
(374, 'DJOKA NGNEPIWO SONIA MAEVA', 'SONIA MAEVA', 'DJOKA NGNEPIWO', '2006-10-12', 'DOUALA', 'F', 'FOKO MARTIAL', '.', NULL, 'FOKO CARINE', '675546376', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 10:00:29', '2025-09-02 10:00:29', 1, '25A00347', 18, 0),
(375, 'MOUNIRA ABDOUL BAKI', 'ABDOUL BAKI', 'MOUNIRA', '2013-05-05', 'DOUALA', 'F', 'BABA ABDOUL BAKI', '696660023', NULL, 'NGO PEHA MARLISE VALERIE', '694040648', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 10:01:42', '2025-09-02 10:01:42', 1, '25A00348', 10, 0),
(376, 'WANDJI MEWA LESLIE', 'LESLIE', 'WANDJI MEWA', '2008-09-10', 'DOUALA', 'F', 'FOKO MARTIAL', '.', NULL, 'FOKO CARINE', '675546376', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 10:02:55', '2025-09-02 10:02:55', 1, '25A00349', 19, 0),
(377, 'MOTSOU SEZINE SANDRA', 'SEZINE SANDRA', 'MOTSOU', '2006-07-02', 'DOUALA', 'F', 'MOTSOU HONORAT', '699816529', NULL, 'MANDJI HELENE', '652019307', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 10:06:33', '2025-09-02 10:06:33', 1, '25A00350', 5, 0),
(378, 'BABENA ANGE BLONDELLE', 'ANGE BLONDELLE', 'BABENA', '2006-07-10', 'NTUI', 'F', '.', '.', NULL, 'BALOMO PAGRACE EVELYNE', '673016912', NULL, NULL, NULL, 107, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 10:09:25', '2025-09-02 10:09:25', 1, '25A00351', 3, 0),
(379, 'WEM BASSOM JANE INGRID LESLY', 'JANE INGRID LESLY', 'WEM BASSOM', '2007-01-29', 'DOUALA', 'F', 'BASSOM OSCAR', '.', NULL, 'MAMA CAROLINE MARLYSE', '651330875', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 10:10:00', '2025-09-02 10:10:00', 1, '25A00352', 20, 0),
(380, 'NDOUMBE JOSEPH FRANCOIS', 'JOSEPH FRANCOIS', 'NDOUMBE', '2008-10-06', 'DOUALA', 'M', 'BOUYABAGA CHARLES', '691013748', NULL, 'AMANENGUENE NDOUMBE ODETTE', '691764670', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 10:14:00', '2025-09-02 10:14:00', 1, '25A00353', 24, 0),
(381, 'MOKAM ATEBA PAMELA SINTCHA', 'PAMELA SINTCHA', 'MOKAM ATEBA', '2006-10-23', 'DOUALA', 'F', 'ATEBA LAURENT', '675872553', NULL, 'NOUMO VALERINE CHANTAL', '675054471', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 10:21:07', '2025-09-02 10:21:07', 1, '25A00354', 21, 0),
(382, 'BEBO BRYAN WISDOM', 'BRYAN WISDOM', 'BEBO', '2011-09-17', 'SANTCHOU', 'M', 'YAMTCHE ALAIN GIRES', NULL, NULL, 'BEBO AMELIE AGATHA', '675185287', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 11:34:55', '2025-09-02 11:34:55', 1, '25A00355', 4, 0),
(383, 'DAÏAWA BELLE ANDREÏNA', 'ANDREÏNA', 'DAÏAWA BELLE', '2012-09-04', 'DOUALA', 'F', 'BELLE ANDRE', NULL, NULL, 'DAÏAWE DOURGA JOSEPHINE', '677600913', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 11:37:24', '2025-09-02 11:37:24', 1, '25A00356', 12, 0),
(384, 'DOMGA CEDRIC', 'CEDRIC', 'DOMGA', '2004-04-24', 'DOUALA', 'M', 'BELLE ANDRE', NULL, NULL, 'DAÏAWE DOURGA JOSEPHINE', '677600913', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 11:40:46', '2025-09-02 11:40:46', 1, '25A00357', 13, 0),
(385, 'DE-ELMBE GUILIGUI BELLE CAROLINE', 'BELLE CAROLINE', 'DE-ELMBE GUILIGUI', '2009-06-01', 'DOUALA', 'F', 'BELLE ANDRE', NULL, NULL, 'DAÏ-AWE DOURGA JOSEPHINE', '677600913', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 11:42:56', '2025-09-02 11:42:56', 1, '25A00358', 14, 0),
(386, 'BELLE ELISE', 'ELISE', 'BELLE', '2010-07-08', 'GUERE', 'M', 'HAROUM JEREMI', '652003260', NULL, 'AÏMAMANI', '676902705', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 11:44:58', '2025-09-02 11:44:58', 1, '25A00359', 9, 0),
(387, 'ZEH EDENE ALICIA SANDRINE', 'ALICIA SANDRINE', 'ZEH EDENE', '2010-06-02', 'YAOUNDE', 'F', 'ZEH CYRIL', '692697926', NULL, 'AFANA AMOUGOU MARIE LAURE', '675130741', NULL, NULL, NULL, 70, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 11:52:41', '2025-09-02 11:52:41', 1, '25A00360', 3, 0),
(388, 'BOUMSONG ISAAC YOHAN', 'ISAAC YOHAN', 'BOUMSONG', '2013-08-01', 'DOUALA', 'M', '.', '699897247', NULL, 'BOUMSONG SIPORA', '658541614', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 12:01:49', '2025-09-02 12:01:49', 1, '25A00361', 16, 0),
(389, 'MAKOU ZEMAGHO CHARLOTTE ESTHER', 'CHARLOTTE ESTHER', 'MAKOU ZEMAGHO', '2011-05-11', 'DOUALA', 'F', 'ZEMAGHO APPOLINAIRE', '675010934', NULL, 'DJANIAL JEANETTE', '677868920', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 12:06:07', '2025-09-02 12:06:07', 1, '25A00362', 4, 0),
(390, 'INNA ASTAHARAM MOHAMADOU', 'MOHAMADOU', 'INNA ASTAHARAM', '2013-01-15', 'NGAOUNDERE', 'F', 'MOHAMADOU AWAL', '696832820', NULL, 'HADIDJATOU IBRAHIM', '696351760', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 12:10:05', '2025-09-02 12:10:05', 1, '25A00363', 11, 0),
(391, 'IHOYA NDJOCK ANDREE CECILE GRACE', 'ANDREE CECILE GRACE', 'IHOYA NDJOCK', '2012-07-13', 'DOUALA', 'F', 'NDJOCK PASCAL', '.', NULL, 'NGOBAHA CHANTAL', '691180144', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 13:14:34', '2025-09-02 13:14:34', 1, '25A00364', 6, 0),
(392, 'NDONGO NDJOCK MARTINE LAURE', 'MARTINE LAURE', 'NDONGO NDJOCK', '2007-11-05', 'DOUALA', 'F', 'NDJOCK PASCAL', '.', NULL, 'NGO BAHA CHANTAL', '691180144', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 13:17:35', '2025-09-02 13:17:35', 1, '25A00365', 10, 0),
(393, 'ZE DIMI PEGUY LOÏC', 'PEGUY LOÏC', 'ZE DIMI', '2006-01-28', 'TALBA', 'M', 'DIMI NVOULONG', '696427010', NULL, 'ABOMO PELAGIE', '694741357', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 13:26:50', '2025-09-02 22:30:54', 1, '25A00366', 15, 0),
(394, 'BOPDA DZUDOM ISMAEL JAUIS', 'ISMAEL JAUIS', 'BOPDA DZUDOM', '2013-02-27', 'DOUALA', 'M', 'DZUDOM FOTSO ALBERT', '677522367', NULL, 'KEGNE FOGUE MARIE', '676106297', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 05:58:23', '2025-09-03 05:58:23', 1, '25A00367', 7, 0),
(395, 'FONDOUOP TCHOFFO YVES WILLIAMS', 'YVES WILLIAMS', 'FONDOUOP TCHOFFO', '2013-02-23', 'DOUALA', 'M', 'TCHOFFO ERIC', NULL, NULL, 'MOBOU MARIE', '679659319', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 06:08:12', '2025-09-12 11:19:07', 1, '25A00368', 37, 1),
(396, 'ANGE GABRIELLE BOOG', 'BOOG', 'ANGE GABRIELLE', '2011-09-18', 'DOUALA', 'F', 'BOOG DAVID', '693350277', NULL, 'BOOG ERNESTINE', '694289424', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 06:10:29', '2025-09-03 06:10:29', 1, '25A00369', 22, 0),
(397, 'MAKOUCHE NEGUEU MANUELLA MEGANE', 'MANUELLA MEGANE', 'MAKOUCHE NEGUEU', '2011-05-17', 'DOUALA', 'F', 'NEGUEU AUGUSTIN AUBAIN', '657259209', NULL, 'MAFFO SANDRINE', '673426256', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 06:16:49', '2025-09-03 06:16:49', 1, '25A00370', 11, 0),
(398, 'TINDO LONTSIE MARIOLLE', 'MARIOLLE', 'TINDO LONTSIE', '2008-06-26', 'MBOUDA', 'F', 'LONTSIE SYLVAIN', '694315293', NULL, 'KUETE IDOSINE MICALE', '672364609', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 06:31:32', '2025-09-03 06:31:32', 1, '25A00371', 5, 0),
(399, 'BILLOT GIVETY JAINYIN', 'JAINYIN', 'BILLOT GIVETY', '2012-03-01', 'DOUALA', 'F', 'GINSEH JASPA JAINYIN', '.', NULL, 'CATHERINE FANGWE LEVA', '676059542', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 07:13:17', '2025-09-03 07:13:17', 1, '25A00372', 5, 0),
(400, 'HADIDJATOU ABDOUL BAGUI', 'ABDOUL BAGUI', 'HADIDJATOU', '2011-06-01', 'NGAOUNDERE', 'F', 'ABDOUL BAGUI', '.', NULL, 'OUMMOUL MAHMOUNOU', '695545145', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 08:06:13', '2025-09-12 07:21:18', 1, '25A00373', 18, 0),
(401, 'NAOUSSI DJIOMAGUE SAMUEL LOYS', 'SAMUEL LOYS', 'NAOUSSI DJIOMAGUE', '2014-11-27', 'DOUALA', 'M', 'DJIOMAGUE KEVALI YANNICK DIMITRI', '.', NULL, 'GUETCHOUESSI KAYO NADEGE', '656849784', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 08:11:15', '2025-09-12 11:18:55', 1, '25A00374', 36, 0),
(402, 'DJOU KALEGANG ORCHELLE', 'ORCHELLE', 'DJOU KALEGANG', '2015-10-05', 'BABADJOU', 'F', 'KALEGANG ELVIS', '676054149', NULL, 'LAMAGE AMEL DOUCE', '677051941', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 08:15:00', '2025-09-12 11:18:44', 1, '25A00375', 35, 0),
(403, 'BONDJE JEAN KAREL', 'JEAN KAREL', 'BONDJE', '2009-12-01', 'DOUALA', 'M', 'BONDJE JEAN CLAUDE', '686351901', NULL, 'BIBOUM ANNE DELPHINE', '697240681', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 08:28:09', '2025-09-03 08:28:09', 1, '25A00376', 11, 0),
(404, 'TAGUE TENE EMMANUEL DIVANE', 'EMMANUEL DIVANE', 'TAGUE TENE', '2013-09-05', 'DOUALA', 'M', 'TENE JOSEPH', '679742429', NULL, 'NGUEDIA KAFACK SULATHE', '671105202', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 08:39:00', '2025-09-12 11:18:28', 1, '25A00377', 34, 0),
(405, 'MBOGNE TENE EZEKIEL', 'EZEKIEL', 'MBOGNE TENE', '2011-08-15', 'DOUALA', 'M', 'TENE JOSEPH', '679742429', NULL, 'NGUEDIA KAFACK SULATHE', '671105202', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 08:41:04', '2025-09-03 08:41:04', 1, '25A00378', 13, 0),
(406, 'BAH NGA COLLETTE DONOVANE', 'COLLETTE DONOVANE', 'BAH NGA', '2009-09-25', 'OKOK-ESSELE', 'F', 'NGA BIENVENU JALOUX', '674472708', NULL, 'OMGBA MELIENGA MARIE CHRISTINE', '658976379', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 08:47:36', '2025-09-03 08:47:36', 1, '25A00379', 12, 0),
(407, 'NANA MOUNCHANDINI ABDEL', 'ABDEL', 'NANA MOUNCHANDINI', '2013-10-14', 'FOUMBAN', 'M', 'MOUNCHANDINI INOUSSA', '699009434', NULL, 'NGATCHOUANG CHARLENE', '691178959', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 08:54:39', '2025-09-03 08:54:39', 1, '25A00380', 14, 0),
(408, 'HOLANYE HERMAN', 'HERMAN', 'HOLANYE', '2006-08-20', 'DOUALA', 'M', 'PAMBOG DAVID', '677774470', NULL, 'DEUKAM DENISE', '699362597', NULL, NULL, NULL, 91, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:03:34', '2025-09-03 09:03:34', 1, '25A00381', 1, 0),
(409, 'BEPE AMBASSA STEVE', 'STEVE', 'BEPE AMBASSA', '2011-06-17', 'EBOLOWA', 'M', 'MBASSA PASCAL MARIE', NULL, NULL, 'EKEMEYONG IRENE', '699731643', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:05:59', '2025-09-03 09:05:59', 1, '25A00382', 25, 0),
(410, 'LONTCHI DOGMENI CHRIST MAËL', 'CHRIST MAËL', 'LONTCHI DOGMENI', '2011-06-04', 'YAOUNDE', 'M', 'DOGMENI', '691021439', NULL, 'STATEDEM YONTA', '656215677', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:08:35', '2025-09-03 09:08:56', 1, '25A00383', 5, 0),
(411, 'LINGOM SIMON', 'SIMON', 'LINGOM', '2008-08-23', 'MANDOUMBA', 'M', 'LINGOM SIMON', '677966916', NULL, 'FEBI EBOUA JULIE', '.', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:18:17', '2025-09-03 09:18:17', 1, '25A00384', 12, 0),
(412, 'NDOUM NGAMBI LUCIEN SAMUEL', 'LUCIEN SAMUEL', 'NDOUM NGAMBI', '2007-06-09', 'DOUALA', 'M', 'NGAMBI ETIENNE CYRILLE', '6907186750', NULL, 'MASSO Epse NGAMBI VERONIQUE DANIELLE', '695445470', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:22:11', '2025-09-12 13:06:11', 1, '25A00385', 11, 0),
(413, 'MBESSE MENGUENE GISELE FRANCE JANIS', 'GISELE FRANCE JANIS', 'MBESSE MENGUENE', '2013-03-07', 'DOUALA', 'F', '.', '670403323', NULL, 'MBALLA SALOME', '692370446', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:27:34', '2025-09-12 11:17:59', 1, '25A00386', 33, 0),
(414, 'MANGA MAEVA', 'MAEVA', 'MANGA', '2010-05-10', 'EYENMEYONG', 'F', 'SIMON SERGE', NULL, NULL, 'NGONO BIKELE EMILIENNE JOSIANE', '655433520', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:30:35', '2025-09-03 09:30:35', 1, '25A00387', 4, 0),
(415, 'SANOU TCHOUSSA IVANA', 'IVANA', 'SANOU TCHOUSSA', '2013-12-12', 'DOUALA', 'F', 'TCHOUSSA NDJANGANG BEMBADIS', '675642798', NULL, 'TCHAKYEU ROSINE', '651317531', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:35:19', '2025-09-12 11:17:50', 1, '25A00388', 32, 1),
(416, 'OKALA NTEDE FERNANDE PATRICIA', 'FERNANDE PATRICIA', 'OKALA NTEDE', '2008-07-01', 'DOUALA', 'F', 'NTEDE NDZEBE TELESPHORE', '693808771', NULL, 'ONOGO NGOUMOU MARIE', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:41:11', '2025-09-03 09:41:11', 1, '25A00389', 26, 0),
(417, 'SEN BIKECK GRACE MALVINE', 'GRACE MALVINE', 'SEN BIKECK', '2013-04-13', 'DOUALA', 'F', 'ESSOME BIKECK RICHARD', '.', NULL, 'YOUDJEU VVANSI ALVINE SORELLE', '682872734', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:48:35', '2025-09-03 09:48:35', 1, '25A00390', 17, 0),
(418, 'MAGNIE BIKECK SCHEKINA ANGE', 'SCHEKINA ANGE', 'MAGNIE BIKECK', '2008-06-22', 'PENJA', 'F', 'ESSOME BIKECK RICHARD', '.', NULL, 'YOUDJEU WANSI ALVINE', '682872734', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 09:54:10', '2025-09-03 09:54:10', 1, '25A00391', 6, 0),
(419, 'AROLD FRANCK KONDJI EUGENE JEREMIE', 'KONDJI EUGENE JEREMIE', 'AROLD FRANCK', '2004-03-18', 'DOUALA', 'M', 'MOUKANDJO MODELE  MBAN', '.', NULL, 'SOH KOLLO NATACHA', '657764071', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 10:05:44', '2025-09-03 10:05:44', 1, '25A00392', 9, 0),
(420, 'MEKAM MBOGNE DIVINE AKANDE', 'DIVINE AKANDE', 'MEKAM MBOGNE', '2013-08-01', 'BATOUFAM', 'F', 'MBOGNE LONKAP AIME', '676753030', NULL, 'TOUKAM DOMGANG VIVIANE', '696907764', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 10:11:03', '2025-09-03 10:11:03', 1, '25A00393', 7, 0),
(421, 'ABDOUL MOUHSIN HASSAN ALI MAI', 'HASSAN ALI MAI', 'ABDOUL MOUHSIN', '2015-02-27', 'MASSAKORY', 'M', 'HASSAN ALI MAI', '690041448', NULL, 'CHARIFA', '.', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 10:17:36', '2025-09-03 10:17:36', 1, '25A00394', 15, 0),
(422, 'MARYAMOU DOUDOU OUMAROU', 'DOUDOU OUMAROU', 'MARYAMOU', '2011-03-23', 'NGAOUNDERE', 'F', 'OUMAROU', '690071854', NULL, 'FATIMATOU', NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 10:31:50', '2025-09-03 10:31:50', 1, '25A00395', 13, 0),
(423, 'CHEHOU OUSMANOU OUMAROU', 'OUSMANOU OUMAROU', 'CHEHOU', '2014-11-20', 'DOUALA', 'M', 'OUMAROU', '690071854', NULL, 'FADIMATOU', '.', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 10:34:47', '2025-09-03 10:34:47', 1, '25A00396', 16, 0),
(424, 'ABDOULLAHI BAH-DJALLO OUMAROU', 'BAH-DJALLO OUMAROU', 'ABDOULLAHI', '2009-04-16', 'NGAOUNDERE', 'M', 'OUMAROU', '690071854', NULL, 'FADIMATOU', '.', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 10:37:40', '2025-09-12 08:20:57', 1, '25A00397', 6, 0),
(425, 'MAKENG KENGNE AUXANE', 'AUXANE', 'MAKENG KENGNE', '2011-03-07', 'DOUALA', 'F', 'KEUNGNE ABEL', '678036539', NULL, 'METSADJIO CAROLINE', '671121362', NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 10:49:14', '2025-09-03 10:49:14', 1, '25A00398', 4, 0),
(426, 'MONKAM AARON BRISTAND', 'AARON BRISTAND', 'MONKAM', '2015-06-25', 'DOUALA', 'M', 'MONKAM PIERRE', '699384756', NULL, 'TCHOUTANG ELVIGE LOVE', '.', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 10:59:53', '2025-09-12 11:17:39', 1, '25A00399', 31, 0),
(427, 'YODOYMAN DJIMADOUM JOEL', 'JOEL', 'YODOYMAN DJIMADOUM', '2014-10-17', 'DJAMENA', 'M', 'CASIMIR YODOYMAN', '655665671', NULL, 'SOLIRY EDITH', '.', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:14:59', '2025-09-12 11:17:29', 1, '25A00400', 30, 0),
(428, 'ABOUDE FIAGA CEDRIC YOAN', 'CEDRIC YOAN', 'ABOUDE FIAGA', '2012-09-12', 'DOUALA', 'M', 'BENDEGUE FIAGA MATHURIN', '699844514', NULL, 'EKANDO OLEMBE ANNIE', '699561964', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:27:54', '2025-09-03 11:27:54', 1, '25A00401', 17, 0),
(429, 'JAISSPREET SONNIE FRANCHESCA', 'FRANCHESCA', 'JAISSPREET SONNIE', '2011-10-06', 'DOUALA', 'F', 'SONNY SINGH', '.', NULL, 'NAOMIE KOOBANJOH', '699686351', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:38:26', '2025-09-03 11:38:26', 1, '25A00402', 4, 0),
(430, 'SIBI YVANNA FRESHNELL', 'YVANNA FRESHNELL', 'SIBI', '2014-12-02', 'DOUALA', 'F', 'MRS TIGHI LOUIS', '.', NULL, 'KENGNE LORIANE', '671746615', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:44:44', '2025-09-03 11:44:44', 1, '25A00403', 17, 0),
(431, 'TAKOU SOREILLE FORTUNE', 'SOREILLE FORTUNE', 'TAKOU', '2008-01-22', 'DOUALA', 'F', 'FONKOUA MICHEL', '.', NULL, 'MANKOU JACQUELINE', '651897343', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:45:24', '2025-09-03 11:45:24', 1, '25A00404', 23, 0),
(432, 'NOMENI BLANCHE', 'BLANCHE', 'NOMENI', '2006-09-19', 'YAOUNDE', 'F', 'TCHANTCHUME FLAUBERT', '677029314', NULL, 'MOUKGNOU EMERANCE', '.', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:50:43', '2025-09-03 11:50:43', 1, '25A00405', 10, 0),
(433, 'AMINATOU MOHAMAN AWAL', 'MOHAMAN AWAL', 'AMINATOU', '2012-08-23', 'DOUALA', 'F', 'MOHAMAN AWAL', '694417960', NULL, 'SAHADATOU AMADOU', '651799184', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:50:47', '2025-09-03 11:50:47', 1, '25A00406', 12, 0),
(434, 'BELING ROCHE BEDILE', 'ROCHE BEDILE', 'BELING', '2007-11-01', 'BABETTA', 'F', '.', '699384756', NULL, 'ASSOGA ENET PAULINE', '673021478', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:56:41', '2025-09-03 11:56:41', 1, '25A00407', 7, 0),
(435, 'NDOUN MBANG REGINE PAULINE', 'REGINE PAULINE', 'NDOUN MBANG', '2007-08-02', 'DOUALA', 'F', 'MBANG DANIEL', '677692220', NULL, 'NGOKE REBECCA HELENE', '.', NULL, NULL, NULL, 41, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 11:57:02', '2025-09-03 11:57:02', 1, '25A00408', 4, 0),
(436, 'NKONDO MBANG JEANINE', 'JEANINE', 'NKONDO MBANG', '2010-02-28', 'DOUALA', 'F', 'MBANG DANIEL', '677692220', NULL, 'NGOKE REBECCA HELENE', '.', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 12:05:00', '2025-09-03 12:05:00', 1, '25A00409', 13, 0),
(437, 'SOUP TIGHI CHRISTIANS JUNIOR', 'CHRISTIANS JUNIOR', 'SOUP TIGHI', '2012-05-07', 'DOUALA', 'M', 'LOUIS TIGHI', '.', NULL, 'LOURE KENGNE', '671746615', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 12:10:37', '2025-09-03 12:10:37', 1, '25A00410', 6, 0),
(438, 'TATBEUM BEKO BEKONO GRACE DIVINE', 'GRACE DIVINE', 'TATBEUM BEKO BEKONO', '2012-02-13', 'DOUALA', 'F', 'BEKONO THIEBAU', NULL, NULL, 'DIBAMBEUN LEOPOLDINE', '675762580', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 12:18:46', '2025-09-03 12:18:46', 1, '25A00411', 13, 0),
(439, 'ANGE BEKO BEKONO FABIOLA', 'FABIOLA', 'ANGE BEKO BEKONO', '2008-04-07', 'DOUALA', 'F', 'BEKONO THIEBAU', '.', NULL, 'DIBAMBEUN LEOPOLDINE', '675762580', NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 12:21:25', '2025-09-03 12:21:25', 1, '25A00412', 4, 0),
(440, 'JOSEPH TAGO STEVE WILLIAMS', 'STEVE WILLIAMS', 'JOSEPH TAGO', '2010-06-30', 'NKONGSAMBA', 'M', 'TCHOKONA FRANCOIS AIME', '675260744', NULL, 'TIDO TAGO BLANDINE AIMEE', '672767232', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 12:25:40', '2025-09-03 12:25:40', 1, '25A00413', 13, 0),
(441, 'EMVOUTOU KEYETAT GISLAIN DANIEL', 'GISLAIN DANIEL', 'EMVOUTOU KEYETAT', '2012-12-03', 'DOUALA', 'M', 'KEYETAT HUBERT', '695773457', NULL, 'NOUKEU FIDELINE', '.', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 12:58:40', '2025-09-03 13:12:59', 1, '25A00414', 18, 0),
(442, 'KEGNE SIMO ELODIE', 'ELODIE', 'KEGNE SIMO', '2004-04-14', 'DOUALA', 'F', 'TAGNE DURAN', '.', NULL, 'NGONGO OLIVE CLAIR', '697400244', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 13:08:16', '2025-09-03 13:08:16', 1, '25A00415', 27, 0),
(443, 'DONGMO PRINCESS AGBESANYU', 'PRINCESS AGBESANYU', 'DONGMO', '2010-10-31', 'NGUTI', 'F', 'NANFACK DONGMO THOMAS', '.', NULL, 'NDOKI STELLA OKIE', '673502663', NULL, NULL, NULL, 61, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-03 13:15:52', '2025-09-03 13:15:52', 1, '25A00416', 3, 0),
(444, 'BITJA\'A KODY BORIS DENIS', 'BORIS DENIS', 'BITJA\'A KODY', '2011-03-22', 'DOUALA', 'M', 'KODY FILS PAUL', '699644413', NULL, 'NDABMAL PAULINE', '656530825', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 05:45:13', '2025-09-05 05:45:13', 1, '25A00417', 14, 0),
(445, 'PRINCESSE EMMANUELLE MUNE KODY', 'MUNE KODY', 'PRINCESSE EMMANUELLE', '2008-12-12', 'YAOUNDE', 'F', 'KODY FILS PAUL', '699644413', NULL, 'NDABMAL PAULINE', '656530825', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 05:47:44', '2025-09-05 05:47:44', 1, '25A00418', 8, 0),
(446, 'MBOGNE ZIDANE ULRICH', 'ZIDANE ULRICH', 'MBOGNE', '2010-07-08', 'BANSOA', 'M', 'KUEGOUMGENG', '681042025', NULL, 'MADJOUOKOUO FONKOU MIRABELLE', '677430001', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 05:56:56', '2025-09-05 05:56:56', 1, '25A00419', 16, 0),
(447, 'WANDJA FABO HEUMEGNI SERGES BERTINI', 'SERGES BERTINI', 'WANDJA FABO HEUMEGNI', '2012-08-25', 'DOUALA-CAMEROUN', 'M', 'FABO HEUMAGNI SIMPLICE VALERY', '670253897', NULL, 'MBAKOP TCHAMBA MARIE', '654558216', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 06:06:21', '2025-09-05 06:06:21', 1, '25A00420', 18, 0),
(448, 'NGOUEKA KEMETA MAYELA KYNDRA', 'MAYELA KYNDRA', 'NGOUEKA KEMETA', '2010-07-05', 'BABADJOU', 'F', 'KEMETA', '670621841', NULL, 'WINNI', '674133309', NULL, NULL, NULL, 110, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 06:15:21', '2025-09-05 06:15:21', 1, '25A00421', 2, 0),
(449, 'AFA\'A ACHILLE JUNIOR XAVIER', 'ACHILLE JUNIOR XAVIER', 'AFA\'A', '2003-07-25', 'EBOLOWA', 'M', 'AFA\'A ACHILLE NORBERT', '679526724', NULL, 'BILOUNGA EBALE MARIE', '699045520', NULL, NULL, NULL, 63, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 06:20:18', '2025-09-05 06:20:18', 1, '25A00422', 2, 0),
(450, 'KANIYON BELAME FRANCISCA', 'FRANCISCA', 'KANIYON BELAME', '2010-01-31', 'YAOUNDE', 'F', 'KANIYON', '699445707', NULL, 'NGO MPAÏ', '699334047', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 06:27:01', '2025-09-05 06:27:01', 1, '25A00423', 6, 0),
(451, 'BVUMYEM NGANGA MARIE DRUSSILE', 'MARIE DRUSSILE', 'BVUMYEM NGANGA', '2007-11-19', 'CAMPO', 'F', 'NKOULY MANA MARC HERVE', '655508470', NULL, 'DELAVIE MARIE CLAIRE', '650655457', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 06:32:31', '2025-09-10 13:45:49', 1, '25A00424', 17, 0),
(452, 'MONYAP VII AARON CESAR DUPREL', 'AARON CESAR DUPREL', 'MONYAP VII', '2004-11-23', 'CAMPO', 'M', 'NKOULY MANA MARC HERVE', '655508470', NULL, 'MARIE CLAIRE DELAVIE', '650655457', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 06:37:55', '2025-09-05 06:37:55', 1, '25A00425', 9, 0),
(453, 'MINTERI NKOULY MANA JAYLEE CASSANDRA', 'JAYLEE CASSANDRA', 'MINTERI NKOULY MANA', '2014-07-17', 'KRIBI', 'F', 'NKOULY MANA MARC HERVE', '655608470', NULL, 'DELAVIE MARIE CLAIRE', '650655457', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 06:39:54', '2025-09-12 11:17:18', 1, '25A00426', 29, 0),
(454, 'KENFACK JENNY FRANCELLE', 'JENNY FRANCELLE', 'KENFACK', '2010-10-02', 'DOUALA', 'F', 'TSAFACK THOMAS', '675907535', NULL, 'TSAKENG NINA CHANCELLINE', '677195092', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 07:03:14', '2025-09-05 07:03:14', 1, '25A00427', 8, 0),
(455, 'KOUETE SAP ELVIRA CHANEL', 'ELVIRA CHANEL', 'KOUETE SAP', '2009-05-30', 'DOUALA', 'F', 'NGOUMETA MODESTE', '675000291', NULL, 'TAFOKA SAP SILVIM', '675808539', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 07:29:02', '2025-09-05 07:29:02', 1, '25A00428', 9, 0),
(456, 'ZOBO BANAS JEANNE ANAEL', 'JEANNE ANAEL', 'ZOBO BANAS', '2012-05-31', 'YAOUNDE', 'F', 'ZOBO BIKELE EMILE CHRISTIAN', '653476190', NULL, 'ADZESSA LECILE', NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 07:48:33', '2025-09-05 07:48:33', 1, '25A00429', 15, 0),
(457, 'ATEMENGUE ZOBO BENOIT MERCIELLE', 'BENOIT MERCIELLE', 'ATEMENGUE ZOBO', '2012-03-08', 'YAOUNDE', 'F', 'EOBO BIKELE EMILE CHRISTIAN', '653476190', NULL, 'ZOUA MLAGUENA CHRISTELLE', NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 07:50:38', '2025-09-05 07:50:38', 1, '25A00430', 16, 0),
(458, 'KOMBOU WOMNGNI MORINE SYNDIE', 'MORINE SYNDIE', 'KOMBOU WOMNGNI', '2002-01-14', 'MELONG', 'F', 'WOMGNI FELIX ALAIN', '657235398', NULL, 'LEUWE ADELINE NADEGE', '675278196', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 07:54:38', '2025-09-05 07:54:38', 1, '25A00431', 7, 0),
(459, 'GANI NDJENG MARIE-CHRISTIANE', 'MARIE-CHRISTIANE', 'GANI NDJENG', '2008-04-18', 'DOUALA', 'F', 'NDJENG NARCISSE', '696550928', NULL, 'MBANG ONANA KARINE', '699137751', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 07:58:33', '2025-09-05 07:58:33', 1, '25A00432', 24, 0),
(460, 'SITCHEU TCHADJE LEANA LUCIENNE', 'LEANA LUCIENNE', 'SITCHEU TCHADJE', '2008-01-24', 'DOUALA', 'F', 'TCHADJE', NULL, NULL, 'DJUIMO', '675960007', NULL, NULL, NULL, 107, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:14:49', '2025-09-05 08:14:49', 1, '25A00433', 4, 0),
(461, 'KEMAJOU ANYAM CHRISTANGE', 'CHRISTANGE', 'KEMAJOU ANYAM', '2009-10-29', 'DOUALA', 'M', 'NYAM ESSELE EMMANUEL', NULL, NULL, 'DAKAM KEMAJOU PULCHERIE', '696278896', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:22:36', '2025-09-05 08:22:36', 1, '25A00434', 18, 0),
(462, 'NGWEM FELICITE PELAGIE', 'FELICITE PELAGIE', 'NGWEM', '2011-04-25', 'MAKAK', 'F', 'NGWEN', '655647157', NULL, NULL, '695260465', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:26:40', '2025-09-05 08:26:40', 1, '25A00435', 17, 0),
(463, 'BOUNEZI VANESSA', 'VANESSA', 'BOUNEZI', '2008-04-04', 'BALEVENG', 'F', 'KEMGUETSOP BONIFACE', NULL, NULL, 'MEKOUE HELENE', '679493984', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:32:12', '2025-09-05 08:32:12', 1, '25A00436', 11, 0),
(464, 'KEMGUETSOP FONKOUA EVRA', 'EVRA', 'KEMGUETSOP FONKOUA', '2012-11-30', 'BALEVENG', 'M', 'KEMGUETSOP BONIFACE', NULL, NULL, 'MEKOUE HELENE', '679493984', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:34:04', '2025-09-05 08:34:04', 1, '25A00437', 11, 0),
(465, 'ABESSOLO ABADA MADELEINE KINDA', 'MADELEINE KINDA', 'ABESSOLO ABADA', '2012-08-28', 'DOUALA', 'F', 'ABADA NKONGO JEAN CLAUDE', '694048965', NULL, 'NTOLO JOSEPHINE', '675068966', NULL, NULL, NULL, 71, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:40:36', '2025-09-05 08:40:36', 1, '25A00438', 4, 0),
(466, 'TCHOUNKE NJEUMESSEU KENDRA YOLAINE', 'KENDRA YOLAINE', 'TCHOUNKE NJEUMESSEU', '2014-09-08', 'DOUALA', 'F', 'TCHOUNKE DJAYOU DAVID FELICITE', NULL, NULL, 'MALIZE NJEUMESSEU RAISSE', NULL, NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:45:18', '2025-09-12 11:17:07', 1, '25A00439', 28, 0),
(467, 'TCHUENKAM KEMENI VANIEL', 'VANIEL', 'TCHUENKAM KEMENI', '2002-01-31', 'FOMOPEA', 'M', 'TCHUENKAM JOËL', NULL, NULL, 'BANE NGUEMOU ANNE CHANCELINE', '672380960', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:49:06', '2025-09-05 08:51:42', 1, '25A00440', 10, 0),
(468, 'TCHUENKAM WANDJA CELIANTHE DIVINE', 'CELIANTHE DIVINE', 'TCHUENKAM WANDJA', '2009-04-10', 'DOUALA', 'F', 'TCHUENKAM JOEL', '674202950', NULL, 'BANE NGUEMOU ANNE CHANCELINE', '672380960', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:53:31', '2025-09-05 08:53:31', 1, '25A00441', 4, 0),
(469, 'MBOUANDI PITKEU JESSIKA LA FORTUNE', 'JESSIKA LA FORTUNE', 'MBOUANDI PITKEU', '2013-06-15', 'MALANDEM', 'F', '.', NULL, NULL, 'MOUGOU MBOUANDI GLADICE', '698304828', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 08:56:58', '2025-09-12 11:16:56', 1, '25A00442', 27, 1),
(470, 'MBIANDJEU TCHANKOU GABRIELLA ANAELLE', 'GABRIELLA ANAELLE', 'MBIANDJEU TCHANKOU', '2008-05-12', 'YAOUNDE', 'F', 'MELIGANG NZETCHOUANG VICTORIN', '675588965', NULL, 'NJIKE NYA Epse MEUGANG PETRONILE MARCISSE', '699076376', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:01:59', '2025-09-05 09:01:59', 1, '25A00443', 25, 0),
(471, 'NGOS ALINE MARTHE MELANIE', 'ALINE MARTHE MELANIE', 'NGOS', '2013-04-29', 'DOUALA', 'F', 'NGOS DIEUDONNE', NULL, NULL, 'NGOS ALINE FRANCOISE', '690192355', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:07:34', '2025-09-05 09:07:34', 1, '25A00444', 8, 0),
(472, 'ETOUNA BELLA JEANNETTE', 'BELLA JEANNETTE', 'ETOUNA', '2008-03-13', 'DOUALA', 'F', 'ETOUNDI BELLA ABERD', '697845998', NULL, 'LEMA DENISE', '698296341', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:09:56', '2025-09-05 09:09:56', 1, '25A00445', 16, 0),
(473, 'TCHOUATEN  NGATCHANG QUEEN NELLY', 'QUEEN NELLY', 'TCHOUATEN  NGATCHANG', '2009-02-11', 'DOUALA', 'F', 'NGANTCHANG SERGES', '674444525', NULL, 'TCHAGNOU BEATRICE', '696423732', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:12:27', '2025-09-05 09:12:27', 1, '25A00446', 17, 0),
(474, 'MENAP KOUO KEDI WILLVINA', 'WILLVINA', 'MENAP KOUO KEDI', '2008-03-14', 'BARE', 'F', 'MISE DAVID', NULL, NULL, 'DJASSEP DJOFAND INA PICOLIE', '674453918', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:22:29', '2025-09-05 09:22:29', 1, '25A00447', 28, 0),
(475, 'SIMO TEWOUSSI STELLA DIANA', 'STELLA DIANA', 'SIMO TEWOUSSI', '2009-12-19', 'BAFOUSSAM', 'F', 'MISSE DAVID', '658148350', NULL, 'KOMMOGNE TEWOUSSI ELLA', '681559002', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:24:26', '2025-09-05 09:24:26', 1, '25A00448', 8, 0),
(476, 'KENFACK FEUGUE AMEDE', 'AMEDE', 'KENFACK FEUGUE', '2009-12-26', 'DOUALA', 'M', 'VOUMO FEUGUE GERMES', NULL, NULL, 'NGUEUOUN MARINETTE OLIVE', '690761753', NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:29:23', '2025-09-05 09:29:23', 1, '25A00449', 5, 0),
(477, 'WOUKENG FEUGUE CHRIST CARLAN', 'CHRIST CARLAN', 'WOUKENG FEUGUE', '2011-09-08', 'DOUALA', 'M', 'VOU%O FEUGUE GERMES', NULL, NULL, 'NGUEVOU MARINETTE OLIVE', '690761753', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:31:14', '2025-09-05 09:31:14', 1, '25A00450', 8, 0),
(478, 'DJOMO MICHELINE KHIRA', 'MICHELINE KHIRA', 'DJOMO', '2012-07-21', 'DOUALA', 'F', 'DJOMO', '698758497', NULL, 'NSANA MIMI', '675648318', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:34:50', '2025-09-05 09:34:50', 1, '25A00451', 9, 0),
(479, 'MBOMEYO NYABELA ADRIANE STEVY', 'ADRIANE STEVY', 'MBOMEYO NYABELA', '2014-04-19', 'OBALA', 'M', 'NYABELA CLAUDE PATRICK', '674716230', NULL, 'NGO NYOBE CHRISTELLE LAURE', '696614908', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:40:03', '2025-09-05 09:40:03', 1, '25A00452', 19, 0),
(480, 'NDASSI YANKAP DIVINE ARC ANGE', 'DIVINE ARC ANGE', 'NDASSI YANKAP', '2014-07-04', 'DOUALA', 'F', 'NDASSI GAEL DIMITRI', '675768173', NULL, 'KEPTCHOAC TEMBAP FLORIETTE STEPHANIE', '652141091', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:45:40', '2025-09-05 09:45:40', 1, '25A00453', 20, 0),
(481, 'FONTEM NANA LOÏC ARNAULD', 'LOÏC ARNAULD', 'FONTEM NANA', '2009-08-16', 'YAOUNDE', 'M', 'NANA CESAIRE MATHURIN', '677891977', NULL, 'BAKOP FONTEM EMMANUELLE NATHALIE', '671818281', NULL, NULL, NULL, 110, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:48:38', '2025-09-05 09:48:38', 1, '25A00454', 3, 0),
(482, 'PEG JOB MWAHA CHARLES HENRI', 'CHARLES HENRI', 'PEG JOB MWAHA', '2009-09-06', 'TOMEL', 'M', 'BIKATAL SINTAT LOUIS GABRIEL', NULL, NULL, 'BINOSOL BIYOM JEANNE', '655654663', NULL, NULL, NULL, 34, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:52:12', '2025-09-05 09:52:12', 1, '25A00455', 2, 0),
(483, 'TCHOMGANG NANA CHARANE NAVELIE', 'CHARANE NAVELIE', 'TCHOMGANG NANA', '2014-10-19', 'DOUALA', 'F', 'NANA SERGE ALAIN', '699470286', NULL, 'NOUMEBI SOPDJANG CAROLINE', '675831270', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:54:58', '2025-09-05 09:54:58', 1, '25A00456', 19, 0),
(484, 'MAGNE NOUMBISSI PRINCESSE', 'PRINCESSE', 'MAGNE NOUMBISSI', '2004-01-01', 'DOUALA', 'F', '.', '699470286', NULL, '.', '675831270', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 09:56:38', '2025-09-05 09:57:41', 1, '25A00457', 7, 0),
(485, 'NDJANA LYNDIE MARIE NOELLE', 'LYNDIE MARIE NOELLE', 'NDJANA', '2006-11-20', 'DOUALA', 'F', 'TJANG VINCENT', '699005534', NULL, 'NGO NGWEM JEANNE MICHEL', '691354786', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:00:52', '2025-09-05 10:00:52', 1, '25A00458', 26, 0),
(486, 'ANGONO ZAMBO GERMAINE FELICITE', 'GERMAINE FELICITE', 'ANGONO ZAMBO', '2005-06-15', 'MFOU', 'F', 'ZAMBO PHILIPE', '675563872', NULL, 'MBEZELE JEANNE', '682778028', NULL, NULL, NULL, 108, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:04:04', '2025-09-05 10:04:04', 1, '25A00459', 2, 0),
(487, 'APOLE MINKOS RUTH', 'RUTH', 'APOLE MINKOS', '2009-10-10', 'BIKA', 'F', 'MINKOS SILVIN', NULL, NULL, 'MARIANE', '655522811', NULL, NULL, NULL, 73, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:06:50', '2025-09-05 10:06:50', 1, '25A00460', 3, 0),
(488, 'EDIMO MPEDI LOUISE ROSINE', 'LOUISE ROSINE', 'EDIMO MPEDI', '2009-04-15', 'DOUALA', 'F', 'MPEDI PIERRE', '655195791', NULL, 'TOLLEN JOSEPHINE', '694534828', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:10:10', '2025-09-05 10:10:10', 1, '25A00461', 20, 0),
(489, 'MADONG MPEDI MADELEINE ROSE', 'MADELEINE ROSE', 'MADONG MPEDI', '2009-08-15', 'DOUALA', 'F', 'MPEDI PIERRE', '694534828', NULL, 'TOLLEN JOSEPHINE', '674532676', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:11:57', '2025-09-05 10:11:57', 1, '25A00462', 19, 0),
(490, 'NGUEFACK TSAMO IVANELLE DADIANE', 'IVANELLE DADIANE', 'NGUEFACK TSAMO', '2012-03-20', 'DSCHANG', 'F', 'TSAMO BARTHELEMY JAGETAN', '678783777', NULL, 'TSAFACK ARISTIDE SERAPHINE', '676260774', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:20:43', '2025-09-05 10:20:43', 1, '25A00463', 14, 0),
(491, 'NAYO JULIA ALLEGRESSE', 'JULIA ALLEGRESSE', 'NAYO', '2012-04-12', 'DOUALA', 'F', 'BEINDE PAUL', '657213314', NULL, 'FORTINA', '656123919', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:23:04', '2025-09-05 10:23:04', 1, '25A00464', 5, 0),
(492, 'DJAMI TCHAPDA HILARY', 'HILARY', 'DJAMI TCHAPDA', '2004-02-03', 'NKONGSAMBA', 'F', 'TRCHAPDA MBAKAM JEAN-BAPTISTE', NULL, NULL, 'WENKO PAULINE', '671600891', NULL, NULL, NULL, 107, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:27:00', '2025-09-05 10:27:00', 1, '25A00465', 5, 0),
(493, 'MOMHA GUILLAUME JUNIOR', 'GUILLAUME JUNIOR', 'MOMHA', '2013-06-29', 'EDEA', 'M', 'MOMHA MOMHA DIDIER FRANCIS', '693130691', NULL, 'NGO NDJENGWES JULIENNE', '690646128', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:28:56', '2025-09-05 10:28:56', 1, '25A00466', 21, 0),
(494, 'DJOMOU TALLA GESSICA ASHLEY', 'GESSICA ASHLEY', 'DJOMOU TALLA', '2011-08-02', 'BAHOUANG', 'F', 'TALLA YANNICK', '699219855', NULL, 'MATAFE YPERITTE', '674050807', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:32:54', '2025-09-05 10:32:54', 1, '25A00467', 10, 0),
(495, 'LOULOUGA ANNA FRANCHESCA MACHE', 'ANNA FRANCHESCA MACHE', 'LOULOUGA', '2007-09-05', 'BAFOUSSAM', 'F', 'BIKATAL SINTAT LOUIS GABRIEL', '691366048', NULL, 'BINOGOL BIYOM JEANNE', '655654663', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:36:28', '2025-09-05 10:36:28', 1, '25A00468', 4, 0),
(496, 'MOUNANANG ODODI GEOVANI', 'GEOVANI', 'MOUNANANG ODODI', '2008-05-30', 'BEKITO', 'M', 'ODODI JEAN CLAUDE', '699879782', NULL, 'BADAM SYLVIE', '671103204', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:49:01', '2025-09-05 10:49:01', 1, '25A00469', 27, 0),
(497, 'NZOUPET TCHAKTEGHE URIELLE PASCALLE', 'URIELLE PASCALLE', 'NZOUPET TCHAKTEGHE', '2007-04-08', 'BANGOU', 'F', 'TCHAKTEGHE DJEUKAM LANGEVIN', '696108956', NULL, 'TCHIMINI VIVI', '675903340', NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:52:38', '2025-09-05 10:52:38', 1, '25A00470', 5, 0),
(498, 'DJOUKA LINDA ANGE', 'LINDA ANGE', 'DJOUKA', '2009-04-15', 'DOUALA-CAMEROUN', 'F', 'TAKOUTSING ELIE', NULL, NULL, 'KENGNE DADILE', '682267049', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 10:56:07', '2025-09-05 10:56:07', 1, '25A00471', 18, 0),
(499, 'LIWA DJOGOTNA JOEL', 'JOEL', 'LIWA DJOGOTNA', '2014-10-23', 'GOBO', 'M', 'DJOGOTNA', '.', NULL, 'NDILINGA ANTOINETTE', '691235539', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 11:05:47', '2025-09-12 11:16:46', 1, '25A00472', 26, 0),
(500, 'BASSOM BRYAN SIEGFRIED', 'BRYAN SIEGFRIED', 'BASSOM', '2005-11-17', 'DOUALA', 'M', '.', '676046615', NULL, 'NGO BASSOM ANNETTE', '699495896', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 11:16:58', '2025-09-05 11:16:58', 1, '25A00473', 20, 0),
(501, 'MAFOMA BIDIAS YVANNE SYDRIQUE', 'YVANNE SYDRIQUE', 'MAFOMA BIDIAS', '2006-11-15', 'BAKOA', 'F', 'BIDIAS ESSING VINCENT', '657536768', NULL, 'KEYI MARIP THERESE', '697535965', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 11:46:17', '2025-09-05 11:46:17', 1, '25A00474', 12, 0),
(502, 'NGO BANG JULIENNE', 'JULIENNE', 'NGO BANG', '2008-09-13', 'EDEA', 'F', 'MOMHA PAUL HENRI', NULL, NULL, 'NGO TOLEN MAXEMILIENNE', '699212327', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 11:51:30', '2025-09-05 11:51:30', 1, '25A00475', 28, 0),
(503, 'AWOUO RAÏSSATOU', 'RAÏSSATOU', 'AWOUO', '2004-08-07', 'DOUALA', 'F', 'AWOUOYIEGNIGNI OUMAROU', '698612288', NULL, 'MANDOU AMSETOU', '696057401', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 11:55:52', '2025-09-05 11:55:52', 1, '25A00476', 5, 0),
(504, 'MEGNE MBE MELISSA SHANNELLE', 'MELISSA SHANNELLE', 'MEGNE MBE', '2010-04-20', 'DOUALA', 'F', '.', '679655722', NULL, 'TSEGUI MYRIAM CENDRINE', '672799362', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 11:58:57', '2025-09-05 11:58:57', 1, '25A00477', 21, 0),
(505, 'MATSINGOUM MBE MANUELLA ASHLEY', 'MANUELLA ASHLEY', 'MATSINGOUM MBE', '2013-07-09', 'DOUALA', 'F', 'MBE JEAN CLAUDE', '650771718', NULL, 'TSEGUI TSEGUI MYRIAM SANDRINE', '672799362', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 12:02:08', '2025-09-05 12:02:08', 1, '25A00478', 12, 0),
(506, 'BESSALA MBILI MARCELLE', 'MARCELLE', 'BESSALA MBILI', '2007-06-01', 'DOUALA', 'F', 'MBILI BIYIDI RAYMOND', '695958320', NULL, 'MBIA BESSALA MADELEINE', '696417600', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 12:11:59', '2025-09-05 12:11:59', 1, '25A00479', 19, 0),
(507, 'HAGBE MWAHA GERMAIN LORIK', 'GERMAIN LORIK', 'HAGBE MWAHA', '2008-01-12', 'DOUALA', 'M', '.', NULL, NULL, 'GWED MWAHA MARIE', '683263002', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 12:31:10', '2025-09-05 12:31:10', 1, '25A00480', 22, 0),
(508, 'YEPNJOUE DJATA DANIELLA ANGE', 'DANIELLA ANGE', 'YEPNJOUE DJATA', '2006-04-11', 'DOUALA', 'F', 'YAPNJOUO JUSTIN', '686695382', NULL, 'MBOULE ROSINE HERMELINE', '674769383', NULL, NULL, NULL, 39, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 12:33:47', '2025-09-05 12:33:47', 1, '25A00481', 1, 0),
(509, 'DOUDOU MAMOUDOU', 'MAMOUDOU', 'DOUDOU', '2006-10-10', 'GUIDER', 'F', 'MAMOUDOU ZOURMBA', '698112764', NULL, 'HAPSSATOU MIRDASSOU', NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 12:38:00', '2025-09-05 12:41:09', 1, '25A00482', 29, 0),
(510, 'FOFACK BOUAZA PAULE FRESCHENELLE', 'PAULE FRESCHENELLE', 'FOFACK BOUAZA', '2009-03-23', 'DOUALA', 'F', 'NGOMENDA FOFACK DIDIER', '680370888', NULL, 'MAGNE SOLANGE', '698636567', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 12:44:54', '2025-09-05 12:44:54', 1, '25A00483', 20, 0);
INSERT INTO `students` (`id`, `name`, `first_name`, `last_name`, `date_of_birth`, `place_of_birth`, `gender`, `parent_name`, `parent_phone`, `parent_email`, `mother_name`, `mother_phone`, `address`, `photo`, `subname`, `class_series_id`, `email`, `student_status`, `phone_number`, `birthday`, `birthday_place`, `sex`, `father_name`, `profession`, `status`, `is_new`, `is_active`, `created_at`, `updated_at`, `school_year_id`, `student_number`, `order`, `has_scholarship_enabled`) VALUES
(511, 'AGOUME NDOME VANINA CASSANDRA', 'VANINA CASSANDRA', 'AGOUME NDOME', '2013-01-26', 'DOUALA', 'F', 'NDOME ARMAND BLAISE', '690071170', NULL, 'NGONO AMOUGOU Epse NDOME', '691119979', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 12:53:05', '2025-09-05 12:55:53', 1, '25A00484', 22, 0),
(512, 'JIEPNOU KEPBU JOEL NATHAN', 'JOEL NATHAN', 'JIEPNOU KEPBU', '2015-10-12', 'DOUALA', 'M', 'KEPBU JIEPNOU', '676056679', NULL, 'NKONGUEP SANDRINE ULRICH', '656825973', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 13:01:59', '2025-09-12 11:16:34', 1, '25A00485', 25, 0),
(513, 'CHENO KEPBU ANGE DORETTE', 'ANGE DORETTE', 'CHENO KEPBU', '2010-09-08', 'DOUALA', 'F', 'KEPBU JIEPNOU RICHARD', '676056679', NULL, 'NKONGUEP SANDRINE ULRICH', '656825973', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-05 13:04:38', '2025-09-05 13:04:38', 1, '25A00486', 23, 0),
(514, 'ETOUNDI EYENGA MICHAEL', 'MICHAEL', 'ETOUNDI EYENGA', '2013-09-16', 'YAOUNDE', 'M', 'ESSAMA ETIENNE', '.', NULL, 'BEYALA ANTOINETTE', '683352444', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 06:54:08', '2025-09-06 06:54:08', 1, '25A00487', 23, 0),
(515, 'MVIGNE ABDOUL SALAM MOUHAMADOU', 'SALAM MOUHAMADOU', 'MVIGNE ABDOUL', '2009-06-20', 'DOUALA', 'M', 'MVIGNE ADAMOU', '690295999', NULL, 'MARIAM MOUHAMADOU', '.', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 06:59:56', '2025-09-06 07:04:15', 1, '25A00488', 13, 0),
(516, 'FANKA SEWOYEBAH VALMIE', 'VALMIE', 'FANKA SEWOYEBAH', '2014-04-11', 'BAMENDA', 'F', 'FANKA KINGSLY', '.', NULL, 'LORENDER JINGLA', '672948531', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 07:11:49', '2025-09-06 07:11:49', 1, '25A00489', 21, 0),
(517, 'NJOUOVE KUETCHE SANDRA', 'SANDRA', 'NJOUOVE KUETCHE', '2004-02-15', 'BANSOA', 'F', 'KUETCHE SAMUEL', '672116235', NULL, 'MADZO VIRGINETTE', '.', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 07:18:27', '2025-09-06 07:18:27', 1, '25A00490', 6, 0),
(518, 'MBEDE OMAIRE GLORIA GRACE', 'OMAIRE GLORIA GRACE', 'MBEDE', '2014-08-20', 'DOUALA', 'F', '.', '.', NULL, 'OLANG MBEDE CELESTIN', '694641034', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 07:22:53', '2025-09-12 11:16:15', 1, '25A00491', 24, 0),
(519, 'VOLONTE JEAN', 'JEAN', 'VOLONTE', '2005-01-12', 'NGOBO', 'M', 'MANSOU VICTOR', '691569210', NULL, 'MAIKITING BRIGITTE', '.', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 07:29:22', '2025-09-06 07:29:22', 1, '25A00492', 9, 0),
(520, 'HAOUA ROUKAYATOU IRISSOU', 'IRISSOU', 'HAOUA ROUKAYATOU', '2013-04-16', 'DOUALA', 'F', 'IDRISSOU ABDOULAYE', '691234472', NULL, 'RAHINATOU TALATOU', '699988247', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 07:35:16', '2025-09-12 11:16:01', 1, '25A00493', 23, 0),
(521, 'SANI GHONGA HILARIE PANGRAS', 'HILARIE PANGRAS', 'SANI GHONGA', '2008-06-18', 'BAKOU', 'M', 'GHONGA JEAN GEORGES', '671198631', NULL, 'SEN GISELE AIMEE', '650477021', NULL, NULL, NULL, 104, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 07:40:59', '2025-09-06 07:40:59', 1, '25A00494', 2, 0),
(522, 'NZEUFACK TSOPMEZA FRANSHESCA MARCELLA', 'FRANSHESCA MARCELLA', 'NZEUFACK TSOPMEZA', '2014-01-04', 'BAFANG', 'F', 'YNOU TSOPMEWA ARSENE', '671198631', NULL, 'DJA?BOU YOUNKAM JACQUELINE', '650477021', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 07:50:10', '2025-09-06 07:50:10', 1, '25A00495', 18, 0),
(523, 'GAMGNE FOGUE IRENE AUDREY GABRIELLE', 'IRENE AUDREY GABRIELLE', 'GAMGNE FOGUE', '2007-05-28', 'DOUALA', 'F', 'FOGUE JOSEPH RAYMOND', '.', NULL, 'MAWABO FOGUE NATHALIE LAURE', '693646291', NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 07:56:01', '2025-09-06 07:56:01', 1, '25A00496', 6, 0),
(524, 'BIKOY CLEMENTINE BIBIANE BELINDA', 'CLEMENTINE BIBIANE BELINDA', 'BIKOY', '2011-03-01', 'DOUALA', 'F', 'BIKOY PAUL BERTRAND', '.', NULL, 'EDOA NDI CHRISTINE ROSINE', '675508493', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 08:01:33', '2025-09-06 08:01:33', 1, '25A00497', 5, 0),
(525, 'TEMDEMNOU NDELE OLIVIER WARREN', 'OLIVIER WARREN', 'TEMDEMNOU NDELE', '2011-02-28', 'BAFANG', 'M', '.', '.', NULL, 'DJAMBOU YOUNKAM JACQUELINE', '671198631', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 08:08:37', '2025-09-06 08:08:37', 1, '25A00498', 14, 0),
(526, 'MENDOUGA ABESSOLO STEAVE LEVY', 'STEAVE LEVY', 'MENDOUGA ABESSOLO', '2011-09-14', 'YAOUNDE', 'M', 'KIARI BLAISE', '696945013', NULL, 'NGAH OLIVE', '691786962', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 08:13:17', '2025-09-06 08:13:17', 1, '25A00499', 6, 0),
(527, 'NGONGANG NKOUADO JESSICA DUCHELLE', 'JESSICA DUCHELLE', 'NGONGANG NKOUADO', '2007-11-07', 'DOUALA', 'F', 'NKOUADO TCHOUNKEU ELIE CALVIN', '699746930', NULL, 'KWEKEU MIMI GAELLE', '696250066', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 08:19:13', '2025-09-06 08:19:13', 1, '25A00500', 24, 0),
(528, 'NGAH ETABA BLANDINE', 'BLANDINE', 'NGAH ETABA', '0012-01-10', 'YAOUNDE', 'F', 'ETAPA SIMON', '694865871', NULL, 'NOMO NICOLE', '.', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 08:27:05', '2025-09-06 08:27:05', 1, '25A00501', 9, 0),
(529, 'NGO NSEGBE WANNYO RACHELLE SHEMARIA', 'RACHELLE SHEMARIA', 'NGO NSEGBE WANNYO', '2013-02-24', 'DOUALA', 'F', 'NSEGBE WAHNYO', '691542747', NULL, 'NGO MBEGBE ODILE SOLANGE', '650503536', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 08:36:39', '2025-09-06 08:36:39', 1, '25A00502', 7, 0),
(530, 'NNEMETHE LOUISE DINARA CELESTE', 'LOUISE DINARA CELESTE', 'NNEMETHE', '2009-09-14', 'DOUALA', 'F', 'BIDIMA MVONDO BLAISE', '699112265', NULL, 'MBIE HELENE', '671850705', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:02:44', '2025-09-06 09:05:58', 1, '25A00503', 29, 0),
(531, 'BISSA ABENG JANET LUNELLE', 'JANET LUNELLE', 'BISSA ABENG', '2004-01-01', 'DOUALA', 'F', 'ABENG MENGUE JOEL', '699045520', NULL, 'BIKIE ZANG MARIE BRUNA', '675361278', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:03:09', '2025-09-06 09:03:09', 1, '25A00504', 8, 0),
(532, 'MBONG MAFOUTO ERICA YAMA', 'ERICA YAMA', 'MBONG MAFOUTO', '2009-12-13', 'DOUALA', 'F', 'MBONG PIERRE MARCEL', '696890920', NULL, 'BOUCHEUIN TOGUEM GAELLE', '699586238', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:10:08', '2025-09-06 09:10:08', 1, '25A00505', 15, 0),
(533, 'TIOKOUP TIENKWA ANAIS SAMIRA', 'ANAIS SAMIRA', 'TIOKOUP TIENKWA', '2013-07-29', 'DOUALA', 'F', 'TIOKOUP CHRISPAIN DARTEAU', '652592872', NULL, 'MATUEGUE ALINE MYRIAM', '657869765', NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:10:19', '2025-09-06 09:11:55', 1, '25A00506', 5, 0),
(534, 'MAOUBE KEPDEP ABIGAELLE GARCIALE', 'ABIGAELLE GARCIALE', 'MAOUBE KEPDEP', '2010-07-17', 'DOUALA', 'F', 'KEPDEP WILLY PHILIPPE', '.', NULL, 'MATCHINDA ALVINE IRENE', '698844239', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:33:48', '2025-09-06 09:33:48', 1, '25A00507', 13, 0),
(535, 'TCHOUNKE TCHOUNKEU ESTELLA', 'ESTELLA', 'TCHOUNKE TCHOUNKEU', '2011-08-05', 'GUINNEE EQUATORIALE', 'F', 'TCHOUNKEU', '.', NULL, 'TCHOUNKEU', '698844239', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:38:29', '2025-09-06 09:38:29', 1, '25A00508', 8, 0),
(536, 'MOMANDJOA ANGE ARIELLE', 'ANGE ARIELLE', 'MOMANDJOA', '2013-02-28', 'DOUALA', 'F', 'ZEH GONGO BENOIT', '.', NULL, 'BEDJEME LAETITIA', '688832951', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:43:10', '2025-09-06 09:43:10', 1, '25A00509', 19, 0),
(537, 'CHOUANGA HELECK JOHNARA', 'JOHNARA', 'CHOUANGA HELECK', '2008-03-30', 'DOUALA', 'F', 'TCHOUANGA NGADJUI JEAN RENE', '678793067', NULL, 'DEMANOU MARIE BERTINE', '675739429', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:46:59', '2025-09-06 09:46:59', 1, '25A00510', 10, 0),
(538, 'MIHAMLE JOSEPHINE GLADICE MERVEILLE', 'JOSEPHINE GLADICE MERVEILLE', 'MIHAMLE', '2009-04-08', 'DOUALA', 'F', 'MIHAMLE VINCENT DE PAUL', '691565187', NULL, 'MIHAMLE ANNE ANDELA', '683871622', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 09:54:01', '2025-09-06 09:54:01', 1, '25A00511', 30, 0),
(539, 'AMINATOU ABDOUL NASSIR', 'NASSIR', 'AMINATOU ABDOUL', '2012-09-28', 'NGAOUNDERE', 'M', 'ABDOUL NASSIR', '674987499', NULL, 'MAMMA MARYAMOU', '.', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 10:02:16', '2025-09-06 10:02:16', 1, '25A00512', 24, 0),
(540, 'KOUMDA ATANGA EMMANUEL FREDERICK', 'EMMANUEL FREDERICK', 'KOUMDA ATANGA', '2013-01-24', 'DOUALA', 'M', 'ATANGA ONANA GUILLAUME', '696448810', NULL, 'NGONO KOU?DA ROSINE', '694692858', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 10:09:03', '2025-09-06 10:09:03', 1, '25A00513', 25, 0),
(541, 'NGOUNDI MARIELLA FELLICIA', 'MARIELLA FELLICIA', 'NGOUNDI', '2010-07-10', 'NGOLOMBEBE', 'F', 'AZED YSTEVE', NULL, NULL, 'KAKOUANDE GISELE', '679501501', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 11:09:08', '2025-09-06 11:09:08', 1, '25A00514', 9, 0),
(542, 'KOTAP GIRES', 'GIRES', 'KOTAP', '2006-08-05', 'BABADJOU', 'M', 'MSOUMA THIERRY SINCLAIR', NULL, NULL, 'YEMELONG MARIE', '670517542', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-06 11:13:48', '2025-09-06 11:13:48', 1, '25A00515', 7, 0),
(543, 'TABIA TANEH POWER VALDES', 'POWER VALDES', 'TABIA TANEH', '2006-05-27', 'DOUALA', 'M', 'TANEH GODFRED', NULL, NULL, 'YONGO CARINE', '676100597', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 04:21:15', '2025-09-09 04:21:15', 1, '25A00516', 10, 0),
(544, 'DJIME ERICKA SOFLANE', 'ERICKA SOFLANE', 'DJIME', '2014-07-15', 'MELONG', 'F', '.', '.', NULL, 'AICHA FENKEM EMMANUELLE', '672003334', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 05:40:19', '2025-09-09 05:40:19', 1, '25A00517', 20, 0),
(545, 'BELNOUTOU MBAKLA DESIRE', 'DESIRE', 'BELNOUTOU MBAKLA', '2007-05-16', 'DARGALA', 'M', 'SAR RAPHAEL', '697752125', NULL, 'AKOUNTI CHRISTINE', ',', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 05:51:10', '2025-09-09 05:51:10', 1, '25A00518', 8, 0),
(546, 'ADAH MBAKLA EVARIST', 'EVARIST', 'ADAH MBAKLA', '2006-04-06', 'DARGALA', 'M', 'SAR RAPHAEL', '697752125', NULL, 'AKOUNTI CHRISTINE', ',', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 05:55:42', '2025-09-09 05:55:42', 1, '25A00519', 11, 0),
(547, 'RAKIATOU ALI', 'ALI', 'RAKIATOU', '2008-06-05', 'DOUALA', 'F', 'ALI MOHAMAN TANKO', ',', NULL, 'AMINATOU ADAMA', '697105228', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:07:48', '2025-09-09 06:07:48', 1, '25A00520', 31, 0),
(548, 'MOUHAMAN LAWAL ALI', 'ALI', 'MOUHAMAN LAWAL', '2009-06-20', 'DOUALA', 'M', 'ALI MOHAMAN', ',', NULL, 'AMINATOU ADAMA', '697105228', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:11:19', '2025-09-09 06:11:19', 1, '25A00521', 18, 0),
(549, 'DJOUKA BOUZEKO CORALIE', 'CORALIE', 'DJOUKA BOUZEKO', '2009-07-14', 'DOUALA', 'F', 'BOUZEKO GILBERT', NULL, NULL, 'TEUMO YVETTE FLORENCE', '678255209', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:15:49', '2025-09-09 06:15:49', 1, '25A00522', 21, 0),
(550, 'POKAM YANN HERVE', 'YANN HERVE', 'POKAM', '2015-01-01', 'DOUALA', 'M', 'POKAM IGOR', '675337327', NULL, 'MEDIFO AGNES', '651770628', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:16:49', '2025-09-09 06:16:49', 1, '25A00523', 26, 0),
(551, 'TCHOULA ANGE MERVEILLE', 'MERVEILLE', 'TCHOULA ANGE', '2010-09-10', 'DOUALA', 'F', 'POKAM IGOR', ',', NULL, 'MEDIFFO AGNES', '678359578', NULL, NULL, NULL, 69, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:20:30', '2025-09-09 06:20:30', 1, '25A00524', 1, 0),
(552, 'ETEKE NYAME MELAMI', 'MELAMI', 'ETEKE NYAME', '2010-05-25', 'DSCHANG', 'F', 'EWANG NYAME BRICE MERLIN', '676246946', NULL, 'TCHOUDEU MIREILLE', '679313048', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:27:13', '2025-09-09 06:27:13', 1, '25A00525', 30, 0),
(553, 'ZINGA ATEBA VICTOR FERRY', 'VICTOR FERRY', 'ZINGA ATEBA', '2008-05-31', 'DOUALA', 'M', 'ZINGA ALAIN', ',', NULL, 'KASSA ANGELINE LYDIENNE', '658536491', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:31:27', '2025-09-09 06:31:27', 1, '25A00526', 14, 0),
(554, 'AYISSI AYISSI EMRICK', 'EMRICK', 'AYISSI AYISSI', '2005-09-04', 'NKOLESSONO', 'M', 'AYISSI NDZESSE POLYCARPE', '694650880', NULL, 'EYENGA SUZANNE', '689183233', NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:32:11', '2025-09-09 06:32:11', 1, '25A00527', 7, 0),
(556, 'NYA TCHILONG CAROLE', 'CAROLE', 'NYA TCHILONG', '2008-08-08', 'LOUM', 'M', 'TCHATOU NYA CELESTIN', '653392418', NULL, 'NANA JUDITH FLORE', '678955758', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:38:54', '2025-09-09 06:38:54', 1, '25A00529', 7, 0),
(557, 'MEJIO  KEPSU TRACY CHANAELLE', 'TRACY CHANAELLE', 'MEJIO  KEPSU', '2007-02-27', 'YAOUNDE', 'F', 'NJOMADJI JEAN BLAISE', '654777338', NULL, 'MEMEMKEN HORTENSE', ',', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:43:11', '2025-09-09 06:43:11', 1, '25A00530', 14, 0),
(558, 'PAFING ZOUA JULIO', 'JULIO', 'PAFING ZOUA', '2015-02-07', 'DOUALA', 'M', 'ZOUA ROBERT', '674875858', NULL, 'ABBO EMILIENNE', '697747494', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:45:09', '2025-09-09 06:45:09', 1, '25A00531', 22, 0),
(559, 'MBAKOP TCHASSEM ANDY DAVEN', 'ANDY DAVEN', 'MBAKOP TCHASSEM', '2010-04-12', 'DOUALA', 'M', 'TCHASSEM ERIC D\'AFFE', '675203011', NULL, 'TCHOUNGA TCHOUNDJEU EMMA', '697512349', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:49:57', '2025-09-09 06:49:57', 1, '25A00532', 5, 0),
(560, 'BEKONO DENISE MIRISSE', 'DENISE MIRISSE', 'BEKONO', '2014-06-15', 'YAOUNDE', 'F', 'SANDJO ARNAUD', '655316141', NULL, 'BEYALA GERALDINE', '676566914', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:52:50', '2025-09-09 06:52:50', 1, '25A00533', 23, 0),
(561, 'BASSONE PRECILLA YVONNE', 'PRECILLA YVONNE', 'BASSONE', '2014-11-25', 'EDEA', 'F', 'BASSONE MOUTANGO MARC NOEL', ',', NULL, 'MOUTANGO MARIE ANTOINETTE', '655579954', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:54:11', '2025-09-09 06:54:11', 1, '25A00534', 21, 0),
(562, 'MOUTCHEU DJONAKOUA ADORE STAELLE', 'ADORE STAELLE', 'MOUTCHEU DJONAKOUA', '2011-07-28', 'DOUALA', 'F', 'DJOUAKOUA DEUTOU ANSELME', '652117295', NULL, 'TCHAMEBE TCHUGOUALEU JEANNE FLORE', '652117295', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 06:57:21', '2025-09-09 06:57:21', 1, '25A00535', 31, 0),
(563, 'MOTIE TANKEU LUVINE', 'LUVINE', 'MOTIE TANKEU', '2014-11-06', 'DOUALA', 'F', 'TANKEU MICHEL', '675655527', NULL, 'NJOUMESSI MARCELINE', '659700780', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:02:30', '2025-09-09 07:06:06', 1, '25A00536', 1, 1),
(564, 'TONDI MAKAK REBECCA', 'REBECCA', 'TONDI MAKAK', '2009-09-15', 'DOUALA', 'F', 'MAKOK BLAISE', '696418334', NULL, 'KOME ALICE', ',', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:05:41', '2025-09-09 07:05:41', 1, '25A00537', 22, 0),
(565, 'OBONO MAKAK SYLVIE MURIEL', 'SYLVIE MURIEL', 'OBONO MAKAK', '2010-02-10', 'NGONGA', 'F', 'MAKOK BLAISE', '696418334', NULL, 'OBONO NDJAHA MARIE SYLVIE', ',', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:09:23', '2025-09-09 07:09:23', 1, '25A00538', 32, 0),
(566, 'ERINE BERTHE NGUEDA', 'NGUEDA', 'ERINE BERTHE', '2013-03-16', 'NIETE V3', 'F', 'FANMEN BEDROCIANT', '657071379', NULL, 'NOUBOUWA VICTOIRE', '656277913', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:10:55', '2025-09-09 07:10:55', 1, '25A00539', 22, 1),
(567, 'NYANGONO NDO ANNIL BRAD JADEL', 'ANNIL BRAD JADEL', 'NYANGONO NDO', '2010-07-15', 'ESSANGMVOUT', 'M', 'NDOZE STEVE LANDRY', '690922974', NULL, 'BIKA DARELLE ESTHER', '658868150', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:20:25', '2025-09-09 07:20:25', 1, '25A00540', 6, 0),
(568, 'NGANZEU PRUNELLE WENDY', 'PRUNELLE WENDY', 'NGANZEU', '2012-11-17', 'YAOUNDE', 'F', 'NJANGA EMMANUEL', '659012427', NULL, 'NTEMA LYDIENNE', '699960621', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:23:16', '2025-09-09 07:23:16', 1, '25A00541', 6, 0),
(569, 'MACHUM ANGE DOPHINE', 'ANGE DOPHINE', 'MACHUM', '2014-06-18', 'BAHAM', 'F', ',', ',', NULL, 'GUIAMEUGNE NOUBISSI ALICE', ',', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:26:32', '2025-09-09 07:27:13', 1, '25A00542', 23, 1),
(570, 'DJOUEDJONG KAME CHARLAINE', 'CHARLAINE', 'DJOUEDJONG KAME', '2006-04-10', 'BAMENDJOU', 'F', 'KAMELA LAZARE', '677478892', NULL, 'TCHINDA CHRISTIANE', ',', NULL, NULL, NULL, 69, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:35:09', '2025-09-09 07:35:09', 1, '25A00543', 2, 0),
(571, 'NGOBEM JOSEPHA CHLOE', 'JOSEPHA CHLOE', 'NGOBEM', '2009-09-07', 'DOUALA', 'F', 'BEM FAUSTIN', ',', NULL, 'BANONHAG VERONIQUE', '693523862', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:40:21', '2025-09-09 07:40:21', 1, '25A00544', 33, 0),
(572, 'HELE ZEUKENG FRANCOISE EDOUARDA', 'FRANCOISE EDOUARDA', 'HELE ZEUKENG', '2012-10-16', 'DOUALA', 'F', 'ZEUKENG NGOUTSOP LEVIS ARMAND', '656003.174', NULL, 'HELE YEKEME ODETTE', '697339097', NULL, NULL, NULL, 110, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:44:58', '2025-09-09 07:44:58', 1, '25A00545', 4, 0),
(573, 'TANOU TCHOUMI OCEANE BEYONCE', 'OCEANE BEYONCE', 'TANOU TCHOUMI', '2010-05-21', 'DOUALA', 'F', 'TCHOUMI EMMANUEL', '691080669', NULL, 'DJOMOU BERTHE CLAIRE', '693257776', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:48:03', '2025-09-09 07:48:03', 1, '25A00546', 16, 0),
(574, 'EBOUMBOU RICHARDE PLANEDIE GRÂCE', 'RICHARDE PLANEDIE GRÂCE', 'EBOUMBOU', '2012-09-17', 'DOUALA', 'F', 'EBOUMBOU ESSOKE RICHARD ERIC', '676994387', NULL, 'NWANAK LYDIE GILBERTE', '694798920', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:48:55', '2025-09-09 07:48:55', 1, '25A00547', 10, 0),
(575, 'LADO TSASSE JENNY ANGE', 'JENNY ANGE', 'LADO TSASSE', '2011-08-28', 'DOUALA', 'F', 'FOMEKON TSASSE NECTOR', '675010772', NULL, 'MAFFO TCHOUPO JOSIANE', '671737054', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:53:19', '2025-09-09 07:53:19', 1, '25A00548', 2, 0),
(576, 'NEPONG OMBALA EL SHALOM', 'EL SHALOM', 'NEPONG OMBALA', '2010-10-16', 'DOUALA', 'F', 'OMBALA MOUKOUNDI RENE', '690112013', NULL, 'ENANIKOUL BABONG PAULINE', ',', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 07:58:03', '2025-09-09 07:58:03', 1, '25A00549', 34, 0),
(577, 'NKADA BILOA VICTOIR DE GRACE', 'VICTOIR DE GRACE', 'NKADA BILOA', '2012-01-21', 'DOUALA', 'M', 'BILOA RIGOBERT', NULL, NULL, 'NKADA EBODE MARIE CLAIRE', '695120588', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:06:41', '2025-09-09 08:06:41', 1, '25A00550', 15, 0),
(578, 'WOUEMBE JOACHIM SAMUEL VICTORIRE', 'JOACHIM SAMUEL VICTORIRE', 'WOUEMBE', '2007-07-06', 'DOUALA', 'M', 'WOUEMBE HENRI', '693639953', NULL, 'EYANGO FELECITE WOUEMBE', '.', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:06:45', '2025-09-09 08:06:45', 1, '25A00551', 25, 0),
(579, 'ONGOLO BILOA JEAN NATANAELLE', 'JEAN NATANAELLE', 'ONGOLO BILOA', '2013-09-29', 'DOUALA', 'M', 'BILOA RIGOBERT', NULL, NULL, 'NKADA EBODE MARIE CLAIRE', '695120588', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:10:03', '2025-09-09 08:10:03', 1, '25A00552', 15, 0),
(580, 'SIKOMPE TALA PRINCE ALEXE', 'PRINCE ALEXE', 'SIKOMPE TALA', '2013-06-18', 'BAFOUSSAM', 'M', 'FOTIE MICHEL', '653104652', NULL, 'MEKUE AMANDINE', '677688912', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:13:07', '2025-09-09 08:13:07', 1, '25A00553', 15, 0),
(581, 'MBONDO MICHEL MOISE', 'MICHEL MOISE', 'MBONDO', '2013-08-26', 'MANOYOI', 'M', 'MBONDO MICHEL', '697995946', NULL, 'NGO GWET AGNES', '676295764', NULL, NULL, NULL, 78, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:20:16', '2025-09-09 08:20:16', 1, '25A00554', 2, 0),
(582, 'KOUAM SIMEU RYTHA FLORE', 'RYTHA FLORE', 'KOUAM SIMEU', '2012-01-22', 'DOUALA', 'F', 'SIMEU KOUAMEGNI BERTRAND F.', '653798128', NULL, 'KUISSEU ESTHER', '699795554', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:22:40', '2025-09-09 08:22:40', 1, '25A00555', 17, 0),
(583, 'DJANTHE KOUAMOU MAHEL DIMITRY', 'MAHEL DIMITRY', 'DJANTHE KOUAMOU', '2010-03-08', 'LOUM', 'M', 'SIMEU KOUAMEGNI BERTRAND F.', '653738128', NULL, 'KUISSEU ESTHER', '699795554', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:24:51', '2025-09-09 08:24:51', 1, '25A00556', 27, 0),
(584, 'KEPGANG SIMEU PRISCA SERENA', 'PRISCA SERENA', 'KEPGANG SIMEU', '2009-04-24', 'DOUALA', 'F', 'SIMEU KOUAMEGNI BERTRAND FERDINAND', '653738128', NULL, 'KUISSEU ESTHER', '699795554', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:27:14', '2025-09-09 08:27:14', 1, '25A00557', 18, 0),
(585, 'NOUMBISSI FOTSO AUGUSTIN BRICE', 'AUGUSTIN BRICE', 'NOUMBISSI FOTSO', '2009-08-25', 'BANDJOUN', 'M', 'FOTSO WAMBA GUY', '675699587', NULL, 'MODJOM HUGUETTE FLORE', '679292496', NULL, NULL, NULL, 78, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:38:16', '2025-09-09 08:38:16', 1, '25A00558', 3, 0),
(586, 'KENGNE FOTSO FRANCHESCA LADOUCE', 'FRANCHESCA LADOUCE', 'KENGNE FOTSO', '2012-04-25', 'DOUALA', 'M', 'FOTSO WAMBA GUY', '675699587', NULL, 'MODJOM HUGUETTE FLORE', '679292496', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:46:25', '2025-09-09 08:46:25', 1, '25A00559', 16, 0),
(587, 'NDZIE HONORINE CHERIDANNE', 'HONORINE CHERIDANNE', 'NDZIE', '2011-08-09', 'DOUALA', 'M', 'NGNEDOP NOUMEGNE JISLIN', '.', NULL, 'ONANA ELISABETH NADINE', '679390938', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 08:49:28', '2025-09-09 08:49:28', 1, '25A00560', 28, 0),
(588, 'NGWELIH KAKAMOU RUBIE KAYLA', 'RUBIE KAYLA', 'NGWELIH KAKAMOU', '2011-06-04', 'DOUALA', 'F', 'KAKAMOU RENE PAULIN', '650767877', NULL, 'TCHIBONWI MBOUMEKANG ALINE STEVE', '656639819', NULL, NULL, NULL, 99, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:11:13', '2025-09-09 09:11:13', 1, '25A00561', 1, 0),
(589, 'MAWAMBA BAH ANGE NEFERTITIE', 'ANGE NEFERTITIE', 'MAWAMBA BAH', '2007-02-01', 'DOUALA', 'F', 'BAH JEAN', NULL, NULL, 'NOUMESSI MARIE NOEL', '670561935', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:26:33', '2025-09-09 09:26:33', 1, '25A00562', 26, 0),
(590, 'MINWA VOUNDJABA MIREILLE', 'MIREILLE', 'MINWA VOUNDJABA', '2006-10-29', 'GOBO', 'F', 'VOUNDJABA BARNABAS', '698612261', NULL, 'HAMADA RACHEL', NULL, NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:35:43', '2025-09-09 09:35:43', 1, '25A00563', 11, 0),
(591, 'FITOUA VOUNDJABA EVELINE', 'EVELINE', 'FITOUA VOUNDJABA', '2012-07-17', 'GALAM', 'F', 'VOUNDJABA BARNABAS', '698612261', NULL, 'HAMADA RACHEL', NULL, NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:37:21', '2025-09-09 09:37:21', 1, '25A00564', 19, 0),
(592, 'MBOSADI HINFIA VOUNDJABA ODETTE', 'ODETTE', 'MBOSADI HINFIA VOUNDJABA', '2008-12-30', 'GOBO', 'F', 'VOUNDJABA BARNABAS', '698612261', NULL, 'HAMADA RACHEL', NULL, NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:39:20', '2025-09-09 09:39:20', 1, '25A00565', 15, 0),
(593, 'YEMELE NGUEGHA\'A TRESOR WILFRID', 'TRESOR WILFRID', 'YEMELE NGUEGHA\'A', '2014-08-16', 'BALEVENG', 'M', 'NGUEGHA KENGNI JEAN MARIE', '678357691', NULL, 'FELIFACK MARIE NOEL', '653531852', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:44:33', '2025-09-09 09:44:33', 1, '25A00566', 3, 0),
(594, 'NJOYA NJIGMBOU DOLORESSE LOVE', 'DOLORESSE LOVE', 'NJOYA NJIGMBOU', '2009-09-08', 'MANENGOLE', 'F', 'NJIGMBOU ANDRE MERLIN', '696511751', NULL, 'TCHADIEU ALPHONSINE', '678010581', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:55:16', '2025-09-09 09:55:16', 1, '25A00567', 23, 0),
(595, 'POUSSEU KOUAMINI RICHINELLE LANDRY', 'RICHINELLE LANDRY', 'POUSSEU KOUAMINI', '2008-08-13', 'YAOUNDE', 'M', 'KOUAMINI HERVE', '696511751', NULL, 'KOUAMOU FLORETTE LAURE', '678010581', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:57:02', '2025-09-09 09:57:02', 1, '25A00568', 24, 0),
(597, 'YEPJOUO NJIGMBOU GRACE DIVINE', 'GRACE DIVINE', 'YEPJOUO NJIGMBOU', '2012-01-10', 'MANENGOLE', 'F', 'NJIGMBOU ANDRE MERLIN', '678010581', NULL, 'TCHADIEU ALPHONSINE', '696511751', NULL, NULL, NULL, 81, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 09:59:08', '2025-09-09 09:59:08', 1, '25A00570', 2, 0),
(598, 'KAGO KAKAPEN MARIE EVELINE', 'MARIE EVELINE', 'KAGO KAKAPEN', '2009-06-03', 'DOUALA', 'F', 'KEPAN MARCELIN', NULL, NULL, 'AMIDOU MARIELLE', '677553778', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:03:12', '2025-09-09 10:03:12', 1, '25A00571', 27, 0),
(599, 'MPOT DAVID LE ROI', 'LE ROI', 'MPOT DAVID', '2011-03-05', 'POUMA', 'M', '.', '.', NULL, 'KONGUE ALICE LAURE', '656077330', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:03:13', '2025-09-09 10:03:13', 1, '25A00572', 20, 0),
(600, 'TOUMTCHEJOU NOAH ANAS', 'ANAS', 'TOUMTCHEJOU NOAH', '2014-08-13', 'DOUALA', 'M', 'TOUMTCHJOU MBUYA GUY', '671986323', NULL, 'MAGNE TALING', '679780576', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:06:48', '2025-09-09 10:06:48', 1, '25A00573', 24, 1),
(601, 'AÏCHATOU AHMADOU', 'AHMADOU', 'AÏCHATOU', '2009-06-10', 'BANYO', 'F', 'AHMADOU DAHIROU', '676220606', NULL, 'HAOUA MOHAMADOU', '699651678', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:08:45', '2025-09-09 10:08:45', 1, '25A00574', 14, 0),
(602, 'KASSEBE CHARLINE', 'CHARLINE', 'KASSEBE', '2011-05-12', 'BABADJOU', 'F', 'TSOYIMO DUPLEX MAJOR', '678000146', NULL, 'MATANG CLARISSE', '.', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:14:07', '2025-09-09 10:14:07', 1, '25A00575', 7, 0),
(603, 'NGONO AMOUGOU ASHLEY LYNSAID', 'ASHLEY LYNSAID', 'NGONO AMOUGOU', '2008-06-15', 'L\'HÔPITAL DE DISTRICT DE MBALMAYO', 'F', '.', NULL, NULL, 'CHUMENI MARIE CLAIRE', '650665180', NULL, NULL, NULL, 104, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:16:28', '2025-09-09 10:16:28', 1, '25A00576', 3, 0),
(604, 'AYI NGONO AMOUGOU LESLY MAELLE', 'LESLY MAELLE', 'AYI NGONO AMOUGOU', '2006-06-13', 'MBALMAYO', 'F', 'AMOUGOU ELANGA SIMEON', NULL, NULL, 'CHUMENI MARIE CLAIRE', '650665180', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:18:36', '2025-09-09 10:18:36', 1, '25A00577', 4, 0),
(605, 'LEUMENI AMOUGOU LEÏLA ARMELLE', 'LEÏLA ARMELLE', 'LEUMENI AMOUGOU', '2011-02-09', 'MBALMAYO', 'F', 'AMOUGOU ELANGA SIMEON', NULL, NULL, 'CHUMENI MARIE CLAIRE', '650665180', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:20:28', '2025-09-09 10:20:28', 1, '25A00578', 6, 0),
(606, 'MINCHE MAFOKA ALI CHAHIDA', 'CHAHIDA', 'MINCHE MAFOKA ALI', '2013-04-07', 'KOUPA-MATAPIT', 'F', 'ALI', '699903916', NULL, 'KENFACK JEANNETTE', '.', NULL, NULL, NULL, 71, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:26:13', '2025-09-09 10:26:13', 1, '25A00579', 5, 0),
(607, 'TCHAMBA TCHUITET OCEANE DIVINE', 'OCEANE DIVINE', 'TCHAMBA TCHUITET', '2009-03-23', 'DOUALA', 'F', 'TCHUITET KOUOPLONT ERIC DIVINE', '697110103', NULL, 'TCHATO GNAFA BENEDICTE', '689977401', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:29:19', '2025-09-09 10:29:19', 1, '25A00580', 35, 0),
(608, 'MVOTTO BINYET ESTHER', 'ESTHER', 'MVOTTO BINYET', '2010-05-08', 'TOUE', 'F', 'BINYET PHILIPPE', '.', NULL, 'ESANEME MBENG', '699107191', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:29:35', '2025-09-09 10:29:35', 1, '25A00581', 36, 0),
(609, 'WOPIWO KUETCHEU MERVEILLE FORTUNE', 'MERVEILLE FORTUNE', 'WOPIWO KUETCHEU', '2010-11-11', 'BOSSOSSIA', 'F', 'KUETCHEU XAVIER', '.', NULL, 'DJOUMESSI DJUENCKEN GHISLEINE', '656970953', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:33:41', '2025-09-09 10:33:41', 1, '25A00582', 37, 0),
(610, 'BAHANE CHRISTINE', 'CHRISTINE', 'BAHANE', '2012-07-04', 'YAOUNDE', 'F', 'DJOY-YONG PHILIPPE', '.', NULL, 'NADA BERNADETTE', '693273658', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:42:42', '2025-09-09 10:42:42', 1, '25A00583', 4, 0),
(611, 'MENGUE TCHOUMEGNI SALOME CARINE', 'SALOME CARINE', 'MENGUE TCHOUMEGNI', '2008-11-11', 'DOUALA', 'F', 'TCHOUMEGNI FREDERIC', '675058294', NULL, 'ESSA\'A LAURENTINE', '.', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:46:42', '2025-09-09 10:46:42', 1, '25A00584', 16, 0),
(612, 'NJOCK PETAT ALAIN VIDAL', 'ALAIN VIDAL', 'NJOCK PETAT', '2008-08-09', 'DOUALA', 'M', 'NGASSA PETAT ECLATOR', '690531147', NULL, 'NJOCK JEANNE NATALIE', '.', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 10:51:21', '2025-09-09 10:51:21', 1, '25A00585', 28, 0),
(613, 'NGO BIYONG MARCELINE STELLA', 'MARCELINE STELLA', 'NGO BIYONG', '2011-04-20', 'BAFOUSSAM', 'F', 'MBEIGER SAMUEL', '692560685', NULL, 'MADE FLORICE CLAIR', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:00:35', '2025-09-09 11:00:35', 1, '25A00586', 38, 0),
(614, 'MAKUETE MBUETDA PRINCESSE MANUELA', 'PRINCESSE MANUELA', 'MAKUETE MBUETDA', '2009-06-17', 'MBOUDA', 'F', 'TADO MBUETDA DUPLUX', '675646662', NULL, 'MBOUE LILIANE', '.', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:04:57', '2025-09-09 11:04:57', 1, '25A00587', 8, 0),
(615, 'DJOMO NGNEDOP FLORE DESMONNE', 'FLORE DESMONNE', 'DJOMO NGNEDOP', '2008-10-13', 'DOUALA', 'F', 'NGNEDOP JISLIN', '679151746', NULL, 'ONANA ELISABETH NADINE', '651256322', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:17:44', '2025-09-09 11:17:44', 1, '25A00588', 2, 0),
(616, 'DJEUMENI KAMENI ALAIN JUNIOR', 'ALAIN JUNIOR', 'DJEUMENI KAMENI', '2014-01-11', 'DOUALA', 'M', 'KAMENI GERARD', '.', NULL, 'KEUMO NOMBO NADEGE', '692757502', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:19:13', '2025-09-09 11:19:13', 1, '25A00589', 5, 0),
(617, 'AÏSSATOU SOUMAYATA MALPETEL', 'MALPETEL', 'AÏSSATOU SOUMAYATA', '2013-02-05', 'MEIGANGA', 'F', 'MOHAMADOU BASSIROU', '678306800', NULL, 'DIA FADIMATOU', '697406046', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:23:13', '2025-09-09 11:23:13', 1, '25A00590', 24, 0),
(618, 'SABERI BIDJA LESLY PHAREL', 'LESLY PHAREL', 'SABERI BIDJA', '2010-03-30', 'YAOUNDE', 'F', 'BIDJA JEAN CALVIN', '657973379', NULL, 'SOMOU JOSIANE', '.', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:24:28', '2025-09-09 14:06:33', 1, '25A00591', 19, 0),
(619, 'MADELEINE KLOE COLOMBE MOUNLOM', 'MOUNLOM', 'MADELEINE KLOE COLOMBE', '2009-10-21', 'DOUALA', 'F', '.', '.', NULL, 'NGO  MOUNLOM MADELEINE CLEO', '656110651', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:27:56', '2025-09-09 11:27:56', 1, '25A00592', 39, 0),
(620, 'NAZIRA HADJI', 'HADJI', 'NAZIRA', '2013-05-14', 'BANGUI', 'F', 'HADJI YACOUB ABIB', '651165213', NULL, 'KOUNDA DABOUKIANGO SYLVIE', '697206270', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:33:46', '2025-09-09 11:33:46', 1, '25A00593', 6, 0),
(621, 'ENYEGUE ONDOA MARIE CHRISTINE', 'MARIE CHRISTINE', 'ENYEGUE ONDOA', '2012-06-08', 'YAOUNDE', 'F', 'ONDOA NDONG CYRIL', '659905622', NULL, 'MENYE EMBOGO MICHEL DIANE', '656468317', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:37:27', '2025-09-09 11:37:27', 1, '25A00594', 21, 0),
(622, 'NKOBEU SIPEUWA CHRIST CABREL', 'CHRIST CABREL', 'NKOBEU SIPEUWA', '2008-09-13', 'DOUALA', 'M', 'PENDA LAURAN', '691222816', NULL, 'KEMEGNE CHANTAL', '696735135', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:38:31', '2025-09-09 11:38:31', 1, '25A00595', 40, 0),
(623, 'KELBA BEBI HANNA ELIE', 'HANNA ELIE', 'KELBA BEBI', '2005-12-20', 'BONGANDO', 'F', 'MEYOU BEBI', '670101763', NULL, 'AISSATOU ZOUBAINATOU', '.', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:42:42', '2025-09-09 11:42:42', 1, '25A00596', 22, 0),
(624, 'NKWENDA MEDJONNAG FRANCHESCA BAYLLA', 'FRANCHESCA BAYLLA', 'NKWENDA MEDJONNAG', '2015-04-01', 'DOUALA', 'F', 'MEDJONNAG PATRICK', '671360302', NULL, 'NGONGANG GERADYTE', '.', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 11:47:51', '2025-09-09 11:50:00', 1, '25A00597', 25, 1),
(625, 'EMADE EPANDA GERMAINE', 'GERMAINE', 'EMADE EPANDA', '2006-11-05', 'DOUALA', 'F', 'EPANDA EBOUACK PIERRE', '696126363', NULL, 'EDJAME PHILOMENE', '697161505', NULL, NULL, NULL, 39, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:00:31', '2025-09-09 12:00:31', 1, '25A00598', 2, 0),
(626, 'NANGA LOMBO MAEL', 'MAEL', 'NANGA LOMBO', '2012-07-05', 'YAOUNDE', 'M', 'LOMBO', '698754291', NULL, 'MVENG', '.', NULL, NULL, NULL, 77, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:06:38', '2025-09-09 12:06:38', 1, '25A00599', 3, 0),
(627, 'ZE BITCHO JEAN RIVELY', 'JEAN RIVELY', 'ZE BITCHO', '2011-11-18', 'EBOLOWA', 'M', 'ZE ZE ELVIS', '691303545', NULL, 'NSASSO CHRISTELLE', '.', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:12:22', '2025-09-09 12:12:22', 1, '25A00600', 8, 0),
(628, 'BRUXEL DE MEKA. .', '.', 'BRUXEL DE MEKA.', '2010-04-13', 'DOUALA', 'M', 'MEYIFI STEPHANE', '679526002', NULL, 'NYEMECK MEYIFI', '.', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:16:37', '2025-09-09 12:16:37', 1, '25A00601', 9, 0),
(629, 'APOUFOH-TETANG AZER', 'AZER', 'APOUFOH-TETANG', '2012-07-09', 'DOUALA', 'M', 'TETANG CLAUDE', '.', NULL, 'NGUEMENGNE-TAYOU ARMEL', '.', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:24:09', '2025-09-09 12:24:09', 1, '25A00602', 25, 1),
(630, 'TETE ANNE ELODIE', 'ANNE ELODIE', 'TETE', '2007-02-02', 'DOUALA', 'F', 'NINTCHEU OLIVIER', '692085853', NULL, 'BINZE BANEN ELISABETH', '683689656', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:24:28', '2025-09-09 12:24:28', 1, '25A00603', 25, 0),
(631, 'DOUKI FOTSING EVA CAROLINA', 'EVA CAROLINA', 'DOUKI FOTSING', '2011-02-09', 'DOUALA', 'F', 'FOTSING', '678046034', NULL, 'EPOTER EPOTER', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:36:04', '2025-09-09 12:36:04', 1, '25A00604', 41, 0),
(632, 'GNETCHOKO FOTSING CARELLE LESLY', 'CARELLE LESLY', 'GNETCHOKO FOTSING', '2008-05-05', 'DOUALA', 'F', 'FOTSING', '678046034', NULL, 'EPOTER EPOTER', '.', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:42:34', '2025-09-09 12:42:34', 1, '25A00605', 29, 0),
(633, 'MATANGO MBAPTE ESTRELA KARMENE', 'ESTRELA KARMENE', 'MATANGO MBAPTE', '2008-04-29', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:47:02', '2025-09-09 12:47:02', 1, '25A00606', 9, 0),
(634, 'TCHOUENDOUM LUCRESSE DANILA', 'LUCRESSE DANILA', 'TCHOUENDOUM', '2007-02-02', 'BAMENDJOU', 'F', 'WABO SYLVAIN', '.', NULL, 'MEGU MOUAFO MARTHE', '682994217', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:52:42', '2025-09-09 12:52:42', 1, '25A00607', 30, 0),
(635, 'SOMO VINCENT ROMEO', 'VINCENT ROMEO', 'SOMO', '2012-08-16', 'DOUALA', 'M', 'NJOYA FANSEU HERVE', '699174025', NULL, 'ONGBABOUGUEK SOMO CHANEL', '655796433', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 12:58:49', '2025-09-09 12:58:49', 1, '25A00608', 7, 0),
(636, 'TAMKEU BATCHAHAN MYRIAM NAOMIE', 'MYRIAM NAOMIE', 'TAMKEU BATCHAHAN', '2006-12-21', 'DOUALA', 'F', 'BATCHAHAN RAOUL', '651369053', NULL, 'TANCHOU NYA NATHALIE', '651366802', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:05:47', '2025-09-09 13:05:47', 1, '25A00609', 32, 0),
(637, 'MOUSSA YAMBE ROSALIE MERVEILLE', 'ROSALIE MERVEILLE', 'MOUSSA YAMBE', '2014-12-09', 'LIMBE', 'F', 'MOUSSA GAULE JOHN WICKLIF', '693829225', NULL, 'NANGA GEORGETTE', '670499292', NULL, NULL, NULL, 71, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:11:02', '2025-09-09 13:11:02', 1, '25A00610', 6, 0),
(638, 'KOUAYEP EZOM MEGANE FORTUNE', 'MEGANE FORTUNE', 'KOUAYEP EZOM', '2007-01-29', 'DOUALA', 'F', 'EZOM MBA', '.', NULL, 'KOUAYEP', '677287245', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:12:05', '2025-09-09 13:12:05', 1, '25A00611', 33, 0),
(639, 'YOUGANG TAGHEU SHARON ROSE', 'SHARON ROSE', 'YOUGANG TAGHEU', '2013-01-29', 'DOUALA', 'F', 'TAGHEU MICHEL', '677501874', NULL, 'KEMOE JEANNET', '656656502', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:21:45', '2025-09-09 13:21:45', 1, '25A00612', 17, 0),
(640, 'BELIGUEU KAMDOUM ANGE BLONDELLE', 'ANGE BLONDELLE', 'BELIGUEU KAMDOUM', '2007-05-18', 'DOUALA', 'F', 'KAMDOUM WESSIKOUE MATHURIN', '678013094', NULL, 'MATANA CLARISSE', '653012756', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:32:01', '2025-09-09 13:32:01', 1, '25A00613', 8, 0),
(641, 'KEUMBOU NJIKE MARTHE JESSIE', 'MARTHE JESSIE', 'KEUMBOU NJIKE', '2012-02-07', 'BATCHAM', 'F', 'KEUMBOU PHILBERT', '675274887', NULL, 'TEMGOUA HENRIETTE LAURE', '653658935', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:32:13', '2025-09-09 13:32:13', 1, '25A00614', 15, 0),
(642, 'MADENG MBOCK ANGE CALINCA', 'ANGE CALINCA', 'MADENG MBOCK', '2006-06-16', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:37:05', '2025-09-09 13:37:05', 1, '25A00615', 26, 0),
(643, 'LENG JOUN NGAH NDIZLI EVORA', 'NDIZLI EVORA', 'LENG JOUN NGAH', '2007-08-26', 'BAFOUSSAM', 'F', 'MBOU ISSAC', '674545581', NULL, 'NZUEPOUO', '677499901', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:42:15', '2025-09-11 13:16:07', 1, '25A00616', 55, 0),
(644, 'NJIFON MOUHANAD ISDINE', 'ISDINE', 'NJIFON MOUHANAD', '2013-03-28', 'DOUALA', 'M', 'NJIFON IBRAHIM', '698064361', NULL, 'RAYE NGAMILIYA SALAMATOU', '697513529', NULL, NULL, NULL, 78, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:47:49', '2025-09-09 13:47:49', 1, '25A00617', 4, 0),
(645, 'CLARA MANKA\'A NDOUTOU', 'MANKA\'A NDOUTOU', 'CLARA', '2010-12-01', 'DOUALA', 'F', 'MBONG NDOUTOU JOSEPH', NULL, NULL, 'LILIAN NCHANG NGWA', '675641941', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:51:15', '2025-09-09 13:51:15', 1, '25A00618', 20, 0),
(646, 'MAKOUETE LUDIVINE STELLA', 'LUDIVINE STELLA', 'MAKOUETE', '2010-01-14', 'MBOUDA', 'F', 'DAKMETA PELA ERIC', '.', NULL, 'GOUMELA NICOLE', '651377345', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-09 13:52:38', '2025-09-09 13:52:38', 1, '25A00619', 42, 0),
(648, 'NYEMECK NWAHA CHRISTHOPE', 'CHRISTHOPE', 'NYEMECK NWAHA', '2012-06-30', 'DOUALA', 'M', '.', '.', NULL, 'GWED NWAHA MARIE GILBERTE', '683263002', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:00:10', '2025-09-10 05:00:10', 1, '25A00621', 29, 0),
(649, 'NGO OUM FRANCOISE CHANCELINE', 'FRANCOISE CHANCELINE', 'NGO OUM', '2007-03-10', 'YAOUNDE', 'F', 'NDJIE PAUL', '697495159', NULL, 'LISSOM MARIE ROSE', '.', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:09:18', '2025-09-10 05:09:18', 1, '25A00622', 34, 0),
(650, 'NGUIWOUA BELVINE', 'BELVINE', 'NGUIWOUA', '2006-01-11', 'DOUALA', 'F', 'NGOULA PAUL', '654748340', NULL, 'MAFFO BERNADETTE', NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:16:15', '2025-09-10 05:16:15', 1, '25A00623', 35, 0),
(651, 'ETAME JOSEPH NATHAN FARID', 'JOSEPH NATHAN FARID', 'ETAME', '2012-08-15', 'DOUALA', 'M', 'BIKEI-FILS', '679626679', NULL, 'IMBIKO\'O', '699282244', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:20:32', '2025-09-10 05:20:32', 1, '25A00624', 16, 0),
(652, 'KUAWA MAFFO DARELLE', 'DARELLE', 'KUAWA MAFFO', '2011-10-11', 'BAFOUSSAM', 'F', 'KUAWA MOKO BLAISE', '.', NULL, 'BETBOUE RAISSA', '694299797', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:23:38', '2025-09-10 05:23:38', 1, '25A00625', 7, 0),
(653, 'KAMGA DJEUGOUE ERIC DUVAL', 'ERIC DUVAL', 'KAMGA DJEUGOUE', '2003-01-30', 'TILLO DIBOMBARI', 'M', 'DJEUGOUE JEAN VINCENT', '677033116', NULL, 'APOUHELA DJEUGOUE JUDITH', '679324532', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:33:40', '2025-09-10 05:33:40', 1, '25A00626', 12, 0),
(654, 'MBANGO MBOCKE ANGE CHRISTINE', 'ANGE CHRISTINE', 'MBANGO MBOCKE', '2010-07-10', 'DOUALA', 'F', 'MBOCKE DANIEL', '676397437', NULL, 'NDONGO JULIENNE', '697951092', NULL, NULL, NULL, 110, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:34:08', '2025-09-10 05:34:08', 1, '25A00627', 5, 0),
(655, 'OTTOU ATHANASE LEYIK', 'ATHANASE LEYIK', 'OTTOU', '2002-11-15', 'YAOUNDE', 'M', 'ETOUNDI', NULL, NULL, 'ZOUGA CECILE', '695903745', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:37:53', '2025-09-10 05:37:53', 1, '25A00628', 9, 0),
(656, 'KAKEU LAURENCE NOEL', 'LAURENCE NOEL', 'KAKEU', '2011-04-10', 'KUMBA', 'M', 'KETCHESSO GUY BERLIN', NULL, NULL, 'MATCHI SANDRINE FLORA', '671528216', NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:41:19', '2025-09-10 05:41:19', 1, '25A00629', 6, 0),
(657, 'EYENGA BEBI ESTELLE COLETTE', 'ESTELLE COLETTE', 'EYENGA BEBI', '2007-09-02', 'BONGANDO', 'F', 'MEYOU BEBI', '670101763', NULL, 'AISSATOU ZOUBAINATOU', '640923514', NULL, NULL, NULL, 39, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 05:41:59', '2025-09-10 05:41:59', 1, '25A00630', 3, 0),
(658, 'MATSING MBE LYNE VANNEL', 'LYNE VANNEL', 'MATSING MBE', '2010-11-02', 'DOUALA', 'F', 'MBE JOSEPH ELISEE', NULL, NULL, 'NITEDEME BERTINE', '681526084', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 06:54:41', '2025-09-10 06:54:41', 1, '25A00631', 43, 0),
(659, 'ALIMA MAX', 'MAX', 'ALIMA', '2006-06-20', 'YAOUNDE', 'M', 'AMOUGOU', '655521906', NULL, 'NGAH AMANDINE', '.', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 06:57:02', '2025-09-10 06:57:02', 1, '25A00632', 13, 0),
(660, 'ATANGANA NGOUMOU CAROLE MATHIEU', 'CAROLE MATHIEU', 'ATANGANA NGOUMOU', '2009-11-02', 'DOUALA', 'F', 'NGOUMOU TITUS', NULL, NULL, 'BILOA SANDRINE', '697688775', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 06:58:16', '2025-09-10 06:58:16', 1, '25A00633', 14, 0),
(661, 'TCHATCHOUANG TOUKAM CHELSEA MIRIANE', 'CHELSEA MIRIANE', 'TCHATCHOUANG TOUKAM', '2008-07-06', 'YAOUNDE', 'F', 'PAWACK ALAIN', NULL, NULL, 'TCHUISSEU ELODIE', '674877545', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:01:40', '2025-09-10 07:01:40', 1, '25A00634', 10, 0),
(662, 'KUEBO KUEBO SHEILLA BELVANIE', 'SHEILLA BELVANIE', 'KUEBO KUEBO', '2011-11-29', 'DOUALA', 'F', 'NYAMEKO JOSEPH', '692839324', NULL, 'NGASSA NYA GENEVIENE', '693949544', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:03:17', '2025-09-10 07:03:17', 1, '25A00635', 7, 0),
(663, 'DJIDJOU BERNADETTE ALICIA MERVEILLE', 'BERNADETTE ALICIA MERVEILLE', 'DJIDJOU', '2010-03-08', 'DOUALA', 'F', 'TETO SAMUEL', NULL, NULL, 'CHIMENE LIDY', '670589626', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:04:51', '2025-09-10 07:04:51', 1, '25A00636', 44, 0),
(664, 'MOLEL EPETI JEANNETTE PURICHE ANDREA', 'JEANNETTE PURICHE ANDREA', 'MOLEL EPETI', '2008-07-30', 'DOUALA', 'F', 'MOLEL DIEUDONE', NULL, NULL, 'MBOME MARTINE', '695848509', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:08:40', '2025-09-10 07:08:40', 1, '25A00637', 9, 0),
(665, 'LASEWA NADEGE', 'NADEGE', 'LASEWA', '2012-02-29', 'DOUALA', 'F', 'GOUKOUNI DAKSALA JOSEPH', '696279522', NULL, 'MAIMOUDNI ODETTE', '.', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:08:50', '2025-09-10 07:08:50', 1, '25A00638', 10, 0),
(666, 'RADAIWA JEANNETTE', 'JEANNETTE', 'RADAIWA', '2007-02-16', 'DOUALA', 'F', 'GOUKOUNI DAKSALA JOSEPH', '673716770', NULL, 'MAIMOUDNI CODETTE', '696279522', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:13:33', '2025-09-10 07:13:33', 1, '25A00639', 27, 0),
(667, 'MOUKAM YOUBI WILFRYDE DALIA', 'WILFRYDE DALIA', 'MOUKAM YOUBI', '2006-08-11', 'BANKA', 'F', 'MOUAHA CORNEILLE', '651749804', NULL, 'DANKO ANNE', '696125187', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:16:45', '2025-09-10 07:16:45', 1, '25A00640', 36, 0),
(668, 'SEN MICHELLE ALIZA', 'MICHELLE ALIZA', 'SEN', '2010-03-19', 'DOUALA', 'F', 'TCHOKONTCHE HERMANN', NULL, NULL, 'NOUBOUSSI CECILE', '653184312', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:19:55', '2025-09-10 07:34:27', 1, '25A00641', 5, 0),
(669, 'AMBASSA DESIRE SYLVERE', 'DESIRE SYLVERE', 'AMBASSA', '2011-06-06', 'DOUALA', 'M', '.', '.', NULL, 'MBENTI THECLE', '699452303', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:22:33', '2025-09-10 07:22:33', 1, '25A00642', 30, 0);
INSERT INTO `students` (`id`, `name`, `first_name`, `last_name`, `date_of_birth`, `place_of_birth`, `gender`, `parent_name`, `parent_phone`, `parent_email`, `mother_name`, `mother_phone`, `address`, `photo`, `subname`, `class_series_id`, `email`, `student_status`, `phone_number`, `birthday`, `birthday_place`, `sex`, `father_name`, `profession`, `status`, `is_new`, `is_active`, `created_at`, `updated_at`, `school_year_id`, `student_number`, `order`, `has_scholarship_enabled`) VALUES
(670, 'HAGBANG HAGBANG MATHIS', 'MATHIS', 'HAGBANG HAGBANG', '2006-08-08', 'DOUALA', 'M', 'HAGBANG HAGBANG MATHIAS', '656246426', NULL, 'MBEY EMMILIENNE', '640446633', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:24:17', '2025-09-10 07:24:17', 1, '25A00643', 5, 0),
(671, 'ZOBO AMBASSA AUDREY SIRIELLE', 'AUDREY SIRIELLE', 'ZOBO AMBASSA', '2008-05-26', 'DOUALA', 'F', 'AMBASSA AMBASSA LUC', '699452303', NULL, 'ZOBO LEKA SALOME', '.', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:27:58', '2025-09-10 07:27:58', 1, '25A00644', 15, 0),
(672, 'ABDOURAMAN SOUDAÏSSI MOHAMADOU', 'MOHAMADOU', 'ABDOURAMAN SOUDAÏSSI', '2014-12-25', 'YAGOUA', 'M', 'MOHAMADOU SALI', '699672556', NULL, 'DIDJATOU', '674758963', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:29:35', '2025-09-10 07:29:35', 1, '25A00645', 26, 0),
(673, 'DJUIKOM CARELLE', 'CARELLE', 'DJUIKOM', '2013-03-13', 'BANDJOUM', 'F', 'TCHATCHOUA WANSI', '672476495', NULL, 'TOUKAM KAMGA GISLAINE', '.', NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:33:10', '2025-09-10 07:33:10', 1, '25A00646', 6, 0),
(674, 'EBIABOUA DOUDOU LUCRECE', 'DOUDOU LUCRECE', 'EBIABOUA', '2011-01-05', 'BERTOUA', 'F', 'MAMOUDOU DONGUIM OLIVIER', '698616350', NULL, 'MEKANA LOUISE CHARLYSE', '671542612', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:37:31', '2025-09-10 07:37:31', 1, '25A00647', 37, 0),
(675, 'ZEKEN ROBERT', 'ROBERT', 'ZEKEN', '2013-05-05', 'DOUALA', 'M', 'ZEKEN CHARLES', '657071379', NULL, 'NGUEGUANG COLETTE', '.', NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:40:35', '2025-09-10 07:40:35', 1, '25A00648', 7, 0),
(676, 'BELOH LINDA SELSILE', 'LINDA SELSILE', 'BELOH', '2008-08-23', 'BAMENDJOU', 'F', 'TANKEU ACHILLE', NULL, NULL, 'KOUNDJOU HERMINE', '652763678', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:42:04', '2025-09-10 07:42:04', 1, '25A00649', 31, 0),
(677, 'NGENDE JEANNE EMERAUDE ARISKEGNE', 'JEANNE EMERAUDE ARISKEGNE', 'NGENDE', '2012-11-26', 'DOUALA', 'F', 'BIYICK BI NJOUMA VICTOIRE', '697033099', NULL, 'NJOM MARIE SANDRA', '699187478', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:48:54', '2025-09-10 07:48:54', 1, '25A00650', 31, 0),
(678, 'KALLO MEKANA LOUIS ALANE', 'LOUIS ALANE', 'KALLO MEKANA', '2011-12-07', 'YOKADOUMA', 'M', 'KALLO MEKANA LOUIS LAVENIR', '671542612', NULL, 'LEMO SANDRINE', '698616350', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:54:06', '2025-09-10 07:54:06', 1, '25A00651', 45, 0),
(679, 'MEDEN MEBON LESLINE CARELLE', 'LESLINE CARELLE', 'MEDEN MEBON', '2005-07-06', 'FOTOUNI', 'F', 'MEBON PIERRE', '675830678', NULL, 'MEFFO ROBERTINE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:54:36', '2025-09-10 07:54:36', 1, '25A00652', 28, 0),
(680, 'AMBIE NDJOHO STAELLE CIELLA', 'STAELLE CIELLA', 'AMBIE NDJOHO', '2009-07-29', 'MELONG', 'F', 'NDJOHO ACHA JONAS', '671648837', NULL, 'NJONGUE NTUH PHILOMENE', '686130020', NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 07:59:33', '2025-09-10 07:59:33', 1, '25A00653', 8, 0),
(681, 'WETIE TCHUISSEU GUIDEL SYLVESTRE', 'GUIDEL SYLVESTRE', 'WETIE TCHUISSEU', '2011-03-09', 'DOUALA', 'M', 'TCHUISSEU NARIS', '670190904', NULL, 'FANDJEU LEONIE', '.', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:01:14', '2025-09-10 08:01:14', 1, '25A00654', 16, 0),
(682, 'ITETI NDOGA ODETTE MARISA  CHANCELLA', 'ODETTE MARISA  CHANCELLA', 'ITETI NDOGA', '2008-02-18', 'DOUALA', 'F', 'MOUE NDOGA THOMAS', '656868760', NULL, 'DIBONZO SIRIKI MARIE CLAIRE', '691498517', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:04:02', '2025-09-10 08:04:02', 1, '25A00655', 38, 0),
(683, 'YANG MBOCK PASCALE KIMORA JOYCE', 'PASCALE KIMORA JOYCE', 'YANG MBOCK', '2014-01-02', 'DOUALA', 'F', 'MBOCK MBOCK DANI JUNIOR', '695278170', NULL, 'MOUKALA DEKIBI FRIDA', '658654192', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:05:52', '2025-09-10 08:05:52', 1, '25A00656', 8, 0),
(684, 'NYEP BAHEM FRIDA AIMIONE', 'FRIDA AIMIONE', 'NYEP BAHEM', '2006-05-02', 'DOUALA', 'F', 'MOJIE YIE CYRIL', '695278170', NULL, 'MOUKALA EKIBI FRIDA', '658654192', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:09:43', '2025-09-10 08:09:43', 1, '25A00657', 29, 0),
(685, 'ONANA DYLANE', 'DYLANE', 'ONANA', '2008-12-19', 'DOUALA', 'M', 'DIONI ONANA ALAIN', NULL, NULL, 'IRAÏ MIRIELLE', '640920376', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:12:37', '2025-09-10 08:12:37', 1, '25A00658', 9, 0),
(686, 'AKA YONKEU TOH EMMANUEL', 'EMMANUEL', 'AKA YONKEU TOH', '2008-08-02', 'DOUALA NDOG-PASSI II', 'M', 'AKA MIKA TOH', '674276187', NULL, 'NGUEA CECILE CAROLE', '691444051', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:15:20', '2025-09-10 08:15:20', 1, '25A00659', 17, 0),
(687, 'GORMO JONATHAN', 'JONATHAN', 'GORMO', '2005-10-06', 'DOUALA', 'M', 'GOUKOUNI DAKSALA JOSEPH', '673716770', NULL, 'MAIMOUDNI ODETTE', '696279522', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:16:12', '2025-09-10 08:16:12', 1, '25A00660', 16, 0),
(688, 'PÂTOUMA CHAFAOU NAGOPEN', 'NAGOPEN', 'PÂTOUMA CHAFAOU', '2015-05-14', 'GAMBA', 'F', 'GUIMPEUNE DJIDDERE', '674867255', NULL, 'DJABOU ASSABE', '675499450', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:39:38', '2025-09-10 08:41:12', 1, '25A00661', 9, 0),
(689, 'HIDAYA KALTA', 'KALTA', 'HIDAYA', '2009-09-04', 'GAMBA', 'F', 'KALTA', NULL, NULL, 'AÏSSATOU', '674867255', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:42:41', '2025-09-10 08:42:41', 1, '25A00662', 10, 0),
(690, 'NGOUMSSEU KEPSEU DOMINIE CECILIA', 'DOMINIE CECILIA', 'NGOUMSSEU KEPSEU', '2008-03-21', 'DOUALA', 'F', 'KEPSEU GUY BERTIN', '672743164', NULL, 'FANDO DJOCHA JUSTINE', '678292530', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:44:33', '2025-09-10 08:44:33', 1, '25A00663', 46, 0),
(691, 'MBEM KUNDI FELICITE STEPHANIE', 'FELICITE STEPHANIE', 'MBEM KUNDI', '2011-11-13', 'DOUALA', 'F', 'KUNDI ESALE', '657171337', NULL, 'NGO MPEGNYEB YVETTE', '699210611', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:53:17', '2025-09-10 08:53:17', 1, '25A00664', 10, 0),
(692, 'HAPPI NDJOMENI PATRICIA', 'PATRICIA', 'HAPPI NDJOMENI', '2013-02-08', 'CSI DE BATCHA', 'F', '.', NULL, NULL, 'KENMENI MEHELO CHANCELINE', '651182183', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:55:32', '2025-09-10 08:55:32', 1, '25A00665', 27, 0),
(693, 'HAPPI  KOUAMENI NESLINE', 'NESLINE', 'HAPPI  KOUAMENI', '2013-02-08', 'CSI DE BATCHA', 'F', '.', NULL, NULL, 'KENMEGNI MEHELO CHANCELINE', '651182183', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:57:48', '2025-09-10 08:57:48', 1, '25A00666', 28, 0),
(694, 'MBOZO\'O BEBI SYCFRIED LE DEBONNAIRE', 'SYCFRIED LE DEBONNAIRE', 'MBOZO\'O BEBI', '2012-04-15', 'DOUALA', 'M', 'MEYOU BEBI', '670101763', NULL, 'ABOU\'OU GINETTE', '640923514', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:58:20', '2025-09-10 08:58:20', 1, '25A00667', 11, 0),
(695, 'HAPPI TCHUENKOU MERVEILLE', 'MERVEILLE', 'HAPPI TCHUENKOU', '2011-03-03', 'CSI DE BATCHA', 'F', '.', NULL, NULL, 'KENMEGNI MEHELO CHANCELINE', '651182183', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 08:59:54', '2025-09-10 08:59:54', 1, '25A00668', 6, 0),
(696, 'NYEMECK LOGMO SYLVIE AUDREY', 'SYLVIE AUDREY', 'NYEMECK LOGMO', '2010-10-05', 'DOUALA', 'F', 'MEYIFI MPONO STEPHANE', '679526002', NULL, 'NYEMECK SYLVIE GAËL Epse MEYIFI', '699874165', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:06:17', '2025-09-10 09:06:17', 1, '25A00669', 3, 0),
(698, 'KWETCHA NINTCHEU DIVINE CARELLE', 'DIVINE CARELLE', 'KWETCHA NINTCHEU', '2007-01-07', 'BANKA', 'F', 'NINTCHEU LAZARD', NULL, NULL, 'FAGUE VICTORINE', '650766417', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:11:25', '2025-09-10 09:11:25', 1, '25A00670', 47, 0),
(699, 'NKITSOM ATIATI SHARON', 'SHARON', 'NKITSOM ATIATI', '2010-07-27', 'DOUALA', 'F', 'ELOI ATIATI DURANG', '695263724', NULL, 'ELOUMA MARIE BERNADETTE', '.', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:15:26', '2025-09-10 09:15:26', 1, '25A00671', 6, 0),
(700, 'EBAH MEKOURGOU JESSICA JOVANA', 'JESSICA JOVANA', 'EBAH MEKOURGOU', '2007-09-04', 'DOUALA', 'F', 'MEKOURGOU M\'EBAH MARTIN', NULL, NULL, 'MPESSE A ETELE EUGENE', '677207614', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:16:23', '2025-09-10 09:16:23', 1, '25A00672', 48, 0),
(701, 'FOKOU ALLAN PATRICK', 'ALLAN PATRICK', 'FOKOU', '2008-03-12', 'DOUALA', 'M', '.', '.', NULL, 'TSANG SOP FLAURE', '691414443', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:18:44', '2025-09-10 09:18:44', 1, '25A00673', 10, 0),
(702, 'NJASSEU DJEUKUI MAEVA', 'MAEVA', 'NJASSEU DJEUKUI', '2008-10-17', 'DOUALA', 'F', 'TAKAM MARTIAL', '682344031', NULL, 'NGUETA JOSIANE', '678951547', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:22:28', '2025-09-10 09:22:28', 1, '25A00674', 39, 0),
(703, 'WACHO NKFUBO NADEGE', 'NADEGE', 'WACHO NKFUBO', '2009-05-04', 'YAOUNDE', 'M', 'TANKO KENNETH WACHO', '652140147', NULL, 'WOUAGOU ANGE BERTILLE', '.', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:27:44', '2025-09-10 09:27:44', 1, '25A00675', 32, 0),
(704, 'WAMEGNI DJEUGOUE ANGE DIVINE', 'ANGE DIVINE', 'WAMEGNI DJEUGOUE', '2015-01-19', 'DOUALA', 'F', 'DJEUGOUE MAURICE', '696973219', NULL, 'NGATCHUI JOSIANE', '651910154', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:29:46', '2025-09-10 09:29:46', 1, '25A00676', 11, 0),
(705, 'BELLA HOL JACQUELINE SARAH', 'JACQUELINE SARAH', 'BELLA HOL', '2008-04-19', 'DOUALA', 'F', 'HOL SERGE APPOLINAIRE', '.', NULL, 'BALOMO MARIE THERESE', '696607441', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:33:27', '2025-09-10 09:33:27', 1, '25A00677', 40, 0),
(706, 'ADAMA OUMAROU', 'OUMAROU', 'ADAMA', '2007-03-21', 'DOUALA', 'F', 'MOUHAMMAN SANI', '696180077', NULL, 'ZENABOU ISSA', '.', NULL, NULL, NULL, 34, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:41:17', '2025-09-10 09:41:17', 1, '25A00678', 3, 0),
(707, 'TCHUDJO TCHASSI ROLEX VENISE', 'ROLEX VENISE', 'TCHUDJO TCHASSI', '2014-01-17', 'DOUALA', 'M', 'SIANI TCHASSI JOSEPH DURANT', '675259279', NULL, 'MAZZIE TCHUDJO HERMINE', '694015287', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:42:53', '2025-09-10 09:42:53', 1, '25A00679', 32, 0),
(708, 'YEUPO GUEMNIN MARIA JORDANNA', 'MARIA JORDANNA', 'YEUPO GUEMNIN', '2009-03-16', 'DOUALA', 'F', 'GUEMNIN CLAUDE HERVE', '694640276', NULL, 'KWAPANEGNIGNI LADIFATOU', '.', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:46:06', '2025-09-10 09:46:06', 1, '25A00680', 33, 0),
(709, 'NGO MINLEND AGNES MERVEILLE', 'AGNES MERVEILLE', 'NGO MINLEND', '2009-05-09', 'DOUALA', 'F', 'MINLEND ETIENNE', '699677123', NULL, 'MINLEND ESTHER SOPPI', '699688027', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:51:36', '2025-09-10 09:51:36', 1, '25A00681', 10, 0),
(710, 'IBRAHIM HASSAN', 'HASSAN', 'IBRAHIM', '2007-10-12', 'DOUALA', 'M', 'MOUHAMMADOU HASSAN', '658068962', NULL, 'HABIBA', '.', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:53:41', '2025-09-10 09:53:41', 1, '25A00682', 41, 0),
(711, 'NGO MINLEND JULY GRACE', 'JULY GRACE', 'NGO MINLEND', '2009-05-09', 'DOUALA', 'F', 'MINLEND ETIENNE', '699677123', NULL, 'MINLEND ESTHER', '.', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 09:59:20', '2025-09-10 09:59:20', 1, '25A00683', 11, 0),
(712, 'POUANI ANGE KESINA', 'ANGE KESINA', 'POUANI', '2010-06-27', 'DOUALA', 'F', '.', '677813991', NULL, 'MATCHOUATCHAM NELLY', '657031643', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:05:25', '2025-09-10 10:05:25', 1, '25A00684', 11, 0),
(713, 'TESSA SHARON PAULINE', 'PAULINE', 'TESSA SHARON', '2011-03-11', 'DOUALA', 'F', 'TESSA TSAGUI PAULIN', '682872734', NULL, 'WANSI SIDONIE MAJOLIE', '.', NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:05:39', '2025-09-10 10:05:39', 1, '25A00685', 7, 0),
(714, 'PIEGUE TAMNO ABIGAELLE DARINA', 'ABIGAELLE DARINA', 'PIEGUE TAMNO', '2007-07-15', 'LA MATERNITE DE SENDO', 'F', 'TAMNO ENGILBERT', '675437915', NULL, 'MAKUATE FOTSO DAURICE', '653942107', NULL, NULL, NULL, 104, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:08:45', '2025-09-10 13:05:43', 1, '25A00686', 4, 0),
(715, 'TINDO ZOUKEM YANN MAEL', 'YANN MAEL', 'TINDO ZOUKEM', '2012-07-26', 'DOUALA', 'M', 'TOUKEM TINDO PEGUY MARCEL', '674336772', NULL, 'DOUMTSOP NZOFOU ANI CLAIRE', '655724958', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:19:12', '2025-09-10 10:19:12', 1, '25A00687', 16, 0),
(716, 'MEFIRE A SEKE FAOZIA', 'FAOZIA', 'MEFIRE A SEKE', '2012-08-02', 'YAOUNDE', 'F', 'BIGWONG SOULEYMAN', '693006974', NULL, 'NTEMTIE BLIKISSOU', '.', NULL, NULL, NULL, 47, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:22:44', '2025-09-10 10:22:44', 1, '25A00688', 4, 0),
(717, 'KABEYENE NDZIH SUZANNE MUMOSETTE', 'SUZANNE MUMOSETTE', 'KABEYENE NDZIH', '2007-01-18', 'MINTA', 'F', 'NDZIH PRAKRICK', '656597671', NULL, 'MVAME MANUELLE', '693546563', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:25:51', '2025-09-10 10:25:51', 1, '25A00689', 30, 0),
(718, 'MELACHI WINDA VIAHADA', 'WINDA VIAHADA', 'MELACHI', '2008-08-02', '.', 'F', 'MELACHI AIME DE DIEU', '681570413', NULL, 'DONGMO EVELINE', '687414370', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:30:14', '2025-09-10 10:30:14', 1, '25A00690', 31, 0),
(719, 'TCHANA NYOSSI DJIBRIL FAYEL', 'DJIBRIL FAYEL', 'TCHANA NYOSSI', '2008-10-02', 'DOUALA', 'M', 'NYOSSI ELVIS BIENVENUE', '677545190', NULL, 'NJIONJIPKOMGA CARINE NATHALIE', '670903291', NULL, NULL, NULL, 110, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:39:05', '2025-09-10 10:39:05', 1, '25A00691', 6, 0),
(720, 'TCHANTCHOU TANKEU ANGE DIANE', 'ANGE DIANE', 'TCHANTCHOU TANKEU', '2007-12-08', 'NKONGSAMBA', 'F', 'TCHANTCHOU DJOFANG', '690969442', NULL, 'TANKEU OLIVE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:42:37', '2025-09-10 10:42:37', 1, '25A00692', 32, 0),
(721, 'NIGOUMI MEYIFI EPHRAIM NATHAN', 'EPHRAIM NATHAN', 'NIGOUMI MEYIFI', '2007-07-03', 'DOUALA', 'M', 'MEYIFI MPONO STEPHANE', '699374165', NULL, 'MEYIFI SILVIR GAEL', '679526002', NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:46:36', '2025-09-10 10:46:36', 1, '25A00693', 9, 0),
(722, 'ENOAH ASSOLA THERESE DIVINE', 'THERESE DIVINE', 'ENOAH ASSOLA', '2012-11-12', 'YAOUNDE', 'F', 'ASSOLA RENE GUY', '698066125', NULL, 'KAMAHA EMILIENNE', '675686179', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:47:41', '2025-09-10 10:47:41', 1, '25A00694', 17, 0),
(723, 'TANKWA DJOUPOUOP CHRIST BRAYAN', 'CHRIST BRAYAN', 'TANKWA DJOUPOUOP', '2014-05-05', 'DOUALA', 'M', 'DJOUPOUOP MITERAN', '670190904', NULL, 'KEMAJOU JUNIE CAZIDELLE', NULL, NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:48:58', '2025-09-10 10:48:58', 1, '25A00695', 11, 0),
(724, 'TEGHEN MANUELLA AKWEN', 'MANUELLA AKWEN', 'TEGHEN', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:51:07', '2025-09-10 10:51:07', 1, '25A00696', 11, 0),
(725, 'NGOUEGNE PRINCESSE MAEVA', 'MAEVA', 'NGOUEGNE PRINCESSE', '2009-04-09', 'BABADJOU', 'F', 'SABISI ISIDOR', '675780822', NULL, 'MBANKENG DANIE', '.', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:52:43', '2025-09-10 10:52:43', 1, '25A00697', 42, 0),
(726, 'MPON A YOMBO PRINCESSE', 'PRINCESSE', 'MPON A YOMBO', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:52:58', '2025-09-10 10:52:58', 1, '25A00698', 11, 0),
(727, 'NGO BASSICK CELINE', 'CELINE', 'NGO BASSICK', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:55:10', '2025-09-10 10:55:10', 1, '25A00699', 17, 0),
(728, 'MBOUGANG LAURICA MAELLE', 'LAURICA MAELLE', 'MBOUGANG', '2007-08-29', 'BATIE', 'F', 'YOUMBI ALAIN', '673542616', NULL, 'MEUGANG MIRA', '.', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 10:56:54', '2025-09-10 10:56:54', 1, '25A00700', 43, 0),
(729, 'NOUMEN ZUNYA ESTHER ROSE', 'ESTHER ROSE', 'NOUMEN ZUNYA', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:00:11', '2025-09-10 11:00:11', 1, '25A00701', 17, 0),
(730, 'BESSOUE KOUNOU ALIDA', 'ALIDA', 'BESSOUE KOUNOU', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:02:18', '2025-09-10 11:02:18', 1, '25A00702', 49, 0),
(731, 'KENGNE KUATE ALVINE THERESA', 'ALVINE THERESA', 'KENGNE KUATE', '2011-10-06', 'DOUALA', 'F', 'KUATE ALAIN CLAUDE', '657552145', NULL, 'TCHEUTCHOUA GORGETTE', '683028371', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:03:43', '2025-09-10 11:03:43', 1, '25A00703', 34, 0),
(732, 'SIAKA MICHELLE BELVA', 'MICHELLE BELVA', 'SIAKA', '2007-06-11', 'DOUALA', 'F', 'SIAKA AUGUSTA', '693941670', NULL, 'BALEMA MARIE', '696650849', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:06:11', '2025-09-10 11:06:11', 1, '25A00704', 44, 0),
(733, 'BIDJA MVELE FREDY', 'FREDY', 'BIDJA MVELE', '2010-06-25', 'NGUELEMENDOUKO', 'M', 'BIDJA JEAN CALVIN', NULL, NULL, 'MEYENE ARMELLE', '657973379', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:08:24', '2025-09-10 11:08:24', 1, '25A00705', 18, 0),
(734, 'DJUIKWA KENMEGNI MIRIAM FLORE', 'MIRIAM FLORE', 'DJUIKWA KENMEGNI', '2010-02-24', 'FOUMBOT', 'F', 'KDATE ALAIN', '683733001', NULL, 'TCHEUTCHOU GEORGETTE', '.', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:08:43', '2025-09-10 11:08:43', 1, '25A00706', 35, 0),
(735, 'MENGUE EBAM HENRI KENZO', 'HENRI KENZO', 'MENGUE EBAM', '2012-06-20', 'ABONO', 'M', 'EBAM THADEE CYRILLE', '674826101', NULL, 'AVINA MBALA GERTRUDE ALINE', '675854947', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:11:19', '2025-09-10 11:11:19', 1, '25A00707', 33, 0),
(736, 'MENDOMO BIZEME AUGUSTINE MOISA', 'AUGUSTINE MOISA', 'MENDOMO BIZEME', '2011-09-11', 'DOUALA', 'F', 'BIZEME ABONDO PIERRE HILAIRE', '696586382', NULL, 'NTOUZO\'O MARIE BEATRICE', '658466762', NULL, NULL, NULL, 47, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:14:22', '2025-09-12 06:51:11', 1, '25A00708', 5, 0),
(737, 'DASSIE DASSIE ANGE DUCHESSE', 'ANGE DUCHESSE', 'DASSIE DASSIE', '2014-08-05', 'BABOATE', 'F', 'DASSIE', NULL, NULL, 'MAZAN', '653376212', NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:16:06', '2025-09-10 11:16:06', 1, '25A00709', 8, 0),
(738, 'NGONTOUCK NGOUMOU NGONO BALBINE', 'NGONO BALBINE', 'NGONTOUCK NGOUMOU', '2009-08-30', 'YAOUNDE', 'F', 'MOISE TCHNA', '658398846', NULL, 'NGONDJOCK DENISE', '.', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:21:18', '2025-09-10 11:21:18', 1, '25A00710', 6, 0),
(740, 'NGNHIOU MABOU MIGAIN BRONDON', 'MIGAIN BRONDON', 'NGNHIOU MABOU', '2006-04-10', 'DOUALA', 'M', 'MABOU BELROND', '698844239', NULL, 'METTAFO GRACE MAJOLIE', '.', NULL, NULL, NULL, 39, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:26:04', '2025-09-10 11:26:04', 1, '25A00712', 4, 0),
(741, 'OUMAROU SALIHOU', 'SALIHOU', 'OUMAROU', '2007-04-05', 'BERTOUA', 'M', 'SALIHOU OUMAROU', '696258416', NULL, 'HIDAYATOU ALIM', '695387111', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:27:45', '2025-09-10 11:27:45', 1, '25A00713', 12, 0),
(742, 'MODIE TAGHEU GRACE MERVEILLE', 'GRACE MERVEILLE', 'MODIE TAGHEU', '2006-06-23', 'DOUALA', 'F', 'TAGHEU MICHEL', '677501874', NULL, 'KENMOE JEANNETTE', '656656502', NULL, NULL, NULL, 107, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:33:13', '2025-09-10 11:33:13', 1, '25A00714', 6, 0),
(743, 'NGO SAMMICK JEANNE ETOILE', 'JEANNE ETOILE', 'NGO SAMMICK', '2002-08-03', 'DOUALA', 'F', '.', '.', NULL, '.', '691813234', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:38:57', '2025-09-10 11:38:57', 1, '25A00715', 45, 0),
(745, 'NZIE YAP FATIMA', 'YAP FATIMA', 'NZIE', '2010-10-25', 'DOUALA', 'F', 'TCHOWAT YAP SALIFOU', NULL, NULL, 'NOUMO VALERINE CHANTAL', '675054471', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:48:33', '2025-09-10 11:48:33', 1, '25A00716', 23, 0),
(746, 'DJUIKOUA SOUOP DIVINE KETHURA', 'DIVINE KETHURA', 'DJUIKOUA SOUOP', '2009-03-16', 'DOUALA', 'F', 'SOUOP FOTSO ANTOINE', '670597083', NULL, 'NGUEUTSA GERMAINE', '677690236', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:50:05', '2025-09-10 11:50:05', 1, '25A00717', 7, 0),
(747, 'BELIMBI SAMBA LUCIEN EVRANCE', 'LUCIEN EVRANCE', 'BELIMBI SAMBA', '2013-05-28', 'MFOU', 'M', 'SAMBA YDELBERT', NULL, NULL, 'OMGBA OMGBA JEANNE FRANCINE', '677390394', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:52:26', '2025-09-10 11:53:14', 1, '25A00718', 13, 1),
(748, 'IDRISSOU AMADOU', 'AMADOU', 'IDRISSOU', '2009-02-27', 'DOUALA', 'M', 'AMADOU BABA', '699203583', NULL, 'BILKISSOU DJOUMAI', '.', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:54:24', '2025-09-10 11:54:24', 1, '25A00719', 4, 0),
(749, 'ANGONI AYISSI CHANCELLINE', 'CHANCELLINE', 'ANGONI AYISSI', '2011-09-17', 'SOA', 'F', 'AYISSI ATANGANA ELISEE', '673101073', NULL, 'FOUDA VIRGINIE', '695784700', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 11:56:50', '2025-09-10 11:56:50', 1, '25A00720', 50, 0),
(750, 'KENKO NOUMBISSIE JEREMIE', 'JEREMIE', 'KENKO NOUMBISSIE', '2014-07-15', 'DOUALA', 'M', 'NOUMBISSIE TCHAMGOUE ANTOINE', '683654772', NULL, 'BEUTOU BIBICHE GLORINE', '694639415', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:02:49', '2025-09-10 12:02:49', 1, '25A00721', 29, 0),
(751, 'KEMEGNI TAFEN GERRY  ABIGAIL', 'GERRY  ABIGAIL', 'KEMEGNI TAFEN', '2006-11-08', 'LELEM', 'F', 'TAFEN NGASSAM', '655026068', NULL, 'MBUTOU', '675885587', NULL, NULL, NULL, 107, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:03:14', '2025-09-10 12:03:14', 1, '25A00722', 7, 0),
(752, 'AARONNE MOÏSETTE TEHNA LAFAVEUR', 'LAFAVEUR', 'AARONNE MOÏSETTE TEHNA', '2014-02-03', 'SAKBAYEME', 'F', 'MOÏSE TEHNA', NULL, NULL, 'NGONDJOCK DENISE', '658398846', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:06:31', '2025-09-10 12:06:31', 1, '25A00723', 14, 0),
(753, 'BABILADINA BAHECK FRED ISMAEL', 'FRED ISMAEL', 'BABILADINA BAHECK', '2012-01-12', 'DOUALA', 'M', 'ABISSAMA YVES ANICET', '696104636', NULL, 'NGO WONDJE CHRISTINE STEPHANIE', '693551206', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:09:41', '2025-09-10 12:09:41', 1, '25A00724', 18, 0),
(754, 'NATCHANG TIATOU DARLINE MADO', 'DARLINE MADO', 'NATCHANG TIATOU', '2012-04-28', 'DOUALA', 'F', 'TIATOU GEORGES', '699328352', NULL, 'TCHOGOUE MARGUERITTE', '696002702', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:15:27', '2025-09-10 12:15:27', 1, '25A00725', 7, 0),
(755, 'MBENGONO MENDJANA MARTHE CHIMENE', 'MARTHE CHIMENE', 'MBENGONO MENDJANA', '2008-04-03', 'YAOUNDE', 'F', 'MENDJANA FRANCOIS', '678094803', NULL, 'EPONI BOCK YANG AMANDINE', '640053095', NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:20:42', '2025-09-10 12:20:42', 1, '25A00726', 10, 0),
(756, 'MEKUATE KAMGA SIRIANE MABELLE', 'SIRIANE MABELLE', 'MEKUATE KAMGA', '2008-05-06', 'DOUALA', 'F', 'KAMGA FULBERT LANDRY', '654176983', NULL, 'KAPCHE NDOLLA FLORENCE', '693289197', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:22:37', '2025-09-10 12:22:37', 1, '25A00727', 51, 0),
(757, 'NOTUE KAMGA JOVANIE DELAURE', 'JOVANIE DELAURE', 'NOTUE KAMGA', '2010-05-27', 'DOUALA', 'F', 'KAMGA FULBERT LANDRY', '654176983', NULL, 'KAPCHE NDOLLA FLORENCE', '693289197', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:24:46', '2025-09-10 12:24:46', 1, '25A00728', 52, 0),
(758, 'NGONO MARIE', 'MARIE', 'NGONO', '2007-10-30', 'YAOUNDE', 'F', '.', '.', NULL, 'ESSOUNG LA JOIE', '675557259', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:29:43', '2025-09-10 12:29:43', 1, '25A00729', 12, 0),
(759, 'YENWO FAVOUR BRIGHT MANGUNG', 'FAVOUR BRIGHT MANGUNG', 'YENWO', '2008-12-08', 'BAFANJI', 'F', 'YENWO NGWEFUNI ANDREW', '655866434', NULL, 'TITIAHONG LYDIA', '675795071', NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:29:56', '2025-09-10 12:29:56', 1, '25A00730', 8, 0),
(760, 'BAHANAG GERMAIN ROLAND', 'GERMAIN ROLAND', 'BAHANAG', '2001-01-22', 'NYANON', 'M', 'BAHANAG EDOUARD', '696990966', NULL, 'NGO BABE', NULL, NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:31:52', '2025-09-10 12:31:52', 1, '25A00731', 13, 0),
(761, 'NGA LAMINE', 'LAMINE', 'NGA', '2009-01-02', 'YAOUNDE', 'F', '.', '.', NULL, '.ESSOUNG LA JOIE', '690856965', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:33:21', '2025-09-10 12:33:21', 1, '25A00732', 46, 0),
(762, 'UBENYI CHINECHEREM GLORY', 'GLORY', 'UBENYI CHINECHEREM', '2014-01-07', 'DOUALA', 'F', 'UBENYI UCHENNA SUNDAY', '689446706', NULL, 'NGAYENE FOE ADELE FLEUR', '699593346', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:35:02', '2025-09-10 12:35:02', 1, '25A00733', 15, 0),
(763, 'DINOU YEPMOU DAVINA', 'DAVINA', 'DINOU YEPMOU', '2011-08-23', 'MANYO', 'F', 'YEPMOU KOUAMOU GUILLAUME', '681877135', NULL, 'TCHUENKOU ROMELINE', '674484420', NULL, NULL, NULL, 47, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:37:29', '2025-09-10 12:37:29', 1, '25A00734', 6, 0),
(764, 'TCHAMGOUE NOUBISSIE ISRAEL PARFAIT', 'ISRAEL PARFAIT', 'TCHAMGOUE NOUBISSIE', '2012-10-15', 'FOTOUNI', 'M', 'NOUNBISSIE TCHAMGOUE ANTOINE', '683654772', NULL, 'BEUTOU BIBICH GLORINE', '694639415', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:39:12', '2025-09-10 12:39:12', 1, '25A00735', 12, 0),
(765, 'KOTTO EWONDE CLARITA', 'CLARITA', 'KOTTO EWONDE', '2010-11-24', 'BERTOUA', 'F', 'EWONDE OLIVIER', '698101249', NULL, 'AKAMBA DENISE', '.', NULL, NULL, NULL, 73, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:44:54', '2025-09-10 12:44:54', 1, '25A00736', 4, 0),
(766, 'AISSATOU SALI', 'SALI', 'AISSATOU', '2011-04-14', 'GAROUA', 'F', 'SALI MANA', '680709156', NULL, 'HABIBA ASSABE IYATOU', '.', NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:49:48', '2025-09-10 12:49:48', 1, '25A00737', 9, 0),
(767, 'MOUA DJOBATTA ROSALIE', 'ROSALIE', 'MOUA DJOBATTA', '2011-03-06', '.', 'F', 'BOUOBO BABENE ALVAREZ', '694301035', NULL, '.', '.', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 12:56:31', '2025-09-10 12:56:31', 1, '25A00738', 9, 0),
(768, 'NGOXOCK MARIE', 'MARIE', 'NGOXOCK', '2012-08-05', 'DOUALA', 'F', 'NYEBH', '656958509', NULL, 'NOMGA MARIE', '.', NULL, NULL, NULL, 69, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 13:01:34', '2025-09-10 13:01:34', 1, '25A00739', 3, 0),
(769, 'YOUSSOUFA CHERIF', 'CHERIF', 'YOUSSOUFA', '2010-09-05', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 34, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 13:06:26', '2025-09-10 13:06:26', 1, '25A00740', 4, 0),
(770, 'EKALLE ELOBO JOSS JOSEPHA', 'JOSEPHA', 'EKALLE ELOBO JOSS', '2011-08-04', 'DOUALA', 'F', 'DOOH ELOMBO JOSS DANIEL', '696360001', NULL, 'ELOMBO ZANG STEPHANIE', '.', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 13:15:32', '2025-09-10 13:15:32', 1, '25A00741', 7, 0),
(771, 'MADJIETCHEU DJIEMENI LINE CLARENCE', 'LINE CLARENCE', 'MADJIETCHEU DJIEMENI', '2008-01-31', 'DOUALA', 'F', 'DJIEMENI DAVID', '699217280', NULL, 'DJUISSI ROSINE', '.', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 13:19:41', '2025-09-10 13:19:41', 1, '25A00742', 13, 0),
(772, 'ASSANGNA OLI GEOVANNI', 'GEOVANNI', 'ASSANGNA OLI', '2008-05-01', 'DOUALA', 'M', 'OLI ALAIN', NULL, NULL, 'NGO TONYE COLETTE NICOLE', '657356061', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-10 13:53:07', '2025-09-10 13:53:07', 1, '25A00743', 36, 0),
(773, 'MEDJEU KAMGAIN ODILON JUNIOR', 'ODILON JUNIOR', 'MEDJEU KAMGAIN', '2011-05-05', 'DOUALA', 'M', 'KAMGAIN AMEE', '675156255', NULL, 'KAMGAIN MICHELLE', '.', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 08:39:54', '2025-09-11 08:39:54', 1, '25A00744', 13, 0),
(774, 'NJAYA EBONG AUDREY CHARLINE', 'AUDREY CHARLINE', 'NJAYA EBONG', '2014-01-01', 'DOUALA', 'F', 'EBONG JEAN CLAUDE', '674209528', NULL, 'NYAKE SOPPI ESTHER', '696293370', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 08:48:30', '2025-09-11 08:48:30', 1, '25A00745', 16, 0),
(775, 'NGAN WANDJIMI CHANCELINE', 'CHANCELINE', 'NGAN WANDJIMI', '2012-07-08', 'DOUALA', 'F', 'BORONANG ROGER', '675083248', NULL, 'NOUDJIBATEU HOLLANDE', '672026983', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 08:54:19', '2025-09-11 08:54:19', 1, '25A00746', 12, 0),
(776, 'ETO\'O AKOULOU ZEH JOSIANE', 'JOSIANE', 'ETO\'O AKOULOU ZEH', '2011-06-16', 'ALAM-EBOLOWA', 'F', 'BELL ADAMOU', '699944970', NULL, 'ETO\'O JOSIANE', '.', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 09:01:08', '2025-09-11 09:01:08', 1, '25A00747', 8, 0),
(777, 'MESSA TAMEKUE MELANIE PASCALE', 'MELANIE PASCALE', 'MESSA TAMEKUE', '2010-05-08', 'DOUALA', 'F', 'TAMEKUE DENIS', '651873785', NULL, 'MEFOGUE TCHATUE CAROLINE', '678057113', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 09:10:18', '2025-09-11 09:10:18', 1, '25A00748', 53, 0),
(778, 'TCHANSI DIHOUM SOLAMIDE ATLANTA', 'SOLAMIDE ATLANTA', 'TCHANSI DIHOUM', '2009-04-20', 'NKONDJOCK', 'F', 'DIHOUM DAVID', '672375941', NULL, 'DJOMGA EMABOU MARIE HORTENSE', '659146528', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 09:17:34', '2025-09-11 09:17:34', 1, '25A00749', 33, 0),
(779, 'BILKISSOU-ALIM BAORO', 'BAORO', 'BILKISSOU-ALIM', '2006-10-30', 'DJOHONG', 'F', 'MOHOMED ABBO FADIL', '674151192', NULL, 'SAFRAOU NOURA', '699977900', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 09:21:18', '2025-09-11 09:21:18', 1, '25A00750', 34, 0),
(780, 'BAMEN KAMGIAN SHARONE', 'SHARONE', 'BAMEN KAMGIAN', '2009-11-23', 'DOUALA', 'F', 'KAMGIAN SERGE AIME', '675156255', NULL, 'KAMGIAN MICHELLE', '.', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 09:29:01', '2025-09-11 09:29:01', 1, '25A00751', 5, 0),
(781, 'ABOUBAKAR SIDDIKI', 'SIDDIKI', 'ABOUBAKAR', '2005-02-14', 'NGAOUNDERE', 'M', '.', '.', NULL, 'FADIMATOU ABDOULAYE', '694017520', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 09:32:11', '2025-09-11 09:32:11', 1, '25A00752', 9, 0),
(782, 'KENGNE FOSSI IVANA RADIANNE', 'IVANA RADIANNE', 'KENGNE FOSSI', '2010-04-05', 'BAFOUSSAM', 'F', 'FOSSI MEFEYA FRANCIS', '670873165', NULL, 'MAFFO ALISE', '686767601', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 09:38:18', '2025-09-11 09:38:18', 1, '25A00753', 47, 0),
(783, 'SILATCHA FOSSI CARELLE DAINA', 'CARELLE DAINA', 'SILATCHA FOSSI', '2011-07-29', 'DOUALA', 'F', 'FOSSI MEFEYA FRANCIS', '686767601', NULL, 'MAFFO ALISE', '.', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 09:41:13', '2025-09-11 09:41:13', 1, '25A00754', 12, 0),
(784, 'TSINDA GISELLE LAELE', 'GISELLE LAELE', 'TSINDA', '2008-01-01', 'MBOUDA', 'F', '.', NULL, NULL, 'MATSE NANA VALENTINE CLAIRE', '652550777', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 10:26:23', '2025-09-11 10:26:23', 1, '25A00755', 13, 0),
(785, 'NGOH EKANE INES GLORIA', 'INES GLORIA', 'NGOH EKANE', '2009-09-09', 'DOUALA', 'F', 'EKANE  ETOUKA POLICARP CLOVIS', '694843927', NULL, 'ZINTIEZ MARIE', '694313594', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 10:30:45', '2025-09-11 10:30:45', 1, '25A00756', 12, 0),
(786, 'HABIBA ALMA LADI', 'LADI', 'HABIBA ALMA', '2002-10-20', 'MBOUDJI YADJI', 'F', 'ABDOULAYE DJAORU', '693980512', NULL, 'DINA JEANETTE HANON', '698982321', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 10:39:14', '2025-09-11 10:39:14', 1, '25A00757', 18, 0),
(787, 'LOMBE MEDAGUE AROLD MIGUEL', 'AROLD MIGUEL', 'LOMBE MEDAGUE', '2008-11-12', 'DOUALA', 'M', 'MEDAGUE LANDRY', NULL, NULL, 'MVONGO REBECCA FLORE', '691782626', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 10:39:16', '2025-09-11 10:39:16', 1, '25A00758', 24, 0),
(788, 'DJUIDJEN NDENGUE MARIA KIMORIANE', 'MARIA KIMORIANE', 'DJUIDJEN NDENGUE', '2009-11-22', 'DOUALA', 'F', 'JEAN BAPTISTE NDENGUE', '699969374', NULL, 'DJOWOU MAGNE MBO SIMONE', '695039346', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 10:55:45', '2025-09-11 10:55:45', 1, '25A00759', 10, 0),
(789, 'GOTCHO WEMBOU DAINA', 'DAINA', 'GOTCHO WEMBOU', '2005-01-16', 'BAFANG', 'F', 'WEMBOU PIERRE', '694875828', NULL, 'NGASSA SYLVIE', '.', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:00:55', '2025-09-11 11:00:55', 1, '25A00760', 19, 0),
(790, 'NGAMALEU PENTE ANGE DANIELLE', 'ANGE DANIELLE', 'NGAMALEU PENTE', '2011-11-30', 'MBANGA', 'F', 'PENTE', '695380558', NULL, 'DJEUMOU', '.', NULL, NULL, NULL, 47, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:06:23', '2025-09-11 11:06:23', 1, '25A00761', 7, 0),
(791, 'BOTOCK PAULINE ANNE RACHEL', 'PAULINE ANNE RACHEL', 'BOTOCK', '2008-01-15', 'DOUALA', 'F', 'BOTOCK', '693344581', NULL, 'NGO KOKOM', '694958174', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:11:47', '2025-09-11 11:11:47', 1, '25A00762', 35, 0),
(792, 'OUMAROU MOUHAMADOU SALI', 'MOUHAMADOU SALI', 'OUMAROU', '2013-04-14', 'DOUALA', 'M', 'MOHAMADOU SALI', '699672556', NULL, 'DIDJATOU', '674758965', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:19:21', '2025-09-11 11:19:21', 1, '25A00763', 19, 0),
(793, 'SAGUK FOTSO DIBRICE PHILEMON', 'DIBRICE PHILEMON', 'SAGUK FOTSO', '2004-01-01', 'DOUALA', 'M', 'SAGUK', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:25:03', '2025-09-11 11:25:03', 1, '25A00764', 17, 0),
(794, 'NGUENDJI DJEUTCHOM GLORIA', 'GLORIA', 'NGUENDJI DJEUTCHOM', '2012-01-12', 'DOUALA', 'F', '.', '656607051', NULL, '.', '.', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:27:46', '2025-09-11 11:27:46', 1, '25A00765', 8, 0),
(795, 'MBOMEYO NYABELA ADRIANE', 'ADRIANE', 'MBOMEYO NYABELA', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:30:31', '2025-09-11 11:30:31', 1, '25A00766', 30, 0),
(796, 'MALLA DJEUTCHOM SORAYA DAINA', 'SORAYA DAINA', 'MALLA DJEUTCHOM', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:33:57', '2025-09-11 11:33:57', 1, '25A00767', 12, 0),
(797, 'SEN SONGUE ANGE LUMIERE', 'ANGE LUMIERE', 'SEN SONGUE', '2010-11-29', 'LOGBADJECK', 'F', '.', '.', NULL, 'DJOGNE MIREILLE JOSEE', '696264737', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:44:18', '2025-09-11 11:44:18', 1, '25A00768', 54, 0),
(798, 'KREMENI TCHOKONA MIGOUEL JOEL', 'MIGOUEL JOEL', 'KREMENI TCHOKONA', '2008-09-22', 'NKONGSAMBA', 'M', 'TCHOKONA FRANCOIS', '675260744', NULL, 'TIDO BLANDINE', '672767232', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:49:48', '2025-09-11 11:49:48', 1, '25A00769', 25, 0),
(799, 'AÏSSATOU MADI', 'MADI', 'AÏSSATOU', '2004-01-01', 'DOUALA', 'F', 'KALFABE BONIFACE', '691975964', NULL, 'KALTOUMI', '670937684', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:51:00', '2025-09-11 11:51:00', 1, '25A00770', 13, 0),
(800, 'SOH DJOUONTZO PAGNOL AROME', 'PAGNOL AROME', 'SOH DJOUONTZO', '2010-05-12', 'BANSOA', 'F', 'DJOUONTZO MARCEL', '652542834', NULL, 'CHOUSSI MARIE ESTER', '691571162', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:52:42', '2025-09-11 11:53:26', 1, '25A00771', 8, 0),
(801, 'LOTI TCHOUPOU CHINELDA LESLIE', 'CHINELDA LESLIE', 'LOTI TCHOUPOU', '2013-07-30', 'DOUALA', 'F', 'TCHOUPOU JEAN-BAPTISTE', '675300318', NULL, 'COPI LOTI JOLIVANE', '.', NULL, NULL, NULL, 78, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 11:54:51', '2025-09-11 11:54:51', 1, '25A00772', 5, 0),
(802, 'ATIOMENE TAKWETE RANDINE STARFORT', 'RANDINE STARFORT', 'ATIOMENE TAKWETE', '2009-08-08', 'MANENGOLE', 'F', 'TAKWETE JEAN CLAUDE', '675300318', NULL, 'TCHINDA ADELINE', '.', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:01:48', '2025-09-11 12:01:48', 1, '25A00773', 17, 0),
(803, 'DJUISSIE TOUKAM PAOLA', 'PAOLA', 'DJUISSIE TOUKAM', '2008-03-22', 'BAFOUSSAM', 'F', 'TOUKAM RICHARD', '652121902', NULL, 'TIAKO', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:04:46', '2025-09-11 12:04:46', 1, '25A00774', 36, 0),
(805, 'ONOMO MICHEL ULRICH', 'MICHEL ULRICH', 'ONOMO', '2008-10-25', 'DOUALA', 'M', 'OWONA BIAKOLO RICHARD', '688393019', NULL, 'MENGUE BERNADETTE PASCALINE', '695186119', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:16:21', '2025-09-11 12:16:21', 1, '25A00775', 37, 0),
(806, 'NGUIDJOL MARTHE FLORINDA', 'MARTHE FLORINDA', 'NGUIDJOL', '2007-08-22', 'DOUALA', 'F', 'NGUIDJOL DANIEL', '693201627', NULL, 'ON TONYE RACHEL', '697727582', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:21:44', '2025-09-11 12:21:44', 1, '25A00776', 38, 0),
(807, 'NGADJEU TCHIADJEU ELISABETH FLORE', 'ELISABETH FLORE', 'NGADJEU TCHIADJEU', '2007-09-22', 'BAFANG', 'F', 'TCHIADJEU JEAN PIERRE', '671650946', NULL, 'TCHOKOCHA YOUSSA EMILIENNE', '655554975', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:26:40', '2025-09-11 12:26:40', 1, '25A00777', 11, 0),
(808, 'NONGA A MBASSA ALEX LUDOVIC', 'ALEX LUDOVIC', 'NONGA A MBASSA', '2006-07-04', 'ESSAZOK', 'M', 'MBASSA MOÏSE', NULL, NULL, 'NGO BILLIM MARIE CLAUDINE', '696363942', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:32:15', '2025-09-11 12:32:15', 1, '25A00778', 6, 0),
(809, 'MATCHAN MICHELLE CHRISTIANE', 'CHRISTIANE', 'MATCHAN MICHELLE', '2003-04-10', 'DOUALA', 'M', 'EKAMOU A BASSI', '698158736', NULL, 'NDIANG AUGUSTINE', '.', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:36:30', '2025-09-11 12:36:30', 1, '25A00779', 14, 0),
(810, 'BINYET NKEMBE ANGE LA GRACE', 'ANGE LA GRACE', 'BINYET NKEMBE', '2012-03-08', 'KRIBI', 'F', 'NKEMB', '699267024', NULL, 'NGO HANGBOCK', '670928647', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:44:29', '2025-09-11 12:44:29', 1, '25A00780', 26, 0),
(811, 'PAWA NKOUE ANGE ELODIE', 'ANGE ELODIE', 'PAWA NKOUE', '2008-09-29', 'DOUALA', 'F', 'NKOUE MANKOUE JUSTIN', '655082084', NULL, 'MADIESSE TASSING MARTERELLE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:48:10', '2025-09-11 12:48:10', 1, '25A00781', 39, 0),
(812, 'MIFENDA MEWALI DAVID ROSLAND', 'DAVID ROSLAND', 'MIFENDA MEWALI', '2009-12-02', 'YAOUNDE', 'M', 'GEORGE MBASSY', '698516788', NULL, 'ELE MARIE YOLANDE', '697520095', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 12:55:41', '2025-09-11 12:55:41', 1, '25A00782', 12, 0),
(813, 'MBAZOA ABA\'A ANGE MACELLINE', 'ANGE MACELLINE', 'MBAZOA ABA\'A', '2009-04-16', 'DOUALA', 'F', 'ATANGANA ABA\'A CHARLES STEEVE', '659964310', NULL, 'CHIMENE ABA\'A', '699346124', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 13:03:45', '2025-09-11 13:03:45', 1, '25A00783', 18, 0),
(814, 'MATAGNE TADIE ANGE MERVEILLE', 'ANGE MERVEILLE', 'MATAGNE TADIE', '2008-05-03', 'TONGA', 'F', 'SGOPET WILLIAM', '695157628', NULL, 'MADGE DANIELLE', '679152663', NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 13:10:27', '2025-09-11 13:10:27', 1, '25A00784', 14, 0),
(815, 'NGO BELL JOSEE MAGLOIRE', 'JOSEE MAGLOIRE', 'NGO BELL', '2012-05-29', 'DOUALA', 'F', 'DOBIL MENGALO NICODEME', '694737086', NULL, 'NGO BELL ODILENNE LAFORTUNE', '696113693', NULL, NULL, NULL, 69, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 13:16:12', '2025-09-11 13:16:12', 1, '25A00785', 4, 0),
(816, 'ARAFATH DJOUMAI LABELLE', 'LABELLE', 'ARAFATH DJOUMAI', '2013-10-11', 'DOUALA', 'F', 'ISSA NGAMBO', '699498283', NULL, 'FASIIATOU TAIROU', '.', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 13:40:16', '2025-09-11 13:40:16', 1, '25A00786', 20, 0),
(817, 'RAMATOU PRINCESSE', 'PRINCESSE', 'RAMATOU', '2009-03-09', 'DOUALA', 'F', 'ISSA NGAMBO', '699498283', NULL, 'FASSILATOU TAIROU', '.', NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 13:47:26', '2025-09-11 13:47:26', 1, '25A00787', 11, 0),
(818, 'NOUGA NGNOBIA CHRIST ROY', 'CHRIST ROY', 'NOUGA NGNOBIA', '2013-09-15', 'DOUALA', 'M', 'NGNOBIA DANIEL', '682769891', NULL, 'NGEUDA ALIMATOU', '677949771', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-11 14:13:00', '2025-09-11 14:13:00', 1, '25A00788', 18, 0),
(819, 'ANAPA VANESSA CHRISTINE', 'VANESSA CHRISTINE', 'ANAPA', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 04:21:49', '2025-09-12 04:21:49', 1, '25A00789', 13, 0),
(820, 'KUETE TANO ANGE RISTELLE', 'ANGE RISTELLE', 'KUETE TANO', '2009-01-14', 'DOUALA', 'F', 'P.JEAN TANO', '679413715', NULL, 'PHOMENA DORIANE', '672975987', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 05:15:14', '2025-09-12 05:15:14', 1, '25A00790', 48, 0),
(821, 'MANTHO NKONLACK CLARA BRUNETTE', 'CLARA BRUNETTE', 'MANTHO NKONLACK', '2009-10-24', 'DOUALA', 'F', 'NKONLACK HUGUES', '695425409', NULL, 'NGAFFO SERAFINE', '.', NULL, NULL, NULL, 104, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 05:22:44', '2025-09-12 05:22:44', 1, '25A00791', 5, 0),
(822, 'CHOUADEU POUMENI ANTOINETTE LESLI', 'ANTOINETTE LESLI', 'CHOUADEU POUMENI', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 70, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 05:41:27', '2025-09-12 05:41:27', 1, '25A00792', 4, 0),
(823, 'BADIANA RUTH PECULIA', 'RUTH PECULIA', 'BADIANA', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, NULL, '.', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 05:49:23', '2025-09-12 05:49:23', 1, '25A00793', 19, 0),
(824, 'TCHAYA WOME NGONO DESIREE', 'NGONO DESIREE', 'TCHAYA WOME', '2007-03-04', 'SOUZA-GARE', 'F', 'TCHAYA ROGER XAVIER', '674467218', NULL, 'KANSE FIDELIE', '690333722', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 05:55:49', '2025-09-12 05:55:49', 1, '25A00794', 7, 0),
(825, 'TEDONGMO MELI FRASHNELLE', 'FRASHNELLE', 'TEDONGMO MELI', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:07:20', '2025-09-12 06:07:20', 1, '25A00795', 7, 0),
(826, 'NTOUKO NOUBISSIE CLEONA MIKAELLA', 'CLEONA MIKAELLA', 'NTOUKO NOUBISSIE', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:10:40', '2025-09-12 06:10:40', 1, '25A00796', 26, 0),
(827, 'ATCHONKE MBANGA CHRIS WILSON', 'CHRIS WILSON', 'ATCHONKE MBANGA', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:13:27', '2025-09-12 06:13:27', 1, '25A00797', 31, 0),
(828, 'MBOULA TCHOUALA CHRIST NATHAN', 'CHRIST NATHAN', 'MBOULA TCHOUALA', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:17:45', '2025-09-12 06:17:45', 1, '25A00798', 8, 0),
(829, 'TEKOH FAVOUR AJEG .', '.', 'TEKOH FAVOUR AJEG', '2014-04-03', 'BUEA', 'M', 'TEKOH BARNABAS TEKOH', '651683664', NULL, 'TEKO VERA NOUM', '675157923', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:26:12', '2025-09-12 06:26:12', 1, '25A00799', 32, 0),
(830, 'TCHOMENI KAPWA SCHELLA', 'SCHELLA', 'TCHOMENI KAPWA', '2011-11-21', 'DOUALA', 'F', 'KAPNAK KAPWA ROMEO', '691830672', NULL, 'FEUDJEU ABALI GLADYS INGRID', '653879040', NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:30:37', '2025-09-12 06:30:37', 1, '25A00800', 9, 0),
(831, 'MEGAPTCHE MODJUYIE BENIGNE DEGRACE', 'BENIGNE DEGRACE', 'MEGAPTCHE MODJUYIE', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:37:08', '2025-09-12 06:37:08', 1, '25A00801', 8, 0),
(832, 'NDI BALLA LAURIS URSELA', 'LAURIS URSELA', 'NDI BALLA', '2008-04-23', 'DOUALA', 'F', 'ANDRE MARIE BALLA', '691401904', NULL, 'ABE JUSTIN ODETTE', '694449904', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:43:55', '2025-09-12 06:43:55', 1, '25A00802', 49, 0);
INSERT INTO `students` (`id`, `name`, `first_name`, `last_name`, `date_of_birth`, `place_of_birth`, `gender`, `parent_name`, `parent_phone`, `parent_email`, `mother_name`, `mother_phone`, `address`, `photo`, `subname`, `class_series_id`, `email`, `student_status`, `phone_number`, `birthday`, `birthday_place`, `sex`, `father_name`, `profession`, `status`, `is_new`, `is_active`, `created_at`, `updated_at`, `school_year_id`, `student_number`, `order`, `has_scholarship_enabled`) VALUES
(833, 'MOMO TCHONANG VENANT WILFRIED', 'VENANT WILFRIED', 'MOMO TCHONANG', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:49:45', '2025-09-12 06:49:45', 1, '25A00803', 19, 0),
(834, 'TOMBOM MARIE GINETTE', 'MARIE GINETTE', 'TOMBOM', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:55:45', '2025-09-12 06:55:45', 1, '25A00804', 56, 0),
(835, 'BITEE GENIE CLAVER', 'CLAVER', 'BITEE GENIE', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 06:58:29', '2025-09-12 06:59:32', 1, '25A00805', 20, 1),
(836, 'SAGUK FOTSO DIBRICE PHILEMON', 'DIBRICE PHILEMON', 'SAGUK FOTSO', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:04:02', '2025-09-12 07:04:02', 1, '25A00806', 27, 0),
(837, 'ATOUBA ONDOUA ANNE FABIOLA', 'ANNE FABIOLA', 'ATOUBA ONDOUA', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:06:30', '2025-09-12 07:06:30', 1, '25A00807', 21, 0),
(838, 'SAHA DOUMTSOP OSEE ESRAM', 'OSEE ESRAM', 'SAHA DOUMTSOP', '2008-05-13', 'DOUALA', 'M', 'DOUMTSOP BLAISE OLIVIER', '691444019', NULL, 'TIMELA SAHA ADELINE', '672378899', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:11:47', '2025-09-12 07:11:47', 1, '25A00808', 57, 0),
(839, 'ONDO EKOUTI ORNELLA MYLEN', 'ORNELLA MYLEN', 'ONDO EKOUTI', '2006-01-04', 'DOUALA', 'F', 'EKOUTI ELLA SYLVAIRE', '674869041', NULL, 'BILOA ELISABETH URCILE', '654839072', NULL, NULL, NULL, 41, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:19:30', '2025-09-12 07:19:30', 1, '25A00809', 5, 0),
(840, 'ALETELEH EMMERENCIA NORONZEM', 'NORONZEM', 'ALETELEH EMMERENCIA', '2007-11-28', 'MUYUKA', 'F', 'ASONG GRABRA', '677116529', NULL, 'ATABONG AGENES', '653960668', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:29:09', '2025-09-12 07:29:09', 1, '25A00810', 8, 0),
(841, 'NGO MBEE MARIE', 'MARIE', 'NGO MBEE', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:35:10', '2025-09-12 07:35:10', 1, '25A00811', 14, 0),
(842, 'MOBYA ANNE LEANE', 'ANNE LEANE', 'MOBYA', '2011-01-02', 'SONGMBO', 'F', 'KONDO GEORGE', '697393694', NULL, 'BELL ELISETTE', '656503270', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:46:44', '2025-09-12 07:46:44', 1, '25A00812', 21, 0),
(843, 'TSOE SAYANGANE CLAUDE ANNE', 'CLAUDE ANNE', 'TSOE SAYANGANE', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:52:42', '2025-09-12 07:52:42', 1, '25A00813', 58, 0),
(844, 'NGWO THOMAS .', '.', 'NGWO THOMAS', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 07:58:22', '2025-09-12 07:58:22', 1, '25A00814', 14, 0),
(846, 'MITJAMLA BLAISE YVAN', 'BLAISE YVAN', 'MITJAMLA', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:01:35', '2025-09-12 08:01:35', 1, '25A00815', 59, 0),
(847, 'NYA TCHAKOUNTE PRINCESSE ARIANE', 'PRINCESSE ARIANE', 'NYA TCHAKOUNTE', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 16, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:01:48', '2025-09-12 08:01:48', 1, '25A00816', 22, 1),
(848, 'DJAWA LENOU JANINA', 'JANINA', 'DJAWA LENOU', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 104, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:03:23', '2025-09-12 08:03:23', 1, '25A00817', 6, 0),
(849, 'KAMDE TENAWA EUGENE DASSY', 'EUGENE DASSY', 'KAMDE TENAWA', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:07:09', '2025-09-12 08:07:09', 1, '25A00818', 34, 0),
(851, 'KENMOE FESSIE ANGE MICHELLE', 'ANGE MICHELLE', 'KENMOE FESSIE', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:09:16', '2025-09-12 08:09:16', 1, '25A00820', 15, 0),
(852, 'DJOUWA DIZE ELVIRA DIVINE', 'ELVIRA DIVINE', 'DJOUWA DIZE', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:11:03', '2025-09-12 08:11:03', 1, '25A00821', 50, 0),
(853, 'mbogning takou wilfride', 'wilfride', 'mbogning takou', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:11:51', '2025-09-12 08:11:51', 1, '25A00822', 40, 0),
(854, 'MOUNANA MOUSSOMBO OCEANNE JACQUIE', 'OCEANNE JACQUIE', 'MOUNANA MOUSSOMBO', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:13:23', '2025-09-12 08:13:23', 1, '25A00823', 51, 0),
(856, 'NKOT EVELINE PASCALE', 'EVELINE PASCALE', 'NKOT', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:29:20', '2025-09-12 08:29:20', 1, '25A00825', 60, 0),
(857, 'IYAMI TOKI JOSEPH', 'JOSEPH', 'IYAMI TOKI', '2004-01-01', 'DOUALA', 'M', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 91, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:31:16', '2025-09-12 08:31:16', 1, '25A00826', 2, 0),
(858, 'DONANG NZEMENI MEGANNE ZITA', 'MEGANNE ZITA', 'DONANG NZEMENI', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:32:52', '2025-09-12 08:32:52', 1, '25A00827', 15, 0),
(859, 'MAFFO TAMBOU FERIOLE TATIANE', 'FERIOLE TATIANE', 'MAFFO TAMBOU', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:42:02', '2025-09-12 08:42:02', 1, '25A00828', 10, 0),
(860, 'DUI IYA ATTA MARIE', 'ATTA MARIE', 'DUI IYA', '2006-05-25', 'DOUALA', 'F', 'IYA PAUL', '690644059', NULL, 'GBANE BERNADETTE', '679235950', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:47:43', '2025-09-12 08:47:43', 1, '25A00829', 52, 0),
(861, 'WOUVI TAKOU FRANCK ARON', 'FRANCK ARON', 'WOUVI TAKOU', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:52:41', '2025-09-12 08:52:41', 1, '25A00830', 35, 0),
(862, 'NGAMENI DJIMGOU ANGE PATRICIA', 'ANGE PATRICIA', 'NGAMENI DJIMGOU', '2004-01-01', 'DOUALA', 'F', '.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 49, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:54:46', '2025-09-12 08:54:46', 1, '25A00831', 10, 0),
(863, 'TCHANA LEWE NATACHA CHANELLE', 'NATACHA CHANELLE', 'TCHANA LEWE', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 65, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 08:55:13', '2025-09-12 08:55:13', 1, '25A00832', 9, 0),
(864, 'BA\'ANA SERAPHINE KARELLE', 'SERAPHINE KARELLE', 'BA\'ANA', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 69, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 09:04:35', '2025-09-12 09:04:35', 1, '25A00833', 5, 0),
(865, 'BAKAM BUIMLA ANNE MARIE', 'ANNE MARIE', 'BAKAM BUIMLA', '2014-08-29', 'DOUALA', 'F', 'BIUMLA AIME RODRIGUE', '677164193', NULL, 'BUIMLA GUESSU GERMAINE', '.', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 09:16:45', '2025-09-12 09:16:45', 1, '25A00834', 21, 0),
(866, 'TCHUENTE BIUMLA ALINE GISELE', 'ALINE GISELE', 'TCHUENTE BIUMLA', '2010-02-12', 'PAN-MAKAK', 'F', 'BIUMLA AIME RODRIGUE', '677649374', NULL, 'NGUESSU GERMAINE', '.', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 09:19:48', '2025-09-12 09:19:48', 1, '25A00835', 10, 0),
(867, 'NGO BIUMLA GRACE THERESE', 'GRACE THERESE', 'NGO BIUMLA', '2007-04-24', 'PAN-MAKAK', 'F', 'BIUMLA AIME RODRIGUE', '677649374', NULL, 'BIUMLA GUESSU GERMAINE', '693129928', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 09:24:04', '2025-09-12 09:24:04', 1, '25A00836', 8, 0),
(868, 'NGANSOP DJAKWA FADEL BRYANT', 'FADEL BRYANT', 'NGANSOP DJAKWA', '2010-10-11', 'DOUALA', 'M', 'WANDJI NGANSOP ROMARIC DUBOIS', '674427081', NULL, 'YOUSSEU CELINE', '655285569', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 10:17:36', '2025-09-12 10:17:36', 1, '25A00837', 61, 0),
(869, 'TCHOUWA TCHATCHOUANG PATIENCE DANIELE', 'PATIENCE DANIELE', 'TCHOUWA TCHATCHOUANG', '2008-01-30', 'DOUALA', 'F', 'TCHATCHOUANG PAUL', '678245001', NULL, 'TANMI ODILE BARBARA', '.', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 10:21:42', '2025-09-12 10:21:42', 1, '25A00838', 9, 0),
(870, 'MAGUEGOUE LAPPI ISABELLE FRAICHLINE', 'ISABELLE FRAICHLINE', 'MAGUEGOUE LAPPI', '2007-10-08', 'DOUALA', 'F', 'LAPPI ELIE', '676343532', NULL, 'KAMENI BERLINE', '.', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 10:28:35', '2025-09-12 10:28:35', 1, '25A00839', 53, 0),
(871, 'HAKOUA WONKAM GUY MERLIN', 'GUY MERLIN', 'HAKOUA WONKAM', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 61, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 10:38:56', '2025-09-12 10:38:56', 1, '25A00840', 4, 0),
(872, 'MEZAM JORANE VANESSA', 'JORANE VANESSA', 'MEZAM', '2008-05-23', 'DOUALA', 'F', 'DJOUMESSI', '696242440', NULL, 'METSAJIO STEPHANIE', '691927320', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 10:51:34', '2025-09-12 10:51:34', 1, '25A00841', 41, 0),
(873, 'NJOM PIERRE ULRICK DAVID', 'PIERRE ULRICK DAVID', 'NJOM', '2006-03-15', 'DOUALA', 'M', 'NJOM DAVID FELIX', '677691631', NULL, 'NJOLLE SOPHIE MARTINE', '640209170', NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 10:55:44', '2025-09-12 10:59:41', 1, '25A00842', 14, 0),
(874, 'LECHE SIGHE VALDES', 'VALDES', 'LECHE SIGHE', '2007-04-19', 'BAMENGOUM', 'M', 'FONGANG MARCELIN', '674517565', NULL, 'MAGANG SOPHIE', '672666148', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 11:04:28', '2025-09-12 11:04:28', 1, '25A00843', 62, 0),
(875, 'YAHE ELESSA ROSALIE INGRID', 'ROSALIE INGRID', 'YAHE ELESSA', '2007-03-27', 'DOUALA', 'F', 'ELESSA SOLE', '699099775', NULL, 'BANGYA MARIE NOEL', '699781680', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 11:10:07', '2025-09-12 11:10:07', 1, '25A00844', 37, 0),
(876, 'MBIANFORKWA LASTANIA PRINCESSE', 'LASTANIA PRINCESSE', 'MBIANFORKWA', '2011-10-09', 'YAOUNDE', 'F', 'KEMAJOU NKOUAPLONG THIERRY RODRIGUE', '694684519', NULL, 'MBALA AVINA MARIE LOUISE', '.', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 11:19:28', '2025-09-12 11:19:28', 1, '25A00845', 16, 0),
(877, 'DOOH ELOMBO NDEDI', 'NDEDI', 'DOOH ELOMBO', '2013-03-30', 'DOUALA', 'M', 'DOOH ELOMBO JOSS DANIEL', '696360061', NULL, 'ELOMBO ZANG BERNADETTE', '696491547', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 11:23:08', '2025-09-12 11:23:08', 1, '25A00846', 13, 0),
(878, 'NDZIE NGUELE BERNADETTE ORNELLA', 'BERNADETTE ORNELLA', 'NDZIE NGUELE', '2012-02-21', 'YAOUNDE', 'F', 'NDI NGUELE RICHARD', NULL, NULL, 'NDZIE BERNADETTE ESTELLE', '656531950', NULL, NULL, NULL, 73, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 11:33:24', '2025-09-12 11:33:24', 1, '25A00847', 5, 0),
(879, 'DIKONGUE BELLE STEVE JORDAN', 'STEVE JORDAN', 'DIKONGUE BELLE', '2007-03-04', 'DOUALA', 'M', 'DIKONGUE BELLE ADAMS', '658869507', NULL, 'ETO\'O JOSIANE', '699949766', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 12:11:45', '2025-09-12 12:11:45', 1, '25A00848', 8, 0),
(880, 'AZIMBOMBI QUEEN ALIAN', 'ALIAN', 'AZIMBOMBI QUEEN', '2011-05-16', 'NDOP', 'F', 'TATANG ALI', '675795071', NULL, 'MILORINE MBOMBIHOH', '679243158', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 12:24:46', '2025-09-12 12:24:46', 1, '25A00849', 33, 0),
(881, 'MOUYAMA AICHA MANGA', 'AICHA MANGA', 'MOUYAMA', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 12:29:09', '2025-09-12 12:43:41', 1, '25A00850', 19, 0),
(882, 'KOUNA ANASTASIE ELSY', 'ANASTASIE ELSY', 'KOUNA', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 12:31:28', '2025-09-12 12:31:28', 1, '25A00851', 18, 0),
(883, 'FOGAN TAKOU BRAYAN DARLING', 'DARLING', 'FOGAN TAKOU BRAYAN', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 12:39:18', '2025-09-12 12:39:18', 1, '25A00852', 27, 0),
(884, 'BILOA BRIGITTE AUDREY', 'BRIGITTE AUDREY', 'BILOA', '2015-09-25', 'DOUALA', 'F', 'DO?KONG ERIC BERTIN', '698101019', NULL, 'NGA TABI BENEDICTE', '683735487', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 12:44:01', '2025-09-12 12:44:01', 1, '25A00853', 28, 0),
(885, 'LE-NYE 3 JEAN APOTRE', 'APOTRE', 'LE-NYE 3 JEAN', '2004-01-01', 'DOUALA', 'M', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 12:53:10', '2025-09-12 12:53:10', 1, '25A00854', 20, 0),
(886, 'OUM ODETTE NAVELIE', 'ODETTE NAVELIE', 'OUM', '2004-01-01', 'DOUALA', 'F', '.', '.', NULL, '.', '.', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-12 13:20:38', '2025-09-12 13:20:38', 1, '25A00855', 38, 0);

-- --------------------------------------------------------

--
-- Structure de la table `student_attendances`
--

CREATE TABLE `student_attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `school_class_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `is_present` tinyint(1) NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `marked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `attendance_type` enum('manual','qr_scan','automatic') NOT NULL DEFAULT 'manual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `student_rame_status`
--

CREATE TABLE `student_rame_status` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `has_brought_rame` tinyint(1) NOT NULL DEFAULT 0,
  `marked_date` date DEFAULT NULL,
  `deposit_date` date DEFAULT NULL COMMENT 'Date de dépôt physique de la RAME',
  `marked_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `student_rame_status`
--

INSERT INTO `student_rame_status` (`id`, `student_id`, `school_year_id`, `has_brought_rame`, `marked_date`, `deposit_date`, `marked_by_user_id`, `notes`, `created_at`, `updated_at`) VALUES
(3, 3, 1, 1, '2025-08-04', NULL, NULL, NULL, '2025-08-04 07:48:06', '2025-08-04 07:48:10'),
(4, 4, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-08-04 07:50:46', '2025-09-02 08:28:56'),
(6, 6, 1, 1, '2025-08-04', NULL, NULL, NULL, '2025-08-04 08:05:18', '2025-08-04 08:05:38'),
(8, 8, 1, 1, '2025-08-04', NULL, NULL, NULL, '2025-08-04 08:26:27', '2025-08-04 08:27:12'),
(10, 10, 1, 1, '2025-08-04', NULL, NULL, NULL, '2025-08-04 09:25:29', '2025-08-04 09:25:35'),
(11, 11, 1, 1, '2025-08-04', NULL, NULL, NULL, '2025-08-04 09:29:06', '2025-08-04 09:29:11'),
(12, 12, 1, 1, '2025-08-04', NULL, NULL, NULL, '2025-08-04 10:11:24', '2025-08-04 10:11:29'),
(13, 13, 1, 1, '2025-08-08', NULL, 3, NULL, '2025-08-04 11:22:57', '2025-08-08 09:31:32'),
(17, 17, 1, 1, '2025-08-04', NULL, 12, NULL, '2025-08-04 12:57:07', '2025-08-04 12:57:23'),
(18, 18, 1, 1, '2025-08-04', NULL, 12, NULL, '2025-08-04 13:16:48', '2025-08-04 13:17:06'),
(19, 19, 1, 1, '2025-08-04', NULL, 3, NULL, '2025-08-04 13:30:42', '2025-08-04 13:31:44'),
(20, 20, 1, 1, '2025-08-04', NULL, 3, NULL, '2025-08-04 14:00:44', '2025-08-04 14:02:04'),
(22, 22, 1, 1, '2025-08-06', NULL, 3, NULL, '2025-08-06 08:31:37', '2025-08-06 08:35:11'),
(23, 23, 1, 1, '2025-08-06', NULL, 3, NULL, '2025-08-06 08:48:06', '2025-08-06 08:48:12'),
(24, 24, 1, 1, '2025-08-06', NULL, 3, NULL, '2025-08-06 09:12:45', '2025-08-06 09:13:08'),
(25, 25, 1, 1, '2025-08-06', NULL, 3, NULL, '2025-08-06 09:32:49', '2025-08-06 09:33:03'),
(26, 26, 1, 1, '2025-08-06', NULL, 3, NULL, '2025-08-06 09:42:23', '2025-08-06 09:42:28'),
(30, 30, 1, 1, '2025-08-06', NULL, 3, NULL, '2025-08-06 10:33:18', '2025-08-06 10:33:27'),
(31, 31, 1, 1, '2025-08-06', NULL, 3, NULL, '2025-08-06 10:41:15', '2025-08-06 10:44:09'),
(32, 32, 1, 1, '2025-08-06', NULL, 12, NULL, '2025-08-06 11:48:21', '2025-08-06 12:08:34'),
(34, 34, 1, 1, '2025-08-16', '2025-08-16', 3, NULL, '2025-08-06 13:34:00', '2025-08-16 08:43:17'),
(35, 36, 1, 1, '2025-08-06', NULL, 12, NULL, '2025-08-06 14:13:38', '2025-08-06 14:16:57'),
(36, 37, 1, 1, '2025-08-06', NULL, 12, NULL, '2025-08-06 14:35:56', '2025-08-06 14:38:26'),
(37, 38, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-08-06 14:44:51', '2025-09-02 08:30:47'),
(38, 39, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-08-06 14:58:49', '2025-09-01 09:18:57'),
(40, 41, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-08-07 11:11:28', '2025-09-02 08:31:32'),
(41, 42, 1, 1, '2025-08-07', NULL, 3, NULL, '2025-08-07 11:19:45', '2025-08-07 11:19:58'),
(42, 43, 1, 1, '2025-08-07', NULL, 3, NULL, '2025-08-07 11:27:34', '2025-08-07 11:27:38'),
(44, 45, 1, 1, '2025-08-07', NULL, 3, NULL, '2025-08-07 11:33:37', '2025-08-07 11:33:42'),
(45, 46, 1, 1, '2025-08-07', NULL, 3, NULL, '2025-08-07 11:42:53', '2025-08-07 11:42:58'),
(47, 48, 1, 1, '2025-08-07', NULL, 3, NULL, '2025-08-07 11:47:57', '2025-08-07 11:48:03'),
(48, 49, 1, 1, '2025-08-07', NULL, 3, NULL, '2025-08-07 12:02:00', '2025-08-07 12:02:06'),
(50, 51, 1, 1, '2025-08-07', NULL, 12, NULL, '2025-08-07 12:46:51', '2025-08-07 12:48:28'),
(51, 52, 1, 1, '2025-08-07', NULL, 12, NULL, '2025-08-07 12:58:37', '2025-08-07 13:01:02'),
(53, 54, 1, 1, '2025-08-07', NULL, 12, NULL, '2025-08-07 13:38:30', '2025-08-07 13:40:43'),
(59, 61, 1, 1, '2025-08-11', NULL, 3, NULL, '2025-08-11 06:48:25', '2025-08-11 06:48:31'),
(60, 62, 1, 1, '2025-08-12', '2025-08-12', 3, NULL, '2025-08-12 06:11:21', '2025-08-12 06:11:58'),
(63, 65, 1, 1, '2025-08-12', '2025-08-12', 3, NULL, '2025-08-12 07:16:59', '2025-08-12 07:18:18'),
(64, 66, 1, 1, '2025-08-12', '2025-08-12', 3, NULL, '2025-08-12 10:50:34', '2025-08-12 10:50:47'),
(65, 67, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-08-12 10:56:15', '2025-09-02 08:32:43'),
(67, 69, 1, 1, '2025-08-16', '2025-08-16', 3, NULL, '2025-08-15 06:06:48', '2025-08-16 06:44:05'),
(70, 72, 1, 1, '2025-08-15', '2025-08-15', 3, NULL, '2025-08-15 06:28:00', '2025-08-15 06:28:15'),
(71, 73, 1, 1, '2025-08-15', '2025-08-15', 3, NULL, '2025-08-15 06:34:49', '2025-08-15 06:34:57'),
(72, 76, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 11:57:41', '2025-08-19 11:57:47'),
(73, 77, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:05:02', '2025-08-19 14:05:12'),
(75, 79, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:14:50', '2025-08-19 14:14:54'),
(76, 80, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:18:00', '2025-08-19 14:18:05'),
(77, 81, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:21:21', '2025-08-19 14:21:28'),
(78, 82, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:27:09', '2025-08-19 14:27:13'),
(79, 83, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:34:53', '2025-08-19 14:34:59'),
(80, 84, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:38:42', '2025-08-19 14:38:45'),
(82, 86, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:47:31', '2025-08-19 14:47:34'),
(83, 87, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 14:52:15', '2025-08-19 14:52:18'),
(84, 88, 1, 1, '2025-08-19', '2025-08-19', 15, NULL, '2025-08-19 15:00:15', '2025-08-19 15:00:18'),
(85, 89, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 06:25:56', '2025-08-20 06:26:10'),
(86, 90, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 06:30:51', '2025-08-20 06:30:54'),
(87, 91, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 07:22:45', '2025-08-20 07:22:55'),
(88, 92, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 07:27:55', '2025-08-20 07:27:58'),
(89, 93, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 07:44:00', '2025-08-20 07:44:09'),
(90, 94, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 07:47:50', '2025-08-20 07:47:52'),
(91, 95, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 07:51:35', '2025-08-20 07:51:41'),
(92, 96, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 07:55:03', '2025-08-20 07:55:10'),
(93, 97, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 08:00:12', '2025-08-20 08:00:15'),
(94, 98, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 08:06:38', '2025-08-20 08:06:41'),
(95, 99, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 08:24:23', '2025-08-20 08:24:28'),
(96, 100, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 09:27:09', '2025-08-20 09:27:12'),
(97, 101, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 09:31:11', '2025-08-20 09:31:14'),
(98, 102, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 09:38:55', '2025-08-20 09:39:09'),
(99, 103, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 09:43:47', '2025-08-20 09:43:50'),
(100, 104, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 09:51:34', '2025-08-20 09:51:38'),
(101, 105, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 10:02:27', '2025-08-20 10:02:33'),
(102, 106, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 10:05:44', '2025-08-20 10:05:54'),
(103, 107, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 10:10:51', '2025-08-20 10:10:54'),
(104, 108, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 10:22:32', '2025-08-20 10:22:36'),
(105, 109, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 10:59:36', '2025-08-20 10:59:40'),
(106, 110, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:02:36', '2025-08-20 11:02:40'),
(107, 111, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:05:11', '2025-08-20 11:05:14'),
(108, 112, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:08:36', '2025-08-20 11:08:41'),
(110, 114, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:18:21', '2025-08-20 11:18:24'),
(111, 115, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:22:04', '2025-08-20 11:22:07'),
(112, 116, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:25:26', '2025-08-20 11:25:29'),
(113, 117, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:30:14', '2025-08-20 11:30:18'),
(114, 118, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:33:55', '2025-08-20 11:33:59'),
(115, 119, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 11:37:03', '2025-08-20 11:37:07'),
(116, 120, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 12:23:28', '2025-08-20 12:23:39'),
(117, 121, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 12:26:35', '2025-08-20 12:26:37'),
(118, 122, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 12:29:57', '2025-08-20 12:30:00'),
(119, 123, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 13:52:07', '2025-08-20 13:52:13'),
(120, 124, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 14:00:53', '2025-08-20 14:00:57'),
(121, 125, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 14:05:24', '2025-08-20 14:05:30'),
(122, 126, 1, 1, '2025-08-20', '2025-08-20', 15, NULL, '2025-08-20 14:09:51', '2025-08-20 14:10:01'),
(123, 127, 1, 1, '2025-08-26', '2025-08-26', 16, NULL, '2025-08-26 11:08:07', '2025-08-26 11:08:37'),
(125, 129, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 09:28:23', '2025-08-27 09:28:28'),
(126, 130, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 09:59:07', '2025-08-27 09:59:11'),
(127, 131, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:03:36', '2025-08-27 10:03:46'),
(128, 132, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:11:19', '2025-08-27 10:11:22'),
(129, 133, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:13:52', '2025-08-27 10:13:57'),
(130, 134, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:16:45', '2025-08-27 10:16:49'),
(131, 135, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:21:05', '2025-08-27 10:21:10'),
(132, 136, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:24:53', '2025-08-27 10:24:57'),
(133, 137, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:27:55', '2025-08-27 10:27:58'),
(134, 138, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:31:10', '2025-08-27 10:31:14'),
(135, 139, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:34:14', '2025-08-27 10:34:17'),
(136, 140, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:36:48', '2025-08-27 10:36:53'),
(137, 141, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:40:52', '2025-08-27 10:40:58'),
(138, 142, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:43:33', '2025-08-27 10:43:37'),
(140, 144, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 11:08:40', '2025-08-27 11:08:49'),
(141, 145, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 11:14:24', '2025-08-27 11:14:34'),
(142, 146, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 11:21:33', '2025-08-27 11:21:41'),
(143, 147, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 11:28:32', '2025-08-27 11:28:45'),
(144, 148, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 11:38:45', '2025-08-27 11:38:50'),
(145, 149, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 12:14:52', '2025-08-27 12:14:58'),
(146, 150, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 12:22:32', '2025-08-27 12:22:35'),
(147, 151, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 12:26:15', '2025-08-27 12:26:18'),
(148, 152, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 12:39:20', '2025-08-27 12:39:25'),
(149, 153, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 12:42:37', '2025-08-27 12:42:40'),
(150, 154, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 12:46:18', '2025-08-27 12:46:22'),
(151, 155, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 12:51:05', '2025-08-27 12:51:20'),
(152, 156, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 12:59:27', '2025-08-27 12:59:29'),
(153, 157, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:08:02', '2025-08-27 13:08:05'),
(154, 158, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:12:58', '2025-08-27 13:13:05'),
(155, 159, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:17:45', '2025-08-27 13:17:47'),
(156, 160, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:23:44', '2025-08-27 13:24:17'),
(157, 161, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:26:58', '2025-08-27 13:27:01'),
(158, 162, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:30:05', '2025-08-27 13:30:08'),
(159, 163, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:41:47', '2025-08-27 13:41:50'),
(160, 164, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:45:11', '2025-08-27 13:45:13'),
(161, 165, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:53:23', '2025-08-27 13:53:26'),
(162, 166, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 13:58:55', '2025-08-27 13:58:58'),
(163, 167, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 06:18:53', '2025-08-28 06:18:58'),
(164, 168, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 06:22:29', '2025-08-28 06:22:34'),
(165, 169, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 06:28:48', '2025-08-28 06:28:51'),
(166, 170, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:18:22', '2025-08-28 07:18:25'),
(167, 171, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:21:34', '2025-08-28 07:21:37'),
(168, 172, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:25:17', '2025-08-28 07:25:21'),
(169, 173, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:28:35', '2025-08-28 07:28:39'),
(170, 174, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:33:09', '2025-08-28 07:33:13'),
(171, 175, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:35:40', '2025-08-28 07:35:43'),
(172, 176, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:39:02', '2025-08-28 07:39:05'),
(173, 177, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:42:16', '2025-08-28 07:42:20'),
(174, 178, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:48:02', '2025-08-28 07:48:05'),
(175, 179, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:53:19', '2025-08-28 07:53:22'),
(176, 180, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 07:57:16', '2025-08-28 07:57:19'),
(177, 181, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 08:04:43', '2025-08-28 08:04:46'),
(178, 182, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 08:08:14', '2025-08-28 08:08:16'),
(179, 183, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 08:11:23', '2025-08-28 08:11:25'),
(180, 184, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 08:13:46', '2025-08-28 08:13:49'),
(181, 185, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 08:17:06', '2025-08-28 08:17:11'),
(182, 186, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 08:26:29', '2025-08-28 08:27:13'),
(183, 187, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 08:33:07', '2025-08-28 08:33:11'),
(184, 188, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:12:47', '2025-08-28 10:12:54'),
(185, 189, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:17:21', '2025-08-28 10:17:25'),
(186, 190, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:20:40', '2025-08-28 10:20:44'),
(187, 191, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:26:25', '2025-08-28 10:26:29'),
(188, 192, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:29:22', '2025-08-28 10:29:25'),
(189, 193, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:33:04', '2025-08-28 10:33:13'),
(190, 194, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:38:00', '2025-08-28 10:38:06'),
(191, 195, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:40:31', '2025-08-28 10:40:34'),
(192, 196, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:43:33', '2025-08-28 10:43:38'),
(193, 197, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:46:47', '2025-08-28 10:46:52'),
(194, 198, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:49:34', '2025-08-28 10:49:37'),
(195, 199, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:53:46', '2025-08-28 10:53:49'),
(196, 200, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 10:57:11', '2025-08-28 10:57:13'),
(197, 201, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:02:11', '2025-08-28 11:02:15'),
(198, 202, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:05:18', '2025-08-28 11:05:21'),
(199, 203, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:09:22', '2025-08-28 11:09:26'),
(200, 204, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:11:49', '2025-08-28 11:11:54'),
(201, 205, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:14:27', '2025-08-28 11:14:30'),
(202, 206, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:17:07', '2025-08-28 11:17:10'),
(203, 207, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:22:23', '2025-08-28 11:22:26'),
(204, 208, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:25:47', '2025-08-28 11:25:55'),
(205, 209, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:28:55', '2025-08-28 11:28:57'),
(206, 210, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:31:30', '2025-08-28 11:31:35'),
(207, 211, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:35:33', '2025-08-28 11:35:36'),
(208, 212, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:39:44', '2025-08-28 11:39:47'),
(209, 213, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:47:36', '2025-08-28 11:47:39'),
(210, 214, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:53:35', '2025-08-28 11:53:39'),
(211, 215, 1, 1, '2025-08-28', '2025-08-28', 15, NULL, '2025-08-28 11:58:55', '2025-08-28 11:59:04'),
(212, 216, 1, 1, '2025-08-29', '2025-08-29', 15, NULL, '2025-08-29 10:58:29', '2025-08-29 10:58:33'),
(213, 217, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-08-29 13:26:41', '2025-09-02 08:30:21'),
(214, 218, 1, 1, '2025-08-29', '2025-08-29', 16, NULL, '2025-08-29 13:36:11', '2025-08-29 13:37:02'),
(215, 219, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 05:50:10', '2025-08-30 05:50:21'),
(216, 220, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 05:54:51', '2025-08-30 05:55:00'),
(217, 221, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 05:58:56', '2025-08-30 05:59:06'),
(218, 222, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:03:33', '2025-08-30 06:03:40'),
(219, 223, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:04:15', '2025-08-30 06:04:23'),
(220, 224, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:07:06', '2025-08-30 06:07:12'),
(221, 225, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:09:07', '2025-08-30 06:09:12'),
(222, 226, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:09:51', '2025-08-30 06:09:54'),
(223, 227, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:12:43', '2025-08-30 06:12:46'),
(224, 228, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:13:24', '2025-08-30 06:13:27'),
(225, 229, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:15:52', '2025-08-30 06:15:55'),
(226, 230, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:17:39', '2025-08-30 06:17:57'),
(227, 231, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:20:23', '2025-08-30 06:20:28'),
(228, 232, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:22:20', '2025-08-30 06:22:23'),
(229, 233, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:23:07', '2025-08-30 06:23:10'),
(230, 235, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:25:59', '2025-08-30 06:26:03'),
(231, 234, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:26:09', '2025-08-30 06:26:14'),
(232, 236, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:28:50', '2025-08-30 06:28:53'),
(233, 237, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:29:47', '2025-08-30 06:29:54'),
(234, 238, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:31:47', '2025-08-30 06:31:50'),
(235, 239, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:33:26', '2025-08-30 06:34:00'),
(236, 240, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:34:34', '2025-08-30 06:34:39'),
(237, 241, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:38:29', '2025-08-30 06:38:32'),
(238, 242, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:41:33', '2025-08-30 06:41:37'),
(239, 243, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:42:07', '2025-08-30 06:42:10'),
(240, 244, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:44:48', '2025-08-30 06:44:51'),
(241, 245, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:45:01', '2025-08-30 06:45:05'),
(242, 246, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:48:17', '2025-08-30 06:48:20'),
(243, 247, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:48:22', '2025-08-30 06:48:26'),
(244, 248, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:50:29', '2025-08-30 06:50:32'),
(245, 249, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:53:29', '2025-08-30 06:53:34'),
(246, 250, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:53:46', '2025-08-30 06:53:51'),
(247, 251, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 06:57:24', '2025-08-30 06:57:26'),
(248, 252, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 06:58:20', '2025-08-30 06:58:31'),
(249, 253, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 07:00:17', '2025-08-30 07:00:22'),
(250, 255, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 07:03:31', '2025-08-30 07:03:34'),
(251, 256, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 07:06:46', '2025-08-30 07:06:49'),
(252, 257, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 07:09:25', '2025-08-30 07:09:28'),
(253, 258, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:11:16', '2025-08-30 07:11:22'),
(254, 259, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 07:13:07', '2025-08-30 07:13:10'),
(255, 260, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:14:36', '2025-08-30 07:14:40'),
(256, 261, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 07:17:04', '2025-08-30 07:17:11'),
(257, 262, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:19:21', '2025-08-30 07:19:26'),
(258, 263, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 07:21:02', '2025-08-30 07:21:05'),
(259, 264, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-08-30 07:23:27', '2025-09-02 08:34:29'),
(260, 265, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:25:43', '2025-08-30 07:25:48'),
(261, 266, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-08-30 07:26:47', '2025-09-02 08:31:57'),
(262, 267, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:29:15', '2025-08-30 07:29:21'),
(263, 268, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:33:33', '2025-08-30 07:33:39'),
(264, 269, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:37:43', '2025-08-30 07:37:48'),
(265, 270, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:41:45', '2025-08-30 07:41:49'),
(266, 271, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:47:34', '2025-08-30 07:47:38'),
(267, 272, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:51:45', '2025-08-30 07:51:50'),
(268, 273, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:55:59', '2025-08-30 07:56:02'),
(269, 274, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 07:58:51', '2025-08-30 07:58:57'),
(270, 275, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 08:00:29', '2025-08-30 08:00:32'),
(271, 254, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 08:19:20', '2025-08-30 08:19:30'),
(272, 276, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 08:26:15', '2025-08-30 08:26:18'),
(273, 277, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 08:35:56', '2025-08-30 08:35:59'),
(274, 278, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 08:38:57', '2025-08-30 08:39:02'),
(275, 279, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 08:53:14', '2025-08-30 08:53:20'),
(276, 280, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 08:56:31', '2025-08-30 08:56:35'),
(277, 281, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 08:58:26', '2025-08-30 08:58:29'),
(278, 282, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 09:00:22', '2025-08-30 09:00:27'),
(279, 283, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 09:02:38', '2025-08-30 09:02:42'),
(280, 284, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 09:14:12', '2025-08-30 09:14:15'),
(281, 285, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 09:16:32', '2025-08-30 09:16:37'),
(282, 286, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 09:38:20', '2025-08-30 09:38:23'),
(283, 287, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 09:59:09', '2025-08-30 09:59:12'),
(284, 288, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 10:01:27', '2025-08-30 10:01:30'),
(285, 289, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 10:19:47', '2025-08-30 10:19:57'),
(286, 290, 1, 1, '2025-08-30', '2025-08-30', 15, NULL, '2025-08-30 10:22:03', '2025-08-30 10:22:06'),
(287, 291, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 05:38:22', '2025-09-01 05:38:26'),
(288, 292, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 06:02:37', '2025-09-01 06:02:44'),
(289, 293, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 07:11:36', '2025-09-01 07:11:54'),
(290, 294, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 07:16:33', '2025-09-01 07:16:36'),
(291, 295, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 07:21:33', '2025-09-01 07:21:40'),
(292, 296, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 07:27:19', '2025-09-01 07:27:27'),
(293, 297, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 07:37:16', '2025-09-01 07:37:19'),
(294, 298, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 07:39:23', '2025-09-01 07:39:37'),
(295, 299, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 07:46:12', '2025-09-01 07:46:16'),
(296, 300, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 07:51:53', '2025-09-01 07:52:00'),
(297, 301, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 07:57:15', '2025-09-01 07:57:56'),
(298, 302, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 08:01:44', '2025-09-01 08:01:47'),
(299, 303, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 08:07:35', '2025-09-01 08:07:38'),
(300, 304, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 08:12:11', '2025-09-01 08:12:22'),
(301, 305, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 08:25:49', '2025-09-01 08:25:53'),
(302, 306, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 08:52:39', '2025-09-01 08:52:42'),
(303, 307, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 08:56:44', '2025-09-01 08:56:48'),
(304, 308, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 09:02:28', '2025-09-01 09:02:33'),
(305, 309, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 09:19:50', '2025-09-01 09:19:54'),
(306, 310, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 09:40:29', '2025-09-01 09:40:34'),
(307, 311, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 10:07:16', '2025-09-01 10:07:19'),
(308, 312, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 10:12:34', '2025-09-01 10:12:40'),
(309, 313, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 10:24:05', '2025-09-01 10:24:09'),
(310, 314, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 10:41:51', '2025-09-01 10:41:58'),
(311, 315, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 10:45:41', '2025-09-01 10:45:44'),
(312, 316, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 10:57:12', '2025-09-01 10:57:16'),
(313, 317, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 12:06:20', '2025-09-01 12:06:23'),
(314, 318, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 12:11:04', '2025-09-01 12:11:07'),
(315, 319, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 12:18:05', '2025-09-01 12:18:07'),
(316, 323, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 12:32:33', '2025-09-01 12:32:36'),
(317, 324, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 12:46:42', '2025-09-01 12:46:46'),
(318, 322, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 12:46:48', '2025-09-01 12:46:52'),
(319, 321, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 12:48:06', '2025-09-01 12:48:09'),
(320, 320, 1, 1, '2025-09-01', '2025-09-01', 16, NULL, '2025-09-01 12:50:01', '2025-09-01 12:50:11'),
(321, 325, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 12:54:35', '2025-09-01 12:54:39'),
(322, 326, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-09-01 13:05:02', '2025-09-01 13:05:08'),
(324, 328, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:27:16', '2025-09-02 04:27:19'),
(325, 329, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:32:28', '2025-09-02 04:32:31'),
(326, 330, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:35:44', '2025-09-02 04:35:47'),
(327, 331, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:38:51', '2025-09-02 04:38:54'),
(328, 332, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:42:35', '2025-09-02 04:42:39'),
(329, 333, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:45:39', '2025-09-02 04:45:42'),
(330, 334, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:49:35', '2025-09-02 04:49:40'),
(331, 335, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:53:58', '2025-09-02 04:54:04'),
(332, 336, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 04:58:32', '2025-09-02 04:58:36'),
(333, 337, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:03:11', '2025-09-02 05:03:14'),
(334, 338, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:06:36', '2025-09-02 05:06:45'),
(335, 339, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:14:51', '2025-09-02 05:14:53'),
(336, 340, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:20:43', '2025-09-02 05:20:47'),
(337, 341, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:26:47', '2025-09-02 05:26:51'),
(338, 342, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:30:48', '2025-09-02 05:30:51'),
(339, 343, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:33:56', '2025-09-02 05:34:03'),
(340, 344, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:44:24', '2025-09-02 05:44:27'),
(341, 345, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:47:29', '2025-09-02 05:47:36'),
(342, 346, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 05:50:42', '2025-09-02 05:50:46'),
(343, 347, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 06:24:00', '2025-09-02 06:24:03'),
(344, 348, 1, 0, NULL, NULL, NULL, NULL, '2025-09-02 06:40:48', '2025-09-02 06:40:48'),
(345, 349, 1, 0, NULL, NULL, NULL, NULL, '2025-09-02 06:42:43', '2025-09-02 06:42:43'),
(346, 350, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 06:50:49', '2025-09-02 06:50:53'),
(347, 351, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 06:55:12', '2025-09-02 06:55:15'),
(348, 352, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 07:06:07', '2025-09-02 07:06:10'),
(349, 353, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 07:26:01', '2025-09-02 07:26:11'),
(350, 354, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 07:44:18', '2025-09-02 07:44:21'),
(351, 355, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 07:48:14', '2025-09-02 07:48:18'),
(352, 356, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 07:55:31', '2025-09-02 07:55:34'),
(353, 357, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 07:55:56', '2025-09-02 07:55:59'),
(354, 358, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 08:02:06', '2025-09-02 08:02:09'),
(355, 359, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 08:07:42', '2025-09-02 08:07:45'),
(356, 360, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 08:20:44', '2025-09-02 08:20:48'),
(357, 361, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 08:40:02', '2025-09-02 08:40:05'),
(358, 362, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 08:48:14', '2025-09-02 08:48:17'),
(359, 363, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 08:57:46', '2025-09-02 08:57:50'),
(360, 364, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 09:15:21', '2025-09-02 09:15:24'),
(361, 365, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 09:17:23', '2025-09-02 09:17:26'),
(362, 366, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 09:35:02', '2025-09-02 09:35:05'),
(363, 367, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 09:36:08', '2025-09-02 09:36:10'),
(364, 368, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 09:37:06', '2025-09-02 09:37:09'),
(365, 369, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 09:39:05', '2025-09-02 09:39:07'),
(366, 370, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 09:39:35', '2025-09-02 09:39:40'),
(367, 371, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 09:45:14', '2025-09-02 09:45:18'),
(368, 372, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 09:48:25', '2025-09-02 09:48:30'),
(369, 373, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 09:59:23', '2025-09-02 09:59:26'),
(370, 374, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 10:00:44', '2025-09-02 10:00:50'),
(371, 375, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 10:01:51', '2025-09-02 10:01:55'),
(372, 376, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 10:04:01', '2025-09-02 10:04:05'),
(373, 377, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 10:06:39', '2025-09-02 10:06:41'),
(374, 378, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 10:09:33', '2025-09-02 10:09:36'),
(375, 379, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 10:10:49', '2025-09-02 10:10:53'),
(376, 380, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 10:14:14', '2025-09-02 10:14:16'),
(377, 381, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 10:21:14', '2025-09-02 10:21:21'),
(378, 382, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 11:35:01', '2025-09-02 11:35:03'),
(379, 383, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 11:37:31', '2025-09-02 11:37:33'),
(380, 384, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 11:40:55', '2025-09-02 11:40:59'),
(381, 385, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 11:43:06', '2025-09-02 11:43:09'),
(382, 386, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 11:45:05', '2025-09-02 11:45:08'),
(383, 387, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 11:57:35', '2025-09-02 11:57:38'),
(384, 388, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 12:02:03', '2025-09-02 12:02:06'),
(385, 389, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 12:06:13', '2025-09-02 12:06:15'),
(386, 390, 1, 1, '2025-09-02', '2025-09-02', 15, NULL, '2025-09-02 12:10:25', '2025-09-02 12:10:28'),
(387, 391, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 13:14:47', '2025-09-02 13:14:51'),
(388, 392, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 13:17:48', '2025-09-02 13:17:51'),
(389, 393, 1, 0, NULL, NULL, NULL, NULL, '2025-09-02 13:26:57', '2025-09-02 13:26:57'),
(390, 394, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 05:58:35', '2025-09-03 05:58:37'),
(391, 395, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 06:08:20', '2025-09-03 06:12:49'),
(392, 396, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 06:10:36', '2025-09-03 06:12:02'),
(393, 397, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 06:17:13', '2025-09-03 06:17:17'),
(394, 398, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 06:31:43', '2025-09-03 06:31:46'),
(395, 399, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 07:13:23', '2025-09-03 07:13:28'),
(396, 400, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 08:06:25', '2025-09-03 08:06:29'),
(397, 401, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 08:11:25', '2025-09-03 08:11:29'),
(398, 402, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 08:15:25', '2025-09-03 08:15:28'),
(399, 403, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 08:28:21', '2025-09-03 08:28:24'),
(400, 404, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 08:39:07', '2025-09-03 08:39:12'),
(401, 405, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 08:41:10', '2025-09-03 08:41:13'),
(402, 406, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 08:47:44', '2025-09-03 08:47:55'),
(403, 407, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 08:54:46', '2025-09-03 08:54:49'),
(404, 408, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:03:40', '2025-09-03 09:03:43'),
(405, 409, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:06:06', '2025-09-03 09:06:09'),
(406, 410, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:09:05', '2025-09-03 09:09:07'),
(407, 412, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:22:27', '2025-09-03 09:22:29'),
(408, 411, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:23:29', '2025-09-03 09:23:32'),
(409, 413, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:27:53', '2025-09-03 09:27:56'),
(410, 414, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:30:42', '2025-09-03 09:30:45'),
(411, 415, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:35:40', '2025-09-03 09:35:43'),
(412, 416, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 09:41:36', '2025-09-03 09:41:38'),
(413, 417, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 09:49:15', '2025-09-03 09:49:19'),
(414, 418, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 09:54:55', '2025-09-03 09:54:58'),
(415, 419, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 10:06:07', '2025-09-03 10:06:10'),
(416, 420, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 10:11:17', '2025-09-03 10:11:24'),
(417, 421, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 10:17:52', '2025-09-03 10:17:56'),
(418, 422, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 10:32:04', '2025-09-03 10:32:08'),
(419, 423, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 10:35:07', '2025-09-03 10:35:11'),
(420, 424, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 10:37:52', '2025-09-03 10:37:57'),
(421, 425, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 10:49:33', '2025-09-03 10:49:36'),
(422, 426, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 11:00:24', '2025-09-03 11:00:27'),
(423, 427, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 11:15:16', '2025-09-03 11:15:19'),
(424, 428, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 11:28:10', '2025-09-03 11:28:13'),
(425, 429, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 11:38:43', '2025-09-03 11:38:46'),
(426, 430, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 11:45:17', '2025-09-03 11:45:21'),
(427, 431, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 11:45:36', '2025-09-03 11:45:39'),
(428, 433, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 11:50:54', '2025-09-03 11:50:57'),
(429, 432, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 11:51:46', '2025-09-03 11:51:49'),
(430, 434, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 11:56:46', '2025-09-03 11:56:53'),
(431, 435, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 11:58:20', '2025-09-03 11:58:23'),
(432, 436, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 12:05:09', '2025-09-03 12:05:12'),
(433, 437, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 12:10:45', '2025-09-03 12:10:50'),
(434, 438, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 12:19:21', '2025-09-03 12:19:24'),
(435, 439, 1, 1, '2025-09-03', '2025-09-03', 15, NULL, '2025-09-03 12:21:32', '2025-09-03 12:21:35'),
(436, 440, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 12:25:48', '2025-09-03 12:25:52'),
(437, 441, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 12:59:10', '2025-09-03 12:59:22'),
(438, 442, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 13:08:25', '2025-09-03 13:08:28'),
(439, 443, 1, 1, '2025-09-03', '2025-09-03', 16, NULL, '2025-09-03 13:16:06', '2025-09-03 13:16:31'),
(440, 444, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 05:45:23', '2025-09-05 05:45:25'),
(441, 445, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 05:47:52', '2025-09-05 05:47:55'),
(442, 446, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 05:57:05', '2025-09-05 05:57:08'),
(443, 447, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 06:06:43', '2025-09-05 06:06:47'),
(444, 448, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 06:15:29', '2025-09-05 06:15:31'),
(445, 449, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 06:20:26', '2025-09-05 06:20:28'),
(446, 450, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 06:29:01', '2025-09-05 06:29:04'),
(447, 452, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 06:38:08', '2025-09-05 06:47:18'),
(448, 453, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 06:45:00', '2025-09-05 06:45:03'),
(449, 451, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 06:46:01', '2025-09-05 06:46:04'),
(450, 454, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 07:03:21', '2025-09-05 07:03:25'),
(451, 455, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 07:29:16', '2025-09-05 07:29:19'),
(452, 456, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 07:48:39', '2025-09-05 07:48:42'),
(453, 457, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 07:50:49', '2025-09-05 07:50:53'),
(454, 458, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 07:54:48', '2025-09-05 07:54:53'),
(455, 459, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 07:58:46', '2025-09-05 07:58:50'),
(456, 460, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:14:55', '2025-09-05 08:14:58'),
(457, 461, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:22:44', '2025-09-05 08:22:54'),
(458, 462, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:26:51', '2025-09-05 08:26:53'),
(459, 463, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:32:19', '2025-09-05 08:32:22'),
(460, 464, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:34:11', '2025-09-05 08:34:14'),
(461, 465, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:40:53', '2025-09-05 08:40:56'),
(462, 466, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:45:29', '2025-09-05 08:45:32'),
(463, 467, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:51:47', '2025-09-05 08:51:52'),
(464, 468, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:53:37', '2025-09-05 08:53:40'),
(465, 469, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 08:57:06', '2025-09-05 08:57:09'),
(466, 470, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:02:13', '2025-09-05 09:02:16'),
(467, 472, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:10:02', '2025-09-05 09:10:05'),
(468, 473, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:12:36', '2025-09-05 09:12:39'),
(469, 471, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:13:43', '2025-09-05 09:13:46'),
(470, 474, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:22:36', '2025-09-05 09:22:39'),
(471, 475, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:24:32', '2025-09-05 09:24:35'),
(472, 476, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:29:32', '2025-09-05 09:29:34'),
(473, 477, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:31:21', '2025-09-05 09:31:25'),
(474, 478, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:34:55', '2025-09-05 09:34:59'),
(475, 479, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:40:17', '2025-09-05 09:40:20'),
(476, 480, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:45:50', '2025-09-05 09:45:53'),
(477, 481, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:48:43', '2025-09-05 09:48:46'),
(478, 482, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:52:16', '2025-09-05 09:52:18'),
(479, 483, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:55:06', '2025-09-05 09:55:09'),
(480, 484, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 09:56:45', '2025-09-05 09:56:51'),
(481, 485, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:01:00', '2025-09-05 10:01:03'),
(482, 486, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:04:08', '2025-09-05 10:04:16'),
(483, 487, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:06:56', '2025-09-05 10:06:59'),
(484, 488, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:10:17', '2025-09-05 10:10:19'),
(485, 489, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:12:04', '2025-09-05 10:12:10'),
(486, 490, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:20:50', '2025-09-05 10:20:53'),
(487, 491, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:23:10', '2025-09-05 10:23:13'),
(488, 492, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:27:07', '2025-09-05 10:27:10'),
(489, 493, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:29:07', '2025-09-05 10:29:12'),
(490, 494, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:33:03', '2025-09-05 10:33:06'),
(491, 495, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:36:34', '2025-09-05 10:36:37'),
(492, 496, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:49:12', '2025-09-05 10:49:16'),
(493, 497, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:52:44', '2025-09-05 10:52:50'),
(494, 498, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 10:56:20', '2025-09-05 10:56:28'),
(495, 499, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 11:05:58', '2025-09-05 11:06:02'),
(496, 500, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 11:17:06', '2025-09-05 11:17:09'),
(497, 501, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 11:46:29', '2025-09-05 11:46:32'),
(498, 502, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 11:51:42', '2025-09-05 11:51:45'),
(499, 503, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 11:56:00', '2025-09-05 11:56:03'),
(500, 504, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 11:59:08', '2025-09-05 11:59:12'),
(501, 505, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 12:02:17', '2025-09-05 12:02:20'),
(502, 506, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 12:12:28', '2025-09-05 12:12:31'),
(503, 507, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 12:31:20', '2025-09-05 12:31:24'),
(504, 508, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 12:33:58', '2025-09-05 12:34:01'),
(505, 509, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 12:38:07', '2025-09-05 12:38:10'),
(506, 510, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 12:45:04', '2025-09-05 12:45:07'),
(507, 511, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 12:53:13', '2025-09-05 12:53:15'),
(508, 512, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 13:02:08', '2025-09-05 13:02:11'),
(509, 513, 1, 1, '2025-09-05', '2025-09-05', 15, NULL, '2025-09-05 13:05:01', '2025-09-05 13:05:07'),
(510, 514, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 06:54:20', '2025-09-06 06:54:26'),
(511, 515, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:00:17', '2025-09-06 07:00:21'),
(512, 516, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:12:05', '2025-09-06 07:12:12'),
(513, 517, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:18:37', '2025-09-06 07:18:41'),
(514, 518, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:23:26', '2025-09-06 07:23:32'),
(515, 519, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:29:34', '2025-09-06 07:29:38'),
(516, 520, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:35:29', '2025-09-06 07:35:33'),
(517, 521, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:41:08', '2025-09-06 07:41:13'),
(518, 522, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:50:22', '2025-09-06 07:50:28'),
(519, 523, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 07:56:23', '2025-09-06 07:56:27'),
(520, 524, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 08:01:50', '2025-09-06 08:01:54'),
(521, 525, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 08:09:00', '2025-09-06 08:09:05'),
(522, 526, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 08:13:39', '2025-09-06 08:13:42'),
(523, 527, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 08:19:25', '2025-09-06 08:19:28'),
(524, 528, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 08:27:22', '2025-09-06 08:27:27'),
(525, 529, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 08:37:05', '2025-09-06 08:37:24'),
(526, 530, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 09:03:05', '2025-09-06 09:03:09'),
(527, 531, 1, 1, '2025-09-06', '2025-09-06', 15, NULL, '2025-09-06 09:03:16', '2025-09-06 09:03:20'),
(528, 532, 1, 1, '2025-09-06', '2025-09-06', 15, NULL, '2025-09-06 09:10:17', '2025-09-06 09:10:20'),
(529, 533, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 09:10:42', '2025-09-06 09:10:46'),
(530, 534, 1, 1, '2025-09-06', '2025-09-06', 15, NULL, '2025-09-06 09:34:12', '2025-09-06 09:34:17'),
(531, 535, 1, 1, '2025-09-06', '2025-09-06', 15, NULL, '2025-09-06 09:38:40', '2025-09-06 09:38:44'),
(532, 536, 1, 1, '2025-09-06', '2025-09-06', 15, NULL, '2025-09-06 09:43:24', '2025-09-06 09:43:28'),
(533, 537, 1, 1, '2025-09-06', '2025-09-06', 15, NULL, '2025-09-06 09:47:12', '2025-09-06 09:47:16'),
(534, 538, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 09:54:25', '2025-09-06 09:54:28'),
(535, 539, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 10:02:36', '2025-09-06 10:02:39'),
(536, 540, 1, 1, '2025-09-06', '2025-09-06', 16, NULL, '2025-09-06 10:09:20', '2025-09-06 10:09:25');
INSERT INTO `student_rame_status` (`id`, `student_id`, `school_year_id`, `has_brought_rame`, `marked_date`, `deposit_date`, `marked_by_user_id`, `notes`, `created_at`, `updated_at`) VALUES
(537, 541, 1, 1, '2025-09-06', '2025-09-06', 15, NULL, '2025-09-06 11:09:16', '2025-09-06 11:09:19'),
(538, 542, 1, 1, '2025-09-06', '2025-09-06', 15, NULL, '2025-09-06 11:13:55', '2025-09-06 11:13:58'),
(539, 543, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 04:21:21', '2025-09-09 04:21:24'),
(540, 544, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 05:40:32', '2025-09-09 05:40:42'),
(541, 545, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 05:51:17', '2025-09-09 05:51:19'),
(542, 546, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 05:55:50', '2025-09-09 05:55:53'),
(543, 547, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 06:07:59', '2025-09-09 06:08:02'),
(544, 548, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 06:11:28', '2025-09-09 06:11:31'),
(545, 549, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 06:15:56', '2025-09-09 06:15:59'),
(546, 550, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 06:17:00', '2025-09-09 06:17:04'),
(547, 551, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 06:20:39', '2025-09-09 06:20:42'),
(548, 552, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 06:27:21', '2025-09-09 06:27:29'),
(549, 553, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 06:31:49', '2025-09-09 06:31:53'),
(550, 554, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 06:32:29', '2025-09-09 06:32:36'),
(552, 556, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 06:39:00', '2025-09-09 06:39:03'),
(553, 557, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 06:43:19', '2025-09-09 06:43:24'),
(554, 558, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 06:45:24', '2025-09-09 06:45:26'),
(555, 559, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 06:50:02', '2025-09-09 06:50:05'),
(556, 560, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 06:52:58', '2025-09-09 06:53:03'),
(557, 561, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 06:54:21', '2025-09-09 06:54:24'),
(558, 562, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 06:57:30', '2025-09-09 06:57:33'),
(559, 563, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 07:02:34', '2025-09-09 07:02:37'),
(560, 564, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:05:55', '2025-09-09 07:05:58'),
(561, 565, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:09:31', '2025-09-09 07:09:43'),
(562, 566, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 07:11:02', '2025-09-09 07:11:04'),
(563, 567, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:20:32', '2025-09-09 07:20:35'),
(564, 568, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 07:23:22', '2025-09-09 07:23:25'),
(565, 569, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:26:40', '2025-09-09 07:26:43'),
(566, 570, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:35:17', '2025-09-09 07:35:22'),
(567, 571, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:40:36', '2025-09-09 07:40:46'),
(568, 572, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 07:45:16', '2025-09-09 07:45:18'),
(569, 573, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:48:12', '2025-09-09 07:48:15'),
(570, 574, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 07:49:02', '2025-09-09 07:49:04'),
(571, 575, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:53:33', '2025-09-09 07:53:37'),
(572, 576, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 07:58:19', '2025-09-09 07:58:22'),
(573, 577, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 08:06:48', '2025-09-09 08:06:51'),
(574, 578, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 08:06:56', '2025-09-09 08:07:02'),
(575, 579, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 08:10:14', '2025-09-09 08:10:21'),
(576, 580, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 08:13:18', '2025-09-09 08:13:21'),
(577, 581, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 08:20:25', '2025-09-09 08:20:27'),
(578, 582, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 08:22:46', '2025-09-09 08:22:49'),
(579, 583, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 08:25:01', '2025-09-09 08:25:04'),
(580, 584, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 08:27:22', '2025-09-09 08:27:24'),
(581, 585, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 08:38:20', '2025-09-09 08:38:22'),
(582, 586, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 08:46:33', '2025-09-09 08:46:36'),
(583, 587, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 08:56:23', '2025-09-09 08:56:26'),
(584, 588, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:11:19', '2025-09-09 09:11:24'),
(585, 589, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:26:45', '2025-09-09 09:26:47'),
(586, 590, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:35:49', '2025-09-09 09:35:52'),
(587, 591, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:37:28', '2025-09-09 09:37:32'),
(588, 592, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:39:30', '2025-09-09 09:39:41'),
(589, 593, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:44:44', '2025-09-09 09:44:49'),
(590, 594, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:55:22', '2025-09-09 09:55:24'),
(591, 595, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:57:09', '2025-09-09 09:57:12'),
(593, 597, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 09:59:14', '2025-09-09 09:59:16'),
(594, 598, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 10:03:20', '2025-09-09 10:03:23'),
(595, 599, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:03:52', '2025-09-09 10:03:55'),
(596, 600, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:07:05', '2025-09-09 10:07:08'),
(597, 601, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 10:09:36', '2025-09-09 10:09:39'),
(598, 602, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:14:22', '2025-09-09 10:14:26'),
(599, 603, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 10:16:33', '2025-09-09 10:16:35'),
(600, 604, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 10:18:40', '2025-09-09 10:18:42'),
(601, 605, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 10:20:38', '2025-09-09 10:20:41'),
(602, 606, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:26:30', '2025-09-09 10:26:34'),
(603, 607, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 10:29:28', '2025-09-09 10:29:31'),
(604, 608, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:29:45', '2025-09-09 10:29:48'),
(605, 609, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:33:53', '2025-09-09 10:33:57'),
(606, 610, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:42:53', '2025-09-09 10:42:56'),
(607, 611, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:46:51', '2025-09-09 10:46:53'),
(608, 612, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 10:51:32', '2025-09-09 10:51:36'),
(609, 613, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:00:47', '2025-09-09 11:00:51'),
(610, 614, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:05:14', '2025-09-09 11:05:18'),
(611, 615, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 11:17:49', '2025-09-09 11:17:52'),
(612, 616, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:19:23', '2025-09-09 11:19:29'),
(613, 617, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 11:23:24', '2025-09-09 11:23:26'),
(614, 618, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:24:36', '2025-09-09 11:24:46'),
(615, 619, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:28:16', '2025-09-09 11:28:19'),
(616, 620, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:34:11', '2025-09-09 11:34:15'),
(617, 621, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 11:37:41', '2025-09-09 11:37:44'),
(618, 622, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:38:46', '2025-09-09 11:38:49'),
(619, 623, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:43:11', '2025-09-09 11:43:14'),
(620, 624, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 11:48:11', '2025-09-09 11:48:27'),
(621, 625, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:00:42', '2025-09-09 12:00:45'),
(622, 626, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:06:46', '2025-09-09 12:06:50'),
(623, 627, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:12:34', '2025-09-09 12:12:43'),
(624, 628, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:16:46', '2025-09-09 12:16:53'),
(625, 629, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:24:18', '2025-09-09 12:24:25'),
(626, 630, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 12:24:41', '2025-09-09 12:24:44'),
(627, 631, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:36:14', '2025-09-09 12:36:16'),
(628, 632, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:42:58', '2025-09-09 12:43:02'),
(629, 633, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:47:11', '2025-09-09 12:47:14'),
(630, 634, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:52:56', '2025-09-09 12:53:00'),
(631, 635, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 12:59:08', '2025-09-09 12:59:10'),
(632, 636, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 13:05:57', '2025-09-09 13:06:01'),
(633, 637, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 13:11:13', '2025-09-09 13:11:17'),
(634, 638, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 13:12:16', '2025-09-09 13:12:19'),
(635, 639, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 13:21:57', '2025-09-09 13:22:00'),
(636, 640, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 13:32:08', '2025-09-09 13:32:11'),
(637, 641, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 13:32:21', '2025-09-09 13:32:32'),
(638, 642, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 13:37:25', '2025-09-09 13:37:28'),
(639, 643, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 13:42:24', '2025-09-09 13:42:27'),
(640, 644, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 13:47:57', '2025-09-09 13:48:00'),
(641, 645, 1, 1, '2025-09-09', '2025-09-09', 15, NULL, '2025-09-09 13:51:22', '2025-09-09 13:51:25'),
(642, 646, 1, 1, '2025-09-09', '2025-09-09', 16, NULL, '2025-09-09 13:52:46', '2025-09-09 13:52:49'),
(644, 648, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 05:00:40', '2025-09-10 05:00:45'),
(645, 649, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 05:10:26', '2025-09-10 05:10:37'),
(646, 650, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 05:16:31', '2025-09-10 05:16:33'),
(647, 651, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 05:20:39', '2025-09-10 05:20:41'),
(648, 652, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 05:23:48', '2025-09-10 05:23:51'),
(649, 653, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 05:33:47', '2025-09-10 05:33:50'),
(650, 654, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 05:34:17', '2025-09-10 05:34:23'),
(651, 655, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 05:38:00', '2025-09-10 05:38:09'),
(652, 656, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 05:42:01', '2025-09-10 05:42:03'),
(653, 657, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 05:42:07', '2025-09-10 07:41:59'),
(654, 658, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 06:54:47', '2025-09-10 06:54:50'),
(655, 659, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 06:57:16', '2025-09-10 06:57:20'),
(656, 660, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 06:58:23', '2025-09-10 06:58:25'),
(657, 662, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:03:28', '2025-09-10 07:03:31'),
(658, 663, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:04:59', '2025-09-10 07:05:02'),
(659, 664, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:08:45', '2025-09-10 07:08:48'),
(660, 665, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:09:04', '2025-09-10 07:09:09'),
(661, 661, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:11:09', '2025-09-10 07:11:12'),
(662, 666, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:13:41', '2025-09-10 07:13:44'),
(663, 668, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:20:01', '2025-09-10 07:30:52'),
(664, 667, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:21:28', '2025-09-10 07:21:30'),
(665, 669, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:22:41', '2025-09-10 07:22:45'),
(666, 670, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:24:22', '2025-09-10 07:24:26'),
(667, 671, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:28:07', '2025-09-10 07:28:11'),
(668, 672, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:29:42', '2025-09-10 07:29:49'),
(669, 673, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:33:18', '2025-09-10 07:33:21'),
(670, 674, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:37:43', '2025-09-10 07:37:47'),
(671, 675, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:40:43', '2025-09-10 07:40:46'),
(672, 676, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:42:11', '2025-09-10 07:42:13'),
(673, 677, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:49:13', '2025-09-10 07:49:18'),
(674, 678, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:54:15', '2025-09-10 07:54:17'),
(675, 679, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 07:54:48', '2025-09-10 07:54:52'),
(676, 680, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 07:59:43', '2025-09-10 07:59:50'),
(677, 681, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 08:01:26', '2025-09-10 08:01:45'),
(678, 682, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 08:04:13', '2025-09-10 08:04:33'),
(679, 683, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 08:06:00', '2025-09-10 08:06:04'),
(680, 684, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 08:10:01', '2025-09-10 08:10:04'),
(681, 685, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 08:12:53', '2025-09-10 08:12:56'),
(682, 686, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 08:15:32', '2025-09-10 08:15:34'),
(683, 687, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 08:16:21', '2025-09-10 08:16:25'),
(684, 688, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 08:39:47', '2025-09-10 08:39:50'),
(685, 689, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 08:42:50', '2025-09-10 08:42:52'),
(686, 690, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 08:44:50', '2025-09-10 08:44:54'),
(687, 691, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 08:53:30', '2025-09-10 08:53:34'),
(688, 692, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 08:55:41', '2025-09-10 08:55:44'),
(689, 693, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 08:57:57', '2025-09-10 08:58:01'),
(690, 694, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 08:58:31', '2025-09-10 08:58:35'),
(691, 695, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 09:00:00', '2025-09-10 09:00:02'),
(693, 698, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 09:11:35', '2025-09-10 09:11:38'),
(694, 699, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:15:36', '2025-09-10 09:15:40'),
(695, 700, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 09:16:29', '2025-09-10 09:16:32'),
(696, 701, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:18:53', '2025-09-10 09:18:57'),
(697, 702, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:22:42', '2025-09-10 09:22:44'),
(698, 703, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:27:54', '2025-09-10 09:27:57'),
(699, 704, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 09:29:53', '2025-09-10 09:29:57'),
(700, 705, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:33:37', '2025-09-10 09:33:40'),
(701, 706, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:41:24', '2025-09-10 09:41:28'),
(702, 707, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 09:43:04', '2025-09-10 09:43:06'),
(703, 708, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:46:15', '2025-09-10 09:46:18'),
(704, 709, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:51:44', '2025-09-10 09:51:47'),
(705, 710, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:53:52', '2025-09-10 09:53:55'),
(706, 711, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 09:59:35', '2025-09-10 09:59:41'),
(707, 696, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:03:03', '2025-09-10 10:03:06'),
(708, 712, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:05:37', '2025-09-10 10:05:39'),
(709, 713, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 10:05:51', '2025-09-10 10:05:57'),
(710, 714, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:08:53', '2025-09-10 10:08:56'),
(711, 715, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 10:19:19', '2025-09-10 10:19:22'),
(712, 716, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 10:22:52', '2025-09-10 10:22:55'),
(713, 718, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 10:30:37', '2025-09-10 10:30:43'),
(714, 717, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:33:58', '2025-09-10 10:34:01'),
(715, 719, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:39:11', '2025-09-10 10:39:14'),
(716, 720, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 10:42:50', '2025-09-10 10:42:54'),
(717, 721, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:46:43', '2025-09-10 10:46:46'),
(718, 722, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 10:47:52', '2025-09-10 10:47:55'),
(719, 723, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:49:07', '2025-09-10 10:49:10'),
(720, 724, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:51:15', '2025-09-10 10:51:17'),
(721, 725, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 10:52:51', '2025-09-10 10:52:54'),
(722, 726, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:53:06', '2025-09-10 10:53:08'),
(723, 727, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 10:55:17', '2025-09-10 10:55:28'),
(724, 728, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 10:57:05', '2025-09-10 10:57:08'),
(725, 729, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:00:20', '2025-09-10 11:00:22'),
(726, 730, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:02:34', '2025-09-10 11:02:38'),
(727, 731, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:03:52', '2025-09-10 11:03:57'),
(728, 732, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:06:22', '2025-09-10 11:07:05'),
(729, 733, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:08:39', '2025-09-10 11:08:42'),
(730, 734, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:08:52', '2025-09-10 11:08:55'),
(731, 735, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:11:33', '2025-09-10 11:11:36'),
(732, 736, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:14:29', '2025-09-10 11:14:32'),
(733, 737, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:16:14', '2025-09-10 11:16:20'),
(734, 738, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:21:26', '2025-09-10 11:21:32'),
(736, 740, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:26:12', '2025-09-10 11:26:16'),
(737, 741, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:27:55', '2025-09-10 11:27:58'),
(738, 742, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:33:21', '2025-09-10 11:33:24'),
(739, 743, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:39:08', '2025-09-10 11:39:11'),
(741, 745, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:48:43', '2025-09-10 11:48:46'),
(742, 746, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:50:13', '2025-09-10 11:50:17'),
(743, 747, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:52:40', '2025-09-10 11:53:31'),
(744, 748, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 11:55:05', '2025-09-10 11:55:09'),
(745, 749, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 11:57:43', '2025-09-10 11:57:55'),
(746, 750, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 12:03:01', '2025-09-10 12:03:05'),
(747, 751, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:03:23', '2025-09-10 12:03:27'),
(748, 752, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 12:06:41', '2025-09-10 12:06:44'),
(749, 753, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:09:51', '2025-09-10 12:09:57'),
(750, 754, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:15:36', '2025-09-10 12:15:40'),
(751, 755, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:20:52', '2025-09-10 12:20:55'),
(752, 756, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 12:22:46', '2025-09-10 12:22:50'),
(753, 757, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 12:24:56', '2025-09-10 12:25:00'),
(754, 758, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:29:52', '2025-09-10 12:29:55'),
(755, 759, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 12:30:04', '2025-09-10 12:30:10'),
(756, 760, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 12:32:13', '2025-09-10 12:32:16'),
(757, 761, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:33:31', '2025-09-10 12:33:34'),
(758, 762, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 12:35:11', '2025-09-10 12:35:14'),
(759, 763, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 12:37:34', '2025-09-10 12:37:38'),
(760, 764, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:39:21', '2025-09-10 12:39:24'),
(761, 765, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:45:03', '2025-09-10 12:45:07'),
(762, 766, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:49:58', '2025-09-10 12:50:03'),
(763, 767, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 12:56:42', '2025-09-10 12:56:46'),
(764, 768, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 13:01:39', '2025-09-10 13:01:42'),
(765, 769, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 13:06:32', '2025-09-10 13:06:38'),
(766, 770, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 13:15:57', '2025-09-10 13:16:00'),
(767, 771, 1, 1, '2025-09-10', '2025-09-10', 16, NULL, '2025-09-10 13:19:55', '2025-09-10 13:19:58'),
(768, 772, 1, 1, '2025-09-10', '2025-09-10', 15, NULL, '2025-09-10 13:53:15', '2025-09-10 13:53:17'),
(769, 773, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 08:40:08', '2025-09-11 08:40:13'),
(770, 774, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 08:48:44', '2025-09-11 08:48:49'),
(771, 775, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 08:54:29', '2025-09-11 08:54:34'),
(772, 776, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 09:01:19', '2025-09-11 09:01:23'),
(773, 777, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 09:10:28', '2025-09-11 09:10:32'),
(774, 778, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 09:17:43', '2025-09-11 09:17:57'),
(775, 779, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 09:21:30', '2025-09-11 09:21:34'),
(776, 780, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 09:29:08', '2025-09-11 09:29:20'),
(777, 781, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 09:32:25', '2025-09-11 09:32:30'),
(778, 782, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 09:38:30', '2025-09-11 09:38:34'),
(779, 783, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 09:41:20', '2025-09-11 09:41:25'),
(780, 784, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 10:26:40', '2025-09-11 10:27:01'),
(781, 785, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 10:31:03', '2025-09-11 10:31:05'),
(782, 786, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 10:39:23', '2025-09-11 10:39:26'),
(783, 787, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 10:39:28', '2025-09-11 10:39:33'),
(784, 788, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 10:55:54', '2025-09-11 10:55:58'),
(785, 789, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:01:04', '2025-09-11 11:01:07'),
(786, 790, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:06:30', '2025-09-11 11:06:33'),
(787, 791, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 11:11:57', '2025-09-11 11:11:59'),
(788, 792, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:20:59', '2025-09-11 11:21:03'),
(789, 793, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 11:25:52', '2025-09-11 11:25:57'),
(790, 794, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:27:59', '2025-09-11 11:28:03'),
(791, 795, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:30:43', '2025-09-11 11:30:47'),
(792, 796, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:34:07', '2025-09-11 11:34:11'),
(793, 797, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:44:31', '2025-09-11 11:44:36'),
(794, 798, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:49:55', '2025-09-11 11:49:59'),
(795, 800, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 11:53:30', '2025-09-11 11:53:36'),
(796, 801, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 11:54:57', '2025-09-11 11:55:01'),
(797, 799, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 11:58:29', '2025-09-11 11:58:32'),
(798, 802, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:01:57', '2025-09-11 12:02:00'),
(799, 803, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:05:04', '2025-09-11 12:05:08'),
(800, 805, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:16:32', '2025-09-11 12:16:36'),
(801, 806, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:22:01', '2025-09-11 12:22:05'),
(802, 807, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:27:39', '2025-09-11 12:27:45'),
(803, 808, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 12:32:22', '2025-09-11 12:32:25'),
(804, 809, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:36:38', '2025-09-11 12:36:41'),
(805, 810, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:44:43', '2025-09-11 12:44:47'),
(806, 811, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:48:20', '2025-09-11 12:48:23'),
(807, 812, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 12:55:50', '2025-09-11 12:55:53'),
(808, 813, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 13:04:01', '2025-09-11 13:04:06'),
(809, 814, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 13:10:37', '2025-09-11 13:10:42'),
(810, 815, 1, 1, '2025-09-11', '2025-09-11', 15, NULL, '2025-09-11 13:16:17', '2025-09-11 13:16:20'),
(811, 816, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 13:40:24', '2025-09-11 13:40:27'),
(812, 817, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 13:47:34', '2025-09-11 13:47:38'),
(813, 818, 1, 1, '2025-09-11', '2025-09-11', 16, NULL, '2025-09-11 14:13:13', '2025-09-11 14:13:16'),
(814, 819, 1, 0, NULL, NULL, NULL, NULL, '2025-09-12 04:22:00', '2025-09-12 04:22:00'),
(815, 820, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 05:15:24', '2025-09-12 05:15:28'),
(816, 821, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 05:22:59', '2025-09-12 05:23:03'),
(817, 822, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 05:42:56', '2025-09-12 05:43:00'),
(818, 823, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 05:50:05', '2025-09-12 05:50:10'),
(819, 824, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 05:55:56', '2025-09-12 05:56:00'),
(820, 825, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:07:30', '2025-09-12 06:07:34'),
(821, 826, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:10:51', '2025-09-12 06:10:55'),
(822, 827, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:13:39', '2025-09-12 06:13:44'),
(823, 828, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:18:04', '2025-09-12 06:18:10'),
(824, 829, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:26:21', '2025-09-12 06:26:25'),
(825, 830, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:30:50', '2025-09-12 06:30:54'),
(826, 831, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:37:31', '2025-09-12 06:37:43'),
(827, 832, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:44:05', '2025-09-12 06:44:09'),
(828, 833, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:49:52', '2025-09-12 06:49:58'),
(829, 834, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:55:59', '2025-09-12 06:56:07'),
(830, 835, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 06:58:40', '2025-09-12 06:58:45'),
(831, 836, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:04:15', '2025-09-12 07:04:21'),
(832, 837, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:06:47', '2025-09-12 07:06:57'),
(833, 838, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:12:00', '2025-09-12 07:12:03'),
(834, 839, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:19:41', '2025-09-12 07:19:44'),
(835, 840, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:29:27', '2025-09-12 07:29:32'),
(836, 841, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:35:24', '2025-09-12 07:35:29'),
(837, 842, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:47:06', '2025-09-12 07:47:09'),
(838, 843, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:53:07', '2025-09-12 07:53:25'),
(839, 844, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 07:58:33', '2025-09-12 07:58:36'),
(841, 846, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 08:01:48', '2025-09-12 08:01:51'),
(842, 847, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:01:58', '2025-09-12 08:02:01'),
(843, 848, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 08:03:30', '2025-09-12 08:03:49'),
(844, 849, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:07:18', '2025-09-12 08:07:22'),
(846, 851, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:09:22', '2025-09-12 08:09:26'),
(847, 852, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:11:09', '2025-09-12 08:11:11'),
(848, 853, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 08:12:01', '2025-09-12 08:12:06'),
(849, 854, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:13:29', '2025-09-12 08:13:32'),
(851, 856, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:29:24', '2025-09-12 08:29:28'),
(852, 857, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:31:22', '2025-09-12 08:31:24'),
(853, 858, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:32:58', '2025-09-12 08:33:01'),
(854, 859, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:42:20', '2025-09-12 08:42:24'),
(855, 860, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 08:48:06', '2025-09-12 08:48:10'),
(856, 861, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 08:52:52', '2025-09-12 08:52:58'),
(857, 862, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 08:54:51', '2025-09-12 08:54:53'),
(858, 863, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 08:55:33', '2025-09-12 08:55:36'),
(859, 864, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 09:04:56', '2025-09-12 09:05:00'),
(860, 867, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 09:26:36', '2025-09-12 09:26:40'),
(861, 866, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 09:29:28', '2025-09-12 09:29:32'),
(862, 865, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 09:32:19', '2025-09-12 09:32:40'),
(863, 868, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 10:17:55', '2025-09-12 10:18:07'),
(864, 869, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 10:21:49', '2025-09-12 10:21:53'),
(865, 870, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 10:28:49', '2025-09-12 10:28:52'),
(866, 871, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 10:39:04', '2025-09-12 10:39:07'),
(867, 872, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 10:51:59', '2025-09-12 10:52:10'),
(868, 873, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 10:55:53', '2025-09-12 10:55:56'),
(869, 874, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 11:04:47', '2025-09-12 11:04:53'),
(870, 875, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 11:10:20', '2025-09-12 11:10:23'),
(871, 876, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 11:19:49', '2025-09-12 11:19:52'),
(872, 877, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 11:23:18', '2025-09-12 11:23:25'),
(873, 878, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 11:33:30', '2025-09-12 11:33:32'),
(874, 879, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 12:11:53', '2025-09-12 12:11:59'),
(875, 880, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 12:24:58', '2025-09-12 12:25:05'),
(876, 881, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 12:29:26', '2025-09-12 12:29:30'),
(877, 882, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 12:31:45', '2025-09-12 12:31:51'),
(878, 883, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 12:39:28', '2025-09-12 12:39:31'),
(879, 884, 1, 1, '2025-09-12', '2025-09-12', 15, NULL, '2025-09-12 12:44:16', '2025-09-12 12:44:19'),
(880, 885, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 12:53:18', '2025-09-12 12:53:22'),
(881, 886, 1, 1, '2025-09-12', '2025-09-12', 16, NULL, '2025-09-12 13:20:50', '2025-09-12 13:20:53');

-- --------------------------------------------------------

--
-- Structure de la table `subjects`
--

CREATE TABLE `subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Orthographe', 'Or', NULL, 1, '2025-09-12 10:10:58', '2025-09-12 10:10:58'),
(2, 'Etude de Texte', 'ET', NULL, 1, '2025-09-12 10:11:14', '2025-09-12 10:11:14'),
(3, 'Anglais', 'En', NULL, 1, '2025-09-12 10:11:36', '2025-09-12 10:11:36'),
(4, 'Éducation de la Citoyenneté', 'ECM', NULL, 1, '2025-09-12 10:12:16', '2025-09-12 10:12:16'),
(5, 'Espagnole', 'Es', NULL, 1, '2025-09-12 10:12:39', '2025-09-12 10:12:39'),
(6, 'Expression Orale', 'EO', NULL, 1, '2025-09-12 10:13:12', '2025-09-12 10:13:12'),
(7, 'Langue et Culture Nationale', 'LCN', NULL, 1, '2025-09-12 10:13:44', '2025-09-12 10:13:44'),
(8, 'Expression Écrite', 'EE', NULL, 1, '2025-09-12 10:14:05', '2025-09-12 10:14:05'),
(9, 'Histoire', 'Hist', NULL, 1, '2025-09-12 10:16:56', '2025-09-12 10:19:08'),
(10, 'Géographie', 'Geo', NULL, 1, '2025-09-12 10:17:14', '2025-09-12 10:17:14'),
(11, 'Mathématiques', 'Math', NULL, 1, '2025-09-12 10:17:40', '2025-09-12 10:17:40'),
(12, 'Physique Chimie-Technologie', 'PCT', NULL, 1, '2025-09-12 10:18:56', '2025-09-12 10:18:56'),
(13, 'SVT', 'svt', NULL, 1, '2025-09-12 10:19:33', '2025-09-12 10:19:33'),
(14, 'Informatique', 'inf', NULL, 1, '2025-09-12 10:19:50', '2025-09-12 10:19:50'),
(15, 'Travail Manuel', 'TM', NULL, 1, '2025-09-12 10:20:10', '2025-09-12 10:20:10'),
(16, 'ESP', 'esp', NULL, 1, '2025-09-12 10:20:22', '2025-09-12 10:20:22'),
(17, 'Éducation Artistique', 'EA', NULL, 1, '2025-09-12 10:20:40', '2025-09-12 10:20:40'),
(18, 'Littérature', 'Litt', NULL, 1, '2025-09-12 10:21:00', '2025-09-12 10:21:00'),
(19, 'Langue Française', 'LF', NULL, 1, '2025-09-12 10:21:18', '2025-09-12 10:21:18'),
(20, 'Philosophie', 'Philo', NULL, 1, '2025-09-12 10:21:36', '2025-09-12 10:21:36'),
(21, 'Chimie', 'chi', NULL, 1, '2025-09-12 10:21:52', '2025-09-12 10:21:52'),
(22, 'Physique', 'ph', NULL, 1, '2025-09-12 10:22:12', '2025-09-12 10:22:12'),
(23, 'Allemand', 'Adll', NULL, 1, '2025-09-12 10:22:28', '2025-09-12 10:22:28'),
(24, 'TA\\TAS', 'TA', NULL, 1, '2025-09-12 10:29:09', '2025-09-12 10:33:23'),
(26, 'OTA', 'OTA', NULL, 1, '2025-09-12 10:29:28', '2025-09-12 10:29:28'),
(27, 'Bureautique', 'Bu', NULL, 1, '2025-09-12 10:29:47', '2025-09-12 10:29:47'),
(28, 'Courrier (redaction prof)', 'C', NULL, 1, '2025-09-12 10:30:44', '2025-09-12 10:30:44'),
(29, 'ECOM Général', 'EG', NULL, 1, '2025-09-12 10:31:10', '2025-09-12 10:31:10'),
(30, 'Economie Organisation des Entreprises', 'EOE', NULL, 1, '2025-09-12 10:31:56', '2025-09-12 10:31:56'),
(31, 'Droit', 'D', NULL, 1, '2025-09-12 10:32:31', '2025-09-12 10:32:31'),
(32, 'MOB\\PRP', 'MP', NULL, 1, '2025-09-12 10:32:57', '2025-09-12 10:32:57'),
(33, 'GSS', 'GSS', NULL, 1, '2025-09-12 10:33:45', '2025-09-12 10:33:45'),
(34, 'GRH', 'GRH', NULL, 1, '2025-09-12 10:33:55', '2025-09-12 10:33:55'),
(35, 'SPOS', 'SPOS', NULL, 1, '2025-09-12 10:34:22', '2025-09-12 10:34:22'),
(36, 'Action Sociale', 'AS', NULL, 1, '2025-09-12 10:34:40', '2025-09-12 10:34:40'),
(37, 'Labo', 'Lbo', NULL, 1, '2025-09-12 10:34:55', '2025-09-12 10:34:55'),
(38, 'Soins Infirmières', 'SI', NULL, 1, '2025-09-12 10:35:10', '2025-09-12 10:35:10'),
(39, 'BC', 'BC', NULL, 1, '2025-09-12 10:36:04', '2025-09-12 10:36:04'),
(40, 'TC', 'tc', NULL, 1, '2025-09-12 10:36:14', '2025-09-12 10:36:14'),
(41, 'Finances D’entreprises', 'FE', NULL, 1, '2025-09-12 10:37:07', '2025-09-12 10:37:07'),
(42, 'Compta de Management', 'CM', NULL, 1, '2025-09-12 10:37:31', '2025-09-12 10:37:31'),
(43, 'Compta d’Entreprise', 'CE', NULL, 1, '2025-09-12 10:38:14', '2025-09-12 10:38:14'),
(44, 'GIF', 'GIF', NULL, 1, '2025-09-12 10:38:31', '2025-09-12 10:38:31'),
(45, 'Mathématiques appliquées', 'Maths', NULL, 1, '2025-09-12 10:39:00', '2025-09-12 10:39:00'),
(46, 'CGAO', 'CGAO', NULL, 1, '2025-09-12 10:39:16', '2025-09-12 10:39:16'),
(47, 'GCAO', 'GCAO', NULL, 1, '2025-09-12 10:39:49', '2025-09-12 10:39:49'),
(48, 'GEH', 'GEH', NULL, 1, '2025-09-12 10:40:10', '2025-09-12 10:40:10'),
(49, 'Déontologie', 'Deo', NULL, 1, '2025-09-12 10:40:29', '2025-09-12 10:40:29'),
(50, 'Corresp', 'Corresp', NULL, 1, '2025-09-12 10:40:47', '2025-09-12 10:40:47'),
(51, 'Mathématiques Com', 'Math com', NULL, 1, '2025-09-12 10:41:06', '2025-09-12 10:41:06'),
(52, 'Commerce', 'cmm', NULL, 1, '2025-09-12 10:42:03', '2025-09-12 10:42:03'),
(53, 'DCC', 'Dcc', NULL, 1, '2025-09-12 10:42:15', '2025-09-12 10:42:15'),
(54, 'PRN', 'Prn', NULL, 1, '2025-09-12 10:42:51', '2025-09-12 10:42:51'),
(55, 'MOB', 'Mob1', NULL, 1, '2025-09-12 10:43:14', '2025-09-12 10:43:14'),
(56, 'LEGIS', 'legis', NULL, 1, '2025-09-12 10:43:30', '2025-09-12 10:43:30'),
(57, 'Terminologies', 'Ter', NULL, 1, '2025-09-12 10:43:49', '2025-09-12 10:43:49'),
(58, 'PGD', 'Pgd', NULL, 1, '2025-09-12 10:44:00', '2025-09-12 10:44:00'),
(59, 'Nutrition', 'Nu', NULL, 1, '2025-09-12 10:44:11', '2025-09-12 10:44:11'),
(60, 'Technique Culinaire', 'TC1', NULL, 1, '2025-09-12 10:44:32', '2025-09-12 10:44:32'),
(61, 'PGO', 'pgo', NULL, 1, '2025-09-12 10:45:03', '2025-09-12 10:45:03'),
(62, 'EAO', 'EAO', NULL, 1, '2025-09-12 10:45:34', '2025-09-12 10:45:34'),
(63, 'GO', 'go', NULL, 1, '2025-09-12 10:46:11', '2025-09-12 10:46:11'),
(64, 'Hygiène', 'hy', NULL, 1, '2025-09-12 10:46:29', '2025-09-12 10:46:29'),
(65, 'SEL/AHNV', 'SA', NULL, 1, '2025-09-12 10:46:50', '2025-09-12 10:46:50'),
(66, 'Biologies', 'B', NULL, 1, '2025-09-12 10:47:04', '2025-09-12 10:47:04'),
(67, 'Vie Sociale', 'VS', NULL, 1, '2025-09-12 10:47:43', '2025-09-12 10:47:43'),
(68, 'Marketing', 'Mark', NULL, 1, '2025-09-12 10:48:04', '2025-09-12 10:48:04');

-- --------------------------------------------------------

--
-- Structure de la table `supervisor_class_assignments`
--

CREATE TABLE `supervisor_class_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supervisor_id` bigint(20) UNSIGNED NOT NULL,
  `school_class_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('critical','high','normal','low') NOT NULL DEFAULT 'normal',
  `status` enum('pending','in_progress','completed','cancelled','overdue') NOT NULL DEFAULT 'pending',
  `category` enum('administrative','pedagogical','maintenance','event','urgent','other') NOT NULL DEFAULT 'other',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `assigned_to` bigint(20) UNSIGNED NOT NULL,
  `assigned_by` bigint(20) UNSIGNED NOT NULL,
  `due_date` date DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `progress` int(11) NOT NULL DEFAULT 0,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_type` enum('daily','weekly','monthly','yearly') DEFAULT NULL,
  `recurrence_interval` int(11) DEFAULT NULL,
  `recurrence_end_date` date DEFAULT NULL,
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `last_reminder_sent` datetime DEFAULT NULL,
  `reminder_count` int(11) NOT NULL DEFAULT 0,
  `points` int(11) NOT NULL DEFAULT 10,
  `difficulty_level` int(11) NOT NULL DEFAULT 1,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `notes` text DEFAULT NULL,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checklist`)),
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task_assignees`
--

CREATE TABLE `task_assignees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `progress` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task_dependencies`
--

CREATE TABLE `task_dependencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `depends_on_task_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('finish_to_start','start_to_start','finish_to_finish') NOT NULL DEFAULT 'finish_to_start',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task_histories`
--

CREATE TABLE `task_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task_templates`
--

CREATE TABLE `task_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('administrative','pedagogical','maintenance','event','urgent','other') NOT NULL,
  `priority` enum('critical','high','normal','low') NOT NULL DEFAULT 'normal',
  `estimated_duration` int(11) DEFAULT NULL,
  `default_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_checklist`)),
  `points` int(11) NOT NULL DEFAULT 10,
  `difficulty_level` int(11) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL COMMENT 'Identifiant enseignant (ex: TCH_001, STAF_027)',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('m','f') DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `type_personnel` enum('V','SP','P') NOT NULL DEFAULT 'V' COMMENT 'Type de personnel: V=Vacataire, SP=Semi-Permanent, P=Permanent',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `expected_arrival_time` time NOT NULL DEFAULT '08:00:00',
  `expected_departure_time` time NOT NULL DEFAULT '17:00:00',
  `daily_work_hours` decimal(4,2) NOT NULL DEFAULT 8.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_id`, `first_name`, `last_name`, `phone_number`, `email`, `address`, `date_of_birth`, `gender`, `qualification`, `hire_date`, `is_active`, `type_personnel`, `user_id`, `department_id`, `qr_code`, `expected_arrival_time`, `expected_departure_time`, `daily_work_hours`, `created_at`, `updated_at`) VALUES
(2, NULL, 'DJAM', 'MARCEL', '694547521', NULL, 'BAFOUSSAM', NULL, 'm', NULL, '2025-08-27', 1, 'V', 25, NULL, 'STAFF_25', '08:00:00', '17:00:00', 8.00, '2025-08-27 14:04:49', '2025-08-27 14:32:14'),
(4, NULL, 'MASSOCK', 'MASSOCK', '6657890', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'SP', 27, NULL, 'TCH_4', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:45:34', '2025-09-10 07:23:44'),
(5, NULL, 'LEONNEL STEPHANE', 'NGAKATH', '696559488', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'SP', 28, NULL, 'TCH_5', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:47:37', '2025-09-10 07:24:17'),
(6, NULL, 'MATHIEU', 'TCHAMENI', '650516446', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'SP', 29, NULL, 'TCH_6', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:51:00', '2025-09-10 07:25:06'),
(7, NULL, 'TOBIE', 'LISSOTA YOMZAK', '694593469', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 30, NULL, 'TCH_7', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:53:00', '2025-08-28 08:16:39'),
(8, NULL, 'PHILIP', 'NKONGHO TAMBE', '675155315', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 0, 'V', 31, NULL, 'STAFF_31', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:57:19', '2025-08-30 07:31:30'),
(9, NULL, 'MARGUERITE', 'PAMOWA MARIE', '674134850', NULL, NULL, NULL, 'f', NULL, '2025-08-28', 1, 'V', 33, NULL, 'TCH_9', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:15:24', '2025-08-28 08:16:39'),
(10, NULL, 'JAMES', 'NGANYA TILONG', '698461021', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 34, NULL, 'TCH_10', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:16:56', '2025-08-28 08:16:39'),
(11, NULL, 'JAMES', 'NGANYA TILONG', '698461021', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 36, NULL, 'TCH_11', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:31:22', '2025-08-28 08:16:39'),
(12, NULL, 'EMANE', 'TAMUNA VERA', '652646952', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 37, NULL, 'TCH_12', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:36:41', '2025-08-28 08:16:39'),
(13, NULL, 'NESTOR', 'KAMTCHOU', '696289883', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 38, NULL, 'TCH_13', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:37:48', '2025-08-28 08:16:39'),
(14, NULL, 'ANDRE', 'MBOCK NOL', '693298209', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 39, NULL, 'TCH_14', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:38:45', '2025-08-28 08:16:39'),
(15, NULL, 'THIERRY', 'TIODA', '681039987', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 40, NULL, 'TCH_15', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:42:33', '2025-08-28 08:16:39'),
(16, NULL, 'BOUBAKARY', 'BRAHIMA', '690701677', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 41, NULL, 'TCH_16', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:43:39', '2025-08-28 08:16:39'),
(17, NULL, 'OSCAR', 'MAMPASSI', '697469756', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 42, NULL, 'TCH_17', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:45:16', '2025-08-28 08:16:39'),
(18, NULL, 'JOEL', 'TCHEBEI TCHOUNKE', '678307239', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 43, NULL, 'TCH_18', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:46:46', '2025-08-28 12:06:50'),
(19, NULL, 'ALEXIS', 'KOUAZE NANA', '699091048', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 44, NULL, 'TCH_19', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:48:00', '2025-08-28 08:16:39'),
(20, NULL, 'VIVIEN', 'NONO GILLES', '696725515', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 45, NULL, 'TCH_20', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:48:49', '2025-08-28 08:16:39'),
(21, NULL, 'ROGER CEDRIC', 'NGANKOUE MANGA', '696474808', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 46, NULL, 'TCH_21', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:49:43', '2025-08-28 08:16:39'),
(22, NULL, 'HERVE', 'YOSSA', '691675326', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 47, NULL, 'TCH_22', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:50:31', '2025-08-28 08:16:39'),
(23, NULL, 'FLORENT', 'EPOH DAVID', '699174337', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 48, NULL, 'TCH_23', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:51:23', '2025-08-28 08:16:39'),
(24, NULL, 'ROMARIC', 'NGAPMEU TCHABONG', '679769812', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 49, NULL, 'TCH_24', '08:00:00', '17:00:00', 8.00, '2025-08-28 08:13:31', '2025-08-28 08:16:39'),
(25, NULL, 'JOSEPH SARA', 'BILONGO’O BILONGO’O', '693740710', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 52, NULL, 'TCH_25', '08:00:00', '17:00:00', 8.00, '2025-08-28 08:14:35', '2025-08-28 08:16:39'),
(26, NULL, 'GEORGETTE', 'NDONDOCK NICAISE', '674385786', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 53, NULL, 'TCH_26', '08:00:00', '17:00:00', 8.00, '2025-08-28 08:16:20', '2025-08-28 08:16:39'),
(27, NULL, 'JACQUELINE', 'PIEFLEYOU', '655689082', NULL, NULL, NULL, 'f', NULL, '2025-08-28', 1, 'P', 87, NULL, 'STAFF_56', '08:00:00', '17:00:00', 8.00, '2025-08-28 10:37:11', '2025-09-10 03:53:58'),
(28, NULL, 'CHRISTIAN', 'KUITCHOU', '696118001', 'christian.kuitchou@cpb.cm', 'Douala', '1980-03-15', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_28', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(29, NULL, 'MOÏSE', 'OWONO MVENG', '696118002', 'moise.owono@cpb.cm', 'Yaoundé', '1982-07-20', 'm', 'Master Physique', '2024-12-12', 1, 'V', 99, NULL, 'TCH_29', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:33:19'),
(30, NULL, 'MILIXANDRE DELFLORE', 'PETKEU', '696118003', 'milixandre.petkeu@cpb.cm', 'Douala', '1979-11-10', 'm', 'Licence Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_30', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(31, NULL, 'DONATIEN', 'NDZANA', '696118004', 'donatien.ndzana@cpb.cm', 'Bafoussam', '1981-09-25', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', 93, NULL, 'TCH_31', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:10:53'),
(32, NULL, 'SUZANNE', 'NGO SAMNICK', '696118005', 'suzanne.samnick@cpb.cm', 'Douala', '1983-05-12', 'f', 'Master Français', '2024-12-12', 1, 'V', 92, NULL, 'TCH_32', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:05:46'),
(33, NULL, 'SANTANA', 'MOUKORY', '696118006', 'santana.moukory@cpb.cm', 'Yaoundé', '1980-12-08', 'f', 'Licence Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_33', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(34, NULL, 'GERAUDE', 'BATOUANEN MOBAN', '696427010', 'geraude.batouanen@cpb.cm', 'Douala', '1978-01-30', 'm', 'Licence Géographie', '2024-12-12', 1, 'V', 94, NULL, 'TCH_34', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:11:01'),
(35, NULL, 'NGNINZEKO', 'BOGNI', '696961822', 'ngninzeko.bogni@cpb.cm', 'Bamenda', '1984-06-18', 'm', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_35', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(36, NULL, 'EMERENTIEN', 'LY-INBE', '698352081', 'emerentien.lyinbe@cpb.cm', 'Douala', '1981-04-22', 'm', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_36', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(37, NULL, 'DESIRE', 'HOUNSOU', '679549423', 'desire.hounsou@cpb.cm', 'Yaoundé', '1982-10-14', 'm', 'Master Économie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_37', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(38, NULL, 'JEAN', 'BEKOMBO POUNGOUE', '672939521', 'jean.bekombo@cpb.cm', 'Douala', '1979-08-05', 'm', 'Licence Droit', '2024-12-12', 1, 'V', 110, NULL, 'TCH_38', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-11 12:50:42'),
(39, NULL, 'PLACIDE', 'PLACIDE', '681879734', 'placide.placide@cpb.cm', 'Yaoundé', '1983-02-16', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_39', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(40, NULL, 'ULRICH LANDRY', 'NJIKI', '697957200', 'ulrich.njiki@cpb.cm', 'Bafoussam', '1980-07-28', 'm', 'Licence Informatique', '2024-12-12', 1, 'P', 105, NULL, 'TCH_40', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-11 08:39:04'),
(41, NULL, 'CYRILLE', 'TATSINKOU TENE', '690151661', 'cyrille.tatsinkou@cpb.cm', 'Douala', '1981-09-11', 'm', 'Master Chimie', '2024-12-12', 1, 'V', 103, NULL, 'TCH_41', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:37:59'),
(42, NULL, 'JUDITH FLORE', 'MEKUATE', '694088658', 'judith.mekuate@cpb.cm', 'Yaoundé', '1982-12-03', 'f', 'Licence Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_42', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(43, NULL, 'ELISE', 'NGANSI WONSSI', '697458185', 'elise.ngansi@cpb.cm', 'Douala', '1984-05-19', 'f', 'Master Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_43', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(44, NULL, 'BERTRAND', 'TONFACK', '670403323', 'bertrand.tonfack@cpb.cm', 'Bafoussam', '1979-11-07', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', 107, NULL, 'TCH_44', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:46:54'),
(45, NULL, 'NESTOR', 'KAMENI', '674831332', 'nestor.kameni@cpb.cm', 'Douala', '1983-03-23', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', 102, NULL, 'TCH_45', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:37:37'),
(46, NULL, 'TALLA', 'AURELIEN', '658047838', 'talla.aurelien@cpb.cm', 'Yaoundé', '1980-08-15', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_46', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(47, NULL, 'MARCEL', 'WOULINA', '674667016', 'marcel.woulina@cpb.cm', 'Douala', '1982-06-02', 'm', 'Master Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_47', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(48, NULL, 'NADEGE', 'WOUASSI', '673697712', 'nadege.wouassi@cpb.cm', 'Yaoundé', '1981-10-26', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_48', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(49, NULL, 'DEBORAH', 'NGO NSEGBE', '670609624', 'deborah.ngo@cpb.cm', 'Douala', '1983-04-18', 'f', 'BTS Secrétariat', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_49', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(50, NULL, 'SOLANGE', 'BI', '674536333', 'solange.bi@cpb.cm', 'Bafoussam', '1980-07-09', 'f', 'Licence Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_50', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(51, NULL, 'DJOMATCHOUA', 'DJOMATCHOUA', '678963262', 'djomatchoua.djomatchoua@cpb.cm', 'Douala', '1982-12-21', 'f', 'Master Économie', '2024-12-12', 1, 'V', 100, NULL, 'TCH_51', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:35:10'),
(52, NULL, 'SANDRINE NATHALIE', 'NOUBISSIE', '696976171', 'sandrine.noubissie@cpb.cm', 'Yaoundé', '1984-01-13', 'f', 'Licence Droit', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_52', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(53, NULL, 'SA', 'BEITI A MOUBIE', '674007378', 'sa.beiti@cpb.cm', 'Douala', '1981-09-05', 'f', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_53', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(54, NULL, 'PATIENCE', 'NZOUYA', '675120578', 'patience.nzouya@cpb.cm', 'Yaoundé', '1979-11-27', 'f', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_54', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(55, NULL, 'UGUETTE PHILOMENE', 'MADADJEU', '697345879', 'uguette.madadjeu@cpb.cm', 'Bafoussam', '1983-02-14', 'f', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_55', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(56, NULL, 'K', 'NOUTAMOUN', '696118029', 'k.noutamoun@cpb.cm', 'Douala', '1980-08-06', 'f', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_56', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(57, NULL, 'LUCIENNE FLORE', 'LUCIENNE FLORE', '696118030', 'lucienne.flore@cpb.cm', 'Yaoundé', '1982-05-18', 'f', 'BTS Secrétariat', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_57', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(58, NULL, 'IDA CLAUDINE', 'MAKOUPO TALLA', '696118031', 'ida.makoupo@cpb.cm', 'Douala', '1981-10-30', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_58', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(59, NULL, 'JOSEPHINE', 'TCHIEDJIO', '674611961', 'josephine.tchiedjio@cpb.cm', 'Bafoussam', '1984-03-12', 'f', 'Master Anglais', '2024-12-12', 1, 'V', 111, NULL, 'TCH_59', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-12 04:34:45'),
(60, NULL, 'TCHUIGANG', 'MBAKOP', '656287367', 'tchuigang.mbakop@cpb.cm', 'Douala', '1979-07-24', 'f', 'Licence Histoire', '2024-12-12', 1, 'V', 101, NULL, 'TCH_60', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:36:08'),
(61, NULL, 'PULCHERIE', 'DJEUKOUA', '675382461', 'pulcherie.djeukoua@cpb.cm', 'Yaoundé', '1982-12-16', 'f', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_61', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(62, NULL, 'ANDRIENNE', 'GUEKAM', '699184325', 'andrienne.guekam@cpb.cm', 'Douala', '1983-04-08', 'f', 'Licence Géographie', '2024-12-12', 1, 'V', 106, NULL, 'TCH_62', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:42:54'),
(63, NULL, 'LEOCARDIE', 'TAGNE', '673427073', 'leocardie.tagne@cpb.cm', 'Bafoussam', '1980-09-20', 'f', 'Master Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_63', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(64, NULL, 'MERLINE', 'FOMEKONG KENNE', '691250098', 'merline.fomekong@cpb.cm', 'Douala', '1984-01-02', 'm', 'Licence Physique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_64', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(65, NULL, 'YACINTHE', 'NYANGONO', '655099808', 'yacinthe.nyangono@cpb.cm', 'Yaoundé', '1981-06-14', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_65', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(66, NULL, 'THALES', 'TCHEUSONG', '655428206', 'thales.tcheusong@cpb.cm', 'Douala', '1979-11-26', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_66', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(67, NULL, 'JEAN MARIE', 'NJINE DEHELALE', '693249266', 'jean.njine@cpb.cm', 'Bafoussam', '1983-02-18', 'm', 'Master Biologie', '2024-12-12', 1, 'V', 89, NULL, 'TCH_67', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 04:02:32'),
(68, NULL, 'RAÏSSA DANIE', 'MEBOT', '694087843', 'raissa.mebot@cpb.cm', 'Douala', '1982-08-10', 'f', 'Licence Français', '2024-12-12', 1, 'V', 108, NULL, 'TCH_68', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-10 05:03:13'),
(69, NULL, 'STEPHANE EVINDI', 'FRANCK', '677999266', 'stephane.franck@cpb.cm', 'Yaoundé', '1980-05-22', 'm', 'BTS Informatique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_69', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(70, NULL, 'BENOIT GERARD', 'BALOMLEKE', '695164220', 'benoit.balomleke@cpb.cm', 'Douala', '1981-12-04', 'm', 'Licence Droit', '2024-12-12', 1, 'V', 112, NULL, 'TCH_70', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-09-12 04:36:45'),
(71, NULL, 'PIUS COLLINS', 'KOWA', '699734094', 'pius.kowa@cpb.cm', 'Bafoussam', '1984-07-16', 'm', 'Master Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_71', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(72, NULL, 'DUMONT', 'NAWESSI', '650466778', 'dumont.nawessi@cpb.cm', 'Douala', '1979-03-28', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_72', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(73, NULL, 'CHRISTIAN', 'KUIZING', '699824521', 'christian.kuizing@cpb.cm', 'Yaoundé', '1982-10-10', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_73', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(74, NULL, 'AMOUR', 'BOUM GWETH', '650824521', 'amour.boum@cpb.cm', 'Douala', '1983-01-22', 'm', 'Licence Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_74', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(75, NULL, 'NGOGUE', 'NGNOGUE', '699893310', 'ngogue.ngnogue@cpb.cm', 'Bafoussam', '1980-09-14', 'm', 'Master Économie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_75', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(76, NULL, 'FREDERIC', 'FREDERIC', '691015957', 'frederic.frederic@cpb.cm', 'Douala', '1984-04-06', 'm', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_76', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(77, NULL, 'PAUL', 'NJEM IV', '696014985', 'paul.njem@cpb.cm', 'Yaoundé', '1981-11-18', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_77', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(78, NULL, 'ELYSEE', 'TASSO', '653675880', 'elysee.tasso@cpb.cm', 'Douala', '1979-06-30', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_78', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(79, NULL, 'JUDITH', 'DZOKOU KENGNE', '694859867', 'judith.dzokou@cpb.cm', 'Bafoussam', '1982-12-12', 'f', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_79', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(80, NULL, 'GABRIEL', 'TCHEKWANDEU', '676373457', 'gabriel.tchekwandeu@cpb.cm', 'Douala', '1983-08-24', 'm', 'Licence Chimie', '2024-12-12', 1, 'V', 85, NULL, 'TCH_80', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 03:52:51'),
(81, NULL, 'GENEVIEVE', 'ONGMETANA', '690151600', 'genevieve.ongmetana@cpb.cm', 'Yaoundé', '1980-02-16', 'f', 'BTS Secrétariat', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_81', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(82, NULL, 'MARCELINE', 'GUEMDJO', '693310561', 'marceline.guemdjo@cpb.cm', 'Douala', '1984-07-08', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_82', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(83, NULL, 'ELEONORE', 'MOMO', '652553099', 'eleonore.momo@cpb.cm', 'Bafoussam', '1981-03-20', 'f', 'Master Anglais', '2024-12-12', 1, 'SP', 97, NULL, 'TCH_83', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 04:20:35'),
(84, NULL, 'ODELE', 'NGO NYOBE', '698950519', 'odele.ngo@cpb.cm', 'Douala', '1982-10-02', 'f', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_84', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(85, NULL, 'GISELE', 'DJUELA FOKOU', '652 55 30 99', 'gisele.djuela@cpb.cm', 'Yaoundé', '1979-05-14', 'f', 'BTS Comptabilité', '2024-12-12', 1, 'SP', 113, NULL, 'TCH_85', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-12 04:41:13'),
(86, NULL, 'LIONIE', 'LOKIO TCHANANG', '695475535', 'lionie.lokio@cpb.cm', 'Douala', '1983-11-26', 'f', 'Licence Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_86', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(87, NULL, 'TOUFFEU', 'KAMBEU', '697320739', 'touffeu.kambeu@cpb.cm', 'Bafoussam', '1984-01-18', 'f', 'Master Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_87', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(88, NULL, 'MIREILLE', 'MIREILLE', '695148001', 'mireille.mireille@cpb.cm', 'Douala', '1980-09-10', 'f', 'Licence Physique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_88', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(89, NULL, 'ERNEST', 'TCHOUDJIN', '670248900', 'ernest.tchoudjin@cpb.cm', 'Yaoundé', '1982-04-22', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_89', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(90, NULL, 'JULES ARSENE', 'NDONI', '681613033', 'jules.ndoni@cpb.cm', 'Douala', '1981-12-04', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_90', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(91, NULL, 'ARIANE', 'SIMO TAKONGUE', '697 32 07 39', 'ariane.simo@cpb.cm', 'Bafoussam', '1983-07-16', 'f', 'Master Biologie', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_91', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:16:03'),
(92, NULL, 'RAISSA', 'RAISSA', '652148494', 'raissa.raissa@cpb.cm', 'Douala', '1980-02-28', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_92', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(93, NULL, 'HUBERT', 'YOUSSA', '677191795', 'hubert.youssa@cpb.cm', 'Yaoundé', '1984-08-10', 'm', 'BTS Informatique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_93', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(94, NULL, 'JEAN JACQUES', 'NGALAKO NJEUNGA', '654377605', 'jean.ngalako@cpb.cm', 'Douala', '1979-05-22', 'm', 'Licence Droit', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_94', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:26:04'),
(95, NULL, 'CONSTANT', 'FOGANG NGOUFO', '681613033', 'constant.fogang@cpb.cm', 'Bafoussam', '1982-11-04', 'm', 'Master Anglais', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_95', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:26:47'),
(96, NULL, 'JOSEPH KINDONG', 'YHAM', '675339919', 'joseph.yham@cpb.cm', 'Douala', '1981-03-16', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_96', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(97, NULL, 'FOMAGNOUA', 'DC FOTSOP', '652 14 84 94', 'fomagnoua.fotsop@cpb.cm', 'Yaoundé', '1983-09-28', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'SP', 96, NULL, 'TCH_97', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 04:11:29'),
(98, NULL, 'JOSEPHINE B', 'JOHNIE', '677212371', 'josephine.johnie@cpb.cm', 'Douala', '1984-01-10', 'f', 'Licence Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_98', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(99, NULL, 'GUSTAVE NOSO', 'PEKWEKEH', '654 37 76 05', 'gustave.pekwekeh@cpb.cm', 'Bafoussam', '1980-06-22', 'm', 'Master Économie', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_99', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:28:32'),
(100, NULL, 'DADY JOEL', 'NKOUAMO', '671711951', 'dady.nkouamo@cpb.cm', 'Douala', '1982-12-14', 'm', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_100', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(101, NULL, 'GERADINE', 'NDASSI', '672719607', 'geradine.ndassi@cpb.cm', 'Yaoundé', '1981-04-06', 'f', 'BTS Secrétariat', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_101', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(102, NULL, 'WAKUNA', 'WAKUNA', '652216968', 'wakuna.wakuna@cpb.cm', 'Douala', '1979-10-18', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_102', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(103, NULL, 'ERIC', 'KUMGAHA TANGNI', '674769687', 'eric.kumgaha@cpb.cm', 'Bafoussam', '1983-07-30', 'm', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_103', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(104, NULL, 'MIRABEL', 'MBULLE', '674378487', 'mirabel.mbulle@cpb.cm', 'Douala', '1984-02-12', 'f', 'Licence Chimie', '2024-12-12', 1, 'V', 98, NULL, 'TCH_104', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 04:24:57'),
(105, NULL, 'JUNIOR', 'EGBENCHUNG BISONG', '671818252', 'junior.egbenchung@cpb.cm', 'Yaoundé', '1980-08-24', 'm', 'BTS Électronique', '2024-12-12', 1, 'SP', 88, NULL, 'TCH_105', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 04:01:10'),
(106, NULL, 'PHILIP', 'NKONGHO TAMBE', '671711951', 'philip.nkongho@cpb.cm', 'Douala', '1982-05-16', 'm', 'Licence Physique', '2024-12-12', 1, 'SP', 86, NULL, 'TCH_106', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 03:53:39'),
(107, NULL, 'GILEAN', 'ANAM', '654193306', 'gilean.anam@cpb.cm', 'Bafoussam', '1981-11-08', 'm', 'Master Anglais', '2024-12-12', 1, 'V', 109, NULL, 'TCH_107', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 23:39:13'),
(108, NULL, 'ASHU', 'TEZE', '680093485', 'ashu.teze@cpb.cm', 'Douala', '1983-03-20', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_108', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(109, NULL, 'NDJOH', 'FRANCK DARIUS', '672126000', 'ndjoh.franck@cpb.cm', 'Yaoundé', '1984-09-02', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_109', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(110, NULL, 'NDJOH', 'NDJOH', '651074407', 'ndjoh.ndjoh@cpb.cm', 'Douala', '1979-01-14', 'm', 'Licence Droit', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_110', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(111, NULL, 'ANGELA DIOH', 'NJINYERU', '675366578', 'angela.njinyeru@cpb.cm', 'Bafoussam', '1982-06-26', 'f', 'Master Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_111', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(112, NULL, 'GILTON LAISIN', 'TANTOH', '651067920', 'gilton.tantoh@cpb.cm', 'Douala', '1980-12-18', 'm', 'Licence Géographie', '2024-12-12', 1, 'SP', 104, NULL, 'TCH_112', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 04:39:12'),
(113, NULL, 'PRINCE WILL', 'LEKEAKA', '678457764', 'prince.lekeaka@cpb.cm', 'Yaoundé', '1983-04-10', 'm', 'BTS Informatique', '2024-12-12', 1, 'V', 91, NULL, 'TCH_113', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 04:05:07'),
(114, NULL, 'MIRABEL WEI', 'MBAIN', '696118087', 'mirabel.mbain@cpb.cm', 'Douala', '1981-10-22', 'f', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_114', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(115, NULL, 'THEOPHELEN', 'ATEMKENG', '680093485', 'theophelen.atemkeng@cpb.cm', 'Bafoussam', '1984-07-14', 'm', 'Master Économie', '2024-12-12', 1, 'SP', 95, NULL, 'TCH_115', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-09-10 04:11:08'),
(116, NULL, 'COLLIN KOLOA', 'MOTO', '696118089', 'collin.moto@cpb.cm', 'Douala', '1979-02-06', 'm', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_116', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(117, NULL, 'DEM', 'KOUZO NGRUNTE', '696118090', 'dem.kouzo@cpb.cm', 'Yaoundé', '1982-09-18', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_117', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(118, NULL, 'JACKON', 'KADJO', '696118091', 'jackon.kadjo@cpb.cm', 'Douala', '1983-05-30', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_118', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(119, NULL, 'BARTHOLOMEW', 'TUMBU', '696118092', 'bartholomew.tumbu@cpb.cm', 'Bafoussam', '1980-11-12', 'm', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_119', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(120, NULL, 'SIDONIE', 'FUH', '696118093', 'sidonie.fuh@cpb.cm', 'Douala', '1984-03-24', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_120', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(121, NULL, 'ELVIS METOUKE', 'MESUMBE', '696118094', 'elvis.mesumbe@cpb.cm', 'Yaoundé', '1981-08-16', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_121', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(122, NULL, 'PETSAM', 'LIENOU SERENA', '680305638', NULL, NULL, NULL, NULL, NULL, '2025-09-09', 1, 'V', 83, NULL, NULL, '08:00:00', '17:00:00', 8.00, '2025-09-09 21:43:31', '2025-09-09 21:43:31'),
(123, NULL, 'CLADORE', 'NKWASSI', '653025600', NULL, NULL, NULL, NULL, NULL, '2025-09-09', 1, 'V', 84, NULL, NULL, '08:00:00', '17:00:00', 8.00, '2025-09-09 21:44:47', '2025-09-09 21:44:47');

-- --------------------------------------------------------

--
-- Structure de la table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `series_subject_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_attendances`
--

CREATE TABLE `teacher_attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `supervisor_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `scanned_at` timestamp NOT NULL,
  `is_present` tinyint(1) NOT NULL DEFAULT 1,
  `event_type` enum('entry','exit') NOT NULL DEFAULT 'entry',
  `work_hours` decimal(4,2) DEFAULT NULL,
  `late_minutes` int(11) NOT NULL DEFAULT 0,
  `early_departure_minutes` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `class_series_id` bigint(20) UNSIGNED NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `trimesters`
--

CREATE TABLE `trimesters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `number` int(11) NOT NULL,
  `school_year_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL COMMENT 'Code QR pour scanner la présence',
  `staff_identifier` varchar(50) DEFAULT NULL COMMENT 'Identifiant personnel (ex: STAF_27, TCH_15)',
  `role` varchar(50) NOT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `working_school_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `contact`, `photo`, `qr_code`, `staff_identifier`, `role`, `qualification`, `is_active`, `working_school_year_id`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrateur', 'Mr Foyet', 'admin@gmail.com', '+237696118389', NULL, 'STAFF_1', 'STAF_1', 'admin', NULL, 1, NULL, NULL, '$2y$12$zYdXO4K0BvRme0PPrsvUo.ZR.WCGcMeko/iv75PnRJEwQh0hN0FCq', NULL, '2025-08-03 18:00:23', '2025-09-11 09:33:56'),
(3, 'Nnanga madeleine', 'comptable', 'comptable@gsbpl.com', '+237657256007', NULL, 'STAFF_3', 'STAF_3', 'accountant', NULL, 1, NULL, NULL, '$2y$12$i1YAhwTPms2T4ZFLZ8JDvuCMo6Hp.lTHYQjvoixWF5RXVpM4n//0y', NULL, '2025-08-03 18:00:23', '2025-09-11 08:35:57'),
(4, 'Utilisateur Test', 'user.test', 'user@gsbpl.com', NULL, NULL, 'STAFF_4', 'STAF_4', 'admin', NULL, 1, NULL, NULL, '$2y$12$VJr5Z1nbNIkVlRGb2qTm1OP6L2ndargZm6aChFSpn3i6isifWLr5e', NULL, '2025-08-03 18:00:23', '2025-09-12 01:26:51'),
(12, 'MEFOBEU', 'mefobeu', 'mefobeu@gmail.com', '+237690866410', 'https://admin1.cpb-douala.com/storage/user_photos/6d5e365d-b2e1-414a-8f2c-63dbf46fe3c0.jpeg', 'STAFF_12', 'STAF_12', 'accountant', NULL, 1, NULL, '2025-08-04 09:35:30', '$2y$12$s5Ylka6qib5ppn2LwgsjAO3tCiN56n8vSBgl3lY1LWX8z/yx0YbaC', NULL, '2025-08-04 09:35:30', '2025-09-11 08:35:57'),
(15, 'Kouoh Ashley', 'Ashley', 'Ashley@gmail.com', '+237655240303', NULL, 'STAFF_15', 'STAF_15', 'secretaire', NULL, 1, NULL, '2025-08-19 11:37:34', '$2y$12$WfrvU/BJoiavDWHhB0GPS.Ria6mcmNuHQIedCGkeKnWS.9ivwJtFW', NULL, '2025-08-19 11:37:34', '2025-09-11 08:35:06'),
(16, 'ELONG ANGE', 'ange', 'ange@gmail.com', '699111062', NULL, 'STAFF_16', 'STAF_16', 'secretaire', NULL, 1, NULL, '2025-08-19 11:54:23', '$2y$12$HJpnkk62glKvg3qZnn4EIe2V3CRUssYcmJFz2URfCosNpVYkbIA6i', NULL, '2025-08-19 11:54:23', '2025-09-11 08:35:15'),
(17, 'soffack kelly', 'kelly', 'kelly@gmail.com', '‪+237651818278‬', NULL, 'STAFF_17', 'STAF_17', 'comptable_superieur', NULL, 1, NULL, '2025-08-20 09:39:16', '$2y$12$EShqinB9OOHAQCnA3Rzjw.7KwidFNOvhg57ofcT2SL/0e6qRNdGuO', NULL, '2025-08-20 09:39:16', '2025-09-11 08:35:57'),
(18, 'DJAM MARCEL', 'djam', 'djam@gmail.com', '694547521', NULL, 'STAFF_18', 'STAF_18', 'accountant', NULL, 1, NULL, '2025-08-27 11:03:41', '$2y$12$7vxntH44yJAD/FOdeGufauxy73hHeNCuNslaD2MZZu5O.nXbZdc9S', NULL, '2025-08-27 11:03:41', '2025-09-11 08:35:57'),
(19, 'PIEFLEYOU JACQUELINE', 'jacqueline', 'jacqueline@gmail.com', '655 68 90 82', NULL, 'STAFF_19', 'STAF_19', 'bibliothecaire', NULL, 1, NULL, '2025-08-27 11:04:19', '$2y$12$C/21anePpLL6HCZal7LdV.mAyLxX1foW.W7qo.LjkXGCf1dtYQqQW', NULL, '2025-08-27 11:04:19', '2025-09-11 08:35:57'),
(20, 'MASSOCK', 'massock', 'massock@gmail.com', NULL, NULL, 'STAFF_20', 'STAF_20', 'accountant', NULL, 1, NULL, '2025-08-27 11:04:51', '$2y$12$3vB8cHGPQqZT1nlJ9LnBnOo76XbIdMCrdi.CSHBxDNhUomG0cfwAC', NULL, '2025-08-27 11:04:51', '2025-09-11 08:35:57'),
(21, 'NGAKATH LEONNEL STEPHANE', 'leonel', 'leonel@gmail.com', '696 55 94 88', NULL, 'STAFF_21', 'STAF_21', 'accountant', NULL, 1, NULL, '2025-08-27 11:05:26', '$2y$12$vRj08/fDgX1Ccii3R2XW2.SwN305ISIdEbN74Lfu1YUOeNjNJ5QD6', NULL, '2025-08-27 11:05:26', '2025-09-11 08:35:57'),
(22, 'TCHAMENI MATHIEU', 'mathieu', 'mathieu@gmail.com', '650 51 64 46', NULL, 'STAFF_22', 'STAF_22', 'accountant', NULL, 1, NULL, '2025-08-27 11:06:13', '$2y$12$FUC5xrM9yPTcVsWcUUINpOEXbHma/1CJPkz1I8AslrhIfGFKy95AK', NULL, '2025-08-27 11:06:13', '2025-09-11 08:35:57'),
(23, 'LISSOTA YOMZAK TOBIE', 'tobie', 'tobie@gmail.com', '694 59 34 69', NULL, 'STAFF_23', 'STAF_23', 'accountant', NULL, 1, NULL, '2025-08-27 11:09:13', '$2y$12$EE.6B99hZa1LrHlXqw23jOVvmSP7gVkpYWEijKztjGzW7UHcRr7lu', NULL, '2025-08-27 11:09:13', '2025-09-11 08:35:57'),
(25, 'DJAM MARCEL', 'ZUDJIE', 'ZUDJIE@school.local', NULL, NULL, 'STAFF_25', 'TCH_25', 'teacher', NULL, 1, NULL, NULL, '$2y$12$vu6MMwlPAo0DkJLRoYaVGeNnBaTXrhsZ1yKPFGlhyluo28DW4kSyW', NULL, '2025-08-27 14:04:49', '2025-09-11 08:35:57'),
(27, 'MASSOCK MASSOCK', 'EZ', 'EZ@school.local', NULL, NULL, 'STAFF_27', 'TCH_27', 'teacher', NULL, 1, NULL, NULL, '$2y$12$mtdcFByYLwEU6dio4EvWs.Iv.8L0RDZWYvnbvWfoBxIu30VllUrra', NULL, '2025-08-28 06:45:34', '2025-09-11 08:35:57'),
(28, 'LEONNEL STEPHANE NGAKATH', 'AZERTY', 'AZERTY@school.local', NULL, NULL, 'STAFF_28', 'TCH_28', 'teacher', NULL, 1, NULL, NULL, '$2y$12$S0mx5d8AHADRK4VI7y06k.nK5jbLFxLMi8ku7IkptI/QXn7H8e4H6', NULL, '2025-08-28 06:47:37', '2025-09-11 08:35:57'),
(29, 'MATHIEU TCHAMENI', 'AZRV7', 'AZRV7@school.local', NULL, NULL, 'STAFF_29', 'TCH_29', 'teacher', NULL, 1, NULL, NULL, '$2y$12$Rrr4Qf2aMLQjZItLz2OkXOTkQjOMjd6PSDXBH52V4Y48jLQPaLoku', NULL, '2025-08-28 06:51:00', '2025-09-11 08:35:57'),
(30, 'TOBIE LISSOTA YOMZAK', 'AZDS', 'AZDS@school.local', NULL, NULL, 'STAFF_30', 'TCH_30', 'teacher', NULL, 1, NULL, NULL, '$2y$12$p7136HVbGEtgHjX6ZL1UpuyuEC3x37pUOXuJVAodZnEnp6sp9tf0O', NULL, '2025-08-28 06:53:00', '2025-09-11 08:35:57'),
(31, 'PHILIP NKONGHO TAMBE', '675 15 53 15', '675 15 53 15@school.local', NULL, NULL, 'STAFF_31', 'TCH_31', 'teacher', NULL, 1, NULL, NULL, '$2y$12$GCaAXdu5Djjp67nHR.zsEuquv3jDMYvb6wUG6LLDgW.JjfmwI6msy', NULL, '2025-08-28 06:57:19', '2025-09-11 08:35:57'),
(33, 'MARGUERITE PAMOWA MARIE', '674 13 48 50', '674 13 48 50@school.local', NULL, NULL, 'STAFF_33', 'TCH_33', 'teacher', NULL, 1, NULL, NULL, '$2y$12$LgYdJscSZMaw2OyP5.NmB.nQRjbN5X.jnuBveDNiLzht4cBjXi5gG', NULL, '2025-08-28 07:15:24', '2025-09-11 08:35:57'),
(34, 'JAMES NGANYA TILONG', '698 46 10 21', '698 46 10 21@school.local', NULL, NULL, 'STAFF_34', 'TCH_34', 'teacher', NULL, 1, NULL, NULL, '$2y$12$GpItYzg/B.kz6TMvW.uPtuzZ0rlM/XhowtxV7h5AqQ.6kOFyLPWje', NULL, '2025-08-28 07:16:56', '2025-09-11 08:35:57'),
(36, 'JAMES NGANYA TILONG', '698461021', '698461021@school.local', NULL, NULL, 'STAFF_36', 'TCH_36', 'teacher', NULL, 1, NULL, NULL, '$2y$12$q8ZK95UKPHM7yLbEQ6/Et.pP9ySg9SG0YWxFUH.2E20RSLxDxmz9q', NULL, '2025-08-28 07:31:22', '2025-09-11 08:35:57'),
(37, 'EMANE TAMUNA VERA', '652646952', '652646952@school.local', NULL, NULL, 'STAFF_37', 'TCH_37', 'teacher', NULL, 1, NULL, NULL, '$2y$12$4s/TQ5sie2C.Xwp.JoZ6HOY1HmOVa25K2mBHB9uMIggc0CPUz0ICm', NULL, '2025-08-28 07:36:41', '2025-09-11 08:35:57'),
(38, 'NESTOR KAMTCHOU', '696289883', '696289883@school.local', NULL, NULL, 'STAFF_38', 'TCH_38', 'teacher', NULL, 1, NULL, NULL, '$2y$12$.jQTxn577P5rPXCMwhx58u.ZaVglALLMYo1SHYjNOBe7Pz05o/vAW', NULL, '2025-08-28 07:37:48', '2025-09-11 08:35:57'),
(39, 'ANDRE MBOCK NOL', '693298209', '693298209@school.local', NULL, NULL, 'STAFF_39', 'TCH_39', 'teacher', NULL, 1, NULL, NULL, '$2y$12$Af1wvIVXzbTlfTwPZkIFJu/FM4zj/YjN7gWNgRd2eOvLN1cXKxgO2', NULL, '2025-08-28 07:38:45', '2025-09-11 08:35:57'),
(40, 'THIERRY TIODA', '681039987', '681039987@school.local', NULL, NULL, 'STAFF_40', 'TCH_40', 'teacher', NULL, 1, NULL, NULL, '$2y$12$n.vdwmVzCH0HRUVVbK7hFO7QkJpOwau9le6hqmE.fIJA1S1aFwghW', NULL, '2025-08-28 07:42:33', '2025-09-11 08:35:57'),
(41, 'BOUBAKARY BRAHIMA', '690701677', '690701677@school.local', NULL, NULL, 'STAFF_41', 'TCH_41', 'teacher', NULL, 1, NULL, NULL, '$2y$12$FS2jLAFDrRQLe7oS//l/POHnDSJ3bFlXgjpcUoiX2ZVj8wKXqotA2', NULL, '2025-08-28 07:43:39', '2025-09-11 08:35:57'),
(42, 'OSCAR MAMPASSI', '697469756', '697469756@school.local', NULL, NULL, 'STAFF_42', 'TCH_42', 'teacher', NULL, 1, NULL, NULL, '$2y$12$5DpMuCFwnDKajNdlScyQwOWlJy8Ga9LQaJ0KZbeX2xAeOiC1FVB66', NULL, '2025-08-28 07:45:16', '2025-09-11 08:35:57'),
(43, 'JOEL TCHEBEI TCHOUNKE', '678307239', '678307239@school.local', NULL, NULL, 'STAFF_43', 'TCH_43', 'teacher', NULL, 1, NULL, NULL, '$2y$12$ZMxEwAQBX0/kgxXPxGRKEegoYVl6ozg0Et0FnBd.dq84Qyxl2uCoa', NULL, '2025-08-28 07:46:46', '2025-09-11 08:35:57'),
(44, 'ALEXIS KOUAZE NANA', '699091048', '699091048@school.local', NULL, NULL, 'STAFF_44', 'TCH_44', 'teacher', NULL, 1, NULL, NULL, '$2y$12$z7Kkoft7EskTqfRnKSijiemyd1Spdjxi4ObsLEyPsLQC0IfCFdjVq', NULL, '2025-08-28 07:48:00', '2025-09-11 08:35:57'),
(45, 'VIVIEN NONO GILLES', '696725515', '696725515@school.local', NULL, NULL, 'STAFF_45', 'TCH_45', 'teacher', NULL, 1, NULL, NULL, '$2y$12$xJY5lZhUusZCLvBxEhXUjO0xhenZSfNM7nxflInswdoLMFf3N0TDS', NULL, '2025-08-28 07:48:49', '2025-09-11 08:35:57'),
(46, 'ROGER CEDRIC NGANKOUE MANGA', '696474808', '696474808@school.local', NULL, NULL, 'STAFF_46', 'TCH_46', 'teacher', NULL, 1, NULL, NULL, '$2y$12$qIFAQBwwUjEebtImLJMIE.Dedp9039Uli/Kzo3kJGGwXfWdvTbQi6', NULL, '2025-08-28 07:49:43', '2025-09-11 08:35:57'),
(47, 'HERVE YOSSA', '691675326', '691675326@school.local', NULL, NULL, 'STAFF_47', 'TCH_47', 'teacher', NULL, 1, NULL, NULL, '$2y$12$GrPGcGZI1gL5iIhr2l87w.PLltJiZ8hV8JkuRaT8jYBae79bt501u', NULL, '2025-08-28 07:50:31', '2025-09-11 08:35:57'),
(48, 'FLORENT EPOH DAVID', '699174337', '699174337@school.local', NULL, NULL, 'STAFF_48', 'TCH_48', 'teacher', NULL, 1, NULL, NULL, '$2y$12$d.a5fUkCIy6SrPwFS6au5u4WzqMEGygZjbHAIsYtVH2ya7LbYr8kW', NULL, '2025-08-28 07:51:23', '2025-09-11 08:35:57'),
(49, 'ROMARIC NGAPMEU TCHABONG', '679769812', '679769812@school.local', NULL, NULL, 'STAFF_49', 'TCH_49', 'teacher', NULL, 1, NULL, NULL, '$2y$12$PP9JrLxZVinQ0qFpf/H0xOOF8YgTHGNNQJOZAD7qkKpK90O2MauIG', NULL, '2025-08-28 08:13:31', '2025-09-11 08:35:57'),
(52, 'JOSEPH SARA BILONGO’O BILONGO’O', '693740710', '693740710@school.local', NULL, NULL, 'STAFF_52', 'TCH_52', 'teacher', NULL, 1, NULL, NULL, '$2y$12$c.YMqlxd/aw7y019czI.UOXzPzc/11.ogO/M8Il/ssY.WY8rs8igy', NULL, '2025-08-28 08:14:35', '2025-09-11 08:35:57'),
(53, 'GEORGETTE NDONDOCK NICAISE', '674385786', '674385786@school.local', NULL, NULL, 'STAFF_53', 'TCH_53', 'teacher', NULL, 1, NULL, NULL, '$2y$12$Al4QScBVmVYu9KfGZIXpveQCQIUr4SrECbwuXZ845SPKwTS.4.SmG', NULL, '2025-08-28 08:16:20', '2025-09-11 08:35:57'),
(57, 'M. TCHEKWANDEU Gabriel', 'Gabriel', 'Gabriel@cpb-douala.cm', '696 01 49 85', NULL, 'STAFF_57', 'STAF_57', 'responsable_pedagogique', 'Maitrise', 1, NULL, '2025-08-28 23:35:57', '$2y$12$ex7l86o.9X52L1i3kMHSGOGzfcpo96tWfyISvxPnFrSupuc3GeDLK', NULL, '2025-08-28 23:35:57', '2025-09-11 08:35:57'),
(58, 'M. ESUA Michael', 'Michael', 'Michael@cpb-douala.com', '682 81 20 61', NULL, 'STAFF_58', 'STAF_58', 'dean_of_studies', 'GCE A+1', 1, NULL, '2025-08-28 23:37:08', '$2y$12$wESAWwEWCmseJoQR8/CvWe04o7G4ZhkuNUt4rRP/AhNhK9oWLRwLm', NULL, '2025-08-28 23:37:08', '2025-09-11 08:35:57'),
(59, 'M. NJIEYA Georges', 'Georges', 'Georges@cpb-douala.com', '677 96 13 95', NULL, 'STAFF_59', 'STAF_59', 'censeur_esg', 'TMSI (bac +2)', 1, NULL, '2025-08-28 23:38:11', '$2y$12$.FmBdlpGvt3IoD.3DJxzquqcp95.o9licXtOQdK5hcGZpeK/9/j2G', NULL, '2025-08-28 23:38:11', '2025-09-11 08:35:57'),
(60, 'Mme NGO SAMNICK Suzanne', 'Suzanne', 'Suzanne@cpb-douala.com', '683 26 30 02', NULL, 'STAFF_60', 'STAF_60', 'censeur', 'BAC + 2', 1, NULL, '2025-08-28 23:39:11', '$2y$12$mnrNU3XKXrtkWy4I0Us1sue/svsXEG4k4ywMcUDA5qSUSbj/Sr5Ly', NULL, '2025-08-28 23:39:11', '2025-09-11 08:35:57'),
(61, 'M. HEUYO Patrice', 'Patrice', 'Patrice@cpb-douala.com', '699 17 89 15', NULL, 'STAFF_61', 'STAF_61', 'surveillant_general', 'DUT', 1, NULL, '2025-08-28 23:40:16', '$2y$12$6/bsxATGqgOTIaQ5rVHuvu0..kllXVsosmx7r.7QznCJnMGFXM/la', NULL, '2025-08-28 23:40:16', '2025-09-11 08:35:57'),
(62, 'M. MBAH Dickson', 'DICKSON', 'DICKSON@cpb-douala.com', '+237682238564', NULL, 'STAFF_62', 'STAF_62', 'surveillant_secteur', 'GCE A +1', 1, NULL, '2025-08-28 23:41:33', '$2y$12$DYstBPgYaxzSWuUVccoc2e4W7x2CJr3z1CpFx/pKckhkMTjB9OfL2', NULL, '2025-08-28 23:41:33', '2025-09-11 09:27:54'),
(63, 'M. YAGAÏ TIZI', 'TIZI', 'TIZI@cpb-douala.com', '697 83 87 17', NULL, 'STAFF_63', 'STAF_63', 'surveillant_secteur', 'PROBATOIRE', 1, NULL, '2025-08-28 23:42:26', '$2y$12$hRVAfQ5Is3Dp9FeEVTxfkuFGPzeS8zK6tbBFCfkLarEFOWpC2OfLu', NULL, '2025-08-28 23:42:26', '2025-09-11 08:35:57'),
(64, 'M. TAGNE LONGANG Aymar', 'Aymar', 'Aymar@cpb-douala.com', '655 54 87 18', NULL, 'STAFF_64', 'STAF_64', 'surveillant_secteur', 'PROBATOIRE', 1, NULL, '2025-08-28 23:43:16', '$2y$12$87ihb7P998foTZbq75lEXOwPLkkvk1qUebhThqrH7zXdyVNthw2Qi', NULL, '2025-08-28 23:43:16', '2025-09-11 08:35:57'),
(65, 'M. OUANDJI NGANTCHA Idriss', 'IDRISS', 'IDRISS@cpb-douala.com', '672 39 29 49', NULL, 'STAFF_65', 'STAF_65', 'surveillant_secteur', 'PROBATOIRE', 1, NULL, '2025-08-28 23:44:21', '$2y$12$qtbOdtsBbA/mMyROEJXgg.Bq3bo32jMYRK5G3SijfGXnakjyH42RG', NULL, '2025-08-28 23:44:21', '2025-09-11 08:35:57'),
(66, 'M. NNOHO A RIM', 'RIM', 'RIM@cpb-douala.com', '681 55 47 57', NULL, 'STAFF_66', 'STAF_66', 'surveillant_secteur', 'BAC F3', 1, NULL, '2025-08-28 23:45:33', '$2y$12$5AsqgYlW6nP1L0JR7sejPewB0nqDpFQRwBW7KTY1mGU.TcS6EZYry', NULL, '2025-08-28 23:45:33', '2025-09-11 08:35:57'),
(67, 'Mlle EWOUAWA PAULINE', 'PAULINE', 'PAULINE@cpb-douala.com', '678 83 20 64', NULL, 'STAFF_67', 'STAF_67', 'secretaire', 'PROBATOIRE', 1, NULL, '2025-08-28 23:46:43', '$2y$12$JePpg1gGfmEEPCsCQnmnvexAbgZlCDBD8duiIVz/H9iFhRtgNunvC', NULL, '2025-08-28 23:46:43', '2025-09-11 08:35:57'),
(73, 'Mme TCHAMBA Désirée', 'Desiree', 'Desiree@cpb-doualaa.com', '675 99 85 39', NULL, 'STAFF_73', 'STAF_73', 'chef_travaux', 'BP', 1, NULL, '2025-08-28 23:51:24', '$2y$12$FEqk8G07/dm4stD58bwRPuMkpHG6UVnUeFNWseqLwYsiUNuRJoXFS', NULL, '2025-08-28 23:51:24', '2025-09-11 08:35:57'),
(74, 'M LIBONG MATH KEVIN', 'KEVIN', 'KEVIN@cpb-doualaa.com', '655 72 97 42', NULL, 'STAFF_74', 'STAF_74', 'surveillant_secteur', 'BAC', 1, NULL, '2025-08-28 23:52:10', '$2y$12$ZACmPpMR3yA33Q6JmJr5jeP6BwDw5OD9dK6bQj.lzyA./SoANsTnq', NULL, '2025-08-28 23:52:10', '2025-09-11 08:35:57'),
(75, 'M. MEDJEUGOUE  KWAMO LOIC', 'LOIC', 'LOIC@cpb-doualaa.com', '695 83 15 04', NULL, 'STAFF_75', 'STAF_75', 'surveillant_secteur', 'BTS', 1, NULL, '2025-08-28 23:53:22', '$2y$12$OAnzqS2azAL0AsVnH5lQH.3eMKRi/BbnzAZjpGOdpB8afQQl0csRC', NULL, '2025-08-28 23:53:22', '2025-09-11 08:35:57'),
(76, 'M.DAIROU', 'DAIROU', 'DAIROU@cpb-doualaa.com', '674 75 04 47', NULL, 'STAFF_76', 'STAF_76', 'chef_securite', NULL, 1, NULL, '2025-08-28 23:54:07', '$2y$12$deQXokh4xqDtZSGLS8.LROaeUmIImeMU/LmNujEHt7Q89QVKxEl9a', NULL, '2025-08-28 23:54:07', '2025-09-11 08:35:57'),
(77, 'M. DALIX CHRISTIAN', 'CHRISTIAN', 'CHRISTIAN@cpb-doualaa.com', '690 17 19 30', NULL, 'STAFF_77', 'STAF_77', 'reprographe', NULL, 1, NULL, '2025-08-28 23:55:25', '$2y$12$fAOyT8TeU1q.RxJ67vdU5eGHzBWMS4iBm2RjvkOWcWq0vY1rHTwRa', NULL, '2025-08-28 23:55:25', '2025-09-11 08:35:57'),
(81, 'M.NGUEYON Hubert Degrando', 'Degrando', 'Degrando@cpb-douala.com', '699 75 89 02', NULL, 'STAFF_81', 'STAF_81', 'principal', 'Maitrise', 1, NULL, '2025-08-29 00:24:57', '$2y$12$9p0TvtiPZzTPrP6DvlpD/.BNOy5j3ozcnl7H3XubGCbIhFjNhbSk.', NULL, '2025-08-29 00:24:57', '2025-09-11 08:35:57'),
(82, 'chris kamgang', 'chriskamgang', 'chriskamgang@gmail.com', '659339778', NULL, 'STAFF_82', 'STAF_82', 'secretaire', NULL, 1, NULL, '2025-09-02 07:32:29', '$2y$12$2toUgOAYbn1o3iGXvMx3b...CQfBV.P2m5XJd8X4tUwcbNK38dHM6', NULL, '2025-09-02 07:32:29', '2025-09-11 08:35:57'),
(83, 'PETSAM LIENOU SERENA', 'LIENOU', 'LIENOU@school.local', '680305638', NULL, NULL, 'TCH_83', 'teacher', NULL, 1, NULL, NULL, '$2y$12$HjKFkYdxNDb4lC.czupNROJHrwR/R9ZFnV0JD9JdN2Bx9.mj2.d8G', NULL, '2025-09-09 21:43:31', '2025-09-11 08:35:57'),
(84, 'CLADORE NKWASSI', 'CLADORE', 'CLADORE@school.local', '653025600', NULL, NULL, 'TCH_84', 'teacher', NULL, 1, NULL, NULL, '$2y$12$7/4.ckSj5MbwkxzzDF8.BeVoYQAO3h/Joy4gtpNpq43oCUas6tIjy', NULL, '2025-09-09 21:44:47', '2025-09-11 08:35:57'),
(85, 'GABRIEL TCHEKWANDEU', 'gabriel_80', 'gabriel.tchekwandeu@cpb.cm', '676373457', NULL, 'TCH_80', 'TCH_85', 'teacher', NULL, 1, NULL, NULL, '$2y$12$TwzQbAs9Dh0bg9piBviuNuuYs/33mrf752Qoz8g5VviVaztzFqPlK', NULL, '2025-09-10 03:52:51', '2025-09-11 08:35:57'),
(86, 'PHILIP NKONGHO TAMBE', 'philip_106', 'philip.nkongho@cpb.cm', '671711951', NULL, 'TCH_106', 'TCH_86', 'teacher', NULL, 1, NULL, NULL, '$2y$12$GqWSGQyaN/LgNEh38k9gE.YsjDU0qDO2VJQDCJmMCYzHkQoDpefqO', NULL, '2025-09-10 03:53:39', '2025-09-11 08:35:57'),
(87, 'JACQUELINE PIEFLEYOU', 'jacqueline_27', 'jacqueline27@school.local', '655689082', NULL, 'STAFF_56', 'TCH_87', 'teacher', NULL, 1, NULL, NULL, '$2y$12$tiwHDIkDWf6CqENjDpxvyuV.GZ34xpdJ7QiKREXpKNOK1rvzPr3VG', NULL, '2025-09-10 03:53:58', '2025-09-11 08:35:57'),
(88, 'JUNIOR EGBENCHUNG BISONG', 'junior_105', 'junior.egbenchung@cpb.cm', '671818252', NULL, 'TCH_105', 'TCH_88', 'teacher', NULL, 1, NULL, NULL, '$2y$12$yprzuKQrPgNpV8gQsh9dH.A3VjrzFnXWdhbBHYIuusDTj3nGLg73y', NULL, '2025-09-10 04:01:10', '2025-09-11 08:35:57'),
(89, 'JEAN MARIE NJINE DEHELALE', 'jean marie_67', 'jean.njine@cpb.cm', '693249266', NULL, 'TCH_67', 'TCH_89', 'teacher', NULL, 1, NULL, NULL, '$2y$12$vYB.eUtycisY3HdTvhW8auhVsbt8dgv9QWErxyASqf6/rG8LEWl9a', NULL, '2025-09-10 04:02:32', '2025-09-11 08:35:57'),
(90, 'Administrateur', 'admin', 'admin@cpb-douala.com', NULL, NULL, NULL, 'STAF_90', 'admin', NULL, 1, NULL, NULL, '$2y$12$bRKYhBtgsB.Qbo3l8aLea.7cr.EhYucvofWJJcHYYDXYSSmdQWfjO', NULL, '2025-09-10 04:03:22', '2025-09-11 08:31:44'),
(91, 'PRINCE WILL LEKEAKA', 'prince will_113', 'prince.lekeaka@cpb.cm', '678457764', NULL, 'TCH_113', 'TCH_91', 'teacher', NULL, 1, NULL, NULL, '$2y$12$3gIlquajk5gnu.GRMF.em.GgZyMiFdpFCLf3v0J5n23IKbVsc8kyS', NULL, '2025-09-10 04:05:06', '2025-09-11 08:35:57'),
(92, 'SUZANNE NGO SAMNICK', 'suzanne_32', 'suzanne.samnick@cpb.cm', '696118005', NULL, 'TCH_32', 'TCH_92', 'teacher', NULL, 1, NULL, NULL, '$2y$12$n1Yaur0YpECr5PZk3cZq0ewTG7C13YaoJhI1orXrif1q9AbUbeYBG', NULL, '2025-09-10 04:05:46', '2025-09-11 08:35:57'),
(93, 'DONATIEN NDZANA', 'donatien_31', 'donatien.ndzana@cpb.cm', '696118004', NULL, 'TCH_31', 'TCH_93', 'teacher', NULL, 1, NULL, NULL, '$2y$12$xcQBpLxIJ74P19PdaVmv5.YncDC9AAl75SVJE6LlaoAN7tU98tmNa', NULL, '2025-09-10 04:10:53', '2025-09-11 08:35:57'),
(94, 'GERAUDE BATOUANEN MOBAN', 'geraude_34', 'geraude.batouanen@cpb.cm', '696427010', NULL, 'TCH_34', 'TCH_94', 'teacher', NULL, 1, NULL, NULL, '$2y$12$LEhrD5j8wTJQOAtJXInPP.8Vp1dog.SPrGWaIgWZhEvVi3otkhS3u', NULL, '2025-09-10 04:11:01', '2025-09-11 08:35:57'),
(95, 'THEOPHELEN ATEMKENG', 'theophelen_115', 'theophelen.atemkeng@cpb.cm', '680093485', NULL, 'TCH_115', 'TCH_95', 'teacher', NULL, 1, NULL, NULL, '$2y$12$YkO17yFOB7ZxGJ.waPnWI.WmN4uOPTlZAdlLTZw4WSnYxpMs3IRVa', NULL, '2025-09-10 04:11:08', '2025-09-11 08:35:57'),
(96, 'FOMAGNOUA DC FOTSOP', 'fomagnoua_97', 'fomagnoua.fotsop@cpb.cm', '652 14 84 94', NULL, 'TCH_97', 'TCH_96', 'teacher', NULL, 1, NULL, NULL, '$2y$12$xaxE1Nq6gscvJQhYE3dZI.Gp5MjEPa/ZiDOYG0xfQmrJcSI9HPOJS', NULL, '2025-09-10 04:11:29', '2025-09-11 08:35:57'),
(97, 'ELEONORE MOMO', 'eleonore_83', 'eleonore.momo@cpb.cm', '652553099', NULL, 'TCH_83', 'TCH_97', 'teacher', NULL, 1, NULL, NULL, '$2y$12$BZBdMYdkJKXqWWDWZaZt2ufD/xu32wsqerjUpCokN37/FjJZjVjV6', NULL, '2025-09-10 04:20:35', '2025-09-11 08:35:57'),
(98, 'MIRABEL MBULLE', 'mirabel_104', 'mirabel.mbulle@cpb.cm', '674378487', NULL, 'TCH_104', 'TCH_98', 'teacher', NULL, 1, NULL, NULL, '$2y$12$TVesMmjIGw5tyqYgr/DDDO0abOScV9297gaj8Q2qT88LDcU/T9Qxa', NULL, '2025-09-10 04:24:57', '2025-09-11 08:35:57'),
(99, 'MOÏSE OWONO MVENG', 'moÏse_29', 'moise.owono@cpb.cm', '696118002', NULL, 'TCH_29', 'TCH_99', 'teacher', NULL, 1, NULL, NULL, '$2y$12$BKwU96z69kIQbfJDkVxGtOHGcRBKEPrEX3fc/vKsy.GwWGGgBvBg.', NULL, '2025-09-10 04:33:19', '2025-09-11 08:35:57'),
(100, 'DJOMATCHOUA DJOMATCHOUA', 'djomatchoua_51', 'djomatchoua.djomatchoua@cpb.cm', '678963262', NULL, 'TCH_51', 'TCH_100', 'teacher', NULL, 1, NULL, NULL, '$2y$12$7qjq1k7/NyLVK5/Kg/f5d.zS2Bg7Eunaxhk70WLR.iO5.wYLaoEma', NULL, '2025-09-10 04:35:10', '2025-09-11 08:35:57'),
(101, 'TCHUIGANG MBAKOP', 'tchuigang_60', 'tchuigang.mbakop@cpb.cm', '656287367', NULL, 'TCH_60', 'TCH_101', 'teacher', NULL, 1, NULL, NULL, '$2y$12$HVnVIUiNTqVajyI/.6dU5eVUC89VSXHdmGbZ8OXjU66fP0O/TQOkm', NULL, '2025-09-10 04:36:08', '2025-09-11 08:35:57'),
(102, 'NESTOR KAMENI', 'nestor_45', 'nestor.kameni@cpb.cm', '674831332', NULL, 'TCH_45', 'TCH_102', 'teacher', NULL, 1, NULL, NULL, '$2y$12$3Ko1X4dhfh7RJbbWhWKo3embvjxhysBU1eSjHRMKgzwuyYtqcI4B.', NULL, '2025-09-10 04:37:37', '2025-09-11 08:35:57'),
(103, 'CYRILLE TATSINKOU TENE', 'cyrille_41', 'cyrille.tatsinkou@cpb.cm', '690151661', NULL, 'TCH_41', 'TCH_103', 'teacher', NULL, 1, NULL, NULL, '$2y$12$zy18Xjno9es41T2gGajS..E290Z1DMNJ0vbiLJAxoP9SEa703Qzz.', NULL, '2025-09-10 04:37:59', '2025-09-11 08:35:57'),
(104, 'GILTON LAISIN TANTOH', 'gilton laisin_112', 'gilton.tantoh@cpb.cm', '651067920', NULL, 'TCH_112', 'TCH_104', 'teacher', NULL, 1, NULL, NULL, '$2y$12$D22Hnhq73f.QR/frUs9TcO3.H8ikegcv6KnZFVv.Gjt0Vw4ysb5EG', NULL, '2025-09-10 04:39:12', '2025-09-11 08:35:57'),
(105, 'ULRICH LANDRY NJIKI', 'ulrich landry_40', 'ulrich.njiki@cpb.cm', '697957200', NULL, 'TCH_40', 'TCH_105', 'teacher', NULL, 1, NULL, NULL, '$2y$12$JLTkMwU5O5.vyTnoixGAOeNadLLAPTR9.jC4InUEvT2UUFQuEq89S', NULL, '2025-09-10 04:42:29', '2025-09-11 08:35:57'),
(106, 'ANDRIENNE GUEKAM', 'andrienne_62', 'andrienne.guekam@cpb.cm', '699184325', NULL, 'TCH_62', 'TCH_106', 'teacher', NULL, 1, NULL, NULL, '$2y$12$CCfFQroQqW2Yq2fKpeyXIuWYD5FGf3Erb9Bd1v5S4oa4SKUA2bZwq', NULL, '2025-09-10 04:42:54', '2025-09-11 08:35:57'),
(107, 'BERTRAND TONFACK', 'bertrand_44', 'bertrand.tonfack@cpb.cm', '670403323', NULL, 'TCH_44', 'TCH_107', 'teacher', NULL, 1, NULL, NULL, '$2y$12$OGgAiw.CbmaVN/wf//IrKeD7afRsGOvAAX65hR7277RnoN5kFK0mq', NULL, '2025-09-10 04:46:54', '2025-09-11 08:35:57'),
(108, 'RAÏSSA DANIE MEBOT', 'raÏssa danie_68', 'raissa.mebot@cpb.cm', '694087843', NULL, 'TCH_68', 'TCH_108', 'teacher', NULL, 1, NULL, NULL, '$2y$12$uQMG7B6AaXuFMWf9R6.okuSJgxrvBCsRu6clMIA7KQZzSvLJcZYlC', NULL, '2025-09-10 05:03:13', '2025-09-11 08:35:57'),
(109, 'GILEAN ANAM', 'gilean_107', 'gilean.anam@cpb.cm', '654193306', NULL, 'TCH_107', 'TCH_109', 'teacher', NULL, 1, NULL, NULL, '$2y$12$9abEpmvD1.2MCrH/EFOVxO09nbwv4pm6Mc3zBlx.yFlOYET4vNCzS', NULL, '2025-09-10 23:39:13', '2025-09-11 08:35:57'),
(110, 'JEAN BEKOMBO POUNGOUE', 'jean_38', 'jean.bekombo@cpb.cm', '672939521', NULL, 'TCH_38', 'TCH_110', 'teacher', NULL, 1, NULL, NULL, '$2y$12$CfAMwl4dFInPKe4GAV64q.m1ETYOjv9dAX4//QnrW8F1Yl8Y2jBoO', NULL, '2025-09-11 12:50:42', '2025-09-12 01:26:51'),
(111, 'JOSEPHINE TCHIEDJIO', 'josephine_59', 'josephine.tchiedjio@cpb.cm', '674611961', NULL, 'TCH_59', 'TCH_111', 'teacher', NULL, 1, NULL, NULL, '$2y$12$HhztX0DQuPdRi3EYV1gR8.hm5gaPTtdoMldf9cTs3KSg/BAvyk4la', NULL, '2025-09-12 04:34:45', '2025-09-12 04:35:40'),
(112, 'BENOIT GERARD BALOMLEKE', 'benoit gerard_70', 'benoit.balomleke@cpb.cm', '695164220', NULL, 'TCH_70', 'TCH_112', 'teacher', NULL, 1, NULL, NULL, '$2y$12$YSgHTT9ICU1ER1tPCJ64KeMSzsMKy5lI4qTOloZd3ZNj0k74kcQ3.', NULL, '2025-09-12 04:36:45', '2025-09-12 04:37:42'),
(113, 'GISELE DJUELA FOKOU', 'gisele_85', 'gisele.djuela@cpb.cm', '652 55 30 99', NULL, 'TCH_85', 'TCH_113', 'teacher', NULL, 1, NULL, NULL, '$2y$12$c/QrpA0oQ2G3U/2AtuVgTuZ0fglBnwRy2oW5iMX5Zg1SLbk4bduiq', NULL, '2025-09-12 04:41:13', '2025-09-12 04:41:25');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `academic_periods`
--
ALTER TABLE `academic_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `academic_periods_school_year_id_order_unique` (`school_year_id`,`order`);

--
-- Index pour la table `academic_system_config`
--
ALTER TABLE `academic_system_config`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_class_date` (`school_class_id`,`attendance_date`),
  ADD KEY `idx_supervisor_date` (`supervisor_id`,`attendance_date`),
  ADD KEY `idx_year_date` (`school_year_id`,`attendance_date`),
  ADD KEY `idx_student_date_event` (`student_id`,`attendance_date`,`event_type`);

--
-- Index pour la table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Index pour la table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Index pour la table `card_templates`
--
ALTER TABLE `card_templates`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `class_payment_amounts`
--
ALTER TABLE `class_payment_amounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_payment_amounts_class_id_payment_tranche_id_unique` (`class_id`,`payment_tranche_id`),
  ADD KEY `class_payment_amounts_class_id_index` (`class_id`),
  ADD KEY `class_payment_amounts_payment_tranche_id_index` (`payment_tranche_id`);

--
-- Index pour la table `class_scholarships`
--
ALTER TABLE `class_scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_scholarships_school_class_id_is_active_index` (`school_class_id`,`is_active`),
  ADD KEY `class_scholarships_payment_tranche_id_foreign` (`payment_tranche_id`);

--
-- Index pour la table `class_series`
--
ALTER TABLE `class_series`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_series_class_id_name_unique` (`class_id`,`name`),
  ADD KEY `class_series_class_id_is_active_index` (`class_id`,`is_active`),
  ADD KEY `class_series_main_teacher_id_index` (`main_teacher_id`),
  ADD KEY `class_series_school_year_id_index` (`school_year_id`);

--
-- Index pour la table `class_series_subjects`
--
ALTER TABLE `class_series_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_series_subjects_class_series_id_subject_id_unique` (`class_series_id`,`subject_id`),
  ADD KEY `class_series_subjects_class_series_id_is_active_index` (`class_series_id`,`is_active`),
  ADD KEY `class_series_subjects_subject_id_is_active_index` (`subject_id`,`is_active`);

--
-- Index pour la table `daily_attendance_states`
--
ALTER TABLE `daily_attendance_states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily_attendance_state` (`class_series_id`,`attendance_date`,`school_year_id`),
  ADD KEY `daily_attendance_states_supervisor_id_foreign` (`supervisor_id`),
  ADD KEY `daily_attendance_states_school_year_id_foreign` (`school_year_id`),
  ADD KEY `daily_attendance_states_attendance_date_school_year_id_index` (`attendance_date`,`school_year_id`),
  ADD KEY `daily_attendance_states_class_series_id_attendance_date_index` (`class_series_id`,`attendance_date`);

--
-- Index pour la table `demandes_explication`
--
ALTER TABLE `demandes_explication`
  ADD PRIMARY KEY (`id`),
  ADD KEY `demandes_explication_emetteur_id_statut_index` (`emetteur_id`,`statut`),
  ADD KEY `demandes_explication_destinataire_id_statut_index` (`destinataire_id`,`statut`),
  ADD KEY `demandes_explication_school_year_id_date_envoi_index` (`school_year_id`,`date_envoi`);

--
-- Index pour la table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_code_unique` (`code`),
  ADD KEY `departments_head_teacher_id_foreign` (`head_teacher_id`),
  ADD KEY `departments_is_active_index` (`is_active`),
  ADD KEY `departments_order_index` (`order`),
  ADD KEY `departments_name_index` (`name`);

--
-- Index pour la table `documentary_fees`
--
ALTER TABLE `documentary_fees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documentary_fees_receipt_number_unique` (`receipt_number`),
  ADD KEY `documentary_fees_school_year_id_foreign` (`school_year_id`),
  ADD KEY `documentary_fees_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `documentary_fees_validated_by_user_id_foreign` (`validated_by_user_id`),
  ADD KEY `documentary_fees_student_id_school_year_id_index` (`student_id`,`school_year_id`),
  ADD KEY `documentary_fees_fee_type_status_index` (`fee_type`,`status`),
  ADD KEY `documentary_fees_payment_date_index` (`payment_date`);

--
-- Index pour la table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documents_folder_id_index` (`folder_id`),
  ADD KEY `documents_uploaded_by_index` (`uploaded_by`),
  ADD KEY `documents_student_id_index` (`student_id`),
  ADD KEY `documents_document_type_index` (`document_type`),
  ADD KEY `documents_visibility_index` (`visibility`),
  ADD KEY `documents_file_extension_index` (`file_extension`),
  ADD KEY `documents_created_at_index` (`created_at`);
ALTER TABLE `documents` ADD FULLTEXT KEY `documents_title_description_original_filename_fulltext` (`title`,`description`,`original_filename`);

--
-- Index pour la table `document_folders`
--
ALTER TABLE `document_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_folders_folder_type_index` (`folder_type`),
  ADD KEY `document_folders_created_by_index` (`created_by`),
  ADD KEY `document_folders_parent_folder_id_index` (`parent_folder_id`),
  ADD KEY `document_folders_is_system_folder_index` (`is_system_folder`);

--
-- Index pour la table `document_permissions`
--
ALTER TABLE `document_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`permissionable_type`,`permissionable_id`,`user_id`,`permission_type`),
  ADD KEY `document_permissions_permissionable_type_permissionable_id_index` (`permissionable_type`,`permissionable_id`),
  ADD KEY `document_permissions_granted_by_foreign` (`granted_by`),
  ADD KEY `document_permissions_user_id_index` (`user_id`),
  ADD KEY `document_permissions_permission_type_index` (`permission_type`),
  ADD KEY `document_permissions_expires_at_index` (`expires_at`);

--
-- Index pour la table `employees_payroll`
--
ALTER TABLE `employees_payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_payroll_matricule_unique` (`matricule`),
  ADD KEY `employees_payroll_user_id_foreign` (`user_id`),
  ADD KEY `employees_payroll_statut_user_id_index` (`statut`,`user_id`);

--
-- Index pour la table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluations_series_subject_id_foreign` (`series_subject_id`),
  ADD KEY `evaluations_sequence_id_series_subject_id_index` (`sequence_id`,`series_subject_id`),
  ADD KEY `evaluations_trimester_id_type_index` (`trimester_id`,`type`),
  ADD KEY `evaluations_school_year_id_date_index` (`school_year_id`,`date`),
  ADD KEY `evaluations_teacher_id_date_index` (`teacher_id`,`date`);

--
-- Index pour la table `evaluation_configs`
--
ALTER TABLE `evaluation_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_config` (`school_year_id`,`level_id`,`is_active`),
  ADD KEY `evaluation_configs_level_id_foreign` (`level_id`),
  ADD KEY `evaluation_configs_school_year_id_level_id_index` (`school_year_id`,`level_id`);

--
-- Index pour la table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Index pour la table `geolocation_zones`
--
ALTER TABLE `geolocation_zones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `geolocation_zones_enabled_index` (`enabled`),
  ADD KEY `geolocation_zones_latitude_longitude_index` (`latitude`,`longitude`);

--
-- Index pour la table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grades_student_id_evaluation_id_unique` (`student_id`,`evaluation_id`),
  ADD KEY `grades_sequence_id_foreign` (`sequence_id`),
  ADD KEY `grades_trimester_id_foreign` (`trimester_id`),
  ADD KEY `grades_school_year_id_foreign` (`school_year_id`),
  ADD KEY `grades_student_id_sequence_id_index` (`student_id`,`sequence_id`),
  ADD KEY `grades_student_id_trimester_id_index` (`student_id`,`trimester_id`),
  ADD KEY `grades_student_id_school_year_id_index` (`student_id`,`school_year_id`),
  ADD KEY `grades_evaluation_id_student_id_index` (`evaluation_id`,`student_id`),
  ADD KEY `grades_series_subject_id_sequence_id_index` (`series_subject_id`,`sequence_id`);

--
-- Index pour la table `grading_scales`
--
ALTER TABLE `grading_scales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grading_scales_level_id_foreign` (`level_id`),
  ADD KEY `grading_scales_school_year_id_level_id_index` (`school_year_id`,`level_id`),
  ADD KEY `grading_scales_grade_code_school_year_id_index` (`grade_code`,`school_year_id`);

--
-- Index pour la table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_items_categorie_etat_index` (`categorie`,`etat`),
  ADD KEY `inventory_items_quantite_quantite_min_index` (`quantite`,`quantite_min`),
  ADD KEY `inventory_items_localisation_index` (`localisation`),
  ADD KEY `inventory_items_responsable_index` (`responsable`);

--
-- Index pour la table `inventory_item_tags`
--
ALTER TABLE `inventory_item_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inventory_item_tags_inventory_item_id_inventory_tag_id_unique` (`inventory_item_id`,`inventory_tag_id`),
  ADD KEY `inventory_item_tags_inventory_tag_id_foreign` (`inventory_tag_id`);

--
-- Index pour la table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_movements_inventory_item_id_foreign` (`inventory_item_id`);

--
-- Index pour la table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inventory_tags_name_unique` (`name`),
  ADD UNIQUE KEY `inventory_tags_slug_unique` (`slug`);

--
-- Index pour la table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Index pour la table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `levels_section_id_is_active_index` (`section_id`,`is_active`),
  ADD KEY `levels_section_id_order_index` (`section_id`,`order`);

--
-- Index pour la table `main_teachers`
--
ALTER TABLE `main_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_main_teacher_per_class` (`school_class_id`,`school_year_id`),
  ADD KEY `main_teachers_teacher_id_foreign` (`teacher_id`),
  ADD KEY `main_teachers_school_year_id_foreign` (`school_year_id`);

--
-- Index pour la table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `needs`
--
ALTER TABLE `needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `needs_approved_by_foreign` (`approved_by`),
  ADD KEY `needs_user_id_index` (`user_id`),
  ADD KEY `needs_status_index` (`status`),
  ADD KEY `needs_created_at_index` (`created_at`);

--
-- Index pour la table `parent_guardians`
--
ALTER TABLE `parent_guardians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parent_guardians_email_unique` (`email`),
  ADD UNIQUE KEY `parent_guardians_phone_unique` (`phone`);

--
-- Index pour la table `parent_notifications`
--
ALTER TABLE `parent_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_notifications_student_id_foreign` (`student_id`),
  ADD KEY `parent_notifications_parent_id_is_read_index` (`parent_id`,`is_read`),
  ADD KEY `parent_notifications_parent_id_created_at_index` (`parent_id`,`created_at`),
  ADD KEY `parent_notifications_admin_id_foreign` (`admin_id`);

--
-- Index pour la table `parent_student_relationships`
--
ALTER TABLE `parent_student_relationships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parent_student_relationships_parent_id_student_id_unique` (`parent_id`,`student_id`),
  ADD KEY `parent_student_relationships_student_id_is_primary_contact_index` (`student_id`,`is_primary_contact`);

--
-- Index pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_receipt_number_unique` (`receipt_number`),
  ADD KEY `payments_school_year_id_foreign` (`school_year_id`),
  ADD KEY `payments_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `payments_student_id_school_year_id_index` (`student_id`,`school_year_id`),
  ADD KEY `payments_payment_date_index` (`payment_date`),
  ADD KEY `payments_receipt_number_index` (`receipt_number`);

--
-- Index pour la table `payment_details`
--
ALTER TABLE `payment_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_details_payment_id_payment_tranche_id_unique` (`payment_id`,`payment_tranche_id`),
  ADD KEY `payment_details_payment_id_index` (`payment_id`),
  ADD KEY `payment_details_payment_tranche_id_index` (`payment_tranche_id`);

--
-- Index pour la table `payment_tranches`
--
ALTER TABLE `payment_tranches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_tranches_is_active_order_index` (`is_active`,`order`);

--
-- Index pour la table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_periods_mois_annee_unique` (`mois`,`annee`),
  ADD KEY `payroll_periods_statut_annee_mois_index` (`statut`,`annee`,`mois`);

--
-- Index pour la table `payroll_whatsapp_notifications`
--
ALTER TABLE `payroll_whatsapp_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_whatsapp_notifications_employee_id_type_statut_index` (`employee_id`,`type`,`statut`),
  ADD KEY `payroll_whatsapp_notifications_payroll_period_id_type_index` (`payroll_period_id`,`type`);

--
-- Index pour la table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payslips_employee_id_period_id_unique` (`employee_id`,`period_id`),
  ADD KEY `payslips_period_id_foreign` (`period_id`),
  ADD KEY `payslips_statut_period_id_index` (`statut`,`period_id`);

--
-- Index pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Index pour la table `salary_cuts`
--
ALTER TABLE `salary_cuts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salary_cuts_period_id_foreign` (`period_id`),
  ADD KEY `salary_cuts_created_by_foreign` (`created_by`),
  ADD KEY `salary_cuts_employee_id_period_id_statut_index` (`employee_id`,`period_id`,`statut`);

--
-- Index pour la table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedules_class_id_day_of_week_index` (`class_id`,`day_of_week`),
  ADD KEY `schedules_academic_year_index` (`academic_year`);

--
-- Index pour la table `school_classes`
--
ALTER TABLE `school_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_classes_level_id_is_active_index` (`level_id`,`is_active`);

--
-- Index pour la table `school_settings`
--
ALTER TABLE `school_settings`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sections_name_unique` (`name`);

--
-- Index pour la table `sequences`
--
ALTER TABLE `sequences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sequences_trimester_id_number_index` (`trimester_id`,`number`),
  ADD KEY `sequences_school_year_id_number_index` (`school_year_id`,`number`),
  ADD KEY `sequences_is_current_is_active_index` (`is_current`,`is_active`);

--
-- Index pour la table `series_subjects`
--
ALTER TABLE `series_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_series_subject` (`school_class_id`,`subject_id`),
  ADD KEY `series_subjects_subject_id_foreign` (`subject_id`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Index pour la table `staff_attendances`
--
ALTER TABLE `staff_attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_attendances_user_id_attendance_date_index` (`user_id`,`attendance_date`),
  ADD KEY `staff_attendances_staff_type_attendance_date_index` (`staff_type`,`attendance_date`),
  ADD KEY `staff_attendances_school_year_id_attendance_date_index` (`school_year_id`,`attendance_date`),
  ADD KEY `staff_attendances_supervisor_id_attendance_date_index` (`supervisor_id`,`attendance_date`),
  ADD KEY `staff_attendances_class_id_index` (`class_id`),
  ADD KEY `staff_attendances_class_id_attendance_date_index` (`class_id`,`attendance_date`),
  ADD KEY `staff_attendances_staff_type_index` (`staff_type`);

--
-- Index pour la table `staff_attendance_classes`
--
ALTER TABLE `staff_attendance_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_staff_attendance_class` (`staff_attendance_id`,`school_class_id`),
  ADD KEY `staff_attendance_classes_school_class_id_foreign` (`school_class_id`),
  ADD KEY `staff_attendance_class_index` (`staff_attendance_id`,`school_class_id`);

--
-- Index pour la table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_student_number_unique` (`student_number`),
  ADD KEY `students_class_series_id_is_active_index` (`class_series_id`,`is_active`),
  ADD KEY `students_status_index` (`status`),
  ADD KEY `students_school_year_id_foreign` (`school_year_id`),
  ADD KEY `students_class_series_id_school_year_id_order_index` (`class_series_id`,`school_year_id`,`order`);

--
-- Index pour la table `student_attendances`
--
ALTER TABLE `student_attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_attendance_unique` (`student_id`,`attendance_date`,`school_year_id`),
  ADD KEY `student_attendances_marked_by_foreign` (`marked_by`),
  ADD KEY `student_attendances_student_id_attendance_date_index` (`student_id`,`attendance_date`),
  ADD KEY `student_attendances_school_class_id_attendance_date_index` (`school_class_id`,`attendance_date`),
  ADD KEY `student_attendances_school_year_id_attendance_date_index` (`school_year_id`,`attendance_date`),
  ADD KEY `student_attendances_attendance_type_index` (`attendance_type`);

--
-- Index pour la table `student_rame_status`
--
ALTER TABLE `student_rame_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_rame_status_student_id_school_year_id_unique` (`student_id`,`school_year_id`),
  ADD KEY `student_rame_status_school_year_id_foreign` (`school_year_id`),
  ADD KEY `student_rame_status_marked_by_user_id_foreign` (`marked_by_user_id`);

--
-- Index pour la table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subjects_code_unique` (`code`),
  ADD KEY `subjects_is_active_index` (`is_active`),
  ADD KEY `subjects_code_index` (`code`);

--
-- Index pour la table `supervisor_class_assignments`
--
ALTER TABLE `supervisor_class_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_supervisor_class_year` (`supervisor_id`,`school_class_id`,`school_year_id`),
  ADD KEY `supervisor_class_assignments_school_year_id_foreign` (`school_year_id`),
  ADD KEY `idx_supervisor_year` (`supervisor_id`,`school_year_id`),
  ADD KEY `idx_class_year` (`school_class_id`,`school_year_id`);

--
-- Index pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tasks_assigned_by_foreign` (`assigned_by`),
  ADD KEY `tasks_approved_by_foreign` (`approved_by`),
  ADD KEY `tasks_assigned_to_status_index` (`assigned_to`,`status`),
  ADD KEY `tasks_due_date_status_index` (`due_date`,`status`),
  ADD KEY `tasks_category_priority_index` (`category`,`priority`),
  ADD KEY `tasks_is_recurring_index` (`is_recurring`),
  ADD KEY `tasks_created_by_index` (`created_by`);

--
-- Index pour la table `task_assignees`
--
ALTER TABLE `task_assignees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_assignees_task_id_user_id_unique` (`task_id`,`user_id`),
  ADD KEY `task_assignees_user_id_status_index` (`user_id`,`status`);

--
-- Index pour la table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_comments_user_id_foreign` (`user_id`),
  ADD KEY `task_comments_task_id_created_at_index` (`task_id`,`created_at`);

--
-- Index pour la table `task_dependencies`
--
ALTER TABLE `task_dependencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_dependencies_task_id_depends_on_task_id_unique` (`task_id`,`depends_on_task_id`),
  ADD KEY `task_dependencies_depends_on_task_id_foreign` (`depends_on_task_id`);

--
-- Index pour la table `task_histories`
--
ALTER TABLE `task_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_histories_user_id_foreign` (`user_id`),
  ADD KEY `task_histories_task_id_created_at_index` (`task_id`,`created_at`);

--
-- Index pour la table `task_templates`
--
ALTER TABLE `task_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_templates_created_by_foreign` (`created_by`);

--
-- Index pour la table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teachers_qr_code_unique` (`qr_code`),
  ADD KEY `teachers_user_id_foreign` (`user_id`),
  ADD KEY `teachers_is_active_index` (`is_active`),
  ADD KEY `teachers_phone_number_index` (`phone_number`),
  ADD KEY `teachers_last_name_first_name_index` (`last_name`,`first_name`),
  ADD KEY `teachers_department_id_index` (`department_id`),
  ADD KEY `teachers_teacher_id_index` (`teacher_id`);

--
-- Index pour la table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_assignment` (`teacher_id`,`series_subject_id`,`school_year_id`),
  ADD KEY `teacher_assignments_series_subject_id_foreign` (`series_subject_id`),
  ADD KEY `teacher_assignments_school_year_id_foreign` (`school_year_id`);

--
-- Index pour la table `teacher_attendances`
--
ALTER TABLE `teacher_attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_date` (`teacher_id`,`attendance_date`),
  ADD KEY `idx_supervisor_date` (`supervisor_id`,`attendance_date`),
  ADD KEY `idx_year_date` (`school_year_id`,`attendance_date`),
  ADD KEY `idx_date_event` (`attendance_date`,`event_type`),
  ADD KEY `idx_teacher_year` (`teacher_id`,`school_year_id`);

--
-- Index pour la table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_subject_series_year_unique` (`teacher_id`,`subject_id`,`class_series_id`,`school_year_id`),
  ADD KEY `teacher_subjects_school_year_id_foreign` (`school_year_id`),
  ADD KEY `teacher_subjects_teacher_id_school_year_id_index` (`teacher_id`,`school_year_id`),
  ADD KEY `teacher_subjects_class_series_id_school_year_id_index` (`class_series_id`,`school_year_id`),
  ADD KEY `teacher_subjects_subject_id_index` (`subject_id`);

--
-- Index pour la table `trimesters`
--
ALTER TABLE `trimesters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trimesters_school_year_id_number_index` (`school_year_id`,`number`),
  ADD KEY `trimesters_is_current_is_active_index` (`is_current`,`is_active`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_qr_code_unique` (`qr_code`),
  ADD KEY `users_working_school_year_id_index` (`working_school_year_id`),
  ADD KEY `users_staff_identifier_index` (`staff_identifier`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `academic_periods`
--
ALTER TABLE `academic_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `academic_system_config`
--
ALTER TABLE `academic_system_config`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `card_templates`
--
ALTER TABLE `card_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `class_payment_amounts`
--
ALTER TABLE `class_payment_amounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=325;

--
-- AUTO_INCREMENT pour la table `class_scholarships`
--
ALTER TABLE `class_scholarships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `class_series`
--
ALTER TABLE `class_series`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT pour la table `class_series_subjects`
--
ALTER TABLE `class_series_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `daily_attendance_states`
--
ALTER TABLE `daily_attendance_states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `demandes_explication`
--
ALTER TABLE `demandes_explication`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `documentary_fees`
--
ALTER TABLE `documentary_fees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `document_folders`
--
ALTER TABLE `document_folders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `document_permissions`
--
ALTER TABLE `document_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `employees_payroll`
--
ALTER TABLE `employees_payroll`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `evaluation_configs`
--
ALTER TABLE `evaluation_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `geolocation_zones`
--
ALTER TABLE `geolocation_zones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `grading_scales`
--
ALTER TABLE `grading_scales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inventory_item_tags`
--
ALTER TABLE `inventory_item_tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `levels`
--
ALTER TABLE `levels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `main_teachers`
--
ALTER TABLE `main_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT pour la table `needs`
--
ALTER TABLE `needs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT pour la table `parent_guardians`
--
ALTER TABLE `parent_guardians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parent_notifications`
--
ALTER TABLE `parent_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parent_student_relationships`
--
ALTER TABLE `parent_student_relationships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=924;

--
-- AUTO_INCREMENT pour la table `payment_details`
--
ALTER TABLE `payment_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1590;

--
-- AUTO_INCREMENT pour la table `payment_tranches`
--
ALTER TABLE `payment_tranches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payroll_whatsapp_notifications`
--
ALTER TABLE `payroll_whatsapp_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `salary_cuts`
--
ALTER TABLE `salary_cuts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `school_classes`
--
ALTER TABLE `school_classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT pour la table `school_settings`
--
ALTER TABLE `school_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `sequences`
--
ALTER TABLE `sequences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `series_subjects`
--
ALTER TABLE `series_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `staff_attendances`
--
ALTER TABLE `staff_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT pour la table `staff_attendance_classes`
--
ALTER TABLE `staff_attendance_classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=887;

--
-- AUTO_INCREMENT pour la table `student_attendances`
--
ALTER TABLE `student_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `student_rame_status`
--
ALTER TABLE `student_rame_status`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=882;

--
-- AUTO_INCREMENT pour la table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT pour la table `supervisor_class_assignments`
--
ALTER TABLE `supervisor_class_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task_assignees`
--
ALTER TABLE `task_assignees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task_dependencies`
--
ALTER TABLE `task_dependencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task_histories`
--
ALTER TABLE `task_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task_templates`
--
ALTER TABLE `task_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT pour la table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `teacher_attendances`
--
ALTER TABLE `teacher_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `trimesters`
--
ALTER TABLE `trimesters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `academic_periods`
--
ALTER TABLE `academic_periods`
  ADD CONSTRAINT `academic_periods_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `class_payment_amounts`
--
ALTER TABLE `class_payment_amounts`
  ADD CONSTRAINT `class_payment_amounts_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_payment_amounts_payment_tranche_id_foreign` FOREIGN KEY (`payment_tranche_id`) REFERENCES `payment_tranches` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `class_scholarships`
--
ALTER TABLE `class_scholarships`
  ADD CONSTRAINT `class_scholarships_payment_tranche_id_foreign` FOREIGN KEY (`payment_tranche_id`) REFERENCES `payment_tranches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_scholarships_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `class_series`
--
ALTER TABLE `class_series`
  ADD CONSTRAINT `class_series_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_series_main_teacher_id_foreign` FOREIGN KEY (`main_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `class_series_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `class_series_subjects`
--
ALTER TABLE `class_series_subjects`
  ADD CONSTRAINT `class_series_subjects_class_series_id_foreign` FOREIGN KEY (`class_series_id`) REFERENCES `class_series` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_series_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `daily_attendance_states`
--
ALTER TABLE `daily_attendance_states`
  ADD CONSTRAINT `daily_attendance_states_class_series_id_foreign` FOREIGN KEY (`class_series_id`) REFERENCES `class_series` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_attendance_states_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_attendance_states_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `demandes_explication`
--
ALTER TABLE `demandes_explication`
  ADD CONSTRAINT `demandes_explication_destinataire_id_foreign` FOREIGN KEY (`destinataire_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_explication_emetteur_id_foreign` FOREIGN KEY (`emetteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_explication_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_head_teacher_id_foreign` FOREIGN KEY (`head_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `documentary_fees`
--
ALTER TABLE `documentary_fees`
  ADD CONSTRAINT `documentary_fees_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documentary_fees_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documentary_fees_validated_by_user_id_foreign` FOREIGN KEY (`validated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `document_folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `document_folders`
--
ALTER TABLE `document_folders`
  ADD CONSTRAINT `document_folders_parent_folder_id_foreign` FOREIGN KEY (`parent_folder_id`) REFERENCES `document_folders` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `document_permissions`
--
ALTER TABLE `document_permissions`
  ADD CONSTRAINT `document_permissions_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_sequence_id_foreign` FOREIGN KEY (`sequence_id`) REFERENCES `sequences` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_series_subject_id_foreign` FOREIGN KEY (`series_subject_id`) REFERENCES `series_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `evaluations_trimester_id_foreign` FOREIGN KEY (`trimester_id`) REFERENCES `trimesters` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `evaluation_configs`
--
ALTER TABLE `evaluation_configs`
  ADD CONSTRAINT `evaluation_configs_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluation_configs_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_sequence_id_foreign` FOREIGN KEY (`sequence_id`) REFERENCES `sequences` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_series_subject_id_foreign` FOREIGN KEY (`series_subject_id`) REFERENCES `series_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_trimester_id_foreign` FOREIGN KEY (`trimester_id`) REFERENCES `trimesters` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `grading_scales`
--
ALTER TABLE `grading_scales`
  ADD CONSTRAINT `grading_scales_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grading_scales_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `inventory_item_tags`
--
ALTER TABLE `inventory_item_tags`
  ADD CONSTRAINT `inventory_item_tags_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_item_tags_inventory_tag_id_foreign` FOREIGN KEY (`inventory_tag_id`) REFERENCES `inventory_tags` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `inventory_movements_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `levels`
--
ALTER TABLE `levels`
  ADD CONSTRAINT `levels_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `main_teachers`
--
ALTER TABLE `main_teachers`
  ADD CONSTRAINT `main_teachers_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `main_teachers_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `main_teachers_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `parent_notifications`
--
ALTER TABLE `parent_notifications`
  ADD CONSTRAINT `parent_notifications_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `parent_notifications_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `parent_guardians` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_notifications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `parent_student_relationships`
--
ALTER TABLE `parent_student_relationships`
  ADD CONSTRAINT `parent_student_relationships_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `parent_guardians` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_student_relationships_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payment_details`
--
ALTER TABLE `payment_details`
  ADD CONSTRAINT `payment_details_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_details_payment_tranche_id_foreign` FOREIGN KEY (`payment_tranche_id`) REFERENCES `payment_tranches` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payroll_whatsapp_notifications`
--
ALTER TABLE `payroll_whatsapp_notifications`
  ADD CONSTRAINT `payroll_whatsapp_notifications_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees_payroll` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_whatsapp_notifications_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees_payroll` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslips_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `salary_cuts`
--
ALTER TABLE `salary_cuts`
  ADD CONSTRAINT `salary_cuts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees_payroll` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salary_cuts_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `school_classes`
--
ALTER TABLE `school_classes`
  ADD CONSTRAINT `school_classes_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sequences`
--
ALTER TABLE `sequences`
  ADD CONSTRAINT `sequences_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sequences_trimester_id_foreign` FOREIGN KEY (`trimester_id`) REFERENCES `trimesters` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `series_subjects`
--
ALTER TABLE `series_subjects`
  ADD CONSTRAINT `series_subjects_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `series_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `staff_attendances`
--
ALTER TABLE `staff_attendances`
  ADD CONSTRAINT `staff_attendances_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staff_attendances_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `staff_attendance_classes`
--
ALTER TABLE `staff_attendance_classes`
  ADD CONSTRAINT `staff_attendance_classes_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_attendance_classes_staff_attendance_id_foreign` FOREIGN KEY (`staff_attendance_id`) REFERENCES `staff_attendances` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_class_series_id_foreign` FOREIGN KEY (`class_series_id`) REFERENCES `class_series` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`);

--
-- Contraintes pour la table `student_attendances`
--
ALTER TABLE `student_attendances`
  ADD CONSTRAINT `student_attendances_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_attendances_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendances_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `student_rame_status`
--
ALTER TABLE `student_rame_status`
  ADD CONSTRAINT `student_rame_status_marked_by_user_id_foreign` FOREIGN KEY (`marked_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_rame_status_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_rame_status_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `supervisor_class_assignments`
--
ALTER TABLE `supervisor_class_assignments`
  ADD CONSTRAINT `supervisor_class_assignments_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supervisor_class_assignments_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `task_assignees`
--
ALTER TABLE `task_assignees`
  ADD CONSTRAINT `task_assignees_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `task_dependencies`
--
ALTER TABLE `task_dependencies`
  ADD CONSTRAINT `task_dependencies_depends_on_task_id_foreign` FOREIGN KEY (`depends_on_task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_dependencies_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `task_histories`
--
ALTER TABLE `task_histories`
  ADD CONSTRAINT `task_histories_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `task_templates`
--
ALTER TABLE `task_templates`
  ADD CONSTRAINT `task_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD CONSTRAINT `teacher_assignments_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_series_subject_id_foreign` FOREIGN KEY (`series_subject_id`) REFERENCES `series_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teacher_attendances`
--
ALTER TABLE `teacher_attendances`
  ADD CONSTRAINT `teacher_attendances_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_attendances_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_class_series_id_foreign` FOREIGN KEY (`class_series_id`) REFERENCES `class_series` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `trimesters`
--
ALTER TABLE `trimesters`
  ADD CONSTRAINT `trimesters_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_working_school_year_id_foreign` FOREIGN KEY (`working_school_year_id`) REFERENCES `school_years` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
