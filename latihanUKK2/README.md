<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Deployment to cPanel

The Laravel project is in `latihanUKK2`; the domain's document root must serve only its `public` directory. Do not place the project root, `.env`, `vendor`, or writable `storage` directory under a public web directory.

The domain `rapi-smkn1bantul.web.id` is registered, but hosting has not been purchased yet. The domain alone does not provide a server, database, SMTP service, or SSL certificate. After purchasing Domainesia Nimbus One, use the IP/DNS records supplied by Domainesia in the IDWebHost domain DNS panel; do not change DNS to a guessed address. The production environment template already uses this domain, but HTTPS will only work after DNS points to the hosting and its SSL certificate is active.

1. In IDWebHost, set the domain's DNS to the A record/IP address provided by Domainesia for your Nimbus One hosting. Do not guess the IP; use the hosting panel's displayed address and allow DNS time to propagate. In Domainesia cPanel, add the domain and select PHP 8.3 or newer with the required Laravel extensions, including PDO MySQL and GD. Create a MySQL database and database user, and grant that user access to the database.
2. Upload the project outside `public_html` if possible, then point the domain document root to `latihanUKK2/public`. If cPanel does not allow changing the document root, keep the application outside `public_html`, copy only the contents of `latihanUKK2/public` into `public_html`, and update the two application paths at the top of `public_html/index.php` to point to the uploaded project's `vendor/autoload.php` and `bootstrap/app.php`. Never copy `.env` into `public_html`.
3. Use `.env.production.example` as a checklist, then create the actual `.env` outside the public web root. Replace every `GANTI_...` value with the real domain and values shown in cPanel. In cPanel SSL/TLS Status, enable AutoSSL (or the provider's SSL feature) for the domain and wait until the certificate is active before setting `APP_URL=https://...`; enabling HTTPS on a domain requires both working DNS and a certificate. Then enable **Force HTTPS Redirect** for the domain in cPanel so browser requests are redirected to HTTPS. Set `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS `APP_URL`, a unique secret `APP_KEY`, and the cPanel MySQL database name, username, and password. Generate the key once with `php artisan key:generate --force` after creating `.env`; do not regenerate it after storing production data. Set `MAIL_MAILER=smtp` and configure the SMTP host, port, username, password, and sender address supplied by your email provider; password reset depends on email delivery. Keep `.env` private.
4. Install production dependencies with `composer install --no-dev --optimize-autoloader`, then run `php artisan migrate --force`. There is no npm lockfile in this project, so use `npm install` followed by `npm run build` to generate frontend assets before deployment, and upload `public/build` including its manifest. If the host has no Node.js, build on a compatible machine and upload those generated assets.
5. Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`. Ensure `storage` and `bootstrap/cache` are writable by the PHP process. Run `php artisan storage:link` so complaint photos can be served; confirm the host permits symbolic links.
6. The seeder creates demo users with known passwords only in local/testing environments. In production, `php artisan db:seed --force` creates the role records but not demo accounts or a demo room. To create the first administrator, use `php artisan tinker` from the application directory and enter the following interactively, replacing the sample values with your own:

   ```php
   $role = \App\Models\Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
   \App\Models\User::create(['name' => 'Admin Sekolah', 'email' => 'admin@domain-anda.example', 'password' => 'GANTI_DENGAN_PASSWORD_KUAT', 'role_id' => $role->id, 'whatsapp' => '6281234567890']);
   ```

   The application hashes the password when saving the user. Do not use the sample email or password. Then create the required rooms and other users through the application.
7. Check the HTTPS site, `/up`, login, email reset, mobile API, photo upload, and report downloads. Confirm cPanel backups cover both the database and uploaded files.

Do not copy a local `.env`, local database, or development credentials to production. If deployment is done by FTP without Composer access, install dependencies on a compatible build environment and upload the production `vendor` directory as well.

## GitHub Actions CI/CD

The workflow is at the workspace root in `.github/workflows/ci-cd.yml`. Pushes and pull requests to `main` or `master` run Laravel tests, build the Vite assets, and analyze/test the Flutter app. A successful push can deploy the Laravel backend over SSH; deployment is intentionally disabled until the hosting account is ready.

After purchasing hosting and confirming SSH access is available:

1. Push this workspace to a GitHub repository with the workflow at the repository root. Create the production application directory outside the web root (for example, `/home/CPANEL_USER/rapi_app`), create its `storage` and `bootstrap/cache` directories, and create its production `.env` there. Point the domain document root to that directory's `public` folder.
2. In GitHub repository **Settings → Secrets and variables → Actions**, add the repository variable `CPANEL_DEPLOY_ENABLED` with value `true`.
3. Add these repository secrets: `CPANEL_SSH_HOST`, `CPANEL_SSH_PORT`, `CPANEL_SSH_USER`, `CPANEL_SSH_PRIVATE_KEY`, `CPANEL_SSH_KNOWN_HOSTS`, and `CPANEL_APP_PATH`. Get the host, port, user, and the trusted SSH host key from Domainesia. `CPANEL_SSH_KNOWN_HOSTS` must contain the host-key line verified with the provider; do not generate/trust it blindly inside the workflow. `CPANEL_APP_PATH` is the absolute application directory outside the public web root. Optionally add `CPANEL_PHP_BIN` with the PHP 8.3 CLI path supplied by the host if its default `php` command is not PHP 8.3.
4. Confirm the deploy user can write to the application, `storage`, and `bootstrap/cache` directories, and can run PHP CLI and Artisan. The workflow preserves the server `.env` and uploaded `storage` files, uploads the built Laravel application, and runs migrations and Laravel caches. Back up the database before enabling automatic production deploys.
5. Deploy runs only on successful `main`/`master` pushes (or a manual run with **Deploy the selected main/master branch to production** enabled). Until SSH access and the secrets are configured, leave `CPANEL_DEPLOY_ENABLED` unset so CI can still run without attempting a deploy. If Nimbus One does not provide SSH, this SSH deploy job cannot be enabled; use the hosting's supported Git deployment or request SSH access before choosing an alternative transport.

Never put `.env`, database/email passwords, or Android keystore files in GitHub source files. GitHub Actions secrets are used for deployment credentials. The frontend package currently has no npm lockfile, so pinning JavaScript dependency resolution with a committed lockfile is recommended before relying on repeatable production builds.
