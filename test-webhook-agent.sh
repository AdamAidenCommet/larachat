#!/bin/bash

# Get the webhook URL and secret from environment or use defaults
WEBHOOK_URL="${WEBHOOK_URL:-http://localhost:8000/api/webhook}"
WEBHOOK_SECRET="${WEBHOOK_SECRET:-your-webhook-secret}"

echo "Testing webhook with agent parameter..."
echo "======================================="

# Test with agent slug
echo -e "\n1. Testing with agent slug:"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message with agent slug",
    "agent": "my-agent",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

# Test with agent ID (assuming agent ID 1 exists)
echo -e "\n2. Testing with agent ID:"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message with agent ID",
    "agent": 1,
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

# Test with invalid agent (should work without agent)
echo -e "\n3. Testing with invalid agent (should work without agent):"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message with invalid agent",
    "agent": "non-existent-agent",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

# Test without agent parameter
echo -e "\n4. Testing without agent parameter:"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message without agent",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

# Test with both agent and mode
echo -e "\n5. Testing with both agent and mode:"
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: $WEBHOOK_SECRET" \
  -d '{
    "message": "Test message with agent and mode",
    "agent": "my-agent",
    "mode": "code",
    "repository": "TestRepo"
  }' \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n======================================="
echo "Testing complete. Check the application logs for agent values."