<?php

namespace Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TestApp\OtherSettings\FunSettings;
use TestApp\Service\MinimalTestService;
use TestApp\Service\TestService;
use TestApp\Settings\Ui\BoxSettings;
use TestApp\TestKernel;

class AutowiringTest extends KernelTestCase
{
    protected static $class = TestKernel::class;

    /**
     * test environment="test" which has complex config
     */
    public function testAutowiring(): void
    {
        self::bootKernel();
        $this->setUpDatabase();

        $testService = self::getContainer()->get(TestService::class);

        self::assertInstanceOf(BoxSettings::class, $testService->getBoxSettings('day'));
        self::assertInstanceOf(FunSettings::class, $testService->getFunSettings());
    }

    /**
     * test environment="minimal" which has as little config as possible
     */
    public function testMinimalAutowiring(): void
    {
        self::bootKernel(['environment' => 'minimal']);
        $this->setUpDatabase();

        $testService = self::getContainer()->get(MinimalTestService::class);

        self::assertInstanceOf(BoxSettings::class, $testService->getBoxSettings());
        self::assertInstanceOf(BoxSettings::class, $testService->getBoxSettings('default'));
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
    }
}