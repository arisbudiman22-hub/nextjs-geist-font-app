# WordPress Plugin "MLM Member Recruitment" – Development Plan

This plan details all changes, dependent files, and best practices required to develop a WordPress plugin that supports MLM/affiliate member recruitment. The plugin includes comprehensive admin interfaces and member area pages integrated via shortcodes for use with Elementor. All inputs are validated; error handling and security (nonces, capability checks, sanitization/escaping) are applied across all forms and AJAX endpoints.

---

## 1. Plugin Structure and File Organization

Create a new directory called `mlm-member-plugin` with the following structure:

```
mlm-member-plugin/

├── mlm-member-plugin.php
├── README.md
├── languages/
│   └── mlm-member-plugin.pot
├── assets/
│   ├── admin.css
│   ├── member.css
│   └── js/
│       ├── admin.js
│       └── member.js
└── includes/
    ├── inc.functions.php
    ├── inc.db.php
    ├── admin/
    │   ├── pengaturan-umum.php
    │   ├── email-config.php
    │   ├── form-builder.php
    │   ├── member-list.php
    │   ├── atur-download.php
    │   └── shortcode-builder.php
    └── public/
        ├── dashboard.php
        ├── profile.php
        ├── member.php
        ├── jaringan.php
        ├── download.php
        └── statistik.php
```

---

## 2. Main Plugin File: mlm-member-plugin.php

- **Plugin Header & Includes:**  
  - Add standard plugin headers (name, description, version, author).  
  - Include files from the `includes/` folder (e.g., `inc.functions.php` and `inc.db.php`).  
  - Prevent direct access by checking if `ABSPATH` is defined.

- **Hooks and Shortcodes:**  
  - Register activation and deactivation hooks to create necessary database tables via `inc.db.php`.  
  - Register admin menus and shortcodes (e.g., `[mlm_dashboard]`, `[mlm_profile]`, etc.) that map to files in the `includes/public/` folder.  
  - Enqueue CSS/JS files using `wp_enqueue_scripts()` and `admin_enqueue_scripts()`.

- **Error Handling:**  
  - Validate file includes and check for function existence; log errors when necessary.

---

## 3. Activation/Deactivation & Database Setup (includes/inc.db.php)

- **Database Tables Creation:**  
  - Create tables (e.g., `wp_mlm_members`, `wp_mlm_forms`, `wp_mlm_downloads`) using the `dbDelta()` function on activation.  
  - Define columns for member details (user_id, sponsor_id, status, registration_date, etc.) and store form configurations as JSON.  
  - For deactivation, clean up temporary data if needed (do not drop tables unless specified).

- **Error & Logging:**  
  - Implement error checking/logging during table creation.

---

## 4. Admin Area Pages (includes/admin/)

### 4.1. pengaturan-umum.php  
- **Functionality:**  
  - Create a settings page with form fields for:  
    - Member area URL, registration page, and success page settings.  
    - Bank details for payment, admin contact number, and notification email.  
    - Network tree settings and default sponsor configuration.  
    - Replica URL type selection (subdomain vs. username).  
- **Implementation:**  
  - Use the WordPress Settings API with nonce fields and capability checks.  
  - Sanitize and validate all inputs.

### 4.2. email-config.php  
- **Functionality:**  
  - Provide an interface to edit notification email templates and other message contents.  
- **Implementation:**  
  - Optionally integrate a rich text editor.  
  - Validate submissions using nonces and proper sanitization.

### 4.3. form-builder.php  
- **Functionality:**  
  - Implement a drag-and-drop form builder for custom dynamic forms (e.g., registration, profile, subscribe forms).  
- **Implementation:**  
  - Use HTML5 drag-and-drop with custom JavaScript (in `assets/js/admin.js`).  
  - Save form configuration as JSON, validating required fields.

### 4.4. member-list.php  
- **Functionality:**  
  - Display a paginated, sortable table of all members with search & filter options.  
  - Enable actions: activation/deactivation, deletion, export.  
- **Implementation:**  
  - Use AJAX (with proper nonce verification) to update member status in real time.

### 4.5. atur-download.php  
- **Functionality:**  
  - Allow admin to upload/manage downloadable files using the WP Media uploader.  
- **Implementation:**  
  - Validate file types/sizes with `wp_check_filetype()`.  
  - List files with options to delete or update.

### 4.6. shortcode-builder.php  
- **Functionality:**  
  - Provide an interface to generate dynamic shortcodes (with configurable parameters) for use in Elementor.  
- **Implementation:**  
  - Offer preview and “copy-to-clipboard” functionality.  
  - Ensure inputs are sanitized and validated.

---

## 5. Member (Public) Area Pages (includes/public/)

Each page is rendered via a shortcode for easy Elementor integration:

- **dashboard.php:**  
  - Display member-specific statistics, text-based graphs (using HTML/CSS) and profile summary.  
  - Use AJAX (via `assets/js/member.js`) for real-time notifications.

- **profile.php:**  
  - Provide a form to edit member profile details. Form fields are dynamically generated from the form-builder configuration.

- **member.php:**  
  - Show the list of members recruited (referrals) with search and filter functionality.

- **jaringan.php:**  
  - Render a network tree using nested HTML lists and CSS for visual hierarchy.  
  - Ensure graceful layout without external images.

- **download.php:**  
  - List files available for download (fetched from the database) with secure URLs.

- **statistik.php:**  
  - Display real-time visit statistics for the member’s replica link; use periodic AJAX calls.

- **logout:**  
  - Implement a logout link using `wp_logout_url()`.

Each page validates that the user is logged in and has appropriate permissions before output.

---

## 6. UI/UX Considerations

- **Modern, Clean Interface:**  
  - Admin and member interfaces use custom CSS (in `assets/admin.css` and `assets/member.css`) that emphasize typography, spacing, and layout for a modern look.  
  - Avoid external icon libraries; use text-based indicators or CSS shapes where needed.

- **Form & Notification Design:**  
  - Display inline error messages and confirmations.  
  - If images are used (e.g., banners), use `<img>` tags with placeholder URLs such as:  
    ```html
    <img src="https://placehold.co/1200x400?text=Admin+banner+display+for+modern+interface" alt="A modern styled banner for admin interface, featuring clean typography and spacious layout" onerror="this.style.display='none';"/>
    ```
  - Ensure accessibility by adding detailed alt text.

- **Drag-and-Drop & AJAX:**  
  - Use native HTML5 features for drag-and-drop in the form-builder.  
  - Real-time notifications on member pages are implemented using AJAX polling with graceful error handling.

---

## 7. AJAX Endpoints & Real-Time Notifications

- **AJAX for Notifications:**  
  - Register custom AJAX actions (using `wp_ajax_` hooks) to periodically fetch notification data for logged-in members.  
  - Validate nonces and return JSON responses with proper HTTP status codes and error messages on failure.

- **Error Handling:**  
  - Ensure responses include clear error states and fallback behavior if the AJAX request fails.

---

## 8. Security, Error Handling, and Best Practices

- **Capability Checks & Nonces:**  
  - For every admin page and form submission, use `current_user_can('manage_options')` (or appropriate capabilities) and nonce verification.

- **Input Sanitization & Output Escaping:**  
  - Use functions like `sanitize_text_field()`, `sanitize_email()`, and `esc_html()` to prevent XSS/SQL injection vulnerabilities.

- **Logging and Debugging:**  
  - Use PHP error logging (`error_log()`) for debugging database errors and other critical failures.

- **Documentation & Readability:**  
  - Add inline comments, PHPDoc blocks, and update the README.md with installation and usage instructions.

---

## 9. Documentation and Testing

- **README.md:**  
  - Describe how to install and configure the plugin, including instructions to integrate shortcodes with Elementor.

- **Testing:**  
  - Set up a local WordPress environment to test installation, form submissions, AJAX notifications, and member actions.  
  - Use the browser console and WP_DEBUG for error identification; ensure that all features (activation, form builder, member list management, etc.) function as expected.

---

**Summary:**
- The plan creates a new WordPress plugin structure with dedicated directories for admin and public pages, assets, and language files.
- The main plugin file sets up hooks, includes all dependencies, and registers shortcodes.
- Admin pages provide configuration interfaces for general settings, email templates, a drag-and-drop form builder, member management, download management, and shortcode generation.
- Member area pages are rendered via shortcodes, offering dashboard, profile, network tree, download, and statistics features with AJAX-based real-time notifications.
- The UI is modern and clean, using custom CSS and accessible HTML, while ensuring secure input validation and error handling.
- Comprehensive documentation and testing instructions are provided to ensure smooth integration with Elementor and WordPress.

