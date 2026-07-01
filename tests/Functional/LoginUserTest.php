<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;

class LoginUserTest extends FunctionalTestCase
{
    public static function validUsers(): iterable
    {
        yield 'invité non révoqué' => [
            'login'    => self::NON_ADMIN_IDENTIFIER,
            'email'    => self::NON_ADMIN_IDENTIFIER,
            'password' => 'password',
            'name'     => self::NON_ADMIN_NAME,
        ];
    }

    #[DataProvider('validUsers')]
    public function testSuccessfulLogin(string $login, string $password, string $name, string $email): void
    {
        $this->get('/login');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Connexion', [
            '_username' => $login,
            '_password' => $password,
        ]);

        $token = $this->client->getContainer()->get('security.token_storage')->getToken();
        $this->assertNotNull($token, 'Un token est présent après connexion');

        $user = $token->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($login, $user->getLogin());
        $this->assertSame($name, $user->getName());
        $this->assertSame($email, $user->getEmail());
        $this->assertFalse($user->isRevocated());
    }
}
