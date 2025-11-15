-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: medivaultdb
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
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(20) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Ongoing','Completed','Cancelled') DEFAULT 'Pending',
  `type` enum('Consultation','Follow-up','Emergency','Regular Checkup') DEFAULT 'Consultation',
  `notes` text DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `fk_doctor` (`doctor_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (4,'P0003',NULL,'Pending','Consultation',NULL,47,'2025-11-11 04:00:00'),(7,'P0006',NULL,'Pending','Consultation',NULL,47,'2025-11-11 03:30:00'),(13,'P0006',NULL,'Pending','',NULL,47,'2025-11-11 03:29:00'),(14,'P0006',NULL,'Pending','',NULL,47,'2025-11-11 03:29:00'),(15,'P0004',NULL,'Pending','Follow-up',NULL,46,'2025-11-11 05:00:00'),(16,'P0005',NULL,'Pending','Regular Checkup',NULL,46,'2025-11-12 10:00:00'),(17,'P0003',NULL,'Completed','Consultation',NULL,47,'2025-11-13 17:00:00'),(18,'P0003',NULL,'Pending','Follow-up',NULL,58,'2025-11-13 23:27:00'),(19,'P0004',NULL,'Pending','Consultation',NULL,58,'2025-11-13 23:32:00');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `staff_name` varchar(255) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (2,NULL,'Doctor (shin)','Restock Item','Inventory','Restocked 10 units for M002','::1','2025-11-13 06:15:05'),(3,NULL,'Doctor (shin)','Add Item','Inventory','Added item Microscope Slides (S007)','::1','2025-11-13 06:15:43'),(4,NULL,'Doctor (shin)','Restock Item','Inventory','Restocked 5 units for M005','::1','2025-11-13 11:51:46'),(5,NULL,'Doctor (shin)','View','Patient','Viewed patient P0007 - isa pa','::1','2025-11-13 11:57:29'),(6,NULL,'Doctor (shin)','View','Patient','Viewed patient P0006 - sdfsd','::1','2025-11-13 11:57:43'),(7,NULL,'Doctor (shin)','Edit','Patient','Updated patient record ID 6 - sdfsddfsd','::1','2025-11-13 11:57:46'),(8,NULL,'Doctor (shin)','View','Patient','Viewed patient P0004 - Shin Dagami','::1','2025-11-13 12:04:49'),(9,NULL,'Doctor (jai)','View','Patient','Viewed patient P0004 - Shin Dagami','::1','2025-11-13 12:07:22'),(10,NULL,'Doctor (jai)','View','Appointments','Fetched appointments for date 2025-11-13','::1','2025-11-13 15:18:20'),(11,NULL,'Doctor (jai)','View','Appointments','Fetched current queue','::1','2025-11-13 15:18:20'),(12,NULL,'Doctor (jai)','View','Appointments','Fetched current queue','::1','2025-11-13 15:18:23'),(13,NULL,'Doctor (jai)','View','Appointments','Fetched appointments for date 2025-11-13','::1','2025-11-13 15:18:23'),(14,NULL,'Doctor (jai)','View','Appointments','Fetched patient list','::1','2025-11-13 15:18:25'),(15,NULL,'Doctor (jai)','View','Appointments','Fetched doctor list','::1','2025-11-13 15:18:25'),(16,NULL,'Doctor (jai)','Add','Appointments','Scheduled appointment for patient_id=P0003 with doctor_id=58 at 2025-11-13 23:27:00, type=Follow-up','::1','2025-11-13 15:18:44'),(17,NULL,'Doctor (jai)','View','Appointments','Fetched appointments for date 2025-11-13','::1','2025-11-13 15:18:44'),(18,NULL,'Doctor (jai)','View','Appointments','Fetched current queue','::1','2025-11-13 15:18:44'),(19,NULL,'Doctor (jai)','View','Appointments','Fetched appointments for date 2025-11-13','::1','2025-11-13 15:23:18'),(20,NULL,'Doctor (jai)','View','Appointments','Fetched current queue','::1','2025-11-13 15:23:18'),(21,NULL,'Doctor (jai)','View','Appointments','Fetched patient list','::1','2025-11-13 15:23:20'),(22,NULL,'Doctor (jai)','View','Appointments','Fetched doctor list','::1','2025-11-13 15:23:20'),(23,NULL,'Doctor (jai)','Add','Appointments','Scheduled appointment for patient Shin Dagami with Doctor Jai Jeetendra Bhavnani at 2025-11-13 23:32:00, type=Consultation','::1','2025-11-13 15:23:27'),(24,NULL,'Doctor (jai)','View','Appointments','Fetched appointments for date 2025-11-13','::1','2025-11-13 15:23:27'),(25,NULL,'Doctor (jai)','View','Appointments','Fetched current queue','::1','2025-11-13 15:23:28'),(26,NULL,'Doctor (jai)','View','Appointments','Fetched appointments for date 2025-11-13','::1','2025-11-13 15:23:56'),(27,NULL,'Doctor (jai)','View','Appointments','Fetched current queue','::1','2025-11-13 15:23:56'),(28,NULL,'Doctor (jai)','View','Appointments','Fetched appointments for date 2025-11-13','::1','2025-11-13 15:24:13'),(29,NULL,'Doctor (jai)','View','Appointments','Fetched current queue','::1','2025-11-13 15:24:13'),(30,NULL,'Doctor (jai)','Edit','Appointments','Updated appointment_id=17 to status=Ongoing','::1','2025-11-13 16:05:44'),(31,NULL,'Doctor (jai)','Edit','Appointments','Updated appointment for patient Kenshin with Doctor shin to status=Completed','::1','2025-11-13 16:11:04');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(100) DEFAULT NULL,
  `type` enum('Full','Partial') DEFAULT NULL,
  `size_mb` decimal(10,2) DEFAULT NULL,
  `status` enum('Success','Failed') DEFAULT 'Success',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
INSERT INTO `backups` VALUES (1,NULL,'',0.00,'Failed',NULL,'2025-11-13 17:08:11',''),(2,NULL,'',0.00,'Success',NULL,'2025-11-13 17:10:19','../backups/backup_manual_20251113_181017.sql'),(3,NULL,'',0.00,'Success',NULL,'2025-11-13 17:14:02','../backups/backup_manual_20251113_181400.sql'),(4,NULL,'',0.00,'Success',NULL,'2025-11-13 17:17:45','../backups/backup_manual_20251113_181743.sql'),(5,NULL,'',0.00,'Failed',NULL,'2025-11-13 17:20:26',''),(6,NULL,'',0.00,'Success',NULL,'2025-11-13 17:28:43','../backups/backup_manual_20251113_182841.sql'),(7,NULL,'',0.00,'Success',NULL,'2025-11-13 17:37:44','../backups/backup_manual_20251113_183742.sql'),(8,NULL,'',0.00,'Failed',NULL,'2025-11-13 17:42:09',''),(9,NULL,'',0.00,'Failed',NULL,'2025-11-13 17:44:25',''),(10,NULL,'',0.00,'Failed',NULL,'2025-11-13 17:46:26',''),(11,NULL,'',0.03,'Success',NULL,'2025-11-13 17:59:57','../backups/backup_manual_20251113_185957.sql');
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_code` varchar(10) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 0,
  `supplier` varchar(100) DEFAULT NULL,
  `last_restocked` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Available','Low Stock','Out of Stock') DEFAULT 'Available',
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (4,'M001','Paracetamol 500mg','Medicine',450,'Tablets',200,'si mama mo','2025-11-11','2026-12-31','Available'),(5,'M002','Amoxicillin 250mg','Medicine',70,'Tablets',100,'si lord','2025-11-13','2025-12-15','Available'),(6,'S001','Surgical Gloves','Supplies',70,'Boxes',50,'si lord','2025-11-11','2026-02-15','Available'),(7,'S002','Surgical Gloves','Supplies',20,'Boxes',50,'si lord','2025-11-11','2025-12-16','Available'),(8,'M003','Insulin Injection','Medicine',185,'Boxes',100,'si lord','2025-11-13','2026-12-15','Available'),(9,'M004','Aspirin 75mg','Medicine',320,'Boxes',150,'si lord','2025-11-12','2026-12-15','Available'),(10,'S003','Syringes 5ml','Supplies',70,'Pack',30,'si lord','2025-11-12','2026-12-15','Available'),(11,'M005','Ibuprofen 400mg','Medicine',805,'Tablets',400,'si lord','2025-11-13','2026-05-12','Available'),(12,'S004','Foley Catheter (Size 16)','Supplies',50,'Units',15,'si lord','2025-11-13','2026-05-12','Available'),(13,'S005','Sterile Gauze Pads (4x4)','Supplies',100,'Packs',50,'si lord','2025-11-13','2026-05-12','Available'),(14,'S006','Suture Kit (General)','Supplies',15,'Kits',5,'si lord','2025-11-13','2026-05-12','Available'),(15,'S007','Microscope Slides','Supplies',10,'Boxes',2,'si lord','2025-11-13','2026-05-12','Available');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `recipient` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `channel` enum('Email','SMS') DEFAULT 'Email',
  `status` enum('Sent','Pending','Failed') DEFAULT 'Pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'email','ryushinjiro@gmail.com','testing lang ba ','Email','Sent','2025-11-11 21:03:23','2025-11-11 21:03:23'),(2,'email','ryushinjiro@gmail.com','testing lang ba ','Email','Sent','2025-11-11 21:03:26','2025-11-11 21:03:26'),(3,'email','ryushinjiro@gmail.com','testing lang ba ','Email','Sent','2025-11-11 21:03:29','2025-11-11 21:03:29'),(4,'email','ryushinjiro@gmail.com','testing lang ba ','Email','Sent','2025-11-11 21:03:33','2025-11-11 21:03:33'),(5,'email','ryushinjiro@gmail.com','isa pa','Email','Failed','2025-11-11 21:14:48','2025-11-11 21:14:48'),(6,'email','ryushinjiro@gmail.com','isa pa','Email','Failed','2025-11-11 21:14:57','2025-11-11 21:14:57'),(7,'email','ryushinjiro@gmail.com','isa pa','Email','Failed','2025-11-11 21:15:09','2025-11-11 21:15:09'),(8,'email','ryushinjiro@gmail.com','tite','Email','Sent','2025-11-11 21:16:53','2025-11-11 21:16:53'),(9,'email','ryushinjiro@gmail.com','testing timer','Email','Failed','2025-11-11 21:40:24','2025-11-11 21:40:24'),(10,'email','ryushinjiro@gmail.com','testing timer','Email','Failed','2025-11-11 21:40:36','2025-11-11 21:40:36'),(11,'sms','09636889456','tangena mo gago','SMS','Failed','2025-11-12 19:37:17','2025-11-12 19:37:17'),(12,'sms','09636889456','tangena mo gago','SMS','Failed','2025-11-12 19:37:42','2025-11-12 19:37:42'),(13,'sms','09636889456','testing nga','SMS','Failed','2025-11-12 19:40:45','2025-11-12 19:40:45'),(14,'sms','09636889456','testing','SMS','Failed','2025-11-12 19:54:22','2025-11-12 19:54:22'),(15,'sms','+639636889456','testing','SMS','Failed','2025-11-12 19:54:35','2025-11-12 19:54:35'),(16,'sms','+639636889456','testing','SMS','Failed','2025-11-12 19:55:04','2025-11-12 19:55:04'),(17,'sms','+639636889456','test','SMS','Sent','2025-11-12 19:56:18','2025-11-12 19:56:18'),(18,'email','ryushinjiro@gmail.com','testing the audit','Email','Sent','2025-11-12 20:55:05','2025-11-12 20:55:05'),(19,'email','melvindorin01@gmail.com','tangena mo','Email','Sent','2025-11-13 14:06:54','2025-11-13 14:06:54');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `face_image_path` varchar(255) DEFAULT NULL,
  `face_encoding` text DEFAULT NULL,
  `biometric_enrolled` tinyint(1) DEFAULT 0,
  `status` enum('Active','Discharged','Transferred') DEFAULT 'Active',
  `last_visit` date DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (3,'P0003','Kenshin',21,'Male','09636889445','dto lang sa kanto','A+','si amamasss','sdfsad','sdfsadfasd','eto meron na kingina ka','uploads/faces/face_P0003_1761705953.jpeg','[-0.09695906937122345,0.14274103939533234,0.0751725435256958,-0.022151337936520576,-0.08466443419456482,-0.06053606793284416,0.03427989408373833,-0.08034095913171768,0.20189107954502106,-0.08770231157541275,0.2543592154979706,-0.08379649370908737,-0.20592980086803436,-0.12183684855699539,0.029311155900359154,0.15896055102348328,-0.1695486307144165,-0.13452033698558807,-0.07782314717769623,0.01739579252898693,0.07181020826101303,-0.011236337013542652,0.0013750339858233929,0.012810365296900272,-0.09868112206459045,-0.4381864070892334,-0.05516468361020088,-0.08735167235136032,0.03210855647921562,-0.09649045765399933,-0.07959593832492828,0.02187289670109749,-0.22996577620506287,-0.13431088626384735,0.009425329975783825,0.023005204275250435,-0.029349124059081078,-0.026872817426919937,0.158761665225029,0.0053942701779305935,-0.2635302245616913,-0.04178852215409279,0.029531007632613182,0.2605302631855011,0.1451861709356308,0.07667393237352371,0.015013998374342918,-0.10128328204154968,0.08815719932317734,-0.16367976367473602,0.022295843809843063,0.11690487712621689,0.14217624068260193,0.036269061267375946,0.012939024716615677,-0.12949389219284058,0.026537686586380005,0.09488668292760849,-0.18154211342334747,-0.009191117249429226,0.08892583101987839,-0.10153897851705551,-0.0520448163151741,-0.022965598851442337,0.29305899143218994,0.039326172322034836,-0.09240836650133133,-0.12045570462942123,0.05172256752848625,-0.14168493449687958,-0.026298556476831436,0.022354284301400185,-0.1033172607421875,-0.1683153510093689,-0.3471924364566803,0.013758278451859951,0.36752352118492126,0.0655834898352623,-0.20921891927719116,0.025441082194447517,-0.10791587829589844,0.027725987136363983,0.12726595997810364,0.16648653149604797,-0.0929243192076683,0.1014096587896347,-0.09164279699325562,-0.009683779440820217,0.16329646110534668,-0.06512388586997986,0.014677408151328564,0.23107846081256866,-0.029836934059858322,0.05052805319428444,-0.010818888433277607,-0.0642719566822052,-0.08831322938203812,0.014146778732538223,-0.04803447052836418,-0.04385184496641159,0.08318502455949783,-0.03558677062392235,0.0042406655848026276,0.14228083193302155,-0.2827507257461548,0.06515894085168839,-0.010881276801228523,0.040456678718328476,0.09769789129495621,-0.03001592680811882,-0.013708094134926796,-0.17651453614234924,0.06547369807958603,-0.25462257862091064,0.11610861867666245,0.22018463909626007,-0.0199291929602623,0.1400703340768814,0.06301563233137131,0.036177851259708405,-0.009108075872063637,0.0279125664383173,-0.19550873339176178,0.05403115600347519,0.13912558555603027,-0.03763756901025772,0.05821788311004639,-0.04781047999858856]',1,'Active','0000-00-00',NULL,'2025-10-29 02:45:53','2025-10-29 17:34:17'),(4,'P0004','Shin Dagami',20,'Male','0934566','cupang ba','A+','si amamss','nung nakaraan may','drugs','None','uploads/faces/face_P0004_1761777590.jpeg','[-0.04237509146332741,0.0692162960767746,0.011026671156287193,-0.015112606808543205,-0.0456274189054966,-0.04989006742835045,0.02743249200284481,-0.07983402162790298,0.14942215383052826,-0.10259424895048141,0.15116792917251587,-0.11876879632472992,-0.2551484704017639,-0.03936058655381203,-0.05088828131556511,0.15394245088100433,-0.16018161177635193,-0.0958796963095665,-0.05829446017742157,0.01377260871231556,0.05585449934005737,0.0364118292927742,0.036853112280368805,0.05377879738807678,-0.034722886979579926,-0.33275818824768066,-0.059446901082992554,-0.1184401884675026,-0.03575097396969795,-0.06228723004460335,-0.05645626410841942,0.07281165570020676,-0.1660270243883133,-0.06272514909505844,0.0314040407538414,0.029683295637369156,-0.03420494496822357,-0.018530378118157387,0.21540866792201996,0.04152862727642059,-0.21545232832431793,-0.035310227423906326,0.02247869037091732,0.2840322256088257,0.18638582527637482,0.1015959307551384,-0.07181040942668915,-0.09457206726074219,0.10626879334449768,-0.19973024725914001,0.002049426781013608,0.11013510078191757,0.0802997574210167,0.06292693316936493,-0.019890442490577698,-0.16458521783351898,0.0508866123855114,0.06922696530818939,-0.18195666372776031,0.0175421554595232,0.04325168579816818,-0.12924833595752716,-0.02896597981452942,-0.01878448761999607,0.31563133001327515,0.043953269720077515,-0.14630760252475739,-0.08924845606088638,0.11000505089759827,-0.1867779791355133,-0.0440063402056694,0.04189950227737427,-0.10295936465263367,-0.1749948412179947,-0.24638938903808594,-0.0066176000982522964,0.35377591848373413,0.10585308820009232,-0.2163049280643463,0.02170288749039173,-0.0541674941778183,0.023020008578896523,0.18729634582996368,0.20770417153835297,-0.03643713518977165,0.05429145321249962,-0.06934298574924469,-0.08269362896680832,0.15979118645191193,-0.07161090523004532,-0.08189579099416733,0.24540214240550995,-0.05213932693004608,0.07976401597261429,0.042369674891233444,-0.0560433566570282,-0.05191757157444954,0.005843829829245806,-0.14822492003440857,-0.049792684614658356,0.05374486744403839,-0.06397587060928345,-0.024089790880680084,0.1130538284778595,-0.17354512214660645,0.09454598277807236,-0.011750283651053905,0.03368578851222992,0.05661958083510399,-0.03228960558772087,-0.08504948019981384,-0.08235815167427063,0.13189111649990082,-0.2515718340873718,0.18174682557582855,0.15265803039073944,0.010930510237812996,0.14111635088920593,0.08619090914726257,0.08053521811962128,0.0011762307258322835,0.004845490679144859,-0.22644034028053284,-0.009700067341327667,0.16562357544898987,0.012112417258322239,0.11377823352813721,-0.043186966329813004]',1,'Active','0000-00-00',NULL,'2025-10-29 22:39:50','2025-10-29 22:42:12'),(5,'P0005','Shin Dagamisdf',25,'Male','934566','cupang ba','A-','sdf','dsf','sdf','bakit non kupal ka ba','uploads/faces/face_P0005_1761778133.jpeg','[-0.06641510874032974,0.08436734974384308,0.1019410490989685,0.006637008395045996,-0.03180220350623131,-0.06833437830209732,0.03731633350253105,-0.0742834210395813,0.12135545909404755,-0.07798535376787186,0.19667239487171173,-0.15956564247608185,-0.2603358030319214,-0.03429989516735077,-0.0162378940731287,0.1498996615409851,-0.1299581080675125,-0.06367122381925583,-0.05181749165058136,0.0025443686172366142,0.10393813252449036,-0.005789274349808693,0.012170729227364063,0.042940810322761536,-0.0847150906920433,-0.36400848627090454,-0.04939598590135574,-0.06611635535955429,-0.012143285945057869,-0.0544150248169899,-0.07997506111860275,0.07257956266403198,-0.18116523325443268,-0.0703146681189537,-0.0129472641274333,0.06973262876272202,-0.07373730093240738,-0.021330082789063454,0.1891215294599533,0.022641446441411972,-0.22945153713226318,-0.03839525207877159,-0.003600012045353651,0.2662879526615143,0.14405426383018494,0.11386619508266449,-0.034522294998168945,-0.07509282976388931,0.0797133818268776,-0.20829911530017853,0.01658058539032936,0.13847166299819946,0.07929456979036331,0.05088019743561745,-0.010959913954138756,-0.10190556198358536,0.06990925967693329,0.048150911927223206,-0.2610654830932617,0.037432439625263214,0.10138346254825592,-0.18620356917381287,-0.042420726269483566,-0.033314336091279984,0.2160545140504837,0.09095972031354904,-0.07917618006467819,-0.10706622153520584,0.1066499873995781,-0.18781931698322296,-0.04304593428969383,0.043487802147865295,-0.07025036960840225,-0.19102568924427032,-0.29895690083503723,-0.010443327017128468,0.36194080114364624,0.11390656232833862,-0.1848357617855072,0.02784368395805359,-0.033624399453401566,0.007417363580316305,0.2376575469970703,0.13759000599384308,-0.04067785292863846,0.06182025745511055,-0.1086784303188324,-0.016180073842406273,0.14085334539413452,-0.08404859900474548,-0.06430047005414963,0.2128772884607315,-0.033131856471300125,0.04023907706141472,0.04144563898444176,-0.020888373255729675,-0.07572484761476517,0.04418730363249779,-0.12340487539768219,-0.04003887251019478,0.10897817462682724,-0.07825976610183716,0.010383198969066143,0.06829854846000671,-0.19453765451908112,0.12409938126802444,-0.004191410727798939,0.026308005675673485,0.0779258981347084,-0.0468762144446373,-0.13514673709869385,-0.07591571658849716,0.127141535282135,-0.2471558302640915,0.15245015919208527,0.16862574219703674,-0.025311686098575592,0.11679299920797348,0.09216658025979996,0.07381106913089752,-0.004793176893144846,-0.02217923104763031,-0.21430478990077972,-0.026216663420200348,0.14748147130012512,0.026312001049518585,0.12555550038814545,-0.02298133261501789]',1,'Active','0000-00-00',NULL,'2025-10-29 22:48:53','2025-10-30 02:50:44'),(6,'P0006','sdfsddfsd',21,'Female','123112','fsadf','B-','1243124','sdfsdf','sdf','sdafsdfsdfsd',NULL,'',0,'Active','0000-00-00',NULL,'2025-10-30 16:11:25','2025-11-13 11:57:46'),(7,'P0007','isa pa',21,'Male','123112','fsadf','B-','1243124','dfsdf','sdaf','sadfsadfsdf',NULL,'',0,'Active','0000-00-00',NULL,'2025-11-10 08:50:45','2025-11-10 08:50:45');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `access_level` varchar(50) NOT NULL DEFAULT 'limited view only',
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (7,'STF-0002','tite teeete','Nurse','Emergency','tite@gmail.com','09123456723','Active','2025-11-05','Day','teetee','$2y$10$GsR4IsanbczN0XC40QK0m.dwS5iTjvUgwd47.M8afneTnSQRlNO3u','Appointments and scheduling'),(44,'STF-0003','hihee','Admin','','hihe@gmail.com','53463654','Active','2025-11-06','Day','hihee','$2y$10$YlKdrDl3JxpxSPXQrzDNOechjvajGOzj97gmf7yrP5HAElyV/.DaO','Full Access - All modules'),(45,'STF-0004','weh','Admin','','weh@gmail.com','21312','Active','2025-11-06','Day','weh','$2y$10$YFuuW2U6nGMd6fG2mePHWOXwc8Ez.iDw2779ivaFg3qOW.8OSoVcC','Full Access - All modules'),(46,'STF-0005','teste','Doctor','Pediatrics','test@gmail.com','0912434','Active','2025-11-07','Day','test123','$2y$10$6.yP.Ogojs4kkZTZahnOK.bOm4KZrT.82WObu.nREievvrRHUUfF.','Full Access - All modules'),(47,'STF-0006','shin','Doctor','Neurology','shin@gmail.com','35234','Active','2025-11-08','Day','shin','$2y$10$znTrIj5IZltCK/nKmEspDOBd4HKHjdG.pDb5qL0DfMZ9w7qitTyfq','Full Access - All modules'),(56,'STF-0007','ryu','Admin','','ryu@gmail.com','123','Active','2025-11-10','Day','ryu','$2y$10$64aWi9ZoOQ4Eya9gJKt3.ejF2z4ib3kPgH5T.uimobA3aeZu/TRQi','Full Access - All modules'),(57,'STF-0008','jiro','Nurse','Emergency','jiro@gmail.com','12435','Active','2025-11-10','Day','jiro','$2y$10$vm5Y8/Ou1buznj5Df.PE5e.n6IV5yVcimjlTnddROJjn9ce5F016e','Full Access - All modules'),(58,'STF-0009','Jai Jeetendra Bhavnani','Doctor','Pharmacy','jai@gmail.com','09123456734','Active','2025-11-13','Day','jai','$2y$10$mx3ZO2UMxwLtF.NbJ6kFY./uV6sPUnD1Jp3kb6RXhh89ol3sylzOm','Full Access - All modules');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Staff') NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$jTtDGf5p3dWw1NuBP6TdVOSd2ra7VNvopb6TtfGyX4LkF3pULvdGW','Admin','Admin User','admin@hospital.com','Active','2025-10-25 13:59:55'),(2,'staff','$2y$10$iGFBAe1F85V.XCHXypcc8Ot1xoaxF0yg4dQmQKClQJUgADX2G2ep2','Staff','staff','staff@gmail.com','Active','2025-10-26 06:06:45');
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

-- Dump completed on 2025-11-14  2:34:33
