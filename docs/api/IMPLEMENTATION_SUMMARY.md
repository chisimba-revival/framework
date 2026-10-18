# Chisimba REST API - Implementation Summary

## What Was Implemented

This document summarizes the implementation of the field-guiding service contract REST API endpoints in the Chisimba framework.

## Implementation Date

October 6, 2026

## Files Modified/Created

### Core API Controller
- **`framework/app/core_modules/restapi/controller.php`**
  - Implemented `GET_auth_token()` - Returns abuse evidence (CSRF token, issued_at, nonce, signature)
  - Implemented `POST_auth_token()` - Exchanges credentials for access + refresh JWT tokens
  - Implemented `POST_auth_refresh()` - Rotates refresh token, returns new access + refresh pair
  - Implemented `GET_user_profile()` - Returns user identity with context grants, roles, scopes, epoch
  - Added backward compatibility methods: `GET_auth_login()`, `POST_auth_login()`, `GET_auth_me()`
  - Added comprehensive input validation using `validate()` method

### Authentication Service
- **`framework/app/core_modules/security/classes/tokenauthservice_class_inc.php`**
  - Added `device_id` parameter to `issueTokens()`
  - Implemented `refreshAccessToken(string $refreshToken, string $deviceId)` with replay attack detection
  - Added `revokeAllUserDeviceTokens(string $userId, string $deviceId)` for family revocation
  - JWT tokens now include `device_id` in claims when provided

### Database Schema
- **`framework/app/core_modules/restapi/sql/tbl_rest_refresh_tokens.sql`**
  - Added `device_id` column (varchar(255))
  - Added indexes: `idx_device_id`, `idx_user_device`

### Docker Initialization
- **`docker-init/02-schema-updates.sql`** - Adds device_id column automatically
- **`docker-init/03-admin-password.sql`** - Sets admin password to 'a' for testing
- **`docker-init/04-verify-config.sh`** - Post-startup verification script
- **`docker-init/README.md`** - Documentation for initialization scripts

### Documentation
- **`framework/docs/api/REST_API_FIELD_GUIDING_SERVICE.md`** - Complete API documentation

## Working Endpoints

### ✅ GET /api/v1/auth/token
Returns CSRF evidence for login. Creates PHP session cookie.

**Response:**
```json
{
  "data": {
    "csrf_token": "...",
    "issued_at": "timestamp",
    "nonce": "...",
    "signature": "...",
    "minimum_seconds": 1,
    "maximum_seconds": 3600
  }
}
```

### ✅ POST /api/v1/auth/token
Exchanges username/password + CSRF evidence for JWT tokens.

**Required Fields:**
- username, password, csrf_token, abuse_issued_at, abuse_nonce, abuse_signature

**Optional Fields:**
- device_id, remember, website (honeypot)

**Response:**
```json
{
  "data": {
    "user": { "id": "1", "username": "admin", ... },
    "tokens": {
      "access_token": "eyJ...",
      "refresh_token": "eyJ...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  }
}
```

### ✅ POST /api/v1/auth/refresh
Rotates refresh token and returns new access + refresh pair.

**Required Fields:**
- refresh_token, device_id

**Security:** Refresh tokens are single-use. Rotation generates new pair.

### ✅ GET /api/v1/user/profile
Returns current user's profile with authentication details.

**Headers:** `Authorization: Bearer <access_token>`

**Response:**
```json
{
  "data": {
    "user": { "id": "1", "username": "admin", ... },
    "active_context": "",
    "context_grants": ["field:write", "Site Admin"],
    "roles": ["field:write", "Site Admin"],
    "scopes": ["field:write", "Site Admin"],
    "identity_epoch": 1,
    "account_active": true
  }
}
```

## Key Issues Encountered & Solutions

### 1. CSRF Token Validation Failure

**Problem:** Login returned "Invalid credentials" even with correct password.

**Root Cause:** CSRF tokens are stored in PHP session, but REST API requests didn't maintain the session cookie.

**Solution:** 
- Must use `curl -c cookies.txt` (save cookie) and `curl -b cookies.txt` (send cookie)
- Session cookie `PHPSESSION` must be maintained between GET and POST requests

### 2. Password Hash Mismatch

**Problem:** Admin password didn't verify even though hash looked correct.

**Root Cause:** Hash in database was not a valid hash for password 'a'.

**Solution:**
```bash
# Update password hash
NEW_HASH=$(php -r "echo password_hash('a', PASSWORD_BCRYPT, ['cost' => 12]);")
docker exec chisimba-web mysql -h db -u chisimba -pchisimba chisimba -e "
UPDATE tbl_users SET pass='$NEW_HASH' WHERE username='admin';
"
```

### 3. Missing device_id Column

**Problem:** Table `tbl_rest_refresh_tokens` was missing the device_id column.

**Root Cause:** Schema was updated in SQL file but not applied to existing database.

**Solution:**
```bash
docker exec chisimba-web mysql -h db -u chisimba -pchisimba chisimba -e "
ALTER TABLE tbl_rest_refresh_tokens 
ADD COLUMN device_id VARCHAR(255) DEFAULT NULL AFTER revoked_at,
ADD INDEX idx_device_id (device_id),
ADD INDEX idx_user_device (user_id, device_id);
"
```

## Testing

All endpoints have been tested and verified working:

```bash
# Complete test flow
1. GET http://localhost:8080/api/v1/auth/token (get evidence + cookie)
2. POST http://localhost:8080/api/v1/auth/token (login, save tokens)
3. GET http://localhost:8080/api/v1/user/profile (verify access token works)
4. POST http://localhost:8080/api/v1/auth/refresh (verify refresh token works)
```

See `framework/docs/api/REST_API_FIELD_GUIDING_SERVICE.md` for complete testing examples.

## Security Features Implemented

1. **Abuse Protection**: Form evidence with HMAC signature prevents automated attacks
2. **Rate Limiting**: Failed login attempts are rate-limited
3. **CSRF Protection**: Single-use CSRF tokens prevent CSRF attacks
4. **Refresh Token Rotation**: Each refresh token can only be used once
5. **Family Revocation**: Replaying an old refresh token revokes all tokens for user+device
6. **Device Binding**: Tokens are bound to device_id for device-specific revocation
7. **Password Security**: Bcrypt hashing with cost 12
8. **JWT Security**: RSA-signed tokens (RS256)

## Docker Compose Integration

The initialization scripts run automatically on fresh `docker compose up`:

```yaml
services:
  db:
    volumes:
      - ./docker-init:/docker-entrypoint-initdb.d:ro
```

After startup, verify configuration:
```bash
bash docker-init/04-verify-config.sh
```

## Known Limitations

1. **Stateful Sessions Required**: REST API requires session cookies for CSRF validation
   - Mobile apps must implement cookie handling
   - Future improvement: Redis/database CSRF storage for true statelessness

2. **Refresh Token Expiry**: Old refresh tokens are immediately invalid after use
   - Clients must handle token rotation correctly
   - Store only the latest refresh token

## Next Steps

1. **Mobile App Integration**: Document cookie handling requirements for mobile developers
2. **True Stateless Mode**: Consider Redis-backed CSRF tokens for better scalability
3. **API Versioning**: Add version headers for future compatibility
4. **OpenAPI Spec**: Generate OpenAPI 3.0 specification from implementation
5. **Testing**: Add integration tests to CI/CD pipeline

## References

- Field Guiding Service Contract: `framework/docs/architecture/field-guiding-service-contract.md`
- Field Guiding Service Design: `framework/docs/architecture/field-guiding-service-design.md`
- REST API Documentation: `framework/docs/api/REST_API_FIELD_GUIDING_SERVICE.md`
- Docker Init README: `docker-init/README.md`