<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Tests;

use PHPUnit\Framework\TestCase;
use Softspring\CmsMcpPlugin\SfsCmsMcpPlugin;

class SfsCmsMcpPluginTest extends TestCase
{
    public function testProvidesCmsPluginAlias(): void
    {
        self::assertSame('sfs_cms_mcp', SfsCmsMcpPlugin::getAlias());
    }

    public function testReturnsPackagePath(): void
    {
        self::assertDirectoryExists(new SfsCmsMcpPlugin()->getPath());
    }
}
