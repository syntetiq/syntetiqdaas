include .env

.DEFAULT_GOAL := help
IS_NOT_ARM64 := $(shell uname -m | sed s/arm64//)
COMPOSER_INSTALL_CMD := composer install --no-interaction --no-progress
COMPOSER_UPDATE_CMD := composer update --no-interaction --no-progress
DOCKER_COMPOSE := docker compose
COMPOSER_HOME := ${COMPOSER_HOME_OVERRIDE}

NC := \033[0m
GREEN := \033[0;32m
YELLOW := \033[103m

help:
	@echo "Command list:"

	@echo "${GREEN}make help${NC} this command"
	@echo "${GREEN}make welcome${NC} welcome information"
	@echo "${GREEN}make up${NC} up docker containers"
	@echo "${GREEN}make stop${NC} stop docker containers"
	@echo "${GREEN}make down${NC} down docker containers"
	@echo "${GREEN}make composer-install${NC} install vendor dependencies project"
	@echo "${GREEN}make install${NC} install project"
	@echo "${GREEN}make update${NC} update and migrate project"
	@echo "${GREEN}make app-update${NC} alias for update"
	@echo "${GREEN}make reinstall${NC} recreate database and install project"
	@echo "${GREEN}make assets-install${NC} install Oro assets"

	@echo "${GREEN}make cache-clear-all${NC}"
	@echo "${GREEN}make cache-clear${NC}"
	@echo "${GREEN}make cache-warmup${NC} warmup prod cache"
	@echo "${GREEN}make cache-warmup-dev${NC} warmup dev cache"
	@echo "${GREEN}make cache-warmup-jetson${NC} warmup jetson cache"
	@echo "${GREEN}make bucket-delete${NC} delete GCS bucket in emulator"
	@echo "${GREEN}make bucket-create${NC} create GCS bucket in emulator"
	@echo "${GREEN}make bucket-empty${NC} delete all objects (and ${GCS_BUCKET_DIRECTORY} folder) from GCS bucket in emulator"
	@echo "${GREEN}make bucket-import-tmp-clear${NC} remove only files from import_tmp"
	@echo "${GREEN}make db-recreate${NC} recreate database"
	@echo "${GREEN}make oauth-keys-generate${NC} generate oauth keys"
	@echo ""
	@echo "${YELLOW}App Commands:${NC}"
	@echo "${GREEN}make console args=...${NC} run symfony console command"
	@echo "${GREEN}make console-jetson args=...${NC} run symfony console command in jetson container"
	@echo "${GREEN}make console-debug args=...${NC} run symfony console command with debug"
	@echo ""
	@echo "${YELLOW}Container Ops:${NC}"
	@echo "${GREEN}make get-in-fpm${NC} bash into fpm container"
	@echo "${GREEN}make get-in-cli${NC} bash into cli container (www-data)"
	@echo "${GREEN}make get-in-cli-root${NC} bash into cli container (root)"
	@echo "${GREEN}make get-in-cli-ubuntu${NC} bash into cli container (ubuntu)"
	@echo "${GREEN}make get-in-tensorboard${NC} bash into tensorboard container"
	@echo "${GREEN}make consumer-memory-watch${NC} live RSS/VSZ memory view for the MQ consumer"
	@echo "${GREEN}make consumer-kill-check${NC} inspect phpcli container restarts/OOM and grep logs for kill-like events"
	@echo ""
	@echo "${YELLOW}Model Training:${NC}"
	@echo "${GREEN}make model-train-cli${NC} run model training (cli)"
	@echo "${GREEN}make model-train-docker${NC} run model training (docker)"
	@echo "${GREEN}make model-train-docker-nv${NC} run model training (docker nvidia)"
	@echo ""
	@echo "${YELLOW}Helper Models:${NC}"
	@echo "${GREEN}make sam-model-download variant=tiny${NC} download a SAM2 checkpoint into server/sam-api/model/checkpoints"
	@echo "${GREEN}make sam-model-download-tiny${NC} download the SAM2 tiny checkpoint"
	@echo "${GREEN}make sam-model-download-small${NC} download the SAM2 small checkpoint"
	@echo "${GREEN}make sam-model-download-base-plus${NC} download the SAM2 base_plus checkpoint"
	@echo "${GREEN}make sam-model-download-large${NC} download the SAM2 large checkpoint"
	@echo ""
	@echo "${YELLOW}Services:${NC}"
	@echo "${GREEN}make rebuild service=syntetiq-sam-api-cpu${NC} rebuild and restart any docker compose service"
	@echo "${GREEN}make cron-run${NC} start cron service"
	@echo "${GREEN}make cron-stop${NC} stop cron service"
	@echo "${GREEN}make supervisord-run${NC} start supervisord"
	@echo "${GREEN}make supervisord-stop${NC} stop supervisord"
	@echo ""
	@echo "${YELLOW}Svelte:${NC}"
	@echo "${GREEN}make svelte-install${NC} install Svelte dependencies"
	@echo "${GREEN}make svelte-build-test${NC} build test component and copy assets"
	@echo "${GREEN}make svelte-build-image-editor${NC} build image editor component and copy assets"
	@echo "${GREEN}make svelte-dev${NC} run Svelte dev servers"

welcome:
	@echo "Welcome to ${GREEN}syntetiq-stack${NC}!"
	@echo ""
	@echo "Installation is completed! Please read the ${GREEN}README.md${NC} about how to run the services."

# SyntetiQ app commands
down:
	@${DOCKER_COMPOSE} ${CONFIG} down

stop:
	@${DOCKER_COMPOSE} ${CONFIG} stop

up:
	@${DOCKER_COMPOSE} ${CONFIG} up -d

install: bucket-delete bucket-create cache-clear
	@rm -rf ../app/public/js/*
	@rm -rf ../app/public/css/*
	@echo "File cache has deleted"
	@docker exec --env XDEBUG_MODE=off --workdir=/data --user=www-data ${PHPCLI_CONTAINER} php -dxdebug.mode=off bin/console oro:install --env=prod --user-name=admin --timeout=${TIMEOUT} --user-email=${EMAIL} --user-firstname=admin --user-lastname=admin --user-password=${ADMIN_PASSWORD} --sample-data=${SAMPLE_DATA} --organization-name=SyntetiQ --application-url=${PROTOCOL}://${URL}/

# SyntetiQ app commands
db-recreate:
# Recreate SyntetiQ database
	@${DOCKER_COMPOSE} exec ${DB_INSTANCE} psql "postgresql://${DB_USER}:${DB_PASS}@${DB_INSTANCE}/postgres" -c "DROP DATABASE IF EXISTS ${DB_NAME} WITH (FORCE);"
	@${DOCKER_COMPOSE} exec ${DB_INSTANCE} psql "postgresql://${DB_USER}:${DB_PASS}@${DB_INSTANCE}/postgres" -c "CREATE DATABASE ${DB_NAME};"
	@${DOCKER_COMPOSE} exec ${DB_INSTANCE} echo "DB ${DB_NAME} has dropped and created"
	@${DOCKER_COMPOSE} exec ${DB_INSTANCE} psql "postgresql://${DB_USER}:${DB_PASS}@${DB_INSTANCE}/${DB_NAME}" -c "CREATE EXTENSION \"uuid-ossp\";"
	@echo "DB ${DB_NAME} has extensions created"

reinstall: db-recreate install

cache-clear-all: cache-clear
	@rm -Rf ../app/vendor
	@rm -Rf ../app/node_modules
	@rm -Rf ../app/public/build
	@rm -Rf ../app/public/js/*.js
	@rm -Rf ../app/public/bundles/*
	@rm -Rf ../app/public/media/cache
	@rm -Rf ../app/public/media/js
	@echo "Cache, vendor, node_modules, public/build, public/js, public/bundles and public/media cache has deleted"

cache-clear:
	@find ../app/var/cache -maxdepth 1 -mindepth 1 -type d -name '*.delete-*' -exec rm -Rf {} + 2>/dev/null || true
	@for dir in prod dev jetson cloud; do \
		path="../app/var/cache/$$dir"; \
		if [ ! -e "$$path" ]; then \
			continue; \
		fi; \
		tmp="$$path.delete-$$(date +%s)-$$$$"; \
		if mv "$$path" "$$tmp" 2>/dev/null; then \
			rm -Rf "$$tmp" 2>/dev/null || true; \
		else \
			rm -Rf "$$path" 2>/dev/null || true; \
		fi; \
	done
	@echo "Cache has cleared"

console:
	@docker exec --env XDEBUG_MODE=off --workdir=/data --user=www-data ${PHPCLI_CONTAINER} php -dxdebug.mode=off bin/console $(args)

console-jetson:
	@${DOCKER_COMPOSE} exec --env XDEBUG_MODE=off --workdir=/data --user=www-data ${PHPCLI_JETSON_INSTANCE} php -dxdebug.mode=off bin/console $(args)

console-debug:
	@docker exec --workdir=/data --user=www-data ${PHPCLI_CONTAINER} php bin/console $(args)

composer-install: cache-clear
	@rm -Rf ../app/node_modules
	@docker exec --env XDEBUG_MODE=off --env COMPOSER_PROCESS_TIMEOUT=0 --workdir=/data --user=www-data ${PHPCLI_CONTAINER} php -dmemory_limit=5G /usr/local/bin/composer install

cache-warmup: cache-clear
	@docker exec --env XDEBUG_MODE=off --workdir=/data --user=www-data ${PHPCLI_CONTAINER} php -dxdebug.mode=off bin/console cache:warmup --env=prod

cache-warmup-dev: cache-clear
	@docker exec --env XDEBUG_MODE=off --workdir=/data --user=www-data ${PHPCLI_CONTAINER} php -dxdebug.mode=off bin/console cache:warmup --env=dev

cache-warmup-jetson: cache-clear
	@docker exec --env XDEBUG_MODE=off --workdir=/data --user=www-data ${PHPCLI_JETSON_INSTANCE} php -dxdebug.mode=off bin/console cache:warmup --env=jetson

update: cache-clear
	@docker exec --env XDEBUG_MODE=off --workdir=/data --user=www-data ${PHPCLI_CONTAINER} php -dxdebug.mode=off bin/console oro:platform:update --force
	@echo "Oro platform updated"
	@$(MAKE) assets-install

app-update: update

assets-install:
	@docker exec --env XDEBUG_MODE=off --workdir=/data --user=www-data ${PHPCLI_CONTAINER} php -dxdebug.mode=off bin/console oro:assets:install --no-interaction --no-ansi
	@echo "Oro assets installed"

oauth-keys-generate:
	openssl genrsa -out ../app/var/oauth_private.key 2048
	openssl rsa -in ../app/var/oauth_private.key -pubout -out ../app/var/oauth_public.key

# Containers ops
get-in-fpm:
	@${DOCKER_COMPOSE} exec --workdir=/data --user=www-data ${PHPFPM_INSTANCE} /bin/bash

get-in-cli-ubuntu:
	@docker exec --workdir=/data --user=ubuntu -it ${PHPCLI_CONTAINER} /bin/bash

get-in-cli:
	@docker exec --workdir=/data --user=www-data -it ${PHPCLI_CONTAINER} /bin/bash

get-in-cli-root:
	@docker exec --workdir=/data --user=root -it ${PHPCLI_CONTAINER} /bin/bash

get-in-tensorboard:
	@${DOCKER_COMPOSE} exec --workdir=/data --user=www-data ${TENSORBOARD_INSTANCE} /bin/bash

consumer-memory-watch:
	@while true; do \
		clear; \
		date; \
		echo ""; \
		docker exec ${PHPCLI_CONTAINER} sh -lc 'ps -o pid=,rss=,vsz=,etime=,args= -C php | awk '\''/oro:message-queue:consume/ { printf "PID: %s\nRSS: %.1f MB\nVSZ: %.1f MB\nETIME: %s\nCMD: %s\n", $$1, $$2/1024, $$3/1024, $$4, substr($$0, index($$0, $$5)) }'\'''; \
		sleep 2; \
	done

consumer-kill-check:
	@echo "== Container state =="
	@docker inspect ${PHPCLI_CONTAINER} --format 'Name={{.Name}} Status={{.State.Status}} OOMKilled={{.State.OOMKilled}} ExitCode={{.State.ExitCode}} RestartCount={{.RestartCount}} StartedAt={{.State.StartedAt}} FinishedAt={{.State.FinishedAt}}'
	@echo ""
	@echo "== Kill-like log lines =="
	@docker logs ${PHPCLI_CONTAINER} 2>&1 | rg -n 'Killed|Out of memory|OOM|SIGKILL|SIGTERM|exit status 137|137;|terminated|consumer\.ERROR|consumer\.WARNING|WARN exited: oro_message_consumer' -S || true

# GCS bucket management (runs on host; use localhost so emulator hostname resolves)
bucket-empty:
	@GCS_BUCKET_NAME=${GCS_BUCKET_NAME} \
	GCS_BUCKET_DIRECTORY=${GCS_BUCKET_DIRECTORY} \
	STORAGE_EMULATOR_HOST=http://127.0.0.1:4443 \
	python bin/bucket-empty.py
	@echo "Bucket ${GCS_BUCKET_NAME} has been emptied"

bucket-import-tmp-clear:
	@GCS_BUCKET_NAME=${GCS_BUCKET_NAME} \
	GCS_BUCKET_DIRECTORY=data/import_tmp \
	GCS_BUCKET_ONLY_PREFIX=1 \
	STORAGE_EMULATOR_HOST=http://127.0.0.1:4443 \
	python bin/bucket-empty.py
	@echo "Files from import_tmp have been removed"

bucket-delete: bucket-empty
	@curl -v -X DELETE -H "Content-Type: application/json" "http://127.0.0.1:4443/storage/v1/b/${GCS_BUCKET_NAME}"

bucket-create:
	@curl -v -X POST --data-binary '{"name": "${GCS_BUCKET_NAME}"}' -H "Content-Type: application/json" "http://127.0.0.1:4443/storage/v1/b"

# Run train manually
model-train-cli:
	@set -a; . .env-model-runner; set +a; cd ../model && ./runner.sh

model-train-docker:
	@docker exec --env-file .env-model-runner --workdir /model --user www-data ${PHPCLI_CONTAINER} ./runner.sh

model-train-docker-nv:
	@docker exec --env-file .env-model-runner --workdir /model --user www-data ${PHPCLI_JETSON_CONTAINER} ./runner.sh

sam-model-download:
	@./sam-api/download_checkpoint.sh $(or $(variant),tiny)

sam-model-download-tiny:
	@./sam-api/download_checkpoint.sh tiny

sam-model-download-small:
	@./sam-api/download_checkpoint.sh small

sam-model-download-base-plus:
	@./sam-api/download_checkpoint.sh base_plus

sam-model-download-large:
	@./sam-api/download_checkpoint.sh large

# Cron management
cron-run:
	@docker exec --user=root ${PHPCLI_CONTAINER} service cron start

cron-stop:
	@docker exec --user=root ${PHPCLI_CONTAINER} service cron stop

# Supervisord management
supervisord-run:
	@docker exec --user=root ${PHPCLI_CONTAINER} supervisord -c /etc/supervisor/supervisord.conf

supervisord-stop:
	@docker exec --user=root ${PHPCLI_CONTAINER} service supervisor stop	

# Service management
rebuild:
	@$(if $(service),${DOCKER_COMPOSE} ${CONFIG} up -d --build $(service),$(error service is required, usage: make rebuild service=<docker-compose-service>))

# Svelte build commands
svelte-install:
	@cd ../js-components/test-svelte-component && npm install
	@cd ../js-components/svelte-image-editor-component && npm install
	@echo "Svelte dependencies installed"

svelte-build-test: svelte-install
	@cd ../js-components/test-svelte-component && npm run build
	@mkdir -p ../app/src/SyntetiQ/Bundle/ModelBundle/Resources/public/js/svelte
	@mkdir -p ../app/src/SyntetiQ/Bundle/ModelBundle/Resources/public/css/svelte
	@rm -f ../app/src/SyntetiQ/Bundle/ModelBundle/Resources/public/js/svelte/*
	@rm -f ../app/src/SyntetiQ/Bundle/ModelBundle/Resources/public/css/svelte/*
	@cp ../js-components/test-svelte-component/dist/*.js ../app/src/SyntetiQ/Bundle/ModelBundle/Resources/public/js/svelte/
	@find ../js-components/test-svelte-component/dist -maxdepth 1 -type f -name '*.css' -exec cp {} ../app/src/SyntetiQ/Bundle/ModelBundle/Resources/public/css/svelte/ \;
	@echo "Svelte build copied to ModelBundle public/js/svelte and public/css/svelte"
	@$(MAKE) assets-install

svelte-build-image-editor: svelte-install
	@cd ../js-components/svelte-image-editor-component && npm run build
	@mkdir -p ../app/src/SyntetiQ/Bundle/DataSetBundle/Resources/public/js/svelte
	@mkdir -p ../app/src/SyntetiQ/Bundle/DataSetBundle/Resources/public/css/svelte
	@rm -f ../app/src/SyntetiQ/Bundle/DataSetBundle/Resources/public/js/svelte/*
	@rm -f ../app/src/SyntetiQ/Bundle/DataSetBundle/Resources/public/css/svelte/*
	@cp ../js-components/svelte-image-editor-component/dist/*.js ../app/src/SyntetiQ/Bundle/DataSetBundle/Resources/public/js/svelte/
	@find ../js-components/svelte-image-editor-component/dist -maxdepth 1 -type f -name '*.css' -exec cp {} ../app/src/SyntetiQ/Bundle/DataSetBundle/Resources/public/css/svelte/ \;
	@echo "Svelte build copied to DataSetBundle public/js/svelte and public/css/svelte"
	@$(MAKE) assets-install
	
svelte-dev:
	@cd ../js-components/test-svelte-component && npm run dev
	@cd ../js-components/svelte-image-editor-component && npm run dev
