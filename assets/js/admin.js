/**
 * HubSpot Referrals - Admin Scripts
 *
 * @package HubSpot_Referrals
 */

(function($) {
    'use strict';

    const config = window.hsrAdmin || {};

    /**
     * Initialize admin functionality
     */
    function init() {
        bindGeneratorForm();
        bindSearchFilter();
        initColorPickers();
        bindDirectoryToggle();
        bindPartnerEdit();
    }
    
    /**
     * Initialize WordPress color pickers
     */
    function initColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.hsr-color-picker').wpColorPicker();
        }
    }

    /**
     * Bind generator form submission
     */
    function bindGeneratorForm() {
        $('#hsr-manual-generator').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const btnText = $btn.text();
            
            $btn.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsr_generate_code',
                    nonce: config.nonce,
                    first_name: $('#gen_first_name').val(),
                    last_name: $('#gen_last_name').val(),
                    email: $('#gen_email').val(),
                    organization: $('#gen_organization').val(),
                    custom_code: $('#gen_custom_code').val(),
                    send_email: $('#gen_send_email').is(':checked') ? '1' : '0'
                },
                success: function(response) {
                    if (response.success) {
                        $('#generated-link').val(response.data.referral_link);
                        $('#generated-code').text(response.data.referral_code);
                        
                        // Show email sent message if applicable
                        if (response.data.email_sent) {
                            const emailMsg = '<div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #28a745;">✓ Welcome email sent successfully!</div>';
                            $('#hsr-generator-result .result-success').prepend(emailMsg);
                        }
                        
                        $form.hide();
                        $('#hsr-generator-result').fadeIn();
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to generate link'));
                        $btn.prop('disabled', false).text(btnText);
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text(btnText);
                }
            });
        });
    }

    /**
     * Bind search filter
     */
    function bindSearchFilter() {
        $('#hsr-search').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#hsr-referral-tbody tr').each(function() {
                const $row = $(this);
                if ($row.hasClass('hsr-conversions-row')) return;
                
                const searchData = $row.data('search') || '';
                if (searchData.toString().indexOf(searchTerm) > -1) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        });
    }
    
    /**
     * Bind directory toggle switches
     */
    function bindDirectoryToggle() {
        $(document).on('change', '.hsr-directory-toggle', function() {
            const $toggle = $(this);
            const contactId = $toggle.data('contact-id');
            const showInDirectory = $toggle.is(':checked');
            
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsr_toggle_directory',
                    nonce: config.nonce,
                    contact_id: contactId,
                    show_in_directory: showInDirectory ? '1' : '0'
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Error: ' + (response.data.message || 'Failed to update'));
                        $toggle.prop('checked', !showInDirectory);
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $toggle.prop('checked', !showInDirectory);
                }
            });
        });
    }
    
    /**
     * Bind partner edit functionality
     */
    function bindPartnerEdit() {
        // Media uploader for logo
        let mediaUploader;
        $('#hsr-upload-logo-btn').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select Partner Logo',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#partner_logo_url').val(attachment.url);
            });
            
            mediaUploader.open();
        });
        
        // Form submit
        $('#hsr-partner-edit-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const btnText = $btn.text();
            
            $btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=hsr_update_partner',
                success: function(response) {
                    if (response.success) {
                        alert('Partner information updated successfully!');
                        hsrClosePartnerModal();
                        location.reload();
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to update'));
                        $btn.prop('disabled', false).text(btnText);
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text(btnText);
                }
            });
        });
    }

    // Global functions for inline onclick handlers
    window.hsrResetGenerator = function() {
        $('#hsr-manual-generator')[0].reset();
        $('#hsr-manual-generator').show();
        $('#hsr-generator-result').hide();
    };

    window.hsrCopyLink = function() {
        const input = document.getElementById('generated-link');
        input.select();
        document.execCommand('copy');
        alert('Link copied to clipboard!');
    };

    window.hsrCopyReferralLink = function(code) {
        const contactPage = config.contactPage || '/contact/';
        const siteUrl = config.siteUrl || window.location.origin;
        const link = siteUrl + contactPage + '?referral_source=' + code;
        
        const temp = document.createElement('input');
        document.body.appendChild(temp);
        temp.value = link;
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert('Referral link copied!');
    };

    window.hsrToggleConversions = function(code) {
        const row = document.getElementById('conversions-' + code);
        if (row) {
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
    };

    window.hsrRefreshData = function() {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hsr_refresh_data',
                nonce: config.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to refresh'));
                }
            },
            error: function() {
                alert('Network error. Please try again.');
            }
        });
    };

    window.hsrExportCSV = function() {
        const rows = [];
        rows.push(['Name', 'Organization', 'Code', 'Conversions', 'Created', 'Status']);
        
        $('#hsr-referral-tbody tr').each(function() {
            const $row = $(this);
            if ($row.hasClass('hsr-conversions-row')) return;
            
            const cells = $row.find('td');
            if (cells.length > 0) {
                rows.push([
                    cells.eq(0).text().trim(),
                    cells.eq(1).text().trim(),
                    cells.eq(2).text().trim(),
                    cells.eq(3).text().trim().split('\n')[0],
                    cells.eq(4).text().trim(),
                    cells.eq(5).text().trim()
                ]);
            }
        });
        
        const csvContent = rows.map(row => row.map(cell => '"' + cell.replace(/"/g, '""') + '"').join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'referrals-' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    };
    
    window.hsrEditPartner = function(contactId, code) {
        // Show modal
        $('#hsr-partner-modal').fadeIn();
        
        // Set contact ID and code
        $('#partner_contact_id').val(contactId);
        $('#partner_code').val(code);
        
        // Load partner data
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hsr_get_partner',
                nonce: config.nonce,
                contact_id: contactId
            },
            success: function(response) {
                if (response.success) {
                    const partner = response.data.partner;
                    $('#partner_logo_url').val(partner.logo_url || '');
                    $('#partner_description').val(partner.directory_description || '');
                    $('#partner_website').val(partner.website_url || '');
                    $('#partner_directory_order').val(partner.directory_order || 999);
                }
            }
        });
    };
    
    window.hsrClosePartnerModal = function() {
        $('#hsr-partner-modal').fadeOut();
        $('#hsr-partner-edit-form')[0].reset();
    };

    // Initialize when ready
    $(document).ready(init);

})(jQuery);
