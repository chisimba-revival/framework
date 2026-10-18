# Field Service Integration Documentation

## Overview

This document describes the integration between Chisimba REST API and the Field Service microservice, including the authentication flow, token validation, and end-to-end testing.

## Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Mobile App    │◄──►│  Chisimba API    │◄──►│  Field Service  │
│   (React Native)│    │  (PHP/REST)      │    │  (Go/HTTP)      │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
  ┌──────────────┐      ┌──────────────┐      ┌──────────────┐
  │   Local DB   │      │   MySQL DB   │      │ PostgreSQL   │
  │  (SQLite)    │      │  (Chisimba)  │      │ + PostGIS    │
  └──────────────┘      └──────────────┘      └──────────────┘
```

## Authentication Flow

### 1. Token Issuance (Chisimba)

The mobile app authenticates with Chisimba to get JWT tokens:

```http
POST /api/v1/auth/token
Content-Type: application/json

{
  "username": "field.guide",
  "password": "password123",
  "abuse_nonce": "1230430e49302622dd267dec4bccf93d",
  "csrf_token": "9241fb260c8fba1d9e87bc754460259aee8bdaa5f67f4ad9f8dd8a5345f6e2c6",
  "abuse_issued_at": "1791320725",
  "abuse_signature": "9002d5d670be89331dc5dea1b9543e09bf8ca6eca4addcc40a8f7357934af1fb",
  "device_id": "mobile-device-uuid"
}
```

**Response:**
```json
{
  "data": {
    "user": {
      "id": "1",
      "username": "field.guide",
      "email": "guide@example.com",
      "fullname": "Field Guide",
      "is_active": 1
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIs...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIs...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  }
}
```

### 2. Token Validation (Field Service)

The field-service validates tokens using Chisimba's JWKS endpoint:

```
Field Service JWKS URL: http://web/api/v1/auth/jwks
```

The token contains:
- `iss`: "chisimba" (issuer)
- `aud`: "chisimba-api" (audience)
- `sub`: User ID
- `ctx`: Active context (e.g., "FGASA-2024-C3")
- `ctxs`: Context grants array
- `scope`: Effective scopes
- `roles`: Role names

### 3. API Calls (Field Service)

Mobile app uses the token to call field-service endpoints:

```http
POST /api/v1/sync/push
Authorization: Bearer <access_token>
Content-Type: application/json

{
  "operations": [{
    "operation_id": "op-001",
    "entity": "log_book_entry",
    "kind": "create",
    "entity_id": "entry-001",
    "captured_at": "2026-10-06T21:00:00Z",
    "recorded_at": "2026-10-06T21:05:00Z",
    "payload": {"species": "lion", "count": 2}
  }]
}
```

## Integration Testing

### Test Results

| Component | Status | Details |
|-----------|--------|---------|
| **JWKS Endpoint** | ✅ Working | Returns valid RSA public key |
| **Token Issuance** | ✅ Working | Issues access + refresh tokens |
| **Token Validation** | ✅ Working | Field-service validates tokens correctly |
| **Sync Push** | ⚠️ Partial | Works but needs active context |
| **Sync Pull** | ⚠️ Partial | Works but needs active context |

### Test Commands

#### 1. Get Abuse Evidence
```bash
curl -c /tmp/cookies.txt -b /tmp/cookies.txt http://localhost:8080/api/v1/auth/token
```

#### 2. Login and Get Tokens
```bash
curl -c /tmp/cookies.txt -b /tmp/cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "a",
    "abuse_nonce": "1230430e49302622dd267dec4bccf93d",
    "csrf_token": "9241fb260c8fba1d9e87bc754460259aee8bdaa5f67f4ad9f8dd8a5345f6e2c6",
    "abuse_issued_at": "1791320725",
    "abuse_signature": "9002d5d670be89331dc5dea1b9543e09bf8ca6eca4addcc40a8f7357934af1fb",
    "device_id": "integration-test-device"
  }' \
  http://localhost:8080/api/v1/auth/token
```

#### 3. Test Field Service with Token
```bash
ACCESS_TOKEN="<access_token_from_step_2>"

curl -X POST -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"operations":[]}' \
  http://localhost:8081/api/v1/sync/push
```

## Configuration

### Chisimba Configuration

```yaml
# docker-compose.yml
web:
  environment:
    MYSQL_HOST: db
    MYSQL_DATABASE: chisimba
    MYSQL_USER: chisimba
    MYSQL_PASSWORD: chisimba
    REST_TOKEN_TTL: 3600
    REST_REFRESH_TTL: 1209600
```

### Field Service Configuration

```yaml
# docker-compose.yml
field-service:
  environment:
    FIELDSVC_ISSUER: chisimba
    FIELDSVC_AUDIENCE: chisimba-api
    FIELDSVC_JWKS_URL: http://web/api/v1/auth/jwks
    FIELDSVC_DATABASE_URL: postgres://fieldapp:fieldsvc@field-service-postgis-1:5432/fieldlog?sslmode=disable
    FIELDSVC_LISTEN: :8080
```

## Context Management

### Current State

The admin user has:
- **Context Grants**: `["field:write", "Site Admin"]` (role names)
- **Active Context**: `""` (empty)
- **Roles**: `["field:write", "Site Admin"]`

### Issue

The field-service requires an active context (`ctx` claim) for push/pull operations, but the admin user doesn't have one set. The context grants contain role names rather than actual context codes.

### Required for Production

1. **Context Assignment**: Users need to be assigned to actual contexts (e.g., "FGASA-2024-C3")
2. **Active Context**: Users need an active context set in their profile
3. **Context Codes**: The `ctxs` array should contain context codes, not role names

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| `no bearer token presented` | Missing Authorization header | Include `Authorization: Bearer <token>` |
| `token carries no active context` | `ctx` claim is empty | Set active context for user |
| `unreadable_body` | Invalid JSON or wrong field names | Check request format |
| `no_operations` | Empty operations array | Provide at least one operation |

## Security Considerations

### Token Security
- Access tokens expire in 1 hour (3600 seconds)
- Refresh tokens expire in 14 days (1209600 seconds)
- Refresh tokens are single-use and rotated on refresh
- Device binding prevents token theft across devices

### Context Isolation
- Field-service enforces context boundaries through PostgreSQL RLS
- Users can only read/write within their granted contexts
- The `app.context_grants` setting controls visibility

### Audit Trail
- All operations are recorded with outcomes
- Replay attempts return original recorded outcomes
- Conflicts are refused, never merged

## Mobile App Integration

### Token Storage
Store tokens securely on the device:
- Access token: Short-lived, use for API calls
- Refresh token: Long-lived, use for token renewal
- Device ID: Unique identifier for the device

### Token Refresh
When the access token expires:

```http
POST /api/v1/auth/refresh
Content-Type: application/json

{
  "refresh_token": "<refresh_token>",
  "device_id": "<same_device_id>"
}
```

### Context Selection
After login, the mobile app should:
1. Fetch available contexts from `/api/v1/user/profile`
2. Allow user to select active context
3. Store selected context in secure storage
4. Use context in subsequent API calls

## Troubleshooting

### Field Service Not Validating Tokens

1. Check JWKS URL is accessible from field-service container:
   ```bash
   docker exec field-service-1 curl http://web/api/v1/auth/jwks
   ```

2. Verify issuer and audience match:
   ```bash
   docker exec field-service-1 env | grep FIELDSVC
   ```

3. Check field-service logs:
   ```bash
   docker logs field-service-1
   ```

### Empty Active Context

1. Check user's context assignments in database
2. Verify context codes are being set correctly
3. Ensure user has an active context selected

### Database Issues

1. Check field-service database connection:
   ```bash
   docker exec field-service-1 pg_isready -h field-service-postgis-1 -U fieldapp
   ```

2. Verify PostGIS extensions are installed:
   ```sql
   SELECT PostGIS_Version();
   ```

## Production Deployment

### Environment Variables

```bash
# Chisimba
REST_TOKEN_TTL=3600
REST_REFRESH_TTL=1209600
REST_ISSUER=chisimba
REST_AUDIENCE=chisimba-api

# Field Service
FIELDSVC_ISSUER=chisimba
FIELDSVC_AUDIENCE=chisimba-api
FIELDSVC_JWKS_URL=https://your-chisimba-domain.com/api/v1/auth/jwks
FIELDSVC_DATABASE_URL=postgres://user:pass@host:5432/db
```

### SSL/TLS

For production:
1. Use HTTPS for all endpoints
2. Configure proper certificate validation
3. Use secure JWKS URLs
4. Enable HSTS headers

### Monitoring

Monitor these metrics:
- Token issuance rate
- Token validation failures
- Sync operation success rates
- Database connection pool status
- JWKS cache hit rates

## API Reference

### Chisimba Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/auth/token` | Get abuse evidence |
| `POST` | `/api/v1/auth/token` | Login and get tokens |
| `GET` | `/api/v1/user/profile` | Get user profile with context |
| `POST` | `/api/v1/auth/refresh` | Refresh access token |
| `GET` | `/api/v1/auth/jwks` | JWKS public keys |

### Field Service Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/sync/push` | Push operations (requires auth) |
| `POST` | `/api/v1/sync/pull` | Pull changes (requires auth) |
| `GET` | `/api/v1/outings` | List outings (requires auth) |
| `POST` | `/api/v1/signoffs` | Submit sign-off (requires auth) |
| `GET` | `/health` | Health check |

## Next Steps

1. **Context Management**: Implement proper context assignment for users
2. **Media Endpoints**: Add `/api/v1/media/upload` and download endpoints
3. **Webhook Delivery**: Implement webhook system for real-time updates
4. **Offline Support**: Implement offline operation queue and conflict resolution
5. **Performance**: Add caching and optimize database queries

---

*Last updated: October 6, 2026*
*Integration tested with Chisimba 5.2.0 and Field Service v1.0.0*