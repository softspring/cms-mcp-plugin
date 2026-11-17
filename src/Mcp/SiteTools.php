<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsApiPlugin\Application\Query\SiteQuery;
use Throwable;

class SiteTools
{
    public function __construct(private readonly SiteQuery $siteQuery) {}

    #[McpTool(
        name: 'sfs_cms_sites_list',
        title: 'List CMS sites',
        description: 'Return the configured CMS sites with canonical URL and metadata.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function listSites(): array
    {
        try {
            return $this->siteQuery->listContexts();
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    #[McpTool(
        name: 'sfs_cms_sites_get_context',
        title: 'Get CMS site context',
        description: 'Return read-only CMS configuration and metadata for one site.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function getSiteContext(
        #[Schema(type: 'string', description: 'CMS site id.')]
        string $site,
    ): array {
        try {
            $result = $this->siteQuery->getContext($site);

            return $result ?? ['error' => sprintf('Site "%s" was not found.', $site)];
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
