#!/usr/bin/env bash

# AWS Login Script for mcp-adapter-initializer
# Self-contained OKTA authentication and AWS role assumption

set -e

AUTH_METHOD="aws-okta-processor"

show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -m, --method METHOD    Authentication method: 'aws-okta-processor' or 'oktaplz' (default: aws-okta-processor)"
    echo "  -h, --help            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                              # Use default aws-okta-processor"
    echo "  $0 -m oktaplz                   # Use oktaplz"
    echo "  $0 --method aws-okta-processor  # Use aws-okta-processor explicitly"
}

while [[ $# -gt 0 ]]; do
    case $1 in
        -m|--method)
            if [ -z "${2:-}" ]; then
                echo "❌ Option $1 requires a value"
                show_usage
                exit 1
            fi
            AUTH_METHOD="$2"
            shift 2
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Validate authentication method
case "$AUTH_METHOD" in
    "aws-okta-processor"|"oktaplz")
        # Valid method
        ;;
    *)
        echo "❌ Invalid authentication method: $AUTH_METHOD"
        echo "Valid methods: aws-okta-processor, oktaplz"
        exit 1
        ;;
esac

CREDENTIALS_FILE_PATH="$PWD/.aws/credentials"
CACHE_DIR="$PWD/.cache"
WINGCORE_PX_DEV_OPS_USER_CACHE_FILE_PATH="$CACHE_DIR/wingcore-devops-user.json"

mkdir -p "$PWD/.aws"
mkdir -p "$CACHE_DIR"

require_cmd() {
    if ! command -v "$1" >/dev/null 2>&1; then
        echo "❌ Required command not found: $1"
        echo "   $2"
        exit 1
    fi
}

require_cmd aws "Install the AWS CLI: https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html"
require_cmd docker "Install Docker Desktop or the docker CLI."
require_cmd jq "Install jq (e.g., 'brew install jq')."
require_cmd "$AUTH_METHOD" "Install the chosen auth tool, or pass -m to select another."

WINGCORE_PX_DEV_OPS_USER_ARN="arn:aws:iam::620535918125:role/GD-AWS-USA-CPO-WingCorePX-Ops"
GODADDY_AWS="https://godaddy.okta.com/home/amazon_aws/0oakmpp1rkb2ZuZsQ0x7/272"

# ECR config — overridable via env, defaults match the Makefile
AWS_REGION="${AWS_REGION:-us-west-2}"
ECR_REGISTRY="${ECR_REGISTRY:-764525110978.dkr.ecr.${AWS_REGION}.amazonaws.com}"

echo "🔐 Setting up AWS credentials for mcp-adapter-initializer..."
echo "Using authentication method: $AUTH_METHOD"

# Function to perform OKTA authentication using aws-okta-processor
perform_okta_processor_auth() {
    echo "🔑 Performing OKTA authentication..."
    
    # Dynamically extract username with fallback methods
    WHOAMI_USER=""
    
    # Try extracting username from credential_process in AWS config
    if [ -f ~/.aws/config ]; then
        WHOAMI_USER=$(grep 'credential_process.*-u ' ~/.aws/config | head -1 | sed 's/.*-u \([^ ]*\).*/\1/' 2>/dev/null)
    fi
    
    # If that fails, try extracting from credentials file
    if [ -z "$WHOAMI_USER" ] && [ -f ~/.aws/credentials ]; then
        WHOAMI_USER=$(grep -m1 'user=' ~/.aws/credentials | sed 's/.*--user="\([^"]*\)".*/\1/' 2>/dev/null)
    fi
    
    # Try environment variable
    if [ -z "$WHOAMI_USER" ] && [ -n "$AWS_OKTA_USER" ]; then
        WHOAMI_USER="$AWS_OKTA_USER"
        echo "Using username from AWS_OKTA_USER environment variable: $WHOAMI_USER"
    fi
    
    # Try git config user.email (extract username part before @)
    # `|| true` so an unset value (git exits 1) doesn't trip `set -e`
    if [ -z "$WHOAMI_USER" ]; then
        GIT_EMAIL=$(git config user.email 2>/dev/null || true)
        if [ -n "$GIT_EMAIL" ]; then
            WHOAMI_USER=$(echo "$GIT_EMAIL" | cut -d'@' -f1)
            echo "Using username extracted from git config user.email: $WHOAMI_USER"
        fi
    fi
    
    # Try system username
    if [ -z "$WHOAMI_USER" ] && [ -n "$USER" ]; then
        WHOAMI_USER="$USER"
        echo "Using system username: $WHOAMI_USER"
    fi
    
    # Last resort: prompt user for input
    if [ -z "$WHOAMI_USER" ]; then
        echo "Could not automatically detect username."
        echo "Please enter your GoDaddy username (without @godaddy.com):"
        read -r WHOAMI_USER
        
        if [ -z "$WHOAMI_USER" ]; then
            echo "❌ No username provided. Exiting."
            exit 1
        fi
        
        echo "Tip: You can set the AWS_OKTA_USER environment variable to avoid this prompt:"
        echo "export AWS_OKTA_USER=\"$WHOAMI_USER\""
    fi
    
    echo "Using username: $WHOAMI_USER"
    
    wingcorePXDevOpsUser=$(aws-okta-processor authenticate \
        --user "${WHOAMI_USER}@godaddy.com" \
        --organization godaddy.okta.com \
        --application "$GODADDY_AWS" \
        --role="$WINGCORE_PX_DEV_OPS_USER_ARN" \
        --factor=token:hardware:yubico \
        --duration 12420)

    # aws-okta-processor may return either {"Credentials": {...}} or the flat credential_process format
    # ({AccessKeyId, SecretAccessKey, SessionToken, Expiration, Version}). Normalize to flat in a
    # single jq pass so invalid JSON fails loudly here instead of being misdiagnosed downstream.
    credentialsJson=$(echo "$wingcorePXDevOpsUser" | jq -e 'if has("Credentials") then .Credentials else . end')

    accessKeyId=$(echo "$credentialsJson" | jq -r '.AccessKeyId')
    accessKey=$(echo "$credentialsJson" | jq -r '.SecretAccessKey')
    sessionToken=$(echo "$credentialsJson" | jq -r '.SessionToken')

    if [ -z "$accessKeyId" ] || [ "$accessKeyId" = "null" ] \
       || [ -z "$accessKey" ] || [ "$accessKey" = "null" ] \
       || [ -z "$sessionToken" ] || [ "$sessionToken" = "null" ]; then
        echo "❌ Failed to assume role for ${WINGCORE_PX_DEV_OPS_USER_ARN}"
        exit 1
    fi

    umask 077
    echo "$credentialsJson" > "$WINGCORE_PX_DEV_OPS_USER_CACHE_FILE_PATH"
    chmod 600 "$WINGCORE_PX_DEV_OPS_USER_CACHE_FILE_PATH"
    echo "✅ OKTA authentication successful"
}

perform_oktaplz_auth() {
    echo "🔑 Performing OKTA authentication using oktaplz..."

    # Keep stderr out of the JSON payload — route it to a tempfile we surface only on failure
    local stderr_file
    stderr_file=$(mktemp)
    trap 'rm -f "$stderr_file"' RETURN

    if ! wingcorePXDevOpsUser=$(oktaplz aws --role "$WINGCORE_PX_DEV_OPS_USER_ARN" 2>"$stderr_file"); then
        echo "❌ Failed to authenticate using oktaplz"
        cat "$stderr_file" >&2
        exit 1
    fi

    if [ -z "$wingcorePXDevOpsUser" ]; then
        echo "❌ oktaplz returned empty response"
        cat "$stderr_file" >&2
        exit 1
    fi

    umask 077
    echo "$wingcorePXDevOpsUser" > "$WINGCORE_PX_DEV_OPS_USER_CACHE_FILE_PATH"
    chmod 600 "$WINGCORE_PX_DEV_OPS_USER_CACHE_FILE_PATH"
    echo "✅ oktaplz authentication successful"
}

# Perform authentication based on selected method
case "$AUTH_METHOD" in
    "oktaplz")
        perform_oktaplz_auth
        ;;
    "aws-okta-processor"|*)
        perform_okta_processor_auth
        ;;
esac

# Load WingCorePX DevOps credentials
wingcorePXDevOpsUser=$(cat "$WINGCORE_PX_DEV_OPS_USER_CACHE_FILE_PATH")
wingcorePXDevOpsUserAccessKeyId=$(echo "$wingcorePXDevOpsUser" | jq -r '.AccessKeyId')
wingcorePXDevOpsUserAccessKey=$(echo "$wingcorePXDevOpsUser" | jq -r '.SecretAccessKey')
wingcorePXDevOpsUserSessionToken=$(echo "$wingcorePXDevOpsUser" | jq -r '.SessionToken')
wingcorePXDevOpsUserExpiration=$(echo "$wingcorePXDevOpsUser" | jq -r '.Expiration')

for v in "$wingcorePXDevOpsUserAccessKeyId" "$wingcorePXDevOpsUserAccessKey" "$wingcorePXDevOpsUserSessionToken"; do
    if [ -z "$v" ] || [ "$v" = "null" ]; then
        echo "❌ Failed to parse WingCorePX DevOps credentials"
        exit 1
    fi
done

echo "✅ Loaded WingCorePX DevOps credentials"

echo "📝 Writing credentials to .aws/credentials..."
umask 077
cat > "$CREDENTIALS_FILE_PATH" << EOF
[default]
aws_access_key_id = ${wingcorePXDevOpsUserAccessKeyId}
aws_secret_access_key = ${wingcorePXDevOpsUserAccessKey}
aws_session_token = ${wingcorePXDevOpsUserSessionToken}
aws_expiration = ${wingcorePXDevOpsUserExpiration}
EOF
chmod 600 "$CREDENTIALS_FILE_PATH"

echo "🎉 AWS credentials configured successfully!"
echo "Expiration: $wingcorePXDevOpsUserExpiration"

echo "🐳 Logging into AWS ECR ($ECR_REGISTRY)..."
AWS_SHARED_CREDENTIALS_FILE="$CREDENTIALS_FILE_PATH" \
  aws ecr get-login-password --region "$AWS_REGION" | \
  docker login --username AWS --password-stdin "$ECR_REGISTRY"
echo "✅ ECR Docker login successful!"
