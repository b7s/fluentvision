.PHONY: help install test test-unit test-coverage catraca release clean

RELEASE_VERSION := $(if $(VERSION),$(VERSION),$(version))
RELEASE_MESSAGE := $(if $(MESSAGE),$(MESSAGE),$(message))

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install

test: ## Run all tests
	composer test

test-unit: ## Run unit tests only
	composer test:unit

test-coverage: ## Run tests with coverage
	composer test:coverage

analyse: ## Run PHPStan
	composer analyse

catraca: ## Run quality gate
	./vendor/bin/catraca

release: ## Create tagged release (version=x.y.z message='msg')
	@if [ -f version ]; then \
		LAST_VERSION=$$(cat version); \
		echo "Last version: v$$LAST_VERSION"; \
		echo ""; \
	fi; \
	VERSION_INPUT="$(RELEASE_VERSION)"; \
	if [ -z "$$VERSION_INPUT" ]; then \
		read -p "Enter release version (format x.y.z): " VERSION_INPUT; \
	fi; \
	if ! echo "$$VERSION_INPUT" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+$$'; then \
		echo "Invalid version format. Expected x.y.z"; exit 1; \
	fi; \
	echo "New version: v$$VERSION_INPUT"; \
	MESSAGE_INPUT="$(RELEASE_MESSAGE)"; \
	if [ -z "$$MESSAGE_INPUT" ]; then \
		echo "Enter release message:"; \
		MESSAGE_INPUT=$$(cat); \
		if [ -z "$$MESSAGE_INPUT" ]; then \
			MESSAGE_INPUT="Release v$$VERSION_INPUT"; \
		fi; \
	fi; \
	if ! composer test; then \
		echo "Tests failed. Fix issues before releasing."; exit 1; \
	fi; \
	if ! git diff --quiet || ! git diff --cached --quiet; then \
		git add -A; \
		git commit -m "$$MESSAGE_INPUT" || true; \
	fi; \
	git push origin HEAD || true; \
	git tag -a v$$VERSION_INPUT -m "$$MESSAGE_INPUT"; \
	git push origin v$$VERSION_INPUT; \
	echo "$$VERSION_INPUT" > version; \
	git add version; \
	git commit -m "Update version to $$VERSION_INPUT" || true; \
	git push origin HEAD || true; \
	echo "Release v$$VERSION_INPUT created successfully!";

clean: ## Clean cache and temporary files
	rm -rf build/ vendor/ .phpunit.cache/ .phpstan.cache/
