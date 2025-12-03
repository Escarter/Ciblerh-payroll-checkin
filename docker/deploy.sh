#!/bin/bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}🚀 Ciblerh Docker Deployment Script${NC}"
echo ""

# Function to print colored messages
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if Docker is running
check_docker() {
    print_info "Checking Docker daemon..."
    if ! docker ps > /dev/null 2>&1; then
        print_error "Docker daemon is not running!"
        echo "Please start Docker Desktop and try again."
        exit 1
    fi
    print_success "Docker is running"
}

# Check if .env file exists
check_env_file() {
    print_info "Checking .env file..."
    if [ ! -f "$PROJECT_DIR/.env" ]; then
        print_warning ".env file not found. Creating from .env.example..."
        if [ -f "$PROJECT_DIR/.env.example" ]; then
            cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
            print_success ".env file created"
        else
            print_error ".env.example not found. Please create .env manually."
            exit 1
        fi
    else
        print_success ".env file exists"
    fi
}

# Update .env file with Docker-specific settings
update_env_file() {
    print_info "Updating .env file with Docker settings..."
    
    cd "$PROJECT_DIR"
    
    # Backup .env file
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    print_info "Backed up .env file"
    
    # Update DB_HOST if it's localhost or 127.0.0.1
    if grep -q "DB_HOST=127.0.0.1" .env || grep -q "DB_HOST=localhost" .env; then
        sed -i.bak 's/DB_HOST=127.0.0.1/DB_HOST=mysql/' .env
        sed -i.bak 's/DB_HOST=localhost/DB_HOST=mysql/' .env
        print_success "Updated DB_HOST to mysql"
    fi
    
    # Update REDIS_HOST if it's localhost or 127.0.0.1
    if grep -q "REDIS_HOST=127.0.0.1" .env || grep -q "REDIS_HOST=localhost" .env; then
        sed -i.bak 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/' .env
        sed -i.bak 's/REDIS_HOST=localhost/REDIS_HOST=redis/' .env
        print_success "Updated REDIS_HOST to redis"
    fi
    
    # Add or update PDF tool paths
    if ! grep -q "PDFTOTEXT_PATH" .env; then
        echo "" >> .env
        echo "# PDF Tools (Docker paths)" >> .env
        echo "PDFTOTEXT_PATH=/usr/bin/pdftotext" >> .env
        echo "PDFSEPARATE_PATH=/usr/bin/pdfseparate" >> .env
        echo "PDFTK_PATH=/usr/bin/pdftk" >> .env
        print_success "Added PDF tool paths"
    else
        # Update existing paths
        sed -i.bak 's|PDFTOTEXT_PATH=.*|PDFTOTEXT_PATH=/usr/bin/pdftotext|' .env
        sed -i.bak 's|PDFSEPARATE_PATH=.*|PDFSEPARATE_PATH=/usr/bin/pdfseparate|' .env
        sed -i.bak 's|PDFTK_PATH=.*|PDFTK_PATH=/usr/bin/pdftk|' .env
        print_success "Updated PDF tool paths"
    fi
    
    # Ensure queue connection is redis
    if ! grep -q "QUEUE_CONNECTION" .env; then
        echo "QUEUE_CONNECTION=redis" >> .env
        print_success "Added QUEUE_CONNECTION"
    else
        sed -i.bak 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env
        print_success "Updated QUEUE_CONNECTION to redis"
    fi
    
    # Ensure cache and session drivers are redis
    if ! grep -q "CACHE_DRIVER" .env; then
        echo "CACHE_DRIVER=redis" >> .env
    else
        sed -i.bak 's/CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
    fi
    
    if ! grep -q "SESSION_DRIVER" .env; then
        echo "SESSION_DRIVER=redis" >> .env
    else
        sed -i.bak 's/SESSION_DRIVER=.*/SESSION_DRIVER=redis/' .env
    fi
    
    # Add timezone if not present
    if ! grep -q "^TZ=" .env; then
        echo "TZ=Africa/Douala" >> .env
        print_success "Added timezone"
    fi
    
    # Clean up backup files created by sed
    rm -f .env.bak
    
    print_success ".env file updated for Docker"
}

# Build Docker images
build_images() {
    print_info "Building Docker images (this may take a few minutes)..."
    cd "$PROJECT_DIR"
    
    if docker-compose build; then
        print_success "Docker images built successfully"
    else
        print_error "Failed to build Docker images"
        exit 1
    fi
}

# Start services
start_services() {
    print_info "Starting Docker services..."
    cd "$PROJECT_DIR"
    
    docker-compose up -d
    
    print_success "Services started"
    print_info "Waiting for services to be ready (30 seconds)..."
    sleep 30
}

# Wait for MySQL to be ready
wait_for_mysql() {
    print_info "Waiting for MySQL to be ready..."
    cd "$PROJECT_DIR"
    
    max_attempts=30
    attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -p"${DB_PASSWORD:-password}" --silent 2>/dev/null; then
            print_success "MySQL is ready"
            return 0
        fi
        attempt=$((attempt + 1))
        echo -n "."
        sleep 2
    done
    
    echo ""
    print_warning "MySQL took longer than expected to start, but continuing..."
}

# Wait for Redis to be ready
wait_for_redis() {
    print_info "Waiting for Redis to be ready..."
    cd "$PROJECT_DIR"
    
    max_attempts=15
    attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if docker-compose exec -T redis redis-cli ping > /dev/null 2>&1; then
            print_success "Redis is ready"
            return 0
        fi
        attempt=$((attempt + 1))
        echo -n "."
        sleep 2
    done
    
    echo ""
    print_warning "Redis took longer than expected to start, but continuing..."
}

# Initialize application
initialize_app() {
    print_info "Initializing application..."
    cd "$PROJECT_DIR"
    
    # Generate app key if not set
    if ! grep -q "APP_KEY=base64:" .env; then
        print_info "Generating application key..."
        docker-compose exec -T app php artisan key:generate --force
        print_success "Application key generated"
    else
        print_info "Application key already exists"
    fi
    
    # Run migrations
    print_info "Running database migrations..."
    if docker-compose exec -T app php artisan migrate --force; then
        print_success "Migrations completed"
    else
        print_warning "Migrations may have failed or already run"
    fi
    
    # Create storage symlink
    print_info "Creating storage symlink..."
    docker-compose exec -T app php artisan storage:link || print_warning "Storage link may already exist"
    print_success "Storage symlink created"
    
    # Set permissions
    print_info "Setting storage permissions..."
    docker-compose exec -T app chmod -R 775 storage bootstrap/cache || true
    docker-compose exec -T app chown -R www-data:www-data storage bootstrap/cache || true
    print_success "Permissions set"
}

# Verify services
verify_services() {
    print_info "Verifying services..."
    cd "$PROJECT_DIR"
    
    echo ""
    print_info "Service Status:"
    docker-compose ps
    
    echo ""
    print_info "Testing PDF tools..."
    if docker-compose exec -T app which pdftk > /dev/null 2>&1; then
        print_success "pdftk is installed"
    else
        print_error "pdftk not found"
    fi
    
    if docker-compose exec -T app which pdftotext > /dev/null 2>&1; then
        print_success "pdftotext is installed"
    else
        print_error "pdftotext not found"
    fi
    
    if docker-compose exec -T app which pdfseparate > /dev/null 2>&1; then
        print_success "pdfseparate is installed"
    else
        print_error "pdfseparate not found"
    fi
    
    echo ""
    print_info "Testing database connection..."
    if docker-compose exec -T app php artisan db:monitor > /dev/null 2>&1 || docker-compose exec -T app php -r "try { new PDO('mysql:host=mysql;dbname=${DB_DATABASE:-ciblerh}', '${DB_USERNAME:-ciblerh}', '${DB_PASSWORD:-password}'); echo 'OK'; } catch(Exception \$e) { echo 'FAIL'; }" | grep -q "OK"; then
        print_success "Database connection successful"
    else
        print_warning "Database connection test failed (may need more time)"
    fi
    
    echo ""
    print_info "Testing Redis connection..."
    if docker-compose exec -T redis redis-cli ping | grep -q "PONG"; then
        print_success "Redis connection successful"
    else
        print_warning "Redis connection test failed"
    fi
}

# Main execution
main() {
    echo ""
    check_docker
    echo ""
    
    check_env_file
    echo ""
    
    update_env_file
    echo ""
    
    read -p "Do you want to build Docker images? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        build_images
        echo ""
    else
        print_info "Skipping image build"
        echo ""
    fi
    
    start_services
    echo ""
    
    wait_for_mysql
    wait_for_redis
    echo ""
    
    initialize_app
    echo ""
    
    verify_services
    echo ""
    
    print_success "🎉 Deployment completed successfully!"
    echo ""
    echo -e "${BLUE}Next steps:${NC}"
    echo "  • Access application: http://localhost"
    echo "  • Horizon dashboard: http://localhost/horizon"
    echo "  • View logs: docker-compose logs -f"
    echo "  • Stop services: docker-compose down"
    echo ""
    echo -e "${BLUE}Useful commands:${NC}"
    echo "  • View all logs: docker-compose logs -f"
    echo "  • View app logs: docker-compose logs -f app"
    echo "  • View horizon logs: docker-compose logs -f horizon"
    echo "  • Execute artisan: docker-compose exec app php artisan <command>"
    echo "  • Access shell: docker-compose exec app bash"
    echo ""
}

# Run main function
main

