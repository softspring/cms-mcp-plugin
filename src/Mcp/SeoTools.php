<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SeoTools
{
    public function __construct(private readonly ServiceLocator $seoServices) {}

    #[McpTool(
        name: 'sfs_cms_seo_version_statistics',
        title: 'Analyze CMS version word statistics',
        description: 'Return deterministic word frequencies, density and semantic emphasis from a specific CMS version, including drafts. Analyzes rendered version containers, without global layout navigation. Requires the SEO plugin and CMS_API_READ. Never publishes or changes metadata. Weights describe editorial emphasis, not search ranking.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function statistics(
        string $contentType,
        string $contentId,
        string $versionId,
        ?string $locale = null,
        ?string $site = null,
        #[Schema(type: 'integer', minimum: 1, maximum: 100)]
        int $limit = 20,
    ): array {
        if (!$this->seoServices->has('seo_statistics')) {
            return ['installed' => false, 'ok' => false, 'error' => 'The CMS SEO plugin is not enabled.'];
        }
        try {
            return $this->seoServices->get('seo_statistics')->analyze($contentType, $contentId, $versionId, $locale, $site, $limit);
        } catch (HttpExceptionInterface $exception) {
            return ['ok' => false, 'error' => ['status' => $exception->getStatusCode(), 'message' => $exception->getMessage()]];
        }
    }
}
