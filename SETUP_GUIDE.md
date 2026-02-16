# GhLinks Subscription Platform - Quick Setup Guide

## 🚀 Quick Start (5 Minutes)

### Prerequisites Checklist
- ✅ XAMPP installed and running
- ✅ Composer installed globally
- ✅ Node.js & npm installed
- ✅ Terminal/Command Prompt access

---

## Step-by-Step Installation

### 1. Navigate to Project Directory
```bash
cd c:\xampp\htdocs\GhProfit\ghlinks
```

### 2. Install Dependencies
```bash
# Install PHP dependencies (this may take 2-3 minutes)
composer install

# Install JavaScript dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database

**Open `.env` file and update:**
```env
DB_DATABASE=ghlinks_subscription
DB_USERNAME=root
DB_PASSWORD=
```

**Create Database in phpMyAdmin:**
1. Open http://localhost/phpmyadmin
2. Click "New" to create database
3. Name it: `ghlinks_subscription`
4. Click "Create"

### 5. Run Migrations
```bash
php artisan migrate --seed
```

Expected output:
```
Migration table created successfully.
Migrating: ... (several migrations)
Seeded database successfully!
```

### 6. Compile Assets
```bash
npm run dev
```

### 7. Start the Application

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Queue Worker:**
```bash
php artisan queue:work
```

**Terminal 3 - Scheduler (Optional for dev):**
```bash
php artisan schedule:work
```

---

## 🎉 You're Ready!

### Access the Application
- **Homepage**: http://localhost:8000
- **User Login**: http://localhost:8000/login
- **Admin Login**: http://localhost:8000/admin/login

### Default Login Credentials

**SuperAdmin:**
- Email: `superadmin@ghlinks.com`
- Password: `password`

**Admin:**
- Email: `admin@ghlinks.com`
- Password: `password`

**Test User (OTP):**
- Phone: `0241234567`
- OTP: Check `storage/logs/laravel.log`

---

## 🔧 Payment Gateway Setup

### For Testing (Paystack)

1. **Get Test Keys:**
   - Visit https://dashboard.paystack.com/#/settings/developers
   - Copy Public Key and Secret Key

2. **Update `.env`:**
```env
PAYMENT_PROVIDER=paystack
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxx
```

3. **Test Payment:**
   - Login as test user
   - Choose subscription plan
   - Use Paystack test card: `5060 6666 6666 6666 404`
   - Use any future expiry date and CVV `123`

---

## 📱 Mobile Testing

The application is mobile-responsive. Test on:
- Chrome DevTools (F12 > Toggle Device Toolbar)
- Real mobile device (use ngrok for external access)

---

## ⚡ Common Commands

```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Expire subscriptions manually
php artisan subscriptions:expire

# Restart queue worker
# (Ctrl+C to stop, then restart)
php artisan queue:work

# View logs
# Location: storage/logs/laravel.log
```

---

## 🐛 Troubleshooting

### Issue: "Class not found" errors
**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### Issue: Assets not loading
**Solution:**
```bash
npm run dev
# Or for production:
npm run build
```

### Issue: Database connection failed
**Solution:**
1. Ensure MySQL is running in XAMPP
2. Verify database name in `.env`
3. Check username/password

### Issue: OTP not appearing
**Solution:**
- Check `storage/logs/laravel.log`
- OTP is logged there in development mode

### Issue: Payment not processing
**Solution:**
1. Ensure queue worker is running
2. Check `storage/logs/laravel.log` for errors
3. Verify payment provider credentials

---

## 📊 Testing the Full Flow

### 1. Register a User
- Login as Admin
- Go to Users > Register New User
- Enter: Name, Phone (0245551234)

### 2. User Login
- Logout from admin
- Go to User Login
- Enter phone number
- Check logs for OTP
- Login with OTP

### 3. Make Payment
- Choose Daily or Monthly plan
- Complete payment (use test credentials)
- Watch payment pending page
- Verify subscription activated

### 4. Monitor as Admin
- Login as SuperAdmin
- View dashboard analytics
- Check payment logs
- Monitor subscriptions

---

## 🔒 Security Notes

**Before Production:**
1. Change all default passwords
2. Set `APP_ENV=production`
3. Set `APP_DEBUG=false`
4. Use real payment credentials
5. Configure Frog SMS API credentials
6. Enable HTTPS
7. Set up proper queue worker service
8. Configure cron for scheduler

---

## 📞 Need Help?

Check these files:
- `README.md` - Comprehensive documentation
- `storage/logs/laravel.log` - Application logs
- `.env.example` - Configuration reference

---

## ✅ Feature Checklist

Test these features:
- ✅ User OTP Login
- ✅ Admin Password Login
- ✅ SuperAdmin Dashboard
- ✅ User Registration
- ✅ Payment Processing (Paystack/Hubtel)
- ✅ Subscription Activation
- ✅ Payment History
- ✅ User Suspension/Activation
- ✅ Revenue Analytics
- ✅ Mobile Responsiveness

---

## 🎯 Next Steps

1. **Customize Branding:**
   - Update logo in views
   - Customize colors in `tailwind.config.js`

2. **Configure SMS (Frog SMS API):**
   - Get Frog SMS credentials
   - Update `.env` with `FROG_SMS_API_KEY`, `FROG_SMS_USERNAME`, `FROG_SMS_SENDER_ID`
   - OTPs will auto-send via Frog Networks in production

3. **Deploy to Production:**
   - Follow deployment section in README.md
   - Set up SSL certificate
   - Configure production database

---

**Happy Coding! 🚀**
