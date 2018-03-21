<?php

namespace Lichee\Action;

use Lichee\Kernel\Container;
use Lichee\Router\RouteInterface;
use Lichee\Template\TemplateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Closure;


/**
 * Class Action
 * @package Lichee\Kernel\Action
 */
class Action
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var RouteInterface
     */
    protected $route;

    /**
     * @param Container|null $container
     */
    public function setContainer(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return RouteInterface
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * 设置当前路由
     * @param RouteInterface $route
     */
    public function setRoute(RouteInterface $route)
    {
        $this->route = $route;
    }

    /**
     * 根据参数生成一个URL
     * @param string $route The name of the route
     * @param array $parameters An array of parameters
     * @param int $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    public function generateUrl($route, $parameters = array(), $referenceType = 1)
    {
        return $this->container['router']->generate($route, $parameters, $referenceType);
    }

    /**
     * 根据指定URL返回一个RedirectResponse
     *
     * @param string $url The URL to redirect to
     * @param int $status The status code to use for the Response
     *
     * @return RedirectResponse
     */
    public function redirect($url, $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * 根据携带参数的Route返回一个RedirectResponse
     *
     * @param string $route The name of the route
     * @param array $parameters An array of parameters
     * @param int $status The status code to use for the Response
     *
     * @return RedirectResponse
     */
    protected function redirectToRoute($route, array $parameters = array(), $status = 302): RedirectResponse
    {
        $url = $this->generateUrl($route, $parameters);
        return $this->redirect($url, $status);
    }

    /**
     * 在当前会话中添加指定类型的flash消息
     *
     * @param string $type The type
     * @param string $message The message
     *
     * @throws \LogicException
     */
    protected function addFlash($type, $message)
    {
        if (!$this->container->has('session')) {
            throw new \LogicException('You can not use the addFlash method if sessions are disabled.');
        }

        $this->container->get('session')->getFlashBag()->add($type, $message);
    }

    /**
     * 返回一个渲染后的视图
     *
     * @param string $view The view name
     * @param array $parameters An array of parameters to pass to the view
     *
     * @return string The rendered view
     */
    public function renderView($view, array $parameters = array())
    {
        if ($this->container->has('template')) {
            return $this->container->get('template')->render($view, $parameters);
        }
    }

    /**
     * example:
     *  '@AppBundle/User/User.php'
     * @param string $view The view name
     * @param array $parameters An array of parameters to pass to the view
     * @param Response $response A response instance
     *
     * @return Response A Response instance
     */
    public function render($view, array $parameters = array(), Response $response = null): Response
    {
        if (null === $response) {
            $response = new Response();
        }
        /**
         * @var $template TemplateInterface
         */
        $template = $this->getTemplate();
        $response->setContent($template->render($view, $parameters));
        return $response;
    }

    /**
     * @return TemplateInterface
     */
    public function getTemplate()
    {
        return $this->container->get('template');
    }

    /**
     * This will result in a 404 response code. Usage example:
     *
     *     throw $this->createNotFoundException('Page not found!');
     *
     * @param string $message A message
     * @param \Exception|null $previous The previous exception
     *
     * @return NotFoundHttpException
     */
    public function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }

    /**
     * This will result in a 403 response code. Usage example:
     *
     *     throw $this->createAccessDeniedException('Unable to access this page!');
     *
     * @param string $message A message
     * @param \Exception|null $previous The previous exception
     *
     * @return AccessDeniedHttpException
     */
    public function createAccessDeniedException($message = 'Access Denied.', \Exception $previous = null, $code = 0)
    {
        return new AccessDeniedHttpException($message, $previous, $code);
    }

    /**
     * 返回Request
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->container->get('request');
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->container->get('response');
    }

    /**
     * @param $id
     * @return bool
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * @param $className
     * @return mixed
     */
    public function getService($className)
    {
        return $this->container['serviceGenerator']->get($className);
    }


}
