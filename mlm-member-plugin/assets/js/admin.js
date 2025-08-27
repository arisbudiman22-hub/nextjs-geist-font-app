/**
 * Admin JavaScript for MLM Member Plugin
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize admin functionality
    initAdminFeatures();
    
    /**
     * Initialize all admin features
     */
    function initAdminFeatures() {
        initMemberActions();
        initFormBuilder();
        initNotifications();
        initModals();
        initTooltips();
    }
    
    /**
     * Initialize member actions
     */
    function initMemberActions() {
        // Bulk actions
        $('.bulkactions select').on('change', function() {
            var action = $(this).val();
            var submitBtn = $(this).siblings('input[type="submit"]');
            
            if (action === 'delete') {
                submitBtn.addClass('button-danger');
            } else {
                submitBtn.removeClass('button-danger');
            }
        });
        
        // Individual member actions
        $('.mmp-action-buttons .button-link-delete').on('click', function(e) {
            if (!confirm(mmp_ajax.strings.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Member status update via AJAX
        $('.mmp-status-toggle').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var memberId = $button.data('member-id');
            var newStatus = $button.data('status');
            
            $button.prop('disabled', true).text(mmp_ajax.strings.processing);
            
            $.ajax({
                url: mmp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mmp_update_member_status',
                    member_id: memberId,
                    status: newStatus,
                    nonce: mmp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || mmp_ajax.strings.error);
                        $button.prop('disabled', false).text($button.data('original-text'));
                    }
                },
                error: function() {
                    alert(mmp_ajax.strings.error);
                    $button.prop('disabled', false).text($button.data('original-text'));
                }
            });
        });
        
        // Member details modal
        $('.member-details').on('click', function(e) {
            e.preventDefault();
            
            var memberId = $(this).data('member-id');
            var $modal = $('#member-details-modal');
            var $content = $('#member-details-content');
            
            $content.html('<div class="mmp-loading-spinner">Loading...</div>');
            $modal.show();
            
            $.ajax({
                url: mmp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mmp_get_member_details',
                    member_id: memberId,
                    nonce: mmp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $content.html(response.data);
                    } else {
                        $content.html('<p>Error loading member details.</p>');
                    }
                },
                error: function() {
                    $content.html('<p>Error loading member details.</p>');
                }
            });
        });
    }
    
    /**
     * Initialize form builder functionality
     */
    function initFormBuilder() {
        if (!$('.mmp-form-builder').length) return;
        
        var fieldIndex = 0;
        
        // Make form fields sortable
        if ($('#mmp-form-fields').length) {
            $('#mmp-form-fields').sortable({
                placeholder: 'mmp-field-placeholder',
                handle: '.mmp-field-handle',
                update: function() {
                    updateFormData();
                }
            });
        }
        
        // Field type drag and drop
        $('.mmp-field-type').draggable({
            helper: 'clone',
            revert: 'invalid',
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
                            <button type="button" class="mmp-field-settings button button-small">Settings</button>
                            <button type="button" class="mmp-field-delete button button-small">Delete</button>
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
                'text': 'Text Field',
                'email': 'Email Field',
                'tel': 'Phone Field',
                'password': 'Password Field',
                'textarea': 'Textarea',
                'select': 'Select Dropdown',
                'radio': 'Radio Buttons',
                'checkbox': 'Checkbox',
                'date': 'Date Field',
                'number': 'Number Field'
            };
            return labels[type] || type;
        }
        
        // Update form data
        function updateFormData() {
            var formFields = [];
            $('#mmp-form-fields .mmp-form-field').each(function() {
                try {
                    var fieldData = JSON.parse($(this).find('.mmp-field-data').text());
                    formFields.push(fieldData);
                } catch (e) {
                    console.error('Error parsing field data:', e);
                }
            });
            $('#form_fields_data').val(JSON.stringify(formFields));
        }
        
        // Delete field
        $(document).on('click', '.mmp-field-delete', function() {
            if (confirm('Are you sure you want to delete this field?')) {
                $(this).closest('.mmp-form-field').remove();
                updateFormData();
                
                if ($('#mmp-form-fields .mmp-form-field').length === 0) {
                    $('#mmp-form-fields').html('<div class="mmp-empty-form"><p>Drag fields from the sidebar to build your form</p></div>');
                }
            }
        });
        
        // Field settings
        $(document).on('click', '.mmp-field-settings', function() {
            var $field = $(this).closest('.mmp-form-field');
            var fieldData = JSON.parse($field.find('.mmp-field-data').text());
            
            // Open field settings modal
            openFieldSettingsModal(fieldData, $field);
        });
        
        // Copy shortcode
        $('.copy-shortcode').on('click', function() {
            var shortcode = $(this).data('shortcode');
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    showNotification('Shortcode copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = shortcode;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Shortcode copied to clipboard!', 'success');
            }
        });
    }
    
    /**
     * Open field settings modal
     */
    function openFieldSettingsModal(fieldData, $field) {
        var $modal = $('#field-settings-modal');
        var $content = $('#field-settings-content');
        
        // Generate settings form based on field type
        var settingsHtml = generateFieldSettingsHtml(fieldData);
        $content.html(settingsHtml);
        
        $modal.show();
        
        // Save field settings
        $('#save-field-settings').off('click').on('click', function() {
            var updatedData = collectFieldSettings(fieldData.type);
            $field.find('.mmp-field-data').text(JSON.stringify(updatedData));
            
            // Update field preview
            var previewHtml = generateFieldPreview(updatedData.type, updatedData.name, updatedData.label);
            $field.find('.mmp-field-preview').html(previewHtml);
            $field.find('.mmp-field-label').text(updatedData.label);
            
            updateFormData();
            $modal.hide();
        });
    }
    
    /**
     * Generate field settings HTML
     */
    function generateFieldSettingsHtml(fieldData) {
        var html = `
            <div class="mmp-field-settings-form">
                <table class="form-table">
                    <tr>
                        <th><label for="field-label">Field Label</label></th>
                        <td><input type="text" id="field-label" value="${fieldData.label}" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="field-name">Field Name</label></th>
                        <td><input type="text" id="field-name" value="${fieldData.name}" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="field-placeholder">Placeholder</label></th>
                        <td><input type="text" id="field-placeholder" value="${fieldData.placeholder || ''}" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="field-required">Required</label></th>
                        <td><input type="checkbox" id="field-required" ${fieldData.required ? 'checked' : ''} /> This field is required</td>
                    </tr>
        `;
        
        // Add options for select and radio fields
        if (fieldData.type === 'select' || fieldData.type === 'radio') {
            html += `
                <tr>
                    <th><label>Options</label></th>
                    <td>
                        <div id="field-options">
            `;
            
            var options = fieldData.options || ['Option 1', 'Option 2'];
            options.forEach(function(option, index) {
                html += `
                    <div class="option-row">
                        <input type="text" value="${option}" class="option-value regular-text" />
                        <button type="button" class="button remove-option">Remove</button>
                    </div>
                `;
            });
            
            html += `
                        </div>
                        <button type="button" id="add-option" class="button">Add Option</button>
                    </td>
                </tr>
            `;
        }
        
        html += `
                </table>
            </div>
        `;
        
        return html;
    }
    
    /**
     * Collect field settings from modal
     */
    function collectFieldSettings(fieldType) {
        var data = {
            type: fieldType,
            name: $('#field-name').val(),
            label: $('#field-label').val(),
            placeholder: $('#field-placeholder').val(),
            required: $('#field-required').is(':checked'),
            options: [],
            validation: {}
        };
        
        // Collect options for select and radio fields
        if (fieldType === 'select' || fieldType === 'radio') {
            $('.option-value').each(function() {
                var value = $(this).val().trim();
                if (value) {
                    data.options.push(value);
                }
            });
        }
        
        return data;
    }
    
    /**
     * Initialize notifications
     */
    function initNotifications() {
        // Auto-hide success messages
        $('.notice.is-dismissible').each(function() {
            var $notice = $(this);
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        });
    }
    
    /**
     * Initialize modals
     */
    function initModals() {
        // Close modal when clicking close button or outside modal
        $(document).on('click', '.mmp-modal-close, .mmp-modal', function(e) {
            if (e.target === this) {
                $(this).closest('.mmp-modal').hide();
            }
        });
        
        // Prevent modal from closing when clicking inside modal content
        $(document).on('click', '.mmp-modal-content', function(e) {
            e.stopPropagation();
        });
        
        // Add/remove option handlers for field settings
        $(document).on('click', '#add-option', function() {
            var optionHtml = `
                <div class="option-row">
                    <input type="text" value="" class="option-value regular-text" placeholder="Enter option" />
                    <button type="button" class="button remove-option">Remove</button>
                </div>
            `;
            $('#field-options').append(optionHtml);
        });
        
        $(document).on('click', '.remove-option', function() {
            $(this).closest('.option-row').remove();
        });
    }
    
    /**
     * Initialize tooltips
     */
    function initTooltips() {
        // Add tooltips to help icons
        $('.help-tip').tooltip({
            position: { my: "left+15 center", at: "right center" }
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $(`
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after($notification);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
        
        // Handle dismiss button
        $notification.find('.notice-dismiss').on('click', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Export members functionality
     */
    $('#export-members').on('click', function(e) {
        e.preventDefault();
        
        var params = new URLSearchParams(window.location.search);
        params.set('action', 'export_members');
        params.set('_wpnonce', mmp_ajax.nonce);
        
        // Create temporary link and trigger download
        var link = document.createElement('a');
        link.href = '?' + params.toString();
        link.download = 'members-export.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    /**
     * Select all checkboxes functionality
     */
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('input[name="members[]"]').prop('checked', isChecked);
    });
    
    // Update select all checkbox when individual checkboxes change
    $('input[name="members[]"]').on('change', function() {
        var totalCheckboxes = $('input[name="members[]"]').length;
        var checkedCheckboxes = $('input[name="members[]"]:checked').length;
        
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    /**
     * Form validation
     */
    $('form').on('submit', function(e) {
        var $form = $(this);
        var isValid = true;
        
        // Check required fields
        $form.find('input[required], select[required], textarea[required]').each(function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (!value) {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Please fill in all required fields.', 'error');
            return false;
        }
    });
    
    /**
     * AJAX loading states
     */
    $(document).ajaxStart(function() {
        $('body').addClass('mmp-loading');
    }).ajaxStop(function() {
        $('body').removeClass('mmp-loading');
    });
});
