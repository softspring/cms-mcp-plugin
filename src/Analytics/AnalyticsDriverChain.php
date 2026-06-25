<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Analytics;

use Softspring\CmsBundle\Model\SiteInterface;

class AnalyticsDriverChain
{
    /**
     * @param iterable<AnalyticsDriverInterface> $drivers
     */
    public function __construct(
        private readonly iterable $drivers,
    ) {
    }

    public function getDriver(SiteInterface $site, ?string $path = null): ?AnalyticsDriverInterface
    {
        $fallback = null;

        foreach ($this->drivers as $driver) {
            if (!$driver->isAvailable()) {
                continue;
            }

            $configuration = $driver->resolveConfiguration($site, $path);
            if ($configuration->usable) {
                return $driver;
            }

            if (null === $fallback) {
                $fallback = $driver;
            }
        }

        return $fallback;
    }

    /**
     * @return string[]
     */
    public function getAvailableProviderNames(): array
    {
        $providers = [];

        foreach ($this->drivers as $driver) {
            if ($driver->isAvailable()) {
                $providers[] = $driver->getName();
            }
        }

        return $providers;
    }
}
