# Proctoring & verification image storage (cPanel / server)

All proctoring images (face at start/end, violation captures) are stored under Laravel’s `storage/app/public` when “Server” is selected in settings.

## On the server (one-time)

1. **Create the storage link** (so `public/storage` serves files from `storage/app/public`):
   ```bash
   php artisan storage:link
   ```

2. **Create proctoring directories and check permissions**:
   ```bash
   php artisan storage:ensure-proctoring
   ```

3. **Ensure the web server can write to storage**:
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R <web-server-user>:<web-server-user> storage bootstrap/cache
   ```
   On cPanel the user is often your account name or `nobody`. Check with your host.

4. **Ensure `APP_URL`** in `.env` matches your site (e.g. `https://quiz.example.com`) so image URLs are correct.

After this, verification and violation images will be saved under `storage/app/public/verification/` and `storage/app/public/violations/` and will be viewable in the admin session details.
