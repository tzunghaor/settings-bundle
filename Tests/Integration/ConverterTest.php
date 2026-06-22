<?php

namespace Tzunghaor\SettingsBundle\Test\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TestApp\Model\Message;
use TestApp\Service\CustomSettingConverter;
use TestApp\Settings\Ui\BoxSettings;
use Tzunghaor\SettingsBundle\Model\Type;
use Tzunghaor\SettingsBundle\Service\SettingsService;

class ConverterTest extends KernelTestCase
{
    public function testCustomConverter(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => false]);

        // 1. set up what custom converter should do
        /** @var CustomSettingConverter $converter */
        $converter = self::getContainer()->get(CustomSettingConverter::class);
        $convertedType = new Type('object', false, Message::class, true);
        $converter->setAcceptedType($convertedType);
        // this expected to be used when save() is called
        $converter->setFromStringResult([new Message('a', 'Converted from string')]);
        // this expected to be used when getSection() is called
        $converter->setToStringResult('["b-Converted to string"]');

        // 2. call service methods that should trigger custom converter
        /** @var SettingsService $service */
        $service = self::getContainer()->get('tzunghaor_settings.settings_service.default');

        $messagesToSave = [new Message('c', 'Saved')];
        $service->save(BoxSettings::class, 'root', ['messages' => $messagesToSave]);

        $boxSettings = $service->getSection(BoxSettings::class, 'root');

        // 3. check results
        self::assertInstanceOf(BoxSettings::CLASS, $boxSettings);
        self::assertCount(1, $boxSettings->getMessages());
        self::assertEquals('a', $boxSettings->getMessages()[0]->getType());
        self::assertEquals('Converted from string', $boxSettings->getMessages()[0]->getText());

        // 3b. check that converter was called with expected values
        self::assertEquals([$messagesToSave], $converter->getToStringValues());
        self::assertEquals(['["b-Converted to string"]'], $converter->getFromStringValues());
    }

    public function testSerializerConverter(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => false]);

        // 1. call service methods that should trigger serializer converter
        /** @var SettingsService $service */
        $service = self::getContainer()->get('tzunghaor_settings.settings_service.default');

        $message = new Message('c', 'Saved');
        $messagesToSave = [$message];
        $service->save(BoxSettings::class, 'root', ['messages' => $messagesToSave]);
        // modify message to be sure that not just this instance is passed around instead of serialization
        $message->setText('modified');

        $boxSettings = $service->getSection(BoxSettings::class, 'root');

        // 3. check results
        self::assertInstanceOf(BoxSettings::CLASS, $boxSettings);
        self::assertCount(1, $boxSettings->getMessages());
        self::assertEquals('c', $boxSettings->getMessages()[0]->getType());
        self::assertEquals('Saved', $boxSettings->getMessages()[0]->getText());
    }
}