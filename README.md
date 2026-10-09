# GYUNDA HOUSE

A small, responsive rental-property manager built with PHP and MySQL/MariaDB. It includes a session-protected manager dashboard, tenant and room directories, rent-payment records, a one-time administrator setup page, and an English/Swahili language switcher throughout the manager experience.

## Requirements

- PHP 8.1 or later with `pdo_mysql` and `mbstring` enabled.
- MySQL 8+ or a compatible MariaDB version.
- Apache with PHP enabled (for example, WAMP).
- HTTPS for any deployment outside a trusted local development machine.

## Project layout

```text
config/
  app.php                 Database and setup settings, read from environment variables
database/
  schema.sql              Initial database schema and database creation
  migrations/
    remove_tenant_invite_expiry.sql  Remove 24-hour expiry from existing invitation records
public/
  .htaccess               Disable directory listings
  index.php               Sign-in page
  register.php            One-time first-manager registration
  overview.php            Public visual walkthrough of the system
  dashboard.php           Authenticated dashboard and management forms
  setup.php               One-time, setup-key-protected admin provisioning
  logout.php              CSRF-protected sign out
  api.php                 Authenticated JSON API
  assets/
    app.css               Responsive interface and motion
    app.js                Dashboard, forms, API calls and table rendering
    login.js              Sign-in password visibility control
    overview.js           Scroll-triggered animations for the overview tour
  user/
    loginpage.php         Tenant login
    registerpage.php      Invitation-only tenant signup
    dashboardpage.php     Tenant dashboard, profile, contract, rules and payments
    api.php               Tenant-session-only portal API
    logout.php            Tenant sign out
    assets/
      portal.css          Responsive Swahili tenant portal and theme styles
      portal.js           Portal data, theme, signature pad and PDF download
src/
  .htaccess               Deny direct web access to PHP internals
  Auth.php                Password verification and session authentication
  Database.php            PDO connection
  bootstrap.php           Session, security headers and shared setup
  helpers.php             HTML, CSRF and JSON helpers
```

## WAMP setup

1. Start Apache and MySQL from the WAMP control panel. Confirm PHP 8.1+ is selected and `pdo_mysql` and `mbstring` are enabled.
2. In phpMyAdmin, import [`database/schema.sql`](database/schema.sql). It creates the `gyunda_house` database and all tables.
3. Configure the environment variables in the Apache virtual host (or Apache configuration outside the web root). Example:

   ```apache
   SetEnv GYUNDA_DB_HOST 127.0.0.1
   SetEnv GYUNDA_DB_PORT 3306
   SetEnv GYUNDA_DB_NAME gyunda_house
   SetEnv GYUNDA_DB_USER root
   SetEnv GYUNDA_DB_PASSWORD ""
   SetEnv GYUNDA_SETUP_KEY replace-this-with-a-random-secret-at-least-32-characters-long
   ```

   Change the database account/password to a dedicated MySQL user in non-local environments. Generate a setup secret with `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Do not commit secrets into this project.
4. Prefer serving **only** the `public` directory. For WAMP, configure a virtual host with `DocumentRoot "C:/wamp64/www/Wapangajisystem/public"` and an allowed `<Directory "C:/wamp64/www/Wapangajisystem/public">` block. Restart Apache after changing its configuration. The `.htaccess` files also deny direct access to `config`, `database`, and `src` when Apache `AllowOverride` is enabled.
5. Visit `http://localhost/register.php` on that virtual host (or use **Register your manager account** on the sign-in page). Create the first manager username and password (12–72 characters). Registration is serialized and permanently closes after the first account is created. Only expose the system to people you trust until this first manager registers; do not publish the unregistered installation to the internet. Alternatively, use the extra setup-key-protected `setup.php` page.
6. Remove `GYUNDA_SETUP_KEY` from the Apache environment after setup, then visit `http://localhost/` to sign in. Add rooms first, then tenants, and record rent payments from the dashboard.
7. On an existing installation, apply `database/schema.sql` to add the tenant portal tables; it preserves existing manager, tenant, room and payment rows. If the installation already has the 24-hour invite schema, back up the database and apply `database/migrations/remove_tenant_invite_expiry.sql` once before deploying the updated application code. The migration marks already-expired invitations used before removing the expiry field; unused invitations that had not expired remain usable.
8. In the manager dashboard, open **Tenants** and use the invitation icon beside an active tenant. The registration link and a single-use code are copied for private sharing. The code does not expire and remains valid until used. The tenant opens `/user/registerpage.php`, redeems the code, and then signs in at `/user/loginpage.php`.

The manager sign-in, registration/setup pages, dashboard, and management views support English and Swahili. Use the language button in the page header to switch; the selection is saved in that browser.

If not using a virtual host, the default WAMP URL is typically `http://localhost/Wapangajisystem/public/`. Keep the sensitive project directories inaccessible from the public web and use HTTPS for a remotely reachable installation. If Apache does not load variables set at the Windows user level, define them with `SetEnv` in the virtual-host configuration and restart Apache.

## Data and dashboard calculations

- Rent and payment amounts use `DECIMAL(12,2)`; recorded partial payments retain the exact amount entered.
- Each payment records its actual payment date, method, optional transaction reference, and the first day of the rent month it covers.
- “All-time collections” is the sum of recorded payments. The smaller caption shows payments applied to the current month.
- “Pending” is the sum of each active tenant’s monthly rent minus payments applied to the current month, floored at zero per tenant. This is an estimate based on the manager-entered monthly rent; advance payments are applied only to the month selected on the payment.
- Vacating a tenant preserves their payment history and releases their room. A room with an active tenant cannot be removed.

## JSON API

All routes are served by `public/api.php`; every route requires a signed-in session. Mutating requests must include the session CSRF token in the `X-CSRF-Token` header and send JSON.

| Method | Query | Purpose |
|---|---|---|
| `GET` | `?resource=dashboard` | Summary metrics and current-month totals |
| `GET` | `?resource=tenants` | Active tenants and current-month paid amount |
| `GET` | `?resource=rooms` | Rooms, occupancy and assigned tenants |
| `GET` | `?resource=payments` | Payment history |
| `POST` | `?resource=rooms` | Create a room |
| `POST` | `?resource=tenants` | Create a tenant and assign an available room |
| `POST` | `?resource=payments` | Record a rent payment |
| `POST` | `?resource=tenants&id={id}&action=vacate` | Mark a tenant as moved out |
| `PATCH` | `?resource=tenants&id={id}` | Update an active tenant’s monthly rent |
| `DELETE` | `?resource=rooms&id={id}` | Remove a vacant room |
| `POST` | `?resource=tenant-invites` | Issue a non-expiring, single-use portal invitation for an active tenant |

Errors are returned as JSON with an appropriate HTTP status. The API uses prepared statements, validates all user input, enforces room occupancy while holding a database lock, and does not expose database errors to clients.

## Tenant portal

The tenant portal is Swahili-first and uses a separate tenant session, API and authorization guard from the manager dashboard. Tenants cannot self-select another tenant record: registration requires a landlord-issued, single-use invitation code. Codes do not expire and stay valid until redeemed; issuing another code does not invalidate an earlier unused code. Portal accounts are limited to active tenants; usernames and email addresses are unique, passwords are hashed, login failures are temporarily locked, and state-changing API requests require the session CSRF token. A newly registered tenant session discards any stale manager identity before setting the tenant role. Each tenant can submit only one signed contract.

After signing in, tenants can view their contract/payment summary, update their name and phone number, upload a profile photo (JPG/PNG/WebP, maximum 1 MB), complete and draw a digital signature on the tenancy agreement, and download the signed agreement as a PDF. The contract signature and tenant-specific payment history are saved against the authenticated tenant. House rules have a Swahili/English switch; the portal theme toggle persists light/dark mode in the browser. The footer links to support at `0787982159` and WhatsApp at `https://wa.me/255787982159`.

Tenant endpoints are served from `public/user/api.php`; all require a tenant session. Available read resources are `dashboard`, `profile`, `contract` and `payments`. `POST profile` updates account details/photo, and `POST contract` saves the tenant's signed agreement. Do not expose the project root as the document root; serve only `public`.
