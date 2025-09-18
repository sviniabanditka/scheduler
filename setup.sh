#!/bin/bash

# Laravel Scheduler Docker Setup Script

echo "🚀 Setting up Laravel Scheduler Docker Environment..."

# Copy environment file
if [ ! -f .env ]; then
    echo "📝 Creating .env file from docker.env.example..."
    cp docker.env.example .env
    echo "✅ .env file created successfully!"
else
    echo "⚠️  .env file already exists, skipping..."
fi

# Create storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

echo "✅ Setup completed successfully!"
echo ""
echo "Next steps:"
echo "1. Run 'make build' to build Docker containers"
echo "2. Run 'make up' to start the services"
echo "3. Run 'make install' to install Laravel dependencies"
echo "4. Run 'make migrate' to run database migrations"
echo "5. Visit http://localhost:8080 to see your application"
echo ""
echo "For more commands, run 'make help'"
