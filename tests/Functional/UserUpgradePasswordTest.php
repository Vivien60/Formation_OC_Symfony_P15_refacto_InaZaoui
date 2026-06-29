<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

class UserUpgradePasswordTest extends FunctionalTestCase
{
    public function testRevocateUserByAdmin()
    {
        self::markTestIncomplete("A implémenter");
        $this->login(self::NON_ADMIN_IDENTIFIER);
        $crawler = $this->client->request('GET', '/login');
    }
}
