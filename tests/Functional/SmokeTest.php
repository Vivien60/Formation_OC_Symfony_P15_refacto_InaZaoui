<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Smoke tests : on ne vérifie QUE le code de retour HTTP des routes.
 *
 * Aucune logique métier testée. Rôle : filet pour l'upgrade Symfony —
 * détecte les explosions de boot kernel, routing, rendu Twig et firewall.
 *
 * Les routes "data" interrogent la base (Postgres de test peuplé, id à partir
 * de 1). On ne vérifie jamais le contenu, seulement le statut HTTP.
 */
class SmokeTest extends FunctionalTestCase
{

    #[DataProvider('publicSuccessfulRoutes')]
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
     */
    #[DataProvider('databaseBackedRoutes')]
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
     */
    #[DataProvider('protectedRoutes')]
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
     */
    #[DataProvider('authenticatedAdminRoutes')]
    public function testAuthenticatedAdminRouteIsSuccessful(string $url): void
    {
        $this->login('ina@zaoui.com');
        $this->client->request('GET', $url);

        $this->assertCurrentResponseIsSuccessful();
    }

    public static function authenticatedAdminRoutes(): iterable
    {
        yield 'admin album index' => ['/admin/album'];
        yield 'admin media index' => ['/admin/media'];
    }

    /**
     * POST mutant : édition d'un album existant (id 1). Succès = redirection 302
     * vers l'index. Mutation annulée par DAMA.
     */
    public function testAdminUpdateAlbumRedirects(): void
    {
        $this->login('ina@zaoui.com');
        $this->client->request('GET', '/admin/album/update/1');
        $this->client->submitForm('Modifier', [
            'album[name]' => 'Smoke test album updated',
        ]);

        $this->assertResponseRedirects('/admin/album');
    }

    /**
     * Suppression d'un album existant (id 1). La route supprime sur un simple
     * GET puis redirige (302). DAMA restaure la ligne après le test.
     */
    public function testAdminDeleteAlbumRedirects(): void
    {
        $this->login('ina@zaoui.com');
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
                rawurldecode((string) $response->headers->get('X-Debug-Exception', 'none'))
            )
        );
    }
}
