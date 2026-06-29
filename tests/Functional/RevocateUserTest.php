<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\User\UserInterface;

class RevocateUserTest extends FunctionalTestCase
{
    public static function validUsers(): iterable
    {
        yield 'revocate user by admin' => [
            'id'               => 102,
            'name'             => 'marie',
        ];
    }

    public static function revokedUsers(): iterable
    {
        yield 'user revocated' => [
            'login'            => 'invite+0@example.com',
            'password'         => 'password',
            'name'             => 'Invité 0',
            'id'               => 2,
        ];
    }

    public static function revokedUsersWithMedias(): iterable
    {
        $revokedUsers = self::revokedUsers();
        $revokedUser = $revokedUsers->current();
        yield 'user revocated with medias' => [
            'mediaTitle' => 'Titre 0',
            'user'       => $revokedUser,
        ];
    }

    #[DataProvider('validUsers')]
    public function testRevocateUserByAdmin(int $id, string $name)
    {
        $this->login(self::ADMIN_IDENTIFIER);
        $crawler = $this->client->request('GET', '/admin/guest');
        $form = $crawler
            ->filterXPath(
                xpath: sprintf('//form[contains(@class, "revocate-form") and contains(@action, "/admin/guest/revocate/%s")]', $id))
            ->form();
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/guest');
        $crawler = $this->client->followRedirect();

        $this->assertAnySelectorTextContains('h1', 'Invités');

        $revokedUserRow = $crawler->filterXPath(sprintf(
            '//tr[td[normalize-space(.) = "%s"]]',
            $name
        ));
        self::assertCount(1, $revokedUserRow, 'L\'utilisateur est affiché dans la liste');
        self::assertCount(0, $revokedUserRow->filterXPath('.//form[contains(@class, "revocate-form")]'), 'Le bouton permettant de révoquer ne s\'affiche plus après révocation de l\'invité');
        self::assertCount(1, $revokedUserRow->filterXPath('.//form[contains(@class, "reinstate-form")]'), 'Le bouton "Restaurer l\'accès s\'affiche pour l\'utilisateur révoqué');
    }
    #[DataProvider('revokedUsers')]
    public function testReinstateUserByAdmin(int $id, string $name, string $login, string $password)
    {
        $this->login(self::ADMIN_IDENTIFIER);
        $crawler = $this->client->request('GET', '/admin/guest');
        $form = $crawler
            ->filterXPath(
                xpath: sprintf('//form[contains(@class, "reinstate-form") and contains(@action, "/admin/guest/reinstate/%s")]', $id))
            ->form();
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/guest');
        $crawler = $this->client->followRedirect();

        $this->assertAnySelectorTextContains('h1', 'Invités');

        $reinstatedUserRow = $crawler->filterXPath(sprintf(
            '//tr[td[normalize-space(.) = "%s"]]',
            $name
        ));
        self::assertCount(1, $reinstatedUserRow, 'L\'utilisateur est affiché dans la liste');
        self::assertCount(1, $reinstatedUserRow->filterXPath('.//form[contains(@class, "revocate-form")]'), 'Le bouton permettant de révoquer ne s\'affiche plus après révocation de l\'invité');
        self::assertCount(0, $reinstatedUserRow->filterXPath('.//form[contains(@class, "reinstate-form")]'), 'Le bouton "Restaurer l\'accès s\'affiche pour l\'utilisateur révoqué');
        $this->get('/guests');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.guests', $name);
        $this->get('/portfolio');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('.media-title', 'Titre 0');
        $this->get('/login');
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('Connexion',
            [
                '_username' => $login,
                '_password' => $password,
            ]);
        $token = $this->client->getContainer()->get('security.token_storage')->getToken();
        $this->assertTrue($token->getUser() instanceof UserInterface, 'L\'utilisateur restauré est bien authentifié');
    }

    #[DataProvider('revokedUsers')]
    public function testConnectAsRevocatedUser(string $login, string $password, string $name, int $id)
    {
        $this->get('/login');
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('Connexion',
            [
                '_username' => $login,
                '_password' => $password,
            ]);
        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorTextContains(selector: '.alert', text: 'Accès révoqué');
        $token = $this->client->getContainer()->get('security.token_storage')->getToken();
        $this->assertFalse(null !== $token && $token->getUser() instanceof UserInterface, 'L\'utilisateur révoqué est bien bloqué  à l\'authentification');
    }

    #[DataProvider('revokedUsers')]
    public function testRevocatedUserDoesNotAppearOnGuestsPage(string $login, string $password, string $name, int $id): void
    {
        $this->get('/guests');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('.guests', $name);
    }

    #[DataProvider('revokedUsersWithMedias')]
    public function testRevocatedUserDoesNotAppearOnPortfolioPage(string $mediaTitle, array $user): void
    {
        $this->get('/portfolio');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('.media-title', $mediaTitle);
    }
}
