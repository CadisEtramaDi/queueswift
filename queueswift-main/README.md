# QueueSwift — Setup & Deployment Guide

## 🚀 Local Setup (XAMPP)

### Step 1: Install XAMPP
1. Download from https://www.apachefriends.org
2. Install and launch XAMPP Control Panel
3. Start **Apache** and **MySQL** services

### Step 2: Copy Project Files
1. Navigate to: `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
2. Copy the entire `queueswift` folder here
3. Your path should be: `htdocs/queueswift/`

### Step 3: Set Up the Database
1. Open your browser → go to http://localhost/phpmyadmin
2. Click **"New"** in the left sidebar
3. Name it `queueswift` and click **Create**
4. Click **Import** tab → Choose file → select `queueswift/database.sql`
5. Click **Go** to import

### Step 4: Launch
- **Customer View:** http://localhost/queueswift/index.html
- **Admin Dashboard:** http://localhost/queueswift/admin/index.html

---

## 🌐 Free Hosting Options

### Option A: InfinityFree (Recommended — Free PHP + MySQL)
1. Sign up at https://infinityfree.com (free)
2. Create a new account/subdomain (e.g., `queueswift.rf.gd`)
3. Go to **Control Panel** → **MySQL Databases**
   - Create database, note the host/user/password
4. Update `api/config.php` with the provided credentials:
   ```php
   define('DB_HOST', 'sql_host_from_infinityfree');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'your_db_name');
   ```
5. Open **phpMyAdmin** in InfinityFree and import `database.sql`
6. Upload all files via **File Manager** or FTP (use FileZilla)
7. Your site is live!

### Option B: 000webhost (Free)
1. Sign up at https://www.000webhost.com
2. Create a website → go to **Manage Website**
3. In **File Manager** → upload your files to `public_html/`
4. Create MySQL DB in **MySQL Databases** section
5. Import `database.sql` via phpMyAdmin
6. Update `api/config.php` credentials

### Option C: Hostinger (Free 1-month trial)
- Similar to above with better performance
- https://www.hostinger.com

### Option D: Railway (Free tier — Docker-based)
1. Sign up at https://railway.app
2. Add a MySQL service
3. Deploy PHP app using the built-in PHP + Apache buildpack
4. Set environment variables for DB credentials

---

## 📁 Project Structure
```
queueswift/
├── index.html              ← Customer-facing queue app
├── database.sql            ← Database setup script
├── api/
│   ├── config.php          ← Database configuration
│   ├── businesses.php      ← Business listing API
│   └── queue.php           ← Queue join/status/manage API
└── admin/
    ├── index.html          ← Staff dashboard
    └── admin_update.php    ← Update queue status API
```

---

## 🛠️ Configuration

### Adding More Businesses
In phpMyAdmin, run:
```sql
USE queueswift;
INSERT INTO businesses (name, category, description, address, phone, avg_service_minutes, max_queue)
VALUES ('Your Business Name', 'salon', 'Description here', 'Full Address', '+63 912 345 6789', 30, 40);
```
Categories: `office`, `salon`, `pet_grooming`, `clinic`

### Adding Services to a Business
```sql
INSERT INTO business_services (business_id, service_name, duration_minutes)
VALUES (1, 'New Service', 20);
```

---

## 🔑 How It Works
1. **Customer** visits the site, picks a business, fills the form → gets a unique 8-character token
2. **Customer** can check their position & wait time anytime using their token
3. **Staff** opens Admin dashboard → selects their business → clicks "Call Next" to serve customers
4. Queue updates in real-time (auto-refreshes every 8 seconds)

---

## ⚙️ Customization Tips
- **Change avg_service_minutes** per business to improve wait time accuracy
- **Change max_queue** to limit daily queue capacity
- Add `open_time`/`close_time` validation in `api/queue.php` for business hours enforcement
- Optionally integrate SMS notifications using a free tier of Vonage or Twilio

---

## 🐛 Troubleshooting
| Issue | Solution |
|-------|----------|
| Blank page on index.html | Make sure XAMPP Apache is running |
| "Database connection failed" | Check `api/config.php` credentials |
| Businesses not loading | Verify database is imported correctly |
| Admin shows no businesses | Re-import `database.sql` |
| CORS errors | Make sure you're accessing via `localhost`, not `file://` |

---

*Built with PHP 8+, MySQL, Vanilla JS — No frameworks required.*
