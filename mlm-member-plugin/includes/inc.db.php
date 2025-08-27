<?php
/**
 * Database operations for MLM Member Plugin
 * 
 * @package MLM_Member_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create database tables on plugin activation
 */
function mmp_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Members table
    $table_members = $wpdb->prefix . 'mlm_members';
    $sql_members = "CREATE TABLE $table_members (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        sponsor_id mediumint(9) DEFAULT NULL,
        member_code varchar(50) NOT NULL,
        status enum('active','inactive','pending','free') DEFAULT 'pending',
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        activation_date datetime DEFAULT NULL,
        replica_url varchar(255) DEFAULT NULL,
        total_referrals int(11) DEFAULT 0,
        level_position int(11) DEFAULT 1,
        custom_fields longtext DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id),
        UNIQUE KEY member_code (member_code),
        KEY sponsor_id (sponsor_id),
        KEY status (status)
    ) $charset_collate;";
    
    // Forms table
    $table_forms = $wpdb->prefix . 'mlm_forms';
    $sql_forms = "CREATE TABLE $table_forms (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_name varchar(255) NOT NULL,
        form_type enum('registration','profile','prospect','subscribe','custom') DEFAULT 'custom',
        form_fields longtext NOT NULL,
        form_settings longtext DEFAULT NULL,
        status enum('active','inactive') DEFAULT 'active',
        created_by bigint(20) UNSIGNED NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY form_type (form_type),
        KEY status (status)
    ) $charset_collate;";
    
    // Form submissions table
    $table_submissions = $wpdb->prefix . 'mlm_form_submissions';
    $sql_submissions = "CREATE TABLE $table_submissions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_id mediumint(9) NOT NULL,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        referrer_id mediumint(9) DEFAULT NULL,
        submission_data longtext NOT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        status enum('pending','processed','rejected') DEFAULT 'pending',
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        processed_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY form_id (form_id),
        KEY user_id (user_id),
        KEY referrer_id (referrer_id),
        KEY status (status)
    ) $charset_collate;";
    
    // Downloads table
    $table_downloads = $wpdb->prefix . 'mlm_downloads';
    $sql_downloads = "CREATE TABLE $table_downloads (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text DEFAULT NULL,
        file_url varchar(500) NOT NULL,
        file_type varchar(50) DEFAULT NULL,
        file_size bigint(20) DEFAULT NULL,
        access_level enum('all','active','premium') DEFAULT 'active',
        download_count int(11) DEFAULT 0,
        status enum('active','inactive') DEFAULT 'active',
        created_by bigint(20) UNSIGNED NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY access_level (access_level),
        KEY status (status)
    ) $charset_collate;";
    
    // Download logs table
    $table_download_logs = $wpdb->prefix . 'mlm_download_logs';
    $sql_download_logs = "CREATE TABLE $table_download_logs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        download_id mediumint(9) NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        ip_address varchar(45) DEFAULT NULL,
        downloaded_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY download_id (download_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    // Notifications table
    $table_notifications = $wpdb->prefix . 'mlm_notifications';
    $sql_notifications = "CREATE TABLE $table_notifications (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        title varchar(255) NOT NULL,
        message text NOT NULL,
        type enum('info','success','warning','error') DEFAULT 'info',
        is_read tinyint(1) DEFAULT 0,
        action_url varchar(500) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        read_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY is_read (is_read),
        KEY type (type)
    ) $charset_collate;";
    
    // Statistics table
    $table_statistics = $wpdb->prefix . 'mlm_statistics';
    $sql_statistics = "CREATE TABLE $table_statistics (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        stat_type enum('visit','click','conversion','referral') NOT NULL,
        stat_value int(11) DEFAULT 1,
        referrer_url varchar(500) DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        recorded_date date NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY stat_type (stat_type),
        KEY recorded_date (recorded_date)
    ) $charset_collate;";
    
    // Email templates table
    $table_email_templates = $wpdb->prefix . 'mlm_email_templates';
    $sql_email_templates = "CREATE TABLE $table_email_templates (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        template_name varchar(255) NOT NULL,
        template_type enum('welcome','activation','notification','reminder','custom') NOT NULL,
        subject varchar(500) NOT NULL,
        content longtext NOT NULL,
        variables longtext DEFAULT NULL,
        status enum('active','inactive') DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY template_name (template_name),
        KEY template_type (template_type),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create tables
    $tables = array(
        $sql_members,
        $sql_forms,
        $sql_submissions,
        $sql_downloads,
        $sql_download_logs,
        $sql_notifications,
        $sql_statistics,
        $sql_email_templates
    );
    
    foreach ($tables as $sql) {
        $result = dbDelta($sql);
        if ($wpdb->last_error) {
            error_log('MLM Plugin DB Error: ' . $wpdb->last_error);
        }
    }
    
    // Insert default email templates
    mmp_insert_default_email_templates();
    
    // Insert default form
    mmp_insert_default_registration_form();
}

/**
 * Insert default email templates
 */
function mmp_insert_default_email_templates() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_email_templates';
    
    $default_templates = array(
        array(
            'template_name' => 'welcome_email',
            'template_type' => 'welcome',
            'subject' => 'Welcome to Our MLM Network!',
            'content' => '<h2>Welcome {{member_name}}!</h2>
                         <p>Thank you for joining our network. Your member code is: <strong>{{member_code}}</strong></p>
                         <p>Your replica link: <a href="{{replica_url}}">{{replica_url}}</a></p>
                         <p>Login to your member area: <a href="{{member_area_url}}">Member Area</a></p>',
            'variables' => '["member_name","member_code","replica_url","member_area_url"]'
        ),
        array(
            'template_name' => 'activation_email',
            'template_type' => 'activation',
            'subject' => 'Your Account Has Been Activated',
            'content' => '<h2>Congratulations {{member_name}}!</h2>
                         <p>Your account has been activated and you now have full access to all member benefits.</p>
                         <p>Login to your member area: <a href="{{member_area_url}}">Member Area</a></p>',
            'variables' => '["member_name","member_area_url"]'
        ),
        array(
            'template_name' => 'new_referral_notification',
            'template_type' => 'notification',
            'subject' => 'New Referral Registered!',
            'content' => '<h2>Great News {{sponsor_name}}!</h2>
                         <p>You have a new referral: <strong>{{referral_name}}</strong></p>
                         <p>Registration Date: {{registration_date}}</p>
                         <p>View your network: <a href="{{network_url}}">Network Tree</a></p>',
            'variables' => '["sponsor_name","referral_name","registration_date","network_url"]'
        )
    );
    
    foreach ($default_templates as $template) {
        $wpdb->insert($table_name, $template);
    }
}

/**
 * Insert default registration form
 */
function mmp_insert_default_registration_form() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_forms';
    
    $default_form_fields = array(
        array(
            'type' => 'text',
            'name' => 'first_name',
            'label' => 'First Name',
            'required' => true,
            'placeholder' => 'Enter your first name'
        ),
        array(
            'type' => 'text',
            'name' => 'last_name',
            'label' => 'Last Name',
            'required' => true,
            'placeholder' => 'Enter your last name'
        ),
        array(
            'type' => 'email',
            'name' => 'email',
            'label' => 'Email Address',
            'required' => true,
            'placeholder' => 'Enter your email address'
        ),
        array(
            'type' => 'tel',
            'name' => 'phone',
            'label' => 'Phone Number',
            'required' => true,
            'placeholder' => 'Enter your phone number'
        ),
        array(
            'type' => 'password',
            'name' => 'password',
            'label' => 'Password',
            'required' => true,
            'placeholder' => 'Create a password'
        ),
        array(
            'type' => 'password',
            'name' => 'confirm_password',
            'label' => 'Confirm Password',
            'required' => true,
            'placeholder' => 'Confirm your password'
        )
    );
    
    $form_settings = array(
        'redirect_after_submit' => '',
        'auto_activate' => false,
        'send_welcome_email' => true,
        'require_sponsor' => false
    );
    
    $wpdb->insert($table_name, array(
        'form_name' => 'Default Registration Form',
        'form_type' => 'registration',
        'form_fields' => json_encode($default_form_fields),
        'form_settings' => json_encode($form_settings),
        'status' => 'active',
        'created_by' => 1
    ));
}

/**
 * Get member by user ID
 */
function mmp_get_member_by_user_id($user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d",
        $user_id
    ));
}

/**
 * Get member by member code
 */
function mmp_get_member_by_code($member_code) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE member_code = %s",
        $member_code
    ));
}

/**
 * Insert new member
 */
function mmp_insert_member($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    // Generate unique member code
    $member_code = mmp_generate_member_code();
    
    $member_data = array(
        'user_id' => $data['user_id'],
        'sponsor_id' => isset($data['sponsor_id']) ? $data['sponsor_id'] : null,
        'member_code' => $member_code,
        'status' => isset($data['status']) ? $data['status'] : 'pending',
        'replica_url' => mmp_generate_replica_url($member_code),
        'custom_fields' => isset($data['custom_fields']) ? json_encode($data['custom_fields']) : null
    );
    
    $result = $wpdb->insert($table_name, $member_data);
    
    if ($result) {
        // Update sponsor's referral count
        if (!empty($data['sponsor_id'])) {
            mmp_update_referral_count($data['sponsor_id']);
        }
        
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Update member status
 */
function mmp_update_member_status($member_id, $status) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    $update_data = array('status' => $status);
    
    if ($status === 'active') {
        $update_data['activation_date'] = current_time('mysql');
    }
    
    return $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $member_id),
        array('%s', '%s'),
        array('%d')
    );
}

/**
 * Generate unique member code
 */
function mmp_generate_member_code() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    do {
        $code = 'MEM' . strtoupper(wp_generate_password(8, false));
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE member_code = %s",
            $code
        ));
    } while ($exists > 0);
    
    return $code;
}

/**
 * Generate replica URL
 */
function mmp_generate_replica_url($member_code) {
    $settings = get_option('mmp_general_settings', array());
    $site_url = get_site_url();
    
    if (isset($settings['replica_url_type']) && $settings['replica_url_type'] === 'subdomain') {
        // For subdomain implementation (requires additional server configuration)
        $parsed_url = parse_url($site_url);
        return $parsed_url['scheme'] . '://' . strtolower($member_code) . '.' . $parsed_url['host'];
    } else {
        // Default: username after domain
        return $site_url . '/ref/' . strtolower($member_code);
    }
}

/**
 * Update referral count
 */
function mmp_update_referral_count($sponsor_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE sponsor_id = %d",
        $sponsor_id
    ));
    
    $wpdb->update(
        $table_name,
        array('total_referrals' => $count),
        array('id' => $sponsor_id),
        array('%d'),
        array('%d')
    );
}

/**
 * Get user notifications
 */
function mmp_get_user_notifications($user_id, $limit = 10) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_notifications';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
        $user_id,
        $limit
    ));
}

/**
 * Mark notification as read
 */
function mmp_mark_notification_read($notification_id, $user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_notifications';
    
    return $wpdb->update(
        $table_name,
        array(
            'is_read' => 1,
            'read_at' => current_time('mysql')
        ),
        array(
            'id' => $notification_id,
            'user_id' => $user_id
        ),
        array('%d', '%s'),
        array('%d', '%d')
    );
}

/**
 * Add notification
 */
function mmp_add_notification($user_id, $title, $message, $type = 'info', $action_url = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_notifications';
    
    return $wpdb->insert($table_name, array(
        'user_id' => $user_id,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'action_url' => $action_url
    ));
}
