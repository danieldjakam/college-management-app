-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mar. 02 sep. 2025 à 10:03
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
(15, 13, 'A', NULL, 86, 1, '2025-08-04 02:56:10', '2025-08-04 02:56:10', NULL, NULL),
(16, 13, 'B', NULL, 80, 1, '2025-08-04 02:56:10', '2025-08-04 02:56:10', NULL, NULL),
(17, 14, 'A', NULL, 88, 1, '2025-08-04 02:57:43', '2025-08-04 02:57:43', NULL, NULL),
(18, 14, 'B', NULL, 65, 1, '2025-08-04 02:57:43', '2025-08-04 02:57:43', NULL, NULL),
(19, 15, 'ALL', NULL, 72, 1, '2025-08-04 02:59:49', '2025-08-04 07:27:13', NULL, NULL),
(20, 15, 'ESP', NULL, 62, 1, '2025-08-04 02:59:49', '2025-08-04 07:27:13', NULL, NULL),
(21, 16, 'ESP', NULL, 109, 1, '2025-08-04 03:01:56', '2025-08-04 03:01:56', NULL, NULL),
(22, 16, 'ALL', NULL, 106, 1, '2025-08-04 03:01:56', '2025-08-04 03:01:56', NULL, NULL),
(33, 21, 'C1', NULL, 41, 1, '2025-08-04 05:16:21', '2025-08-04 08:28:27', NULL, NULL),
(34, 21, 'A4 ALL', NULL, 47, 1, '2025-08-04 07:16:32', '2025-08-04 08:29:11', NULL, NULL),
(35, 22, 'A4 ESP', NULL, 39, 1, '2025-08-04 07:18:25', '2025-08-04 07:18:25', NULL, NULL),
(36, 22, 'A4 ALL', NULL, 67, 1, '2025-08-04 07:18:25', '2025-08-04 07:18:25', NULL, NULL),
(37, 22, 'D', NULL, 60, 1, '2025-08-04 07:18:25', '2025-08-04 07:18:25', NULL, NULL),
(38, 22, 'C', NULL, 60, 1, '2025-08-04 07:18:25', '2025-08-04 07:18:25', NULL, NULL),
(39, 23, 'Tle A4 All', NULL, 70, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08', NULL, NULL),
(40, 23, 'Tle ESP', NULL, 71, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08', NULL, NULL),
(41, 23, 'Tle C', NULL, 60, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08', NULL, NULL),
(42, 23, 'Tle D', NULL, 60, 1, '2025-08-04 09:41:08', '2025-08-04 09:41:08', NULL, NULL),
(43, 24, '1ere A', NULL, 60, 1, '2025-08-04 09:43:45', '2025-08-04 09:43:45', NULL, NULL),
(44, 24, '1ere B', NULL, 60, 1, '2025-08-04 09:43:45', '2025-08-04 09:43:45', NULL, NULL),
(45, 25, 'A', NULL, 60, 1, '2025-08-04 09:45:02', '2025-08-04 09:45:02', NULL, NULL),
(46, 25, 'B', NULL, 60, 1, '2025-08-04 09:45:02', '2025-08-04 09:45:02', NULL, NULL),
(47, 26, 'A', NULL, 60, 1, '2025-08-04 09:45:59', '2025-08-04 09:45:59', NULL, NULL),
(48, 26, 'B', NULL, 60, 1, '2025-08-04 09:45:59', '2025-08-04 09:45:59', NULL, NULL),
(49, 27, 'A', NULL, 60, 1, '2025-08-04 09:47:04', '2025-08-04 09:47:04', NULL, NULL),
(50, 27, 'B', NULL, 60, 1, '2025-08-04 09:47:04', '2025-08-04 09:47:04', NULL, NULL),
(51, 28, 'A', NULL, 60, 1, '2025-08-04 11:49:24', '2025-08-04 11:49:24', NULL, NULL),
(52, 28, 'B', NULL, 57, 1, '2025-08-04 11:49:24', '2025-08-04 11:49:24', NULL, NULL),
(53, 29, 'A', NULL, 60, 1, '2025-08-04 12:47:00', '2025-08-04 12:47:00', NULL, NULL),
(54, 29, 'B', NULL, 55, 1, '2025-08-04 12:47:00', '2025-08-04 12:47:00', NULL, NULL),
(55, 30, 'A', NULL, 60, 1, '2025-08-04 12:55:09', '2025-08-04 12:55:09', NULL, NULL),
(56, 30, 'B', NULL, 60, 1, '2025-08-04 12:55:09', '2025-08-04 12:55:09', NULL, NULL),
(57, 31, 'A', NULL, 60, 1, '2025-08-04 12:56:54', '2025-08-04 12:56:54', NULL, NULL),
(58, 31, 'B', NULL, 60, 1, '2025-08-04 12:56:54', '2025-08-04 12:56:54', NULL, NULL),
(59, 32, 'A', NULL, 60, 1, '2025-08-04 13:02:07', '2025-08-04 13:02:07', NULL, NULL),
(60, 32, 'B', NULL, 60, 1, '2025-08-04 13:02:07', '2025-08-04 13:02:07', NULL, NULL),
(61, 33, 'A', NULL, 60, 1, '2025-08-04 13:03:19', '2025-08-04 13:03:19', NULL, NULL),
(62, 33, 'B', NULL, 60, 1, '2025-08-04 13:03:19', '2025-08-04 13:03:19', NULL, NULL),
(63, 34, 'A', NULL, 60, 1, '2025-08-04 13:05:04', '2025-08-04 13:05:04', NULL, NULL),
(64, 34, 'B', NULL, 60, 1, '2025-08-04 13:05:04', '2025-08-04 13:05:04', NULL, NULL),
(65, 35, 'A', NULL, 60, 1, '2025-08-04 13:07:27', '2025-08-04 13:07:27', NULL, NULL),
(66, 35, 'B', NULL, 60, 1, '2025-08-04 13:07:27', '2025-08-04 13:07:27', NULL, NULL),
(67, 36, 'A', NULL, 60, 1, '2025-08-04 13:39:42', '2025-08-04 13:39:42', NULL, NULL),
(68, 36, 'B', NULL, 60, 1, '2025-08-04 13:39:42', '2025-08-04 13:39:42', NULL, NULL),
(69, 37, 'A', NULL, 60, 1, '2025-08-04 13:43:07', '2025-08-04 13:43:07', NULL, NULL),
(70, 38, 'A', NULL, 60, 1, '2025-08-04 13:45:11', '2025-08-04 13:45:11', NULL, NULL),
(71, 39, 'A', NULL, 60, 1, '2025-08-04 13:48:18', '2025-08-04 13:48:18', NULL, NULL),
(72, 40, 'A', NULL, 60, 1, '2025-08-04 13:50:14', '2025-08-04 13:50:14', NULL, NULL),
(73, 41, 'A', NULL, 60, 1, '2025-08-05 10:15:38', '2025-08-05 10:15:38', NULL, NULL),
(74, 41, 'B', NULL, 57, 1, '2025-08-05 10:15:38', '2025-08-05 10:15:38', NULL, NULL),
(75, 42, 'A', NULL, 60, 1, '2025-08-05 10:16:56', '2025-08-05 10:16:56', NULL, NULL),
(76, 42, 'B', NULL, 60, 1, '2025-08-05 10:16:56', '2025-08-05 10:16:56', NULL, NULL),
(77, 43, 'a', NULL, 55, 1, '2025-08-05 12:05:47', '2025-08-05 12:05:47', NULL, NULL),
(78, 44, 'A', NULL, 60, 1, '2025-08-05 12:06:41', '2025-08-05 12:06:41', NULL, NULL),
(79, 45, 'A', NULL, 60, 1, '2025-08-05 12:07:23', '2025-08-05 12:07:23', NULL, NULL),
(80, 46, 'A', NULL, 60, 1, '2025-08-05 12:08:02', '2025-08-05 12:08:02', NULL, NULL),
(81, 47, 'A', NULL, 60, 1, '2025-08-05 12:08:43', '2025-08-05 12:08:43', NULL, NULL),
(82, 48, 'A', NULL, 60, 1, '2025-08-05 12:25:57', '2025-08-05 12:25:57', NULL, NULL),
(84, 50, 'A', NULL, 60, 1, '2025-08-05 12:28:28', '2025-08-05 12:28:28', NULL, NULL),
(86, 52, 'A', NULL, 60, 1, '2025-08-05 12:31:27', '2025-08-05 12:31:27', NULL, NULL),
(87, 53, 'A', NULL, 60, 1, '2025-08-05 12:32:26', '2025-08-05 12:32:26', NULL, NULL),
(89, 55, 'A', NULL, 60, 1, '2025-08-05 12:34:13', '2025-08-05 12:34:13', NULL, NULL),
(91, 57, 'A', NULL, 60, 1, '2025-08-05 12:36:28', '2025-08-05 12:36:28', NULL, NULL),
(92, 58, 'A', NULL, 60, 1, '2025-08-05 12:38:37', '2025-08-05 12:38:37', NULL, NULL),
(94, 60, 'A', NULL, 60, 1, '2025-08-05 12:41:00', '2025-08-05 12:41:00', NULL, NULL),
(98, 64, 'A', NULL, 60, 1, '2025-08-05 12:59:48', '2025-08-05 12:59:48', NULL, NULL),
(99, 65, 'A', NULL, 60, 1, '2025-08-05 13:01:06', '2025-08-05 13:01:06', NULL, NULL),
(100, 66, 'A', NULL, 60, 1, '2025-08-05 13:03:23', '2025-08-05 13:03:23', NULL, NULL),
(101, 67, 'A', NULL, 60, 1, '2025-08-05 13:05:15', '2025-08-05 13:05:15', NULL, NULL),
(102, 68, 'A', NULL, 60, 1, '2025-08-06 06:55:28', '2025-08-06 06:55:28', NULL, NULL),
(103, 69, 'A', NULL, 60, 1, '2025-08-06 06:57:38', '2025-08-06 06:57:38', NULL, NULL),
(104, 70, 'A', NULL, 60, 1, '2025-08-06 06:58:37', '2025-08-06 06:58:37', NULL, NULL),
(105, 71, 'A', NULL, 60, 1, '2025-08-06 06:59:28', '2025-08-06 06:59:28', NULL, NULL),
(106, 72, 'A', NULL, 60, 1, '2025-08-06 07:01:45', '2025-08-06 07:01:45', NULL, NULL),
(107, 73, 'A', NULL, 60, 1, '2025-08-06 07:02:44', '2025-08-06 07:02:44', NULL, NULL),
(108, 74, 'A', NULL, 60, 1, '2025-08-06 07:03:45', '2025-08-06 07:03:45', NULL, NULL),
(109, 75, 'A', NULL, 60, 1, '2025-08-06 07:06:25', '2025-08-06 07:06:25', NULL, NULL),
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
(83, '2025_09_01_104515_add_scanned_qr_code_to_staff_attendances', 11);

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
  `user_id` bigint(20) UNSIGNED NOT NULL,
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
(16, 'Besoin pédagogique', 'Liste du matériel Pédagogique pour débuter la rentrée scolaire\n08 cartons de Craies blanches 21000*8 = 210,000F\n03 cartons de craies de couleurs  42000*3 = 126,000F\n15 paquets de chemises 3000*15 =45,000F\n15 paquets sous chemises 2000*15 = 30,000F\n10 paquets de marqueurs Bic ( bleu,rouge,noir) 10*2500= 25,000F\n02 paquets de stylo bleu 5000*2 = 10,000F\n02 paquets de stylo rouge 5000*2 = 10,000F\n02 paquets de stylo vert 5000*2 = 10,000F\n02 paquets de stylo noir 5000*2 = 10,000F\n05 règles de 100 cm 5*1000 = 5000F\n80 cahiers de texte 2000*80 = 160,000F\n80 cahier d\'appel 2000*80 = 160,000F\n10 cahiers de 200 pages (registre) 10*1000 = 10,OOOF\n41 serpieres 41*1000 = 41,000F \n41 raclettes 41*1500 = 61,500F\n41 sceau  41*1000 = 41,000F \n05 pelle à main 5*500 = 2500F\n05 râteaux 05*1000= 5000f\n100 balais traductionnel 100*250= 25,000F\n03 balais a manche 1500*3 = 4500F\n05 machettes 05*1500 = 7500F\n05 houes 5*2000 = 10,000F\nTotal =', 1009000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-12 13:44:36', '2025-08-18 08:57:54'),
(17, 'Besoin Administratif', 'pour commencer la rentrée scolaire\n15 sous chemise 15*3500= 52,000F\n15 chemises 15*4500 = 67,500F\n05 agrafeuses 24/6 05*1500 = 7500F\n10 agrafeuses bébés 10*1500 = 15,000F\n10 paquets de 10 agrafes 24/6  800 *10 = 8000F\n10 paquets de 10 agrafes bébés  800*10 = 8000F\n10 paquets de marqueurs ( bleu, rouge noir, vert)  2500*10 = 25,000F\n10 paquets de trombone 1000*10 = 10,000F\n02 paquets de stylo bleu 5000*2 = 10,000F\n02 paquets de stylo rouge 5000*2 = 10,000F\n02 paquets de stylo vert 5000*2 = 10,000F\n02 paquets de stylo noir 5000*2 = 10,000F\n03 paquets de crayon 2B 500*3 = 1500F\n20 tailles crayons 20*300= 6000F\n30 gommes 30*100= 3000F\n10 paquets de petite fronde 10*500= 5000F\n10 paquets de moyen fronde 10*1000= 10,000F\n02 paquets d\'encre rouge 12000*2= 24,000F\n03 paquets de colles transparente 2000*3 = 6000F\n10 gros scotchs 1500*10 = 15,000F\n10 boites de punaises 10 *500= 5000F\n15 règles de 30 cm 15*200 = 3000F\n10 paquet de corrector Bic 10*2500= 25,000F\n10 paquet de souligner 10*500= 5000F\n06  paire de ciseaux 06*500 = 3000F\n12 agenda 3000*12= 36,000F\n10 bloc notes 10*1500= 15,000F\n20 stick notes 20*2000 = 40,000F\n13 Pen stand 13*1000 = 13,000F\n100 rouleaux de papier hygiénique 100*300 = 30,000F \n12 clé USB de 8GO 12*2500 = 30,000F\n10 paquets d\'enveloppe A4 10*2000 = 20,000F\n10 paquets d\'enveloppes A5 10*1000 = 10,000F\n10 calculatrices 10*2000= 20,000F\n25 cartons pour archive 25*500 = 12,500F\n02 carton serre dos petit noir 2*2500 = 5000F\n02 carton serre dos moyen noir 2*3500 = 7000F\n02 carton serre dos grand noir 2*8000 = 16,000F\n01 carton de spirales petit noir 01*2000 = 2000F\n01 carton de spirales moyen noir 2500*1 = 2500F\n01 carton de spirales grand noir 4000*1 = 4000F\n02 paquet de papier transparent A4             5000F\n15 paquets de papier cartonnée A4                  25,000F\n02 paquet de papier cartonnée glacer A4          6000F\n02 paquet de papier carbone                                 5000F\n03 paquet d\'enveloppes A3                                  3000F', 651500.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-12 14:52:45', '2025-08-18 09:08:33'),
(18, 'Besoin Adminitratif', 'suites\n03 chaise de bureau                                            105,000F\n05 rallonges                                                          15000F\n02 armoire de bureau                                          280,000F\n02 régulateur de tensions                                  44,000F\n03 portes clé                                                        1500F\n02 table de bureau                                           120,000F', 565000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-12 15:24:48', '2025-08-12 15:29:08'),
(19, 'Besoin technique', '01 paquet d\'attaches 2500F\n04 écrous                 1500F\n01 régulateur de tension 15000F\nNB : urgent\nNB déjà gérer', 19000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-14 09:11:48', '2025-08-15 06:00:51'),
(24, 'Besoin pédagoque', 'Transport pour aller déposer le règlement intérieur a la délégation \nUrgent\nNB DEJA GERER', 2000.00, 'pending', 3, NULL, NULL, NULL, 0, '2025-08-18 09:02:00', '2025-08-26 13:02:19'),
(32, 'Besoin pour la starling', 'Il faut recharger la Starling d\'ESTUAIRE DOUALA  cette semaine\nNB DEJA GERER', 60000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-19 11:50:50', '2025-08-20 09:09:38'),
(33, 'Besoin administratif', 'Contribution du SEDUC LT prévu pour le 27/08/2025 a douala  30,000F transport 5000f \nil a demander 55,000f mais il est venu me restituer 20,000f \nNB DEJA GERER', 35000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:17:12', '2025-08-20 09:17:12'),
(34, 'transport', 'Déplacement de M kamgang chris pour Yaoundé aller retour\nNB DEJA GERER', 20000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:24:24', '2025-08-20 09:24:25'),
(35, 'Besoin technique', 'achat des tubes LED regrette et ampoule\nNB DEJA GERER', 31300.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:27:41', '2025-08-20 09:27:41'),
(36, 'Besoin technique', 'Main d\'oeuvre macon travaux de terre sceller les fers au sol et poteau et de la toiture solder \nNB DEJA GERER', 40000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:35:18', '2025-08-20 09:35:18'),
(37, 'MATERIEL', 'LA vente de cable pour bus\nNB DEJA GERER', 10000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-20 09:37:03', '2025-08-20 09:37:04'),
(38, 'Besoin technique', 'ARDOISINE\n10 boites d\'ardoisine noir 4500*10 = 45000\n4 feuilles de contreplaquer 3300*4= 13200\n15l de diluant 1000*15 =                      15000\n05 rouleaux 700*5 =                          3500\n01 planche pour règle                         7000\n01 paquet de pointe toc                     1500\n01 paquet de pointe de 30              5000\nTransport                                        3000\nTOTAL  93,200   \nNB DEJA  GERER', 93200.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 11:38:41', '2025-08-26 11:38:41'),
(39, 'Besoin technique', 'DEVIS POUR LE SELAGE DU PORTAIL\n06 disques a couper 1000*6= 6000\n03 antirouilles 3000*3= 9000\n02 Diluant 1500*2= 3000\n02 rouleaux 500*2 = 1000\nTOTAL = 24,000f', 24000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-08-26 11:47:32', '2025-08-26 11:47:33'),
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
(51, 'Besoin technique', 'Nettoyage des toilettes\n10L acide 2000*10= 20000\n05L cresyle 2000*5= 10000\n02 savon en liquide de 5l 3500*2= 7000\nMotivation des enfants 3 pour 4 jours 30000\nURGENT', 67000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-02 07:36:09', '2025-09-02 07:36:10'),
(52, 'Besoin technique', 'Devis estimatif pour les châteaux d\'eau flotteur\n01 contacteur D40 15000\n01 disjoncteur 2pole 16A 5000\nMain d\'oeuvre 10000\nURGENT', 30000.00, 'pending', 3, NULL, NULL, NULL, 1, '2025-09-02 07:38:50', '2025-09-02 07:38:51');

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
(161, 143, 1, 80000.00, '2025-08-27', '2025-08-26', '2025-08-27 10:53:12', 'cash', NULL, NULL, 15, 'REC26250827233709', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-27 10:53:12', '2025-08-27 10:53:12'),
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
(308, 288, 1, 78000.00, '2025-08-30', '2025-08-30', '2025-08-30 10:01:40', 'cash', NULL, NULL, 15, 'REC26250830876062', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 10:01:40', '2025-08-30 10:01:40');
INSERT INTO `payments` (`id`, `student_id`, `school_year_id`, `total_amount`, `payment_date`, `versement_date`, `validation_date`, `payment_method`, `reference_number`, `notes`, `created_by_user_id`, `receipt_number`, `is_rame_physical`, `has_scholarship`, `scholarship_amount`, `has_reduction`, `reduction_amount`, `discount_reason`, `created_at`, `updated_at`) VALUES
(309, 289, 1, 31000.00, '2025-08-30', '2025-08-30', '2025-08-30 10:20:08', 'cash', NULL, NULL, 15, 'REC26250830664735', 0, 0, 0.00, 0, 0.00, NULL, '2025-08-30 10:20:08', '2025-08-30 10:20:08'),
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
(384, 358, 1, 50000.00, '2025-09-02', '2025-09-02', '2025-09-02 08:02:23', 'cash', NULL, NULL, 16, 'REC26250902203848', 0, 0, 0.00, 0, 0.00, NULL, '2025-09-02 08:02:23', '2025-09-02 08:02:23');

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
(207, 107, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 06:26:24', '2025-08-20 06:26:24'),
(208, 107, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Montant normal - 42,000 FCFA', 1, '2025-08-20 06:26:24', '2025-08-20 06:26:24'),
(209, 107, 4, 19700.00, 0.00, 19700.00, 19700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,700 FCFA', 1, '2025-08-20 06:26:24', '2025-08-20 06:26:24'),
(210, 108, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 06:31:43', '2025-08-20 06:31:43'),
(211, 108, 3, 67000.00, 0.00, 67000.00, 67000.00, 0, 'Montant normal - 67,000 FCFA', 1, '2025-08-20 06:31:43', '2025-08-20 06:31:43'),
(212, 108, 4, 17200.00, 0.00, 17200.00, 17200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,200 FCFA', 1, '2025-08-20 06:31:43', '2025-08-20 06:31:43'),
(213, 109, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 07:23:35', '2025-08-20 07:23:35'),
(214, 110, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 07:28:16', '2025-08-20 07:28:16'),
(215, 111, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-20 07:44:25', '2025-08-20 07:44:25'),
(216, 111, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-08-20 07:44:25', '2025-08-20 07:44:25'),
(217, 111, 4, 24900.00, 0.00, 24900.00, 24900.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 30,000 FCFA, Réduit: 24,900 FCFA', 1, '2025-08-20 07:44:25', '2025-08-20 07:44:25'),
(218, 112, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 07:48:04', '2025-08-20 07:48:04'),
(219, 112, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-08-20 07:48:04', '2025-08-20 07:48:04'),
(220, 112, 4, 25900.00, 0.00, 25900.00, 25900.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 30,000 FCFA, Réduit: 25,900 FCFA', 1, '2025-08-20 07:48:04', '2025-08-20 07:48:04'),
(221, 113, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 07:51:58', '2025-08-20 07:51:58'),
(222, 114, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 07:55:23', '2025-08-20 07:55:23'),
(223, 114, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Montant normal - 72,000 FCFA', 1, '2025-08-20 07:55:23', '2025-08-20 07:55:23'),
(224, 114, 4, 16700.00, 0.00, 16700.00, 16700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 16,700 FCFA', 1, '2025-08-20 07:55:23', '2025-08-20 07:55:23'),
(225, 115, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 08:00:35', '2025-08-20 08:00:35'),
(226, 116, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-20 08:09:56', '2025-08-20 08:09:56'),
(227, 116, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Montant normal - 72,000 FCFA', 1, '2025-08-20 08:09:56', '2025-08-20 08:09:56'),
(228, 116, 4, 15700.00, 0.00, 15700.00, 15700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 15,700 FCFA', 1, '2025-08-20 08:09:56', '2025-08-20 08:09:56'),
(229, 117, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 08:24:54', '2025-08-20 08:24:54'),
(230, 117, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-20 08:24:54', '2025-08-20 08:24:54'),
(231, 117, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-20 08:24:54', '2025-08-20 08:24:54'),
(232, 118, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-20 09:27:31', '2025-08-20 09:27:31'),
(233, 119, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 09:31:34', '2025-08-20 09:31:34'),
(234, 120, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-20 09:31:54', '2025-08-20 09:31:54'),
(235, 120, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-20 09:31:54', '2025-08-20 09:31:54'),
(236, 121, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-20 09:39:30', '2025-08-20 09:39:30'),
(237, 121, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Montant normal - 52,000 FCFA', 1, '2025-08-20 09:39:30', '2025-08-20 09:39:30'),
(238, 121, 4, 17700.00, 0.00, 17700.00, 17700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,700 FCFA', 1, '2025-08-20 09:39:30', '2025-08-20 09:39:30'),
(239, 122, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 09:52:26', '2025-08-20 09:52:26'),
(240, 123, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-20 10:02:59', '2025-08-20 10:02:59'),
(241, 124, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-20 10:06:09', '2025-08-20 10:06:09'),
(242, 124, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, 'Montant normal - 92,000 FCFA', 1, '2025-08-20 10:06:09', '2025-08-20 10:06:09'),
(243, 124, 4, 13700.00, 0.00, 13700.00, 13700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 13,700 FCFA', 1, '2025-08-20 10:06:09', '2025-08-20 10:06:09'),
(244, 125, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-20 10:11:07', '2025-08-20 10:11:07'),
(245, 125, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, 'Montant normal - 92,000 FCFA', 1, '2025-08-20 10:11:07', '2025-08-20 10:11:07'),
(246, 125, 4, 13700.00, 0.00, 13700.00, 13700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 13,700 FCFA', 1, '2025-08-20 10:11:07', '2025-08-20 10:11:07'),
(247, 126, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 10:22:55', '2025-08-20 10:22:55'),
(248, 126, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, 'Montant normal - 57,000 FCFA', 1, '2025-08-20 10:22:55', '2025-08-20 10:22:55'),
(249, 126, 4, 18200.00, 0.00, 18200.00, 18200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 18,200 FCFA', 1, '2025-08-20 10:22:55', '2025-08-20 10:22:55'),
(250, 127, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 10:59:56', '2025-08-20 10:59:56'),
(251, 127, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Montant normal - 42,000 FCFA', 1, '2025-08-20 10:59:56', '2025-08-20 10:59:56'),
(252, 127, 4, 19700.00, 0.00, 19700.00, 19700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,700 FCFA', 1, '2025-08-20 10:59:56', '2025-08-20 10:59:56'),
(253, 128, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:02:54', '2025-08-20 11:02:54'),
(254, 129, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-20 11:05:28', '2025-08-20 11:05:28'),
(255, 130, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:09:03', '2025-08-20 11:09:03'),
(256, 131, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 11:18:42', '2025-08-20 11:18:42'),
(257, 131, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, 'Montant normal - 47,000 FCFA', 1, '2025-08-20 11:18:42', '2025-08-20 11:18:42'),
(258, 131, 4, 19200.00, 0.00, 19200.00, 19200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,200 FCFA', 1, '2025-08-20 11:18:42', '2025-08-20 11:18:42'),
(259, 132, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:22:22', '2025-08-20 11:22:22'),
(260, 132, 3, 14000.00, 0.00, 14000.00, 22000.00, 0, NULL, 0, '2025-08-20 11:22:22', '2025-08-20 11:22:22'),
(261, 133, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:25:45', '2025-08-20 11:25:45'),
(262, 134, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 11:30:36', '2025-08-20 11:30:36'),
(263, 134, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-08-20 11:30:36', '2025-08-20 11:30:36'),
(264, 134, 4, 25900.00, 0.00, 25900.00, 25900.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 30,000 FCFA, Réduit: 25,900 FCFA', 1, '2025-08-20 11:30:36', '2025-08-20 11:30:36'),
(265, 135, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:34:11', '2025-08-20 11:34:11'),
(266, 136, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 11:37:28', '2025-08-20 11:37:28'),
(267, 137, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 12:23:54', '2025-08-20 12:23:54'),
(268, 138, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-20 12:26:52', '2025-08-20 12:26:52'),
(269, 139, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, 'Montant normal - 21,000 FCFA', 1, '2025-08-20 12:30:17', '2025-08-20 12:30:17'),
(270, 139, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-08-20 12:30:17', '2025-08-20 12:30:17'),
(271, 139, 4, 22400.00, 0.00, 22400.00, 22400.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 25,000 FCFA, Réduit: 22,400 FCFA', 1, '2025-08-20 12:30:17', '2025-08-20 12:30:17'),
(272, 140, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-20 13:52:52', '2025-08-20 13:52:52'),
(273, 140, 3, 29000.00, 0.00, 29000.00, 57000.00, 0, NULL, 0, '2025-08-20 13:52:52', '2025-08-20 13:52:52'),
(274, 141, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-20 14:02:07', '2025-08-20 14:02:07'),
(275, 141, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Montant normal - 72,000 FCFA', 1, '2025-08-20 14:02:07', '2025-08-20 14:02:07'),
(276, 141, 4, 16700.00, 0.00, 16700.00, 16700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 16,700 FCFA', 1, '2025-08-20 14:02:07', '2025-08-20 14:02:07'),
(277, 142, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-20 14:06:02', '2025-08-20 14:06:02'),
(278, 142, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-08-20 14:06:02', '2025-08-20 14:06:02'),
(279, 142, 4, 24900.00, 0.00, 24900.00, 24900.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 30,000 FCFA, Réduit: 24,900 FCFA', 1, '2025-08-20 14:06:02', '2025-08-20 14:06:02'),
(280, 143, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, 'Montant normal - 21,000 FCFA', 1, '2025-08-20 14:10:15', '2025-08-20 14:10:15'),
(281, 143, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-08-20 14:10:15', '2025-08-20 14:10:15'),
(282, 143, 4, 22400.00, 0.00, 22400.00, 22400.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 25,000 FCFA, Réduit: 22,400 FCFA', 1, '2025-08-20 14:10:15', '2025-08-20 14:10:15'),
(283, 144, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, NULL, 1, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(284, 144, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, NULL, 1, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(285, 144, 4, 25000.00, 0.00, 25000.00, 25000.00, 0, NULL, 1, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(286, 144, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-08-26 11:09:59', '2025-08-26 11:09:59'),
(290, 146, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-27 09:25:47', '2025-08-27 09:25:47'),
(291, 146, 3, 77000.00, 0.00, 77000.00, 77000.00, 0, 'Montant normal - 77,000 FCFA', 1, '2025-08-27 09:25:47', '2025-08-27 09:25:47'),
(292, 146, 4, 15200.00, 0.00, 15200.00, 15200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 15,200 FCFA', 1, '2025-08-27 09:25:47', '2025-08-27 09:25:47'),
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
(303, 152, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-27 10:18:17', '2025-08-27 10:18:17'),
(304, 152, 3, 77000.00, 0.00, 77000.00, 77000.00, 0, 'Montant normal - 77,000 FCFA', 1, '2025-08-27 10:18:17', '2025-08-27 10:18:17'),
(305, 152, 4, 15200.00, 0.00, 15200.00, 15200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 15,200 FCFA', 1, '2025-08-27 10:18:17', '2025-08-27 10:18:17'),
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
(320, 161, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 10:53:12', '2025-08-27 10:53:12'),
(321, 161, 3, 49000.00, 0.00, 49000.00, 57000.00, 0, NULL, 0, '2025-08-27 10:53:12', '2025-08-27 10:53:12'),
(325, 163, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 11:09:27', '2025-08-27 11:09:27'),
(326, 163, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-08-27 11:09:27', '2025-08-27 11:09:27'),
(327, 163, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-08-27 11:09:27', '2025-08-27 11:09:27'),
(328, 164, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-27 11:15:04', '2025-08-27 11:15:04'),
(329, 165, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 11:22:36', '2025-08-27 11:22:36'),
(330, 166, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 11:29:26', '2025-08-27 11:29:26'),
(331, 167, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-27 11:39:46', '2025-08-27 11:39:46'),
(332, 168, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-27 12:16:47', '2025-08-27 12:16:47'),
(333, 168, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Montant normal - 52,000 FCFA', 1, '2025-08-27 12:16:47', '2025-08-27 12:16:47'),
(334, 168, 4, 17700.00, 0.00, 17700.00, 17700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,700 FCFA', 1, '2025-08-27 12:16:47', '2025-08-27 12:16:47'),
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
(364, 185, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-27 13:53:47', '2025-08-27 13:53:47'),
(365, 185, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Montant normal - 82,000 FCFA', 1, '2025-08-27 13:53:47', '2025-08-27 13:53:47'),
(366, 185, 4, 15700.00, 0.00, 15700.00, 15700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 15,700 FCFA', 1, '2025-08-27 13:53:47', '2025-08-27 13:53:47'),
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
(383, 194, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-28 07:33:34', '2025-08-28 07:33:34'),
(384, 194, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Montant normal - 52,000 FCFA', 1, '2025-08-28 07:33:34', '2025-08-28 07:33:34'),
(385, 194, 4, 17700.00, 0.00, 17700.00, 17700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,700 FCFA', 1, '2025-08-28 07:33:34', '2025-08-28 07:33:34'),
(386, 195, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-28 07:35:58', '2025-08-28 07:35:58'),
(387, 195, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Montant normal - 42,000 FCFA', 1, '2025-08-28 07:35:58', '2025-08-28 07:35:58'),
(388, 195, 4, 19700.00, 0.00, 19700.00, 19700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,700 FCFA', 1, '2025-08-28 07:35:58', '2025-08-28 07:35:58'),
(389, 196, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-28 07:39:21', '2025-08-28 07:39:21'),
(390, 196, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-08-28 07:39:21', '2025-08-28 07:39:21'),
(391, 196, 4, 24900.00, 0.00, 24900.00, 24900.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 30,000 FCFA, Réduit: 24,900 FCFA', 1, '2025-08-28 07:39:21', '2025-08-28 07:39:21'),
(392, 197, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-28 07:42:35', '2025-08-28 07:42:35'),
(393, 197, 3, 62000.00, 0.00, 62000.00, 62000.00, 0, 'Montant normal - 62,000 FCFA', 1, '2025-08-28 07:42:35', '2025-08-28 07:42:35'),
(394, 197, 4, 17700.00, 0.00, 17700.00, 17700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,700 FCFA', 1, '2025-08-28 07:42:35', '2025-08-28 07:42:35'),
(395, 198, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-28 07:48:22', '2025-08-28 07:48:22'),
(396, 198, 3, 62000.00, 0.00, 62000.00, 62000.00, 0, 'Montant normal - 62,000 FCFA', 1, '2025-08-28 07:48:22', '2025-08-28 07:48:22'),
(397, 198, 4, 17700.00, 0.00, 17700.00, 17700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,700 FCFA', 1, '2025-08-28 07:48:22', '2025-08-28 07:48:22'),
(398, 199, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-28 07:53:35', '2025-08-28 07:53:35'),
(399, 199, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Montant normal - 82,000 FCFA', 1, '2025-08-28 07:53:35', '2025-08-28 07:53:35'),
(400, 199, 4, 14700.00, 0.00, 14700.00, 14700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 14,700 FCFA', 1, '2025-08-28 07:53:35', '2025-08-28 07:53:35'),
(401, 200, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-28 07:57:35', '2025-08-28 07:57:35'),
(402, 200, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Montant normal - 42,000 FCFA', 1, '2025-08-28 07:57:35', '2025-08-28 07:57:35'),
(403, 200, 4, 19700.00, 0.00, 19700.00, 19700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,700 FCFA', 1, '2025-08-28 07:57:35', '2025-08-28 07:57:35'),
(404, 201, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-28 08:05:11', '2025-08-28 08:05:11'),
(405, 201, 3, 24000.00, 0.00, 24000.00, 72000.00, 0, NULL, 0, '2025-08-28 08:05:11', '2025-08-28 08:05:11'),
(406, 202, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-28 08:08:36', '2025-08-28 08:08:36'),
(407, 202, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Montant normal - 82,000 FCFA', 1, '2025-08-28 08:08:36', '2025-08-28 08:08:36'),
(408, 202, 4, 15700.00, 0.00, 15700.00, 15700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 15,700 FCFA', 1, '2025-08-28 08:08:36', '2025-08-28 08:08:36'),
(409, 203, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-28 08:11:41', '2025-08-28 08:11:41'),
(410, 203, 3, 4000.00, 0.00, 4000.00, 82000.00, 0, NULL, 0, '2025-08-28 08:11:41', '2025-08-28 08:11:41'),
(411, 204, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-08-28 08:14:02', '2025-08-28 08:14:02'),
(412, 204, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Montant normal - 82,000 FCFA', 1, '2025-08-28 08:14:02', '2025-08-28 08:14:02'),
(413, 204, 4, 14700.00, 0.00, 14700.00, 14700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 14,700 FCFA', 1, '2025-08-28 08:14:02', '2025-08-28 08:14:02'),
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
(531, 272, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-08-30 06:58:56', '2025-08-30 06:58:56'),
(532, 272, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Montant normal - 72,000 FCFA', 1, '2025-08-30 06:58:56', '2025-08-30 06:58:56'),
(533, 272, 4, 16700.00, 0.00, 16700.00, 16700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 16,700 FCFA', 1, '2025-08-30 06:58:56', '2025-08-30 06:58:56'),
(534, 273, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:00:44', '2025-08-30 07:00:44'),
(536, 275, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:03:47', '2025-08-30 07:03:47'),
(537, 276, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:07:00', '2025-08-30 07:07:00'),
(538, 277, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 07:09:47', '2025-08-30 07:09:47'),
(539, 278, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:11:46', '2025-08-30 07:11:46'),
(540, 278, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, NULL, 1, '2025-08-30 07:11:46', '2025-08-30 07:11:46'),
(541, 278, 4, 12000.00, 0.00, 12000.00, 20000.00, 0, NULL, 0, '2025-08-30 07:11:46', '2025-08-30 07:11:46'),
(542, 279, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:13:26', '2025-08-30 07:13:26'),
(543, 279, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, NULL, 1, '2025-08-30 07:13:26', '2025-08-30 07:13:26'),
(544, 280, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:15:05', '2025-08-30 07:15:05'),
(545, 281, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 07:17:26', '2025-08-30 07:17:26'),
(546, 281, 3, 76000.00, 0.00, 76000.00, 82000.00, 0, NULL, 0, '2025-08-30 07:17:26', '2025-08-30 07:17:26'),
(547, 282, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-08-30 07:19:43', '2025-08-30 07:19:43'),
(548, 283, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-08-30 07:21:24', '2025-08-30 07:21:24'),
(549, 283, 3, 69000.00, 0.00, 69000.00, 72000.00, 0, NULL, 0, '2025-08-30 07:21:24', '2025-08-30 07:21:24'),
(550, 284, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, 'Montant normal - 21,000 FCFA', 1, '2025-08-30 07:23:43', '2025-08-30 07:23:43'),
(551, 284, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-08-30 07:23:43', '2025-08-30 07:23:43');
INSERT INTO `payment_details` (`id`, `payment_id`, `payment_tranche_id`, `amount_allocated`, `previous_amount`, `new_total_amount`, `required_amount_at_time`, `was_reduced`, `reduction_context`, `is_fully_paid`, `created_at`, `updated_at`) VALUES
(552, 284, 4, 22400.00, 0.00, 22400.00, 22400.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 25,000 FCFA, Réduit: 22,400 FCFA', 1, '2025-08-30 07:23:43', '2025-08-30 07:23:43'),
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
(592, 311, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-01 05:04:42', '2025-09-01 05:04:42'),
(593, 311, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Montant normal - 42,000 FCFA', 1, '2025-09-01 05:04:43', '2025-09-01 05:04:43'),
(594, 311, 4, 19700.00, 0.00, 19700.00, 19700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,700 FCFA', 1, '2025-09-01 05:04:43', '2025-09-01 05:04:43'),
(595, 312, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-01 05:38:34', '2025-09-01 05:38:34'),
(596, 313, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-01 05:48:39', '2025-09-01 05:48:39'),
(597, 313, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, 'Montant normal - 57,000 FCFA', 1, '2025-09-01 05:48:39', '2025-09-01 05:48:39'),
(598, 313, 4, 18200.00, 0.00, 18200.00, 18200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 18,200 FCFA', 1, '2025-09-01 05:48:39', '2025-09-01 05:48:39'),
(599, 314, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, NULL, 1, '2025-09-01 06:02:54', '2025-09-01 06:02:54'),
(600, 314, 3, 9000.00, 0.00, 9000.00, 82000.00, 0, NULL, 0, '2025-09-01 06:02:54', '2025-09-01 06:02:54'),
(601, 315, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-01 06:12:10', '2025-09-01 06:12:10'),
(602, 315, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Montant normal - 42,000 FCFA', 1, '2025-09-01 06:12:10', '2025-09-01 06:12:10'),
(603, 315, 4, 19700.00, 0.00, 19700.00, 19700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,700 FCFA', 1, '2025-09-01 06:12:10', '2025-09-01 06:12:10'),
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
(640, 335, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-09-01 10:07:39', '2025-09-01 10:07:39'),
(641, 335, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, 'Montant normal - 92,000 FCFA', 1, '2025-09-01 10:07:39', '2025-09-01 10:07:39'),
(642, 335, 4, 13700.00, 0.00, 13700.00, 13700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 13,700 FCFA', 1, '2025-09-01 10:07:39', '2025-09-01 10:07:39'),
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
(673, 354, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, 'Montant normal - 21,000 FCFA', 1, '2025-09-02 04:27:40', '2025-09-02 04:27:40'),
(674, 354, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-09-02 04:27:40', '2025-09-02 04:27:40'),
(675, 354, 4, 22400.00, 0.00, 22400.00, 22400.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 25,000 FCFA, Réduit: 22,400 FCFA', 1, '2025-09-02 04:27:40', '2025-09-02 04:27:40'),
(676, 355, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-02 04:32:47', '2025-09-02 04:32:47'),
(677, 355, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Montant normal - 42,000 FCFA', 1, '2025-09-02 04:32:47', '2025-09-02 04:32:47'),
(678, 355, 4, 19700.00, 0.00, 19700.00, 19700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,700 FCFA', 1, '2025-09-02 04:32:47', '2025-09-02 04:32:47'),
(679, 356, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-09-02 04:35:55', '2025-09-02 04:35:55'),
(680, 356, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Montant normal - 82,000 FCFA', 1, '2025-09-02 04:35:55', '2025-09-02 04:35:55'),
(681, 356, 4, 14700.00, 0.00, 14700.00, 14700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 14,700 FCFA', 1, '2025-09-02 04:35:55', '2025-09-02 04:35:55'),
(682, 357, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-09-02 04:39:17', '2025-09-02 04:39:17'),
(683, 357, 3, 82000.00, 0.00, 82000.00, 82000.00, 0, 'Montant normal - 82,000 FCFA', 1, '2025-09-02 04:39:17', '2025-09-02 04:39:17'),
(684, 357, 4, 14700.00, 0.00, 14700.00, 14700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 14,700 FCFA', 1, '2025-09-02 04:39:17', '2025-09-02 04:39:17'),
(685, 358, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-02 04:42:52', '2025-09-02 04:42:52'),
(686, 358, 3, 42000.00, 0.00, 42000.00, 42000.00, 0, 'Montant normal - 42,000 FCFA', 1, '2025-09-02 04:42:52', '2025-09-02 04:42:52'),
(687, 358, 4, 19700.00, 0.00, 19700.00, 19700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,700 FCFA', 1, '2025-09-02 04:42:52', '2025-09-02 04:42:52'),
(688, 359, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-02 04:46:05', '2025-09-02 04:46:05'),
(689, 359, 3, 67000.00, 0.00, 67000.00, 67000.00, 0, 'Montant normal - 67,000 FCFA', 1, '2025-09-02 04:46:05', '2025-09-02 04:46:05'),
(690, 359, 4, 17200.00, 0.00, 17200.00, 17200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,200 FCFA', 1, '2025-09-02 04:46:05', '2025-09-02 04:46:05'),
(691, 360, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-09-02 04:49:55', '2025-09-02 04:49:55'),
(692, 360, 3, 77000.00, 0.00, 77000.00, 77000.00, 0, 'Montant normal - 77,000 FCFA', 1, '2025-09-02 04:49:55', '2025-09-02 04:49:55'),
(693, 360, 4, 15200.00, 0.00, 15200.00, 15200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 15,200 FCFA', 1, '2025-09-02 04:49:55', '2025-09-02 04:49:55'),
(694, 361, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-02 04:54:18', '2025-09-02 04:54:18'),
(695, 361, 3, 72000.00, 0.00, 72000.00, 72000.00, 0, 'Montant normal - 72,000 FCFA', 1, '2025-09-02 04:54:18', '2025-09-02 04:54:18'),
(696, 361, 4, 16700.00, 0.00, 16700.00, 16700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 16,700 FCFA', 1, '2025-09-02 04:54:18', '2025-09-02 04:54:18'),
(697, 362, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-09-02 04:59:04', '2025-09-02 04:59:04'),
(698, 362, 3, 92000.00, 0.00, 92000.00, 92000.00, 0, 'Montant normal - 92,000 FCFA', 1, '2025-09-02 04:59:04', '2025-09-02 04:59:04'),
(699, 362, 4, 13700.00, 0.00, 13700.00, 13700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 13,700 FCFA', 1, '2025-09-02 04:59:04', '2025-09-02 04:59:04'),
(700, 363, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-02 05:03:26', '2025-09-02 05:03:26'),
(701, 363, 3, 47000.00, 0.00, 47000.00, 47000.00, 0, 'Montant normal - 47,000 FCFA', 1, '2025-09-02 05:03:26', '2025-09-02 05:03:26'),
(702, 363, 4, 19200.00, 0.00, 19200.00, 19200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 19,200 FCFA', 1, '2025-09-02 05:03:26', '2025-09-02 05:03:26'),
(703, 364, 2, 21000.00, 0.00, 21000.00, 21000.00, 0, 'Montant normal - 21,000 FCFA', 1, '2025-09-02 05:06:59', '2025-09-02 05:06:59'),
(704, 364, 3, 70000.00, 0.00, 70000.00, 70000.00, 0, 'Montant normal - 70,000 FCFA', 1, '2025-09-02 05:06:59', '2025-09-02 05:06:59'),
(705, 364, 4, 22400.00, 0.00, 22400.00, 22400.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 25,000 FCFA, Réduit: 22,400 FCFA', 1, '2025-09-02 05:06:59', '2025-09-02 05:06:59'),
(706, 365, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-09-02 05:15:08', '2025-09-02 05:15:08'),
(707, 365, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Montant normal - 52,000 FCFA', 1, '2025-09-02 05:15:08', '2025-09-02 05:15:08'),
(708, 365, 4, 17700.00, 0.00, 17700.00, 17700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,700 FCFA', 1, '2025-09-02 05:15:08', '2025-09-02 05:15:08'),
(709, 366, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 05:23:48', '2025-09-02 05:23:48'),
(710, 367, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, NULL, 1, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(711, 367, 3, 22000.00, 0.00, 22000.00, 22000.00, 0, NULL, 1, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(712, 367, 4, 20000.00, 0.00, 20000.00, 20000.00, 0, NULL, 1, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(713, 367, 5, 10000.00, 0.00, 10000.00, 10000.00, 0, NULL, 1, '2025-09-02 05:27:09', '2025-09-02 05:27:09'),
(714, 368, 2, 41000.00, 0.00, 41000.00, 41000.00, 0, 'Montant normal - 41,000 FCFA', 1, '2025-09-02 05:31:03', '2025-09-02 05:31:03'),
(715, 368, 3, 52000.00, 0.00, 52000.00, 52000.00, 0, 'Montant normal - 52,000 FCFA', 1, '2025-09-02 05:31:03', '2025-09-02 05:31:03'),
(716, 368, 4, 17700.00, 0.00, 17700.00, 17700.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 17,700 FCFA', 1, '2025-09-02 05:31:03', '2025-09-02 05:31:03'),
(717, 369, 2, 31000.00, 0.00, 31000.00, 31000.00, 0, 'Montant normal - 31,000 FCFA', 1, '2025-09-02 05:34:18', '2025-09-02 05:34:18'),
(718, 369, 3, 57000.00, 0.00, 57000.00, 57000.00, 0, 'Montant normal - 57,000 FCFA', 1, '2025-09-02 05:34:18', '2025-09-02 05:34:18'),
(719, 369, 4, 18200.00, 0.00, 18200.00, 18200.00, 1, 'Nouvelle réduction 10.00% sur dernières tranches - Normal: 20,000 FCFA, Réduit: 18,200 FCFA', 1, '2025-09-02 05:34:18', '2025-09-02 05:34:18'),
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
(745, 384, 3, 19000.00, 0.00, 19000.00, 72000.00, 0, NULL, 0, '2025-09-02 08:02:23', '2025-09-02 08:02:23');

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
(14, '5e', 13, NULL, 1, '2025-08-04 02:57:43', '2025-08-04 02:57:43'),
(15, '4em', 13, NULL, 1, '2025-08-04 02:59:49', '2025-08-04 02:59:49'),
(16, '3e', 13, NULL, 1, '2025-08-04 03:01:56', '2025-08-04 03:01:56'),
(21, '2nd', 14, NULL, 1, '2025-08-04 05:16:21', '2025-08-04 05:16:21'),
(22, '1ere', 14, NULL, 1, '2025-08-04 07:18:25', '2025-08-04 07:18:25'),
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
(1, 'COLLÈGE POLYVALENT BILINGUE  DOUALA', NULL, 'B.P. 4100, Douala, Cameroun', '233 43 25 47', 'contact@cpb-douala.com', 'https://cpb-douala.com/', 'logos/i76wD5ne1K1rv8C3cOkBkYOsDKWFHlreYfc7cTzu.png', 'FCFA', 'FIGEC', 'Cameroun', 'Douala', 'Vos dossiers ne seront transmis qu\'après paiement de la totalité des frais de scolarité sollicités', '2025-08-15', 10.00, '#6f42c1', 'Stephane Foyet', '+237696118389', 1, 'https://api.ultramsg.com/instance97191/', '97191', 'vdnvcpgsd1veydwc', '2025-08-03 17:55:23', '2025-08-20 12:32:02');

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
('056syPH8kUWqWbOdd4H70VLmRpCRCzgRoFZdO9nl', NULL, '199.45.155.73', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVHJ4ZXFmUzl2ckNpRm1mTjdKRUFjNFZPRmFVeWszVUt2Mk5ET1JIciI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755302310),
('0zI4DnS0V2ltygmNPakKFjBZRH20gi4qde9xhzi9', NULL, '167.94.138.205', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidG5EU01pQ2JQTGNoODg4SktkMUdaQ3ZsVUpjdFA0Zkdkb1NLMTVPOSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755309838),
('1zMWh9bkdxzdTV2ivsGklbZujQDxvdHG5vRW13c5', NULL, '35.225.44.127', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR3Y4RVFySENXaFM4U2taRjJSbGtyNUxueTBRS2xiNHMyTUN1WWlnMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755187596),
('2GtBiCfDeBMhdioqGX1RMtD1pAl7sQy5tOkJ5wp9', NULL, '3.146.111.124', 'Mozilla/5.0 zgrab/0.x', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicmhDMkp6dDVqZ3NFN3gwWVNiOGIxU0dmcnRGZURKb25ORGpoZ0pGVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755729572),
('2yYY1uk2ULxsNnsDRbV6Znxjk55lhEomzKdKUjNH', NULL, '41.202.207.150', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiOFFqZGdnVGhMTExwS2R6dlVHYzlBcnlDZDhWUXlXdUVBN0lCOExiTCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755251012),
('30il0hwwAgkCeZfX6ziWJtd1MtKgnu1S3C81zkid', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMmFPQ3M0TGVwYzBtUE9HekNtR0NpNWdmZHVOMVBtOGI1MmlSQ0dNSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vP3BocGluZm89MSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1756142259),
('3SVMAH1YLedmiPqFhQ7z0LD2ZBph2hRG8Bhxy2Hp', NULL, '206.168.34.68', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSE1NcjJZOUZpTjZnenhFMXZVYnRRNHByQk1xOUlXUEJtVklTWWJMaiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755320274),
('4vkQs3Tq3SCJZ7jqHWIzgetB0HyJFgXfngfAFx8p', NULL, '199.45.154.124', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYWh5MzlWbjJudFFsRXlPb0l0WHJGY2VqdmZRRnk0aHZZUHF1a3pzVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755322003),
('7o91CJRgd1YFUL33UBeq9aRDAT33vXJxGOZzI0s0', NULL, '185.177.72.12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUVNIc3lYdzB0YThyZGtQeVZyRXVBSFptRWRPbnMxNHJxSG1qMWFBaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755216635),
('9Jl8MUu0IfnPeEX6F8vMjlMrErAUbHwKRPideklj', NULL, '128.192.12.117', 'Mozilla/5.0 (compatible; UGAResearchAgent/1.0; Please visit: NISLabUGA.github.io)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiM1d3NTlpNzF3ak10VXFKTTBLNmRRcTJzd01ZdGRUcWthdXhROUEzbyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755453319),
('AwyjUG3fwKDwsI0sP9NDSD7fo5etVXU30I6XwsUB', NULL, '179.43.149.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3887.7 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUHpmV2hlR0FLNXFteW83MGRjUVM3RTdGMnZnRTVkWTBpeWt2bjJSVyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755288996),
('dCtv3ScAt4XjZNABxgJU8r7iHTicJyHdCVjseG2H', NULL, '3.239.70.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ0xGREExaDBEUVpCV1ExcGtjcE9QY2xZMUx3QUNvYUtERUplSUg4YiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755347714),
('EC39nMxw6MHtWQl1fD7lJ6k6XDFDEBE6mSbiF93M', NULL, '20.171.207.204', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibnpqME1LZm16VW9PNThSYU1tN2NHeU44M01RRXk5dHFiRTJQdTJ4WCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755052843),
('EUVQmaQj0yENoPoAGnHirAEpZO20EU25Yotkb5NV', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicEg4NVZyMEdpQ21IMVJpQ1RhOUpLNnc1a1N2enQ1M0JYak14Q2VGSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vP3BwPWVudiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1756142725),
('gP2O9n62FlZ79brOBupqbydNzRZgnTmlcrWJ7BmE', NULL, '129.0.80.191', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieG5Ib3pmZEYxSlhPTmpFaGpFSEdCbHBKRWZqMHF0R21XUndZZFRFTiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755580815),
('gPpJxS2f3eGnU6emUAFVpXoFW1BGnUi9oy4BkIY9', NULL, '64.222.212.198', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUzhSR1hNSkw3bGk2QmVGU1B5QzBxVHFHSWZ3NDFmSXlrYlpTbU5YOCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755027136),
('GxwSPThQxoCpM10f0wy6z9cVmlmggxEdpZB2ifVj', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSEFyb01zNW54cXNPUVZwRzVrS0ZWd1NSRUliWDZGdDVUUzd3aHhneSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vP3E9aW5mbyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1756142261),
('hR26PT9Vjasfikmz8b8FmGXrn8sJhTpUgd5PVhQr', NULL, '157.143.53.238', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTVRKczZuM1c5RzZJUkJXUEUwekhlcE5BZU93OUZvVU52M3dsb3NQZSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755245072),
('hTZwKBJeRjH7t9MyGGv1vWg6KTnG0FEqurhnDV19', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibmRrZ0I3MDZSNWExcHVIMk8wZERQbTJINXRwVnNpVWtuM2VjaTFyZCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vP3hkZWJ1Z2luZm89Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1756142261),
('ijJ2txJLrtIny6Ddy1cOJTfhbvjSDISO3MlIIUrB', NULL, '213.209.143.116', 'Mozilla/5.0 (X11; Linux i686; rv:124.0) Gecko/20100101 Firefox/124.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibnpWVXV2eU14ZHo2V3BqNk0wd3lKalhRZk9ZYkV0TmNzQ0NLMTAxayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755202633),
('JGOtLJTEmAoK6FzSByiNExsQ61KPXkhLOO1NKsHb', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRVdYOTRuZTNKUVVMcGltMXJmTkFNenJ6UkdYZFVqNGZDNlQ1WDN5RCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vP3BwPWVudiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1756142725),
('l8NQzAGaOTpjcrPp9A12lUrtc1WQyB5MyLO3axF2', NULL, '93.123.109.79', 'fasthttp', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSVF5dExXTlhjdWNHS05kSHM5WVlWUUpvaU1Qcm4xbU05ZEV0Mk41NSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755179033),
('LdDkeJrwsJn7pwRGrJPxDWfn2Iz8o1Mxf6jmbtlm', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibkRrM0ZHek1LbU9yelhNUzRxZ2FVcUk0ZG9oSXNSSE9DMDRNQVVWRSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1756142218),
('Le1E3o0H4RtXWelv5YZFuIKnC4CWv4H3azAbJ2Au', NULL, '185.177.72.46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZlZ3T3A5cFkzS3VVYmV4UmVyTVhoUG84bW5BZUNpcXJTdHB5dW9hSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vaW5kZXgucGhwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755444289),
('LKZ8k6LQsqJfO44sUuRThFRCaC2mrDCzdoKFlL3R', NULL, '199.45.155.73', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaUthQmJDdmlsa1l3OHJ2U0NzSW1Wb1Z2RVpHVTFPOGplbEtsNjlFZCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755325370),
('M8LqmYUJc2CbHOBvtq1ot7Vnfu724FjEOIbE7m2j', NULL, '185.177.72.115', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVVE5MEpYUXNZdFB3SmtIVEdVSTZYejBFdHlJaXRoRWFrU0RVaGYwYiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755268282),
('mDga9PwTtzEcuyvHlnxrgd2BFipwjBKwEcvRwaWo', NULL, '205.169.39.57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.5938.132 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoic1NUd3A5djdGa1VDdVZoWHpOWWgwYjdNVnZhQ3pzc1FFc3JLV01lZCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755948802),
('mZAydKwewUOSL8PrB9xibZrNtFvPNjNviuEZZ5cD', NULL, '34.76.2.252', 'python-requests/2.32.4', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWVp4QjczY0xCQ2RZbGRNZ3RuRW94Z0RuTGlsS2VObFhCdklvbzNobiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755335208),
('n6m3RnUNRkwSwdtnD2ZZ7VegXD4AiXkrom0gqyEu', NULL, '149.57.180.166', 'Mozilla/5.0 (X11; Linux i686; rv:109.0) Gecko/20100101 Firefox/120.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicmMwSGhPTmYwNXhIUWU3Y0V3OHFiSUxESlNkSUFKQXcyZ2RMTFZpSyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755253536),
('NFkCJ7DHMSA5EMLZu8YrE83xuwTWR87tHL4VsJzS', NULL, '206.168.34.73', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiejBFaUFIWUtNN2hDa1BIUFJEY2lScnBLNFBXaUZpeGJteEdCMjU2OCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755321443),
('NzVvuMYqJdeKKvQ1XNxZuLMam7wMbwGr2tgiN6Qh', NULL, '66.249.66.40', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoia2d6aFpWcTRnazRIelhyZnowMzExRUFDeEpFRHlNY1lyRGpwZkxYUiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755477909),
('oAI1dtXnNy3lEC6v6GQluPlrV5N2AuyDVELqLNwW', NULL, '185.177.72.115', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUXozT0I1ZEVDY25wNnNNQmZqS3JUemdhWnBiSk5uWDlyd2RsYnEwNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vP3BocGluZm89MSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1755268282),
('oEcgWQfjnxRiZ0ektIgZIPwse1DwSOEQobR0myy3', NULL, '34.76.2.252', 'python-requests/2.32.4', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNndNcFNNZ0hpWDdSWFZvVVJja2NWa0hzM0ZPYTVIRFFLUUJNVXo3QSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755338192),
('OLeoz0dH3lTwgGBkLDIr3oCckI9FiG9JPpzPyhyQ', NULL, '162.142.125.207', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiT3daaTN2NldwUVVqZlVOOW8wb0oxQTdHMVBpNjhXSWFzek92SEpmaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755317780),
('oNsrxxTUmXVJ2EdrTlyIMvLVYdVnPKGqq10qBuau', NULL, '34.76.2.252', 'python-requests/2.32.4', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVGM5QUJTNVZFMDQxSVZ0dUxxd251Y1JXYVpaTDQ5QkNYRFU0YUVIQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755335208),
('OvxtvsmBal0iqTAx6lwr4eK9N2zxAzv3X1hgVPT9', NULL, '206.168.34.116', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaGZCaVpIZUxYY0lpUjVEUHBTa3JlNmpLZkNtNEIzdEFzSE1zU3VEUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755845924),
('p55esInfswNMaNRSSEUCGZImchF72deR3jynJERz', NULL, '185.177.72.12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNmJhaGpVdUlNUklaM2pmVk4wczF3Z1E3MU02ZjZTRHFjVVYyVmlVWCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vP3BwPWVudiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1755216723),
('PBVTyOFpAi1JO8YifjplSrAU7xuyitxlXvADZmYP', NULL, '34.44.80.189', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNE1kZDZFQllDbGFMRW5UUXhDNkRTbUtSWjBBc1BMZFh2dUNWaTNoSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755197663),
('PFYAAFp8sItiAeaFSde1cmICOQC5SVczKhxKOvU5', NULL, '66.249.66.40', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNVJMbXM0eEZFY01wc2hjQkY4cjBsUndMVVBjMk9xRU9CZFVMM3JVSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755477913),
('pZTYRbTQy0BvKqb1SQB7DRPgq3YbfMmnPyIdI15v', NULL, '206.168.34.33', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRWo0aWkyMzNTYmlkcDU4eW5xYkJsM1IwaDZvSUdtYzZwVFNweHVWciI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755318688),
('qkU64P1yjtfPEdi68aIBG3T0fjWbwUhFXMO7yRnr', NULL, '206.168.34.84', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoid2s4blVyU0VYQTE5UERJRlNwR243MWZvTWFNTVdrcmtybFByUE9qTSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755320502),
('QSYMWXFW8oWuRTiUaPk5H0bmGr7AFzMGGJlwzmFX', NULL, '149.57.180.54', 'Mozilla/5.0 (X11; Linux i686; rv:109.0) Gecko/20100101 Firefox/120.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiT1A5RFRicXhZbmhiUkhZbE9pbHlyZDlKbWVwNVNTbkhyZmtudWZ4MSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755265441),
('r4E3tRCpPnrQTJtrt68oSJ1vbgL6h265rTP84WYE', NULL, '199.45.155.80', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNHNBV2NDR041ZkQ1V2NUUXczclpSNVdGSGtadmVPeGNBb0I4NWxwVyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755325125),
('rjkoBj2yVTaJiRIK69qSQ94AKlbHvIDmT7naOM3E', NULL, '206.168.34.123', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQk54Y205VVBQNUNpT01WdnZxM3N4dUFoNjRvYXFKdmhxVm5Jc29WTCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755328306),
('rRAQC3MsLak63PvlEZH2dBoddSXa3rZWPNZcPxbh', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicnZOOFFYc1JEUHo5ZFdvOExFN3JnbXFueEpQcG5LcTdjZEd4Z2g4aCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1756142725),
('SQmBnGMOABOX1d9gTcb0z6P14xwE7SSAI1bvxjWT', NULL, '205.169.39.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.5938.132 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMDN5TEEwWUNBamFIUkVaNzE5VkRWVWNITWVMdHgwNU5ETkVyREtFRCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1756224530),
('tFWwHkM6td5PPnA98ozKDWVXS27UtV45oWsROfLH', NULL, '34.76.248.112', 'python-requests/2.32.4', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibE5oNWtlb29lcHlpOUdwNmpFVGV1cTRLQVFRN3MycFVzY0tLZFB4cSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1756804676),
('Ua1weFVkFxRfdQvReNCtmU50rk51GaUzXphYxQWR', NULL, '129.0.80.191', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQmFUTmRieTJGQjA4bjE4U1dobjdjU3lpNTFEUGhNZGFDWk4wMGtCNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly8zMS4yMDcuMzQuNjk6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1755527564),
('vM37bq66sNXb8WF64ezfXHS29p5PzxsSqSfkTwbg', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR09lRjNyNXh3UUd6QTBRVzFPcEZmYUpXTmpSeVdsUGp5eEpETTljQiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vbG9naW4/cHA9ZW52Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1756142725),
('vtDxBD84hzziPOMfZariD2wj5Yso8YxUfTrIEvLk', NULL, '3.239.179.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOVlrU1I3aTQyYTlhYUVjcDJaMUJBam1xRlJQYkVJNjd1VlQ4ZGpMTiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755347714),
('w4hMeY5qCaqzX6zpmByAHTwOrADyxdUojZ6SnBeA', NULL, '185.177.72.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNFlqb0tmd3BLVjBKcFpRTnRvTktqaTVrbnpCWjFpaWJZMFNlaWxJUiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vaW5kZXgucGhwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1756142240),
('WlzqytgEH0AgG3skIPl2bYffYpiRrnCO6pK6xVGq', NULL, '206.168.34.90', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidkNrWUg0U2k5M05lYjRrb05UOXNuZVp6b216ZngwRTlMamRFS0Y2YSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755319606),
('WrOh2lQhhdQNUyXH0uGPVj0s1kSdWgZg2atmSPzf', NULL, '199.45.155.72', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidEk2dEFBVUlPZmhnWnNrSEEzM1dOaHI2dzZHOXhKMk1zMklYd2lGayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755326977),
('XewtOl8rK8B6aZC5ED0Tjiy0pH5cBhI6dWbftVOu', NULL, '162.142.125.36', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ1JPdDNzRkZmcURFdTI1dkZtdnVEZUtUeW5tcEMwRVBGSlBFdG9FdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755308783),
('XwHcDOy1Z4h2aIa0s9N0Wip3IkVoQYyEa9oHoSxN', NULL, '205.169.39.26', 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWEVSWVRMbVExOGdkVGlrWUR3ZEtBQXgzU2hVTlg3SFo3SWxVbjczQSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755247402),
('Y4xo38rk3hfg0vPeTK8KDJiEsw03nCnPUFD3FrBm', NULL, '44.249.166.219', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNm4yZmNqMWxsUjJTMlVpMEVDVVR5S1doRDB4MVFCeGU0WVlNNXExSyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755027516),
('ydGfpr1hfKM05xtpq1AbiSSqRe5oMGYyG7Ix1JjW', NULL, '206.168.34.74', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR1JqbU1Xek1OWk5DaFN3Rzh1anZ2dmFBZDZmVzNpaEd3Qjdmbjl0RSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755319335),
('yKcxdI2C3vw2pJaqseT8zpBMgDE6KaKK4GYuUl78', NULL, '206.168.34.116', 'Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoid1VNT3hqdjZnRlE4ZXB3RGFNYVBBM0ROdEFKbVhjUVVwRWxCM0lGNSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vYWRtaW4xLmNwYi1kb3VhbGEuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1755323857),
('yW5g1ZzhXZ0LmE3PxD9Eq60KyLZBVEIWItjZ28fK', NULL, '143.105.152.61', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMDYzNXBPdE5zWmh1SnFVb3BLOHhKbjM4cE84Uk4wSXhpQUU3c0t4VCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1755027131),
('ZHd2ABg4XDmmhAAwCgFuDHAsMKKuo9IQehAtCha8', NULL, '185.177.72.12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWFROWGx5ZVFoVEhqV3VHekdleUowYUNidzI0SU1nalNlRVc1TWw3TCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly9hZG1pbjEuY3BiLWRvdWFsYS5jb20vP3BwPWVudiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1755216691);

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
  `staff_type` enum('teacher','accountant','supervisor','admin','secretaire','bibliothecaire') DEFAULT NULL COMMENT 'Type de personnel',
  `work_hours` decimal(5,2) DEFAULT NULL COMMENT 'Heures de travail effectuées',
  `late_minutes` int(11) NOT NULL DEFAULT 0 COMMENT 'Minutes de retard',
  `early_departure_minutes` int(11) NOT NULL DEFAULT 0 COMMENT 'Minutes de départ anticipé',
  `notes` text DEFAULT NULL COMMENT 'Notes ou observations',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(3, 'BAHA NDJOM JEAN MARIE', 'JEAN MARIE', 'BAHA NDJOM', '2017-02-02', 'Douala', 'M', 'BAHA', '+237690581731', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 07:47:58', '2025-08-04 07:47:58', 1, '20250002', 2, 0),
(4, 'AISSATOU DJOUVOULDA AISSATOU', 'AISSATOU', 'AISSATOU DJOUVOULDA', '2013-10-03', 'Douala', 'F', 'AISSATOUT', '+237699998727', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 07:50:41', '2025-09-01 12:06:30', 1, '20250003', 3, 0),
(6, 'DJAMEN SANI ROISSY KARLE', 'ROISSY KARLE', 'DJAMEN SANI', '2011-05-15', 'DOUALA', 'M', 'DJAMEN', '+237652409600', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 08:05:06', '2025-09-01 05:52:11', 1, '20250005', 1, 0),
(8, 'APINA KAMDEM JACQUES LEDOUX', 'JACQUES LEDOUX', 'APINA KAMDEM', '2009-04-14', 'Douala', 'M', 'APINA PASCAL', '+237696608079', NULL, NULL, NULL, NULL, NULL, NULL, 20, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 08:26:17', '2025-08-04 09:25:06', 1, '20250007', 2, 0),
(10, 'APINA APINA CHRISTIAN PASCAL', 'CHRISTIAN PASCAL', 'APINA APINA', '2011-10-12', 'DOUALA', 'M', 'APINA', '+237696608079', NULL, NULL, NULL, NULL, NULL, NULL, 20, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 09:23:49', '2025-08-04 09:25:06', 1, '20250009', 1, 0),
(11, 'SANTIA MINNA MANUELLA', 'MANUELLA', 'SANTIA MINNA', '2012-08-25', 'GUIBI', 'F', 'SANTIA', '+237699998727', NULL, NULL, NULL, NULL, NULL, NULL, 20, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 09:28:56', '2025-08-04 09:28:56', 1, '20250010', 3, 0),
(12, 'ALI ALHADJI ADAMOU', 'ADAMOU', 'ALI ALHADJI', '2009-05-12', 'DOUALA', 'M', 'ADAMOU', '+237655605530', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'old', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 10:11:13', '2025-08-04 10:11:13', 1, '20250011', 2, 0),
(13, 'TYUE EYOLE VICTOR RICHARD', 'VICTOR RICHARD', 'TYUE EYOLE', '2012-09-19', 'BANGONG', 'M', 'EYOLE LINUS', '656925922', NULL, NULL, NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 11:22:39', '2025-08-04 12:37:02', 1, '20250012', 1, 0),
(17, 'ANGELIQUE VERONIQUE ESTHER ZAMATONGUI IVONNE', 'ZAMATONGUI IVONNE', 'ANGELIQUE VERONIQUE ESTHER', '2006-06-22', 'BANGUI', 'F', 'ZAMA MATHIEU', '+237651933730', NULL, NULL, NULL, NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 12:54:58', '2025-08-04 12:56:58', 1, '20250015', 2, 0),
(18, 'MBOLO TCHOUDIKOA EVRAD HARDEN', 'EVRAD HARDEN', 'MBOLO TCHOUDIKOA', '2014-12-17', 'DOUALA', 'M', 'TCHOUDIKOA EBECAF EDDY', '+237674950037', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 13:15:11', '2025-08-04 13:15:11', 1, '20250016', 4, 0),
(19, 'ZAMA NKONGBO ANGELA', 'ANGELA', 'ZAMA NKONGBO', '2006-06-22', 'BAMGUI', 'F', 'ZAMA MATHIEU', '697620655', NULL, NULL, NULL, NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 13:30:30', '2025-08-04 13:30:30', 1, '20250017', 3, 0),
(20, 'ZAMA DEGRENDE BONHEUR DAVID', 'BONHEUR DAVID', 'ZAMA DEGRENDE', '2009-07-01', 'Bangui', 'M', 'Zama Mathieu', '697620655', NULL, NULL, NULL, NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-04 14:00:02', '2025-08-04 14:00:02', 1, '20250018', 1, 0),
(22, 'KATOUA DJOCOTNA OBET', 'DJOCOTNA OBET', 'KATOUA', '2008-07-07', 'GOBO', 'M', 'DJOCOTNA PROSPER', '+23691235539', NULL, NULL, NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 08:30:11', '2025-08-06 08:30:11', 1, '25A00001', 2, 0),
(23, 'SEVIDZEM ADEL NYUYKONGMO', 'ADEL NYUYKONGMO', 'SEVIDZEM', '2014-05-30', 'KUMBO', 'F', 'NGAH ELIAS', '+237653264071', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 08:47:52', '2025-08-06 08:50:22', 1, '25A00002', 1, 1),
(24, 'MBEDE CHYREL ARNOLD', 'CHYREL ARNOLD', 'MBEDE', '2008-10-27', 'YAOUNDE', 'M', 'MBOUTOUH ERIC', '+237692360421', NULL, NULL, NULL, NULL, 'students/photos/student_25A00003_1754513402.png', NULL, 86, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 09:06:15', '2025-08-11 07:01:13', 1, '25A00003', 1, 0),
(25, 'NDANA DJOGUIRA SILVESTRE', 'SILVESTRE', 'NDANA DJOGUIRA', '2006-12-13', 'NDJAMENA', 'M', 'NDANA FELIX', '+237688675350', NULL, NULL, NULL, NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 09:32:33', '2025-08-06 09:32:33', 1, '25A00004', 2, 0),
(26, 'YEUMOU ANGE', 'ANGE', 'YEUMOU', '2009-09-30', 'DOUALA', 'F', 'YEUMO ARNAUD CHARLY', '+237699632295', NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 09:42:05', '2025-08-06 09:42:05', 1, '25A00005', 1, 0),
(30, 'ABADA EKOUMA NELIE FAYELLE', 'NELIE FAYELLE', 'ABADA EKOUMA', '2010-09-27', 'NDAMVO', 'F', 'EKOUMA AMBASSA', '+237676597753', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 10:32:44', '2025-08-06 10:32:44', 1, '25A00009', 2, 0),
(31, 'JAIDZELA VERMA', 'VERMA', 'JAIDZELA', '2006-11-14', 'KUMBO', 'M', 'NGAH ELIAS', '+237653264071', NULL, NULL, NULL, NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 10:39:45', '2025-08-06 10:39:45', 1, '25A00010', 1, 0),
(32, 'ESSOME MBOCK FRANCINE', 'MBOCK FRANCINE', 'ESSOME', '2007-04-28', 'DOUALA', 'M', 'BOCK SAMUEL LEDOU', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 11:36:39', '2025-08-06 11:36:39', 1, '25A00011', 2, 0),
(34, 'MBONG BOAL AMBO CECILE LAURENTINE', 'CECILE LAURENTINE', 'MBONG BOAL AMBO', '2008-05-07', 'KON', 'F', 'ASSOL ALICE', '677572470', NULL, NULL, NULL, NULL, NULL, NULL, 86, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 13:32:53', '2025-08-06 14:30:15', 1, '25A00013', 2, 0),
(36, 'WELLIMUM MBOUTOUH NICOLES', 'NICOLES', 'WELLIMUM MBOUTOUH', '2012-05-22', 'YAOUNDE', 'M', 'MBOUTOUH ERIC', '691360421', NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 14:13:17', '2025-08-06 14:13:17', 1, '25A00014', 1, 0),
(37, 'TANGMO DOUANLA ANGE INDIRA', 'ANGE INDIRA', 'TANGMO DOUANLA', '2012-05-16', 'MBOUDA', 'F', 'DOUANLA SERGE', '695776742', NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 14:35:35', '2025-08-06 14:50:07', 1, '25A00015', 2, 0),
(38, 'KENBANG DJIMELI YANN AIME', 'YANN AIME', 'KENBANG DJIMELI', '2011-09-29', 'BAMBI', 'M', 'DJIMELI BEAUCLAIR', '655275664', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 14:44:32', '2025-08-06 14:44:32', 1, '25A00016', 3, 0),
(39, 'DJEPANG ATATWA AMANDA GABRILLA', 'AMANDA GABRILLA', 'DJEPANG ATATWA', '2012-03-13', 'DOUALA', 'F', 'ATATWA JOSEE', '699159523', NULL, NULL, NULL, NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-06 14:58:16', '2025-08-06 14:58:16', 1, '25A00017', 1, 0),
(41, 'Douanla Arthur Joel', 'Joel', 'Douanla Arthur', '2011-01-02', 'Mbouda', 'M', 'Douanla serge', '695776742/695360102', NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:10:54', '2025-08-07 11:10:54', 1, '25A00019', 3, 0),
(42, 'ESSOH MOUMKOUM EMMANUEL', 'MOUMKOUM EMMANUEL', 'ESSOH', '2014-06-14', 'FOUMBOT', 'M', 'MOUMKOUM ARNMOS', '+237658556255', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:18:11', '2025-08-07 11:20:47', 1, '25A00020', 2, 1),
(43, 'NTADA MOUMKOUM RAISSA', 'MOUMKOUM RAISSA', 'NTADA', '2011-11-12', 'FOUMBOT', 'F', 'MOUMKOUM ARNMOS', '658556255', NULL, NULL, NULL, NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:27:13', '2025-08-07 11:27:13', 1, '25A00021', 1, 0),
(45, 'KAMGA NIEMEJI CHRIST MESSI', 'CHRIST MESSI', 'KAMGA NIEMEJI', '2014-10-23', 'DOUALA', 'M', 'NIEMEJI STEPHANE ERNESS', '+237679805772', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:33:01', '2025-08-07 11:33:01', 1, '25A00023', 5, 1),
(46, 'TOGODNE PODWE EMMANUELLE', 'PODWE EMMANUELLE', 'TOGODNE', '2014-06-24', 'DOUALA', 'M', 'PODWE', '+237699308696', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:42:34', '2025-08-07 11:42:34', 1, '25A00024', 6, 0),
(48, 'MEDOM YOUSSI ELISABETH LAURE', 'ELISABETH LAURE', 'MEDOM YOUSSI', '2015-10-13', 'DOUALA', 'F', 'YOUSSI EMMANUEL', '696663063', NULL, 'TCHUENCHE TENTCHOUENG STEPHANIE SANDRINE', NULL, NULL, NULL, NULL, 77, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 11:47:35', '2025-08-27 12:55:32', 1, '25A00026', 1, 0),
(49, 'ABOUBAKAR ALHADJI ADAMOU', 'ALHADJI ADAMOU', 'ABOUBAKAR', '2012-03-14', 'YAOUNDE', 'M', 'ADAMOU MOUHAMADOU', '655605530', NULL, NULL, NULL, NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 12:01:47', '2025-08-07 12:01:47', 1, '25A00027', 1, 0),
(51, 'Ngassa tchoua Paul Cyril', 'Paul Cyril', 'Ngassa tchoua', '2014-03-22', 'Douala', 'M', 'Tchouami njiontchou Bertrand', '677529567', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 12:46:09', '2025-08-07 12:46:09', 1, '25A00029', 7, 1),
(52, 'Watat  tchoua Axel joel', 'Axel joel', 'Watat  tchoua', '2012-07-27', 'Douala', 'M', 'Tchouami  njiontchou Bertrand', '677529567', NULL, NULL, NULL, NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 12:58:09', '2025-08-07 12:58:09', 1, '25A00030', 1, 0),
(54, 'Foko shammah Emmanuel', 'Emmanuel', 'Foko shammah', '2015-05-31', 'Douala', 'M', 'Foko sammuel', '697444836', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-07 13:38:11', '2025-08-07 13:38:11', 1, '25A00032', 3, 1),
(61, 'NYA TCHAKOUNTE PRINCESSE ARIANE', 'PRINCESSE ARIANE', 'NYA TCHAKOUNTE', '2014-06-29', 'DOUALA', 'F', 'TCHAKOUNTE BRUNO', '653746947', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-11 06:48:12', '2025-08-11 06:48:12', 1, '25A00037', 9, 1),
(62, 'ASSAGA THONG DITERLINE LAURENTINE', 'DITERLINE LAURENTINE', 'ASSAGA THONG', '2013-12-29', 'DOUALA', 'F', 'THONG PHILIPPE CLEMENT', '696752501', NULL, 'NGO NYEMB GENEVIEVE SYLVIE', '693245916', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-12 06:11:10', '2025-08-29 10:25:23', 1, '25A00038', 4, 1),
(65, 'AFANA NDONGO INGRID ELISABETH', 'INGRID ELISABETH', 'AFANA NDONGO', '2009-11-30', 'DOUALA', 'F', 'NDONGO SIMON', '699972503', NULL, NULL, NULL, NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-12 07:16:45', '2025-08-12 07:16:45', 1, '25A00041', 2, 0),
(66, 'FOKO SHAMMAH DANIELLE', 'DANIELLE', 'FOKO SHAMMAH', '2013-06-27', 'DOUALA', 'F', 'FOKO SAMMUEL', '673876973', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-12 10:50:20', '2025-08-12 10:50:20', 1, '25A00042', 5, 1),
(67, 'NKOLO BEYALA GISLENE', 'GISLENE', 'NKOLO BEYALA', '2008-07-04', 'DOUALA', 'F', 'NKOLO JOSEPH DESIRE', '695300417', NULL, 'MBALLA MARLISE LOUISETTE', '.', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-12 10:55:59', '2025-08-28 08:21:56', 1, '25A00043', 1, 0),
(69, 'KEUMOU ANGE', 'ANGE', 'KEUMOU', '2014-10-20', 'DOUALA', 'F', 'NFEUFEN OUSSENI', '690904210', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-15 06:06:31', '2025-08-15 06:06:31', 1, '25A00045', 10, 1),
(72, 'MBOA ELISABETH KENDRA', 'ELISABETH KENDRA', 'MBOA', '2009-01-21', 'DOUALA', 'F', 'KOUKA CHRISTIAN EMMANUEL', '698777843', NULL, NULL, NULL, NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-15 06:25:56', '2025-08-15 06:25:56', 1, '25A00048', 3, 0),
(73, 'MBELAMA MANAOUDA', 'MANAOUDA', 'MBELAMA', '2010-09-10', 'BAO-TASAÏ', 'M', 'MANAOUDA GABRIEL', '699367288', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-15 06:34:36', '2025-08-15 06:34:36', 1, '25A00049', 3, 0),
(76, 'MUSSIMA NBWANGA RHODES PRUNELLE', 'RHODES PRUNELLE', 'MUSSIMA NBWANGA', '2008-06-20', 'DOUALA', 'F', 'NBWANGA OSCAR LIBERTE', '.', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 10:00:03', '2025-08-19 10:00:03', 1, '25A00051', 5, 0),
(77, 'EANG ANGE MURIELLE', 'ANGE MURIELLE', 'EANG', '2008-09-29', 'MBOT MAKAK', 'F', 'EANG JEAN PAUL', '699736235', NULL, NULL, NULL, NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:04:31', '2025-08-19 14:04:31', 1, '25A00052', 3, 0),
(79, 'YAMAPI TCHASSI LA COMPTESSE DIVINE', 'LA COMPTESSE DIVINE', 'YAMAPI TCHASSI', '2012-02-20', 'DOUALA', 'F', 'SANI TCHASSI JOSEPH DURANT', '696427010', NULL, NULL, NULL, NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:14:40', '2025-08-19 14:14:40', 1, '25A00054', 1, 0),
(80, 'DONGMO DANIE AIMEE', 'DANIE AIMEE', 'DONGMO', '2011-08-25', 'BAFOU', 'F', 'NGUEFACK MARC', '675981670', NULL, NULL, NULL, NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:17:49', '2025-08-19 14:17:49', 1, '25A00055', 1, 0),
(81, 'BERTHE KHADIDJA AMOU', 'KHADIDJA AMOU', 'BERTHE', '2009-09-25', 'DOUALA', 'F', 'BERTHE SALLAHA', '.', NULL, NULL, NULL, NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:21:12', '2025-08-19 14:21:12', 1, '25A00056', 2, 0),
(82, 'KAMENI KEN BRIGHT MELVIN', 'BRIGHT MELVIN', 'KAMENI KEN', '2014-06-06', 'DOUALA', 'M', 'KAMENI TCHONAMANI NARCISSE HERVE', '672671898', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:26:29', '2025-08-19 14:26:29', 1, '25A00057', 12, 0),
(83, 'KAMENI KAMAGO ANAYA ELFRIED', 'ANAYA ELFRIED', 'KAMENI KAMAGO', '2013-02-02', 'DOUALA', 'F', 'KAMENI TCHOUAMANI NARCISSE HERVE', '677445610', NULL, NULL, NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:34:38', '2025-08-19 14:34:38', 1, '25A00058', 3, 0),
(84, 'DIEDIFFO LOÏC DJOKAEFF', 'LOÏC DJOKAEFF', 'DIEDIFFO', '2010-09-20', 'MBOUDA', 'M', 'DIEDIFFO SHAGUE ERIC LAMBERT', '671086946', NULL, NULL, NULL, NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:38:30', '2025-08-19 14:38:30', 1, '25A00059', 2, 0),
(86, 'EDJANGHA TETIO MARCK RICK FREYD', 'MARCK RICK FREYD', 'EDJANGHA TETIO', '2015-12-25', 'NYETE', 'M', 'TETIO NGUETSA MELVIS DUCLAIR', '697476049', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:47:19', '2025-08-19 14:47:19', 1, '25A00060', 6, 1),
(87, 'JUINE DARELLE', 'DARELLE', 'JUINE', '2009-09-13', 'DOUALA', 'F', 'ALOMENWING WILSON NDIANG FOH', '.', NULL, NULL, NULL, NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:51:56', '2025-08-19 14:51:56', 1, '25A00061', 3, 0),
(88, 'NYABEYEU TCHETMI JORDANNE', 'JORDANNE', 'NYABEYEU TCHETMI', '2009-12-02', 'DOUALA', 'F', 'NYABEYEU NKOMTCHOUA DAGOBERT', '670597980', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-19 14:59:58', '2025-08-19 14:59:58', 1, '25A00062', 6, 0),
(89, 'FOTSO KEGNE JORANE KINSLEY', 'JORANE KINSLEY', 'FOTSO KEGNE', '2012-05-23', 'BAFOUSSAM', 'M', 'OUAGNE FOTSO', '691839008', NULL, NULL, NULL, NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 06:25:49', '2025-08-20 06:25:49', 1, '25A00063', 5, 0),
(90, 'NGUIAMBOP PRINCESSE KARNI', 'PRINCESSE KARNI', 'NGUIAMBOP', '2013-01-30', 'DOUALA', 'F', 'NDEPPAFI MOUANAH ISIDORE', '652033637', NULL, NULL, NULL, NULL, NULL, NULL, 73, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 06:30:16', '2025-08-20 06:30:16', 1, '25A00064', 1, 0),
(91, 'BILOUNGA MANUELA RAMATOU', 'MANUELA RAMATOU', 'BILOUNGA', '2012-03-10', 'YAOUNDE', 'F', 'HAMISSOU HAMZA', '655426778', NULL, NULL, NULL, NULL, NULL, NULL, 72, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:22:20', '2025-08-20 07:22:20', 1, '25A00065', 4, 0),
(92, 'BIKONDO KOUOH FALESKA OLIVIA', 'FALESKA OLIVIA', 'BIKONDO KOUOH', '2011-07-15', 'YAOUNDE', 'F', 'KOUOH MARC ANDRE PASCAL', '695840755', NULL, NULL, NULL, NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:27:43', '2025-08-20 07:27:43', 1, '25A00066', 2, 0),
(93, 'PRECIOUS QUINTA KEYONYUI', 'KEYONYUI', 'PRECIOUS QUINTA', '2009-02-08', 'BAMUNKA URBAN HEALTH CENTRE', 'F', 'NGWALEH ELVIS', '650653602', NULL, 'BONWONG BERTILLA', NULL, NULL, NULL, NULL, 101, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:43:42', '2025-08-27 08:40:58', 1, '25A00067', 3, 0),
(94, 'ELTEN CHRIS FUAYEH NGWALEH', 'FUAYEH NGWALEH', 'ELTEN CHRIS', '2011-05-21', 'BAMUNKA URBAN H/CENTRE', 'M', 'NGWELEH ELVIS', '650653602', NULL, 'BOWONG BERTILA NGWALEH', NULL, NULL, NULL, NULL, 61, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:47:35', '2025-08-27 08:34:57', 1, '25A00068', 1, 0),
(95, 'MEVOA ESSILA PRUDENCE AUDREY', 'PRUDENCE AUDREY', 'MEVOA ESSILA', '2008-04-22', 'YAOUNDE', 'F', 'KOUOH MARC ANDRE', '675406012', NULL, NULL, NULL, NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:51:28', '2025-08-20 07:51:28', 1, '25A00069', 1, 0),
(96, 'PIEJION MAGNE PRINCESSE CHERIDANN', 'PRINCESSE CHERIDANN', 'PIEJION MAGNE', '2008-01-05', 'DOUALA', 'F', 'PIEJION ROBERT', '677406414', NULL, NULL, NULL, NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:54:53', '2025-08-20 07:54:53', 1, '25A00070', 4, 0),
(97, 'TAGNI TCHETGNIA EMMANUELLE DOMINIQUE', 'EMMANUELLE DOMINIQUE', 'TAGNI TCHETGNIA', '2008-04-01', 'DOUALA', 'F', 'TAGNI TOMDOMNOU HEBRAD JOEL', '699917047', NULL, NULL, NULL, NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 07:59:49', '2025-08-20 07:59:49', 1, '25A00071', 2, 0),
(98, 'MENGAPTCHE DEUSSIDJI PATRICIA FORTUNE', 'PATRICIA FORTUNE', 'MENGAPTCHE DEUSSIDJI', '2010-05-27', 'BANA', 'F', 'DEUSSIDJI MONTHE MARTIAL', '694019010', NULL, NULL, NULL, NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 08:06:29', '2025-08-20 08:06:29', 1, '25A00072', 2, 0),
(99, 'FOLONG JOYS PERLITA', 'JOYS PERLITA', 'FOLONG', '2014-09-14', 'DOUALA', 'F', 'MEGOUE MICHAEL', '673047805', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 08:24:13', '2025-08-20 08:24:13', 1, '25A00073', 13, 1),
(100, 'GANKAM MAYET GABRIELLA COLLETE', 'GABRIELLA COLLETE', 'GANKAM MAYET', '2014-02-13', 'BONABERI-DOUALA', 'F', 'GANKAM DJONONSI SERGE PATRICK', '671223045', NULL, NULL, NULL, NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:26:53', '2025-08-20 09:26:53', 1, '25A00074', 7, 1),
(101, 'NDIKI TCHOUPE DAVID EMMANUEL', 'DAVID EMMANUEL', 'NDIKI TCHOUPE', '2013-03-02', 'YAOUNDE', 'M', 'TCHOUPE BRICE', '653469995', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:31:00', '2025-08-20 09:31:00', 1, '25A00075', 14, 1),
(102, 'DAYON PIEJION NEHEMIE GRACE', 'NEHEMIE GRACE', 'DAYON PIEJION', '2010-09-17', 'DOUALA', 'F', 'PIEJION ROBERT', '677406414', NULL, NULL, NULL, NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:38:48', '2025-08-20 09:38:48', 1, '25A00076', 1, 0),
(103, 'OUMBENOU MANUELLA ELENHORE', 'MANUELLA ELENHORE', 'OUMBENOU', '2012-05-15', 'DOUALA', 'F', 'OUMBENOU JEAN PIERRE', '675056614', NULL, NULL, NULL, NULL, NULL, NULL, 108, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:43:05', '2025-08-20 09:43:05', 1, '25A00077', 1, 0),
(104, 'LOUGHA BAYIHA HELENE GRACIELLA', 'HELENE GRACIELLA', 'LOUGHA BAYIHA', '2013-09-06', 'DOUALA', 'F', 'BAYIHA YEBGA GUY NESTOR', '696913377', NULL, NULL, NULL, NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 09:51:01', '2025-08-20 09:51:01', 1, '25A00078', 2, 1),
(105, 'WANDJI NJIKE ASHLEY BRENDA', 'ASHLEY BRENDA', 'WANDJI NJIKE', '2009-03-10', 'DOUALA', 'F', 'NJIKE TAMBIA CARLOS', '699938438', NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:02:10', '2025-08-20 10:02:10', 1, '25A00079', 4, 0),
(106, 'KENGNE NKAKEH MORGANE CHLOE', 'MORGANE CHLOE', 'KENGNE NKAKEH', '2005-05-31', 'YAOUNDE', 'F', 'KAMBOU RODOLPHE', '.', NULL, NULL, NULL, NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:05:36', '2025-08-20 10:05:36', 1, '25A00080', 3, 0),
(107, 'TCHAKOUTIO TENDON PATRICIA NOELLE', 'PATRICIA NOELLE', 'TCHAKOUTIO TENDON', '2005-01-03', 'BAZOU', 'F', 'TENDON MARTIN', '699801798', NULL, NULL, NULL, NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:10:44', '2025-08-20 10:10:44', 1, '25A00081', 4, 0),
(108, 'BAGOUP MBIABOUO EMILIENE ROSETTE', 'EMILIENE ROSETTE', 'BAGOUP MBIABOUO', '2010-07-19', 'MBOUO', 'F', 'MBIABOUO NZOUTOM DANIEL DORE', '699801798', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:22:21', '2025-08-20 10:22:21', 1, '25A00082', 4, 0),
(109, 'YOBA MBIABOUO SILAS ABED-NEGO', 'SILAS ABED-NEGO', 'YOBA MBIABOUO', '2012-04-04', 'NKONGSAMBA', 'M', 'MBIABOUO NZOUTOM DANIEL DORE', '699801798', NULL, NULL, NULL, NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 10:59:26', '2025-08-20 10:59:26', 1, '25A00083', 3, 0),
(110, 'NGUEMO DJIONKOU PRINCESS DIVINE', 'PRINCESS DIVINE', 'NGUEMO DJIONKOU', '2015-09-30', 'LOUM', 'F', 'DJIONKOU ROMUED BIENVENU', '653406687', NULL, NULL, NULL, NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:02:26', '2025-08-20 11:02:26', 1, '25A00084', 3, 1),
(111, 'MENYE MARIE THERESE ROSE', 'MARIE THERESE ROSE', 'MENYE', '2008-01-10', 'YAOUNDE', 'F', 'NADJIBE CLEMENT', '690229637', NULL, NULL, NULL, NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:04:58', '2025-08-20 11:04:58', 1, '25A00085', 2, 0),
(112, 'NTOUOMAMO MOUNIR EL MADHI', 'EL MADHI', 'NTOUOMAMO MOUNIR', '2013-06-02', 'DOUALA', 'M', 'NTOUOMAMO YALOUBA', '698799565', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:08:23', '2025-08-20 11:08:23', 1, '25A00086', 15, 1),
(114, 'MAKEUNE ELOKO LUCIA PASCAL', 'LUCIA PASCAL', 'MAKEUNE ELOKO', '2013-05-28', 'DOUALA', 'F', 'ELOKO ACHILLE', '699029198', NULL, NULL, NULL, NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:18:13', '2025-08-20 11:18:13', 1, '25A00088', 1, 0),
(115, 'AMBIAGA BRIGITTE', 'BRIGITTE', 'AMBIAGA', '2012-06-19', 'BEGNI-BOKITO', 'F', 'BOGNOMO OLI', '675356061', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:21:55', '2025-08-20 11:21:55', 1, '25A00089', 16, 1),
(116, 'ADJAWO MABIEME MIRABELLE', 'MIRABELLE', 'ADJAWO MABIEME', '2013-10-05', 'CSI DE DJAPOSTEN', 'F', 'MABIEME CHARLY CONSTANT', '696955544', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:25:15', '2025-08-20 11:25:15', 1, '25A00090', 17, 1),
(117, 'BAGNEKI BALEMAGNA MORGAN EDELL', 'MORGAN EDELL', 'BAGNEKI BALEMAGNA', '2010-04-25', 'DOUALA', 'M', 'BALEMAGNA BETONDE VALENTIN', '674527325', NULL, NULL, NULL, NULL, NULL, NULL, 61, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:30:05', '2025-08-20 11:30:05', 1, '25A00091', 2, 0),
(118, 'BIKELE MBALLA DANIEL RICHESSE', 'DANIEL RICHESSE', 'BIKELE MBALLA', '2011-01-11', 'DOUALA', 'M', 'MBALLA DIDIER', '699676334', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:33:45', '2025-08-20 11:33:45', 1, '25A00092', 18, 1),
(119, 'BESSALA KOUOH JOSEPH STEPHANE', 'JOSEPH STEPHANE', 'BESSALA KOUOH', '2014-10-19', 'DOUALA', 'M', 'KOUOH MARC ANDRE PASCAL', '675406012', NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 11:36:51', '2025-08-20 11:36:51', 1, '25A00093', 19, 1),
(120, 'ASSOAK AMPI ALEXANDRE MATHIEU', 'ALEXANDRE MATHIEU', 'ASSOAK AMPI', '2009-08-16', 'BERTOUA', 'M', '.', '657246949', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 12:23:19', '2025-08-20 12:23:19', 1, '25A00094', 5, 0),
(121, 'BESSOUE KOUNOU YANNICELLE ALIDA', 'YANNICELLE ALIDA', 'BESSOUE KOUNOU', '2008-04-06', 'MBANGASSINA', 'F', 'BESSOUE TSANGO DJIOUKOU', '695483200', NULL, NULL, NULL, NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 12:26:26', '2025-08-20 12:26:26', 1, '25A00095', 7, 0),
(122, 'NDOUMBE MINKA NDOLOM LESHA', 'LESHA', 'NDOUMBE MINKA NDOLOM', '2012-05-22', 'DOUALA', 'F', 'NDOLOM JACQUES DIDIER', '699973557', NULL, NULL, NULL, NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 12:29:48', '2025-08-20 12:29:48', 1, '25A00096', 1, 0),
(123, 'DEJBAÏ MIGUEL', 'MIGUEL', 'DEJBAÏ', '2009-03-02', 'BAO-TASSAÏ', 'F', 'MANAOUDA GABRIEL', '699367288', NULL, NULL, NULL, NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 13:51:57', '2025-08-20 13:51:57', 1, '25A00097', 6, 0),
(124, 'ARBOUTOU WAYA KANKAO FRANCIS', 'FRANCIS', 'ARBOUTOU WAYA KANKAO', '2009-03-14', 'FOTOKOL', 'M', 'BADORA KANKAO', '699172812', NULL, NULL, NULL, NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 14:00:46', '2025-08-20 14:00:46', 1, '25A00098', 1, 0),
(125, 'ETUKA SANDJO HARRY STEWART TRESOR', 'HARRY STEWART TRESOR', 'ETUKA SANDJO', '2013-11-03', 'DOUALA', 'M', 'SANDJO ETUKA MARTIAL BORIS', '.', NULL, NULL, NULL, NULL, NULL, NULL, 100, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 14:05:14', '2025-08-20 14:05:14', 1, '25A00099', 1, 0),
(126, 'NGOUMEZO MADADJEU LEANA ZEJOU', 'LEANA ZEJOU', 'NGOUMEZO MADADJEU', '2014-05-04', 'TRENTO', 'F', '.', '677700420', NULL, NULL, NULL, NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-20 14:09:20', '2025-08-20 14:09:20', 1, '25A00100', 3, 0),
(127, 'LUCK\'S BRIGHT VONYUI NGAH', 'VONYUI NGAH', 'LUCK\'S BRIGHT', '2013-01-22', 'BAMUNKA URBAN HEALTH CENTRE', 'M', 'ELVIS NGWALEH', '671162880', NULL, 'BONWONG BERTILLA', '650653602', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-26 11:07:40', '2025-08-27 08:38:45', 1, '25A00101', 1, 0),
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
(143, 'CLARA MANKA\'A NDOUTOU CLARA', 'CLARA', 'CLARA MANKA\'A NDOUTOU', '2010-12-01', 'DOUALA', 'F', 'MBONG NDOUTOU JOSEPH', NULL, NULL, 'LILLIAN NCHANG NGWA', '675641941', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 10:52:07', '2025-08-27 10:52:07', 1, '25A00117', 7, 0),
(144, 'EKOMI NNA ANGE GABRIELLE', 'ANGE GABRIELLE', 'EKOMI NNA', '2014-09-16', 'EBOLOWA', 'M', 'EKOMI NNA SAPEUR', '690530087', NULL, 'BIKOMO MEBARA ULRICH DANA', '675859920', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:00:32', '2025-08-27 11:08:31', 1, '25A00118', 20, 1),
(145, 'NGO NTEP ANNE SEGOLENE', 'ANNE SEGOLENE', 'NGO NTEP', '2007-06-02', 'YAOUNDE', 'F', 'NTEP BENJAMIN', '699367007', NULL, 'NGO NGAMBI ANNE NICOLE', '674815569', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:14:07', '2025-08-27 11:14:07', 1, '25A00119', 3, 0),
(146, 'NGOUG MANANG EMMANUEL MARVIN', 'EMMANUEL MARVIN', 'NGOUG MANANG', '2013-04-14', 'YAOUNDE', 'M', 'NGOUG MANANG IVAN', NULL, NULL, 'BEDIAM SUZANNE ALBERTINE', '656370690', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:21:18', '2025-08-27 11:21:18', 1, '25A00120', 5, 0),
(147, 'KATCHO NZIMENI MARYPHEV JANAI', 'MARYPHEV JANAI', 'KATCHO NZIMENI', '2014-08-06', 'BAFANG', 'F', 'NZIMENI WATANA LANDRY', '691513891', NULL, 'GUEGA LUCRECE VERAH', '699735483', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:28:17', '2025-08-27 11:28:17', 1, '25A00121', 6, 0),
(148, 'MPACKO NKOUWANG MELVINE GRACE', 'MELVINE GRACE', 'MPACKO NKOUWANG', '2015-08-15', 'DOUALA', 'F', 'NKOUWANG CYRELLE JORDAN', '.', NULL, 'ESSEBE EHAWEL EWANE CHARLOTTE', '671561034', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 11:38:35', '2025-08-27 11:38:35', 1, '25A00122', 21, 0),
(149, 'FANSU DONGMA LYNN', 'LYNN', 'FANSU DONGMA', '2010-07-16', 'BAFANG', 'F', 'DONGMA SIMPLICE', '695894257', NULL, 'MEKAMGUEN KOUAMO', '650133365', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:14:21', '2025-08-27 12:14:21', 1, '25A00123', 9, 0),
(150, 'PIDA HABIBA ZENABOU', 'ZENABOU', 'PIDA HABIBA', '2010-04-09', 'DOUALA', 'F', 'PIDA NTONGA BERNARD', '.', NULL, 'HABIBA MOHAMAN', '686263083', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:22:18', '2025-08-27 12:22:18', 1, '25A00124', 10, 0),
(151, 'DJOB A HOLA BARRACK', 'BARRACK', 'DJOB A HOLA', '2011-08-03', 'DOUALA', 'M', 'PONDI BRUNO', '686778580', NULL, 'NDZENGUE EKANI ANASTASIE', '696113693', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:26:01', '2025-08-27 12:26:01', 1, '25A00125', 5, 0),
(152, 'MAKUEATE TSAFACK LUCRESS', 'LUCRESS', 'MAKUEATE TSAFACK', '2013-01-11', 'DOUALA', 'F', 'TSAFACK GUY ERIC', '677724022', NULL, 'KIAMPI ELWIGE', '651439356', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:37:56', '2025-08-27 12:39:13', 1, '25A00126', 8, 1),
(153, 'ZE ATANGANA MARIE PAULE SAMIRA', 'MARIE PAULE SAMIRA', 'ZE ATANGANA', '2014-07-07', 'YAOUNDE', 'F', 'ATANGANA ATANGANA EMMANUEL', '.', NULL, 'TSAMA AVA MARIE JUSTINE', '656940269', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:42:25', '2025-09-02 07:20:19', 1, '25A00127', 50, 1),
(154, 'MOHAMED SALI', 'SALI', 'MOHAMED', '2012-06-20', 'YAOUNDE', 'M', 'SALI NDEKGOUA', '696933493', NULL, 'ZAKIATOU AMADOU', '658230076', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:46:08', '2025-08-27 12:46:08', 1, '25A00128', 22, 1),
(155, 'BOGNOUA DJOUATSA DJAMILA LINE', 'DJAMILA LINE', 'BOGNOUA DJOUATSA', '2015-11-08', 'SANTCHOU', 'F', 'KENGNI DJOUATSA NELSON DIDERO', '652042995', NULL, 'KENFACK CLAURETTE', '671849805', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:50:53', '2025-08-27 12:50:53', 1, '25A00129', 9, 1),
(156, 'KEGNE TELLA PATRICIA ORNELLA', 'PATRICIA ORNELLA', 'KEGNE TELLA', '2013-01-04', 'BAFOUSSAM', 'F', 'TELLA PIERRE EMMANUELLA', NULL, NULL, 'SANDGEU FOKA ALLIANCE', '650872506', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 12:59:12', '2025-08-27 12:59:12', 1, '25A00130', 23, 1),
(157, 'PANIWELE M\'MANDOA MANUELE LAURA', 'MANUELE LAURA', 'PANIWELE M\'MANDOA', '2013-02-11', 'DOUALA', 'F', 'M\'MANDOA MICHEL', '.', NULL, 'BETIBIGUE YOLA JOSEPHINE', '654394794', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:07:51', '2025-08-27 13:07:51', 1, '25A00131', 24, 1),
(158, 'EKONDO MMANDOA RODRIGUE', 'RODRIGUE', 'EKONDO MMANDOA', '2011-10-18', 'DOUALA', 'M', 'MMANDOA MICHEL', '.', NULL, 'BETIBIGUE YOLA JOSEPHINE', '654394794', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:12:44', '2025-08-27 13:12:44', 1, '25A00132', 25, 1),
(159, 'DJEKA EBENGUE GABRIEL STEEVEN', 'GABRIEL STEEVEN', 'DJEKA EBENGUE', '2013-08-11', 'DOUALA', 'M', 'EBENGUE DJEKA PIERRE BLONDIN', '691640489', NULL, 'NGO NYOGOCK ODILE YOLLANDE', '656332742', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:17:35', '2025-08-27 13:18:24', 1, '25A00133', 26, 0),
(160, 'MALENOUE NJANTA FRANCESCA BRESDELLE', 'FRANCESCA BRESDELLE', 'MALENOUE NJANTA', '2014-07-08', 'DOUALA', 'F', 'NJANTA FRANCKY BRUNEL', '651934682', NULL, 'MATCHOUM WEMBE CYNTHIA JOUVENCELLE', '655741851', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:23:28', '2025-08-27 13:23:28', 1, '25A00134', 27, 1),
(161, 'MBAIN GERALDINE', 'GERALDINE', 'MBAIN', '2010-06-26', 'DOUALA', 'F', 'SEKEM PATRICK', NULL, NULL, 'VEBESSE ERNESTINE', '683619300', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:26:40', '2025-08-27 13:26:40', 1, '25A00135', 2, 0),
(162, 'MBELLE NJIMENI MARIE SYNDI', 'MARIE SYNDI', 'MBELLE NJIMENI', '2010-12-03', 'MTE DE KEKEM', 'F', 'MBELE', NULL, NULL, 'BOUAGUET PHILOMENE', '679748153', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:29:54', '2025-08-27 13:29:54', 1, '25A00136', 7, 0),
(163, 'MACHETEH CLAUDINE', 'CLAUDINE', 'MACHETEH', '2012-02-12', 'DOUALA', 'F', 'SEKEM PATRICK', NULL, NULL, 'VEBESSE ERNESTINE', '683619300', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:41:38', '2025-08-27 13:41:38', 1, '25A00137', 4, 0),
(164, 'TSANGA DIVIN GEDEON WILFRED', 'DIVIN GEDEON WILFRED', 'TSANGA', '2014-07-07', 'LIMBE', 'M', '.', NULL, NULL, 'MEKOLO JULIENNE', '676323879', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:45:02', '2025-08-27 13:45:02', 1, '25A00138', 10, 0),
(165, 'DJAMPOU TIOGUEP STELLA DANIELLA', 'STELLA DANIELLA', 'DJAMPOU TIOGUEP', '2012-02-04', 'DOUALA', 'F', 'TIOGUEP NJOUKWE ROLLAND', NULL, NULL, 'DAKAM ROSELINE', '699570588', NULL, NULL, NULL, 70, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:53:13', '2025-08-27 13:53:13', 1, '25A00139', 1, 0),
(166, 'NGA MANGA MARIE ESTHER', 'MARIE ESTHER', 'NGA MANGA', '2007-12-12', 'BENEBALOT', 'F', '.', '656415695', NULL, 'NGA MOUGOU MARIE MADELEINE', '696427010', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-27 13:58:42', '2025-08-27 13:58:42', 1, '25A00140', 4, 0),
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
(179, 'NKWEN BAYIHA MADELEINE ANNAELLE', 'MADELEINE ANNAELLE', 'NKWEN BAYIHA', '2006-10-30', 'DOUALA', 'F', 'BAYIHA EUGENE', '694928158', NULL, 'NGO MPONGO SOM NADEGE', '699024400', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:53:09', '2025-08-28 07:53:09', 1, '25A00153', 5, 0),
(180, 'NGO LIHEP BAYIHA ALBERTINE PATIENCE', 'ALBERTINE PATIENCE', 'NGO LIHEP BAYIHA', '2013-12-25', 'DOUALA', 'F', 'BAYIHA EUGENE', '694928158', NULL, 'NGO MPONG SON NADEGE', '699024400', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 07:56:59', '2025-08-28 07:56:59', 1, '25A00154', 4, 0),
(181, 'SEN EMILIENNE ANAÏS', 'EMILIENNE ANAÏS', 'SEN', '2009-01-05', 'LYGI', 'F', 'TCHONNANG ROMUAL', '670258044', NULL, 'LEUKAM YVETTE', '675215769', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:04:16', '2025-08-28 08:04:16', 1, '25A00155', 4, 0),
(182, 'MBOUAMGUE MANUELLA STELLA', 'MANUELLA STELLA', 'MBOUAMGUE', '2010-01-01', 'DOUALA', 'F', '.', '651710146', NULL, 'EMENE KELENG NADEGE', '696689202', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:08:01', '2025-08-28 08:08:01', 1, '25A00156', 2, 0),
(183, 'CHRIST NJEM NJEM', 'NJEM NJEM', 'CHRIST', '2006-07-26', 'DOUALA', 'M', 'NDOGJOUE GILBERT', '656456516', NULL, 'NGO NTAMAK JULIENNE SOLANGE', '652601382', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:11:08', '2025-08-28 08:11:08', 1, '25A00157', 6, 0),
(184, 'MAFOGANG NAOUSSI IVANA PATIENCE', 'IVANA PATIENCE', 'MAFOGANG NAOUSSI', '2008-10-20', 'BAMOUGOUM', 'F', 'NAOUSSI BLAISE', '674302865', NULL, 'MATOUKAM MARIE CLAIRE', '652217494', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 08:13:37', '2025-08-28 08:13:37', 1, '25A00158', 6, 0),
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
(196, 'BALEBA BEATRICE DARLA CHARMANTINE', 'BEATRICE DARLA CHARMANTINE', 'BALEBA', '2004-03-14', 'DOUALA', 'F', 'BALEBA PAUL', '658595858', NULL, 'NGO TEMGA LEONCE SOPHIE', '697758427', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:43:26', '2025-08-28 10:43:26', 1, '25A00170', 7, 0);
INSERT INTO `students` (`id`, `name`, `first_name`, `last_name`, `date_of_birth`, `place_of_birth`, `gender`, `parent_name`, `parent_phone`, `parent_email`, `mother_name`, `mother_phone`, `address`, `photo`, `subname`, `class_series_id`, `email`, `student_status`, `phone_number`, `birthday`, `birthday_place`, `sex`, `father_name`, `profession`, `status`, `is_new`, `is_active`, `created_at`, `updated_at`, `school_year_id`, `student_number`, `order`, `has_scholarship_enabled`) VALUES
(197, 'DAMA ALEXANDRA', 'ALEXANDRA', 'DAMA', '2010-12-04', 'NDJOLE', 'F', 'ESSOMO ARMAND DANIEL', '698895831', NULL, 'FONA VERONIQUE', '699108118', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:46:39', '2025-08-28 10:46:39', 1, '25A00171', 2, 0),
(198, 'NANYEP KOUWO DANIEL', 'DANIEL', 'NANYEP KOUWO', '2013-01-19', 'DOUALA', 'M', 'KOUWO HERVE YALMIR', '693321099', NULL, 'KOUWO', '677132535', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:49:23', '2025-08-28 10:49:23', 1, '25A00172', 11, 0),
(199, 'ANGUISSA ZOBO ANGEL LEA', 'ANGEL LEA', 'ANGUISSA ZOBO', '2013-02-22', 'YAOUNDE', 'F', 'ZOBO TSANGA', '.', NULL, 'NKODO ONDOA MAPIE DOMINIQUE', '658311145', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:53:30', '2025-08-28 10:53:30', 1, '25A00173', 28, 0),
(200, 'METSEGOUOC KUE YVANA', 'YVANA', 'METSEGOUOC KUE', '2010-01-12', 'DOUALA', 'F', 'SANDJO KUE JOSEPH', '696478149', NULL, 'MAUTCHA\'A ALICE', '698595286', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 10:56:58', '2025-08-28 10:56:58', 1, '25A00174', 10, 0),
(201, 'KAMGA KUATE PRINCE SIDONNE', 'PRINCE SIDONNE', 'KAMGA KUATE', '2014-11-17', 'MOMBO', 'M', 'KUATE KAMGA WILLIAM SALVADOR', '674367453', NULL, 'KELLE PELAGIE', NULL, NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:02:03', '2025-08-28 11:02:03', 1, '25A00175', 29, 1),
(202, 'NGALANI NDEUSI TREASURE', 'TREASURE', 'NGALANI NDEUSI', '2011-09-23', 'MUYUKA-FAKO', 'F', 'NGALANI MAXCELL II', '653569658', NULL, 'PAULINE LUM', '676684943', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:05:08', '2025-08-28 11:05:08', 1, '25A00176', 4, 0),
(203, 'SANA LATTA', 'LATTA', 'SANA', '2013-10-18', 'DOUALA-CAMEROUN', 'F', 'SANA ATALA', '.', NULL, 'OUEDRAOGO ZENEBOU', '698084423', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:09:12', '2025-08-28 11:09:12', 1, '25A00177', 8, 0),
(204, 'SIMEU FANLEU LUCRESSE PAVELLE', 'LUCRESSE PAVELLE', 'SIMEU FANLEU', '2007-07-07', 'DOUALA', 'F', 'FANLEU EMMANUEL', '652600044', NULL, 'YANDEU CHANCELINE', '698240025', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:11:37', '2025-08-28 11:11:37', 1, '25A00178', 5, 0),
(205, 'SIDJUI MBIANDA STEPHIE FARELLE', 'STEPHIE FARELLE', 'SIDJUI MBIANDA', '2009-08-21', 'DOUALA', 'F', 'MBIANDA NGASSAM FIDELE', '699217280', NULL, 'KAGOUE TCHAMGUE JEANNETTE', '679752095', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:14:19', '2025-08-28 11:14:19', 1, '25A00179', 12, 0),
(206, 'NDEME GUEMPAGA PAUL DAVID', 'PAUL DAVID', 'NDEME GUEMPAGA', '2011-02-25', 'DOUALA', 'M', 'PASCAL ALPHONSE', '697130129', NULL, 'AMALIGA MIREILLE', '683820414', NULL, NULL, NULL, 98, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:16:57', '2025-08-28 11:16:57', 1, '25A00180', 3, 0),
(207, 'DJIESSEU TCHAPTCHET ADRIEN DARYL', 'ADRIEN DARYL', 'DJIESSEU TCHAPTCHET', '2008-05-05', 'BONABERI-DOUALA', 'M', 'TCHAPTCHET ARMAND JOËL', '671518033', NULL, 'TCHUISSEU CHRISTINE ALLIANCE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:22:13', '2025-08-28 11:22:13', 1, '25A00181', 8, 0),
(208, 'NGO KANA VICTOIRE ALEXANDRA', 'VICTOIRE ALEXANDRA', 'NGO KANA', '2009-05-07', 'EDEA', 'F', 'KANA MAKON ALEXANDRE DUMAS', '650919926', NULL, 'BINYET CLAUDIA CARINE', '694883623', NULL, NULL, NULL, 38, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:25:38', '2025-08-28 11:25:38', 1, '25A00182', 3, 0),
(209, 'AYAGALA APINA GLORIA PRINCESSE', 'GLORIA PRINCESSE', 'AYAGALA APINA', '2007-09-29', 'DOUALA', 'F', 'APINA PASCAL', '696427010', NULL, 'SEKE A WANNKOUM RAMATOU', '694334135', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:28:46', '2025-08-28 11:28:46', 1, '25A00183', 4, 0),
(210, 'DJIOSSEU TCHOMTA EVRARD JUNIOR', 'EVRARD JUNIOR', 'DJIOSSEU TCHOMTA', '2011-01-21', 'DOUALA', 'M', 'TCHOMTA JACOB', '696041485', NULL, 'KOUATA DJIOSSEU OLIVE LAURE', '673083481', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:31:21', '2025-08-28 11:31:21', 1, '25A00184', 6, 0),
(211, 'MATAFO NGUIMGO DOMINICK DERIN', 'DOMINICK DERIN', 'MATAFO NGUIMGO', '2012-12-23', 'YAOUNDE', 'M', 'NGUIMGO PIERRE MARCIAL', '691959961', NULL, 'TIDANG NKWETTE BEANNETTE', '690775004', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:35:25', '2025-08-28 11:35:25', 1, '25A00185', 5, 0),
(212, 'YAADOO MAMIDOU FABIEN', 'FABIEN', 'YAADOO MAMIDOU', '2008-10-15', 'DOUALA', 'M', 'MAMIDOU MBARBOLA', '699846320', NULL, 'FASSA MADELEINE', '676818493', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:39:23', '2025-08-28 11:39:23', 1, '25A00186', 6, 0),
(213, 'AKA\'AYELE ANGO FRESHNEL EKANA', 'FRESHNEL EKANA', 'AKA\'AYELE ANGO', '2012-05-12', 'EBOLOWA', 'F', 'ANGO ANGO FELIX', '698120421', NULL, 'MBOZO\'O NNA LAURENE JOELLE', '.', NULL, NULL, NULL, 75, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:47:25', '2025-08-28 11:47:25', 1, '25A00187', 3, 0),
(214, 'MASSOCK LUMIERE DIVINE', 'LUMIERE DIVINE', 'MASSOCK', '2014-06-12', 'DOUALA', 'F', 'MASSOCK PATRICE', '696587295', NULL, 'NYEMB ETOMBE KOLOTTO PAULINE', '.', NULL, NULL, NULL, 71, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:53:20', '2025-08-28 11:53:20', 1, '25A00188', 1, 1),
(215, 'DALLE NDOUMBE NDOLOM KANDIS MARIVONE', 'KANDIS MARIVONE', 'DALLE NDOUMBE NDOLOM', '2016-02-08', 'DOUALA', 'F', 'NDOLOM JACQUES DIDIER', '699973557', NULL, 'NSENGUE NDEMA ROSE', '694086212', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-28 11:58:34', '2025-08-28 11:58:50', 1, '25A00189', 12, 1),
(216, 'NGAUSS PALLA ADOLPHE RENE', 'ADOLPHE RENE', 'NGAUSS PALLA', '2008-07-27', 'DOUALA', 'M', 'NGAUSS NDOUNG GERARD', '677627962', NULL, 'NGANOMA DOROTHEE', '677345626', NULL, NULL, NULL, 42, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-29 10:58:20', '2025-08-29 10:58:20', 1, '25A00190', 5, 0),
(217, 'DJAOWE JUNIOR', 'JUNIOR', 'DJAOWE', '2012-04-17', 'DOUKOULA', 'M', 'DJOMAILA ROGER', NULL, NULL, 'BADONIWA ELEINE', '658061078', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-29 13:24:02', '2025-08-29 13:24:02', 1, '25A00191', 30, 1),
(218, 'MBALLA ANE TATIANA DANIELLA', 'ANE TATIANA DANIELLA', 'MBALLA', '2014-01-18', 'DOUALA', 'F', 'MOMHA HONDA JOSEPH RAOUL', NULL, NULL, 'OLOMO EBENGUE MIREILLE CLARISSE', '698542044', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-29 13:35:57', '2025-08-29 13:35:57', 1, '25A00192', 31, 0),
(219, 'WAFO KOUOMEGNE FABRICE VAILLANT', 'FABRICE VAILLANT', 'WAFO KOUOMEGNE', '2008-04-14', 'DOUALA', 'M', 'KOUOMEGNE PIERRE', '654338827', NULL, 'DJUIGNE MADELEINE', '657998790', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 05:49:48', '2025-08-30 05:49:48', 1, '25A00193', 32, 0),
(220, 'DELIVRANCE ROBERT', 'ROBERT', 'DELIVRANCE', '2011-03-12', 'YAGOUA', 'M', 'ARONA GEREMI', '676902705', NULL, 'AINA', '694345639', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 05:54:29', '2025-08-30 05:54:29', 1, '25A00194', 9, 0),
(221, 'NOHA OMBOUTOU DURANT', 'DURANT', 'NOHA OMBOUTOU', '2008-12-17', 'BANDJOUN', 'M', 'OMBOUTOU JEAN', NULL, NULL, 'MOTOUOM DOROTHEE', '691702055', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 05:58:42', '2025-08-30 05:58:42', 1, '25A00195', 7, 0),
(222, 'GUEMDJO VANESSA', 'VANESSA', 'GUEMDJO', '2009-10-11', 'BAHAM', 'F', 'NOUBISSI JOSEPH', '677751013', NULL, 'DJUINGNE MARTINE', '675157833', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:03:19', '2025-08-30 06:03:19', 1, '25A00196', 4, 0),
(223, 'NGUIDJOL ADRIEN RYAN', 'ADRIEN RYAN', 'NGUIDJOL', '2011-01-20', 'DOUALA', 'M', 'BAKANG JACQUES', '694808497', NULL, 'NGO LOGA ANGELE MARIE ELISEE', '698290393', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:04:06', '2025-08-30 06:04:06', 1, '25A00197', 33, 0),
(224, 'KAMANI LEUMENI MARC NATHAN', 'MARC NATHAN', 'KAMANI LEUMENI', '2014-02-26', 'BATCHAM', 'M', 'LEUMENI BERNARD RODRIGUE', '696594220', NULL, 'TSAMENE DOUANLA CLARISSE AIMEE', '676570561', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:06:55', '2025-08-30 06:06:55', 1, '25A00198', 34, 0),
(225, 'MONTHE NDZINGA CHLOE MAEVA', 'CHLOE MAEVA', 'MONTHE NDZINGA', '2009-10-30', 'DOUALA', 'F', 'MONTHE JEAN BAUDOUIN', '697105520', NULL, 'ABOGO AMBANI ANTOINETTE', '680325010', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:08:41', '2025-08-30 06:08:41', 1, '25A00199', 3, 0),
(226, 'DONGMO LOYIE FADEL', 'FADEL', 'DONGMO LOYIE', '2003-07-02', 'FONGO-TONGO', 'M', 'ZATSA CHRETIEN', '670052957', NULL, 'TEUTENG SUZANNE', NULL, NULL, NULL, NULL, 63, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:09:42', '2025-08-30 06:09:42', 1, '25A00200', 1, 0),
(227, 'FRANCK CHRISTIAN MAKANDA', 'MAKANDA', 'FRANCK CHRISTIAN', '2013-07-15', 'DOUALA', 'M', 'MAKANDA MAKANDA FELIX', '696540135', NULL, 'WONSO ELISABETH MARION', '698896502', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:12:34', '2025-08-30 06:12:34', 1, '25A00201', 35, 0),
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
(239, 'BAADI ABDOUL AZIZ', 'ABDOUL AZIZ', 'BAADI', '2007-03-15', 'DOUALA', 'M', 'KOINA MAMOUDOU', '690824242', NULL, 'MAIMOUNOU OUMAROU', NULL, NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:32:51', '2025-08-30 06:32:51', 1, '25A00213', 9, 0),
(240, 'NTOOGUE MARIA JESSICA', 'MARIA JESSICA', 'NTOOGUE', '2014-07-10', 'DOUALA', 'F', 'NTOOGUE GILLES EBENEZER', '650666754', NULL, 'MINYEM ZINI GISELE', '679197411', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:34:23', '2025-08-30 06:35:42', 1, '25A00214', 36, 0),
(241, 'NTOOGUE SIMEON WILFRIED', 'SIMEON WILFRIED', 'NTOOGUE', '2014-07-10', 'DOUALA', 'M', 'NTOOGUE GILLES EBENEZER', '650666754', NULL, 'MINYEM ZINI GISELE', '679197411', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:38:20', '2025-08-30 06:38:20', 1, '25A00215', 37, 0),
(242, 'DANIELLE LIZ MAELLE OSSENDE .', '.', 'DANIELLE LIZ MAELLE OSSENDE', '2008-09-10', 'YAOUNDE', 'F', 'OSSENDE KAROL AURELIEN', '691113654', NULL, 'NGO ELOUGA CATHERINE', NULL, NULL, NULL, NULL, 41, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:41:17', '2025-08-30 06:41:17', 1, '25A00216', 1, 0),
(243, 'IBRAHIMA LAMINE', 'LAMINE', 'IBRAHIMA', '2006-08-31', 'BERTOUA', 'M', 'MOUHAMADOU LAMINE', '699037059', NULL, 'DJOULEYHATOU ALIM', '690585156', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:41:48', '2025-08-30 06:41:48', 1, '25A00217', 7, 0),
(244, 'MARIE JOSEPH THEOPHANE NGAH OSSENDE', 'NGAH OSSENDE', 'MARIE JOSEPH THEOPHANE', '2012-08-05', 'YAOUNDE', 'F', 'OSSENDE KAROL AURELIEN', '691113654', NULL, 'NGO  ELOUGA CATHERINE', NULL, NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:44:34', '2025-08-30 06:44:34', 1, '25A00218', 5, 0),
(245, 'DJUIDJIE CHOUPO SANDRA CHIMENE', 'SANDRA CHIMENE', 'DJUIDJIE CHOUPO', '2009-05-28', 'KAYO-BANDJOUN', 'F', 'CHOUPO YVE', '677646381', NULL, 'KENGNE SIDONIE', '656888665', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:44:55', '2025-08-30 06:44:55', 1, '25A00219', 5, 0),
(246, 'AÏSSATOU YAYA', 'YAYA', 'AÏSSATOU', '2008-07-07', 'DIBONG', 'F', 'YAYA MOSSI', NULL, NULL, 'NGO MBENOUN ESTHER', '658349169', NULL, NULL, NULL, 40, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:47:56', '2025-08-30 06:48:13', 1, '25A00220', 2, 0),
(247, 'HELENE DOROTHEE OSSENDE .', '.', 'HELENE DOROTHEE OSSENDE', '2014-04-05', 'YAOUNDE', 'F', 'OSSENDE KAROL AURELIEN', '691113654', NULL, 'NGO ELOUGA CATHERINE', '698963214', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:48:04', '2025-08-30 06:48:04', 1, '25A00221', 11, 0),
(248, 'HASSANA YAYA', 'YAYA', 'HASSANA', '2005-11-08', 'DIBANG', 'F', 'YAYA MOSSI', NULL, NULL, 'NGO MBENOUN ESTHER', '658349169', NULL, NULL, NULL, 36, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:50:24', '2025-08-30 06:50:24', 1, '25A00222', 4, 0),
(249, 'MEFANG GWLADYS DIANE', 'DIANE', 'MEFANG GWLADYS', '2007-04-09', 'YAOUNDE', 'F', '.', NULL, NULL, 'WOUMO JOSEPHINE', '696948157', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:53:12', '2025-08-30 06:53:12', 1, '25A00223', 8, 0),
(250, 'KWETCHOU GATOU SCHEKINA GREECY', 'SCHEKINA GREECY', 'KWETCHOU GATOU', '2013-07-15', 'DOUALA', 'F', 'GATORO ERIC CLOTIN', NULL, NULL, 'NJADJA MARGUERITE', '697541480', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:53:36', '2025-08-30 06:53:36', 1, '25A00224', 9, 0),
(251, 'ACHTA HASSIM', 'HASSIM', 'ACHTA', '2013-06-07', 'NGAOUNDERE', 'F', 'HASSI TAHIR', '653530405', NULL, 'HADJA KORE MOUSTAPHA', '670159751', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 06:57:12', '2025-08-30 06:57:12', 1, '25A00225', 38, 1),
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
(265, 'MONTHE EYENGA BERTHE ASHLEY', 'BERTHE ASHLEY', 'MONTHE EYENGA', '2014-11-05', 'DOUALA', 'F', 'MONTHE PADJI JEAN BEAUDOUIN', '697105520', NULL, 'ABOGO AMBANI ANTOINETTE', '680325010', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 07:25:26', '2025-08-30 07:25:26', 1, '25A00239', 39, 0),
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
(276, 'HAMOUA ABOUBAKAR ABDOUL-RAZACK', 'ABDOUL-RAZACK', 'HAMOUA ABOUBAKAR', '2012-08-15', 'IDOOL', 'M', 'IYA MOHAMAN BELLO', '675253631', NULL, 'ADDA HAPSATOU', '697703575', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:24:57', '2025-08-30 08:25:58', 1, '25A00250', 40, 0),
(277, 'MBASSI MURIELLE DORIANE', 'MURIELLE DORIANE', 'MBASSI', '2008-05-17', 'DOUALA', 'F', 'MBASSI EDOUMA ABDON', '697104866', NULL, 'MOKA AMBADIANG JUDITH PATIENCE', '699185216', NULL, NULL, NULL, 107, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:35:41', '2025-08-30 08:35:41', 1, '25A00251', 2, 0),
(278, 'TONKA MOKA NATIVIDAD', 'NATIVIDAD', 'TONKA MOKA', '2012-12-24', 'LUBA-GUINEE EQUATORIAL', 'F', '.', NULL, NULL, 'MOKA OKODOMBE MONIQUE BEPENGERE', '699185216', NULL, NULL, NULL, 45, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:38:51', '2025-08-30 08:38:51', 1, '25A00252', 3, 0),
(279, 'WAFO KOUOMEGNE FABRICE VAILLANT', 'FABRICE VAILLANT', 'WAFO KOUOMEGNE', '2008-04-14', 'DOUALA', 'M', 'KOUOMEGNE PIERRE', '654338827', NULL, 'DJUIGNE MADELEINE', '657998780', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:52:52', '2025-08-30 08:52:52', 1, '25A00253', 15, 0),
(280, 'MOUHAMED ABDOU BOUBA', 'BOUBA', 'MOUHAMED ABDOU', '2014-08-08', 'DOUALA', 'M', 'ABDOU BOUBA', '699870592', NULL, 'DJARA DAOUDA', '671313107', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:56:16', '2025-08-30 08:56:16', 1, '25A00254', 41, 0),
(281, 'YAYA ABDOU', 'ABDOU', 'YAYA', '2011-01-10', 'WAZA', 'M', '.', '699870592', NULL, 'DJORA DAOUDA', '671313107', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 08:58:16', '2025-08-30 08:58:16', 1, '25A00255', 12, 0),
(282, 'ABDOU BOUBA BACHIR', 'BACHIR', 'ABDOU BOUBA', '2013-05-08', 'DOUALA', 'M', 'ABDOU BOUBA', '699870592', NULL, 'DJARA DAOUDA', '671313107', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:00:13', '2025-08-30 09:00:13', 1, '25A00256', 7, 0),
(283, 'DIDJA ABDOU .', '.', 'DIDJA ABDOU', '2008-08-18', 'WAZA', 'F', '.', '699870592', NULL, 'DJARA DAOUDA', '671313107', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:02:30', '2025-08-30 09:02:30', 1, '25A00257', 10, 0),
(284, 'MEFANG GWLADYS DIANE', 'DIANE', 'MEFANG GWLADYS', '2007-04-09', 'YAOUNDE', 'F', '.', NULL, NULL, 'WOUMO JOSEPHINE', '696948157', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:14:02', '2025-08-30 09:14:02', 1, '25A00258', 7, 0),
(285, 'ZANG OWONO PATRICK ENZO', 'PATRICK ENZO', 'ZANG OWONO', '2007-03-14', 'DOUALA', 'M', 'POOK JOSEPH RICHARD', '699252023', NULL, 'MIMBE THERESE', '677155031', NULL, NULL, NULL, 110, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:16:27', '2025-08-30 09:16:27', 1, '25A00259', 1, 0),
(286, 'DZUIKUI TCHINDA DUCHESSE LUCIANE', 'DUCHESSE LUCIANE', 'DZUIKUI TCHINDA', '2015-06-04', 'BABETE', 'F', 'TCHINDA ARMAND', '677424549', NULL, 'MAKEM TCHINDA ROSINE', '654988942', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:38:13', '2025-08-30 09:38:13', 1, '25A00260', 10, 0),
(287, 'KEUMAYOU TCHANA MAYELLE ARCHANGE', 'MAYELLE ARCHANGE', 'KEUMAYOU TCHANA', '2014-06-29', 'DOUALA', 'F', 'TCHANA RODRIGUE', '694472827', NULL, 'MOUTO EPISSI AGNES', '656718831', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 09:58:59', '2025-08-30 09:58:59', 1, '25A00261', 11, 0),
(288, 'WONDJA EPISSI ANAYEL', 'ANAYEL', 'WONDJA EPISSI', '2013-01-04', 'MANJO', 'F', '.', '694472827', NULL, 'KALATI ROSE', '656718831', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 10:01:18', '2025-08-30 10:01:18', 1, '25A00262', 12, 0),
(289, 'APOUZA MESSIRENI BRIANA', 'BRIANA', 'APOUZA MESSIRENI', '2013-02-15', 'DOUALA', 'F', 'MESSIRENI', '.', NULL, 'NGOUMPECHIO LAURA', '675816390', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 10:19:37', '2025-08-30 10:19:37', 1, '25A00263', 42, 0),
(290, 'NOBOP MESSIRENI DAVILA', 'DAVILA', 'NOBOP MESSIRENI', '2011-03-23', 'DOUALA', 'F', 'MESSIRENI', NULL, NULL, 'NGOUMPECHIO LAURA', '675816390', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-08-30 10:21:55', '2025-08-30 10:21:55', 1, '25A00264', 8, 0),
(291, 'MESSINA GEORETTE BRITANIE', 'GEORETTE BRITANIE', 'MESSINA', '2014-03-28', 'ATOK', 'F', 'NIEPANG NKOT CLOVIS', NULL, NULL, 'ENGAMB NDJOH  TATIANA LAURE', '698892798', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 05:38:08', '2025-09-01 05:38:08', 1, '25A00265', 43, 0),
(292, 'NOUGUEP MANOELA PRINCESSE', 'MANOELA PRINCESSE', 'NOUGUEP', '2009-12-19', 'DOUALA', 'F', 'SOUOP SERGE', '677751429', NULL, 'DOMGAP LEOLODINE', '677488891', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 06:02:29', '2025-09-01 06:02:29', 1, '25A00266', 12, 0),
(293, 'NDOUENGAM EXTHER SARINA', 'EXTHER SARINA', 'NDOUENGAM', '2012-04-28', 'DOUALA', 'F', 'NDOUENGAM  COLLINS BRUNO', NULL, NULL, 'MEGNANG ABIDIAS VICTORINE NADEGE', 'MEGNANG NADEGE', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:10:18', '2025-09-01 07:10:18', 1, '25A00267', 9, 0),
(294, 'NZIKOUO NDOUENGAM MIRABELLE RHODE', 'MIRABELLE RHODE', 'NZIKOUO NDOUENGAM', '2014-07-01', 'DOUALA', 'F', 'NDOUENGAM COLLINS BRUNO', NULL, NULL, 'MEGNANG ABIDIAS VICTORINE NADEGE', '699250088', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:15:51', '2025-09-01 07:15:51', 1, '25A00268', 44, 0),
(295, 'EMAGUE AYINA BRAYAN', 'BRAYAN', 'EMAGUE AYINA', '2010-11-10', 'SOUZA GARE', 'M', 'EMAGUE JEAN GUY', NULL, NULL, 'AYINA AYINA COLLETTE', '658670639', NULL, NULL, NULL, 70, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:21:20', '2025-09-01 07:21:20', 1, '25A00269', 2, 0),
(296, 'EVELE AHMET', 'AHMET', 'EVELE', '2009-07-08', 'DOUALA', 'M', 'ABDOU AFIDI', '699343566', NULL, 'ZENBOU ABDOULAYE', '.', NULL, NULL, NULL, 81, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:26:23', '2025-09-01 07:26:23', 1, '25A00270', 1, 0),
(297, 'MAYACKI JOVANNA KAELLA', 'JOVANNA KAELLA', 'MAYACKI', '2010-04-02', 'DOUALA', 'F', 'MAYACKI JEAN CALVI N', '677550384', NULL, 'NGOYEM ADELE GERMAINE', '.', NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:36:50', '2025-09-01 07:43:16', 1, '25A00271', 3, 0),
(298, 'NTSAMA ONGUENE BENEDICTE', 'BENEDICTE', 'NTSAMA ONGUENE', '2007-08-12', 'DOUALA', 'F', 'ONGUENE SEVERIN', '696384593', NULL, 'NGONO FRANCOISE', '696384593', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:39:11', '2025-09-01 07:39:11', 1, '25A00272', 10, 0),
(299, 'POMOU DEUNGA CLAUDE AUDREE PRINCESSE', 'CLAUDE AUDREE PRINCESSE', 'POMOU DEUNGA', '2006-03-21', 'DOUALA', 'F', 'NJOPTCHOUANG BLAISE', '691286418', NULL, 'DJAMBOU JUSTINE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:45:35', '2025-09-01 07:45:35', 1, '25A00273', 11, 0),
(300, 'MAKOUNE MANDJI DAINA DIVINE', 'DAINA DIVINE', 'MAKOUNE MANDJI', '2012-10-08', 'BASSOUGOUM', 'F', 'MANDJI HENRI JOEL', '674471322', NULL, 'MAGAM SIGHE CHANCELLE GAELLE', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:50:55', '2025-09-01 07:50:55', 1, '25A00274', 16, 0),
(301, 'MINKEU NANA DORCAS JESSICA', 'DORCAS JESSICA', 'MINKEU NANA', '2013-09-23', 'MANJO', 'F', 'NANA CESAIR', '676920227', NULL, 'MBAKOP NATHALIE', '650475223', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 07:57:05', '2025-09-01 07:57:05', 1, '25A00275', 10, 0),
(302, 'ATEBA BANDOLO TRACY SYLVANA', 'TRACY SYLVANA', 'ATEBA BANDOLO', '2011-09-29', 'NGAOUNDERE', 'F', 'ATEBA', '693312261', NULL, 'MADANY SALE', '675943201', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:01:20', '2025-09-01 08:01:20', 1, '25A00276', 17, 0),
(303, 'BOSSOUNG PEFOUHO BLESSING SHARON', 'BLESSING SHARON', 'BOSSOUNG PEFOUHO', '2015-01-02', 'BABADJOU', 'F', 'PEFOUHO SEME DANY', '656813995', NULL, 'MENEGHA LOCDJINO ALLIANCE', '.', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:07:24', '2025-09-01 08:07:24', 1, '25A00277', 45, 0),
(304, 'AYANGMA NTSOH PRINCE CABREL', 'PRINCE CABREL', 'AYANGMA NTSOH', '2014-07-08', 'DOUALA', 'M', 'ONANINA PIERRE PASCAL', '676046421', NULL, 'BOSONG MARTHE VIVIANE', '.', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:11:43', '2025-09-01 08:11:43', 1, '25A00278', 13, 0),
(305, 'MPOT DIDERLINE', 'DIDERLINE', 'MPOT', '2011-11-05', 'BERTOUA', 'F', 'KARDE PIERRE', '696087033', NULL, 'ALONDO MARIE GULLE', '.', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:25:19', '2025-09-01 08:25:19', 1, '25A00279', 18, 0),
(306, 'AKONO MEBA DIVIN', 'DIVIN', 'AKONO MEBA', '2012-11-04', 'NKOLOTOUTOU', 'F', 'MEBA EFORA THIERRY DAVID', '.', NULL, 'EBOUTOU AKONO SANDRINE', '650865739', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:52:23', '2025-09-01 09:03:26', 1, '25A00280', 13, 0),
(307, 'MAKOK ONDOA AGATHE LESLIE', 'AGATHE LESLIE', 'MAKOK ONDOA', '2012-01-23', 'DOUALA', 'F', 'ONDOA ANDRE MARIE MAVE', '682193243', NULL, 'MAKOK MARIE FRANCOISE', '.', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 08:56:30', '2025-09-01 08:56:30', 1, '25A00281', 7, 0),
(308, 'UM BITJEL MARCELIN ROMARIC', 'MARCELIN ROMARIC', 'UM BITJEL', '2011-11-16', 'BOUMNYEBEL', 'M', 'BASSAMA EMMANUEL', '695384967', NULL, 'NGO NSEGBE PEBORAH L\'OR', '.', NULL, NULL, NULL, 105, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 09:02:15', '2025-09-01 09:02:15', 1, '25A00282', 3, 0),
(309, 'ESSOME CHEWO ANGE VANELLE', 'ANGE VANELLE', 'ESSOME CHEWO', '2013-06-21', 'DOUALA', 'F', 'ESSOME ESSOME CHRISTIAN JOEL', '.', NULL, 'GUIADEM SIDOINE', '657614230', NULL, NULL, NULL, 67, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 09:19:32', '2025-09-01 09:19:32', 1, '25A00283', 3, 0),
(310, 'BIBOUM BONDJE MARINA PAOLA DELPHINE', 'MARINA PAOLA DELPHINE', 'BIBOUM BONDJE', '2008-01-05', 'DOUALA', 'F', '.', '.', NULL, 'ANNE NICAISE BONDJE', '+33605912130', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 09:40:11', '2025-09-01 09:40:11', 1, '25A00284', 19, 0),
(311, 'KEMDJO TOCHE MILEINE CABREL', 'MILEINE CABREL', 'KEMDJO TOCHE', '2008-03-05', 'DOUALA', 'F', 'TOCHE AIME PHILIPPE', '699412647', NULL, 'TOCHE SYLVIE FLORE', '665000000', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:07:09', '2025-09-01 10:07:09', 1, '25A00285', 5, 0),
(312, 'SITCHOM JOACHIM', 'JOACHIM', 'SITCHOM', '2015-11-22', 'DOUALA', 'M', 'SITCHOM YANNICK STEPHANE', '682224688', NULL, 'MAKUSSU PASCALINE', '699577289', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:11:40', '2025-09-01 10:12:28', 1, '25A00286', 46, 0),
(313, 'AWE NGOMNA STEPHANE', 'STEPHANE', 'AWE NGOMNA', '2009-12-19', 'DOUALA', 'M', 'NGOMNA JEAN PAUL', '699313870', NULL, 'NDIKWA SUZANNE', '658462011', NULL, NULL, NULL, 33, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:23:47', '2025-09-01 10:23:47', 1, '25A00287', 11, 0),
(314, 'LIBAM MALLONG BENOIT BRICE', 'BENOIT BRICE', 'LIBAM MALLONG', '2012-04-03', 'DOUALA', 'M', 'BAYIHA BAKIDI  BASILE', '691819821', NULL, 'NGO MALLONG ADELE GHANDI', '656577620', NULL, NULL, NULL, 47, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:41:20', '2025-09-01 10:41:20', 1, '25A00288', 2, 0),
(315, 'NGO MALLONG BAYIHA MARIE RAPHAELLE', 'BAYIHA MARIE RAPHAELLE', 'NGO MALLONG', '2010-11-30', 'DOUALA', 'F', 'BAYIHA BAKIDI BASILE', '691819821', NULL, 'NGO MALLONG ADELE GHANDI', '656577620', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:45:22', '2025-09-01 10:50:39', 1, '25A00289', 20, 0),
(316, 'DONG DONG TOSTANIE NICAISE', 'TOSTANIE NICAISE', 'DONG DONG', '2011-02-07', 'DONENKENG', 'F', 'DONG ANGON PAUL CREPAIN', '659723813', NULL, 'MEFOUMA ABAMA JOSEPHINE', '.', NULL, NULL, NULL, 103, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 10:56:56', '2025-09-01 10:56:56', 1, '25A00290', 6, 0),
(317, 'SANDJON LINDA VIRGINIE GLOIRE', 'LINDA VIRGINIE GLOIRE', 'SANDJON', '2007-09-08', 'YAOUNDE', 'F', 'YABOU', '655488332', NULL, 'SIMO FANNY', '.', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:04:54', '2025-09-01 12:04:54', 1, '25A00291', 13, 0),
(318, 'ZEINAB LATIFA GAMBO', 'LATIFA GAMBO', 'ZEINAB', '2010-08-17', 'DOUALA', 'F', 'GAMBO', '694752505', NULL, 'HAWA', '655627890', NULL, NULL, NULL, 82, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:10:35', '2025-09-01 12:10:35', 1, '25A00292', 3, 0),
(319, 'LEMBOIGNY SENGOUA SHAMA JOY', 'SHAMA JOY', 'LEMBOIGNY SENGOUA', '2012-11-09', 'DOUALA', 'F', 'SENGOUA HERMANN', '676024631', NULL, 'BAHONO AMELIE LAURE', '676873942', NULL, NULL, NULL, 78, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:18:00', '2025-09-01 12:18:00', 1, '25A00293', 1, 0),
(320, 'TAGOUFO KENNE ANGE MERVEILLE', 'ANGE MERVEILLE', 'TAGOUFO KENNE', '2014-01-17', 'NGAOUNDERE', 'F', 'TAGOUFO ALAIN VALERE', '699903619', NULL, 'TAYONT LONCHI ESTHER', '672007557', NULL, NULL, NULL, 53, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:19:23', '2025-09-01 12:19:23', 1, '25A00294', 14, 0),
(321, 'TIOTSOP VANELLE DANIELLA', 'VANELLE DANIELLA', 'TIOTSOP', '2015-03-16', 'BALENG', 'F', 'WOBNDJOH SAMUEL', '699903619', NULL, 'NTSAPI NZEUMEKEM SANDRA', '672007557', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:24:25', '2025-09-01 12:24:25', 1, '25A00295', 47, 0),
(322, 'TAGOUFO FOPA DANIEL', 'DANIEL', 'TAGOUFO FOPA', '2012-09-30', 'NGAOUNDERE', 'M', 'TAGOUFO ALAIN VALERE', '699903619', NULL, 'TAYONT LONCHI PULCHERIE', '672007557', NULL, NULL, NULL, 22, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:27:44', '2025-09-01 12:27:44', 1, '25A00296', 3, 0),
(323, 'KEGNIA EMMANUEL CHRIST', 'EMMANUEL CHRIST', 'KEGNIA', '2014-01-18', 'DOUALA', 'M', 'NYABEYE PANGOU FIRMIN', '696108651', NULL, 'KENGNIA ARMANDA', '650660336', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:32:23', '2025-09-01 12:32:23', 1, '25A00297', 13, 0),
(324, 'MESSACK SANDRA', 'SANDRA', 'MESSACK', '2006-08-08', 'BALENG -KONTI', 'F', 'NEABIN JEAN-BAPTISTE', '671850647', NULL, 'MADJUI EMMERENCE', '691086039', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:46:31', '2025-09-01 12:46:31', 1, '25A00298', 21, 0),
(325, 'BIKIE NGUELE MARIE JEANNE', 'MARIE JEANNE', 'BIKIE NGUELE', '2009-01-28', 'YAOUNDE', 'F', 'NDI NGUELE RICHARD', '.', NULL, 'NDZIE MVOND BERNADETTE ESTELLE', '656531950', NULL, NULL, NULL, 92, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 12:54:29', '2025-09-01 12:54:29', 1, '25A00299', 4, 0),
(326, 'MOUKAM YOUALEU MICHEL JASON', 'MICHEL JASON', 'MOUKAM YOUALEU', '2012-07-20', 'DOUALA', 'M', 'MOUKAM MICHEL MAGLOIRE', '670633015', NULL, 'DEUDJUI IVETTE', '696669915', NULL, NULL, NULL, 57, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-01 13:04:48', '2025-09-01 13:04:48', 1, '25A00300', 6, 0),
(328, 'KAMBOU ARCHANGE JOYCE', 'ARCHANGE JOYCE', 'KAMBOU', '2014-05-15', 'DOUALA', 'F', 'FEUKAM SAGOU RODRIGUE MICHEL', '672816719', NULL, 'SAGOU BERJINETTE', '693497342', NULL, NULL, NULL, 55, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:27:08', '2025-09-02 04:27:08', 1, '25A00301', 8, 0),
(329, 'MBEUHEM ABESSANG ARON KLOE', 'ARON KLOE', 'MBEUHEM ABESSANG', '2013-07-17', 'TONGANG', 'M', 'MBEUHEM KAMTE NARCISSE', '670223268', NULL, 'MIGUIM ABESSANG CHRISTELLE', '679211528', NULL, NULL, NULL, 19, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:32:20', '2025-09-02 04:32:20', 1, '25A00302', 11, 0),
(330, 'BENJON TSAFACK JOSEPHINE', 'JOSEPHINE', 'BENJON TSAFACK', '2008-10-28', 'DOUALA', 'F', 'BENJON', '673045514', NULL, NULL, NULL, NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:35:30', '2025-09-02 04:35:30', 1, '25A00303', 14, 0),
(331, 'EBONG TCHUISSEU ANDREA KRISTY', 'ANDREA KRISTY', 'EBONG TCHUISSEU', '2009-09-19', 'DOUALA', 'F', 'TCHUISSEU NKONDAH FREDERIC', '696521354', NULL, 'MBITCHA ANNE BERTHE', '672968269', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:38:42', '2025-09-02 04:38:42', 1, '25A00304', 12, 0),
(332, 'KOUMA BOULI RYAN DARIEL', 'RYAN DARIEL', 'KOUMA BOULI', '2011-01-05', 'DOUALA', 'M', 'BOULI PIERRE PAUL', '686824254', NULL, 'EKANDO MIRIOLLE FLAVIE', '675202112', NULL, NULL, NULL, 20, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:42:27', '2025-09-02 04:42:27', 1, '25A00305', 8, 0),
(333, 'NDZANA NGAH JEANNE ESTHER', 'JEANNE ESTHER', 'NDZANA NGAH', '2008-07-06', 'DOUALA', 'F', 'NGAH FREDERIC', '653977709', NULL, 'NGANA MENAMAGA ANTOINETTE FLORE', '.', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:45:31', '2025-09-02 04:45:31', 1, '25A00306', 8, 0),
(334, 'TITCHO YENGOUA MARTHE LAUREINA', 'MARTHE LAUREINA', 'TITCHO YENGOUA', '2010-04-30', 'BAHAM', 'F', 'TITCHO', '659812944', NULL, NULL, NULL, NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:49:29', '2025-09-02 04:49:29', 1, '25A00307', 3, 0),
(335, 'SANI ALHADJI ADAMOU', 'ALHADJI ADAMOU', 'SANI', '2008-01-19', 'YAOUNDE', 'M', 'ADAMOU MOUHAMADOU', '655605530', NULL, 'AMINATOU ILIASSOU', NULL, NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:53:51', '2025-09-02 04:53:51', 1, '25A00308', 11, 0),
(336, 'NZEUMENI DAHEU FLORINDA', 'FLORINDA', 'NZEUMENI DAHEU', '2006-06-11', 'DOUALA', 'F', 'NZEUMENI DIMITRIC', '696941326', NULL, 'NTANIMI NJINOU AFLIDETTE', '673606254', NULL, NULL, NULL, 106, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 04:57:55', '2025-09-02 04:57:55', 1, '25A00309', 6, 0),
(337, 'MFEUGUE FOE SIMONIE', 'SIMONIE', 'MFEUGUE FOE', '2010-03-10', 'DOUALA', 'F', 'FOE PATRICE', '691030491', NULL, 'NDOMTCHENG Epse FOE DENISE', NULL, NULL, NULL, NULL, 47, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:03:04', '2025-09-02 07:44:20', 1, '25A00310', 3, 0),
(338, 'NKIE URSBRIGHT ESEGEMU', 'ESEGEMU', 'NKIE URSBRIGHT', '2012-03-10', 'DOUALA', 'F', 'NKIE', '699797990', NULL, NULL, NULL, NULL, NULL, NULL, 59, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:06:29', '2025-09-02 05:06:29', 1, '25A00311', 4, 0),
(339, 'NGO NLEND CHRISTIANETTE', 'CHRISTIANETTE', 'NGO NLEND', '2025-07-11', 'DOUALA', 'F', '.', '682887448', NULL, 'NGO TONYE THERESE', '656587017', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:14:35', '2025-09-02 05:14:35', 1, '25A00312', 22, 0),
(340, 'EYENGA EKOUMA YOLANDE RISPA', 'YOLANDE RISPA', 'EYENGA EKOUMA', '2012-07-29', 'NDAMVO', 'F', 'EKOUMA AMBASSA', '676597753', NULL, 'MBANA EBOGO MARCELLE', '655334911', NULL, NULL, NULL, 51, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:20:35', '2025-09-02 05:20:35', 1, '25A00313', 9, 0),
(341, 'MEVA\'A JUNIOR', 'JUNIOR', 'MEVA\'A', '2012-12-29', 'MBANKOMO', 'M', 'MOTO ABATHE JEAN CLAUDE', '657371919', NULL, 'NYANGONO CHRISTELLE', '682744482', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:26:39', '2025-09-02 05:26:39', 1, '25A00314', 48, 1),
(342, 'SIANTOU DJIETCHEU YOLANN PETRINA', 'YOLANN PETRINA', 'SIANTOU DJIETCHEU', '2011-03-23', 'NGOUSSO-YAOUNDE', 'F', 'DJIETCHEU NGOUAMBE SERAPHIN', '698327483', NULL, 'BOUEMANI SICKAM CHIMAINE BERLINE', '695066177', NULL, NULL, NULL, 84, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:30:39', '2025-09-02 05:30:39', 1, '25A00315', 23, 0),
(343, 'KWAKEP NZEUMENI AURELIEN WILFRIED', 'AURELIEN WILFRIED', 'KWAKEP NZEUMENI', '2010-05-10', 'DOUALA', 'M', 'NZEUMENI DIMITRIC', '673606254', NULL, 'NTAMINI NJINOU AFLIDETTE', '696941326', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:33:47', '2025-09-02 05:33:47', 1, '25A00316', 9, 0),
(344, 'YOTA PRINCESSE NASIRA', 'PRINCESSE NASIRA', 'YOTA', '2014-03-18', 'BALATCHI', 'F', 'TIOYO MICHEL', NULL, NULL, 'DOUANLA TOBOUO ROSINE', '670061951', NULL, NULL, NULL, 43, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:44:09', '2025-09-02 05:44:09', 1, '25A00317', 14, 0),
(345, 'NGO LIKENG MIREILLE', 'MIREILLE', 'NGO LIKENG', '2014-09-09', 'ESEKA', 'F', 'ANGOH FREDY', NULL, NULL, 'PAGBE ELOMA CHRISTELLE', '693095778', NULL, NULL, NULL, 15, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:47:12', '2025-09-02 05:47:12', 1, '25A00318', 49, 0),
(346, 'ELOMA JEAN EMMANUEL YVAN', 'JEAN EMMANUEL YVAN', 'ELOMA', '2008-12-07', 'YAOUNDE', 'M', 'ANGOH FREDY', NULL, NULL, 'PAGBE ELOMA CHRISTELLE', '693095778', NULL, NULL, NULL, 35, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 05:50:36', '2025-09-02 05:50:36', 1, '25A00319', 8, 0),
(347, 'MEFANG OLIVIA', 'OLIVIA', 'MEFANG', '2008-02-16', 'DIMAKO', 'F', 'YOUDOM NGOMSI', '698066798', NULL, 'AZONG LILIE STELLA', '677608495', NULL, NULL, NULL, 87, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:23:53', '2025-09-02 06:23:53', 1, '25A00320', 4, 0),
(348, 'BEA LOUISE CHELISSA', 'LOUISE CHELISSA', 'BEA', '2010-10-26', 'BOT-MAKAK', 'F', 'TENLEP EMMANUEL', '699582328', NULL, 'MVONDO BIWOLIE', NULL, NULL, NULL, NULL, 73, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:40:42', '2025-09-02 06:40:42', 1, '25A00321', 2, 0),
(349, 'NGAN NGAN PAUL  FRIJOLITO', 'PAUL  FRIJOLITO', 'NGAN NGAN', '2004-01-01', 'DOUALA', 'M', 'TENLEP EMMANUEL', '699582328', NULL, 'MVONDO BIWOLIE', NULL, NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:42:35', '2025-09-02 06:42:35', 1, '25A00322', 14, 0),
(350, 'NGANDO JEAN DANIEL', 'JEAN DANIEL', 'NGANDO', '2012-03-18', 'YAOUNDE', 'M', 'NGANDO JEAN', '697692851', NULL, 'ATAMA ROSINE', '695058807', NULL, NULL, NULL, 17, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:50:31', '2025-09-02 06:50:31', 1, '25A00323', 15, 0),
(351, 'MALE CELESTIN NIDELE', 'NIDELE', 'MALE CELESTIN', '2008-04-16', 'BAMESSO', 'F', 'PEUBOU ALEXANDRE', '695286548', NULL, 'DJUINE CELINE', '.', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 06:54:16', '2025-09-02 06:54:16', 1, '25A00324', 6, 0),
(352, 'FINKAM EVANA PASCALINE', 'EVANA PASCALINE', 'FINKAM', '2004-01-11', 'DOUALA', 'F', 'FOKAM EMMANUEL', '.', NULL, 'TCHUINMEGNE BEATRICE', '659992595', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:05:35', '2025-09-02 07:05:35', 1, '25A00325', 13, 0),
(353, 'HAKO KAMENI CAROLE', 'CAROLE', 'HAKO KAMENI', '2005-10-08', 'DOUALA', 'F', 'KAMENI DENIS', '677624190', NULL, 'DJOMALEU AGATHE', '.', NULL, NULL, NULL, 94, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:24:49', '2025-09-02 07:24:49', 1, '25A00326', 14, 0),
(354, 'YIMGNIA MERVEILLE LAURE', 'MERVEILLE LAURE', 'YIMGNIA', '2007-04-08', 'BANTOUM', 'F', 'KWATCHET ROSTAND', '.', NULL, 'PETGA DIANE', '678260979', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:44:01', '2025-09-02 07:44:01', 1, '25A00327', 15, 0),
(355, 'NDZIE EBODE CECILE MARIE REINE', 'CECILE MARIE REINE', 'NDZIE EBODE', '2011-08-27', 'DOUALA', 'F', 'EBODE ONANA', '.', NULL, 'BELLA NDZANA', '656094860', NULL, NULL, NULL, 21, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:47:42', '2025-09-02 07:47:42', 1, '25A00328', 10, 0);
INSERT INTO `students` (`id`, `name`, `first_name`, `last_name`, `date_of_birth`, `place_of_birth`, `gender`, `parent_name`, `parent_phone`, `parent_email`, `mother_name`, `mother_phone`, `address`, `photo`, `subname`, `class_series_id`, `email`, `student_status`, `phone_number`, `birthday`, `birthday_place`, `sex`, `father_name`, `profession`, `status`, `is_new`, `is_active`, `created_at`, `updated_at`, `school_year_id`, `student_number`, `order`, `has_scholarship_enabled`) VALUES
(356, 'ONBASSILEK SOKMAK DANYELLE', 'DANYELLE', 'ONBASSILEK SOKMAK', '2010-11-01', 'DOUALA', 'F', 'SOKMAK', '679609752', NULL, 'DJIFAK', '653958707', NULL, NULL, NULL, 89, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:55:13', '2025-09-02 07:55:13', 1, '25A00329', 16, 0),
(357, 'KENFACK ELAUGE NAOMIE', 'ELAUGE NAOMIE', 'KENFACK', '2007-08-05', 'BAMENDOU', 'F', 'LEMEKOUTE CHRISTOPHE', '698770202', NULL, 'KEUGNE ELISABETH', '651300336', NULL, NULL, NULL, 102, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 07:55:47', '2025-09-02 07:55:47', 1, '25A00330', 7, 0),
(358, 'MAGNE KEMNEUGNE LINE MEGANE', 'LINE MEGANE', 'MAGNE KEMNEUGNE', '2008-05-15', 'DOUALA', 'F', 'KEMNEUGNE', '650975003', NULL, 'DJIKOM BLANDINE', '678150005', NULL, NULL, NULL, 37, NULL, 'new', NULL, NULL, NULL, NULL, NULL, NULL, 'new', 1, 1, '2025-09-02 08:01:48', '2025-09-02 08:01:48', 1, '25A00331', 12, 0);

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
(4, 4, 1, 0, NULL, NULL, NULL, NULL, '2025-08-04 07:50:46', '2025-08-04 07:50:46'),
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
(37, 38, 1, 0, NULL, NULL, NULL, NULL, '2025-08-06 14:44:51', '2025-08-06 14:44:51'),
(38, 39, 1, 1, '2025-09-01', '2025-09-01', 15, NULL, '2025-08-06 14:58:49', '2025-09-01 09:18:57'),
(40, 41, 1, 0, NULL, NULL, NULL, NULL, '2025-08-07 11:11:28', '2025-08-07 11:11:28'),
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
(65, 67, 1, 0, NULL, NULL, NULL, NULL, '2025-08-12 10:56:15', '2025-08-12 10:56:15'),
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
(139, 143, 1, 1, '2025-08-27', '2025-08-27', 15, NULL, '2025-08-27 10:52:26', '2025-08-27 10:52:34'),
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
(213, 217, 1, 0, NULL, NULL, NULL, NULL, '2025-08-29 13:26:41', '2025-08-29 13:26:41'),
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
(259, 264, 1, 0, NULL, NULL, NULL, NULL, '2025-08-30 07:23:27', '2025-08-30 07:23:27'),
(260, 265, 1, 1, '2025-08-30', '2025-08-30', 16, NULL, '2025-08-30 07:25:43', '2025-08-30 07:25:48'),
(261, 266, 1, 0, NULL, NULL, NULL, NULL, '2025-08-30 07:26:47', '2025-08-30 07:26:47'),
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
(354, 358, 1, 1, '2025-09-02', '2025-09-02', 16, NULL, '2025-09-02 08:02:06', '2025-09-02 08:02:09');

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
-- Structure de la table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
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

INSERT INTO `teachers` (`id`, `first_name`, `last_name`, `phone_number`, `email`, `address`, `date_of_birth`, `gender`, `qualification`, `hire_date`, `is_active`, `type_personnel`, `user_id`, `department_id`, `qr_code`, `expected_arrival_time`, `expected_departure_time`, `daily_work_hours`, `created_at`, `updated_at`) VALUES
(2, 'DJAM', 'MARCEL', '694547521', NULL, 'BAFOUSSAM', NULL, 'm', NULL, '2025-08-27', 1, 'V', 25, NULL, 'STAFF_25', '08:00:00', '17:00:00', 8.00, '2025-08-27 14:04:49', '2025-08-27 14:32:14'),
(4, 'MASSOCK', 'MASSOCK', '6657890', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 27, NULL, 'TCH_4', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:45:34', '2025-08-28 08:16:39'),
(5, 'LEONNEL STEPHANE', 'NGAKATH', '696559488', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 28, NULL, 'TCH_5', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:47:37', '2025-08-28 08:16:39'),
(6, 'MATHIEU', 'TCHAMENI', '650516446', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 29, NULL, 'TCH_6', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:51:00', '2025-08-28 08:16:39'),
(7, 'TOBIE', 'LISSOTA YOMZAK', '694593469', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 30, NULL, 'TCH_7', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:53:00', '2025-08-28 08:16:39'),
(8, 'PHILIP', 'NKONGHO TAMBE', '675155315', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 0, 'V', 31, NULL, 'STAFF_31', '08:00:00', '17:00:00', 8.00, '2025-08-28 06:57:19', '2025-08-30 07:31:30'),
(9, 'MARGUERITE', 'PAMOWA MARIE', '674134850', NULL, NULL, NULL, 'f', NULL, '2025-08-28', 1, 'V', 33, NULL, 'TCH_9', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:15:24', '2025-08-28 08:16:39'),
(10, 'JAMES', 'NGANYA TILONG', '698461021', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 34, NULL, 'TCH_10', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:16:56', '2025-08-28 08:16:39'),
(11, 'JAMES', 'NGANYA TILONG', '698461021', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 36, NULL, 'TCH_11', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:31:22', '2025-08-28 08:16:39'),
(12, 'EMANE', 'TAMUNA VERA', '652646952', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 37, NULL, 'TCH_12', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:36:41', '2025-08-28 08:16:39'),
(13, 'NESTOR', 'KAMTCHOU', '696289883', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 38, NULL, 'TCH_13', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:37:48', '2025-08-28 08:16:39'),
(14, 'ANDRE', 'MBOCK NOL', '693298209', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 39, NULL, 'TCH_14', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:38:45', '2025-08-28 08:16:39'),
(15, 'THIERRY', 'TIODA', '681039987', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 40, NULL, 'TCH_15', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:42:33', '2025-08-28 08:16:39'),
(16, 'BOUBAKARY', 'BRAHIMA', '690701677', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 41, NULL, 'TCH_16', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:43:39', '2025-08-28 08:16:39'),
(17, 'OSCAR', 'MAMPASSI', '697469756', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 42, NULL, 'TCH_17', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:45:16', '2025-08-28 08:16:39'),
(18, 'JOEL', 'TCHEBEI TCHOUNKE', '678307239', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 43, NULL, 'TCH_18', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:46:46', '2025-08-28 12:06:50'),
(19, 'ALEXIS', 'KOUAZE NANA', '699091048', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 44, NULL, 'TCH_19', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:48:00', '2025-08-28 08:16:39'),
(20, 'VIVIEN', 'NONO GILLES', '696725515', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 45, NULL, 'TCH_20', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:48:49', '2025-08-28 08:16:39'),
(21, 'ROGER CEDRIC', 'NGANKOUE MANGA', '696474808', NULL, NULL, NULL, 'm', NULL, '2025-08-28', 1, 'V', 46, NULL, 'TCH_21', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:49:43', '2025-08-28 08:16:39'),
(22, 'HERVE', 'YOSSA', '691675326', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 47, NULL, 'TCH_22', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:50:31', '2025-08-28 08:16:39'),
(23, 'FLORENT', 'EPOH DAVID', '699174337', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 48, NULL, 'TCH_23', '08:00:00', '17:00:00', 8.00, '2025-08-28 07:51:23', '2025-08-28 08:16:39'),
(24, 'ROMARIC', 'NGAPMEU TCHABONG', '679769812', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 49, NULL, 'TCH_24', '08:00:00', '17:00:00', 8.00, '2025-08-28 08:13:31', '2025-08-28 08:16:39'),
(25, 'JOSEPH SARA', 'BILONGO’O BILONGO’O', '693740710', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 52, NULL, 'TCH_25', '08:00:00', '17:00:00', 8.00, '2025-08-28 08:14:35', '2025-08-28 08:16:39'),
(26, 'GEORGETTE', 'NDONDOCK NICAISE', '674385786', NULL, NULL, NULL, NULL, NULL, '2025-08-28', 1, 'V', 53, NULL, 'TCH_26', '08:00:00', '17:00:00', 8.00, '2025-08-28 08:16:20', '2025-08-28 08:16:39'),
(27, 'JACQUELINE', 'PIEFLEYOU', '655689082', NULL, NULL, NULL, 'f', NULL, '2025-08-28', 1, 'P', NULL, NULL, 'STAFF_56', '08:00:00', '17:00:00', 8.00, '2025-08-28 10:37:11', '2025-08-28 12:09:11'),
(28, 'CHRISTIAN', 'KUITCHOU', '696118001', 'christian.kuitchou@cpb.cm', 'Douala', '1980-03-15', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_28', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(29, 'MOÏSE', 'OWONO MVENG', '696118002', 'moise.owono@cpb.cm', 'Yaoundé', '1982-07-20', 'm', 'Master Physique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_29', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(30, 'MILIXANDRE DELFLORE', 'PETKEU', '696118003', 'milixandre.petkeu@cpb.cm', 'Douala', '1979-11-10', 'm', 'Licence Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_30', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(31, 'DONATIEN', 'NDZANA', '696118004', 'donatien.ndzana@cpb.cm', 'Bafoussam', '1981-09-25', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_31', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(32, 'SUZANNE', 'NGO SAMNICK', '696118005', 'suzanne.samnick@cpb.cm', 'Douala', '1983-05-12', 'f', 'Master Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_32', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(33, 'SANTANA', 'MOUKORY', '696118006', 'santana.moukory@cpb.cm', 'Yaoundé', '1980-12-08', 'f', 'Licence Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_33', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(34, 'GERAUDE', 'BATOUANEN MOBAN', '696427010', 'geraude.batouanen@cpb.cm', 'Douala', '1978-01-30', 'm', 'Licence Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_34', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(35, 'NGNINZEKO', 'BOGNI', '696961822', 'ngninzeko.bogni@cpb.cm', 'Bamenda', '1984-06-18', 'm', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_35', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(36, 'EMERENTIEN', 'LY-INBE', '698352081', 'emerentien.lyinbe@cpb.cm', 'Douala', '1981-04-22', 'm', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_36', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(37, 'DESIRE', 'HOUNSOU', '679549423', 'desire.hounsou@cpb.cm', 'Yaoundé', '1982-10-14', 'm', 'Master Économie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_37', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(38, 'JEAN', 'BEKOMBO POUNGOUE', '672939521', 'jean.bekombo@cpb.cm', 'Douala', '1979-08-05', 'm', 'Licence Droit', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_38', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(39, 'PLACIDE', 'PLACIDE', '681879734', 'placide.placide@cpb.cm', 'Yaoundé', '1983-02-16', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_39', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(40, 'ULRICH LANDRY', 'NJIKI', '697957200', 'ulrich.njiki@cpb.cm', 'Bafoussam', '1980-07-28', 'm', 'Licence Informatique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_40', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(41, 'CYRILLE', 'TATSINKOU TENE', '690151661', 'cyrille.tatsinkou@cpb.cm', 'Douala', '1981-09-11', 'm', 'Master Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_41', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(42, 'JUDITH FLORE', 'MEKUATE', '694088658', 'judith.mekuate@cpb.cm', 'Yaoundé', '1982-12-03', 'f', 'Licence Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_42', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(43, 'ELISE', 'NGANSI WONSSI', '697458185', 'elise.ngansi@cpb.cm', 'Douala', '1984-05-19', 'f', 'Master Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_43', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(44, 'BERTRAND', 'TONFACK', '670403323', 'bertrand.tonfack@cpb.cm', 'Bafoussam', '1979-11-07', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_44', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(45, 'NESTOR', 'KAMENI', '674831332', 'nestor.kameni@cpb.cm', 'Douala', '1983-03-23', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_45', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(46, 'TALLA', 'AURELIEN', '658047838', 'talla.aurelien@cpb.cm', 'Yaoundé', '1980-08-15', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_46', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(47, 'MARCEL', 'WOULINA', '674667016', 'marcel.woulina@cpb.cm', 'Douala', '1982-06-02', 'm', 'Master Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_47', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(48, 'NADEGE', 'WOUASSI', '673697712', 'nadege.wouassi@cpb.cm', 'Yaoundé', '1981-10-26', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_48', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(49, 'DEBORAH', 'NGO NSEGBE', '670609624', 'deborah.ngo@cpb.cm', 'Douala', '1983-04-18', 'f', 'BTS Secrétariat', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_49', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(50, 'SOLANGE', 'BI', '674536333', 'solange.bi@cpb.cm', 'Bafoussam', '1980-07-09', 'f', 'Licence Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_50', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(51, 'DJOMATCHOUA', 'DJOMATCHOUA', '678963262', 'djomatchoua.djomatchoua@cpb.cm', 'Douala', '1982-12-21', 'f', 'Master Économie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_51', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(52, 'SANDRINE NATHALIE', 'NOUBISSIE', '696976171', 'sandrine.noubissie@cpb.cm', 'Yaoundé', '1984-01-13', 'f', 'Licence Droit', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_52', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(53, 'SA', 'BEITI A MOUBIE', '674007378', 'sa.beiti@cpb.cm', 'Douala', '1981-09-05', 'f', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_53', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(54, 'PATIENCE', 'NZOUYA', '675120578', 'patience.nzouya@cpb.cm', 'Yaoundé', '1979-11-27', 'f', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_54', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(55, 'UGUETTE PHILOMENE', 'MADADJEU', '697345879', 'uguette.madadjeu@cpb.cm', 'Bafoussam', '1983-02-14', 'f', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_55', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(56, 'K', 'NOUTAMOUN', '696118029', 'k.noutamoun@cpb.cm', 'Douala', '1980-08-06', 'f', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_56', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(57, 'LUCIENNE FLORE', 'LUCIENNE FLORE', '696118030', 'lucienne.flore@cpb.cm', 'Yaoundé', '1982-05-18', 'f', 'BTS Secrétariat', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_57', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(58, 'IDA CLAUDINE', 'MAKOUPO TALLA', '696118031', 'ida.makoupo@cpb.cm', 'Douala', '1981-10-30', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_58', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(59, 'JOSEPHINE', 'TCHIEDJIO', '674611961', 'josephine.tchiedjio@cpb.cm', 'Bafoussam', '1984-03-12', 'f', 'Master Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_59', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(60, 'TCHUIGANG', 'MBAKOP', '656287367', 'tchuigang.mbakop@cpb.cm', 'Douala', '1979-07-24', 'f', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_60', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(61, 'PULCHERIE', 'DJEUKOUA', '675382461', 'pulcherie.djeukoua@cpb.cm', 'Yaoundé', '1982-12-16', 'f', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_61', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(62, 'ANDRIENNE', 'GUEKAM', '699184325', 'andrienne.guekam@cpb.cm', 'Douala', '1983-04-08', 'f', 'Licence Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_62', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(63, 'LEOCARDIE', 'TAGNE', '673427073', 'leocardie.tagne@cpb.cm', 'Bafoussam', '1980-09-20', 'f', 'Master Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_63', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(64, 'MERLINE', 'FOMEKONG KENNE', '691250098', 'merline.fomekong@cpb.cm', 'Douala', '1984-01-02', 'm', 'Licence Physique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_64', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(65, 'YACINTHE', 'NYANGONO', '655099808', 'yacinthe.nyangono@cpb.cm', 'Yaoundé', '1981-06-14', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_65', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(66, 'THALES', 'TCHEUSONG', '655428206', 'thales.tcheusong@cpb.cm', 'Douala', '1979-11-26', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_66', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(67, 'JEAN MARIE', 'NJINE DEHELALE', '693249266', 'jean.njine@cpb.cm', 'Bafoussam', '1983-02-18', 'm', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_67', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(68, 'RAÏSSA DANIE', 'MEBOT', '694087843', 'raissa.mebot@cpb.cm', 'Douala', '1982-08-10', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_68', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(69, 'STEPHANE EVINDI', 'FRANCK', '677999266', 'stephane.franck@cpb.cm', 'Yaoundé', '1980-05-22', 'm', 'BTS Informatique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_69', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(70, 'BENOIT GERARD', 'BALOMLEKE', '695164220', 'benoit.balomleke@cpb.cm', 'Douala', '1981-12-04', 'm', 'Licence Droit', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_70', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:04', '2025-08-28 20:47:49'),
(71, 'PIUS COLLINS', 'KOWA', '699734094', 'pius.kowa@cpb.cm', 'Bafoussam', '1984-07-16', 'm', 'Master Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_71', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(72, 'DUMONT', 'NAWESSI', '650466778', 'dumont.nawessi@cpb.cm', 'Douala', '1979-03-28', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_72', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(73, 'CHRISTIAN', 'KUIZING', '699824521', 'christian.kuizing@cpb.cm', 'Yaoundé', '1982-10-10', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_73', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(74, 'AMOUR', 'BOUM GWETH', '650824521', 'amour.boum@cpb.cm', 'Douala', '1983-01-22', 'm', 'Licence Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_74', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(75, 'NGOGUE', 'NGNOGUE', '699893310', 'ngogue.ngnogue@cpb.cm', 'Bafoussam', '1980-09-14', 'm', 'Master Économie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_75', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(76, 'FREDERIC', 'FREDERIC', '691015957', 'frederic.frederic@cpb.cm', 'Douala', '1984-04-06', 'm', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_76', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(77, 'PAUL', 'NJEM IV', '696014985', 'paul.njem@cpb.cm', 'Yaoundé', '1981-11-18', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_77', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(78, 'ELYSEE', 'TASSO', '653675880', 'elysee.tasso@cpb.cm', 'Douala', '1979-06-30', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_78', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(79, 'JUDITH', 'DZOKOU KENGNE', '694859867', 'judith.dzokou@cpb.cm', 'Bafoussam', '1982-12-12', 'f', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_79', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(80, 'GABRIEL', 'TCHEKWANDEU', '676373457', 'gabriel.tchekwandeu@cpb.cm', 'Douala', '1983-08-24', 'm', 'Licence Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_80', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(81, 'GENEVIEVE', 'ONGMETANA', '690151600', 'genevieve.ongmetana@cpb.cm', 'Yaoundé', '1980-02-16', 'f', 'BTS Secrétariat', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_81', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(82, 'MARCELINE', 'GUEMDJO', '693310561', 'marceline.guemdjo@cpb.cm', 'Douala', '1984-07-08', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_82', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(83, 'ELEONORE', 'MOMO', '652553099', 'eleonore.momo@cpb.cm', 'Bafoussam', '1981-03-20', 'f', 'Master Anglais', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_83', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:14:04'),
(84, 'ODELE', 'NGO NYOBE', '698950519', 'odele.ngo@cpb.cm', 'Douala', '1982-10-02', 'f', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_84', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(85, 'GISELE', 'DJUELA FOKOU', '652 55 30 99', 'gisele.djuela@cpb.cm', 'Yaoundé', '1979-05-14', 'f', 'BTS Comptabilité', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_85', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:15:03'),
(86, 'LIONIE', 'LOKIO TCHANANG', '695475535', 'lionie.lokio@cpb.cm', 'Douala', '1983-11-26', 'f', 'Licence Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_86', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(87, 'TOUFFEU', 'KAMBEU', '697320739', 'touffeu.kambeu@cpb.cm', 'Bafoussam', '1984-01-18', 'f', 'Master Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_87', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(88, 'MIREILLE', 'MIREILLE', '695148001', 'mireille.mireille@cpb.cm', 'Douala', '1980-09-10', 'f', 'Licence Physique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_88', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(89, 'ERNEST', 'TCHOUDJIN', '670248900', 'ernest.tchoudjin@cpb.cm', 'Yaoundé', '1982-04-22', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_89', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(90, 'JULES ARSENE', 'NDONI', '681613033', 'jules.ndoni@cpb.cm', 'Douala', '1981-12-04', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_90', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(91, 'ARIANE', 'SIMO TAKONGUE', '697 32 07 39', 'ariane.simo@cpb.cm', 'Bafoussam', '1983-07-16', 'f', 'Master Biologie', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_91', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:16:03'),
(92, 'RAISSA', 'RAISSA', '652148494', 'raissa.raissa@cpb.cm', 'Douala', '1980-02-28', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_92', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(93, 'HUBERT', 'YOUSSA', '677191795', 'hubert.youssa@cpb.cm', 'Yaoundé', '1984-08-10', 'm', 'BTS Informatique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_93', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(94, 'JEAN JACQUES', 'NGALAKO NJEUNGA', '654377605', 'jean.ngalako@cpb.cm', 'Douala', '1979-05-22', 'm', 'Licence Droit', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_94', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:26:04'),
(95, 'CONSTANT', 'FOGANG NGOUFO', '681613033', 'constant.fogang@cpb.cm', 'Bafoussam', '1982-11-04', 'm', 'Master Anglais', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_95', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:26:47'),
(96, 'JOSEPH KINDONG', 'YHAM', '675339919', 'joseph.yham@cpb.cm', 'Douala', '1981-03-16', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_96', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(97, 'FOMAGNOUA', 'DC FOTSOP', '652 14 84 94', 'fomagnoua.fotsop@cpb.cm', 'Yaoundé', '1983-09-28', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_97', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:27:21'),
(98, 'JOSEPHINE B', 'JOHNIE', '677212371', 'josephine.johnie@cpb.cm', 'Douala', '1984-01-10', 'f', 'Licence Géographie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_98', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(99, 'GUSTAVE NOSO', 'PEKWEKEH', '654 37 76 05', 'gustave.pekwekeh@cpb.cm', 'Bafoussam', '1980-06-22', 'm', 'Master Économie', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_99', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:28:32'),
(100, 'DADY JOEL', 'NKOUAMO', '671711951', 'dady.nkouamo@cpb.cm', 'Douala', '1982-12-14', 'm', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_100', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(101, 'GERADINE', 'NDASSI', '672719607', 'geradine.ndassi@cpb.cm', 'Yaoundé', '1981-04-06', 'f', 'BTS Secrétariat', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_101', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(102, 'WAKUNA', 'WAKUNA', '652216968', 'wakuna.wakuna@cpb.cm', 'Douala', '1979-10-18', 'm', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_102', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(103, 'ERIC', 'KUMGAHA TANGNI', '674769687', 'eric.kumgaha@cpb.cm', 'Bafoussam', '1983-07-30', 'm', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_103', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(104, 'MIRABEL', 'MBULLE', '674378487', 'mirabel.mbulle@cpb.cm', 'Douala', '1984-02-12', 'f', 'Licence Chimie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_104', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(105, 'JUNIOR', 'EGBENCHUNG BISONG', '671818252', 'junior.egbenchung@cpb.cm', 'Yaoundé', '1980-08-24', 'm', 'BTS Électronique', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_105', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:29:20'),
(106, 'PHILIP', 'NKONGHO TAMBE', '671711951', 'philip.nkongho@cpb.cm', 'Douala', '1982-05-16', 'm', 'Licence Physique', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_106', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:32:33'),
(107, 'GILEAN', 'ANAM', '654193306', 'gilean.anam@cpb.cm', 'Bafoussam', '1981-11-08', 'm', 'Master Anglais', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_107', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(108, 'ASHU', 'TEZE', '680093485', 'ashu.teze@cpb.cm', 'Douala', '1983-03-20', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_108', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(109, 'NDJOH', 'FRANCK DARIUS', '672126000', 'ndjoh.franck@cpb.cm', 'Yaoundé', '1984-09-02', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_109', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(110, 'NDJOH', 'NDJOH', '651074407', 'ndjoh.ndjoh@cpb.cm', 'Douala', '1979-01-14', 'm', 'Licence Droit', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_110', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(111, 'ANGELA DIOH', 'NJINYERU', '675366578', 'angela.njinyeru@cpb.cm', 'Bafoussam', '1982-06-26', 'f', 'Master Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_111', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(112, 'GILTON LAISIN', 'TANTOH', '651067920', 'gilton.tantoh@cpb.cm', 'Douala', '1980-12-18', 'm', 'Licence Géographie', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_112', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:38:10'),
(113, 'PRINCE WILL', 'LEKEAKA', '678457764', 'prince.lekeaka@cpb.cm', 'Yaoundé', '1983-04-10', 'm', 'BTS Informatique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_113', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(114, 'MIRABEL WEI', 'MBAIN', '696118087', 'mirabel.mbain@cpb.cm', 'Douala', '1981-10-22', 'f', 'Licence Mathématiques', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_114', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(115, 'THEOPHELEN', 'ATEMKENG', '680093485', 'theophelen.atemkeng@cpb.cm', 'Bafoussam', '1984-07-14', 'm', 'Master Économie', '2024-12-12', 1, 'SP', NULL, NULL, 'TCH_115', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-30 07:38:54'),
(116, 'COLLIN KOLOA', 'MOTO', '696118089', 'collin.moto@cpb.cm', 'Douala', '1979-02-06', 'm', 'Licence Philosophie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_116', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(117, 'DEM', 'KOUZO NGRUNTE', '696118090', 'dem.kouzo@cpb.cm', 'Yaoundé', '1982-09-18', 'm', 'BTS Électronique', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_117', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(118, 'JACKON', 'KADJO', '696118091', 'jackon.kadjo@cpb.cm', 'Douala', '1983-05-30', 'm', 'Licence Histoire', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_118', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(119, 'BARTHOLOMEW', 'TUMBU', '696118092', 'bartholomew.tumbu@cpb.cm', 'Bafoussam', '1980-11-12', 'm', 'Master Biologie', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_119', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(120, 'SIDONIE', 'FUH', '696118093', 'sidonie.fuh@cpb.cm', 'Douala', '1984-03-24', 'f', 'Licence Français', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_120', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49'),
(121, 'ELVIS METOUKE', 'MESUMBE', '696118094', 'elvis.mesumbe@cpb.cm', 'Yaoundé', '1981-08-16', 'm', 'BTS Comptabilité', '2024-12-12', 1, 'V', NULL, NULL, 'TCH_121', '08:00:00', '17:00:00', 8.00, '2025-08-28 20:42:05', '2025-08-28 20:47:49');

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

INSERT INTO `users` (`id`, `name`, `username`, `email`, `contact`, `photo`, `qr_code`, `role`, `qualification`, `is_active`, `working_school_year_id`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrateur', 'admin', 'admin@gsbpl.com', '+237696118389', NULL, NULL, 'admin', NULL, 1, NULL, NULL, '$2y$12$zYdXO4K0BvRme0PPrsvUo.ZR.WCGcMeko/iv75PnRJEwQh0hN0FCq', NULL, '2025-08-03 18:00:23', '2025-08-06 05:55:47'),
(3, 'Nnanga madeleine', 'comptable', 'comptable@gsbpl.com', '+237657256007', NULL, 'STAFF_3', 'accountant', NULL, 1, NULL, NULL, '$2y$12$i1YAhwTPms2T4ZFLZ8JDvuCMo6Hp.lTHYQjvoixWF5RXVpM4n//0y', NULL, '2025-08-03 18:00:23', '2025-08-26 13:01:48'),
(4, 'Utilisateur Test', 'user.test', 'user@gsbpl.com', NULL, NULL, NULL, 'admin', NULL, 1, NULL, NULL, '$2y$12$VJr5Z1nbNIkVlRGb2qTm1OP6L2ndargZm6aChFSpn3i6isifWLr5e', NULL, '2025-08-03 18:00:23', '2025-08-03 18:00:23'),
(12, 'MEFOBEU', 'mefobeu', 'mefobeu@gmail.com', '+237690866410', 'https://admin1.cpb-douala.com/storage/user_photos/6d5e365d-b2e1-414a-8f2c-63dbf46fe3c0.jpeg', 'STAFF_12', 'accountant', NULL, 1, NULL, '2025-08-04 09:35:30', '$2y$12$s5Ylka6qib5ppn2LwgsjAO3tCiN56n8vSBgl3lY1LWX8z/yx0YbaC', NULL, '2025-08-04 09:35:30', '2025-08-27 10:49:07'),
(15, 'Kouoh Ashley', 'Ashley', 'Ashley@gmail.com', '+237655240303', NULL, 'STAFF_15', 'secretaire', NULL, 1, NULL, '2025-08-19 11:37:34', '$2y$12$WfrvU/BJoiavDWHhB0GPS.Ria6mcmNuHQIedCGkeKnWS.9ivwJtFW', NULL, '2025-08-19 11:37:34', '2025-08-27 10:49:07'),
(16, 'ELONG ANGE', 'ange', 'ange@gmail.com', '699111062', NULL, 'STAFF_16', 'secretaire', NULL, 1, NULL, '2025-08-19 11:54:23', '$2y$12$HJpnkk62glKvg3qZnn4EIe2V3CRUssYcmJFz2URfCosNpVYkbIA6i', NULL, '2025-08-19 11:54:23', '2025-08-27 10:49:07'),
(17, 'soffack kelly', 'kelly', 'kelly@gmail.com', '‪+237651818278‬', NULL, 'STAFF_17', 'comptable_superieur', NULL, 1, NULL, '2025-08-20 09:39:16', '$2y$12$s3V25IGta.3aBjmv1IQWKOSkY1Aw7vOkt628w.ZtSGGfp8wlibg0m', NULL, '2025-08-20 09:39:16', '2025-08-27 10:49:07'),
(18, 'DJAM MARCEL', 'djam', 'djam@gmail.com', '694547521', NULL, 'STAFF_18', 'accountant', NULL, 1, NULL, '2025-08-27 11:03:41', '$2y$12$7vxntH44yJAD/FOdeGufauxy73hHeNCuNslaD2MZZu5O.nXbZdc9S', NULL, '2025-08-27 11:03:41', '2025-08-27 20:09:18'),
(19, 'PIEFLEYOU JACQUELINE', 'jacqueline', 'jacqueline@gmail.com', '655 68 90 82', NULL, 'STAFF_19', 'bibliothecaire', NULL, 1, NULL, '2025-08-27 11:04:19', '$2y$12$C/21anePpLL6HCZal7LdV.mAyLxX1foW.W7qo.LjkXGCf1dtYQqQW', NULL, '2025-08-27 11:04:19', '2025-09-01 13:00:16'),
(20, 'MASSOCK', 'massock', 'massock@gmail.com', NULL, NULL, 'STAFF_20', 'accountant', NULL, 1, NULL, '2025-08-27 11:04:51', '$2y$12$3vB8cHGPQqZT1nlJ9LnBnOo76XbIdMCrdi.CSHBxDNhUomG0cfwAC', NULL, '2025-08-27 11:04:51', '2025-08-27 20:09:18'),
(21, 'NGAKATH LEONNEL STEPHANE', 'leonel', 'leonel@gmail.com', '696 55 94 88', NULL, 'STAFF_21', 'accountant', NULL, 1, NULL, '2025-08-27 11:05:26', '$2y$12$vRj08/fDgX1Ccii3R2XW2.SwN305ISIdEbN74Lfu1YUOeNjNJ5QD6', NULL, '2025-08-27 11:05:26', '2025-08-27 20:09:18'),
(22, 'TCHAMENI MATHIEU', 'mathieu', 'mathieu@gmail.com', '650 51 64 46', NULL, 'STAFF_22', 'accountant', NULL, 1, NULL, '2025-08-27 11:06:13', '$2y$12$FUC5xrM9yPTcVsWcUUINpOEXbHma/1CJPkz1I8AslrhIfGFKy95AK', NULL, '2025-08-27 11:06:13', '2025-08-27 20:09:18'),
(23, 'LISSOTA YOMZAK TOBIE', 'tobie', 'tobie@gmail.com', '694 59 34 69', NULL, 'STAFF_23', 'accountant', NULL, 1, NULL, '2025-08-27 11:09:13', '$2y$12$EE.6B99hZa1LrHlXqw23jOVvmSP7gVkpYWEijKztjGzW7UHcRr7lu', NULL, '2025-08-27 11:09:13', '2025-08-27 20:09:18'),
(25, 'DJAM MARCEL', 'ZUDJIE', 'ZUDJIE@school.local', NULL, NULL, 'STAFF_25', 'teacher', NULL, 1, NULL, NULL, '$2y$12$vu6MMwlPAo0DkJLRoYaVGeNnBaTXrhsZ1yKPFGlhyluo28DW4kSyW', NULL, '2025-08-27 14:04:49', '2025-08-27 14:32:14'),
(27, 'MASSOCK MASSOCK', 'EZ', 'EZ@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$mtdcFByYLwEU6dio4EvWs.Iv.8L0RDZWYvnbvWfoBxIu30VllUrra', NULL, '2025-08-28 06:45:34', '2025-08-28 06:45:34'),
(28, 'LEONNEL STEPHANE NGAKATH', 'AZERTY', 'AZERTY@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$S0mx5d8AHADRK4VI7y06k.nK5jbLFxLMi8ku7IkptI/QXn7H8e4H6', NULL, '2025-08-28 06:47:37', '2025-08-28 06:47:37'),
(29, 'MATHIEU TCHAMENI', 'AZRV7', 'AZRV7@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$Rrr4Qf2aMLQjZItLz2OkXOTkQjOMjd6PSDXBH52V4Y48jLQPaLoku', NULL, '2025-08-28 06:51:00', '2025-08-28 06:51:00'),
(30, 'TOBIE LISSOTA YOMZAK', 'AZDS', 'AZDS@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$p7136HVbGEtgHjX6ZL1UpuyuEC3x37pUOXuJVAodZnEnp6sp9tf0O', NULL, '2025-08-28 06:53:00', '2025-08-28 06:53:00'),
(31, 'PHILIP NKONGHO TAMBE', '675 15 53 15', '675 15 53 15@school.local', NULL, NULL, 'STAFF_31', 'teacher', NULL, 1, NULL, NULL, '$2y$12$GCaAXdu5Djjp67nHR.zsEuquv3jDMYvb6wUG6LLDgW.JjfmwI6msy', NULL, '2025-08-28 06:57:19', '2025-08-29 06:25:17'),
(33, 'MARGUERITE PAMOWA MARIE', '674 13 48 50', '674 13 48 50@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$LgYdJscSZMaw2OyP5.NmB.nQRjbN5X.jnuBveDNiLzht4cBjXi5gG', NULL, '2025-08-28 07:15:24', '2025-08-28 07:15:24'),
(34, 'JAMES NGANYA TILONG', '698 46 10 21', '698 46 10 21@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$GpItYzg/B.kz6TMvW.uPtuzZ0rlM/XhowtxV7h5AqQ.6kOFyLPWje', NULL, '2025-08-28 07:16:56', '2025-08-28 07:16:56'),
(36, 'JAMES NGANYA TILONG', '698461021', '698461021@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$q8ZK95UKPHM7yLbEQ6/Et.pP9ySg9SG0YWxFUH.2E20RSLxDxmz9q', NULL, '2025-08-28 07:31:22', '2025-08-28 07:31:22'),
(37, 'EMANE TAMUNA VERA', '652646952', '652646952@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$4s/TQ5sie2C.Xwp.JoZ6HOY1HmOVa25K2mBHB9uMIggc0CPUz0ICm', NULL, '2025-08-28 07:36:41', '2025-08-28 07:36:41'),
(38, 'NESTOR KAMTCHOU', '696289883', '696289883@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$.jQTxn577P5rPXCMwhx58u.ZaVglALLMYo1SHYjNOBe7Pz05o/vAW', NULL, '2025-08-28 07:37:48', '2025-08-28 07:37:48'),
(39, 'ANDRE MBOCK NOL', '693298209', '693298209@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$Af1wvIVXzbTlfTwPZkIFJu/FM4zj/YjN7gWNgRd2eOvLN1cXKxgO2', NULL, '2025-08-28 07:38:45', '2025-08-28 07:38:45'),
(40, 'THIERRY TIODA', '681039987', '681039987@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$n.vdwmVzCH0HRUVVbK7hFO7QkJpOwau9le6hqmE.fIJA1S1aFwghW', NULL, '2025-08-28 07:42:33', '2025-08-28 07:42:33'),
(41, 'BOUBAKARY BRAHIMA', '690701677', '690701677@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$FS2jLAFDrRQLe7oS//l/POHnDSJ3bFlXgjpcUoiX2ZVj8wKXqotA2', NULL, '2025-08-28 07:43:39', '2025-08-28 07:43:39'),
(42, 'OSCAR MAMPASSI', '697469756', '697469756@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$5DpMuCFwnDKajNdlScyQwOWlJy8Ga9LQaJ0KZbeX2xAeOiC1FVB66', NULL, '2025-08-28 07:45:16', '2025-08-28 07:45:16'),
(43, 'JOEL TCHEBEI TCHOUNKE', '678307239', '678307239@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$ZMxEwAQBX0/kgxXPxGRKEegoYVl6ozg0Et0FnBd.dq84Qyxl2uCoa', NULL, '2025-08-28 07:46:46', '2025-08-28 07:46:46'),
(44, 'ALEXIS KOUAZE NANA', '699091048', '699091048@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$z7Kkoft7EskTqfRnKSijiemyd1Spdjxi4ObsLEyPsLQC0IfCFdjVq', NULL, '2025-08-28 07:48:00', '2025-08-28 07:48:00'),
(45, 'VIVIEN NONO GILLES', '696725515', '696725515@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$xJY5lZhUusZCLvBxEhXUjO0xhenZSfNM7nxflInswdoLMFf3N0TDS', NULL, '2025-08-28 07:48:49', '2025-08-28 07:48:49'),
(46, 'ROGER CEDRIC NGANKOUE MANGA', '696474808', '696474808@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$qIFAQBwwUjEebtImLJMIE.Dedp9039Uli/Kzo3kJGGwXfWdvTbQi6', NULL, '2025-08-28 07:49:43', '2025-08-28 07:49:43'),
(47, 'HERVE YOSSA', '691675326', '691675326@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$GrPGcGZI1gL5iIhr2l87w.PLltJiZ8hV8JkuRaT8jYBae79bt501u', NULL, '2025-08-28 07:50:31', '2025-08-28 07:50:31'),
(48, 'FLORENT EPOH DAVID', '699174337', '699174337@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$d.a5fUkCIy6SrPwFS6au5u4WzqMEGygZjbHAIsYtVH2ya7LbYr8kW', NULL, '2025-08-28 07:51:23', '2025-08-28 07:51:23'),
(49, 'ROMARIC NGAPMEU TCHABONG', '679769812', '679769812@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$PP9JrLxZVinQ0qFpf/H0xOOF8YgTHGNNQJOZAD7qkKpK90O2MauIG', NULL, '2025-08-28 08:13:31', '2025-08-28 08:13:31'),
(52, 'JOSEPH SARA BILONGO’O BILONGO’O', '693740710', '693740710@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$c.YMqlxd/aw7y019czI.UOXzPzc/11.ogO/M8Il/ssY.WY8rs8igy', NULL, '2025-08-28 08:14:35', '2025-08-28 08:14:35'),
(53, 'GEORGETTE NDONDOCK NICAISE', '674385786', '674385786@school.local', NULL, NULL, NULL, 'teacher', NULL, 1, NULL, NULL, '$2y$12$Al4QScBVmVYu9KfGZIXpveQCQIUr4SrECbwuXZ845SPKwTS.4.SmG', NULL, '2025-08-28 08:16:20', '2025-08-28 08:16:20'),
(57, 'M. TCHEKWANDEU Gabriel', 'Gabriel', 'Gabriel@cpb-douala.cm', '696 01 49 85', NULL, 'STAFF_57', 'responsable_pedagogique', 'Maitrise', 1, NULL, '2025-08-28 23:35:57', '$2y$12$ex7l86o.9X52L1i3kMHSGOGzfcpo96tWfyISvxPnFrSupuc3GeDLK', NULL, '2025-08-28 23:35:57', '2025-08-29 00:45:04'),
(58, 'M. ESUA Michael', 'Michael', 'Michael@cpb-douala.com', '682 81 20 61', NULL, 'STAFF_58', 'dean_of_studies', 'GCE A+1', 1, NULL, '2025-08-28 23:37:08', '$2y$12$wESAWwEWCmseJoQR8/CvWe04o7G4ZhkuNUt4rRP/AhNhK9oWLRwLm', NULL, '2025-08-28 23:37:08', '2025-08-29 00:45:04'),
(59, 'M. NJIEYA Georges', 'Georges', 'Georges@cpb-douala.com', '677 96 13 95', NULL, 'STAFF_59', 'censeur_esg', 'TMSI (bac +2)', 1, NULL, '2025-08-28 23:38:11', '$2y$12$.FmBdlpGvt3IoD.3DJxzquqcp95.o9licXtOQdK5hcGZpeK/9/j2G', NULL, '2025-08-28 23:38:11', '2025-08-29 00:45:04'),
(60, 'Mme NGO SAMNICK Suzanne', 'Suzanne', 'Suzanne@cpb-douala.com', '683 26 30 02', NULL, 'STAFF_60', 'censeur', 'BAC + 2', 1, NULL, '2025-08-28 23:39:11', '$2y$12$mnrNU3XKXrtkWy4I0Us1sue/svsXEG4k4ywMcUDA5qSUSbj/Sr5Ly', NULL, '2025-08-28 23:39:11', '2025-08-29 00:45:04'),
(61, 'M. HEUYO Patrice', 'Patrice', 'Patrice@cpb-douala.com', '699 17 89 15', NULL, 'STAFF_61', 'surveillant_general', 'DUT', 1, NULL, '2025-08-28 23:40:16', '$2y$12$x6U/kFFLLPeOt5BiWXSxYe/l5O7A3t1icZtE60lkRbxXlbe06tDvO', NULL, '2025-08-28 23:40:16', '2025-08-29 00:45:04'),
(62, 'M. MBAH Dickson', 'Dickson', 'Dickson@cpb-douala.com', NULL, NULL, 'STAFF_62', 'surveillant_secteur', 'GCE A +1', 1, NULL, '2025-08-28 23:41:33', '$2y$12$q/9BN4xrGkfjtP6WeKKaWu.9Drksn.zS7mL1as.gFQXpqshMoS3Oi', NULL, '2025-08-28 23:41:33', '2025-08-29 00:45:04'),
(63, 'M. YAGAÏ TIZI', 'TIZI', 'TIZI@cpb-douala.com', '697 83 87 17', NULL, 'STAFF_63', 'surveillant_secteur', 'PROBATOIRE', 1, NULL, '2025-08-28 23:42:26', '$2y$12$GVGM8hNNcsezXxZmfJ/r6OFUHClbsH456o81e14lJEr3RqcoauARW', NULL, '2025-08-28 23:42:26', '2025-08-29 00:45:04'),
(64, 'M. TAGNE LONGANG Aymar', 'Aymar', 'Aymar@cpb-douala.com', '655 54 87 18', NULL, 'STAFF_64', 'surveillant_secteur', 'PROBATOIRE', 1, NULL, '2025-08-28 23:43:16', '$2y$12$87ihb7P998foTZbq75lEXOwPLkkvk1qUebhThqrH7zXdyVNthw2Qi', NULL, '2025-08-28 23:43:16', '2025-08-29 00:45:04'),
(65, 'M. OUANDJI NGANTCHA Idriss', 'Idriss', 'Idriss@cpb-douala.com', '672 39 29 49', NULL, 'STAFF_65', 'surveillant_secteur', 'PROBATOIRE', 1, NULL, '2025-08-28 23:44:21', '$2y$12$MZpMrniBVwecATotFw017e6ypKocTRRTa6xaBsbLqb5h9W7X42xxe', NULL, '2025-08-28 23:44:21', '2025-08-29 00:45:04'),
(66, 'M. NNOHO A RIM', 'RIM', 'RIM@cpb-douala.com', '681 55 47 57', NULL, 'STAFF_66', 'surveillant_secteur', 'BAC F3', 1, NULL, '2025-08-28 23:45:33', '$2y$12$kZq5sXIvW2kz6TwSXdELsuKz6DD/TCdxqx7KFhxAEUJGh2NBfdRFq', NULL, '2025-08-28 23:45:33', '2025-08-29 00:45:04'),
(67, 'Mlle EWOUAWA PAULINE', 'PAULINE', 'PAULINE@cpb-douala.com', '678 83 20 64', NULL, 'STAFF_67', 'secretaire', 'PROBATOIRE', 1, NULL, '2025-08-28 23:46:43', '$2y$12$JePpg1gGfmEEPCsCQnmnvexAbgZlCDBD8duiIVz/H9iFhRtgNunvC', NULL, '2025-08-28 23:46:43', '2025-08-29 00:45:04'),
(73, 'Mme TCHAMBA Désirée', 'Desiree', 'Desiree@cpb-doualaa.com', '675 99 85 39', NULL, 'STAFF_73', 'chef_travaux', 'BP', 1, NULL, '2025-08-28 23:51:24', '$2y$12$FEqk8G07/dm4stD58bwRPuMkpHG6UVnUeFNWseqLwYsiUNuRJoXFS', NULL, '2025-08-28 23:51:24', '2025-08-29 00:45:04'),
(74, 'M LIBONG MATH KEVIN', 'KEVIN', 'KEVIN@cpb-doualaa.com', '655 72 97 42', NULL, 'STAFF_74', 'surveillant_secteur', 'BAC', 1, NULL, '2025-08-28 23:52:10', '$2y$12$hgUUGsbqof8fBHzMzICyGeNKuvh/TKW2FQZcvKwW9SXqKYdbiruUG', NULL, '2025-08-28 23:52:10', '2025-08-29 00:45:04'),
(75, 'M. MEDJEUGOUE  KWAMO LOIC', 'LOIC', 'LOIC@cpb-doualaa.com', '695 83 15 04', NULL, 'STAFF_75', 'surveillant_secteur', 'BTS', 1, NULL, '2025-08-28 23:53:22', '$2y$12$OAnzqS2azAL0AsVnH5lQH.3eMKRi/BbnzAZjpGOdpB8afQQl0csRC', NULL, '2025-08-28 23:53:22', '2025-08-29 00:45:04'),
(76, 'M.DAIROU', 'DAIROU', 'DAIROU@cpb-doualaa.com', '674 75 04 47', NULL, 'STAFF_76', 'chef_securite', NULL, 1, NULL, '2025-08-28 23:54:07', '$2y$12$deQXokh4xqDtZSGLS8.LROaeUmIImeMU/LmNujEHt7Q89QVKxEl9a', NULL, '2025-08-28 23:54:07', '2025-08-29 00:45:04'),
(77, 'M. DALIX CHRISTIAN', 'CHRISTIAN', 'CHRISTIAN@cpb-doualaa.com', '690 17 19 30', NULL, 'STAFF_77', 'reprographe', NULL, 1, NULL, '2025-08-28 23:55:25', '$2y$12$fAOyT8TeU1q.RxJ67vdU5eGHzBWMS4iBm2RjvkOWcWq0vY1rHTwRa', NULL, '2025-08-28 23:55:25', '2025-08-29 00:45:04'),
(81, 'M.NGUEYON Hubert Degrando', 'Degrando', 'Degrando@cpb-douala.com', '699 75 89 02', NULL, 'STAFF_81', 'principal', 'Maitrise', 1, NULL, '2025-08-29 00:24:57', '$2y$12$9p0TvtiPZzTPrP6DvlpD/.BNOy5j3ozcnl7H3XubGCbIhFjNhbSk.', NULL, '2025-08-29 00:24:57', '2025-09-02 07:17:57'),
(82, 'chris kamgang', 'chriskamgang', 'chriskamgang@gmail.com', '659339778', NULL, NULL, 'secretaire', NULL, 1, NULL, '2025-09-02 07:32:29', '$2y$12$2toUgOAYbn1o3iGXvMx3b...CQfBV.P2m5XJd8X4tUwcbNK38dHM6', NULL, '2025-09-02 07:32:29', '2025-09-02 07:32:29');

--
-- Index pour les tables déchargées
--

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
-- Index pour la table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

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
-- Index pour la table `salary_cuts`
--
ALTER TABLE `salary_cuts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salary_cuts_period_id_foreign` (`period_id`),
  ADD KEY `salary_cuts_created_by_foreign` (`created_by`),
  ADD KEY `salary_cuts_employee_id_period_id_statut_index` (`employee_id`,`period_id`,`statut`);

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
  ADD KEY `staff_attendances_supervisor_id_attendance_date_index` (`supervisor_id`,`attendance_date`);

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
-- Index pour la table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teachers_qr_code_unique` (`qr_code`),
  ADD KEY `teachers_user_id_foreign` (`user_id`),
  ADD KEY `teachers_is_active_index` (`is_active`),
  ADD KEY `teachers_phone_number_index` (`phone_number`),
  ADD KEY `teachers_last_name_first_name_index` (`last_name`,`first_name`),
  ADD KEY `teachers_department_id_index` (`department_id`);

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
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_qr_code_unique` (`qr_code`),
  ADD KEY `users_working_school_year_id_index` (`working_school_year_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `attendances`
--
ALTER TABLE `attendances`
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
-- AUTO_INCREMENT pour la table `failed_jobs`
--
ALTER TABLE `failed_jobs`
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT pour la table `needs`
--
ALTER TABLE `needs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=385;

--
-- AUTO_INCREMENT pour la table `payment_details`
--
ALTER TABLE `payment_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=746;

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
-- AUTO_INCREMENT pour la table `salary_cuts`
--
ALTER TABLE `salary_cuts`
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
-- AUTO_INCREMENT pour la table `series_subjects`
--
ALTER TABLE `series_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `staff_attendances`
--
ALTER TABLE `staff_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=359;

--
-- AUTO_INCREMENT pour la table `student_rame_status`
--
ALTER TABLE `student_rame_status`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=355;

--
-- AUTO_INCREMENT pour la table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `supervisor_class_assignments`
--
ALTER TABLE `supervisor_class_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

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
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `documentary_fees_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documentary_fees_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documentary_fees_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documentary_fees_validated_by_user_id_foreign` FOREIGN KEY (`validated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `document_folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `document_folders`
--
ALTER TABLE `document_folders`
  ADD CONSTRAINT `document_folders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_folders_parent_folder_id_foreign` FOREIGN KEY (`parent_folder_id`) REFERENCES `document_folders` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `document_permissions`
--
ALTER TABLE `document_permissions`
  ADD CONSTRAINT `document_permissions_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `document_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `employees_payroll`
--
ALTER TABLE `employees_payroll`
  ADD CONSTRAINT `employees_payroll_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Contraintes pour la table `needs`
--
ALTER TABLE `needs`
  ADD CONSTRAINT `needs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `needs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
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
  ADD CONSTRAINT `salary_cuts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salary_cuts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees_payroll` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salary_cuts_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `school_classes`
--
ALTER TABLE `school_classes`
  ADD CONSTRAINT `school_classes_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `staff_attendances_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_attendances_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_class_series_id_foreign` FOREIGN KEY (`class_series_id`) REFERENCES `class_series` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`);

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
  ADD CONSTRAINT `supervisor_class_assignments_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supervisor_class_assignments_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `teacher_attendances_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
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
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_working_school_year_id_foreign` FOREIGN KEY (`working_school_year_id`) REFERENCES `school_years` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
