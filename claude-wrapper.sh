#!/bin/bash

# Add the paths where claude might be installed
# Dynamically use the current user's home directory
export PATH="${HOME}/Library/Application Support/Herd/config/nvm/versions/node/v20.19.3/bin:${HOME}/Library/Application Support/Herd/config/nvm/versions/node/v20.19.4/bin:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:$PATH"

# this should be passed as first argument
COMMAND_DIRECTORY="$1"

# check if both arguments are provided, otherwise fail
if [ -z "$COMMAND_DIRECTORY" ]; then
    echo "Error: COMMAND_DIRECTORY is required"
    echo "Usage: $(basename "$0") <COMMAND_DIRECTORY> [claude arguments...]"
    echo "Example: $(basename "$0") /path/to/projects/my-project --help"
    exit 1
fi

if [ ! -d "$COMMAND_DIRECTORY" ]; then
    echo "Error: Project directory does not exist: $COMMAND_DIRECTORY"
    exit 1
fi

cd "$COMMAND_DIRECTORY" || exit 1

# Shift first argument so remaining can be passed to claude
shift 1

# Execute claude with all arguments from the project directory
# If the last argument doesn't start with a dash (it's the prompt), pass it via stdin
# This ensures claude doesn't wait for interactive input
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
