#!/bin/bash
# Run this on the SERVER when git pull fails with:
#   "Your local changes to the following files would be overwritten by merge"
#
# It stashes your local changes, pulls, then reminds you how to reapply.

set -e
echo "Stashing local changes..."
git stash push -m "pre-pull $(date +%Y%m%d-%H%M%S)" -- resources/views/admin/quizzes/create.blade.php 2>/dev/null || git stash push -m "pre-pull $(date +%Y%m%d-%H%M%S)"
echo "Pulling from remote..."
git pull
echo "Done. To reapply your stashed changes: git stash list && git stash pop"
