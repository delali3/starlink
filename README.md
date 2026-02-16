# GhLinks Subscription Platform

A comprehensive Laravel 12 subscription payment platform with daily (GHC 3) and monthly (GHC 60) payment options. Built with Laravel, Tailwind CSS, and integrated with Paystack/Hubtel payment gateways.

## 🚀 Features

### User Roles & Permissions
- **SuperAdmin**: Complete system control, revenue analytics, user/admin management
- **Admin**: User management, payment monitoring (no revenue access)
- **User**: OTP-based phone login, subscription management, payment history

### Payment Integration
- ✅ Paystack Gateway Integration
- ✅ Hubtel Gateway Integration
- ✅ Configurable via environment variables
- ✅ Secure webhook handling
- ✅ Queue-based payment verification
- ✅ Duplicate transaction prevention

### Subscription Management
- Daily subscription (GHC 3) - 1 day validity
- Monthly subscription (GHC 60) - 30 days validity
- Automatic subscription extension logic
- Scheduled subscription expiration
- Real-time status tracking

### Advanced Features
- 📱 Mobile-responsive design (Tailwind CSS)
- 🔐 OTP-based authentication for users
- 📊 Comprehensive analytics dashboard
- 📝 Complete audit logging
- 🔄 Automated subscription expiration
- 💳 Multiple payment provider support
- 🎨 Modern, clean UI

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- MySQL 5.7 or higher
- Node.js & npm (for asset compilation)
- XAMPP/WAMP (for local development)

## 🛠️ Installation Steps

### 1. Clone/Setup Project

```bash
cd c:\xampp\htdocs\GhProfit\ghlinks
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database

Edit `.env` file and configure your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ghlinks_subscription
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in MySQL:

```sql
CREATE DATABASE ghlinks_subscription;
```

### 5. Configure Payment Providers

Edit `.env` file:

```env
# Choose payment provider (paystack or hubtel)
PAYMENT_PROVIDER=paystack

# Paystack Configuration
PAYSTACK_PUBLIC_KEY=your_paystack_public_key
PAYSTACK_SECRET_KEY=your_paystack_secret_key
PAYSTACK_CALLBACK_URL=http://localhost/payment/callback

# Hubtel Configuration (if using Hubtel)
HUBTEL_CLIENT_ID=your_hubtel_client_id
HUBTEL_CLIENT_SECRET=your_hubtel_client_secret
HUBTEL_CALLBACK_URL=http://localhost/webhooks/hubtel

# Subscription Pricing
SUBSCRIPTION_DAILY_PRICE=3
SUBSCRIPTION_MONTHLY_PRICE=60
```

### 6. Run Migrations & Seeders

```bash
# Run migrations
php artisan migrate

# Seed database with default users
php artisan db:seed
```

### 7. Configure Queue Worker

Edit `.env`:

```env
QUEUE_CONNECTION=database
```

Run migrations for queue:

```bash
php artisan queue:table
php artisan migrate
```

### 8. Compile Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 9. Start Development Server

```bash
# Start Laravel server
php artisan serve

# Start queue worker (in a separate terminal)
php artisan queue:work

# Start scheduler (for subscription expiration)
php artisan schedule:work
```

## 👥 Default Login Credentials

### SuperAdmin
- **Email**: superadmin@ghlinks.com
- **Password**: password
- **URL**: http://localhost:8000/login

### Admin
- **Email**: admin@ghlinks.com
- **Password**: password
- **URL**: http://localhost:8000/login

### Test User (OTP Login)
- **Phone**: 0241234567
- **URL**: http://localhost:8000/login
- **Note**: OTP will be logged in `storage/logs/laravel.log` (development mode)

## 🔧 Configuration

### Payment Provider Setup

#### Paystack
1. Create account at https://paystack.com
2. Get API keys from Settings > API Keys
3. Set webhook URL: `https://yourdomain.com/webhooks/paystack`
4. Update `.env` with your keys

#### Hubtel
1. Create account at https://hubtel.com
2. Get Client ID and Secret from developer dashboard
3. Set callback URL: `https://yourdomain.com/webhooks/hubtel`
4. Update `.env` with your credentials

### SMS Provider Setup (Frog SMS API)

For OTP authentication via SMS:

1. Create account at Frog Networks
2. Get your API credentials
3. Update `.env`:

```env
FROG_SMS_API_KEY=your_api_key_here
FROG_SMS_USERNAME=your_username
FROG_SMS_SENDER_ID=GhProfit
```

**Note**: In development mode (`APP_ENV=local`), OTPs are logged to `storage/logs/laravel.log` instead of being sent via SMS.

### Queue Configuration

For production, use Redis or another robust queue driver:

```env
QUEUE_CONNECTION=redis
```

### Scheduler Configuration

Add to your server's cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📡 Webhook URLs

### Paystack Webhook
```
POST https://yourdomain.com/webhooks/paystack
```

### Hubtel Webhook
```
POST https://yourdomain.com/webhooks/hubtel
```

**Important**: Configure these URLs in your payment provider's dashboard.

## 🎯 Key Artisan Commands

```bash
# Expire subscriptions manually
php artisan subscriptions:expire

# Run queue worker
php artisan queue:work

# Run scheduler (development)
php artisan schedule:work

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📱 User Flows

### User Registration & Payment
1. Admin/SuperAdmin registers user with phone number
2. User logs in with phone number (receives OTP)
3. User chooses daily or monthly subscription
4. Redirected to payment gateway
5. Payment processed via webhook
6. Subscription activated automatically

### Subscription Extension
- If user pays before expiration → extends from current expiry
- If user pays after expiration → starts from current date
- Prevents overlapping subscriptions

## 🔒 Security Features

- ✅ CSRF protection on all forms
- ✅ Webhook signature verification
- ✅ User status checking middleware
- ✅ Role-based access control
- ✅ OTP expiration (10 minutes)
- ✅ Transaction idempotency
- ✅ Audit logging for all actions

## 📊 Database Structure

### Users Table
- id, name, phone (unique), email, password, status, timestamps

### Subscriptions Table
- id, user_id, type, amount, start_date, end_date, status, timestamps

### Payments Table
- id, user_id, subscription_id, reference, amount, payment_provider, status, transaction_id, metadata, paid_at, timestamps

### Audit Logs Table
- id, user_id, action, metadata, ip_address, user_agent, timestamps

## 🎨 Frontend Stack

- **Tailwind CSS** - Utility-first CSS framework
- **Alpine.js** - Lightweight JavaScript framework
- **Blade Templates** - Laravel's templating engine
- **Responsive Design** - Mobile-first approach

## 🚀 Deployment

### Production Checklist

1. ✅ Set `APP_ENV=production` in `.env`
2. ✅ Set `APP_DEBUG=false`
3. ✅ Configure proper database credentials
4. ✅ Set up SSL certificate (HTTPS)
5. ✅ Configure queue worker service
6. ✅ Set up cron for scheduler
7. ✅ Configure real SMS provider for OTP
8. ✅ Set correct webhook URLs
9. ✅ Run `php artisan config:cache`
10. ✅ Run `php artisan route:cache`
11. ✅ Run `php artisan view:cache`
12. ✅ Set proper file permissions

### Queue Worker Service (Supervisor)

Create `/etc/supervisor/conf.d/ghlinks-worker.conf`:

```ini
[program:ghlinks-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
```

## 📞 Support & Maintenance

### Logs Location
- Application logs: `storage/logs/laravel.log`
- Queue logs: Check supervisor logs
- Web server logs: Check Apache/Nginx logs

### Common Issues

**Issue**: OTP not received
- **Solution**: Check `storage/logs/laravel.log` (development) or configure SMS provider

**Issue**: Payment not verified
- **Solution**: Check queue worker is running, verify webhook signatures

**Issue**: Subscription not activated
- **Solution**: Check payment status, run `php artisan queue:work`

## 📝 License

This project is proprietary software. All rights reserved.

## 👨‍💻 Credits

Built with Laravel 12, Spatie Permission, and modern web technologies.

---

## 🎉 Quick Start for Development

```bash
# 1. Install dependencies
composer install && npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Configure database in .env
# DB_DATABASE=ghlinks_subscription

# 4. Run migrations & seed
php artisan migrate --seed

# 5. Compile assets
npm run dev

# 6. Start servers
php artisan serve
# In another terminal:
php artisan queue:work

# 7. Visit http://localhost:8000
```

**Login as SuperAdmin**: superadmin@ghlinks.com / password

---

For questions and support, contact the development team.
