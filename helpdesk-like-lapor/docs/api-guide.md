# API Guide for Helpdesk/LAPOR-like System

This document provides detailed information about the API endpoints available in the helpdesk/complaint management system.

## Table of Contents
- [Authentication](#authentication)
- [Users](#users)
- [Agencies](#agencies)
- [Complaint Categories](#complaint-categories)
- [Complaints](#complaints)
- [Comments](#comments)
- [Follow-Ups](#follow-ups)
- [Transfers](#transfers)
- [Ratings](#ratings)

## Base URL

All URLs referenced in the documentation have the following base:

```
http://localhost:8000/api
```

## Authentication

The API uses Laravel Sanctum for token-based authentication. Include the token in the `Authorization` header for authenticated endpoints.

### Register

Creates a new user account.

```
POST /register
```

**Request Body:**
```json
{
  "name": "User Full Name",
  "username": "username123",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "Reporter" // Options: "Admin", "Agency Manager", "Reporter"
}
```

**Response (201 Created):**
```json
{
  "message": "User registered successfully"
}
```

### Login

Authenticates a user and returns an API token.

```
POST /login
```

**Request Body:**
```json
{
  "login": "username123", // Can be username or email
  "password": "password123",
  "device_name": "postman" // Identifies the device or application
}
```

**Response (200 OK):**
```json
{
  "token": "1|laravel_sanctum_token_string..."
}
```

### Get Current User

Returns information about the authenticated user.

```
GET /user
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "id": "uuid-string",
  "name": "User Full Name",
  "username": "username123",
  "email": "user@example.com",
  "role": "Reporter",
  "agency_id": null,
  "created_at": "2023-01-01T12:00:00.000000Z",
  "updated_at": "2023-01-01T12:00:00.000000Z",
  "agency": null // Will include agency details if user has one
}
```

### Logout

Invalidates the current user's token.

```
POST /logout
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

## Users

User management endpoints (Admin only).

### List Users

Returns a paginated list of users.

```
GET /users
```

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
role=Admin - Filter by role (Admin, Agency Manager, Reporter)
search=term - Search in name, username, email
per_page=15 - Number of results per page
page=1 - Page number
sort_by=name - Field to sort by (name, username, email, role, created_at)
sort_dir=asc - Sort direction (asc, desc)
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": "uuid-string",
      "name": "User Full Name",
      "username": "username123",
      "email": "user@example.com",
      "role": "Reporter",
      "agency": null // Agency details if applicable
    },
    // More users...
  ],
  "links": {
    "first": "http://localhost:8000/api/users?page=1",
    "last": "http://localhost:8000/api/users?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/users?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "links": [...],
    "path": "http://localhost:8000/api/users",
    "per_page": 15,
    "to": 15,
    "total": 68
  }
}
```

### Create User

Creates a new user.

```
POST /users
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "New User",
  "username": "newuser",
  "email": "newuser@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "Agency Manager",
  "agency_id": "agency-uuid" // Required for Agency Manager, optional for others
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": "new-user-uuid",
    "name": "New User",
    "username": "newuser",
    "email": "newuser@example.com",
    "role": "Agency Manager",
    "agency": {
      "id": "agency-uuid",
      "name": "Agency Name"
    }
  }
}
```

### View User

Returns details about a specific user.

```
GET /users/{user_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "uuid-string",
    "name": "User Full Name",
    "username": "username123",
    "email": "user@example.com",
    "role": "Reporter",
    "agency": null
  }
}
```

### Update User

Updates a user's information.

```
PUT /users/{user_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Updated Name",
  "role": "Agency Manager",
  "agency_id": "agency-uuid",
  "password": "newpassword", // Optional
  "password_confirmation": "newpassword" // Required if password provided
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "uuid-string",
    "name": "Updated Name",
    "username": "username123",
    "email": "user@example.com",
    "role": "Agency Manager",
    "agency": {
      "id": "agency-uuid",
      "name": "Agency Name"
    }
  }
}
```

### Delete User

Deletes a user.

```
DELETE /users/{user_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204 No Content)**

## Agencies

Agency management endpoints.

### List Agencies

Returns a list of all agencies.

```
GET /agencies
```

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
top_level_only=true - Show only top-level agencies (no parent)
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": "agency-uuid",
      "name": "Agency Name",
      "address": "Agency Address",
      "email": "agency@example.com",
      "phone": "123-456-7890",
      "parent_id": null
    },
    // More agencies...
  ]
}
```

### Create Agency

Creates a new agency (Admin only).

```
POST /agencies
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "New Agency",
  "address": "123 Main St",
  "email": "agency@example.com",
  "phone": "123-456-7890",
  "parent_id": "parent-agency-uuid" // Optional, for creating sub-agencies
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": "new-agency-uuid",
    "name": "New Agency",
    "address": "123 Main St",
    "email": "agency@example.com",
    "phone": "123-456-7890",
    "parent_id": "parent-agency-uuid"
  }
}
```

### View Agency

Returns details about a specific agency.

```
GET /agencies/{agency_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
include_parent=true - Include parent agency details
include_children=true - Include child agencies
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "agency-uuid",
    "name": "Agency Name",
    "address": "Agency Address",
    "email": "agency@example.com",
    "phone": "123-456-7890",
    "parent_id": "parent-agency-uuid",
    "parent_agency": {
      "id": "parent-agency-uuid",
      "name": "Parent Agency Name"
    },
    "child_agencies": [
      {
        "id": "child-agency-uuid",
        "name": "Child Agency Name"
      }
    ]
  }
}
```

### Update Agency

Updates an agency's information (Admin only).

```
PUT /agencies/{agency_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Updated Agency Name",
  "email": "updated@example.com",
  "parent_id": "new-parent-uuid" // Use null to make it a top-level agency
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "agency-uuid",
    "name": "Updated Agency Name",
    "address": "Agency Address",
    "email": "updated@example.com",
    "phone": "123-456-7890",
    "parent_id": "new-parent-uuid"
  }
}
```

### Delete Agency

Deletes an agency (Admin only).

```
DELETE /agencies/{agency_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204 No Content)** or **(409 Conflict)** if agency has users, complaints, or sub-agencies.

## Complaint Categories

Complaint category management endpoints.

### List Categories

Returns a list of all complaint categories (Admin and Agency Manager only).

```
GET /complaint-categories
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": "category-uuid",
      "name": "Infrastructure",
      "description": "Issues related to public infrastructure"
    },
    // More categories...
  ]
}
```

### Create Category

Creates a new complaint category (Admin only).

```
POST /complaint-categories
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "New Category",
  "description": "Description of the new category"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": "new-category-uuid",
    "name": "New Category",
    "description": "Description of the new category"
  }
}
```

### View Category

Returns details about a specific category (Admin and Agency Manager only).

```
GET /complaint-categories/{category_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "category-uuid",
    "name": "Infrastructure",
    "description": "Issues related to public infrastructure"
  }
}
```

### Update Category

Updates a category's information (Admin only).

```
PUT /complaint-categories/{category_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Updated Category Name",
  "description": "Updated category description"
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "category-uuid",
    "name": "Updated Category Name",
    "description": "Updated category description"
  }
}
```

### Delete Category

Deletes a category (Admin only).

```
DELETE /complaint-categories/{category_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204 No Content)** or **(409 Conflict)** if the category is in use by complaints.

## Complaints

Complaint management endpoints.

### List Complaints

Returns a paginated list of complaints based on user role.

```
GET /complaints
```

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
status=Pending - Filter by status (Unprocessed, Pending, In Progress, Resolved, Archived)
priority=High - Filter by priority (Low, Medium, High)
category_id=uuid - Filter by category
agency_id=uuid - Filter by agency (Admin only)
per_page=15 - Number of results per page
page=1 - Page number
sort_by=created_at - Field to sort by
sort_dir=desc - Sort direction (asc, desc)
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": "complaint-uuid",
      "title": "Broken Street Light",
      "description": "The street light at Main St. is not working",
      "status": "Pending",
      "priority": "Medium",
      "created_at": "2023-01-01T12:00:00.000000Z",
      "reporter_id": "user-uuid",
      "reporter_name": "John Doe",
      "assigned_agency_id": "agency-uuid",
      "assigned_agency_name": "Public Works Department",
      "category_id": "category-uuid",
      "category_name": "Infrastructure"
    },
    // More complaints...
  ],
  "links": {
    "first": "http://localhost:8000/api/complaints?page=1",
    "last": "http://localhost:8000/api/complaints?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/complaints?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "links": [...],
    "path": "http://localhost:8000/api/complaints",
    "per_page": 15,
    "to": 15,
    "total": 68
  }
}
```

### Create Complaint

Creates a new complaint (Reporter only).

```
POST /complaints
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Broken Street Light",
  "description": "The street light at Main St. has been out for 3 days",
  "category_id": "category-uuid",
  "priority": "Medium", // Optional: Low, Medium, High (defaults to Medium)
  "attachments": [] // Optional: Array of files (multipart form-data)
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": "new-complaint-uuid",
    "title": "Broken Street Light",
    "description": "The street light at Main St. has been out for 3 days",
    "status": "Unprocessed",
    "priority": "Medium",
    "created_at": "2023-01-01T12:00:00.000000Z",
    "reporter_id": "user-uuid",
    "reporter_name": "John Doe",
    "assigned_agency_id": null,
    "assigned_agency_name": null,
    "category_id": "category-uuid",
    "category_name": "Infrastructure",
    "attachments": [] // Will contain attachment details if provided
  }
}
```

### View Complaint

Returns details about a specific complaint.

```
GET /complaints/{complaint_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "complaint-uuid",
    "title": "Broken Street Light",
    "description": "The street light at Main St. has been out for 3 days",
    "status": "Pending",
    "priority": "Medium",
    "created_at": "2023-01-01T12:00:00.000000Z",
    "updated_at": "2023-01-01T13:00:00.000000Z",
    "user": {
      "id": "user-uuid",
      "name": "John Doe"
    },
    "agency": {
      "id": "agency-uuid",
      "name": "Public Works Department"
    },
    "category": {
      "id": "category-uuid",
      "name": "Infrastructure"
    },
    "attachments": [
      {
        "id": "attachment-uuid",
        "file_name": "photo.jpg",
        "url": "http://localhost:8000/storage/uploads/complaints/photo.jpg",
        "uploaded_at": "2023-01-01T12:00:00.000000Z"
      }
    ],
    "comments": [
      {
        "id": "comment-uuid",
        "message": "We have received your complaint",
        "created_at": "2023-01-01T12:30:00.000000Z",
        "user": {
          "id": "manager-uuid",
          "name": "Agency Manager",
          "role": "Agency Manager"
        }
      }
    ],
    "logs": [
      {
        "id": "log-uuid",
        "action": "Created",
        "details": null,
        "timestamp": "2023-01-01T12:00:00.000000Z",
        "user": {
          "id": "user-uuid",
          "name": "John Doe",
          "role": "Reporter"
        }
      }
    ]
  }
}
```

### Update Complaint

Updates a complaint's information (Admin and Agency Manager only).

```
PUT /complaints/{complaint_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "status": "In Progress",
  "priority": "High",
  "agency_id": "new-agency-uuid" // Admin only
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "complaint-uuid",
    "title": "Broken Street Light",
    "description": "The street light at Main St. has been out for 3 days",
    "status": "In Progress",
    "priority": "High",
    "created_at": "2023-01-01T12:00:00.000000Z",
    "updated_at": "2023-01-01T14:00:00.000000Z",
    "reporter_id": "user-uuid",
    "reporter_name": "John Doe",
    "assigned_agency_id": "new-agency-uuid",
    "assigned_agency_name": "Public Works Department",
    "category_id": "category-uuid",
    "category_name": "Infrastructure"
  }
}
```

### Delete Complaint

Deletes a complaint (Admin only).

```
DELETE /complaints/{complaint_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204 No Content)**

## Comments

Comment management endpoints.

### Add Comment to Complaint

Adds a comment to a complaint.

```
POST /complaints/{complaint_id}/comments
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "message": "We are looking into this issue. An engineer will be dispatched tomorrow."
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": "comment-uuid",
    "message": "We are looking into this issue. An engineer will be dispatched tomorrow.",
    "created_at": "2023-01-01T14:30:00.000000Z",
    "user": {
      "id": "user-uuid",
      "name": "Agency Manager",
      "role": "Agency Manager"
    }
  }
}
```

### Delete Comment

Deletes a comment (Admin can delete any, users can only delete their own).

```
DELETE /comments/{comment_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204 No Content)**

## Follow-Ups

Follow-up management endpoints.

### Add Follow-Up to Complaint

Adds a follow-up to a complaint (Admin and assigned Agency Manager only).

```
POST /complaints/{complaint_id}/follow-ups
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "description": "Contacted the electrical department. They will send a team to fix the light tomorrow."
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": "follow-up-uuid",
    "description": "Contacted the electrical department. They will send a team to fix the light tomorrow.",
    "created_at": "2023-01-01T15:00:00.000000Z",
    "user": {
      "id": "user-uuid",
      "name": "Agency Manager",
      "role": "Agency Manager"
    },
    "agency_id": "agency-uuid"
  }
}
```

### Delete Follow-Up

Deletes a follow-up (Admin only).

```
DELETE /complaint-follow-ups/{follow_up_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204 No Content)**

## Transfers

Complaint transfer endpoints.

### Transfer Complaint

Transfers a complaint to another agency (Admin only).

```
POST /complaints/{complaint_id}/transfer
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "to_agency_id": "target-agency-uuid",
  "reason": "This is a maintenance issue that should be handled by the Maintenance Department"
}
```

**Response (200 OK):**
```json
{
  "id": "complaint-uuid",
  "title": "Broken Street Light",
  "assigned_agency_id": "target-agency-uuid",
  "assigned_agency_name": "Maintenance Department",
  "status": "Pending",
  "agency": {
    "id": "target-agency-uuid",
    "name": "Maintenance Department"
  }
}
```

## Ratings

Agency rating endpoints.

### List Ratings

Returns a paginated list of ratings (Admin only).

```
GET /ratings
```

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
agency_id=uuid - Filter by agency
stars=5 - Filter by rating (1-5)
per_page=15 - Number of results per page
page=1 - Page number
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": "rating-uuid",
      "stars": 5,
      "review": "Great service! Fixed within 24 hours.",
      "created_at": "2023-01-03T12:00:00.000000Z",
      "user": {
        "id": "user-uuid",
        "name": "John Doe"
      },
      "agency": {
        "id": "agency-uuid",
        "name": "Public Works Department"
      },
      "complaint_id": "complaint-uuid"
    },
    // More ratings...
  ],
  "links": { /* pagination links */ },
  "meta": { /* pagination metadata */ }
}
```

### Create Rating

Adds a rating for a resolved complaint (Reporter only).

```
POST /ratings
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "complaint_id": "resolved-complaint-uuid",
  "stars": 5, // 1-5
  "review": "Excellent service. The issue was resolved quickly and professionally."
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": "rating-uuid",
    "stars": 5,
    "review": "Excellent service. The issue was resolved quickly and professionally.",
    "created_at": "2023-01-03T12:00:00.000000Z",
    "user": {
      "id": "user-uuid",
      "name": "John Doe"
    },
    "agency": {
      "id": "agency-uuid",
      "name": "Public Works Department"
    },
    "complaint_id": "resolved-complaint-uuid"
  }
}
```

### View Rating

Returns details about a specific rating (Admin, rating owner, and rated agency's manager).

```
GET /ratings/{rating_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": "rating-uuid",
    "stars": 5,
    "review": "Excellent service. The issue was resolved quickly and professionally.",
    "created_at": "2023-01-03T12:00:00.000000Z",
    "user": {
      "id": "user-uuid",
      "name": "John Doe"
    },
    "agency": {
      "id": "agency-uuid",
      "name": "Public Works Department"
    },
    "complaint_id": "resolved-complaint-uuid"
  }
}
```

### Delete Rating

Deletes a rating (Admin only).

```
DELETE /ratings/{rating_id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204 No Content)**