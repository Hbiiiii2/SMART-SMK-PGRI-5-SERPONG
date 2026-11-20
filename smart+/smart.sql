-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 10, 2026 at 03:16 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi_guru`
--

CREATE TABLE `absensi_guru` (
  `id` bigint UNSIGNED NOT NULL,
  `guru_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `status` enum('Hadir','Terlambat','Alpha','Sakit','Izin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Alpha',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi_guru`
--

INSERT INTO `absensi_guru` (`id`, `guru_id`, `tanggal`, `jam_masuk`, `jam_keluar`, `status`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-07-10', '21:16:55', NULL, 'Terlambat', NULL, '2026-07-10 14:16:55', '2026-07-10 14:16:55');

-- --------------------------------------------------------

--
-- Table structure for table `absensi_siswa`
--

CREATE TABLE `absensi_siswa` (
  `id` bigint UNSIGNED NOT NULL,
  `siswa_id` bigint UNSIGNED NOT NULL,
  `jadwal_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Hadir','Terlambat','Alfa','Sakit','Izin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `diedit_oleh` bigint UNSIGNED DEFAULT NULL,
  `alasan_perubahan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi_siswa`
--

INSERT INTO `absensi_siswa` (`id`, `siswa_id`, `jadwal_id`, `tanggal`, `status`, `jam_masuk`, `jam_keluar`, `keterangan`, `diedit_oleh`, `alasan_perubahan`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-07-10', 'Hadir', '21:13:49', NULL, NULL, NULL, NULL, '2026-07-10 14:13:49', '2026-07-10 14:13:49');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('smart-cache-admin_dashboard_2026-07-10', 'a:8:{s:10:\"guru_count\";i:1;s:11:\"siswa_count\";i:1;s:10:\"rfid_count\";i:2;s:18:\"tahun_ajaran_aktif\";s:9:\"2024/2025\";s:19:\"guru_hadir_hari_ini\";i:1;s:20:\"siswa_hadir_hari_ini\";i:1;s:19:\"siswa_alfa_hari_ini\";i:0;s:21:\"absensi_siswa_terbaru\";O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:1:{i:0;O:23:\"App\\Models\\AbsensiSiswa\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:13:\"absensi_siswa\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:1;s:8:\"siswa_id\";i:1;s:9:\"jadwal_id\";i:1;s:7:\"tanggal\";s:10:\"2026-07-10\";s:6:\"status\";s:5:\"Hadir\";s:9:\"jam_masuk\";s:8:\"21:13:49\";s:10:\"jam_keluar\";N;s:10:\"keterangan\";N;s:11:\"diedit_oleh\";N;s:16:\"alasan_perubahan\";N;s:10:\"created_at\";s:19:\"2026-07-10 21:13:49\";s:10:\"updated_at\";s:19:\"2026-07-10 21:13:49\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:1;s:8:\"siswa_id\";i:1;s:9:\"jadwal_id\";i:1;s:7:\"tanggal\";s:10:\"2026-07-10\";s:6:\"status\";s:5:\"Hadir\";s:9:\"jam_masuk\";s:8:\"21:13:49\";s:10:\"jam_keluar\";N;s:10:\"keterangan\";N;s:11:\"diedit_oleh\";N;s:16:\"alasan_perubahan\";N;s:10:\"created_at\";s:19:\"2026-07-10 21:13:49\";s:10:\"updated_at\";s:19:\"2026-07-10 21:13:49\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:7:\"tanggal\";s:4:\"date\";s:9:\"jam_masuk\";s:12:\"datetime:H:i\";s:10:\"jam_keluar\";s:12:\"datetime:H:i\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:2:{s:5:\"siswa\";O:16:\"App\\Models\\Siswa\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"siswa\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:13:{s:2:\"id\";i:1;s:7:\"user_id\";i:5;s:4:\"nisn\";s:14:\"00987654321001\";s:12:\"nama_lengkap\";s:19:\"Ahmad Akhtar Rayyan\";s:13:\"jenis_kelamin\";s:1:\"L\";s:13:\"tanggal_lahir\";s:10:\"2012-11-02\";s:6:\"alamat\";s:24:\"Griya Serpong Kademangan\";s:10:\"no_telepon\";s:1:\"-\";s:4:\"foto\";N;s:5:\"kelas\";s:1:\"X\";s:15:\"tahun_ajaran_id\";i:3;s:10:\"created_at\";s:19:\"2026-07-10 21:13:24\";s:10:\"updated_at\";s:19:\"2026-07-10 21:13:24\";}s:11:\"\0*\0original\";a:13:{s:2:\"id\";i:1;s:7:\"user_id\";i:5;s:4:\"nisn\";s:14:\"00987654321001\";s:12:\"nama_lengkap\";s:19:\"Ahmad Akhtar Rayyan\";s:13:\"jenis_kelamin\";s:1:\"L\";s:13:\"tanggal_lahir\";s:10:\"2012-11-02\";s:6:\"alamat\";s:24:\"Griya Serpong Kademangan\";s:10:\"no_telepon\";s:1:\"-\";s:4:\"foto\";N;s:5:\"kelas\";s:1:\"X\";s:15:\"tahun_ajaran_id\";i:3;s:10:\"created_at\";s:19:\"2026-07-10 21:13:24\";s:10:\"updated_at\";s:19:\"2026-07-10 21:13:24\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:1:{s:13:\"tanggal_lahir\";s:4:\"date\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:4:\"user\";O:15:\"App\\Models\\User\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"users\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:10:{s:2:\"id\";i:5;s:4:\"name\";s:19:\"Ahmad Akhtar Rayyan\";s:5:\"email\";s:23:\"3091375841@smart.sch.id\";s:17:\"email_verified_at\";N;s:8:\"password\";s:60:\"$2y$12$41o8ZuxuU6i2x8FZALrBTejpogj45WUNu1DbP9fgEYkVrAlj8BixG\";s:4:\"role\";s:5:\"siswa\";s:9:\"is_active\";i:1;s:14:\"remember_token\";N;s:10:\"created_at\";s:19:\"2026-07-10 21:13:24\";s:10:\"updated_at\";s:19:\"2026-07-10 21:13:24\";}s:11:\"\0*\0original\";a:10:{s:2:\"id\";i:5;s:4:\"name\";s:19:\"Ahmad Akhtar Rayyan\";s:5:\"email\";s:23:\"3091375841@smart.sch.id\";s:17:\"email_verified_at\";N;s:8:\"password\";s:60:\"$2y$12$41o8ZuxuU6i2x8FZALrBTejpogj45WUNu1DbP9fgEYkVrAlj8BixG\";s:4:\"role\";s:5:\"siswa\";s:9:\"is_active\";i:1;s:14:\"remember_token\";N;s:10:\"created_at\";s:19:\"2026-07-10 21:13:24\";s:10:\"updated_at\";s:19:\"2026-07-10 21:13:24\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:17:\"email_verified_at\";s:8:\"datetime\";s:8:\"password\";s:6:\"hashed\";s:9:\"is_active\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:2:{i:0;s:8:\"password\";i:1;s:14:\"remember_token\";}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:5:\"email\";i:2;s:8:\"password\";i:3;s:4:\"role\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:19:\"\0*\0authPasswordName\";s:8:\"password\";s:20:\"\0*\0rememberTokenName\";s:14:\"remember_token\";}}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:10:{i:0;s:7:\"user_id\";i:1;s:4:\"nisn\";i:2;s:12:\"nama_lengkap\";i:3;s:13:\"jenis_kelamin\";i:4;s:5:\"kelas\";i:5;s:13:\"tanggal_lahir\";i:6;s:6:\"alamat\";i:7;s:10:\"no_telepon\";i:8;s:4:\"foto\";i:9;s:15:\"tahun_ajaran_id\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}s:6:\"jadwal\";O:17:\"App\\Models\\Jadwal\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:6:\"jadwal\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:1;s:7:\"guru_id\";i:1;s:17:\"mata_pelajaran_id\";i:1;s:15:\"tahun_ajaran_id\";i:3;s:5:\"kelas\";s:1:\"X\";s:4:\"hari\";s:5:\"Jumat\";s:9:\"jam_mulai\";s:8:\"21:00:00\";s:11:\"jam_selesai\";s:8:\"23:00:00\";s:9:\"lokasi_id\";i:1;s:10:\"created_at\";s:19:\"2026-07-10 21:12:56\";s:10:\"updated_at\";s:19:\"2026-07-10 21:12:56\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:1;s:7:\"guru_id\";i:1;s:17:\"mata_pelajaran_id\";i:1;s:15:\"tahun_ajaran_id\";i:3;s:5:\"kelas\";s:1:\"X\";s:4:\"hari\";s:5:\"Jumat\";s:9:\"jam_mulai\";s:8:\"21:00:00\";s:11:\"jam_selesai\";s:8:\"23:00:00\";s:9:\"lokasi_id\";i:1;s:10:\"created_at\";s:19:\"2026-07-10 21:12:56\";s:10:\"updated_at\";s:19:\"2026-07-10 21:12:56\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:13:\"mataPelajaran\";O:24:\"App\\Models\\MataPelajaran\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:14:\"mata_pelajaran\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:1;s:4:\"kode\";s:3:\"MTK\";s:4:\"nama\";s:10:\"Matematika\";s:9:\"deskripsi\";s:25:\"Mata pelajaran Matematika\";s:10:\"created_at\";s:19:\"2026-07-10 12:50:41\";s:10:\"updated_at\";s:19:\"2026-07-10 12:50:41\";}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:1;s:4:\"kode\";s:3:\"MTK\";s:4:\"nama\";s:10:\"Matematika\";s:9:\"deskripsi\";s:25:\"Mata pelajaran Matematika\";s:10:\"created_at\";s:19:\"2026-07-10 12:50:41\";s:10:\"updated_at\";s:19:\"2026-07-10 12:50:41\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:4:\"kode\";i:1;s:4:\"nama\";i:2;s:9:\"deskripsi\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:7:\"guru_id\";i:1;s:17:\"mata_pelajaran_id\";i:2;s:15:\"tahun_ajaran_id\";i:3;s:5:\"kelas\";i:4;s:4:\"hari\";i:5;s:9:\"jam_mulai\";i:6;s:11:\"jam_selesai\";i:7;s:9:\"lokasi_id\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"siswa_id\";i:1;s:9:\"jadwal_id\";i:2;s:7:\"tanggal\";i:3;s:6:\"status\";i:4;s:9:\"jam_masuk\";i:5;s:10:\"jam_keluar\";i:6;s:10:\"keterangan\";i:7;s:11:\"diedit_oleh\";i:8;s:16:\"alasan_perubahan\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}}', 1783696394),
('smart-cache-count_guru', 'i:1;', 1783696394),
('smart-cache-count_rfid_active', 'i:2;', 1783696394),
('smart-cache-count_siswa', 'i:1;', 1783696394),
('smart-cache-live_stats_2026-07-10', 'a:7:{s:5:\"hadir\";i:1;s:9:\"terlambat\";i:0;s:4:\"alfa\";i:0;s:5:\"sakit\";i:0;s:4:\"izin\";i:0;s:5:\"total\";i:1;s:10:\"guru_hadir\";i:1;}', 1783693983),
('smart-cache-sidebar_menu_admin_sekolah', 'a:12:{i:0;a:3:{s:4:\"icon\";s:9:\"dashboard\";s:4:\"name\";s:9:\"Dashboard\";s:4:\"path\";s:16:\"/admin/dashboard\";}i:1;a:3:{s:4:\"icon\";s:8:\"calendar\";s:4:\"name\";s:12:\"Tahun Ajaran\";s:4:\"path\";s:19:\"/admin/tahun-ajaran\";}i:2;a:3:{s:4:\"icon\";s:4:\"guru\";s:4:\"name\";s:4:\"Guru\";s:4:\"path\";s:11:\"/admin/guru\";}i:3;a:3:{s:4:\"icon\";s:5:\"siswa\";s:4:\"name\";s:5:\"Siswa\";s:4:\"path\";s:12:\"/admin/siswa\";}i:4;a:3:{s:4:\"icon\";s:4:\"book\";s:4:\"name\";s:14:\"Mata Pelajaran\";s:4:\"path\";s:21:\"/admin/mata-pelajaran\";}i:5;a:3:{s:4:\"icon\";s:6:\"jadwal\";s:4:\"name\";s:16:\"Jadwal Pelajaran\";s:4:\"path\";s:13:\"/admin/jadwal\";}i:6;a:3:{s:4:\"icon\";s:8:\"location\";s:4:\"name\";s:6:\"Lokasi\";s:4:\"path\";s:13:\"/admin/lokasi\";}i:7;a:3:{s:4:\"icon\";s:4:\"rfid\";s:4:\"name\";s:10:\"Kartu RFID\";s:4:\"path\";s:11:\"/admin/rfid\";}i:8;a:3:{s:4:\"icon\";s:8:\"settings\";s:4:\"name\";s:18:\"Pengaturan Sekolah\";s:4:\"path\";s:25:\"/admin/pengaturan-sekolah\";}i:9;a:3:{s:4:\"icon\";s:6:\"report\";s:4:\"name\";s:17:\"Laporan Kehadiran\";s:4:\"path\";s:14:\"/admin/laporan\";}i:10;a:3:{s:4:\"icon\";s:7:\"holiday\";s:4:\"name\";s:10:\"Hari Libur\";s:4:\"path\";s:17:\"/admin/hari-libur\";}i:11;a:3:{s:4:\"icon\";s:8:\"kalender\";s:4:\"name\";s:17:\"Kalender Akademik\";s:4:\"path\";s:9:\"/kalender\";}}', 1783696597),
('smart-cache-sidebar_menu_guru', 'a:5:{i:0;a:3:{s:4:\"icon\";s:9:\"dashboard\";s:4:\"name\";s:9:\"Dashboard\";s:4:\"path\";s:15:\"/guru/dashboard\";}i:1;a:3:{s:4:\"icon\";s:7:\"absensi\";s:4:\"name\";s:13:\"Absensi Siswa\";s:4:\"path\";s:13:\"/guru/absensi\";}i:2;a:3:{s:4:\"icon\";s:6:\"jadwal\";s:4:\"name\";s:11:\"Jadwal Saya\";s:4:\"path\";s:12:\"/guru/jadwal\";}i:3;a:3:{s:4:\"icon\";s:6:\"report\";s:4:\"name\";s:17:\"Laporan Kehadiran\";s:4:\"path\";s:13:\"/guru/laporan\";}i:4;a:3:{s:4:\"icon\";s:8:\"kalender\";s:4:\"name\";s:17:\"Kalender Akademik\";s:4:\"path\";s:9:\"/kalender\";}}', 1783696650),
('smart-cache-sidebar_menu_super_admin', 'a:14:{i:0;a:3:{s:4:\"icon\";s:9:\"dashboard\";s:4:\"name\";s:9:\"Dashboard\";s:4:\"path\";s:16:\"/admin/dashboard\";}i:1;a:3:{s:4:\"icon\";s:8:\"calendar\";s:4:\"name\";s:12:\"Tahun Ajaran\";s:4:\"path\";s:19:\"/admin/tahun-ajaran\";}i:2;a:3:{s:4:\"icon\";s:4:\"guru\";s:4:\"name\";s:4:\"Guru\";s:4:\"path\";s:11:\"/admin/guru\";}i:3;a:3:{s:4:\"icon\";s:5:\"siswa\";s:4:\"name\";s:5:\"Siswa\";s:4:\"path\";s:12:\"/admin/siswa\";}i:4;a:3:{s:4:\"icon\";s:4:\"book\";s:4:\"name\";s:14:\"Mata Pelajaran\";s:4:\"path\";s:21:\"/admin/mata-pelajaran\";}i:5;a:3:{s:4:\"icon\";s:6:\"jadwal\";s:4:\"name\";s:16:\"Jadwal Pelajaran\";s:4:\"path\";s:13:\"/admin/jadwal\";}i:6;a:3:{s:4:\"icon\";s:8:\"location\";s:4:\"name\";s:6:\"Lokasi\";s:4:\"path\";s:13:\"/admin/lokasi\";}i:7;a:3:{s:4:\"icon\";s:4:\"rfid\";s:4:\"name\";s:10:\"Kartu RFID\";s:4:\"path\";s:11:\"/admin/rfid\";}i:8;a:3:{s:4:\"icon\";s:8:\"settings\";s:4:\"name\";s:18:\"Pengaturan Sekolah\";s:4:\"path\";s:25:\"/admin/pengaturan-sekolah\";}i:9;a:3:{s:4:\"icon\";s:6:\"report\";s:4:\"name\";s:17:\"Laporan Kehadiran\";s:4:\"path\";s:14:\"/admin/laporan\";}i:10;a:3:{s:4:\"icon\";s:7:\"holiday\";s:4:\"name\";s:10:\"Hari Libur\";s:4:\"path\";s:17:\"/admin/hari-libur\";}i:11;a:3:{s:4:\"icon\";s:8:\"kalender\";s:4:\"name\";s:17:\"Kalender Akademik\";s:4:\"path\";s:9:\"/kalender\";}i:12;a:3:{s:4:\"icon\";s:6:\"report\";s:4:\"name\";s:13:\"Log Aktivitas\";s:4:\"path\";s:20:\"/admin/log-aktivitas\";}i:13;a:3:{s:4:\"icon\";s:5:\"users\";s:4:\"name\";s:15:\"Kelola Pengguna\";s:4:\"path\";s:12:\"/admin/users\";}}', 1783696974),
('smart-cache-tahun_ajaran_aktif', 'O:22:\"App\\Models\\TahunAjaran\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:12:\"tahun_ajaran\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:7:{s:2:\"id\";i:3;s:4:\"nama\";s:9:\"2024/2025\";s:13:\"tanggal_mulai\";s:10:\"2024-01-12\";s:15:\"tanggal_selesai\";s:10:\"2025-01-12\";s:8:\"is_aktif\";i:1;s:10:\"created_at\";s:19:\"2026-07-10 21:11:16\";s:10:\"updated_at\";s:19:\"2026-07-10 21:11:16\";}s:11:\"\0*\0original\";a:7:{s:2:\"id\";i:3;s:4:\"nama\";s:9:\"2024/2025\";s:13:\"tanggal_mulai\";s:10:\"2024-01-12\";s:15:\"tanggal_selesai\";s:10:\"2025-01-12\";s:8:\"is_aktif\";i:1;s:10:\"created_at\";s:19:\"2026-07-10 21:11:16\";s:10:\"updated_at\";s:19:\"2026-07-10 21:11:16\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:13:\"tanggal_mulai\";s:4:\"date\";s:15:\"tanggal_selesai\";s:4:\"date\";s:8:\"is_aktif\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:4:{i:0;s:4:\"nama\";i:1;s:13:\"tanggal_mulai\";i:2;s:15:\"tanggal_selesai\";i:3;s:8:\"is_aktif\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}', 1783696973);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `nip` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id`, `user_id`, `nip`, `nama_lengkap`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `no_telepon`, `foto`, `created_at`, `updated_at`) VALUES
(1, 4, '0123456789', 'ANDI', 'L', '2000-01-20', '-', '-', NULL, '2026-07-10 14:12:28', '2026-07-10 14:12:28');

-- --------------------------------------------------------

--
-- Table structure for table `hari_libur`
--

CREATE TABLE `hari_libur` (
  `id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hari_libur`
--

INSERT INTO `hari_libur` (`id`, `tanggal`, `nama`, `keterangan`, `is_recurring`, `created_at`, `updated_at`) VALUES
(1, '2026-01-01', 'Tahun Baru Masehi', 'Libur nasional', 1, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(2, '2026-01-29', 'Isra Mi\'raj', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(3, '2026-03-20', 'Tahun Baru Islam 1448 H', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(4, '2026-03-29', 'Wafat Isa Almasih', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(5, '2026-03-30', 'Isra Mi\'raj (Cuti Bersama)', 'Cuti bersama', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(6, '2026-04-02', 'Hari Raya Nyepi', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(7, '2026-04-03', 'Hari Raya Waisak', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(8, '2026-04-18', 'Wafat Isa Almasih (Cuti Bersama)', 'Cuti bersama', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(9, '2026-04-20', 'Hari Raya Idul Fitri 1447 H', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(10, '2026-04-21', 'Cuti Bersama Idul Fitri', 'Cuti bersama', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(11, '2026-05-01', 'Hari Buruh Internasional', 'Hari libur nasional', 1, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(12, '2026-05-14', 'Kenaikan Isa Almasih', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(13, '2026-05-26', 'Hari Raya Waisak', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(14, '2026-06-01', 'Hari Lahir Pancasila', 'Hari libur nasional', 1, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(15, '2026-06-07', 'Hari Raya Idul Adha 1447 H', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(16, '2026-06-27', 'Tahun Baru Islam 1448 H', 'Hari libur nasional', 0, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(17, '2026-08-17', 'Hari Kemerdekaan RI', 'Hari libur nasional', 1, '2026-07-10 05:50:41', '2026-07-10 05:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id` bigint UNSIGNED NOT NULL,
  `guru_id` bigint UNSIGNED NOT NULL,
  `mata_pelajaran_id` bigint UNSIGNED NOT NULL,
  `tahun_ajaran_id` bigint UNSIGNED NOT NULL,
  `kelas` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `lokasi_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id`, `guru_id`, `mata_pelajaran_id`, `tahun_ajaran_id`, `kelas`, `hari`, `jam_mulai`, `jam_selesai`, `lokasi_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, 'X', 'Jumat', '21:00:00', '23:00:00', 1, '2026-07-10 14:12:56', '2026-07-10 14:12:56');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `aksi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aksi`, `deskripsi`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 1, 'login', 'Super Admin berhasil masuk', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 05:51:02', '2026-07-10 05:51:02'),
(2, 1, 'delete_tahun_ajaran', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 05:57:53', '2026-07-10 05:57:53'),
(3, 1, 'delete_tahun_ajaran', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 05:57:59', '2026-07-10 05:57:59'),
(4, 2, 'login', 'Admin Sekolah berhasil masuk', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 06:25:02', '2026-07-10 06:25:02'),
(5, 2, 'login', 'Admin Sekolah berhasil masuk', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 13:14:23', '2026-07-10 13:14:23'),
(6, 2, 'update_pengaturan', 'Mengubah pengaturan sekolah', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:05:38', '2026-07-10 14:05:38'),
(7, 2, 'update_pengaturan', 'Mengubah pengaturan sekolah', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:09:30', '2026-07-10 14:09:30'),
(8, 2, 'update_pengaturan', 'Mengubah pengaturan sekolah', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:09:42', '2026-07-10 14:09:42'),
(9, 2, 'update_pengaturan', 'Mengubah pengaturan sekolah', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:09:44', '2026-07-10 14:09:44'),
(10, 2, 'create_tahun_ajaran', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:11:16', '2026-07-10 14:11:16'),
(11, 2, 'create_guru', 'Menambah guru: ANDI', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:12:28', '2026-07-10 14:12:28'),
(12, 2, 'create_jadwal', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:12:56', '2026-07-10 14:12:56'),
(13, 2, 'create_siswa', 'Menambah siswa: Ahmad Akhtar Rayyan', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:13:24', '2026-07-10 14:13:24'),
(14, 2, 'create_rfid', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:13:38', '2026-07-10 14:13:38'),
(15, 2, 'rfid_scan', '{\"success\":true,\"message\":\"Absensi Hadir berhasil.\",\"type\":\"masuk\",\"role\":\"siswa\",\"nama\":\"Ahmad Akhtar Rayyan\",\"nisn\":\"00987654321001\",\"kelas\":\"X\",\"foto\":null,\"initials\":\"AH\",\"mata_pelajaran\":\"Matematika\",\"status\":\"Hadir\",\"jam\":\"21:13:49\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:13:49', '2026-07-10 14:13:49'),
(16, 2, 'create_rfid', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:16:50', '2026-07-10 14:16:50'),
(17, 2, 'rfid_scan', '{\"success\":true,\"message\":\"Check-in guru Terlambat.\",\"type\":\"masuk\",\"role\":\"guru\",\"nama\":\"ANDI\",\"nip\":\"0123456789\",\"foto\":null,\"initials\":\"AN\",\"status\":\"Terlambat\",\"jam\":\"21:16:55\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:16:55', '2026-07-10 14:16:55'),
(18, 4, 'login', 'ANDI berhasil masuk', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:17:29', '2026-07-10 14:17:29'),
(19, 2, 'logout', 'Admin Sekolah telah keluar', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:22:44', '2026-07-10 14:22:44'),
(20, 1, 'login', 'Super Admin berhasil masuk', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 14:22:53', '2026-07-10 14:22:53'),
(21, 2, 'login', 'Admin Sekolah berhasil masuk', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-10 14:25:30', '2026-07-10 14:25:30'),
(22, 2, 'change_password', 'Password berhasil diubah', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-10 15:08:09', '2026-07-10 15:08:09'),
(23, 4, 'change_password', 'Password berhasil diubah', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-10 15:08:39', '2026-07-10 15:08:39');

-- --------------------------------------------------------

--
-- Table structure for table `lokasi`
--

CREATE TABLE `lokasi` (
  `id` bigint UNSIGNED NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `radius` decimal(8,2) NOT NULL DEFAULT '100.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lokasi`
--

INSERT INTO `lokasi` (`id`, `nama`, `deskripsi`, `latitude`, `longitude`, `radius`, `created_at`, `updated_at`) VALUES
(1, 'Gedung A', 'Gedung utama lantai 1-3', -6.2297000, 106.8213000, 100.00, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(2, 'Gedung B', 'Gedung laboratorium', -6.2298000, 106.8215000, 100.00, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(3, 'Lab Komputer', 'Laboratorium komputer lantai 2', -6.2299000, 106.8214000, 50.00, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(4, 'Perpustakaan', 'Perpustakaan sekolah', -6.2296000, 106.8216000, 75.00, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(5, 'Aula', 'Aula serbaguna', -6.2295000, 106.8212000, 150.00, '2026-07-10 05:50:41', '2026-07-10 05:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `mata_pelajaran`
--

CREATE TABLE `mata_pelajaran` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mata_pelajaran`
--

INSERT INTO `mata_pelajaran` (`id`, `kode`, `nama`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'MTK', 'Matematika', 'Mata pelajaran Matematika', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(2, 'IPA', 'Ilmu Pengetahuan Alam', 'Mata pelajaran IPA', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(3, 'IPS', 'Ilmu Pengetahuan Sosial', 'Mata pelajaran IPS', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(4, 'BHS', 'Bahasa Indonesia', 'Mata pelajaran Bahasa Indonesia', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(5, 'ING', 'Bahasa Inggris', 'Mata pelajaran Bahasa Inggris', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(6, 'PKN', 'Pendidikan Kewarganegaraan', 'Mata pelajaran PKN', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(7, 'PJOK', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'Mata pelajaran PJOK', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(8, 'SBY', 'Seni Budaya', 'Mata pelajaran Seni Budaya', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(9, 'TIK', 'Teknologi Informasi dan Komunikasi', 'Mata pelajaran TIK', '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(10, 'AKL', 'Akuntansi', 'Mata pelajaran Akuntansi', '2026-07-10 05:50:41', '2026-07-10 05:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_01_01_000003_create_pengaturan_sekolah_table', 1),
(5, '2025_01_01_000004_create_tahun_ajaran_table', 1),
(6, '2025_01_01_000005_create_lokasi_table', 1),
(7, '2025_01_01_000006_create_guru_table', 1),
(8, '2025_01_01_000007_create_mata_pelajaran_table', 1),
(9, '2025_01_01_000008_create_siswa_table', 1),
(10, '2025_01_01_000009_create_rfid_card_table', 1),
(11, '2025_01_01_000010_create_jadwal_table', 1),
(12, '2025_01_01_000011_create_absensi_guru_table', 1),
(13, '2025_01_01_000012_create_absensi_siswa_table', 1),
(14, '2025_01_01_000013_create_log_aktivitas_table', 1),
(15, '2025_01_01_000014_add_kelas_to_siswa_table', 1),
(16, '2025_01_01_000015_add_guru_id_to_rfid_card_table', 1),
(17, '2025_01_01_000016_make_siswa_id_nullable_in_rfid_card_table', 1),
(18, '2025_01_01_000017_add_kepala_sekolah_role_to_users_table', 1),
(19, '2025_01_01_000018_create_hari_libur_table', 1),
(20, '2025_01_01_000019_add_toleransi_alpha_to_pengaturan_sekolah_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan_sekolah`
--

CREATE TABLE `pengaturan_sekolah` (
  `id` bigint UNSIGNED NOT NULL,
  `nama_sekolah` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat_sekolah` text COLLATE utf8mb4_unicode_ci,
  `no_telepon_sekolah` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_sekolah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jam_masuk` time NOT NULL DEFAULT '07:00:00',
  `jam_pulang` time NOT NULL DEFAULT '15:00:00',
  `toleransi_terlambat_menit` smallint UNSIGNED NOT NULL DEFAULT '15',
  `toleransi_alpha_menit` smallint UNSIGNED NOT NULL DEFAULT '30',
  `radius_absensi_meter` decimal(8,2) NOT NULL DEFAULT '100.00',
  `sistem` enum('RFID','Manual','Hybrid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RFID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan_sekolah`
--

INSERT INTO `pengaturan_sekolah` (`id`, `nama_sekolah`, `alamat_sekolah`, `no_telepon_sekolah`, `email_sekolah`, `logo`, `jam_masuk`, `jam_pulang`, `toleransi_terlambat_menit`, `toleransi_alpha_menit`, `radius_absensi_meter`, `sistem`, `created_at`, `updated_at`) VALUES
(1, 'SMKN 1 Jakarta', 'Jl. Pendidikan No. 1, Jakarta Selatan', '021-5551234', 'info@smkn1jakarta.sch.id', 'logo/0ctpAMqzKNeCwm4xeDMzlvnsqSHjNcFvGP1BpKQF.jpg', '21:00:00', '23:00:00', 15, 30, 100.00, 'RFID', '2026-07-10 05:50:41', '2026-07-10 14:09:44');

-- --------------------------------------------------------

--
-- Table structure for table `rfid_card`
--

CREATE TABLE `rfid_card` (
  `id` bigint UNSIGNED NOT NULL,
  `siswa_id` bigint UNSIGNED DEFAULT NULL,
  `guru_id` bigint UNSIGNED DEFAULT NULL,
  `uid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rfid_card`
--

INSERT INTO `rfid_card` (`id`, `siswa_id`, `guru_id`, `uid`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '0009819920', 1, '2026-07-10 14:13:38', '2026-07-10 14:13:38'),
(2, NULL, 1, '0009744232', 1, '2026-07-10 14:16:50', '2026-07-10 14:16:50');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Cx3MX7RjFNQblPa0URYj1BuRK2Ua5YKfrucOYpMn', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOHByMVZSV3lUZElGano0VXFxNkxzd1FmSDBuSHFKQVpRenp0aWh2TCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi90YWh1bi1hamFyYW4iO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1783694052),
('dHuUDbrkTP7HDFDmTFPRFQtxDeJWbpRpEK78MYAb', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTGE5QkZXVVAzaGR5c29qaVpwUU01VnBjcTdBaTNkbkFON1lrSW1VcSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9ndXJ1L2xhcG9yYW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0O30=', 1783696133),
('W1gCCxDmGQpMoxZUGzhFlTyqdof8jbC8pXi2t75L', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTmYwR3dBeUhJeDhPMEcxNllNSHZETW5xNHBDVVlYNEpNQ29pQ01uSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1783696094);

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `nisn` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelas` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_ajaran_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `user_id`, `nisn`, `nama_lengkap`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `no_telepon`, `foto`, `kelas`, `tahun_ajaran_id`, `created_at`, `updated_at`) VALUES
(1, 5, '00987654321001', 'Ahmad Akhtar Rayyan', 'L', '2012-11-02', 'Griya Serpong Kademangan', '-', NULL, 'X', 3, '2026-07-10 14:13:24', '2026-07-10 14:13:24');

-- --------------------------------------------------------

--
-- Table structure for table `tahun_ajaran`
--

CREATE TABLE `tahun_ajaran` (
  `id` bigint UNSIGNED NOT NULL,
  `nama` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tahun_ajaran`
--

INSERT INTO `tahun_ajaran` (`id`, `nama`, `tanggal_mulai`, `tanggal_selesai`, `is_aktif`, `created_at`, `updated_at`) VALUES
(3, '2024/2025', '2024-01-12', '2025-01-12', 1, '2026-07-10 14:11:16', '2026-07-10 14:11:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin_sekolah','kepala_sekolah','guru','siswa') COLLATE utf8mb4_unicode_ci DEFAULT 'siswa',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@smart.sch.id', NULL, '$2y$12$xFicmmJArDOCpoiuztL/mek6KyXPNOOGE57ftGh8YUT68N1fKPolq', 'super_admin', 1, NULL, '2026-07-10 05:50:40', '2026-07-10 05:50:40'),
(2, 'Admin Sekolah', 'adminsekolah@smart.sch.id', NULL, '$2y$12$BI11EgxWaP9fGLK5hMs6ledNj4rXs.ENXEFz.MZzYSvXfnJ7vEpcC', 'admin_sekolah', 1, NULL, '2026-07-10 05:50:40', '2026-07-10 15:08:09'),
(3, 'Kepala Sekolah', 'kepalasekolah@smart.sch.id', NULL, '$2y$12$ri12LVLmr1eNIrR36sw7/uGBVLCl7hZHw8SeKkVDwhch1Bm8iT5vq', 'kepala_sekolah', 1, NULL, '2026-07-10 05:50:41', '2026-07-10 05:50:41'),
(4, 'ANDI', '15240133@bsi.ac.id', NULL, '$2y$12$hRrrIY377ttrRjl2U6ydreH6hhQkX.RnMoXHJ/c9jk6bl/Jb/W8fm', 'guru', 1, NULL, '2026-07-10 14:12:28', '2026-07-10 15:08:39'),
(5, 'Ahmad Akhtar Rayyan', '3091375841@smart.sch.id', NULL, '$2y$12$41o8ZuxuU6i2x8FZALrBTejpogj45WUNu1DbP9fgEYkVrAlj8BixG', 'siswa', 1, NULL, '2026-07-10 14:13:24', '2026-07-10 14:13:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `absensi_guru_guru_id_tanggal_unique` (`guru_id`,`tanggal`),
  ADD KEY `absensi_guru_tanggal_index` (`tanggal`),
  ADD KEY `absensi_guru_status_index` (`status`);

--
-- Indexes for table `absensi_siswa`
--
ALTER TABLE `absensi_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `absensi_siswa_siswa_id_jadwal_id_tanggal_unique` (`siswa_id`,`jadwal_id`,`tanggal`),
  ADD KEY `absensi_siswa_diedit_oleh_foreign` (`diedit_oleh`),
  ADD KEY `absensi_siswa_tanggal_index` (`tanggal`),
  ADD KEY `absensi_siswa_status_index` (`status`),
  ADD KEY `absensi_siswa_jadwal_id_index` (`jadwal_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `guru_nip_unique` (`nip`),
  ADD KEY `guru_user_id_foreign` (`user_id`),
  ADD KEY `guru_nip_index` (`nip`),
  ADD KEY `guru_nama_lengkap_index` (`nama_lengkap`);

--
-- Indexes for table `hari_libur`
--
ALTER TABLE `hari_libur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hari_libur_tanggal_unique` (`tanggal`),
  ADD KEY `hari_libur_tanggal_index` (`tanggal`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jadwal_tahun_ajaran_id_kelas_hari_jam_mulai_unique` (`tahun_ajaran_id`,`kelas`,`hari`,`jam_mulai`),
  ADD KEY `jadwal_guru_id_index` (`guru_id`),
  ADD KEY `jadwal_mata_pelajaran_id_index` (`mata_pelajaran_id`),
  ADD KEY `jadwal_tahun_ajaran_id_index` (`tahun_ajaran_id`),
  ADD KEY `jadwal_kelas_index` (`kelas`),
  ADD KEY `jadwal_hari_index` (`hari`),
  ADD KEY `jadwal_lokasi_id_index` (`lokasi_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `log_aktivitas_user_id_index` (`user_id`),
  ADD KEY `log_aktivitas_aksi_index` (`aksi`),
  ADD KEY `log_aktivitas_created_at_index` (`created_at`);

--
-- Indexes for table `lokasi`
--
ALTER TABLE `lokasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lokasi_nama_index` (`nama`);

--
-- Indexes for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mata_pelajaran_kode_unique` (`kode`),
  ADD KEY `mata_pelajaran_kode_index` (`kode`),
  ADD KEY `mata_pelajaran_nama_index` (`nama`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `pengaturan_sekolah`
--
ALTER TABLE `pengaturan_sekolah`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rfid_card`
--
ALTER TABLE `rfid_card`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rfid_card_uid_unique` (`uid`),
  ADD KEY `rfid_card_uid_index` (`uid`),
  ADD KEY `rfid_card_siswa_id_index` (`siswa_id`),
  ADD KEY `rfid_card_is_active_index` (`is_active`),
  ADD KEY `rfid_card_guru_id_index` (`guru_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `siswa_nisn_unique` (`nisn`),
  ADD KEY `siswa_user_id_foreign` (`user_id`),
  ADD KEY `siswa_nisn_index` (`nisn`),
  ADD KEY `siswa_nama_lengkap_index` (`nama_lengkap`),
  ADD KEY `siswa_tahun_ajaran_id_index` (`tahun_ajaran_id`),
  ADD KEY `siswa_kelas_index` (`kelas`);

--
-- Indexes for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tahun_ajaran_nama_unique` (`nama`),
  ADD KEY `tahun_ajaran_is_aktif_index` (`is_aktif`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_is_active_index` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `absensi_siswa`
--
ALTER TABLE `absensi_siswa`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hari_libur`
--
ALTER TABLE `hari_libur`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `lokasi`
--
ALTER TABLE `lokasi`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `pengaturan_sekolah`
--
ALTER TABLE `pengaturan_sekolah`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rfid_card`
--
ALTER TABLE `rfid_card`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD CONSTRAINT `absensi_guru_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `absensi_siswa`
--
ALTER TABLE `absensi_siswa`
  ADD CONSTRAINT `absensi_siswa_diedit_oleh_foreign` FOREIGN KEY (`diedit_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `absensi_siswa_jadwal_id_foreign` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_siswa_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `guru`
--
ALTER TABLE `guru`
  ADD CONSTRAINT `guru_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `jadwal_lokasi_id_foreign` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `jadwal_mata_pelajaran_id_foreign` FOREIGN KEY (`mata_pelajaran_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `jadwal_tahun_ajaran_id_foreign` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rfid_card`
--
ALTER TABLE `rfid_card`
  ADD CONSTRAINT `rfid_card_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rfid_card_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_tahun_ajaran_id_foreign` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `siswa_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
