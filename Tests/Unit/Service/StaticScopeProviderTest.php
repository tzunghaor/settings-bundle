<?php

namespace Tzunghaor\SettingsBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Tzunghaor\SettingsBundle\Model\Scope;
use Tzunghaor\SettingsBundle\Service\StaticScopeProvider;

class StaticScopeProviderTest extends TestCase
{
    private $scopeHierarchy = [
        ['name' => 'all', 'children' => [
            ['name' => 'foo'],
            ['name' => 'bar',  'title' => 'Great Bar', 'class' => 'lofty', 'children' => [
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
            new Scope('all', null, [
                new Scope('foo', null, [], false, []),
                new Scope('bar', 'Great Bar', [
                    new Scope('bar1', null, [], false, []),
                    new Scope('bar2', null, [], false, []),
                ], false, ['class' => 'lofty'])
            ], false, []),
            new Scope('johnny', null, [
                new Scope('babar', null, [], false, []),
                new Scope('doofoo', null, [], false, []),
            ], false, []),
        ];

        $barExpected = [
            new Scope('all', null, [
                new Scope('bar', 'Great Bar', [
                    new Scope('bar1'),
                    new Scope('bar2'),
                ], false, ['class' => 'lofty'])
            ], true),
            new Scope('johnny', null, [
                new Scope('babar'),
            ], true),
        ];

        return [
            // no search => return all
            'all scopes' => [null, $defaultExpected],
            // no matching scope => empty list
            'search not found' => ['xy', []],
            // matching "bar"
            'search "bar"' => ['bar', $barExpected]
        ];
    }

    /**
     * @dataProvider scopeHierarchyProvider
     */
    public function testGetScopeHierarchy($searchString, $expected): void
    {
        $provider = new StaticScopeProvider($this->scopeHierarchy, 'all');

        $hierarchy = $provider->getScopeDisplayHierarchy($searchString);

        // todo: avoid testing extra path
        self::assertEquals($expected, $hierarchy);
    }

    public function testDuplicateScope()
    {
        self::expectException(InvalidConfigurationException::class);

        new StaticScopeProvider(
            [['name' => 'foo', 'children' =>
                [['name' => 'bar'], ['name' => 'foo']]
            ]],
            'default'
        );
    }

}