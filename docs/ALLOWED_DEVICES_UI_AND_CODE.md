# Where “Desktop only” / Allowed devices lives (UI + code, for lecturer)

## Where you find it in the system (UI)

**Who sets it:** **Coordinator** (or Super Admin). Examiners do not set this.

1. **Dashboard → Class groups** → open a **class group** (click its name).
2. Near the top, in **“Group actions”**, you’ll see:
   - **“Allowed devices for quizzes:”** with a dropdown: **Desktop only** | **Mobile only** | **Both (desktop and mobile)**.
3. Choose the option and click **Save**.

You can also set it when **creating** or **editing** a class group (same dropdown in the form).

**Where it appears (read‑only):**

- **Examiner:** Dashboard → Quizzes → open a quiz → a small badge near the top shows “Desktop only”, “Mobile only”, or “Both”.
- **Student:** If the quiz is “Desktop only” and they open it on a phone, they see a message asking them to use a computer. If it’s “Both” or “Mobile only”, they can take it on mobile.

So in the UI: **one place to set it** (class group, by coordinator), and **the same value is shown** to examiners and students everywhere.

---

## Where “Desktop only” comes from in the code (simple explanation for lecturer)

- The system has **one** setting per class group: “allowed devices”. It can be **desktop only**, **mobile only**, or **both**.
- That choice is stored **once** (in the class group and synced to its quizzes and a small settings store). The code **does not** decide “desktop only” in lots of different places.
- Whenever the app needs to know “is this desktop only?” (examiner view, student quiz page, rules, layout), it uses **one** shared path in the code: it asks the **quiz** for its “effective” allowed devices, which in turn comes from the **class group** (the coordinator’s choice). So the label “Desktop only” (or “Mobile only” / “Both”) always comes from that **single** source.
- That way there are no conflicts: if the coordinator sets “Both”, the examiner and the student side both see and use “Both”; they don’t keep showing “Desktop only” from somewhere else.

In short: **“Desktop only” comes from the one setting the coordinator picks for the class group; the rest of the system only reads that same value.**
