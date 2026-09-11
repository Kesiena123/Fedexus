-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2026 at 10:38 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fedexus`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_logs`
--

CREATE TABLE `admin_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(255) DEFAULT NULL,
  `target_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `device_fingerprint` varchar(128) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `previous_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(128) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'string' COMMENT 'string, text, boolean, integer, json, email, url',
  `group` varchar(64) NOT NULL DEFAULT 'general',
  `label` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Selectable options for dropdown type' CHECK (json_valid(`options`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `swift_bic` varchar(255) DEFAULT NULL,
  `iban` varchar(255) DEFAULT NULL,
  `routing_number` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `branch_address` text DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `bank_logo` varchar(255) DEFAULT NULL,
  `supported_currency` varchar(8) NOT NULL DEFAULT 'USD',
  `payment_instructions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_name`, `account_number`, `swift_bic`, `iban`, `routing_number`, `branch_name`, `branch_address`, `country`, `bank_logo`, `supported_currency`, `payment_instructions`, `is_active`, `is_default`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'First National Bank', 'FreightFlow Logistics Inc.', '1234567890', 'FNBBUS33', 'US1234567890123456789012', '021000021', 'Memphis Main Branch', '100 Madison Ave, Memphis, TN 38103', 'US', NULL, 'USD', 'Include your payment reference in the transfer description.', 1, 1, 1, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(2, 'Barclays UK', 'FreightFlow Logistics Ltd.', '98765432', 'BARCGB22', 'GB29BARC20000098765432', NULL, 'London Corporate Branch', '1 Churchill Place, London E14 5HP', 'GB', NULL, 'GBP', 'Quote reference number on all transfers.', 1, 0, 2, '2026-09-10 00:10:05', '2026-09-10 00:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_type` varchar(40) NOT NULL DEFAULT 'custom',
  `subject` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guest_chat_conversations`
--

CREATE TABLE `guest_chat_conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `guest_name` varchar(140) DEFAULT NULL,
  `guest_email` varchar(180) DEFAULT NULL,
  `guest_phone` varchar(60) DEFAULT NULL,
  `subject` varchar(180) DEFAULT NULL,
  `status` enum('open','pending','closed') NOT NULL DEFAULT 'open',
  `secure_token` varchar(96) NOT NULL,
  `last_guest_message_at` timestamp NULL DEFAULT NULL,
  `last_admin_message_at` timestamp NULL DEFAULT NULL,
  `closed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `chat_status` varchar(30) NOT NULL DEFAULT 'open',
  `is_guest_notified` tinyint(1) NOT NULL DEFAULT 0,
  `is_admin_notified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guest_chat_messages`
--

CREATE TABLE `guest_chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `guest_chat_conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` enum('guest','admin','system') NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `body` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `delivery_status` varchar(20) NOT NULL DEFAULT 'sent',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_01_01_000000_create_personal_access_tokens_table', 1),
(2, '2026_01_01_000001_create_logistics_platform_tables', 1),
(3, '2026_06_27_000002_create_shipment_workflow_tables', 1),
(4, '2026_06_27_000003_add_route_metadata_to_tracking_events', 1),
(5, '2026_07_02_000010_create_admin_audit_logs_table', 1),
(6, '2026_07_02_000020_add_admin_control_fields_to_users_table', 1),
(7, '2026_07_02_000030_add_admin_permission_overrides_to_users_table', 1),
(8, '2026_07_21_000002_create_notification_preferences_table', 1),
(9, '2026_07_21_000003_create_admin_settings_table', 1),
(10, '2026_07_23_000001_create_email_logs_table', 1),
(11, '2026_07_23_000002_create_payment_settings_table', 1),
(12, '2026_07_23_000003_create_production_logistics_extensions', 1),
(13, '2026_07_23_000004_add_shipment_cost_fields', 1),
(14, '2026_07_26_000001_enhance_payment_settings_table', 1),
(15, '2026_07_26_000002_enhance_payment_transactions_table', 1),
(16, '2026_07_26_000003_create_payment_audit_logs_table', 1),
(17, '2026_07_27_134734_add_encryption_key_to_payment_settings_table', 1),
(18, '2026_07_27_150000_add_environment_credentials_to_payment_settings_table', 1),
(19, '2026_07_28_000001_create_bank_accounts_table', 1),
(20, '2026_07_28_000002_create_payment_proofs_table', 1),
(21, '2026_07_28_000003_add_payment_reference_to_payment_requests_table', 1),
(22, '2026_07_28_225517_add_file_hash_to_payment_proofs_table', 1),
(23, '2026_07_28_233438_add_notes_to_payment_proofs_table', 1),
(24, '2026_07_28_234523_add_country_to_bank_accounts_table', 1),
(25, '2026_07_29_000001_enhance_payment_requests_table', 2),
(26, '2026_07_30_000001_create_live_chat_tables', 2),
(27, '2026_07_30_150632_optimize_database_indexes_and_cleanup', 2),
(28, '2026_07_30_160000_remove_unused_tables', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'in_app',
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` tinyint(1) NOT NULL DEFAULT 1,
  `in_app` tinyint(1) NOT NULL DEFAULT 1,
  `sms` tinyint(1) NOT NULL DEFAULT 0,
  `marketing` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `stage` tinyint(3) UNSIGNED NOT NULL,
  `label` varchar(255) NOT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 20.00,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('locked','pending','checkout_created','paid','verified','failed','rejected','refunded') NOT NULL DEFAULT 'locked',
  `provider` varchar(255) DEFAULT NULL,
  `provider_reference` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `shipment_id`, `stage`, `label`, `percentage`, `amount`, `status`, `provider`, `provider_reference`, `paid_at`, `unlocked_at`, `verified_at`, `verified_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Booking Deposit', 20.00, 28.55, 'pending', NULL, NULL, NULL, '2026-09-10 00:10:05', NULL, NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(2, 1, 2, 'Pickup Confirmation', 20.00, 28.55, 'locked', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(3, 1, 3, 'International Processing', 20.00, 28.55, 'locked', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(4, 1, 4, 'Destination Hub Processing', 20.00, 28.55, 'locked', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(5, 1, 5, 'Final Delivery Release', 20.00, 28.55, 'locked', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `payment_audit_logs`
--

CREATE TABLE `payment_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event` varchar(80) NOT NULL,
  `provider` varchar(80) DEFAULT NULL,
  `provider_reference` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(8) DEFAULT NULL,
  `previous_status` enum('payment_required','payment_initiated','awaiting_verification','initiated','pending','processing','paid','verified','failed','cancelled','refunded','expired') DEFAULT NULL,
  `new_status` enum('payment_required','payment_initiated','awaiting_verification','initiated','pending','processing','paid','verified','failed','cancelled','refunded','expired') DEFAULT NULL,
  `admin_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_proofs`
--

CREATE TABLE `payment_proofs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_request_id` bigint(20) UNSIGNED NOT NULL,
  `payment_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tracking_number` varchar(255) NOT NULL,
  `payment_reference` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `file_type` varchar(20) NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `original_filename` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `resubmission_requested_at` timestamp NULL DEFAULT NULL,
  `resubmission_reason` varchar(2000) DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_requests`
--

CREATE TABLE `payment_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `priority` varchar(10) NOT NULL DEFAULT 'normal',
  `reason` text NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(8) NOT NULL DEFAULT 'USD',
  `requested_method` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'payment_required',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `secure_token` varchar(96) NOT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `internal_notes` text DEFAULT NULL,
  `payment_instructions` text DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `duplicated_from_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gateway_name` varchar(255) NOT NULL,
  `api_key` text DEFAULT NULL,
  `secret_key` text DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `webhook_secret` text DEFAULT NULL,
  `encryption_key` text DEFAULT NULL,
  `environment_credentials` text DEFAULT NULL,
  `webhook_url` varchar(255) DEFAULT NULL,
  `merchant_name` varchar(255) DEFAULT NULL,
  `processing_fee_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fixed_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_amount` decimal(12,2) DEFAULT NULL,
  `max_amount` decimal(12,2) DEFAULT NULL,
  `supported_currencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_currencies`)),
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `mode` enum('test','live') NOT NULL DEFAULT 'test',
  `currency` varchar(8) NOT NULL DEFAULT 'USD',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `gateway_name`, `api_key`, `secret_key`, `public_key`, `webhook_secret`, `encryption_key`, `environment_credentials`, `webhook_url`, `merchant_name`, `processing_fee_percent`, `fixed_fee`, `min_amount`, `max_amount`, `supported_currencies`, `config`, `mode`, `currency`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'flutterwave', 'eyJpdiI6ImErK2RKTVJFNkZlOHNKVjlmZEY5Umc9PSIsInZhbHVlIjoibXBuM3hTVy82T3p0WWxETzc3dnp2c01RSnYvY2hrMVRnY2lyaUNXR1U0aWhZV3BKTVlYMHRDY3JFbllLR1kvVSIsIm1hYyI6Ijk4OTc3MmUyMDg4OWFhNjMwODA0ZDUxOWU2ODE2N2I2NWE5YTM3MTRkOTc0Y2UyZWY2MTczYTBlNTMyNjAzM2YiLCJ0YWciOiIifQ==', 'eyJpdiI6InBISTlzUm1HZWN5dU5FRlQ2S1o2Rnc9PSIsInZhbHVlIjoiRlJkWW8vUENrMjRNN1EvOEx5QkNqSHplSlIrN2Q0WDJnMlZzU08xSHl4REpFMXpXTkRNLzFQc05hd243V2NxMyIsIm1hYyI6Ijg0ZDBjZGRkOWRjYmU0NmRlYjg1OTU3MTEyYTM2ZDAwMTczNmRlOGJlMTAxZjEzNzQ4Yjg5MzU4MDE4ZTNlODkiLCJ0YWciOiIifQ==', NULL, 'eyJpdiI6InpuL1p6S3ZoVmg3bkhTSXQxTWZEbGc9PSIsInZhbHVlIjoiQ1U5NDBabGRWTWZ0b2xSSmNZTzg0MFVCVlE1T2dwNStKMEp2NGpCUjRZOVR4Z2FpNVFReUI0SWM5RWMvUUM3ZyIsIm1hYyI6IjQ4NDkwYzk2ZmQ1OTE5NmVjNWU5MWQ3NjlkMDhlODU3MTk1NzQ2NGE3ZTE3MjQ1MWM2ZjI0M2YwZGI1NWRkMGMiLCJ0YWciOiIifQ==', 'eyJpdiI6IjFGSHlqWGRUSWpqU0tDdTRaY0w3MVE9PSIsInZhbHVlIjoiK0xrVUR3MEY0TTloNGxIQW5HZEd5N2pMWWRWTXRBbmhxclVkeGsyY2ZnNFJRRU9ZN2FtbStTSDMzY2VWdXladCIsIm1hYyI6IjQ4NTUzM2FmZDY5MDI0OWU0Mzk3OTNhZTFhN2Q1ZTNhOTA5ZGNiOTk5ZGU3MmRhMGM2ZDFlYjk2YTk1YzU3ZDIiLCJ0YWciOiIifQ==', NULL, NULL, NULL, 1.40, 0.00, NULL, NULL, NULL, NULL, 'test', 'NGN', 1, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(2, 'stripe', 'eyJpdiI6IjNPK2M3ZzhQaWNwYkgyaHhobEU0TlE9PSIsInZhbHVlIjoiWjFDZmlNQmVDc25aT0hibkZ0YytJSFNQWmo1eG9aK2pmMUxKdmVpcFVVZjQ1R0xaM1VGVW1pWGhTbEZ3L2l0cjlmVnhkZzE3KzJseERrT29zUTRuSFE9PSIsIm1hYyI6IjIyYmQ4MDQ2MGNhYzNlZGIwZjg1MTM1YzQxMjljOTZkMWExYWVmNzNkNzBiNjg5N2RhMTQ4ZWJlNmEyNDNmMDUiLCJ0YWciOiIifQ==', 'eyJpdiI6ImdzMGQ5dlVCd2ZuODlOdlFDNmVuRXc9PSIsInZhbHVlIjoiMGhXWUhQNGtHOE9iR1Q5dnh1NDl5R096NVl4ZlZBNkdKb1RXRzlMNVh0TEtIdkFBRDhUQm56QW05SmNvbUVWSiIsIm1hYyI6IjNkZmFhNWMzZDFiODdkOTJmNGYwMTI5MWI2MDQ4MjRjNzljYmYzMzNjYzczZGFmNDAyNGVmMWYxYmUzYTJjMGQiLCJ0YWciOiIifQ==', NULL, NULL, NULL, NULL, NULL, NULL, 2.90, 0.30, NULL, NULL, NULL, NULL, 'test', 'USD', 1, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(3, 'paypal', 'eyJpdiI6InBBNllkZDlwaG11aG5FdW9Ib0xhOXc9PSIsInZhbHVlIjoiOEtBdE5US3lNVnQyVGI4dFo5T2lZUT09IiwibWFjIjoiODMxMzVmZjA2OGEyNzM3NzFlZGFlMGY2ODVmMWVlN2U3OTE3Y2MwMGMyYjgyN2Q4M2U4MTc4N2I5MGM5OWZhNyIsInRhZyI6IiJ9', 'eyJpdiI6Ik9ia3NKR2svZTVlOUhFQnl3R0NHRnc9PSIsInZhbHVlIjoiU3NDOWs2L0QzVXpPUHUwR2M0WnBMdz09IiwibWFjIjoiMTQ3OWY2NDIwZDgxMTdjNWU3ZGVjY2UwZjE0ZmM3NGQzZTM1NDNlNTM3ODJhOTExZDY5ZGY2NzM4ODAzNzhmYyIsInRhZyI6IiJ9', NULL, NULL, NULL, NULL, NULL, NULL, 3.49, 0.49, NULL, NULL, NULL, NULL, 'test', 'USD', 1, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(4, 'bank_transfer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, '{\"bank_name\":\"Test Bank\",\"account_name\":\"FreightFlow Inc\",\"account_number\":\"1234567890\",\"routing_number\":\"021000021\",\"swift_code\":\"TESTUS66\",\"payment_instructions\":\"Include reference in description.\"}', 'test', 'USD', 1, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(5, 'crypto', 'eyJpdiI6Img3bDVCc0JkZzZZWWZiblVHVnhoVlE9PSIsInZhbHVlIjoiSDZXMmVHRVEzZGpwZmJCY0lZUVpwQT09IiwibWFjIjoiZWExMDUyOGZhZTUzNDdhNDZhMDFmYTk0NTcyZDUyNjBiYjgwZjY0M2NiYjVlYmY0ZDM1YjlmNTZkNDI3NWVhMyIsInRhZyI6IiJ9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, '{\"provider\":\"nowpayments\",\"preferred_coin\":\"btc\"}', 'test', 'USD', 1, '2026-09-10 00:10:05', '2026-09-10 00:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_request_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(80) NOT NULL,
  `provider_reference` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(8) NOT NULL DEFAULT 'USD',
  `fee_amount` decimal(10,2) DEFAULT NULL,
  `net_amount` decimal(12,2) DEFAULT NULL,
  `status` enum('initiated','pending','paid','verified','failed','cancelled') NOT NULL DEFAULT 'initiated',
  `provider_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_payload`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `verified_at` timestamp NULL DEFAULT NULL,
  `webhook_received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `status` enum('shipment_requested','admin_review','approved','rejected','booked','pickup_scheduled','picked_up','warehouse_processing','international_processing','destination_hub','out_for_delivery','delivered','paused','exception','cancelled') NOT NULL DEFAULT 'shipment_requested',
  `service_level` varchar(255) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `origin_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`origin_address`)),
  `destination_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`destination_address`)),
  `weight_kg` decimal(10,2) NOT NULL,
  `declared_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quoted_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estimated_delivery_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `user_id`, `driver_id`, `warehouse_id`, `tracking_number`, `status`, `service_level`, `sender_name`, `recipient_name`, `origin_address`, `destination_address`, `weight_kg`, `declared_value`, `quoted_amount`, `estimated_delivery_at`, `delivered_at`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, NULL, 'FDX-2026-8K3P91QZ', 'international_processing', 'international_priority', 'Maya Carter', 'Lena Morgan', '{\"city\":\"Nashville\",\"country\":\"US\",\"postal_code\":\"37201\"}', '{\"city\":\"London\",\"country\":\"GB\",\"postal_code\":\"SW1A 1AA\"}', 8.50, 450.00, 142.75, '2026-09-14 00:10:05', NULL, NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_attachments`
--

CREATE TABLE `shipment_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'additional_document',
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'public',
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_drafts`
--

CREATE TABLE `shipment_drafts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `customer_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_step` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_route_points`
--

CREATE TABLE `shipment_route_points` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `type` enum('origin','facility','checkpoint','customs','destination') NOT NULL DEFAULT 'checkpoint',
  `label` varchar(160) NOT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `arrived_at` timestamp NULL DEFAULT NULL,
  `departed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_schedules`
--

CREATE TABLE `shipment_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('pickup','delivery') NOT NULL,
  `window_start` timestamp NULL DEFAULT NULL,
  `window_end` timestamp NULL DEFAULT NULL,
  `address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`address`)),
  `instructions` text DEFAULT NULL,
  `status` enum('scheduled','confirmed','completed','missed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shipment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('delivery','billing','customs','damage','account','other') NOT NULL DEFAULT 'other',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('open','pending','resolved','closed') NOT NULL DEFAULT 'open',
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tracking_events`
--

CREATE TABLE `tracking_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `checkpoint_label` varchar(255) DEFAULT NULL,
  `warehouse_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `time` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tracking_events`
--

INSERT INTO `tracking_events` (`id`, `shipment_id`, `status`, `location`, `latitude`, `longitude`, `country_code`, `checkpoint_label`, `warehouse_name`, `description`, `admin_notes`, `created_by`, `occurred_at`, `time`, `created_at`, `updated_at`) VALUES
(1, 1, 'booked', 'Nashville, TN', NULL, NULL, NULL, NULL, NULL, 'Shipment booked and deposit captured.', NULL, 1, '2026-09-09 19:10:05', NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','admin','manager','support','warehouse','driver','customer') NOT NULL DEFAULT 'customer',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `email_verification_code` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires_at` timestamp NULL DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_code_hash` varchar(255) DEFAULT NULL,
  `two_factor_expires_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspension_reason` text DEFAULT NULL,
  `granted_admin_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`granted_admin_permissions`)),
  `revoked_admin_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`revoked_admin_permissions`)),
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `company`, `role`, `email_verified_at`, `email_verification_code`, `password_reset_token`, `password_reset_expires_at`, `two_factor_enabled`, `two_factor_code_hash`, `two_factor_expires_at`, `last_login_at`, `is_suspended`, `suspended_at`, `suspension_reason`, `granted_admin_permissions`, `revoked_admin_permissions`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Operator', 'admin@freightflow.test', NULL, NULL, 'admin', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '$2y$12$KizqIVoJjX54Wcmi85r04us3Uc54Y/XzgsD5BsSIMqQF2WZmdaj9C', NULL, '2026-09-10 00:10:04', '2026-09-10 00:10:04'),
(2, 'James Driver', 'driver@freightflow.test', NULL, NULL, 'driver', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '$2y$12$PiCimV2/xKA5tJOIlMb.vuqwfULOsIzjeN5S7r0/CQaiGw551lftG', NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05'),
(3, 'Maya Carter', 'maya@freightflow.test', NULL, NULL, 'customer', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '$2y$12$vDZYo4seHTSnv61ENM5U5uHw2Ky31DLyvWz7VbMzxAV/F2eQgIMHC', NULL, '2026-09-10 00:10:05', '2026-09-10 00:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `country` varchar(2) NOT NULL,
  `address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`address`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `code`, `city`, `country`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Memphis Global Hub', 'MEM', 'Memphis', 'US', '{\"street\":\"2850 Airways Blvd\",\"city\":\"Memphis\",\"state\":\"TN\",\"postal_code\":\"38131\",\"country\":\"US\"}', '2026-09-10 00:10:04', '2026-09-10 00:10:04'),
(2, 'Heathrow Gateway Hub', 'LHR', 'London', 'GB', '{\"street\":\"Heathrow Cargo Area\",\"city\":\"London\",\"country\":\"GB\",\"postal_code\":\"TW6 2GA\"}', '2026-09-10 00:10:04', '2026-09-10 00:10:04'),
(3, 'Dubai Transit Center', 'DXB', 'Dubai', 'AE', '{\"street\":\"Dubai Logistics City\",\"city\":\"Dubai\",\"country\":\"AE\"}', '2026-09-10 00:10:04', '2026-09-10 00:10:04'),
(4, 'Lagos Coastal Hub', 'LOS', 'Lagos', 'NG', '{\"street\":\"Apapa Port Complex\",\"city\":\"Lagos\",\"country\":\"NG\"}', '2026-09-10 00:10:04', '2026-09-10 00:10:04'),
(5, 'Sao Paulo Cargo Hub', 'GRU', 'Sao Paulo', 'BR', '{\"street\":\"Guarulhos International Cargo\",\"city\":\"Sao Paulo\",\"country\":\"BR\"}', '2026-09-10 00:10:04', '2026-09-10 00:10:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_audit_logs_admin_user_id_foreign` (`admin_user_id`),
  ADD KEY `admin_audit_logs_action_index` (`action`),
  ADD KEY `admin_audit_logs_target_type_index` (`target_type`),
  ADD KEY `admin_audit_logs_target_id_index` (`target_id`),
  ADD KEY `admin_audit_logs_ip_address_index` (`ip_address`),
  ADD KEY `admin_audit_logs_device_fingerprint_index` (`device_fingerprint`);

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_settings_key_unique` (`key`),
  ADD KEY `admin_settings_group_index` (`group`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_logs_sender_id_foreign` (`sender_id`),
  ADD KEY `email_logs_recipient_user_id_foreign` (`recipient_user_id`),
  ADD KEY `email_logs_recipient_email_index` (`recipient_email`),
  ADD KEY `email_logs_recipient_type_index` (`recipient_type`),
  ADD KEY `email_logs_status_index` (`status`),
  ADD KEY `email_logs_sent_at_index` (`sent_at`);

--
-- Indexes for table `guest_chat_conversations`
--
ALTER TABLE `guest_chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `guest_chat_conversations_secure_token_unique` (`secure_token`),
  ADD KEY `guest_chat_conversations_shipment_id_foreign` (`shipment_id`),
  ADD KEY `guest_chat_conversations_closed_by_foreign` (`closed_by`),
  ADD KEY `guest_chat_conversations_tracking_number_index` (`tracking_number`),
  ADD KEY `guest_chat_conversations_status_index` (`status`),
  ADD KEY `guest_chat_conversations_chat_status_index` (`chat_status`);

--
-- Indexes for table `guest_chat_messages`
--
ALTER TABLE `guest_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guest_chat_messages_guest_chat_conversation_id_foreign` (`guest_chat_conversation_id`),
  ADD KEY `guest_chat_messages_admin_user_id_foreign` (`admin_user_id`),
  ADD KEY `guest_chat_messages_sender_type_index` (`sender_type`),
  ADD KEY `guest_chat_messages_delivery_status_index` (`delivery_status`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_foreign` (`user_id`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_preferences_user_id_foreign` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_shipment_id_stage_unique` (`shipment_id`,`stage`),
  ADD KEY `payments_verified_by_foreign` (`verified_by`),
  ADD KEY `payments_status_index` (`status`);

--
-- Indexes for table `payment_audit_logs`
--
ALTER TABLE `payment_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_audit_logs_admin_user_id_foreign` (`admin_user_id`),
  ADD KEY `payment_audit_logs_event_created_at_index` (`event`,`created_at`),
  ADD KEY `payment_audit_logs_payment_transaction_id_index` (`payment_transaction_id`),
  ADD KEY `payment_audit_logs_event_index` (`event`),
  ADD KEY `payment_audit_logs_provider_index` (`provider`);

--
-- Indexes for table `payment_proofs`
--
ALTER TABLE `payment_proofs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_proofs_file_hash_unique` (`file_hash`),
  ADD KEY `payment_proofs_payment_transaction_id_foreign` (`payment_transaction_id`),
  ADD KEY `payment_proofs_verified_by_foreign` (`verified_by`),
  ADD KEY `payment_proofs_tracking_number_index` (`tracking_number`),
  ADD KEY `payment_proofs_payment_reference_index` (`payment_reference`),
  ADD KEY `payment_proofs_status_index` (`status`),
  ADD KEY `payment_proofs_payment_request_id_foreign` (`payment_request_id`);

--
-- Indexes for table `payment_requests`
--
ALTER TABLE `payment_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_requests_new_secure_token_unique` (`secure_token`),
  ADD KEY `payment_requests_new_shipment_id_foreign` (`shipment_id`),
  ADD KEY `payment_requests_new_created_by_foreign` (`created_by`),
  ADD KEY `payment_requests_new_verified_by_foreign` (`verified_by`),
  ADD KEY `payment_requests_new_currency_index` (`currency`),
  ADD KEY `payment_requests_new_requested_method_index` (`requested_method`),
  ADD KEY `payment_requests_new_status_index` (`status`),
  ADD KEY `payment_requests_new_payment_reference_index` (`payment_reference`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_settings_gateway_name_unique` (`gateway_name`),
  ADD KEY `payment_settings_mode_index` (`mode`),
  ADD KEY `payment_settings_currency_index` (`currency`),
  ADD KEY `payment_settings_is_active_index` (`is_active`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_transactions_provider_index` (`provider`),
  ADD KEY `payment_transactions_provider_reference_index` (`provider_reference`),
  ADD KEY `payment_transactions_status_index` (`status`),
  ADD KEY `payment_transactions_payment_request_id_foreign` (`payment_request_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipments_tracking_number_unique` (`tracking_number`),
  ADD KEY `shipments_driver_id_foreign` (`driver_id`),
  ADD KEY `shipments_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `shipments_user_id_status_index` (`user_id`,`status`),
  ADD KEY `shipments_status_index` (`status`),
  ADD KEY `shipments_service_level_index` (`service_level`);

--
-- Indexes for table `shipment_attachments`
--
ALTER TABLE `shipment_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_attachments_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `shipment_attachments_shipment_id_category_index` (`shipment_id`,`category`),
  ADD KEY `shipment_attachments_category_index` (`category`);

--
-- Indexes for table `shipment_drafts`
--
ALTER TABLE `shipment_drafts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipment_drafts_admin_user_id_unique` (`admin_user_id`),
  ADD KEY `shipment_drafts_customer_user_id_foreign` (`customer_user_id`),
  ADD KEY `shipment_drafts_updated_at_index` (`updated_at`);

--
-- Indexes for table `shipment_route_points`
--
ALTER TABLE `shipment_route_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_route_points_created_by_foreign` (`created_by`),
  ADD KEY `shipment_route_points_shipment_id_sort_order_index` (`shipment_id`,`sort_order`),
  ADD KEY `shipment_route_points_sort_order_index` (`sort_order`),
  ADD KEY `shipment_route_points_type_index` (`type`);

--
-- Indexes for table `shipment_schedules`
--
ALTER TABLE `shipment_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_schedules_shipment_id_foreign` (`shipment_id`),
  ADD KEY `shipment_schedules_type_index` (`type`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `support_tickets_user_id_foreign` (`user_id`),
  ADD KEY `support_tickets_shipment_id_foreign` (`shipment_id`),
  ADD KEY `support_tickets_priority_index` (`priority`),
  ADD KEY `support_tickets_status_index` (`status`);

--
-- Indexes for table `tracking_events`
--
ALTER TABLE `tracking_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tracking_events_shipment_id_foreign` (`shipment_id`),
  ADD KEY `tracking_events_created_by_foreign` (`created_by`),
  ADD KEY `tracking_events_occurred_at_index` (`occurred_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_password_reset_token_index` (`password_reset_token`),
  ADD KEY `users_is_suspended_index` (`is_suspended`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouses_code_unique` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guest_chat_conversations`
--
ALTER TABLE `guest_chat_conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guest_chat_messages`
--
ALTER TABLE `guest_chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payment_audit_logs`
--
ALTER TABLE `payment_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_proofs`
--
ALTER TABLE `payment_proofs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_requests`
--
ALTER TABLE `payment_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shipment_attachments`
--
ALTER TABLE `shipment_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_drafts`
--
ALTER TABLE `shipment_drafts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_route_points`
--
ALTER TABLE `shipment_route_points`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_schedules`
--
ALTER TABLE `shipment_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tracking_events`
--
ALTER TABLE `tracking_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD CONSTRAINT `admin_audit_logs_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_recipient_user_id_foreign` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `email_logs_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `guest_chat_conversations`
--
ALTER TABLE `guest_chat_conversations`
  ADD CONSTRAINT `guest_chat_conversations_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `guest_chat_conversations_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `guest_chat_messages`
--
ALTER TABLE `guest_chat_messages`
  ADD CONSTRAINT `guest_chat_messages_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `guest_chat_messages_guest_chat_conversation_id_foreign` FOREIGN KEY (`guest_chat_conversation_id`) REFERENCES `guest_chat_conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_audit_logs`
--
ALTER TABLE `payment_audit_logs`
  ADD CONSTRAINT `payment_audit_logs_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_proofs`
--
ALTER TABLE `payment_proofs`
  ADD CONSTRAINT `payment_proofs_payment_request_id_foreign` FOREIGN KEY (`payment_request_id`) REFERENCES `payment_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_proofs_payment_transaction_id_foreign` FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_proofs_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_requests`
--
ALTER TABLE `payment_requests`
  ADD CONSTRAINT `payment_requests_new_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_requests_new_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_requests_new_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_payment_request_id_foreign` FOREIGN KEY (`payment_request_id`) REFERENCES `payment_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shipment_attachments`
--
ALTER TABLE `shipment_attachments`
  ADD CONSTRAINT `shipment_attachments_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipment_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_drafts`
--
ALTER TABLE `shipment_drafts`
  ADD CONSTRAINT `shipment_drafts_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipment_drafts_customer_user_id_foreign` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shipment_route_points`
--
ALTER TABLE `shipment_route_points`
  ADD CONSTRAINT `shipment_route_points_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipment_route_points_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_schedules`
--
ALTER TABLE `shipment_schedules`
  ADD CONSTRAINT `shipment_schedules_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `support_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tracking_events`
--
ALTER TABLE `tracking_events`
  ADD CONSTRAINT `tracking_events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tracking_events_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
