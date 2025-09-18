.PHONY: help build up down migrate seed install clean logs shell

# Default target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build Docker containers
	docker-compose build --no-cache

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

install: ## Install Laravel dependencies
	docker-compose exec app composer install
	docker-compose exec app cp .env.example .env
	docker-compose exec app php artisan key:generate

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

fresh: ## Fresh migrate and seed
	docker-compose exec app php artisan migrate:fresh --seed

logs: ## Show logs from all services
	docker-compose logs -f

logs-app: ## Show logs from app service
	docker-compose logs -f app

logs-nginx: ## Show logs from nginx service
	docker-compose logs -f nginx

logs-db: ## Show logs from database service
	docker-compose logs -f db

shell: ## Access app container shell
	docker-compose exec app bash

shell-db: ## Access database container shell
	docker-compose exec db bash

clean: ## Clean up containers and volumes
	docker-compose down -v
	docker system prune -f

test: ## Run tests
	docker-compose exec app php artisan test

cache-clear: ## Clear application cache
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
