# Scripts

## prepare-commit-msg

Git hook that replaces the footer **Made-with: Cursor** with **Developed by Manuel on git version pull** in every commit message.

**Install (one-time per clone):**

```bash
cp scripts/prepare-commit-msg .git/hooks/prepare-commit-msg
chmod +x .git/hooks/prepare-commit-msg
```

After this, any commit (including from Cursor) will have the attribution footer updated automatically.
