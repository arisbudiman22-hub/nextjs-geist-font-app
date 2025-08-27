# MLM Member Recruitment Plugin

A comprehensive WordPress plugin for MLM (Multi-Level Marketing) and affiliate member recruitment with advanced member management, form builder, network tree visualization, and Elementor integration.

## Features

### Admin Features
- **General Settings**: Configure member area pages, contact details, sponsor settings, replica URL types, and payment bank details
- **Member Management**: Complete member list with search, filter, bulk actions, activation/deactivation, and export functionality
- **Form Builder**: Drag-and-drop form builder with multiple field types for registration, profile, prospect, and custom forms
- **Email Configuration**: Customizable email templates for welcome messages, notifications, and member communications
- **Download Management**: Upload and manage files for member downloads with access level controls
- **Shortcode Builder**: Generate dynamic shortcodes for use with Elementor and other page builders

### Member Area Features
- **Dashboard**: Statistics overview, recent activity, replica link management, and quick actions
- **Profile Management**: Edit personal information using custom forms created in the form builder
- **Network Tree**: Visual representation of referral network with multiple levels
- **Member List**: View and manage recruited members with status tracking
- **Downloads**: Access to files based on member status (free, active, premium)
- **Statistics**: Real-time analytics for link visits, conversions, and performance tracking
- **Real-time Notifications**: Live updates for new referrals, status changes, and important announcements

### Technical Features
- **Replica Link System**: Customizable URL structure (subdomain or path-based)
- **Multi-level Network**: Support for unlimited network depth with configurable display levels
- **Member Status Management**: Free, pending, active, and inactive member states
- **AJAX-powered Interface**: Real-time updates without page refreshes
- **Elementor Integration**: Full compatibility with Elementor page builder via shortcodes
- **Responsive Design**: Mobile-friendly interface for all devices
- **Security**: Nonce verification, capability checks, input sanitization, and output escaping

## Installation

1. **Upload Plugin Files**
   ```
   Upload the mlm-member-plugin folder to /wp-content/plugins/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "MLM Member Recruitment Plugin"
   - Click "Activate"

3. **Configure Settings**
   - Go to MLM Members → General Settings
   - Configure your basic settings:
     - Member area page
     - Registration page
     - Success page
     - Admin contact details
     - Bank details for payments

4. **Create Pages**
   - Create a page for member area (e.g., "Member Area")
   - Create a page for registration (e.g., "Join Us")
   - Create a success page (e.g., "Welcome")

## Usage

### Setting Up Member Area with Elementor

1. **Create Member Area Page**
   - Create a new page in WordPress
   - Edit with Elementor
   - Add HTML widget or shortcode widget

2. **Add Member Dashboard**
   ```
   [mlm_dashboard]
   ```

3. **Add Member Profile Form**
   ```
   [mlm_profile]
   ```

4. **Add Network Tree**
   ```
   [mlm_network]
   ```

5. **Add Member List**
   ```
   [mlm_members]
   ```

6. **Add Downloads Section**
   ```
   [mlm_downloads]
   ```

7. **Add Statistics**
   ```
   [mlm_statistics]
   ```

### Creating Registration Form

1. **Build Form**
   - Go to MLM Members → Form Builder
   - Create new registration form
   - Drag and drop fields as needed
   - Configure form settings

2. **Add to Page**
   ```
   [mlm_form id="1"]
   ```
   or
   ```
   [mlm_form type="registration"]
   ```

### Advanced Shortcodes

**Custom Form with Specific ID**
```
[mlm_form id="5"]
```

**Login Form**
```
[mlm_login]
```

**Logout Link**
```
[mlm_logout]
```

**Member-specific Data**
```
[mlm_member_data field="name"]
[mlm_member_data field="email"]
[mlm_member_data field="phone"]
[mlm_member_data field="member_code"]
[mlm_member_data field="replica_url"]
```

## Database Tables

The plugin creates the following database tables:

- `wp_mlm_members` - Member information and network relationships
- `wp_mlm_forms` - Form builder configurations
- `wp_mlm_form_submissions` - Form submission data
- `wp_mlm_downloads` - Downloadable files management
- `wp_mlm_download_logs` - Download tracking
- `wp_mlm_notifications` - Real-time notifications
- `wp_mlm_statistics` - Visit and conversion tracking
- `wp_mlm_email_templates` - Email template configurations

## Hooks and Filters

### Actions

```php
// After member registration
do_action('mmp_member_registered', $member_id, $user_id);

// After member activation
do_action('mmp_member_activated', $member_id);

// After form submission
do_action('mmp_form_submitted', $form_id, $submission_data);

// Before email send
do_action('mmp_before_email_send', $template_name, $to_email, $variables);
```

### Filters

```php
// Modify member registration data
$member_data = apply_filters('mmp_member_registration_data', $member_data);

// Modify email template variables
$variables = apply_filters('mmp_email_template_variables', $variables, $template_name);

// Modify form fields
$form_fields = apply_filters('mmp_form_fields', $form_fields, $form_id);

// Modify member access levels
$has_access = apply_filters('mmp_member_has_access', $has_access, $required_level, $user_id);
```

## Customization

### Custom CSS

Add custom styles to your theme's `style.css` or use the Customizer:

```css
/* Customize member dashboard */
.mmp-member-dashboard {
    /* Your custom styles */
}

/* Customize statistics cards */
.mmp-stat-card {
    /* Your custom styles */
}

/* Customize network tree */
.mmp-network-tree {
    /* Your custom styles */
}
```

### Custom Templates

Create custom templates in your theme:

```
your-theme/
├── mlm-templates/
│   ├── dashboard.php
│   ├── profile.php
│   ├── network.php
│   └── members.php
```

### Custom Functions

Add custom functionality using hooks:

```php
// Custom member registration processing
function my_custom_member_registration($member_id, $user_id) {
    // Your custom code here
}
add_action('mmp_member_registered', 'my_custom_member_registration', 10, 2);

// Custom email variables
function my_custom_email_variables($variables, $template_name) {
    $variables['custom_field'] = 'Custom Value';
    return $variables;
}
add_filter('mmp_email_template_variables', 'my_custom_email_variables', 10, 2);
```

## Replica Link Configuration

### Path-based URLs (Default)
```
https://yoursite.com/ref/MEMBERCODE
```

### Subdomain URLs (Requires Server Configuration)
```
https://membercode.yoursite.com
```

**Apache Configuration (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{HTTP_HOST} ^([^.]+)\.yoursite\.com$
RewriteRule ^(.*)$ https://yoursite.com/ref/%1/$1 [L,QSA]
```

**Nginx Configuration**
```nginx
server {
    server_name ~^(?<subdomain>.+)\.yoursite\.com$;
    return 301 https://yoursite.com/ref/$subdomain$request_uri;
}
```

## Troubleshooting

### Common Issues

**1. Database Tables Not Created**
- Deactivate and reactivate the plugin
- Check WordPress database user permissions
- Enable WordPress debug logging

**2. Shortcodes Not Working**
- Ensure plugin is activated
- Check for theme conflicts
- Verify shortcode syntax

**3. AJAX Requests Failing**
- Check for JavaScript errors in browser console
- Verify nonce values are correct
- Ensure user has proper permissions

**4. Email Templates Not Sending**
- Check WordPress email configuration
- Verify SMTP settings
- Test with a simple email plugin

### Debug Mode

Enable debug mode by adding to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('MMP_DEBUG', true);
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Modern web browser with JavaScript enabled

## Browser Support

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Security Features

- **Nonce Verification**: All forms and AJAX requests use WordPress nonces
- **Capability Checks**: Admin functions require proper user capabilities
- **Input Sanitization**: All user inputs are sanitized before processing
- **Output Escaping**: All outputs are escaped to prevent XSS attacks
- **SQL Injection Prevention**: All database queries use prepared statements

## Performance Optimization

- **Lazy Loading**: Statistics and notifications load on demand
- **Caching**: Database queries are optimized with proper indexing
- **Minified Assets**: CSS and JavaScript files are optimized for production
- **AJAX Updates**: Real-time updates without full page reloads

## Support

For support and documentation:

1. Check the plugin settings and configuration
2. Review the troubleshooting section
3. Enable debug mode to identify issues
4. Contact plugin developer with debug information

## Changelog

### Version 1.0.0
- Initial release
- Complete admin interface
- Member area with dashboard, profile, network tree
- Form builder with drag-and-drop functionality
- Real-time notifications and statistics
- Elementor integration via shortcodes
- Replica link system
- Email template management
- Download management system

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed with modern WordPress best practices and security standards.
