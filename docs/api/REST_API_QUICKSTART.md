# Chisimba REST API - Quick Start Guide

This guide documents how to use the Chisimba REST API endpoints that implement the field-guiding service contract.

## Prerequisites

- Docker and Docker Compose installed
- Chisimba containers running (`docker compose up -d`)

## Quick Start

### 1. Start Services

```bash
docker compose up -d
```

### 2. Verify Configuration

```bash
bash docker-init/04-verify-config.sh
```

### 3. Test API

```bash
bash framework/app/core_modules/restapi/tests/rest_api_full_test.sh
```

## API Endpoints

### Base URL
```
http://localhost:8080/api/v1
```

### 1. Get Abuse Evidence

**Required before login. Creates a session cookie.**

```bash
curl -c cookies.txt http://localhost:8080/api/v1/auth/token
```

**Response:**
```json
{
  "data": {
    "csrf_token": "...",
    "issued_at": 1234567890,
    "nonce": "...",
    "signature": "...",
    "minimum_seconds": 1,
    "maximum_seconds": 3600
  }
}
```

### 2. Login

**Must use the session cookie from step 1.**

```bash
# Extract values from step 1
CSRF="..."
ISSUED=...
NONCE="..."
SIG="..."

curl -b cookies.txt -X POST http://localhost:8080/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "a",
    "csrf_token": "'$CSRF'",
    "abuse_issued_at": '$ISSUED',
    "abuse_nonce": "'$NONCE'",
    "abuse_signature": "'$SIG'",
    "device_id": "mobile-app-001"
  }'
```

**Response:**
```json
{
  "data": {
    "user": {
      "id": "1",
      "username": "admin",
      "email": "",
      "fullname": "Site Administrator",
      "is_active": 1
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1Q...",
      "refresh_token": "eyJ0eXAiOiJKV1Q...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  }
}
```

### 3. Get User Profile

**Use the access_token from step 2.**

```bash
ACCESS_TOKEN="eyJ0eXAiOiJKV1Q..."

curl -H "Authorization: Bearer $ACCESS_TOKEN" \
  http://localhost:8080/api/v1/user/profile
```

**Response:**
```json
{
  "data": {
    "user": {
      "id": "1",
      "username": "admin",
      ...
    },
    "active_context": "",
    "context_grants": ["field:write", "Site Admin"],
    "roles": ["field:write", "Site Admin"],
    "scopes": ["field:write", "Site Admin"],
    "identity_epoch": 1,
    "account_active": true
  }
}
```

### 4. Refresh Token

**Use the refresh_token from step 2.**

```bash
REFRESH_TOKEN="eyJ0eXAiOiJKV1Q..."

curl -X POST http://localhost:8080/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "'$REFRESH_TOKEN'",
    "device_id": "mobile-app-001"
  }'
```

**Response:** Same as login response with NEW tokens.

**Important:** The old refresh token is now invalid!

### 5. Get Public Keys (JWKS)

**For verifying JWT tokens.**

```bash
curl http://localhost:8080/api/v1/auth/jwks
```

**Response:**
```json
{
  "keys": [
    {
      "kty": "RSA",
      "kid": "key-id",
      "use": "sig",
      "n": "...",
      "e": "AQAB"
    }
  ]
}
```

## Working with Tokens

### Token Types

1. **Access Token** (expires in 1 hour)
   - Used for API calls to /user/profile and other endpoints
   - Sent as `Authorization: Bearer <token>` header

2. **Refresh Token** (expires in 14 days)
   - Used to get new access token
   - Single-use only - always use the NEW refresh token returned
   - Binds to device_id for security

### Security Features

- **Abuse Protection**: CSRF token evidence required for login
- **Rate Limiting**: Failed attempts are rate-limited
- **Single-Use Refresh Tokens**: Prevents token replay attacks
- **Device Binding**: Tokens tied to device_id for revocation
- **Family Revocation**: Replay attack revokes all tokens for user+device

## Common Issues

### "Invalid credentials" with correct password

**Cause**: Session cookie not maintained between GET and POST requests.

**Solution**: Always use `-c cookies.txt` (save cookie) and `-b cookies.txt` (send cookie):

```bash
# Get evidence (creates session)
curl -c cookies.txt http://localhost:8080/api/v1/auth/token

# Login (uses same session)
curl -b cookies.txt -X POST ...
```

### "Invalid or expired refresh token"

**Cause**: Trying to reuse a refresh token.

**Solution**: Always use the NEW refresh token from the refresh response. Old tokens are immediately invalid after use.

### Testing Script Fails

**Cause**: Cookie extraction or session handling issue.

**Solution**: Use the provided test script:
```bash
bash framework/app/core_modules/restapi/tests/rest_api_full_test.sh
```

## Docker Initialization

### Fresh Install

When you run `docker compose up -d` for the first time, the initialization scripts will:

1. Create the database and user
2. Add `device_id` column to refresh tokens table
3. Set admin password to 'a'
4. Verify configuration

### Verify After Startup

```bash
bash docker-init/04-verify-config.sh
```

### Manual Re-initialization

If needed:
```bash
docker compose down -v  # Deletes volumes
docker compose up -d    # Fresh start
```

## Files Created/Modified

### Documentation
- `docker-init/README.md` - Docker initialization guide
- `framework/docs/api/REST_API_FIELD_GUIDING_SERVICE.md` - API documentation
- `framework/docs/api/REST_API_QUICKSTART.md` - This file

### Scripts
- `docker-init/01-init.sql` - Database creation
- `docker-init/02-schema-updates.sql` - Add device_id column
- `docker-init/03-admin-password.sql` - Set admin password
- `docker-init/04-verify-config.sh` - Configuration verification
- `framework/app/core_modules/restapi/tests/rest_api_full_test.sh` - Full test suite

### Core Implementation
- `framework/app/core_modules/restapi/controller.php` - REST API controller
- `framework/app/core_modules/security/classes/tokenauthservice_class_inc.php` - Token service
- `framework/app/core_modules/restapi/sql/tbl_rest_refresh_tokens.sql` - Schema

## Next Steps

1. Test the API with the mobile app
2. Implement field-guiding service integration using these tokens
3. Test token revocation and cleanup
4. Monitor for any issues in production

## Support

For issues or questions, check:
- API documentation: `framework/docs/api/REST_API_FIELD_GUIDING_SERVICE.md`
- Test suite: `framework/app/core_modules/restapi/tests/rest_api_full_test.sh`
- Docker init: `docker-init/README.md`