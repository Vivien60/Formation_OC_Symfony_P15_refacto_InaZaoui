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

    public static function adminMediaForm(): iterable
    {
        yield 'admin upload media and add it to album as user' => [
            'formFields' => [
                'media[title]' => 'Test image',
                'media[user]'  => '1',
                'media[album]' => '1',
                'media[file]'  => self::uploadedFileOk(),
            ],
        ];
    }

    public static function nonAdminMediaForm(): iterable
    {
        yield 'normal user upload media' => [
            'formFields' => [
                'media[title]' => 'Test image',
                'media[file]'  => self::uploadedFileOk(),
            ]];
    }

    public static function fileIsTooBigForm(): iterable
    {
        yield 'file is too big' => [
            'formFields' => [
                'media[title]'  => 'Test image',
                'media[user]'   => '1',
                'media[album]'  => '1',
                'media[file]'   => self::uploadedFileTooBig(),
            ],
            'userIdentifier'    => self::ADMIN_IDENTIFIER,
            'expected'          => [
                'code' => 422,
            ],
        ];
    }

    public function testAdminShouldAccessUploadForm(): void
    {
        $this->login(self::ADMIN_IDENTIFIER);
        $this->get('/admin/media/add');
        $this->assertResponseIsSuccessful();
    }

    public function testNonAdminShouldAccessUploadForm(): void
    {
        $this->login(self::NON_ADMIN_IDENTIFIER);
        $this->get('/admin/media/add');
        $this->assertResponseIsSuccessful();
    }

    #[DataProvider('adminMediaForm')]
    public function testAdminCanUploadMedia(array $formFields): void
    {
        // Given
        $this->login(self::ADMIN_IDENTIFIER);
        // When
        $this->postMedia($formFields);
        // Then
        $this->assertMediaUploadedAs(self::ADMIN_IDENTIFIER, $formFields);
        $this->assertMediaAddedToAlbum($formFields['media[album]'], $formFields['media[title]']);
    }

    #[DataProvider('nonAdminMediaForm')]
    public function testNonAdminCanUploadMedia(array $formFields): void
    {
        $this->login(self::NON_ADMIN_IDENTIFIER);
        $this->postMedia($formFields);
        $this->assertMediaUploadedAs(self::NON_ADMIN_IDENTIFIER, $formFields);
        $this->assertMediaAddedToCurrentUser($formFields['media[title]']);
    }

    #[DataProvider('fileIsTooBigForm')]
    public function testShouldNotUploadMediaTooBig(array $formFields, string $userIdentifier, array $expected): void
    {
        $this->login($userIdentifier);
        $this->postMedia($formFields);
        $this->assertResponseStatusCodeSame($expected['code']);
    }


    public function testNonAdminCannotListOtherUsersMedias(): void
    {
        $this->login(self::ADMIN_IDENTIFIER);
        $formFields = [
            'media[title]'  => sprintf('Test media %s', self::ADMIN_IDENTIFIER),
            'media[file]'   => self::uploadedFileOk(),
        ];
        $this->postMedia($formFields);

        $this->login(self::NON_ADMIN_IDENTIFIER);
        $formFields['media[title]'] = 'Test media';
        $this->postMedia($formFields);
        $this->get('/admin/media');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextNotContains('td', self::ADMIN_IDENTIFIER);
    }

    protected function postMedia(array $formFields): void
    {
        $this->get('/admin/media/add');
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('Ajouter', $formFields);
    }

    private function assertMediaUploadedAs(string $userIdentifier, array $formFields): void
    {
        $this->assertResponseRedirects('/admin/media');
    }

    private function assertMediaAddedToAlbum(string $albumId, string $title): void
    {
        $this->get(sprintf('/portfolio/%s', $albumId));
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('.media-title', $title);
    }

    private function assertMediaAddedToCurrentUser(string $title): void
    {
        $this->get('/admin/media');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('tr>td', $title);
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
