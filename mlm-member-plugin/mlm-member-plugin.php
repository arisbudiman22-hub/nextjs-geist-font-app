<?php
/**
 * Plugin Name: MLM Member Recruitment Plugin
 * Plugin URI: https://example.com/mlm-member-plugin
 * Description: Comprehensive MLM/Affiliate member recruitment plugin with admin management, member area, form builder, and Elementor integration.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: mlm-member-plugin
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MMP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MMP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MMP_VERSION', '1.0.0');

/**
 * Main MLM Member Plugin Class
 */
class MLM_Member_Plugin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Include required files
        $this->includes();
        
        // Initialize admin area
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
        
        // Initialize public area
        add_action('wp_enqueue_scripts', array($this, 'public_scripts'));
        add_action('wp_ajax_mmp_get_notifications', array($this, 'ajax_get_notifications'));
        add_action('wp_ajax_mmp_update_member_status', array($this, 'ajax_update_member_status'));
        
        // Register shortcodes
        $this->register_shortcodes();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once MMP_PLUGIN_PATH . 'includes/inc.functions.php';
        require_once MMP_PLUGIN_PATH . 'includes/inc.db.php';
        
        // Include admin files
        if (is_admin()) {
            require_once MMP_PLUGIN_PATH . 'includes/admin/pengaturan-umum.php';
            require_once MMP_PLUGIN_PATH . 'includes/admin/email-config.php';
            require_once MMP_PLUGIN_PATH . 'includes/admin/form-builder.php';
            require_once MMP_PLUGIN_PATH . 'includes/admin/member-list.php';
            require_once MMP_PLUGIN_PATH . 'includes/admin/atur-download.php';
            require_once MMP_PLUGIN_PATH . 'includes/admin/shortcode-builder.php';
        }
        
        // Include public files
        require_once MMP_PLUGIN_PATH . 'includes/public/dashboard.php';
        require_once MMP_PLUGIN_PATH . 'includes/public/profile.php';
        require_once MMP_PLUGIN_PATH . 'includes/public/member.php';
        require_once MMP_PLUGIN_PATH . 'includes/public/jaringan.php';
        require_once MMP_PLUGIN_PATH . 'includes/public/download.php';
        require_once MMP_PLUGIN_PATH . 'includes/public/statistik.php';
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('mlm-member-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (function_exists('mmp_create_tables')) {
            mmp_create_tables();
        }
        
        // Set default options
        $default_options = array(
            'member_area_page' => '',
            'registration_page' => '',
            'success_page' => '',
            'admin_email' => get_option('admin_email'),
            'admin_contact' => '',
            'default_sponsor' => 'random',
            'replica_url_type' => 'username',
            'bank_details' => array()
        );
        
        add_option('mmp_general_settings', $default_options);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up temporary data if needed
        flush_rewrite_rules();
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_menu_page(
            __('MLM Members', 'mlm-member-plugin'),
            __('MLM Members', 'mlm-member-plugin'),
            'manage_options',
            'mlm-members',
            array($this, 'admin_dashboard'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'mlm-members',
            __('General Settings', 'mlm-member-plugin'),
            __('General Settings', 'mlm-member-plugin'),
            'manage_options',
            'mlm-general-settings',
            'mmp_pengaturan_umum_page'
        );
        
        add_submenu_page(
            'mlm-members',
            __('Email Configuration', 'mlm-member-plugin'),
            __('Email Configuration', 'mlm-member-plugin'),
            'manage_options',
            'mlm-email-config',
            'mmp_email_config_page'
        );
        
        add_submenu_page(
            'mlm-members',
            __('Form Builder', 'mlm-member-plugin'),
            __('Form Builder', 'mlm-member-plugin'),
            'manage_options',
            'mlm-form-builder',
            'mmp_form_builder_page'
        );
        
        add_submenu_page(
            'mlm-members',
            __('Member List', 'mlm-member-plugin'),
            __('Member List', 'mlm-member-plugin'),
            'manage_options',
            'mlm-member-list',
            'mmp_member_list_page'
        );
        
        add_submenu_page(
            'mlm-members',
            __('Downloads', 'mlm-member-plugin'),
            __('Downloads', 'mlm-member-plugin'),
            'manage_options',
            'mlm-downloads',
            'mmp_atur_download_page'
        );
        
        add_submenu_page(
            'mlm-members',
            __('Shortcode Builder', 'mlm-member-plugin'),
            __('Shortcode Builder', 'mlm-member-plugin'),
            'manage_options',
            'mlm-shortcode-builder',
            'mmp_shortcode_builder_page'
        );
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('MLM Member Management Dashboard', 'mlm-member-plugin'); ?></h1>
            <div class="mmp-dashboard-grid">
                <div class="mmp-dashboard-card">
                    <h3><?php _e('Total Members', 'mlm-member-plugin'); ?></h3>
                    <p class="mmp-stat-number"><?php echo mmp_get_total_members(); ?></p>
                </div>
                <div class="mmp-dashboard-card">
                    <h3><?php _e('Active Members', 'mlm-member-plugin'); ?></h3>
                    <p class="mmp-stat-number"><?php echo mmp_get_active_members(); ?></p>
                </div>
                <div class="mmp-dashboard-card">
                    <h3><?php _e('Pending Members', 'mlm-member-plugin'); ?></h3>
                    <p class="mmp-stat-number"><?php echo mmp_get_pending_members(); ?></p>
                </div>
                <div class="mmp-dashboard-card">
                    <h3><?php _e('Recent Registrations', 'mlm-member-plugin'); ?></h3>
                    <p class="mmp-stat-number"><?php echo mmp_get_recent_registrations(); ?></p>
                </div>
            </div>
            
            <div class="mmp-dashboard-section">
                <h2><?php _e('Quick Actions', 'mlm-member-plugin'); ?></h2>
                <div class="mmp-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=mlm-member-list'); ?>" class="button button-primary">
                        <?php _e('Manage Members', 'mlm-member-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mlm-form-builder'); ?>" class="button button-secondary">
                        <?php _e('Create Form', 'mlm-member-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mlm-general-settings'); ?>" class="button button-secondary">
                        <?php _e('Settings', 'mlm-member-plugin'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'mlm-') !== false) {
            wp_enqueue_style('mmp-admin-style', MMP_PLUGIN_URL . 'assets/admin.css', array(), MMP_VERSION);
            wp_enqueue_script('mmp-admin-script', MMP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), MMP_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('mmp-admin-script', 'mmp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mmp_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'mlm-member-plugin'),
                    'processing' => __('Processing...', 'mlm-member-plugin'),
                    'error' => __('An error occurred. Please try again.', 'mlm-member-plugin')
                )
            ));
        }
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function public_scripts() {
        if (is_user_logged_in() && mmp_is_member_area()) {
            wp_enqueue_style('mmp-member-style', MMP_PLUGIN_URL . 'assets/member.css', array(), MMP_VERSION);
            wp_enqueue_script('mmp-member-script', MMP_PLUGIN_URL . 'assets/js/member.js', array('jquery'), MMP_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('mmp-member-script', 'mmp_member_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mmp_member_nonce'),
                'user_id' => get_current_user_id(),
                'strings' => array(
                    'loading' => __('Loading...', 'mlm-member-plugin'),
                    'error' => __('An error occurred. Please try again.', 'mlm-member-plugin')
                )
            ));
        }
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('mlm_dashboard', 'mmp_dashboard_shortcode');
        add_shortcode('mlm_profile', 'mmp_profile_shortcode');
        add_shortcode('mlm_members', 'mmp_members_shortcode');
        add_shortcode('mlm_network', 'mmp_network_shortcode');
        add_shortcode('mlm_downloads', 'mmp_downloads_shortcode');
        add_shortcode('mlm_statistics', 'mmp_statistics_shortcode');
        add_shortcode('mlm_form', 'mmp_form_shortcode');
        add_shortcode('mlm_login', 'mmp_login_shortcode');
        add_shortcode('mlm_logout', 'mmp_logout_shortcode');
    }
    
    /**
     * AJAX handler for getting notifications
     */
    public function ajax_get_notifications() {
        check_ajax_referer('mmp_member_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(__('Unauthorized access', 'mlm-member-plugin'));
        }
        
        $user_id = get_current_user_id();
        $notifications = mmp_get_user_notifications($user_id);
        
        wp_send_json_success($notifications);
    }
    
    /**
     * AJAX handler for updating member status
     */
    public function ajax_update_member_status() {
        check_ajax_referer('mmp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'mlm-member-plugin'));
        }
        
        $member_id = intval($_POST['member_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = mmp_update_member_status($member_id, $status);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Member status updated successfully', 'mlm-member-plugin')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update member status', 'mlm-member-plugin')));
        }
    }
}

// Initialize the plugin
new MLM_Member_Plugin();

/**
 * Helper function to check if current page is member area
 */
function mmp_is_member_area() {
    global $post;
    if (!$post) return false;
    
    $member_shortcodes = array('mlm_dashboard', 'mlm_profile', 'mlm_members', 'mlm_network', 'mlm_downloads', 'mlm_statistics');
    
    foreach ($member_shortcodes as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            return true;
        }
    }
    
    return false;
}
