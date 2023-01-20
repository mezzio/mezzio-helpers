<?php

declare(strict_types=1);

namespace Mezzio\Helper;

class ConfigProvider
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /** @return array<string, mixed> */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
                ServerUrlHelper::class => ServerUrlHelper::class,
                Template\TemplateVariableContainerMiddleware::class
                => Template\TemplateVariableContainerMiddleware::class,
            ],
            'factories'  => [
                ServerUrlMiddleware::class => ServerUrlMiddlewareFactory::class,
                UrlHelper::class           => UrlHelperFactory::class,
                UrlHelperMiddleware::class => UrlHelperMiddlewareFactory::class,
            ],
            'aliases'    => [
                UrlHelperInterface::class => UrlHelper::class,
            ],
        ];
    }
}
