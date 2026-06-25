<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Analytics;

final readonly class AnalyticsConfiguration
{
    /**
     * @param string[]             $missingReasons
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $provider,
        public bool $enabled,
        public bool $usable,
        public array $missingReasons = [],
        public ?string $dashboardUrl = null,
        public array $context = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'enabled' => $this->enabled,
            'usable' => $this->usable,
            'missingReasons' => $this->missingReasons,
            'dashboardUrl' => $this->dashboardUrl,
            'context' => $this->context,
        ];
    }
}
