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
                    custom_code: $('#gen_custom_code').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#generated-link').val(response.data.referral_link);
                        $('#generated-code').text(response.data.referral_code);
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

    // Initialize when ready
    $(document).ready(init);

})(jQuery);
