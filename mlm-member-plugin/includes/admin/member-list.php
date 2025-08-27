<?php
/**
 * Member List Admin Page
 * 
 * @package MLM_Member_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display member list page
 */
function mmp_member_list_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'mlm-member-plugin'));
    }
    
    // Handle bulk actions
    if (isset($_POST['action']) && $_POST['action'] !== '-1' && isset($_POST['members']) && wp_verify_nonce($_POST['mmp_member_list_nonce'], 'mmp_member_list_action')) {
        mmp_handle_bulk_actions();
    }
    
    // Handle individual actions
    if (isset($_GET['action']) && isset($_GET['member_id']) && wp_verify_nonce($_GET['_wpnonce'], 'mmp_member_action')) {
        mmp_handle_individual_action();
    }
    
    // Get members data
    $members_data = mmp_get_members_list();
    ?>
    
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Member List', 'mlm-member-plugin'); ?></h1>
        <a href="#" class="page-title-action" id="export-members"><?php _e('Export Members', 'mlm-member-plugin'); ?></a>
        
        <?php if (isset($_GET['message'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html(mmp_get_admin_message($_GET['message'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html(mmp_get_admin_message($_GET['error'])); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Search and Filter Form -->
        <div class="mmp-member-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="mlm-member-list" />
                
                <div class="alignleft actions">
                    <input type="text" name="search" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>" placeholder="<?php _e('Search members...', 'mlm-member-plugin'); ?>" />
                    
                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'mlm-member-plugin'); ?></option>
                        <option value="active" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'active'); ?>><?php _e('Active', 'mlm-member-plugin'); ?></option>
                        <option value="pending" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pending'); ?>><?php _e('Pending', 'mlm-member-plugin'); ?></option>
                        <option value="inactive" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'inactive'); ?>><?php _e('Inactive', 'mlm-member-plugin'); ?></option>
                        <option value="free" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'free'); ?>><?php _e('Free', 'mlm-member-plugin'); ?></option>
                    </select>
                    
                    <select name="date_range">
                        <option value=""><?php _e('All Dates', 'mlm-member-plugin'); ?></option>
                        <option value="today" <?php selected(isset($_GET['date_range']) ? $_GET['date_range'] : '', 'today'); ?>><?php _e('Today', 'mlm-member-plugin'); ?></option>
                        <option value="week" <?php selected(isset($_GET['date_range']) ? $_GET['date_range'] : '', 'week'); ?>><?php _e('This Week', 'mlm-member-plugin'); ?></option>
                        <option value="month" <?php selected(isset($_GET['date_range']) ? $_GET['date_range'] : '', 'month'); ?>><?php _e('This Month', 'mlm-member-plugin'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'mlm-member-plugin'); ?>" />
                    
                    <?php if (!empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['date_range'])): ?>
                        <a href="<?php echo admin_url('admin.php?page=mlm-member-list'); ?>" class="button"><?php _e('Clear', 'mlm-member-plugin'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Members Table -->
        <form method="post" action="">
            <?php wp_nonce_field('mmp_member_list_action', 'mmp_member_list_nonce'); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action">
                        <option value="-1"><?php _e('Bulk Actions', 'mlm-member-plugin'); ?></option>
                        <option value="activate"><?php _e('Activate', 'mlm-member-plugin'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate', 'mlm-member-plugin'); ?></option>
                        <option value="set_pending"><?php _e('Set Pending', 'mlm-member-plugin'); ?></option>
                        <option value="delete"><?php _e('Delete', 'mlm-member-plugin'); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php _e('Apply', 'mlm-member-plugin'); ?>" />
                </div>
                
                <div class="alignright">
                    <span class="displaying-num">
                        <?php printf(__('%d members', 'mlm-member-plugin'), $members_data['total']); ?>
                    </span>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1" />
                        </td>
                        <th scope="col" class="manage-column column-member">
                            <a href="<?php echo mmp_get_sort_url('member'); ?>">
                                <?php _e('Member', 'mlm-member-plugin'); ?>
                                <?php mmp_display_sort_indicator('member'); ?>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-code">
                            <a href="<?php echo mmp_get_sort_url('code'); ?>">
                                <?php _e('Member Code', 'mlm-member-plugin'); ?>
                                <?php mmp_display_sort_indicator('code'); ?>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-sponsor">
                            <?php _e('Sponsor', 'mlm-member-plugin'); ?>
                        </th>
                        <th scope="col" class="manage-column column-status">
                            <a href="<?php echo mmp_get_sort_url('status'); ?>">
                                <?php _e('Status', 'mlm-member-plugin'); ?>
                                <?php mmp_display_sort_indicator('status'); ?>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-referrals">
                            <a href="<?php echo mmp_get_sort_url('referrals'); ?>">
                                <?php _e('Referrals', 'mlm-member-plugin'); ?>
                                <?php mmp_display_sort_indicator('referrals'); ?>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-date">
                            <a href="<?php echo mmp_get_sort_url('date'); ?>">
                                <?php _e('Registration Date', 'mlm-member-plugin'); ?>
                                <?php mmp_display_sort_indicator('date'); ?>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-actions">
                            <?php _e('Actions', 'mlm-member-plugin'); ?>
                        </th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php if (!empty($members_data['members'])): ?>
                        <?php foreach ($members_data['members'] as $member): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="members[]" value="<?php echo esc_attr($member->id); ?>" />
                                </th>
                                
                                <td class="column-member">
                                    <strong>
                                        <a href="#" class="member-details" data-member-id="<?php echo esc_attr($member->id); ?>">
                                            <?php echo esc_html($member->display_name); ?>
                                        </a>
                                    </strong>
                                    <br>
                                    <small><?php echo esc_html($member->user_email); ?></small>
                                    
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="#" class="member-details" data-member-id="<?php echo esc_attr($member->id); ?>">
                                                <?php _e('View Details', 'mlm-member-plugin'); ?>
                                            </a> |
                                        </span>
                                        <span class="edit">
                                            <a href="<?php echo get_edit_user_link($member->user_id); ?>">
                                                <?php _e('Edit User', 'mlm-member-plugin'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                
                                <td class="column-code">
                                    <code><?php echo esc_html($member->member_code); ?></code>
                                    <br>
                                    <small>
                                        <a href="<?php echo esc_url($member->replica_url); ?>" target="_blank">
                                            <?php _e('View Replica', 'mlm-member-plugin'); ?>
                                        </a>
                                    </small>
                                </td>
                                
                                <td class="column-sponsor">
                                    <?php if ($member->sponsor_name): ?>
                                        <?php echo esc_html($member->sponsor_name); ?>
                                        <br>
                                        <small><code><?php echo esc_html($member->sponsor_code); ?></code></small>
                                    <?php else: ?>
                                        <span class="description"><?php _e('No Sponsor', 'mlm-member-plugin'); ?></span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="column-status">
                                    <span class="mmp-status mmp-status-<?php echo esc_attr($member->status); ?>">
                                        <?php echo esc_html(mmp_get_status_label($member->status)); ?>
                                    </span>
                                    
                                    <?php if ($member->status !== 'active'): ?>
                                        <br>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mlm-member-list&action=activate&member_id=' . $member->id), 'mmp_member_action'); ?>" class="button button-small">
                                            <?php _e('Activate', 'mlm-member-plugin'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="column-referrals">
                                    <strong><?php echo intval($member->total_referrals); ?></strong>
                                    <?php if ($member->total_referrals > 0): ?>
                                        <br>
                                        <a href="#" class="view-referrals" data-member-id="<?php echo esc_attr($member->id); ?>">
                                            <?php _e('View Network', 'mlm-member-plugin'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="column-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($member->registration_date))); ?>
                                    <br>
                                    <small><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($member->registration_date))); ?></small>
                                </td>
                                
                                <td class="column-actions">
                                    <div class="mmp-action-buttons">
                                        <?php if ($member->status === 'active'): ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mlm-member-list&action=deactivate&member_id=' . $member->id), 'mmp_member_action'); ?>" class="button button-small">
                                                <?php _e('Deactivate', 'mlm-member-plugin'); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mlm-member-list&action=activate&member_id=' . $member->id), 'mmp_member_action'); ?>" class="button button-small button-primary">
                                                <?php _e('Activate', 'mlm-member-plugin'); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mlm-member-list&action=delete&member_id=' . $member->id), 'mmp_member_action'); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php _e('Are you sure you want to delete this member?', 'mlm-member-plugin'); ?>')">
                                            <?php _e('Delete', 'mlm-member-plugin'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-items">
                                <?php _e('No members found.', 'mlm-member-plugin'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="action2">
                        <option value="-1"><?php _e('Bulk Actions', 'mlm-member-plugin'); ?></option>
                        <option value="activate"><?php _e('Activate', 'mlm-member-plugin'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate', 'mlm-member-plugin'); ?></option>
                        <option value="set_pending"><?php _e('Set Pending', 'mlm-member-plugin'); ?></option>
                        <option value="delete"><?php _e('Delete', 'mlm-member-plugin'); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php _e('Apply', 'mlm-member-plugin'); ?>" />
                </div>
                
                <?php if ($members_data['total_pages'] > 1): ?>
                    <div class="tablenav-pages">
                        <?php echo mmp_get_pagination_links($members_data['current_page'], $members_data['total_pages']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Member Details Modal -->
    <div id="member-details-modal" class="mmp-modal" style="display: none;">
        <div class="mmp-modal-content">
            <div class="mmp-modal-header">
                <h2><?php _e('Member Details', 'mlm-member-plugin'); ?></h2>
                <span class="mmp-modal-close">&times;</span>
            </div>
            <div class="mmp-modal-body">
                <div id="member-details-content">
                    <?php _e('Loading...', 'mlm-member-plugin'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle member details modal
        $('.member-details').click(function(e) {
            e.preventDefault();
            var memberId = $(this).data('member-id');
            
            $('#member-details-content').html('<?php _e('Loading...', 'mlm-member-plugin'); ?>');
            $('#member-details-modal').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mmp_get_member_details',
                    member_id: memberId,
                    nonce: '<?php echo wp_create_nonce('mmp_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#member-details-content').html(response.data);
                    } else {
                        $('#member-details-content').html('<p><?php _e('Error loading member details.', 'mlm-member-plugin'); ?></p>');
                    }
                },
                error: function() {
                    $('#member-details-content').html('<p><?php _e('Error loading member details.', 'mlm-member-plugin'); ?></p>');
                }
            });
        });
        
        // Close modal
        $('.mmp-modal-close, .mmp-modal').click(function(e) {
            if (e.target === this) {
                $('#member-details-modal').hide();
            }
        });
        
        // Export members
        $('#export-members').click(function(e) {
            e.preventDefault();
            
            var params = new URLSearchParams(window.location.search);
            params.set('action', 'export_members');
            params.set('_wpnonce', '<?php echo wp_create_nonce('mmp_export_members'); ?>');
            
            window.location.href = '?' + params.toString();
        });
        
        // Select all checkboxes
        $('#cb-select-all-1').change(function() {
            $('input[name="members[]"]').prop('checked', this.checked);
        });
    });
    </script>
    
    <?php
}

/**
 * Get members list with filtering and pagination
 */
function mmp_get_members_list() {
    global $wpdb;
    
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Base query
    $sql = "SELECT m.*, u.display_name, u.user_email, u.user_registered,
                   s.member_code as sponsor_code, su.display_name as sponsor_name
            FROM {$wpdb->prefix}mlm_members m
            LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}mlm_members s ON m.sponsor_id = s.id
            LEFT JOIN {$wpdb->users} su ON s.user_id = su.ID
            WHERE 1=1";
    
    $count_sql = "SELECT COUNT(*)
                  FROM {$wpdb->prefix}mlm_members m
                  LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                  WHERE 1=1";
    
    $params = array();
    
    // Search filter
    if (!empty($_GET['search'])) {
        $search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['search'])) . '%';
        $sql .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s OR m.member_code LIKE %s)";
        $count_sql .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s OR m.member_code LIKE %s)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    // Status filter
    if (!empty($_GET['status'])) {
        $status = sanitize_text_field($_GET['status']);
        $sql .= " AND m.status = %s";
        $count_sql .= " AND m.status = %s";
        $params[] = $status;
    }
    
    // Date range filter
    if (!empty($_GET['date_range'])) {
        $date_range = sanitize_text_field($_GET['date_range']);
        switch ($date_range) {
            case 'today':
                $sql .= " AND DATE(m.registration_date) = CURDATE()";
                $count_sql .= " AND DATE(m.registration_date) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND m.registration_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                $count_sql .= " AND m.registration_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $sql .= " AND m.registration_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                $count_sql .= " AND m.registration_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
    }
    
    // Sorting
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
    $order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
    
    switch ($orderby) {
        case 'member':
            $sql .= " ORDER BY u.display_name $order";
            break;
        case 'code':
            $sql .= " ORDER BY m.member_code $order";
            break;
        case 'status':
            $sql .= " ORDER BY m.status $order";
            break;
        case 'referrals':
            $sql .= " ORDER BY m.total_referrals $order";
            break;
        default:
            $sql .= " ORDER BY m.registration_date $order";
            break;
    }
    
    // Add pagination
    $sql .= " LIMIT %d OFFSET %d";
    $params[] = $per_page;
    $params[] = $offset;
    
    // Get members
    $members = $wpdb->get_results($wpdb->prepare($sql, $params));
    
    // Get total count
    $total = $wpdb->get_var($wpdb->prepare($count_sql, array_slice($params, 0, -2)));
    
    return array(
        'members' => $members,
        'total' => intval($total),
        'current_page' => $current_page,
        'total_pages' => ceil($total / $per_page)
    );
}

/**
 * Handle bulk actions
 */
function mmp_handle_bulk_actions() {
    $action = sanitize_text_field($_POST['action']);
    if ($action === '-1') {
        $action = sanitize_text_field($_POST['action2']);
    }
    
    if ($action === '-1' || empty($_POST['members'])) {
        return;
    }
    
    $member_ids = array_map('intval', $_POST['members']);
    $count = 0;
    
    foreach ($member_ids as $member_id) {
        switch ($action) {
            case 'activate':
                if (mmp_update_member_status($member_id, 'active')) {
                    $count++;
                }
                break;
            case 'deactivate':
                if (mmp_update_member_status($member_id, 'inactive')) {
                    $count++;
                }
                break;
            case 'set_pending':
                if (mmp_update_member_status($member_id, 'pending')) {
                    $count++;
                }
                break;
            case 'delete':
                if (mmp_delete_member($member_id)) {
                    $count++;
                }
                break;
        }
    }
    
    $message = '';
    switch ($action) {
        case 'activate':
            $message = sprintf(__('%d members activated.', 'mlm-member-plugin'), $count);
            break;
        case 'deactivate':
            $message = sprintf(__('%d members deactivated.', 'mlm-member-plugin'), $count);
            break;
        case 'set_pending':
            $message = sprintf(__('%d members set to pending.', 'mlm-member-plugin'), $count);
            break;
        case 'delete':
            $message = sprintf(__('%d members deleted.', 'mlm-member-plugin'), $count);
            break;
    }
    
    wp_redirect(add_query_arg('message', urlencode($message), remove_query_arg(array('action', 'members', 'mmp_member_list_nonce'))));
    exit;
}

/**
 * Handle individual actions
 */
function mmp_handle_individual_action() {
    $action = sanitize_text_field($_GET['action']);
    $member_id = intval($_GET['member_id']);
    
    $success = false;
    $message = '';
    
    switch ($action) {
        case 'activate':
            $success = mmp_update_member_status($member_id, 'active');
            $message = $success ? 'member_activated' : 'activation_failed';
            break;
        case 'deactivate':
            $success = mmp_update_member_status($member_id, 'inactive');
            $message = $success ? 'member_deactivated' : 'deactivation_failed';
            break;
        case 'delete':
            $success = mmp_delete_member($member_id);
            $message = $success ? 'member_deleted' : 'deletion_failed';
            break;
    }
    
    $redirect_args = array('page' => 'mlm-member-list');
    if ($success) {
        $redirect_args['message'] = $message;
    } else {
        $redirect_args['error'] = $message;
    }
    
    wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}

/**
 * Delete member
 */
function mmp_delete_member($member_id) {
    global $wpdb;
    
    // Get member data
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mlm_members WHERE id = %d",
        $member_id
    ));
    
    if (!$member) {
        return false;
    }
    
    // Delete member record
    $result = $wpdb->delete(
        $wpdb->prefix . 'mlm_members',
        array('id' => $member_id),
        array('%d')
    );
    
    if ($result) {
        // Update sponsor's referral count
        if ($member->sponsor_id) {
            mmp_update_referral_count($member->sponsor_id);
        }
        
        // Optionally delete WordPress user (uncomment if needed)
        // wp_delete_user($member->user_id);
        
        return true;
    }
    
    return false;
}

/**
 * Get status label
 */
function mmp_get_status_label($status) {
    $labels = array(
        'active' => __('Active', 'mlm-member-plugin'),
        'inactive' => __('Inactive', 'mlm-member-plugin'),
        'pending' => __('Pending', 'mlm-member-plugin'),
        'free' => __('Free', 'mlm-member-plugin')
    );
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * Get admin message
 */
function mmp_get_admin_message($message_key) {
    $messages = array(
        'member_activated' => __('Member activated successfully.', 'mlm-member-plugin'),
        'member_deactivated' => __('Member deactivated successfully.', 'mlm-member-plugin'),
        'member_deleted' => __('Member deleted successfully.', 'mlm-member-plugin'),
        'activation_failed' => __('Failed to activate member.', 'mlm-member-plugin'),
        'deactivation_failed' => __('Failed to deactivate member.', 'mlm-member-plugin'),
        'deletion_failed' => __('Failed to delete member.', 'mlm-member-plugin')
    );
    
    return isset($messages[$message_key]) ? $messages[$message_key] : $message_key;
}

/**
 * Get sort URL
 */
function mmp_get_sort_url($column) {
    $current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';
    $current_order = isset($_GET['order']) ? $_GET['order'] : 'desc';
    
    $new_order = ($current_orderby === $column && $current_order === 'desc') ? 'asc' : 'desc';
    
    $args = array_merge($_GET, array(
        'orderby' => $column,
        'order' => $new_order
    ));
    
    return add_query_arg($args, admin_url('admin.php'));
}

/**
 * Display sort indicator
 */
function mmp_display_sort_indicator($column) {
    $current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';
    $current_order = isset($_GET['order']) ? $_GET['order'] : 'desc';
    
    if ($current_orderby === $column) {
        echo '<span class="sorting-indicator">';
        echo $current_order === 'asc' ? '↑' : '↓';
        echo '</span>';
    }
}

/**
 * Get pagination links
 */
function mmp_get_pagination_links($current_page, $total_pages) {
    $links = array();
    
    // Previous page
    if ($current_page > 1) {
        $prev_args = array_merge($_GET, array('paged' => $current_page - 1));
        $links[] = '<a class="prev-page button" href="' . esc_url(add_query_arg($prev_args, admin_url('admin.php'))) . '">' . __('Previous', 'mlm-member-plugin') . '</a>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $first_args = array_merge($_GET, array('paged' => 1));
        $links[] = '<a class="first-page button" href="' . esc_url(add_query_arg($first_args, admin_url('admin.php'))) . '">1</a>';
        
        if ($start > 2) {
            $links[] = '<span class="paging-input">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $links[] = '<span class="current-page">' . $i . '</span>';
        } else {
            $page_args = array_merge($_GET, array('paged' => $i));
            $links[] = '<a class="page-numbers" href="' . esc_url(add_query_arg($page_args, admin_url('admin.php'))) . '">' . $i . '</a>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $links[] = '<span class="paging-input">...</span>';
        }
        
        $last_args = array_merge($_GET, array('paged' => $total_pages));
        $links[] = '<a class="last-page button" href="' . esc_url(add_query_arg($last_args, admin_url('admin.php'))) . '">' . $total_pages . '</a>';
    }
    
    // Next page
    if ($current_page < $total_pages) {
        $next_args = array_merge($_GET, array('paged' => $current_page + 1));
        $links[] = '<a class="next-page button" href="' . esc_url(add_query_arg($next_args, admin_url('admin.php'))) . '">' . __('Next', 'mlm-member-plugin') . '</a>';
    }
    
    return implode(' ', $links);
}
