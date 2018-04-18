<?php

namespace Lichee\Kernel;


use Lichee\Action\Action;
use Lichee\Kernel\Event\ActionEvent;
use Lichee\Action\Resolve\ActionResolver;
use Lichee\Router\RouteInterface;
use Lichee\Router\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class HttpHandle
 * @package Lichee\Kernel
 */
class HttpHandle
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * HttpHandle constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        /**
         * @var $router Router
         */
        $router = $this->container['router'];
        if (false == $router->matchCurrentRoute($request)) {
            throw new NotFoundHttpException('Page Not Found');
        }
        return $this->getResponse($this->actionResolver($router->route))
            ->prepare($request);
    }

    /**
     * @param RouteInterface $route
     * @return callable
     */
    public function actionResolver(RouteInterface $route)
    {
        /**
         * @var $actionResolver ActionResolver
         */
        $actionResolver = $this->container['action_resolver'];
        return $actionResolver->resolver($route);
    }

    /**
     * 控制器输出转换为 Response
     * @param $callable
     * @return Response
     */
    protected function getResponse($callable)
    {
        $callableResponse = $callable(function ($params) {
            $response = call_user_func_array([$params['action'], $params['method']], $params['params']);
            return $response;
        });
        $content = ob_get_clean();
        if ($callableResponse instanceof Response) {
            return $callableResponse;
        } else {
            /**
             * @var $response Response
             */
            $response = $this->container['response'];
            $response->setContent($content);
            return $response;
        }
    }

    /**
     * @return EventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->container['dispatcher'];
    }
}