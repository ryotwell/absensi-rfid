-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 19, 2025 at 02:56 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_rfid`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int NOT NULL,
  `uid` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(50) NOT NULL,
  `waktu` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `absen_pagi`
--

CREATE TABLE `absen_pagi` (
  `id` int NOT NULL,
  `uid` varchar(50) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `tanggal_absen` date DEFAULT (curdate()),
  `jam_absen` time DEFAULT (curtime()),
  `waktu` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `absen_pagi`
--

INSERT INTO `absen_pagi` (`id`, `uid`, `nama`, `jabatan`, `tanggal_absen`, `jam_absen`, `waktu`) VALUES
(57, 'a', 'a', 'a', '2025-10-14', '09:49:01', NULL),
(58, 'b', 'b', 'b', '2025-10-14', '06:49:01', NULL),
(59, 'a', 'a', 'a', '2025-10-15', '10:51:42', NULL),
(60, 'c', 'c', 'c', '2025-10-19', '16:25:40', NULL),
(61, 'd', 'd', 'd', '2025-10-20', '16:25:40', NULL),
(62, 'z', 'z', 'z', '2025-10-26', '22:31:46', NULL),
(63, 'x', 'x', 'x', '2025-10-28', '22:33:25', NULL),
(64, 'a', 'a', 'a', '2025-10-26', '09:49:01', NULL),
(65, 'b', 'b', 'b', '2025-10-26', '06:49:01', NULL),
(66, 'a', 'a', 'a', '2025-10-27', '10:51:42', NULL),
(67, 'c', 'c', 'c', '2025-10-26', '16:25:40', NULL),
(68, 'd', 'd', 'd', '2025-10-26', '16:25:40', NULL),
(69, 'z', 'z', 'z', '2025-10-26', '22:31:46', NULL),
(70, 'x', 'x', 'x', '2025-10-26', '22:33:25', NULL),
(71, 'a', 'a', 'a', '2025-10-27', '09:49:01', NULL),
(72, 'b', 'b', 'b', '2025-10-27', '06:49:01', NULL),
(73, 'c', 'c', 'c', '2025-10-27', '16:25:40', NULL),
(74, 'd', 'd', 'd', '2025-10-27', '16:25:40', NULL),
(75, 'z', 'z', 'z', '2025-10-27', '22:31:46', NULL),
(76, 'x', 'x', 'x', '2025-10-27', '22:33:25', NULL),
(80, 'q', 'q', 'q', '2025-11-01', '07:03:34', NULL),
(81, 'w', 'w', 'w', '2025-11-01', '09:03:34', NULL),
(82, 'q', 'q', 'q', '2025-10-19', '14:11:18', NULL),
(83, 'w', 'w', 'w', '2025-10-19', '15:11:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `absen_siang`
--

CREATE TABLE `absen_siang` (
  `id` int NOT NULL,
  `uid` varchar(50) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `tanggal_absen` date DEFAULT (curdate()),
  `jam_absen` time DEFAULT (curtime()),
  `waktu` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `absen_siang`
--

INSERT INTO `absen_siang` (`id`, `uid`, `nama`, `jabatan`, `tanggal_absen`, `jam_absen`, `waktu`) VALUES
(96, 'a', 'a', 'a', '2025-10-14', '06:50:05', NULL),
(97, 'b', 'b', 'b', '2025-10-14', '11:50:05', NULL),
(98, 'b', 'b', 'b', '2025-10-15', '11:50:05', NULL),
(99, 'c', 'c', 'c', '2025-10-19', '21:26:55', NULL),
(100, 'D349FFFE', 'H HASAN BASRI', 'KASI PEMERINTAHAN', '2025-10-16', '23:30:33', '2025-10-16 23:30:33'),
(101, 'D11CFB29', 'SAPRIADI, S.Pd', 'KAUR KEUANGAN', '2025-10-16', '23:31:00', '2025-10-16 23:31:00'),
(102, '4621AA87', 'HERMAN, SE', 'KASI PELAYANAN', '2025-10-16', '23:31:05', '2025-10-16 23:31:05'),
(103, '9644E687', 'ZULKARNAEN, SH', 'KEPALA DESA', '2025-10-16', '23:41:13', '2025-10-16 23:41:13'),
(104, '9644E687', 'ZULKARNAEN, SH', 'KEPALA DESA', '2025-10-17', '00:29:22', '2025-10-17 00:29:22'),
(105, 'D349FFFE', 'H HASAN BASRI', 'KASI PEMERINTAHAN', '2025-10-17', '00:29:32', '2025-10-17 00:29:32'),
(106, 'D11CFB29', 'SAPRIADI, S.Pd', 'KAUR KEUANGAN', '2025-10-17', '00:29:48', '2025-10-17 00:29:48'),
(107, '4621AA87', 'HERMAN, SE', 'KASI PELAYANAN', '2025-10-17', '00:29:52', '2025-10-17 00:29:52'),
(108, 'q', 'q', 'q', '2025-11-01', '14:06:28', NULL),
(109, 'w', 'w', 'w', '2025-11-01', '15:06:28', NULL),
(110, 'q', 'q', 'q', '2025-10-19', '08:10:21', NULL),
(111, 'w', 'w', 'w', '2025-10-19', '06:10:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(2, 'admin', '$2y$10$Ywp8eOpPOLcMwYjRwzFX0uaxWQ.8P4RiNSr360IVhYbjU6YI472oa'),
(3, 'a', 'a'),
(4, 'adit', '$2y$10$Xisto7igI5wVRpZLKCDkP.EfmNnRbYq1EqwJA930nRDgLz/Ht2w9y');

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id` int NOT NULL,
  `uid` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `tgl_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id`, `uid`, `nama`, `jabatan`, `tgl_daftar`) VALUES
(46, '4621AA87', 'HERMAN, SE', 'KASI PELAYANAN', '2025-10-12 12:44:45'),
(47, 'D11CFB29', 'SAPRIADI, S.Pd', 'KAUR KEUANGAN', '2025-10-12 12:44:58'),
(48, 'D349FFFE', 'H HASAN BASRI', 'KASI PEMERINTAHAN', '2025-10-12 12:45:19'),
(49, 'a', 'a', 'a', '2025-10-14 04:48:30'),
(50, 'b', 'b', 'b', '2025-10-14 04:48:37'),
(51, 'c', 'c', 'c', '2025-10-14 04:48:43'),
(52, 'd', 'd', 'd', '2025-10-14 04:48:48'),
(53, '10', '10', 'kepala desa', '2025-10-14 07:56:46'),
(54, '9644E687', 'ZULKARNAEN, SH', 'KEPALA DESA', '2025-10-16 15:40:23'),
(55, 'aaa', 'aa', 'aa', '2025-10-19 03:30:16'),
(56, 'bb', 'bb', 'bb', '2025-10-19 03:30:30'),
(57, 'q', 'q', 'q', '2025-10-19 04:02:38'),
(58, 'w', 'w', 'w', '2025-10-19 04:02:44'),
(59, 'e', 'e', 'e', '2025-10-19 04:02:52');

-- --------------------------------------------------------

--
-- Table structure for table `uid_terakhir`
--

CREATE TABLE `uid_terakhir` (
  `id` int NOT NULL,
  `uid` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `uid_terakhir`
--

INSERT INTO `uid_terakhir` (`id`, `uid`) VALUES
(1, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `absen_pagi`
--
ALTER TABLE `absen_pagi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `absen_siang`
--
ALTER TABLE `absen_siang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`);

--
-- Indexes for table `uid_terakhir`
--
ALTER TABLE `uid_terakhir`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `absen_pagi`
--
ALTER TABLE `absen_pagi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `absen_siang`
--
ALTER TABLE `absen_siang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `uid_terakhir`
--
ALTER TABLE `uid_terakhir`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
