<?php


namespace Tzunghaor\SettingsBundle\Controller;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
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
     * Settings edit form controller action
     *
     * @throws Throwable
     */
    public function edit(Request $request, ?string $collection, ?string $section, ?string $scope): Response
    {
        // Make the request handled as PATCH, so that non-submitted values are not cleared,
        // but remain the current inherited values.
        // The form defines PATCH, but it doesn't work without `http_method_override` set to true in the config,
        // and that is false by default, and I don't want to make people enable it only for this form.
        $request->setMethod(Request::METHOD_PATCH);

        $route = $request->attributes->get('_route');
        $fixedParameters = $request->attributes->get('fixedParameters', []);
        $searchRoute = $request->attributes->get('searchRoute', 'tzunghaor_settings_scope_search');
        $searchUrl = empty($searchRoute) ? null : $this->router->generate($searchRoute);
        $template = $request->attributes->get('template', '@TzunghaorSettings/editor_page.html.twig');
        $urlGenerator = $this->createUrlGenerator($route, $fixedParameters);
        $sectionAddress = $this->settingsEditorService->createSectionAddress($section, $scope, $collection);

        $form = $this->settingsEditorService->createForm($sectionAddress);

        // $form might be null if $section is not defined
        if ($form !== null) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->settingsEditorService->save($form->getData(), $sectionAddress);
                $routeParameters = ['collection' => $collection, 'section' => $section, 'scope' => $scope];
                $uri = $urlGenerator($routeParameters);

                return new RedirectResponse($uri);
            }
        }

        $twigContext = $this->settingsEditorService->getTwigContext($sectionAddress, $urlGenerator, $form,
                                                                    $searchUrl, $route, $fixedParameters);

        return new Response($this->twig->render($template, $twigContext));
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
        $urlGenerator = $this->createUrlGenerator($linkRouteName, $fixedParameters);
        $sectionAddress = $this->settingsEditorService->createSectionAddress($section, $currentScope, $collection);

        $twigContext = $this->settingsEditorService->getSearchScopeTwigContext($searchString, $sectionAddress, $urlGenerator);

        return new Response($this->twig->render('@TzunghaorSettings/list.html.twig', $twigContext));
    }

    /**
     * Creates a function that can generate urls to the editor page
     *
     * @return callable function(array $routeParameters): string
     */
    protected function createUrlGenerator(string $route, array $fixedParameters): callable
    {
        $fixedParametersFlipped = array_flip($fixedParameters);

        return function (array $parameters = []) use ($route, $fixedParametersFlipped) {
            $filteredParameters = array_diff_key($parameters, $fixedParametersFlipped);

            return $this->router->generate($route, $filteredParameters);
        };
    }
}
