<?php
/**
 * Helper functions for MLM Member Plugin
 * 
 * @package MLM_Member_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get total members count
 */
function mmp_get_total_members() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}

/**
 * Get active members count
 */
function mmp_get_active_members() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
}

/**
 * Get pending members count
 */
function mmp_get_pending_members() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
}

/**
 * Get recent registrations count (last 7 days)
 */
function mmp_get_recent_registrations() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
}

/**
 * Check if user is MLM member
 */
function mmp_is_member($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $member = mmp_get_member_by_user_id($user_id);
    return !empty($member);
}

/**
 * Get current member data
 */
function mmp_get_current_member() {
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        return false;
    }
    
    return mmp_get_member_by_user_id($user_id);
}

/**
 * Check if member has access to content based on status
 */
function mmp_member_has_access($required_status = 'active', $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $member = mmp_get_member_by_user_id($user_id);
    
    if (!$member) {
        return false;
    }
    
    $status_hierarchy = array(
        'free' => 1,
        'pending' => 2,
        'inactive' => 3,
        'active' => 4
    );
    
    $member_level = isset($status_hierarchy[$member->status]) ? $status_hierarchy[$member->status] : 0;
    $required_level = isset($status_hierarchy[$required_status]) ? $status_hierarchy[$required_status] : 4;
    
    return $member_level >= $required_level;
}

/**
 * Get member's referrals
 */
function mmp_get_member_referrals($member_id, $status = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_members';
    
    $sql = "SELECT m.*, u.display_name, u.user_email 
            FROM $table_name m 
            LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
            WHERE m.sponsor_id = %d";
    
    $params = array($member_id);
    
    if ($status) {
        $sql .= " AND m.status = %s";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY m.registration_date DESC";
    
    return $wpdb->get_results($wpdb->prepare($sql, $params));
}

/**
 * Get member's network tree
 */
function mmp_get_member_network_tree($member_id, $levels = 3) {
    $tree = array();
    $current_level = array($member_id);
    
    for ($level = 0; $level < $levels; $level++) {
        $next_level = array();
        
        foreach ($current_level as $parent_id) {
            $referrals = mmp_get_member_referrals($parent_id);
            
            if (!empty($referrals)) {
                foreach ($referrals as $referral) {
                    $tree[$level][] = $referral;
                    $next_level[] = $referral->id;
                }
            }
        }
        
        if (empty($next_level)) {
            break;
        }
        
        $current_level = $next_level;
    }
    
    return $tree;
}

/**
 * Generate member statistics
 */
function mmp_get_member_statistics($user_id) {
    global $wpdb;
    
    $member = mmp_get_member_by_user_id($user_id);
    
    if (!$member) {
        return false;
    }
    
    $stats_table = $wpdb->prefix . 'mlm_statistics';
    
    // Get visit statistics for the last 30 days
    $visits = $wpdb->get_results($wpdb->prepare(
        "SELECT recorded_date, SUM(stat_value) as total_visits 
         FROM $stats_table 
         WHERE user_id = %d AND stat_type = 'visit' AND recorded_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         GROUP BY recorded_date 
         ORDER BY recorded_date ASC",
        $user_id
    ));
    
    // Get total statistics
    $total_visits = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(stat_value) FROM $stats_table WHERE user_id = %d AND stat_type = 'visit'",
        $user_id
    ));
    
    $total_clicks = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(stat_value) FROM $stats_table WHERE user_id = %d AND stat_type = 'click'",
        $user_id
    ));
    
    $total_conversions = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(stat_value) FROM $stats_table WHERE user_id = %d AND stat_type = 'conversion'",
        $user_id
    ));
    
    return array(
        'member' => $member,
        'daily_visits' => $visits,
        'total_visits' => intval($total_visits),
        'total_clicks' => intval($total_clicks),
        'total_conversions' => intval($total_conversions),
        'conversion_rate' => $total_clicks > 0 ? round(($total_conversions / $total_clicks) * 100, 2) : 0
    );
}

/**
 * Record member statistic
 */
function mmp_record_statistic($user_id, $stat_type, $value = 1, $referrer_url = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_statistics';
    $today = current_time('Y-m-d');
    
    // Check if record exists for today
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND stat_type = %s AND recorded_date = %s",
        $user_id,
        $stat_type,
        $today
    ));
    
    if ($existing) {
        // Update existing record
        $wpdb->update(
            $table_name,
            array('stat_value' => $existing->stat_value + $value),
            array('id' => $existing->id),
            array('%d'),
            array('%d')
        );
    } else {
        // Insert new record
        $wpdb->insert($table_name, array(
            'user_id' => $user_id,
            'stat_type' => $stat_type,
            'stat_value' => $value,
            'referrer_url' => $referrer_url,
            'ip_address' => mmp_get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'recorded_date' => $today
        ));
    }
}

/**
 * Get client IP address
 */
function mmp_get_client_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Send email using template
 */
function mmp_send_template_email($template_name, $to_email, $variables = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_email_templates';
    
    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE template_name = %s AND status = 'active'",
        $template_name
    ));
    
    if (!$template) {
        return false;
    }
    
    $subject = $template->subject;
    $content = $template->content;
    
    // Replace variables
    foreach ($variables as $key => $value) {
        $subject = str_replace('{{' . $key . '}}', $value, $subject);
        $content = str_replace('{{' . $key . '}}', $value, $content);
    }
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    return wp_mail($to_email, $subject, $content, $headers);
}

/**
 * Get available downloads for member
 */
function mmp_get_member_downloads($user_id) {
    global $wpdb;
    
    $member = mmp_get_member_by_user_id($user_id);
    
    if (!$member) {
        return array();
    }
    
    $table_name = $wpdb->prefix . 'mlm_downloads';
    
    $access_levels = array('all');
    
    if ($member->status === 'active') {
        $access_levels[] = 'active';
        $access_levels[] = 'premium';
    } elseif ($member->status === 'pending' || $member->status === 'free') {
        // Only 'all' access level
    }
    
    $placeholders = implode(',', array_fill(0, count($access_levels), '%s'));
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE status = 'active' AND access_level IN ($placeholders) ORDER BY created_at DESC",
        ...$access_levels
    ));
}

/**
 * Log download activity
 */
function mmp_log_download($download_id, $user_id) {
    global $wpdb;
    
    $downloads_table = $wpdb->prefix . 'mlm_downloads';
    $logs_table = $wpdb->prefix . 'mlm_download_logs';
    
    // Insert log entry
    $wpdb->insert($logs_table, array(
        'download_id' => $download_id,
        'user_id' => $user_id,
        'ip_address' => mmp_get_client_ip()
    ));
    
    // Update download count
    $wpdb->query($wpdb->prepare(
        "UPDATE $downloads_table SET download_count = download_count + 1 WHERE id = %d",
        $download_id
    ));
}

/**
 * Get form by ID
 */
function mmp_get_form($form_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_forms';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND status = 'active'",
        $form_id
    ));
}

/**
 * Get form by type
 */
function mmp_get_form_by_type($form_type) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_forms';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE form_type = %s AND status = 'active' ORDER BY created_at DESC LIMIT 1",
        $form_type
    ));
}

/**
 * Process form submission
 */
function mmp_process_form_submission($form_id, $form_data, $referrer_id = null) {
    global $wpdb;
    
    $form = mmp_get_form($form_id);
    
    if (!$form) {
        return array('success' => false, 'message' => __('Form not found', 'mlm-member-plugin'));
    }
    
    $form_fields = json_decode($form->form_fields, true);
    $form_settings = json_decode($form->form_settings, true);
    
    // Validate required fields
    foreach ($form_fields as $field) {
        if (isset($field['required']) && $field['required'] && empty($form_data[$field['name']])) {
            return array(
                'success' => false, 
                'message' => sprintf(__('Field "%s" is required', 'mlm-member-plugin'), $field['label'])
            );
        }
    }
    
    // Handle registration form
    if ($form->form_type === 'registration') {
        return mmp_process_registration($form_data, $form_settings, $referrer_id);
    }
    
    // Store form submission
    $submissions_table = $wpdb->prefix . 'mlm_form_submissions';
    
    $submission_id = $wpdb->insert($submissions_table, array(
        'form_id' => $form_id,
        'user_id' => get_current_user_id(),
        'referrer_id' => $referrer_id,
        'submission_data' => json_encode($form_data),
        'ip_address' => mmp_get_client_ip(),
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
    ));
    
    if ($submission_id) {
        return array('success' => true, 'message' => __('Form submitted successfully', 'mlm-member-plugin'));
    }
    
    return array('success' => false, 'message' => __('Failed to submit form', 'mlm-member-plugin'));
}

/**
 * Process registration
 */
function mmp_process_registration($form_data, $form_settings, $referrer_id = null) {
    // Validate email
    if (!is_email($form_data['email'])) {
        return array('success' => false, 'message' => __('Invalid email address', 'mlm-member-plugin'));
    }
    
    // Check if email already exists
    if (email_exists($form_data['email'])) {
        return array('success' => false, 'message' => __('Email already registered', 'mlm-member-plugin'));
    }
    
    // Validate password
    if (strlen($form_data['password']) < 6) {
        return array('success' => false, 'message' => __('Password must be at least 6 characters', 'mlm-member-plugin'));
    }
    
    if ($form_data['password'] !== $form_data['confirm_password']) {
        return array('success' => false, 'message' => __('Passwords do not match', 'mlm-member-plugin'));
    }
    
    // Create WordPress user
    $user_id = wp_create_user(
        $form_data['email'],
        $form_data['password'],
        $form_data['email']
    );
    
    if (is_wp_error($user_id)) {
        return array('success' => false, 'message' => $user_id->get_error_message());
    }
    
    // Update user meta
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => sanitize_text_field($form_data['first_name']),
        'last_name' => sanitize_text_field($form_data['last_name']),
        'display_name' => sanitize_text_field($form_data['first_name'] . ' ' . $form_data['last_name'])
    ));
    
    // Add phone number
    if (!empty($form_data['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($form_data['phone']));
    }
    
    // Determine sponsor
    $sponsor_id = null;
    if ($referrer_id) {
        $sponsor_id = $referrer_id;
    } else {
        $settings = get_option('mmp_general_settings', array());
        if (isset($settings['default_sponsor']) && $settings['default_sponsor'] !== 'random') {
            $sponsor_id = intval($settings['default_sponsor']);
        }
    }
    
    // Create MLM member record
    $member_data = array(
        'user_id' => $user_id,
        'sponsor_id' => $sponsor_id,
        'status' => isset($form_settings['auto_activate']) && $form_settings['auto_activate'] ? 'active' : 'pending',
        'custom_fields' => $form_data
    );
    
    $member_id = mmp_insert_member($member_data);
    
    if ($member_id) {
        // Send welcome email
        if (isset($form_settings['send_welcome_email']) && $form_settings['send_welcome_email']) {
            $member = mmp_get_member_by_user_id($user_id);
            $variables = array(
                'member_name' => $form_data['first_name'] . ' ' . $form_data['last_name'],
                'member_code' => $member->member_code,
                'replica_url' => $member->replica_url,
                'member_area_url' => get_permalink(get_option('mmp_member_area_page'))
            );
            
            mmp_send_template_email('welcome_email', $form_data['email'], $variables);
        }
        
        // Notify sponsor
        if ($sponsor_id) {
            $sponsor = mmp_get_member_by_user_id($sponsor_id);
            if ($sponsor) {
                $sponsor_user = get_user_by('ID', $sponsor->user_id);
                $variables = array(
                    'sponsor_name' => $sponsor_user->display_name,
                    'referral_name' => $form_data['first_name'] . ' ' . $form_data['last_name'],
                    'registration_date' => current_time('F j, Y'),
                    'network_url' => get_permalink(get_option('mmp_member_area_page')) . '?page=network'
                );
                
                mmp_send_template_email('new_referral_notification', $sponsor_user->user_email, $variables);
                
                // Add notification
                mmp_add_notification(
                    $sponsor->user_id,
                    __('New Referral!', 'mlm-member-plugin'),
                    sprintf(__('You have a new referral: %s', 'mlm-member-plugin'), $form_data['first_name'] . ' ' . $form_data['last_name']),
                    'success'
                );
            }
        }
        
        return array(
            'success' => true, 
            'message' => __('Registration successful! Please check your email for login details.', 'mlm-member-plugin'),
            'user_id' => $user_id,
            'member_id' => $member_id
        );
    }
    
    return array('success' => false, 'message' => __('Registration failed. Please try again.', 'mlm-member-plugin'));
}

/**
 * Sanitize and validate form data
 */
function mmp_sanitize_form_data($data) {
    $sanitized = array();
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = mmp_sanitize_form_data($value);
        } else {
            switch ($key) {
                case 'email':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                case 'phone':
                    $sanitized[$key] = preg_replace('/[^0-9+\-\s\(\)]/', '', $value);
                    break;
                case 'url':
                case 'website':
                    $sanitized[$key] = esc_url_raw($value);
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }
    }
    
    return $sanitized;
}
