/**
 * Member Area JavaScript for MLM Member Plugin
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize member area functionality
    initMemberFeatures();
    
    /**
     * Initialize all member features
     */
    function initMemberFeatures() {
        initNotifications();
        initStatistics();
        initProfileForm();
        initNetworkTree();
        initDownloads();
        initReplicaLinks();
        initRealTimeUpdates();
    }
    
    /**
     * Initialize real-time notifications
     */
    function initNotifications() {
        // Create notifications container if it doesn't exist
        if (!$('.mmp-notifications').length) {
            $('body').append('<div class="mmp-notifications"></div>');
        }
        
        // Poll for new notifications every 30 seconds
        if (typeof mmp_member_ajax !== 'undefined') {
            setInterval(function() {
                fetchNotifications();
            }, 30000);
            
            // Fetch initial notifications
            fetchNotifications();
        }
        
        // Handle notification close
        $(document).on('click', '.mmp-notification .close', function() {
            var $notification = $(this).closest('.mmp-notification');
            var notificationId = $notification.data('id');
            
            $notification.fadeOut(function() {
                $(this).remove();
            });
            
            // Mark as read on server
            if (notificationId) {
                markNotificationRead(notificationId);
            }
        });
        
        // Auto-hide notifications after 10 seconds
        $(document).on('mmp:notification:added', '.mmp-notification', function() {
            var $notification = $(this);
            setTimeout(function() {
                if ($notification.is(':visible')) {
                    $notification.fadeOut(function() {
                        $(this).remove();
                    });
                }
            }, 10000);
        });
    }
    
    /**
     * Fetch notifications from server
     */
    function fetchNotifications() {
        $.ajax({
            url: mmp_member_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mmp_get_notifications',
                nonce: mmp_member_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    displayNotifications(response.data);
                }
            },
            error: function() {
                console.log('Error fetching notifications');
            }
        });
    }
    
    /**
     * Display notifications
     */
    function displayNotifications(notifications) {
        var $container = $('.mmp-notifications');
        
        notifications.forEach(function(notification) {
            // Check if notification already exists
            if ($container.find('[data-id="' + notification.id + '"]').length > 0) {
                return;
            }
            
            var $notification = $(`
                <div class="mmp-notification ${notification.type}" data-id="${notification.id}">
                    <div class="title">${notification.title}</div>
                    <div class="message">${notification.message}</div>
                    <button class="close">&times;</button>
                </div>
            `);
            
            $container.prepend($notification);
            $notification.trigger('mmp:notification:added');
        });
    }
    
    /**
     * Mark notification as read
     */
    function markNotificationRead(notificationId) {
        $.ajax({
            url: mmp_member_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mmp_mark_notification_read',
                notification_id: notificationId,
                nonce: mmp_member_ajax.nonce
            }
        });
    }
    
    /**
     * Initialize statistics updates
     */
    function initStatistics() {
        // Update statistics every 5 minutes
        setInterval(function() {
            updateStatistics();
        }, 300000);
        
        // Update statistics when page becomes visible
        $(document).on('visibilitychange', function() {
            if (!document.hidden) {
                updateStatistics();
            }
        });
    }
    
    /**
     * Update statistics
     */
    function updateStatistics() {
        $('.mmp-stats-grid .mmp-stat-card').each(function() {
            var $card = $(this);
            var statType = $card.data('stat-type');
            
            if (statType) {
                $.ajax({
                    url: mmp_member_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mmp_get_stat',
                        stat_type: statType,
                        nonce: mmp_member_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var $number = $card.find('.stat-number');
                            var currentValue = parseInt($number.text()) || 0;
                            var newValue = parseInt(response.data.value) || 0;
                            
                            if (newValue !== currentValue) {
                                animateNumber($number, currentValue, newValue);
                            }
                        }
                    }
                });
            }
        });
    }
    
    /**
     * Animate number change
     */
    function animateNumber($element, from, to) {
        var duration = 1000;
        var start = Date.now();
        
        function update() {
            var now = Date.now();
            var progress = Math.min((now - start) / duration, 1);
            var current = Math.floor(from + (to - from) * progress);
            
            $element.text(current);
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        update();
    }
    
    /**
     * Initialize profile form
     */
    function initProfileForm() {
        var $profileForm = $('.mmp-profile-form');
        
        if ($profileForm.length === 0) return;
        
        // Form validation
        $profileForm.on('submit', function(e) {
            var isValid = validateForm($(this));
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            var $submitBtn = $(this).find('input[type="submit"], button[type="submit"]');
            $submitBtn.prop('disabled', true).val(mmp_member_ajax.strings.loading);
        });
        
        // Real-time validation
        $profileForm.find('input, textarea, select').on('blur', function() {
            validateField($(this));
        });
        
        // Password strength indicator
        $profileForm.find('input[type="password"]').on('input', function() {
            var password = $(this).val();
            var strength = calculatePasswordStrength(password);
            showPasswordStrength($(this), strength);
        });
    }
    
    /**
     * Validate form
     */
    function validateForm($form) {
        var isValid = true;
        
        $form.find('input[required], textarea[required], select[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Validate individual field
     */
    function validateField($field) {
        var value = $field.val().trim();
        var fieldType = $field.attr('type');
        var isRequired = $field.prop('required');
        var isValid = true;
        var errorMessage = '';
        
        // Remove existing error states
        $field.removeClass('error');
        $field.siblings('.field-error').remove();
        
        // Required field validation
        if (isRequired && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }
        
        // Email validation
        if (fieldType === 'email' && value && !isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address.';
        }
        
        // Phone validation
        if (fieldType === 'tel' && value && !isValidPhone(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid phone number.';
        }
        
        // Password validation
        if (fieldType === 'password' && value && value.length < 6) {
            isValid = false;
            errorMessage = 'Password must be at least 6 characters long.';
        }
        
        // Show error state
        if (!isValid) {
            $field.addClass('error');
            $field.after('<div class="field-error">' + errorMessage + '</div>');
        }
        
        return isValid;
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Validate phone format
     */
    function isValidPhone(phone) {
        var phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
    }
    
    /**
     * Calculate password strength
     */
    function calculatePasswordStrength(password) {
        var score = 0;
        
        if (password.length >= 8) score += 1;
        if (password.match(/[a-z]/)) score += 1;
        if (password.match(/[A-Z]/)) score += 1;
        if (password.match(/[0-9]/)) score += 1;
        if (password.match(/[^a-zA-Z0-9]/)) score += 1;
        
        return score;
    }
    
    /**
     * Show password strength indicator
     */
    function showPasswordStrength($field, strength) {
        var $indicator = $field.siblings('.password-strength');
        
        if ($indicator.length === 0) {
            $indicator = $('<div class="password-strength"></div>');
            $field.after($indicator);
        }
        
        var strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        var strengthClass = ['very-weak', 'weak', 'fair', 'good', 'strong'];
        
        $indicator
            .removeClass('very-weak weak fair good strong')
            .addClass(strengthClass[strength] || 'very-weak')
            .text(strengthText[strength] || 'Very Weak');
    }
    
    /**
     * Initialize network tree
     */
    function initNetworkTree() {
        var $networkTree = $('.mmp-network-tree');
        
        if ($networkTree.length === 0) return;
        
        // Make tree nodes clickable for details
        $networkTree.on('click', '.mmp-tree-node', function() {
            var memberId = $(this).data('member-id');
            
            if (memberId) {
                showMemberDetails(memberId);
            }
        });
        
        // Expand/collapse functionality
        $networkTree.on('click', '.expand-toggle', function(e) {
            e.stopPropagation();
            
            var $node = $(this).closest('.mmp-tree-node');
            var $children = $node.find('.tree-children');
            
            if ($children.is(':visible')) {
                $children.slideUp();
                $(this).text('+');
            } else {
                $children.slideDown();
                $(this).text('-');
            }
        });
    }
    
    /**
     * Show member details modal
     */
    function showMemberDetails(memberId) {
        // Create modal if it doesn't exist
        if ($('#member-details-modal').length === 0) {
            $('body').append(`
                <div id="member-details-modal" class="mmp-modal">
                    <div class="mmp-modal-content">
                        <div class="mmp-modal-header">
                            <h3>Member Details</h3>
                            <span class="mmp-modal-close">&times;</span>
                        </div>
                        <div class="mmp-modal-body">
                            <div id="member-details-content"></div>
                        </div>
                    </div>
                </div>
            `);
        }
        
        var $modal = $('#member-details-modal');
        var $content = $('#member-details-content');
        
        $content.html('<div class="loading">Loading member details...</div>');
        $modal.show();
        
        $.ajax({
            url: mmp_member_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mmp_get_member_details',
                member_id: memberId,
                nonce: mmp_member_ajax.nonce
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
    }
    
    /**
     * Initialize downloads
     */
    function initDownloads() {
        // Track download clicks
        $('.mmp-download-item .mmp-btn').on('click', function() {
            var downloadId = $(this).data('download-id');
            
            if (downloadId) {
                trackDownload(downloadId);
            }
        });
    }
    
    /**
     * Track download
     */
    function trackDownload(downloadId) {
        $.ajax({
            url: mmp_member_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mmp_track_download',
                download_id: downloadId,
                nonce: mmp_member_ajax.nonce
            }
        });
    }
    
    /**
     * Initialize replica links
     */
    function initReplicaLinks() {
        // Copy replica link functionality
        $('.copy-replica-link').on('click', function(e) {
            e.preventDefault();
            
            var replicaUrl = $(this).data('url') || $('.mmp-replica-url').text();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(replicaUrl).then(function() {
                    showNotification('Replica link copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = replicaUrl;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Replica link copied to clipboard!', 'success');
            }
        });
        
        // Share replica link
        $('.share-replica-link').on('click', function(e) {
            e.preventDefault();
            
            var replicaUrl = $(this).data('url') || $('.mmp-replica-url').text();
            var shareText = 'Check out this amazing opportunity!';
            
            if (navigator.share) {
                navigator.share({
                    title: 'Join My Network',
                    text: shareText,
                    url: replicaUrl
                });
            } else {
                // Fallback - open share options
                showShareOptions(replicaUrl, shareText);
            }
        });
    }
    
    /**
     * Show share options
     */
    function showShareOptions(url, text) {
        var encodedUrl = encodeURIComponent(url);
        var encodedText = encodeURIComponent(text);
        
        var shareOptions = [
            {
                name: 'Facebook',
                url: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
                icon: 'facebook'
            },
            {
                name: 'Twitter',
                url: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`,
                icon: 'twitter'
            },
            {
                name: 'LinkedIn',
                url: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
                icon: 'linkedin'
            },
            {
                name: 'WhatsApp',
                url: `https://wa.me/?text=${encodedText}%20${encodedUrl}`,
                icon: 'whatsapp'
            }
        ];
        
        var shareHtml = '<div class="share-options">';
        shareOptions.forEach(function(option) {
            shareHtml += `<a href="${option.url}" target="_blank" class="share-option ${option.icon}">${option.name}</a>`;
        });
        shareHtml += '</div>';
        
        showModal('Share Your Link', shareHtml);
    }
    
    /**
     * Initialize real-time updates
     */
    function initRealTimeUpdates() {
        // Update page data every 2 minutes
        setInterval(function() {
            updatePageData();
        }, 120000);
    }
    
    /**
     * Update page data
     */
    function updatePageData() {
        // Update statistics
        updateStatistics();
        
        // Update member count if on members page
        if ($('.mmp-member-list').length > 0) {
            updateMemberList();
        }
        
        // Update network tree if visible
        if ($('.mmp-network-tree').length > 0) {
            updateNetworkTree();
        }
    }
    
    /**
     * Update member list
     */
    function updateMemberList() {
        $.ajax({
            url: mmp_member_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mmp_get_member_list',
                nonce: mmp_member_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.mmp-member-list').html(response.data);
                }
            }
        });
    }
    
    /**
     * Update network tree
     */
    function updateNetworkTree() {
        $.ajax({
            url: mmp_member_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mmp_get_network_tree',
                nonce: mmp_member_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.mmp-network-tree').html(response.data);
                }
            }
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $(`
            <div class="mmp-notification ${type}">
                <div class="message">${message}</div>
                <button class="close">&times;</button>
            </div>
        `);
        
        $('.mmp-notifications').prepend($notification);
        $notification.trigger('mmp:notification:added');
    }
    
    /**
     * Show modal
     */
    function showModal(title, content) {
        var modalId = 'mmp-modal-' + Date.now();
        
        var $modal = $(`
            <div id="${modalId}" class="mmp-modal">
                <div class="mmp-modal-content">
                    <div class="mmp-modal-header">
                        <h3>${title}</h3>
                        <span class="mmp-modal-close">&times;</span>
                    </div>
                    <div class="mmp-modal-body">
                        ${content}
                    </div>
                </div>
            </div>
        `);
        
        $('body').append($modal);
        $modal.show();
        
        // Auto-remove modal after 30 seconds
        setTimeout(function() {
            $modal.remove();
        }, 30000);
    }
    
    /**
     * Close modals
     */
    $(document).on('click', '.mmp-modal-close, .mmp-modal', function(e) {
        if (e.target === this) {
            $(this).closest('.mmp-modal').fadeOut(function() {
                $(this).remove();
            });
        }
    });
    
    /**
     * Prevent modal content clicks from closing modal
     */
    $(document).on('click', '.mmp-modal-content', function(e) {
        e.stopPropagation();
    });
    
    /**
     * Handle form submissions with AJAX
     */
    $('.mmp-ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
        var originalText = $submitBtn.val() || $submitBtn.text();
        
        // Show loading state
        $submitBtn.prop('disabled', true).val(mmp_member_ajax.strings.loading);
        
        $.ajax({
            url: mmp_member_ajax.ajax_url,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message || 'Success!', 'success');
                    
                    // Reset form if specified
                    if (response.data.reset_form) {
                        $form[0].reset();
                    }
                    
                    // Redirect if specified
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    showNotification(response.data.message || 'An error occurred.', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                // Restore button state
                $submitBtn.prop('disabled', false).val(originalText);
            }
        });
    });
    
    /**
     * Smooth scrolling for anchor links
     */
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    /**
     * Initialize tooltips
     */
    $('[data-tooltip]').each(function() {
        var $element = $(this);
        var tooltipText = $element.data('tooltip');
        
        $element.on('mouseenter', function() {
            var $tooltip = $('<div class="mmp-tooltip">' + tooltipText + '</div>');
            $('body').append($tooltip);
            
            var elementOffset = $element.offset();
            $tooltip.css({
                top: elementOffset.top - $tooltip.outerHeight() - 10,
                left: elementOffset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
            });
        });
        
        $element.on('mouseleave', function() {
            $('.mmp-tooltip').remove();
        });
    });
});
