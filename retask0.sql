-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 15, 2025 lúc 04:20 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `retask0`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0 CHECK (`is_admin` in (0,1)),
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_crop` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `birthday`, `password`, `created_at`, `is_admin`, `avatar`, `avatar_crop`) VALUES
(1, 'Admin', 'ad@example.com', '098765431', '2003-01-05', '$2y$10$JRlRWwrfDDvIp0WRDCh8I.bXdw.3YIip7CnLQ5lKX9QlN1qEwozHS', '2025-09-10 07:09:05', 1, '68c3d7f2efb73_1757665266.png', NULL),
(2, 'Customer', 'cu@example.com', '0123456789', '2003-01-05', '$2y$10$7Z5Y8kKOdUuj320CI1hwOeglAQHd5L0zQ/sGGLMQzotdZqIXWCzYW', '2025-09-10 07:09:52', 0, '68c3f51110550_1757672721.jpg', NULL),
(3, 'Adam', 'ada@example.com', '0944287004', '2003-01-05', '$2y$10$9uxKlncQ2Z3UhNHKXNwbd.6Uf4Qlsp2WyoymkqhFORJRsUU00iUoi', '2025-09-10 07:11:06', 0, NULL, NULL),
(4, 'Customer1', 'cu1@example.com', '0944332211', '2003-01-05', '$2y$10$kgmTj0uY3wgN5JokJiM0ouXvgehjc4WvW7Ac1POYsPX1cMFDAiqGe', '2025-09-10 07:19:08', 0, NULL, NULL),
(5, 'Ad1', 'ad1@example.com', '0122334455', '2003-01-05', '$2y$10$U0/M7XpNAsy06GpKrC6Bxey3d95umklkoLX5/B.KdiumVrQwAuyjC', '2025-09-10 09:09:18', 0, NULL, NULL),
(7, 'NguyenAnhDo', 'admin1@gmail.com', '0944335566', '2003-01-05', '$2y$10$T0bEYdjOSayFMT2FHls2XuFqjIVFEo2ZyltMtRbG67AVsarQ7lRci', '2025-09-11 08:22:40', 0, '68c7719708cdd_1757901207.png', NULL),
(8, 'NguyenAnh', 'na@example.com', '0778899665', '2003-01-05', '$2y$10$RvttMLK9frJMtFheKhqtRuwzPM49.XfgNTlrVRL1DWp6BayAOEI3y', '2025-09-11 08:31:23', 0, '68c3db6f42d3a_1757666159.jpg', NULL),
(14, 'Ánh', 'anh@example.com', '0996674433', '1994-03-17', '$2y$10$6ylfKBaUgJ6TNTwaa0r57e71rC4puEtj4DjD1vBXlrbNudBJGxShK', '2025-09-12 08:31:48', 0, '68c3da73e8748_1757665907.png', NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
