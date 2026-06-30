# Contribuer au projet

Ce document rassemble les informations utiles pour reprendre et faire évoluer ce
projet sans réintroduire de dette technique déjà identifiée. Il ne définit pas
une organisation d'équipe complète : la procédure exacte de contribution dépend
de l'organisation retenue par le client après la passation, notamment le logiciel de ticketing.

## Points d'attention propres au projet

- le Makefile recense des commandes utiles, avec leurs paramètres, ainsi que des enchainements de commandes, afin de faciliter leur utilisation.
    Il peut être utile de le garder à jour. Et il est obligatoire de maintenir les commandes associées à la CI.
    cf `.github/workflows/symfony.yml`
- `backup/` sert à reconstruire les bases via le Makefile. Les gros dumps ou
  archives de sauvegarde ne sont pas destinés à être ajoutés au dépôt.
- `APP_PUBLIC_DIR` permet de séparer les fichiers publics utilisés en
  développement et ceux manipulés par les tests.
- Les tests fonctionnels s'appuient sur PostgreSQL et sur les imports SQL de
  `backup/`.
- `app:migrate-inmemory-users --dry-run` ne persiste pas, mais lit quand même la
  base.

## Tests

La suite de tests couvre les principales fonctionnalités du projet :

- authentification ;
- uploads et validation des médias ;
- gestion des médias ;
- révocation, restauration et suppression d'invités ;
- smoke tests sur les routes publiques et administrées ;
- commandes Symfony utiles à la reprise du projet.

Toutes les fonctionnalités sont représentées, mais elles peuvent etre encore enrichies par des vérifications supplémentaires.
En l'état, ajouter ou adapter un test lors d'une nouvelle fonctionnalité ou lorsqu'une fonctionnalité évolue permet de garder l'application protégée
contre les régressions et de la rendre plus facilement refactorisable et évolutive.

Préparation de la base de test :

```bash
make db-test
```

Exécution sans couverture :

```bash
php bin/phpunit --no-coverage
```

Exécution avec couverture :

```bash
php bin/phpunit
```

## Vérifications utiles

Linters Symfony :

```bash
make symfony-linters-execute
```

Analyse statique :

```bash
vendor/bin/phpstan analyse -c phpstan.dist.neon --no-progress
```

Rector en lecture seule :

```bash
vendor/bin/rector process --dry-run
```

## Contribuer : process

### Problèmes et demandes d'évolution

La définition d'un processus de remontée d'anomalies ou de demande fonctionnelle
relève de l'organisation interne du client après la passation.

À défaut de processus défini côté client, les anomalies ou évolutions doivent
être décrites avec le contexte de reproduction, le comportement attendu et les
zones applicatives concernées.

### Style de code

Le projet suit les conventions Symfony appliquées par PHP-CS-Fixer. La
configuration est définie dans `.php-cs-fixer.dist.php`.

Le projet suit les standards Symfony via PHP-CS-Fixer.
Le preset `@Symfony` s'appuie sur PER Coding Style 3.0 de PHP-FIG, qui étend PSR-12 et requiert le respect de PSR-1.

Le projet surcharge aussi l'alignement des opérateurs `=>` avec la règle
`binary_operator_spaces`.

Vérification sans modification :

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Correction automatique :

```bash
vendor/bin/php-cs-fixer fix
```

Il est recommandé de configurer PHP-CS-Fixer dans l'IDE, notamment dans
PhpStorm. Cela permet d'obtenir des warnings presque immédiatement pendant le
développement et de demander à l'IDE d'appliquer les corrections automatiques
via PHP-CS-Fixer lorsque c'est pertinent.

### Pull requests
**En solo :**
Les pull requests sont utilisées comme étape de validation avant intégration dans
les branches principales du projet.
Utilisées comme trace de changement et déclencheur CI.

Avant fusion, il faut vérifier que la CI est en succès et que les tests ou
vérifications utiles au changement ont été lancés localement si nécessaire.

### Code review
**En solo :**
L'objectif est de relire le changement avant intégration : comportement modifié,
tests associés, impact sur la CI et cohérence avec les points d'attention du projet.

### Bonnes pratiques
- Utiliser au mieux un message de commit explicite et concis, par ex en se basant sur [Best Practices for Git Commit](https://www.baeldung.com/ops/git-commit-messages)
- Utiliser PHP-CS-Fixer pour continuer d'uniformiser le style de code, en se rapprochant des styles couramment utilisés.
- Utiliser PHPStan pour prévenir certains bugs et faire les montées de version.
- Maintenir la CI pour automatiser les tests et vérifications afin d'éviter les dérives
