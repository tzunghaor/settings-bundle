<?php

namespace TestApp\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use TestApp\Entity\OtherPersistedSetting;

class DoctrineListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Fills the OtherSetting entities extra field with the 'extra' http request param
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof OtherPersistedSetting) {
            $entity->setExtra($this->requestStack->getCurrentRequest()->get('extra', ''));
        }
    }
}