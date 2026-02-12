-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: db_nilai
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
-- Table structure for table `absensi`
--

DROP TABLE IF EXISTS `absensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `absensi` (
  `id_absensi` int NOT NULL AUTO_INCREMENT,
  `id_mengajar` int NOT NULL,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Hadir','Sakit','Izin','Alfa') NOT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_absensi`),
  UNIQUE KEY `unique_absensi` (`id_mengajar`,`id_siswa`,`tanggal`),
  KEY `id_siswa` (`id_siswa`),
  CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`id_mengajar`) REFERENCES `mengajar` (`id_mengajar`) ON DELETE CASCADE,
  CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `absensi`
--

LOCK TABLES `absensi` WRITE;
/*!40000 ALTER TABLE `absensi` DISABLE KEYS */;
INSERT INTO `absensi` VALUES (22,20,16,'2026-02-09','Hadir',NULL,'2026-02-09 06:46:05');
/*!40000 ALTER TABLE `absensi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guru`
--

DROP TABLE IF EXISTS `guru`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru` (
  `id_guru` int NOT NULL AUTO_INCREMENT,
  `nip` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_user` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_guru`),
  UNIQUE KEY `nip` (`nip`),
  UNIQUE KEY `email` (`email`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `guru_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guru`
--

LOCK TABLES `guru` WRITE;
/*!40000 ALTER TABLE `guru` DISABLE KEYS */;
INSERT INTO `guru` VALUES (12,'232323','ripalah','081333335871','ripalahabdurrohman143@gmail.com',3,'2025-10-08 15:04:49','2025-10-08 15:10:18'),(15,'089','hidmat Ramadhan','089','2406130@itg.ac.id',10,'2026-01-14 06:23:09','2026-01-29 09:13:41'),(16,'555555','Hikmatiar','0890088990','Hikmatiar@gmail.com',18,'2026-02-03 01:55:06','2026-02-08 13:11:15'),(17,'555','raya','081333335871','240613@ac.id',NULL,'2026-02-08 13:01:01','2026-02-08 13:01:01');
/*!40000 ALTER TABLE `guru` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jurusan`
--

DROP TABLE IF EXISTS `jurusan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jurusan` (
  `id_jurusan` int NOT NULL AUTO_INCREMENT,
  `kode_jurusan` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_jurusan` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_jurusan`),
  UNIQUE KEY `kode_jurusan` (`kode_jurusan`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jurusan`
--

LOCK TABLES `jurusan` WRITE;
/*!40000 ALTER TABLE `jurusan` DISABLE KEYS */;
INSERT INTO `jurusan` VALUES (12,'TKJ','Teknik Komputer dan Jaringan');
/*!40000 ALTER TABLE `jurusan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kelas`
--

DROP TABLE IF EXISTS `kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tingkat` enum('10','11','12') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_jurusan` int NOT NULL,
  `id_tahun_ajaran` int NOT NULL DEFAULT '11',
  `id_guru_wali_kelas` int NOT NULL,
  PRIMARY KEY (`id_kelas`),
  KEY `id_jurusan` (`id_jurusan`),
  KEY `id_guru_wali_kelas` (`id_guru_wali_kelas`),
  CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`id_jurusan`) REFERENCES `jurusan` (`id_jurusan`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `kelas_ibfk_2` FOREIGN KEY (`id_guru_wali_kelas`) REFERENCES `guru` (`id_guru`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kelas`
--

LOCK TABLES `kelas` WRITE;
/*!40000 ALTER TABLE `kelas` DISABLE KEYS */;
INSERT INTO `kelas` VALUES (14,'XI','11',12,11,12),(15,'X','10',12,11,15);
/*!40000 ALTER TABLE `kelas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mata_pelajaran`
--

DROP TABLE IF EXISTS `mata_pelajaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mata_pelajaran` (
  `id_mapel` int NOT NULL AUTO_INCREMENT,
  `kode_mapel` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_mapel` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('Normatif','Adaptif','Produktif','Muatan Lokal') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_mapel`),
  UNIQUE KEY `kode_mapel` (`kode_mapel`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mata_pelajaran`
--

LOCK TABLES `mata_pelajaran` WRITE;
/*!40000 ALTER TABLE `mata_pelajaran` DISABLE KEYS */;
INSERT INTO `mata_pelajaran` VALUES (6,'MAP006','Jaringan Komputer','Produktif'),(11,'MP02','IPA','Normatif');
/*!40000 ALTER TABLE `mata_pelajaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mengajar`
--

DROP TABLE IF EXISTS `mengajar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mengajar` (
  `id_mengajar` int NOT NULL AUTO_INCREMENT,
  `id_guru` int NOT NULL,
  `id_mapel` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_tahun_ajaran` int NOT NULL,
  `hari` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  PRIMARY KEY (`id_mengajar`),
  UNIQUE KEY `unik_mengajar` (`id_guru`,`id_mapel`,`id_kelas`,`id_tahun_ajaran`),
  KEY `id_mapel` (`id_mapel`),
  KEY `id_kelas` (`id_kelas`),
  KEY `id_tahun_ajaran` (`id_tahun_ajaran`),
  CONSTRAINT `mengajar_ibfk_1` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `mengajar_ibfk_2` FOREIGN KEY (`id_mapel`) REFERENCES `mata_pelajaran` (`id_mapel`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `mengajar_ibfk_3` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `mengajar_ibfk_4` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mengajar`
--

LOCK TABLES `mengajar` WRITE;
/*!40000 ALTER TABLE `mengajar` DISABLE KEYS */;
INSERT INTO `mengajar` VALUES (13,15,11,14,11,'Sabtu','07:30:00','09:00:00'),(20,16,6,15,11,'Senin','08:00:00','10:00:00');
/*!40000 ALTER TABLE `mengajar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nilai`
--

DROP TABLE IF EXISTS `nilai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nilai` (
  `id_nilai` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `id_mengajar` int NOT NULL,
  `jenis_nilai` enum('Tugas','UTS','UAS','Praktik','Harian') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` decimal(5,2) NOT NULL,
  `tanggal_penilaian` date DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_nilai`),
  KEY `id_siswa` (`id_siswa`),
  KEY `id_mengajar` (`id_mengajar`),
  CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nilai_ibfk_2` FOREIGN KEY (`id_mengajar`) REFERENCES `mengajar` (`id_mengajar`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nilai`
--

LOCK TABLES `nilai` WRITE;
/*!40000 ALTER TABLE `nilai` DISABLE KEYS */;
INSERT INTO `nilai` VALUES (63,16,13,'Tugas',80.00,NULL,NULL,'2026-01-14 06:38:42'),(64,16,13,'UTS',79.00,NULL,NULL,'2026-01-14 06:38:42'),(65,16,13,'UAS',90.00,NULL,NULL,'2026-01-14 06:38:42'),(66,16,13,'Praktik',80.00,NULL,NULL,'2026-01-14 06:38:42'),(107,16,20,'Tugas',80.00,NULL,NULL,'2026-02-09 05:26:45'),(108,16,20,'UTS',88.00,NULL,NULL,'2026-02-09 05:26:45'),(109,16,20,'UAS',87.00,NULL,NULL,'2026-02-09 05:26:45'),(110,16,20,'Praktik',90.00,NULL,NULL,'2026-02-09 05:26:45');
/*!40000 ALTER TABLE `nilai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengumuman`
--

DROP TABLE IF EXISTS `pengumuman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengumuman` (
  `id_pengumuman` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `tanggal_posting` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user_pembuat` int DEFAULT NULL,
  `target_role` enum('semua','guru','siswa') NOT NULL DEFAULT 'semua',
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_pengumuman`),
  KEY `id_user_pembuat` (`id_user_pembuat`),
  CONSTRAINT `pengumuman_ibfk_1` FOREIGN KEY (`id_user_pembuat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengumuman`
--

LOCK TABLES `pengumuman` WRITE;
/*!40000 ALTER TABLE `pengumuman` DISABLE KEYS */;
INSERT INTO `pengumuman` VALUES (1,'Study tour','setelah lama sekolah kita akan mengadakan liburan akhir tahun','2025-10-26 16:52:18',1,'semua',0),(4,'libur','besok libur yey','2026-01-18 02:24:26',1,'semua',0),(5,'Study tour','jalan jalan ke luar kota','2026-01-19 06:55:39',1,'semua',0);
/*!40000 ALTER TABLE `pengumuman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `siswa`
--

DROP TABLE IF EXISTS `siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL AUTO_INCREMENT,
  `nis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nisn` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `id_kelas` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_user` int DEFAULT NULL,
  PRIMARY KEY (`id_siswa`),
  UNIQUE KEY `nis` (`nis`),
  UNIQUE KEY `nisn` (`nisn`),
  KEY `id_kelas` (`id_kelas`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `siswa`
--

LOCK TABLES `siswa` WRITE;
/*!40000 ALTER TABLE `siswa` DISABLE KEYS */;
INSERT INTO `siswa` VALUES (16,'99999999','88888888','dumi',NULL,'Laki-laki','0',15,'2025-10-12 14:02:55','2026-01-19 02:50:35',9),(21,'22','1010','uj','2009-08-09','Laki-laki','kp ganteng',14,'2026-02-09 06:32:51','2026-02-09 06:32:51',NULL);
/*!40000 ALTER TABLE `siswa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tahun_ajaran`
--

DROP TABLE IF EXISTS `tahun_ajaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tahun_ajaran` (
  `id_tahun_ajaran` int NOT NULL AUTO_INCREMENT,
  `tahun_ajaran` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('Ganjil','Genap') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_aktif` enum('Aktif','Tidak Aktif') COLLATE utf8mb4_unicode_ci DEFAULT 'Tidak Aktif',
  PRIMARY KEY (`id_tahun_ajaran`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tahun_ajaran`
--

LOCK TABLES `tahun_ajaran` WRITE;
/*!40000 ALTER TABLE `tahun_ajaran` DISABLE KEYS */;
INSERT INTO `tahun_ajaran` VALUES (8,'2023/2024','Genap','Tidak Aktif'),(9,'2024/2025','Ganjil','Tidak Aktif'),(11,'2026/2027','Ganjil','Aktif'),(12,'2025/2026','Ganjil','Tidak Aktif');
/*!40000 ALTER TABLE `tahun_ajaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ujian`
--

DROP TABLE IF EXISTS `ujian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ujian` (
  `id_ujian` int NOT NULL AUTO_INCREMENT,
  `id_mengajar` int NOT NULL,
  `judul_ujian` varchar(255) NOT NULL,
  `jenis_ujian` enum('Pilihan Ganda','Esai') NOT NULL DEFAULT 'Pilihan Ganda',
  `durasi_menit` int NOT NULL DEFAULT '60',
  `waktu_mulai` datetime NOT NULL,
  `waktu_selesai` datetime NOT NULL,
  `status_ujian` enum('Draft','Published','Selesai') NOT NULL DEFAULT 'Draft',
  PRIMARY KEY (`id_ujian`),
  KEY `id_mengajar` (`id_mengajar`),
  CONSTRAINT `ujian_ibfk_1` FOREIGN KEY (`id_mengajar`) REFERENCES `mengajar` (`id_mengajar`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ujian`
--

LOCK TABLES `ujian` WRITE;
/*!40000 ALTER TABLE `ujian` DISABLE KEYS */;
INSERT INTO `ujian` VALUES (11,13,'tugas','Pilihan Ganda',60,'2026-01-23 17:00:00','2026-01-23 19:00:00','Published'),(14,13,'uas','Pilihan Ganda',60,'2026-02-07 00:00:00','2026-02-07 15:00:00','Published'),(19,20,'tugas','Esai',60,'2026-02-09 11:00:00','2026-02-09 13:00:00','Published');
/*!40000 ALTER TABLE `ujian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ujian_hasil`
--

DROP TABLE IF EXISTS `ujian_hasil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ujian_hasil` (
  `id_hasil` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `id_siswa` int NOT NULL,
  `waktu_mulai_mengerjakan` datetime DEFAULT NULL,
  `waktu_selesai_mengerjakan` datetime DEFAULT NULL,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `status_pengerjaan` enum('Belum','Mengerjakan','Selesai','Dinilai') NOT NULL DEFAULT 'Belum',
  PRIMARY KEY (`id_hasil`),
  KEY `id_ujian` (`id_ujian`),
  KEY `id_siswa` (`id_siswa`),
  CONSTRAINT `ujian_hasil_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id_ujian`) ON DELETE CASCADE,
  CONSTRAINT `ujian_hasil_ibfk_2` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ujian_hasil`
--

LOCK TABLES `ujian_hasil` WRITE;
/*!40000 ALTER TABLE `ujian_hasil` DISABLE KEYS */;
INSERT INTO `ujian_hasil` VALUES (10,19,16,'2026-02-09 11:49:26','2026-02-09 04:49:44',74.50,'Dinilai');
/*!40000 ALTER TABLE `ujian_hasil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ujian_jawaban_siswa`
--

DROP TABLE IF EXISTS `ujian_jawaban_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ujian_jawaban_siswa` (
  `id_jawaban` int NOT NULL AUTO_INCREMENT,
  `id_hasil` int NOT NULL,
  `id_soal` int NOT NULL,
  `jawaban_siswa` text,
  `is_benar` tinyint(1) DEFAULT NULL,
  `nilai_esai` decimal(5,2) DEFAULT '0.00',
  PRIMARY KEY (`id_jawaban`),
  KEY `id_hasil` (`id_hasil`),
  KEY `id_soal` (`id_soal`),
  CONSTRAINT `ujian_jawaban_siswa_ibfk_1` FOREIGN KEY (`id_hasil`) REFERENCES `ujian_hasil` (`id_hasil`) ON DELETE CASCADE,
  CONSTRAINT `ujian_jawaban_siswa_ibfk_2` FOREIGN KEY (`id_soal`) REFERENCES `ujian_soal` (`id_soal`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ujian_jawaban_siswa`
--

LOCK TABLES `ujian_jawaban_siswa` WRITE;
/*!40000 ALTER TABLE `ujian_jawaban_siswa` DISABLE KEYS */;
INSERT INTO `ujian_jawaban_siswa` VALUES (4,10,16,'benar',NULL,80.00),(5,10,17,'buah',NULL,69.00);
/*!40000 ALTER TABLE `ujian_jawaban_siswa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ujian_soal`
--

DROP TABLE IF EXISTS `ujian_soal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ujian_soal` (
  `id_soal` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nomor_soal` int NOT NULL,
  `pertanyaan` text NOT NULL,
  `opsi_a` text,
  `opsi_b` text,
  `opsi_c` text,
  `opsi_d` text,
  `opsi_e` text,
  `kunci_jawaban` text,
  PRIMARY KEY (`id_soal`),
  KEY `id_ujian` (`id_ujian`),
  CONSTRAINT `ujian_soal_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id_ujian`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ujian_soal`
--

LOCK TABLES `ujian_soal` WRITE;
/*!40000 ALTER TABLE `ujian_soal` DISABLE KEYS */;
INSERT INTO `ujian_soal` VALUES (8,11,1,'apa yang dimaksud mamalia','menyusui','tidak tahu','mengandung','bisa jadi','melahirkan','A'),(11,14,1,'apa yang dimaksud hidup','cg','vd','ca','d','c','D'),(16,19,1,'kehidupan selalu penuh dengan rintangan benar atau salah',NULL,NULL,NULL,NULL,NULL,NULL),(17,19,2,'jika kehidupan itu manis, apa yang lebih manis daripada kehidupan',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `ujian_soal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','operator','guru','siswa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operator',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `foto_profil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_guru` int DEFAULT NULL,
  `id_siswa` int DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`),
  KEY `fk_users_siswa` (`id_siswa`),
  CONSTRAINT `fk_users_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Muhamad Rais Ar','2406133@itg.ac.id','$2y$12$Wy7PZNJnEFtZdJAvxLguOutLleJP8hazGU5fWz8Cf0fW9mcOrU.pW','admin','aktif','user_1_1761485854.jpg',NULL,NULL,'2026-02-09 13:14:48','2025-10-03 14:28:26','2026-02-09 06:14:48'),(3,'ripalah','ripalahabdurrohman143@gmail.com','$2y$12$/ll/rABbPD2M9uwYw3LWW.T8GvuRbR8A/cKhzrA9aEIq9BkhUU1me','guru','aktif','user_3_1761497811.jpg',12,NULL,'2026-02-07 19:17:14','2025-10-08 15:09:52','2026-02-07 12:17:14'),(9,'dumi','dumy2@mail.com','$2y$12$X6vb/kDnu72VG/39pacjv.pfpsaTK6H7T0UTT8AtbeO2jM1NRkrJu','siswa','aktif','user_9_1761496427.jpg',NULL,16,'2026-02-09 13:37:22','2025-10-12 14:03:54','2026-02-09 06:37:22'),(10,'Hidmat Ramadhan','2406130@itg.ac.id','$2y$12$Y.iQVoZ7VWqmZw5V00rJP.3sHS/ZCKdJ9DVHUt4uFAJ6OqxocK4j6','guru','aktif','user_10_1769000639.jpeg',15,NULL,'2026-02-09 14:29:53','2026-01-14 06:30:16','2026-02-09 07:29:53'),(18,'Hikmatiar','Hikmatiar@gmail.com','$2y$12$LcEkS1JgBTy8qln5u.Mtg.oWtY6OKoJeTshIUVXIK2ko60fPpZXZ2','guru','aktif',NULL,16,NULL,'2026-02-09 13:45:48','2026-02-08 13:08:14','2026-02-09 06:45:48');
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

-- Dump completed on 2026-02-09 14:49:08
