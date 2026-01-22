# HubSpot Referrals

A complete WordPress referral management system with deep HubSpot CRM integration, customizable forms, partner self-service, and automated tracking.

## ✨ Features

### Core Functionality
- **🔗 Referral Code Generation** - Unique codes for partners/affiliates with auto-generation or custom options
- **🎯 HubSpot CRM Integration** - Full API v3/v4 support with Private App authentication
- **📊 Conversion Tracking** - Track clicks, conversions, and monthly performance stats
- **🎨 Form Builder** - Visual customization with live preview and 14+ styling options
- **📧 Flexible Email Delivery** - WordPress wp_mail, HubSpot Workflows API, or manual handling
- **👤 Partner Dashboard** - Self-service portal for partners to view their stats
- **📥 Bulk Import/Export** - CSV import for existing partners, export for reporting
- **🔔 Webhook Receiver** - Real-time conversion notifications from HubSpot
- **📝 Webhook Logs** - View and debug incoming webhook activity

### Admin Tools
- **Admin Dashboard** - Overview of all referrers with click/conversion stats
- **Form Builder** - Dedicated UI for customizing form appearance and behavior
- **Settings Panel** - HubSpot configuration, tracking options, email settings
- **Bulk Operations** - Import multiple partners via CSV, export current data
- **Webhook Logs** - Monitor incoming conversions and troubleshoot issues

### Frontend Features
- **Request Form Shortcode** - Beautiful, customizable referral signup form
- **Partner Dashboard Shortcode** - Let partners view their own performance
- **Cookie-Based Tracking** - 30-day (configurable) referral attribution
- **Custom Code Support** - Let partners choose their own referral codes

## 📋 Requirements

- WordPress 6.0+
- PHP 8.0+
- HubSpot account with Private App access (CRM API scopes required)

## 🚀 Installation

1. Upload the `hubspot_referrals` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Go to **Referrals → Settings**
4. Configure HubSpot API credentials (see HubSpot Setup below)
5. Go to **Referrals → Form Builder** to customize your form appearance

## 🔧 HubSpot Setup

### 1. Create a Private App

1. In HubSpot, go to **Settings → Integrations → Private Apps**
2. Click **Create private app**
3. Name it "WordPress Referrals" (or similar)
4. Under **Scopes**, enable:
   - `crm.objects.contacts.read`
   - `crm.objects.contacts.write`
   - `crm.schemas.contacts.read`
   - `crm.schemas.contacts.write`
5. Click **Create app** and copy the access token (starts with `pat-`)
6. Paste the token into **Referrals → Settings → HubSpot API Key**

### 2. Find Your Portal ID

1. In HubSpot, click your account name (top right)
2. Go to **Account & Billing**
3. Copy the Hub ID number
4. Paste into **Referrals → Settings → HubSpot Portal ID**

### 3. Create Custom Contact Properties

In HubSpot, go to **Settings → Data Management → Properties** and create these contact properties:

| Property Name | Internal Name | Type | Description |
|--------------|---------------|------|-------------|
| Referral Code | `referral_code` | Single-line text | The partner's unique referral code |
| Referral Source | `referral_source` | Single-line text | Which partner referred this lead |
| Click Count | `click_count` | Number | Total clicks on this partner's link |
| Conversion Count | `conversion_count` | Number | Total conversions generated |

### 4. Set Up Conversion Tracking (Optional)

To track conversions automatically:

1. In HubSpot, go to **Automation → Workflows**
2. Create a contact-based workflow
3. Enrollment trigger: Contact property "Lifecycle stage" is "Customer" (or your conversion criteria)
4. Filter: "Referral Source" is known
5. Add action: **Webhook**
   - Method: POST
   - URL: `https://yoursite.com/wp-json/hsr/v1/conversion`
   - Body: `{"email":"{{contact.email}}"}`
6. Activate the workflow

## 💡 How It Works

### Referral Flow

1. **Partner Requests Code** 
   - Submits form via `[hsr_request_code]` shortcode
   - Receives email with unique referral link
   - HubSpot contact created with `referral_code` property

2. **Partner Shares Link**
   - Example: `yoursite.com/contact/?referral_source=johnsmith`
   - Can share via email, social media, etc.

3. **Visitor Clicks Link**
   - Cookie saved for 30 days (configurable)
   - Tracks which partner referred them

4. **Visitor Converts**
   - Fills out your contact form (any form)
   - `referral_source` captured from cookie
   - HubSpot contact created/updated

5. **Conversion Tracked**
   - HubSpot workflow sends webhook to WordPress
   - Partner's `conversion_count` incremented
   - Monthly stats updated for reporting

## 📝 Shortcodes

### Request Code Form

Display a form for partners to request their referral link:

```
[hsr_request_code]
```

**Available Attributes:**

```
[hsr_request_code 
  title="Join Our Program"
  subtitle="Start earning rewards today"
  button_text="Get Started"
  button_color="#667eea"
  button_hover_color="#5568d3"
  accent_color="#764ba2"
  text_color="#2c3e50"
  border_color="#e0e0e0"
  background_color="#ffffff"
  form_width="700px"
  border_radius="12px"
  padding="40px"
  field_style="rounded"
  title_size="28px"
  title_weight="700"
  button_size="16px"
  button_weight="600"
  hide_custom_code="false"
  show_organization="true"
  show_info_box="true"
]
```

**Field Styles:**
- `rounded` - Rounded corners (default)
- `square` - Sharp corners
- `pill` - Fully rounded

### Partner Dashboard

Let partners view their own stats:

```
[hsr_partner_dashboard]
```

Partners log in with their email to see:
- Total clicks on their link
- Total conversions generated
- Their unique referral link
- One-click link copying

## 🎨 Form Customization

### Using the Form Builder (Recommended)

1. Go to **Referrals → Form Builder**
2. Customize in 6 organized sections:
   - **Colors** - Button, accent, text, border, background
   - **Layout** - Width, border radius, padding, field style
   - **Typography** - Title and button sizes/weights
   - **Fields** - Toggle organization and custom code fields
   - **Default Text** - Form title, subtitle, button text
   - **Live Preview** - See changes in real-time
3. Click **Save Changes**
4. Settings apply globally to all `[hsr_request_code]` shortcodes

### Per-Page Customization

Override global settings on specific pages using shortcode attributes:

```
[hsr_request_code button_color="#ff6b6b" form_width="500px"]
```

## ⚙️ Configuration

### Settings Panel

**HubSpot Configuration**
- API Key (Private App token)
- Portal ID
- Test connection button

**Tracking Settings**
- Referral parameter name (default: `referral_source`)
- Cookie duration in days (default: 30)
- Contact page path (default: `/contact/`)

**Email Settings**
- **WordPress (wp_mail)** - Uses your server's mail setup
- **HubSpot (Workflows)** - More reliable, requires Workflow ID
- **None (Manual)** - No automated emails, handle externally

**Additional Options**
- Enable/disable monthly stats emails for partners

### Bulk Import

Import existing partners via CSV:

1. Go to **Referrals → Bulk Import**
2. Download the sample CSV template
3. Add partner data (first_name, last_name, email, organization, custom_code)
4. Upload CSV file
5. Review results and error log

### Email Configuration

#### Option 1: WordPress (Simple)

Default method, uses `wp_mail()`. Requires your server to have working email (many shared hosts do).

#### Option 2: HubSpot Workflows (Recommended)

1. In HubSpot, create a contact-based workflow
2. Enrollment trigger: "Referral Code" is known
3. Filter: Re-enrollment OFF (one-time email)
4. Add **Send email** action with referral link details
5. Copy the Workflow ID (from URL)
6. Paste into **Settings → HubSpot Workflow ID**

#### Option 3: Manual/External

Use this if you handle emails separately (e.g., Zapier, Make.com).

## 📊 Admin Dashboard

View all referrers at **Referrals** in WordPress admin:

- **Partner Information** - Name, email, organization, referral code
- **Performance Stats** - Clicks, conversions, click-to-conversion rate
- **Actions** - Copy link, view in HubSpot
- **Filtering** - Search by name, email, or code
- **Export** - Download CSV of all data

## 🔍 Webhook Logs

Monitor incoming conversions at **Referrals → Webhook Logs**:

- Timestamp of each webhook
- Email address of converting lead
- Referral source credited
- Status (success/error)
- Helps troubleshoot tracking issues

## 🛠️ Development

### File Structure

```
hubspot_referrals/
├── hubspot-referrals.php        # Main plugin file
├── includes/
│   ├── class-hsr-admin.php      # Admin menu & dashboard
│   ├── class-hsr-ajax.php       # AJAX handlers
│   ├── class-hsr-api.php        # HubSpot API wrapper
│   ├── class-hsr-cron.php       # Scheduled tasks
│   ├── class-hsr-email.php      # Email delivery
│   ├── class-hsr-form-builder.php  # Form customization UI
│   ├── class-hsr-settings.php   # Settings page
│   ├── class-hsr-shortcodes.php # Shortcode handlers
│   ├── class-hsr-tracker.php    # Cookie tracking
│   └── class-hsr-webhook.php    # Webhook receiver
├── templates/
│   ├── public-form.php          # Request code form
│   └── partner-dashboard.php    # Partner stats view
└── assets/
    ├── css/
    │   ├── admin.css            # Admin styling
    │   └── public.css           # Frontend styling
    └── js/
        ├── admin.js             # Admin functionality
        └── referral-tracker.js  # Frontend tracking

```

### CSS Architecture

Form styling uses CSS custom properties for easy theming:

```css
--hsr-button-color
--hsr-button-hover-color
--hsr-accent-color
--hsr-text-color
--hsr-border-color
--hsr-background-color
--hsr-form-width
--hsr-border-radius
--hsr-padding
--hsr-field-radius
--hsr-title-size
--hsr-title-weight
--hsr-button-size
--hsr-button-weight
```

Override in your theme if needed:

```css
.hsr-form-wrapper {
  --hsr-button-color: #your-brand-color;
}
```

## 📅 Changelog

### 1.1.0 (Current)
- **New:** Complete Form Builder UI with live preview
- **New:** 14 CSS custom properties for comprehensive theming
- **New:** 20+ shortcode attributes for per-page customization
- **New:** WordPress Color Picker integration
- **New:** Organization field visibility toggle
- **New:** Custom code field visibility toggle
- **New:** Info box visibility toggle
- **Improved:** Separated form customization from main settings
- **Improved:** Template conditional rendering
- **Fixed:** Organization field now properly respects visibility setting
- **Fixed:** Duplicate method declarations
- **Fixed:** Static method callback syntax

### 1.0.0
- Initial release
- HubSpot API integration
- Admin dashboard with stats
- Frontend cookie tracking
- Partner dashboard shortcode
- Bulk CSV import/export
- Webhook receiver
- Email delivery (3 methods)
- Monthly stats cron

## 📄 License

GPL v2 or later

## 👤 Author

Tyler Sear - [Smart Grant Solutions](https://smartgrantsolutions.com)