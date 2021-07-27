<?php


namespace Tzunghaor\SettingsBundle\Controller;


use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Tzunghaor\SettingsBundle\Service\SettingsEditorService;

/**
 * Controller to edit the settings stored in DB
 */
class SettingsEditorController
{

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var ServiceLocator
     */
    private $settingsServiceLocator;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(
        ServiceLocator $settingsServiceLocator,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        Environment $twig
    ) {
        $this->router = $router;
        $this->twig = $twig;
        $this->settingsServiceLocator = $settingsServiceLocator;
        $this->formFactory = $formFactory;
    }

    /**
     * @param Request $request
     * @param string  $collection
     * @param string  $section
     * @param string  $scope
     *
     * @return RedirectResponse|Response
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function edit(Request $request, string $collection, string $section, string $scope)
    {
        $settingsService = $this->settingsServiceLocator->get($collection);
        $settingsEditorService = new SettingsEditorService($settingsService, $this->formFactory);
        $form = $settingsEditorService->createForm($section, $scope);

        // $form might be null if $section is not defined
        if ($form !== null) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $settingsEditorService->save($form->getData(), $section, $scope);
                $uri = $this->router->generate('tzunghaor_settings_edit', [
                    'collection' => $collection, 'section' => $section, 'scope' => $scope]);

                return new RedirectResponse($uri);
            }
        }

        $collections = array_keys($this->settingsServiceLocator->getProvidedServices());
        $twigContext = $settingsEditorService->getTwigContext($collections, $collection, $section, $scope, $form);

        return new Response($this->twig->render('@TzunghaorSettings/editor_page.html.twig', $twigContext));
    }
}