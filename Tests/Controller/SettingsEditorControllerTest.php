<?php

namespace Tzunghaor\SettingsBundle\Tests\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use Tzunghaor\SettingsBundle\Tests\TestProject\Settings\Ui\BoxSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\TestKernel;

class SettingsEditorControllerTest extends KernelTestCase
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
                    'uri' => '/day/Ui.BoxSettings',
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
                    'uri' => '/root/Ui.BoxSettings',
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
                    'uri' => '/day/Ui.BoxSettings',
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
                    'uri' => '/root/Ui.BoxSettings',
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
        self::bootKernel(['environment' => 'test', 'debug' => false]);
        $browser = new KernelBrowser(self::$kernel);

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
}