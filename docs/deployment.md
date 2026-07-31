# Deployment

## Environments

- Local: Windows/XAMPP.
- QA and production: Ubuntu EC2, Apache, PHP 8.3 and PostgreSQL 16.
- Apache document root must point only to `<project>/public`.
- Each environment has separate database, base URL, sessions, provider credentials, AWS resources and secrets.

## HTTPS and secure-request policy

CodeIgniter's built-in `ForceHTTPS` filter is registered globally in `app/Config/Filters.php`. Do not call `forceHTTPS()` from individual controllers and do not create a duplicate custom HTTPS filter.

`app/Config/App.php` keeps the repository-safe default:

```php
public bool $forceGlobalSecureRequests = false;
```

Each environment overrides that value through its own uncommitted `.env` file.

### Environment matrix

| Environment | Base URL | Force HTTPS | Secure cookies |
|---|---|---:|---:|
| Local | `http://sikhanandkaraj.local/` | `false` | `false` |
| QA | QA HTTPS hostname | `true` | `true` |
| Production | Canonical production HTTPS hostname | `true` | `true` |

### Local Windows/XAMPP

The local virtual host currently uses HTTP. Keep HTTPS enforcement and secure-only cookies disabled:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://sikhanandkaraj.local/'
app.indexPage = ''
app.forceGlobalSecureRequests = false

cookie.secure = false
cookie.httponly = true
cookie.samesite = 'Lax'
```

This prevents redirects to a local HTTPS endpoint that has not been configured.

### QA Ubuntu/Apache

QA is deployed under:

```text
/var/www/sikhanandkaraj-qa
```

Use the real QA hostname in `/var/www/sikhanandkaraj-qa/.env`:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://qa.sikhanandkaraj.com/'
app.indexPage = ''
app.forceGlobalSecureRequests = true

cookie.secure = true
cookie.httponly = true
cookie.samesite = 'Lax'

session.cookieName = sak_qa_session
```

Use a QA-specific session cookie name so QA and production sessions cannot collide when they share a parent domain.

### Production Ubuntu/Apache

Production is deployed under:

```text
/var/www/sikhanandkaraj-webapp
```

Use the selected canonical hostname in `/var/www/sikhanandkaraj-webapp/.env`:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://www.sikhanandkaraj.com/'
app.indexPage = ''
app.forceGlobalSecureRequests = true

cookie.secure = true
cookie.httponly = true
cookie.samesite = 'Lax'

session.cookieName = sak_session
```

Choose either the `www` or non-`www` hostname as canonical and use it consistently in DNS, Apache redirects, certificates and `app.baseURL`.

### Apache redirect and SSL virtual hosts

Apache should perform the primary HTTP-to-HTTPS redirect before PHP executes. CodeIgniter's `ForceHTTPS` filter remains the application-level safeguard.

QA port 80 example:

```apache
<VirtualHost *:80>
    ServerName qa.sikhanandkaraj.com

    RewriteEngine On
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>
```

QA SSL virtual host example:

```apache
<VirtualHost *:443>
    ServerName qa.sikhanandkaraj.com

    DocumentRoot /var/www/sikhanandkaraj-qa/public

    <Directory /var/www/sikhanandkaraj-qa/public>
        AllowOverride All
        Options -Indexes
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/qa.sikhanandkaraj.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/qa.sikhanandkaraj.com/privkey.pem

    ErrorLog ${APACHE_LOG_DIR}/sikhanandkaraj-qa-error.log
    CustomLog ${APACHE_LOG_DIR}/sikhanandkaraj-qa-access.log combined
</VirtualHost>
```

Production port 80 example with `www` as canonical:

```apache
<VirtualHost *:80>
    ServerName sikhanandkaraj.com
    ServerAlias www.sikhanandkaraj.com

    RewriteEngine On
    RewriteRule ^ https://www.sikhanandkaraj.com%{REQUEST_URI} [R=301,L]
</VirtualHost>
```

Production SSL virtual host example:

```apache
<VirtualHost *:443>
    ServerName www.sikhanandkaraj.com
    ServerAlias sikhanandkaraj.com

    DocumentRoot /var/www/sikhanandkaraj-webapp/public

    <Directory /var/www/sikhanandkaraj-webapp/public>
        AllowOverride All
        Options -Indexes
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/www.sikhanandkaraj.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/www.sikhanandkaraj.com/privkey.pem

    ErrorLog ${APACHE_LOG_DIR}/sikhanandkaraj-error.log
    CustomLog ${APACHE_LOG_DIR}/sikhanandkaraj-access.log combined
</VirtualHost>
```

Enable required modules, validate Apache and reload:

```bash
sudo a2enmod rewrite
sudo a2enmod ssl
sudo apachectl configtest
sudo systemctl reload apache2
```

Do not reload Apache unless `apachectl configtest` reports `Syntax OK`.

### Reverse proxies and forwarded HTTPS

Keep `Config\App::$proxyIPs` empty when Apache directly terminates browser HTTPS.

When HTTPS terminates at a trusted load balancer or reverse proxy, configure only the real private proxy address or subnet. Never trust `0.0.0.0/0`. The proxy must forward the original scheme, normally through:

```text
X-Forwarded-Proto: https
```

Incorrect proxy trust or missing forwarded-protocol information can cause an HTTPS redirect loop.

### HSTS caution

Enable long-lived HSTS only after HTTPS is stable, certificate renewal is automated and every required production subdomain supports HTTPS. Do not apply production HSTS assumptions to the local `.local` environment.

### HTTPS verification

Local must remain on HTTP:

```text
http://sikhanandkaraj.local/
```

Verify QA redirect and final response:

```bash
curl -I http://qa.sikhanandkaraj.com/
curl -I https://qa.sikhanandkaraj.com/
curl -IL --max-redirs 5 http://qa.sikhanandkaraj.com/
```

Verify production canonical redirect:

```bash
curl -IL --max-redirs 5 http://sikhanandkaraj.com/
```

Expected behavior:

- HTTP returns a `301` redirect to HTTPS.
- HTTPS returns a valid application response or intentional application redirect.
- The redirect chain does not loop.
- Session and CSRF cookies contain the `Secure` and `HttpOnly` attributes in QA and production.

## Production route mode

Production currently supports prelaunch routing: public home, login and registration entry points redirect to the prelaunch profile flow. Confirm this mode before every release; launch requires an explicit route/configuration decision rather than accidental environment behavior.

## Release order

```text
Reviewed commit
  → backup and prerequisites
  → composer install --no-dev --optimize-autoloader
  → apply pending SQL in order
  → deploy files
  → verify writable permissions and cron
  → verify HTTPS, canonical redirects and secure cookies
  → smoke-test public, member, admin, prelaunch, SMS and media paths
```

## Mandatory smoke tests

- HTTP redirects once to the canonical HTTPS hostname;
- HTTPS has no redirect loop;
- QA and production cookies are `Secure` and `HttpOnly`;
- local HTTP remains usable without forced HTTPS;
- mobile-only registration and OTP activation;
- password and OTP login choices;
- password recovery through verified mobile;
- profile sections and completion calculation;
- private-media upload and signed delivery;
- admin role restrictions and photo/profile review;
- prelaunch form, field-officer verification and optional email;
- no-cache headers on sensitive pages;
- PostgreSQL/session/provider connectivity.

Never deploy secrets, CloudFront private keys or AWS access keys from source control. Prefer instance roles and protected environment configuration.
