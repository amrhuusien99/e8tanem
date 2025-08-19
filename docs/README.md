# اغتنم API Documentation

## Overview
اغتنم is a comprehensive learning platform API built with Laravel, providing endpoints for managing educational videos, podcasts, lessons, subjects, and user interactions including likes, comments, and notifications.

## Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Laravel 12.10+
- SQLite/MySQL
- Node.js and npm (for frontend assets)

### Installation
1. Clone the repository
2. Run `composer install`
3. Run `npm install`
4. Copy `.env.example` to `.env` and configure your database
5. Run `php artisan migrate`
6. Run `php artisan storage:link`
7. Run `php artisan serve`

# Clean all file types
php artisan storage:cleanup


## Authentication
The API uses Laravel Sanctum for authentication. Include the bearer token in the Authorization header:
```
Authorization: Bearer <your_token>
```

## API Endpoints

### Authentication

#### Register
```http
POST /api/register
```
**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```
**Response:** `201 Created`
```json
{
    "token": "1|laravel_sanctum_token",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

#### Login
```http
POST /api/login
```
**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```
**Response:**
```json
{
    "token": "1|laravel_sanctum_token",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

#### Logout
```http
POST /api/logout
```
**Response:**
```json
{
    "message": "Logged out successfully"
}
```

#### Get User Profile
```http
GET /api/user
```
**Response:**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
}
```

### Password Reset

#### Request Reset Code
```http
POST /api/forgot-password
```
**Request Body:**
```json
{
    "email": "john@example.com"
}
```
**Response:** `200 OK`
```json
{
    "message": "Verification code sent to your email"
}
```

#### Verify Reset Code
```http
POST /api/verify-reset-code
```
**Request Body:**
```json
{
    "email": "john@example.com",
    "code": "123456"
}
```
**Response:** `200 OK`
```json
{
    "message": "Code verified successfully",
    "reset_token": "valid-reset-token"
}
```

#### Reset Password
```http
POST /api/reset-password
```
**Request Body:**
```json
{
    "email": "john@example.com",
    "reset_token": "valid-reset-token",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```
**Response:** `200 OK`
```json
{
    "message": "Password reset successfully"
}
```

### Subjects & Lessons

#### List Subjects
```http
GET /api/subjects
```
**Response:** `200 OK`
```json
[
    {
        "id": 1,
        "name": "Mathematics",
        "description": "Learn advanced mathematics",
        "thumbnail": "path/to/thumbnail.jpg",
        "is_active": true,
        "lessons_count": 5,
        "created_at": "2025-04-24T10:00:00Z",
        "updated_at": "2025-04-24T10:00:00Z"
    }
]
```

#### Get Subject with Lessons
```http
GET /api/subjects/{id}
```
**Response:** `200 OK`
```json
{
    "id": 1,
    "name": "Mathematics",
    "description": "Learn advanced mathematics",
    "thumbnail": "path/to/thumbnail.jpg",
    "is_active": true,
    "lessons": [
        {
            "id": 1,
            "title": "Introduction to Calculus",
            "description": "Learn the basics of calculus",
            "video_url": "path/to/video.mp4",
            "thumbnail_url": "path/to/thumbnail.jpg",
            "duration": 3600,
            "order": 1,
            "views_count": 100
        }
    ]
}
```

#### Get Lesson Details
```http
GET /api/lessons/{id}
```
**Response:** `200 OK`
```json
{
    "id": 1,
    "title": "Introduction to Calculus",
    "description": "Learn the basics of calculus",
    "video_url": "path/to/video.mp4",
    "thumbnail_url": "path/to/thumbnail.jpg",
    "duration": 3600,
    "order": 1,
    "views_count": 100,
    "subject": {
        "id": 1,
        "name": "Mathematics"
    }
}
```

#### Stream Lesson Video
```http
GET /api/lessons/{id}/stream
```
**Response:** `200 OK` or `206 Partial Content`
- Supports range requests for video streaming
- Returns video content with appropriate headers

### Podcasts

#### List Podcasts
```http
GET /api/podcasts
```
**Query Parameters:**
- `category` (optional): Filter by category

**Response:** `200 OK`
```json
{
    "data": [
        {
            "id": 1,
            "title": "Learning Mathematics",
            "audio_url": "path/to/audio.mp3",
            "thumbnail_url": "path/to/thumbnail.jpg",
            "category": "education",
            "description": "A podcast about mathematics",
            "plays_count": 100,
            "user": {
                "id": 1,
                "name": "John Doe"
            }
        }
    ]
}
```

#### Get Podcast Details
```http
GET /api/podcasts/{id}
```
**Response:** `200 OK`
```json
{
    "id": 1,
    "title": "Learning Mathematics",
    "audio_url": "path/to/audio.mp3",
    "thumbnail_url": "path/to/thumbnail.jpg",
    "category": "education",
    "description": "A podcast about mathematics",
    "plays_count": 100,
    "user": {
        "id": 1,
        "name": "John Doe"
    }
}
```

#### Stream Podcast
```http
GET /api/podcasts/{id}/stream
```
**Response:** `200 OK` or `206 Partial Content`
- Supports range requests for audio streaming
- Returns audio content with appropriate headers

#### Increment Plays
```http
POST /api/podcasts/{id}/play
```
**Response:** `200 OK`
```json
{
    "plays_count": 101
}
```

### Videos

#### List Active Videos
```http
GET /api/videos
```
**Response:**
```json
[
    {
        "id": 1,
        "title": "Sample Video",
        "description": "Video description",
        "video_url": "https://example.com/video.mp4",
        "thumbnail_url": "https://example.com/thumbnail.jpg",
        "views_count": 100,
        "likes_count": 50,
        "comments_count": 25,
        "is_active": true,
        "created_at": "2025-04-24T10:00:00Z",
        "updated_at": "2025-04-24T10:00:00Z"
    }
]
```

#### Get Video Details
```http
GET /api/videos/{id}
```
**Response:**
```json
{
    "id": 1,
    "title": "Sample Video",
    "description": "Video description",
    "video_url": "https://example.com/video.mp4",
    "thumbnail_url": "https://example.com/thumbnail.jpg",
    "views_count": 100,
    "likes_count": 50,
    "comments_count": 25,
    "is_active": true,
    "created_at": "2025-04-24T10:00:00Z",
    "updated_at": "2025-04-24T10:00:00Z"
}
```

### Likes

#### Toggle Like Status
```http
POST /api/videos/{id}/toggle-like
```
**Response:**
```json
{
    "liked": true
}
```

#### Get Like Status
```http
GET /api/videos/{id}/like-status
```
**Response:**
```json
{
    "liked": true
}
```

### Comments

#### Add Comment to Video
```http
POST /api/videos/{id}/comments
```
**Request Body:**
```json
{
    "content": "This is a great video!"
}
```
**Response:**
```json
{
    "id": 1,
    "content": "This is a great video!",
    "user": {
        "id": 1,
        "name": "John Doe"
    },
    "created_at": "2025-04-24T10:00:00Z"
}
```

#### Update Comment
```http
PUT /api/videos/{video_id}/comments/{comment_id}
```
**Request Body:**
```json
{
    "content": "Updated comment content"
}
```
**Response:**
```json
{
    "id": 1,
    "content": "Updated comment content",
    "user": {
        "id": 1,
        "name": "John Doe"
    },
    "updated_at": "2025-04-24T10:00:00Z"
}
```

#### Delete Comment
```http
DELETE /api/videos/{video_id}/comments/{comment_id}
```
**Response:**
```json
{
    "message": "Comment deleted"
}
```

### Notifications

#### Get User Notifications
```http
GET /api/notifications
```
**Response:**
```json
[
    {
        "id": 1,
        "user_id": 1,
        "content": "New video available",
        "is_read": false,
        "created_at": "2025-04-24T10:00:00Z",
        "updated_at": "2025-04-24T10:00:00Z"
    }
]
```

#### Get Unread Count
```http
GET /api/notifications/unread-count
```
**Response:**
```json
{
    "count": 5
}
```

#### Mark Notification as Read
```http
POST /api/notifications/{id}/mark-as-read
```
**Response:**
```json
{
    "message": "Notification marked as read"
}
```

#### Mark All Notifications as Read
```http
POST /api/notifications/mark-all-as-read
```
**Response:**
```json
{
    "message": "All notifications marked as read"
}
```

## Media Streaming

### Video/Audio Streaming Features
- Supports byte-range requests
- Chunked transfer encoding
- Configurable chunk size (default: 8KB)
- Caching headers for mobile optimization
- Supports seek operations
- Automatic view/play counting

### Supported Content Types
- Video: MP4 (H.264 + AAC)
- Audio: MP3, AAC

## Error Handling

### HTTP Status Codes
- 200: Success
- 201: Created
- 206: Partial Content (for media streaming)
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

### Error Response Format
```json
{
    "message": "Error description",
    "errors": {
        "field": [
            "Validation error message"
        ]
    }
}
```

## Rate Limiting

API requests are limited to 60 per minute per user. The response headers include rate limit information:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
The project follows PSR-12 coding standards. Run PHP CS Fixer to maintain consistency:
```bash
./vendor/bin/php-cs-fixer fix
```

### API Documentation
- Interactive API documentation: `/api/documentation`
- OpenAPI/Swagger specification: `/api-docs.json`

## Deployment to Hostinger Shared Hosting

### Pre-deployment Checklist
1. Optimize your application:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. Update `.env` file for production:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   
   DB_HOST=your-hostinger-mysql-host
   DB_DATABASE=your-database-name
   DB_USERNAME=your-database-username
   DB_PASSWORD=your-database-password
   
   FILESYSTEM_DISK=public
   ```

### Deployment Steps

1. **Create a Hostinger Database**
   - Log in to Hostinger control panel
   - Create a new MySQL database and user
   - Note down the database credentials

2. **Upload Files**
   - Upload all files to the public_html folder except:
     - `.env`
     - `node_modules/`
     - `.git/`
     - `tests/`
     - `storage/logs/*`
     - `storage/framework/cache/*`
   - Create a new `.env` file with production settings

3. **Directory Structure**
   - Move all files except those in `public/` to a directory above `public_html/`
   - Move contents of `public/` to `public_html/`
   - Update `index.php` paths in `public_html/`

4. **Set Permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chmod -R 755 public_html/storage
   ```

5. **Configure Domain**
   - Point your domain to Hostinger nameservers
   - Set up SSL certificate through Hostinger

6. **Final Setup**
   ```bash
   php artisan storage:link
   php artisan migrate
   php artisan key:generate
   ```

### Important Files to Update

1. **public_html/index.php**
   ```php
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```

2. **config/filesystems.php**
   - Ensure proper storage configuration
   ```php
   'public' => [
       'driver' => 'local',
       'root' => storage_path('app/public'),
       'url' => env('APP_URL').'/storage',
       'visibility' => 'public',
   ],
   ```

3. **.htaccess in public_html/**
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteBase /
       RewriteRule ^index\.php$ - [L]
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule . /index.php [L]
   </IfModule>
   
   # PHP Settings
   php_value upload_max_filesize 64M
   php_value post_max_size 64M
   php_value max_execution_time 300
   php_value max_input_time 300
   ```

### Troubleshooting

1. **500 Internal Server Error**
   - Check storage folder permissions
   - Verify .env file exists and is properly configured
   - Review Laravel logs in storage/logs

2. **Database Connection Issues**
   - Confirm database credentials in .env
   - Check if database user has proper permissions
   - Verify database host is correct

3. **File Upload Issues**
   - Check storage symlink is created
   - Verify folder permissions
   - Confirm PHP upload limits in .htaccess

4. **Performance Optimization**
   - Enable OPCache in php.ini
   - Use proper cache drivers
   - Enable Gzip compression

### Maintenance

1. **Regular Updates**
   ```bash
   composer update --no-dev
   php artisan config:clear
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   ```

2. **Backup Strategy**
   - Regular database backups through Hostinger panel
   - Backup .env and other configuration files
   - Store uploaded media files separately

3. **Monitoring**
   - Set up error logging
   - Monitor disk space usage
   - Check application logs regularly

## Security Considerations

1. **SSL Certificate**
   - Enable HTTPS through Hostinger
   - Force HTTPS redirect
   - Update APP_URL in .env to use https://

2. **File Permissions**
   - Proper ownership of files
   - Restricted access to sensitive directories
   - Regular security audits

3. **Database Security**
   - Strong passwords
   - Limited database user privileges
   - Regular security updates

## Support

For hosting-specific issues, contact Hostinger support. For application issues, please create an issue in the project repository.

## License

This project is licensed under the MIT License.
