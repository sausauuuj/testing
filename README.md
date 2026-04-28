# Inventory Management System

A plain PHP, MySQL, jQuery, and Tailwind-based inventory management system with:

- Responsive dashboard and sidebar navigation
- PAR-safe bulk asset entry
- Accountable officer management
- AJAX-powered filters, charts, and reports
- Print-ready report output

## Run It in XAMPP

1. Create the database and tables by importing `database/schema.sql`.
2. Optionally import `database/seed.sql` for starter officers.
3. Review `config/database.php` and update credentials if your MySQL setup differs from XAMPP defaults.
4. Open `http://127.0.0.1/IMS%20(04-21-2026)/` in your browser.

## Main Files

- `index.php` - responsive UI shell
- `app/Services/AssetService.php` - bulk asset workflow, filtering, update, delete
- `app/Services/ParService.php` - PAR generation and reuse logic
- `api/` - AJAX endpoints
- `database/schema.sql` - MySQL schema
