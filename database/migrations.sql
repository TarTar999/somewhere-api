-- =====================================================
-- SOMEWHERE APP - DATABASE MIGRATION SCRIPT
-- Execute this SQL in phpMyAdmin on OVH
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. USERS TABLE (base)
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(255) NULL,
    `last_name` VARCHAR(255) NULL,
    `name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(255) NULL,
    `email_verified_at` TIMESTAMP NULL,
    `password` VARCHAR(255) NOT NULL,
    `two_factor_secret` TEXT NULL,
    `two_factor_recovery_codes` TEXT NULL,
    `two_factor_confirmed_at` TIMESTAMP NULL,
    `sex` ENUM('male', 'female') NULL,
    `nui_number` VARCHAR(255) NULL,
    `cni_number` VARCHAR(255) NULL,
    `cni_expiration_date` DATE NULL,
    `avatar_path` VARCHAR(255) NULL,
    `lottie_avatar` VARCHAR(255) NULL,
    `signature` LONGTEXT NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `deletion_requested_at` TIMESTAMP NULL,
    `deletion_scheduled_at` TIMESTAMP NULL,
    `deletion_reason` TEXT NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `users_email_unique` (`email`),
    UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. PASSWORD RESET TOKENS
-- =====================================================
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. SESSIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CACHE
-- =====================================================
CREATE TABLE IF NOT EXISTS `cache` (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` INT NOT NULL,
    KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL,
    KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. JOBS
-- =====================================================
CREATE TABLE IF NOT EXISTS `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL,
    `cancelled_at` INT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(255) NOT NULL,
    `connection` TEXT NOT NULL,
    `queue` TEXT NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. PERSONAL ACCESS TOKENS (Sanctum)
-- =====================================================
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `tokenable_type` VARCHAR(255) NOT NULL,
    `tokenable_id` BIGINT UNSIGNED NOT NULL,
    `name` TEXT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `abilities` TEXT NULL,
    `last_used_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
    KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`),
    KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. USER SETTINGS
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `language` VARCHAR(10) NOT NULL DEFAULT 'fr',
    `unit` ENUM('metric', 'imperial') NOT NULL DEFAULT 'metric',
    `notifications` ENUM('enabled', 'disabled') NOT NULL DEFAULT 'enabled',
    `map_type` ENUM('ApplePlan', 'GoogleMap') NOT NULL DEFAULT 'GoogleMap',
    `proof_of_residence` VARCHAR(255) NULL,
    `proof_of_residence_date` TIMESTAMP NULL,
    `google_search` TINYINT(1) NOT NULL DEFAULT 1,
    `is_city_mapper` TINYINT(1) NOT NULL DEFAULT 0,
    `dark_mode` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `user_settings_user_id_unique` (`user_id`),
    CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. STREETS
-- =====================================================
CREATE TABLE IF NOT EXISTS `streets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `osm_id` BIGINT UNSIGNED NOT NULL,
    `osm_type` VARCHAR(255) NOT NULL DEFAULT 'way',
    `display_name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(255) NULL,
    `commune_name` VARCHAR(255) NULL,
    `commune_number` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `structure` JSON NULL,
    `bounding_box` JSON NULL,
    `start_lat` DECIMAL(10, 8) NULL,
    `start_lon` DECIMAL(11, 8) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `streets_osm_id_unique` (`osm_id`),
    UNIQUE KEY `streets_code_unique` (`code`),
    KEY `streets_commune_name_commune_number_index` (`commune_name`, `commune_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. ADDRESSES
-- =====================================================
CREATE TABLE IF NOT EXISTS `addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `street_id` BIGINT UNSIGNED NULL,
    `street_number` INT UNSIGNED NULL,
    `distance_on_street` DECIMAL(10, 2) NULL,
    `street_side` ENUM('left', 'right') NULL,
    `sw_address` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(255) NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `itinerary` JSON NULL,
    `itinerary_street_id` BIGINT UNSIGNED NULL,
    `itinerary_description` TEXT NULL,
    `itinerary_distance` INT NULL,
    `accuracy` FLOAT NULL,
    `house_type` ENUM('immeuble', 'villa', 'maison', 'studio', 'bureau', 'autre') NULL,
    `home_status` ENUM('locataire', 'residence', 'familiale', 'proprietaire', 'commercial') NULL,
    `quarter` VARCHAR(255) NULL,
    `sub_quarter` VARCHAR(255) NULL,
    `lieu_dit` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `official_address` VARCHAR(255) NULL,
    `way_code` VARCHAR(255) NULL,
    `way_display_name` VARCHAR(255) NULL,
    `honor_declaration` TINYINT(1) NOT NULL DEFAULT 0,
    `signature` TEXT NULL,
    `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `video_path` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `addresses_sw_address_unique` (`sw_address`),
    KEY `addresses_latitude_longitude_index` (`latitude`, `longitude`),
    KEY `addresses_sw_address_index` (`sw_address`),
    KEY `addresses_user_id_index` (`user_id`),
    KEY `addresses_verification_status_index` (`verification_status`),
    CONSTRAINT `addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `addresses_street_id_foreign` FOREIGN KEY (`street_id`) REFERENCES `streets` (`id`) ON DELETE SET NULL,
    CONSTRAINT `addresses_itinerary_street_id_foreign` FOREIGN KEY (`itinerary_street_id`) REFERENCES `streets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. COLLECTIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `collections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `owner_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `logo` VARCHAR(255) NULL,
    `icon` VARCHAR(255) NULL,
    `color` VARCHAR(7) NULL,
    `type` ENUM('system', 'custom', 'delivery') NOT NULL DEFAULT 'custom',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `collections_slug_unique` (`slug`),
    KEY `collections_owner_id_index` (`owner_id`),
    KEY `collections_type_index` (`type`),
    CONSTRAINT `collections_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. ADDRESS_COLLECTION (pivot)
-- =====================================================
CREATE TABLE IF NOT EXISTS `address_collection` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `address_id` BIGINT UNSIGNED NOT NULL,
    `collection_id` BIGINT UNSIGNED NOT NULL,
    `order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `address_collection_address_id_collection_id_unique` (`address_id`, `collection_id`),
    CONSTRAINT `address_collection_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `address_collection_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. SHARED_COLLECTIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `shared_collections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `collection_id` BIGINT UNSIGNED NOT NULL,
    `shared_with_user_id` BIGINT UNSIGNED NOT NULL,
    `permissions` ENUM('view', 'edit') NOT NULL DEFAULT 'view',
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `shared_collections_collection_id_shared_with_user_id_unique` (`collection_id`, `shared_with_user_id`),
    CONSTRAINT `shared_collections_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `shared_collections_shared_with_user_id_foreign` FOREIGN KEY (`shared_with_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. OTP_CODES
-- =====================================================
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL,
    `code` VARCHAR(7) NOT NULL,
    `type` ENUM('phone', 'email') NOT NULL DEFAULT 'phone',
    `purpose` ENUM('registration', 'login', 'password_reset', 'verification') NOT NULL DEFAULT 'verification',
    `expires_at` TIMESTAMP NOT NULL,
    `verified_at` TIMESTAMP NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `otp_codes_identifier_type_purpose_index` (`identifier`, `type`, `purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. REFRESH_TOKENS
-- =====================================================
CREATE TABLE IF NOT EXISTS `refresh_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `device_name` VARCHAR(255) NULL,
    `device_id` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `revoked_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `refresh_tokens_token_unique` (`token`),
    KEY `refresh_tokens_token_index` (`token`),
    KEY `refresh_tokens_user_id_index` (`user_id`),
    CONSTRAINT `refresh_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 15. DELIVERY_REQUESTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `delivery_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `initiator_id` BIGINT UNSIGNED NOT NULL,
    `recipient_id` BIGINT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `value` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'XAF',
    `status` ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `initiator_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
    `recipient_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
    `pickup_address_id` BIGINT UNSIGNED NULL,
    `delivery_address_id` BIGINT UNSIGNED NULL,
    `delivery_latitude` DECIMAL(10, 8) NULL,
    `delivery_longitude` DECIMAL(11, 8) NULL,
    `share_token` VARCHAR(64) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `accepted_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    UNIQUE KEY `delivery_requests_share_token_unique` (`share_token`),
    KEY `delivery_requests_initiator_id_index` (`initiator_id`),
    KEY `delivery_requests_recipient_id_index` (`recipient_id`),
    KEY `delivery_requests_status_index` (`status`),
    CONSTRAINT `delivery_requests_initiator_id_foreign` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `delivery_requests_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `delivery_requests_pickup_address_id_foreign` FOREIGN KEY (`pickup_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
    CONSTRAINT `delivery_requests_delivery_address_id_foreign` FOREIGN KEY (`delivery_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 16. TRACKS
-- =====================================================
CREATE TABLE IF NOT EXISTS `tracks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `structure` JSON NOT NULL,
    `color` VARCHAR(255) NOT NULL DEFAULT '#3B82F6',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `share_token` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `tracks_share_token_unique` (`share_token`),
    KEY `tracks_user_id_index` (`user_id`),
    KEY `tracks_share_token_index` (`share_token`),
    CONSTRAINT `tracks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `track_shares` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `track_id` BIGINT UNSIGNED NOT NULL,
    `shared_with_user_id` BIGINT UNSIGNED NOT NULL,
    `permission` ENUM('view', 'edit') NOT NULL DEFAULT 'view',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `track_shares_track_id_shared_with_user_id_unique` (`track_id`, `shared_with_user_id`),
    CONSTRAINT `track_shares_track_id_foreign` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `track_shares_shared_with_user_id_foreign` FOREIGN KEY (`shared_with_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 17. PAYMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `address_id` BIGINT UNSIGNED NULL,
    `transaction_id` VARCHAR(255) NULL,
    `external_id` VARCHAR(255) NULL,
    `type` ENUM('proof_of_location', 'kyc_verification', 'subscription', 'other', 'location_plan', 'proof_of_residence') NOT NULL DEFAULT 'proof_of_location',
    `amount` INT NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'XAF',
    `status` ENUM('pending', 'successful', 'failed', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    `payment_link` VARCHAR(255) NULL,
    `medium` VARCHAR(255) NULL,
    `phone` VARCHAR(255) NULL,
    `fapshi_response` JSON NULL,
    `failure_reason` TEXT NULL,
    `webhook_received_at` TIMESTAMP NULL,
    `paid_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`),
    KEY `payments_user_id_status_index` (`user_id`, `status`),
    KEY `payments_transaction_id_index` (`transaction_id`),
    KEY `payments_status_created_at_index` (`status`, `created_at`),
    CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `payments_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 18. KYC_VERIFICATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `kyc_verifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'in_review', 'approved', 'rejected', 'expired') NOT NULL DEFAULT 'pending',
    `level` ENUM('basic', 'standard', 'premium') NOT NULL DEFAULT 'basic',
    `cni_front_path` VARCHAR(255) NULL,
    `cni_back_path` VARCHAR(255) NULL,
    `selfie_path` VARCHAR(255) NULL,
    `video_path` VARCHAR(255) NULL,
    `cni_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `selfie_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `address_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `phone_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `reviewed_by` BIGINT UNSIGNED NULL,
    `rejection_reason` TEXT NULL,
    `admin_notes` TEXT NULL,
    `reviewed_at` TIMESTAMP NULL,
    `approved_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    KEY `kyc_verifications_user_id_status_index` (`user_id`, `status`),
    KEY `kyc_verifications_status_created_at_index` (`status`, `created_at`),
    CONSTRAINT `kyc_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `kyc_verifications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 19. PROOF_OF_LOCATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `proof_of_locations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `address_id` BIGINT UNSIGNED NOT NULL,
    `payment_id` BIGINT UNSIGNED NULL,
    `document_type` ENUM('location_plan', 'proof_of_residence') NOT NULL DEFAULT 'location_plan',
    `document_number` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `qr_code_token` VARCHAR(255) NOT NULL,
    `verification_code` VARCHAR(32) NULL,
    `price` INT NOT NULL DEFAULT 0,
    `status` ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active',
    `issued_at` TIMESTAMP NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `revoked_at` TIMESTAMP NULL,
    `revocation_reason` TEXT NULL,
    `download_count` INT NOT NULL DEFAULT 0,
    `last_downloaded_at` TIMESTAMP NULL,
    `qr_scan_count` INT NOT NULL DEFAULT 0,
    `last_scanned_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `proof_of_locations_document_number_unique` (`document_number`),
    UNIQUE KEY `proof_of_locations_qr_code_token_unique` (`qr_code_token`),
    UNIQUE KEY `proof_of_locations_verification_code_unique` (`verification_code`),
    KEY `proof_of_locations_user_id_status_index` (`user_id`, `status`),
    KEY `proof_of_locations_qr_code_token_index` (`qr_code_token`),
    KEY `proof_of_locations_expires_at_status_index` (`expires_at`, `status`),
    CONSTRAINT `proof_of_locations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `proof_of_locations_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `proof_of_locations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 20. INVOICES
-- =====================================================
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `invoice_number` VARCHAR(255) NOT NULL,
    `invoice_type` ENUM('invoice', 'receipt') NOT NULL DEFAULT 'invoice',
    `file_path` VARCHAR(255) NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` INT NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'XAF',
    `tax_amount` INT NOT NULL DEFAULT 0,
    `total_amount` INT NOT NULL,
    `invoice_date` DATE NOT NULL,
    `due_date` DATE NULL,
    `paid_at` TIMESTAMP NULL,
    `access_token` VARCHAR(255) NOT NULL,
    `verification_code` VARCHAR(32) NULL,
    `company_name` VARCHAR(255) NOT NULL DEFAULT 'Ket-Up Sarl',
    `company_address` VARCHAR(255) NULL,
    `company_phone` VARCHAR(255) NULL,
    `company_email` VARCHAR(255) NULL,
    `company_rccm` VARCHAR(255) NULL,
    `company_niu` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
    UNIQUE KEY `invoices_access_token_unique` (`access_token`),
    UNIQUE KEY `invoices_verification_code_unique` (`verification_code`),
    KEY `invoices_user_id_created_at_index` (`user_id`, `created_at`),
    KEY `invoices_access_token_index` (`access_token`),
    CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `invoices_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 21. RECEIPTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `receipts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `invoice_id` BIGINT UNSIGNED NULL,
    `receipt_number` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` INT NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'XAF',
    `payment_method` VARCHAR(255) NULL,
    `transaction_reference` VARCHAR(255) NULL,
    `company_name` VARCHAR(255) NOT NULL DEFAULT 'Ket-Up Sarl',
    `company_address` VARCHAR(255) NULL,
    `company_phone` VARCHAR(255) NULL,
    `company_email` VARCHAR(255) NULL,
    `verification_code` VARCHAR(32) NOT NULL,
    `access_token` VARCHAR(255) NOT NULL,
    `paid_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `receipts_receipt_number_unique` (`receipt_number`),
    UNIQUE KEY `receipts_verification_code_unique` (`verification_code`),
    UNIQUE KEY `receipts_access_token_unique` (`access_token`),
    KEY `receipts_user_id_created_at_index` (`user_id`, `created_at`),
    KEY `receipts_verification_code_index` (`verification_code`),
    KEY `receipts_access_token_index` (`access_token`),
    CONSTRAINT `receipts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `receipts_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `receipts_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 22. WEB_ACCESS_TOKENS
-- =====================================================
CREATE TABLE IF NOT EXISTS `web_access_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `type` ENUM('proof_of_location', 'invoice', 'kyc_status', 'dashboard') NOT NULL DEFAULT 'dashboard',
    `resource_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(255) NULL,
    `user_agent` VARCHAR(255) NULL,
    `usage_count` INT NOT NULL DEFAULT 0,
    `max_usage` INT NOT NULL DEFAULT 1,
    `expires_at` TIMESTAMP NOT NULL,
    `used_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `web_access_tokens_token_unique` (`token`),
    KEY `web_access_tokens_token_index` (`token`),
    KEY `web_access_tokens_user_id_type_index` (`user_id`, `type`),
    KEY `web_access_tokens_expires_at_index` (`expires_at`),
    CONSTRAINT `web_access_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 23. DOMICILIATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `domiciliations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `address_id` BIGINT UNSIGNED NOT NULL,
    `invited_by` BIGINT UNSIGNED NULL,
    `name` VARCHAR(255) NOT NULL DEFAULT 'Domicile',
    `role` ENUM('owner', 'resident', 'visitor') NOT NULL DEFAULT 'resident',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    `invitation_token` VARCHAR(64) NULL,
    `token_expires_at` TIMESTAMP NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `domiciliations_user_id_address_id_unique` (`user_id`, `address_id`),
    UNIQUE KEY `domiciliations_invitation_token_unique` (`invitation_token`),
    KEY `domiciliations_address_id_status_index` (`address_id`, `status`),
    KEY `domiciliations_invitation_token_index` (`invitation_token`),
    CONSTRAINT `domiciliations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `domiciliations_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `domiciliations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 24. MIGRATIONS TABLE (Laravel)
-- =====================================================
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert migration records
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2025_08_26_100418_add_two_factor_columns_to_users_table', 1),
('2026_01_24_191919_create_personal_access_tokens_table', 1),
('2026_01_24_200001_extend_users_table', 1),
('2026_01_24_200002_create_user_settings_table', 1),
('2026_01_24_200003_create_addresses_table', 1),
('2026_01_24_200004_create_collections_table', 1),
('2026_01_24_200005_create_address_collection_table', 1),
('2026_01_24_200006_create_shared_collections_table', 1),
('2026_01_24_200007_create_otp_codes_table', 1),
('2026_01_24_200008_create_refresh_tokens_table', 1),
('2026_01_25_100001_create_delivery_requests_table', 1),
('2026_02_12_144509_create_streets_table', 1),
('2026_02_12_175556_create_tracks_table', 1),
('2026_05_17_000001_create_payments_table', 1),
('2026_05_17_000002_create_kyc_verifications_table', 1),
('2026_05_17_000003_create_proof_of_locations_table', 1),
('2026_05_17_000004_create_invoices_table', 1),
('2026_05_17_000005_create_web_access_tokens_table', 1),
('2026_05_17_000006_add_account_deletion_fields_to_users_table', 1),
('2026_05_17_000007_add_metadata_to_otp_codes_table', 1),
('2026_05_17_070000_create_domiciliations_table', 1),
('2026_05_17_120000_add_document_types_and_verification', 1),
('2026_05_17_130000_update_payments_type_enum', 1),
('2026_05_17_140000_update_users_phone_login', 1),
('2026_05_17_150000_make_transaction_id_nullable', 1),
('2026_05_18_055200_add_lottie_avatar_to_users_table', 1),
('2026_05_18_072630_add_signature_to_users_table', 1),
('2026_05_18_074706_add_itinerary_to_addresses_table', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- DONE! All tables created successfully.
-- =====================================================
