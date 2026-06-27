<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

class RevocateUserTest extends FunctionalTestCase
{
    public static function users(): iterable
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
        ];
    }

    #[DataProvider('users')]
    public function testRevocateUserByAdmin($id, $name)
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
    public function testConnectAsRevocatedUser($login, $password)
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
    }
}
