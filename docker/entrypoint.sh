#!/bin/bash

# Exit on any error
set -e

echo "Starting Laravel application with queue worker and scheduler..."

# Run migrations (optional, you can remove this if not needed)
# php artisan migrate --force

# Start the queue worker in the background
echo "Starting queue worker..."
php artisan queue:work --sleep=3 --tries=3 --max-time=3600 &

# Start the scheduler in the background
echo "Starting scheduler..."
php artisan schedule:work &

# Start the main web server in the foreground
echo "Starting web server..."
exec frankenphp