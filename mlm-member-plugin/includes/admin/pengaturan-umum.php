<?php
/**
 * General Settings Admin Page
 * 
 * @package MLM_Member_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display general settings page
 */
function mmp_pengaturan_umum_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'mlm-member-plugin'));
    }
    
    // Handle form submission
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['mmp_general_nonce'], 'mmp_general_settings')) {
        mmp_save_general_settings();
    }
    
    // Get current settings
    $settings = get_option('mmp_general_settings', array());
    $default_settings = array(
        'member_area_page' => '',
        'registration_page' => '',
        'success_page' => '',
        'admin_email' => get_option('admin_email'),
        'admin_contact' => '',
        'default_sponsor' => 'random',
        'replica_url_type' => 'username',
        'bank_details' => array(),
        'network_tree_levels' => 3,
        'enable_notifications' => true,
        'auto_approve_members' => false
    );
    
    $settings = wp_parse_args($settings, $default_settings);
    ?>
    
    <div class="wrap">
        <h1><?php _e('General Settings', 'mlm-member-plugin'); ?></h1>
        
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Settings saved successfully!', 'mlm-member-plugin'); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('mmp_general_settings', 'mmp_general_nonce'); ?>
            
            <div class="mmp-settings-container">
                <!-- Page Settings -->
                <div class="mmp-settings-section">
                    <h2><?php _e('Page Settings', 'mlm-member-plugin'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="member_area_page"><?php _e('Member Area Page', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <?php
                                wp_dropdown_pages(array(
                                    'name' => 'member_area_page',
                                    'id' => 'member_area_page',
                                    'selected' => $settings['member_area_page'],
                                    'show_option_none' => __('Select a page', 'mlm-member-plugin'),
                                    'option_none_value' => ''
                                ));
                                ?>
                                <p class="description">
                                    <?php _e('Select the page where members will access their dashboard. Add shortcodes like [mlm_dashboard] to this page.', 'mlm-member-plugin'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="registration_page"><?php _e('Registration Page', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <?php
                                wp_dropdown_pages(array(
                                    'name' => 'registration_page',
                                    'id' => 'registration_page',
                                    'selected' => $settings['registration_page'],
                                    'show_option_none' => __('Select a page', 'mlm-member-plugin'),
                                    'option_none_value' => ''
                                ));
                                ?>
                                <p class="description">
                                    <?php _e('Select the page for member registration. Add [mlm_form type="registration"] shortcode to this page.', 'mlm-member-plugin'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="success_page"><?php _e('Success Page', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <?php
                                wp_dropdown_pages(array(
                                    'name' => 'success_page',
                                    'id' => 'success_page',
                                    'selected' => $settings['success_page'],
                                    'show_option_none' => __('Select a page', 'mlm-member-plugin'),
                                    'option_none_value' => ''
                                ));
                                ?>
                                <p class="description">
                                    <?php _e('Page to redirect users after successful registration or form submission.', 'mlm-member-plugin'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Contact Settings -->
                <div class="mmp-settings-section">
                    <h2><?php _e('Contact Settings', 'mlm-member-plugin'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="admin_email"><?php _e('Admin Email', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="admin_email" name="admin_email" value="<?php echo esc_attr($settings['admin_email']); ?>" class="regular-text" />
                                <p class="description">
                                    <?php _e('Email address for admin notifications and member communications.', 'mlm-member-plugin'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="admin_contact"><?php _e('Admin Contact Number', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="admin_contact" name="admin_contact" value="<?php echo esc_attr($settings['admin_contact']); ?>" class="regular-text" />
                                <p class="description">
                                    <?php _e('Contact number displayed to members for support.', 'mlm-member-plugin'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Sponsor Settings -->
                <div class="mmp-settings-section">
                    <h2><?php _e('Sponsor Settings', 'mlm-member-plugin'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="default_sponsor"><?php _e('Default Sponsor', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <select id="default_sponsor" name="default_sponsor">
                                    <option value="random" <?php selected($settings['default_sponsor'], 'random'); ?>>
                                        <?php _e('Random Assignment', 'mlm-member-plugin'); ?>
                                    </option>
                                    <option value="admin" <?php selected($settings['default_sponsor'], 'admin'); ?>>
                                        <?php _e('Admin (Site Owner)', 'mlm-member-plugin'); ?>
                                    </option>
                                    <?php
                                    // Get existing members for sponsor selection
                                    global $wpdb;
                                    $members = $wpdb->get_results("SELECT m.id, m.member_code, u.display_name 
                                                                  FROM {$wpdb->prefix}mlm_members m 
                                                                  LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
                                                                  WHERE m.status = 'active' 
                                                                  ORDER BY u.display_name");
                                    
                                    foreach ($members as $member) {
                                        echo '<option value="' . esc_attr($member->id) . '" ' . selected($settings['default_sponsor'], $member->id, false) . '>';
                                        echo esc_html($member->display_name . ' (' . $member->member_code . ')');
                                        echo '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description">
                                    <?php _e('Default sponsor assigned to new members when no referrer is specified.', 'mlm-member-plugin'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Replica URL Settings -->
                <div class="mmp-settings-section">
                    <h2><?php _e('Replica URL Settings', 'mlm-member-plugin'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="replica_url_type"><?php _e('URL Type', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="replica_url_type" value="username" <?php checked($settings['replica_url_type'], 'username'); ?> />
                                        <?php _e('Username after domain', 'mlm-member-plugin'); ?>
                                        <span class="description">(<?php echo get_site_url(); ?>/ref/membercode)</span>
                                    </label><br>
                                    
                                    <label>
                                        <input type="radio" name="replica_url_type" value="subdomain" <?php checked($settings['replica_url_type'], 'subdomain'); ?> />
                                        <?php _e('Subdomain', 'mlm-member-plugin'); ?>
                                        <span class="description">(membercode.<?php echo parse_url(get_site_url(), PHP_URL_HOST); ?>)</span>
                                    </label>
                                </fieldset>
                                <p class="description">
                                    <?php _e('Choose how member replica URLs are structured. Subdomain requires additional server configuration.', 'mlm-member-plugin'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Network Settings -->
                <div class="mmp-settings-section">
                    <h2><?php _e('Network Settings', 'mlm-member-plugin'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="network_tree_levels"><?php _e('Network Tree Levels', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="network_tree_levels" name="network_tree_levels" value="<?php echo esc_attr($settings['network_tree_levels']); ?>" min="1" max="10" class="small-text" />
                                <p class="description">
                                    <?php _e('Number of levels to display in the network tree (1-10).', 'mlm-member-plugin'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Member Settings -->
                <div class="mmp-settings-section">
                    <h2><?php _e('Member Settings', 'mlm-member-plugin'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="enable_notifications"><?php _e('Enable Notifications', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable_notifications" name="enable_notifications" value="1" <?php checked($settings['enable_notifications'], true); ?> />
                                    <?php _e('Enable real-time notifications for members', 'mlm-member-plugin'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="auto_approve_members"><?php _e('Auto Approve Members', 'mlm-member-plugin'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="auto_approve_members" name="auto_approve_members" value="1" <?php checked($settings['auto_approve_members'], true); ?> />
                                    <?php _e('Automatically activate new member registrations', 'mlm-member-plugin'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Bank Details -->
                <div class="mmp-settings-section">
                    <h2><?php _e('Payment Bank Details', 'mlm-member-plugin'); ?></h2>
                    <div id="bank-details-container">
                        <?php
                        $bank_details = isset($settings['bank_details']) ? $settings['bank_details'] : array();
                        if (empty($bank_details)) {
                            $bank_details = array(array('bank_name' => '', 'account_name' => '', 'account_number' => '', 'branch' => ''));
                        }
                        
                        foreach ($bank_details as $index => $bank) {
                            mmp_render_bank_detail_row($index, $bank);
                        }
                        ?>
                    </div>
                    
                    <button type="button" id="add-bank-detail" class="button button-secondary">
                        <?php _e('Add Bank Account', 'mlm-member-plugin'); ?>
                    </button>
                </div>
            </div>
            
            <?php submit_button(__('Save Settings', 'mlm-member-plugin')); ?>
        </form>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var bankIndex = <?php echo count($bank_details); ?>;
        
        $('#add-bank-detail').click(function() {
            var newRow = `
                <div class="bank-detail-row" data-index="${bankIndex}">
                    <h4><?php _e('Bank Account', 'mlm-member-plugin'); ?> ${bankIndex + 1}</h4>
                    <table class="form-table">
                        <tr>
                            <th><label for="bank_name_${bankIndex}"><?php _e('Bank Name', 'mlm-member-plugin'); ?></label></th>
                            <td><input type="text" name="bank_details[${bankIndex}][bank_name]" id="bank_name_${bankIndex}" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="account_name_${bankIndex}"><?php _e('Account Name', 'mlm-member-plugin'); ?></label></th>
                            <td><input type="text" name="bank_details[${bankIndex}][account_name]" id="account_name_${bankIndex}" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="account_number_${bankIndex}"><?php _e('Account Number', 'mlm-member-plugin'); ?></label></th>
                            <td><input type="text" name="bank_details[${bankIndex}][account_number]" id="account_number_${bankIndex}" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="branch_${bankIndex}"><?php _e('Branch', 'mlm-member-plugin'); ?></label></th>
                            <td><input type="text" name="bank_details[${bankIndex}][branch]" id="branch_${bankIndex}" class="regular-text" /></td>
                        </tr>
                    </table>
                    <button type="button" class="button button-link-delete remove-bank-detail"><?php _e('Remove', 'mlm-member-plugin'); ?></button>
                </div>
            `;
            
            $('#bank-details-container').append(newRow);
            bankIndex++;
        });
        
        $(document).on('click', '.remove-bank-detail', function() {
            $(this).closest('.bank-detail-row').remove();
        });
    });
    </script>
    
    <?php
}

/**
 * Render bank detail row
 */
function mmp_render_bank_detail_row($index, $bank) {
    ?>
    <div class="bank-detail-row" data-index="<?php echo $index; ?>">
        <h4><?php printf(__('Bank Account %d', 'mlm-member-plugin'), $index + 1); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bank_name_<?php echo $index; ?>"><?php _e('Bank Name', 'mlm-member-plugin'); ?></label>
                </th>
                <td>
                    <input type="text" name="bank_details[<?php echo $index; ?>][bank_name]" id="bank_name_<?php echo $index; ?>" value="<?php echo esc_attr($bank['bank_name']); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="account_name_<?php echo $index; ?>"><?php _e('Account Name', 'mlm-member-plugin'); ?></label>
                </th>
                <td>
                    <input type="text" name="bank_details[<?php echo $index; ?>][account_name]" id="account_name_<?php echo $index; ?>" value="<?php echo esc_attr($bank['account_name']); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="account_number_<?php echo $index; ?>"><?php _e('Account Number', 'mlm-member-plugin'); ?></label>
                </th>
                <td>
                    <input type="text" name="bank_details[<?php echo $index; ?>][account_number]" id="account_number_<?php echo $index; ?>" value="<?php echo esc_attr($bank['account_number']); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="branch_<?php echo $index; ?>"><?php _e('Branch', 'mlm-member-plugin'); ?></label>
                </th>
                <td>
                    <input type="text" name="bank_details[<?php echo $index; ?>][branch]" id="branch_<?php echo $index; ?>" value="<?php echo esc_attr($bank['branch']); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        
        <?php if ($index > 0): ?>
            <button type="button" class="button button-link-delete remove-bank-detail"><?php _e('Remove', 'mlm-member-plugin'); ?></button>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Save general settings
 */
function mmp_save_general_settings() {
    // Sanitize and validate input
    $settings = array(
        'member_area_page' => intval($_POST['member_area_page']),
        'registration_page' => intval($_POST['registration_page']),
        'success_page' => intval($_POST['success_page']),
        'admin_email' => sanitize_email($_POST['admin_email']),
        'admin_contact' => sanitize_text_field($_POST['admin_contact']),
        'default_sponsor' => sanitize_text_field($_POST['default_sponsor']),
        'replica_url_type' => sanitize_text_field($_POST['replica_url_type']),
        'network_tree_levels' => intval($_POST['network_tree_levels']),
        'enable_notifications' => isset($_POST['enable_notifications']),
        'auto_approve_members' => isset($_POST['auto_approve_members']),
        'bank_details' => array()
    );
    
    // Validate email
    if (!is_email($settings['admin_email'])) {
        add_settings_error('mmp_general_settings', 'invalid_email', __('Invalid email address.', 'mlm-member-plugin'));
        return;
    }
    
    // Validate network tree levels
    if ($settings['network_tree_levels'] < 1 || $settings['network_tree_levels'] > 10) {
        $settings['network_tree_levels'] = 3;
    }
    
    // Process bank details
    if (isset($_POST['bank_details']) && is_array($_POST['bank_details'])) {
        foreach ($_POST['bank_details'] as $bank) {
            if (!empty($bank['bank_name']) || !empty($bank['account_name']) || !empty($bank['account_number'])) {
                $settings['bank_details'][] = array(
                    'bank_name' => sanitize_text_field($bank['bank_name']),
                    'account_name' => sanitize_text_field($bank['account_name']),
                    'account_number' => sanitize_text_field($bank['account_number']),
                    'branch' => sanitize_text_field($bank['branch'])
                );
            }
        }
    }
    
    // Save settings
    update_option('mmp_general_settings', $settings);
    
    // Redirect with success message
    wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
    exit;
}
