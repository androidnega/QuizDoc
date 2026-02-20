# QuizSnap Quiz Flow & Question Generation

This document describes the **quiz flow** and **how AI questions are generated** in QuizSnap, for use when debugging or handing off to another developer/AI.

---

## 1. Route base

- **Staff (admin/examiner) dashboard** is under: **`/dashboard`**
- All quiz management routes use the **`dashboard.`** route name prefix.
- Example base URL: `https://your-domain.com/dashboard` (or `http://localhost/QuizDoc/public/dashboard` locally).

---

## 2. Routes for quiz creation & question generation

### Quiz list & create (staff)

| Method | Path | Route name | Controller method |
|--------|------|------------|-------------------|
| GET | `/dashboard/quizzes` | `dashboard.quizzes.index` | `QuizManagementController@index` |
| GET | `/dashboard/quizzes/create` | `dashboard.quizzes.create` | `QuizManagementController@create` |
| POST | `/dashboard/quizzes` | `dashboard.quizzes.store` | `QuizManagementController@store` |
| GET | `/dashboard/quizzes/test-quiz` | `dashboard.quizzes.test-quiz` | `QuizManagementController@testQuizPage` |
| POST | `/dashboard/quizzes/create-test` | `dashboard.quizzes.create-test` | `QuizManagementController@createTestQuiz` |

### Quiz show & edit (staff)

| Method | Path | Route name | Controller method |
|--------|------|------------|-------------------|
| GET | `/dashboard/quizzes/{quiz}` | `dashboard.quizzes.show` | `QuizManagementController@show` |
| GET | `/dashboard/quizzes/{quiz}/edit` | `dashboard.quizzes.edit` | `QuizManagementController@edit` |
| PUT | `/dashboard/quizzes/{quiz}` | `dashboard.quizzes.update` | `QuizManagementController@update` |

### AI question generation (staff) — main routes for “how questions are generated”

| Method | Path | Route name | Controller method | Purpose |
|--------|------|------------|-------------------|---------|
| **POST** | **`/dashboard/quizzes/{quiz}/ai-generate/gemini`** | **`dashboard.quizzes.ai-generate.gemini`** | **`QuizManagementController@generateBatchGemini`** | **Primary: generate questions with Gemini only (5 per request). Frontend calls this in a loop until `done`.** |
| POST | `/dashboard/quizzes/{quiz}/ai-generate/batch` | `dashboard.quizzes.ai-generate.batch` | `QuizManagementController@generateBatch` | Fallback: Gemini → OpenAI → DeepSeek (5 per request). |
| GET | `/dashboard/quizzes/{quiz}/ai-generate/batch` | `dashboard.quizzes.ai-generate.batch.get` | (redirect) | Redirects to quiz show; not used for generation. |

### Question pool & approval (staff)

| Method | Path | Route name | Controller method |
|--------|------|------------|-------------------|
| POST | `/dashboard/quizzes/{quiz}/question-pools/{pool}/approve` | `dashboard.quizzes.pool.approve` | `QuizManagementController@approvePool` |
| POST | `/dashboard/quizzes/{quiz}/approve-all-pool` | `dashboard.quizzes.approve-all-pool` | `QuizManagementController@approveAllPool` |
| GET | `/dashboard/quizzes/{quiz}/question-pools/{pool}/edit` | `dashboard.quizzes.pool.edit` | `QuizManagementController@editPool` |
| PUT | `/dashboard/quizzes/{quiz}/question-pools/{pool}` | `dashboard.quizzes.pool.update` | `QuizManagementController@updatePool` |
| DELETE | `/dashboard/quizzes/{quiz}/question-pools/{pool}` | `dashboard.quizzes.pool.reject` | `QuizManagementController@rejectPool` |

### Publish / end quiz (staff)

| Method | Path | Route name |
|--------|------|------------|
| POST | `/dashboard/quizzes/{quiz}/publish` | `dashboard.quizzes.publish` |
| POST | `/dashboard/quizzes/{quiz}/unpublish` | `dashboard.quizzes.unpublish` |
| POST | `/dashboard/quizzes/{quiz}/end` | `dashboard.quizzes.end` |

---

## 3. How questions are generated (flow)

### On quiz create (POST `/dashboard/quizzes`)

1. **Controller:** `QuizManagementController@store`
2. Quiz is created in DB.
3. **Synchronous generation:** In the same request, the controller calls:
   - `AiQuestionService::generatePoolAndStoreGeminiOnly($quiz, $topicList, $toGenerate, $sourceText)`
   - In a loop (batches of 5, up to `questions_per_student`), until at least one question is in the pool or max attempts reached.
4. Questions are stored in **`question_pools`** (unapproved). User is redirected to **quiz show**; there they **approve** pool items to move them into **`questions`**.

### On “Generate questions with Gemini” (quiz show page)

1. **Frontend** (see `resources/views/admin/quizzes/partials/overview.blade.php`):
   - Button “Generate questions with Gemini” calls `startAiBatchGeneration(quizId, batchUrl, topics, target)`.
   - **batchUrl** = `route('dashboard.quizzes.ai-generate.gemini', $quiz)` → **POST `/dashboard/quizzes/{quiz}/ai-generate/gemini`**
2. **Request body (POST):** `target`, `topics`, `first_call` (and CSRF).
3. **Controller:** `QuizManagementController@generateBatchGemini`
   - Validates: `target` (1–250), `topics` (optional string), `first_call` (optional bool).
   - On first call: consumes one AI token (AiQuizTokenService), clears cache for Gemini key.
   - Calls **`AiQuestionService::generatePoolAndStoreGeminiOnly($quiz, $topics, $batchSize, $sourceText)`** with `$batchSize = min(5, $remaining)`.
4. **Response (JSON):** `generated`, `questions_count`, `pool_count`, `total_so_far`, `target`, `done`. If `done` is false, frontend calls the same route again (with `first_call: 0`) until `done` is true, then reloads the page.

### Service layer (actual AI call & storage)

- **File:** `app/Services/AiQuestionService.php`
- **Key methods:**
  - **`generatePoolAndStoreGeminiOnly(Quiz $quiz, array $topics, int $count, ?string $sourceText)`**  
    Delegates to `generatePoolAndStore(..., $geminiOnly = true)`. Uses **only Gemini** (no OpenAI/DeepSeek).
  - **`generatePoolAndStore(Quiz $quiz, array $topics, int $count, ?string $sourceText, bool $geminiOnly)`**  
    - If `$count > 20`, uses **`generatePoolInBatches`** (20 questions per API call).
    - Otherwise builds one prompt, calls **`callAiWithUsage($prompt, $geminiOnly)`**.
  - **`callAiWithUsage($prompt, $geminiOnly)`**  
    When `$geminiOnly === true`, only **Gemini** is used. When false, order is **Gemini → OpenAI → DeepSeek**.
  - **`callGemini($apiKey, $prompt)`**  
    Calls Google Generative Language API (`generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`). Tries models: `gemini-2.0-flash` → `gemini-1.5-flash` → `gemini-1.5-pro`.
- **Storage:** Each generated item is inserted into **`question_pools`** (columns: `quiz_id`, `question_text`, `options`, `correct_answer`, `topic`, `is_approved` = false, etc.). Approved pool items are later copied into **`questions`** (via `approvePool` / `approveAllPool`).

### ChatGPT / pasted JSON flow (no direct AI from system)

1. **Create quiz** (`/dashboard/quizzes/create`): Examiner fills form, copies the **Generated AI Prompt** (from topics + number of questions), pastes it into ChatGPT (or another AI), then pastes the returned JSON into **Paste AI JSON**.
2. **Validate:** Frontend (Validate JSON button) and backend (on submit) check: JSON syntax, required keys (`text`/`question`, `options`, `correct`/`correctAnswer`), exactly 4 options A–D, and that the number of questions matches the form.
3. **Store:** If valid, `QuizManagementController@store` creates the quiz and **`AiQuestionService::createPoolsFromValidatedJson($quiz, $parsed)`** inserts each item into **`question_pools`** with **`is_approved` = false** (same table as Gemini-generated questions).
4. **Approval pool integration:** Redirect goes to quiz show (Overview tab). All unapproved items—whether from pasted JSON or from “Generate questions with Gemini”—appear in the **Question Pool (Unapproved)** section. Examiner can **Approve** or **Reject** each item, or **Approve All**. Approved items are copied into the **`questions`** table (Approved Pool / live quiz questions).

---

## 4. Approval pool (Phase 4)

- **Unapproved pool:** `question_pools` where `is_approved = false`. Shown on quiz show → Overview as “Question Pool (Unapproved)”.
- **Examiner actions:** Per item: **Edit**, **Approve** (adds to quiz), **Reject** (removes from pool). **Approve All** approves every unapproved item.
- **Approved pool:** Rows in **`questions`** (the live quiz). Created when examiner clicks Approve (single or Approve All); data is copied from `question_pools` and the pool row is marked `is_approved = true`.
- **Pasted JSON** and **Gemini-generated** questions use the same pool and the same approval flow.

---

## 5. Quiz creation & scheduling (Phase 5)

When the examiner clicks **Create Quiz** (or **Update Quiz** on edit):

- **Quiz details saved:** title, exam type, class group, course, duration, number of questions, questions per student, topics, and (on edit) script URL/text.
- **Schedule:** `starts_at` and `ends_at` (optional) are saved; students can only start when the window is open (no `starts_at` or `starts_at` past; no `ends_at` or `ends_at` future).
- **Status:** New quizzes are created with **status = Draft** and **is_published = false**. After the examiner approves questions and clicks **Publish**, the quiz becomes published. Display status for the UI is derived as:
  - **Draft** — not published.
  - **Scheduled** — published, `starts_at` in the future (window not yet open).
  - **Active** — published, window open (between `starts_at` and `ends_at`).
  - **Ended** — published, `ends_at` in the past.
- **Approved questions:** The quiz’s live questions are the rows in the **`questions`** table linked by `quiz_id` (approved from the question pool). No separate “link” step—approving pool items creates those rows.
- **Result visibility** (saved on create/update):
  - **Immediately (score only)** — students see their score right after submit; no correct answers or review.
  - **After Deadline (full review)** — full question/answer review is shown after the quiz window has ended (`ends_at` past).
  - **Manual Release (no score or review until released)** — results are hidden until the examiner releases them (visibility = disabled).

---

## 6. Key files (for fixing issues)

| Purpose | File path |
|--------|-----------|
| Quiz + AI generation routes | `routes/web.php` (search for `quizzes` and `ai-generate`) |
| Quiz CRUD + generate endpoints | `app/Http/Controllers/Admin/QuizManagementController.php` |
| AI API calls (Gemini, OpenAI, DeepSeek) & pool generation | `app/Services/AiQuestionService.php` |
| Quiz show page (overview with “Generate questions with Gemini” button) | `resources/views/admin/quizzes/show.blade.php` |
| Overview tab (button + JS that calls ai-generate/gemini) | `resources/views/admin/quizzes/partials/overview.blade.php` |
| Quiz create form | `resources/views/admin/quizzes/create.blade.php` |
| API keys (DB) | `app/Models/Setting.php` (keys: `openai_api_key`, `gemini_api_key`, `deepseek_api_key`) |
| Config (timeout, keys from env) | `config/services.php` (`gemini.key`, `gemini.timeout`) |

---

## 7. AI provider order and config

- **Test connection (Settings → AI):** Tries **Gemini → OpenAI → DeepSeek**.
- **Question generation (main flow):**  
  - **Gemini-only** when using `generatePoolAndStoreGeminiOnly` (quiz create and “Generate questions with Gemini” button).  
  - **Gemini → OpenAI → DeepSeek** when using `generatePoolAndStore` with `$geminiOnly = false` (e.g. batch endpoint).
- **Keys:** Stored in DB (Settings) or env: `GEMINI_API_KEY`, `OPENAI_API_KEY`, `DEEPSEEK_API_KEY`. See `config/services.php` and `Setting::getValue(Setting::KEY_*_API)`.

---

## 8. One-line summary for ChatGPT

**Route for generating questions in QuizSnap:**  
**POST `/dashboard/quizzes/{quiz}/ai-generate/gemini`** (route name: `dashboard.quizzes.ai-generate.gemini`) — body: `target`, `topics`, `first_call`. Controller: `QuizManagementController@generateBatchGemini`; service: `AiQuestionService::generatePoolAndStoreGeminiOnly`. Questions are written to `question_pools`; the UI then approves them into `questions`.
