<?php

/**
 * ViewFactory class.
 */

namespace Alltube;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Views\Smarty;
use Slim\Views\SmartyPlugins;
use SmartyException;

/**
 * Create Smarty view object.
 */
class ViewFactory
{
    /**
     * Create Smarty view object.
     *
     * @param ContainerInterface $container Slim dependency container
     * @param Request|null $request PSR-7 request
     *
     * @return Smarty
     * @throws SmartyException
     */
    public static function create(ContainerInterface $container, Request $request = null)
    {
        if (!isset($request)) {
            $request = $container->get('request');
        }

        $view = new Smarty(__DIR__ . '/../templates/');

        $uri = $request->getUri();
        if (in_array('https', $request->getHeader('X-Forwarded-Proto'))) {
            $uri = $uri->withScheme('https')->withPort(443);
        }

        // set values from X-Forwarded-* headers
        $host = current($request->getHeader('X-Forwarded-Host'));
        if ($host) {
            $uri = $uri->withHost($host);
        }

        $port = current($request->getHeader('X-Forwarded-Port'));
        if ($port) {
            $uri = $uri->withPort(intVal($port));
        }

        $path = current($request->getHeader('X-Forwarded-Path'));
        if ($path) {
            $uri = $uri->withBasePath($path);
        }

        /** @var LocaleManager $localeManager */
        $localeManager = $container->get('locale');

        $smartyPlugins = new SmartyPlugins($container->get('router'), $uri->withUserInfo(null));
        $view->registerPlugin('function', 'path_for', [$smartyPlugins, 'pathFor']);
        $view->registerPlugin('function', 'base_url', [$smartyPlugins, 'baseUrl']);
        $view->registerPlugin('block', 't', [$localeManager, 'smartyTranslate']);

        return $view;
    }
}
