# Academic Years on the Coordinator Dashboard

## What It Is

**Academic Years** are the time periods used by Docu Mentor for grouping project groups and projects (e.g. `2024/2025`). The coordinator creates and manages these so that:

- Every **project group** belongs to one academic year.
- When a **group leader** creates a group (by adding their first member), the system assigns it to the **active** academic year.
- **Projects** are tied to groups, so they are effectively scoped by academic year as well.

---

## How to Write / Use It

### 1. **Create an academic year**

- Go to **Dashboard** → **Academic Years** (sidebar under “Academic Structure”).
- Click **Add Academic Year**.
- Enter:
  - **Year**: e.g. `2024/2025` or `2025` (max 9 characters, must be unique).
  - **Submission deadline** (optional): default is end of September of the following year if left blank.
  - **Set as active year**: check this to make this year the one used for new groups.
- Submit. Only one year can be active at a time; setting a new one as active clears the previous.

### 2. **Edit an academic year**

- On the Academic Years list, click **Edit** for the row.
- Change **Year**, **Submission deadline**, or **Set as active year**.
- Save. Again, if you set this year as active, any other active year is deactivated.

### 3. **Delete an academic year**

- Click **Delete** on the row.
- Deletion is **blocked** if that year has any **groups** or **projects**. You must remove or reassign those first (or leave the year in place).

---

## How It Works in the System

| Component | Role of academic year |
|----------|------------------------|
| **Model** | `App\Models\DocuMentor\AcademicYear` — table `academic_years` with `year`, `is_active`, `submission_deadline`. |
| **Controller** | `App\Http\Controllers\DocuMentor\AcademicYearController` — index, create, store, edit, update, destroy. |
| **Routes** | Under coordinator dashboard: `dashboard.coordinators.academic-years.*` (e.g. `/dashboard/coordinators/academic-years`). |
| **Active year** | `AcademicYear::active()` returns the one record with `is_active = true`. Used when auto-creating a group for a group leader. |
| **Deadline** | `effective_deadline`: uses `submission_deadline` if set, otherwise September 30 of the year after the `year` value. |
| **Relations** | An academic year `hasMany` groups and `hasMany` projects; these are checked before allowing delete. |

**Flow in code:**

1. Coordinator creates/edits years via the **Academic Years** CRUD pages.
2. When a **group leader** adds their first member and has no group yet, `GroupLeaderController::addMember` uses `AcademicYear::active()` (or the latest year) to set `academic_year_id` on the new group.
3. Projects and workload reports can use the same year and deadline for filtering and display.

---

## Summary

- **Where**: Coordinator dashboard → **Academic Years** (unified URL: `/dashboard/coordinators/academic-years`).
- **What**: Create/edit/delete year labels and set one as **active**; optional submission deadline.
- **Why**: New groups are attached to the active year; deletion is prevented if the year has groups or projects.
