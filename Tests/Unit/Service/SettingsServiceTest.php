<?php

namespace Tzunghaor\SettingsBundle\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Contracts\Cache\CacheInterface;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Model\Item;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingsCacheEntry;
use Tzunghaor\SettingsBundle\Model\SettingSectionAddress;
use Tzunghaor\SettingsBundle\Service\SettingsMetaService;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use Tzunghaor\SettingsBundle\Service\SettingsStoreInterface;

class SettingsServiceTest extends TestCase
{
    private SettingsService $settingsService;

    public function setUp(): void
    {
        $mockSettingsMetaService = $this->createMock(SettingsMetaService::class);
        $mockSettingsStore = $this->createMock(SettingsStoreInterface::class);
        $mockCache = $this->createMock(CacheInterface::class);

        $mockSettingsMetaService
            ->method('getCollectionName')
            ->willReturn('dada')
        ;
        $mockSettingsMetaService
            ->method('getScope')
            ->willReturn(new Item('scopeName'))
        ;
        $mockSettingsMetaService
            ->method('getSectionMetaData')
            ->willReturn(new SectionMetaData('SectionName', '', 'GoodClass', '', []))
        ;
        $mockSettingsMetaService
            ->method('hasSectionClass')
            ->willReturnMap([
                ['GoodClass', true],
                ['WrongClass', false],
            ])
        ;
        $mockSettingsMetaService
            ->method('getScopePath')
            ->willReturnMap([
                ['scope', []],
                ['childScope', ['scope']]
            ])
        ;

        $mockCache
            ->method('get')
            ->willReturn(new SettingsCacheEntry([], [], new StdClass()))
        ;


        $this->settingsService = new SettingsService($mockSettingsMetaService, $mockSettingsStore, [], $mockCache);
    }

    public function testGetSectionAddress(): void
    {
        $address = $this->settingsService->getSectionAddress('GoodClass', 'scope');

        $this->assertEquals(
            new SettingSectionAddress('dada', 'scopeName', 'SectionName'),
            $address
        );
    }

    public function exceptionDataProvider(): array
    {
        return [
            'good class' => [CacheInterface::class, 'GoodClass', 'scope', false],
            'wrong class' => [CacheInterface::class, 'WrongClass', 'scope', true],
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testExceptions(
        string $cacheInterface,
        string $sectionClass,
        string $scope,
        bool   $expectException
    ): void {
        if ($expectException) {
            $this->expectException(SettingsException::class);
        }

        $setting = $this->settingsService->getSection($sectionClass, $scope);

        if (!$expectException) {
            $this->assertInstanceOf(StdClass::class, $setting);
        }
    }
}