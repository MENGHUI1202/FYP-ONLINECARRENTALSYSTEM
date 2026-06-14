-- Unified Car Rental System database migration
-- Base file to import first:
--   car_rental_system.sql
--
-- Purpose:
-- 1. Keep customer schema as the source of truth.
-- 2. Add admin-side tables and approval fields.
-- 3. Use car_units as the real inventory source.
-- 4. Synchronize booking approval/rejection with unit availability.
-- 5. Synchronize KYC document approval/rejection with users.kyc_status.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

CREATE DATABASE IF NOT EXISTS `car_rental_system`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `car_rental_system`;

-- --------------------------------------------------------
-- Admin accounts and audit trail from the admin system
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'manager',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Suspended',
  `perm_users` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Can manage customers/KYC',
  `perm_fleet` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Can manage cars/categories',
  `perm_bookings` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Can manage bookings/reports',
  `avatar` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `admin_name` varchar(100) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `car_model` varchar(100) DEFAULT NULL,
  `details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_admin_id` (`admin_id`),
  KEY `idx_audit_target` (`target_type`, `target_id`),
  CONSTRAINT `fk_audit_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin`
  (`id`, `username`, `password`, `role`, `is_active`, `perm_users`, `avatar`, `email`, `reset_token`, `token_expiry`, `perm_fleet`, `perm_bookings`)
VALUES
  (1, 'admin_TCF', '$2y$10$jJD7MppZS5F3IonxSu/NSume3WEi6I2XM82FVVxLv0PjixeioaGIW', 'super_admin', 1, 1, 'assets/uploads/1770666188_WhatsApp Image 2026-02-10 at 03.38.53.jpeg', 'clement.lee.jun@student.mmu.edu.my', NULL, NULL, 1, 1),
  (2, 'admin_Menghui', '$2y$10$uFKCJToss5T19ijaZTPZ0eRz3FM346gfldsNkC/a5pc/cA92sU80C', 'manager', 1, 1, 'assets/uploads/1770667061_OIP (2).webp', 'hoo.meng.hui@student.mmu.edu.my', NULL, NULL, 1, 1)
ON DUPLICATE KEY UPDATE
  `role` = VALUES(`role`),
  `is_active` = VALUES(`is_active`),
  `perm_users` = VALUES(`perm_users`),
  `perm_fleet` = VALUES(`perm_fleet`),
  `perm_bookings` = VALUES(`perm_bookings`);

-- --------------------------------------------------------
-- User and KYC unification
-- users remains the single customer table.
-- user_documents remains the single document table.
-- users.kyc_status is a summary for fast admin dashboard display.
-- --------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `id` int(11) DEFAULT NULL FIRST,
  ADD COLUMN IF NOT EXISTS `kyc_status`
    enum('Unverified','Pending','Verified','Rejected') NOT NULL DEFAULT 'Unverified'
    AFTER `profile_picture`,
  ADD COLUMN IF NOT EXISTS `updated_at`
    timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
    AFTER `created_at`;

ALTER TABLE `user_documents`
  ADD COLUMN IF NOT EXISTS `reviewed_by_admin_id` int(11) DEFAULT NULL AFTER `admin_note`,
  ADD COLUMN IF NOT EXISTS `reviewed_at` datetime DEFAULT NULL AFTER `reviewed_by_admin_id`,
  ADD KEY IF NOT EXISTS `idx_user_documents_reviewed_by` (`reviewed_by_admin_id`);

SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_documents'
    AND CONSTRAINT_NAME = 'fk_user_documents_reviewed_by_admin'
);
SET @sql = IF(
  @constraint_exists = 0,
  'ALTER TABLE `user_documents` ADD CONSTRAINT `fk_user_documents_reviewed_by_admin` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- Booking unification
-- bookings remains the single booking header table.
-- booking_items remains the single booking line table.
-- car_units is the real stock source.
-- --------------------------------------------------------

ALTER TABLE `bookings`
  ADD COLUMN IF NOT EXISTS `id` int(11) DEFAULT NULL FIRST,
  MODIFY `booking_status`
    enum('pending','approved','rejected','cancelled','active','completed') NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`,
  ADD COLUMN IF NOT EXISTS `service_fee` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `tax_amount`,
  ADD COLUMN IF NOT EXISTS `security_deposit` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `extra_services_total`,
  ADD COLUMN IF NOT EXISTS `deposit_status`
    enum('pending','captured','refunded') NOT NULL DEFAULT 'pending'
    AFTER `security_deposit`,
  ADD COLUMN IF NOT EXISTS `reviewed_by_admin_id` int(11) DEFAULT NULL AFTER `cancelled_at`,
  ADD COLUMN IF NOT EXISTS `reviewed_at` datetime DEFAULT NULL AFTER `reviewed_by_admin_id`,
  ADD COLUMN IF NOT EXISTS `admin_notes` text DEFAULT NULL AFTER `admin_note`,
  ADD KEY IF NOT EXISTS `idx_bookings_reviewed_by` (`reviewed_by_admin_id`);

SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'bookings'
    AND CONSTRAINT_NAME = 'fk_bookings_reviewed_by_admin'
);
SET @sql = IF(
  @constraint_exists = 0,
  'ALTER TABLE `bookings` ADD CONSTRAINT `fk_bookings_reviewed_by_admin` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `booking_items`
  ADD COLUMN IF NOT EXISTS `id` int(11) DEFAULT NULL FIRST,
  ADD COLUMN IF NOT EXISTS `pickup_state` varchar(80) DEFAULT NULL AFTER `pickup_state_id`,
  ADD COLUMN IF NOT EXISTS `dropoff_state` varchar(80) DEFAULT NULL AFTER `pickup_location`,
  ADD COLUMN IF NOT EXISTS `rental_type`
    enum('daily','hourly') NOT NULL DEFAULT 'daily'
    AFTER `dropoff_location`;

ALTER TABLE `car_units`
  ADD COLUMN IF NOT EXISTS `reserved_booking_id` int(11) DEFAULT NULL AFTER `current_status`,
  ADD KEY IF NOT EXISTS `idx_car_units_status` (`car_id`, `state_id`, `current_status`),
  ADD KEY IF NOT EXISTS `idx_car_units_reserved_booking` (`reserved_booking_id`);

SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'car_units'
    AND CONSTRAINT_NAME = 'fk_car_units_reserved_booking'
);
SET @sql = IF(
  @constraint_exists = 0,
  'ALTER TABLE `car_units` ADD CONSTRAINT `fk_car_units_reserved_booking` FOREIGN KEY (`reserved_booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Compatibility columns for admin fleet pages.
-- The canonical availability still comes from car_units and v_car_stock.
ALTER TABLE `cars`
  ADD COLUMN IF NOT EXISTS `id` int(11) DEFAULT NULL FIRST,
  ADD COLUMN IF NOT EXISTS `brand` varchar(80) DEFAULT NULL AFTER `car_name`,
  ADD COLUMN IF NOT EXISTS `image_url` varchar(700) DEFAULT NULL AFTER `main_image`,
  ADD COLUMN IF NOT EXISTS `specification` text DEFAULT NULL AFTER `description`,
  ADD COLUMN IF NOT EXISTS `price_per_hour` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `price_per_day`,
  ADD COLUMN IF NOT EXISTS `availability` tinyint(1) NOT NULL DEFAULT 1 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `stock_quantity` int(11) NOT NULL DEFAULT 0 AFTER `availability`,
  ADD COLUMN IF NOT EXISTS `is_deleted` tinyint(1) NOT NULL DEFAULT 0 AFTER `stock_quantity`;

ALTER TABLE `brands`
  ADD COLUMN IF NOT EXISTS `id` int(11) DEFAULT NULL FIRST;

CREATE TABLE IF NOT EXISTS `vehicle_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vehicle_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `vehicle_categories` (`id`, `name`, `created_at`)
SELECT `category_id`, `category_name`, CURRENT_TIMESTAMP
FROM `categories`;

ALTER TABLE `cars`
  MODIFY `brand_id` int(11) NOT NULL DEFAULT 1,
  MODIFY `category_id` int(11) NOT NULL DEFAULT 1,
  MODIFY `model` varchar(120) NOT NULL DEFAULT '',
  MODIFY `year` int(11) NOT NULL DEFAULT 2026,
  MODIFY `type` varchar(80) NOT NULL DEFAULT '',
  MODIFY `engine` varchar(120) NOT NULL DEFAULT '',
  MODIFY `horsepower` varchar(80) NOT NULL DEFAULT '',
  MODIFY `torque` varchar(80) NOT NULL DEFAULT '',
  MODIFY `transmission` varchar(80) NOT NULL DEFAULT '',
  MODIFY `drivetrain` varchar(80) NOT NULL DEFAULT '',
  MODIFY `fuel_type` varchar(80) NOT NULL DEFAULT '',
  MODIFY `fuel_consumption` varchar(80) NOT NULL DEFAULT '',
  MODIFY `seats` int(11) NOT NULL DEFAULT 5,
  MODIFY `doors` int(11) NOT NULL DEFAULT 4,
  MODIFY `luggage_capacity` varchar(80) NOT NULL DEFAULT '',
  MODIFY `safety_features` text NULL,
  MODIFY `comfort_features` text NULL,
  MODIFY `entertainment_features` text NULL,
  MODIFY `description` text NULL,
  MODIFY `main_image` varchar(700) NOT NULL DEFAULT '';

UPDATE users SET id = user_id WHERE id IS NULL OR id <> user_id;
UPDATE bookings SET id = booking_id WHERE id IS NULL OR id <> booking_id;
UPDATE booking_items SET id = booking_item_id WHERE id IS NULL OR id <> booking_item_id;
UPDATE cars SET id = car_id WHERE id IS NULL OR id <> car_id;
UPDATE brands SET id = brand_id WHERE id IS NULL OR id <> brand_id;

ALTER TABLE `car_images`
  ADD COLUMN IF NOT EXISTS `id` int(11) DEFAULT NULL FIRST,
  ADD COLUMN IF NOT EXISTS `image_order` int(11) NOT NULL DEFAULT 0 AFTER `image_url`,
  ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `sort_order`,
  MODIFY `image_type` varchar(50) NOT NULL DEFAULT 'gallery',
  MODIFY `sort_order` int(11) NOT NULL DEFAULT 0;

UPDATE car_images SET id = image_id WHERE id IS NULL OR id <> image_id;

UPDATE cars c
LEFT JOIN brands b ON b.brand_id = c.brand_id
SET
  c.brand = COALESCE(c.brand, b.brand_name),
  c.image_url = COALESCE(c.image_url, c.main_image);

UPDATE booking_items bi
LEFT JOIN rental_states rs ON rs.state_id = bi.pickup_state_id
SET
  bi.pickup_state = COALESCE(bi.pickup_state, rs.state_name),
  bi.dropoff_state = COALESCE(bi.dropoff_state, rs.state_name);

UPDATE bookings
SET admin_notes = COALESCE(admin_notes, admin_note);

-- --------------------------------------------------------
-- Views for admin/customer shared reporting
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `v_car_stock` AS
SELECT
  c.car_id,
  c.car_name,
  COUNT(cu.unit_id) AS total_units,
  SUM(CASE WHEN cu.current_status = 'available' THEN 1 ELSE 0 END) AS available_units,
  SUM(CASE WHEN cu.current_status = 'booked' THEN 1 ELSE 0 END) AS booked_units,
  SUM(CASE WHEN cu.current_status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_units,
  SUM(CASE WHEN cu.current_status = 'inactive' THEN 1 ELSE 0 END) AS inactive_units
FROM cars c
LEFT JOIN car_units cu ON cu.car_id = c.car_id
GROUP BY c.car_id, c.car_name;

CREATE OR REPLACE VIEW `v_admin_bookings` AS
SELECT
  b.booking_id AS id,
  b.booking_id,
  b.booking_reference,
  b.user_id,
  u.name AS customer_name,
  u.email AS customer_email,
  u.phone AS customer_phone,
  u.license_number,
  b.payment_method,
  b.deposit_status,
  b.total_amount,
  b.grand_total,
  b.booking_status,
  b.admin_note AS admin_notes,
  b.payment_status,
  b.created_at,
  b.approved_at,
  b.rejected_at,
  b.cancelled_at,
  b.reviewed_by_admin_id,
  b.reviewed_at,
  b.security_deposit
FROM bookings b
LEFT JOIN users u ON u.user_id = b.user_id;

CREATE OR REPLACE VIEW `v_admin_booking_items` AS
SELECT
  bi.booking_item_id AS id,
  bi.booking_item_id,
  bi.booking_id,
  bi.car_id,
  bi.unit_id,
  c.car_name,
  bi.rental_type,
  bi.start_datetime,
  bi.end_datetime,
  bi.rental_days AS duration,
  bi.pickup_state_id,
  bi.pickup_location,
  bi.dropoff_location,
  bi.price_per_day AS base_price,
  bi.subtotal,
  bi.fuel_option,
  bi.fuel_charge,
  bi.insurance_package,
  bi.insurance_charge,
  bi.driver_age_group,
  bi.driver_age_charge,
  bi.addon_services,
  bi.addon_services_charge,
  bi.extra_services_total,
  bi.created_at
FROM booking_items bi
LEFT JOIN cars c ON c.car_id = bi.car_id;

CREATE OR REPLACE VIEW `v_admin_users_kyc` AS
SELECT
  u.user_id AS id,
  u.user_id,
  u.name,
  u.email,
  u.phone,
  u.ic_number,
  u.license_number,
  u.address,
  u.date_of_birth,
  u.profile_picture,
  u.kyc_status,
  MAX(CASE WHEN ud.document_type IN ('IC Photo','IC','Identity Card') THEN ud.file_path END) AS ic_front_image,
  MAX(CASE WHEN ud.document_type IN ('Driving License Photo','Driving License','License') THEN ud.file_path END) AS driving_license_image,
  u.created_at,
  u.updated_at
FROM users u
LEFT JOIN user_documents ud ON ud.user_id = u.user_id
GROUP BY
  u.user_id, u.name, u.email, u.phone, u.ic_number, u.license_number,
  u.address, u.date_of_birth, u.profile_picture, u.kyc_status,
  u.created_at, u.updated_at;

-- --------------------------------------------------------
-- Stored procedures for synchronized admin actions
-- --------------------------------------------------------

DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_refresh_user_kyc_status`$$
CREATE PROCEDURE `sp_refresh_user_kyc_status`(IN p_user_id int)
BEGIN
  DECLARE v_rejected_count int DEFAULT 0;
  DECLARE v_pending_count int DEFAULT 0;
  DECLARE v_verified_required_count int DEFAULT 0;

  SELECT COUNT(*) INTO v_rejected_count
  FROM user_documents
  WHERE user_id = p_user_id
    AND verification_status = 'Rejected';

  SELECT COUNT(*) INTO v_pending_count
  FROM user_documents
  WHERE user_id = p_user_id
    AND verification_status = 'Pending Verification';

  SELECT COUNT(DISTINCT document_type) INTO v_verified_required_count
  FROM user_documents
  WHERE user_id = p_user_id
    AND verification_status = 'Verified'
    AND document_type IN ('IC Photo', 'Driving License Photo');

  IF v_rejected_count > 0 THEN
    UPDATE users SET kyc_status = 'Rejected' WHERE user_id = p_user_id;
  ELSEIF v_verified_required_count >= 2 THEN
    UPDATE users SET kyc_status = 'Verified' WHERE user_id = p_user_id;
  ELSEIF v_pending_count > 0 THEN
    UPDATE users SET kyc_status = 'Pending' WHERE user_id = p_user_id;
  ELSE
    UPDATE users SET kyc_status = 'Unverified' WHERE user_id = p_user_id;
  END IF;
END$$

DROP PROCEDURE IF EXISTS `sp_admin_review_user_document`$$
CREATE PROCEDURE `sp_admin_review_user_document`(
  IN p_document_id int,
  IN p_admin_id int,
  IN p_status varchar(30),
  IN p_admin_note text
)
BEGIN
  DECLARE v_user_id int DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_status NOT IN ('Pending Verification', 'Verified', 'Rejected') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid KYC review status.';
  END IF;

  SELECT user_id INTO v_user_id
  FROM user_documents
  WHERE document_id = p_document_id
  LIMIT 1;

  IF v_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Document not found.';
  END IF;

  START TRANSACTION;

  UPDATE user_documents
  SET
    verification_status = p_status,
    admin_note = p_admin_note,
    reviewed_by_admin_id = NULLIF(p_admin_id, 0),
    reviewed_at = NOW()
  WHERE document_id = p_document_id;

  CALL sp_refresh_user_kyc_status(v_user_id);

  INSERT INTO audit_logs (admin_id, admin_name, action_type, target_type, target_id, details)
  SELECT
    a.id,
    a.username,
    CONCAT('KYC_', UPPER(REPLACE(p_status, ' ', '_'))),
    'user_document',
    p_document_id,
    CONCAT('Document ', p_document_id, ' reviewed as ', p_status)
  FROM admin a
  WHERE a.id = p_admin_id;

  COMMIT;
END$$

DROP PROCEDURE IF EXISTS `sp_admin_review_booking`$$
CREATE PROCEDURE `sp_admin_review_booking`(
  IN p_booking_id int,
  IN p_admin_id int,
  IN p_status varchar(30),
  IN p_admin_note text
)
BEGIN
  DECLARE v_unavailable_units int DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_status NOT IN ('approved', 'rejected', 'cancelled', 'active', 'completed') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid booking review status.';
  END IF;

  START TRANSACTION;

  IF p_status = 'approved' THEN
    SELECT COUNT(*) INTO v_unavailable_units
    FROM booking_items bi
    LEFT JOIN car_units cu ON cu.unit_id = bi.unit_id
    WHERE bi.booking_id = p_booking_id
      AND (
        bi.unit_id IS NULL
        OR cu.unit_id IS NULL
        OR cu.current_status <> 'available'
      );

    IF v_unavailable_units > 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'One or more selected car units are no longer available.';
    END IF;

    UPDATE car_units cu
    INNER JOIN booking_items bi ON bi.unit_id = cu.unit_id
    SET
      cu.current_status = 'booked',
      cu.reserved_booking_id = p_booking_id
    WHERE bi.booking_id = p_booking_id;

    UPDATE bookings
    SET
      booking_status = p_status,
      admin_note = p_admin_note,
      admin_notes = p_admin_note,
      reviewed_by_admin_id = NULLIF(p_admin_id, 0),
      reviewed_at = NOW(),
      approved_at = IF(approved_at IS NULL, NOW(), approved_at)
    WHERE booking_id = p_booking_id;
  ELSEIF p_status = 'active' THEN
    UPDATE bookings
    SET
      booking_status = 'active',
      admin_note = p_admin_note,
      admin_notes = p_admin_note,
      reviewed_by_admin_id = NULLIF(p_admin_id, 0),
      reviewed_at = NOW()
    WHERE booking_id = p_booking_id;
  ELSE
    UPDATE car_units cu
    INNER JOIN booking_items bi ON bi.unit_id = cu.unit_id
    SET
      cu.current_status = 'available',
      cu.reserved_booking_id = NULL
    WHERE bi.booking_id = p_booking_id
      AND cu.reserved_booking_id = p_booking_id;

    UPDATE bookings
    SET
      booking_status = p_status,
      admin_note = p_admin_note,
      admin_notes = p_admin_note,
      reviewed_by_admin_id = NULLIF(p_admin_id, 0),
      reviewed_at = NOW(),
      rejected_at = IF(p_status = 'rejected', NOW(), rejected_at),
      cancelled_at = IF(p_status = 'cancelled', NOW(), cancelled_at)
    WHERE booking_id = p_booking_id;
  END IF;

  INSERT INTO audit_logs (admin_id, admin_name, action_type, target_type, target_id, details)
  SELECT
    a.id,
    a.username,
    CONCAT('BOOKING_', UPPER(p_status)),
    'booking',
    p_booking_id,
    CONCAT('Booking ', p_booking_id, ' reviewed as ', p_status)
  FROM admin a
  WHERE a.id = p_admin_id;

  COMMIT;
END$$

DROP TRIGGER IF EXISTS `trg_user_documents_ai_refresh_kyc`$$
CREATE TRIGGER `trg_user_documents_ai_refresh_kyc`
AFTER INSERT ON `user_documents`
FOR EACH ROW
BEGIN
  CALL sp_refresh_user_kyc_status(NEW.user_id);
END$$

DROP TRIGGER IF EXISTS `trg_user_documents_au_refresh_kyc`$$
CREATE TRIGGER `trg_user_documents_au_refresh_kyc`
AFTER UPDATE ON `user_documents`
FOR EACH ROW
BEGIN
  CALL sp_refresh_user_kyc_status(NEW.user_id);
END$$

DELIMITER ;

-- --------------------------------------------------------
-- Initial synchronization
-- --------------------------------------------------------

UPDATE cars c
LEFT JOIN (
  SELECT
    car_id,
    SUM(CASE WHEN current_status = 'available' THEN 1 ELSE 0 END) AS available_units
  FROM car_units
  GROUP BY car_id
) s ON s.car_id = c.car_id
SET
  c.stock_quantity = COALESCE(s.available_units, 0),
  c.availability = CASE WHEN COALESCE(s.available_units, 0) > 0 THEN 1 ELSE 0 END;

UPDATE users u
SET kyc_status = CASE
  WHEN EXISTS (
    SELECT 1 FROM user_documents ud
    WHERE ud.user_id = u.user_id AND ud.verification_status = 'Rejected'
  ) THEN 'Rejected'
  WHEN (
    SELECT COUNT(DISTINCT ud.document_type)
    FROM user_documents ud
    WHERE ud.user_id = u.user_id
      AND ud.verification_status = 'Verified'
      AND ud.document_type IN ('IC Photo', 'Driving License Photo')
  ) >= 2 THEN 'Verified'
  WHEN EXISTS (
    SELECT 1 FROM user_documents ud
    WHERE ud.user_id = u.user_id AND ud.verification_status = 'Pending Verification'
  ) THEN 'Pending'
  ELSE 'Unverified'
END;

COMMIT;
