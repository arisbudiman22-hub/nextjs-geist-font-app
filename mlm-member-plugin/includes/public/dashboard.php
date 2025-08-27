<?php
/**
 * Member Dashboard Shortcode
 * 
 * @package MLM_Member_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard shortcode handler
 */
function mmp_dashboard_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return mmp_login_required_message();
    }
    
    // Get current member
    $member = mmp_get_current_member();
    if (!$member) {
        return '<div class="mmp-error">Member account not found. Please contact administrator.</div>';
    }
    
    // Check member access
    if (!mmp_member_has_access('free')) {
        return '<div class="mmp-error">Your account is not active. Please contact administrator.</div>';
    }
    
    // Get member statistics
    $stats = mmp_get_member_statistics(get_current_user_id());
    $user = wp_get_current_user();
    
    ob_start();
    ?>
    
    <div class="mmp-member-dashboard">
        <!-- Dashboard Header -->
        <div class="mmp-dashboard-header">
            <h1><?php printf(__('Welcome back, %s!', 'mlm-member-plugin'), esc_html($user->display_name)); ?></h1>
            <div class="member-code">
                <?php printf(__('Member Code: %s', 'mlm-member-plugin'), esc_html($member->member_code)); ?>
            </div>
        </div>
        
        <!-- Statistics Grid -->
        <div class="mmp-stats-grid">
            <div class="mmp-stat-card" data-stat-type="referrals">
                <div class="stat-icon referrals">👥</div>
                <div class="stat-number"><?php echo intval($member->total_referrals); ?></div>
                <div class="stat-label"><?php _e('Total Referrals', 'mlm-member-plugin'); ?></div>
            </div>
            
            <div class="mmp-stat-card" data-stat-type="visits">
                <div class="stat-icon visits">👁️</div>
                <div class="stat-number"><?php echo intval($stats['total_visits']); ?></div>
                <div class="stat-label"><?php _e('Link Visits', 'mlm-member-plugin'); ?></div>
            </div>
            
            <div class="mmp-stat-card" data-stat-type="conversions">
                <div class="stat-icon conversions">✅</div>
                <div class="stat-number"><?php echo intval($stats['total_conversions']); ?></div>
                <div class="stat-label"><?php _e('Conversions', 'mlm-member-plugin'); ?></div>
            </div>
            
            <div class="mmp-stat-card" data-stat-type="conversion_rate">
                <div class="stat-icon earnings">📈</div>
                <div class="stat-number"><?php echo esc_html($stats['conversion_rate']); ?>%</div>
                <div class="stat-label"><?php _e('Conversion Rate', 'mlm-member-plugin'); ?></div>
            </div>
        </div>
        
        <!-- Replica Link Section -->
        <div class="mmp-content-section">
            <div class="mmp-replica-section">
                <h3><?php _e('Your Replica Link', 'mlm-member-plugin'); ?></h3>
                <div class="mmp-replica-url"><?php echo esc_url($member->replica_url); ?></div>
                <div class="mmp-replica-actions">
                    <button class="mmp-btn mmp-btn-primary copy-replica-link" data-url="<?php echo esc_url($member->replica_url); ?>">
                        <?php _e('Copy Link', 'mlm-member-plugin'); ?>
                    </button>
                    <button class="mmp-btn mmp-btn-secondary share-replica-link" data-url="<?php echo esc_url($member->replica_url); ?>">
                        <?php _e('Share Link', 'mlm-member-plugin'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="mmp-content-section">
            <h2><?php _e('Recent Activity', 'mlm-member-plugin'); ?></h2>
            
            <?php
            $recent_referrals = mmp_get_member_referrals($member->id, null);
            $recent_referrals = array_slice($recent_referrals, 0, 5); // Get last 5
            ?>
            
            <?php if (!empty($recent_referrals)): ?>
                <div class="mmp-recent-activity">
                    <h3><?php _e('Recent Referrals', 'mlm-member-plugin'); ?></h3>
                    <div class="mmp-member-list">
                        <?php foreach ($recent_referrals as $referral): ?>
                            <div class="mmp-member-item">
                                <div class="mmp-member-avatar">
                                    <?php echo esc_html(strtoupper(substr($referral->display_name, 0, 1))); ?>
                                </div>
                                <div class="mmp-member-info">
                                    <div class="name"><?php echo esc_html($referral->display_name); ?></div>
                                    <div class="details">
                                        <?php printf(__('Joined %s', 'mlm-member-plugin'), date_i18n(get_option('date_format'), strtotime($referral->registration_date))); ?>
                                    </div>
                                </div>
                                <div class="mmp-member-status <?php echo esc_attr($referral->status); ?>">
                                    <?php echo esc_html(mmp_get_status_label($referral->status)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo esc_url(add_query_arg('mmp_page', 'members', get_permalink())); ?>" class="mmp-btn mmp-btn-secondary">
                            <?php _e('View All Members', 'mlm-member-plugin'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="mmp-empty-state">
                    <p><?php _e('No referrals yet. Start sharing your replica link to grow your network!', 'mlm-member-plugin'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="mmp-content-section">
            <h2><?php _e('Quick Actions', 'mlm-member-plugin'); ?></h2>
            <div class="mmp-quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="<?php echo esc_url(add_query_arg('mmp_page', 'profile', get_permalink())); ?>" class="mmp-btn mmp-btn-primary">
                    <?php _e('Edit Profile', 'mlm-member-plugin'); ?>
                </a>
                
                <a href="<?php echo esc_url(add_query_arg('mmp_page', 'network', get_permalink())); ?>" class="mmp-btn mmp-btn-secondary">
                    <?php _e('View Network', 'mlm-member-plugin'); ?>
                </a>
                
                <a href="<?php echo esc_url(add_query_arg('mmp_page', 'downloads', get_permalink())); ?>" class="mmp-btn mmp-btn-secondary">
                    <?php _e('Downloads', 'mlm-member-plugin'); ?>
                </a>
                
                <a href="<?php echo esc_url(add_query_arg('mmp_page', 'statistics', get_permalink())); ?>" class="mmp-btn mmp-btn-secondary">
                    <?php _e('Statistics', 'mlm-member-plugin'); ?>
                </a>
            </div>
        </div>
        
        <!-- Tips Section -->
        <div class="mmp-content-section">
            <h2><?php _e('Tips for Success', 'mlm-member-plugin'); ?></h2>
            <div class="mmp-tips-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="mmp-tip-card" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3><?php _e('Share Your Link', 'mlm-member-plugin'); ?></h3>
                    <p><?php _e('Share your replica link on social media, with friends, and in your network to attract new members.', 'mlm-member-plugin'); ?></p>
                </div>
                
                <div class="mmp-tip-card" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3><?php _e('Stay Active', 'mlm-member-plugin'); ?></h3>
                    <p><?php _e('Regular activity and engagement help build trust and attract more referrals to your network.', 'mlm-member-plugin'); ?></p>
                </div>
                
                <div class="mmp-tip-card" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3><?php _e('Support Your Team', 'mlm-member-plugin'); ?></h3>
                    <p><?php _e('Help your referrals succeed by providing guidance and support. Their success is your success!', 'mlm-member-plugin'); ?></p>
                </div>
            </div>
        </div>
        
        <?php if (mmp_member_has_access('active')): ?>
            <!-- Premium Features (Active Members Only) -->
            <div class="mmp-content-section">
                <h2><?php _e('Premium Features', 'mlm-member-plugin'); ?></h2>
                <div class="mmp-premium-features">
                    <p><?php _e('As an active member, you have access to:', 'mlm-member-plugin'); ?></p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 5px 0;">✅ <?php _e('Advanced statistics and analytics', 'mlm-member-plugin'); ?></li>
                        <li style="padding: 5px 0;">✅ <?php _e('Premium downloads and resources', 'mlm-member-plugin'); ?></li>
                        <li style="padding: 5px 0;">✅ <?php _e('Priority support', 'mlm-member-plugin'); ?></li>
                        <li style="padding: 5px 0;">✅ <?php _e('Advanced network management tools', 'mlm-member-plugin'); ?></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Login required message
 */
function mmp_login_required_message() {
    $login_url = wp_login_url(get_permalink());
    
    ob_start();
    ?>
    <div class="mmp-login-required">
        <div class="mmp-content-section" style="text-align: center; padding: 40px 20px;">
            <h2><?php _e('Member Area Access Required', 'mlm-member-plugin'); ?></h2>
            <p><?php _e('Please log in to access your member dashboard.', 'mlm-member-plugin'); ?></p>
            <div style="margin-top: 20px;">
                <a href="<?php echo esc_url($login_url); ?>" class="mmp-btn mmp-btn-primary">
                    <?php _e('Login', 'mlm-member-plugin'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * AJAX handler for getting statistics
 */
add_action('wp_ajax_mmp_get_stat', 'mmp_ajax_get_stat');
function mmp_ajax_get_stat() {
    check_ajax_referer('mmp_member_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_die(__('Unauthorized access', 'mlm-member-plugin'));
    }
    
    $stat_type = sanitize_text_field($_POST['stat_type']);
    $user_id = get_current_user_id();
    $member = mmp_get_member_by_user_id($user_id);
    
    if (!$member) {
        wp_send_json_error(array('message' => __('Member not found', 'mlm-member-plugin')));
    }
    
    $value = 0;
    
    switch ($stat_type) {
        case 'referrals':
            $value = intval($member->total_referrals);
            break;
            
        case 'visits':
            $stats = mmp_get_member_statistics($user_id);
            $value = intval($stats['total_visits']);
            break;
            
        case 'conversions':
            $stats = mmp_get_member_statistics($user_id);
            $value = intval($stats['total_conversions']);
            break;
            
        case 'conversion_rate':
            $stats = mmp_get_member_statistics($user_id);
            $value = floatval($stats['conversion_rate']);
            break;
    }
    
    wp_send_json_success(array('value' => $value));
}

/**
 * Handle replica link visits
 */
add_action('init', 'mmp_handle_replica_visit');
function mmp_handle_replica_visit() {
    // Check if this is a replica link visit
    $request_uri = $_SERVER['REQUEST_URI'];
    
    if (strpos($request_uri, '/ref/') !== false) {
        $member_code = basename($request_uri);
        $member = mmp_get_member_by_code($member_code);
        
        if ($member) {
            // Record the visit
            mmp_record_statistic($member->user_id, 'visit', 1, wp_get_referer());
            
            // Store referrer in session for registration
            if (!session_id()) {
                session_start();
            }
            $_SESSION['mmp_referrer'] = $member->id;
            
            // Redirect to registration page or home page
            $settings = get_option('mmp_general_settings', array());
            $redirect_url = !empty($settings['registration_page']) ? get_permalink($settings['registration_page']) : home_url();
            
            wp_redirect($redirect_url);
            exit;
        }
    }
}
