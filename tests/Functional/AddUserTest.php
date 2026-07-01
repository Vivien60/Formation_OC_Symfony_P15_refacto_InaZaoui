<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

class AddUserTest extends FunctionalTestCase
{
    public function testAddUserByAdmin()
    {
        $email = 'test+0@test.test';
        $passwd = 'password';
        $this->login(self::ADMIN_IDENTIFIER);
        $crawler = $this->client->request('GET', '/admin/guest/add');
        $form = $crawler
            ->filterXPath(
                xpath: '//form[contains(@name, "guest")]')
            ->form();
        $form['guest[name]'] = 'test 0';
        $form['guest[email]'] = $email;
        $form['guest[plainPassword]'] = $passwd;
        $form['guest[admin]'] = '0';
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/guest');

        $crawler = $this->client->followRedirect();
        $this->assertAnySelectorTextContains('h1', 'Invités');
        $this->assertAnySelectorTextContains('td', 'test 0');

        $this->get('/logout');
        $this->get('/login');
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('Connexion', [
            '_username' => $email,
            '_password' => $passwd,
        ]);
        $token = $this->client->getContainer()->get('security.token_storage')->getToken();
        $this->assertNotNull($token, "L'utilisateur est connecté");
    }
}
