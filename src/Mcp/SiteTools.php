<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Model\SiteInterface;
use Throwable;

class SiteTools
{
    private const SITE_AI_METADATA_FIELD = 'sfs_cms_ai';

    public function __construct(
        private readonly CmsConfig $cmsConfig,
    ) {
    }

    #[McpTool(
        name: 'sfs_cms_get_site_context',
        title: 'Get CMS site context',
        description: 'Return read-only CMS configuration, metadata, and AI instructions for one site.',
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

            return $this->serializeSite($siteEntity, true);
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    private function serializeSite(SiteInterface $site, bool $includeConfig = false): array
    {
        $data = [
            'id' => $site->getId(),
            'canonical' => [
                'scheme' => $site->getCanonicalScheme(),
                'host' => $site->getCanonicalHost(),
                'port' => $site->getCanonicalPort(),
            ],
            'metadata' => $site->getMetadata(),
            'aiInstructions' => $site->getMetadataField(self::SITE_AI_METADATA_FIELD, []),
        ];

        if ($includeConfig) {
            $data['config'] = $site->getConfig();
        }

        return $data;
    }

    private function toolError(Throwable $e): array
    {
        return [
            'error' => $e->getMessage(),
            'type' => $e::class,
        ];
    }
}
