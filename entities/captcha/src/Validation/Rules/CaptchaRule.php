<?php

namespace InetStudio\CaptchaPackage\Captcha\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class CaptchaRule.
 */
class CaptchaRule implements Rule
{
    /** @var  array */
    protected $skipIps = [];

    /**
     * CaptchaRule constructor.
     */
    public function __construct()
    {
        $this->skipIps(
            config()->get('captcha.skip-ips', [])
        );
    }

    /**
     * Set the ips to skip.
     *
     * @param  string|array  $ip
     *
     * @return $this
     */
    public function skipIps($ip)
    {
        $this->skipIps = Arr::wrap($ip);

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $ip = request()->ip();

        if (in_array($ip, $this->skipIps)) {
            return true;
        }

        return no_captcha()->verifyRequest($value, $ip);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return (string) trans('validation.captcha');
    }
}
