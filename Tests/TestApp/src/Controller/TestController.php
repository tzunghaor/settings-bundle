<?php

namespace TestApp\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TestApp\Entity\User;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use TestApp\OtherSettings\FunSettings;
use TestApp\OtherSettings\SadSettings;

class TestController
{
    public function __construct(private AuthorizationCheckerInterface $authorizationChecker)
    {

    }

    public function other(SettingsService $otherSettings)
    {
        // get the settings of the default scope
        /** @var FunSettings $funSettings */
        $funSettings = $otherSettings->getSection(FunSettings::class);
        /** @var SadSettings $sadSettings */
        $sadSettings = $otherSettings->getSection(SadSettings::class);

        return new Response($funSettings->getName() . ' --- ' . $sadSettings->getName());
    }

    public function customGrant(EntityManagerInterface $em, string $userId): Response
    {
        $user = $em->find(User::class, $userId);
        $content = $this->authorizationChecker->isGranted('edit_settings', $user) ? 'granted' : 'NOT granted';

        return new Response($content);
    }
}