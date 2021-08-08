<?php


namespace Tzunghaor\SettingsBundle\Controller;


use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\TwigFunction;
use Tzunghaor\SettingsBundle\Service\SettingsEditorService;
use Tzunghaor\SettingsBundle\Service\SettingsService;

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
    /**
     * @var ServiceLocator
     */
    private $settingsServiceLocator;


    public function __construct(
        SettingsEditorService $settingsEditorService,
        ServiceLocator $settingsServiceLocator,
        RouterInterface $router,
        Environment $twig
    ) {
        $this->router = $router;
        $this->twig = $twig;
        $this->settingsEditorService = $settingsEditorService;
        $this->settingsServiceLocator = $settingsServiceLocator;
    }

    /**
     * Settings edit form controller action
     *
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
    public function edit(Request $request, string $collection, string $section, string $scope): Response
    {
        $route = $request->attributes->get('_route');
        $fixedParameters = $request->attributes->get('fixedParameters', []);
        $urlGenerator = $this->createUrlGenerator($route, $fixedParameters);
        $form = $this->settingsEditorService->createForm($section, $scope, $collection);

        // $form might be null if $section is not defined
        if ($form !== null) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->settingsEditorService->save($form->getData(), $section, $scope, $collection);
                $routeParameters = ['collection' => $collection, 'section' => $section, 'scope' => $scope];
                $uri = $urlGenerator($routeParameters);

                return new RedirectResponse($uri);
            }
        }

        $twigContext = $this->settingsEditorService
            ->getTwigContext($section, $scope, $form, $collection, $route, $fixedParameters);

        $this->twig->addFunction(new TwigFunction('tzungsettings_url', $urlGenerator));

        return new Response($this->twig->render('@TzunghaorSettings/editor_page.html.twig', $twigContext));
    }

    /**
     * Scope search controller action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchScope(Request $request): JsonResponse
    {
        $requestObject = json_decode($request->getContent(), true);
        $collection = $requestObject['collection'];
        $section = $requestObject['section'];
        $linkRoute = $requestObject['linkRoute'];

        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($collection);
        $hierarchy = $settingsService->getSettingsMetaService()->getScopeHierarchy($requestObject['searchString']);
        $this->addUrlToHierarchy($hierarchy, $collection, $section, $linkRoute);

        return new JsonResponse($hierarchy);
    }

    /**
     * Recursive function to build scopeHierarchy response for searchScope controller action
     *
     * @param array $hierarchy
     * @param string $collection
     * @param string $section
     * @param string $linkRoute name of route for which urls are generated for scopes
     */
    protected function addUrlToHierarchy(array &$hierarchy, string $collection, string $section, string $linkRoute): void
    {
        foreach ($hierarchy as &$item) {
            $urlParameters = ['collection' => $collection, 'scope' => $item['name'], 'section' => $section];
            $item['url'] = $this->router->generate($linkRoute, $urlParameters);
            if (array_key_exists('children', $item)) {
                $this->addUrlToHierarchy($item['children'], $collection, $section, $linkRoute);
            }
        }
    }

    /**
     * Creates a function that can generate urls to the editor page
     *
     * @param string $route
     * @param array $fixedParameters
     *
     * @return callable function(array $routeParameters): string
     */
    protected function createUrlGenerator(string $route, array $fixedParameters): callable
    {
        $fixedParametersFlipped = array_flip($fixedParameters);
        $router = $this->router;

        return function (array $parameters = []) use ($route, $router, $fixedParametersFlipped) {
            $filteredParameters = array_diff_key($parameters, $fixedParametersFlipped);

            return $this->router->generate($route, $filteredParameters);
        };
    }
}