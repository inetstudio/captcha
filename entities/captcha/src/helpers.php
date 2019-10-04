<?php

if (! function_exists('no_captcha')) {
    /**
     * @param  string  $version
     *
     * @return \Illuminate\Contracts\Foundation\Application|mixed
     */
    function no_captcha($version = '')
    {
        return app('InetStudio\CaptchaPackage\Captcha\Contracts\Services\Front\CaptchaServiceContract');
    }
}
