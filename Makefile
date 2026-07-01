db-create:
	docker compose up -d --wait
	docker exec -i ina_zaoui-postgres-1 psql -U postgres -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'ina_zaoui' AND pid <> pg_backend_pid();"
	symfony console doctrine:database:drop --if-exists --force
	symfony console doctrine:database:create
	symfony console doctrine:migrations:migrate 'DoctrineMigrations\Version20260613234928' --no-interaction
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui < backup/user.sql
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui < backup/album.sql
	docker exec -i ina_zaoui-postgres-1 psql -q -v ON_ERROR_STOP=1 -U postgres -d ina_zaoui < backup/media.sql
	symfony console doctrine:migrations:migrate  --no-interaction
	php bin/console dbal:run-sql "SELECT setval('media_id_seq', (SELECT COALESCE(MAX(id), 1) FROM media), (SELECT COUNT(*) > 0 FROM media))"
	php bin/console dbal:run-sql "SELECT setval('album_id_seq', (SELECT COALESCE(MAX(id), 1) FROM \"album\"), (SELECT COUNT(*) > 0 FROM album))"
	php bin/console dbal:run-sql "SELECT setval('user_id_seq', (SELECT COALESCE(MAX(id), 1) FROM \"user\"), (SELECT COUNT(*) > 0 FROM user))"

db-test:
	docker compose up -d --wait
	docker exec -i ina_zaoui-postgres-1 psql -U postgres -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'ina_zaoui_test' AND pid <> pg_backend_pid();"
	php bin/console doctrine:database:drop -f --if-exists --env=test
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:migrations:migrate --no-interaction --env=test
	php bin/console doctrine:fixtures:load --no-interaction --env=test

test:
	php bin/phpunit --testdox --coverage-html "var/coverage/"

test-unit:
	php bin/phpunit --testdox --coverage-html "var/coverage/"  --filter Unit

test-no-coverage:
	php bin/phpunit --testdox --no-coverage

test-config-github-actions:
	docker stop ina_zaoui-postgres-1
	time act --pull=false -j symfony-tests --container-options "--user 0"
	docker start ina_zaoui-postgres-1

migrate-users:
	php bin/console app:migrate-inmemory-users

symfony-linters-execute:
	php bin/console lint:yaml config
	php bin/console lint:twig templates
	php bin/console lint:container
	php bin/console doctrine:schema:validate --skip-sync
