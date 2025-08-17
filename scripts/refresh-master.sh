#!/bin/bash

# Refresh master branch with latest changes
# Stash any local changes first
git stash push -m "Auto-stash before refresh-master" --include-untracked

# Switch to master and pull latest changes
git checkout master && git pull origin master --rebase

# Ensure all scripts have executable permissions
chmod +x scripts/*.sh 2>/dev/null || true

# Check if there were stashed changes and notify
if git stash list | grep -q "Auto-stash before refresh-master"; then
    echo "Note: Local changes were stashed. Run 'git stash pop' to restore them if needed."
fi
