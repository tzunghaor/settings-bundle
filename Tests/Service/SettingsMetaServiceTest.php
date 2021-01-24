<?php
namespace Tzunghaor\SettingsBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Contracts\Cache\CacheInterface;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Service\MetaDataExtractor;
use Tzunghaor\SettingsBundle\Service\SettingsMetaService;

class SettingsMetaServiceTest extends TestCase
{
    /**
     * @var SettingsMetaService
     */
    private $settingsMetaService;

    private $fakeSectionMeta = [];

    private $classes = ['name1' => 'Class1', 'name2' => 'Class2'];

    private $scopeHierarchy = [['name' => 'all', 'children' =>
        [['name' => 'foo'], ['name' => 'bar', 'children' =>
            [['name' => 'bar1'], ['name' => 'bar2']]
        ]]
    ]];

    /**
     * @var CacheInterface|MockObject
     */
    private $mockCache;

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

        $this->settingsMetaService = new SettingsMetaService(
            $this->mockCache,
            $mockExtractor,
            $this->classes,
            $this->scopeHierarchy
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


    public function testGetScopeHierarchy()
    {
        self::assertSame($this->scopeHierarchy, $this->settingsMetaService->getScopeHierarchy());
    }

    public function testHasScope()
    {
        self::assertTrue($this->settingsMetaService->hasScope('foo'));
        self::assertTrue($this->settingsMetaService->hasScope('bar2'));
        self::assertFalse($this->settingsMetaService->hasScope('foo-bar'));
    }

    public function testHasSectionClass()
    {
        self::assertTrue($this->settingsMetaService->hasSectionClass('Class1'));
        self::assertTrue($this->settingsMetaService->hasSectionClass('Class2'));
        self::assertFalse($this->settingsMetaService->hasSectionClass('Class3'));
    }

    public function testSectionPath()
    {
        self::assertEquals([], $this->settingsMetaService->getScopePath('all'));
        self::assertEquals(['all'], $this->settingsMetaService->getScopePath('foo'));
        self::assertEquals(['all', 'bar'], $this->settingsMetaService->getScopePath('bar1'));
    }

    public function testDuplicateScope()
    {
        $mockCache = $this->createMock(CacheInterface::class);
        $mockExtractor = $this->createMock(MetaDataExtractor::class);

        self::expectException(InvalidConfigurationException::class);

        new SettingsMetaService(
            $mockCache,
            $mockExtractor,
            [],
            [['name' => 'foo', 'children' =>
                [['name' => 'bar'], ['name' => 'foo']]
            ]]
        );
    }

    public function testCacheWarmer()
    {
        self::assertTrue($this->settingsMetaService->isOptional());

        $this->mockCache->expects(self::once())->method('get');
        $this->settingsMetaService->warmUp('tmp');
    }
}