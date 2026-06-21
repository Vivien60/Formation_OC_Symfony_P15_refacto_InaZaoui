db-test:
	docker compose up -d --wait
	php bin/console doctrine:database:drop -f --if-exists --env=test
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui_test < backup/user.sql
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui_test < backup/album.sql
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui_test < backup/media.sql
	php bin/console doctrine:query:sql "SELECT setval('media_id_seq', (SELECT COALESCE(MAX(id), 1) FROM media), true)" --env=test
	php bin/console doctrine:query:sql "SELECT setval('album_id_seq', (SELECT COALESCE(MAX(id), 1) FROM \"album\"), true)" --env=test
	php bin/console doctrine:query:sql "SELECT setval('user_id_seq', (SELECT COALESCE(MAX(id), 1) FROM \"user\"), true)" --env=test

test: db-test
	php bin/phpunit --testdox --coverage-html "public/test-coverage/"  --filter tests

test-unit: db-test
	php bin/phpunit --testdox --coverage-html "public/test-coverage/"  --filter Unit

test-no-coverage: db-test
	php bin/phpunit --testdox --filter tests --no-coverage

test-config-github-actions:
	docker stop ina_zaoui-postgres-1
	time act --pull=false -j symfony-tests --container-options "--user 0"
	docker start ina_zaoui-postgres-1

db-create:
	docker compose up -d --wait
	symfony console doctrine:database:drop --if-exists --force
	symfony console doctrine:database:create
	symfony console doctrine:migrations:migrate --no-interaction
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui < backup/user.sql
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui < backup/album.sql
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui < backup/media.sql
	php bin/console doctrine:query:sql "SELECT setval('media_id_seq', (SELECT COALESCE(MAX(id), 1) FROM media), true)"
	php bin/console doctrine:query:sql "SELECT setval('album_id_seq', (SELECT COALESCE(MAX(id), 1) FROM \"album\"), true)"
	php bin/console doctrine:query:sql "SELECT setval('user_id_seq', (SELECT COALESCE(MAX(id), 1) FROM \"user\"), true)"

migrate-users:
	php bin/console app:migrate-inmemory-users

symfony-linters-execute:
	php bin/console lint:yaml config
	php bin/console lint:twig templates
	php bin/console lint:container
	php bin/console doctrine:schema:validate --skip-sync
