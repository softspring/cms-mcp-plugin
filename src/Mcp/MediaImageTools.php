<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsApiPlugin\Application\Query\MediaImageQuery;
use Throwable;

class MediaImageTools
{
    public function __construct(private readonly MediaImageQuery $mediaImageQuery) {}

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
            return $this->mediaImageQuery->listImageTypes($includePrivate);
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
            return $this->mediaImageQuery->searchImages($query, $type, $includePrivate, $limit);
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
            return $this->mediaImageQuery->getImageContext($mediaId);
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    private function toolError(Throwable $e): array
    {
        return [
            'error' => $e->getMessage(),
            'type' => $e::class,
        ];
    }
}
