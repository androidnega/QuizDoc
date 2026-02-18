# Fix "would be overwritten by merge" on server

When `git pull` fails with:

```
error: Your local changes to the following files would be overwritten by merge:
    resources/views/admin/quizzes/create.blade.php
Please commit your changes or stash them before you merge. Aborting
```

**On the server**, run one of these:

### Option 1: Stash, then pull (keeps your local edits)

```bash
git stash push -m "server local changes"
git pull
# Optional: reapply your changes
git stash list
git stash pop
```

### Option 2: Use the script (after you can pull)

```bash
chmod +x scripts/fix-pull-on-server.sh
./scripts/fix-pull-on-server.sh
```

### Option 3: Discard local changes (only if you don't need them)

```bash
git checkout -- resources/views/admin/quizzes/create.blade.php
git pull
```

Prefer **Option 1** if you want to keep any server-only edits.
