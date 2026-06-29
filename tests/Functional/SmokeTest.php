<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;

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
    public static function publicSuccessfulRoutes(): iterable
    {
        yield 'home'  => ['/'];
        yield 'about' => ['/about'];
        yield 'login' => ['/login'];
    }

    public static function databaseBackedRoutes(): iterable
    {
        yield 'guests list'     => ['/guests'];
        yield 'guest by id'     => ['/guest/1'];
        yield 'portfolio'       => ['/portfolio'];
        yield 'portfolio by id' => ['/portfolio/1'];
    }

    public static function protectedRoutes(): iterable
    {
        yield 'admin album index' => ['/admin/album'];
        yield 'admin media index' => ['/admin/media'];
        yield 'admin guest index' => ['/admin/guest'];
    }

    public static function authenticatedAdminRoutes(): iterable
    {
        yield 'admin album index' => ['/admin/album'];
        yield 'admin media index' => ['/admin/media'];
        yield 'admin guest index' => ['/admin/guest'];
    }

    #[DataProvider('publicSuccessfulRoutes')]
    public function testPublicRouteIsSuccessful(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertCurrentResponseIsSuccessful();
    }

    /**
     * Routes publiques qui interrogent la base.
     */
    #[DataProvider('databaseBackedRoutes')]
    public function testDatabaseRouteIsSuccessful(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertCurrentResponseIsSuccessful();
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

    /**
     * Une fois authentifié, les index admin doivent répondre 200. C'est ce qui
     * exécute réellement le code des contrôleurs admin.
     *
     * On se limite aux GET non mutants : surtout pas les routes "delete"
     * (admin album/media), qui suppriment en base sur un simple GET.
     */
    #[DataProvider('
  
  ')]
    public function testAuthenticatedAdminRouteIsSuccessful(string $url): void
    {
        $this->login('ina@zaoui.com');
        $this->client->request('GET', $url);

        $this->assertCurrentResponseIsSuccessful();
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

    public function testAdminDeleteMediaRedirects(): void
    {
        $this->resetTestUploadsDirectory();
        $this->login(self::ADMIN_IDENTIFIER);
        $crawler = $this->client->request('GET', '/admin/media');
        $form = $crawler
            ->filterXPath('//form[contains(@class, "delete-media__form") and contains(@action, "/admin/media/delete/4")]')
            ->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/media');
    }

    private function resetTestUploadsDirectory(): void
    {
        $source = self::getContainer()->getParameter('kernel.project_dir').'/public';
        $target = self::getContainer()->getParameter('app.public_dir');
        $filesystem = new Filesystem();
        $filesystem->remove($target);
        $filesystem->mirror($source, $target);
    }

    public function testAdminDeleteGuestRedirects(): void
    {
        $this->login(self::ADMIN_IDENTIFIER);
        $crawler = $this->client->request('GET', '/admin/guest');
        $form = $crawler
            ->filterXPath(
                xpath: '//form[contains(@class, "delete-form") and contains(@action, "/admin/guest/delete/5")]')
            ->form();

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/guest');
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
