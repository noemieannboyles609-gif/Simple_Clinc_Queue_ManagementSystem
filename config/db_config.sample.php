<?php
/**
 * CareQueue - InfinityFree / Custom MySQL Configuration
 * 
 * Instructions for InfinityFree users:
 * 1. Log in to your InfinityFree Client Area (https://dash.infinityfree.com/)
 * 2. Go to your Account -> "MySQL Databases"
 * 3. Create a new database (e.g., "clinic_queue")
 * 4. Copy your MySQL Details:
 *    - MySQL Hostname (e.g. sql302.infinityfree.com or sqlXXX.epizy.com)
 *    - MySQL Database Name (e.g. if0_38123456_clinic_queue)
 *    - MySQL Username (e.g. if0_38123456)
 *    - MySQL Password (your InfinityFree vPanel / account password)
 * 5. On InfinityFree File Manager (or locally), create a copy of this file named `db_config.php`
 *    inside the `config/` directory with your actual credentials.
 * 
 * Note: `db_config.php` is ignored by Git and CI/CD so your live credentials are never overwritten!
 */

define('DB_HOST', 'sqlXXX.infinityfree.com'); // Replace with your InfinityFree MySQL Hostname
define('DB_USER', 'if0_XXXXXXXX');           // Replace with your InfinityFree MySQL Username
define('DB_PASS', 'your_account_password');  // Replace with your InfinityFree Account Password
define('DB_NAME', 'if0_XXXXXXXX_clinic_db'); // Replace with your Full Database Name
define('DB_PORT', 3306);
