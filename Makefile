.DEFAULT_GOAL : help

help: ## Show this help
	@printf "\033[33m%s:\033[0m\n" 'Available commands'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z0-9_-]+:.*?## / {printf "  \033[32m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
build: ## Build docker image
	docker compose build
update: ## Update dependencies
	docker compose run php env XDEBUG_MODE=off composer update
zipkin: ## Start zipkin
	docker compose up -d zipkin
demo: ## Run the demo
	docker compose run php env XDEBUG_MODE=off php examples/demo.php
run: zipkin demo ## Start zipkin and run the demo
test: ## Run tests
	docker compose run php env XDEBUG_MODE=off vendor/bin/phpunit
shell: ## Shell to PHP container
	docker compose run php bash