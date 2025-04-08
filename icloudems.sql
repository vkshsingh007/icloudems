-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table icloudems.branches
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_name` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table icloudems.entry_mode
CREATE TABLE IF NOT EXISTS `entry_mode` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entry_modename` varchar(255) NOT NULL,
  `crdr` char(50) NOT NULL,
  `entrymodeno` tinyint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table icloudems.fee_category
CREATE TABLE IF NOT EXISTS `fee_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fee_category` text NOT NULL,
  `br_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_fee_category_branches` (`br_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table icloudems.fee_types
CREATE TABLE IF NOT EXISTS `fee_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fee_category` int NOT NULL DEFAULT '0',
  `f_name` varchar(255) NOT NULL,
  `collection_id` int NOT NULL DEFAULT '0',
  `br_id` int NOT NULL DEFAULT '0',
  `seq_id` int NOT NULL DEFAULT '0',
  `fee_type_ledger` varchar(255) NOT NULL,
  `fee_headtype` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_fee_types_fee_category` (`fee_category`),
  KEY `FK_fee_types_fee_collection_type` (`collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table icloudems.financial_trans
CREATE TABLE IF NOT EXISTS `financial_trans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module_id` int NOT NULL DEFAULT (0),
  `trans_id` int NOT NULL,
  `admn_no` text NOT NULL,
  `amount` decimal(20,2) NOT NULL,
  `crdr` char(50) NOT NULL,
  `trans_date` date NOT NULL,
  `acad_year` varchar(255) NOT NULL,
  `entry_mode` int NOT NULL DEFAULT (0),
  `voucher_no` int NOT NULL,
  `br_id` int NOT NULL DEFAULT (0),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table icloudems.financial__trans_details
CREATE TABLE IF NOT EXISTS `financial__trans_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `financial_trans_id` int NOT NULL,
  `module_id` int NOT NULL DEFAULT (0),
  `amount` decimal(20,2) NOT NULL,
  `head_id` int NOT NULL DEFAULT (0),
  `crdr` char(50) NOT NULL,
  `brid` int NOT NULL DEFAULT (0),
  `head_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_financial__trans_details_financial_trans` (`financial_trans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table icloudems.module
CREATE TABLE IF NOT EXISTS `module` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module_name` varchar(255) NOT NULL,
  `module_id` tinyint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table icloudems.temp_table
CREATE TABLE IF NOT EXISTS `temp_table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sr` int NOT NULL,
  `date` date NOT NULL,
  `academic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `session` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alloted_category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `voucher_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `voucher_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `roll_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `admn_no_unique_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `fee_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `faculty` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `program` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `department` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `batch` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `receipt_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `fee_head` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `due_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `concession_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `scholarship_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `reverse_concession_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `write_off_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `adjusted_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `refund_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `fund_tranCfer_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `remarks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  PRIMARY KEY (`id`,`admn_no_unique_id`) USING BTREE,
  KEY `id` (`id`),
  KEY `sr` (`sr`),
  KEY `admn_no_unique_id` (`admn_no_unique_id`),
  KEY `roll_no` (`roll_no`),
  KEY `voucher_no` (`voucher_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
