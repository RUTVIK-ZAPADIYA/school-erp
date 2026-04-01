-- Add password reset and email verification fields to users table
ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(255) NULL AFTER `updated_at`;
ALTER TABLE `users` ADD COLUMN `reset_token_expiry` DATETIME NULL AFTER `reset_token`;
ALTER TABLE `users` ADD COLUMN `email_verification_token` VARCHAR(255) NULL AFTER `reset_token_expiry`;
ALTER TABLE `users` ADD COLUMN `email_verification_expiry` DATETIME NULL AFTER `email_verification_token`;
ALTER TABLE `users` ADD COLUMN `email_verified` TINYINT(1) DEFAULT 0 AFTER `email_verification_expiry`;
