<?php

namespace Tzunghaor\SettingsBundle\Helper;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Tzunghaor\SettingsBundle\Model\EditorUrlParameters;
use Tzunghaor\SettingsBundle\Model\SettingSectionAddress;
use Tzunghaor\SettingsBundle\Service\SettingsEditorService;

class FormEditorHelper
{
    private bool $isSuccessfulSubmit;
    private SettingSectionAddress $sectionAddress;
    private EditorUrlParameters $editorUrlParameters;
    private ?FormInterface $form;
    private ?string $searchUrl;
    private string $template;
    private array $fixedParameters;

    public function __construct(
        bool                  $isSuccessfulSubmit,
        SettingSectionAddress $sectionAddress,
        EditorUrlParameters   $editorUrlParameters,
        ?FormInterface        $form,
        ?string               $searchUrl,
        string                $template,
        array                 $fixedParameters = []
    ) {
        $this->isSuccessfulSubmit = $isSuccessfulSubmit;
        $this->sectionAddress = $sectionAddress;
        $this->editorUrlParameters = $editorUrlParameters;
        $this->form = $form;
        $this->searchUrl = $searchUrl;
        $this->template = $template;
        $this->fixedParameters = $fixedParameters;
    }

    public function isSuccessfulSubmit(): bool
    {
        return $this->isSuccessfulSubmit;
    }

    public function getSectionAddress(): SettingSectionAddress
    {
        return $this->sectionAddress;
    }

    public function geteditorUrlParameters(): EditorUrlParameters
    {
        return $this->editorUrlParameters;
    }

    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    public function getSearchUrl(): ?string
    {
        return $this->searchUrl;
    }

    public function getFixedParameters(): array
    {
        return $this->fixedParameters;
    }

    public function getEditorUrl(RouterInterface $router): string
    {
        $routeParameters = [
            'collection' => $this->sectionAddress->getCollectionName(),
            'section' => $this->sectionAddress->getSectionName(),
            'scope' => $this->sectionAddress->getScope(),
        ];

        return $router->generate(
            $this->editorUrlParameters->getRoute(),
            $this->editorUrlParameters->filterParameters($routeParameters)
        );
    }

    public function renderForm(SettingsEditorService $settingsEditorService, Environment $twig): string
    {
        $twigContext = $settingsEditorService->getTwigContext(
            $this->sectionAddress,
            $this->editorUrlParameters,
            $this->form,
            $this->searchUrl,
            $this->fixedParameters
        );

        return $twig->render($this->template, $twigContext);
    }
}