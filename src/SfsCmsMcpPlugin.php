<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin;

use Softspring\CmsBundle\Plugin\SfsCmsPlugin;

class SfsCmsMcpPlugin extends SfsCmsPlugin
{
    public static function getAlias(): string
    {
        return 'sfs_cms_mcp';
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
