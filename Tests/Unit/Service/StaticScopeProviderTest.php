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
            ['name' => 'bar', 'children' => [
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
    public function testGetScopePath($defaultScope, $subject, $expected): void
    {
        $provider = new StaticScopeProvider($this->scopeHierarchy, $defaultScope);

        $path = $provider->getScopePath($subject);

        self::assertEquals($expected, $path);
    }

    public function scopeHierarchyProvider(): array
    {
        // Without search string the original scopes are returned with path, though path is not necessary
        // maybe factor out path from Scope? Or always return path?
        $defaultExpected = [
            new Scope('all', [
                new Scope('foo', [], false, ['path' => ['all']]),
                new Scope('bar', [
                    new Scope('bar1', [], false, ['path' => ['all', 'bar']]),
                    new Scope('bar2', [], false, ['path' => ['all', 'bar']]),
                ], false, ['path' => ['all']])
            ], false, ['path' => []]),
            new Scope('johnny', [
                new Scope('babar', [], false, ['path' => ['johnny']]),
                new Scope('doofoo', [], false, ['path' => ['johnny']]),
            ], false, ['path' => []]),
        ];

        $barExpected = [
            new Scope('all', [
                new Scope('bar', [
                    new Scope('bar1'),
                    new Scope('bar2'),
                ])
            ], true),
            new Scope('johnny', [
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