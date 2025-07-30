# RESET Ticketing System - Local Testing Guide

## 🚧 Local Development Mode

This system automatically detects when you're running on a local development environment and **bypasses the Sampath Bank payment gateway** so you can test all functionality without external payment processing.

## 🔍 Local Environment Detection

The system considers you're in "local mode" when:
- Domain is `localhost`, `127.0.0.1`, or any `.local` domain
- Running on development ports `:8000`, `:3000`, `:8080`
- WordPress debug mode is enabled with `WP_ENV=development`

## 📋 Testing Checklist

### 1. Activate the Plugin
```
WordPress Admin > Plugins > RESET Plugin v.1.2-2 > Activate
```

### 2. Generate Test Tokens
```
WordPress Admin > RESET Dashboard > Token Management > Generate Master Tokens
```
Generate a few master tokens for testing.

### 3. Test the Complete Flow

#### Step 1: Token Entry
- Visit: `http://localhost/your-site/reset`
- Enter one of your generated master tokens
- You should see token validation success

#### Step 2: Booking Form
- Fill out your details (name, email, phone)
- Select a ticket type
- Submit the form

#### Step 3: Local Payment Bypass
- **You'll see**: "🚧 LOCAL MODE: Payment bypassed"
- **Instead of payment gateway**: Direct redirect to success page
- **URL will include**: `&local=1` parameter

#### Step 4: Success Page
- **Yellow banner**: "🚧 LOCAL DEVELOPMENT MODE - Payment Gateway Bypassed"
- **Real data displayed**: Your actual booking details
- **QR Code**: Generated automatically
- **invitation keys**: 5 real tokens created

#### Step 5: Email Testing
- **Check your email**: You should receive the HTML confirmation email
- **QR Code**: Embedded in the email
- **invitation keys**: Listed in the email

### 4. Test Admin Features

#### Dashboard
```
WordPress Admin > RESET Dashboard
```
- View sales statistics
- Monitor token usage
- Check system status

#### Token Management
```
WordPress Admin > Token Management
```
- Generate more tokens
- Cancel tokens
- View token status

#### Ticket Management
```
WordPress Admin > Ticket Management
```
- Add/edit ticket types
- Change prices
- Enable/disable tickets

#### Sales Reports
```
WordPress Admin > Sales Report
```
- View purchase history
- Export data
- Track revenue

## 🧪 What Gets Tested in Local Mode

✅ **Working in Local Mode:**
- Token validation
- Booking form processing
- Database operations
- QR code generation
- Email sending (HTML templates)
- Invitation token creation
- Admin dashboard functionality
- Success page display

❌ **Skipped in Local Mode:**
- Sampath Bank payment gateway
- Real payment processing
- External payment validation
- Live payment callbacks

## 🔧 Local Mode Features

### Visual Indicators
- Yellow banner on success page
- "LOCAL MODE" message in booking flow
- Console logs for debugging
- Special transaction IDs (LOCAL_DEV_*)

### Automatic Processing
- Payment status set to "completed"
- Tokens marked as used
- QR codes generated
- Emails sent immediately
- invitation keys created

## 📧 Email Testing

Make sure WordPress email is configured:

```php
// In wp-config.php for testing
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_USER', 'your-email');
define('SMTP_PASS', 'your-password');
```

Or use a local email testing tool like MailHog.

## 🐛 Debugging

### Check Debug Logs
WordPress debug log will show:
```
RESET Payments: Local development mode - bypassing payment gateway
RESET Payments: Simulating local payment for purchase ID: X
RESET Payments: Local mode - marking token as used...
RESET Payments: Local mode - sending confirmation email...
```

### Browser Console
In local mode, you'll see:
```
🚧 RESET Ticketing System - Local Development Mode
Payment gateway bypassed for local testing
Purchase data: {...}
```

### Database Check
Verify data in these tables:
- `wp_reset_tokens` - Token status should be "used"
- `wp_reset_purchases` - Payment status should be "completed"
- `wp_reset_email_logs` - Email sending logs

## 🚀 Ready for Production

When you deploy to production:
1. The system automatically detects the live environment
2. Payment gateway integration becomes active
3. Real Sampath Bank processing occurs
4. All local mode bypasses are disabled

## ✅ Local Testing Complete!

You can now test the entire RESET 2025 ticketing system locally without any external dependencies!

�� **Happy Testing!** 