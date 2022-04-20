<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Controller\Exception\PermissionDeniedException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaService
{
    protected LoggerInterface $logger;

    protected FilesystemInterface $fileSystemPublic;

    protected EntityRepositoryInterface $mediaRepository;

    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(LoggerInterface $logger, FilesystemInterface $fileSystemPublic, EntityRepositoryInterface $mediaRepository, UrlGeneratorInterface $urlGenerator)
    {
        $this->logger = $logger;
        $this->fileSystemPublic = $fileSystemPublic;
        $this->mediaRepository = $mediaRepository;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * It needs permission to delete the media
     * Delete media entity
     */
    public function deleteSystemMedia(string $mediaId, Context $context): bool
    {
        $contextSource = $context->getSource();
        if (!$contextSource instanceof AdminApiSource) {
            $this->logger->critical(
                sprintf('Cannot delete media with ID: %s Context source is not AdminApiSource', $mediaId)
            );

            throw new MediaNotFoundException($mediaId);
        }

        return $context->scope(Context::SYSTEM_SCOPE, function ($context) use ($mediaId, $contextSource) {
            // We have to fetch media entity because shopware core doesn't fetch correctly media
            $media = $this->getAdminSystemMedia($mediaId, $context);

            if (!$contextSource->isAllowed(PaymentMethodService::PRIVILEGES_DELETER)) {
                $this->logger->critical(
                    sprintf('Do not have permission to delete with media ID %s', $mediaId)
                );

                throw new PermissionDeniedException();
            }

            return (bool) $this->mediaRepository->delete([
                ['id' => $media->getId()],
            ], $context);
        });
    }

    /**
     * It needs permission to access the media
     * Return a media entity
     */
    public function getAdminSystemMedia(string $mediaId, Context $context): MediaEntity
    {
        $contextSource = $context->getSource();
        if (!$contextSource instanceof AdminApiSource) {
            $this->logger->critical(
                sprintf('Cannot view media with ID: %s Context source is not AdminApiSource', $mediaId)
            );

            throw new MediaNotFoundException($mediaId);
        }

        $criteria = new Criteria();

        if (!$contextSource->isAllowed(PaymentMethodService::PRIVILEGES_VIEWER)) {
            $this->logger->critical(
                sprintf('Do not have permission to access with media ID %s', $mediaId)
            );

            throw new PermissionDeniedException();
        }

        return $this->getMedia($mediaId, $criteria, $context);
    }

    public function getMedia(string $mediaId, Criteria $criteria, Context $context): MediaEntity
    {
        $criteria->addFilter(
            new EqualsFilter('id', $mediaId)
        );

        /** @var MediaEntity|null $media */
        $media = $context->scope(Context::SYSTEM_SCOPE, function ($context) use ($criteria) {
            return $this->mediaRepository->search($criteria, $context)->first();
        });

        if (!$media instanceof MediaEntity) {
            $this->logger->critical(
                sprintf('Could not fetch media with ID %s', $mediaId)
            );

            throw new MediaNotFoundException($mediaId);
        }

        return $media;
    }

    public function getPathVideoMedia(MediaEntity $media): string
    {
        return $this->urlGenerator->getRelativeMediaUrl($media);
    }

    /**
     * @throws FileNotFoundException
     */
    public function getStreamResponse(string $path): StreamedResponse
    {
        $fileStream = $this->fileSystemPublic->readStream($path);
        if (!\is_resource($fileStream)) {
            throw new FileNotFoundException($path);
        }

        return new StreamedResponse(function () use ($fileStream): void {
            fpassthru($fileStream);
        });
    }
}
