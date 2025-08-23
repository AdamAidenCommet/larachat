# AGENTS.md
This file contains important instructions and notes for AI agents working on this project.

# Blue Code
Blue code is 19B

## Important Instructions
1. **Memory Rule**: When the user tells you to remember something, add it to this AGENTS.md file.
2. **Answer First Rule**: When someone asks you a question, answer the question first before starting to code. Don't immediately jump into coding without answering what was asked.
2. **ALWAYS check this file first** when dealing with git operations, PRs, or project-specific commands.

## Critical Git Operation Rules

### When user says "commit":
- **ALWAYS** commit ALL files: `git add .` then `git commit -m "message"`
- Include both modified AND untracked files

### When user says "push":
1. First commit all changes (see above)
2. Check current branch: `git branch --show-current`
3. **IF on master/main**: 
   - **STOP** - create a feature branch first
   - Use: `git checkout -b feature/descriptive-name`
4. Then push: `git push -u fork feature/branch-name`
5. **NEVER EVER** push directly to master/main

### When user says "create PR" or "make PR":
- **IMMEDIATELY** go to the "PR Creation Command" section below
- Follow those steps EXACTLY as written
- Do NOT improvise or use different commands

## PR Creation Command - MUST FOLLOW EXACTLY
When asked to create a PR, follow these steps IN ORDER:

### Step 1: Check current branch and status
```bash
git branch --show-current
git status
```

### Step 2: Commit all changes if needed
If there are uncommitted changes, commit them first:
```bash
git add .
git commit -m "Your commit message"
```

### Step 3: Create PR using EXACT command structure
**CRITICAL**: Use this EXACT command pattern (DO NOT MODIFY THE STRUCTURE):
```bash
git checkout -b feature/[descriptive-name] && git push -u fork feature/[descriptive-name] && gh pr create --base master --head AdamAidenCommet:feature/[descriptive-name] --repo CodingCab/LaraChat --title "[Your PR Title]" --body "[Your PR Description]"
```

**MANDATORY RULES**:
- ✅ ALWAYS use `fork` (NOT `origin`) for pushing
- ✅ ALWAYS use `--head AdamAidenCommet:feature/[descriptive-name]` (the fork branch)
- ✅ ALWAYS target `--repo CodingCab/LaraChat` (the ORIGIN repository)
- ✅ ALWAYS use `--base master` (the main branch)
- ✅ ALWAYS replace `[descriptive-name]` with a meaningful branch name (e.g., `fix-sidebar-navigation`, `add-user-profile`)
- ✅ ALWAYS include a clear PR title and description
- ❌ NEVER push directly to master
- ❌ NEVER push to origin, always use fork
- ❌ NEVER skip the commit step if there are changes

### Example of CORRECT PR creation:
```bash
git checkout -b feature/improve-chat-ui && git push -u fork feature/improve-chat-ui && gh pr create --base master --head AdamAidenCommet:feature/improve-chat-ui --repo CodingCab/LaraChat --title "Improve chat UI responsiveness" --body "- Enhanced mobile layout\n- Fixed message alignment\n- Added loading states"
```

## Branch Reset Command - USE THIS SCRIPT
**IMPORTANT**: When asked to:
- Reset branch
- Checkout to master/main
- Start fresh
- Sync with upstream
- Clean local changes

**ALWAYS USE THIS COMMAND**:
```bash
scripts/refresh-master.sh
```

**DO NOT** manually run git checkout master, git pull, etc. The script handles everything correctly.

## Claude Subagents Integration
Database agents are automatically synchronized to `.claude/agents/` directory for use with Claude Code's built-in subagent feature.

### How it works:
1. When an agent is created/updated/deleted in the database, it automatically syncs to `.claude/agents/`
2. Each agent becomes a markdown file with YAML frontmatter containing:
   - `name`: Slugified agent name (e.g., "code-reviewer")
   - `description`: Purpose of the agent
   - `tools`: Comma-separated list of allowed tools (e.g., "Read, Grep, Glob")
3. Claude Code automatically discovers and uses these agents based on task context

### Agent Fields:
- **name**: Display name of the agent
- **description**: Brief description for Claude to understand when to use this agent
- **prompt**: System prompt defining the agent's behavior
- **tools**: Optional list of tools the agent can access (defaults to all if not specified)

### Manual Sync Command:
If needed, sync all agents from database to filesystem:
```bash
php artisan agents:sync
```

### Example Agent File (.claude/agents/code-reviewer.md):
```markdown
---
name: code-reviewer
description: Expert at reviewing Laravel and Vue.js code
tools: Read, Grep, Glob, Bash
---

You are a code review specialist focused on Laravel and Vue.js applications...
```
