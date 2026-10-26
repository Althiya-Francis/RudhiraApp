# RudhiraApp - Blood Donor Management System (college project)

Plain PHP + MySQL + HTML + CSS. No frameworks, no JavaScript.

## Files
| File | What it does |
|---|---|
| `database.sql` | Creates `rudhira_db`, the tables and the 416 donors from the PDF (safe to import twice) |
| `upgrade_existing_database.sql` | ONLY if you imported the OLD database.sql before: run once, then import database.sql |
| `config/database.php` | Connects to MySQL (host `localhost`, db `rudhira_db`, user `root`, empty password) |
| `includes/auth.php` | "Are you logged in?" check, included by every protected page |
| `includes/header.php`, `footer.php` | Shared menu and page ending |
| `index.php` | Sends visitors to login or dashboard |
| `register.php`, `login.php`, `logout.php` | Accounts (password_hash / password_verify) |
| `dashboard.php` | Totals and blood-group summary from the database |
| `find_donors.php` | MAIN FEATURE: search by name, blood group and place |
| `blood_requests.php`, `camps.php` | "Coming soon" demo pages |
| `css/style.css` | All styling |

## Run in XAMPP
1. Open the XAMPP Control Panel and **Start** both **Apache** and **MySQL**.
2. Copy the whole `RudhiraApp` folder to `C:\xampp\htdocs\` (so you have `C:\xampp\htdocs\RudhiraApp\index.php`).
3. Open http://localhost/phpmyadmin
4. Click the **Import** tab -> **Choose File** -> select `database.sql` -> **Go**.
   (If you imported the OLD version before: first open database `rudhira_db` -> **SQL** tab, paste the contents of
   `upgrade_existing_database.sql`, click **Go** once, then do the import above.)
5. Open **http://localhost/RudhiraApp/**
6. Click **Create an account**, register, log in. You land on the dashboard.

If your MySQL root user has a password, type it in `config/database.php` (`$db_pass`).

## How Find Donors works
1. The form on the page uses `method="get"`, so choices go into the address:
   `find_donors.php?name=fat&blood_group=O%2B&place=Kozhikode`
2. PHP reads them with `$_GET`.
3. Blood group is checked against the list of 8 allowed values; place is checked against the places in the database.
4. PHP starts with `SELECT ... FROM students WHERE 1 = 1` and adds `AND name LIKE ?`, `AND blood_group = ?`,
   `AND place = ?` only for the boxes the user filled in. The `?` values are sent separately (prepared statement),
   so typed text can never change the SQL.
5. MySQL returns only matching rows; PHP loops over them (`foreach`) and prints table rows.
   Every value is printed through `htmlspecialchars()`. If there are no rows, a "No donors found" message appears.
6. "Show all donors" is simply a link to `find_donors.php` with nothing in the address.

## How the dashboard counts
- `SELECT COUNT(*) FROM students` = total donors.
- `... WHERE donor_status = 'Available'` = available donors.
- `SELECT blood_group, COUNT(*) FROM students GROUP BY blood_group` = donors per blood group.

## Data notes
- Donors come from the scanned PDF (416 rows). `donor_status` is **not in the PDF**; every donor defaults to `Available`.
- Register/login: passwords are stored only as hashes. There is no built-in demo login; register your own account.
