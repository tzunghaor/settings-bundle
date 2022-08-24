<?php


namespace Tzunghaor\SettingsBundle;


use Symfony\Component\HttpKernel\Bundle\Bundle;

class TzunghaorSettingsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        if (null === $this->path) {
            $reflected = new \ReflectionObject($this);
            // use the modern directory structure
            $this->path = \dirname($reflected->getFileName(), 2);
        }

        return $this->path;
    }

}
