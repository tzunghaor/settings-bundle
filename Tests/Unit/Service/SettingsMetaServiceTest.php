<?php
namespace Tzunghaor\SettingsBundle\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Model\Item;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Service\MetaDataExtractor;
use Tzunghaor\SettingsBundle\Service\ScopeProviderInterface;
use Tzunghaor\SettingsBundle\Service\SettingsMetaService;

class SettingsMetaServiceTest extends TestCase
{
    /**
     * @var SettingsMetaService
     */
    private $settingsMetaService;

    private $fakeSectionMeta = [];

    private $classes = ['name1' => 'Class1', 'name2' => 'Class2'];

    /**
     * @var CacheInterface|MockObject
     */
    private $mockCache;

    /**
     * @var ScopeProviderInterface|MockObject
     */
    private $mockScopeProvider;

    public function setUp(): void
    {
        $this->mockCache = $this->createMock(CacheInterface::class);
        $this->mockCache
            ->method('get')
            ->willReturnCallback(function ($key, $callable) { return $callable(new CacheItem()); })
        ;

        $this->fakeSectionMeta['Class1'] = new SectionMetaData('foo1', 'bar1', 'data', 'desc', []);
        $this->fakeSectionMeta['Class2'] = new SectionMetaData('foo1', 'bar1', 'data', 'desc', []);

        $mockExtractor = $this->createMock(MetaDataExtractor::class);
        $mockExtractor
            ->method('createSectionMetaData')
            ->withConsecutive(['name1', 'Class1'], ['name2', 'Class2'])
            ->willReturnOnConsecutiveCalls($this->fakeSectionMeta['Class1'], $this->fakeSectionMeta['Class2'])
        ;
        $this->mockScopeProvider = $this->createMock(ScopeProviderInterface::class);

        $this->settingsMetaService = new SettingsMetaService(
            $this->mockCache,
            $mockExtractor,
            $this->mockScopeProvider,
            'default',
            $this->classes
        );
    }

    public function testGetSectionMetaDataArray()
    {

        // tested method
        $metaData1 = $this->settingsMetaService->getSectionMetaData('Class1');
        self::assertSame($this->fakeSectionMeta['Class1'], $metaData1);

        $metaData2 = $this->settingsMetaService->getSectionMetaData('Class2');
        self::assertSame($this->fakeSectionMeta['Class2'], $metaData2);
    }

    public function testGetSectionMetaDataArrayByName()
    {

        // tested method
        $metaData1 = $this->settingsMetaService->getSectionMetaDataByName('name1');
        self::assertSame($this->fakeSectionMeta['Class1'], $metaData1);

        $metaData2 = $this->settingsMetaService->getSectionMetaDataByName('name2');
        self::assertSame($this->fakeSectionMeta['Class2'], $metaData2);
    }

    public function testGetUnknowSection()
    {
        self::expectException(SettingsException::class);

        $this->settingsMetaService->getSectionMetaDataByName('foo');
    }


    public function testGetScopeDisplayHierarchy()
    {
        $this->mockScopeProvider->expects(self::once())->method('getScopeDisplayHierarchy')->willReturn(['foo']);

        self::assertSame(['foo'], $this->settingsMetaService->getScopeDisplayHierarchy());
    }

    public function testHasSectionClass()
    {
        self::assertTrue($this->settingsMetaService->hasSectionClass('Class1'));
        self::assertTrue($this->settingsMetaService->hasSectionClass('Class2'));
        self::assertFalse($this->settingsMetaService->hasSectionClass('Class3'));
    }


    public function testGetScope()
    {
        $obj = new class() {};
        $this->mockScopeProvider->expects(self::once())->method('getScope')
            ->with($obj)->willReturn(new Item('foo'));

        self::assertEquals(new Item('foo'), $this->settingsMetaService->getScope($obj));

    }

    public function testGetScopePath()
    {
        $this->mockScopeProvider->expects(self::once())->method('getScopePath')
            ->with('bar')->willReturn(['foo']);

        self::assertEquals(['foo'], $this->settingsMetaService->getScopePath('bar'));
    }

    public function testCacheWarmer()
    {
        self::assertTrue($this->settingsMetaService->isOptional());

        $this->mockCache->expects(self::once())->method('get');
        $this->settingsMetaService->warmUp('tmp');
    }
}