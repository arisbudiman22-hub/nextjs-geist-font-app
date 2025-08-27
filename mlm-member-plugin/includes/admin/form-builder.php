<?php
/**
 * Form Builder Admin Page
 * 
 * @package MLM_Member_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display form builder page
 */
function mmp_form_builder_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'mlm-member-plugin'));
    }
    
    // Handle form actions
    if (isset($_POST['action']) && wp_verify_nonce($_POST['mmp_form_builder_nonce'], 'mmp_form_builder_action')) {
        mmp_handle_form_builder_action();
    }
    
    // Get current form if editing
    $current_form = null;
    if (isset($_GET['edit']) && !empty($_GET['edit'])) {
        $form_id = intval($_GET['edit']);
        $current_form = mmp_get_form($form_id);
    }
    
    // Get all forms
    $forms = mmp_get_all_forms();
    ?>
    
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Form Builder', 'mlm-member-plugin'); ?></h1>
        
        <?php if (!$current_form): ?>
            <a href="<?php echo add_query_arg('edit', 'new', admin_url('admin.php?page=mlm-form-builder')); ?>" class="page-title-action">
                <?php _e('Add New Form', 'mlm-member-plugin'); ?>
            </a>
        <?php else: ?>
            <a href="<?php echo admin_url('admin.php?page=mlm-form-builder'); ?>" class="page-title-action">
                <?php _e('Back to Forms', 'mlm-member-plugin'); ?>
            </a>
        <?php endif; ?>
        
        <?php if (isset($_GET['message'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html(mmp_get_form_message($_GET['message'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html(mmp_get_form_message($_GET['error'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($current_form || isset($_GET['edit'])): ?>
            <!-- Form Builder Interface -->
            <div class="mmp-form-builder">
                <form method="post" action="">
                    <?php wp_nonce_field('mmp_form_builder_action', 'mmp_form_builder_nonce'); ?>
                    <input type="hidden" name="action" value="<?php echo $current_form ? 'update_form' : 'create_form'; ?>" />
                    <?php if ($current_form): ?>
                        <input type="hidden" name="form_id" value="<?php echo esc_attr($current_form->id); ?>" />
                    <?php endif; ?>
                    
                    <div class="mmp-form-builder-header">
                        <div class="mmp-form-settings">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="form_name"><?php _e('Form Name', 'mlm-member-plugin'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="form_name" name="form_name" value="<?php echo $current_form ? esc_attr($current_form->form_name) : ''; ?>" class="regular-text" required />
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="form_type"><?php _e('Form Type', 'mlm-member-plugin'); ?></label>
                                    </th>
                                    <td>
                                        <select id="form_type" name="form_type">
                                            <option value="registration" <?php echo ($current_form && $current_form->form_type === 'registration') ? 'selected' : ''; ?>>
                                                <?php _e('Registration Form', 'mlm-member-plugin'); ?>
                                            </option>
                                            <option value="profile" <?php echo ($current_form && $current_form->form_type === 'profile') ? 'selected' : ''; ?>>
                                                <?php _e('Profile Form', 'mlm-member-plugin'); ?>
                                            </option>
                                            <option value="prospect" <?php echo ($current_form && $current_form->form_type === 'prospect') ? 'selected' : ''; ?>>
                                                <?php _e('Prospect Form', 'mlm-member-plugin'); ?>
                                            </option>
                                            <option value="subscribe" <?php echo ($current_form && $current_form->form_type === 'subscribe') ? 'selected' : ''; ?>>
                                                <?php _e('Subscribe Form', 'mlm-member-plugin'); ?>
                                            </option>
                                            <option value="custom" <?php echo ($current_form && $current_form->form_type === 'custom') ? 'selected' : ''; ?>>
                                                <?php _e('Custom Form', 'mlm-member-plugin'); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mmp-form-builder-content">
                        <div class="mmp-form-builder-sidebar">
                            <h3><?php _e('Form Fields', 'mlm-member-plugin'); ?></h3>
                            <div class="mmp-field-types">
                                <div class="mmp-field-type" data-type="text">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Text Field', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="email">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php _e('Email Field', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="tel">
                                    <span class="dashicons dashicons-phone"></span>
                                    <?php _e('Phone Field', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="password">
                                    <span class="dashicons dashicons-lock"></span>
                                    <?php _e('Password Field', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="textarea">
                                    <span class="dashicons dashicons-text"></span>
                                    <?php _e('Textarea', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="select">
                                    <span class="dashicons dashicons-list-view"></span>
                                    <?php _e('Select Dropdown', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="radio">
                                    <span class="dashicons dashicons-marker"></span>
                                    <?php _e('Radio Buttons', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="checkbox">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Checkbox', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php _e('Date Field', 'mlm-member-plugin'); ?>
                                </div>
                                
                                <div class="mmp-field-type" data-type="number">
                                    <span class="dashicons dashicons-calculator"></span>
                                    <?php _e('Number Field', 'mlm-member-plugin'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mmp-form-builder-main">
                            <h3><?php _e('Form Preview', 'mlm-member-plugin'); ?></h3>
                            <div id="mmp-form-preview" class="mmp-form-preview">
                                <div id="mmp-form-fields" class="mmp-form-fields">
                                    <?php if ($current_form && !empty($current_form->form_fields)): ?>
                                        <?php
                                        $fields = json_decode($current_form->form_fields, true);
                                        foreach ($fields as $index => $field) {
                                            mmp_render_form_field_builder($field, $index);
                                        }
                                        ?>
                                    <?php else: ?>
                                        <div class="mmp-empty-form">
                                            <p><?php _e('Drag fields from the sidebar to build your form', 'mlm-member-plugin'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <input type="hidden" id="form_fields_data" name="form_fields" value="<?php echo $current_form ? esc_attr($current_form->form_fields) : ''; ?>" />
                        </div>
                    </div>
                    
                    <div class="mmp-form-builder-settings">
                        <h3><?php _e('Form Settings', 'mlm-member-plugin'); ?></h3>
                        
                        <?php
                        $form_settings = $current_form && !empty($current_form->form_settings) ? json_decode($current_form->form_settings, true) : array();
                        ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="redirect_after_submit"><?php _e('Redirect After Submit', 'mlm-member-plugin'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    wp_dropdown_pages(array(
                                        'name' => 'form_settings[redirect_after_submit]',
                                        'id' => 'redirect_after_submit',
                                        'selected' => isset($form_settings['redirect_after_submit']) ? $form_settings['redirect_after_submit'] : '',
                                        'show_option_none' => __('Default Success Page', 'mlm-member-plugin'),
                                        'option_none_value' => ''
                                    ));
                                    ?>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="auto_activate"><?php _e('Auto Activate Members', 'mlm-member-plugin'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="auto_activate" name="form_settings[auto_activate]" value="1" <?php checked(isset($form_settings['auto_activate']) ? $form_settings['auto_activate'] : false, true); ?> />
                                        <?php _e('Automatically activate new registrations', 'mlm-member-plugin'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="send_welcome_email"><?php _e('Send Welcome Email', 'mlm-member-plugin'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="send_welcome_email" name="form_settings[send_welcome_email]" value="1" <?php checked(isset($form_settings['send_welcome_email']) ? $form_settings['send_welcome_email'] : true, true); ?> />
                                        <?php _e('Send welcome email to new registrations', 'mlm-member-plugin'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="require_sponsor"><?php _e('Require Sponsor', 'mlm-member-plugin'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="require_sponsor" name="form_settings[require_sponsor]" value="1" <?php checked(isset($form_settings['require_sponsor']) ? $form_settings['require_sponsor'] : false, true); ?> />
                                        <?php _e('Require a sponsor for registration', 'mlm-member-plugin'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="mmp-form-builder-actions">
                        <?php submit_button($current_form ? __('Update Form', 'mlm-member-plugin') : __('Create Form', 'mlm-member-plugin'), 'primary', 'submit', false); ?>
                        
                        <?php if ($current_form): ?>
                            <button type="button" id="preview-form" class="button button-secondary">
                                <?php _e('Preview Form', 'mlm-member-plugin'); ?>
                            </button>
                            
                            <button type="button" id="get-shortcode" class="button button-secondary">
                                <?php _e('Get Shortcode', 'mlm-member-plugin'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Forms List -->
            <div class="mmp-forms-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-name">
                                <?php _e('Form Name', 'mlm-member-plugin'); ?>
                            </th>
                            <th scope="col" class="manage-column column-type">
                                <?php _e('Type', 'mlm-member-plugin'); ?>
                            </th>
                            <th scope="col" class="manage-column column-fields">
                                <?php _e('Fields', 'mlm-member-plugin'); ?>
                            </th>
                            <th scope="col" class="manage-column column-shortcode">
                                <?php _e('Shortcode', 'mlm-member-plugin'); ?>
                            </th>
                            <th scope="col" class="manage-column column-status">
                                <?php _e('Status', 'mlm-member-plugin'); ?>
                            </th>
                            <th scope="col" class="manage-column column-actions">
                                <?php _e('Actions', 'mlm-member-plugin'); ?>
                            </th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php if (!empty($forms)): ?>
                            <?php foreach ($forms as $form): ?>
                                <?php
                                $fields = json_decode($form->form_fields, true);
                                $field_count = is_array($fields) ? count($fields) : 0;
                                ?>
                                <tr>
                                    <td class="column-name">
                                        <strong>
                                            <a href="<?php echo add_query_arg('edit', $form->id, admin_url('admin.php?page=mlm-form-builder')); ?>">
                                                <?php echo esc_html($form->form_name); ?>
                                            </a>
                                        </strong>
                                        
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo add_query_arg('edit', $form->id, admin_url('admin.php?page=mlm-form-builder')); ?>">
                                                    <?php _e('Edit', 'mlm-member-plugin'); ?>
                                                </a> |
                                            </span>
                                            <span class="duplicate">
                                                <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'duplicate_form', 'form_id' => $form->id), admin_url('admin.php?page=mlm-form-builder')), 'mmp_form_action'); ?>">
                                                    <?php _e('Duplicate', 'mlm-member-plugin'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'delete_form', 'form_id' => $form->id), admin_url('admin.php?page=mlm-form-builder')), 'mmp_form_action'); ?>" onclick="return confirm('<?php _e('Are you sure you want to delete this form?', 'mlm-member-plugin'); ?>')">
                                                    <?php _e('Delete', 'mlm-member-plugin'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    
                                    <td class="column-type">
                                        <span class="mmp-form-type mmp-form-type-<?php echo esc_attr($form->form_type); ?>">
                                            <?php echo esc_html(mmp_get_form_type_label($form->form_type)); ?>
                                        </span>
                                    </td>
                                    
                                    <td class="column-fields">
                                        <?php printf(__('%d fields', 'mlm-member-plugin'), $field_count); ?>
                                    </td>
                                    
                                    <td class="column-shortcode">
                                        <code>[mlm_form id="<?php echo esc_attr($form->id); ?>"]</code>
                                        <button type="button" class="button button-small copy-shortcode" data-shortcode='[mlm_form id="<?php echo esc_attr($form->id); ?>"]'>
                                            <?php _e('Copy', 'mlm-member-plugin'); ?>
                                        </button>
                                    </td>
                                    
                                    <td class="column-status">
                                        <span class="mmp-status mmp-status-<?php echo esc_attr($form->status); ?>">
                                            <?php echo esc_html(mmp_get_status_label($form->status)); ?>
                                        </span>
                                    </td>
                                    
                                    <td class="column-actions">
                                        <a href="<?php echo add_query_arg('edit', $form->id, admin_url('admin.php?page=mlm-form-builder')); ?>" class="button button-small">
                                            <?php _e('Edit', 'mlm-member-plugin'); ?>
                                        </a>
                                        
                                        <?php if ($form->status === 'active'): ?>
                                            <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'deactivate_form', 'form_id' => $form->id), admin_url('admin.php?page=mlm-form-builder')), 'mmp_form_action'); ?>" class="button button-small">
                                                <?php _e('Deactivate', 'mlm-member-plugin'); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'activate_form', 'form_id' => $form->id), admin_url('admin.php?page=mlm-form-builder')), 'mmp_form_action'); ?>" class="button button-small button-primary">
                                                <?php _e('Activate', 'mlm-member-plugin'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-items">
                                    <?php _e('No forms found. Create your first form to get started.', 'mlm-member-plugin'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Field Settings Modal -->
    <div id="field-settings-modal" class="mmp-modal" style="display: none;">
        <div class="mmp-modal-content">
            <div class="mmp-modal-header">
                <h2><?php _e('Field Settings', 'mlm-member-plugin'); ?></h2>
                <span class="mmp-modal-close">&times;</span>
            </div>
            <div class="mmp-modal-body">
                <div id="field-settings-content">
                    <!-- Field settings will be loaded here -->
                </div>
            </div>
            <div class="mmp-modal-footer">
                <button type="button" id="save-field-settings" class="button button-primary">
                    <?php _e('Save Settings', 'mlm-member-plugin'); ?>
                </button>
                <button type="button" class="button mmp-modal-close">
                    <?php _e('Cancel', 'mlm-member-plugin'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var fieldIndex = <?php echo $current_form && !empty($current_form->form_fields) ? count(json_decode($current_form->form_fields, true)) : 0; ?>;
        
        // Make form fields sortable
        $('#mmp-form-fields').sortable({
            placeholder: 'mmp-field-placeholder',
            handle: '.mmp-field-handle',
            update: function() {
                updateFormData();
            }
        });
        
        // Drag and drop field types
        $('.mmp-field-type').draggable({
            helper: 'clone',
            connectToSortable: '#mmp-form-fields',
            start: function() {
                $('.mmp-empty-form').hide();
            }
        });
        
        $('#mmp-form-fields').droppable({
            accept: '.mmp-field-type',
            drop: function(event, ui) {
                var fieldType = ui.draggable.data('type');
                addFormField(fieldType);
                $('.mmp-empty-form').hide();
            }
        });
        
        // Add form field
        function addFormField(type) {
            var fieldHtml = generateFieldHtml(type, fieldIndex);
            $('#mmp-form-fields').append(fieldHtml);
            fieldIndex++;
            updateFormData();
        }
        
        // Generate field HTML
        function generateFieldHtml(type, index) {
            var fieldName = type + '_field_' + index;
            var fieldLabel = getFieldTypeLabel(type);
            
            return `
                <div class="mmp-form-field" data-type="${type}" data-index="${index}">
                    <div class="mmp-field-header">
                        <span class="mmp-field-handle dashicons dashicons-move"></span>
                        <span class="mmp-field-label">${fieldLabel}</span>
                        <div class="mmp-field-actions">
                            <button type="button" class="mmp-field-settings button button-small">
                                <?php _e('Settings', 'mlm-member-plugin'); ?>
                            </button>
                            <button type="button" class="mmp-field-delete button button-small">
                                <?php _e('Delete', 'mlm-member-plugin'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="mmp-field-preview">
                        ${generateFieldPreview(type, fieldName, fieldLabel)}
                    </div>
                    <div class="mmp-field-data" style="display: none;">
                        ${JSON.stringify(getDefaultFieldData(type, fieldName, fieldLabel))}
                    </div>
                </div>
            `;
        }
        
        // Generate field preview
        function generateFieldPreview(type, name, label) {
            switch(type) {
                case 'text':
                case 'email':
                case 'tel':
                case 'password':
                case 'date':
                case 'number':
                    return `<label>${label}</label><input type="${type}" name="${name}" placeholder="Enter ${label.toLowerCase()}" disabled />`;
                case 'textarea':
                    return `<label>${label}</label><textarea name="${name}" placeholder="Enter ${label.toLowerCase()}" disabled></textarea>`;
                case 'select':
                    return `<label>${label}</label><select name="${name}" disabled><option>Option 1</option><option>Option 2</option></select>`;
                case 'radio':
                    return `<label>${label}</label><div><label><input type="radio" name="${name}" disabled /> Option 1</label><label><input type="radio" name="${name}" disabled /> Option 2</label></div>`;
                case 'checkbox':
                    return `<label><input type="checkbox" name="${name}" disabled /> ${label}</label>`;
                default:
                    return `<label>${label}</label><input type="text" name="${name}" disabled />`;
            }
        }
        
        // Get default field data
        function getDefaultFieldData(type, name, label) {
            return {
                type: type,
                name: name,
                label: label,
                required: false,
                placeholder: '',
                options: type === 'select' || type === 'radio' ? ['Option 1', 'Option 2'] : [],
                validation: {}
            };
        }
        
        // Get field type label
        function getFieldTypeLabel(type) {
            var labels = {
                'text': '<?php _e('Text Field', 'mlm-member-plugin'); ?>',
                'email': '<?php _e('Email Field', 'mlm-member-plugin'); ?>',
                'tel': '<?php _e('Phone Field', 'mlm-member-plugin'); ?>',
                'password': '<?php _e('Password Field', 'mlm-member-plugin'); ?>',
                'textarea': '<?php _e('Textarea', 'mlm-member-plugin'); ?>',
                'select': '<?php _e('Select Dropdown', 'mlm-member-plugin'); ?>',
                'radio': '<?php _e('Radio Buttons', 'mlm-member-plugin'); ?>',
                'checkbox': '<?php _e('Checkbox', 'mlm-member-plugin'); ?>',
                'date': '<?php _e('Date Field', 'mlm-member-plugin'); ?>',
                'number': '<?php _e('Number Field', 'mlm-member-plugin'); ?>'
            };
            return labels[type] || type;
        }
        
        // Update form data
        function updateFormData() {
            var formFields = [];
            $('#mmp-form-fields .mmp-form-field').each(function() {
                var fieldData = JSON.parse($(this).find('.mmp-field-data').text());
                formFields.push(fieldData);
            });
            $('#form_fields_data').val(JSON.stringify(formFields));
        }
        
        // Delete field
        $(document).on('click', '.mmp-field-delete', function() {
            if (confirm('<?php _e('Are you sure you want to delete this field?', 'mlm-member-plugin'); ?>')) {
                $(this).closest('.mmp-form-field').remove();
                updateFormData();
                
                if ($('#mmp-form-fields .mmp-form-field').length === 0) {
                    $('#mmp-form-fields').html('<div class="mmp-empty-form"><p><?php _e('Drag fields from the sidebar to build your form', 'mlm-member-plugin'); ?></p></div>');
                }
            }
        });
        
        // Copy shortcode
        $('.copy-shortcode').click(function() {
            var shortcode = $(this).data('shortcode');
            navigator.clipboard.writeText(shortcode).then(function() {
                alert('<?php _e('Shortcode copied to clipboard!', 'mlm-member-plugin'); ?>');
            });
        });
        
        // Modal functionality
        $('.mmp-modal-close').click(function() {
            $(this).closest('.mmp-modal').hide();
        });
        
        // Initialize existing fields if editing
        <?php if ($current_form && !empty($current_form->form_fields)): ?>
            updateFormData();
        <?php endif; ?>
    });
    </script>
    
    <?php
}

/**
 * Render form field in builder
 */
function mmp_render_form_field_builder($field, $index) {
    ?>
    <div class="mmp-form-field" data-type="<?php echo esc_attr($field['type']); ?>" data-index="<?php echo esc_attr($index); ?>">
        <div class="mmp-field-header">
            <span class="mmp-field-handle dashicons dashicons-move"></span>
            <span class="mmp-field-label"><?php echo esc_html($field['label']); ?></span>
            <div class="mmp-field-actions">
                <button type="button" class="mmp-field-settings button button-small">
                    <?php _e('Settings', 'mlm-member-plugin'); ?>
                </button>
                <button type="button" class="mmp-field-delete button button-small">
                    <?php _e('Delete', 'mlm-member-plugin'); ?>
                </button>
            </div>
        </div>
        <div class="mmp-field-preview">
            <?php mmp_render_field_preview($field); ?>
        </div>
        <div class="mmp-field-data" style="display: none;">
            <?php echo esc_html(json_encode($field)); ?>
        </div>
    </div>
    <?php
}

/**
 * Render field preview
 */
function mmp_render_field_preview($field) {
    $type = $field['type'];
    $name = $field['name'];
    $label = $field['label'];
    $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
    
    switch ($type) {
        case 'text':
        case 'email':
        case 'tel':
        case 'password':
        case 'date':
        case 'number':
            echo '<label>' . esc_html($label) . '</label>';
            echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '" disabled />';
            break;
            
        case 'textarea':
            echo '<label>' . esc_html($label) . '</label>';
            echo '<textarea name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '" disabled></textarea>';
            break;
            
        case 'select':
            echo '<label>' . esc_html($label) . '</label>';
            echo '<select name="' . esc_attr($name) . '" disabled>';
            if (isset($field['options']) && is_array($field['options'])) {
                foreach ($field['options'] as $option) {
                    echo '<option>' . esc_html($option) . '</option>';
                }
            } else {
                echo '<option>Option 1</option><option>Option 2</option>';
            }
            echo '</select>';
            break;
            
        case 'radio':
            echo '<label>' . esc_html($label) . '</label>';
            echo '<div>';
            if (isset($field['options']) && is_array($field['options'])) {
                foreach ($field['options'] as $option) {
                    echo '<label><input type="radio" name="' . esc_attr($name) . '" disabled /> ' . esc_html($option) . '</label>';
                }
            } else {
                echo '<label><input type="radio" name="' . esc_attr($name) . '" disabled /> Option 1</label>';
                echo '<label><input type="radio" name="' . esc_attr($name) . '" disabled /> Option 2</label>';
            }
            echo '</div>';
            break;
            
        case 'checkbox':
            echo '<label><input type="checkbox" name="' . esc_attr($name) . '" disabled /> ' . esc_html($label) . '</label>';
            break;
            
        default:
            echo '<label>' . esc_html($label) . '</label>';
            echo '<input type="text" name="' . esc_attr($name) . '" disabled />';
            break;
    }
}

/**
 * Get all forms
 */
function mmp_get_all_forms() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mlm_forms';
    
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
}

/**
 * Handle form builder actions
 */
function mmp_handle_form_builder_action() {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'create_form':
            mmp_create_form();
            break;
        case 'update_form':
            mmp_update_form();
            break;
    }
}

/**
 * Create new form
 */
function mmp_create_form() {
    global $wpdb;
    
    $form_name = sanitize_text_field($_POST['form_name']);
    $form_type = sanitize_text_field($_POST['form_type']);
    $form_fields = sanitize_textarea_field($_POST['form_fields']);
    
    // Process form settings
    $form_settings = array();
    if (isset($_POST['form_settings']) && is_array($_POST['form_settings'])) {
        foreach ($_POST['form_settings'] as $key => $value) {
            $form_settings[sanitize_key($key)] = sanitize_text_field($value);
        }
    }
    
    $table_name = $wpdb->prefix . 'mlm_forms';
    
    $result = $wpdb->insert($table_name, array(
        'form_name' => $form_name,
        'form_type' => $form_type,
        'form_fields' => $form_fields,
        'form_settings' => json_encode($form_settings),
        'status' => 'active',
        'created_by' => get_current_user_id()
    ));
    
    if ($result) {
        wp_redirect(add_query_arg('message', 'form_created', admin_url('admin.php?page=mlm-form-builder')));
    } else {
        wp_redirect(add_query_arg('error', 'form_creation_failed', admin_url('admin.php?page=mlm-form-builder')));
    }
    exit;
}

/**
 * Update existing form
 */
function mmp_update_form() {
    global $wpdb;
    
    $form_id = intval($_POST['form_id']);
    $form_name = sanitize_text_field($_POST['form_name']);
    $form_type = sanitize_text_field($_POST['form_type']);
    $form_fields = sanitize_textarea_field($_POST['form_fields']);
    
    // Process form settings
    $form_settings = array();
    if (isset($_POST['form_settings']) && is_array($_POST['form_settings'])) {
        foreach ($_POST['form_settings'] as $key => $value) {
            $form_settings[sanitize_key($key)] = sanitize_text_field($value);
        }
    }
    
    $table_name = $wpdb->prefix . 'mlm_forms';
    
    $result = $wpdb->update($table_name, array(
        'form_name' => $form_name,
        'form_type' => $form_type,
        'form_fields' => $form_fields,
        'form_settings' => json_encode($form_settings)
    ), array('id' => $form_id));
    
    if ($result !== false) {
        wp_redirect(add_query_arg(array('edit' => $form_id, 'message' => 'form_updated'), admin_url('admin.php?page=mlm-form-builder')));
    } else {
        wp_redirect(add_query_arg(array('edit' => $form_id, 'error' => 'form_update_failed'), admin_url('admin.php?page=mlm-form-builder')));
    }
    exit;
}

/**
 * Get form type label
 */
function mmp_get_form_type_label($type) {
    $labels = array(
        'registration' => __('Registration', 'mlm-member-plugin'),
        'profile' => __('Profile', 'mlm-member-plugin'),
        'prospect' => __('Prospect', 'mlm-member-plugin'),
        'subscribe' => __('Subscribe', 'mlm-member-plugin'),
        'custom' => __('Custom', 'mlm-member-plugin')
    );
    
    return isset($labels[$type]) ? $labels[$type] : $type;
}

/**
 * Get form message
 */
function mmp_get_form_message($message_key) {
    $messages = array(
        'form_created' => __('Form created successfully.', 'mlm-member-plugin'),
        'form_updated' => __('Form updated successfully.', 'mlm-member-plugin'),
        'form_deleted' => __('Form deleted successfully.', 'mlm-member-plugin'),
        'form_creation_failed' => __('Failed to create form.', 'mlm-member-plugin'),
        'form_update_failed' => __('Failed to update form.', 'mlm-member-plugin'),
        'form_deletion_failed' => __('Failed to delete form.', 'mlm-member-plugin')
    );
    
    return isset($messages[$message_key]) ? $messages[$message_key] : $message_key;
}
