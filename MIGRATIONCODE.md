# Run migrations via browser (no SSH)

**One link – run pending Laravel migrations on the server.**

## Link (QuizSnap live)

```
https://quizsnap.online/migrationcode?key=QuizSnapMigrate2026Xp9k3m7
```

1. Open the link in your browser (or click it from your deployment notes).
2. The page will run `php artisan migrate --force` and clear caches.
3. You’ll see plain-text output: migration lines and “SUCCESS” when done.

## Custom secret

To use your own key, set in `.env`:

```env
MIGRATION_RUN_KEY=your_secret_here
```

Then use:

```
https://quizsnap.online/migrationcode?key=your_secret_here
```

## Other URLs (same key)

- `https://quizsnap.online/migration?key=...` – same as above  
- `https://quizsnap.online/themigration?key=...` – short alias  
- `https://quizsnap.online/run-migrations?key=...` – same

Use **migrationcode** when you only want to run migrations (and cache clear) in one step.
