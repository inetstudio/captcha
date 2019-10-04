<?php

namespace InetStudio\CaptchaPackage\Captcha\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * Class BindingsServiceProvider.
 */
class BindingsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
    * @var  array
    */
    public $bindings = [
        'InetStudio\CaptchaPackage\Captcha\Contracts\Services\Front\CaptchaServiceContract' => 'InetStudio\CaptchaPackage\Captcha\Services\Front\CaptchaService',
    ];

    /**
     * Получить сервисы от провайдера.
     *
     * @return  array
     */
    public function provides()
    {
        return array_keys($this->bindings);
    }
}
