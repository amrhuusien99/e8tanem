# Custom Artisan Commands

This document provides information about the custom Artisan commands available in the E8tanem application. These commands help with administrative tasks, debugging, and maintenance.

## User Management Commands

### Promote User to Admin

Promotes an existing user to admin status by setting the `role` to 'admin'.

```bash
php artisan app:promote-user-to-admin user@example.com
```

**Arguments:**
- `email`: The email of the user to promote to admin (required)

**Example:**
```bash
php artisan app:promote-user-to-admin admin@example.com
```

### Create Filament Admin User

Creates a new user with admin privileges for Filament.

```bash
php artisan app:create-filament-admin "Admin Name" admin@example.com [password]
```

**Arguments:**
- `name`: The name of the admin user (required)
- `email`: The email address of the admin user (required)
- `password`: The password for the admin user (optional, will prompt if not provided)

**Example:**
```bash
php artisan app:create-filament-admin "John Admin" john@example.com
```

### Fix Filament Users

Set the admin role for existing Filament users.

```bash
php artisan app:fix-filament-users [--dry-run]
```

**Options:**
- `--dry-run`: Show how many users would be affected without making changes

**Example:**
```bash
# Check which users would be affected without making changes
php artisan app:fix-filament-users --dry-run

# Fix all users
php artisan app:fix-filament-users
```

## Storage Management Commands

### Storage Debug

Debug and manage storage files with various actions.

```bash
php artisan storage:debug {action} [--id=] [--model=]
```

**Arguments:**
- `action`: The action to perform (default: "list"). Available actions: list, clean, dump-model

**Options:**
- `--id`: The ID of the model to work with
- `--model`: The model type (Video, Podcast, Lesson)

**Examples:**
```bash
# List all storage files
php artisan storage:debug list

# Clean files for a specific model
php artisan storage:debug clean --model=Video --id=1

# Dump file information for a model
php artisan storage:debug dump-model --model=Podcast --id=2
```

### Debug Storage

Debug storage issues and handle file cleanup.

```bash
php artisan debug:storage {action} [--model=] [--id=]
```

**Arguments:**
- `action`: The action to perform (default: "check"). Available actions: check, clean, clean-all

**Options:**
- `--model`: The model type for specific actions
- `--id`: The ID of the model to work with

**Examples:**
```bash
# Check storage status
php artisan debug:storage check

# Clean files for a specific model
php artisan debug:storage clean --model=Video --id=1

# Clean all orphaned files
php artisan debug:storage clean-all
```

### Storage Cleanup

Clean up orphaned files in storage that no longer have database records.

```bash
php artisan storage:cleanup [--force] [--type=all]
```

**Options:**
- `--force`: Skip confirmation prompt and proceed with deletion
- `--type`: Type of files to clean (default: "all"). Available types: all, videos, podcasts, lessons

**Examples:**
```bash
# Show orphaned files without deleting
php artisan storage:cleanup

# Force cleanup of all orphaned files
php artisan storage:cleanup --force

# Clean up only video files
php artisan storage:cleanup --type=videos --force
```

## Usage Notes

- Always run storage cleanup commands with caution, preferably in a non-production environment first.
- For admin user management, ensure you're promoting trusted users only.
- When in doubt, use the dry-run or check options first to see what changes would be made.

## Implementing New Commands

To create a new command:

```bash
php artisan make:command YourNewCommand
```

Then, implement the command logic in the handle method of the created class.
