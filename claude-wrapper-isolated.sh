#!/bin/bash

# Claude Wrapper with Complete Directory Isolation
# This wrapper ensures Claude can ONLY access the specified directory

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

# Convert to absolute path if relative
PROJECT_DIR=$(cd "$PROJECT_DIR" && pwd)

# Shift first argument so remaining can be passed to claude
shift 1

# Create a temporary script that will run claude with restricted environment
# This script will be executed with the project directory as HOME and working directory
TEMP_SCRIPT=$(mktemp /tmp/claude-isolated-XXXXXX.sh)
chmod +x "$TEMP_SCRIPT"

cat > "$TEMP_SCRIPT" << 'SCRIPT_END'
#!/bin/bash

# This script runs inside the isolated environment
# All paths are relative to the project directory which is now our HOME and PWD

# Ensure we're in the correct directory
cd "$HOME" || exit 1

# Run claude with all arguments
if [ $# -gt 0 ]; then
    last_arg="${*: -1}"
    if [[ ! "$last_arg" =~ ^- ]]; then
        # Remove the last argument (the prompt) and pass it via stdin
        set -- "${@:1:$(($#-1))}"
        echo "$last_arg" | exec claude "$@"
    else
        exec claude "$@"
    fi
else
    exec claude "$@"
fi
SCRIPT_END

# Execute claude in an isolated environment
# - Set HOME to the project directory to prevent access to user's real home
# - Change to project directory before running
# - Unset variables that might reveal the real directory structure
# - Use 'env -i' to start with a clean environment
(
    cd "$PROJECT_DIR" || exit 1
    exec env -i \
        HOME="$PROJECT_DIR" \
        PWD="$PROJECT_DIR" \
        PATH="$PATH" \
        USER="$USER" \
        SHELL="/bin/bash" \
        TERM="$TERM" \
        LANG="$LANG" \
        LC_ALL="$LC_ALL" \
        /bin/bash "$TEMP_SCRIPT" "$@"
)

EXIT_CODE=$?

# Clean up temporary script
rm -f "$TEMP_SCRIPT"

exit $EXIT_CODE