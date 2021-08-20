<?php

namespace Tzunghaor\SettingsBundle\Tests\Integration\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use Tzunghaor\SettingsBundle\Tests\TestProject\Settings\Ui\BoxSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\TestKernel;

class SettingsEditorControllerTest extends WebTestCase
{
    protected static $class = TestKernel::class;

    public function editDataProvider(): array
    {
        $defaultBoxSettings = new BoxSettings();
        $expectedDayBoxSettings = new BoxSettings(0, 14, ['top', 'left']);

        // simple case: edit only in one scope
        $cases['day'] = [
            'edits' => [
                [
                    'uri' => '/edit/default/day/Ui.BoxSettings',
                    'formEdits' => [
                        // set value for "margin" but not the "in_scope" radio button => it should not be saved
                        'settings_editor' => [
                            'settings' => ['padding' => 12, 'margin' => 14],
                            'in_scope' => ['padding' => 0, 'margin' => 1, 'borders' => 1],
                        ],
                        // multi-select must be set separately
                        'settings_editor[settings][borders]' => ['top', 'left'],
                    ],
                ],
            ],
            'expectedSettings' => [
                BoxSettings::class => [
                    // root scope is not changed: expected class default values
                    'root' => $defaultBoxSettings,
                    // night not changed
                    'night' => $defaultBoxSettings,
                    // day scope is changed: expected saved values
                    'day' => $expectedDayBoxSettings,
                    // morning and afternoon are not changed: expected inherited values from day
                    'morning' => $expectedDayBoxSettings,
                    'afternoon' => $expectedDayBoxSettings,
                ]
            ],
        ];

        $expectedComplexRootBoxSettings = new BoxSettings(8, 0, ['top', 'bottom']);
        $expectedComplexDayBoxSettings = new BoxSettings(8, 22, ['right']);

        // complex case: edit in one scope twice and in a lower level scope once
        $cases['complex'] = [
            'edits' => [
                // first set margin and borders in root
                [
                    'uri' => '/edit/default/root/Ui.BoxSettings',
                    'formEdits' => [
                        'settings_editor' => [
                            'settings' => ['padding' => 12, 'margin' => 14],
                            'in_scope' => ['padding' => 0, 'margin' => 1, 'borders' => 1],
                        ],
                        // multi-select must be set separately
                        'settings_editor[settings][borders]' => ['top', 'left'],
                    ],
                ],

                // then set custom margin and borders in the lower level 'day'
                [
                    'uri' => '/edit/default/day/Ui.BoxSettings',
                    'formEdits' => [
                        'settings_editor' => [
                            'settings' => ['padding' => 0, 'margin' => 22],
                            'in_scope' => ['padding' => 0, 'margin' => 1, 'borders' => 1],
                        ],
                        // multi-select must be set separately
                        'settings_editor[settings][borders]' => ['right'],
                    ],
                ],

                // and last unset margin but set padding and different borders in root
                [
                    'uri' => '/edit/default/root/Ui.BoxSettings',
                    'formEdits' => [
                        'settings_editor' => [
                            'settings' => ['padding' => 8, 'margin' => 62],
                            'in_scope' => ['padding' => 1, 'margin' => 0, 'borders' => 1],
                        ],
                        // multi-select must be set separately
                        'settings_editor[settings][borders]' => ['top', 'bottom'],
                    ],
                ],

            ],

            'expectedSettings' => [
                BoxSettings::class => [
                    // afternoon and morning has no custom setting, they inherit from day
                    'afternoon' => $expectedComplexDayBoxSettings,
                    'morning' => $expectedComplexDayBoxSettings,
                    // day has custom margin and border, inherits padding from root
                    'day' => $expectedComplexDayBoxSettings,
                    // night has no custom settings, just inherit from root
                    'night' => $expectedComplexRootBoxSettings,
                    // root has custom value for padding and border
                    'root' => $expectedComplexRootBoxSettings,
                ]
            ],
        ];

        return $cases;
    }


    /**
     * @dataProvider editDataProvider
     *
     * @param array $edits
     * @param array $expectedSections
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Tzunghaor\SettingsBundle\Exception\SettingsException
     */
    public function testEdit(array $edits, array $expectedSections)
    {
        $browser = static::createClient();
        self::bootKernel(['environment' => 'test', 'debug' => false]);

        // test uses temporary in-memory DB, so create tables when booting
        $entityManager = self::$container->get('doctrine')->getManager();

        $allMetadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($allMetadata);
        $schemaTool->updateSchema($allMetadata);

        foreach ($edits as $edit) {
            $crawler = $browser->request('get', $edit['uri']);
            $form = $crawler->selectButton('Save')->form();

            foreach ($edit['formEdits'] as $field => $value) {
                $form[$field] = $value;
            }

            $browser->submit($form);
        }

        /** @var SettingsService $settingsService */
        $settingsService = self::$container->get('tzunghaor_settings.settings_service');

        foreach ($expectedSections as $sectionClass => $expectedScopedSections) {
            foreach ($expectedScopedSections as $scope => $expectedSection) {
                self::assertEquals(
                    $expectedSection,
                    $settingsService->getSection($sectionClass, $scope),
                    sprintf('Unexpected values for "%s" in scope "%s"', $sectionClass, $scope)
                );
            }
        }
    }

    public function searchScopeDataProvider(): array
    {
        return [
            'search empty string' => [
                [
                    'collection' => 'default',
                    'currentScope' => 'root',
                    'section' => 'foo',
                    'searchString' => '',
                    'linkRoute' => 'tzunghaor_settings_edit',
                ],
                [
                    'root' => [
                        'href' => '/edit/default/root/foo',
                        'current' => true,
                        'children' => [
                            'day' => [
                                'href' => '/edit/default/day/foo',
                                'children' => [
                                    'morning' => [
                                        'href' => '/edit/default/morning/foo',
                                    ],
                                    'afternoon' => [
                                        'href' => '/edit/default/afternoon/foo',
                                    ]
                                ]
                            ],
                            'night' => [
                                'href' => '/edit/default/night/foo',
                            ],
                        ],
                    ],
                ],
            ],
            'search "ni"' => [
                [
                    'collection' => 'default',
                    'currentScope' => 'day',
                    'section' => 'foo',
                    'searchString' => 'ni',
                    'linkRoute' => 'tzunghaor_settings_edit',
                ],
                [
                    'root' => [
                        // not matching elements that are shown only because of matching child have no href
                        'children' => [
                            'day' => [
                                'current' => true,
                                'children' => [
                                    'morning' => [
                                        'href' => '/edit/default/morning/foo',
                                    ],
                                ]
                            ],
                            'night' => [
                                'href' => '/edit/default/night/foo',
                            ],
                        ],
                    ],
                ],
            ],
            'notfound' => [
                [
                    'collection' => 'default',
                    'currentScope' => 'day',
                    'section' => 'foo',
                    'searchString' => 'xxxxxx',
                    'linkRoute' => 'tzunghaor_settings_edit',
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider searchScopeDataProvider
     *
     * @param array $content this will be passed in search POST request json-encoded
     * @param array $expected assertions of returned html
     */
    public function testSearchScope(array $content, array $expected): void
    {
        $browser = self::createClient();
        self::bootKernel(['environment' => 'test', 'debug' => false]);

        // though the actual response is only a partial starting with UL element, the crawler embeds it into HTML
        $crawler = $browser->xmlHttpRequest('post', '/scope-search', [], [], [], json_encode($content));

        $ul = $crawler->filterXPath('//body/ul');
        self::assertCorrectScopeList($ul, $expected);
    }

    /**
     * Asserts that the scope list looks like expected
     *
     * @param Crawler $list root node should be an UL
     * @param array $expected
     */
    private static function assertCorrectScopeList(Crawler $list, array $expected)
    {
        $i = 0;
        $liNodes = $list->filterXPath('./ul/li');
        self::assertEquals(count($expected), $liNodes->count());

        foreach ($expected as $expectedName => $expectations) {
            $li = $liNodes->eq($i);
            $class = $li->attr('class') ?? '';
            echo $class;
            if ($expectations['current'] ?? false) {
                self::assertStringContainsString('tzunghaor_settings_current', $class);
            } else {
                self::assertStringNotContainsString('tzunghaor_settings_current', $class);
            }

            $expectedHref = $expectations['href'] ?? null;
            $links = $li->filterXPath('./li/a');
            if ($expectedHref) {
                self::assertEquals(1, $links->count());
                self::assertEquals($expectedHref, $links->attr('href'));
                $scopeName = $links->first()->text();
            } else {
                self::assertEquals(0, $links->count());
                $scopeName = $li->filterXPath('./li/span')->text();
            }
            self::assertEquals($expectedName, $scopeName);

            $expectedChildren = $expectations['children'] ?? null;
            $ul = $li->filterXPath('./li/ul');
            if ($expectedChildren) {
                self::assertCorrectScopeList($ul, $expectedChildren);
            } else {
                self::assertEquals(0, $ul->count());
            }

            $i++;
        }
    }
}