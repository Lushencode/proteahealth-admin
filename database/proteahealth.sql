-- MySQL dump 10.13  Distrib 8.4.11, for macos15 (arm64)
--
-- Host: localhost    Database: proteahealth
-- ------------------------------------------------------
-- Server version	8.4.11

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Admins`
--

DROP TABLE IF EXISTS `Admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Admins`
--

LOCK TABLES `Admins` WRITE;
/*!40000 ALTER TABLE `Admins` DISABLE KEYS */;
INSERT INTO `Admins` VALUES (1,'Lushen','Govender','$2y$12$nEpoSyK.k2E98af7j7HLpuckiyX26qMrgLLAOBPKJgUz8wrtzPwzO','lushen@proteahealth.com'),(2,'Nianca','Chetty','$2y$12$hF...wwWNt2PPh2NkNil8OtoqlzROowEzfgfdbRPSkOFGi8Vi1EE.','nianca@proteahealth.com'),(3,'Vusiwe','Bengu','$2y$12$CFgQRuAhU//JijF46u5Zc.M73LpNUdMLHb5oayE4cyjmKCgGVrgIG','vusiwe@proteahealth.com'),(4,'Mahek','Lala','$2y$12$c6stcNKgdqo2aOrjsYolxOi/LEOLaE2dgIze0ZB5E5jngl6D4Bley','mahek@proteahealth.com'),(5,'Wengal','Weldekidan','$2y$12$RcpO5iUBOzYKsoFgKNMI4ugwRN65Bq6TnJkzI8Q93xWv2Qe7jlT.O','wengal@proteahealth.com');
/*!40000 ALTER TABLE `Admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointment_reminder_log`
--

DROP TABLE IF EXISTS `appointment_reminder_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_reminder_log` (
  `appointment_id` int NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  CONSTRAINT `appointment_reminder_log_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `Appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_reminder_log`
--

LOCK TABLES `appointment_reminder_log` WRITE;
/*!40000 ALTER TABLE `appointment_reminder_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `appointment_reminder_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Appointments`
--

DROP TABLE IF EXISTS `Appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_type` varchar(100) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` varchar(500) NOT NULL,
  `accepted` tinyint(1) DEFAULT NULL,
  `status` enum('pending','confirmed','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `doctor_response` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_appointment_patient` (`patient_id`),
  KEY `fk_appointment_doctor` (`doctor_id`),
  CONSTRAINT `fk_appointment_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `Doctor` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_patient` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Appointments`
--

LOCK TABLES `Appointments` WRITE;
/*!40000 ALTER TABLE `Appointments` DISABLE KEYS */;
INSERT INTO `Appointments` VALUES (1,6,6,'Clinic visit','2026-10-10','10:00:00','ss',0,'rejected','Appointment declined by doctor','2026-10-06 23:52:57','2026-10-12 14:39:31'),(2,6,6,'Clinic visit','2026-10-10','10:30:00','4643',0,'rejected','Appointment declined by doctor','2026-10-06 23:57:37','2026-10-12 14:39:34'),(3,6,6,'Doctor consultation','2026-10-12','12:00:00','sleep',NULL,'pending',NULL,'2026-10-12 14:12:36','2026-10-12 14:12:36'),(4,6,6,'Doctor consultation','2026-10-12','11:30:00','tt',NULL,'pending',NULL,'2026-10-12 14:43:16','2026-10-12 14:43:16'),(5,8,6,'Clinic visit','2026-10-12','14:30:00','542',1,'confirmed','Appointment confirmed by doctor','2026-10-12 15:36:45','2026-10-12 15:37:44'),(6,9,6,'Doctor consultation','2026-10-12','13:30:00','defending',1,'confirmed','Appointment confirmed by doctor','2026-10-12 15:51:04','2026-10-12 15:52:20'),(7,9,6,'Doctor consultation','2026-10-13','11:30:00','PASSING',0,'rejected','Appointment declined by doctor','2026-10-12 16:03:31','2026-10-13 13:26:01'),(8,6,6,'Follow-up appointment','2026-10-14','09:30:00','123',1,'confirmed','Appointment confirmed by doctor','2026-10-13 11:31:35','2026-10-13 13:25:57'),(9,6,6,'Doctor consultation','2026-10-14','11:00:00','hello',NULL,'pending',NULL,'2026-10-14 01:28:13','2026-10-14 01:28:13'),(10,10,6,'Doctor consultation','2026-10-14','12:30:00','I cant pass',1,'confirmed','Appointment confirmed by doctor','2026-10-14 01:42:11','2026-10-14 01:42:50'),(11,9,6,'Clinic visit','2026-10-15','13:00:00','defending',NULL,'pending',NULL,'2026-10-15 16:38:32','2026-10-15 16:38:32');
/*!40000 ALTER TABLE `Appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AuthTokens`
--

DROP TABLE IF EXISTS `AuthTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AuthTokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role` enum('patient','doctor','pharmacist') NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_auth_user` (`user_id`,`role`),
  KEY `idx_auth_expiry` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=181 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AuthTokens`
--

LOCK TABLES `AuthTokens` WRITE;
/*!40000 ALTER TABLE `AuthTokens` DISABLE KEYS */;
INSERT INTO `AuthTokens` VALUES (1,6,'patient','2ddf56d328c71cd87906cab208d11dfa4f06cea636dd99e3064756fb8d9152c5','2026-10-09 14:28:50','2026-10-08 14:28:50'),(2,4,'pharmacist','f8d67b9589a564b7b465c94831e0a432ad11aad78eb933e882adce9333b96978','2026-10-09 15:26:38','2026-10-08 15:26:38'),(3,4,'pharmacist','ff18695c2d75989ec821d46e7f9aca3229c77a4e38b1acb2c28d154cad8c5953','2026-10-09 15:29:09','2026-10-08 15:29:09'),(4,4,'pharmacist','034d8ec21325912db674bef5a029471f4c9db5036a29f6fbff5e1cceeaf6873a','2026-10-09 15:30:21','2026-10-08 15:30:21'),(5,4,'pharmacist','f0a69e4b9055333003d0b2d6ededb7a2760817b7d5a7f065998f727c7e17ba31','2026-10-09 15:35:02','2026-10-08 15:35:02'),(6,6,'patient','75e8238bb81a58cb8cb4f679aa4bcef2a33d24872dea5c9560d282d80af029d9','2026-10-09 15:45:32','2026-10-08 15:45:32'),(7,4,'pharmacist','c455796b09edb9dbe2cb439798cdebc726c99f114f156712892a065d6bd4fcaf','2026-10-09 15:46:13','2026-10-08 15:46:13'),(8,6,'doctor','db1e26e624390af020ac17fd169d39bb4060d2bf44272e48e15507c49b3bd5c1','2026-10-09 15:49:51','2026-10-08 15:49:51'),(9,6,'doctor','08127ef571f4eafed318b6706cd3211cd98298fc8563b8e409070b26845961e6','2026-10-09 15:58:36','2026-10-08 15:58:36'),(10,6,'patient','01de69057b88ab7e3457b307b5c83b114982a38a5c03aaa4e0af9e8c007f6717','2026-10-09 16:02:02','2026-10-08 16:02:02'),(11,6,'patient','88d4a798e7cb4ae5a293967aa1479235aff03e36914756d4a6ae9ef4ea19df3e','2026-10-09 16:04:14','2026-10-08 16:04:14'),(12,4,'pharmacist','286e4edf3318a42a2f178178984a5381020628f03498fb1289b58a631a4f21da','2026-10-09 16:15:53','2026-10-08 16:15:53'),(13,4,'pharmacist','c62a4e5cafb721b2d6d51846f18c465abcda046e53852d2e287ccc9e86633913','2026-10-09 16:19:35','2026-10-08 16:19:35'),(14,4,'pharmacist','ef18103425f68581a3773252ff1c5c09fd223156e0751520508dd2a796264d1f','2026-10-09 16:35:40','2026-10-08 16:35:40'),(15,4,'pharmacist','c0e45f2ff0fe92d26e94d3c44f5284a8b401c390f0dbe91995cbb6ddb472a32d','2026-10-09 16:41:56','2026-10-08 16:41:56'),(16,4,'pharmacist','2f6943594e2c8f4d57eb4289b29eec5a85fe2d34837030a7790a9e3073be183e','2026-10-09 16:48:57','2026-10-08 16:48:57'),(17,4,'pharmacist','c8853e048c0e2bb4a77be5dcb2a05b6943f0d16424eb257eaf2758345f815227','2026-10-09 16:54:34','2026-10-08 16:54:34'),(18,4,'pharmacist','ff7ada0642f39e6e257a15d2d17b421105cf3eea83f2c8f229e7854e9a0c506c','2026-10-09 17:00:36','2026-10-08 17:00:36'),(19,4,'pharmacist','ae6a4851a6847d1e9f26f06a9cfc533d551310fd368346aac4809926e1f8c3c9','2026-10-09 17:11:33','2026-10-08 17:11:33'),(20,4,'pharmacist','efbcacf5e7aea2ac2fd02de812333181c2633e751e38d33a0ae949d7cb9de851','2026-10-09 17:15:08','2026-10-08 17:15:08'),(21,4,'pharmacist','b59296e2873196ab377c738ab382b93e318e4868f40646702e9a0c94b4db40a6','2026-10-09 17:22:18','2026-10-08 17:22:18'),(22,4,'pharmacist','f00dff0764216c08aad493cbe34424b7bc5dfe728fb85bc46abb2ca015bb1bbb','2026-10-09 17:22:48','2026-10-08 17:22:48'),(23,4,'pharmacist','2f6898b807a6fecfa1d8681753fd7038f889ec8184723f57c75e930ad7204e25','2026-10-09 17:26:24','2026-10-08 17:26:24'),(24,4,'pharmacist','c0f1a0213eaec79d19f1b0a53b588e316e67acfa043a8fe947de6305187c9962','2026-10-09 17:29:46','2026-10-08 17:29:46'),(25,6,'patient','3a34c90120dbe90adb9999970dd97a6866443dcb826577c9303adb5942685968','2026-10-09 17:36:05','2026-10-08 17:36:05'),(26,4,'pharmacist','8ded316f9cda7aac276b820168684e89de337a3f83f6767f90c8dcddbcd3625b','2026-10-09 17:39:02','2026-10-08 17:39:02'),(27,6,'patient','01039d8045d99a6a7fd124deb8bdb021b896d890c250d908c42990a2ac26d0c2','2026-10-09 17:39:47','2026-10-08 17:39:47'),(28,6,'patient','0a985e3378faf90519a53db963617ce2468d3b04a0fd90266cae0e187f6363ca','2026-10-09 17:52:24','2026-10-08 17:52:24'),(29,4,'pharmacist','55e4a91f82b766f69c46fa07060a337771e5d00fbbc0c735d642d0f7be3c249d','2026-10-09 17:52:53','2026-10-08 17:52:53'),(30,4,'pharmacist','8edcea06563426973e557db101300ff91760bcfbeba8f024c7edcaaf49033dbc','2026-10-09 17:56:26','2026-10-08 17:56:26'),(31,6,'patient','40aebf41e81b24891f534702ffcf68076280c158be12b346a138028d9629d0f0','2026-10-09 18:13:36','2026-10-08 18:13:36'),(32,6,'patient','6f1bddf820507aec3a5d84560c71ac43a07e3bad8d810a2a2dcaef683c765145','2026-10-09 18:33:28','2026-10-08 18:33:28'),(33,4,'pharmacist','b2822a781a0f22a26396043b892184923423b7ecc6e4e9bebd3ef2513da96c42','2026-10-09 18:40:42','2026-10-08 18:40:42'),(34,4,'pharmacist','ac0d6bea911e505b58f4fad6a9e3f5b84756a0e24fa42168a3d5da779cf4f280','2026-10-09 18:43:29','2026-10-08 18:43:29'),(35,6,'patient','d73cfadfe49943471479fbc60bbfc85923b71a61feb881b486e804761df3c541','2026-10-09 18:43:59','2026-10-08 18:43:59'),(36,4,'pharmacist','3b16d17af55474bdc9a930c084b211a04dbf20d02030a7f3211a670df4dbe9cb','2026-10-09 19:06:20','2026-10-08 19:06:20'),(37,4,'pharmacist','c8482452e73610afd773821f39ee34659a92af71d68aaf458ca522eb92c72b47','2026-10-09 19:15:37','2026-10-08 19:15:37'),(38,4,'pharmacist','f9cde39967f4995434cdd098fe66921828a96867cc1889955c735f7ae95ede53','2026-10-09 19:22:42','2026-10-08 19:22:42'),(39,4,'pharmacist','286f6b61ed2de4b1f7a6b03d5d5bdf183d78813b29aca4db10bc022f1a4617b9','2026-10-09 21:31:18','2026-10-08 21:31:18'),(40,6,'patient','78b3461e5f5e616d79ec6154fdea22bad6fceb6be09f65ce6e0e4565481a3aa8','2026-10-09 21:37:23','2026-10-08 21:37:23'),(41,4,'pharmacist','2955d654cec32e96673e8580b82005b834ea8a39326ca0d5f7404c7d48961fb2','2026-10-09 21:39:27','2026-10-08 21:39:27'),(42,6,'patient','1017476faec6a28da31a3b5bf21f1f225adfafce99c4fd74a696cad7db410d84','2026-10-09 21:53:49','2026-10-08 21:53:49'),(43,4,'pharmacist','2a2e3d1a6d97cdaba5904d6f6b2b1b068806f3cd59acddcb42ad4eea94c204c8','2026-10-09 22:14:30','2026-10-08 22:14:30'),(44,4,'pharmacist','00804939ee30dde39d069b60b844df67704c8101dc520e3a23b68b37ab0e9587','2026-10-09 22:26:27','2026-10-08 22:26:27'),(45,4,'pharmacist','b719ea6a7b59799f1a76e452b09ccee8e08c41db868a68dfd3cac929ffec4666','2026-10-09 22:29:32','2026-10-08 22:29:32'),(46,4,'pharmacist','19de0d7711e2d2c79b3e456b455cb715d06a2a661159ed2ac1d9622614e57130','2026-10-09 23:00:31','2026-10-08 23:00:31'),(47,4,'pharmacist','40afc9970c53f30ce2c6fb6f17e66d63047fd3bb63a85a99d40b082a6ef54237','2026-10-09 23:09:38','2026-10-08 23:09:38'),(48,4,'pharmacist','2422f9a9ef8125009d69cc04080f6eae9e1f110f7e5675ce30cbdb8efd70d9e1','2026-10-09 23:19:12','2026-10-08 23:19:12'),(49,4,'pharmacist','f4a87accd9bec4eb93641a93092ff2fe2955ae7862a04346aade0249387a071e','2026-10-09 23:22:28','2026-10-08 23:22:28'),(50,4,'pharmacist','cb21bfcb005bb9fef08fb5eeece82b7f642eda0b6010c641371f7e5d08458094','2026-10-09 23:31:39','2026-10-08 23:31:39'),(51,4,'pharmacist','068f9e4bf96a2b34d55ecbe75b6ed73c166daf213ed2ebc759ab53f9361daab9','2026-10-09 23:38:47','2026-10-08 23:38:47'),(52,4,'pharmacist','8a2b2537d0578a2327f994094e9e7812c325abcae038cd639ffcffe3ccbe22cb','2026-10-10 00:07:15','2026-10-09 00:07:15'),(53,4,'pharmacist','11932bb5f9bdb8bea1b9313062094f806f386aaec1458ccc439d80dc86d97a18','2026-10-10 00:17:25','2026-10-09 00:17:25'),(54,4,'pharmacist','a5159e3d16e4b909624cd6dcc31a7c6fe9d05751072bc916f8f2df9048d5d2a5','2026-10-10 00:26:17','2026-10-09 00:26:17'),(55,4,'pharmacist','a071608ded04f8621f9fdb721ec57f659d4c2d453ef2d791f538cb786ca14abe','2026-10-10 00:34:23','2026-10-09 00:34:23'),(56,4,'pharmacist','8e9cdbecd688524a385f96df9c1c5110bcf3851e506484cc02bf3180b75ab1b0','2026-10-10 00:37:08','2026-10-09 00:37:08'),(57,4,'pharmacist','6f35e2082aa794e87bb1171f8493cc68d7e87a0ba19903d5266786d9a517f233','2026-10-10 00:38:35','2026-10-09 00:38:35'),(58,6,'patient','ea9fce33df5e6aef00d2cebefc56071141bea879ba554cab682b4f48fe70fdf9','2026-10-12 17:35:57','2026-10-11 17:35:57'),(59,4,'pharmacist','f67ced3ffbd7cac8553193866b579cfeb21683ecf6138672e417b780707fca8d','2026-10-12 17:37:19','2026-10-11 17:37:19'),(60,6,'patient','11356caef8343270ced64bb26e49c7963ffa21948875bb0b6b5a16ed586ae40f','2026-10-12 17:39:01','2026-10-11 17:39:01'),(61,6,'doctor','ac884fa904b7a6617e893814373eab478e8d20eef8485513a099e1dc6b1c1093','2026-10-12 17:52:48','2026-10-11 17:52:48'),(62,4,'pharmacist','58328daaa6741bcbbc007efcef3d508e7909cd198d9e90f9fdf02a39d72b8c6c','2026-10-12 22:10:53','2026-10-11 22:10:53'),(63,6,'doctor','18c1cde7e6152bb7a25ab7dbfaa32b1263babbfb573b044606057e524984f5e1','2026-10-12 22:11:39','2026-10-11 22:11:39'),(64,4,'pharmacist','2c4d997e71736b9a8d666b17dcc2803970ca1b92048f90f6e4108a217342ba7e','2026-10-12 22:20:22','2026-10-11 22:20:22'),(65,4,'pharmacist','11f54e2413ae1aaceeeb6f48c252b3cfe30c81be119b04e54feb1133f4d34638','2026-10-12 22:21:46','2026-10-11 22:21:46'),(66,6,'patient','26e7d13de6bc92399043878080a48e2fe010f563149492b9441339f547a9a1a2','2026-10-12 22:44:54','2026-10-11 22:44:54'),(67,4,'pharmacist','8f029083904fad6bcb0c2dd1845ab9cd80d2bf6b5aca6f9218c8b0c3c10e1d3a','2026-10-12 22:46:00','2026-10-11 22:46:00'),(68,4,'pharmacist','7ddaa013fd554d329dc3dbbe7d9a041f7c0927d52ff5694cfd5481a20ad7dfcc','2026-10-12 23:01:56','2026-10-11 23:01:56'),(69,6,'patient','26616b8426dded6d3aa3c97787455f1ef3a649d695f598ee59b6ab45516d649b','2026-10-12 23:13:30','2026-10-11 23:13:30'),(70,6,'patient','a603c5b68ec564ad1b1b029ac75a2954b771b2e2fc78430c16439e68341851d4','2026-10-12 23:16:56','2026-10-11 23:16:56'),(71,4,'pharmacist','d3be071e0ca0444fc088bd51c62462a6c57b80e1491c39a3d1f63dd20c1bfc0c','2026-10-12 23:17:21','2026-10-11 23:17:21'),(72,6,'patient','ca5336e52ed8abfbf5acb7577ebc0dbb8764fef3a416b499a52be467215d51da','2026-10-12 23:18:22','2026-10-11 23:18:22'),(73,6,'doctor','b992276a28cbeec4a994661c30d131464dad56dcad807dabdb835f9f83336a8a','2026-10-13 13:33:28','2026-10-12 13:33:28'),(74,6,'doctor','02bafb4b7b186b774cd3f83014e8e4f6271eb4e9807a31a2d60bedaa34966e16','2026-10-13 13:44:51','2026-10-12 13:44:51'),(75,6,'patient','92cee0d2787e1df7e2c3aa9081bca3df4fb9f68b73ec197ade6ff398801b81de','2026-10-13 13:46:06','2026-10-12 13:46:06'),(76,6,'doctor','63be1c11f45926e3abb6e875928573f1a3062a6980457242e26c5a5d6f0a0aea','2026-10-13 14:10:33','2026-10-12 14:10:33'),(77,6,'patient','a5e64626babbd09c55f6f8d33791c3ecb06f4f05b311802e966d027cc343dbf2','2026-10-13 14:12:13','2026-10-12 14:12:13'),(78,6,'doctor','53849dd076094ca10feab0f9072dd9370315aaff53c28563513c784998a8e4b4','2026-10-13 14:13:13','2026-10-12 14:13:13'),(79,6,'doctor','0290780464f5edaad7a3a7a29ebeca75d423b4fca6fa5a30749c06b8a9fce1d1','2026-10-13 14:17:32','2026-10-12 14:17:32'),(80,6,'doctor','8dd38b4779fd76e34bae6ef7e4ca69c0e0dcc937f368e010c4911d22f334537e','2026-10-13 14:39:22','2026-10-12 14:39:22'),(81,6,'patient','815277858d116f3b8cdced7620e58e5066903b230e69d2ee5231d5063fe313f9','2026-10-13 14:42:27','2026-10-12 14:42:27'),(82,6,'doctor','cb2eaa6adf63fbd1a7371e547cfa372bdcec4e58c73e5e510e493a63983a9eea','2026-10-13 14:43:40','2026-10-12 14:43:40'),(83,6,'doctor','819667258db67b828e6449c044338cd80ef20c22abff320ce232b71521e88951','2026-10-13 15:01:18','2026-10-12 15:01:18'),(84,6,'patient','ac775296f5574f52dacbe2bffd6a8093229255bb324d73e3d6aa835ef7a81c85','2026-10-13 15:03:11','2026-10-12 15:03:11'),(85,6,'patient','1ac406b9518f919852e69292d3134319c2e9905550ca18d2da2bc86a4b5212e2','2026-10-13 15:08:43','2026-10-12 15:08:43'),(86,6,'patient','a310a26b10215dd1edc76fc5ba19c452327c4a8e6d868e7aa7bbf85a3faabbf9','2026-10-13 15:15:10','2026-10-12 15:15:10'),(87,6,'patient','117fefe53749f67e1ea7d74df2467167222518995fe607ff2e1848037e633890','2026-10-13 15:21:51','2026-10-12 15:21:51'),(88,8,'patient','84001ddc2923b86562d648d4e4a72480337e8bc91a46aec4937f2488bf35d25a','2026-10-13 15:33:56','2026-10-12 15:33:56'),(89,8,'patient','ac3a2c199c63dc2d5c821b222bc0335089bf2f8e36486cf5065c9ee520e7214b','2026-10-13 15:35:43','2026-10-12 15:35:43'),(90,6,'doctor','48112a257b48b1d513cd9f5a463f9eaed5c7b5489fcba79e1f3ac3b91e8ac99c','2026-10-13 15:37:14','2026-10-12 15:37:14'),(91,8,'patient','e846d91edfaf0c527b2f1c7d5689bae2fad3d92a95dc8f0d45ea38b58a3d5db2','2026-10-13 15:39:06','2026-10-12 15:39:06'),(92,4,'pharmacist','91988cd8b6c615ac712da0d28749ce4a30439a7733faea7b90f04167d8902b23','2026-10-13 15:40:15','2026-10-12 15:40:15'),(93,8,'patient','48fd95912c84b2bdb23acaa9347e9ebb2c2227aae6327b7f2ccd254f294884e7','2026-10-13 15:42:02','2026-10-12 15:42:02'),(94,9,'patient','f1f1246e5f5e7f58bd7f3a6fa81921efe95e01a9b647e6cf79c83d10fb92115c','2026-10-13 15:48:56','2026-10-12 15:48:56'),(95,6,'doctor','9dffa4e94d98a15ddc1037ac5c5a53576b4b682d08d2ae32bd61a870d44025bf','2026-10-13 15:51:55','2026-10-12 15:51:55'),(96,9,'patient','7c83df67a23d7b411a1c56f0e0e248ae7d54bd906e47adc4082f1cff1939f3bf','2026-10-13 15:54:25','2026-10-12 15:54:25'),(97,4,'pharmacist','e55287573f6e87e20d9dbb89d90eb80902a94ad8062f384ecd201f6e15e73321','2026-10-13 15:55:56','2026-10-12 15:55:56'),(98,9,'patient','f74f165b571dd7c17b94b996dd746ff97749efea3fbeb85b2a45e44c4478c7b7','2026-10-13 15:57:37','2026-10-12 15:57:37'),(99,4,'pharmacist','e15fd03102a954963e639e51573e395401c7d241c40d64caaa0f33581c00c838','2026-10-13 15:58:53','2026-10-12 15:58:53'),(100,9,'patient','3359a44a3a1fa65bd13053f3f5d17af785d19759d7ac17deada2ac65cd8ead50','2026-10-13 15:59:52','2026-10-12 15:59:52'),(101,6,'doctor','2bd81272b0da4c205f256bfb0013b7114b0fc054a4e89ecda8f04bf820a6a1a4','2026-10-13 16:02:02','2026-10-12 16:02:02'),(102,9,'patient','c2f5c5fe81b0a52375f53ea39e4b4a6a661710f44145cfcea175de70a85f680a','2026-10-13 16:03:03','2026-10-12 16:03:03'),(103,6,'patient','efb158b6759a28db9743598b14d7cdde4850bab86a4d14ce27ca544a9abf8d89','2026-10-14 11:00:46','2026-10-13 11:00:46'),(104,4,'pharmacist','979478c1e2772ef5627855c22057898cbc8331f3fb050c5bc5f98d1dfe1b8080','2026-10-14 11:02:57','2026-10-13 11:02:57'),(105,6,'patient','2ea13ea7f643ba00aabe2e9b7886614e3eaa709082c7f8fe30d8d49af270d820','2026-10-14 11:05:00','2026-10-13 11:05:00'),(106,4,'pharmacist','3f4ec3f3a1e7b6691764cf2322b0eb58a8dec55b9c1a2d96685e5927a3b7b094','2026-10-14 11:06:12','2026-10-13 11:06:12'),(107,6,'patient','88a09c14a25939984d1c83b381b1b61ec0a483c3c8ea2e9641cc19b5146fc155','2026-10-14 11:21:35','2026-10-13 11:21:35'),(108,4,'pharmacist','23a7ba4008538541f76c65e3204fad5e4a896c2bcaa69899910922c072d853e3','2026-10-14 11:22:31','2026-10-13 11:22:31'),(109,4,'pharmacist','99207fc89c94106030f5200f7105888df2273ef7a4f44a23b522d4785bcaa56d','2026-10-14 11:29:00','2026-10-13 11:29:00'),(110,6,'doctor','ba6a3fb7f8cd70b8dc82d3cfb1c02af476ec754e3befa6c1ed2dde92638e39cf','2026-10-14 11:30:19','2026-10-13 11:30:19'),(111,6,'patient','4b962d10a4186078d29a4250bd25d454108a0036d0058aec5e0ee7c411f54856','2026-10-14 11:31:04','2026-10-13 11:31:04'),(112,6,'doctor','5ab2a9e0df491ddc4cdfb5e8ea45b211ab662a8f4bf2ab03485ee2c8507bb27d','2026-10-14 11:31:59','2026-10-13 11:31:59'),(113,4,'pharmacist','8e6bc36fb67166f4336470e5de3d5d026c6c0edea11c09aaf336a813877e718c','2026-10-14 13:00:18','2026-10-13 13:00:18'),(114,4,'pharmacist','4f002ded30acd75ba1ff6ed890d1c474b89c2555d8af70e2a0c6006b246e542c','2026-10-14 13:00:52','2026-10-13 13:00:52'),(115,6,'doctor','83306d2ddf5ce89087bf30bbc0d7368bc6daa744346e64addc4232c262346ed8','2026-10-14 13:03:57','2026-10-13 13:03:57'),(116,6,'patient','225ea67eac1ee3e9c07a4d9e320135b5786604f41f2bd9c40f3c4a8c82c32b35','2026-10-14 13:04:27','2026-10-13 13:04:27'),(117,6,'doctor','f86b2e2b8c631843837b4e7ddbe217fae1b22739a32eebae6f09b2a7b19b4565','2026-10-14 13:23:46','2026-10-13 13:23:46'),(118,6,'patient','41377b2520abd42a56d5056f3b2c85b936413b7e8759d2425dbb6844c61ac1a4','2026-10-14 13:27:22','2026-10-13 13:27:22'),(119,6,'doctor','a2bfd14ffef197299be03b60bc1359d2a859c1351cbda986a879038f8b1cc0c3','2026-10-14 13:32:09','2026-10-13 13:32:09'),(120,6,'doctor','c0cb9699e363156b373f15b62647e9ad54577b3a49cc0e87ece83451b7b87858','2026-10-14 23:13:55','2026-10-13 23:13:55'),(121,6,'doctor','4c2529b3b98d6c5d57fb33c91c8e4138d2c8c3671bcfe23ae794ed8ce2f13798','2026-10-14 23:17:44','2026-10-13 23:17:44'),(122,6,'doctor','0e9b96da046f45e7e9e0c0c74317dc45d656cd0a65da3678e3329e0060812626','2026-10-14 23:27:22','2026-10-13 23:27:22'),(123,6,'patient','2b1074a591d25e12e259851ce381599c689e5e22df3bdcebddd2bf688c3d54eb','2026-10-14 23:29:19','2026-10-13 23:29:19'),(124,6,'patient','95425be8e67e541b4c630ddf321e040e0de20f94662e1892bfd805eba89fc66e','2026-10-14 23:31:12','2026-10-13 23:31:12'),(125,6,'doctor','317302f6b32b6665a5ca45fd367f7795371b0b36393da31199e38646dff64f07','2026-10-14 23:35:10','2026-10-13 23:35:10'),(126,6,'doctor','ab7594d0b2ec2907d5f936b54e7b7e9ac8ee3713c39f46152e18fcd824216eaf','2026-10-14 23:37:45','2026-10-13 23:37:45'),(127,6,'doctor','75f5cdc9bdb1d3698ac7f6e0856497579d138b7a530225b0b8ae4813716fd4b4','2026-10-14 23:57:17','2026-10-13 23:57:17'),(128,6,'doctor','7d0a30747465f5c0560bb6e66e6761becb0c34ada0a1537956d80fc09f0bfcad','2026-10-15 00:13:34','2026-10-14 00:13:34'),(129,6,'doctor','b0c3b02539162bb9065feb04cee72c835d61725870da34c0961c7d3c5d7b1fc3','2026-10-15 00:35:49','2026-10-14 00:35:49'),(130,6,'doctor','a5dba6fa4e15dff4374e14b014744ee72571ff83ccd8b58a65963410807d1c0f','2026-10-15 00:46:52','2026-10-14 00:46:52'),(131,6,'patient','f7ff8a456b82bf731e1aa0b0cb2774b4dddee10b304a61261e1d5af2d1fccdb1','2026-10-15 01:02:14','2026-10-14 01:02:14'),(132,6,'patient','d77d5ed63335ec9c356d7416d28f6058a9992edf640175da17f011ff16f27a15','2026-10-15 01:27:29','2026-10-14 01:27:29'),(133,6,'doctor','fbe6a5d33ab88a61791ccf6cd816dc118618c14856a30784445dfb0962915897','2026-10-15 01:28:36','2026-10-14 01:28:36'),(134,10,'patient','776f94e95ba1cff8620f1e017991a9470f52180f0f0ce56f0cbb0f2060f1291d','2026-10-15 01:41:30','2026-10-14 01:41:30'),(135,6,'doctor','a268ac4fe380b7e1ece4005a9acca0006d1d4d18e75f4a77553d536a4739bf60','2026-10-15 01:42:33','2026-10-14 01:42:33'),(136,13,'pharmacist','6e35784098a5d502d70112b6e1986fe51e375c663bf9696b4dd3f1c932aa314d','2026-10-15 14:40:34','2026-10-14 14:40:34'),(137,6,'patient','233b77da12fb2a98cd761f4c674a777c22dbc49cefccf13c92ccca3edbb045c1','2026-10-16 15:08:05','2026-10-15 15:08:05'),(138,6,'patient','1944080694098f13addf213067e3cf483e984a1aae64cc975a8a46924f157a42','2026-10-16 15:09:22','2026-10-15 15:09:22'),(139,6,'doctor','b34f333969d57edc6a51785a7aca415a3b2c1c5741f94a09e139ba61b3abeee6','2026-10-16 15:12:52','2026-10-15 15:12:52'),(140,6,'patient','2d988e091b6681d698ce23d694474f3a1176c7988c82d5892108727ee84fbdd7','2026-10-16 15:13:24','2026-10-15 15:13:24'),(141,12,'doctor','a989ef3874f824083d21a3f4ef8d2406b0dbd22905bb55d857431c5be9b01e11','2026-10-16 15:26:53','2026-10-15 15:26:53'),(142,12,'doctor','bdcc08992ae9d7c302e963f9d86cc0726d6ec77d02a6eadcd8a161481e994ff4','2026-10-16 15:36:00','2026-10-15 15:36:00'),(143,12,'doctor','2bf7f17f9a14a23c6fe50fbd7e500f03af5b6b81e637b150d1528a8947fcfdfb','2026-10-16 15:46:49','2026-10-15 15:46:49'),(144,12,'doctor','6683ca1a4e79b95904119946d5f9645cdec23baeab2ea4ee663f4d18f92b0055','2026-10-16 15:47:48','2026-10-15 15:47:48'),(145,6,'patient','fd855f58e57a4e834edc7ccb03717dab93da04e159c4b28bcb872315bb793894','2026-10-16 15:57:18','2026-10-15 15:57:18'),(146,9,'patient','2642925d1091de6cc92a56a5a9c84dc28e2fce56f7b329beb935452428fc51ba','2026-10-16 15:58:08','2026-10-15 15:58:08'),(147,6,'doctor','798a769e4cc054d8f62ad61151ab8006d37d1c6d56f63da0e86495b044b6cd63','2026-10-16 16:00:56','2026-10-15 16:00:56'),(148,6,'patient','3235c753864872f7ac600a1a90abd3f4d1a60293bef5b42c708da83b6e898c26','2026-10-16 16:31:54','2026-10-15 16:31:54'),(149,6,'doctor','2132eb2b29ec436772ffbb479b0c3ec914f91eaa0e427edee19d56a3d12fee85','2026-10-16 16:37:21','2026-10-15 16:37:21'),(150,9,'patient','c4a6b2871822c734d32e9f9a6fadd4b0da59027537a02e9bd5da79d2b3155099','2026-10-16 16:38:09','2026-10-15 16:38:09'),(151,6,'doctor','25e7195f34dce2d266d6e9a877922ae752d0828150e655e81d437a2ce7964c6c','2026-10-16 16:38:46','2026-10-15 16:38:46'),(152,6,'doctor','b9c469cb332790c38bddb414102b76b56085c1b3f9edd9a5a7e86a37d94abd6d','2026-10-16 16:41:34','2026-10-15 16:41:34'),(153,6,'doctor','8678bfc4f4857fe5c5e94a90e3d5d02910e43dd1dc102ce695e2d64cd839cf12','2026-10-16 16:46:10','2026-10-15 16:46:10'),(154,4,'pharmacist','5a16a4bb3850b446987b22d213bd056115d2a25a6333903b1bee7060e593ad98','2026-10-16 16:48:27','2026-10-15 16:48:27'),(155,9,'patient','c7cd91f96d574bab0781295c6c30f2939a74d200c0e53ec43c05a477022999e6','2026-10-16 16:59:01','2026-10-15 16:59:01'),(156,6,'doctor','e252f83bd8221d15345667aad9dcaa6d45e4d94e867a26998ba048585c57cd20','2026-10-16 16:59:36','2026-10-15 16:59:36'),(157,4,'pharmacist','104b447b3d96813ecba22814e77fc775397f47ffd11de1276bcdb2ca17408c3c','2026-10-16 17:00:16','2026-10-15 17:00:16'),(158,9,'patient','d9d6908cee136addd3c8c7109d4cce7e964e13fcc85e2788f0ba92814a3d785d','2026-10-16 17:04:27','2026-10-15 17:04:27'),(159,6,'doctor','30a175c87a76760f8e458e1e6ba1a0be5dbaab220c4af09fbddd427582a1528b','2026-10-16 17:07:39','2026-10-15 17:07:39'),(160,6,'patient','370fb560b47f179a702fa2046acbfbb235a55e03e6e859c47189e6d2727690be','2026-10-16 17:41:50','2026-10-15 17:41:50'),(161,6,'patient','ceb1449c6bb0b677056379d5423502ce664ef34c2e7e2b6f202a1be660c8ba3b','2026-10-16 17:45:31','2026-10-15 17:45:31'),(162,6,'patient','90964d2b79ab4b2eb31463d7e8340d3a31acf949d077e6676fdbb70d1e1ed2a0','2026-10-16 18:08:15','2026-10-15 18:08:15'),(163,4,'pharmacist','de04a51546f8eac373afd3f4e27af3c8a63455c70e1a00a16269f516f04b6e3f','2026-10-16 18:23:15','2026-10-15 18:23:15'),(164,6,'doctor','c6cc07f588751886532f0357cd211d110b6f010308b5ffe40e868cf9212e4e30','2026-10-16 18:49:20','2026-10-15 18:49:20'),(165,9,'patient','2a6d62ed988c1f03c0df9bba1104b1052ee5a8bddca01de7165e1ab3abedb373','2026-10-16 18:53:48','2026-10-15 18:53:48'),(166,6,'doctor','2f4a25a4bb853f0bac6eab41a814415931ed48b55d5c6e15a8afa63114addaf9','2026-10-16 19:17:31','2026-10-15 19:17:31'),(167,6,'doctor','32e04014a62d796124f2d7da79fa7d8f08545a7fc7c01fc409c5b44f37423ca8','2026-10-16 19:27:06','2026-10-15 19:27:06'),(168,6,'doctor','da48e25d43c16c0c267fb1badb0f94db5c8e818c79978993c95fddc913a9fdf0','2026-10-16 19:45:14','2026-10-15 19:45:14'),(169,6,'doctor','f72350238b4e5c497531fd510c2e963ba08e5b42167cc8eac1c532bc45692b83','2026-10-16 19:53:14','2026-10-15 19:53:14'),(170,6,'doctor','0d21a7216dec2298589d2258814bb794ca53a58a6c7fa07bff5f7fd6ce7c9baf','2026-10-16 20:05:52','2026-10-15 20:05:52'),(171,6,'doctor','f4fdeae210b4243cb9a8f4a5befb32784dde7b642cb8a90d055ae36ad5f516d8','2026-10-16 20:19:49','2026-10-15 20:19:49'),(172,6,'doctor','f6c9242dd424c4d215605894f4e8f332a5e04314427baa160b4b88ad34b1d6af','2026-10-16 20:23:16','2026-10-15 20:23:16'),(173,6,'doctor','7b1044f0ba87609805fd142791f16751e88399f1fb34e85fa1c7636966efd7e8','2026-10-16 20:31:32','2026-10-15 20:31:32'),(174,6,'doctor','34ec8ab0ab53f908f2e95f3ac8b5fb45f565607b94fc374574ee26096f83d911','2026-10-16 20:50:40','2026-10-15 20:50:40'),(175,6,'doctor','216a75c588568c2d2f2376de23cd65ab4a56d20d5b1670dae4dde9348accf567','2026-10-16 20:56:23','2026-10-15 20:56:23'),(176,9,'patient','0a67e40b9d9a3e887a77d0a405b35b7acce92f17bf502920f4d232e824ad5d72','2026-10-16 21:07:20','2026-10-15 21:07:20'),(177,9,'patient','2bac0a32a0ac24511e3136ef24c083a33619a9d8d4d3548cb48a954c0521a67d','2026-10-16 21:18:30','2026-10-15 21:18:30'),(178,9,'patient','7da9202650fda5d8d3c34c126e2abf916358ec5fdc181da0ffd061be2bb9a079','2026-10-16 21:22:02','2026-10-15 21:22:02'),(179,9,'patient','17513dc9e09982956568cb66319e637110cfe30692a906ef5282827b892b53ae','2026-10-16 21:37:12','2026-10-15 21:37:12'),(180,9,'patient','469fae06ca174ba9c2ddb52ebd5382e139c749232b572415e5135d751ca3e105','2026-10-16 21:38:02','2026-10-15 21:38:02');
/*!40000 ALTER TABLE `AuthTokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Doctor`
--

DROP TABLE IF EXISTS `Doctor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Doctor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `specialization` varchar(150) NOT NULL,
  `location` varchar(255) NOT NULL,
  `clinic_name` varchar(200) NOT NULL,
  `years_in_professional` int NOT NULL,
  `consultation_price` decimal(10,2) NOT NULL,
  `certification` varchar(255) NOT NULL,
  `verification` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_doctor_price` CHECK ((`consultation_price` >= 0)),
  CONSTRAINT `chk_doctor_years` CHECK ((`years_in_professional` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Doctor`
--

LOCK TABLES `Doctor` WRITE;
/*!40000 ALTER TABLE `Doctor` DISABLE KEYS */;
INSERT INTO `Doctor` VALUES (1,'Sarah','Naidoo','doctor1@proteahealth.local','1234','General Practitioner','Johannesburg','HealthCare Medical Centre',8,650.00,'MBChB, HPCSA','verified','2026-09-30 12:27:33'),(2,'Michael','Dlamini','doctor2@proteahealth.local',NULL,'Cardiologist','Pretoria','HeartCare Clinic',14,1200.00,'MBChB, FCP(SA), HPCSA','verified','2026-09-30 12:27:33'),(3,'Aisha','Khan','doctor3@proteahealth.local',NULL,'Dermatologist','Sandton','Skin Health Clinic',6,900.00,'MBChB, Dip Dermatology, HPCSA','verified','2026-09-30 12:27:33'),(4,'James','Smith','james.smith@example.com','$2y$12$VqaDefKBzp6.vNTt530znuEHuIp6DxF8xYsLTSR7zrZGoU4z9yIOq','General Practitioner','Johannesburg','Protea Health Clinic',8,450.00,'MBChB','rejected','2026-09-30 13:03:22'),(5,'tes','rrr','rgsrg@ee.com','$2y$12$kiF64Hw5FQ.dE9BG7ZNlx.mno/iHcITEsNe2KfWiekuPrWY.I9c2u','sss','rrrr','sss',422,42.00,'rrr','verified','2026-09-30 15:06:06'),(6,'bruno','fernandes','bruno@mun.com','$2y$12$mWrNBTAu0XuLHS6OuXiOLeGPSWT1piVtRDaqZLiN1jpCzeANJdvI.','passing','Manchester','Old Trafford',15,320000.00,'PFA25/26','verified','2026-10-02 05:12:16'),(7,'TestDoctor','Mokoena','testdoctor1@example.com',NULL,'General Practitioner','Johannesburg','Demo Johannesburg Clinic',5,350.00,'DEMO-HPCSA-001','rejected','2026-10-13 19:08:54'),(8,'TestDoctor','Naidoo','testdoctor2@example.com',NULL,'Cardiology','Sandton','Demo Sandton Medical Centre',10,850.00,'DEMO-HPCSA-002','pending','2026-10-13 19:08:54'),(9,'TestDoctor','Dlamini','testdoctor3@example.com',NULL,'Dermatology','Soweto','Demo Soweto Health Centre',7,600.00,'DEMO-HPCSA-003','pending','2026-10-13 19:08:54'),(10,'TestDoctor','Pillay','testdoctor4@example.com',NULL,'Paediatrics','Midrand','Demo Midrand Family Clinic',12,750.00,'DEMO-HPCSA-004','pending','2026-10-13 19:08:54'),(11,'TestDoctor','Khumalo','testdoctor5@example.com',NULL,'General Practitioner','Roodepoort','Demo West Rand Clinic',3,300.00,'DEMO-HPCSA-005','pending','2026-10-13 19:08:54'),(12,'123','123','123@123.com','$2y$12$GNou8YNIbNMf0SFq.6.6gOZk.tahC4OhVqLVVPPwA.T.Sop8.Fjty','123','123','123',123,123.00,'12312','verified','2026-10-15 15:25:40');
/*!40000 ALTER TABLE `Doctor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DoctorAvailability`
--

DROP TABLE IF EXISTS `DoctorAvailability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DoctorAvailability` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL,
  `available_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int NOT NULL DEFAULT '30',
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_availability_doctor` (`doctor_id`),
  CONSTRAINT `fk_availability_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `Doctor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DoctorAvailability`
--

LOCK TABLES `DoctorAvailability` WRITE;
/*!40000 ALTER TABLE `DoctorAvailability` DISABLE KEYS */;
INSERT INTO `DoctorAvailability` VALUES (1,6,'2026-10-10','09:00:00','13:00:00',30,1,'2026-10-06 23:20:17'),(2,6,'2026-10-12','08:30:00','16:30:00',30,1,'2026-10-12 14:11:17'),(3,6,'2026-10-13','08:30:00','16:30:00',30,1,'2026-10-12 16:02:41'),(4,6,'2026-10-14','08:00:00','16:00:00',30,1,'2026-10-13 11:30:43'),(6,6,'2026-10-15','08:30:00','16:30:00',30,1,'2026-10-15 16:37:41');
/*!40000 ALTER TABLE `DoctorAvailability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DoctorPatient`
--

DROP TABLE IF EXISTS `DoctorPatient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DoctorPatient` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doctor_patient` (`doctor_id`,`patient_id`),
  KEY `fk_doctorpatient_patient` (`patient_id`),
  CONSTRAINT `fk_doctorpatient_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `Doctor` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doctorpatient_patient` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DoctorPatient`
--

LOCK TABLES `DoctorPatient` WRITE;
/*!40000 ALTER TABLE `DoctorPatient` DISABLE KEYS */;
INSERT INTO `DoctorPatient` VALUES (1,1,1,'2026-10-14 01:15:18'),(2,2,2,'2026-10-14 01:15:18'),(3,3,3,'2026-10-14 01:15:18'),(4,6,7,'2026-10-14 01:15:18'),(5,6,8,'2026-10-14 01:15:18'),(6,6,9,'2026-10-14 01:15:18'),(8,6,6,'2026-10-14 01:28:13'),(9,6,10,'2026-10-14 01:42:50');
/*!40000 ALTER TABLE `DoctorPatient` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DoctorVerificationAudit`
--

DROP TABLE IF EXISTS `DoctorVerificationAudit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DoctorVerificationAudit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `previous_status` enum('pending','verified','rejected') NOT NULL,
  `new_status` enum('verified','rejected') NOT NULL,
  `decision_reason` text NOT NULL,
  `credential_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DoctorVerificationAudit`
--

LOCK TABLES `DoctorVerificationAudit` WRITE;
/*!40000 ALTER TABLE `DoctorVerificationAudit` DISABLE KEYS */;
INSERT INTO `DoctorVerificationAudit` VALUES (1,4,1,'pending','rejected','non credibility',NULL,'2026-10-13 18:49:19'),(2,3,1,'pending','verified','12332312213','approved','2026-10-13 18:55:00'),(3,7,1,'pending','rejected','dont know u',NULL,'2026-10-13 19:24:23');
/*!40000 ALTER TABLE `DoctorVerificationAudit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Education_Category`
--

DROP TABLE IF EXISTS `Education_Category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Education_Category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `patient_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_category_patient_name` (`patient_id`,`name`),
  KEY `idx_category_patient_id` (`patient_id`),
  CONSTRAINT `fk_category_patient` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Education_Category`
--

LOCK TABLES `Education_Category` WRITE;
/*!40000 ALTER TABLE `Education_Category` DISABLE KEYS */;
INSERT INTO `Education_Category` VALUES (1,'Heart Health',1,'2026-09-30 12:27:33'),(2,'Medication Safety',2,'2026-09-30 12:27:33'),(3,'Skin Health',3,'2026-09-30 12:27:33'),(4,'mental health',NULL,'2026-10-15 19:45:29');
/*!40000 ALTER TABLE `Education_Category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `educational_resource`
--

DROP TABLE IF EXISTS `educational_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `educational_resource` (
  `resource_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `provider_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `summary` text NOT NULL,
  `content` longtext NOT NULL,
  `health_tip` text,
  `read_time` int NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `is_reviewed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`resource_id`),
  KEY `idx_resource_category_id` (`category_id`),
  KEY `idx_resource_provider_id` (`provider_id`),
  CONSTRAINT `fk_resource_category` FOREIGN KEY (`category_id`) REFERENCES `Education_Category` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resource_doctor` FOREIGN KEY (`provider_id`) REFERENCES `Doctor` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_resource_read_time` CHECK ((`read_time` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `educational_resource`
--

LOCK TABLES `educational_resource` WRITE;
/*!40000 ALTER TABLE `educational_resource` DISABLE KEYS */;
INSERT INTO `educational_resource` VALUES (1,1,2,'Understanding Heart Health',NULL,'An introduction to maintaining cardiovascular health.','Heart health can be supported through regular physical activity, balanced nutrition, appropriate medical screening and management of risk factors.',NULL,5,'published',1,'2026-09-30 12:27:33','2026-09-30 12:27:33'),(2,2,1,'Medication Safety Basics',NULL,'Important information about taking medication safely.','Patients should follow the instructions provided by their healthcare professional and pharmacist and should not change prescribed doses without professional advice.',NULL,4,'published',1,'2026-09-30 12:27:33','2026-09-30 12:27:33'),(3,3,3,'Basic Skin Health',NULL,'General information about maintaining healthy skin.','Healthy skin is supported by appropriate cleansing, sun protection and seeking professional advice when persistent or concerning skin changes occur.',NULL,3,'draft',0,'2026-09-30 12:27:33','2026-09-30 12:27:33'),(5,1,6,'dsf','uploads/health_tips/66f8bebb62c323858e5e82dc3647555d.jpg','dfsfd','dfsfd','dfsdfs',1,'published',1,'2026-10-15 20:51:03','2026-10-15 20:51:03');
/*!40000 ALTER TABLE `educational_resource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medication_reminder_log`
--

DROP TABLE IF EXISTS `medication_reminder_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medication_reminder_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int NOT NULL,
  `reminder_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_medication_reminder` (`prescription_id`,`reminder_date`,`scheduled_time`),
  CONSTRAINT `medication_reminder_log_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `Prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medication_reminder_log`
--

LOCK TABLES `medication_reminder_log` WRITE;
/*!40000 ALTER TABLE `medication_reminder_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `medication_reminder_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `pharmacist_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,6,NULL,NULL,'Your appointment with Dr. Smith has been confirmed.','2026-10-07 07:30:00',0),(2,6,NULL,NULL,'Your prescription is ready for collection at your selected pharmacy.','2026-10-07 09:15:00',0),(3,6,NULL,NULL,'Reminder: You have a doctor appointment tomorrow at 10:00 AM.','2026-10-07 11:00:00',0),(4,6,NULL,NULL,'Your medication refill request has been received.','2026-10-06 13:45:00',1),(5,6,NULL,NULL,'Health tip: Remember to take your medication at the same time every day.','2026-10-05 06:00:00',1),(6,6,NULL,NULL,'Your medication order #15 is being prepared.','2026-10-08 17:39:14',0),(7,6,NULL,NULL,'Your medication order #15 has been dispatched for delivery.','2026-10-08 17:52:58',0),(8,6,NULL,NULL,'Your medication order #15 has been delivered.','2026-10-08 17:56:32',0),(9,6,NULL,NULL,'Your medication order #16 is being prepared.','2026-10-08 18:40:53',0),(10,6,NULL,NULL,'Your medication order #16 is ready for collection.','2026-10-08 18:40:56',0),(11,6,NULL,NULL,'Your medication order #16 has been collected.','2026-10-08 18:40:58',0),(12,6,NULL,NULL,'Your medication order #17 is being prepared.','2026-10-08 21:39:36',0),(13,6,NULL,NULL,'Your medication order #17 is ready for collection.','2026-10-08 21:39:40',0),(14,6,NULL,NULL,'Your medication order #17 has been cancelled. Please contact the pharmacy if you need assistance.','2026-10-08 21:44:07',0),(15,6,NULL,NULL,'Your medication order #21 has been cancelled. Please contact the pharmacy if you need assistance.','2026-10-08 22:14:39',0),(16,6,NULL,NULL,'Your medication order #22 is being prepared.','2026-10-08 22:14:48',0),(17,6,NULL,NULL,'Your medication order #22 is ready for collection.','2026-10-08 22:14:58',0),(18,6,NULL,NULL,'Your medication order #22 has been cancelled. Please contact the pharmacy if you need assistance.','2026-10-08 22:15:19',0),(19,6,NULL,NULL,'Your medication order #23 is being prepared.','2026-10-08 22:16:32',0),(20,6,NULL,NULL,'Your medication order #23 is ready for collection.','2026-10-08 22:16:36',0),(21,6,NULL,NULL,'Your medication order #23 has been cancelled. Please contact the pharmacy if you need assistance.','2026-10-08 22:16:39',0),(22,6,NULL,NULL,'Your medication order #24 is being prepared.','2026-10-11 17:37:39',0),(23,6,NULL,NULL,'Your medication order #24 is ready for collection.','2026-10-11 17:37:53',0),(24,6,NULL,NULL,'Your medication order #24 has been cancelled. Please contact the pharmacy if you need assistance.','2026-10-11 17:38:12',0),(25,NULL,NULL,4,'New Order #25: Amoxicillin 500mg (Quantity: 40) - collection.','2026-10-11 22:45:26',0),(26,6,NULL,NULL,'Your medication order #25 is being prepared.','2026-10-11 23:03:18',0),(27,6,NULL,NULL,'Your appointment request for 10 Oct 2026 at 10:00 has been declined.','2026-10-12 14:39:31',0),(28,6,NULL,NULL,'Your appointment request for 10 Oct 2026 at 10:30 has been declined.','2026-10-12 14:39:34',0),(29,NULL,6,NULL,'New appointment request for 12 Oct 2026 at 11:30. Please review the appointment.','2026-10-12 14:43:16',0),(30,NULL,6,NULL,'New appointment request for 12 Oct 2026 at 14:30. Please review the appointment.','2026-10-12 15:36:45',0),(31,8,NULL,NULL,'Your appointment on 12 Oct 2026 at 14:30 has been confirmed.','2026-10-12 15:37:44',0),(32,NULL,NULL,4,'New Order #26: sleep (Quantity: 80) - collection.','2026-10-12 15:39:47',0),(33,8,NULL,NULL,'Your medication order #26 is being prepared.','2026-10-12 15:40:58',0),(34,8,NULL,NULL,'Your medication order #26 is ready for collection.','2026-10-12 15:41:05',0),(35,8,NULL,NULL,'Your medication order #26 has been cancelled. Please contact the pharmacy if you need assistance.','2026-10-12 15:41:40',0),(36,NULL,6,NULL,'New appointment request for 12 Oct 2026 at 13:30. Please review the appointment.','2026-10-12 15:51:04',0),(37,9,NULL,NULL,'Your appointment on 12 Oct 2026 at 13:30 has been confirmed.','2026-10-12 15:52:20',0),(38,NULL,NULL,4,'New Order #27: pills (Quantity: 30) - collection.','2026-10-12 15:55:31',0),(39,9,NULL,NULL,'Your medication order #27 is being prepared.','2026-10-12 15:56:31',0),(40,9,NULL,NULL,'Your medication order #27 is ready for collection.','2026-10-12 15:56:33',0),(41,9,NULL,NULL,'Your medication order #27 has been cancelled. Please contact the pharmacy if you need assistance.','2026-10-12 15:57:01',0),(42,NULL,NULL,4,'New Order #28: pills (Quantity: 30) - delivery.','2026-10-12 15:58:16',0),(43,9,NULL,NULL,'Your medication order #28 is being prepared.','2026-10-12 15:59:20',0),(44,9,NULL,NULL,'Your medication order #28 has been dispatched for delivery.','2026-10-12 15:59:22',0),(45,9,NULL,NULL,'Your medication order #28 has been delivered.','2026-10-12 15:59:23',0),(46,NULL,6,NULL,'New appointment request for 13 Oct 2026 at 11:30. Please review the appointment.','2026-10-12 16:03:31',0),(47,NULL,NULL,4,'New Order #30: Paracetamol 500mg (Quantity: 30) - collection.','2026-10-13 11:05:24',0),(48,NULL,NULL,4,'New Order #31: Paracetamol 500mg (Quantity: 30) - collection.','2026-10-13 11:22:08',0),(49,NULL,6,NULL,'New appointment request for 14 Oct 2026 at 09:30. Please review the appointment.','2026-10-13 11:31:35',0),(50,6,NULL,NULL,'Your appointment on 14 Oct 2026 at 09:30 has been confirmed.','2026-10-13 13:25:57',0),(51,9,NULL,NULL,'Your appointment request for 13 Oct 2026 at 11:30 has been declined.','2026-10-13 13:26:01',0),(52,NULL,6,NULL,'New appointment request for 14 Oct 2026 at 11:00. Please review the appointment.','2026-10-14 01:28:13',0),(53,NULL,6,NULL,'New appointment request for 14 Oct 2026 at 12:30. Please review the appointment.','2026-10-14 01:42:11',0),(54,10,NULL,NULL,'Your appointment on 14 Oct 2026 at 12:30 has been confirmed.','2026-10-14 01:42:50',0),(55,NULL,6,NULL,'New appointment request for 15 Oct 2026 at 13:00. Please review the appointment.','2026-10-15 16:38:32',0);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `pharmacy_id` int NOT NULL,
  `medication_name` varchar(200) NOT NULL,
  `quantity` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fulfillment_method` enum('collection','delivery') NOT NULL DEFAULT 'collection',
  `delivery_address` varchar(500) DEFAULT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `order_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','confirmed','processing','ready_for_collection','collected','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card') NOT NULL DEFAULT 'cash',
  `prescription_id` int DEFAULT NULL,
  `pharmacy_medication_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_patient_id` (`patient_id`),
  KEY `idx_order_pharmacy_id` (`pharmacy_id`),
  CONSTRAINT `fk_order_patient` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_pharmacy` FOREIGN KEY (`pharmacy_id`) REFERENCES `Pharmacies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_order_amount` CHECK ((`amount` >= 0)),
  CONSTRAINT `chk_order_quantity` CHECK ((`quantity` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,1,'Paracetamol 500mg',20,919.80,'collection',NULL,0.00,'2026-09-30 12:27:33','pending','cash',NULL,NULL),(2,2,2,'Amoxicillin 500mg',21,1889.79,'collection',NULL,0.00,'2026-09-30 12:27:33','confirmed','cash',NULL,NULL),(3,3,3,'Cetirizine 10mg',10,655.00,'collection',NULL,0.00,'2026-09-30 12:27:33','delivered','cash',NULL,NULL),(4,6,2,'Paracetamol 500mg',30,1199.70,'collection',NULL,0.00,'2026-10-06 09:03:56','confirmed','cash',NULL,NULL),(5,6,1,'Paracetamol 500mg',30,1414.70,'delivery','123rfrf',35.00,'2026-10-06 13:52:32','confirmed','cash',NULL,NULL),(6,6,4,'Paracetamol 500mg',2,45.00,'collection',NULL,0.00,'2026-10-08 16:17:50','collected','cash',NULL,NULL),(7,6,4,'Vitamin C 500mg',2,85.00,'delivery','123 Main Road, Protea Glen',50.00,'2026-10-08 16:48:31','pending','cash',NULL,NULL),(8,4,4,'Cetirizine 10mg',1,65.00,'delivery','45 Rose Street, Soweto',50.00,'2026-10-08 16:48:31','cancelled','card',NULL,NULL),(9,1,4,'Paracetamol 500mg',3,72.00,'collection',NULL,0.00,'2026-10-08 16:48:31','cancelled','cash',NULL,NULL),(10,3,4,'Saline Nasal Spray',2,110.00,'delivery','18 Oak Avenue, Lenasia',50.00,'2026-10-08 16:48:31','shipped','card',NULL,NULL),(11,5,4,'Antacid Tablets',1,49.99,'collection',NULL,0.00,'2026-10-08 16:48:31','delivered','cash',NULL,NULL),(12,2,4,'Oral Rehydration Sachets',4,120.00,'delivery','77 Market Street, Johannesburg',50.00,'2026-10-08 16:48:31','delivered','cash',NULL,NULL),(13,7,4,'Vitamin D Tablets',1,95.00,'collection',NULL,0.00,'2026-10-08 16:48:31','cancelled','cash',NULL,NULL),(14,6,4,'Zinc Tablets',2,80.00,'delivery','123 Main Road, Protea Glen',50.00,'2026-10-08 16:48:31','delivered','card',NULL,NULL),(15,6,4,'Paracetamol 500mg',30,1489.70,'delivery','12345',50.00,'2026-10-08 17:38:23','delivered','cash',NULL,NULL),(16,6,4,'Amoxicillin 500mg',20,1759.80,'collection',NULL,0.00,'2026-10-08 18:34:26','collected','cash',5,NULL),(17,6,4,'sleep',30,600.00,'collection',NULL,0.00,'2026-10-08 21:38:43','cancelled','cash',NULL,NULL),(18,6,4,'sleep',12,240.00,'collection',NULL,0.00,'2026-10-08 22:05:20','confirmed','cash',NULL,20),(19,6,4,'sleep',12,240.00,'collection',NULL,0.00,'2026-10-08 22:05:38','confirmed','cash',NULL,20),(20,6,4,'sleep',12,240.00,'collection',NULL,0.00,'2026-10-08 22:06:46','confirmed','cash',NULL,20),(21,6,4,'sleep',12,240.00,'collection',NULL,0.00,'2026-10-08 22:08:27','cancelled','cash',NULL,20),(22,6,4,'sleep',12,240.00,'collection',NULL,0.00,'2026-10-08 22:08:27','cancelled','cash',NULL,20),(23,6,4,'sleep',12,240.00,'collection',NULL,0.00,'2026-10-08 22:09:53','cancelled','cash',NULL,20),(24,6,4,'Amoxicillin 500mg',40,3519.60,'collection',NULL,0.00,'2026-10-11 17:37:01','cancelled','cash',5,14),(25,6,4,'Amoxicillin 500mg',40,3519.60,'collection',NULL,0.00,'2026-10-11 22:45:26','processing','cash',5,14),(26,8,4,'sleep',80,1600.00,'collection',NULL,0.00,'2026-10-12 15:39:47','cancelled','cash',8,20),(27,9,4,'pills',30,600.00,'collection',NULL,0.00,'2026-10-12 15:55:31','cancelled','cash',9,42),(28,9,4,'pills',30,650.00,'delivery','123',50.00,'2026-10-12 15:58:16','delivered','cash',9,42),(29,6,1,'Paracetamol 500mg',30,750.00,'collection',NULL,0.00,'2026-10-13 11:02:22','confirmed','cash',4,22),(30,6,4,'Paracetamol 500mg',30,810.00,'collection',NULL,0.00,'2026-10-13 11:05:24','confirmed','cash',4,37),(31,6,4,'Paracetamol 500mg',30,810.00,'collection',NULL,0.00,'2026-10-13 11:22:08','confirmed','cash',4,37);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Patient`
--

DROP TABLE IF EXISTS `Patient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Patient` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `age` int NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') NOT NULL,
  `home_address` varchar(500) NOT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_number` varchar(30) DEFAULT NULL,
  `emergency_contact_relationship` varchar(100) DEFAULT NULL,
  `allergies` text,
  `medical_conditions` text,
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `doc_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_patient_doc_id` (`doc_id`),
  CONSTRAINT `fk_patient_doctor` FOREIGN KEY (`doc_id`) REFERENCES `Doctor` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_patient_age` CHECK (((`age` > 0) and (`age` <= 150)))
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Patient`
--

LOCK TABLES `Patient` WRITE;
/*!40000 ALTER TABLE `Patient` DISABLE KEYS */;
INSERT INTO `Patient` VALUES (1,'John','Mthembu',29,'john.mthembu@example.com','','0825551001','male','Soweto, Johannesburg','Mary Mthembu - 0825552001',NULL,NULL,'Penicillin',NULL,'medium',1,'2026-09-30 12:27:33'),(2,'Lerato','Molefe',41,'lerato.molefe@example.com','','0825551002','female','Pretoria, Gauteng','Thabo Molefe - 0825552002',NULL,NULL,'None known',NULL,'low',2,'2026-09-30 12:27:33'),(3,'Adam','Pillay',35,'adam.pillay@example.com','','0825551003','male','Sandton, Johannesburg','Nadia Pillay - 0825552003',NULL,NULL,'Peanuts',NULL,'high',3,'2026-09-30 12:27:33'),(4,'Test','Patient',25,'test.patient@example.com','$2y$12$FT05GQXrKm19dJY5P3fGCe0jc6z6mW/Eub1GXDVjxXOPH5cNjJXae','0821234567','other','Johannesburg',NULL,NULL,NULL,NULL,NULL,'low',NULL,'2026-09-30 13:00:26'),(5,'james','la',45,'join@why.com','$2y$12$YvNiTOTzv4In9h1hkwQvrevX9F5CJ32d4u6zMMn6GUkT4Pd/jWa.6\n','0651258963','male','grjj','james',NULL,NULL,'',NULL,'low',NULL,'2026-09-30 14:37:45'),(6,'hope','please',84,'work@please.com','$2y$12$4.FCeZd6w74acg.hVnU6aORmkh1aBTZs.qgOwfcpVvR2N2/ynloBe','0977896896666','other','123 hope street','hope','073454222','talking stage','Android Studio','No sleep','medium',NULL,'2026-10-01 21:51:37'),(7,'helo','hi',34,'de@ff.com','$2y$12$d0h5.dw3i5xpqFhz4.THFOi6BCgdn3maFlZMDV/U1H.BqATnPNVne','452545442','other','wrf','hi','56356','f','non',NULL,'low',6,'2026-10-02 00:41:19'),(8,'Marcus','Rashford',28,'rash@mun.com','$2y$12$5qvOX1Jj0ksbmG/iWxnYLe9731Aj/zlJbUdZ/hUK2oLCqfFah0NIa','55574674676','male','next to old trafford','Jesse Lingard','54254456655','teamate','scoring','goals','low',6,'2026-10-12 15:33:46'),(9,'Luke','Shaw',31,'shaw@mun.com','$2y$12$cTbwmqTre2/xgoho9poCTeS9xRYlA3xIq0nmRpNs4ZfabX1g0TtMe','4456746764','male','123 countrylane street','Harry maguire','256536675','cousin','defending','sleep','high',6,'2026-10-12 15:48:37'),(10,'Diogo','Dalot',27,'dalot@mun.com','$2y$12$FOHU4mFK.pZ8MgA9vN7aQetOT4glzMZWXROyu0z7O18ooaC822bt.','057885787585','male','outside old traffod','David De Gea','6356536533','LIfeline','defending','2 left feet','high',NULL,'2026-10-14 01:41:19');
/*!40000 ALTER TABLE `Patient` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Patient_Medication_Inventory`
--

DROP TABLE IF EXISTS `Patient_Medication_Inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Patient_Medication_Inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `order_id` int NOT NULL,
  `medication_name` varchar(200) NOT NULL,
  `quantity_received` int NOT NULL,
  `received_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `patient_medication_inventory_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`id`),
  CONSTRAINT `patient_medication_inventory_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Patient_Medication_Inventory`
--

LOCK TABLES `Patient_Medication_Inventory` WRITE;
/*!40000 ALTER TABLE `Patient_Medication_Inventory` DISABLE KEYS */;
INSERT INTO `Patient_Medication_Inventory` VALUES (1,6,15,'Paracetamol 500mg',30,'2026-10-08 17:56:32'),(2,6,16,'Amoxicillin 500mg',20,'2026-10-08 18:40:58'),(3,9,28,'pills',30,'2026-10-12 15:59:23');
/*!40000 ALTER TABLE `Patient_Medication_Inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Patient_Question`
--

DROP TABLE IF EXISTS `Patient_Question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Patient_Question` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `category_id` int NOT NULL,
  `question_text` text NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('open','answered','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`),
  KEY `idx_question_patient_id` (`patient_id`),
  KEY `idx_question_category_id` (`category_id`),
  CONSTRAINT `fk_question_category` FOREIGN KEY (`category_id`) REFERENCES `Education_Category` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_question_patient` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Patient_Question`
--

LOCK TABLES `Patient_Question` WRITE;
/*!40000 ALTER TABLE `Patient_Question` DISABLE KEYS */;
INSERT INTO `Patient_Question` VALUES (6,6,1,'Is sugar good for you?',0,'answered','2026-10-15 16:36:02'),(7,6,2,'Can I run a 10km after taking my medication',1,'answered','2026-10-15 16:36:27');
/*!40000 ALTER TABLE `Patient_Question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Pharmacies`
--

DROP TABLE IF EXISTS `Pharmacies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Pharmacies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `open_hour` time NOT NULL,
  `close_hours` time NOT NULL,
  `details` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `offers_delivery` tinyint(1) NOT NULL DEFAULT '0',
  `delivery_fee` decimal(10,2) DEFAULT NULL,
  `verification` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_pharmacy_hours` CHECK ((`open_hour` <> `close_hours`))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Pharmacies`
--

LOCK TABLES `Pharmacies` WRITE;
/*!40000 ALTER TABLE `Pharmacies` DISABLE KEYS */;
INSERT INTO `Pharmacies` VALUES (1,'HealthPlus Pharmacy','pharmacy1@proteahealth.local',NULL,'Johannesburg','08:00:00','18:00:00','Community pharmacy offering prescription and over-the-counter medication.','2026-09-30 12:27:33',1,35.00,'pending'),(2,'MedCare Pharmacy','pharmacy2@proteahealth.local',NULL,'Sandton','08:00:00','20:00:00','Full-service pharmacy with medication delivery.','2026-09-30 12:27:33',0,NULL,'pending'),(3,'Wellness Pharmacy','pharmacy3@proteahealth.local',NULL,'Pretoria','09:00:00','19:00:00','Pharmacy specialising in chronic medication and wellness products.','2026-09-30 12:27:33',0,NULL,'pending'),(4,'Protea Pharmacy','protea.pharmacy@example.com','$2y$12$YYPbOgeLCLFCRjP6vD13Xe7fMgyvQCKZ0vwZJJcCtP95udO2Wh53q','Protea Glen','08:00:00','18:00:00','Community pharmacy','2026-09-30 13:04:19',1,50.00,'verified'),(6,'how',NULL,NULL,'next door','08:55:00','01:55:00','','2026-10-13 12:12:22',0,NULL,'verified'),(8,'crazy pharm',NULL,NULL,'123 craxy stree','23:25:00','07:25:00','','2026-10-13 12:28:57',1,400.00,'verified'),(9,'116 charges',NULL,NULL,'ethihad','05:34:00','16:34:00','might get relegated','2026-10-14 14:36:27',1,250.00,'verified');
/*!40000 ALTER TABLE `Pharmacies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Pharmacist`
--

DROP TABLE IF EXISTS `Pharmacist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Pharmacist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `pharmacy_id` int DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `pharmacy_location` varchar(255) NOT NULL,
  `verification` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `registration_code` varchar(100) NOT NULL,
  `work_experience_years` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_pharmacist_pharmacy` (`pharmacy_id`),
  CONSTRAINT `fk_pharmacist_pharmacy` FOREIGN KEY (`pharmacy_id`) REFERENCES `Pharmacies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_pharmacist_experience` CHECK ((`work_experience_years` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Pharmacist`
--

LOCK TABLES `Pharmacist` WRITE;
/*!40000 ALTER TABLE `Pharmacist` DISABLE KEYS */;
INSERT INTO `Pharmacist` VALUES (1,'James','Mokoena','james.mokoena@example.com',NULL,NULL,'0115551001','Johannesburg','pending','',7,'2026-09-30 12:27:33'),(2,'Priya','Patel','priya.patel@example.com',NULL,NULL,'0115551002','Sandton','pending','',5,'2026-09-30 12:27:33'),(3,'Daniel','Smith','daniel.smith@example.com',NULL,NULL,'0115551003','Pretoria','pending','',11,'2026-09-30 12:27:33'),(4,'Test','Pharmacist','pha@123.com','$2y$12$aMxa3r1SGAWXkqtls2YfAODFRXcPXi.6OT50HOWmvxnoO2fnaw.Q2',4,'0123456789','Test Pharmacy','verified','TEST-PHARM-001',1,'2026-10-06 13:02:13'),(5,'dracula','the first','drac@blood.com','$2y$12$AbChSwcuD/tkepjFXcRSLOIjphBgO7a97Kj6QQsskOMUgX17X8yPm',6,'12345678','next door','verified','354245356yeye',2122,'2026-10-13 12:12:22'),(6,'james','bond','james@crazy.com','$2y$12$uatWC2sOglJfLXVj6TuHoO.XsqW7VT6lYavjl5Yi37J6P1sRViv3e',8,'98635466457','123 craxy stree','verified','dghd564647',2,'2026-10-13 12:28:57'),(7,'TestPharmacist','Maseko','testpharmacist1@example.com',NULL,4,'0000000001','Johannesburg','verified','DEMO-SAPC-001',4,'2026-10-13 19:09:52'),(8,'TestPharmacist','Patel','testpharmacist2@example.com',NULL,NULL,'0000000002','Sandton','pending','DEMO-SAPC-002',8,'2026-10-13 19:09:52'),(9,'TestPharmacist','Nkosi','testpharmacist3@example.com',NULL,NULL,'0000000003','Soweto','pending','DEMO-SAPC-003',6,'2026-10-13 19:09:52'),(10,'TestPharmacist','Govender','testpharmacist4@example.com',NULL,NULL,'0000000004','Midrand','pending','DEMO-SAPC-004',10,'2026-10-13 19:09:52'),(11,'TestPharmacist','Mahlangu','testpharmacist5@example.com',NULL,NULL,'0000000005','Pretoria','rejected','DEMO-SAPC-005',2,'2026-10-13 19:09:52'),(12,'Michael','Carrick','carrick@mun.com','$2y$12$hXD8EqKC5W/FfTXSYLiww.6uCj2oPtrdAR0vHQmLzkXv3ssuDU75C',4,'2456425625245','Protea Glen','pending','UEFA LICENSE',4,'2026-10-14 14:32:50'),(13,'pep','116','pep@mci.com','$2y$12$NVp.NOZlR8qPV/jghmT1h.4RdfBR3/EzrB9/E2Oj7dIWxE2PipGtm',9,'2452452','ethihad','verified','S UEFA LICENSE',12,'2026-10-14 14:36:27');
/*!40000 ALTER TABLE `Pharmacist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PharmacistLinkAudit`
--

DROP TABLE IF EXISTS `PharmacistLinkAudit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PharmacistLinkAudit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `pharmacy_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `reason` varchar(2000) NOT NULL,
  `linked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_link_pharmacist` (`pharmacist_id`),
  KEY `idx_link_pharmacy` (`pharmacy_id`),
  KEY `idx_link_admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PharmacistLinkAudit`
--

LOCK TABLES `PharmacistLinkAudit` WRITE;
/*!40000 ALTER TABLE `PharmacistLinkAudit` DISABLE KEYS */;
INSERT INTO `PharmacistLinkAudit` VALUES (1,7,4,1,'new job linked','2026-10-13 22:09:37');
/*!40000 ALTER TABLE `PharmacistLinkAudit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PharmacistUnlinkAudit`
--

DROP TABLE IF EXISTS `PharmacistUnlinkAudit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PharmacistUnlinkAudit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `pharmacy_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `reason` varchar(2000) NOT NULL,
  `unlinked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_unlink_pharmacist` (`pharmacist_id`),
  KEY `idx_unlink_pharmacy` (`pharmacy_id`),
  KEY `idx_unlink_admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PharmacistUnlinkAudit`
--

LOCK TABLES `PharmacistUnlinkAudit` WRITE;
/*!40000 ALTER TABLE `PharmacistUnlinkAudit` DISABLE KEYS */;
INSERT INTO `PharmacistUnlinkAudit` VALUES (1,7,4,1,'testinssss','2026-10-13 22:00:56');
/*!40000 ALTER TABLE `PharmacistUnlinkAudit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PharmacistVerificationAudit`
--

DROP TABLE IF EXISTS `PharmacistVerificationAudit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PharmacistVerificationAudit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacist_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `previous_status` enum('pending','verified','rejected') NOT NULL,
  `new_status` enum('verified','rejected') NOT NULL,
  `decision_reason` text NOT NULL,
  `credential_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pharmacist_id` (`pharmacist_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PharmacistVerificationAudit`
--

LOCK TABLES `PharmacistVerificationAudit` WRITE;
/*!40000 ALTER TABLE `PharmacistVerificationAudit` DISABLE KEYS */;
INSERT INTO `PharmacistVerificationAudit` VALUES (1,7,1,'pending','verified','ghdhghdsfgsf','fgdsfgdg','2026-10-13 20:09:51'),(2,11,1,'pending','rejected','no pharmacy',NULL,'2026-10-13 21:31:03'),(3,5,1,'pending','verified','sdfdsfsdfsdf','g5363tr','2026-10-13 21:31:45'),(4,6,1,'pending','verified','56e56565e6','656e65656','2026-10-13 21:34:30'),(5,13,1,'pending','verified','hhdhhd35636533','563653633','2026-10-14 14:40:11');
/*!40000 ALTER TABLE `PharmacistVerificationAudit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Pharmacy_Medication`
--

DROP TABLE IF EXISTS `Pharmacy_Medication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Pharmacy_Medication` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacy_id` int NOT NULL,
  `medication_name` varchar(200) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_medication_pharmacy_id` (`pharmacy_id`),
  CONSTRAINT `fk_medication_pharmacy` FOREIGN KEY (`pharmacy_id`) REFERENCES `Pharmacies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_medication_price` CHECK ((`price` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Pharmacy_Medication`
--

LOCK TABLES `Pharmacy_Medication` WRITE;
/*!40000 ALTER TABLE `Pharmacy_Medication` DISABLE KEYS */;
INSERT INTO `Pharmacy_Medication` VALUES (1,1,'Paracetamol 500mg',45.99,'unavailable','2026-09-30 12:27:33',0,1),(2,2,'Amoxicillin 500mg',89.99,'unavailable','2026-09-30 12:27:33',0,1),(3,3,'Cetirizine 10mg',65.50,'unavailable','2026-09-30 12:27:33',0,1),(4,1,'Metformin 500mg',85.00,'unavailable','2026-10-04 10:49:56',0,1),(5,1,'Amlodipine 5mg',65.00,'unavailable','2026-10-04 10:49:56',0,1),(6,1,'Atorvastatin 20mg',120.00,'unavailable','2026-10-04 10:49:56',0,1),(7,1,'Paracetamol 500mg',45.99,'unavailable','2026-10-05 12:12:02',0,0),(8,2,'Paracetamol 500mg',39.99,'unavailable','2026-10-05 12:12:02',0,1),(9,3,'Paracetamol 500mg',42.50,'unavailable','2026-10-05 12:12:02',0,1),(10,4,'Paracetamol 500mg',47.99,'unavailable','2026-10-05 12:12:02',60,0),(11,1,'Amoxicillin 500mg',89.99,'unavailable','2026-10-05 12:12:02',0,1),(12,2,'Amoxicillin 500mg',84.50,'unavailable','2026-10-05 12:12:02',0,0),(13,3,'Amoxicillin 500mg',92.99,'unavailable','2026-10-05 12:12:02',0,1),(14,4,'Amoxicillin 500mg',87.99,'available','2026-10-05 12:12:02',200,1),(15,1,'Cetirizine 10mg',59.99,'unavailable','2026-10-05 12:12:02',0,1),(16,2,'Cetirizine 10mg',54.99,'unavailable','2026-10-05 12:12:02',0,1),(17,3,'Cetirizine 10mg',57.50,'unavailable','2026-10-05 12:12:02',0,0),(18,4,'Cetirizine 10mg',61.99,'unavailable','2026-10-05 12:12:02',0,0),(19,4,'sleep',200.00,'unavailable','2026-10-08 19:15:56',100,0),(20,4,'sleep',20.00,'available','2026-10-08 21:36:40',100,1),(21,4,'sleepious',20.00,'available','2026-10-08 22:23:48',400,1),(22,1,'Paracetamol 500mg',25.00,'available','2026-10-11 23:14:50',100,1),(23,1,'Amoxicillin 500mg',85.00,'available','2026-10-11 23:14:50',50,1),(24,1,'Ibuprofen 200mg',45.00,'available','2026-10-11 23:14:50',80,1),(25,1,'Cetirizine 10mg',35.00,'available','2026-10-11 23:14:50',60,1),(26,1,'Omeprazole 20mg',75.00,'available','2026-10-11 23:14:50',40,1),(27,2,'Paracetamol 500mg',30.00,'available','2026-10-11 23:14:50',120,1),(28,2,'Amoxicillin 500mg',92.00,'available','2026-10-11 23:14:50',75,1),(29,2,'Ibuprofen 200mg',39.00,'available','2026-10-11 23:14:50',90,1),(30,2,'Cetirizine 10mg',42.00,'available','2026-10-11 23:14:50',45,1),(31,2,'Omeprazole 20mg',68.00,'available','2026-10-11 23:14:50',55,1),(32,3,'Paracetamol 500mg',22.00,'available','2026-10-11 23:14:50',75,1),(33,3,'Amoxicillin 500mg',89.00,'available','2026-10-11 23:14:50',35,1),(34,3,'Ibuprofen 200mg',42.00,'available','2026-10-11 23:14:50',65,1),(35,3,'Cetirizine 10mg',38.00,'available','2026-10-11 23:14:50',50,1),(36,3,'Omeprazole 20mg',72.00,'available','2026-10-11 23:14:50',30,1),(37,4,'Paracetamol 500mg',27.00,'available','2026-10-11 23:14:50',150,1),(38,4,'Amoxicillin 500mg',82.00,'unavailable','2026-10-11 23:14:50',60,0),(39,4,'Ibuprofen 200mg',41.00,'available','2026-10-11 23:14:50',95,1),(40,4,'Cetirizine 10mg',33.00,'available','2026-10-11 23:14:50',80,1),(41,4,'Omeprazole 20mg',70.00,'available','2026-10-11 23:14:50',45,1),(42,4,'pills',20.00,'available','2026-10-12 15:40:48',170,1),(43,4,'Panardo',10.00,'unavailable','2026-10-15 17:01:21',150,0);
/*!40000 ALTER TABLE `Pharmacy_Medication` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PharmacyVerificationAudit`
--

DROP TABLE IF EXISTS `PharmacyVerificationAudit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PharmacyVerificationAudit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacy_id` int NOT NULL,
  `pharmacist_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `previous_status` enum('pending','verified','rejected') NOT NULL,
  `new_status` enum('verified','rejected') NOT NULL,
  `decision_reason` text NOT NULL,
  `credential_reference` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pharmacy_id` (`pharmacy_id`),
  KEY `idx_pharmacist_id` (`pharmacist_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PharmacyVerificationAudit`
--

LOCK TABLES `PharmacyVerificationAudit` WRITE;
/*!40000 ALTER TABLE `PharmacyVerificationAudit` DISABLE KEYS */;
INSERT INTO `PharmacyVerificationAudit` VALUES (1,6,5,1,'pending','verified','sdfdsfsdfsdf','rtrttet','2026-10-13 21:31:45'),(2,8,6,1,'pending','verified','56e56565e6','e566e6e56','2026-10-13 21:34:30'),(3,9,13,1,'pending','verified','hhdhhd35636533','3563653565','2026-10-14 14:40:11');
/*!40000 ALTER TABLE `PharmacyVerificationAudit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Prescriptions`
--

DROP TABLE IF EXISTS `Prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Prescriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `medication_id` int NOT NULL,
  `quantity` int NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `remaining_doses` int NOT NULL DEFAULT '0',
  `last_taken_date` date DEFAULT NULL,
  `date_prescription` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prescription_patient_id` (`patient_id`),
  KEY `idx_prescription_doctor_id` (`doctor_id`),
  KEY `idx_prescription_medication_id` (`medication_id`),
  CONSTRAINT `fk_prescription_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `Doctor` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_medication` FOREIGN KEY (`medication_id`) REFERENCES `Pharmacy_Medication` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_patient` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_prescription_quantity` CHECK ((`quantity` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Prescriptions`
--

LOCK TABLES `Prescriptions` WRITE;
/*!40000 ALTER TABLE `Prescriptions` DISABLE KEYS */;
INSERT INTO `Prescriptions` VALUES (1,1,1,1,20,NULL,NULL,NULL,0,NULL,'2026-09-30 12:27:33'),(2,2,2,2,21,NULL,NULL,NULL,0,NULL,'2026-09-30 12:27:33'),(3,3,3,3,30,NULL,NULL,NULL,0,NULL,'2026-09-30 12:27:33'),(4,6,1,1,30,'1 tablet','Pain and fever relief','08:00:00',30,NULL,'2026-10-04 10:49:46'),(5,6,1,2,40,'1 capsule','Bacterial infection treatment','14:00:00',40,NULL,'2026-10-04 10:49:46'),(6,6,1,3,10,'1 tablet','Allergy symptom relief','21:00:00',10,NULL,'2026-10-04 10:49:46'),(7,6,6,19,20,'2','lack of','16:00:00',20,NULL,'2026-10-12 15:02:51'),(8,8,6,19,80,'2 hours','sleeping','16:00:00',80,NULL,'2026-10-12 15:38:29'),(9,9,6,42,45,'3 pills','defending','16:00:00',45,'2026-10-12','2026-10-12 15:53:14'),(10,9,6,19,5000,'1 pill','lack','16:00:00',5000,NULL,'2026-10-14 00:48:15');
/*!40000 ALTER TABLE `Prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Question_Answer`
--

DROP TABLE IF EXISTS `Question_Answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Question_Answer` (
  `answer_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `provider_id` int NOT NULL,
  `answer_text` text NOT NULL,
  `answered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`answer_id`),
  KEY `idx_answer_question_id` (`question_id`),
  KEY `idx_answer_provider_id` (`provider_id`),
  CONSTRAINT `fk_answer_doctor` FOREIGN KEY (`provider_id`) REFERENCES `Doctor` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `Patient_Question` (`question_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Question_Answer`
--

LOCK TABLES `Question_Answer` WRITE;
/*!40000 ALTER TABLE `Question_Answer` DISABLE KEYS */;
INSERT INTO `Question_Answer` VALUES (6,7,6,'yes','2026-10-15 18:49:35');
/*!40000 ALTER TABLE `Question_Answer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'proteahealth'
--

--
-- Dumping routines for database 'proteahealth'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-16  0:35:03
