# Run migrations (including Exam Calendar) on live

To create or update all tables (including the **exam_calendar** table) on quizsnap.online, open this URL in your browser:

**https://quizsnap.online/migration?key=YOUR_SECRET**

- Replace `YOUR_SECRET` with your migration key.
- **Default key** (if not set in .env): `QuizSnapMigrate2026Xp9k3m7`
- To use your own key, set `MIGRATION_RUN_KEY=your_secret` in `.env` on the server, then use that value in the URL.

Alternative URL (same behaviour):

**https://quizsnap.online/run-migrations?key=YOUR_SECRET**

The endpoint runs `php artisan migrate --force` (all pending migrations), then clears config, route, cache, and view caches. Without the correct `key` you get 403.
