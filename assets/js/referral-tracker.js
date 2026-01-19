/**
 * HubSpot Referrals - Frontend Tracker
 * 
 * Reads the referral cookie and injects it into all forms
 * so HubSpot can track which referral link brought the lead
 *
 * @package HubSpot_Referrals
 */

(function() {
    'use strict';

    // Get configuration from localized script
    const config = window.hsrConfig || {};
    const COOKIE_NAME = 'hsr_referral_code';
    
    /**
     * Initialize tracking
     */
    function init() {
        console.log('🔗 HubSpot Referrals: Tracking initialized');
        
        // Check URL for referral code first
        const urlParams = new URLSearchParams(window.location.search);
        const refFromUrl = urlParams.get(config.referralParam || 'referral_source');
        
        if (refFromUrl) {
            console.log(`📥 Referral code from URL: ${refFromUrl}`);
            setReferralCode(refFromUrl);
        }
        
        // Get referral code from cookie
        const referralCode = getReferralCode();
        
        if (!referralCode) {
            console.log('No referral code found');
            return;
        }
        
        console.log(`✅ Referral code detected: ${referralCode}`);
        
        // Inject into existing forms on page
        injectReferralIntoForms(referralCode);
        
        // Watch for HubSpot forms that load dynamically
        watchForHubSpotForms(referralCode);
        
        // Watch for any new forms added to DOM
        observeNewForms(referralCode);
        
        // Show referral banner (optional)
        if (referralCode) {
            showReferralBanner(referralCode);
        }
    }

    /**
     * Set referral code in cookie
     */
    function setReferralCode(code) {
        const expiryDays = config.cookieDuration || 30;
        const d = new Date();
        d.setTime(d.getTime() + (expiryDays * 24 * 60 * 60 * 1000));
        const expires = `expires=${d.toUTCString()}`;
        
        document.cookie = `${COOKIE_NAME}=${encodeURIComponent(code)}; ${expires}; path=/; SameSite=Lax`;
        console.log(`✅ Referral code saved to cookie: ${code}`);
    }

    /**
     * Get referral code from cookie
     */
    function getReferralCode() {
        // First check if it's in the page via PHP
        if (window.hsrReferralCode) {
            return window.hsrReferralCode;
        }
        
        // Then check cookie
        const cookies = document.cookie.split(';');
        
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === COOKIE_NAME) {
                return decodeURIComponent(value);
            }
        }
        
        return null;
    }

    /**
     * Inject referral code into existing forms
     */
    function injectReferralIntoForms(referralCode) {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            addReferralFieldToForm(form, referralCode);
        });
    }

    /**
     * Add hidden referral field to a specific form
     */
    function addReferralFieldToForm(form, referralCode) {
        const fieldName = config.referralParam || 'referral_source';
        
        // Check if field already exists
        const existingField = form.querySelector(`input[name="${fieldName}"]`);
        if (existingField) {
            existingField.value = referralCode;
            console.log('Updated existing referral field in form');
            return;
        }
        
        // Create hidden field
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = fieldName;
        hiddenField.value = referralCode;
        
        form.appendChild(hiddenField);
        console.log(`Added referral field to form: ${referralCode}`);
    }

    /**
     * Watch for HubSpot forms that load via their embed API
     */
    function watchForHubSpotForms(referralCode) {
        const fieldName = config.referralParam || 'referral_source';
        
        window.addEventListener('message', (event) => {
            if (event.data.type === 'hsFormCallback' && event.data.eventName === 'onFormReady') {
                const formId = event.data.id;
                console.log(`✅ HubSpot form ready: ${formId}`);
                
                // Use HubSpot's API to set the field value
                if (window.hbspt && window.hbspt.forms && window.hbspt.forms.getForm) {
                    try {
                        const form = window.hbspt.forms.getForm(formId);
                        if (form) {
                            form.setFieldValue(fieldName, referralCode);
                            console.log(`✅ Set ${fieldName} to: ${referralCode}`);
                        }
                    } catch (error) {
                        console.error('Error setting HubSpot field:', error);
                    }
                }
            }
        });
    }

    /**
     * Use MutationObserver to watch for forms added dynamically
     */
    function observeNewForms(referralCode) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType !== Node.ELEMENT_NODE) return;
                    
                    // Check if the added node is a form
                    if (node.tagName === 'FORM') {
                        addReferralFieldToForm(node, referralCode);
                    }
                    
                    // Check if the added node contains forms
                    if (node.querySelectorAll) {
                        const forms = node.querySelectorAll('form');
                        forms.forEach(form => {
                            addReferralFieldToForm(form, referralCode);
                        });
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('MutationObserver watching for new forms');
    }

    /**
     * Display referral info banner
     */
    function showReferralBanner(referralCode) {
        // Check if banner already exists
        if (document.getElementById('hsr-referral-banner')) {
            return;
        }
        
        const banner = document.createElement('div');
        banner.id = 'hsr-referral-banner';
        banner.style.cssText = `
            position: fixed;
            top: 70px;
            right: 20px;
            background: #333;
            color: #fff;
            padding: 8px 12px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 11px;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: background 0.2s;
        `;
        banner.innerHTML = `🔗 <strong>${referralCode}</strong>`;
        
        // Build referral URL
        const contactPage = config.contactPage || '/contact/';
        const referralParam = config.referralParam || 'referral_source';
        const siteUrl = config.siteUrl || window.location.origin;
        const referralUrl = `${siteUrl}${contactPage}?${referralParam}=${referralCode}`;
        
        // Click to copy full referral URL
        banner.addEventListener('click', () => {
            navigator.clipboard.writeText(referralUrl).then(() => {
                const originalBg = banner.style.background;
                banner.style.background = '#FFB03F';
                banner.innerHTML = `✓ <strong>Copied!</strong>`;
                
                setTimeout(() => {
                    banner.style.background = originalBg;
                    banner.innerHTML = `🔗 <strong>${referralCode}</strong>`;
                }, 1500);
            }).catch(() => {
                // Fallback for older browsers
                const tempInput = document.createElement('input');
                tempInput.value = referralUrl;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                const originalBg = banner.style.background;
                banner.style.background = '#FFB03F';
                banner.innerHTML = `✓ <strong>Copied!</strong>`;
                
                setTimeout(() => {
                    banner.style.background = originalBg;
                    banner.innerHTML = `🔗 <strong>${referralCode}</strong>`;
                }, 1500);
            });
        });
        
        document.body.appendChild(banner);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
