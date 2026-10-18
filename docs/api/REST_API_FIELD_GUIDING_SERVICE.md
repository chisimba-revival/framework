# Chisimba REST API - Field Guiding Service Contract

This document describes the REST API endpoints required by the field-guiding service contract, as implemented in the Chisimba framework.

## Table of Contents

1. [Overview](#overview)
2. [Key Configuration Requirements](#key-configuration-requirements)
3. [Session Cookie Requirements](#session-cookie-requirements)
4. [Authentication Endpoints](#authentication-endpoints)
5. [Database Schema Updates](#database-schema-updates)
6. [Docker Initialization](#docker-initialization)

---

## Overview

The Chisimba REST API implements the authentication and user profile endpoints required by the field-guiding service contract. These endpoints allow mobile applications and external services to authenticate users and retrieve identity information.

### Endpoint Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/auth/token` | Get abuse protection evidence |
| POST | `/api/v1/auth/token` | Exchange credentials for tokens |
| POST | `/api/v1/auth/refresh` | Refresh access token |
| GET | `/api/v1/user/profile` | Get user profile with context |

---

## Key Configuration Requirements

### Environment Variables

The following environment variables must be set in the docker-compose configuration:

```yaml
environment:
  - CHISIMBA_JWT_SECRET=<64-character-hex-key>  # For JWT signing internal operations
  - CHISIMBA_SITE_KEY=<64-character-hex-key>    # For abuse protection HMAC signing
```

### JWT Key Path

The REST API uses RSA keys to sign JWT tokens. The private key path is configured in `docker-config/restapi_inc.php`:

```php
if (!defined("KEWL_JWKS_KEY_PATH")) {
    define("KEWL_JWKS_KEY_PATH", "/var/data/restapi/jwks_private.pem");
}
```

**Important**: The directory `/var/data/restapi/` must exist and be writable by the web server.

---

## Session Cookie Requirements

### ⚠️ CRITICAL: Session-Based CSRF Protection

The current implementation uses **PHP sessions** to store CSRF tokens. This means:

1. **Statefulness Required**: The REST API is NOT fully stateless. Each login flow requires:
   - A `GET /api/v1/auth/token` request to obtain CSRF evidence
   - A `POST /api/v1/auth/token` request that **must include the session cookie** from the GET request

2. **Cookie Handling**: Clients must implement cookie handling to maintain the session:

```bash
# Get evidence (creates session cookie)
curl -c cookies.txt http://localhost:8080/api/v1/auth/token

# Login (must use the same session cookie)
curl -b cookies.txt -X POST http://localhost:8080/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"a",...}'
```

3. **Mobile App Considerations**: Mobile applications must:
   - Store and send the `PHPSESSION` cookie between requests
   - OR use a custom CSRF mechanism that doesn't rely on sessions

### Alternative: True Stateless Authentication

For true stateless operation (as required by some mobile architectures), the API can be modified to:

1. Skip CSRF validation for the `/api/v1/auth/token` endpoint (security trade-off)
2. Store CSRF tokens in Redis or database instead of sessions
3. Use API keys or signed requests instead of cookies

**Current Behavior**: The API returns `401 Unauthorized` with "Invalid credentials" if the session cookie is not maintained, even if the password is correct.

---

## Authentication Endpoints

### 1. Get Abuse Evidence

```http
GET /api/v1/auth/token
```

**Response:**
```json
{
  "data": {
    "csrf_token": "ad7a871cfdc962bcfc3566470833e99256826ca10394e55c65901fabea494b8c",
    "issued_at": 1791318265,
    "nonce": "126ec171968b2f890eee9752080b2175",
    "signature": "d4f3377855f22de852fd0dd07b95cb04f7c3c221f315e4c112ef38edbb8c3540",
    "minimum_seconds": 1,
    "maximum_seconds": 3600
  },
  "meta": {
    "timestamp": "2026-10-06T20:24:25+00:00"
  }
}
```

**Fields:**
- `csrf_token`: Must be sent back in POST request (one-time use)
- `issued_at`: Unix timestamp - must wait `minimum_seconds` before POSTing
- `nonce`: Random value for signature verification
- `signature`: HMAC signature of the evidence

---

### 2. Exchange Credentials for Tokens

```http
POST /api/v1/auth/token
Content-Type: application/json
Cookie: PHPSESSION=<from-GET-request>

{
  "username": "admin",
  "password": "a",
  "csrf_token": "<csrf-from-GET>",
  "abuse_issued_at": 1791318265,
  "abuse_nonce": "<nonce-from-GET>",
  "abuse_signature": "<signature-from-GET>",
  "device_id": "mobile-app-001"
}
```

**Required Fields:**
- `username` (string, max 255)
- `password` (string)
- `csrf_token` (string, max 255) - from GET response
- `abuse_issued_at` (integer) - from GET response
- `abuse_nonce` (string, max 64) - from GET response
- `abuse_signature` (string, max 128) - from GET response

**Optional Fields:**
- `device_id` (string, max 255) - for device-specific token revocation
- `remember` (boolean) - extends refresh token TTL to 30 days
- `website` (string, max 255) - honeypot field, must be empty

**Success Response (201):**
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
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6IjI4YWQxYT...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6IjI4YWQxYT...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  },
  "meta": {
    "timestamp": "2026-10-06T20:48:02+00:00"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Invalid credentials (wrong password, no session cookie, or CSRF failure)
- `422 Unprocessable Entity`: Validation failed (missing required fields, invalid signature)
- `429 Too Many Requests`: Rate limit exceeded

**Token Structure:**
- `access_token`: JWT valid for 1 hour (3600 seconds)
- `refresh_token`: JWT valid for 14 days (can only be used once)
- Both tokens are RSA-signed (RS256) with the keys from `KEWL_JWKS_KEY_PATH`

---

### 3. Refresh Access Token

```http
POST /api/v1/auth/refresh
Content-Type: application/json

{
  "refresh_token": "<refresh-token>",
  "device_id": "mobile-app-001"
}
```

**Required Fields:**
- `refresh_token` (string) - from login or previous refresh
- `device_id` (string, max 255) - must match the device_id used at login

**Success Response (200):**
```json
{
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIs...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIs...",
    "token_type": "Bearer",
    "expires_in": 3600
  },
  "meta": {
    "timestamp": "2026-10-06T20:49:34+00:00"
  }
}
```

**Important Security Notes:**
- **Single-Use**: Each refresh token can only be used ONCE
- **Rotation**: Using a refresh token generates a NEW refresh token
- **Family Revocation**: If a refresh token is replayed (used twice), the entire token family for that user+device is revoked
- **Device Binding**: The `device_id` must match the device_id from the original login

---

### 4. Get User Profile

```http
GET /api/v1/user/profile
Authorization: Bearer <access_token>
```

**Success Response (200):**
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
    "active_context": "",
    "context_grants": ["field:write", "Site Admin"],
    "roles": ["field:write", "Site Admin"],
    "scopes": ["field:write", "Site Admin"],
    "identity_epoch": 1,
    "account_active": true
  },
  "meta": {
    "timestamp": "2026-10-06T20:48:49+00:00"
  }
}
```

**Fields:**
- `user`: Basic user identity information
- `active_context`: Current context code (empty if no active context)
- `context_grants`: Array of permission grants for the active context
- `roles`: User's role identifiers
- `scopes`: Effective access scopes
- `identity_epoch`: Incrementing version number for identity revocation
- `account_active`: Boolean indicating if account is active

**Cache Header:**
The response includes `Cache-Control: private, max-age=3600` - clients can cache for up to 1 hour.

---

## Database Schema Updates

### Refresh Tokens Table

The `tbl_rest_refresh_tokens` table requires a `device_id` column to support device-specific token revocation:

```sql
ALTER TABLE tbl_rest_refresh_tokens 
ADD COLUMN device_id VARCHAR(255) DEFAULT NULL AFTER revoked_at,
ADD INDEX idx_device_id (device_id),
ADD INDEX idx_user_device (user_id, device_id);
```

This is handled automatically by the docker initialization script.

---

## Docker Initialization

### Files Structure

```
docker-init/
├── 01-init.sql              # Database creation
├── 02-schema-updates.sql    # Schema modifications (device_id column)
├── 03-admin-password.sql    # Set admin password to 'a'
└── 04-verify-config.sh      # Configuration verification
```

### docker-compose.yml Integration

The initialization scripts are mounted to `/docker-entrypoint-initdb.d/` and run automatically when the database container starts:

```yaml
services:
  db:
    image: mysql:8.0
    volumes:
      - ./docker-init:/docker-entrypoint-initdb.d:ro
    environment:
      - MYSQL_DATABASE=chisimba
      - MYSQL_USER=chisimba
      - MYSQL_PASSWORD=chisimba
      - MYSQL_ROOT_PASSWORD=rootpass
```

### Initialization Scripts

1. **01-init.sql**: Creates the database and user (already exists)
2. **02-schema-updates.sql**: Adds device_id column to refresh tokens table
3. **03-admin-password.sql**: Sets admin password to bcrypt hash of 'a'
4. **04-verify-config.sh**: Verifies all configuration is correct

### Manual Initialization (if needed)

If the automatic initialization doesn't run, you can initialize manually:

```bash
# 1. Add device_id column
docker exec chisimba-web mysql -h db -u chisimba -pchisimba chisimba -e "
ALTER TABLE tbl_rest_refresh_tokens 
ADD COLUMN device_id VARCHAR(255) DEFAULT NULL AFTER revoked_at,
ADD INDEX idx_device_id (device_id),
ADD INDEX idx_user_device (user_id, device_id);
"

# 2. Set admin password
NEW_HASH=$(php -r "echo password_hash('a', PASSWORD_BCRYPT, ['cost' => 12]);")
docker exec chisimba-web mysql -h db -u chisimba -pchisimba chisimba -e "
UPDATE tbl_users SET pass='$NEW_HASH' WHERE username='admin';
"
```

---

## Testing the API

A complete test script is available at:
`/mnt/d/Projects/chisimba/framework/app/core_modules/restapi/tests/rest_api_full_contract_test.sh`

Run it with:
```bash
bash framework/app/core_modules/restapi/tests/rest_api_full_contract_test.sh
```

---

## Security Considerations

1. **HTTPS Only**: In production, all API endpoints must be served over HTTPS
2. **Session Security**: Session cookies should be marked Secure and HttpOnly
3. **Token Storage**: Access tokens should be stored securely (e.g., iOS Keychain, Android Keystore)
4. **Refresh Token Security**: Refresh tokens are long-lived and must be protected
5. **Rate Limiting**: The API implements rate limiting and abuse protection
6. **Device Binding**: Tokens are bound to device_id for device-specific revocation

---

## Troubleshooting

### "Invalid credentials" with correct password

**Cause**: Session cookie not maintained between GET and POST requests.

**Solution**: Use curl with `-c` (save cookies) and `-b` (send cookies) options, or implement cookie handling in your client.

### "Invalid or expired refresh token"

**Cause**: Trying to reuse a refresh token (they are single-use).

**Solution**: Always use the NEW refresh token returned by the refresh endpoint.

### "Access token invalid" after refresh

**Cause**: Token clock skew or epoch mismatch.

**Solution**: Ensure server time is synchronized (NTP) and check that the identity epoch hasn't been incremented.
