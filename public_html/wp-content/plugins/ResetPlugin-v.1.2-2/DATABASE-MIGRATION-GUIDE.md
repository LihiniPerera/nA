# RESET Plugin - Database Migration System Guide

## 📋 Overview

This guide explains how to safely make database changes to the RESET plugin without losing data in production. The migration system allows you to update database schema while preserving all existing data.

## 🎯 The Problem This Solves

**Before Migration System:**
- Upload new plugin ZIP → Database tables recreated → **Data lost** ❌

**With Migration System:**
- Upload new plugin ZIP → Files updated → Run migration → **Data preserved** ✅

---

## 🏗️ How It Works

### 1. **Version Tracking**
- Each database schema has a version number (e.g., `1.3.0`)
- Current version stored in WordPress options: `reset_plugin_db_version`
- Target version defined in code: `RESET_DB_VERSION`

### 2. **Migration Process**
```
Current: 1.3.0 → Target: 1.3.2
├── Run migrate_to_1_3_1() 
├── Run migrate_to_1_3_2()
└── Update version to 1.3.2
```

### 3. **Safe Updates**
- Uses `ALTER TABLE` instead of `DROP TABLE`
- Checks if changes already exist
- Preserves all existing data
- Logs all operations

---

## 🔧 Making Database Changes

### Step 1: Update the Version

**File:** `reset-ticketing.php`

```php
// Find this line (around line 25)
define('RESET_DB_VERSION', '1.3.0');

// Increment the version
define('RESET_DB_VERSION', '1.3.1');
```

**Version Guidelines:**
- **Patch** (1.3.0 → 1.3.1): Small changes (add column, index)
- **Minor** (1.3.0 → 1.4.0): New tables, significant changes
- **Major** (1.3.0 → 2.0.0): Breaking changes, restructure

### Step 2: Add Migration Method

**File:** `includes/class-reset-migration.php`

#### A) Add to Migration Steps Array

Find the `get_migration_steps()` method and add your version:

```php
$available_migrations = array(
    '1.0.0' => array('method' => 'migrate_to_1_0_0', 'description' => 'Initial database setup'),
    '1.1.0' => array('method' => 'migrate_to_1_1_0', 'description' => 'Add gaming_name column'),
    '1.2.0' => array('method' => 'migrate_to_1_2_0', 'description' => 'Add addon system tables'),
    '1.3.0' => array('method' => 'migrate_to_1_3_0', 'description' => 'Current schema improvements'),
    '1.3.1' => array('method' => 'migrate_to_1_3_1', 'description' => 'Your change description'), // ADD THIS
);
```

#### B) Create Migration Method

Add your migration method at the end of the class (before `force_recreate_tables`):

```php
/**
 * Migration to version 1.3.1 - Your change description
 */
private function migrate_to_1_3_1() {
    // Your database changes here
    
    // Example: Add new column
    $table_purchases = $this->wpdb->prefix . 'reset_purchases';
    
    // Check if column exists first
    $column_exists = $this->wpdb->get_results(
        $this->wpdb->prepare(
            "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
            'new_column_name'
        )
    );
    
    // Add column if it doesn't exist
    if (empty($column_exists)) {
        $result = $this->wpdb->query(
            "ALTER TABLE `{$table_purchases}` 
            ADD COLUMN `new_column_name` varchar(255) NULL 
            AFTER `existing_column`"
        );
        
        if ($result === false) {
            error_log("RESET Plugin: Failed to add new_column_name");
            return false;
        }
        
        error_log("RESET Plugin: Successfully added new_column_name");
    }
    
    return true;
}
```

### Step 3: Test Locally

1. **Create ZIP** of your plugin folder
2. **Upload to local/staging** WordPress
3. **Go to:** RESET Ticketing → Capacity Management
4. **Click:** "🔄 Run Migration" button
5. **Check:** Error logs and database structure

### Step 4: Deploy to Production

1. **Create ZIP** with tested changes
2. **Upload to production** WordPress
3. **Go to:** RESET Ticketing → Capacity Management  
4. **Click:** "🔄 Run Migration" button
5. **Verify:** Migration completed successfully

---

## 📝 Migration Examples

### Example 1: Add New Column

```php
private function migrate_to_1_3_1() {
    $table_purchases = $this->wpdb->prefix . 'reset_purchases';
    
    $column_exists = $this->wpdb->get_results(
        $this->wpdb->prepare(
            "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
            'special_notes'
        )
    );
    
    if (empty($column_exists)) {
        $result = $this->wpdb->query(
            "ALTER TABLE `{$table_purchases}` 
            ADD COLUMN `special_notes` text NULL 
            AFTER `total_amount`"
        );
        
        if ($result === false) {
            return false;
        }
    }
    
    return true;
}
```

### Example 2: Create New Table

```php
private function migrate_to_1_4_0() {
    $charset_collate = $this->wpdb->get_charset_collate();
    $table_new = $this->wpdb->prefix . 'reset_new_table';
    
    // Check if table exists
    $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table_new}'");
    
    if (!$table_exists) {
        $sql = "CREATE TABLE {$table_new} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    return true;
}
```

### Example 3: Modify Existing Column

```php
private function migrate_to_1_3_2() {
    $table_tokens = $this->wpdb->prefix . 'reset_tokens';
    
    // Modify column type
    $result = $this->wpdb->query(
        "ALTER TABLE `{$table_tokens}` 
        MODIFY COLUMN `token_code` varchar(100) NOT NULL"
    );
    
    return $result !== false;
}
```

### Example 4: Add Index

```php
private function migrate_to_1_3_3() {
    $table_purchases = $this->wpdb->prefix . 'reset_purchases';
    
    // Check if index exists
    $index_exists = $this->wpdb->get_results(
        "SHOW INDEX FROM `{$table_purchases}` WHERE Key_name = 'idx_created_at'"
    );
    
    if (empty($index_exists)) {
        $result = $this->wpdb->query(
            "ALTER TABLE `{$table_purchases}` 
            ADD INDEX `idx_created_at` (`created_at`)"
        );
        
        if ($result === false) {
            return false;
        }
    }
    
    return true;
}
```

---

## 🛠️ Admin Tools (Development Mode)

**Access:** RESET Ticketing → Capacity Management → Development Tools

**Note:** Only visible when `WP_DEBUG = true`

### Tools Available:

1. **🔄 Run Migration**
   - Applies pending database migrations
   - Safe to run multiple times
   - Shows success/error messages

2. **🗑️ Force Recreate Tables**
   - ⚠️ **DANGER:** Drops all tables and recreates them
   - **ALL DATA WILL BE LOST**
   - Only use in development/testing

3. **🧹 Clear Caches**
   - Clears WordPress object cache
   - Clears opcache if available
   - Helpful after code changes

### Migration Status Display:

- **Current Version:** Database version currently installed
- **Target Version:** Version defined in code
- **Status:** ✅ Up to Date / ⚠️ Migration Needed
- **Last Migration:** Timestamp of last migration
- **Database Validation:** Shows missing tables if any

---

## 🚨 Best Practices

### ✅ DO:

1. **Always increment version** when making DB changes
2. **Test locally first** before production
3. **Check if changes exist** before applying them
4. **Use proper error handling** in migration methods
5. **Add descriptive comments** to migration methods
6. **Return `true` on success**, `false` on failure
7. **Log important operations** using `error_log()`

### ❌ DON'T:

1. **Never skip version numbers** (1.3.0 → 1.3.2 ❌)
2. **Don't drop tables** unless absolutely necessary
3. **Don't make breaking changes** without major version bump
4. **Don't forget to test** migrations thoroughly
5. **Don't run migrations directly** on production database
6. **Don't modify existing migration methods** once deployed

---

## 🔍 Troubleshooting

### Migration Not Running?

1. **Check version numbers:** Current vs Target in admin UI
2. **Check error logs:** Look for migration error messages
3. **Verify method exists:** Migration method properly defined?
4. **Check permissions:** User has admin access?

### Migration Failed?

1. **Check error logs:** Specific error message
2. **Verify SQL syntax:** Test query manually if needed
3. **Check table exists:** Table name correct?
4. **Database permissions:** WordPress can modify tables?

### Common Error Messages:

```
"Migration method migrate_to_X_X_X not found"
→ Method not defined or typo in method name

"Failed to add column_name"  
→ SQL error, check syntax and table structure

"Migration step migrate_to_X_X_X failed"
→ Method returned false, check error logs for details
```

---

## 📁 File Structure

```
wp-content/plugins/ResetPlugin-v.1.2-2/
├── reset-ticketing.php                    # Main plugin file (RESET_DB_VERSION)
├── includes/
│   └── class-reset-migration.php          # Migration logic
├── admin/
│   └── capacity-management.php            # Admin UI with migration tools
└── DATABASE-MIGRATION-GUIDE.md           # This guide
```

---

## 🔄 Workflow Summary

```
1. Make database changes needed
2. Increment RESET_DB_VERSION
3. Add migration method
4. Test locally
5. Create ZIP
6. Upload to production
7. Run migration via admin UI
8. Verify success
```

---

## 📞 Support

If you encounter issues:

1. **Check this guide** for examples
2. **Review error logs** for specific errors  
3. **Test on staging** environment first
4. **Document any new patterns** for future developers

---

**Last Updated:** July 2025  
**System Version:** 1.3.0  
**Compatible With:** WordPress 5.0+, PHP 7.4+ 