<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Analytics;

use Softspring\CmsBundle\Model\SiteInterface;

interface AnalyticsDriverInterface
{
    public function getName(): string;

    public function isAvailable(): bool;

    public function resolveConfiguration(SiteInterface $site, ?string $path = null): AnalyticsConfiguration;

    /**
     * @return array<string, int|float|null>
     */
    public function getSiteMetrics(SiteInterface $site, string $dateRange, ?string $path = null): array;

    /**
     * @return list<array{path: string, metrics: array<string, int|float|null>}>
     */
    public function queryPages(SiteInterface $site, AnalyticsPageQuery $query): array;
}
