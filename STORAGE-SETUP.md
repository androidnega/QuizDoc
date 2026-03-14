# Proctoring & verification image storage (cPanel / server)

All proctoring images (face at start/end, violation captures) are stored under Laravel’s `storage/app/public` when “Server” is selected in settings.

## No SSH / no terminal (cPanel only)

**Open this URL once in your browser** (same key as your migration/pull script):

```
https://quiz.neckpressing.com/thekey.php?key=QuizSnapMigrate2026Xp9k3m7
```

That script will:

1. Pull latest code from git (reset + fetch)
2. Clear Laravel caches
3. Run **storage:link** (create `public/storage` → `storage/app/public`)
4. Run **storage:ensure-proctoring** (create `verification/` and `violations/` dirs)
5. Set permissions on `storage` and `bootstrap/cache` (chmod 775 where possible)

After the page shows “SUCCESS”, proctoring and verification images will save and display. Delete `thekey.php` when you no longer need it.

---

## If you have SSH (optional)

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
