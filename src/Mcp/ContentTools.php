<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Doctrine\ORM\QueryBuilder;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsBundle\Model\SiteInterface;
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
use function json_encode;
use function mb_stripos;
use function str_ends_with;
use function substr;
use function trim;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class ContentTools
{
    public function __construct(
        private readonly CmsConfig $cmsConfig,
        private readonly ContentManagerInterface $contentManager,
        private readonly PublishedContentSerializer $contentSerializer,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
    ) {
    }

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
            $search = $this->searchPublished($contentType, $query, $site, $locale, $limit);

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
            $typedContent = $this->findPublishedContentById($contentId, $contentType);

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

    private function searchPublished(?string $type, string $query, ?string $site, ?string $locale, int $limit): ?array
    {
        $query = trim($query);
        $contents = [];
        $types = $this->getContentTypes($type);

        if ([] === $types) {
            return null;
        }

        foreach ($types as $resolvedType) {
            foreach ($this->findContents($resolvedType, $site, max($limit * 5, 50)) as $content) {
                if (!$content->getPublishedVersion() instanceof ContentVersionInterface) {
                    continue;
                }

                if ($locale && !in_array($locale, $content->getLocales() ?? [], true)) {
                    continue;
                }

                if ('' !== $query && !$this->contentMatchesQuery($content, $query)) {
                    continue;
                }

                $contents[] = $this->contentSerializer->summary($content, $resolvedType, $locale);

                if (count($contents) >= $limit) {
                    break 2;
                }
            }
        }

        return [
            'query' => $query,
            'filters' => [
                'contentType' => $type,
                'site' => $site,
                'locale' => $locale,
                'publishedOnly' => true,
            ],
            'count' => count($contents),
            'results' => $contents,
        ];
    }

    /**
     * @return array{0: string, 1: ContentInterface, 2: ContentVersionInterface}|null
     */
    private function findPublishedContentById(string $contentId, ?string $type): ?array
    {
        foreach ($this->getContentTypes($type) as $resolvedType) {
            try {
                $content = $this->contentManager->getRepository($resolvedType)->find($contentId);
            } catch (Throwable) {
                continue;
            }

            if (!$content instanceof ContentInterface) {
                continue;
            }

            $version = $content->getPublishedVersion();
            if (!$version instanceof ContentVersionInterface) {
                continue;
            }

            return [$resolvedType, $content, $version];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getContentTypes(?string $type): array
    {
        if (null !== $type && '' !== trim($type)) {
            $resolvedType = $this->resolveContentType($type);

            return null !== $resolvedType ? [$resolvedType] : [];
        }

        return array_keys($this->cmsConfig->getContents());
    }

    private function resolveContentType(string $type): ?string
    {
        $type = trim($type);
        if ('' === $type) {
            return null;
        }

        if ($this->cmsConfig->getContent($type, false)) {
            return $type;
        }

        if (str_ends_with($type, 's')) {
            $singularType = substr($type, 0, -1);
            if ($this->cmsConfig->getContent($singularType, false)) {
                return $singularType;
            }
        }

        return null;
    }

    /**
     * @return list<ContentInterface>
     */
    private function findContents(string $type, ?string $site, int $maxResults): array
    {
        try {
            $repository = $this->contentManager->getRepository($type);
            $qb = $repository->createQueryBuilder('content')
                ->andWhere('content.publishedVersion IS NOT NULL')
                ->setMaxResults($maxResults);

            $this->orderContents($qb, $repository->getClassName());

            if ($site) {
                $qb
                    ->innerJoin('content.sites', 'site')
                    ->andWhere('site.id = :site')
                    ->setParameter('site', $site);
            }

            return $qb->getQuery()->getResult();
        } catch (Throwable) {
            return [];
        }
    }

    private function orderContents(QueryBuilder $qb, string $className): void
    {
        $metadata = $qb->getEntityManager()->getClassMetadata($className);

        if ($metadata->hasField('name')) {
            $qb->addOrderBy('content.name', 'ASC');
        }

        if ($metadata->hasField('id')) {
            $qb->addOrderBy('content.id', 'ASC');
        }
    }

    private function contentMatchesQuery(ContentInterface $content, string $query): bool
    {
        if (false !== mb_stripos((string) $content->getName(), $query)) {
            return true;
        }

        foreach ([$content->getExtraData(), $content->getIndexing()] as $payload) {
            if ($this->payloadMatchesQuery($payload, $query)) {
                return true;
            }
        }

        foreach ([$content->getPublishedVersion(), $content->getLastVersion()] as $version) {
            if (!$version instanceof ContentVersionInterface) {
                continue;
            }

            foreach ([$version->getSeo(), $version->getData(), $version->getMeta()] as $payload) {
                if ($this->payloadMatchesQuery($payload, $query)) {
                    return true;
                }
            }
        }

        foreach ($content->getSites() as $site) {
            if ($site instanceof SiteInterface && false !== mb_stripos((string) $site->getId(), $query)) {
                return true;
            }
        }

        return false;
    }

    private function payloadMatchesQuery(mixed $payload, string $query): bool
    {
        if (is_string($payload)) {
            return false !== mb_stripos($payload, $query);
        }

        if (is_array($payload)) {
            return false !== mb_stripos((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $query);
        }

        return false;
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
