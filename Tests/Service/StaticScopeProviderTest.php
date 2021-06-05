<?php

namespace Tzunghaor\SettingsBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
        $barExpected = [
            ['name' => 'all', 'children' => [
                ['name' => 'bar', 'children' => [
                    ['name' => 'bar1'],
                    ['name' => 'bar2']
                ]],
            ]],
            ['name' => 'johnny', 'children' => [
                ['name' => 'babar'],
            ]],
        ];


        return [
            [null, $this->scopeHierarchy],
            ['xy', []],
            ['bar', $barExpected]
        ];
    }

    /**
     * @dataProvider scopeHierarchyProvider
     */
    public function testGetScopeHierarchy($searchString, $expected): void
    {
        $provider = new StaticScopeProvider($this->scopeHierarchy, 'all');

        $hierarchy = $provider->getScopeHierarchy($searchString);

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