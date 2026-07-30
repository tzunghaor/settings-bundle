<?php

namespace Tzunghaor\SettingsBundle\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use TestApp\Service\MockTranslator;
use Tzunghaor\SettingsBundle\Model\Item;
use Tzunghaor\SettingsBundle\Service\StaticScopeProvider;

class StaticScopeProviderTest extends TestCase
{
    private $scopeHierarchy = [
        ['name' => 'all', 'children' => [
            ['name' => 'foo'],
            ['name' => 'bar',  'title' => 'Great Bar', 'extra' => ['class' => 'lofty'], 'children' => [
                ['name' => 'bar1'],
                ['name' => 'bar2']
            ]],
        ]],
        ['name' => 'johnny', 'children' => [
            ['name' => 'babar'],
            ['name' => 'doofoo'],
        ]],
    ];

    public function scopePathProvider(): array
    {
        return [
            ['all', null, []],
            ['bar1', 'all', []],
            ['bar1', null, ['all', 'bar']],
            ['all', 'bar2', ['all', 'bar']],
            ['bar1', 'babar', ['johnny']],
        ];
    }

    /**
     * @dataProvider scopePathProvider
     */
    public function testGetScopePath($defaultScopeName, $subject, $expected): void
    {
        $provider = new StaticScopeProvider($this->scopeHierarchy, $defaultScopeName);

        $path = $provider->getScopePath($subject);

        self::assertEquals($expected, $path);
    }

    public function scopeHierarchyProvider(): array
    {
        $defaultExpected = [
            new Item('all', null, [
                new Item('foo', null, [], []),
                new Item('bar', 'Great Bar', [
                    new Item('bar1', null, [], []),
                    new Item('bar2', null, [], []),
                ], ['class' => 'lofty'])
            ], []),
            new Item('johnny', null, [
                new Item('babar', null, [], []),
                new Item('doofoo', null, [], []),
            ], []),
        ];

        $barExpected = [
            new Item('all', null, [
                new Item('bar', 'Great Bar', [
                    new Item('bar1'),
                    new Item('bar2'),
                ], ['class' => 'lofty'])
            ]),
            new Item('johnny', null, [
                new Item('babar'),
            ]),
        ];

        $translatedBarExpected = [
            new Item('all', 'tzunghaor/de_AT/all', [
                new Item('bar', 'tzunghaor/de_AT/Great Bar', [
                    new Item('bar1', 'tzunghaor/de_AT/bar1'),
                    new Item('bar2', 'tzunghaor/de_AT/bar2'),
                ], ['class' => 'lofty'])
            ]),
            new Item('johnny', 'tzunghaor/de_AT/johnny', [
                new Item('babar', 'tzunghaor/de_AT/babar'),
            ]),
        ];

        return [
            // no search => return all
            'all scopes' => [null, null, $defaultExpected],
            // no matching scope => empty list
            'search not found' => ['xy', null, []],
            // matching "bar"
            'search "bar"' => ['bar', null, $barExpected],
            // matching "bar"
            'search "bar" translated' => ['bar', 'de_AT', $translatedBarExpected],
        ];
    }

    /**
     * @dataProvider scopeHierarchyProvider
     */
    public function testGetScopeHierarchy(?string $searchString, ?string $translationLocale, array $expected): void
    {
        $provider = new StaticScopeProvider($this->scopeHierarchy, 'all');
        if ($translationLocale !== null) {
            $provider->setUpTranslation(
                new MockTranslator( 'de_AT', 'messages'),
                'tzunghaor'
            );
        }

        $hierarchy = $provider->getScopeDisplayHierarchy($searchString);

        // todo: avoid testing extra path
        self::assertEquals($expected, $hierarchy);
    }

    public function testDuplicateScope()
    {
        self::expectException(InvalidConfigurationException::class);

        $scopeProvider = new StaticScopeProvider(
            [['name' => 'foo', 'children' =>
                [['name' => 'bar'], ['name' => 'foo']]
            ]],
            'default'
        );
        $scopeProvider->getScope(null);
    }

}
