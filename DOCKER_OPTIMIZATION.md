# Docker and Database Seeding Optimizations

## Overview
This document outlines the optimizations made to the Docker configuration and database seeding process for better performance, security, and environment-specific behavior.

## Key Optimizations

### 1. Dockerfile Improvements
- **Reduced Docker layers**: Combined system dependencies and PHP extension installation into a single RUN command
- **Better caching**: Copy package.json and composer.json first to leverage Docker layer caching
- **Production-ready builds**: Frontend assets are built during image creation instead of runtime
- **Optimized PHP extensions**: All extensions installed in one layer to reduce image size

### 2. Smart Database Seeding
The database seeding process now adapts to different environments:

#### Development Environment (`APP_ENV=local`)
- Runs `migrate:fresh --seed` by default
- Creates test data (companies, departments, services, users)
- Assigns roles to generated users

#### Production Environment (`APP_ENV=production`)
- Runs regular `migrate --force` (no fresh migrations)
- Only creates essential data (roles, permissions, admin user)
- Skips test data creation entirely

#### Staging/Testing Environments
- Regular migrations without fresh reset
- Optional seeding based on configuration

### 3. Environment Variables for Control

Add these variables to your `.env` file to control seeding behavior:

```bash
# Force fresh migrations with seeding (use with caution)
FORCE_SEED=false

# Skip all seeding operations
SKIP_SEED=false

# Force cache clearing in production
FORCE_CACHE_CLEAR=false
```

### 4. Performance Improvements
- **Conditional cache clearing**: Caches only cleared in development or when explicitly requested
- **Smart asset building**: Frontend assets only built if not already present
- **Environment-aware operations**: Heavy operations only run when necessary

## Usage Examples

### Development Setup
```bash
# Regular development setup with test data
docker-compose up -d

# Skip seeding for faster startup
SKIP_SEED=true docker-compose up -d

# Force fresh seeding
FORCE_SEED=true docker-compose up -d
```

### Production Deployment
```bash
# Production environment - minimal seeding
APP_ENV=production docker-compose up -d

# Production with forced cache clearing (maintenance mode)
APP_ENV=production FORCE_CACHE_CLEAR=true docker-compose up -d
```

## Database Schema
The optimized seeder now:
- Uses `firstOrCreate()` for admin user to prevent duplicates
- Separates concerns with private methods
- Provides clear logging for operations
- Handles role assignment safely

## Security Considerations
- Admin credentials should be changed after first deployment
- Test data is never created in production environments
- Sensitive operations are logged for audit purposes

## Migration Strategy
For existing deployments:
1. Backup your database
2. Update the Docker configuration
3. Set `SKIP_SEED=true` for first run to avoid data loss
4. Gradually migrate to the new seeding approach

## Troubleshooting

### Issue: Seeding takes too long
**Solution**: Set `SKIP_SEED=true` or reduce factory counts in non-production environments

### Issue: Missing admin user in production
**Solution**: Ensure `APP_ENV` is not set to `production` during initial setup, or manually create admin user

### Issue: Assets not building
**Solution**: Check Node.js installation and run `npm run build` manually inside container