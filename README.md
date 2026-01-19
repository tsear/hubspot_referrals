# HubSpot Referrals

A WordPress plugin for complete referral tracking with HubSpot CRM integration.

## Features

- **Referral Code Generation** - Create unique referral codes for partners/affiliates
- **HubSpot Integration** - All data stored in HubSpot CRM, not WordPress database
- **Conversion Tracking** - Track which referrals convert to leads
- **Admin Dashboard** - View stats, manage referrers, export CSV
- **Frontend Tracking** - Automatic cookie-based tracking and form injection
- **HubSpot Form Support** - Works with HubSpot embedded forms

## Requirements

- WordPress 6.0+
- PHP 8.0+
- HubSpot account with Private App access

## Installation

1. Upload the `hubspot_referrals` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress
3. Go to **Referrals → Settings**
4. Enter your HubSpot API key and Portal ID
5. Test the connection

## HubSpot Setup

Create these custom contact properties in HubSpot:

| Property | Type | Description |
|----------|------|-------------|
| `referral_code` | Single-line text | For referrers - their unique code |
| `referral_source` | Single-line text | For leads - the code that referred them |
| `referral_clicks` | Number | (Optional) Track link clicks |
| `last_referral_click` | Date | (Optional) Last click timestamp |

## How It Works

1. **Partner signs up** → Gets unique referral code (e.g., `johnsmith`)
2. **Partner shares link** → `yoursite.com/contact/?referral_source=johnsmith`
3. **Visitor clicks link** → Cookie saved for 30 days
4. **Visitor submits form** → `referral_source` field auto-populated
5. **HubSpot receives lead** → Associated with referrer via `referral_source`

## Configuration

### Settings

- **HubSpot API Key** - Private App token (starts with `pat-`)
- **Portal ID** - Your HubSpot portal number
- **Referral Parameter** - URL param name (default: `referral_source`)
- **Cookie Duration** - Days to remember referral (default: 30)
- **Contact Page** - Path for referral links (default: `/contact/`)

### Generating Referral Codes

**Via Admin Dashboard:**
1. Go to **Referrals** in WordPress admin
2. Fill out the generator form
3. Copy the generated link

**Via Frontend:**
Create a page with the referral signup form (shortcode coming soon).

## Hooks & Filters

```php
// Modify referral link format
add_filter('hsr_referral_link', function($link, $code) {
    return $link;
}, 10, 2);

// After referral code generated
add_action('hsr_code_generated', function($code, $contact_id) {
    // Send notification, etc.
}, 10, 2);
```

## Changelog

### 1.0.0
- Initial release
- HubSpot API integration
- Admin dashboard with stats
- Frontend cookie tracking
- Form field injection

## License

GPL v2 or later

## Author

Tyler Sear - [Smart Grant Solutions](https://smartgrantsolutions.com)