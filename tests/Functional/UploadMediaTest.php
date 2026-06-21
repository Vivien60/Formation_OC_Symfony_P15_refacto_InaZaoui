<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadMediaTest extends FunctionalTestCase
{
    private const ADMIN_IDENTIFIER = 'ina@zaoui.com';
    private const NON_ADMIN_IDENTIFIER = 'marie@example.com';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testShouldShowUploadForm(): void
    {
        $this->login(self::ADMIN_IDENTIFIER);
        $this->get('admin/media/add');
        $this->assertResponseIsSuccessful();
    }

    #[Depends('testShouldShowUploadForm')]
    #[DataProvider('validAdminMediaForm')]
    public function testAdminCanUploadMedia(array $formFields): void
    {
        // Given
        $this->login(self::ADMIN_IDENTIFIER);
        $this->get('admin/media/add');
        // When
        $this->postMedia($formFields);
        // Then
        $this->assertMediaCanBeUploadedAs(self::ADMIN_IDENTIFIER, $formFields);
        $this->assertMediaAddedToAlbum($formFields['media[album]'], $formFields['media[title]']);
    }

    #[Depends('testShouldShowUploadForm')]
    #[DataProvider('validNonAdminMediaForm')]
    public function testNonAdminCanUploadMedia(array $formFields): void
    {
        $this->markTestIncomplete('upload by non admin need to be implemented');
        $this->login(self::NON_ADMIN_IDENTIFIER);
        $this->postMedia($formFields);
        $this->assertMediaCanBeUploadedAs(self::NON_ADMIN_IDENTIFIER, $formFields);
    }

    protected function postMedia(array $formFields): void
    {
        $this->client->submitForm('Ajouter', $formFields);
    }

    private function assertMediaCanBeUploadedAs(string $userIdentifier, array $formFields): void
    {
        $this->assertResponseRedirects('/admin/media');
    }

    private function assertMediaAddedToAlbum(string $albumId, string $title): void
    {
        $this->get(sprintf('/portfolio/%s', $albumId));
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('.media-title', $title);
    }

    public static function validAdminMediaForm(): iterable
    {
        yield 'admin upload media and add it to album as user' => [[
            'media[title]' => 'Test image',
            'media[user]'  => '1',
            'media[album]' => '1',
            'media[file]'  => self::uploadedFileOk(),
        ]];
    }

    public static function validNonAdminMediaForm(): iterable
    {
        yield 'normal user upload media' => [[
            'media[title]' => 'Test image',
            'media[file]'  => self::uploadedFileOk(),
        ]];
    }

    private static function uploadedFileTooBig(): UploadedFile
    {
        return static::uploadedFile('test_toobig.jpg');
    }

    private static function uploadedFileOk(): UploadedFile
    {
        return static::uploadedFile('test_ok.jpg');
    }

    private static function uploadedFile(string $name): UploadedFile
    {
        return new UploadedFile(__DIR__.'/'.$name, $name, 'image/jpeg', null, true);
    }
}
