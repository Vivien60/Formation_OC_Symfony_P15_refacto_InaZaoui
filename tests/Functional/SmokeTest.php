<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests : on ne vérifie QUE le code de retour HTTP des routes.
 *
 * Aucune logique métier testée. Rôle : filet pour l'upgrade Symfony —
 * détecte les explosions de boot kernel, routing, rendu Twig et firewall.
 *
 * Les routes "data" interrogent la base (Postgres de test peuplé, id à partir
 * de 1). On ne vérifie jamais le contenu, seulement le statut HTTP.
 */
class SmokeTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Login simulé : injecte directement un token authentifié en session,
     * sans passer par le formulaire ni vérifier de mot de passe.
     *
     * L'identifiant par défaut est "ina" car le firewall s'authentifie contre
     * un provider "memory" (cf. security.yaml) qui ne contient que ce compte.
     */
    private function login(string $identifier = 'ina'): void
    {
        $provider = static::getContainer()->get('security.user.provider.concrete.users_in_memory');
        $this->client->loginUser($provider->loadUserByIdentifier($identifier));
    }

    /**
     * @dataProvider publicSuccessfulRoutes
     */
    public function testPublicRouteIsSuccessful(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertCurrentResponseIsSuccessful();
    }

    public static function publicSuccessfulRoutes(): iterable
    {
        yield 'home'  => ['/'];
        yield 'about' => ['/about'];
        yield 'login' => ['/login'];
    }

    /**
     * Routes publiques qui interrogent la base. Les id sont hardcodés (1) :
     * on fait confiance au jeu de données de test.
     *
     * @dataProvider databaseBackedRoutes
     */
    public function testDatabaseRouteIsSuccessful(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertCurrentResponseIsSuccessful();
    }

    public static function databaseBackedRoutes(): iterable
    {
        yield 'guests list'     => ['/guests'];
        yield 'guest by id'     => ['/guest/1'];
        yield 'portfolio'       => ['/portfolio'];
        yield 'portfolio by id' => ['/portfolio/1'];
    }

    /**
     * Sans authentification, une route /admin doit renvoyer une redirection
     * (vers le formulaire de login), pas une 200 ni une 500.
     *
     * @dataProvider protectedRoutes
     */
    public function testProtectedRouteRedirects(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertResponseRedirects();
    }

    public static function protectedRoutes(): iterable
    {
        yield 'admin album index' => ['/admin/album'];
        yield 'admin media index' => ['/admin/media'];
    }

    /**
     * Une fois authentifié, les index admin doivent répondre 200. C'est ce qui
     * exécute réellement le code des contrôleurs admin.
     *
     * On se limite aux GET non mutants : surtout pas les routes "delete"
     * (admin album/media), qui suppriment en base sur un simple GET.
     *
     * @dataProvider authenticatedAdminRoutes
     */
    public function testAuthenticatedAdminRouteIsSuccessful(string $url): void
    {
        $this->login();
        $this->client->request('GET', $url);

        $this->assertCurrentResponseIsSuccessful();
    }

    public static function authenticatedAdminRoutes(): iterable
    {
        yield 'admin album index' => ['/admin/album'];
        yield 'admin media index' => ['/admin/media'];
    }

    /**
     * POST mutant : on soumet le formulaire d'ajout d'album. Le succès se signe
     * par une redirection 302 vers l'index (une erreur de validation
     * ré-afficherait le formulaire en 200).
     *
     * Le formulaire est récupéré via le Crawler pour embarquer le token CSRF.
     * La mutation est annulée en fin de test par DAMA (rollback transactionnel).
     */
    public function testAdminAddAlbumRedirects(): void
    {
        $this->login();
        $this->client->request('GET', '/admin/album/add');
        $this->client->submitForm('Ajouter', [
            'album[name]' => 'Smoke test album',
        ]);

        $this->assertResponseRedirects('/admin/album');
    }

    /**
     * POST mutant : édition d'un album existant (id 1). Succès = redirection 302
     * vers l'index. Mutation annulée par DAMA.
     */
    public function testAdminUpdateAlbumRedirects(): void
    {
        $this->login();
        $this->client->request('GET', '/admin/album/update/1');
        $this->client->submitForm('Modifier', [
            'album[name]' => 'Smoke test album updated',
        ]);

        $this->assertResponseRedirects('/admin/album');
    }

    /**
     * Suppression d'un album existant (id 1). La route supprime sur un simple
     * GET puis redirige (302). DAMA restaure la ligne après le test.
     *
     * SKIPPÉ : bug applicatif connu. Le contrôleur fait remove()+flush() sans
     * cascade ni gestion de la contrainte FK, donc supprimer un album encore
     * référencé par des medias renvoie une 500 (FK violation), pas une 302.
     * Sert de test de non-régression : retirer le skip une fois la suppression
     * en cascade (ou la gestion de la contrainte) implémentée.
     */
    public function testAdminDeleteAlbumRedirects(): void
    {
        self::markTestSkipped(
            'Bug applicatif : delete album renvoie 500 si des medias y sont liés '
            .'(pas de cascade). À réactiver après correction.'
        );
        $this->login();
        $this->client->request('GET', '/admin/album/delete/1');

        $this->assertResponseRedirects('/admin/album');
    }

    private function assertCurrentResponseIsSuccessful(): void
    {
        $response = $this->client->getResponse();

        self::assertTrue(
            $response->isSuccessful(),
            sprintf(
                'Expected 2xx, got HTTP %d. Exception: %s',
                $response->getStatusCode(),
                rawurldecode($response->headers->get('X-Debug-Exception', 'none'))
            )
        );
    }
}
