# FarmHub Farmers System

FarmHub is a PHP and MySQL based farmers marketplace and post-harvest management system. It helps farmers record maize harvest batches, monitor post-harvest handling activities, submit products for sale, and connect their produce to buyers through an online marketplace. The system also gives administrators tools for managing farmers, products, orders, users, audit logs, and operational reports.

The project is designed to run locally on XAMPP, using PHP, MySQL, Bootstrap, jQuery, and the `shopping` database.

## Why This System Was Developed

Many smallholder farmers face challenges after harvest and during marketing. Produce may lose value because of poor drying, storage problems, mold, pest infestation, lack of quality records, and limited access to reliable buyers. At the same time, buyers often struggle to know which products are available, who produced them, and whether the products have passed basic handling and quality checks.

FarmHub was developed to reduce these gaps by combining three important needs in one system:

- Farmer record management, so farmer profiles, locations, farm sizes, and group membership can be stored in one place.
- Post-harvest tracking, so harvest batches, drying methods, storage records, moisture levels, quality checks, mold alerts, and aflatoxin readings can be monitored.
- Digital marketplace access, so farmers can submit products for sale and customers can browse, order, and track purchases.

The system supports better coordination between farmers, administrators, and customers. It also gives farmers a more organized way to present their products and gives administrators a clearer view of marketplace and post-harvest activity.

## Importance of the System

FarmHub is important because it supports both agricultural quality control and market access.

For farmers, the system helps them keep records of harvest batches, track drying and storage conditions, submit products for sale, view product approval status, and see customer orders linked to their products.

For administrators, it provides a central dashboard for managing farmers, reviewing marketplace activity, monitoring orders, checking revenue, viewing user activity, and following post-harvest indicators such as drying batches, quality tests, pest alerts, mold alerts, and aflatoxin readings.

For customers, it provides a shopping experience where they can view available products, add items to cart or wishlist, place orders, manage account details, and track order progress.

For agricultural organizations, cooperatives, and farmer groups, the system can improve accountability, reduce manual paperwork, support traceability, and help decision makers understand production and sales activity.

## Main Users

### Farmers

Farmers use the farmer portal to:

- Sign in to their account.
- View an overview of their batches, products, sales, and post-harvest status.
- Add harvest batches.
- View batch records.
- Record post-harvest stages, storage information, and quality logs.
- Submit products for admin approval.
- View submitted product status.
- View customer orders for their sold products.

### Administrators

Administrators use the admin panel to:

- Manage dashboard metrics and operations.
- Add, edit, and manage farmers.
- Manage customer users.
- Insert and manage products.
- Review orders by status.
- View today's orders, pending orders, and delivered orders.
- Track audit logs and user login activity.
- Manage marketplace operations.

### Customers

Customers use the public marketplace to:

- Register or log in.
- Browse products and categories.
- Search for products.
- View product details.
- Add products to cart or wishlist.
- Place orders.
- View order history.
- Track order status.
- Manage billing, shipping, and account details.

## Key Features

### Farmer Management

- Farmer profile records.
- Farmer number, username, name, location, phone, farm size, and group membership.
- Admin-controlled farmer creation and editing.
- Farmer-specific access control.

### Harvest Batch Management

- Add harvest batches.
- Record harvest date, quantity, initial moisture, remaining quantity, drying method, sorting quality score, and status.
- Predict drying duration based on selected drying method.
- View recent and historical batch records.

### Post-Harvest Tracking

- Record post-harvest stages such as drying.
- Track storage type, storage dates, moisture level, temperature, and pest infestation level.
- Log quality checks including mold presence, aflatoxin readings, and notes.
- Monitor active storage, pest alerts, mold alerts, and quality test counts.

### Farmer Marketplace

- Farmers submit products with name, description, unit, available quantity, price, and image.
- Submitted products remain pending until reviewed or published.
- Published products become available in the marketplace.
- Marketplace orders connect customers, products, and farmers.
- Farmer portal shows sold products and customer contact details.

### Customer Shopping

- Product browsing by category and subcategory.
- Product search.
- Product details page.
- Cart and wishlist.
- Checkout and payment method pages.
- Order history and tracking.

### Admin Dashboard

- Live operational dashboard.
- Product, user, farmer, revenue, order, pending order, drying batch, and login metrics.
- Recent orders table.
- Order trend and login activity charts.
- Farmer product mix.
- Audit log access.

### Security and Data Handling

- Session-based access control for admin and farmer areas.
- Role checks for protected pages.
- Reusable database helper functions for prepared queries.
- Flash messages for form feedback.
- Uploaded farmer product images stored under `uploads/farmer-products`.

## Technologies Used

- PHP
- MySQL / MariaDB
- XAMPP
- Bootstrap
- jQuery
- DataTables
- Flot charts
- HTML, CSS, and JavaScript

## Project Structure

```text
admin/                 Admin dashboard, order management, users, farmers, audit logs
farmers/               Farmer portal, batches, post-harvest, product selling
includes/              Shared configuration, helpers, headers, session security
sql/                   Additional SQL tables for farmer marketplace and post-harvest records
SQL file/              Main shopping database SQL file
uploads/               Uploaded farmer product images
assets/, css/, js/     Frontend styles, scripts, fonts, and images
index.php              Public homepage
login.php              Customer login
my-cart.php            Customer cart
order-history.php      Customer order history
track-order.php        Customer order tracking
```

## Database

The application uses the database name:

```text
shopping
```

The database connection is configured in:

```text
includes/config.php
admin/include/config.php
```

Default local database settings are:

```text
Host: localhost
User: root
Password: empty
Database: shopping
```

Important database files include:

- `SQL file/shopping.sql` for the original shopping tables such as admin, category, products, users, orders, reviews, and wishlist.
- `sql/farmer_marketplace_tables.sql` for farmer submitted products, published marketplace products, and marketplace orders.
- `sql/post_harvest_tables.sql` for post-harvest stages, storage records, and quality logs.
- `dbfile` includes a combined schema with shopping, farmer, batch, and post-harvest tables.

## Installation and Setup

1. Install XAMPP.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Copy or keep this project inside:

```text
C:\xampp\htdocs\maizehub-main
```

4. Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

5. Create a database named:

```text
shopping
```

6. Import the main SQL file:

```text
SQL file/shopping.sql
```

7. Import the extra farmer and post-harvest tables:

```text
sql/farmer_marketplace_tables.sql
sql/post_harvest_tables.sql
```

If your database already contains the combined schema from `dbfile`, confirm that the required tables exist before importing duplicate files.

8. Confirm the database credentials in:

```text
includes/config.php
admin/include/config.php
```

9. Visit the system in the browser:

```text
http://localhost/maizehub-main/
```

## How to Use the System

### Public Customer Side

Open:

```text
http://localhost/maizehub-main/
```

Customers can browse products, search products, view product details, register, log in, add items to cart or wishlist, place orders, and track their order history.

### Admin Side

Open:

```text
http://localhost/maizehub-main/admin/
```

After login, the admin can open the dashboard, manage users, manage farmers, insert products, view orders, update order status, and inspect audit logs.

Typical admin workflow:

1. Log in as admin.
2. Create or update farmer records.
3. Monitor dashboard metrics.
4. Review product and order activity.
5. Manage pending and delivered orders.
6. Use audit logs to review system access.

### Farmer Side

Open:

```text
http://localhost/maizehub-main/farmers/login.php
```

After login, a farmer can use the portal to manage production and sales records.

Typical farmer workflow:

1. Log in to the farmer portal.
2. Open the overview page to view batch, quality, product, and sales summaries.
3. Add a harvest batch with quantity, moisture level, drying method, and quality score.
4. Record post-harvest activity such as drying, storage, pest level, mold checks, and aflatoxin readings.
5. Submit products for sale with an image, quantity, unit, and price.
6. Wait for admin review or publishing.
7. View sold products and customer order details.

## Example Use Case

A maize farmer harvests 1,200 kg of maize. The farmer logs into FarmHub and records a new batch with the harvest date, initial moisture percentage, drying method, and quantity. During drying and storage, the farmer or admin records post-harvest stages, storage moisture, temperature, pest levels, and quality test results.

When the maize is ready for sale, the farmer submits a product listing with a photo, available quantity, and price per kilogram or bag. The administrator reviews the submission and publishes it to the marketplace. Customers can then find the product, place orders, and track their purchase. The farmer can later see sold product records and customer contact information from the farmer portal.

## Benefits

- Reduces manual paperwork for farmer and harvest records.
- Improves post-harvest monitoring and quality awareness.
- Helps identify mold, pest, moisture, and aflatoxin risks.
- Gives farmers a digital channel for selling produce.
- Helps administrators manage orders and farmer activity centrally.
- Supports traceability from farmer batch records to marketplace products.
- Makes customer ordering and tracking easier.

## Limitations

- The project is built for a local XAMPP environment and may require configuration before deployment to a live server.
- Some pages use older PHP/MySQL and frontend patterns.
- Online payment integration is not fully automated.
- Email, SMS, and mobile money integrations would require additional setup.
- Production deployment should include stronger security hardening, HTTPS, file upload validation, and environment-based configuration.

## Future Improvements

- Add mobile money payment integration.
- Add SMS or email notifications for orders and farmer approvals.
- Add stronger reporting for farmer groups and cooperatives.
- Add product approval screens with clearer admin review actions.
- Add stock reduction after successful orders.
- Add exportable reports for batches, sales, quality checks, and farmer performance.
- Add role-based permissions for different staff users.
- Improve deployment configuration using environment variables.

## Conclusion

FarmHub is a practical farmers system that connects post-harvest management with digital selling. It was developed to help farmers, administrators, and customers work through one organized platform. By recording harvest quality data and linking farmer products to a marketplace, the system supports better produce management, improved market access, and more transparent agricultural operations.
