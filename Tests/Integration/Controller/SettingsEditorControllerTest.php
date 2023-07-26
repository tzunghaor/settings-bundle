<?php

namespace Integration\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use TestApp\Entity\OtherPersistedSetting;
use TestApp\Entity\OtherSubject;
use TestApp\OtherSettings\FunSettings;
use TestApp\Settings\Ui\BoxSettings;

class SettingsEditorControllerTest extends WebTestCase
{
    public function editDataProvider(): array
    {
        $defaultBoxSettings = new BoxSettings();
        $expectedDayBoxSettings = new BoxSettings(0, 14, [], false);

        // simple case: edit only in one scope
        $cases['day'] = [
            'edits' => [
                [
                    'uri' => '/settings/edit/default/day/Ui.BoxSettings',
                    'formEdits' => [
                        // set value for "margin" but not the "in_scope" radio button => it should not be saved
                        'settings_editor' => [
                            'settings' => ['padding' => 12, 'margin' => 14, 'nightMode' => false],
                            'in_scope' => ['padding' => 0, 'margin' => 1, 'borders' => 1, 'nightMode' => 1],
                        ],
                        // multi-select must be set separately
                        'settings_editor[settings][borders]' => [],
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
        $settingsService = self::getContainer()->get('tzunghaor_settings.settings_service');

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
     * Tests security feature
     */
    public function testSecurity(): void
    {
        $browser = static::createClient();
        self::bootKernel(['environment' => 'test', 'debug' => false]);
        $this->setUpDatabase();

        /** @var SettingsService $settingsService */
        $settingsService = self::getContainer()->get('tzunghaor_settings.settings_service.other');

        // the ForbiddenAuthorizationChecker denies editing scopes containing "forbidden"
        // unless "allow" query parameter is present in the HTTP request.

        $uri = '/settings/edit/other/name-forbidden/FunSettings';
        $browser->request('get', $uri);
        self::assertEquals(Response::HTTP_FORBIDDEN, $browser->getResponse()->getStatusCode());
        self::assertEquals(
            'fff',
            $settingsService->getSection(FunSettings::class, 'name-forbidden')->foo,
            'setting should NOT be changed after GET request'
        );

        $browser->request('get', $uri . '?allow');
        self::assertEquals(Response::HTTP_OK, $browser->getResponse()->getStatusCode());
        self::assertEquals(
            'fff',
            $settingsService->getSection(FunSettings::class, 'name-forbidden')->foo,
            'setting should NOT be changed after GET request'
        );

        $formData = [
            '_method' => 'PATCH',
            'settings_editor' => [
                'in_scope' => ['foo' => '1'],
                'settings' => ['foo' => 'edited'],
            ],
        ];

        $browser->request('post', $uri, $formData);
        self::assertEquals(Response::HTTP_FORBIDDEN, $browser->getResponse()->getStatusCode());
        self::assertEquals(
            'fff',
            $settingsService->getSection(FunSettings::class, 'name-forbidden')->foo,
            'setting should NOT be changed after forbidden POST request'
        );

        $browser->request('post', $uri . '?allow', $formData);
        self::assertEquals(Response::HTTP_FOUND, $browser->getResponse()->getStatusCode());
        self::assertEquals(
            'edited',
            $settingsService->getSection(FunSettings::class, 'name-forbidden')->foo,
            'setting should be changed after allowed POST request'
        );
    }

    public function templateDataProvider(): array
    {
        $collectionSelectorXpath = '//div[contains(@class,"tzunghaor_settings_collection_selector")]';
        $collectionItemXpath = $collectionSelectorXpath . '//*[@class="tzunghaor_settings_item"]';
        return [
            'default template' => [
                '/settings/edit/default/day/Ui.BoxSettings',
                [
                    '//title' => [
                        'text' => self::equalTo('Tzunghaor Settings'),
                    ],
                    $collectionItemXpath => [
                        'count' => self::equalTo(2),
                    ],
                    "($collectionItemXpath)[1]" => [
                        'text' => self::equalTo('Nice Default Collection'),
                    ],
                    "($collectionItemXpath)[2]" => [
                        'text' => self::equalTo('Super Other Collection'),
                    ],
                    '//div[contains(@class,"tzunghaor_settings_scopes_list")]//*[1][@class="tzunghaor_settings_item"]' => [
                        'text' => self::equalTo('Root of All'),
                    ],
                ],
            ],

            'custom template' => [
                '/custom-template/default/day/Ui.BoxSettings',
                [
                    '//title' => [
                        'text' => self::equalTo('Custom Template'),
                    ],
                    '//div[@id="custom_scope_list"]//*[1][@class="tzunghaor_settings_item"]/span' => [
                        'text' => self::equalTo('---Root of All'),
                        'class' => self::equalTo('root-class'),
                    ]

                ],
            ],
        ];
    }


    /**
     * Tests the twig templates
     *
     * @dataProvider templateDataProvider
     */
    public function testTemplate(string $uri, array $assertions): void
    {
        $browser = static::createClient();
        self::bootKernel(['environment' => 'test', 'debug' => false]);
        $crawler = $browser->request('get', $uri);

        foreach ($assertions as $xpath => $elementAssertions) {
            $element = $crawler->filterXPath($xpath);
            foreach ($elementAssertions as $what => $value) {
                self::assertElement($value, $element, $what, $xpath . ' --- ' . $what);
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
        $cache = self::getContainer()->get('test_other_cache');
        $cache->clear();

        self::assertNull($this->getJoeFunCachedItem(), 'test should have emptied TestApp cache');


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
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        
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
        $cache = self::getContainer()->get('test_other_cache');

        $cacheKey = 'tzunghaor_settings_section.TestApp.OtherSettings.FunSettings..name-joe';
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
                    'Root of All' => [
                        'href' => '/settings/edit/default/root/foo',
                        'current' => true,
                        'children' => [
                            'Beautiful Day' => [
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
                    'Root of All' => [
                        'href' => '/settings/edit/default/root/foo',
                        // not matching elements that are shown only because of matching child have no href
                        'children' => [
                            'Beautiful Day' => [
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
                $scopeTitle = trim($links->first()->text());
            } else {
                self::assertEquals(0, $links->count());
                $scopeTitle = trim($li->filterXPath('./li/span')->text());
            }
            self::assertEquals($expectedName, $scopeTitle);

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
        $entityManager = self::getContainer()->get('doctrine')->getManager();

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

        $otherSubjectForbidden = new OtherSubject();
        $otherSubjectForbidden->setName('forbidden');
        $otherSubjectForbidden->setGroup('forbidden');
        $entityManager->persist($otherSubjectForbidden);

        $entityManager->flush();
    }

    /**
     * Helper method to assert certain aspects of html element(s) found by the crawler
     *
     * @param string $what 'count' | 'text' | 'class'
     */
    private static function assertElement(Constraint $constraint, Crawler $element, string $what, string $message): void
    {
        switch ($what) {
            case 'count':
                self::assertThat($element->count(), $constraint, $message);
                break;

            case 'text':
                self::assertThat(trim($element->text()), $constraint, $message);
                break;

            case 'class':
                self::assertThat($element->attr('class'), $constraint, $message);
                break;

            default:
                throw new \DomainException('Unknown $what in ' . __METHOD__);
        }
    }
}