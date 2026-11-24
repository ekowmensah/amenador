-- ADOMGRACE LEGACY POS: OPERATIONAL RESET FOR ALL BUSINESSES
-- DESTRUCTIVE: Export a full SQL backup in phpMyAdmin before importing this file.
-- Select the existing POS database. Close every POS tab and display app first.
-- TRUNCATE is not transactional: this reset cannot be rolled back.
-- No stored procedures, CREATE ROUTINE privilege, terminal or fixed database name required.
-- Optional module tables are skipped if not installed.
-- Keeps: users/passwords, roles/permissions, businesses, locations, subscriptions,
-- system/business settings, taxes, account definitions,
-- invoice layouts, restaurant table definitions and desktop display pairings.
-- Clears: products, brands, units, categories/subcategories, stock, customers/suppliers, sales/purchases/expenses/payments,
-- registers, bookings, operational module records, logs and queued work.
-- Recreates one empty default Walk-In Customer per business (required by POS).
-- User records remain; obsolete CRM contact and category-based department/designation links are cleared.
-- Uploaded files on disk and file-based caches/sessions are not deleted by SQL.

SELECT DATABASE() AS selected_database;
SET @reset_previous_fk_checks = @@SESSION.FOREIGN_KEY_CHECKS;
SET SESSION FOREIGN_KEY_CHECKS = 0;

SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'media' AND COLUMN_NAME = 'model_type'), 'DELETE FROM `media` WHERE REPLACE(`model_type`, CHAR(92), ''/'') IN (''App/Product'',''App/Transaction'',''App/Contact'',''App/DocumentAndNote'',''App/Asset'',''App/Project'',''App/RepairJobSheet'')', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'crm_contact_id'), 'UPDATE `users` SET `crm_contact_id` = NULL WHERE `crm_contact_id` IS NOT NULL', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'essentials_department_id'), 'UPDATE `users` SET `essentials_department_id` = NULL WHERE `essentials_department_id` IS NOT NULL', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'essentials_designation_id'), 'UPDATE `users` SET `essentials_designation_id` = NULL WHERE `essentials_designation_id` IS NOT NULL', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- account_transactions
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'account_transactions' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `account_transactions`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- accounting_acc_trans_mappings
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounting_acc_trans_mappings' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `accounting_acc_trans_mappings`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- accounting_accounts_transactions
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounting_accounts_transactions' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `accounting_accounts_transactions`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- accounting_budgets
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounting_budgets' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `accounting_budgets`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- activity_log
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `activity_log`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- aiassistance_history
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aiassistance_history' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `aiassistance_history`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- asset_maintenances
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_maintenances' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `asset_maintenances`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- asset_transactions
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_transactions' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `asset_transactions`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- asset_warranties
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_warranties' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `asset_warranties`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- assets
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'assets' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `assets`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- bookings
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `bookings`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- business_profits
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'business_profits' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `business_profits`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- cash_denominations
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cash_denominations' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `cash_denominations`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- cash_register_transactions
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cash_register_transactions' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `cash_register_transactions`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- cash_registers
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cash_registers' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `cash_registers`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- brands
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brands' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `brands`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- categories
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `categories`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- categorizables
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categorizables' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `categorizables`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- units
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'units' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `units`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- contacts
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contacts' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `contacts`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_call_logs
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_call_logs' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_call_logs`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_campaigns
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_campaigns' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_campaigns`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_contact_person_commissions
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_contact_person_commissions' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_contact_person_commissions`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_followup_invoices
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_followup_invoices' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_followup_invoices`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_lead_users
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_lead_users' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_lead_users`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_proposals
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_proposals' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_proposals`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_schedule_logs
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_schedule_logs' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_schedule_logs`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_schedule_users
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_schedule_users' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_schedule_users`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- crm_schedules
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_schedules' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `crm_schedules`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- discount_variations
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'discount_variations' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `discount_variations`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- discounts
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'discounts' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `discounts`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- document_and_notes
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'document_and_notes' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `document_and_notes`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_attendances
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_attendances' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_attendances`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_document_shares
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_document_shares' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_document_shares`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_documents
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_documents' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_documents`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_leaves
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_leaves' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_leaves`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_messages
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_messages' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_messages`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_payroll_group_transactions
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_payroll_group_transactions' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_payroll_group_transactions`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_payroll_groups
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_payroll_groups' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_payroll_groups`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_reminders
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_reminders' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_reminders`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_todo_comments
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_todo_comments' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_todo_comments`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_todos_users
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_todos_users' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_todos_users`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- essentials_to_dos
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'essentials_to_dos' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `essentials_to_dos`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- hms_booking_extras
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hms_booking_extras' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `hms_booking_extras`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- hms_booking_lines
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hms_booking_lines' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `hms_booking_lines`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- hms_room_unavailables
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hms_room_unavailables' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `hms_room_unavailables`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- mfg_recipe_ingredients
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mfg_recipe_ingredients' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `mfg_recipe_ingredients`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- mfg_recipes
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mfg_recipes' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `mfg_recipes`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- notifications
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `notifications`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- partner_payments
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'partner_payments' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `partner_payments`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- partners_assets
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'partners_assets' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `partners_assets`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- partners
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'partners' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `partners`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- pjt_invoice_lines
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pjt_invoice_lines' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `pjt_invoice_lines`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- pjt_project_members
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pjt_project_members' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `pjt_project_members`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- pjt_project_task_comments
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pjt_project_task_comments' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `pjt_project_task_comments`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- pjt_project_task_members
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pjt_project_task_members' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `pjt_project_task_members`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- pjt_project_tasks
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pjt_project_tasks' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `pjt_project_tasks`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- pjt_project_time_logs
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pjt_project_time_logs' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `pjt_project_time_logs`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- pjt_projects
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pjt_projects' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `pjt_projects`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- product_locations
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_locations' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `product_locations`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- product_racks
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_racks' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `product_racks`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- product_variations
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variations' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `product_variations`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- products
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `products`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- purchase_lines
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_lines' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `purchase_lines`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- reference_counts
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reference_counts' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `reference_counts`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- repair_job_sheets
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_job_sheets' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `repair_job_sheets`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- res_product_modifier_sets
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'res_product_modifier_sets' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `res_product_modifier_sets`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- sell_line_warranties
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sell_line_warranties' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `sell_line_warranties`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- sheet_spreadsheet_shares
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sheet_spreadsheet_shares' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `sheet_spreadsheet_shares`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- sheet_spreadsheets
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sheet_spreadsheets' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `sheet_spreadsheets`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- stock_adjustment_lines
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_adjustment_lines' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `stock_adjustment_lines`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- stock_adjustments_temp
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_adjustments_temp' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `stock_adjustments_temp`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- stock_adjustments
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_adjustments' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `stock_adjustments`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- superadmin_communicator_logs
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'superadmin_communicator_logs' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `superadmin_communicator_logs`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- transaction_payments
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction_payments' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `transaction_payments`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- transaction_sell_lines
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction_sell_lines' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `transaction_sell_lines`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- transaction_sell_lines_purchase_lines
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction_sell_lines_purchase_lines' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `transaction_sell_lines_purchase_lines`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- transactions
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `transactions`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- user_contact_access
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_contact_access' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `user_contact_access`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- variation_group_prices
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'variation_group_prices' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `variation_group_prices`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- variation_location_details
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'variation_location_details' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `variation_location_details`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- variations
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'variations' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `variations`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- woocommerce_sync_logs
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'woocommerce_sync_logs' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `woocommerce_sync_logs`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- jobs
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `jobs`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- job_batches
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_batches' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `job_batches`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- failed_jobs
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'failed_jobs' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `failed_jobs`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- sessions
SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sessions' AND TABLE_TYPE = 'BASE TABLE'), 'TRUNCATE TABLE `sessions`', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoice_schemes' AND COLUMN_NAME = 'invoice_count'), 'UPDATE `invoice_schemes` SET `invoice_count` = 0', 'DO 0');
PREPARE reset_stmt FROM @reset_sql;
EXECUTE reset_stmt;
DEALLOCATE PREPARE reset_stmt;

-- Preserve invoice prefixes and configured starting numbers; only usage counters reset.
INSERT INTO contacts (business_id, type, name, mobile, created_by, is_default, created_at, updated_at)
SELECT b.id, 'customer', 'Walk-In Customer', '', b.owner_id, 1, NOW(), NOW()
FROM business b;

SET SESSION FOREIGN_KEY_CHECKS = @reset_previous_fk_checks;

-- Expected: operational counts 0, users/locations retained, walk-in count = businesses.
SELECT (SELECT COUNT(*) FROM users) AS users_kept,
       (SELECT COUNT(*) FROM business) AS businesses_kept,
       (SELECT COUNT(*) FROM business_locations) AS locations_kept,
       (SELECT COUNT(*) FROM products) AS products_remaining,
       (SELECT COUNT(*) FROM brands) AS brands_remaining,
       (SELECT COUNT(*) FROM units) AS units_remaining,
       (SELECT COUNT(*) FROM categories) AS categories_remaining,
       (SELECT COUNT(*) FROM transactions) AS transactions_remaining,
       (SELECT COUNT(*) FROM purchase_lines) AS purchase_lines_remaining,
       (SELECT COUNT(*) FROM contacts WHERE is_default = 0) AS customer_supplier_records_remaining,
       (SELECT COUNT(*) FROM contacts WHERE is_default = 1) AS default_walk_in_customers;
-- Reopen/login to the POS after importing. File-cached display orders expire automatically.
