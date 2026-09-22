# Cloud_POS

Cloud_POS is a PHP-based cloud point-of-sale (POS) and inventory management system designed to help businesses manage sales, products, stock, customers, suppliers, invoices, reports, and related operational workflows from a web interface.

## Overview

The application appears to provide:

- A browser-based POS interface for fast sales entry
- Inventory tracking across shops/branches
- Product and price history management
- Hold/resume invoices
- Search tools for inventory and sales-related records
- Reporting for stock, cheques, transfers, and other business activity
- Authentication and role-based access control

The project uses a traditional PHP application structure with separate folders for public pages, controllers, models, AJAX endpoints, assets, views, and includes.

## Features

### Point of Sale
- GUI-based POS screen for adding items to cart
- Invoice numbering and document sequence generation
- Hold and resume invoices
- Customer and salesman selection support
- Product lookup with inventory-aware filtering

### Inventory Management
- Track current stock, bill quantity, transfer in/out quantity, and returns
- View stock by product, shop, and price history
- Search inventory by barcode or item name
- Support for stock calculations and stock value reporting

### Reporting
- Inventory reports with stock sale and stock value calculations
- Cheque-related reporting for customers and suppliers
- Transfer-related and other operational summaries

### Administration and Security
- Session-based authentication
- Access checks for protected pages
- Shop-aware data filtering
- Shop login and per-shop access: each user's role is set per shop, and access to a shop can
  be revoked and restored (see `db/SHOP_ACCESS_MODULE.md`)
- Users, roles and passwords are managed by the system (super) admin only, enforced on the server
- User and role validation during login

## Project Structure

Common directories found in the repository:

- `Public/` — web pages and UI entry points
- `Controller/` — request handling and business logic entry points
- `Model/` — database and domain classes
- `AJAX/` — asynchronous endpoints used by the UI
- `View/` — shared UI components and partials
- `Includes/` — configuration, authentication checks, and shared bootstrap files
- `Assets/` — CSS, JavaScript, images, and third-party libraries

## Technologies Used

- PHP
- MySQL / MariaDB
- HTML, CSS, JavaScript
- Bootstrap
- jQuery
- CKEditor
- PHPMailer

## Requirements

To run the application locally, you will typically need:

- PHP 7.x or later
- MySQL or MariaDB
- A web server such as Apache or Nginx
- A browser for the frontend UI

Depending on your environment, you may also need:

- PHP PDO extension
- `mbstring`, `openssl`, `json`, and other common PHP extensions
- SMTP credentials if email features are enabled

## Installation

1. Clone or download the repository.
2. Place the project in your web server document root.
3. Create a MySQL database for the application.
4. Import the project database schema if available.
5. Update the database configuration in `Includes/config.php` or the relevant config file.
6. Ensure file permissions are correct for the web server.
7. Open the application in your browser.

## Configuration

Review the following areas before deploying:

- Database credentials
- Session and authentication settings
- Shop/company identifiers
- SMTP settings for email notifications
- Any local file paths used by uploads or generated documents

## Usage

Typical usage flow:

1. Log in with an authorized account.
2. Select the shop or branch and sign in to it with your username and password.
3. Use the POS interface to search items and add them to the cart.
4. Hold or complete invoices as needed.
5. Use the inventory and reporting screens to monitor stock and business activity.

## Tests

```
sh tools/get-phpunit.sh                                      # once: downloads PHPUnit 11
C:/xampp/php/php.exe tools/phpunit.phar                      # unit + integration (database sleepmakers_test)
C:/xampp/php/php.exe tests/e2e/shop_login_e2e.php [base-url] # end to end against a running local site
```

## Development Notes

- Many AJAX endpoints return JSON or rendered table rows for the frontend.
- The application relies heavily on session values such as `user_id` and `shop_id`.
- Data queries are shop-scoped in many places, so make sure shop context is set correctly.

## Third-Party Libraries

The repository includes vendor-style frontend and utility libraries such as:

- Bootstrap
- jQuery
- CKEditor
- PHPMailer
- ApexCharts-related assets

## Security Notes

Before production use, review the codebase for:

- SQL injection risks
- XSS handling in rendered HTML
- Session security and cookie flags
- Password hashing and login hardening
- CSRF protection on state-changing requests

## Contributing

If you plan to extend the project, consider contributing improvements in:

- Inventory and reporting accuracy
- UI/UX for the POS workflow
- Validation and security hardening
- Documentation and setup instructions

## License

No license file was found in the repository. Add one if you want to define usage and redistribution terms.

## Support

If you need help setting up the project, check the source files in `Includes/`, `Model/`, and `Public/` to understand the database, authentication, and UI flow.
