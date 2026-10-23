'use strict';
// Generates a phpMyAdmin-compatible SQL file. Never connects to a database.
const fs = require('node:fs');
const path = require('node:path');
const tables = [
    'account_transactions', 'accounting_acc_trans_mappings', 'accounting_accounts_transactions', 'accounting_budgets',
    'activity_log', 'aiassistance_history', 'asset_maintenances', 'asset_transactions', 'asset_warranties', 'assets',
    'bookings', 'business_profits', 'cash_denominations', 'cash_register_transactions', 'cash_registers',
    'brands', 'categories', 'categorizables', 'units', 'contacts', 'crm_call_logs', 'crm_campaigns', 'crm_contact_person_commissions',
    'crm_followup_invoices', 'crm_lead_users', 'crm_proposals', 'crm_schedule_logs', 'crm_schedule_users', 'crm_schedules',
    'discount_variations', 'discounts', 'document_and_notes',
    'essentials_attendances', 'essentials_document_shares', 'essentials_documents', 'essentials_leaves',
    'essentials_messages', 'essentials_payroll_group_transactions', 'essentials_payroll_groups', 'essentials_reminders',
    'essentials_todo_comments', 'essentials_todos_users', 'essentials_to_dos',
    'hms_booking_extras', 'hms_booking_lines', 'hms_room_unavailables',
    'mfg_recipe_ingredients', 'mfg_recipes', 'notifications', 'partner_payments', 'partners_assets', 'partners',
    'pjt_invoice_lines', 'pjt_project_members', 'pjt_project_task_comments', 'pjt_project_task_members',
    'pjt_project_tasks', 'pjt_project_time_logs', 'pjt_projects', 'product_locations', 'product_racks',
    'product_variations', 'products', 'purchase_lines', 'reference_counts', 'repair_job_sheets',
    'res_product_modifier_sets', 'sell_line_warranties', 'sheet_spreadsheet_shares', 'sheet_spreadsheets',
    'stock_adjustment_lines', 'stock_adjustments_temp', 'stock_adjustments', 'superadmin_communicator_logs',
    'transaction_payments', 'transaction_sell_lines', 'transaction_sell_lines_purchase_lines', 'transactions',
    'user_contact_access', 'variation_group_prices', 'variation_location_details', 'variations', 'woocommerce_sync_logs',
    'jobs', 'job_batches', 'failed_jobs', 'sessions',
];
const protectedTables = ['users', 'business', 'business_locations', 'roles', 'permissions', 'model_has_roles',
    'model_has_permissions', 'role_has_permissions', 'migrations', 'system', 'currencies', 'invoice_layouts',
    'invoice_schemes', 'subscriptions', 'packages', 'customer_display_devices'];
if (tables.some(table => protectedTables.includes(table)) || new Set(tables).size !== tables.length) throw new Error('Invalid reset scope.');
const literal = value => "'" + value.replaceAll("'", "''") + "'";
function optionalTable(table, statement) {
    return `-- ${table}\nSET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ${literal(table)} AND TABLE_TYPE = 'BASE TABLE'), ${literal(statement)}, 'DO 0');\nPREPARE reset_stmt FROM @reset_sql;\nEXECUTE reset_stmt;\nDEALLOCATE PREPARE reset_stmt;\n\n`;
}
function optionalColumn(table, column, statement) {
    return `SET @reset_sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ${literal(table)} AND COLUMN_NAME = ${literal(column)}), ${literal(statement)}, 'DO 0');\nPREPARE reset_stmt FROM @reset_sql;\nEXECUTE reset_stmt;\nDEALLOCATE PREPARE reset_stmt;\n\n`;
}
let sql = `-- ADOMGRACE LEGACY POS: OPERATIONAL RESET FOR ALL BUSINESSES
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

`;
// Remove attachments belonging to deleted operational records; retain user/business media.
sql += optionalColumn('media', 'model_type', "DELETE FROM `media` WHERE REPLACE(`model_type`, CHAR(92), '/') IN ('App/Product','App/Transaction','App/Contact','App/DocumentAndNote','App/Asset','App/Project','App/RepairJobSheet')");
// A CRM contact link must not point at a deleted contact or a reused numeric ID.
sql += optionalColumn('users', 'crm_contact_id', 'UPDATE `users` SET `crm_contact_id` = NULL WHERE `crm_contact_id` IS NOT NULL');
// Departments/designations also reference the shared categories table; never delete users.
for (const column of ['essentials_department_id', 'essentials_designation_id']) {
    sql += optionalColumn('users', column, 'UPDATE `users` SET `' + column + '` = NULL WHERE `' + column + '` IS NOT NULL');
}
for (const table of tables) sql += optionalTable(table, 'TRUNCATE TABLE `'+table+'`');
sql += optionalColumn('invoice_schemes', 'invoice_count', 'UPDATE `invoice_schemes` SET `invoice_count` = 0');
sql += `-- Preserve invoice prefixes and configured starting numbers; only usage counters reset.
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
`;
const destination = path.resolve(__dirname, '../database/reset-operational-data-keep-users-locations.sql');
fs.writeFileSync(destination, sql);
console.log('Generated operational reset SQL (' + tables.length + ' optional tables). No database was modified.');
