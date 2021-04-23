<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Helper;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        // @codingStandardsIgnoreStart
        // phpcs:disable
        return [
            // Legacy Zend Framework aliases
            'aliases' => [
                \Zend\Expressive\Helper\ServerUrlHelper::class => ServerUrlHelper::class,
                \Zend\Expressive\Helper\Template\TemplateVariableContainerMiddleware::class => Template\TemplateVariableContainerMiddleware::class,
                \Zend\Expressive\Helper\ServerUrlMiddleware::class => ServerUrlMiddleware::class,
                \Zend\Expressive\Helper\UrlHelper::class => UrlHelper::class,
                \Zend\Expressive\Helper\UrlHelperMiddleware::class => UrlHelperMiddleware::class,
            ],
            'invokables' => [
                ServerUrlHelper::class                              => ServerUrlHelper::class,
                Template\TemplateVariableContainerMiddleware::class => Template\TemplateVariableContainerMiddleware::class,
            ],
            'factories'  => [
                ServerUrlMiddleware::class => ServerUrlMiddlewareFactory::class,
                UrlHelper::class           => UrlHelperFactory::class,
                UrlHelperMiddleware::class => UrlHelperMiddlewareFactory::class,
            ],
        ];
        // phpcs:enable
        // @codingStandardsIgnoreEnd
    }
}
