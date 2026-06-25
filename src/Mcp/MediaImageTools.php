<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsBundle\Serialization\MediaImageSerializer;
use Softspring\CmsBundle\Serialization\MediaTypeRequirementsSerializer;
use Softspring\MediaBundle\EntityManager\MediaManagerInterface;
use Softspring\MediaBundle\Model\MediaInterface;
use Softspring\MediaBundle\Type\MediaTypesCollection;
use Throwable;

use function array_keys;
use function array_values;
use function count;
use function max;
use function mb_stripos;
use function min;
use function trim;

class MediaImageTools
{
    public function __construct(
        private readonly MediaTypesCollection $mediaTypesCollection,
        private readonly MediaManagerInterface $mediaManager,
        private readonly MediaTypeRequirementsSerializer $requirementsSerializer,
        private readonly MediaImageSerializer $mediaImageSerializer,
    ) {
    }

    #[McpTool(
        name: 'sfs_cms_media_images_list_types',
        title: 'List CMS media image types',
        description: 'Return configured image media types and upload requirements.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function listImageTypes(
        #[Schema(type: 'boolean', description: 'Whether to include private media types.')]
        bool $includePrivate = false,
    ): array {
        try {
            $types = [];

            foreach ($this->mediaTypesCollection->getTypes() as $type => $typeConfig) {
                $type = (string) $type;

                if ('image' !== ($typeConfig['type'] ?? null)) {
                    continue;
                }

                if (!$includePrivate && ($typeConfig['private'] ?? false)) {
                    continue;
                }

                $types[$type] = $this->describeMediaType($type, $typeConfig);
            }

            ksort($types);

            return [
                'count' => count($types),
                'types' => array_values($types),
            ];
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    #[McpTool(
        name: 'sfs_cms_media_images_search',
        title: 'Search CMS media images',
        description: 'Search existing CMS image media items by text and type, returning chat-ready thumbnail previews and admin links.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function searchImages(
        #[Schema(type: 'string', description: 'Optional text to search in image name and description.')]
        ?string $query = null,
        #[Schema(type: 'string', description: 'Optional media type key to restrict the search.')]
        ?string $type = null,
        #[Schema(type: 'boolean', description: 'Whether to include private media.')]
        bool $includePrivate = false,
        #[Schema(type: 'integer', description: 'Maximum number of image results to return.')]
        int $limit = 20,
    ): array {
        try {
            $limit = max(1, min($limit, 50));
            $query = trim((string) $query);
            $type = trim((string) $type);

            if ('' !== $type) {
                $typeContext = $this->findMediaType($type);
                if (null === $typeContext) {
                    return ['error' => sprintf('Media type "%s" was not found.', $type)];
                }

                if ('image' !== ($typeContext['mediaType'] ?? null)) {
                    return ['error' => sprintf('Media type "%s" is not an image type.', $type)];
                }
            }

            $images = [];

            foreach ($this->findMedia(max($limit * 5, 50)) as $media) {
                if (!$media->isImage()) {
                    continue;
                }

                if ('' !== $type && $media->getType() !== $type) {
                    continue;
                }

                if (!$includePrivate && $media->getPrivate()) {
                    continue;
                }

                if ('' !== $query && !$this->matchesQuery($media, $query)) {
                    continue;
                }

                $images[] = ['id' => $media->getId()] + $this->mediaImageSerializer->preview($media);

                if (count($images) >= $limit) {
                    break;
                }
            }

            return [
                'query' => '' !== $query ? $query : null,
                'type' => '' !== $type ? $type : null,
                'includePrivate' => $includePrivate,
                'limit' => $limit,
                'count' => count($images),
                'images' => $images,
            ];
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    #[McpTool(
        name: 'sfs_cms_media_images_get_context',
        title: 'Get CMS media image context',
        description: 'Return metadata, upload requirements, and version information for one CMS image media item.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function getImageContext(
        #[Schema(type: 'string', description: 'Media id.')]
        string $mediaId,
    ): array {
        try {
            $media = $this->findMediaById($mediaId);

            if (!$media instanceof MediaInterface) {
                return ['error' => sprintf('Media "%s" was not found.', $mediaId)];
            }

            if (!$media->isImage()) {
                return ['error' => sprintf('Media "%s" is not an image.', $mediaId)];
            }

            return $this->mediaImageSerializer->context($media);
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    private function findMediaType(string $type): ?array
    {
        try {
            return $this->describeMediaType($type, $this->mediaTypesCollection->getType($type));
        } catch (Throwable) {
            return null;
        }
    }

    private function describeMediaType(string $type, array $typeConfig): array
    {
        return $this->requirementsSerializer->describe($type, $typeConfig) + [
            'private' => (bool) ($typeConfig['private'] ?? false),
            'versionKeys' => array_keys($typeConfig['versions'] ?? []),
            'pictureKeys' => array_keys($typeConfig['pictures'] ?? []),
            'videoSetKeys' => array_keys($typeConfig['video_sets'] ?? []),
        ];
    }

    /**
     * @return list<MediaInterface>
     */
    private function findMedia(int $limit): array
    {
        try {
            return $this->mediaManager->getRepository()->findBy([], ['createdAt' => 'DESC'], $limit);
        } catch (Throwable) {
            return [];
        }
    }

    private function findMediaById(string $id): ?MediaInterface
    {
        try {
            $media = $this->mediaManager->getRepository()->find($id);
        } catch (Throwable) {
            return null;
        }

        return $media instanceof MediaInterface ? $media : null;
    }

    private function matchesQuery(MediaInterface $media, string $query): bool
    {
        return false !== mb_stripos((string) $media->getName(), $query)
            || false !== mb_stripos((string) $media->getDescription(), $query)
            || false !== mb_stripos((string) $media->getId(), $query);
    }

    private function toolError(Throwable $e): array
    {
        return [
            'error' => $e->getMessage(),
            'type' => $e::class,
        ];
    }
}
