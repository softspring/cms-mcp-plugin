<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsApiPlugin\Application\Query\ConfigurationContextQuery;
use Throwable;

class ConfigurationTools
{
    public function __construct(private readonly ConfigurationContextQuery $query) {}

    #[McpTool(
        name: 'sfs_cms_configuration_get_context',
        title: 'Get CMS configuration context',
        description: 'Return the configured CMS project context: sites, layouts, modules, content types, media types, menus, blocks and registered plugins. Read each element\'s aiInstructions before choosing or composing elements.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function getConfigurationContext(
        #[Schema(type: 'string', description: 'Optional section filter: all, sites, layouts, modules, contents, menus, blocks or plugins.')]
        string $section = 'all',
        #[Schema(type: 'boolean', description: 'Whether to include full raw CMS config arrays. Sensitive values are redacted.')]
        bool $includeRawConfig = false,
        #[Schema(type: 'boolean', description: 'Whether disabled modules should be included in module summaries.')]
        bool $includeDisabledModules = true,
    ): array {
        try {
            return $this->query->getConfigurationContext($section, $includeRawConfig, $includeDisabledModules);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage(), 'type' => $e::class];
        }
    }
}
