# QuizDoc Scale Guidelines (300–1000+ Students)

This document describes how the system is tuned so the **browser does most of the work** (~80%) and the server and database stay stable when **300–1000+ students** take a quiz at once.

---

## 1. Design principle: 80% load in the browser

- **Timer**: Computed in the browser from server time; only **one time-sync request every 60 seconds** (not every second).
- **Answers**: Saved in the browser and sent in **batches** with a **2.8s debounce**; always use the batch endpoint when multiple answers are pending to minimize requests.
- **Violations**: Violation **events** are sent to the server, but **violation images (screenshots)** are capped at **5 per student per quiz** (all types combined) to limit storage and upload load.
- **Proctor feed**: Live examiner feed is **disabled** during the quiz (`liveProctorEnabled = false`) so no camera frames are sent every 2 seconds per student.
- **Heartbeat**: Sent only when the user **returns to the tab** (focus), not on a timer.

Result: fewer requests per student and less server/DB load at scale.

---

## 2. API / request audit (per student during quiz)

| Endpoint | When | Target frequency |
|----------|------|-------------------|
| `GET /quiz/take` | Page load | 1 per quiz |
| `GET /quiz/time-sync` | Timer correction | Every **60 s** (was 30 s) |
| `POST /quiz/save-answer` | Single answer | Rare (prefer batch) |
| `POST /quiz/save-answers` | Batch answers | Every **~2.8 s** when there are changes |
| `POST /quiz/violation` | Proctoring event | Only when a violation occurs |
| `POST /quiz/violation/capture` | Violation image | Max **5 per student per quiz** |
| `POST /quiz/heartbeat` | Tab focus | On focus only |
| `POST /quiz/proctor-feed` | Live camera | **Disabled** (no requests) |
| `POST /quiz/finalize` | Submit | 1 per quiz |

No polling loops; no per-second server calls. Heavy work (timer, answer buffering, validation) stays in the browser.

---

## 3. Database and backend

- **Session lookup**: `quiz_sessions.session_token` is unique (indexed); used on every quiz API call.
- **Violation counts**: A single grouped query is used per request where needed (e.g. `selectRaw('type, count(*) as c')->groupBy('type')`) instead of multiple `count()` queries.
- **Quiz show page**: Violation counts for the session are loaded in **one query** and derived in PHP.
- **Settings**: Proctoring and app settings are read via `Setting::getValue()`, which uses **cache** (1 hour) to avoid repeated DB hits.
- **Indexes**: Existing indexes on `quiz_sessions (quiz_id, ended_at)`, `quiz_sessions (quiz_id, start_time)`, and `quiz_violations (quiz_session_id, type)` support list and violation queries.

Ensure MySQL (or your DB) has enough connections and that PHP/webserver limits (e.g. `max_children`, `pm.max_requests`) are suitable for 1000 concurrent students.

---

## 4. Configuration changes applied

- **Student login session**: **3 hours**. Set `SESSION_LIFETIME=180` (minutes) in `.env`. Default in `config/session.php` is 180.
- **Proctoring violation images**: **Max 5 per student per quiz** (all types combined). Defined by `StudentQuizController::MAX_QUIZ_VIOLATION_CAPTURES = 5`.
- **Answer save debounce**: **2800 ms** (batch saves, fewer requests).
- **Time sync interval**: **60 s** (fewer time-sync requests).

---

## 5. Optional hardening

- **Rate limiting**: Consider throttling quiz routes (e.g. 60–120 requests per minute per IP or per session token) to protect against misbehaving clients or scripts.
- **Queue**: For heavy post-quiz work (e.g. notifications), use a queue driver (`database` or `redis`) and workers instead of `sync`.
- **Cache driver**: For production at scale, consider `redis` or `memcached` for cache and, if needed, sessions (`SESSION_DRIVER=redis`) to reduce file I/O and share state across workers.
- **Session driver**: With many concurrent users, `SESSION_DRIVER=database` or `redis` can be more robust than `file`; ensure the sessions table or Redis is sized and backed up.

---

## 6. Checklist before a large exam (300–1000+ students)

- [ ] `SESSION_LIFETIME=180` in `.env` (3-hour student session).
- [ ] Confirmed violation images are capped at 5 per student (default in code).
- [ ] Confirmed live proctor feed is off during quiz (no 2 s camera uploads).
- [ ] Time sync 60 s and answer debounce 2.8 s (defaults in `public/js/quiz-proctoring.js`).
- [ ] Database indexes present (quiz_sessions, quiz_violations).
- [ ] Sufficient PHP/webserver capacity and DB connections for peak concurrency.
- [ ] (Optional) Throttle on quiz routes and queue for post-quiz jobs.
- [ ] (Optional) Redis (or similar) for cache/sessions in production.

---

## 7. Where the code lives

| What | Where |
|------|--------|
| Session lifetime | `config/session.php`, `.env` `SESSION_LIFETIME` |
| Max violation images | `App\Http\Controllers\Student\StudentQuizController::MAX_QUIZ_VIOLATION_CAPTURES` |
| Save debounce & time sync | `public/js/quiz-proctoring.js` (`SAVE_DEBOUNCE_MS`, `TIME_SYNC_INTERVAL_MS`) |
| Live proctor feed | `StudentQuizController::show()` sets `liveProctorEnabled = false` |
| Violation count optimization | `StudentQuizController::show()` and `recordViolation()` |
| Setting cache | `App\Models\Setting::getValue()` |

These guidelines and defaults help keep the system robust for 300–1000+ students taking a quiz at once while keeping most of the load in the browser.
