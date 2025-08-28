#!/bin/bash

# Claude Sandbox Wrapper - Maximum Isolation
# This wrapper creates a temporary isolated environment for Claude

# Add the paths where claude might be installed
export PATH="${HOME}/Library/Application Support/Herd/config/nvm/versions/node/v20.19.3/bin:${HOME}/Library/Application Support/Herd/config/nvm/versions/node/v20.19.4/bin:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:$PATH"

# Get the project directory from the first argument
PROJECT_DIR="$1"

# Validate project directory
if [ -z "$PROJECT_DIR" ]; then
    echo "Error: PROJECT_DIR is required"
    echo "Usage: $(basename "$0") <PROJECT_DIR> [claude arguments...]"
    exit 1
fi

if [ ! -d "$PROJECT_DIR" ]; then
    echo "Error: Project directory does not exist: $PROJECT_DIR"
    exit 1
fi

# Convert to absolute path
PROJECT_DIR=$(cd "$PROJECT_DIR" && pwd)

# Shift first argument so remaining can be passed to claude
shift 1

# Create a temporary sandbox directory
SANDBOX_DIR=$(mktemp -d /tmp/claude-sandbox-XXXXXX)
trap "rm -rf $SANDBOX_DIR" EXIT

# Create a project directory in the sandbox
mkdir -p "$SANDBOX_DIR/project"

# Use rsync to copy the project files to sandbox (follows symlinks, preserves permissions)
# Exclude sensitive directories that shouldn't be accessible
rsync -aL --exclude='.env' \
          --exclude='storage/logs' \
          --exclude='storage/framework/sessions' \
          --exclude='storage/framework/cache' \
          --exclude='bootstrap/cache' \
          --exclude='node_modules' \
          --exclude='vendor' \
          --exclude='.git' \
          "$PROJECT_DIR/" "$SANDBOX_DIR/project/" 2>/dev/null || true

# Create a wrapper script in the sandbox
cat > "$SANDBOX_DIR/run-claude.sh" << 'EOF'
#!/bin/bash
cd /project || exit 1

# Process arguments for claude
if [ $# -gt 0 ]; then
    last_arg="${*: -1}"
    if [[ ! "$last_arg" =~ ^- ]]; then
        set -- "${@:1:$(($#-1))}"
        echo "$last_arg" | exec claude "$@"
    else
        exec claude "$@"
    fi
else
    exec claude "$@"
fi
EOF

chmod +x "$SANDBOX_DIR/run-claude.sh"

# Run claude in the sandbox environment with restricted access
# The sandbox directory becomes the root for the claude process
(
    cd "$SANDBOX_DIR" || exit 1
    
    # Create a very restricted environment
    env -i \
        HOME="/project" \
        PWD="/project" \
        PATH="$PATH" \
        USER="claude-sandbox" \
        SHELL="/bin/bash" \
        TERM="xterm-256color" \
        TMPDIR="$SANDBOX_DIR/tmp" \
        /bin/bash "$SANDBOX_DIR/run-claude.sh" "$@"
)

EXIT_CODE=$?

# The trap will clean up the sandbox directory
exit $EXIT_CODE