<?php

namespace App\Tests\Functional;

/**
 * Smoke tests : on ne vérifie QUE le code de retour HTTP des routes.
 *
 * Aucune logique métier testée. Rôle : filet pour l'upgrade Symfony —
 * détecte les explosions de boot kernel, routing, rendu Twig et firewall.
 *
 * Les routes "data" interrogent la base (Postgres de test peuplé, id à partir
 * de 1). On ne vérifie jamais le contenu, seulement le statut HTTP.
 */
class MediaManagementTest extends FunctionalTestCase
{
    /**
     * Tests that adding a media item to an album is successful.
     *
     * This function performs the following steps:
     * - Logs in as an administrator using a predefined identifier.
     * - Fetches the media management*/
    public function testAddingMediaToAlbumIsSuccessful(): void
    {
        $this->login(self::ADMIN_IDENTIFIER);
        $this->get('/admin/media');
        $this->assertCurrentResponseIsSuccessful();
        $mediaLib = 'Photo 3';
        $mediaId = 4;
        $albumLib = 'Album 3';
        $albumId = 4;

        $row = $this->client->getCrawler()->filterXPath(sprintf("//tr[contains(., '%s')]", $mediaLib));
        $form = $row->filter('form')->form();
        $values = [
            'id'    => $mediaId,
            'album' => $albumId,
        ];
        $this->client->submit($form, $values);
        $this->assertCurrentResponseIsSuccessful();

        $row = $this->client->getCrawler()->filterXPath(sprintf("//tr[contains(., '%s')]", $mediaLib));
        $album = $row->filterXPath(sprintf("//tr[contains(., '%s')]/td[contains(text(), '%s')]", $mediaLib, $albumLib));
        $this->assertNotEmpty($album, 'L\'album n\'a pas été associé au média');
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
