<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->service(EntityManagerInterface::class);
    }

    /**
     * @template T
     *
     * @param class-string<T> $id
     *
     * @return T of object
     */
    protected function service(string $id)
    {
        return $this->client->getContainer()->get($id);
    }

    protected function get(string $uri, array $parameters = []): Crawler
    {
        return $this->client->request('GET', $uri, $parameters);
    }

    /**
     * Login simulé : injecte directement un token authentifié en session,
     * sans passer par le formulaire ni vérifier de mot de passe.
     */
    protected function login(string $identifier): void
    {
        $provider = static::getContainer()->get('security.user.provider.concrete.user_provider');

        $this->client->loginUser($provider->loadUserByIdentifier($identifier));
    }
    /**
     * @param array<string, mixed> $formData
     */
    protected function submit(string $button, array $formData = [], string $method = 'POST'): Crawler
    {
        return $this->client->submitForm($button, $formData, $method);
    }
}
