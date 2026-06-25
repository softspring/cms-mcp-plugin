<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsBundle\Serialization\CmsConfigurationSerializer;
use Throwable;

use function array_intersect;
use function in_array;
use function sprintf;
use function strtolower;
use function trim;

class ConfigurationTools
{
    private const SECTIONS = ['sites', 'layouts', 'modules', 'contents', 'menus', 'blocks', 'plugins'];

    public function __construct(
        private readonly CmsConfigurationSerializer $configurationSerializer,
    ) {
    }

    #[McpTool(
        name: 'sfs_cms_configuration_get_context',
        title: 'Get CMS configuration context',
        description: 'Return the configured CMS project context: sites, layouts, modules, content types, menus, blocks and registered plugins.',
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
            $sections = $this->resolveSections($section);

            if ([] === $sections) {
                return [
                    'error' => sprintf('Invalid CMS configuration section "%s".', $section),
                    'availableSections' => self::SECTIONS,
                ];
            }

            $context = [
                'section' => $section,
                'includeRawConfig' => $includeRawConfig,
                'counts' => $this->configurationSerializer->counts(),
            ];

            if (in_array('sites', $sections, true)) {
                $context['sites'] = $this->configurationSerializer->sites($includeRawConfig);
            }

            if (in_array('layouts', $sections, true)) {
                $context['layouts'] = $this->configurationSerializer->layouts($includeRawConfig);
            }

            if (in_array('modules', $sections, true)) {
                $context['modules'] = $this->configurationSerializer->modules($includeRawConfig, $includeDisabledModules);
            }

            if (in_array('contents', $sections, true)) {
                $context['contents'] = $this->configurationSerializer->contents($includeRawConfig);
            }

            if (in_array('menus', $sections, true)) {
                $context['menus'] = $this->configurationSerializer->menus($includeRawConfig);
            }

            if (in_array('blocks', $sections, true)) {
                $context['blocks'] = $this->configurationSerializer->blocks($includeRawConfig);
            }

            if (in_array('plugins', $sections, true)) {
                $context['plugins'] = $this->configurationSerializer->plugins();
            }

            return $context;
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    /**
     * @return list<string>
     */
    private function resolveSections(string $section): array
    {
        $section = strtolower(trim($section));

        if ('' === $section || 'all' === $section) {
            return self::SECTIONS;
        }

        return array_values(array_intersect(self::SECTIONS, [$section]));
    }

    private function toolError(Throwable $e): array
    {
        return [
            'error' => $e->getMessage(),
            'type' => $e::class,
        ];
    }
}
