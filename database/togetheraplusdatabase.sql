-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: togetheraplus
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','moderator') DEFAULT 'moderator',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Admin2','Admin2@gmail.com','$2y$10$X8JDurYn3DVwhaZb3MHis.AfGoMif4DEt2yPWB/AKna9leiRgUCre','super_admin','2025-01-26 18:22:40'),(2,'Fardeen','fardeen1@gmail.com','$2y$10$/i5eMbsb4Malsbbpfz/KDe4XaOauzbDtz8etMoVIjKUWpbDsYbCnG','super_admin','2025-05-22 17:13:51');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `application_id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `helper_id` int NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  KEY `task_id` (`task_id`),
  KEY `helper_id` (`helper_id`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`),
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`helper_id`) REFERENCES `helpers` (`helper_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applications`
--

LOCK TABLES `applications` WRITE;
/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
INSERT INTO `applications` VALUES (1,106,23,'approved','2025-01-26 17:38:21'),(2,107,23,'approved','2025-01-26 17:50:25'),(3,108,23,'approved','2025-01-28 17:57:12'),(4,109,23,'approved','2025-01-29 06:32:30'),(5,110,23,'approved','2025-05-14 15:35:02'),(6,111,23,'approved','2025-06-15 05:24:08'),(7,112,23,'approved','2025-06-15 05:35:55'),(8,113,23,'approved','2025-06-15 08:27:59'),(16,114,24,'pending','2025-06-26 20:28:30'),(17,113,24,'approved','2025-06-26 20:28:44'),(18,115,24,'pending','2025-06-26 20:28:46'),(19,117,24,'approved','2025-06-27 18:05:31'),(20,116,24,'approved','2025-06-27 18:23:34'),(21,118,24,'approved','2025-06-28 07:42:40'),(22,119,24,'approved','2025-06-28 08:37:40'),(23,120,24,'approved','2025-06-28 10:20:38'),(24,121,24,'approved','2025-06-28 10:23:04'),(25,122,24,'approved','2025-06-28 11:52:39'),(26,123,24,'approved','2025-06-28 12:25:28'),(27,124,24,'approved','2025-06-28 12:41:58'),(28,125,24,'approved','2025-06-28 12:45:24'),(29,127,24,'approved','2025-06-29 09:07:53'),(30,128,24,'approved','2025-06-29 09:09:33'),(31,129,24,'approved','2025-06-29 09:21:40'),(32,130,24,'pending','2025-06-29 13:57:54'),(33,131,24,'approved','2025-06-30 05:38:00'),(34,132,24,'approved','2025-06-30 05:42:37'),(35,133,24,'approved','2025-06-30 06:00:52'),(36,134,24,'approved','2025-06-30 09:03:32'),(37,135,24,'approved','2025-07-02 06:51:51'),(38,136,24,'approved','2025-07-02 06:57:32');
/*!40000 ALTER TABLE `applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `sender_role` enum('user','helper') NOT NULL,
  `receiver_id` int NOT NULL,
  `receiver_role` enum('user','helper') NOT NULL,
  `message_content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `helpers`
--

DROP TABLE IF EXISTS `helpers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpers` (
  `helper_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `address` text,
  `skills` text,
  `rating` decimal(3,2) DEFAULT '0.00',
  `profile_photo` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int DEFAULT NULL,
  `status` enum('active','suspended','deactivated') DEFAULT 'active',
  PRIMARY KEY (`helper_id`),
  UNIQUE KEY `email` (`email`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `helpers_ibfk_1` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `helpers`
--

LOCK TABLES `helpers` WRITE;
/*!40000 ALTER TABLE `helpers` DISABLE KEYS */;
INSERT INTO `helpers` VALUES (1,'Helper A','helperA@gmail.com','hashA','1234567890','123 Main Street, City A','Tutoring',4.80,NULL,'2025-01-26 04:28:09','2025-06-27 17:37:26','verified',NULL,'active'),(2,'Helper B','helperB@gmail.com','hashB','9876543210','456 Elm Street, City B','Cleaning',4.50,NULL,'2025-01-26 04:28:09','2025-06-27 17:37:26','verified',NULL,'active'),(3,'Helper C','helperC@gmail.com','hashC','5556667777','789 Oak Avenue, City C','Cooking',4.70,NULL,'2025-01-26 04:28:09','2025-06-27 17:37:26','verified',NULL,'active'),(4,'Lewis Hamilton','lewis.hamilton@gmail.com','hashLH','1112223333','123 Main Street, City A','Sign Language Interpreter',4.85,NULL,'2025-01-26 05:44:01','2025-06-27 17:37:26','verified',NULL,'active'),(5,'Max Verstappen','max.verstappen@gmail.com','hashMV','4445556666','456 Elm Street, City B','Mobility Assistant',4.50,NULL,'2025-01-26 05:44:01','2025-06-27 17:37:26','verified',NULL,'active'),(6,'Sebastian Vettel','sebastian.vettel@gmail.com','hashSV','7778889999','789 Oak Avenue, City C','Caregiving',4.70,NULL,'2025-01-26 05:44:01','2025-01-26 05:44:01','pending',NULL,'active'),(7,'Charles Leclerc','charles.leclerc@gmail.com','hashCL','9998887777','123 Main Street, City A','Speech Therapist',4.90,NULL,'2025-01-26 05:44:01','2025-01-26 05:44:01','pending',NULL,'active'),(8,'Daniel Ricciardo','daniel.ricciardo@gmail.com','hashDR','8885556666','Random City D','Occupational Therapist',4.30,NULL,'2025-01-26 05:44:01','2025-01-26 05:44:01','pending',NULL,'active'),(9,'Fernando Alonso','fernando.alonso@gmail.com','hashFA','7774445555','Random City D','Physical Therapist',4.10,NULL,'2025-01-26 05:44:01','2025-01-26 05:44:01','pending',NULL,'active'),(10,'Lando Norris','lando.norris@gmail.com','hashLN','1113335555','456 Elm Street, City B','Driving Assistant',4.00,NULL,'2025-01-26 05:44:01','2025-01-26 05:44:01','pending',NULL,'active'),(11,'George Russell','george.russell@gmail.com','hashGR','2223334444','123 Main Street, City A','Medical Assistance',4.60,NULL,'2025-01-26 05:44:01','2025-01-26 05:44:01','pending',NULL,'active'),(12,'Valtteri Bottas','valtteri.bottas@gmail.com','hashVB','5556667777','789 Oak Avenue, City C','Personal Caregiver',4.40,NULL,'2025-01-26 05:44:01','2025-01-26 05:44:01','pending',NULL,'active'),(13,'Carlos Sainz','carlos.sainz@gmail.com','hashCS','123987456','789 Oak Avenue, City C','Mobility Assistant',4.50,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(14,'Sergio Perez','sergio.perez@gmail.com','hashSP','987321654','456 Elm Street, City B','Mobility Assistant',4.40,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(15,'Esteban Ocon','esteban.ocon@gmail.com','hashEO','321654987','123 Main Street, City A','Sign Language Interpreter',4.70,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(16,'Pierre Gasly','pierre.gasly@gmail.com','hashPG','654789321','456 Elm Street, City B','Sign Language Interpreter',4.60,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(17,'Kimi Raikkonen','kimi.raikkonen@gmail.com','hashKR','456123789','123 Main Street, City A','Caregiving',4.30,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(18,'Romain Grosjean','romain.grosjean@gmail.com','hashRG','789456123','789 Oak Avenue, City C','Caregiving',4.20,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(19,'Kevin Magnussen','kevin.magnussen@gmail.com','hashKM','321789654','Random City D','Occupational Therapist',4.00,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(20,'Mick Schumacher','mick.schumacher@gmail.com','hashMS','654123789','456 Elm Street, City B','Occupational Therapist',4.10,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(21,'Nico Hulkenberg','nico.hulkenberg@gmail.com','hashNH','987654321','Random City D','Speech Therapist',4.55,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(22,'Robert Kubica','robert.kubica@gmail.com','hashRK','123456789','123 Main Street, City A','Speech Therapist',4.35,NULL,'2025-01-26 05:56:02','2025-01-26 05:56:02','pending',NULL,'active'),(23,'helper two','helper2@gmail.com','123','98214981212','South Badda,Dhaka','Mobility Assistance',0.00,'uploads/6796727e61535-337050406_593499962701217_7534995612957919920_n.jpg','2025-01-26 17:35:58','2025-06-26 18:02:25','verified',1,'active'),(24,'Namare','shakib@gmail.com','$2y$10$wirjOCTBTqvqObK85dPWbuVF6nKHvT3KTyGerNgoydC4CW1IAk5dG','880214312','Mirpur','Tutoring',3.00,'uploads/profiles/helper_24_685d9d588d2936.47523333.jpg','2025-06-26 18:21:50','2025-06-29 09:47:15','verified',1,'active');
/*!40000 ALTER TABLE `helpers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hiring_decisions`
--

DROP TABLE IF EXISTS `hiring_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hiring_decisions` (
  `decision_id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `application_id` int NOT NULL,
  `selected_helper_id` int NOT NULL,
  `decision_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`decision_id`),
  KEY `task_id` (`task_id`),
  KEY `selected_helper_id` (`selected_helper_id`),
  KEY `hiring_decisions_ibfk_3` (`application_id`),
  CONSTRAINT `hiring_decisions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`),
  CONSTRAINT `hiring_decisions_ibfk_2` FOREIGN KEY (`selected_helper_id`) REFERENCES `helpers` (`helper_id`),
  CONSTRAINT `hiring_decisions_ibfk_3` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hiring_decisions`
--

LOCK TABLES `hiring_decisions` WRITE;
/*!40000 ALTER TABLE `hiring_decisions` DISABLE KEYS */;
INSERT INTO `hiring_decisions` VALUES (1,106,1,23,'approved','2025-01-26 17:38:21'),(2,107,2,23,'approved','2025-01-26 17:50:25'),(3,108,3,23,'approved','2025-01-28 17:57:12'),(4,109,4,23,'approved','2025-01-29 06:32:30'),(5,110,5,23,'approved','2025-05-14 15:35:02'),(6,111,6,23,'approved','2025-06-15 05:24:08'),(7,112,7,23,'approved','2025-06-15 05:35:55'),(8,113,8,23,'approved','2025-06-15 08:27:59'),(9,115,18,24,'approved','2025-06-26 21:34:00'),(10,114,16,24,'approved','2025-06-26 21:34:08'),(11,117,19,24,'approved','2025-06-27 18:29:10'),(12,116,20,24,'approved','2025-06-27 18:29:43'),(13,113,17,24,'approved','2025-06-27 20:10:40'),(14,118,21,24,'approved','2025-06-28 07:42:54'),(18,119,22,24,'approved','2025-06-28 08:48:07'),(19,120,23,24,'approved','2025-06-28 10:21:04'),(20,121,24,24,'approved','2025-06-28 10:23:18'),(21,122,25,24,'approved','2025-06-28 11:53:24'),(22,123,26,24,'rejected','2025-06-28 12:25:28'),(23,124,27,24,'approved','2025-06-28 12:41:58'),(24,125,28,24,'rejected','2025-06-28 12:45:24'),(25,127,29,24,'approved','2025-06-29 09:08:00'),(26,128,30,24,'approved','2025-06-29 09:09:45'),(27,129,31,24,'approved','2025-06-29 09:21:58'),(28,131,33,24,'approved','2025-06-30 05:39:26'),(29,132,34,24,'approved','2025-06-30 05:42:37'),(30,133,35,24,'approved','2025-06-30 06:01:15'),(31,134,36,24,'approved','2025-06-30 09:04:07'),(32,135,37,24,'approved','2025-07-02 06:52:35'),(33,136,38,24,'approved','2025-07-02 06:57:32');
/*!40000 ALTER TABLE `hiring_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hiring_records`
--

DROP TABLE IF EXISTS `hiring_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hiring_records` (
  `hiring_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `helper_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `user_confirmation` enum('pending','confirmed','disputed') DEFAULT 'pending',
  `helper_confirmation` enum('pending','confirmed','disputed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','in_progress','completed','disputed') DEFAULT 'pending',
  `chat_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `logged_hours` decimal(10,3) GENERATED ALWAYS AS ((timestampdiff(SECOND,`start_time`,`end_time`) / 3600)) STORED,
  PRIMARY KEY (`hiring_id`),
  KEY `user_id` (`user_id`),
  KEY `helper_id` (`helper_id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `hiring_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `hiring_records_ibfk_2` FOREIGN KEY (`helper_id`) REFERENCES `helpers` (`helper_id`),
  CONSTRAINT `hiring_records_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hiring_records`
--

LOCK TABLES `hiring_records` WRITE;
/*!40000 ALTER TABLE `hiring_records` DISABLE KEYS */;
INSERT INTO `hiring_records` (`hiring_id`, `user_id`, `helper_id`, `task_id`, `start_time`, `end_time`, `hourly_rate`, `user_confirmation`, `helper_confirmation`, `created_at`, `status`, `chat_enabled`) VALUES (1,6,1,101,'2025-01-20 10:00:00','2025-01-20 12:00:00',20.00,'confirmed','pending','2025-01-26 04:28:09','completed',0),(2,6,2,102,'2025-01-21 14:00:00','2025-01-21 17:00:00',15.00,'pending','pending','2025-01-26 04:28:09','completed',0),(3,6,3,103,'2025-01-22 09:00:00','2025-01-22 11:00:00',18.00,'pending','pending','2025-01-26 04:28:09','completed',0),(4,6,4,104,'2025-01-22 12:00:00','2025-01-22 14:00:00',20.00,'pending','pending','2025-01-26 05:47:33','completed',0),(5,6,5,105,'2025-01-23 10:00:00','2025-01-23 11:00:00',22.00,'pending','pending','2025-01-26 05:47:33','completed',0),(6,6,6,103,'2025-01-24 09:00:00','2025-01-24 10:30:00',25.00,'pending','pending','2025-01-26 05:47:33','completed',0),(7,6,13,103,'2025-01-24 12:00:00','2025-01-24 13:30:00',25.00,'pending','pending','2025-01-26 05:56:02','completed',0),(8,6,14,102,'2025-01-25 09:00:00','2025-01-25 10:30:00',18.00,'pending','pending','2025-01-26 05:56:02','completed',0),(9,6,15,101,'2025-01-25 14:00:00','2025-01-25 16:00:00',20.00,'pending','pending','2025-01-26 05:56:02','completed',0),(10,6,23,106,NULL,NULL,22.00,'pending','pending','2025-01-26 17:44:16','completed',0),(11,6,23,107,NULL,NULL,22.00,'pending','pending','2025-01-26 17:51:05','completed',0),(12,6,23,107,'2025-01-26 23:57:58',NULL,22.00,'pending','pending','2025-01-26 17:55:47','completed',0),(13,6,16,NULL,NULL,NULL,23.00,'pending','pending','2025-01-27 04:12:35','pending',0),(14,6,23,108,NULL,NULL,23.00,'pending','pending','2025-01-28 17:57:51','in_progress',0),(15,6,23,109,'2025-01-29 12:34:29','2025-01-29 12:34:36',23.00,'pending','pending','2025-01-29 06:33:21','completed',0),(16,6,23,109,NULL,NULL,23.00,'pending','pending','2025-01-29 06:35:57','completed',0),(17,6,23,110,'2025-05-14 21:36:19','2025-05-14 21:36:33',22.00,'pending','pending','2025-05-14 15:35:41','completed',0),(18,6,23,111,'2025-06-15 11:25:20','2025-06-15 11:25:27',22.00,'pending','pending','2025-06-15 05:24:52','completed',0),(19,6,23,112,'2025-06-15 11:36:40',NULL,34.00,'pending','pending','2025-06-15 05:36:18','completed',0),(20,6,23,113,'2025-06-15 14:29:11',NULL,34.00,'pending','pending','2025-06-15 08:28:54','completed',0),(21,6,24,115,'2025-06-27 03:34:00','2025-06-27 23:47:21',25.00,'confirmed','confirmed','2025-06-26 21:34:00','completed',0),(22,6,24,114,'2025-06-27 03:34:08','2025-06-27 19:31:39',32.00,'pending','confirmed','2025-06-26 21:34:08','completed',0),(23,6,24,119,'2025-06-28 14:48:14','2025-06-28 15:13:06',10.00,'pending','confirmed','2025-06-28 08:48:07','completed',0),(24,6,24,120,'2025-06-28 16:21:16','2025-06-28 16:21:36',50.00,'confirmed','confirmed','2025-06-28 10:21:04','completed',0),(25,6,24,121,'2025-06-28 16:23:22','2025-06-28 16:23:27',100.00,'confirmed','confirmed','2025-06-28 10:23:18','completed',0),(26,6,24,122,'2025-06-29 15:41:35','2025-06-29 15:41:46',12.00,'pending','confirmed','2025-06-28 11:53:24','completed',0),(27,6,24,124,NULL,NULL,24.00,'pending','pending','2025-06-28 12:42:10','in_progress',0),(28,6,24,127,'2025-06-29 15:10:07','2025-06-29 15:10:15',58.00,'confirmed','confirmed','2025-06-29 09:08:00','completed',0),(29,6,24,128,'2025-06-29 15:22:36','2025-06-29 15:22:41',66.00,'confirmed','confirmed','2025-06-29 09:09:45','completed',0),(30,6,24,129,NULL,NULL,100.00,'pending','pending','2025-06-29 09:21:58','in_progress',0),(31,6,24,131,'2025-06-30 11:40:39','2025-06-30 11:40:41',85.00,'confirmed','confirmed','2025-06-30 05:39:26','completed',0),(32,6,24,132,'2025-06-30 11:43:45','2025-06-30 11:43:47',10.00,'pending','confirmed','2025-06-30 05:43:40','completed',0),(33,6,24,133,'2025-06-30 12:01:49','2025-06-30 12:02:01',50.00,'confirmed','confirmed','2025-06-30 06:01:15','completed',0),(34,6,24,134,'2025-06-30 15:04:19','2025-06-30 15:04:33',100.00,'confirmed','confirmed','2025-06-30 09:04:07','completed',0),(35,6,24,135,'2025-07-02 12:52:51','2025-07-02 12:53:23',25.00,'confirmed','confirmed','2025-07-02 06:52:35','completed',0),(36,6,24,136,NULL,NULL,12.00,'pending','pending','2025-07-10 17:55:08','in_progress',0);
/*!40000 ALTER TABLE `hiring_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,3,1,669.00),(2,2,3,1,669.00),(3,3,2,1,889.00),(4,4,3,1,669.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_name` varchar(255) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_postal_code` varchar(20) NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `order_status` enum('pending','paid','failed','shipped','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,6,669.00,'Zayan','Gulshan 1 ,Dhaka','Dhaka','1206','11111111','paid','2025-06-27 14:37:25'),(2,6,669.00,'Zayan','Gulshan 1 ,Dhaka','dhaka','1223','11111111','paid','2025-06-29 09:46:08'),(3,6,889.00,'Zayan','Gulshan 1 ,Dhaka','sda','asdas','11111111','paid','2025-06-30 05:37:03'),(4,6,669.00,'Zayan','Gulshan 1 ,Dhaka','wqsd','asda','11111111','paid','2025-06-30 06:07:03');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_password_resets_helper` (`email`),
  CONSTRAINT `fk_password_resets_admin` FOREIGN KEY (`email`) REFERENCES `admins` (`email`) ON DELETE CASCADE,
  CONSTRAINT `fk_password_resets_helper` FOREIGN KEY (`email`) REFERENCES `helpers` (`email`) ON DELETE CASCADE,
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `hiring_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','mobile_payment','paypal') NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `user_id` (`user_id`),
  KEY `hiring_id` (`hiring_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`hiring_id`) REFERENCES `hiring_records` (`hiring_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,1,40.00,'credit_card','completed','2025-01-26 04:28:09'),(2,1,2,45.00,'paypal','completed','2025-01-26 04:28:09'),(3,1,3,36.00,'mobile_payment','completed','2025-01-26 04:28:09'),(4,1,10,0.00,'credit_card','completed','2025-01-26 17:44:45'),(5,1,11,0.00,'credit_card','completed','2025-01-26 17:57:25'),(6,1,12,0.00,'credit_card','completed','2025-01-26 17:58:11'),(7,1,16,0.00,'credit_card','completed','2025-01-29 06:36:31'),(8,6,17,0.00,'credit_card','completed','2025-05-14 15:36:40'),(9,6,18,0.00,'credit_card','completed','2025-06-15 05:25:47'),(10,6,15,0.00,'credit_card','completed','2025-06-15 05:27:48'),(11,6,19,0.00,'credit_card','completed','2025-06-15 05:36:58'),(12,6,20,0.00,'credit_card','completed','2025-06-15 08:29:24'),(13,6,23,0.00,'credit_card','pending','2025-06-28 09:13:06'),(14,6,24,0.30,'credit_card','pending','2025-06-28 10:21:36'),(15,6,25,0.10,'credit_card','pending','2025-06-28 10:23:27'),(16,6,25,0.10,'mobile_payment','completed','2025-06-28 10:41:00'),(17,6,24,0.30,'credit_card','completed','2025-06-28 10:41:12'),(18,6,1,40.00,'mobile_payment','completed','2025-06-28 10:53:16'),(19,6,28,0.12,'credit_card','pending','2025-06-29 09:10:15'),(20,6,28,0.12,'credit_card','completed','2025-06-29 09:10:28'),(21,6,29,0.07,'credit_card','pending','2025-06-29 09:22:41'),(22,6,29,0.07,'credit_card','completed','2025-06-29 09:23:27'),(23,6,21,505.58,'paypal','completed','2025-06-29 09:35:42'),(24,6,26,0.04,'credit_card','pending','2025-06-29 09:41:46'),(25,6,31,0.09,'credit_card','pending','2025-06-30 05:40:41'),(26,6,31,0.09,'credit_card','completed','2025-06-30 05:41:25'),(27,6,32,0.01,'credit_card','pending','2025-06-30 05:43:47'),(28,6,33,0.15,'credit_card','pending','2025-06-30 06:02:01'),(29,6,33,0.15,'mobile_payment','completed','2025-06-30 06:02:32'),(30,6,34,0.40,'credit_card','pending','2025-06-30 09:04:33'),(31,6,34,0.40,'credit_card','completed','2025-06-30 09:04:58'),(32,6,35,0.23,'credit_card','pending','2025-07-02 06:53:23'),(33,6,35,0.23,'mobile_payment','completed','2025-07-02 06:54:49');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `category` varchar(100) DEFAULT NULL,
  `vendor` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Stander Walker Replacement 15 centimetre Wheels, for the EZ Fold-N-Go Walker and Able Life Space Saver Walker, set of 2, Black','Product details of Stander Walker Replacement 15 centimetre Wheels, for the EZ Fold-N-Go Walker and Able Life Space Saver Walker, set of 2, Black\\r\\n• Set of 2 replacement wheels for EZ Fold-N-Go and Able Life walkers\\r\\n• 15cm diameter wheels for smooth and easy movement\\r\\n• Compatible with Stander walkers for added convenience\\r\\n• Durable black wheels for long-lasting use\\r\\n• Easy to install and replace old or worn out wheels',250.00,'uploads/products/51PgdMjivTL.png',5,'mobility','MiziCo.','2025-06-22 08:09:41','2025-06-27 14:48:08'),(2,'Elbow Crutch Walking Stick Adjustable/Arm Crutch','Elbow Crutch Walking Stick Adjustable\\r\\nElbow Crutch Stick Adjustable ( silver & ash )Lower Tube: Aluminium EN AN 5086 – Length: 605mm – External diameter: 19mm – Weight: 94g – Painted with Epoxy coating – With 2 holes of 6mm for regulation of height – Equipped with “stopper” at the high end for noise reduction Higher Tube: Aluminium EN AN 5086 – Length: 765mm – External diameter: 22mm – Weight: 134g – Painted with Epoxy coating – With 7 holes of 6mm for regulation of height – Equipped with plastic ring at the low end for noise reduction Forearm Support: Made of polypropylene – Available in 7 different colors – Cuff opening: 94mm – Height between grip and top of support can be adjusted I 4 positions – Handle can be folded back in vertical position – Attached with 70mm galvanized screw to aluminium tube Base (Ferrule): Flexible design for maximum contact and adherence – Equipped inside with an anti-perforation steel ring – Replaceable',889.00,'uploads/products/6db710429c3bed825f62f901c943c111.jpg_720x720q80.jpg',6,'mobility','Ishmam.co','2025-06-22 08:13:20','2025-06-30 05:37:03'),(3,'Hand walking Stick China - Stick','Our organization is an eminent name, engaged in trading and supplying a broad array of walking Sticks to our clients. Gives pressurized support for weak & old persons, this stick is designed under the supervision of vendors’ skilled professionals in compliance with set industry norms. To ensure the flawless range to be delivered to our esteemed clients, this stick is strictly checked on several parameter',669.00,'uploads/products/7bf38bfc3dd88da888c208369a779476.jpg_960x960q80.jpg_.webp',1,'mobility','Sadman.co','2025-06-22 08:15:48','2025-06-30 06:07:03'),(4,'WheelChair','WheelChair',30000.00,'uploads/products/809ASV01.png',4,'mobility','namare.co','2025-06-22 08:39:16','2025-06-27 14:48:08');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resources` (
  `resource_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` enum('audio','video','tutorial') NOT NULL,
  `description` text,
  `link` text,
  `uploaded_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resources`
--

LOCK TABLES `resources` WRITE;
/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
INSERT INTO `resources` VALUES (1,'Sign Language Tutorial','tutorial','testing upload','uploads/resources/WhatsApp Video 2025-05-22 at 23.05.58_c4519ab7.mp4',6,'2025-01-23 23:15:21','2025-06-27 11:28:21',NULL),(2,'Sign Language Differences ','tutorial','testing video feature ','uploads/resources/682f5c60da855_WhatsApp Video 2025-05-22 at 23.06.17_bcfc1ec3.mp4',6,'2025-01-23 23:16:49','2025-06-27 11:30:32',NULL),(3,'Sign Language: How are you?','video','to show mizi','uploads/resources/682f9376a437b_WhatsApp Video 2025-05-22 at 23.06.32_40364811.mp4',6,'2025-01-23 23:17:22','2025-06-27 11:30:32',NULL),(4,'UndertheGuns AudioBook ','audio','asdfwF','uploads/resources/682f5d6ab85ed_undertheguns_00_wittenmyer_64kb.mp3',6,'2025-01-23 23:40:06','2025-06-27 11:30:32',NULL),(5,'Sign Language Alphabet','audio','Sign Language Alphabet','uploads/resources/68622634717d0-ASL_Alphabet.jpg',1,'2025-06-30 05:52:52','2025-06-30 05:52:52',NULL);
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `helper_id` int NOT NULL,
  `hiring_id` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`review_id`),
  KEY `user_id` (`user_id`),
  KEY `helper_id` (`helper_id`),
  KEY `hiring_id` (`hiring_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`helper_id`) REFERENCES `helpers` (`helper_id`),
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`hiring_id`) REFERENCES `hiring_records` (`hiring_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,23,10,'He is a good boy','2025-01-26 17:45:00',4.80),(2,1,23,12,'Very nice','2025-01-26 17:58:58',5.00),(3,1,23,16,'He did a good job ','2025-01-29 06:36:51',4.50),(4,6,23,19,'qq,mdqe','2025-06-15 05:37:42',3.00),(5,6,23,20,'good job','2025-06-15 08:29:47',3.00),(6,6,24,25,'Excellent, but a bit late sometimes','2025-06-28 11:12:56',4.00),(7,6,24,28,'Very Bad','2025-06-29 09:47:15',2.00);
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `task_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `skill_required` text,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `urgency` varchar(6) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL,
  `status` enum('open','in_progress','completed') NOT NULL DEFAULT 'open',
  PRIMARY KEY (`task_id`),
  KEY `fk_tasks_user_id` (`user_id`),
  CONSTRAINT `fk_tasks_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (101,'Transportation Support','Assist in transporting goods','Transportation',20.00,'medium','2025-01-24 14:46:38','2025-01-24 14:46:38',NULL,'open'),(102,'General Care','Provide basic caregiving services','General Care',18.00,'low','2025-01-24 14:46:38','2025-01-24 14:46:38',NULL,'open'),(103,'Mobility Assistance','Assist with mobility for the elderly','Mobility Assistance',25.00,'high','2025-01-24 14:46:38','2025-01-24 14:46:38',NULL,'open'),(104,'Housekeeping Help','Cleaning and tidying up','Housekeeping',15.00,'low','2025-01-24 14:46:38','2025-01-24 14:46:38',NULL,'open'),(105,'Cooking Assistance','Prepare meals for an event','Cooking',22.00,'medium','2025-01-24 14:46:38','2025-01-24 14:46:38',NULL,'open'),(106,'Demo Task','Demo Task description','sign-language',22.00,'medium','2025-01-26 17:00:37','2025-06-15 03:56:11',6,'open'),(107,'Demo Task 3','Demo Task 3 Test','reading-help',22.00,'low','2025-01-26 17:48:49','2025-06-15 03:56:11',6,'open'),(108,' New Demo Task','SOmething ','reading-help',23.00,'medium','2025-01-28 17:56:45','2025-06-15 03:56:11',6,'open'),(109,'demo task 4','Soemthing ','reading-help',23.00,'medium','2025-01-29 06:31:22','2025-06-15 03:56:11',6,'open'),(110,'trial task 1','lsknkdcfkswjf','reading-help',22.00,'low','2025-05-14 15:34:26','2025-05-14 15:34:26',6,'open'),(111,'Task Testing 5','akjcnkajdkjadkj','reading-help',22.00,'low','2025-06-15 05:23:47','2025-06-15 05:23:47',6,'open'),(112,'Task 6','aljkcakjckj','reading-help',34.00,'medium','2025-06-15 05:35:37','2025-06-15 05:35:37',6,'open'),(113,'demo task 7','lhhgjhjvjhb','reading-help',34.00,'high','2025-06-15 08:27:38','2025-06-27 20:10:40',6,'in_progress'),(114,'Need Help Coding','I can\'t code at all, I keep looking everywhere but im having trouble alot.','Tech Support',32.00,'Medium','2025-06-26 15:42:15','2025-06-26 21:34:08',6,'in_progress'),(115,'Sign Language Assistance','sadasd','Tutoring',25.00,'Medium','2025-06-26 15:48:08','2025-06-27 15:16:27',6,'in_progress'),(116,'Another Test','This is another test task','Companion',35.00,'High','2025-06-27 11:18:33','2025-06-27 18:29:43',6,'in_progress'),(117,'Help I cant Code','Coding help pls','Tech Support',15.00,'High','2025-06-27 11:38:42','2025-06-27 18:29:10',6,'in_progress'),(118,'Pen Sorting','How do i sort pens?','Companion',100.00,'High','2025-06-28 07:42:21','2025-06-28 07:42:54',6,'in_progress'),(119,'Another Test','Hello','Reading Assistance',10.00,'Low','2025-06-28 08:37:19','2025-06-28 08:48:07',6,'in_progress'),(120,'New Task Test','To test Logged hours','Reading Assistance',50.00,'Low','2025-06-28 10:19:14','2025-06-28 10:21:04',6,'in_progress'),(121,'One More Trial','This is a trial test','Mobility Support',100.00,'High','2025-06-28 10:22:56','2025-06-28 10:23:18',6,'in_progress'),(122,'Direct Task Testing','This is another test','Tutoring',12.00,'high','2025-06-28 11:52:39','2025-06-28 11:53:24',6,'in_progress'),(123,'Direct Task Test 2','Test','Driving',12.00,'low','2025-06-28 12:25:28','2025-06-28 12:25:28',6,'open'),(124,'Direct Task Test 3','Task Test 3 Description','Communication',24.00,'medium','2025-06-28 12:41:58','2025-06-28 12:42:10',6,'in_progress'),(125,'Direct Task Test 4','TA','Communication',21.00,'low','2025-06-28 12:45:24','2025-06-28 12:45:24',6,'open'),(126,'Test 3','Hello','Reading Assistance',25.00,'High','2025-06-29 09:04:27','2025-06-29 09:04:27',6,'open'),(127,'Test 5','Hello','Housekeeping',58.00,'High','2025-06-29 09:07:41','2025-06-29 09:08:00',6,'in_progress'),(128,'Test 6','Test 6 desc','Tutoring',66.00,'High','2025-06-29 09:09:21','2025-06-29 09:09:45',6,'in_progress'),(129,'Test 6','Test 6 Description','Companion',100.00,'High','2025-06-29 09:21:21','2025-06-29 09:21:58',6,'in_progress'),(130,'Test Task 10','Test','Reading Assistance',25.00,'High','2025-06-29 13:57:29','2025-06-29 13:57:29',6,'open'),(131,'Test Task 7','Test Task 7 Description','Housekeeping',85.00,'High','2025-06-30 05:37:33','2025-06-30 05:39:26',6,'in_progress'),(132,'Direct Task 9','Direct Task 9','Driving',10.00,'high','2025-06-30 05:42:37','2025-06-30 05:43:40',6,'in_progress'),(133,'Test Task 20','Test Task 20','Reading Assistance',50.00,'Low','2025-06-30 06:00:22','2025-06-30 06:01:15',6,'in_progress'),(134,'Test 24','Test24','Housekeeping',100.00,'High','2025-06-30 09:03:00','2025-06-30 09:04:07',6,'in_progress'),(135,'Test 45','Test 45','Mobility Support',25.00,'Medium','2025-07-02 06:51:32','2025-07-02 06:52:35',6,'in_progress'),(136,'Direct Task Test 10','Drive me please','Driving',12.00,'low','2025-07-02 06:57:32','2025-07-10 17:55:08',6,'in_progress');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trusted_contacts`
--

DROP TABLE IF EXISTS `trusted_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trusted_contacts` (
  `contact_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`contact_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `trusted_contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trusted_contacts`
--

LOCK TABLES `trusted_contacts` WRITE;
/*!40000 ALTER TABLE `trusted_contacts` DISABLE KEYS */;
INSERT INTO `trusted_contacts` VALUES (4,6,'Donald Trump','99992338','Father','2025-01-24 00:15:01'),(5,6,'Barak Obama','678675567','Uncle ','2025-01-24 06:35:06'),(8,7,'Riyad Naimur','1241324','Brother','2025-01-26 03:55:49'),(16,1,'Christiano Ronaldo','874192898642','Brother','2025-02-04 12:39:59'),(17,6,'Angkon','+8801712521522','Brother','2025-06-29 14:09:45');
/*!40000 ALTER TABLE `trusted_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `profile_photo` text,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `address` text,
  `user_type` enum('disabled_individual','family_member','caretaker') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int DEFAULT NULL,
  `status` enum('active','suspended','deactivated') DEFAULT 'active',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'user test sr','user@gmail.com','uploads/337441957_746731343749335_3219632483723599867_n.jpg','$2y$10$XaAwDAji3jv46uO6K5AJwOQGRLXxubk5IlU13XrVGFlGGc9RwTwYC','016737173727','Banani,Dhaka','disabled_individual','2025-01-26 04:22:06','2025-06-15 03:37:23','verified',1,'active'),(2,'user two','user2@gmail.com',NULL,'$2y$10$IrbTLRnxLGQ1MfMtYwPZ0Ofij7.6Vn13tncocfWSgiz8L4vdypje6','192326987241','Gulshan 1 ,Dhaka','disabled_individual','2025-01-29 06:29:04','2025-01-29 06:29:04','pending',NULL,'active'),(3,'User Three','user3@gmail.com',NULL,'$2y$10$tQj6mBcWDEzen4J2JNiDquMKCrtF2Z5k73xBN0Dd6KQE3fVw2PLPi','9812779184','Banani,Dhaka','disabled_individual','2025-02-04 12:27:44','2025-02-04 12:27:44','pending',NULL,'active'),(4,'Golam Bari Fardeen','usertest4@gmail.com',NULL,'$2y$10$hnHgKXl2FneCPMnh1cDlB.FU7XOW2e2zMW9mArsOokkmfhLKClN2G','81246848763','Mohammadpur,Dhaka','disabled_individual','2025-02-07 12:57:37','2025-02-07 12:57:37','pending',NULL,'active'),(5,'Nafees Masud','nafees@gmail.com',NULL,'$2y$10$2SytXqGVAxpgZiM.zPIG3uLrZuHMCeNG0382qZYli7UKoUVU9TIz.','9837498734','NGulshan','disabled_individual','2025-02-22 11:19:49','2025-02-22 11:19:49','pending',NULL,'active'),(6,'Zayan','zayan1@gmail.com','uploads/profiles/user_6_685da0f0bf7229.98650661.jpg','$2y$10$HWZG3k4LSWJpnoH7T5BOi.U.2JBGBZPTjCFTitx6BM0w9JRJqAm/O','11111111','Gulshan 1 ,Dhaka','disabled_individual','2025-05-14 15:23:57','2025-06-26 19:35:12','verified',1,'active'),(7,'nam','nam@gmail.com',NULL,'$2y$10$SKGYamXgD5gaEChQl/grNe5f3nG4W6KUDBxqKaby.lkh5KJTOseUK','231513','aweawe','disabled_individual','2025-06-26 11:33:07','2025-06-26 11:33:07','pending',NULL,'active'),(8,'namare','tester@gmail.com',NULL,'$2y$10$HWZG3k4LSWJpnoH7T5BOi.U.2JBGBZPTjCFTitx6BM0w9JRJqAm/O','12234','aadwad','disabled_individual','2025-06-26 17:58:11','2025-06-26 17:58:11','pending',NULL,'active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-04  0:05:18
