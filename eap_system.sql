-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 13, 2026 at 10:58 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eap_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `description` text NOT NULL,
  `badge_text` varchar(50) DEFAULT NULL,
  `badge_class` varchar(50) DEFAULT 'bg-secondary',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `description`, `badge_text`, `badge_class`, `created_at`) VALUES
(1, 'Panel ID EAP-P-0008 status updated to', 'Approved', 'bg-success', '2026-01-11 17:13:38'),
(2, 'Panel ID EAP-P-0009 status updated to', 'Approved', 'bg-success', '2026-01-11 17:29:17'),
(3, 'Panel ID EAP-P-0010 status updated to', 'Approved', 'bg-success', '2026-01-11 17:56:26'),
(4, 'Panel ID EAP-P-0011 status updated to', 'Approved', 'bg-success', '2026-01-11 18:13:21'),
(5, 'Panel ID EAP-P-0012 status updated to', 'Approved', 'bg-success', '2026-01-11 18:29:59'),
(6, 'Panel ID EAP-P-0013 status updated to', 'Approved', 'bg-success', '2026-01-11 19:26:27'),
(7, 'New panel registered: imran', 'New', 'bg-primary', '2026-01-11 21:00:11'),
(8, 'Decision made for Panel EAP-P-0014', 'Rejected', 'bg-danger', '2026-01-11 21:12:33'),
(9, 'Decision made for Panel EAP-P-0015', 'Approved', 'bg-success', '2026-01-11 21:37:10'),
(10, 'New panel registered: ahmad', 'New', 'bg-primary', '2026-01-11 21:41:09'),
(11, 'Decision made for Panel EAP-P-0016', 'Approved', 'bg-success', '2026-01-11 21:46:04'),
(12, 'New panel registered: Zakaria', 'New', 'bg-primary', '2026-01-11 21:47:25'),
(13, 'Decision made for Panel EAP-P-0017', 'Approved', 'bg-success', '2026-01-11 21:49:33'),
(14, 'New panel registered: Pablo', 'New', 'bg-primary', '2026-01-11 21:53:04'),
(15, 'Decision made for Panel EAP-P-0018', 'Approved', 'bg-success', '2026-01-11 21:53:19'),
(16, 'New panel registered: Zakaria Ali', 'New', 'bg-primary', '2026-01-11 22:03:43'),
(17, 'Decision made for Panel EAP-P-0019', 'Approved', 'bg-success', '2026-01-11 22:03:55'),
(18, 'New panel registered: Haziq Hafiz', 'New', 'bg-primary', '2026-01-11 22:09:33'),
(19, 'Decision for EAP-P-20', 'Approved', 'bg-success', '2026-01-11 22:12:15'),
(20, 'New panel registered: Fahmi', 'New', 'bg-primary', '2026-01-11 22:15:21'),
(21, 'Decision for EAP-P-21', 'Approved', 'bg-success', '2026-01-11 22:15:44'),
(22, 'New panel registered: Hasbullah', 'New', 'bg-primary', '2026-01-11 22:22:12'),
(23, 'Decision made for Panel EAP-P-22', 'Approved', 'bg-success', '2026-01-11 22:22:29'),
(24, 'New panel registered: Farah', 'New', 'bg-primary', '2026-01-11 22:27:07'),
(25, 'Decision made for Panel EAP-P-23', 'Approved', 'bg-success', '2026-01-11 22:27:24'),
(26, 'New panel registered: Ahmad Zakaria', 'New', 'bg-primary', '2026-01-11 22:34:32'),
(27, 'Decision made for Panel EAP-P-24', 'Approved', 'bg-success', '2026-01-11 22:34:57'),
(28, 'New panel registered: Ahmad bin Ali', 'New', 'bg-primary', '2026-01-12 05:39:21'),
(29, 'Decision made for Panel EAP-P-25', 'Approved', 'bg-success', '2026-01-12 05:41:48'),
(30, 'New panel registered: Dr Sarah Aina', 'New', 'bg-primary', '2026-01-12 20:19:40'),
(31, 'Decision made for Panel EAP-P-26', 'Approved', 'bg-success', '2026-01-12 20:34:11'),
(32, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-13 07:42:53'),
(33, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-13 07:43:26'),
(34, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-13 07:43:41'),
(35, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-13 07:43:51'),
(36, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-13 07:49:07'),
(37, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-13 07:53:51'),
(38, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-13 08:08:32'),
(39, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-13 08:08:54'),
(40, 'New panel registered: Dr Sarah Aina', 'New', 'bg-primary', '2026-01-20 18:37:08'),
(41, 'Decision made for Panel EAP-P-27', 'Approved', 'bg-success', '2026-01-20 18:37:56'),
(42, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-01-20 18:50:28'),
(43, 'New panel registered: test', 'New', 'bg-primary', '2026-03-11 16:20:37'),
(44, 'Decision made for Panel EAP-P-28', 'Approved', 'bg-success', '2026-03-11 16:20:53'),
(45, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 16:53:19'),
(46, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:07:15'),
(47, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:12:03'),
(48, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:22:45'),
(49, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:22:54'),
(50, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:23:14'),
(51, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:23:47'),
(52, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:24:08'),
(53, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:29:03'),
(54, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:37:25'),
(55, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:37:45'),
(56, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:38:22'),
(57, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:49:48'),
(58, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-18 17:50:48'),
(59, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-03-25 07:20:16'),
(60, 'Evaluation submitted by Ahmad bin Ali', 'Evaluation', 'bg-info', '2026-03-27 07:20:37'),
(61, 'Evaluation submitted by Ahmad bin Ali', 'Evaluation', 'bg-info', '2026-03-27 07:27:44'),
(62, 'Evaluation submitted by Ahmad bin Ali', 'Evaluation', 'bg-info', '2026-03-27 07:40:50'),
(63, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-04-03 01:45:25'),
(64, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-04-09 12:51:51'),
(65, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-04-09 13:03:40'),
(66, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-04-09 13:03:50'),
(67, 'Evaluation submitted by Dr Sarah Aina', 'Evaluation', 'bg-info', '2026-04-09 13:06:55'),
(68, 'New panel registered: Abby Abadi', 'New', 'bg-primary', '2026-04-30 09:57:44'),
(69, 'Decision made for Panel EAP-P-29', 'Approved', 'bg-success', '2026-05-04 12:56:12'),
(70, 'New panel registered: Ahmad', 'New', 'bg-primary', '2026-05-05 18:56:15'),
(71, 'Decision made for Panel EAP-P-30', 'Approved', 'bg-success', '2026-05-05 18:57:24'),
(72, 'QA Announcement posted: \"Test Announcement\"', 'Announcement', 'bg-info', '2026-05-13 08:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `cqi_actions`
--

CREATE TABLE `cqi_actions` (
  `cqi_id` int(11) NOT NULL,
  `panel_id` int(11) DEFAULT NULL,
  `issue_title` varchar(200) NOT NULL,
  `issue_description` text DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `criteria_issues`
--

CREATE TABLE `criteria_issues` (
  `id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `issue_text` text NOT NULL,
  `pc_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remark` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `criteria_issues`
--

INSERT INTO `criteria_issues` (`id`, `criteria_id`, `issue_text`, `pc_comment`, `created_at`, `remark`) VALUES
(4, 63, 'EE suggest to relook on BCSS PEO on term of “Computer System Security industry” since Market analysis percentage of this industry in Malaysia is very limited.', '1. Have review with OBEE and SME in BCSS during Currifculum review in section level on the term of “Computer System Security Industry” and agreed with the suggestion. OBEE agreed to change the term “Computer System Security Industry” to “IT and Security industry”. PC will bring this matter to be presented next IAC meeting.', '2026-04-09 13:06:55', 'srtr'),
(5, 63, 'EE recommend to CTC section to explore Direct Master – from foundation to Master (express mode).', '', '2026-04-09 13:06:55', 'test'),
(6, 63, 'Increase expert skill and competence – professional certification – technical modules in the development of the curriculum, including education experts as appropriate.', '', '2026-04-09 13:06:55', ''),
(7, 64, 'EE suggest introducing latest technology in cybersecurity such as Blockchain and Security Analytics.', '', '2026-04-09 13:06:55', ''),
(8, 64, 'Seem student that from SPM/STPM have issue on follow the syllabus and always behind the student that from Diploma level.', NULL, '2026-04-09 13:06:55', NULL),
(9, 64, 'Opportunity for Improvement: Need to monitor student that from SPM/STPM that have issue to adapt the new understanding and environment of studying.', NULL, '2026-04-09 13:06:55', NULL),
(10, 65, 'Some of the selective student have issue on their EQ.', NULL, '2026-04-09 13:06:55', NULL),
(11, 65, 'Opportunity for Improvement: New student also need to selected based on their EQ. Need to do also EQ test before the student are fit to enter UniKL.', NULL, '2026-04-09 13:06:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `evaluation_date` date NOT NULL,
  `overall_comments` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pc_comment` text DEFAULT NULL,
  `pc_status` varchar(20) DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`id`, `panel_id`, `title`, `evaluation_date`, `overall_comments`, `status`, `created_at`, `pc_comment`, `pc_status`) VALUES
(48, 27, 'Mid Summer Evaluation', '2026-03-25', 'Need improvements', 'submitted', '2026-03-25 07:20:16', NULL, 'open'),
(49, 25, 'SENATE MEETING NO. 145 (6/2025)', '2026-04-04', 'Sure', 'submitted', '2026-03-27 07:20:37', 'sfs', 'closed'),
(52, 27, 'Mid term evaluation', '2026-04-04', 'Good', 'submitted', '2026-04-03 01:45:25', NULL, 'open'),
(56, 27, 'Mid Summer Evaluation', '2026-04-10', 'Opportunity for Improvement: New student also need to selected based on their EQ. Need to do also EQ test before the student are fit to enter UniKL.', 'submitted', '2026-04-09 13:06:55', NULL, 'open');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_criteria`
--

CREATE TABLE `evaluation_criteria` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `criteria_name` varchar(255) NOT NULL,
  `comments` text DEFAULT NULL,
  `pc_comment` text DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_criteria`
--

INSERT INTO `evaluation_criteria` (`id`, `evaluation_id`, `criteria_name`, `comments`, `pc_comment`, `remark`, `status`) VALUES
(47, 48, 'Student Asessment', 'Good', 'test', NULL, 'closed'),
(48, 48, 'Lab Work', 'Bad', 'hey', NULL, 'closed'),
(49, 49, 'PROGRAMME DESIGN AND DELIVERY', 'EE suggest to relook on BCSS PEO on term of “Computer System Security industry” since Market analysis percentage of this industry in Malaysia is very limited. ', 'tell', NULL, 'open'),
(50, 49, 'STUDENT SELECTION AND SUPPORT SERVICE', '1. Some of the selective student have issue on their EQ.  \r\n\r\nOpportunity for Improvement: New student also need to selected based on their EQ. Need to do also EQ test before the student are fit to enter UniKL. ', 'hey', NULL, 'open'),
(55, 52, 'Student Assessment', 'test', '', NULL, 'closed'),
(56, 52, 'Lab', 'Test', NULL, NULL, 'closed'),
(63, 56, 'PROGRAMME DESIGN AND DELIVERY', NULL, NULL, NULL, 'closed'),
(64, 56, 'STUDENT ASSESSMENT', NULL, NULL, NULL, 'open'),
(65, 56, 'STUDENT SELECTION AND SUPPORT SERVICE', NULL, NULL, NULL, 'closed');

-- --------------------------------------------------------

--
-- Table structure for table `panel_members`
--

CREATE TABLE `panel_members` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `panel_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `level` varchar(100) NOT NULL,
  `programme` varchar(100) NOT NULL,
  `qualification` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `last_visit_date` date DEFAULT NULL COMMENT 'Most recent PC visit date, kept in sync with panel_visit_history',
  `resume_path` varchar(255) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panel_members`
--

INSERT INTO `panel_members` (`id`, `username`, `password`, `panel_name`, `email`, `phone`, `level`, `programme`, `qualification`, `start_date`, `last_visit_date`, `resume_path`, `remarks`, `status`, `created_at`) VALUES
(3, NULL, NULL, 'Rasyad Saufi bin Tahir', NULL, NULL, 'Bachelor', 'Cybersecurity', 'PhD in Cybersecurity', '2026-03-12', NULL, 'uploads/resumes/Resume_1767054163_Rasyad_Saufi_bin_Tahir.pdf', 'Testing user\n\n[HEAD QA DECISION - 2025-12-30]: Approved', 'Approved', '2025-12-30 00:22:43'),
(4, NULL, NULL, 'Aiman bin Manap', NULL, NULL, 'Diploma', 'Software Engineering', 'Master in Computer Science Hons. Software Engineering', '2026-02-14', NULL, 'uploads/resumes/Resume_1767055012_Aiman_bin_Manap.pdf', 'Testing counter\n\n[HEAD QA DECISION - 2025-12-30]: Test', 'Approved', '2025-12-30 00:36:52'),
(5, NULL, NULL, 'Sopi', NULL, NULL, 'Bachelor', 'Cybersecurity', 'Phd in Cybersecurity', '2026-01-24', NULL, 'uploads/resumes/Resume_1767083441_Sopi.pdf', 'Testing\n\n[HEAD QA DECISION - 2025-12-30]: approved', 'Approved', '2025-12-30 08:30:41'),
(6, NULL, NULL, 'aiman', NULL, NULL, 'Diploma', 'Software Engineering', 'Phd test', '2026-02-27', NULL, 'uploads/resumes/Resume_1767083487_aiman.pdf', 'test reject\n\n[HEAD QA DECISION - 2025-12-30]: rejected', 'Rejected', '2025-12-30 08:31:27'),
(7, NULL, NULL, 'Ali bin Abu', NULL, NULL, 'Bachelor', 'Software Engineering', 'Phd in Software Engineering', '2026-02-14', NULL, 'uploads/resumes/Resume_1767083843_Ali_bin_Abu.pdf', 'Testing\n\n[HEAD QA DECISION - 2025-12-30]: Testing approved', 'Approved', '2025-12-30 08:37:23'),
(8, NULL, NULL, 'Mohamad Adam bin Shah', NULL, NULL, 'Master', 'Data Science', 'PhD in Cybersecurity', '2026-03-20', NULL, 'uploads/resumes/Resume_1768151560_Mohamad_Adam_bin_Shah.pdf', '\n\n[HEAD QA DECISION - 2026-01-11]: Test recent activity', 'Approved', '2026-01-11 17:12:40'),
(9, NULL, NULL, 'Azlan bin Zainal', NULL, NULL, 'Bachelor Degree', 'Bachelor of Information Technology (Hons) in Internet of Things (IoT)', 'Phd test', '2026-04-18', NULL, 'uploads/resumes/Resume_1768152519_Azlan_bin_Zainal.pdf', 'test new updated list function\n\n[HEAD QA DECISION - 2026-01-11]: test updated list function on db', 'Approved', '2026-01-11 17:28:39'),
(10, NULL, NULL, 'Ahmad', NULL, NULL, 'Diploma', 'Diploma in Networking Technology', 'PhD in Cybersecurity', '2026-01-25', NULL, 'uploads/resumes/Resume_1768154161_Ahmad.pdf', 'test QA page notification\n\n[HEAD QA DECISION - 2026-01-11]: test confirmed', 'Approved', '2026-01-11 17:56:01'),
(11, NULL, 'panel11test', 'Ariff bin Ali', NULL, NULL, 'Bachelor Degree', 'Bachelor of Information Technology (Hons) in Internet of Things (IoT)', 'phd in Software Engineering', '2026-04-25', NULL, 'uploads/resumes/Resume_1768155181_Ariff_bin_Ali.pdf', 'test panel login\n\n[HEAD QA DECISION - 2026-01-11]: proceed', 'Approved', '2026-01-11 18:13:01'),
(12, NULL, 'testpanel', 'Imran bin Hasli', NULL, NULL, 'Bachelor Degree', 'Bachelor of Information Technology (Hons) in Software Engineering', 'phd in Software Engineering', '2026-01-23', NULL, 'uploads/resumes/Resume_1768156180_Imran_bin_Hasli.pdf', 'test panel profile\n\n[HEAD QA DECISION - 2026-01-11]: proceed', 'Approved', '2026-01-11 18:29:40'),
(13, NULL, 'paneltest123', 'Ali bin Ahmad abu', NULL, NULL, 'Doctorate', 'Doctor of Philosophy (Information Technology)', 'phd in Cybersecurity', '2026-01-31', NULL, 'uploads/resumes/Resume_1768159558_Ali_bin_Ahmad_abu.pdf', '\n\n[HEAD QA DECISION - 2026-01-11]: proceed', 'Approved', '2026-01-11 19:25:58'),
(14, NULL, '123456', 'test10', NULL, NULL, 'Bachelor Degree', 'Bachelor of Information Technology (Hons) in Internet of Things (IoT)', 'phD in Cybersecurity', '2026-01-28', NULL, 'uploads/resumes/Resume_1768160016_test10.pdf', '\n\n[HQA DECISION - 2026-01-11 22:12]: reject', 'Rejected', '2026-01-11 19:33:36'),
(15, NULL, '123456', 'imran', 'mohamadimran5125@gmail.com', '01124068169', 'Master\\\'s Degree', 'Master of Information Technology', 'phd in Software Engineering', '2026-02-14', NULL, 'uploads/resumes/Resume_1768165211_imran.pdf', '\n\n[HQA DECISION - 2026-01-11 22:37]: test email', 'Approved', '2026-01-11 21:00:11'),
(16, NULL, '123456', 'ahmad', 'mohamadimran5125@gmail.com', '01170705729', 'Diploma', 'Diploma in Information Technology', 'phd in Software Engineering', '2026-01-15', NULL, 'uploads/resumes/Resume_1768167669_ahmad.pdf', '\n\n[HQA DECISION - 2026-01-11 22:46]: test email', 'Approved', '2026-01-11 21:41:09'),
(17, NULL, '123456', 'Zakaria', 'lord.hydr4@gmail.com', '01170705729', 'Diploma', 'Diploma in Networking Technology', 'phd in Cybersecurity', '2026-02-15', NULL, 'uploads/resumes/Resume_1768168045_Zakaria.pdf', '\n\n[HQA DECISION - 2026-01-11 22:49]: test email', 'Approved', '2026-01-11 21:47:25'),
(18, NULL, '123456', 'Pablo', 'lord.hydr4@gmail.com', '01124068169', 'Bachelor Degree', 'Bachelor of Computer Engineering Technology (Hons) in Networking Systems', 'phd in Software Engineering', '2026-03-13', NULL, 'uploads/resumes/Resume_1768168384_Pablo.pdf', '\n\n[HQA DECISION - 2026-01-11 22:53]: test', 'Approved', '2026-01-11 21:53:04'),
(19, NULL, '123456', 'Zakaria Ali', 'lord.hydr4@gmail.com', '01170705729', 'Diploma', 'Diploma in Information Technology', 'phd in Software Engineering', '2026-03-08', NULL, 'uploads/resumes/Resume_1768169023_Zakaria_Ali.pdf', '\n\n[HQA DECISION - 2026-01-11 23:03]: test2', 'Approved', '2026-01-11 22:03:43'),
(20, NULL, '123456', 'Haziq Hafiz', 'lord.hydr4@gmail.com', '01124068169', 'Bachelor Degree', 'Bachelor of Information Technology (Hons) in Software Engineering', 'phd in Software Engineering', '2026-03-13', NULL, 'uploads/resumes/Resume_1768169373_Haziq_Hafiz.pdf', '\n\n[HQA DECISION - 2026-01-11 23:12]: test 3', 'Approved', '2026-01-11 22:09:33'),
(21, NULL, '123456', 'Fahmi', 'mohamadimran5125@gmail.com', '01170705729', 'Bachelor Degree', 'Bachelor of Information Technology (Hons) in Computer System Security', 'phD in Cybersecurity', '2026-03-07', NULL, 'uploads/resumes/Resume_1768169721_Fahmi.pdf', '\n\n[HQA DECISION - 2026-01-11 23:15]: test 4', 'Approved', '2026-01-11 22:15:21'),
(22, NULL, '123456', 'Hasbullah', 'lord.hydr4@gmail.com', '01170705729', 'Diploma', 'Diploma in Information Technology', 'phd in Software Engineering', '2026-03-20', NULL, 'uploads/resumes/Resume_1768170132_Hasbullah.pdf', '\n\n[HQA DECISION - 2026-01-11 23:22]: test 6', 'Approved', '2026-01-11 22:22:12'),
(23, NULL, 'testing123', 'Farah', 'mohamadimran5125@gmail.com', '01170705729', 'Bachelor Degree', 'Bachelor of Information Technology (Hons) in Software Engineering', 'phd in Software Engineering', '2026-03-14', NULL, 'uploads/resumes/Resume_1768170427_Farah.pdf', '\n\n[HQA DECISION - 2026-01-11 23:27]: test email', 'Approved', '2026-01-11 22:27:07'),
(24, NULL, 'testing123', 'Ahmad Zakaria', 'mohamadimran5125@gmail.com', '01124068169', 'Diploma', 'Diploma in Information Technology', 'phd in Software Engineering', '2026-03-13', NULL, 'uploads/resumes/Resume_1768170872_Ahmad_Zakaria.pdf', '\n\n[HQA DECISION - 2026-01-11 23:34]: testing', 'Approved', '2026-01-11 22:34:32'),
(25, NULL, 'testing123', 'Ahmad bin Ali', 'mohamadimran5125@gmail.com', '01170705729', 'Diploma', 'Diploma in Information Technology', 'phd in Software Engineering', '2026-03-13', NULL, 'uploads/resumes/Resume_1768196361_Ahmad_bin_Ali.pdf', '\n\n[HQA DECISION - 2026-01-12 06:41]: Proceed with this panel', 'Approved', '2026-01-12 05:39:21'),
(26, NULL, 'sarah123', 'Dr Sarah Aina', 'sarahaina10@gmail.com', '0129307418', 'Master\\\'s Degree', 'Master in Computer Science', 'PhD in Software Engineering', '2026-01-28', NULL, 'uploads/resumes/Resume_1768249180_Dr_Sarah_Aina.pdf', 'test\n\n[HQA DECISION - 2026-01-12 21:34]: good', 'Approved', '2026-01-12 20:19:40'),
(27, NULL, 'sarah1234', 'Dr Sarah Aina', 'sarahaina10@gmail.com', '0129307418', 'Bachelor Degree', 'Bachelor of Multimedia Technology (Hons) in Interactive Multimedia Design', 'PhD in Computer Science', '2026-02-21', NULL, 'uploads/resumes/Resume_1768934228_Dr_Sarah_Aina.pdf', 'I would like to nominate this panel \n\n[HQA DECISION - 2026-01-20 19:37]: proceed', 'Approved', '2026-01-20 18:37:08'),
(28, NULL, 'testing', 'test', 'test@gmail.com', 't', 'Diploma', 'Diploma in Information Technology', 'et', '2026-03-12', NULL, 'uploads/resumes/Resume_1773246037_test.pdf', '\n\n[HQA DECISION - 2026-03-11 17:20]: yes', 'Approved', '2026-03-11 16:20:37'),
(29, NULL, 'abi123', 'Abby Abadi', 'sarahaina10@gmail.com', '0129307418', 'Bachelor Degree', 'Bachelor of Information Technology (Hons) in Software Engineering', 'PhD in Software Engineering', '2026-04-30', NULL, 'uploads/resumes/Resume_1777543064_Abby_Abadi.pdf', 'Please accept her\n\n[HQA DECISION - 2026-05-04 14:56]: Approved', 'Approved', '2026-04-30 09:57:44'),
(30, NULL, 'Testing123', 'Ahmad', 'lord.hydr4@gmail.com', '01170705729', 'Diploma', 'Bachelor of Multimedia Technology (Hons) in Interactive Multimedia Design', 'master Multimedia', '2026-05-23', '2026-05-05', 'uploads/resumes/Resume_1778007375_Ahmad.pdf', '\n\n[HQA DECISION - 2026-05-05 20:57]: test', 'Approved', '2026-05-05 18:56:15');

-- --------------------------------------------------------

--
-- Table structure for table `panel_visit_history`
--

CREATE TABLE `panel_visit_history` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `recorded_by` varchar(100) NOT NULL COMMENT 'Username of the PC who recorded this',
  `note` text DEFAULT NULL COMMENT 'Optional note for the visit',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `panel_visit_history`
--

INSERT INTO `panel_visit_history` (`id`, `panel_id`, `visit_date`, `recorded_by`, `note`, `created_at`) VALUES
(1, 30, '2026-05-05', 'pc_mm', '', '2026-05-13 16:01:08');

-- --------------------------------------------------------

--
-- Table structure for table `qa_announcements`
--

CREATE TABLE `qa_announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `priority` enum('normal','important','urgent') DEFAULT 'normal',
  `posted_by` varchar(100) NOT NULL,
  `poster_name` varchar(150) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_announcements`
--

INSERT INTO `qa_announcements` (`id`, `title`, `body`, `priority`, `posted_by`, `poster_name`, `is_deleted`, `created_at`) VALUES
(1, 'Test Announcement', 'testing 123', 'normal', 'head_QA', 'Head Of QA', 1, '2026-05-13 16:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `qa_submission_status`
--

CREATE TABLE `qa_submission_status` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `latest_visit_date` date DEFAULT NULL,
  `iac_irpc` varchar(100) DEFAULT NULL,
  `uac_urpc` varchar(100) DEFAULT NULL,
  `senate` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qa_submission_status`
--

INSERT INTO `qa_submission_status` (`id`, `evaluation_id`, `latest_visit_date`, `iac_irpc`, `uac_urpc`, `senate`, `created_at`, `updated_at`) VALUES
(1, 56, '2026-03-26', 'IAC No.17(1/2024)', 'UAC', '', '2026-04-26 02:02:17', '2026-04-26 02:33:16'),
(4, 52, '0000-00-00', 't', 't', 't', '2026-04-26 02:05:15', '2026-04-26 02:06:11'),
(11, 48, '0000-00-00', '', '', '', '2026-05-04 14:29:25', '2026-05-04 14:29:25');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `panel_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `programme` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `name`, `password`, `full_name`, `role`, `programme`, `created_at`) VALUES
(1, 'eap_pc', 'Programme Coordinator', 'admin123', 'System Administrator', 'admin', NULL, '2025-11-25 00:26:14'),
(4, 'head_QA', 'Head Of QA', 'Master123', 'QA Admin', 'Head QA', NULL, '2025-12-29 18:39:11'),
(5, 'pc_se', 'PC Software Engineering', 'pc123', 'Programme Coordinator SE', 'PC', 'Bachelor of Information Technology (Hons) in Software Engineering', '2026-05-04 14:43:14'),
(6, 'pc_iot', 'PC Internet of Things', 'pc123', 'Programme Coordinator IoT', 'PC', 'Bachelor of Information Technology (Hons) in Internet of Things (IoT)', '2026-05-04 14:43:14'),
(7, 'pc_cyber', 'PC Cybersecurity', 'pc123', 'Programme Coordinator Cyber', 'PC', 'Bachelor of Information Technology (Hons) in Cybersecurity', '2026-05-04 14:43:14'),
(8, 'pc_mm', 'PC Multimedia', 'pc123', 'Programme Coordinator MM', 'PC', 'Bachelor of Multimedia Technology (Hons) in Interactive Multimedia Design', '2026-05-04 14:43:14'),
(9, 'pc_dip', 'PC Diploma IT', 'pc123', 'Programme Coordinator DIT', 'PC', 'Diploma in Information Technology', '2026-05-04 14:43:14'),
(10, 'pc_phd', 'PC PhD IT', 'pc123', 'Programme Coordinator PhD', 'PC', 'Doctor of Philosophy (Information Technology)', '2026-05-04 14:43:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cqi_actions`
--
ALTER TABLE `cqi_actions`
  ADD PRIMARY KEY (`cqi_id`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Indexes for table `criteria_issues`
--
ALTER TABLE `criteria_issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criteria_id` (`criteria_id`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Indexes for table `evaluation_criteria`
--
ALTER TABLE `evaluation_criteria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluation_id` (`evaluation_id`);

--
-- Indexes for table `panel_members`
--
ALTER TABLE `panel_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `panel_visit_history`
--
ALTER TABLE `panel_visit_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_panel_id` (`panel_id`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- Indexes for table `qa_announcements`
--
ALTER TABLE `qa_announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qa_submission_status`
--
ALTER TABLE `qa_submission_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `evaluation_id` (`evaluation_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `panel_id` (`panel_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `cqi_actions`
--
ALTER TABLE `cqi_actions`
  MODIFY `cqi_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `criteria_issues`
--
ALTER TABLE `criteria_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `evaluation_criteria`
--
ALTER TABLE `evaluation_criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `panel_members`
--
ALTER TABLE `panel_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `panel_visit_history`
--
ALTER TABLE `panel_visit_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `qa_announcements`
--
ALTER TABLE `qa_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `qa_submission_status`
--
ALTER TABLE `qa_submission_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `criteria_issues`
--
ALTER TABLE `criteria_issues`
  ADD CONSTRAINT `criteria_issues_ibfk_1` FOREIGN KEY (`criteria_id`) REFERENCES `evaluation_criteria` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `panel_members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_criteria`
--
ALTER TABLE `evaluation_criteria`
  ADD CONSTRAINT `evaluation_criteria_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `panel_visit_history`
--
ALTER TABLE `panel_visit_history`
  ADD CONSTRAINT `fk_visit_panel` FOREIGN KEY (`panel_id`) REFERENCES `panel_members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qa_submission_status`
--
ALTER TABLE `qa_submission_status`
  ADD CONSTRAINT `qa_submission_status_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
