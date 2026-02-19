-- Run this on database zdsnyykk_quiz (or your app DB) if you cannot run php artisan migrate there.
-- Creates the otps table for student_login (14-day reusable) and examiner_fallback (one-time) OTPs.

CREATE TABLE IF NOT EXISTS `otps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `index_number_hash` varchar(64) NOT NULL,
  `type` varchar(32) NOT NULL,
  `code` varchar(10) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otps_index_number_hash_index` (`index_number_hash`),
  KEY `otps_type_index` (`type`),
  KEY `otps_index_number_hash_type_created_at_index` (`index_number_hash`,`type`,`created_at`)
);
