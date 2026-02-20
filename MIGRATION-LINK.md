# Run migrations (including Exam Calendar) on live

To create or update all tables (including the **exam_calendar** table) on quizsnap.online, open this URL in your browser:

**https://quizsnap.online/migration?key=QuizSnapMigrate2026Xp9k3m7**

- **Default key** (copy exactly, including the final `7`): **`QuizSnapMigrate2026Xp9k3m7`**
- If you get "Invalid or missing key", check you didn’t drop the last character (e.g. `QuizSnapMigrate2026Xp9k3m` is wrong).
- To use your own key, set `MIGRATION_RUN_KEY=your_secret` in `.env` on the server, then use that value in the URL.

Alternative URL (same behaviour):

**https://quizsnap.online/run-migrations?key=QuizSnapMigrate2026Xp9k3m7**

The endpoint runs `php artisan migrate --force` (all pending migrations), then clears config, route, cache, and view caches. Without the correct `key` you get 403.
