-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: prestamista
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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `auditable_type` varchar(150) DEFAULT NULL,
  `auditable_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_company_id_module_index` (`company_id`,`module`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  CONSTRAINT `audit_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,1,'loan_created','loans','Préstamo PRE-20260601-00001 creado.','App\\Models\\Loan',1,NULL,'{\"id\":1,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260601-00001\",\"principal_amount\":\"120000.00\",\"interest_rate\":\"12.0000\",\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":6,\"installment_amount\":\"22400.00\",\"total_interest\":\"14400.00\",\"total_amount\":\"134400.00\",\"paid_principal\":\"0.00\",\"paid_interest\":\"0.00\",\"paid_late_fee\":\"0.00\",\"remaining_balance\":\"120000.00\",\"late_fee_type\":\"daily_fixed\",\"late_fee_value\":\"150.00\",\"start_date\":\"2026-05-06T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-06T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":\"Motor Honda Lead 2023 y pagar\\u00e9 notarial.\",\"notes\":\"DEMO_PORTFOLIO_V1 Mensual plano: capital RD$120,000, inter\\u00e9s 12%, 6 cuotas de RD$22,400.\",\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-06-01T18:34:03.000000Z\",\"updated_at\":\"2026-06-01T18:34:03.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-06-01 12:34:03'),(2,1,1,'loan_created','loans','Préstamo PRE-20260601-00002 creado.','App\\Models\\Loan',2,NULL,'{\"id\":2,\"company_id\":1,\"client_id\":2,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260601-00002\",\"principal_amount\":\"25000.00\",\"interest_rate\":\"5.0000\",\"interest_type\":\"fixed\",\"payment_frequency\":\"weekly\",\"calculation_method\":\"capital_plus_interest\",\"term_quantity\":8,\"installment_amount\":\"4375.00\",\"total_interest\":\"10000.00\",\"total_amount\":\"35000.00\",\"paid_principal\":\"0.00\",\"paid_interest\":\"0.00\",\"paid_late_fee\":\"0.00\",\"remaining_balance\":\"25000.00\",\"late_fee_type\":\"fixed\",\"late_fee_value\":\"300.00\",\"start_date\":\"2026-04-29T04:00:00.000000Z\",\"first_payment_date\":\"2026-05-06T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":\"Garant\\u00eda solidaria con dos referencias.\",\"notes\":\"DEMO_PORTFOLIO_V1 Semanal capital+inter\\u00e9s: RD$25,000, 5%, 8 cuotas de RD$4,375.\",\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-06-01T18:34:03.000000Z\",\"updated_at\":\"2026-06-01T18:34:03.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-06-01 12:34:04'),(3,1,1,'loan_created','loans','Préstamo PRE-20260601-00003 creado.','App\\Models\\Loan',3,NULL,'{\"id\":3,\"company_id\":1,\"client_id\":3,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260601-00003\",\"principal_amount\":\"15000.00\",\"interest_rate\":\"10.0000\",\"interest_type\":\"fixed\",\"payment_frequency\":\"daily\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":\"1650.00\",\"total_interest\":\"1500.00\",\"total_amount\":\"16500.00\",\"paid_principal\":\"0.00\",\"paid_interest\":\"0.00\",\"paid_late_fee\":\"0.00\",\"remaining_balance\":\"15000.00\",\"late_fee_type\":\"daily_fixed\",\"late_fee_value\":\"75.00\",\"start_date\":\"2026-04-20T04:00:00.000000Z\",\"first_payment_date\":\"2026-04-21T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":\"Nevera comercial y contrato firmado.\",\"notes\":\"DEMO_PORTFOLIO_V1 Diario atrasado: RD$15,000, 10%, 10 cuotas de RD$1,650, mora diaria RD$75.\",\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-06-01T18:34:04.000000Z\",\"updated_at\":\"2026-06-01T18:34:04.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-06-01 12:34:04'),(4,1,2,'payment_registered','payments','Pago registrado al préstamo PRE-20260601-00002.','App\\Models\\Payment',1,NULL,'{\"id\":1,\"company_id\":1,\"loan_id\":2,\"client_id\":2,\"collector_id\":1,\"receipt_number\":\"REC-20260601-MIC2EQPB\",\"mobile_uuid\":null,\"payment_date\":\"2026-05-06T04:00:00.000000Z\",\"amount\":\"4375.00\",\"principal_paid\":\"3125.00\",\"interest_paid\":\"1250.00\",\"late_fee_paid\":\"0.00\",\"discount\":\"0.00\",\"payment_method\":\"cash\",\"previous_balance\":\"25000.00\",\"new_balance\":\"21875.00\",\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":2,\"created_at\":\"2026-06-01T18:34:04.000000Z\",\"updated_at\":\"2026-06-01T18:34:04.000000Z\"}','127.0.0.1','Symfony','2026-06-01 12:34:04'),(5,1,1,'settings_updated','settings','Configuración de empresa actualizada.','App\\Models\\Company',1,'{\"company\":{\"name\":\"Prestamista Demo RD\",\"plan\":\"full\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":\"10.0000\",\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":\"75.00\",\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','{\"company\":{\"name\":\"Prestamista Demo RD\",\"plan\":\"prestamista\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":\"10.0000\",\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":\"75.00\",\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','104.22.24.238','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-06-01 14:29:36');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_movements`
--

DROP TABLE IF EXISTS `cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `type` enum('loan_disbursement','payment_received','expense','collector_commission','capital_injection','capital_withdrawal','adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `direction` enum('in','out') NOT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `movement_date` date NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_movements_created_by_foreign` (`created_by`),
  KEY `cash_movements_company_id_movement_date_index` (`company_id`,`movement_date`),
  KEY `cash_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `cash_movements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_movements`
--

LOCK TABLES `cash_movements` WRITE;
/*!40000 ALTER TABLE `cash_movements` DISABLE KEYS */;
INSERT INTO `cash_movements` VALUES (1,1,'loan_disbursement',120000.00,'out','App\\Models\\Loan',1,'Desembolso de préstamo PRE-20260601-00001','2026-06-01',1,'2026-06-01 12:34:03','2026-06-01 12:34:03'),(2,1,'loan_disbursement',25000.00,'out','App\\Models\\Loan',2,'Desembolso de préstamo PRE-20260601-00002','2026-06-01',1,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(3,1,'loan_disbursement',15000.00,'out','App\\Models\\Loan',3,'Desembolso de préstamo PRE-20260601-00003','2026-06-01',1,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(4,1,'payment_received',4375.00,'in','App\\Models\\Payment',1,'Pago recibido REC-20260601-MIC2EQPB','2026-06-01',2,'2026-06-01 12:34:04','2026-06-01 12:34:04');
/*!40000 ALTER TABLE `cash_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_documents`
--

DROP TABLE IF EXISTS `client_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `title` varchar(180) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_documents_client_id_foreign` (`client_id`),
  CONSTRAINT `client_documents_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_documents`
--

LOCK TABLES `client_documents` WRITE;
/*!40000 ALTER TABLE `client_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_references`
--

DROP TABLE IF EXISTS `client_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_references` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_references_client_id_foreign` (`client_id`),
  CONSTRAINT `client_references_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_references`
--

LOCK TABLES `client_references` WRITE;
/*!40000 ALTER TABLE `client_references` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_references` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `full_name` varchar(180) NOT NULL,
  `identification` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `secondary_phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_reference` varchar(180) DEFAULT NULL,
  `workplace` varchar(180) DEFAULT NULL,
  `workplace_phone` varchar(50) DEFAULT NULL,
  `monthly_income` decimal(12,2) NOT NULL DEFAULT 0.00,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','moroso','blocked') NOT NULL DEFAULT 'active',
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_company_id_code_unique` (`company_id`,`code`),
  KEY `clients_company_id_status_index` (`company_id`,`status`),
  KEY `clients_company_id_identification_index` (`company_id`,`identification`),
  KEY `clients_company_id_latitude_longitude_index` (`company_id`,`latitude`,`longitude`),
  CONSTRAINT `clients_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,1,'CLI-DEMO-001','María Rodríguez','001-1234567-8','809-555-3001','829-555-3001',NULL,'Ensanche Naco, Santo Domingo',18.4834020,-69.9312120,'Cerca de Av. Tiradentes','Comercial Rodríguez',NULL,85000.00,NULL,'active','low','Cliente demo con préstamo mensual al día.','2026-06-01 12:34:03','2026-06-01 12:34:03',NULL),(2,1,'CLI-DEMO-002','José Martínez','001-7654321-0','809-555-3002',NULL,NULL,'Los Mina, Santo Domingo Este',18.4919780,-69.8561590,'Próximo a Av. Venezuela','Taller Martínez',NULL,52000.00,NULL,'active','medium','Cliente demo con préstamo semanal y primer pago registrado.','2026-06-01 12:34:03','2026-06-01 12:34:03',NULL),(3,1,'CLI-DEMO-003','Ana Pérez','402-1122334-5','809-555-3003',NULL,NULL,'Villa Consuelo, Santo Domingo',18.4765680,-69.8984090,'Zona comercial de Villa Consuelo','Colmado Ana',NULL,45000.00,NULL,'moroso','high','Cliente demo con préstamo diario atrasado para probar mora.','2026-06-01 12:34:03','2026-06-01 12:34:04',NULL);
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collector_commissions`
--

DROP TABLE IF EXISTS `collector_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collector_commissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `collector_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `commission_type` enum('percentage','fixed') NOT NULL,
  `commission_value` decimal(12,2) NOT NULL,
  `base_amount` decimal(12,2) NOT NULL,
  `commission_amount` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collector_commissions_collector_id_foreign` (`collector_id`),
  KEY `collector_commissions_payment_id_foreign` (`payment_id`),
  KEY `collector_commissions_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `collector_commissions_collector_id_foreign` FOREIGN KEY (`collector_id`) REFERENCES `collectors` (`id`),
  CONSTRAINT `collector_commissions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collector_commissions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collector_commissions`
--

LOCK TABLES `collector_commissions` WRITE;
/*!40000 ALTER TABLE `collector_commissions` DISABLE KEYS */;
INSERT INTO `collector_commissions` VALUES (1,1,1,1,'percentage',5.00,4375.00,218.75,'pending',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04');
/*!40000 ALTER TABLE `collector_commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collector_location_points`
--

DROP TABLE IF EXISTS `collector_location_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collector_location_points` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `collector_route_session_id` bigint(20) unsigned NOT NULL,
  `collector_id` bigint(20) unsigned NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `accuracy_meters` int(10) unsigned DEFAULT NULL,
  `battery_level` tinyint(3) unsigned DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clp_session_recorded_idx` (`collector_route_session_id`,`recorded_at`),
  KEY `clp_collector_recorded_idx` (`collector_id`,`recorded_at`),
  CONSTRAINT `collector_location_points_collector_id_foreign` FOREIGN KEY (`collector_id`) REFERENCES `collectors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collector_location_points_collector_route_session_id_foreign` FOREIGN KEY (`collector_route_session_id`) REFERENCES `collector_route_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collector_location_points`
--

LOCK TABLES `collector_location_points` WRITE;
/*!40000 ALTER TABLE `collector_location_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `collector_location_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collector_route_sessions`
--

DROP TABLE IF EXISTS `collector_route_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collector_route_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `route_id` bigint(20) unsigned NOT NULL,
  `collector_id` bigint(20) unsigned NOT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  `last_location_at` timestamp NULL DEFAULT NULL,
  `last_latitude` decimal(10,7) DEFAULT NULL,
  `last_longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collector_route_sessions_company_id_status_index` (`company_id`,`status`),
  KEY `collector_route_sessions_collector_id_status_index` (`collector_id`,`status`),
  KEY `collector_route_sessions_route_id_status_index` (`route_id`,`status`),
  CONSTRAINT `collector_route_sessions_collector_id_foreign` FOREIGN KEY (`collector_id`) REFERENCES `collectors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collector_route_sessions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collector_route_sessions_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collector_route_sessions`
--

LOCK TABLES `collector_route_sessions` WRITE;
/*!40000 ALTER TABLE `collector_route_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `collector_route_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collectors`
--

DROP TABLE IF EXISTS `collectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collectors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `commission_type` enum('percentage','fixed','none') NOT NULL DEFAULT 'none',
  `commission_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collectors_user_id_foreign` (`user_id`),
  KEY `collectors_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `collectors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collectors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collectors`
--

LOCK TABLES `collectors` WRITE;
/*!40000 ALTER TABLE `collectors` DISABLE KEYS */;
INSERT INTO `collectors` VALUES (1,1,2,'Carlos Cobrador','809-555-2001','percentage',5.00,'active','2026-06-01 12:34:03','2026-06-01 12:34:03');
/*!40000 ALTER TABLE `collectors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `rnc` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `plan` varchar(255) NOT NULL DEFAULT 'full',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Prestamista Demo RD','131000001','809-555-1000','admin@sistemaprestamista.local','Av. Winston Churchill, Santo Domingo',NULL,'active','prestamista','2026-06-01 12:34:02','2026-06-01 14:29:36');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'RD$',
  `default_interest_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `default_late_fee_type` enum('none','fixed','daily_percentage','daily_fixed') NOT NULL DEFAULT 'none',
  `default_late_fee_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `receipt_prefix` varchar(20) NOT NULL DEFAULT 'REC',
  `loan_prefix` varchar(20) NOT NULL DEFAULT 'PRE',
  `quote_prefix` varchar(20) NOT NULL DEFAULT 'COT',
  `allow_partial_payments` tinyint(1) NOT NULL DEFAULT 1,
  `allow_payment_cancellation` tinyint(1) NOT NULL DEFAULT 1,
  `require_approval_for_loans` tinyint(1) NOT NULL DEFAULT 1,
  `exclude_sundays_for_daily_loans` tinyint(1) NOT NULL DEFAULT 0,
  `route_visit_radius_meters` int(10) unsigned NOT NULL DEFAULT 75,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_settings_company_id_unique` (`company_id`),
  CONSTRAINT `company_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_settings`
--

LOCK TABLES `company_settings` WRITE;
/*!40000 ALTER TABLE `company_settings` DISABLE KEYS */;
INSERT INTO `company_settings` VALUES (1,1,'RD$',10.0000,'daily_fixed',75.00,'REC','PRE','COT',1,1,0,1,75,'2026-06-01 12:34:02','2026-06-01 12:34:03');
/*!40000 ALTER TABLE `company_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `loan_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` enum('promissory_note','loan_contract','disbursement_receipt','payment_receipt','balance_letter','account_statement') NOT NULL,
  `title` varchar(180) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_client_id_foreign` (`client_id`),
  KEY `documents_loan_id_foreign` (`loan_id`),
  KEY `documents_created_by_foreign` (`created_by`),
  KEY `documents_company_id_document_type_index` (`company_id`,`document_type`),
  CONSTRAINT `documents_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documents_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `expense_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,1,'Transporte y combustible','2026-06-01 12:34:03','2026-06-01 12:34:03');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','transfer','card','check','other') NOT NULL DEFAULT 'cash',
  `receipt_file` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_category_id_foreign` (`category_id`),
  KEY `expenses_created_by_foreign` (`created_by`),
  KEY `expenses_company_id_expense_date_index` (`company_id`,`expense_date`),
  CONSTRAINT `expenses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_installments`
--

DROP TABLE IF EXISTS `loan_installments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_installments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `installment_number` int(10) unsigned NOT NULL,
  `due_date` date NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `installment_amount` decimal(12,2) NOT NULL,
  `paid_principal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_interest` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_late_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `days_late` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('pending','partial','paid','late','cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_installments_loan_id_installment_number_unique` (`loan_id`,`installment_number`),
  KEY `loan_installments_loan_id_status_index` (`loan_id`,`status`),
  KEY `loan_installments_due_date_index` (`due_date`),
  CONSTRAINT `loan_installments_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_installments`
--

LOCK TABLES `loan_installments` WRITE;
/*!40000 ALTER TABLE `loan_installments` DISABLE KEYS */;
INSERT INTO `loan_installments` VALUES (1,1,1,'2026-06-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:03','2026-06-01 12:34:04'),(2,1,2,'2026-07-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:03','2026-06-01 12:34:04'),(3,1,3,'2026-08-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:03','2026-06-01 12:34:04'),(4,1,4,'2026-09-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:03','2026-06-01 12:34:04'),(5,1,5,'2026-10-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:03','2026-06-01 12:34:04'),(6,1,6,'2026-11-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:03','2026-06-01 12:34:04'),(7,2,1,'2026-05-06',3125.00,1250.00,4375.00,3125.00,1250.00,0.00,4375.00,0.00,0,'paid','2026-06-01 12:34:04','2026-06-01 12:34:03','2026-06-01 12:34:04'),(8,2,2,'2026-05-13',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:03','2026-06-01 12:34:04'),(9,2,3,'2026-05-20',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:03','2026-06-01 12:34:04'),(10,2,4,'2026-05-27',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(11,2,5,'2026-06-03',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(12,2,6,'2026-06-10',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(13,2,7,'2026-06-17',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(14,2,8,'2026-06-24',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(15,3,1,'2026-04-21',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,1125.00,15,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(16,3,2,'2026-04-22',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,1050.00,14,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(17,3,3,'2026-04-23',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,975.00,13,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(18,3,4,'2026-04-24',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,900.00,12,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(19,3,5,'2026-04-25',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,825.00,11,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(20,3,6,'2026-04-27',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,675.00,9,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(21,3,7,'2026-04-28',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,600.00,8,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(22,3,8,'2026-04-29',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,525.00,7,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(23,3,9,'2026-04-30',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,450.00,6,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04'),(24,3,10,'2026-05-01',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,375.00,5,'late',NULL,'2026-06-01 12:34:04','2026-06-01 12:34:04');
/*!40000 ALTER TABLE `loan_installments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_quotes`
--

DROP TABLE IF EXISTS `loan_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_quotes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `interest_rate` decimal(8,4) NOT NULL,
  `interest_type` enum('fixed','compound','amortized') NOT NULL DEFAULT 'fixed',
  `payment_frequency` enum('daily','weekly','biweekly','monthly') NOT NULL,
  `calculation_method` enum('flat_interest','fixed_installment','capital_plus_interest','interest_only','french_amortization') NOT NULL,
  `term_quantity` int(10) unsigned NOT NULL,
  `installment_amount` decimal(12,2) NOT NULL,
  `total_interest` decimal(12,2) NOT NULL,
  `total_to_pay` decimal(12,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `first_payment_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','converted') NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_quotes_client_id_foreign` (`client_id`),
  KEY `loan_quotes_created_by_foreign` (`created_by`),
  KEY `loan_quotes_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `loan_quotes_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_quotes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_quotes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_quotes`
--

LOCK TABLES `loan_quotes` WRITE;
/*!40000 ALTER TABLE `loan_quotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `collector_id` bigint(20) unsigned DEFAULT NULL,
  `quote_id` bigint(20) unsigned DEFAULT NULL,
  `loan_number` varchar(50) NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL,
  `interest_rate` decimal(8,4) NOT NULL,
  `interest_type` enum('fixed','compound','amortized') NOT NULL DEFAULT 'fixed',
  `payment_frequency` enum('daily','weekly','biweekly','monthly') NOT NULL,
  `calculation_method` enum('flat_interest','fixed_installment','capital_plus_interest','interest_only','french_amortization') NOT NULL,
  `term_quantity` int(10) unsigned NOT NULL,
  `installment_amount` decimal(12,2) NOT NULL,
  `total_interest` decimal(12,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `paid_principal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_interest` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_late_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_balance` decimal(12,2) NOT NULL,
  `late_fee_type` enum('none','fixed','daily_percentage','daily_fixed') NOT NULL DEFAULT 'none',
  `late_fee_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allows_capital_prepayment` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `first_payment_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `guarantee_description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loans_company_id_loan_number_unique` (`company_id`,`loan_number`),
  KEY `loans_collector_id_foreign` (`collector_id`),
  KEY `loans_quote_id_foreign` (`quote_id`),
  KEY `loans_approved_by_foreign` (`approved_by`),
  KEY `loans_created_by_foreign` (`created_by`),
  KEY `loans_company_id_status_index` (`company_id`,`status`),
  KEY `loans_client_id_status_index` (`client_id`,`status`),
  CONSTRAINT `loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loans_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `loans_collector_id_foreign` FOREIGN KEY (`collector_id`) REFERENCES `collectors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loans_quote_id_foreign` FOREIGN KEY (`quote_id`) REFERENCES `loan_quotes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (1,1,1,1,NULL,'PRE-20260601-00001',120000.00,12.0000,'fixed','monthly','flat_interest',6,22400.00,14400.00,134400.00,0.00,0.00,0.00,120000.00,'daily_fixed',150.00,1,'2026-05-06','2026-06-06',NULL,'active','Motor Honda Lead 2023 y pagaré notarial.','DEMO_PORTFOLIO_V1 Mensual plano: capital RD$120,000, interés 12%, 6 cuotas de RD$22,400.',1,1,'2026-06-01 12:34:03','2026-06-01 12:34:03',NULL),(2,1,2,1,NULL,'PRE-20260601-00002',25000.00,5.0000,'fixed','weekly','capital_plus_interest',8,4375.00,10000.00,35000.00,3125.00,1250.00,0.00,21875.00,'fixed',300.00,1,'2026-04-29','2026-05-06',NULL,'active','Garantía solidaria con dos referencias.','DEMO_PORTFOLIO_V1 Semanal capital+interés: RD$25,000, 5%, 8 cuotas de RD$4,375.',1,1,'2026-06-01 12:34:03','2026-06-01 12:34:04',NULL),(3,1,3,1,NULL,'PRE-20260601-00003',15000.00,10.0000,'fixed','daily','flat_interest',10,1650.00,1500.00,16500.00,0.00,0.00,0.00,15000.00,'daily_fixed',75.00,1,'2026-04-20','2026-04-21',NULL,'late','Nevera comercial y contrato firmado.','DEMO_PORTFOLIO_V1 Diario atrasado: RD$15,000, 10%, 10 cuotas de RD$1,650, mora diaria RD$75.',1,1,'2026-06-01 12:34:04','2026-06-01 12:34:04',NULL);
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0000_01_01_000000_create_companies_table',1),(2,'0001_01_01_000000_create_users_table',1),(3,'0001_01_01_000001_create_cache_table',1),(4,'0001_01_01_000002_create_jobs_table',1),(5,'2026_05_06_005423_create_permission_tables',1),(6,'2026_05_06_010000_create_lending_domain_tables',1),(7,'2026_05_06_014628_create_personal_access_tokens_table',1),(8,'2026_05_06_020000_add_mobile_uuid_to_payments_table',1),(9,'2026_05_06_030000_add_location_fields_to_clients_table',1),(10,'2026_05_07_001000_create_collector_route_tracking_tables',1),(11,'2026_05_07_002000_add_route_visit_radius_to_company_settings',1),(12,'2026_05_31_141511_add_capital_prepayment_fields',2),(13,'2026_05_31_174040_add_visible_menus_to_users',2),(14,'2026_05_31_175145_allow_pending_status_on_loans',2),(15,'2026_05_31_215905_add_plan_to_companies',2),(16,'2026_06_01_000000_create_system_owner_user',3),(17,'2026_06_01_000100_add_is_system_owner_to_users',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`company_id`,`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  KEY `model_has_permissions_permission_id_foreign` (`permission_id`),
  KEY `model_has_permissions_team_foreign_key_index` (`company_id`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`company_id`,`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  KEY `model_has_roles_role_id_foreign` (`role_id`),
  KEY `model_has_roles_team_foreign_key_index` (`company_id`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1,1),(1,'App\\Models\\User',3,1),(3,'App\\Models\\User',2,1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_details`
--

DROP TABLE IF EXISTS `payment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `installment_id` bigint(20) unsigned NOT NULL,
  `principal_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_fee_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_details_payment_id_foreign` (`payment_id`),
  KEY `payment_details_installment_id_foreign` (`installment_id`),
  CONSTRAINT `payment_details_installment_id_foreign` FOREIGN KEY (`installment_id`) REFERENCES `loan_installments` (`id`),
  CONSTRAINT `payment_details_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_details`
--

LOCK TABLES `payment_details` WRITE;
/*!40000 ALTER TABLE `payment_details` DISABLE KEYS */;
INSERT INTO `payment_details` VALUES (1,1,7,3125.00,1250.00,0.00,4375.00,'2026-06-01 12:34:04','2026-06-01 12:34:04');
/*!40000 ALTER TABLE `payment_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `loan_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `collector_id` bigint(20) unsigned DEFAULT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `mobile_uuid` char(36) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `principal_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_fee_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `capital_prepaid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `change_given` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','transfer','card','check','other') NOT NULL DEFAULT 'cash',
  `previous_balance` decimal(12,2) NOT NULL,
  `new_balance` decimal(12,2) NOT NULL,
  `status` enum('valid','cancelled') NOT NULL DEFAULT 'valid',
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_company_id_receipt_number_unique` (`company_id`,`receipt_number`),
  UNIQUE KEY `payments_company_id_mobile_uuid_unique` (`company_id`,`mobile_uuid`),
  KEY `payments_client_id_foreign` (`client_id`),
  KEY `payments_collector_id_foreign` (`collector_id`),
  KEY `payments_cancelled_by_foreign` (`cancelled_by`),
  KEY `payments_created_by_foreign` (`created_by`),
  KEY `payments_company_id_payment_date_index` (`company_id`,`payment_date`),
  KEY `payments_loan_id_status_index` (`loan_id`,`status`),
  CONSTRAINT `payments_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `payments_collector_id_foreign` FOREIGN KEY (`collector_id`) REFERENCES `collectors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,2,2,1,'REC-20260601-MIC2EQPB',NULL,'2026-05-06',4375.00,3125.00,1250.00,0.00,0.00,0.00,0.00,'cash',25000.00,21875.00,'valid',NULL,NULL,NULL,2,'2026-06-01 12:34:04','2026-06-01 12:34:04');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(2,'clients.view','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(3,'clients.create','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(4,'clients.update','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(5,'clients.delete','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(6,'quotes.manage','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(7,'quotes.convert','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(8,'quotes.delete','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(9,'loans.view','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(10,'loans.create','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(11,'loans.approve','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(12,'loans.update','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(13,'payments.create','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(14,'payments.cancel','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(15,'collectors.manage','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(16,'routes.manage','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(17,'expenses.manage','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(18,'cash.view','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(19,'reports.view','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(20,'documents.generate','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(21,'legal.manage','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(22,'settings.manage','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(23,'users.manage','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(24,'companies.manage-plan','web','2026-06-01 14:55:56','2026-06-01 14:55:56');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(2,1),(2,2),(2,3),(2,5),(3,1),(3,2),(4,1),(4,2),(5,1),(6,1),(6,2),(7,1),(7,2),(8,1),(9,1),(9,2),(9,3),(9,4),(9,5),(10,1),(10,2),(11,1),(11,2),(12,1),(12,2),(13,1),(13,3),(13,4),(14,1),(14,4),(15,1),(15,2),(16,1),(16,2),(17,1),(17,4),(18,1),(18,4),(19,1),(19,2),(19,4),(19,5),(20,1),(20,2),(20,3),(20,4),(20,5),(21,1),(21,5),(22,1),(23,1);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_company_id_name_guard_name_unique` (`company_id`,`name`,`guard_name`),
  KEY `roles_team_foreign_key_index` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,NULL,'Administrador','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(2,NULL,'Supervisor','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(3,NULL,'Cobrador','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(4,NULL,'Caja/Contabilidad','web','2026-06-01 12:34:02','2026-06-01 12:34:02'),(5,NULL,'Legal','web','2026-06-01 12:34:02','2026-06-01 12:34:02');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `route_clients`
--

DROP TABLE IF EXISTS `route_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `route_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `route_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `order_number` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `route_clients_route_id_client_id_unique` (`route_id`,`client_id`),
  KEY `route_clients_client_id_foreign` (`client_id`),
  CONSTRAINT `route_clients_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `route_clients_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `route_clients`
--

LOCK TABLES `route_clients` WRITE;
/*!40000 ALTER TABLE `route_clients` DISABLE KEYS */;
INSERT INTO `route_clients` VALUES (1,1,1,1,'2026-06-01 12:34:03','2026-06-01 12:34:03'),(2,1,3,2,'2026-06-01 12:34:03','2026-06-01 12:34:03'),(3,1,2,3,'2026-06-01 12:34:03','2026-06-01 12:34:03');
/*!40000 ALTER TABLE `route_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `route_visit_events`
--

DROP TABLE IF EXISTS `route_visit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `route_visit_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `collector_route_session_id` bigint(20) unsigned NOT NULL,
  `route_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `expected_order` int(10) unsigned NOT NULL,
  `visited_order` int(10) unsigned DEFAULT NULL,
  `status` enum('visited','visited_out_of_order') NOT NULL DEFAULT 'visited',
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `distance_meters` int(10) unsigned NOT NULL,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `route_visit_session_client_unique` (`collector_route_session_id`,`client_id`),
  KEY `route_visit_events_client_id_foreign` (`client_id`),
  KEY `route_visit_events_route_id_status_index` (`route_id`,`status`),
  CONSTRAINT `route_visit_events_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `route_visit_events_collector_route_session_id_foreign` FOREIGN KEY (`collector_route_session_id`) REFERENCES `collector_route_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `route_visit_events_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `route_visit_events`
--

LOCK TABLES `route_visit_events` WRITE;
/*!40000 ALTER TABLE `route_visit_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `route_visit_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `routes`
--

DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `routes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `zone_id` bigint(20) unsigned DEFAULT NULL,
  `collector_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `routes_company_id_name_unique` (`company_id`,`name`),
  KEY `routes_zone_id_foreign` (`zone_id`),
  KEY `routes_collector_id_foreign` (`collector_id`),
  KEY `routes_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `routes_collector_id_foreign` FOREIGN KEY (`collector_id`) REFERENCES `collectors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `routes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `routes_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `routes`
--

LOCK TABLES `routes` WRITE;
/*!40000 ALTER TABLE `routes` DISABLE KEYS */;
INSERT INTO `routes` VALUES (1,1,1,1,'Ruta Centro y Este','Ruta demo para probar mapa, cobros y mora por cliente.','active','2026-06-01 12:34:03','2026-06-01 12:34:03');
/*!40000 ALTER TABLE `routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('9fFhj89Fn6t8IDpNAiBU7zmt6Wfq1kO8b4MQeMSn',1,'104.22.24.238','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiRTZyVWdTbFZUUmlGb2ptVmc4OXZ6UzU3SGEzekFRaEZNQWxKYjdkaSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjM5OiJodHRwOi8vcHJlc3RhbWlzdGEuYnNvbHV0aW9ucy5kZXYvcm9sZXMiO3M6NToicm91dGUiO3M6MTE6InJvbGVzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1780345781),('h6GiLyqJT0oq9ZaMUIMpCjkPT8VdqaAZdCiGgvph',NULL,'172.70.226.145','WhatsApp/2.2620.102 W','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVXA1RFNxUG5rQzZicTc3cTZiS205bnhBYVdjRDdiTUlkUUpON2ZmZSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cDovL3ByZXN0YW1pc3RhLmJzb2x1dGlvbnMuZGV2Ijt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9wcmVzdGFtaXN0YS5ic29sdXRpb25zLmRldiI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1780346616),('YaY8N0J8ycgOEf0717EDhPCa95gMLRZMeGxqptmS',NULL,'172.70.226.145','WhatsApp/2.2620.102 W','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVHFkU0RIeGsySUdKUXppaFBDSFJmVlhjMFdSVXFaSmVOZzdEMVRaOCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cDovL3ByZXN0YW1pc3RhLmJzb2x1dGlvbnMuZGV2Ijt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9wcmVzdGFtaXN0YS5ic29sdXRpb25zLmRldiI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1780346621),('Z1SrCrl1k43K7GbyVjO0A6lEydv8MDTCqGgITCf0',1,'172.70.226.145','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWDExVDl0YzBFVjlreVlyY0FNS1dFY1J1cFAxNTVMN1p2aDdJWmMwZyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDM6Imh0dHA6Ly9wcmVzdGFtaXN0YS5ic29sdXRpb25zLmRldi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1780345100);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `is_system_owner` tinyint(1) NOT NULL DEFAULT 0,
  `visible_menus` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visible_menus`)),
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Administrador Demo','admin@sistemaprestamista.local','809-555-1001',NULL,'$2y$12$o4Stw3NjH8FXSiumtFJdWeAQWRBTyIoz7dsfjaMkqR0T1U/XgeQYS','active',0,NULL,'2026-06-01 14:16:42',NULL,'2026-06-01 12:34:03','2026-06-01 14:16:42'),(2,1,'Carlos Cobrador','cobrador@sistemaprestamista.local','809-555-2001',NULL,'$2y$12$CrACjb.dGKUANGRCbebT0uU6wXvqwQLjL2g1ri4yrUuYWuCuPC/Rq','active',0,NULL,NULL,NULL,'2026-06-01 12:34:03','2026-06-01 12:34:03'),(3,1,'Wailan — Dueño del sistema','wailandkey@gmail.com',NULL,NULL,'$2y$12$uPTep8cS1J5YC5ypZeVw..TLZcx3ZuW/8L9pcMnsVMtLgTXvTdKOq','active',1,NULL,NULL,NULL,'2026-06-01 14:55:56','2026-06-01 14:55:56');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zones`
--

DROP TABLE IF EXISTS `zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zones_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `zones_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zones`
--

LOCK TABLES `zones` WRITE;
/*!40000 ALTER TABLE `zones` DISABLE KEYS */;
INSERT INTO `zones` VALUES (1,1,'Zona Centro','Zona demo para rutas urbanas.','2026-06-01 12:34:03','2026-06-01 12:34:03');
/*!40000 ALTER TABLE `zones` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-01 16:57:19
