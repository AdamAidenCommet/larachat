#!/bin/bash

# Get the webhook URL and secret from environment or use defaults
WEBHOOK_URL="${WEBHOOK_URL:-http://localhost:8000/api/webhook}"
WEBHOOK_SECRET="${WEBHOOK_SECRET:-your-webhook-secret}"

echo "Testing webhook with different modes..."
echo "======================================="

# Test with 'ask' mode
echo -e "\n1. Testing with mode='ask' (should convert to 'plan'):"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message with ask mode",
    "mode": "ask",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

# Test with 'plan' mode
echo -e "\n2. Testing with mode='plan' (should stay as 'plan'):"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message with plan mode",
    "mode": "plan",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

# Test with 'code' mode
echo -e "\n3. Testing with mode='code' (should convert to 'bypassPermissions'):"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message with code mode",
    "mode": "code",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

# Test without mode (should default to 'plan')
echo -e "\n4. Testing without mode (should default to 'plan'):"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message without mode",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

# Test with invalid mode (should default to 'plan' with warning)
echo -e "\n5. Testing with invalid mode (should default to 'plan'):"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message with invalid mode",
    "mode": "invalid",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n======================================="
echo "Testing complete. Check the application logs for mode values."