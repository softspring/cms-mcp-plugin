<?php

declare(strict_types=1);

namespace Softspring\CmsMcpPlugin\Tests\Mcp;

use PHPUnit\Framework\TestCase;
use Softspring\CmsApiPlugin\Application\Query\PublishedContentQuery;
use Softspring\CmsBundle\Serialization\PublishedContentSerializer;
use Softspring\CmsMcpPlugin\Mcp\ContentTools;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class ContentToolsTest extends TestCase
{
    public function testSearchDelegatesToTheSharedQueryWithTheMcpLimit(): void
    {
        $result = ['query' => 'guide', 'count' => 0, 'results' => []];
        $query = $this->createMock(PublishedContentQuery::class);
        $query->expects(self::once())->method('searchPublished')
            ->with('pages', 'guide', null, 'en', 50)->willReturn($result);
        $tools = new ContentTools($query, new PublishedContentSerializer(), $this->createStub(RouterInterface::class), new RequestStack());

        self::assertSame($result, $tools->searchPublishedContent('guide', 'pages', null, 'en', 100));
    }

    public function testMissingContentIsConvertedToTheExistingToolError(): void
    {
        $query = $this->createMock(PublishedContentQuery::class);
        $query->expects(self::once())->method('findPublishedContentById')->with('missing', 'page')->willReturn(null);
        $tools = new ContentTools($query, new PublishedContentSerializer(), $this->createStub(RouterInterface::class), new RequestStack());

        self::assertSame(['error' => 'Published content "missing" was not found.'], $tools->getPublishedContent('missing', 'page'));
    }
}
