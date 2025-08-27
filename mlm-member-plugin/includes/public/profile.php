<?php
/**
 * Member Profile Shortcode
 * 
 * @package MLM_Member_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Profile shortcode handler
 */
function mmp_profile_shortcode($atts) {
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
    
    // Handle form submission
    $message = '';
    $message_type = '';
    
    if (isset($_POST['mmp_update_profile']) && wp_verify_nonce($_POST['mmp_profile_nonce'], 'mmp_update_profile')) {
        $result = mmp_update_member_profile();
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
    
    // Get profile form
    $profile_form = mmp_get_form_by_type('profile');
    if (!$profile_form) {
        // Create default profile form if none exists
        $profile_form = mmp_create_default_profile_form();
    }
    
    $user = wp_get_current_user();
    $form_fields = json_decode($profile_form->form_fields, true);
    
    ob_start();
    ?>
    
    <div class="mmp-member-dashboard">
        <div class="mmp-content-section">
            <h2><?php _e('Edit Profile', 'mlm-member-plugin'); ?></h2>
            
            <?php if ($message): ?>
                <div class="mmp-message mmp-message-<?php echo esc_attr($message_type); ?>">
                    <?php echo esc_html($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="mmp-profile-form mmp-ajax-form" enctype="multipart/form-data">
                <?php wp_nonce_field('mmp_update_profile', 'mmp_profile_nonce'); ?>
                <input type="hidden" name="action" value="mmp_update_profile" />
                
                <div class="mmp-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="mmp-form-column">
                        <h3><?php _e('Personal Information', 'mlm-member-plugin'); ?></h3>
                        
                        <?php
                        // Get current user data and custom fields
                        $custom_fields = !empty($member->custom_fields) ? json_decode($member->custom_fields, true) : array();
                        
                        // Render form fields
                        foreach ($form_fields as $field) {
                            mmp_render_profile_field($field, $user, $custom_fields);
                        }
                        ?>
                    </div>
                    
                    <div class="mmp-form-column">
                        <h3><?php _e('Account Information', 'mlm-member-plugin'); ?></h3>
                        
                        <div class="mmp-form-group">
                            <label><?php _e('Member Code', 'mlm-member-plugin'); ?></label>
                            <input type="text" value="<?php echo esc_attr($member->member_code); ?>" disabled class="mmp-readonly" />
                            <div class="form-help"><?php _e('Your unique member identification code', 'mlm-member-plugin'); ?></div>
                        </div>
                        
                        <div class="mmp-form-group">
                            <label><?php _e('Member Status', 'mlm-member-plugin'); ?></label>
                            <input type="text" value="<?php echo esc_attr(mmp_get_status_label($member->status)); ?>" disabled class="mmp-readonly" />
                        </div>
                        
                        <div class="mmp-form-group">
                            <label><?php _e('Registration Date', 'mlm-member-plugin'); ?></label>
                            <input type="text" value="<?php echo esc_attr(date_i18n(get_option('date_format'), strtotime($member->registration_date))); ?>" disabled class="mmp-readonly" />
                        </div>
                        
                        <?php if ($member->activation_date): ?>
                            <div class="mmp-form-group">
                                <label><?php _e('Activation Date', 'mlm-member-plugin'); ?></label>
                                <input type="text" value="<?php echo esc_attr(date_i18n(get_option('date_format'), strtotime($member->activation_date))); ?>" disabled class="mmp-readonly" />
                            </div>
                        <?php endif; ?>
                        
                        <div class="mmp-form-group">
                            <label><?php _e('Total Referrals', 'mlm-member-plugin'); ?></label>
                            <input type="text" value="<?php echo esc_attr($member->total_referrals); ?>" disabled class="mmp-readonly" />
                        </div>
                        
                        <div class="mmp-form-group">
                            <label><?php _e('Replica Link', 'mlm-member-plugin'); ?></label>
                            <div class="mmp-replica-link-container">
                                <input type="text" value="<?php echo esc_attr($member->replica_url); ?>" disabled class="mmp-readonly" />
                                <button type="button" class="mmp-btn mmp-btn-secondary copy-replica-link" data-url="<?php echo esc_url($member->replica_url); ?>">
                                    <?php _e('Copy', 'mlm-member-plugin'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <?php if (mmp_member_has_access('active')): ?>
                            <div class="mmp-premium-info">
                                <h4><?php _e('Premium Member Benefits', 'mlm-member-plugin'); ?></h4>
                                <ul style="list-style: none; padding: 0; margin: 10px 0;">
                                    <li style="padding: 3px 0;">✅ <?php _e('Advanced statistics', 'mlm-member-plugin'); ?></li>
                                    <li style="padding: 3px 0;">✅ <?php _e('Premium downloads', 'mlm-member-plugin'); ?></li>
                                    <li style="padding: 3px 0;">✅ <?php _e('Priority support', 'mlm-member-plugin'); ?></li>
                                    <li style="padding: 3px 0;">✅ <?php _e('Network management tools', 'mlm-member-plugin'); ?></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mmp-form-actions" style="margin-top: 30px; text-align: center;">
                    <input type="submit" name="mmp_update_profile" value="<?php _e('Update Profile', 'mlm-member-plugin'); ?>" class="mmp-btn mmp-btn-primary" />
                    <a href="<?php echo esc_url(remove_query_arg('mmp_page')); ?>" class="mmp-btn mmp-btn-secondary">
                        <?php _e('Back to Dashboard', 'mlm-member-plugin'); ?>
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Password Change Section -->
        <div class="mmp-content-section">
            <h2><?php _e('Change Password', 'mlm-member-plugin'); ?></h2>
            
            <form method="post" class="mmp-password-form" id="mmp-password-form">
                <?php wp_nonce_field('mmp_change_password', 'mmp_password_nonce'); ?>
                <input type="hidden" name="action" value="mmp_change_password" />
                
                <div class="mmp-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="mmp-form-group">
                        <label for="current_password"><?php _e('Current Password', 'mlm-member-plugin'); ?></label>
                        <input type="password" id="current_password" name="current_password" required />
                    </div>
                    
                    <div class="mmp-form-group">
                        <label for="new_password"><?php _e('New Password', 'mlm-member-plugin'); ?></label>
                        <input type="password" id="new_password" name="new_password" required />
                        <div class="password-strength"></div>
                        <div class="form-help"><?php _e('Password must be at least 6 characters long', 'mlm-member-plugin'); ?></div>
                    </div>
                    
                    <div class="mmp-form-group">
                        <label for="confirm_password"><?php _e('Confirm New Password', 'mlm-member-plugin'); ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" required />
                    </div>
                </div>
                
                <div class="mmp-form-actions" style="margin-top: 20px;">
                    <input type="submit" name="mmp_change_password" value="<?php _e('Change Password', 'mlm-member-plugin'); ?>" class="mmp-btn mmp-btn-primary" />
                </div>
            </form>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Password confirmation validation
        $('#mmp-password-form').on('submit', function(e) {
            var newPassword = $('#new_password').val();
            var confirmPassword = $('#confirm_password').val();
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('<?php _e('Passwords do not match.', 'mlm-member-plugin'); ?>');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('<?php _e('Password must be at least 6 characters long.', 'mlm-member-plugin'); ?>');
                return false;
            }
        });
        
        // Copy replica link
        $('.copy-replica-link').on('click', function() {
            var url = $(this).data('url');
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    alert('<?php _e('Replica link copied to clipboard!', 'mlm-member-plugin'); ?>');
                });
            } else {
                // Fallback
                var textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('<?php _e('Replica link copied to clipboard!', 'mlm-member-plugin'); ?>');
            }
        });
    });
    </script>
    
    <?php
    return ob_get_clean();
}

/**
 * Render profile field
 */
function mmp_render_profile_field($field, $user, $custom_fields) {
    $field_name = $field['name'];
    $field_type = $field['type'];
    $field_label = $field['label'];
    $field_required = isset($field['required']) ? $field['required'] : false;
    $field_placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
    
    // Get field value
    $field_value = '';
    switch ($field_name) {
        case 'first_name':
            $field_value = $user->first_name;
            break;
        case 'last_name':
            $field_value = $user->last_name;
            break;
        case 'email':
            $field_value = $user->user_email;
            break;
        case 'display_name':
            $field_value = $user->display_name;
            break;
        default:
            $field_value = isset($custom_fields[$field_name]) ? $custom_fields[$field_name] : get_user_meta($user->ID, $field_name, true);
            break;
    }
    
    ?>
    <div class="mmp-form-group">
        <label for="<?php echo esc_attr($field_name); ?>">
            <?php echo esc_html($field_label); ?>
            <?php if ($field_required): ?>
                <span class="required">*</span>
            <?php endif; ?>
        </label>
        
        <?php
        switch ($field_type) {
            case 'textarea':
                ?>
                <textarea 
                    id="<?php echo esc_attr($field_name); ?>" 
                    name="<?php echo esc_attr($field_name); ?>" 
                    placeholder="<?php echo esc_attr($field_placeholder); ?>"
                    <?php echo $field_required ? 'required' : ''; ?>
                ><?php echo esc_textarea($field_value); ?></textarea>
                <?php
                break;
                
            case 'select':
                ?>
                <select 
                    id="<?php echo esc_attr($field_name); ?>" 
                    name="<?php echo esc_attr($field_name); ?>"
                    <?php echo $field_required ? 'required' : ''; ?>
                >
                    <option value=""><?php _e('Select an option', 'mlm-member-plugin'); ?></option>
                    <?php
                    if (isset($field['options']) && is_array($field['options'])) {
                        foreach ($field['options'] as $option) {
                            echo '<option value="' . esc_attr($option) . '" ' . selected($field_value, $option, false) . '>' . esc_html($option) . '</option>';
                        }
                    }
                    ?>
                </select>
                <?php
                break;
                
            case 'radio':
                if (isset($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $option) {
                        ?>
                        <label class="radio-option">
                            <input 
                                type="radio" 
                                name="<?php echo esc_attr($field_name); ?>" 
                                value="<?php echo esc_attr($option); ?>"
                                <?php checked($field_value, $option); ?>
                                <?php echo $field_required ? 'required' : ''; ?>
                            />
                            <?php echo esc_html($option); ?>
                        </label>
                        <?php
                    }
                }
                break;
                
            case 'checkbox':
                ?>
                <label class="checkbox-option">
                    <input 
                        type="checkbox" 
                        id="<?php echo esc_attr($field_name); ?>" 
                        name="<?php echo esc_attr($field_name); ?>" 
                        value="1"
                        <?php checked($field_value, '1'); ?>
                    />
                    <?php echo esc_html($field_label); ?>
                </label>
                <?php
                break;
                
            default:
                ?>
                <input 
                    type="<?php echo esc_attr($field_type); ?>" 
                    id="<?php echo esc_attr($field_name); ?>" 
                    name="<?php echo esc_attr($field_name); ?>" 
                    value="<?php echo esc_attr($field_value); ?>"
                    placeholder="<?php echo esc_attr($field_placeholder); ?>"
                    <?php echo $field_required ? 'required' : ''; ?>
                />
                <?php
                break;
        }
        ?>
        
        <?php if (isset($field['help']) && !empty($field['help'])): ?>
            <div class="form-help"><?php echo esc_html($field['help']); ?></div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Update member profile
 */
function mmp_update_member_profile() {
    $user_id = get_current_user_id();
    $member = mmp_get_member_by_user_id($user_id);
    
    if (!$member) {
        return array('success' => false, 'message' => __('Member not found.', 'mlm-member-plugin'));
    }
    
    // Get profile form
    $profile_form = mmp_get_form_by_type('profile');
    if (!$profile_form) {
        return array('success' => false, 'message' => __('Profile form not found.', 'mlm-member-plugin'));
    }
    
    $form_fields = json_decode($profile_form->form_fields, true);
    $custom_fields = array();
    $user_data = array('ID' => $user_id);
    
    // Process form fields
    foreach ($form_fields as $field) {
        $field_name = $field['name'];
        $field_value = isset($_POST[$field_name]) ? sanitize_text_field($_POST[$field_name]) : '';
        
        // Handle WordPress user fields
        switch ($field_name) {
            case 'first_name':
                $user_data['first_name'] = $field_value;
                break;
            case 'last_name':
                $user_data['last_name'] = $field_value;
                break;
            case 'email':
                if (is_email($field_value)) {
                    $user_data['user_email'] = $field_value;
                }
                break;
            case 'display_name':
                $user_data['display_name'] = $field_value;
                break;
            default:
                // Custom fields
                $custom_fields[$field_name] = $field_value;
                update_user_meta($user_id, $field_name, $field_value);
                break;
        }
    }
    
    // Update WordPress user
    $user_result = wp_update_user($user_data);
    if (is_wp_error($user_result)) {
        return array('success' => false, 'message' => $user_result->get_error_message());
    }
    
    // Update member custom fields
    if (!empty($custom_fields)) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mlm_members';
        
        $wpdb->update(
            $table_name,
            array('custom_fields' => json_encode($custom_fields)),
            array('id' => $member->id),
            array('%s'),
            array('%d')
        );
    }
    
    return array('success' => true, 'message' => __('Profile updated successfully.', 'mlm-member-plugin'));
}

/**
 * Create default profile form
 */
function mmp_create_default_profile_form() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_forms';
    
    $default_fields = array(
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
            'required' => false,
            'placeholder' => 'Enter your phone number'
        ),
        array(
            'type' => 'textarea',
            'name' => 'bio',
            'label' => 'Bio',
            'required' => false,
            'placeholder' => 'Tell us about yourself'
        )
    );
    
    $form_settings = array(
        'redirect_after_submit' => '',
        'show_success_message' => true
    );
    
    $wpdb->insert($table_name, array(
        'form_name' => 'Default Profile Form',
        'form_type' => 'profile',
        'form_fields' => json_encode($default_fields),
        'form_settings' => json_encode($form_settings),
        'status' => 'active',
        'created_by' => 1
    ));
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $wpdb->insert_id
    ));
}

/**
 * AJAX handler for profile update
 */
add_action('wp_ajax_mmp_update_profile', 'mmp_ajax_update_profile');
function mmp_ajax_update_profile() {
    check_ajax_referer('mmp_profile_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Unauthorized access', 'mlm-member-plugin')));
    }
    
    $result = mmp_update_member_profile();
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

/**
 * AJAX handler for password change
 */
add_action('wp_ajax_mmp_change_password', 'mmp_ajax_change_password');
function mmp_ajax_change_password() {
    check_ajax_referer('mmp_password_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Unauthorized access', 'mlm-member-plugin')));
    }
    
    $user_id = get_current_user_id();
    $current_password = sanitize_text_field($_POST['current_password']);
    $new_password = sanitize_text_field($_POST['new_password']);
    $confirm_password = sanitize_text_field($_POST['confirm_password']);
    
    // Validate current password
    $user = get_user_by('ID', $user_id);
    if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        wp_send_json_error(array('message' => __('Current password is incorrect.', 'mlm-member-plugin')));
    }
    
    // Validate new password
    if (strlen($new_password) < 6) {
        wp_send_json_error(array('message' => __('New password must be at least 6 characters long.', 'mlm-member-plugin')));
    }
    
    if ($new_password !== $confirm_password) {
        wp_send_json_error(array('message' => __('New passwords do not match.', 'mlm-member-plugin')));
    }
    
    // Update password
    wp_set_password($new_password, $user_id);
    
    wp_send_json_success(array('message' => __('Password changed successfully.', 'mlm-member-plugin')));
}
