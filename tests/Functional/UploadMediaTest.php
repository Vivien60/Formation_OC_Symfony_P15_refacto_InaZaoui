<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadMediaTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testUploadMediaShouldFail(): void
    {
        $this->login();
        $this->get('admin/media/add');
        $pathImg3Mo = __DIR__.DIRECTORY_SEPARATOR.'test_toobig.jpg';
        $this->client->submitForm('Ajouter', [
            'media[title]' => 'Test image',
            'media[file]' => new UploadedFile($pathImg3Mo, 'toobig.jpg', 'image/jpeg', null, true),
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'Allowed maximum size is 2 MB');
    }

    #[DataProvider('usersProvider')]
    public function testUploadMediaShouldSucceed(mixed $user): void
    {
        $this->login($user);
        $this->get('admin/media/add');
        $pathImg3Mo = __DIR__.DIRECTORY_SEPARATOR.'test_ok.jpg';
        $this->client->submitForm('Ajouter', [
            'media[user]' => '1',
            'media[album]' => '1',
            'media[title]' => 'Test image',
            'media[file]' => new UploadedFile($pathImg3Mo, 'ok.jpg', 'image/jpeg', null, true),
        ]);
        $this->assertResponseRedirects('/admin/media');
        $this->get('/portfolio/1');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('.media-title', 'Test image');
    }

    public static function usersProvider(): iterable
    {
        yield 'user admin' => ['ina'];
        yield 'user non admin' => ['marie'];
    }
}
