<?php


namespace Tzunghaor\SettingsBundle\Controller;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
use Twig\Environment;
use Tzunghaor\SettingsBundle\Model\EditorUrlParameters;
use Tzunghaor\SettingsBundle\Service\SettingsEditorService;

/**
 * Controller to edit the settings stored in DB
 */
class SettingsEditorController
{
    private RouterInterface $router;

    private Environment $twig;

    private SettingsEditorService $settingsEditorService;


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
     * Settings edit form controller action
     *
     * @throws Throwable
     */
    public function edit(Request $request): Response
    {
        $formEditorHelper = $this->settingsEditorService->handleRequest($request);
        if ($formEditorHelper->isSuccessfulSubmit()) {
            if ($request->hasSession() && method_exists($request->getSession(), 'getFlashBag')) {
                $request->getSession()->getFlashBag()->add('success', 'Settings saved');
            }

            return new RedirectResponse($formEditorHelper->getEditorUrl($this->router));
        }

        return new Response($formEditorHelper->renderForm($this->settingsEditorService, $this->twig));
    }

    /**
     * Scope search controller action
     *
     * @throws Throwable
     */
    public function searchScope(Request $request): Response
    {
        $requestObject = json_decode($request->getContent(), true);

        $searchString = $requestObject['searchString'];
        $collection = $requestObject['collection'];
        $section = $requestObject['section'];
        $currentScope = $requestObject['currentScope'];
        $linkRouteName = $requestObject['linkRoute'];

        $linkRoute = $this->router->getRouteCollection()->get($linkRouteName);
        $fixedParameters = $linkRoute->getDefault('fixedParameters') ?? [];
        $editorUrlParameters = new EditorUrlParameters($linkRouteName, $fixedParameters);
        $sectionAddress = $this->settingsEditorService->createSectionAddress($section, $currentScope, $collection);

        $twigContext = $this->settingsEditorService->getSearchScopeTwigContext($searchString,
            $sectionAddress, $editorUrlParameters);

        return new Response($this->twig->render('@TzunghaorSettings/list.html.twig', $twigContext));
    }
}
