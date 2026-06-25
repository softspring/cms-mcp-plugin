<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsBundle\Manager\MenuManagerInterface;
use Softspring\CmsBundle\Model\MenuInterface;
use Softspring\CmsBundle\Serialization\MenuSerializer;
use Throwable;

use function count;
use function trim;

class MenuTools
{
    public function __construct(
        private readonly MenuManagerInterface $menuManager,
        private readonly MenuSerializer $menuSerializer,
    ) {
    }

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
                return $this->listMenus(100);
            }

            $menuEntity = $this->findMenu($menu);

            if (!$menuEntity instanceof MenuInterface) {
                return ['error' => sprintf('Menu "%s" was not found.', $menu)];
            }

            return $this->menuSerializer->detail($menuEntity);
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    private function listMenus(int $limit): array
    {
        $menus = [];

        foreach ($this->findMenus($limit) as $menu) {
            $menus[] = $this->menuSerializer->summary($menu);

            if (count($menus) >= $limit) {
                break;
            }
        }

        return [
            'filters' => [
                'type' => null,
                'query' => '',
            ],
            'count' => count($menus),
            'menus' => $menus,
        ];
    }

    private function findMenu(string $id): ?MenuInterface
    {
        try {
            $menu = $this->menuManager->getRepository()->find($id);
        } catch (Throwable) {
            return null;
        }

        return $menu instanceof MenuInterface ? $menu : null;
    }

    /**
     * @return list<MenuInterface>
     */
    private function findMenus(int $limit): array
    {
        try {
            return $this->menuManager->getRepository()->findBy([], ['name' => 'ASC'], $limit);
        } catch (Throwable) {
            return [];
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
