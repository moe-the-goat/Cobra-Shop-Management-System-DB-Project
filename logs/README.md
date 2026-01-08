# Cobra Shop Logs Directory

This directory contains application logs.

## Log Files

- `app_YYYY-MM-DD.log` - Daily application logs

## Important

- Do NOT commit log files to version control
- Logs are automatically created by the application
- Old logs should be periodically cleaned up
- In production, consider using a proper log management system

## Log Levels

- **ERROR** - Critical errors that need immediate attention
- **WARNING** - Non-critical issues
- **INFO** - Informational messages
- **SECURITY** - Security-related events (login attempts, CSRF violations, etc.)
