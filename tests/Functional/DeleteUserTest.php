<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

class DeleteUserTest extends FunctionalTestCase
{
    public static function users(): iterable
    {
        yield 'delete user by admin' => [
            'id'   => 5,
            'name' => 'Invité 3',
            'nameNotDeleted' => 'Invité 0',
        ];
    }

    #[DataProvider('users')]
    public function testDeleteUserByAdmin($id, $name, $nameNotDeleted)
    {
        $this->login(self::ADMIN_IDENTIFIER);
        $crawler = $this->client->request('GET', '/admin/guest');
        $form = $crawler
            ->filterXPath(
                xpath: sprintf('//form[contains(@class, "delete-form") and contains(@action, "/admin/guest/delete/%s")]', $id))
            ->form();
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/guest');
        $crawler = $this->client->followRedirect();

        $this->assertAnySelectorTextContains('h1', 'Invités');
        $this->assertSelectorTextNotContains('td', $name);
        $this->assertAnySelectorTextContains('td', $nameNotDeleted);
    }
}
