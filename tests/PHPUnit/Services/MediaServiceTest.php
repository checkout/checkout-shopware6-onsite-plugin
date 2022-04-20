<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Service\MediaService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Api\Controller\Exception\PermissionDeniedException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaServiceTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    protected $logger;

    /**
     * @var FilesystemInterface|MockObject
     */
    protected $fileSystemPublic;

    /**
     * @var FakeEntityRepository
     */
    protected $mediaRepository;

    /**
     * @var MockObject|UrlGeneratorInterface
     */
    protected $urlGenerator;

    protected MediaService $mediaService;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->fileSystemPublic = $this->createMock(FilesystemInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->mediaRepository = new FakeEntityRepository(new MediaDefinition());

        $this->mediaService = new MediaService(
            $this->logger,
            $this->fileSystemPublic,
            $this->mediaRepository,
            $this->urlGenerator
        );
    }

    /**
     * @dataProvider deleteSystemMediaProvider
     */
    public function testDeleteSystemMedia(?string $adminUserId, string $viewPermission, string $deletePermission): void
    {
        $mediaId = 'foo';
        $context = $this->setUpContext($adminUserId, [$viewPermission, $deletePermission]);
        $this->setUpMedia($mediaId);

        if ($adminUserId === null) {
            static::expectException(MediaNotFoundException::class);
        } else {
            /** @var AdminApiSource $sourceContext */
            $sourceContext = $context->getSource();
            if (!$sourceContext->isAllowed($viewPermission) || !$sourceContext->isAllowed($deletePermission)) {
                static::expectException(PermissionDeniedException::class);
            }
        }

        $event = $this->createMock(EntityWrittenContainerEvent::class);
        $this->mediaRepository->entityWrittenContainerEvents[] = $event;
        $result = $this->mediaService->deleteSystemMedia($mediaId, $context);

        static::assertTrue($result);
    }

    /**
     * @dataProvider getAdminSystemMediaProvider
     */
    public function testGetAdminSystemMedia(?string $adminUserId, array $permissions): void
    {
        $mediaId = 'foo';
        $context = $this->setUpContext($adminUserId, $permissions);
        $this->setUpMedia($mediaId);

        if ($adminUserId === null) {
            static::expectException(MediaNotFoundException::class);
        } elseif (empty($permissions)) {
            static::expectException(PermissionDeniedException::class);
        }

        $media = $this->mediaService->getAdminSystemMedia($mediaId, $context);
        static::assertInstanceOf(MediaEntity::class, $media);
    }

    /**
     * @dataProvider getMediaProvider
     */
    public function testGetMedia(?string $adminUserId, string $mediaId, ?string $existsMediaId): void
    {
        $context = $this->setUpContext($adminUserId);

        $mockMedia = $this->createConfiguredMock(MediaEntity::class, [
            'getId' => $existsMediaId,
        ]);

        if ($mediaId !== $existsMediaId) {
            $mockMedia = null;
            static::expectException(MediaNotFoundException::class);
        }

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $mockMedia,
        ]);

        $this->mediaRepository->entitySearchResults[] = $search;

        $media = $this->mediaService->getMedia($mediaId, new Criteria(), $context);

        if ($adminUserId !== null) {
            static::assertTrue($this->mediaRepository->getFirstCriteria()->hasEqualsFilter('userId'));
        }

        static::assertInstanceOf(MediaEntity::class, $media);
    }

    public function testGetPathVideoMedia(): void
    {
        $mediaId = 'foo';
        $media = $this->setUpMedia($mediaId);

        $this->urlGenerator->expects(static::once())->method('getRelativeMediaUrl');
        $path = $this->mediaService->getPathVideoMedia($media);
        static::assertIsString($path);
    }

    /**
     * @dataProvider getStreamResponse
     */
    public function testGetStreamResponse(string $path): void
    {
        $hasSource = !empty($path);
        $resource = fopen('php://input', 'rb');

        if (!$hasSource || $resource === false) {
            static::expectException(FileNotFoundException::class);
        }

        $this->fileSystemPublic->expects(static::once())
            ->method('readStream')
            ->willReturn($hasSource ? $resource : false);

        $response = $this->mediaService->getStreamResponse($path);
        fclose($resource);
        static::assertInstanceOf(StreamedResponse::class, $response);
    }

    public function getStreamResponse()
    {
        return [
            'Test can not read stream' => [
                '',
            ],
            'Test read stream successfully' => [
                'any path',
            ],
        ];
    }

    public function deleteSystemMediaProvider(): array
    {
        return [
            'Test is not admin source expect throw exception' => [
                null,
                '',
                '',
            ],
            'Test do not have permission expect throw exception' => [
                null,
                '',
                '',
            ],
            'Test do not have permission delete expect throw exception' => [
                '12345',
                PaymentMethodService::PRIVILEGES_VIEWER,
                '',
            ],
            'Test is delete successfully' => [
                '12345',
                PaymentMethodService::PRIVILEGES_VIEWER,
                PaymentMethodService::PRIVILEGES_DELETER,
            ],
        ];
    }

    public function getAdminSystemMediaProvider(): array
    {
        return [
            'Test is not admin source expect throw exception' => [
                null,
                [PaymentMethodService::PRIVILEGES_VIEWER],
            ],
            'Test does not have permission expect throw exception' => [
                '12345',
                [],
            ],
            'Test is get media successfully' => [
                '12345',
                [PaymentMethodService::PRIVILEGES_VIEWER],
            ],
        ];
    }

    public function getMediaProvider(): array
    {
        return [
            'Test is admin source must include userid' => [
                '1234',
                '123',
                '234',
            ],
            'Test is not admin source but not found media' => [
                null,
                '123',
                '234',
            ],
            'Test is not admin source and found media' => [
                null,
                '123',
                '123',
            ],
        ];
    }

    private function setUpContext(?string $adminUserId, array $permissions = []): Context
    {
        if ($adminUserId === null) {
            $contextSource = $this->createMock(SalesChannelApiSource::class);
        } else {
            $adminSource = new AdminApiSource($adminUserId);
            $adminSource->setPermissions(array_filter($permissions, function ($permission) {
                return !empty($permission);
            }));
            $contextSource = $adminSource;
        }

        return Context::createDefaultContext($contextSource);
    }

    private function setUpMedia(string $mediaId): MediaEntity
    {
        $mockMedia = $this->createConfiguredMock(MediaEntity::class, [
            'getId' => $mediaId,
        ]);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $mockMedia,
        ]);

        $this->mediaRepository->entitySearchResults[] = $search;

        return $mockMedia;
    }
}
