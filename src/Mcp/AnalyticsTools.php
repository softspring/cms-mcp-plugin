<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;

class AnalyticsTools
{
    public function __construct(private readonly ServiceLocator $analyticsServices) {}

    #[McpTool(
        name: 'sfs_cms_analytics_get_site_metrics',
        title: 'Get CMS site analytics',
        description: 'Return analytics metrics for a CMS site using the statistics API exposed by the CMS analytics plugin.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: true),
    )]
    public function getSiteAnalytics(
        #[Schema(type: 'string', description: 'CMS site id.')]
        string $site,
        #[Schema(type: 'string', description: 'Analytics date range, for example 7d, 30d, month, 6mo or 12mo. Supported values depend on the configured statistics provider.')]
        string $dateRange = '30d',
        #[Schema(type: 'string', description: 'Optional site path. When omitted, returns aggregate site metrics.')]
        ?string $path = null,
    ): array {
        try {
            if (!$this->analyticsServices->has('analytics_query')) {
                return [
                    'installed' => false,
                    'error' => 'No CMS analytics statistics provider is available for this site.',
                    'availableProviders' => [],
                ];
            }
            $query = $this->analyticsServices->get('analytics_query');

            return $query->getSiteAnalytics($site, $dateRange, $path);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage(), 'type' => $e::class];
        }
    }

    #[McpTool(
        name: 'sfs_cms_analytics_query_pages',
        title: 'Query CMS page analytics',
        description: 'Query page analytics with sorting, pagination, exact path or wildcard path filters, and optional CMS content resolution using the CMS analytics statistics API.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: true),
    )]
    public function queryPages(
        #[Schema(type: 'string', description: 'CMS site id.')]
        string $site,
        #[Schema(type: 'string', description: 'Analytics date range, for example 7d, 30d, month, 6mo or 12mo. Supported values depend on the configured statistics provider.')]
        string $dateRange = '30d',
        #[Schema(type: 'string', description: 'Optional exact page path, for example /es/blog/cms-para-symfony.')]
        ?string $path = null,
        #[Schema(type: 'string', description: 'Optional wildcard page path pattern, for example /es/cms-* or /es/blog/*. Uses * and ? matching after the statistics API returns page rows.')]
        ?string $pathPattern = null,
        #[Schema(type: 'string', description: 'Optional CMS content type id used when resolving page rows to CMS content, for example article. Pass null or an empty string to include all content types.')]
        ?string $contentType = null,
        #[Schema(type: 'string', description: 'Optional locale filter for CMS routes.')]
        ?string $locale = null,
        #[Schema(type: 'string', description: 'Metric used for ordering. Allowed values: pageviews, visitors, visits.')]
        string $metric = 'pageviews',
        #[Schema(type: 'string', description: 'Order direction. Allowed values: desc or asc. Use asc for least visited pages.')]
        string $orderDirection = 'desc',
        #[Schema(type: 'integer', description: 'Page number for returned rows.', minimum: 1)]
        int $page = 1,
        #[Schema(type: 'integer', description: 'Maximum number of page rows. Capped at 50.', minimum: 1, maximum: 50)]
        int $limit = 10,
        #[Schema(type: 'boolean', description: 'Whether to include matched CMS content data when a page path maps to a published CMS route.')]
        bool $includeContent = true,
    ): array {
        try {
            if (!$this->analyticsServices->has('analytics_query')) {
                return [
                    'installed' => false,
                    'error' => 'No CMS analytics statistics provider is available for this site.',
                    'availableProviders' => [],
                ];
            }
            $query = $this->analyticsServices->get('analytics_query');

            return $query->queryPages($site, $dateRange, $path, $pathPattern, $contentType, $locale, $metric, $orderDirection, $page, $limit, $includeContent);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage(), 'type' => $e::class];
        }
    }
}
