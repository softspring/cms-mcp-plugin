<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Softspring\CmsApiPlugin\Application\Query\ResourceQuery;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ResourceTools
{
    public function __construct(private readonly ResourceQuery $resources) {}

    #[McpTool(name: 'sfs_cms_resources_read', description: 'List/search or read a CMS resource including drafts. Resources: contents, content_versions, content_preview, routes, media, menus, blocks, sections, section_versions, sites. Pass id for a detail; filters support type/query/site/locale/limit. Content detail requires type. Versions require content or section parent id. content_preview requires content id and type; accepts version/site/locale and returns rendered HTML and compilation errors without publishing. Uses API queries and CMS_API_READ permission.')]
    public function read(string $resource, ?string $id = null, #[Schema(type: 'object', additionalProperties: true)] array $filters = []): array
    {
        try {
            return $this->resources->read($resource, $id, $filters);
        } catch (HttpExceptionInterface $exception) {
            return ['ok' => false, 'error' => ['status' => $exception->getStatusCode(), 'message' => $exception->getMessage()]];
        }
    }
}
