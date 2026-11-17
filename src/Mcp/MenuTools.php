<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsApiPlugin\Application\Query\MenuQuery;
use Throwable;

use function trim;

class MenuTools
{
    public function __construct(private readonly MenuQuery $menuQuery) {}

    #[McpTool(
        name: 'sfs_cms_menus_get_context',
        title: 'Get CMS menu context',
        description: 'Return CMS menu data and menu item tree for navigation context.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function getMenuContext(
        #[Schema(type: 'string', description: 'Optional CMS menu id. If omitted, returns a list of menus.')]
        ?string $menu = null,
    ): array {
        try {
            if (null === $menu || '' === trim($menu)) {
                return $this->menuQuery->list(null, '', 100);
            }

            $result = $this->menuQuery->get($menu);
            if (null === $result) {
                return ['error' => sprintf('Menu "%s" was not found.', $menu)];
            }

            return $result;
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    private function toolError(Throwable $e): array
    {
        return [
            'error' => $e->getMessage(),
            'type' => $e::class,
        ];
    }
}
