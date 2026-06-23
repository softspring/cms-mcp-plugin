<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Softspring\CmsBundle\Manager\MenuManagerInterface;
use Softspring\CmsBundle\Model\MenuInterface;
use Softspring\CmsBundle\Model\MenuItemInterface;
use Throwable;

class MenuTools
{
    public function __construct(
        private readonly MenuManagerInterface $menuManager,
    ) {
    }

    #[McpTool(
        name: 'sfs_cms_get_menu_context',
        title: 'Get CMS menu context',
        description: 'Return CMS menu data and menu item tree for navigation context.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function getMenuContext(
        #[Schema(type: 'string', description: 'Optional CMS menu id. If omitted, returns a list of menus.')]
        ?string $menu = null,
    ): array {
        try {
            $repository = $this->menuManager->getRepository();

            if (null === $menu || '' === trim($menu)) {
                return [
                    'menus' => array_map(
                        fn (MenuInterface $menuEntity): array => $this->serializeMenuSummary($menuEntity),
                        $repository->findAll(),
                    ),
                ];
            }

            $menuEntity = $repository->find($menu);

            if (!$menuEntity instanceof MenuInterface) {
                return ['error' => sprintf('Menu "%s" was not found.', $menu)];
            }

            return $this->serializeMenu($menuEntity);
        } catch (Throwable $e) {
            return $this->toolError($e);
        }
    }

    private function serializeMenuSummary(MenuInterface $menu): array
    {
        return [
            'id' => $menu->getId(),
            'type' => $menu->getType(),
            'name' => $menu->getName(),
        ];
    }

    private function serializeMenu(MenuInterface $menu): array
    {
        return $this->serializeMenuSummary($menu) + [
            'data' => $menu->getData(),
            'items' => array_map(
                fn (MenuItemInterface $item): array => $this->serializeMenuItem($item),
                $menu->getItems()?->filter(static fn (MenuItemInterface $item): bool => null === $item->getParent())->toArray() ?? [],
            ),
        ];
    }

    private function serializeMenuItem(MenuItemInterface $item): array
    {
        return [
            'id' => $item->getId(),
            'type' => $item->getType(),
            'text' => $item->getText(),
            'symfonyRoute' => $item->getSymfonyRoute(),
            'options' => $item->getOptions(),
            'items' => array_map(
                fn (MenuItemInterface $child): array => $this->serializeMenuItem($child),
                $item->getItems()?->toArray() ?? [],
            ),
        ];
    }

    private function toolError(Throwable $e): array
    {
        return [
            'error' => $e->getMessage(),
            'type' => $e::class,
        ];
    }
}
