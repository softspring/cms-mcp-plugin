<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsApiPlugin\Application\Query\PublishedContentQuery;
use Softspring\CmsBundle\Serialization\PublishedContentSerializer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

use function array_slice;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function trim;

class ContentTools
{
    public function __construct(
        private readonly PublishedContentQuery $contentQuery,
        private readonly PublishedContentSerializer $contentSerializer,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
    ) {}

    #[McpTool(
        name: 'sfs_cms_contents_search_published',
        title: 'Search published CMS content',
        description: 'Search published CMS content by text, content type, site, and locale. Returns summaries only.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function searchPublishedContent(
        #[Schema(type: 'string', description: 'Free text query matched against content name and published payload.')]
        string $query = '',
        #[Schema(type: 'string', description: 'Optional CMS content type id.')]
        ?string $contentType = null,
        #[Schema(type: 'string', description: 'Optional CMS site id.')]
        ?string $site = null,
        #[Schema(type: 'string', description: 'Optional locale filter.')]
        ?string $locale = null,
        #[Schema(type: 'integer', description: 'Maximum number of results. Capped at 50.', minimum: 1, maximum: 50)]
        int $limit = 10,
    ): array {
        try {
            $limit = $this->normalizeLimit($limit);
            $search = $this->contentQuery->searchPublished($contentType, $query, $site, $locale, $limit);

            if (null === $search) {
                return ['error' => sprintf('Content type "%s" was not found.', (string) $contentType)];
            }

            foreach ($search['results'] as $key => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $search['results'][$key] = $this->addAdminUrls($row, $locale);
            }

            return $search;
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    #[McpTool(
        name: 'sfs_cms_contents_get_published',
        title: 'Get published CMS content',
        description: 'Return a published CMS content summary and optionally its published payload.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function getPublishedContent(
        #[Schema(type: 'string', description: 'CMS content id.')]
        string $contentId,
        #[Schema(type: 'string', description: 'Optional CMS content type id.')]
        ?string $contentType = null,
        #[Schema(type: 'string', description: 'Optional locale filter for route summaries.')]
        ?string $locale = null,
        #[Schema(type: 'boolean', description: 'Whether to include the published version data payload.')]
        bool $includeData = false,
    ): array {
        try {
            $typedContent = $this->contentQuery->findPublishedContentById($contentId, $contentType);

            if (null === $typedContent) {
                return ['error' => sprintf('Published content "%s" was not found.', $contentId)];
            }

            [$resolvedType, $content, $version] = $typedContent;

            return $this->addAdminUrls($this->contentSerializer->detail($content, $resolvedType, $version, $locale, $includeData), $locale);
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    #[McpTool(
        name: 'sfs_cms_routes_find_internal_links',
        title: 'Find CMS internal links',
        description: 'Find published CMS routes that can be used as internal links.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function findInternalLinks(
        #[Schema(type: 'string', description: 'Free text query matched against content name and published payload.')]
        string $query = '',
        #[Schema(type: 'string', description: 'Optional CMS site id.')]
        ?string $site = null,
        #[Schema(type: 'string', description: 'Optional locale filter.')]
        ?string $locale = null,
        #[Schema(type: 'integer', description: 'Maximum number of link rows. Capped at 50.', minimum: 1, maximum: 50)]
        int $limit = 10,
    ): array {
        try {
            $search = $this->searchPublishedContent($query, null, $site, $locale, $limit);
            if (isset($search['error'])) {
                return $search;
            }

            $links = [];

            foreach ($search['results'] as $row) {
                foreach ($row['routes'] as $route) {
                    foreach ($route['paths'] as $path) {
                        $links[] = [
                            'contentId' => $row['id'],
                            'contentType' => $row['type'],
                            'name' => $row['name'],
                            'routeId' => $route['id'],
                            'locale' => $path['locale'],
                            'path' => $path['compiledPath'] ?? $path['path'],
                            'sites' => $path['sites'],
                        ];
                    }
                }
            }

            return [
                'query' => trim($query),
                'count' => count($links),
                'links' => array_slice($links, 0, $this->normalizeLimit($limit)),
            ];
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function addAdminUrls(array $row, ?string $locale): array
    {
        if (!is_string($row['id'] ?? null) || !is_string($row['type'] ?? null)) {
            return $row;
        }

        $row['adminUrls'] = $this->buildContentAdminUrls($row, $locale);

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, string|null>
     */
    private function buildContentAdminUrls(array $row, ?string $locale): array
    {
        $contentType = $row['type'];
        $parameters = [
            'content' => $row['id'],
        ];

        $locale = $this->resolveContentLocale($row, $locale);
        if ($locale) {
            $parameters['_locale'] = $locale;
        }

        return [
            'content' => $this->generateAdminUrl("sfs_cms_admin_content_{$contentType}_content", $parameters),
            'details' => $this->generateAdminUrl("sfs_cms_admin_content_{$contentType}_details", $parameters),
            'preview' => $this->generateAdminUrl("sfs_cms_admin_content_{$contentType}_preview", $parameters),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveContentLocale(array $row, ?string $requestedLocale): ?string
    {
        if ($this->hasLocale($row, $requestedLocale)) {
            return $requestedLocale;
        }

        $requestLocale = $this->requestStack->getCurrentRequest()?->attributes->get('_locale')
            ?: $this->requestStack->getCurrentRequest()?->getLocale();

        if ($this->hasLocale($row, $requestLocale)) {
            return $requestLocale;
        }

        if (is_string($row['defaultLocale'] ?? null) && '' !== $row['defaultLocale']) {
            return $row['defaultLocale'];
        }

        if (is_array($row['locales'] ?? null)) {
            foreach ($row['locales'] as $locale) {
                if (is_string($locale) && '' !== $locale) {
                    return $locale;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hasLocale(array $row, mixed $locale): bool
    {
        return is_string($locale)
            && '' !== $locale
            && is_array($row['locales'] ?? null)
            && in_array($locale, $row['locales'], true);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function generateAdminUrl(string $route, array $parameters): ?string
    {
        try {
            return $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeLimit(int $limit): int
    {
        return max(1, min(50, $limit));
    }

    private function toolError(Throwable $e): array
    {
        return [
            'error' => $e->getMessage(),
            'type' => $e::class,
        ];
    }
}
