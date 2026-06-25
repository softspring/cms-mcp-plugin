<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Analytics;

use RuntimeException;
use Softspring\CmsBundle\Model\SiteInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;

use function array_fill_keys;
use function max;
use function method_exists;
use function min;
use function property_exists;
use function rawurlencode;
use function rtrim;
use function sprintf;

class PlausibleAnalyticsDriver implements AnalyticsDriverInterface
{
    private const METRICS = [
        'visitors',
        'visits',
        'pageviews',
        'views_per_visit',
        'bounce_rate',
        'time_on_page',
    ];

    private const SITE_METRICS = [
        'visitors',
        'visits',
        'pageviews',
        'views_per_visit',
        'bounce_rate',
    ];

    public function __construct(
        private readonly ServiceLocator $analyticsServices,
    ) {
    }

    public function getName(): string
    {
        return 'plausible';
    }

    public function isAvailable(): bool
    {
        return class_exists('Softspring\\CmsAnalyticsPlugin\\SfsCmsAnalyticsPlugin')
            && $this->analyticsServices->has('plausible_configuration_resolver')
            && $this->analyticsServices->has('http_client');
    }

    public function resolveConfiguration(SiteInterface $site, ?string $path = null): AnalyticsConfiguration
    {
        if (!$this->isAvailable()) {
            return new AnalyticsConfiguration(
                provider: $this->getName(),
                enabled: false,
                usable: false,
                missingReasons: ['Plausible analytics services are not available in the container.'],
            );
        }

        $configuration = $this->resolvePlausibleConfiguration($site);
        $usable = method_exists($configuration, 'isUsable') ? (bool) $configuration->isUsable() : false;
        $dashboardUrl = null;

        if (null !== $path && method_exists($configuration, 'dashboardUrl')) {
            $dashboardUrl = $configuration->dashboardUrl($path);
        } elseif ($usable && property_exists($configuration, 'apiBaseUrl') && property_exists($configuration, 'siteId')) {
            $dashboardUrl = rtrim($configuration->apiBaseUrl, '/').'/'.rawurlencode($configuration->siteId);
        }

        return new AnalyticsConfiguration(
            provider: $this->getName(),
            enabled: property_exists($configuration, 'enabled') ? (bool) $configuration->enabled : false,
            usable: $usable,
            missingReasons: method_exists($configuration, 'missingReasons') ? $configuration->missingReasons() : [],
            dashboardUrl: $dashboardUrl,
            context: [
                'apiBaseUrl' => property_exists($configuration, 'apiBaseUrl') ? $configuration->apiBaseUrl : null,
                'siteId' => property_exists($configuration, 'siteId') ? $configuration->siteId : null,
            ],
        );
    }

    public function getSiteMetrics(SiteInterface $site, string $dateRange, ?string $path = null): array
    {
        $configuration = $this->resolvePlausibleConfiguration($site);

        if (null === $path) {
            return $this->queryPlausible($configuration, $dateRange, self::SITE_METRICS);
        }

        $pageMetrics = $this->queryPlausible($configuration, $dateRange, [
            'visitors',
            'visits',
            'pageviews',
            'bounce_rate',
            'time_on_page',
        ], [
            ['is', 'event:page', [$path]],
        ]);

        return array_replace($pageMetrics, $this->queryPlausible($configuration, $dateRange, [
            'views_per_visit',
        ], [
            ['is', 'visit:entry_page', [$path]],
        ]));
    }

    public function queryPages(SiteInterface $site, AnalyticsPageQuery $query): array
    {
        $configuration = $this->resolvePlausibleConfiguration($site);
        $metricNames = [
            'visitors',
            'visits',
            'pageviews',
        ];
        $limit = min(500, max(1, $query->limit));
        $payload = [
            'site_id' => $configuration->siteId,
            'metrics' => $metricNames,
            'date_range' => $query->dateRange,
            'dimensions' => [
                'event:page',
            ],
            'order_by' => [
                [$query->metric, $query->orderDirection],
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => (max(1, $query->page) - 1) * $limit,
            ],
        ];

        if (null !== $query->path) {
            $payload['filters'] = [
                ['is', 'event:page', [$query->path]],
            ];
        }

        $response = $this->analyticsServices->get('http_client')->request('POST', rtrim($configuration->apiBaseUrl, '/').'/api/v2/query', [
            'headers' => [
                'Authorization' => 'Bearer '.$configuration->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $error = $response->toArray(false)['error'] ?? null;

            throw new RuntimeException(sprintf('Plausible API returned HTTP %s for site "%s"%s.', $response->getStatusCode(), $configuration->siteId, $error ? sprintf(': %s', $error) : ''));
        }

        $rows = [];

        foreach ($response->toArray(false)['results'] ?? [] as $result) {
            $path = $result['dimensions'][0] ?? null;
            if (!is_string($path) || '' === trim($path)) {
                continue;
            }

            $metrics = [];
            foreach ($metricNames as $index => $metricName) {
                $metrics[$metricName] = $result['metrics'][$index] ?? 0;
            }

            $rows[] = [
                'path' => $path,
                'metrics' => $metrics,
            ];
        }

        return $rows;
    }

    /**
     * @param string[]    $metricNames
     * @param list<array> $filters
     *
     * @return array<string, int|float|null>
     */
    private function queryPlausible(object $configuration, string $dateRange, array $metricNames, array $filters = []): array
    {
        $payload = [
            'site_id' => $configuration->siteId,
            'metrics' => $metricNames,
            'date_range' => $dateRange,
        ];

        if ([] !== $filters) {
            $payload['filters'] = $filters;
        }

        $response = $this->analyticsServices->get('http_client')->request('POST', rtrim($configuration->apiBaseUrl, '/').'/api/v2/query', [
            'headers' => [
                'Authorization' => 'Bearer '.$configuration->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $error = $response->toArray(false)['error'] ?? null;

            throw new RuntimeException(sprintf('Plausible API returned HTTP %s for site "%s"%s.', $response->getStatusCode(), $configuration->siteId, $error ? sprintf(': %s', $error) : ''));
        }

        $values = $response->toArray(false)['results'][0]['metrics'] ?? [];
        $metrics = [];

        foreach ($metricNames as $index => $metric) {
            $metrics[$metric] = $values[$index] ?? 0;
        }

        return array_replace($this->emptyMetrics(), $metrics);
    }

    /**
     * @return array<string, int|float|null>
     */
    private function emptyMetrics(): array
    {
        return array_fill_keys(self::METRICS, 0);
    }

    private function resolvePlausibleConfiguration(SiteInterface $site): object
    {
        return $this->analyticsServices->get('plausible_configuration_resolver')->resolve($site);
    }
}
