<?php

namespace Tzunghaor\SettingsBundle\Tests\Integration\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\Cache\CacheInterface;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use Tzunghaor\SettingsBundle\Tests\TestProject\Entity\OtherPersistedSetting;
use Tzunghaor\SettingsBundle\Tests\TestProject\Entity\OtherSubject;
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
                    'uri' => '/settings/edit/default/day/Ui.BoxSettings',
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
                    'uri' => '/settings/edit/default/root/Ui.BoxSettings',
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
                    'uri' => '/settings/edit/default/day/Ui.BoxSettings',
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
                    'uri' => '/settings/edit/default/root/Ui.BoxSettings',
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
        $this->setUpDatabase();
        $this->doEdits($browser, $edits);

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

    /**
     * Testing custom scope provider, custom entity and custom cache
     */
    public function testCustomRoutes(): void
    {
        $browser = static::createClient();
        self::bootKernel(['environment' => 'test', 'debug' => false]);
        $this->setUpDatabase();

        /** @var AdapterInterface $cache */
        $cache = self::$container->get('test_other_cache');
        $cache->clear();

        self::assertNull($this->getJoeFunCachedItem(), 'test should have emptied TestProject cache');


        // OtherScopeProvider uses request's "scopeSubject" as default scope
        // Set FunSettings.name for joe user and foo group, and SadSettings.name for user axolotl
        $edits = [
            [
                'uri' => '/edit-other-subject/FunSettings?scopeSubject=name-joe&extra=extra-joe',
                'formEdits' => [
                    'settings_editor' => [
                        'settings' => ['name' => 'fun-joe'],
                        'in_scope' => ['name' => 1],
                    ],
                ],
            ],
            [
                'uri' => '/edit-other-subject/FunSettings?scopeSubject=group-foo&extra=lightning',
                'formEdits' => [
                    'settings_editor' => [
                        'settings' => ['name' => 'fun-foo'],
                        'in_scope' => ['name' => 1],
                    ],
                ],
            ],
            [
                'uri' => '/edit-other-subject/SadSettings?scopeSubject=name-axolotl',
                'formEdits' => [
                    'settings_editor' => [
                        'settings' => ['name' => 'sad-axolotl'],
                        'in_scope' => ['name' => 1],
                    ],
                ],
            ],
        ];
        $this->doEdits($browser, $edits);

        // test correct settings are returned in custom controller
        $expectedOutputs = [
            'joe' => 'fun-joe --- baba', // name level fun setting, class default sad setting
            'bill' => 'fun-foo --- baba', // group level fun setting, class default sad setting
            'axolotl' => 'baba --- sad-axolotl', // class default fun setting, name level sad setting
        ];

        foreach ($expectedOutputs as $subjectName => $expectedOutput) {
            $crawler = $browser->request('get', '/other-test?scopeSubject=name-' . $subjectName);

            self::assertEquals($expectedOutput, $crawler->text());
        }
        
        // check that custom entity is used and "extra" is filled
        $entityManager = self::$container->get('doctrine')->getManager();
        
        $joeEntity = $entityManager->find(OtherPersistedSetting::class, 
                                          ['scope' => 'name-joe', 'path' => 'FunSettings.name']);
        self::assertEquals('extra-joe', $joeEntity->getExtra());
        $fooEntity = $entityManager->find(OtherPersistedSetting::class,
                                          ['scope' => 'group-foo', 'path' => 'FunSettings.name']);
        self::assertEquals('lightning', $fooEntity->getExtra());

        // check that custom cache is used
        self::assertNotNull($this->getJoeFunCachedItem());
    }

    private function doEdits(KernelBrowser $browser, array $edits): void
    {
        foreach ($edits as $edit) {
            $crawler = $browser->request('get', $edit['uri']);
            $form = $crawler->selectButton('Save')->form();

            foreach ($edit['formEdits'] as $field => $value) {
                $form[$field] = $value;
            }

            $browser->submit($form);
        }
    }

    /**
     * @return mixed null if
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getJoeFunCachedItem()
    {
        /** @var CacheInterface $cache */
        $cache = self::$container->get('test_other_cache');

        $cacheKey = 'tzunghaor_settings_section.Tzunghaor.SettingsBundle.Tests.TestProject.OtherSettings.FunSettings..name-joe';
        $cachedItem = $cache->get($cacheKey, function() { return null; });

        if ($cachedItem === null) {
            // delete the null representing "missing"
            $cache->delete($cacheKey);
        }

        return $cachedItem;
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
                        'href' => '/settings/edit/default/root/foo',
                        'current' => true,
                        'children' => [
                            'day' => [
                                'href' => '/settings/edit/default/day/foo',
                                'children' => [
                                    'morning' => [
                                        'href' => '/settings/edit/default/morning/foo',
                                    ],
                                    'afternoon' => [
                                        'href' => '/settings/edit/default/afternoon/foo',
                                    ]
                                ]
                            ],
                            'night' => [
                                'href' => '/settings/edit/default/night/foo',
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
                        'href' => '/settings/edit/default/root/foo',
                        // not matching elements that are shown only because of matching child have no href
                        'children' => [
                            'day' => [
                                'href' => '/settings/edit/default/day/foo',
                                'current' => true,
                                'children' => [
                                    'morning' => [
                                        'href' => '/settings/edit/default/morning/foo',
                                    ],
                                ]
                            ],
                            'night' => [
                                'href' => '/settings/edit/default/night/foo',
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
        $crawler = $browser->xmlHttpRequest('post', '/settings/scope-search', [], [], [], json_encode($content));

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

    private function setUpDatabase(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get('doctrine')->getManager();

        // drop existing database and build structure
        $allMetadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($allMetadata);
        $schemaTool->updateSchema($allMetadata);

        // insert test data
        $otherSubjectJoe = new OtherSubject();
        $otherSubjectJoe->setName('joe');
        $otherSubjectJoe->setGroup('foo');
        $entityManager->persist($otherSubjectJoe);

        $otherSubjectBill = new OtherSubject();
        $otherSubjectBill->setName('bill');
        $otherSubjectBill->setGroup('foo');
        $entityManager->persist($otherSubjectBill);

        $otherSubjectAxolotl = new OtherSubject();
        $otherSubjectAxolotl->setName('axolotl');
        $otherSubjectAxolotl->setGroup('bar');
        $entityManager->persist($otherSubjectAxolotl);

        $entityManager->flush();
    }
}