<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Model\SiteInterface;
use Softspring\CmsBundle\Serialization\SiteSerializer;
use Throwable;

class SiteTools
{
    public function __construct(
        private readonly CmsConfig $cmsConfig,
        private readonly SiteSerializer $siteSerializer,
    ) {
    }

    #[McpTool(
        name: 'sfs_cms_sites_list',
        title: 'List CMS sites',
        description: 'Return the configured CMS sites with canonical URL and metadata.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function listSites(): array
    {
        try {
            $sites = [];

            foreach ($this->cmsConfig->getSites() as $site) {
                if (!$site instanceof SiteInterface) {
                    continue;
                }

                $sites[] = $this->siteSerializer->context($site);
            }

            return [
                'count' => count($sites),
                'sites' => $sites,
            ];
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
            $siteEntity = $this->cmsConfig->getSite($site, false);

            if (!$siteEntity instanceof SiteInterface) {
                return ['error' => sprintf('Site "%s" was not found.', $site)];
            }

            return $this->siteSerializer->context($siteEntity, true);
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
