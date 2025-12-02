# Ciblerh Payroll and Check-in Management System

A comprehensive Laravel-based HR management system for payroll processing and employee check-in/check-out tracking.

## Features

- **Payroll Management**: Complete payroll processing and payslip generation
- **Employee Check-in/Check-out**: Time tracking and attendance management
- **Leave Management**: Employee leave requests and approvals
- **Overtime Tracking**: Overtime hours management
- **Department Management**: Organizational structure management
- **Role-based Access Control**: Comprehensive user role and permission management
- **Employee Portal**: Self-service portal for employees
- **Reporting**: Export capabilities for various reports

## Technology Stack

- **Framework**: Laravel 10.x
- **Frontend**: Livewire 3.x
- **Queue Management**: Laravel Horizon
- **PDF Generation**: DomPDF
- **Excel Import/Export**: Maatwebsite Excel
- **Permission Management**: Spatie Laravel Permission
- **Authentication**: Laravel Sanctum

## Requirements

- PHP >= 8.1
- Composer
- Node.js and NPM
- Redis (for queues and Horizon)
- Database (MySQL/PostgreSQL)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/Escarter/Ciblerh-payroll-checkin.git
cd Ciblerh-payroll-checkin
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node dependencies:
```bash
npm install
```

4. Copy environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Configure your `.env` file with database credentials and other settings.

7. Run migrations:
```bash
php artisan migrate
```

8. Seed the database (optional):
```bash
php artisan db:seed
```

9. Build frontend assets:
```bash
npm run build
```

## Development

Run the development server:
```bash
php artisan serve
```

For frontend development with hot reload:
```bash
npm run dev
```

## Queue Processing

Start the queue worker:
```bash
php artisan queue:work
```

Or use Laravel Horizon for queue monitoring:
```bash
php artisan horizon
```

## License

MIT

## Author

Escarter
