-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: sandouk_db
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'مدير النظام','admin@sandouk.local','$2y$10$j/waeuToUUF96WC6lUaccOimq7XtSA58WgeWIQgdnohblgw1FgXvq','2026-09-20 11:03:13','2026-09-30 11:05:12');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read') NOT NULL DEFAULT 'new',
  `reply_text` text,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_contact_member` (`member_id`),
  CONSTRAINT `fk_contact_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `content_pages`
--

DROP TABLE IF EXISTS `content_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_pages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `title_en` varchar(200) DEFAULT NULL,
  `content` longtext NOT NULL,
  `content_en` longtext,
  `sections_json` longtext,
  `form_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `form_config_json` longtext,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `content_pages`
--

LOCK TABLES `content_pages` WRITE;
/*!40000 ALTER TABLE `content_pages` DISABLE KEYS */;
INSERT INTO `content_pages` VALUES (1,'about','من نحن','About Us','تأسس صندوق العائلة انطلاقاً من مبدأ التعاون والتكافل الاجتماعي، ليكون مظلة مالية آمنة ومنصة ادخارية تكافلية تخدم جميع أفراد العائلة.\n\nيسعى الصندوق إلى تعزيز أواصر القربى، وتوفير الدعم المالي الميسر الخالي من أي فوائد ربوية، وتمكين الأعضاء من تنمية مدخراتهم من خلال نظام أسهم مرن وموثق بكل دقة وشفافية.','The Family Solidarity Fund was founded on the principles of mutual support and solidarity, serving as a trusted financial haven and savings ecosystem for all family members.\n\nThe fund aims to strengthen kinship bonds, provide easy interest-free financial assistance, and empower members to grow their personal savings through a flexible and transparent shareholding system.','[\n    {\n        \"icon\": \"shield-check\",\n        \"badge\": \"حوكمة وأمان\",\n        \"badge_en\": \"Governance\",\n        \"title_ar\": \"الأمان والشفافية التامة\",\n        \"title_en\": \"Security & Full Transparency\",\n        \"body_ar\": \"إدارة محاسبية مدققة ودفتر أستاذ إلكتروني فوري يوضح إجمالي رصيد الصندوق، الاشتراكات، وحركة القروض بكل وضوح.\",\n        \"body_en\": \"Audited accounting management and real-time electronic ledger showing total fund assets, subscriptions, and loan movements with complete clarity.\"\n    },\n    {\n        \"icon\": \"handshake\",\n        \"badge\": \"قرض حسن\",\n        \"badge_en\": \"Interest-Free\",\n        \"title_ar\": \"تمويل ميسر بدون فوائد\",\n        \"title_en\": \"Zero-Interest Family Loans\",\n        \"body_ar\": \"قروض تكافلية متوافقة 100% مع الضوابط الشرعية، تمنح وفقاً لنظام نقاط وجدولة واضحة لحفظ حقوق الجميع.\",\n        \"body_en\": \"Solidarity loans fully compliant with Islamic principles, granted through a structured queue and scoring system to guarantee fairness.\"\n    },\n    {\n        \"icon\": \"award\",\n        \"badge\": \"استدامة\",\n        \"badge_en\": \"Sustainability\",\n        \"title_ar\": \"حفظ حقوق المساهمين والورثة\",\n        \"title_en\": \"Shareholder & Heir Protection\",\n        \"body_ar\": \"قيمة الأسهم محفوظة ومسجلة رسمياً بالاسم والهوية، مع إمكانية استردادها أو توريثها وفق اللائحة المعتمدة.\",\n        \"body_en\": \"Share values are officially documented under members verified IDs, fully redeemable or inheritable per bylaws.\"\n    },\n    {\n        \"icon\": \"smartphone\",\n        \"badge\": \"بوابة رقمية\",\n        \"badge_en\": \"Digital Portal\",\n        \"title_ar\": \"تجربة رقمية ذكية وشاملة\",\n        \"title_en\": \"Smart & Seamless Digital Experience\",\n        \"body_ar\": \"إمكانية متابعة حسابك، حاسبة القروض التفاعلية، وإشعارات السداد عبر الهاتف أو الحاسوب على مدار الساعة.\",\n        \"body_en\": \"Access account balance, simulate loan repayments, and receive reminders via mobile or desktop 24\\/7.\"\n    }\n]',0,NULL,'2026-09-21 12:20:25'),(2,'contact','تواصل مع إدارة الصندوق','Contact Administration','يسعدنا دائماً استقبال استفساراتكم، مقترحاتكم، أو طلباتكم الخاصة.\n\nيمكنكم التواصل المباشر مع لجنة الصندوق عبر القنوات المعتمدة أدناه.','We welcome all inquiries, suggestions, and assistance requests from our dear family members.\n\nReach out directly to the executive committee through the official channels listed below.','[\n    {\n        \"icon\": \"telephone\",\n        \"badge\": \"هاتف\",\n        \"badge_en\": \"Phone\",\n        \"title_ar\": \"الخط الساخن والمكالمات\",\n        \"title_en\": \"Official Telephone Line\",\n        \"body_ar\": \"متاح يومياً من 4 عصراً حتى 9 مساءً للرد على كافة الاستفسارات الطارئة والتنظيمية.\",\n        \"body_en\": \"Available daily from 4:00 PM to 9:00 PM for urgent queries and executive assistance.\"\n    },\n    {\n        \"icon\": \"envelope\",\n        \"badge\": \"إيميل\",\n        \"badge_en\": \"Email\",\n        \"title_ar\": \"البريد الإلكتروني الرسمي\",\n        \"title_en\": \"Official Support Email\",\n        \"body_ar\": \"info@sandouk.local - يتم الرد على كافة الرسائل الرسمية خلال 24 ساعة عمل.\",\n        \"body_en\": \"info@sandouk.local - All formal communications are addressed within 24 working hours.\"\n    },\n    {\n        \"icon\": \"chat-dots\",\n        \"badge\": \"تذاكر\",\n        \"badge_en\": \"Helpdesk\",\n        \"title_ar\": \"نظام الرسائل الداخلي\",\n        \"title_en\": \"Internal Member Helpdesk\",\n        \"body_ar\": \"يمكن للأعضاء المسجلين إرسال تذاكر دعم فني ومتابعة حالتها مباشرة من لوحة تحكم العضو.\",\n        \"body_en\": \"Registered members can submit tickets and track inquiries directly from their personal dashboard.\"\n    }\n]',1,'{\n    \"enabled\": true,\n    \"type\": \"contact\",\n    \"title_ar\": \"نموذج التواصل والاستفسارات الرسمية\",\n    \"title_en\": \"Official Inquiries & Contact Form\",\n    \"desc_ar\": \"نسعد باستقبال استفساراتكم وملاحظاتكم وسيقوم فريق أمانة الصندوق بمتابعتها والرد عليكم في أقرب وقت.\",\n    \"desc_en\": \"We welcome your inquiries, feedback, and questions. The fund committee will get back to you promptly.\",\n    \"require_email\": true,\n    \"require_subject\": true,\n    \"success_msg_ar\": \"تم إرسال رسالتكم بنجاح وسيتواصل معكم فريق إدارة الصندوق.\",\n    \"success_msg_en\": \"Your message has been received successfully. We will follow up shortly.\"\n}','2026-09-20 11:50:40'),(3,'privacy','سياسة الخصوصية وسرية البيانات','Privacy & Data Protection','نحن نولي خصوصية بيانات أفراد العائلة وسجلاتهم المالية أقصى درجات الاهتمام والسرية.\n\nتوضح هذه الوثيقة كيفية جمع البيانات واستخدامها وحمايتها داخل المنظومة الإلكترونية.','We handle our family members personal and financial records with utmost confidentiality and state-of-the-art security.\n\nThis policy explains how data is collected, securely processed, and protected within our digital ecosystem.','[\n    {\n        \"icon\": \"lock\",\n        \"badge\": \"تشفير\",\n        \"badge_en\": \"Encryption\",\n        \"title_ar\": \"تشفير السجلات وحمايتها\",\n        \"title_en\": \"Data Encryption & Safe Storage\",\n        \"body_ar\": \"جميع كلمات المرور والبيانات البنكية الحساسة مشفرة بأحدث خوارزميات التشفير القياسية العالمية.\",\n        \"body_en\": \"All passwords and sensitive transaction records are encrypted using modern industry-standard security protocols.\"\n    },\n    {\n        \"icon\": \"eye-slash\",\n        \"badge\": \"سرية\",\n        \"badge_en\": \"Confidentiality\",\n        \"title_ar\": \"حظر مشاركة البيانات مع أي طرف خارجي\",\n        \"title_en\": \"Strict Non-Disclosure Guarantee\",\n        \"body_ar\": \"لا يتم مشاركة أي معلومة عن المشتركين أو أرصدتهم أو معاملاتهم مع أي جهة تجارية أو خارجية نهائياً.\",\n        \"body_en\": \"No personal, financial, or contact records are ever shared or monetized with any third party under any circumstances.\"\n    },\n    {\n        \"icon\": \"person-badge\",\n        \"badge\": \"صلاحيات\",\n        \"badge_en\": \"Access Control\",\n        \"title_ar\": \"صلاحيات وصول دقيقة ومحددة\",\n        \"title_en\": \"Role-Based Access Control\",\n        \"body_ar\": \"لا يطلع على التفاصيل الشخصية إلا إدارة الصندوق المخولة والمعتمدة لحفظ الخصوصية التامة.\",\n        \"body_en\": \"Only authorized administrative committee members can review financial records necessary for operational decisions.\"\n    }\n]',0,NULL,'2026-09-20 11:26:04'),(4,'terms','شروط وأحكام الصندوق','Terms & Fund Regulations','تهدف هذه الشروط واللوائح إلى تنظيم عمل الصندوق التكافلي، وضمان العدالة وتكافؤ الفرص بين جميع المشتركين.\n\nإن انضمام أي عضو للصندوق أو تقديمه لأي طلب تمويل يعتبر موافقة كاملة وصريحة على كافة المواد والبنود المنصوص عليها في هذه اللائحة.','These regulations govern the operations of the Family Solidarity Fund, ensuring fairness, equity, and transparency among all active members.\n\nBy registering or submitting any financing request, the member fully acknowledges and agrees to comply with all terms and bylaws stated herein.','[\n    {\n        \"icon\": \"check-circle\",\n        \"badge\": \"المادة 1\",\n        \"badge_en\": \"Article 1\",\n        \"title_ar\": \"شروط الانضمام والعضوية\",\n        \"title_en\": \"Membership Eligibility\",\n        \"body_ar\": \"العضوية متاحة لجميع أفراد العائلة المستوفين لسن الأهلية، مع الالتزام بالاكتتاب بحد أدنى سهم واحد وسداد رسم التأسيس.\",\n        \"body_en\": \"Membership is open to qualified adult family members, with a commitment to subscribe to at least one share and pay the founding fee.\"\n    },\n    {\n        \"icon\": \"calendar-check\",\n        \"badge\": \"المادة 2\",\n        \"badge_en\": \"Article 2\",\n        \"title_ar\": \"الاشتراك الشهري ومواعيد السداد\",\n        \"title_en\": \"Monthly Contributions & Due Dates\",\n        \"body_ar\": \"يستحق قسط السهم الشهري في موعد أقصاه اليوم العاشر من كل شهر ميلادي لضمان استمرارية سيولة الصندوق.\",\n        \"body_en\": \"Monthly share installments are due no later than the 10th day of each calendar month to sustain fund liquidity.\"\n    },\n    {\n        \"icon\": \"cash-coin\",\n        \"badge\": \"المادة 3\",\n        \"badge_en\": \"Article 3\",\n        \"title_ar\": \"ضوابط صرف القروض والتخصيص\",\n        \"title_en\": \"Loan Granting & Repayment Rules\",\n        \"body_ar\": \"يتم الصرف وفق الأولويات ونظام الدور الآلي، بحد أقصى يعتمد على عدد أسهم العضو وسجله الائتماني في الالتزام.\",\n        \"body_en\": \"Disbursements follow automated priority queues, capped by the members active shares and historical commitment score.\"\n    },\n    {\n        \"icon\": \"shield-exclamation\",\n        \"badge\": \"المادة 4\",\n        \"badge_en\": \"Article 4\",\n        \"title_ar\": \"حالات التأخر والتعثر\",\n        \"title_en\": \"Default & Arrears Policy\",\n        \"body_ar\": \"في حال تعثر العضو يتم مراجعة حالته من قبل لجنة الصندوق لإيجاد جدولة ميسرة تناسب ظروفه دون الإضرار بالصندوق.\",\n        \"body_en\": \"In cases of hardship, the committee reviews the case to structure flexible grace periods without risking capital.\"\n    }\n]',0,NULL,'2026-09-20 11:26:04'),(5,'loan_commitment','تعهد سداد القرض',NULL,'أتعهد أنا المشترك بسداد قيمة القرض على الأقساط المحددة في مواعيدها، وأوافق على جميع شروط وأحكام السداد الخاصة بصندوق العائلة.',NULL,NULL,0,NULL,'2026-09-20 11:03:13');
/*!40000 ALTER TABLE `content_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `founding_amounts`
--

DROP TABLE IF EXISTS `founding_amounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `founding_amounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `shares_count_linked` int unsigned NOT NULL DEFAULT '0',
  `total_required` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `due_day` tinyint unsigned DEFAULT NULL COMMENT 'the founding amount own due day (1-28), pinned the day it first becomes owed, independent of the member subscription due day',
  `plan_months` tinyint unsigned NOT NULL DEFAULT '1',
  `plan_start` date DEFAULT NULL,
  `plan_schedule` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_id` (`member_id`),
  CONSTRAINT `fk_founding_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `founding_amounts`
--

LOCK TABLES `founding_amounts` WRITE;
/*!40000 ALTER TABLE `founding_amounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `founding_amounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `founding_payments`
--

DROP TABLE IF EXISTS `founding_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `founding_payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `founding_amount_id` int unsigned NOT NULL,
  `member_id` int unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `recorded_by` int unsigned DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_founding_payments_founding` (`founding_amount_id`),
  KEY `fk_founding_payments_member` (`member_id`),
  KEY `fk_founding_payments_admin` (`recorded_by`),
  CONSTRAINT `fk_founding_payments_admin` FOREIGN KEY (`recorded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_founding_payments_founding` FOREIGN KEY (`founding_amount_id`) REFERENCES `founding_amounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_founding_payments_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `founding_payments`
--

LOCK TABLES `founding_payments` WRITE;
/*!40000 ALTER TABLE `founding_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `founding_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_installments`
--

DROP TABLE IF EXISTS `loan_installments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_installments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` int unsigned NOT NULL,
  `installment_number` int unsigned NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_installments_loan` (`loan_id`),
  CONSTRAINT `fk_installments_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_installments`
--

LOCK TABLES `loan_installments` WRITE;
/*!40000 ALTER TABLE `loan_installments` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_installments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_requests`
--

DROP TABLE IF EXISTS `loan_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `lot_id` int unsigned DEFAULT NULL,
  `amount_requested` decimal(12,2) NOT NULL,
  `reason` enum('personal','educational','marriage','other') NOT NULL,
  `reason_other_text` varchar(500) DEFAULT NULL,
  `installments_months` int unsigned NOT NULL DEFAULT '6',
  `agreed_terms` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(500) DEFAULT NULL,
  `reviewed_by` int unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_loan_requests_member` (`member_id`),
  KEY `fk_loan_requests_admin` (`reviewed_by`),
  KEY `fk_loan_requests_lot` (`lot_id`),
  CONSTRAINT `fk_loan_requests_admin` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_loan_requests_lot` FOREIGN KEY (`lot_id`) REFERENCES `share_lots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_loan_requests_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_requests`
--

LOCK TABLES `loan_requests` WRITE;
/*!40000 ALTER TABLE `loan_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `lot_id` int unsigned DEFAULT NULL,
  `loan_request_id` int unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` enum('personal','educational','marriage','other') NOT NULL,
  `reason_other_text` varchar(500) DEFAULT NULL,
  `loan_date` date NOT NULL,
  `installments_count` int unsigned NOT NULL,
  `installment_value` decimal(12,2) NOT NULL,
  `admin_fee_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `admin_fee_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_remaining` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','partial','paid','closed') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_loans_member` (`member_id`),
  KEY `fk_loans_request` (`loan_request_id`),
  KEY `fk_loans_lot` (`lot_id`),
  CONSTRAINT `fk_loans_lot` FOREIGN KEY (`lot_id`) REFERENCES `share_lots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_loans_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loans_request` FOREIGN KEY (`loan_request_id`) REFERENCES `loan_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `national_address` varchar(255) DEFAULT NULL,
  `national_id` varchar(30) NOT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `iban` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `shares_count` int unsigned NOT NULL DEFAULT '0',
  `subscription_due_day` tinyint unsigned DEFAULT NULL COMMENT 'per-member override of the global subscription due day (1-28); NULL falls back to the site setting',
  `is_admin` tinyint unsigned NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `national_id` (`national_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_subscriptions`
--

DROP TABLE IF EXISTS `monthly_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monthly_subscriptions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `lot_id` int unsigned DEFAULT NULL,
  `month` char(7) NOT NULL COMMENT 'YYYY-MM',
  `shares_count_snapshot` int unsigned NOT NULL,
  `share_value_snapshot` decimal(12,2) NOT NULL,
  `amount_due` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `due_date` date NOT NULL,
  `grace_until` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_member_month_lot` (`member_id`,`month`,`lot_id`),
  KEY `fk_subscriptions_lot` (`lot_id`),
  CONSTRAINT `fk_subscriptions_lot` FOREIGN KEY (`lot_id`) REFERENCES `share_lots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscriptions_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_subscriptions`
--

LOCK TABLES `monthly_subscriptions` WRITE;
/*!40000 ALTER TABLE `monthly_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `monthly_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `target_type` enum('all','specific') NOT NULL DEFAULT 'all',
  `target_member_id` int unsigned DEFAULT NULL,
  `category` enum('system','manual') NOT NULL DEFAULT 'manual',
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `created_by` int unsigned DEFAULT NULL,
  `dedupe_key` varchar(80) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notif_dedupe` (`target_member_id`,`dedupe_key`),
  KEY `fk_notifications_admin` (`created_by`),
  CONSTRAINT `fk_notifications_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notifications_member` FOREIGN KEY (`target_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(150) NOT NULL,
  `code` varchar(10) NOT NULL,
  `purpose` enum('member_register','member_reset','admin_reset') NOT NULL,
  `payload` text,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_identifier_purpose` (`identifier`,`purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_codes`
--

LOCK TABLES `otp_codes` WRITE;
/*!40000 ALTER TABLE `otp_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_limits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rkey` char(64) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rkey_created` (`rkey`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limits`
--

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','صندوق عائلي'),(2,'share_value','1000'),(3,'founding_fee_per_share','500'),(4,'loan_admin_fee_percent','2'),(5,'subscription_due_day','10'),(6,'official_phone','+966500000000'),(7,'official_email','info@sandouk.local'),(9,'site_name_en','Family Solidarity Fund'),(10,'site_slogan','المنظومة المالية والتكافلية للأسرة'),(11,'site_slogan_en','Family Financial & Solidarity Ecosystem'),(12,'site_logo',''),(13,'logo_icon','safe2-fill'),(16,'official_whatsapp','+966500000000'),(17,'official_address','المملكة العربية السعودية - الرياض'),(18,'official_address_en','Riyadh, Kingdom of Saudi Arabia'),(19,'social_twitter','https://x.com'),(20,'social_instagram','https://instagram.com'),(21,'social_telegram','https://t.me'),(22,'section_hero_enabled','1'),(23,'section_stats_enabled','1'),(24,'section_features_enabled','1'),(25,'section_calculator_enabled','1'),(26,'section_charter_enabled','1'),(27,'section_hadith_enabled','1'),(28,'section_cta_enabled','1'),(29,'navigation_menu_json','[\n    {\n        \"id\": \"home\",\n        \"title_ar\": \"الرئيسية\",\n        \"title_en\": \"Home\",\n        \"url\": \"\\/\",\n        \"target\": \"_self\",\n        \"enabled\": true\n    },\n    {\n        \"id\": \"features\",\n        \"title_ar\": \"المميزات\",\n        \"title_en\": \"Features\",\n        \"url\": \"\\/#features\",\n        \"target\": \"_self\",\n        \"enabled\": true\n    },\n    {\n        \"id\": \"calculator\",\n        \"title_ar\": \"حاسبة القروض\",\n        \"title_en\": \"Calculator\",\n        \"url\": \"\\/#calculator\",\n        \"target\": \"_self\",\n        \"enabled\": true\n    },\n    {\n        \"id\": \"charter\",\n        \"title_ar\": \"ميثاق الصندوق\",\n        \"title_en\": \"Charter\",\n        \"url\": \"\\/#charter\",\n        \"target\": \"_self\",\n        \"enabled\": true\n    },\n    {\n        \"id\": \"about\",\n        \"title_ar\": \"من نحن\",\n        \"title_en\": \"About Us\",\n        \"url\": \"\\/page\\/about\",\n        \"target\": \"_self\",\n        \"enabled\": true\n    },\n    {\n        \"id\": \"terms\",\n        \"title_ar\": \"شروط الاستخدام\",\n        \"title_en\": \"Terms\",\n        \"url\": \"\\/page\\/terms\",\n        \"target\": \"_self\",\n        \"enabled\": true\n    },\n    {\n        \"id\": \"contact\",\n        \"title_ar\": \"تواصل معنا\",\n        \"title_en\": \"Contact Us\",\n        \"url\": \"\\/page\\/contact\",\n        \"target\": \"_self\",\n        \"enabled\": true\n    }\n]'),(30,'seo_meta_title','صندوق العائلة التكافلي - البوابة الرسمية'),(31,'seo_meta_title_en','Family Solidarity Fund - Official Portal'),(32,'seo_meta_description','نظام إلكتروني متكامل لإدارة اشتراكات الأسهم والقروض الحسنة لأبناء العائلة بشفافية وأمان تام.'),(33,'seo_meta_description_en','An integrated digital platform for family shares, monthly subscriptions, and 0% interest benevolent loans.'),(34,'seo_meta_keywords','صندوق عائلي, تكافل, قروض حسنة, أسهم, عائلة, تمويل ميسر'),(35,'seo_meta_keywords_en','family fund, solidarity, benevolent loans, shares, family finance'),(36,'seo_og_image',''),(37,'seo_canonical_url',''),(38,'seo_google_analytics',''),(39,'seo_custom_header_scripts',''),(40,'seo_custom_footer_scripts',''),(41,'site_language_mode','multi'),(42,'site_default_language','ar'),(46,'max_loan_ratio','10'),(48,'otp_mode','demo'),(49,'otp_resend_seconds','60'),(50,'otp_length','4'),(51,'otp_expiry_minutes','10'),(52,'sms_provider','taqnyat'),(93,'site_favicon','uploads/branding/favicon_1789984308_09e9e5.jpg'),(94,'sms_sender_name','صندوق عائلي'),(95,'sms_api_key',''),(96,'sms_app_sid',''),(97,'sms_username',''),(98,'sms_password',''),(99,'sms_custom_url',''),(100,'site_favicon_url',''),(102,'lt_enabled','1'),(103,'otp_verify_lock_minutes','1'),(104,'otp_max_failed_attempts','3'),(105,'otp_max_issued_per_identifier','3'),(106,'otp_issue_window_minutes','1'),(107,'subscription_grace_days','7'),(108,'subscription_grace_enabled','0'),(111,'homepage_sections_json','[{\"id\":\"hero\",\"type\":\"hero\",\"enabled\":true,\"badge_ar\":\"المنصة الرقمية الموحدة للصندوق العائلي\",\"badge_en\":\"Unified Family Fund Digital Platform\",\"title_ar\":\"تكافل عائلي مستدام،\",\"title_en\":\"Sustainable Family Solidarity,\",\"title_2_ar\":\"وأمان مالي لأجيالنا\",\"title_2_en\":\"Financial Security for Generations\",\"desc_ar\":\"نظام إلكتروني متكامل يربط أبناء وبنات العائلة لإدارة الأسهم الاستثمارية، الاشتراكات الشهرية، والقروض الحسنة الميسرة بشفافية مطلقة وأعلى درجات الأمان.\",\"desc_en\":\"An integrated digital platform connecting family members to manage investment shares, monthly contributions, and benevolent interest-free loans with absolute transparency and security.\",\"stat_shares_ar\":\"إجمالي الأسهم المسجلة\",\"stat_shares_en\":\"Total Registered Shares\",\"stat_share_val_ar\":\"قيمة السهم الشهري\",\"stat_share_val_en\":\"Monthly Share Value\",\"stat_members_ar\":\"أفراد العائلة المشتركين\",\"stat_members_en\":\"Active Family Members\",\"stat_transparency_ar\":\"شفافية وتوثيق رقمي\",\"stat_transparency_en\":\"Digital Transparency\",\"image\":\"\",\"show_stats\":false},{\"id\":\"features\",\"type\":\"features\",\"enabled\":true,\"kicker_ar\":\"ركائز المنظومة\",\"kicker_en\":\"Core Pillars\",\"title_ar\":\"خدمات وحلول مالية أسرية متقدمة\",\"title_en\":\"Advanced Family Financial Solutions\",\"desc_ar\":\"صُمم الصندوق ليوفر المرونة والعدالة لكل فرد من أفراد الأسرة، وفق تنظيم مالي دقيق ولائحة معتمدة.\",\"desc_en\":\"Designed to ensure fairness, flexibility, and unity for every family member under strict bylaws and governance.\",\"items\":[{\"title_ar\":\"الأسهم والادخار الشهري\",\"title_en\":\"Investment Shares & Savings\",\"body_ar\":\"اكتتاب مرن في أسهم الصندوق مع إمكانية زيادة الأسهم أو دمجها أو التنازل عنها وفق الضوابط.\",\"body_en\":\"Flexible share ownership with options to add, merge, or transfer shares under family governance.\",\"icon\":\"pie-chart-fill\",\"color\":\"emerald\"},{\"title_ar\":\"قروض حسنة بلا فوائد\",\"title_en\":\"Interest-Free Loans\",\"body_ar\":\"تمويلات ميسرة لأبناء العائلة لمساندتهم في الزواج، التعليم، الطوارئ أو المشاريع، بدون أي فوائد ربوية.\",\"body_en\":\"Benevolent mutual financing to support education, marriage, emergencies, or business without any usury.\",\"icon\":\"cash-coin\",\"color\":\"amber\"},{\"title_ar\":\"مبلغ التأسيس المتكافئ\",\"title_en\":\"Founding Capital Contribution\",\"body_ar\":\"مساهمة تأسيسية مرتبطة بحجم الأسهم تضمن استدامة الصندوق وتكوين احتياطي مالي متين لمواجهة الطوارئ.\",\"body_en\":\"Fair capital foundation linked to shares, ensuring long-term solvency and solid emergency reserves.\",\"icon\":\"bank2\",\"color\":\"blue\"},{\"title_ar\":\"حوكمة وشفافية مطلقة\",\"title_en\":\"Absolute Governance & Transparency\",\"body_ar\":\"لوحة متابعة مالية فورية لكل مشترك، إشعارات تلقائية عبر النظام، وكشوفات دورية موثقة لحفظ الحقوق.\",\"body_en\":\"Real-time financial ledgers, automated notifications, and periodic audited reports for the family council.\",\"icon\":\"shield-check\",\"color\":\"purple\"}]},{\"id\":\"calculator\",\"type\":\"calculator\",\"enabled\":true,\"kicker_ar\":\"حاسبة تفاعلية حية\",\"kicker_en\":\"Live Interactive Simulator\",\"title_ar\":\"جرّب حاسبة التكافل والأسهم\",\"title_en\":\"Simulate Your Shares & Loans\",\"desc_ar\":\"حرّك المؤشرات لتستكشف اشتراكك الشهري وسقف القرض التقديري المتاح لك وفق ضوابط الصندوق.\",\"desc_en\":\"Adjust the sliders to explore your monthly contribution, eligible loan ceiling, and projected installments.\",\"disclaimer_ar\":\"هذه الحسبة تقديرية استرشادية مبنية على قيمة السهم المعتمدة حالياً ونسبة القرض التكافلي.\",\"disclaimer_en\":\"This calculation is an estimate based on the current approved share value and mutual loan ratio.\"},{\"id\":\"charter\",\"type\":\"charter\",\"enabled\":true,\"kicker_ar\":\"ميثاق الصندوق\",\"kicker_en\":\"Fund Charter\",\"title_ar\":\"تنظيم عادل يحفظ صلة الرحم ويديم البركة\",\"title_en\":\"Fair Bylaws Protecting Kinship & Lasting Prosperity\",\"desc_ar\":\"يقوم الصندوق على مبادئ التكافل الإسلامي النقي، حيث يُقرض المحتاج دون اشتراط أي زيادة، وتُستثمر المدخرات في منافذ آمنة، ليكون سداً منيعاً يحمي الأسرة من نوائب الدهر.\",\"desc_en\":\"The Fund is built upon pure mutual solidarity, offering benevolent interest-free loans and securing family savings through structured, transparent governance.\",\"button_text_ar\":\"اللائحة والأنظمة\",\"button_text_en\":\"Terms & Bylaws\",\"button_url\":\"/page/terms\",\"items\":[{\"title_ar\":\"لا فوائد ربوية على الإطلاق\",\"title_en\":\"Zero Interest Financing\",\"body_ar\":\"جميع القروض ميسرة لوجه الله وصلة للرحم، مع مصاريف إدارية رمزية مقطوعة لتغطية التشغيل.\",\"body_en\":\"All loans are completely interest-free with nominal fixed administrative expenses.\"},{\"title_ar\":\"حفظ الحقوق وتوثيقها\",\"title_en\":\"Documented Financial Rights\",\"body_ar\":\"كل سهم ومبلغ سداد موثق بسجل مالي مركزي يحمي حقوق كل فرد وورثته مستقبلاً.\",\"body_en\":\"Every share and payment is permanently registered in an audited central ledger.\"},{\"title_ar\":\"لجنة إشرافية مستقلة\",\"title_en\":\"Elected Supervisory Committee\",\"body_ar\":\"لجنة من أعيان وخبراء العائلة تتولى إدارة الطلبات ومراجعة الميزانيات واعتماد التقارير السنوية.\",\"body_en\":\"A dedicated family council manages requests, audits budgets, and oversees disbursements.\"}]},{\"id\":\"hadith\",\"type\":\"hadith\",\"enabled\":true,\"title_ar\":\"صلة الرحم والبركة\",\"title_en\":\"Family Ties & Blessing\",\"quote_ar\":\"قال رسول الله صلى الله عليه وسلم: \\\"مَن سَرَّهُ أَنْ يُبْسَطَ لَهُ فِي رِزْقِهِ، وَأَنْ يُنْسَأَ لَهُ فِي أَثَرِهِ، فَلْيَصِلْ رَحِمَهُ.\\\"\",\"quote_en\":\"\\\"Whoever would like his provision to be abundant and his lifespan to be extended, let him maintain the ties of kinship.\\\"\",\"source_ar\":\"صحيح البخاري ومسلم\",\"source_en\":\"Sahih Al-Bukhari & Muslim\"},{\"id\":\"cta\",\"type\":\"cta\",\"enabled\":true,\"badge_ar\":\"بوابة العائلة الرقمية\",\"badge_en\":\"Digital Family Gateway\",\"title_ar\":\"انضم اليوم وابدأ في بناء مستقبلك المالي التكافلي\",\"title_en\":\"Join Today & Build Your Mutual Savings Future\",\"desc_ar\":\"سجل عضويتك للمشاركة في الاكتتاب، الاستفادة من القروض الحسنة بدون فوائد، ومتابعة حسابك بكل شفافية.\",\"desc_en\":\"Register now to participate in shares, benefit from 0% loans, and track your equity with transparency.\"}]');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `share_lots`
--

DROP TABLE IF EXISTS `share_lots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `share_lots` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `shares_count` int unsigned NOT NULL,
  `subscription_due_day` tinyint unsigned DEFAULT NULL COMMENT 'NULL falls back to the site default',
  `status` enum('active','merged') NOT NULL DEFAULT 'active',
  `source_request_id` int unsigned DEFAULT NULL COMMENT 'the share_request that created/merged this lot, for traceability',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_lots_member` (`member_id`),
  CONSTRAINT `fk_lots_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `share_lots`
--

LOCK TABLES `share_lots` WRITE;
/*!40000 ALTER TABLE `share_lots` DISABLE KEYS */;
/*!40000 ALTER TABLE `share_lots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `share_requests`
--

DROP TABLE IF EXISTS `share_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `share_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `type` enum('add','merge','cancel') NOT NULL,
  `shares_count` int unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(500) DEFAULT NULL,
  `reviewed_by` int unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_share_requests_member` (`member_id`),
  KEY `fk_share_requests_admin` (`reviewed_by`),
  CONSTRAINT `fk_share_requests_admin` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_share_requests_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `share_requests`
--

LOCK TABLES `share_requests` WRITE;
/*!40000 ALTER TABLE `share_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `share_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `category` enum('subscription','founding','loan_disbursement','loan_installment','loan_admin_fee') NOT NULL,
  `related_id` int unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `status` enum('completed') NOT NULL DEFAULT 'completed',
  `recorded_by` int unsigned DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_transactions_member` (`member_id`),
  KEY `fk_transactions_admin` (`recorded_by`),
  CONSTRAINT `fk_transactions_admin` FOREIGN KEY (`recorded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transactions_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-04 15:21:03
