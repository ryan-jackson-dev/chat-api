# API Routes

All routes are currently public. For security (authentication and authorization) and other design rationale, please see the [README](README.md).


## General Notes

- All responses use `application/json` content type.
- ID Formats:
  - `groupId`: UUID7 (36 characters)
  - `userId`: UUID4 (36 characters)
  - `messageId`: Auto-incrementing integer
- All routes should use a rate-limiter in production (see [README](README.md) for details).


## Endpoints

### `GET /`

Returns a simple test application to exercise the API.

---

### `GET /status`

Health check endpoint. Confirms the API is running.

**Response (200 OK)**
```json
{
  "status": "Chat API is running.",
  "version": "0.1.0"
}
```

---

### `GET /users`

List all users.

**Request Parameters**
- `limit`: 1000 // Default, Maximum
- `offset`: 0 // Default, Minimum

---

### `POST /users`

Create a user.

---

### `GET /groups`

List all groups.

**Request Parameters**
- `limit`: 1000 // Default, Maximum
- `offset`: 0 // Default, Minimum

**Response (200 OK)**
```json
{
  "groups": [
    {
      "groupId": "550e8400-e29b-41d4-a716-446655440000",
      "groupName": "Vocal Trance",
      "groupOwnerId": "123e4567-e89b-12d3-a456-426614174000",
      "groupOwnerName": "DJ Doboy"
    }
  ]
}
```

---

### `POST /groups`

Create a new group. User becomes group owner and initial member.

**Request Body**
```json
{
  "groupName": "Vocal Trance",
  "userId": "123e4567-e89b-12d3-a456-426614174000"
}
```

**Response (201 Created)**
```json
{
  "groupId": "550e8400-e29b-41d4-a716-446655440000",
  "groupName": "Vocal Trance"
}
```

---

### `GET /groups/{groupId}/users`

List all members in a group.

**Parameters**
- `groupId` (path): UUID7 string (36 characters)

**Request Parameters**
- `limit`: 1000 // Default, Maximum
- `offset`: 0 // Default, Minimum

**Response (200 OK)**
```json
{
  "groupId": "550e8400-e29b-41d4-a716-446655440000",
  "groupUsers": [
    {
      "userId": "123e4567-e89b-12d3-a456-426614174000",
      "userName": "CJ Stone"
    }
  ]
}
```

---

### `POST /groups/{groupId}/users`

Add a user to a group (join).

**Parameters**
- `groupId` (path): UUID7 string (36 characters)

**Request Body**
```json
{
  "userId": "123e4567-e89b-12d3-a456-426614174000"
}
```

**Response (200 OK)**
```json
{
  "groupId": "550e8400-e29b-41d4-a716-446655440000",
  "userId": "123e4567-e89b-12d3-a456-426614174000",
  "joined": true
}
```

---

### `GET /groups/{groupId}/messages`

Retrieve all messages in a group, ordered by most recent first.

**Parameters**
- `groupId` (path): UUID7 string (36 characters)

**Request Parameters**
- `limit`: 1000 // Default, Maximum
- `offset`: 0 // Default, Minimum

**Response (200 OK)**
```json
{
  "groupId": "550e8400-e29b-41d4-a716-446655440000",
  "groupMessages": [
    {
      "messageId": 42,
      "userId": "123e4567-e89b-12d3-a456-426614174000",
      "content": "The Sun (Goes Down)",
      "createdAt": "2026-05-04T10:35:00"
    }
  ]
}
```

---

### `POST /groups/{groupId}/messages`

Send a message to a group.

**Parameters**
- `groupId` (path): UUID7 string (36 characters)

**Request Body**
```json
{
  "content": "Into the Sea",
  "userId": "123e4567-e89b-12d3-a456-426614174000"
}
```

**Response (201 Created)**
```json
{
  "messageId": 42
}
```

---
