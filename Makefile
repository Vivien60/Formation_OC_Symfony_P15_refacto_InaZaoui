db-test:
	php bin/console doctrine:database:drop -f --if-exists --env=test
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:migrations:migrate -n --env=test
	php bin/console doctrine:fixtures:load -n --purge-with-truncate --env=test

test: db-test
	php bin/phpunit --testdox --coverage-html "public/test-coverage/"  --filter tests

test-unit: db-test
	php bin/phpunit --testdox --coverage-html "public/test-coverage/"  --filter Unit

test-no-coverage: db-test
	php bin/phpunit --testdox --filter tests --no-coverage

test-config-github-actions:
	time docker stop formation_oc_symfony_p14_phpunit-exercice-1-postgres-1
	act --pull=false -j symfony-tests --container-options "--user 0"
	docker start formation_oc_symfony_p14_phpunit-exercice-1-postgres-1

db-create:
	docker compose up -d
	symfony console doctrine:database:drop --if-exists --force
	symfony console doctrine:database:create
	symfony console doctrine:migrations:migrate
