<?php


namespace Tzunghaor\SettingsBundle\Controller;


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
     * @var SettingsEditorService
     */
    private $settingsEditorService;


    public function __construct(
        SettingsEditorService $settingsEditorService,
        RouterInterface $router,
        Environment $twig
    ) {
        $this->router = $router;
        $this->twig = $twig;
        $this->settingsEditorService = $settingsEditorService;
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
        $form = $this->settingsEditorService->createForm($section, $scope, $collection);

        // $form might be null if $section is not defined
        if ($form !== null) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->settingsEditorService->save($form->getData(), $section, $scope, $collection);
                $uri = $this->router->generate('tzunghaor_settings_edit', [
                    'collection' => $collection, 'section' => $section, 'scope' => $scope]);

                return new RedirectResponse($uri);
            }
        }

        $twigContext = $this->settingsEditorService->getTwigContext($section, $scope, $form, $collection);

        return new Response($this->twig->render('@TzunghaorSettings/editor_page.html.twig', $twigContext));
    }
}