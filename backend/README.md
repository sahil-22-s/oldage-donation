# ElderCare Backend - PHP with PostgreSQL

This is the backend API for the ElderCare Home website, built with PHP and PostgreSQL.

## Prerequisites

- PHP 7.4 or higher
- PostgreSQL 12 or higher
- PHP PDO extension for PostgreSQL
- Apache or Nginx web server

## Installation & Setup

### 1. Install PostgreSQL

**Windows:**
- Download from https://www.postgresql.org/download/windows/
- Run the installer and note your password for the `postgres` user
- Default port is 5432

**Mac (with Homebrew):**
```bash
brew install postgresql
brew services start postgresql
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install postgresql postgresql-contrib
sudo service postgresql start
```

### 2. Create Database

1. Open PostgreSQL command line (psql)
   - Windows: Use pgAdmin or pgAdmin CLI
   - Mac/Linux: `psql -U postgres`

2. Create the database:
```sql
CREATE DATABASE eldercare;
```

3. Switch to the database:
```sql
\c eldercare
```

4. Run the database schema file (database.sql):

**Windows (in pgAdmin):**
- Right-click on `eldercare` database
- Select "Query Tool"
- Copy-paste the content from `database.sql`
- Execute

**Or from command line:**
```bash
psql -U postgres -d eldercare -f database.sql
```

### 3. Configure Database Connection

Edit `backend/config.php` and update:
```php
define('DB_PASSWORD', 'your_password_here'); // Your PostgreSQL password
```

If using different database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'eldercare');
define('DB_USER', 'postgres');
define('DB_PASSWORD', 'your_password');
```

### 4. Setup Web Server

**Using PHP Built-in Server (Development only):**
```bash
cd backend
php -S localhost:8000
```

**Using Apache:**
1. Copy the backend folder to Apache's `htdocs` directory
2. Enable `mod_rewrite` in Apache
3. Point your virtual host to the backend directory

**Using Nginx:**
Configure nginx to route requests to the backend PHP files.

## API Endpoints

### Base URL
```
http://localhost:8000/api.php
```

### Donations

**Get all donations:**
```
GET /api.php?action=donations
```

### UPI Payment Routes
These endpoints support the QR generation, confirmation and receipt retrieval flow described in the main README.

**Generate QR for a fixed amount**
```
POST /api.php?action=generate_upi
Content-Type: application/json

{
  "amount": 100.50,
  "donor_name": "Optional Name",
  "email": "optional@example.com",
  "phone": "9876543210"
}
```
Returns `payment_id`, `upi_uri`, and `qr_url`.

**Confirm payment** (called after donor completes UPI payment and provides transaction ID)
```
POST /api.php?action=confirm_payment
Content-Type: application/json

{
  "payment_id": 1,
  "transaction_id": "TXN123456",
  "payer_vpa": "donor@upi",
  "payer_name": "Donor Name"
}
```
Response includes `receipt_url` and `pdf_url` for the generated receipts.

**Check status** (useful for polling)
```
GET /api.php?action=payment_status&payment_id=1
```

**Fetch receipt**
```
GET /api.php?action=get_receipt&payment_id=1[&format=pdf]
```


**Example Donate JSON**

**Create new donation:**
```
POST /api.php?action=donations
Content-Type: application/json

{
  "donor_name": "John Doe",
  "email": "john@example.com",
  "phone": "+91 9876543210",
  "address": "123 Main St",
  "item_name": "Wheelchairs",
  "quantity": 2,
  "payment_method": "upi"
}
```

### Visits

**Get all visits:**
```
GET /api.php?action=visits
```

**Create new visit booking:**
```
POST /api.php?action=visits
Content-Type: application/json

{
  "visitor_name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "+91 9876543210",
  "visit_date": "2026-03-15",
  "visit_time": "14:30",
  "message": "Looking forward to visit"
}
```

### Inventory

**Get all inventory items:**
```
GET /api.php?action=inventory
```

**Add new inventory item:**
```
POST /api.php?action=inventory
Content-Type: application/json

{
  "name": "Wheelchairs",
  "description": "Comfortable mobility wheelchairs",
  "stock_quantity": 10,
  "image_url": "https://..."
}
```

**Update inventory item:**
```
PUT /api.php?action=inventory
Content-Type: application/json

{
  "id": 1,
  "name": "Wheelchairs",
  "description": "Updated description",
  "stock_quantity": 15,
  "image_url": "https://..."
}
```

**Delete inventory item:**
```
DELETE /api.php?action=inventory
Content-Type: application/json

{
  "id": 1
}
```

### Admin Authentication

**Admin Login:**
```
POST /admin.php?action=login
Content-Type: application/json

{
  "username": "admin",
  "password": "1234"
}
```

**Check Session:**
```
GET /admin.php?action=check-session
```

**Logout:**
```
POST /admin.php?action=logout
```

**Get Dashboard Stats:**
```
GET /admin.php?action=dashboard-stats
```

## Default Admin Credentials

- Username: `admin`
- Password: `1234`

**Change this immediately in production!**

To change admin password, update in PostgreSQL:
```sql
UPDATE admins SET password = crypt('new_password', gen_salt('bf')) 
WHERE username = 'admin';
```

## Database Schema

### Tables

**donations**
- id (Primary Key)
- donor_name, email, phone, address
- item_name, quantity
- payment_method
- status
- donation_date, created_at

**visits**
- id (Primary Key)
- visitor_name, email, phone
- visit_date, visit_time
- message
- status
- created_at

**inventory**
- id (Primary Key)
- name, description
- stock_quantity
- image_url
- created_at, updated_at

**admins**
- id (Primary Key)
- username, email
- password (hashed)
- created_at

**users**
- id (Primary Key)
- name, email, phone, address
- created_at

## Frontend Integration

Update your JavaScript fetch calls to use these endpoints:

```javascript
// Example: Donate
fetch('http://localhost:8000/api.php?action=donations', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    donor_name: 'John Doe',
    email: 'john@example.com',
    // ... other fields
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

## Troubleshooting

### Connection Error
- Ensure PostgreSQL is running
- Check DB_HOST, DB_PORT, and DB_NAME in config.php
- Verify PostgreSQL password is correct

### Permission Denied
- Check file permissions for backend folder
- Ensure PHP has write access to session files

### Database Already Exists
- Drop and recreate: `DROP DATABASE eldercare; CREATE DATABASE eldercare;`

## Security Notes

1. Change default admin password immediately
2. Use environment variables for sensitive data in production
3. Implement HTTPS only
4. Add input validation and sanitization
5. Use prepared statements (already implemented)
6. Add rate limiting for API endpoints
7. Implement proper authentication tokens

## Support

For issues or questions, refer to:
- PHP PDO Documentation: https://www.php.net/manual/en/book.pdo.php
- PostgreSQL Documentation: https://www.postgresql.org/docs/
