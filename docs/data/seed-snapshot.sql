-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sistema_prestamos
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,1,'loan_created','loans','Préstamo PRE-20260506-00001 creado.','App\\Models\\Loan',1,NULL,'{\"id\":1,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260506-00001\",\"principal_amount\":120000,\"interest_rate\":12,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":6,\"installment_amount\":22400,\"total_interest\":14400,\"total_amount\":134400,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":120000,\"late_fee_type\":\"daily_fixed\",\"late_fee_value\":150,\"start_date\":\"2026-05-06T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-06T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":\"Motor Honda Lead 2023 y pagar\\u00e9 notarial.\",\"notes\":\"DEMO_PORTFOLIO_V1 Mensual plano: capital RD$120,000, inter\\u00e9s 12%, 6 cuotas de RD$22,400.\",\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-06T14:49:14.000000Z\",\"updated_at\":\"2026-05-06T14:49:14.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-06 14:49:14'),(2,1,1,'loan_created','loans','Préstamo PRE-20260506-00002 creado.','App\\Models\\Loan',2,NULL,'{\"id\":2,\"company_id\":1,\"client_id\":2,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260506-00002\",\"principal_amount\":25000,\"interest_rate\":5,\"interest_type\":\"fixed\",\"payment_frequency\":\"weekly\",\"calculation_method\":\"capital_plus_interest\",\"term_quantity\":8,\"installment_amount\":4375,\"total_interest\":10000,\"total_amount\":35000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":25000,\"late_fee_type\":\"fixed\",\"late_fee_value\":300,\"start_date\":\"2026-04-29T04:00:00.000000Z\",\"first_payment_date\":\"2026-05-06T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":\"Garant\\u00eda solidaria con dos referencias.\",\"notes\":\"DEMO_PORTFOLIO_V1 Semanal capital+inter\\u00e9s: RD$25,000, 5%, 8 cuotas de RD$4,375.\",\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-06T14:49:15.000000Z\",\"updated_at\":\"2026-05-06T14:49:15.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-06 14:49:15'),(3,1,1,'loan_created','loans','Préstamo PRE-20260506-00003 creado.','App\\Models\\Loan',3,NULL,'{\"id\":3,\"company_id\":1,\"client_id\":3,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260506-00003\",\"principal_amount\":15000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"daily\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1650,\"total_interest\":1500,\"total_amount\":16500,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":15000,\"late_fee_type\":\"daily_fixed\",\"late_fee_value\":75,\"start_date\":\"2026-04-20T04:00:00.000000Z\",\"first_payment_date\":\"2026-04-21T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":\"Nevera comercial y contrato firmado.\",\"notes\":\"DEMO_PORTFOLIO_V1 Diario atrasado: RD$15,000, 10%, 10 cuotas de RD$1,650, mora diaria RD$75.\",\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-06T14:49:15.000000Z\",\"updated_at\":\"2026-05-06T14:49:15.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-06 14:49:15'),(4,1,2,'payment_registered','payments','Pago registrado al préstamo PRE-20260506-00002.','App\\Models\\Payment',1,NULL,'{\"id\":1,\"company_id\":1,\"loan_id\":2,\"client_id\":2,\"collector_id\":1,\"receipt_number\":\"REC-20260506-NNARP8MG\",\"payment_date\":\"2026-05-06T04:00:00.000000Z\",\"amount\":4375,\"principal_paid\":3125,\"interest_paid\":1250,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":25000,\"new_balance\":21875,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":2,\"created_at\":\"2026-05-06T14:49:15.000000Z\",\"updated_at\":\"2026-05-06T14:49:15.000000Z\"}','127.0.0.1','Symfony','2026-05-06 14:49:15'),(5,1,1,'loan_created','loans','Préstamo PRE-20260531-00004 creado.','App\\Models\\Loan',4,NULL,'{\"id\":4,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00004\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(6,1,1,'loan_created','loans','Préstamo PRE-20260531-00005 creado.','App\\Models\\Loan',5,NULL,'{\"id\":5,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00005\",\"principal_amount\":12000,\"interest_rate\":2,\"interest_type\":\"amortized\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"french_amortization\",\"term_quantity\":6,\"installment_amount\":2142.31,\"total_interest\":853.86,\"total_amount\":12853.86,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":12000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(7,1,1,'loan_created','loans','Préstamo PRE-20260531-00006 creado.','App\\Models\\Loan',6,NULL,'{\"id\":6,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00006\",\"principal_amount\":6000,\"interest_rate\":5,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"capital_plus_interest\",\"term_quantity\":6,\"installment_amount\":1300,\"total_interest\":1800,\"total_amount\":7800,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":6000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(8,1,1,'loan_created','loans','Préstamo PRE-20260531-00007 creado.','App\\Models\\Loan',7,NULL,'{\"id\":7,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00007\",\"principal_amount\":3000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"daily\",\"calculation_method\":\"flat_interest\",\"term_quantity\":3,\"installment_amount\":1100,\"total_interest\":300,\"total_amount\":3300,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":3000,\"late_fee_type\":\"daily_fixed\",\"late_fee_value\":50,\"start_date\":\"2026-05-25T04:00:00.000000Z\",\"first_payment_date\":\"2026-05-26T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(9,1,1,'loan_created','loans','Préstamo PRE-20260531-00008 creado.','App\\Models\\Loan',8,NULL,'{\"id\":8,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00008\",\"principal_amount\":1000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":1,\"installment_amount\":1100,\"total_interest\":100,\"total_amount\":1100,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":1000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(10,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00004.','App\\Models\\Payment',2,NULL,'{\"id\":2,\"company_id\":1,\"loan_id\":4,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-W9TNRYYM\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1100,\"principal_paid\":1000,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":10000,\"new_balance\":9000,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(11,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00004.','App\\Models\\Payment',3,NULL,'{\"id\":3,\"company_id\":1,\"loan_id\":4,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-MIU54DBV\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":550,\"principal_paid\":450,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":9000,\"new_balance\":8550,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(12,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00006.','App\\Models\\Payment',4,NULL,'{\"id\":4,\"company_id\":1,\"loan_id\":6,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-SVUKQB8Q\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1300,\"principal_paid\":1000,\"interest_paid\":300,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":6000,\"new_balance\":5000,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(13,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00007.','App\\Models\\Payment',5,NULL,'{\"id\":5,\"company_id\":1,\"loan_id\":7,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-T1SOMCGI\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1350,\"principal_paid\":1000,\"interest_paid\":100,\"late_fee_paid\":250,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":3000,\"new_balance\":2000,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(14,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00008.','App\\Models\\Payment',6,NULL,'{\"id\":6,\"company_id\":1,\"loan_id\":8,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-SKP9BGF2\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1100,\"principal_paid\":1000,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":1000,\"new_balance\":0,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:07:02.000000Z\",\"updated_at\":\"2026-05-31T17:07:02.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:07:02'),(15,1,1,'loan_created','loans','Préstamo PRE-20260531-00009 creado.','App\\Models\\Loan',9,NULL,'{\"id\":9,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00009\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(16,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00009.','App\\Models\\Payment',7,NULL,'{\"id\":7,\"company_id\":1,\"loan_id\":9,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-R0SNTDOQ\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":100,\"principal_paid\":0,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":10000,\"new_balance\":10000,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(17,1,1,'loan_created','loans','Préstamo PRE-20260531-00010 creado.','App\\Models\\Loan',10,NULL,'{\"id\":10,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00010\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(18,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00010.','App\\Models\\Payment',8,NULL,'{\"id\":8,\"company_id\":1,\"loan_id\":10,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-CCIVOE8G\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":500,\"principal_paid\":500,\"interest_paid\":0,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":10000,\"new_balance\":9500,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(19,1,1,'loan_created','loans','Préstamo PRE-20260531-00011 creado.','App\\Models\\Loan',11,NULL,'{\"id\":11,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00011\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(20,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00011.','App\\Models\\Payment',9,NULL,'{\"id\":9,\"company_id\":1,\"loan_id\":11,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-WIGCVVZ1\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1100,\"principal_paid\":1000,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":10000,\"new_balance\":9000,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(21,1,1,'loan_created','loans','Préstamo PRE-20260531-00012 creado.','App\\Models\\Loan',12,NULL,'{\"id\":12,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00012\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(22,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00012.','App\\Models\\Payment',10,NULL,'{\"id\":10,\"company_id\":1,\"loan_id\":12,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-KG9BSLOU\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1700,\"principal_paid\":1500,\"interest_paid\":200,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":10000,\"new_balance\":8500,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(23,1,1,'loan_created','loans','Préstamo PRE-20260531-00013 creado.','App\\Models\\Loan',13,NULL,'{\"id\":13,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00013\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:49:20.000000Z\",\"updated_at\":\"2026-05-31T17:49:20.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:49:20'),(24,1,1,'loan_created','loans','Préstamo PRE-20260531-00014 creado.','App\\Models\\Loan',14,NULL,'{\"id\":14,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00014\",\"principal_amount\":5000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":5,\"installment_amount\":1100,\"total_interest\":500,\"total_amount\":5500,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":5000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:53:26'),(25,1,1,'loan_updated','loans','Préstamo PRE-20260531-00014 actualizado.','App\\Models\\Loan',14,'{\"principal_amount\":5000,\"interest_rate\":10,\"term_quantity\":5,\"calculation_method\":\"flat_interest\",\"payment_frequency\":\"monthly\"}','{\"id\":14,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00014\",\"principal_amount\":8000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":8,\"installment_amount\":1100,\"total_interest\":800,\"total_amount\":8800,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":8000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":\"editado\",\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:53:26'),(26,1,1,'loan_created','loans','Préstamo PRE-20260531-00015 creado.','App\\Models\\Loan',15,NULL,'{\"id\":15,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00015\",\"principal_amount\":5000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":5,\"installment_amount\":1100,\"total_interest\":500,\"total_amount\":5500,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":5000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:53:26'),(27,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00015.','App\\Models\\Payment',11,NULL,'{\"id\":11,\"company_id\":1,\"loan_id\":15,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-PMY181EZ\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1100,\"principal_paid\":1000,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":5000,\"new_balance\":4000,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:53:26'),(28,1,1,'loan_updated','loans','Préstamo PRE-20260531-00015 actualizado.','App\\Models\\Loan',15,'{\"principal_amount\":5000,\"interest_rate\":10,\"term_quantity\":5,\"calculation_method\":\"flat_interest\",\"payment_frequency\":\"monthly\"}','{\"id\":15,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00015\",\"principal_amount\":5000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":5,\"installment_amount\":1100,\"total_interest\":500,\"total_amount\":5500,\"paid_principal\":1000,\"paid_interest\":100,\"paid_late_fee\":0,\"remaining_balance\":4000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":\"nota segura\",\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:53:26'),(29,1,1,'loan_created','loans','Préstamo PRE-20260531-00016 creado.','App\\Models\\Loan',16,NULL,'{\"id\":16,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00016\",\"principal_amount\":5000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":5,\"installment_amount\":1100,\"total_interest\":500,\"total_amount\":5500,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":5000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:53:26'),(30,1,1,'loan_deleted','loans','Préstamo PRE-20260531-00016 anulado.','App\\Models\\Loan',16,'{\"id\":16,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00016\",\"principal_amount\":5000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":5,\"installment_amount\":1100,\"total_interest\":500,\"total_amount\":5500,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":5000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"deleted_at\":null}',NULL,'127.0.0.1','Symfony','2026-05-31 17:53:26'),(31,1,1,'loan_created','loans','Préstamo PRE-20260531-00017 creado.','App\\Models\\Loan',17,NULL,'{\"id\":17,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00017\",\"principal_amount\":5000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":5,\"installment_amount\":1100,\"total_interest\":500,\"total_amount\":5500,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":5000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"deleted_at\":null}','127.0.0.1','Symfony','2026-05-31 17:53:26'),(32,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00017.','App\\Models\\Payment',12,NULL,'{\"id\":12,\"company_id\":1,\"loan_id\":17,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-VERVTRW7\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1100,\"principal_paid\":1000,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":5000,\"new_balance\":4000,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T17:53:26.000000Z\",\"updated_at\":\"2026-05-31T17:53:26.000000Z\",\"mobile_uuid\":null}','127.0.0.1','Symfony','2026-05-31 17:53:26'),(33,1,1,'loan_created','loans','Préstamo PRE-20260531-00018 creado.','App\\Models\\Loan',18,NULL,'{\"id\":18,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00018\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T18:22:52.000000Z\",\"updated_at\":\"2026-05-31T18:22:52.000000Z\",\"deleted_at\":null,\"allows_capital_prepayment\":true}','127.0.0.1','Symfony','2026-05-31 18:22:52'),(34,1,1,'loan_created','loans','Préstamo PRE-20260531-00019 creado.','App\\Models\\Loan',19,NULL,'{\"id\":19,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00019\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T18:23:37.000000Z\",\"updated_at\":\"2026-05-31T18:23:37.000000Z\",\"deleted_at\":null,\"allows_capital_prepayment\":true}','127.0.0.1','Symfony','2026-05-31 18:23:37'),(35,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00019.','App\\Models\\Payment',13,NULL,'{\"id\":13,\"company_id\":1,\"loan_id\":19,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-V4KWBPCS\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":2000,\"principal_paid\":1900,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":10000,\"new_balance\":8100,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T18:23:37.000000Z\",\"updated_at\":\"2026-05-31T18:23:37.000000Z\",\"mobile_uuid\":null,\"capital_prepaid\":900,\"change_given\":0}','127.0.0.1','Symfony','2026-05-31 18:23:37'),(36,1,1,'loan_created','loans','Préstamo PRE-20260531-00020 creado.','App\\Models\\Loan',20,NULL,'{\"id\":20,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00020\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T18:23:37.000000Z\",\"updated_at\":\"2026-05-31T18:23:37.000000Z\",\"deleted_at\":null,\"allows_capital_prepayment\":true}','127.0.0.1','Symfony','2026-05-31 18:23:37'),(37,1,1,'payment_registered','payments','Pago registrado al préstamo PRE-20260531-00020.','App\\Models\\Payment',14,NULL,'{\"id\":14,\"company_id\":1,\"loan_id\":20,\"client_id\":1,\"collector_id\":1,\"receipt_number\":\"REC-20260531-LCR9AQYU\",\"payment_date\":\"2026-05-31T04:00:00.000000Z\",\"amount\":1100,\"principal_paid\":1000,\"interest_paid\":100,\"late_fee_paid\":0,\"discount\":0,\"payment_method\":\"cash\",\"previous_balance\":10000,\"new_balance\":9000,\"status\":\"valid\",\"cancelled_by\":null,\"cancelled_at\":null,\"cancellation_reason\":null,\"created_by\":1,\"created_at\":\"2026-05-31T18:23:37.000000Z\",\"updated_at\":\"2026-05-31T18:23:37.000000Z\",\"mobile_uuid\":null,\"capital_prepaid\":0,\"change_given\":500}','127.0.0.1','Symfony','2026-05-31 18:23:37'),(38,1,1,'loan_created','loans','Préstamo PRE-20260531-00021 creado.','App\\Models\\Loan',21,NULL,'{\"id\":21,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00021\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T18:23:37.000000Z\",\"updated_at\":\"2026-05-31T18:23:37.000000Z\",\"deleted_at\":null,\"allows_capital_prepayment\":false}','127.0.0.1','Symfony','2026-05-31 18:23:37'),(39,1,1,'loan_created','loans','Préstamo PRE-20260531-00022 creado.','App\\Models\\Loan',22,NULL,'{\"id\":22,\"company_id\":1,\"client_id\":1,\"collector_id\":1,\"quote_id\":null,\"loan_number\":\"PRE-20260531-00022\",\"principal_amount\":10000,\"interest_rate\":10,\"interest_type\":\"fixed\",\"payment_frequency\":\"monthly\",\"calculation_method\":\"flat_interest\",\"term_quantity\":10,\"installment_amount\":1100,\"total_interest\":1000,\"total_amount\":11000,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"remaining_balance\":10000,\"late_fee_type\":\"none\",\"late_fee_value\":0,\"start_date\":\"2026-05-31T04:00:00.000000Z\",\"first_payment_date\":\"2026-06-01T04:00:00.000000Z\",\"end_date\":null,\"status\":\"active\",\"guarantee_description\":null,\"notes\":null,\"approved_by\":1,\"created_by\":1,\"created_at\":\"2026-05-31T18:23:37.000000Z\",\"updated_at\":\"2026-05-31T18:23:37.000000Z\",\"deleted_at\":null,\"allows_capital_prepayment\":true}','127.0.0.1','Symfony','2026-05-31 18:23:37'),(40,1,1,'settings_updated','settings','Configuración de empresa actualizada.','App\\Models\\Company',1,'{\"company\":{\"name\":\"Prestamista Demo RD\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','{\"company\":{\"name\":\"Prestamista Demo RD\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"US$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.0.0 Safari/537.36','2026-05-31 21:54:54'),(41,1,1,'settings_updated','settings','Configuración de empresa actualizada.','App\\Models\\Company',1,'{\"company\":{\"name\":\"Prestamista Demo RD\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"US$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','{\"company\":{\"name\":\"Prestamista Demo RD\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.0.0 Safari/537.36','2026-05-31 21:54:58'),(42,1,1,'settings_updated','settings','Configuración de empresa actualizada.','App\\Models\\Company',1,'{\"company\":{\"name\":\"Prestamista Demo RD\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','{\"company\":{\"name\":\"Prestamista Demo RD\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"US$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-31 22:25:20'),(43,1,1,'settings_updated','settings','Configuración de empresa actualizada.','App\\Models\\Company',1,'{\"company\":{\"name\":\"Prestamista Demo RD\",\"plan\":\"prestamista\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','{\"company\":{\"name\":\"Prestamista Demo RD\",\"plan\":\"full\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-06-01 02:06:09'),(44,1,1,'settings_updated','settings','Configuración de empresa actualizada.','App\\Models\\Company',1,'{\"company\":{\"name\":\"Prestamista Demo RD\",\"plan\":\"full\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','{\"company\":{\"name\":\"Prestamista Demo RD\",\"plan\":\"prestamista\",\"rnc\":\"131000001\",\"phone\":\"809-555-1000\",\"email\":\"admin@sistemaprestamista.local\",\"address\":\"Av. Winston Churchill, Santo Domingo\"},\"settings\":{\"currency\":\"RD$\",\"default_interest_rate\":10,\"default_late_fee_type\":\"daily_fixed\",\"default_late_fee_value\":75,\"receipt_prefix\":\"REC\",\"loan_prefix\":\"PRE\",\"quote_prefix\":\"COT\",\"allow_partial_payments\":true,\"allow_payment_cancellation\":true,\"require_approval_for_loans\":false,\"exclude_sundays_for_daily_loans\":true,\"route_visit_radius_meters\":75}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-06-01 02:06:21');
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
INSERT INTO `cache` VALUES ('sistema-prestamista-cache-admin@sistemaprestamista.loca|127.0.0.1','i:1;',1780243973),('sistema-prestamista-cache-admin@sistemaprestamista.loca|127.0.0.1:timer','i:1780243973;',1780243973),('sistema-prestamista-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:5:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";s:1:\"j\";s:10:\"company_id\";}s:11:\"permissions\";a:25:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:12:\"clients.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"clients.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:14:\"clients.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:14:\"clients.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:13:\"quotes.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:10:\"loans.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:12:\"loans.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:13:\"loans.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:12:\"loans.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:15:\"payments.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:4;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:15:\"payments.cancel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:17:\"collectors.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:13:\"routes.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:15:\"expenses.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:9:\"cash.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:12:\"reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:18:\"documents.generate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:12:\"legal.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:5;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:15:\"settings.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:12:\"users.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:14:\"quotes.convert\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:13:\"quotes.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:12:\"loans.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:24;a:3:{s:1:\"a\";i:25;s:1:\"b\";s:21:\"companies.manage-plan\";s:1:\"c\";s:3:\"web\";}}s:5:\"roles\";a:5:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"j\";N;s:1:\"b\";s:13:\"Administrador\";s:1:\"c\";s:3:\"web\";}i:1;a:4:{s:1:\"a\";i:2;s:1:\"j\";N;s:1:\"b\";s:10:\"Supervisor\";s:1:\"c\";s:3:\"web\";}i:2;a:4:{s:1:\"a\";i:3;s:1:\"j\";N;s:1:\"b\";s:8:\"Cobrador\";s:1:\"c\";s:3:\"web\";}i:3;a:4:{s:1:\"a\";i:4;s:1:\"j\";N;s:1:\"b\";s:17:\"Caja/Contabilidad\";s:1:\"c\";s:3:\"web\";}i:4;a:4:{s:1:\"a\";i:5;s:1:\"j\";N;s:1:\"b\";s:5:\"Legal\";s:1:\"c\";s:3:\"web\";}}}',1780433364);
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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_movements`
--

LOCK TABLES `cash_movements` WRITE;
/*!40000 ALTER TABLE `cash_movements` DISABLE KEYS */;
INSERT INTO `cash_movements` VALUES (1,1,'loan_disbursement',120000.00,'out','App\\Models\\Loan',1,'Desembolso de préstamo PRE-20260506-00001','2026-05-06',1,'2026-05-06 14:49:14','2026-05-06 14:49:14'),(2,1,'loan_disbursement',25000.00,'out','App\\Models\\Loan',2,'Desembolso de préstamo PRE-20260506-00002','2026-05-06',1,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(3,1,'loan_disbursement',15000.00,'out','App\\Models\\Loan',3,'Desembolso de préstamo PRE-20260506-00003','2026-05-06',1,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(4,1,'payment_received',4375.00,'in','App\\Models\\Payment',1,'Pago recibido REC-20260506-NNARP8MG','2026-05-06',2,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(5,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',4,'Desembolso de préstamo PRE-20260531-00004','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(6,1,'loan_disbursement',12000.00,'out','App\\Models\\Loan',5,'Desembolso de préstamo PRE-20260531-00005','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(7,1,'loan_disbursement',6000.00,'out','App\\Models\\Loan',6,'Desembolso de préstamo PRE-20260531-00006','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(8,1,'loan_disbursement',3000.00,'out','App\\Models\\Loan',7,'Desembolso de préstamo PRE-20260531-00007','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(9,1,'loan_disbursement',1000.00,'out','App\\Models\\Loan',8,'Desembolso de préstamo PRE-20260531-00008','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(10,1,'payment_received',1100.00,'in','App\\Models\\Payment',2,'Pago recibido REC-20260531-W9TNRYYM','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(11,1,'payment_received',550.00,'in','App\\Models\\Payment',3,'Pago recibido REC-20260531-MIU54DBV','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(12,1,'payment_received',1300.00,'in','App\\Models\\Payment',4,'Pago recibido REC-20260531-SVUKQB8Q','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(13,1,'payment_received',1350.00,'in','App\\Models\\Payment',5,'Pago recibido REC-20260531-T1SOMCGI','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(14,1,'payment_received',1100.00,'in','App\\Models\\Payment',6,'Pago recibido REC-20260531-SKP9BGF2','2026-05-31',1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(15,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',9,'Desembolso de préstamo PRE-20260531-00009','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(16,1,'payment_received',100.00,'in','App\\Models\\Payment',7,'Pago recibido REC-20260531-R0SNTDOQ','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(17,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',10,'Desembolso de préstamo PRE-20260531-00010','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(18,1,'payment_received',500.00,'in','App\\Models\\Payment',8,'Pago recibido REC-20260531-CCIVOE8G','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(19,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',11,'Desembolso de préstamo PRE-20260531-00011','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(20,1,'payment_received',1100.00,'in','App\\Models\\Payment',9,'Pago recibido REC-20260531-WIGCVVZ1','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(21,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',12,'Desembolso de préstamo PRE-20260531-00012','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(22,1,'payment_received',1700.00,'in','App\\Models\\Payment',10,'Pago recibido REC-20260531-KG9BSLOU','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(23,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',13,'Desembolso de préstamo PRE-20260531-00013','2026-05-31',1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(24,1,'loan_disbursement',5000.00,'out','App\\Models\\Loan',14,'Desembolso de préstamo PRE-20260531-00014','2026-05-31',1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(25,1,'loan_disbursement',5000.00,'out','App\\Models\\Loan',15,'Desembolso de préstamo PRE-20260531-00015','2026-05-31',1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(26,1,'payment_received',1100.00,'in','App\\Models\\Payment',11,'Pago recibido REC-20260531-PMY181EZ','2026-05-31',1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(27,1,'loan_disbursement',5000.00,'out','App\\Models\\Loan',16,'Desembolso de préstamo PRE-20260531-00016','2026-05-31',1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(28,1,'adjustment',5000.00,'in','App\\Models\\Loan',16,'Reverso por anulación de préstamo PRE-20260531-00016','2026-05-31',1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(29,1,'loan_disbursement',5000.00,'out','App\\Models\\Loan',17,'Desembolso de préstamo PRE-20260531-00017','2026-05-31',1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(30,1,'payment_received',1100.00,'in','App\\Models\\Payment',12,'Pago recibido REC-20260531-VERVTRW7','2026-05-31',1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(31,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',18,'Desembolso de préstamo PRE-20260531-00018','2026-05-31',1,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(32,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',19,'Desembolso de préstamo PRE-20260531-00019','2026-05-31',1,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(33,1,'payment_received',2000.00,'in','App\\Models\\Payment',13,'Pago recibido REC-20260531-V4KWBPCS','2026-05-31',1,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(34,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',20,'Desembolso de préstamo PRE-20260531-00020','2026-05-31',1,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(35,1,'payment_received',1100.00,'in','App\\Models\\Payment',14,'Pago recibido REC-20260531-LCR9AQYU','2026-05-31',1,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(36,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',21,'Desembolso de préstamo PRE-20260531-00021','2026-05-31',1,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(37,1,'loan_disbursement',10000.00,'out','App\\Models\\Loan',22,'Desembolso de préstamo PRE-20260531-00022','2026-05-31',1,'2026-05-31 18:23:37','2026-05-31 18:23:37');
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
INSERT INTO `clients` VALUES (1,1,'CLI-DEMO-001','María Rodríguez','001-1234567-8','809-555-3001','829-555-3001',NULL,'Ensanche Naco, Santo Domingo',18.4834020,-69.9312120,'Cerca de Av. Tiradentes','Comercial Rodríguez',NULL,85000.00,NULL,'active','low','Cliente demo con préstamo mensual al día.','2026-05-06 14:49:14','2026-05-07 03:40:42',NULL),(2,1,'CLI-DEMO-002','José Martínez','001-7654321-0','809-555-3002',NULL,NULL,'Los Mina, Santo Domingo Este',18.4919780,-69.8561590,'Próximo a Av. Venezuela','Taller Martínez',NULL,52000.00,NULL,'active','medium','Cliente demo con préstamo semanal y primer pago registrado.','2026-05-06 14:49:14','2026-05-07 03:40:42',NULL),(3,1,'CLI-DEMO-003','Ana Pérez','402-1122334-5','809-555-3003',NULL,NULL,'Villa Consuelo, Santo Domingo',18.4765680,-69.8984090,'Zona comercial de Villa Consuelo','Colmado Ana',NULL,45000.00,NULL,'active','high','Cliente demo con préstamo diario atrasado para probar mora.','2026-05-06 14:49:14','2026-05-07 03:40:42',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collector_commissions`
--

LOCK TABLES `collector_commissions` WRITE;
/*!40000 ALTER TABLE `collector_commissions` DISABLE KEYS */;
INSERT INTO `collector_commissions` VALUES (1,1,1,1,'percentage',5.00,4375.00,218.75,'pending',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(2,1,1,2,'percentage',5.00,1100.00,55.00,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(3,1,1,3,'percentage',5.00,550.00,27.50,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(4,1,1,4,'percentage',5.00,1300.00,65.00,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(5,1,1,5,'percentage',5.00,1350.00,67.50,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(6,1,1,6,'percentage',5.00,1100.00,55.00,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(7,1,1,7,'percentage',5.00,100.00,5.00,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(8,1,1,8,'percentage',5.00,500.00,25.00,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(9,1,1,9,'percentage',5.00,1100.00,55.00,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(10,1,1,10,'percentage',5.00,1700.00,85.00,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(11,1,1,11,'percentage',5.00,1100.00,55.00,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(12,1,1,12,'percentage',5.00,1100.00,55.00,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(13,1,1,13,'percentage',5.00,2000.00,100.00,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(14,1,1,14,'percentage',5.00,1100.00,55.00,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37');
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
  KEY `location_points_session_recorded_index` (`collector_route_session_id`,`recorded_at`),
  KEY `collector_location_points_collector_id_recorded_at_index` (`collector_id`,`recorded_at`),
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
INSERT INTO `collectors` VALUES (1,1,2,'Carlos Cobrador','809-555-2001','percentage',5.00,'active','2026-05-06 14:49:14','2026-05-06 14:49:14');
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
INSERT INTO `companies` VALUES (1,'Prestamista Demo RD','131000001','809-555-1000','admin@sistemaprestamista.local','Av. Winston Churchill, Santo Domingo',NULL,'active','prestamista','2026-05-06 02:59:31','2026-06-01 02:06:21');
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
INSERT INTO `company_settings` VALUES (1,1,'RD$',10.0000,'daily_fixed',75.00,'REC','PRE','COT',1,1,0,1,75,'2026-05-06 02:59:31','2026-06-01 02:04:51');
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
INSERT INTO `expense_categories` VALUES (1,1,'Transporte y combustible','2026-05-06 14:49:14','2026-05-06 14:49:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=179 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_installments`
--

LOCK TABLES `loan_installments` WRITE;
/*!40000 ALTER TABLE `loan_installments` DISABLE KEYS */;
INSERT INTO `loan_installments` VALUES (1,1,1,'2026-06-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:14','2026-05-06 14:49:14'),(2,1,2,'2026-07-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:14','2026-05-06 14:49:14'),(3,1,3,'2026-08-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:14','2026-05-06 14:49:14'),(4,1,4,'2026-09-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:14','2026-05-06 14:49:14'),(5,1,5,'2026-10-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:14','2026-05-06 14:49:14'),(6,1,6,'2026-11-06',20000.00,2400.00,22400.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:14','2026-05-06 14:49:14'),(7,2,1,'2026-05-06',3125.00,1250.00,4375.00,3125.00,1250.00,0.00,4375.00,0.00,0,'paid','2026-05-06 14:49:15','2026-05-06 14:49:15','2026-05-06 14:49:15'),(8,2,2,'2026-05-13',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(9,2,3,'2026-05-20',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(10,2,4,'2026-05-27',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(11,2,5,'2026-06-03',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(12,2,6,'2026-06-10',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(13,2,7,'2026-06-17',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(14,2,8,'2026-06-24',3125.00,1250.00,4375.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(15,3,1,'2026-04-21',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,1125.00,15,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(16,3,2,'2026-04-22',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,1050.00,14,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(17,3,3,'2026-04-23',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,975.00,13,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(18,3,4,'2026-04-24',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,900.00,12,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(19,3,5,'2026-04-25',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,825.00,11,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(20,3,6,'2026-04-27',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,675.00,9,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(21,3,7,'2026-04-28',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,600.00,8,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(22,3,8,'2026-04-29',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,525.00,7,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(23,3,9,'2026-04-30',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,450.00,6,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(24,3,10,'2026-05-01',1500.00,150.00,1650.00,0.00,0.00,0.00,0.00,375.00,5,'late',NULL,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(25,4,1,'2026-06-01',1000.00,100.00,1100.00,1000.00,100.00,0.00,1100.00,0.00,0,'paid','2026-05-31 17:07:02','2026-05-31 17:07:02','2026-05-31 17:07:02'),(26,4,2,'2026-07-01',1000.00,100.00,1100.00,450.00,100.00,0.00,550.00,0.00,0,'partial',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(27,4,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(28,4,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(29,4,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(30,4,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(31,4,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(32,4,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(33,4,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(34,4,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(35,5,1,'2026-06-01',1902.31,240.00,2142.31,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(36,5,2,'2026-07-01',1940.36,201.95,2142.31,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(37,5,3,'2026-08-01',1979.16,163.15,2142.31,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(38,5,4,'2026-09-01',2018.75,123.56,2142.31,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(39,5,5,'2026-10-01',2059.12,83.19,2142.31,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(40,5,6,'2026-11-01',2100.30,42.01,2142.31,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(41,6,1,'2026-06-01',1000.00,300.00,1300.00,1000.00,300.00,0.00,1300.00,0.00,0,'paid','2026-05-31 17:07:02','2026-05-31 17:07:02','2026-05-31 17:07:02'),(42,6,2,'2026-07-01',1000.00,300.00,1300.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(43,6,3,'2026-08-01',1000.00,300.00,1300.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(44,6,4,'2026-09-01',1000.00,300.00,1300.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(45,6,5,'2026-10-01',1000.00,300.00,1300.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(46,6,6,'2026-11-01',1000.00,300.00,1300.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(47,7,1,'2026-05-26',1000.00,100.00,1100.00,1000.00,100.00,250.00,1350.00,250.00,5,'paid','2026-05-31 17:07:02','2026-05-31 17:07:02','2026-05-31 17:07:02'),(48,7,2,'2026-05-27',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(49,7,3,'2026-05-28',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(50,8,1,'2026-06-01',1000.00,100.00,1100.00,1000.00,100.00,0.00,1100.00,0.00,0,'paid','2026-05-31 17:07:02','2026-05-31 17:07:02','2026-05-31 17:07:02'),(51,9,1,'2026-06-01',1000.00,100.00,1100.00,0.00,100.00,0.00,100.00,0.00,0,'partial',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(52,9,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(53,9,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(54,9,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(55,9,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(56,9,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(57,9,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(58,9,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(59,9,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(60,9,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(61,10,1,'2026-06-01',1000.00,100.00,1100.00,500.00,0.00,0.00,500.00,0.00,0,'partial',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(62,10,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(63,10,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(64,10,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(65,10,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(66,10,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(67,10,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(68,10,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(69,10,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(70,10,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(71,11,1,'2026-06-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(72,11,2,'2026-07-01',1000.00,100.00,1100.00,1000.00,100.00,0.00,1100.00,0.00,0,'paid','2026-05-31 17:49:20','2026-05-31 17:49:20','2026-05-31 17:49:20'),(73,11,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(74,11,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(75,11,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(76,11,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(77,11,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(78,11,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(79,11,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(80,11,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(81,12,1,'2026-06-01',1000.00,100.00,1100.00,1000.00,100.00,0.00,1100.00,0.00,0,'paid','2026-05-31 17:49:20','2026-05-31 17:49:20','2026-05-31 17:49:20'),(82,12,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(83,12,3,'2026-08-01',1000.00,100.00,1100.00,500.00,100.00,0.00,600.00,0.00,0,'partial',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(84,12,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(85,12,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(86,12,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(87,12,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(88,12,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(89,12,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(90,12,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(91,13,1,'2026-06-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(92,13,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(93,13,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(94,13,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(95,13,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(96,13,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(97,13,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(98,13,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(99,13,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(100,13,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(106,14,1,'2026-06-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(107,14,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(108,14,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(109,14,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(110,14,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(111,14,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(112,14,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(113,14,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(114,15,1,'2026-06-01',1000.00,100.00,1100.00,1000.00,100.00,0.00,1100.00,0.00,0,'paid','2026-05-31 17:53:26','2026-05-31 17:53:26','2026-05-31 17:53:26'),(115,15,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(116,15,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(117,15,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(118,15,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(124,17,1,'2026-06-01',1000.00,100.00,1100.00,1000.00,100.00,0.00,1100.00,0.00,0,'paid','2026-05-31 17:53:26','2026-05-31 17:53:26','2026-05-31 17:53:26'),(125,17,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(126,17,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(127,17,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(128,17,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(129,18,1,'2026-06-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(130,18,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(131,18,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(132,18,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(133,18,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(134,18,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(135,18,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(136,18,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(137,18,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(138,18,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:22:52','2026-05-31 18:22:52'),(139,19,1,'2026-06-01',1000.00,100.00,1100.00,1000.00,100.00,0.00,1100.00,0.00,0,'paid','2026-05-31 18:23:37','2026-05-31 18:23:37','2026-05-31 18:23:37'),(140,19,2,'2026-07-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(141,19,3,'2026-08-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(142,19,4,'2026-09-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(143,19,5,'2026-10-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(144,19,6,'2026-11-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(145,19,7,'2026-12-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(146,19,8,'2027-01-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(147,19,9,'2027-02-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(148,19,10,'2027-03-01',900.00,90.00,990.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(149,20,1,'2026-06-01',1000.00,100.00,1100.00,1000.00,100.00,0.00,1100.00,0.00,0,'paid','2026-05-31 18:23:37','2026-05-31 18:23:37','2026-05-31 18:23:37'),(150,20,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(151,20,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(152,20,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(153,20,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(154,20,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(155,20,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(156,20,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(157,20,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(158,20,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(159,21,1,'2026-06-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(160,21,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(161,21,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(162,21,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(163,21,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(164,21,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(165,21,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(166,21,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(167,21,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(168,21,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(169,22,1,'2026-06-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(170,22,2,'2026-07-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(171,22,3,'2026-08-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(172,22,4,'2026-09-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(173,22,5,'2026-10-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(174,22,6,'2026-11-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(175,22,7,'2026-12-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(176,22,8,'2027-01-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(177,22,9,'2027-02-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(178,22,10,'2027-03-01',1000.00,100.00,1100.00,0.00,0.00,0.00,0.00,0.00,0,'pending',NULL,'2026-05-31 18:23:37','2026-05-31 18:23:37');
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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (1,1,1,1,NULL,'PRE-20260506-00001',120000.00,12.0000,'fixed','monthly','flat_interest',6,22400.00,14400.00,134400.00,0.00,0.00,0.00,120000.00,'daily_fixed',150.00,1,'2026-05-06','2026-06-06',NULL,'active','Motor Honda Lead 2023 y pagaré notarial.','DEMO_PORTFOLIO_V1 Mensual plano: capital RD$120,000, interés 12%, 6 cuotas de RD$22,400.',1,1,'2026-05-06 14:49:14','2026-05-06 14:49:14',NULL),(2,1,2,1,NULL,'PRE-20260506-00002',25000.00,5.0000,'fixed','weekly','capital_plus_interest',8,4375.00,10000.00,35000.00,3125.00,1250.00,0.00,21875.00,'fixed',300.00,1,'2026-04-29','2026-05-06',NULL,'active','Garantía solidaria con dos referencias.','DEMO_PORTFOLIO_V1 Semanal capital+interés: RD$25,000, 5%, 8 cuotas de RD$4,375.',1,1,'2026-05-06 14:49:15','2026-05-06 14:49:15',NULL),(3,1,3,1,NULL,'PRE-20260506-00003',15000.00,10.0000,'fixed','daily','flat_interest',10,1650.00,1500.00,16500.00,0.00,0.00,0.00,15000.00,'daily_fixed',75.00,1,'2026-04-20','2026-04-21',NULL,'late','Nevera comercial y contrato firmado.','DEMO_PORTFOLIO_V1 Diario atrasado: RD$15,000, 10%, 10 cuotas de RD$1,650, mora diaria RD$75.',1,1,'2026-05-06 14:49:15','2026-05-06 14:49:15',NULL),(4,1,1,1,NULL,'PRE-20260531-00004',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,1450.00,200.00,0.00,8550.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:07:02','2026-05-31 17:07:02',NULL),(5,1,1,1,NULL,'PRE-20260531-00005',12000.00,2.0000,'amortized','monthly','french_amortization',6,2142.31,853.86,12853.86,0.00,0.00,0.00,12000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:07:02','2026-05-31 17:07:02',NULL),(6,1,1,1,NULL,'PRE-20260531-00006',6000.00,5.0000,'fixed','monthly','capital_plus_interest',6,1300.00,1800.00,7800.00,1000.00,300.00,0.00,5000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:07:02','2026-05-31 17:07:02',NULL),(7,1,1,1,NULL,'PRE-20260531-00007',3000.00,10.0000,'fixed','daily','flat_interest',3,1100.00,300.00,3300.00,1000.00,100.00,250.00,2000.00,'daily_fixed',50.00,1,'2026-05-25','2026-05-26',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:07:02','2026-05-31 17:07:02',NULL),(8,1,1,1,NULL,'PRE-20260531-00008',1000.00,10.0000,'fixed','monthly','flat_interest',1,1100.00,100.00,1100.00,1000.00,100.00,0.00,0.00,'none',0.00,1,'2026-05-31','2026-06-01','2026-05-31','paid',NULL,NULL,1,1,'2026-05-31 17:07:02','2026-05-31 17:07:02',NULL),(9,1,1,1,NULL,'PRE-20260531-00009',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,0.00,100.00,0.00,10000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:49:20','2026-05-31 17:49:20',NULL),(10,1,1,1,NULL,'PRE-20260531-00010',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,500.00,0.00,0.00,9500.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:49:20','2026-05-31 17:49:20',NULL),(11,1,1,1,NULL,'PRE-20260531-00011',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,1000.00,100.00,0.00,9000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:49:20','2026-05-31 17:49:20',NULL),(12,1,1,1,NULL,'PRE-20260531-00012',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,1500.00,200.00,0.00,8500.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:49:20','2026-05-31 17:49:20',NULL),(13,1,1,1,NULL,'PRE-20260531-00013',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,0.00,0.00,0.00,10000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:49:20','2026-05-31 17:49:20',NULL),(14,1,1,1,NULL,'PRE-20260531-00014',8000.00,10.0000,'fixed','monthly','flat_interest',8,1100.00,800.00,8800.00,0.00,0.00,0.00,8000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,'editado',1,1,'2026-05-31 17:53:26','2026-05-31 17:53:26',NULL),(15,1,1,1,NULL,'PRE-20260531-00015',5000.00,10.0000,'fixed','monthly','flat_interest',5,1100.00,500.00,5500.00,1000.00,100.00,0.00,4000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,'nota segura',1,1,'2026-05-31 17:53:26','2026-05-31 17:53:26',NULL),(16,1,1,1,NULL,'PRE-20260531-00016',5000.00,10.0000,'fixed','monthly','flat_interest',5,1100.00,500.00,5500.00,0.00,0.00,0.00,5000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:53:26','2026-05-31 17:53:26','2026-05-31 17:53:26'),(17,1,1,1,NULL,'PRE-20260531-00017',5000.00,10.0000,'fixed','monthly','flat_interest',5,1100.00,500.00,5500.00,1000.00,100.00,0.00,4000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 17:53:26','2026-05-31 17:53:26',NULL),(18,1,1,1,NULL,'PRE-20260531-00018',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,0.00,0.00,0.00,10000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 18:22:52','2026-05-31 18:22:52',NULL),(19,1,1,1,NULL,'PRE-20260531-00019',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,910.00,10910.00,1900.00,100.00,0.00,8100.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 18:23:37','2026-05-31 18:23:37',NULL),(20,1,1,1,NULL,'PRE-20260531-00020',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,1000.00,100.00,0.00,9000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 18:23:37','2026-05-31 18:23:37',NULL),(21,1,1,1,NULL,'PRE-20260531-00021',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,0.00,0.00,0.00,10000.00,'none',0.00,0,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 18:23:37','2026-05-31 18:23:37',NULL),(22,1,1,1,NULL,'PRE-20260531-00022',10000.00,10.0000,'fixed','monthly','flat_interest',10,1100.00,1000.00,11000.00,0.00,0.00,0.00,10000.00,'none',0.00,1,'2026-05-31','2026-06-01',NULL,'active',NULL,NULL,1,1,'2026-05-31 18:23:37','2026-05-31 18:23:37',NULL);
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
INSERT INTO `migrations` VALUES (1,'0000_01_01_000000_create_companies_table',1),(2,'0001_01_01_000000_create_users_table',1),(3,'0001_01_01_000001_create_cache_table',1),(4,'0001_01_01_000002_create_jobs_table',1),(5,'2026_05_06_005423_create_permission_tables',1),(6,'2026_05_06_010000_create_lending_domain_tables',1),(7,'2026_05_06_014628_create_personal_access_tokens_table',1),(8,'2026_05_06_020000_add_mobile_uuid_to_payments_table',1),(9,'2026_05_06_030000_add_location_fields_to_clients_table',1),(10,'2026_05_07_001000_create_collector_route_tracking_tables',1),(11,'2026_05_07_002000_add_route_visit_radius_to_company_settings',1),(12,'2026_05_31_141511_add_capital_prepayment_fields',1),(13,'2026_05_31_174040_add_visible_menus_to_users',1),(14,'2026_05_31_175145_allow_pending_status_on_loans',1),(15,'2026_05_31_215905_add_plan_to_companies',1),(16,'2026_06_01_000000_create_system_owner_user',2),(17,'2026_06_01_000100_add_is_system_owner_to_users',3);
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_details`
--

LOCK TABLES `payment_details` WRITE;
/*!40000 ALTER TABLE `payment_details` DISABLE KEYS */;
INSERT INTO `payment_details` VALUES (1,1,7,3125.00,1250.00,0.00,4375.00,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(2,2,25,1000.00,100.00,0.00,1100.00,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(3,3,26,450.00,100.00,0.00,550.00,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(4,4,41,1000.00,300.00,0.00,1300.00,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(5,5,47,1000.00,100.00,250.00,1350.00,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(6,6,50,1000.00,100.00,0.00,1100.00,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(7,7,51,0.00,100.00,0.00,100.00,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(8,8,61,500.00,0.00,0.00,500.00,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(9,9,72,1000.00,100.00,0.00,1100.00,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(10,10,81,1000.00,100.00,0.00,1100.00,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(11,10,83,500.00,100.00,0.00,600.00,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(12,11,114,1000.00,100.00,0.00,1100.00,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(13,12,124,1000.00,100.00,0.00,1100.00,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(14,13,139,1000.00,100.00,0.00,1100.00,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(15,14,149,1000.00,100.00,0.00,1100.00,'2026-05-31 18:23:37','2026-05-31 18:23:37');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,2,2,1,'REC-20260506-NNARP8MG',NULL,'2026-05-06',4375.00,3125.00,1250.00,0.00,0.00,0.00,0.00,'cash',25000.00,21875.00,'valid',NULL,NULL,NULL,2,'2026-05-06 14:49:15','2026-05-06 14:49:15'),(2,1,4,1,1,'REC-20260531-W9TNRYYM',NULL,'2026-05-31',1100.00,1000.00,100.00,0.00,0.00,0.00,0.00,'cash',10000.00,9000.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(3,1,4,1,1,'REC-20260531-MIU54DBV',NULL,'2026-05-31',550.00,450.00,100.00,0.00,0.00,0.00,0.00,'cash',9000.00,8550.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(4,1,6,1,1,'REC-20260531-SVUKQB8Q',NULL,'2026-05-31',1300.00,1000.00,300.00,0.00,0.00,0.00,0.00,'cash',6000.00,5000.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(5,1,7,1,1,'REC-20260531-T1SOMCGI',NULL,'2026-05-31',1350.00,1000.00,100.00,250.00,0.00,0.00,0.00,'cash',3000.00,2000.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(6,1,8,1,1,'REC-20260531-SKP9BGF2',NULL,'2026-05-31',1100.00,1000.00,100.00,0.00,0.00,0.00,0.00,'cash',1000.00,0.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:07:02','2026-05-31 17:07:02'),(7,1,9,1,1,'REC-20260531-R0SNTDOQ',NULL,'2026-05-31',100.00,0.00,100.00,0.00,0.00,0.00,0.00,'cash',10000.00,10000.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(8,1,10,1,1,'REC-20260531-CCIVOE8G',NULL,'2026-05-31',500.00,500.00,0.00,0.00,0.00,0.00,0.00,'cash',10000.00,9500.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(9,1,11,1,1,'REC-20260531-WIGCVVZ1',NULL,'2026-05-31',1100.00,1000.00,100.00,0.00,0.00,0.00,0.00,'cash',10000.00,9000.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(10,1,12,1,1,'REC-20260531-KG9BSLOU',NULL,'2026-05-31',1700.00,1500.00,200.00,0.00,0.00,0.00,0.00,'cash',10000.00,8500.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:49:20','2026-05-31 17:49:20'),(11,1,15,1,1,'REC-20260531-PMY181EZ',NULL,'2026-05-31',1100.00,1000.00,100.00,0.00,0.00,0.00,0.00,'cash',5000.00,4000.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(12,1,17,1,1,'REC-20260531-VERVTRW7',NULL,'2026-05-31',1100.00,1000.00,100.00,0.00,0.00,0.00,0.00,'cash',5000.00,4000.00,'valid',NULL,NULL,NULL,1,'2026-05-31 17:53:26','2026-05-31 17:53:26'),(13,1,19,1,1,'REC-20260531-V4KWBPCS',NULL,'2026-05-31',2000.00,1900.00,100.00,0.00,0.00,900.00,0.00,'cash',10000.00,8100.00,'valid',NULL,NULL,NULL,1,'2026-05-31 18:23:37','2026-05-31 18:23:37'),(14,1,20,1,1,'REC-20260531-LCR9AQYU',NULL,'2026-05-31',1100.00,1000.00,100.00,0.00,0.00,0.00,500.00,'cash',10000.00,9000.00,'valid',NULL,NULL,NULL,1,'2026-05-31 18:23:37','2026-05-31 18:23:37');
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(2,'clients.view','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(3,'clients.create','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(4,'clients.update','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(5,'clients.delete','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(6,'quotes.manage','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(7,'loans.view','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(8,'loans.create','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(9,'loans.approve','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(10,'loans.update','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(11,'payments.create','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(12,'payments.cancel','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(13,'collectors.manage','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(14,'routes.manage','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(15,'expenses.manage','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(16,'cash.view','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(17,'reports.view','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(18,'documents.generate','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(19,'legal.manage','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(20,'settings.manage','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(21,'users.manage','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(22,'quotes.convert','web','2026-05-07 10:07:58','2026-05-07 10:07:58'),(23,'quotes.delete','web','2026-05-07 10:07:58','2026-05-07 10:07:58'),(24,'loans.delete','web','2026-05-31 17:52:52','2026-05-31 17:52:52'),(25,'companies.manage-plan','web','2026-06-01 20:48:56','2026-06-01 20:48:56');
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (2,'App\\Models\\User',2,'Android','23e5d83bc20f34e1a30f3eeb841f749fb3d3bd17189f17a9ebd0008b20aff2c5','[\"mobile\"]','2026-05-06 20:14:22',NULL,'2026-05-06 15:19:08','2026-05-06 20:14:22'),(3,'App\\Models\\User',2,'Local check','0326858e8e27e20c58d17d1a74377455d7414526877a907be514011fa85f6551','[\"mobile\"]',NULL,NULL,'2026-05-06 15:21:00','2026-05-06 15:21:00'),(4,'App\\Models\\User',2,'Android','d56a0c5eff8d53ce23d8a9f06c36bd7d7cc3e9784090c8afe527c6a3e0d99d1e','[\"mobile\"]','2026-05-06 22:53:08',NULL,'2026-05-06 22:53:00','2026-05-06 22:53:08'),(5,'App\\Models\\User',2,'Android','51a1556cfc2c54c7bcec186720ddd320f7e3b484317a1b3f08be6debcbbc1111','[\"mobile\"]','2026-05-06 23:03:01',NULL,'2026-05-06 23:02:54','2026-05-06 23:03:01'),(6,'App\\Models\\User',2,'Android','00cbc2e87039bbaf22d7ede9439163d160c02597139ba5d883880f8ed81fdd5c','[\"mobile\"]','2026-05-06 23:33:11',NULL,'2026-05-06 23:33:04','2026-05-06 23:33:11'),(7,'App\\Models\\User',2,'Android','e35c26ae196f4ee72a1bab3212caf6da28d1fa40311daf417b5101ffcbd3b621','[\"mobile\"]','2026-05-06 23:46:58',NULL,'2026-05-06 23:46:51','2026-05-06 23:46:58'),(8,'App\\Models\\User',2,'Android','492c02e610553d07f3843480f2a60a58b0211bb477a767db78bd7348ca576cd4','[\"mobile\"]','2026-05-07 00:05:39',NULL,'2026-05-07 00:05:32','2026-05-07 00:05:39'),(9,'App\\Models\\User',2,'Android','94ea493cf463dffe579f5aafae6c36807b3e1eff6e347b184c0aab547f8b047a','[\"mobile\"]','2026-05-07 00:23:01',NULL,'2026-05-07 00:22:42','2026-05-07 00:23:01'),(10,'App\\Models\\User',2,'Android','264e98bf92cad3b146d5ab997295ce8a3732ab36e299c3fed1956e825a5feb3e','[\"mobile\"]','2026-05-07 00:38:21',NULL,'2026-05-07 00:38:07','2026-05-07 00:38:21'),(11,'App\\Models\\User',2,'Android','92b00a47c23e641e6393f4cef43b8f83c7bf75be9ee8a754c7fe54718fe88ce0','[\"mobile\"]','2026-05-07 00:38:57',NULL,'2026-05-07 00:38:44','2026-05-07 00:38:57'),(12,'App\\Models\\User',2,'Android','e1d05f967228b6f7810505467519c65b1013a88f1472b6649ffee064c6d2da3e','[\"mobile\"]','2026-05-07 00:41:04',NULL,'2026-05-07 00:40:52','2026-05-07 00:41:04'),(13,'App\\Models\\User',2,'Android','a769510de52c6a81af18c9cc5f4ffdd742461fd5c16eea012ca507dbbc2c52fe','[\"mobile\"]','2026-05-07 00:47:21',NULL,'2026-05-07 00:45:35','2026-05-07 00:47:21'),(14,'App\\Models\\User',2,'Android','c5b42cdc73d94c9752ec292cce0766e58560214a5a8ce54bd104e2d0e27e6f1b','[\"mobile\"]','2026-05-07 00:52:17',NULL,'2026-05-07 00:51:54','2026-05-07 00:52:17'),(15,'App\\Models\\User',2,'Android','3c493803cc7e2961211cb117920a934c0d6f3ff4e745d83d07b0df10078270a6','[\"mobile\"]','2026-05-07 00:59:38',NULL,'2026-05-07 00:59:19','2026-05-07 00:59:38'),(16,'App\\Models\\User',2,'Android','1ec2d085debb8d4d1b45a849457584a638c961a69ab37dbbaac3ddda99ed7c15','[\"mobile\"]','2026-05-07 01:15:03',NULL,'2026-05-07 01:14:44','2026-05-07 01:15:03'),(17,'App\\Models\\User',2,'Android','48dddc3729ddd91daf14e9919ffd99f2fc6bceb8ce09d651b9c2233f7d36b33c','[\"mobile\"]','2026-05-07 01:42:17',NULL,'2026-05-07 01:41:55','2026-05-07 01:42:17'),(18,'App\\Models\\User',2,'Android','900411625e6aec95bc9d0a9f76be35d6625a47f41640aaeee4bf9a19f3c05aa9','[\"mobile\"]','2026-05-07 02:08:20',NULL,'2026-05-07 02:07:58','2026-05-07 02:08:20'),(19,'App\\Models\\User',2,'Android','666ad49a10f2efbff9021d78baf26840763a7fe9d85993a9f6896405d6e62544','[\"mobile\"]','2026-05-07 05:30:03',NULL,'2026-05-07 05:29:20','2026-05-07 05:30:03');
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
INSERT INTO `role_has_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(2,1),(2,2),(2,3),(2,5),(3,1),(3,2),(4,1),(4,2),(5,1),(6,1),(6,2),(7,1),(7,2),(7,3),(7,4),(7,5),(8,1),(8,2),(9,1),(9,2),(10,1),(10,2),(11,1),(11,3),(11,4),(12,1),(12,4),(13,1),(13,2),(14,1),(14,2),(15,1),(15,4),(16,1),(16,4),(17,1),(17,2),(17,4),(17,5),(18,1),(18,2),(18,3),(18,4),(18,5),(19,1),(19,5),(20,1),(21,1),(22,1),(22,2),(23,1),(24,1),(24,2);
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
INSERT INTO `roles` VALUES (1,NULL,'Administrador','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(2,NULL,'Supervisor','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(3,NULL,'Cobrador','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(4,NULL,'Caja/Contabilidad','web','2026-05-06 02:59:31','2026-05-06 02:59:31'),(5,NULL,'Legal','web','2026-05-06 02:59:31','2026-05-06 02:59:31');
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
INSERT INTO `route_clients` VALUES (1,1,1,1,'2026-05-07 03:40:42','2026-06-01 18:40:22'),(2,1,3,2,'2026-05-07 03:40:42','2026-06-01 18:40:22'),(3,1,2,3,'2026-05-07 03:40:42','2026-06-01 18:40:22');
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
INSERT INTO `routes` VALUES (1,1,1,1,'Ruta Centro y Este','Ruta demo para probar mapa, cobros y mora por cliente.','active','2026-05-07 03:40:42','2026-05-07 03:40:42');
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
INSERT INTO `sessions` VALUES ('2echGcwWihRHcgxFKUOT7mSvTMgs2Tbmx1PhDHK0',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNE5FVXJhYk1ZOHV6VWhwWmpaVVY5Uzh1eURrSEQzd3NFbGhreTV0cyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb25maWd1cmFjaW9uIjtzOjU6InJvdXRlIjtzOjE0OiJzZXR0aW5ncy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1780279448),('EzDbGc6KvomndLJfcBsFuBqF3JEx8T6K4rhU1UtQ',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiNWdFeWFHd3B2TW1IZDhqd1BRd2JnR0c3b0Z2SVY5M0JMdzZSWVB4MSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjMxOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvZGFzaGJvYXJkIjtzOjU6InJvdXRlIjtzOjk6ImRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1780280944),('oHQ3qiYtBd3Q4UEbXMyFxZX5kTuXbOJxcvv6feWL',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUmREdkV4RFZvUmY1eUM1NFZ5Wk9POGVHSnRVeFRNak9QSTh4MmREWiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wcmVzdGFtb3MvY3JlYXIiO3M6NToicm91dGUiO3M6MTI6ImxvYW5zLmNyZWF0ZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1780280738),('OtllBn1IDIgDoIJ4tfIdsYw8P49lpjdKmcUmSMmQ',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQjFpbXMxWFNlUjFUVHZDU3BXU2xYWmw3d0d4eE81aDhuTjZLY0JaUyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb25maWd1cmFjaW9uIjtzOjU6InJvdXRlIjtzOjE0OiJzZXR0aW5ncy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1780280132);
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
INSERT INTO `users` VALUES (1,1,'Administrador Demo','admin@sistemaprestamista.local','809-555-1001',NULL,'$2y$12$sRfoDXvPIDVSxE3Ymm03UOljZ66VU7TxuvyC9b3gymI2wdJ/CmC72','active',0,NULL,'2026-06-01 02:25:35','iRPQ7soBHUVTRq8yYIFWlpzH0LhTGwtGMsT6dY5BOTlDQXDVylB4owdX4cMS','2026-05-06 02:59:31','2026-06-01 18:40:22'),(2,1,'Carlos Cobrador','cobrador@sistemaprestamista.local','809-555-2001',NULL,'$2y$12$X56.xUM/5cdVA1/yRqEq0uYv5fi4zGuwizDjLnTTbh7Hob89OyjOa','active',0,NULL,'2026-05-07 05:29:20',NULL,'2026-05-06 14:49:14','2026-06-01 18:40:22'),(3,1,'Wailan — Dueño del sistema','wailandkey@gmail.com',NULL,NULL,'$2y$12$GcfikC0DOL5WMBj.YYJ0FujtgRnqFAfqhHBVGNmxEavG8kOXFi6ke','active',1,NULL,NULL,NULL,'2026-06-01 20:33:51','2026-06-01 20:33:51');
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
INSERT INTO `zones` VALUES (1,1,'Zona Centro','Zona demo para rutas urbanas.','2026-05-06 14:49:14','2026-05-06 14:49:14');
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

-- Dump completed on 2026-06-01 16:49:57
