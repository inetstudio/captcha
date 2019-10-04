<?php

namespace InetStudio\CaptchaPackage\Captcha\Services\Front;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;
use InetStudio\CaptchaPackage\Captcha\Contracts\Services\Front\CaptchaServiceContract;

/**
 * Class CaptchaService.
 */
class CaptchaService implements CaptchaServiceContract
{
    const CLIENT_API = 'https://www.google.com/recaptcha/api.js';
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * The recaptcha secret key.
     *
     * @var string
     */
    protected $secret;

    /**
     * The recaptcha sitekey key.
     *
     * @var string
     */
    protected $sitekey;

    /**
     * @var Client
     */
    protected $http;

    /**
     * The cached verified responses.
     *
     * @var array
     */
    protected $verifiedResponses = [];

    /**
     * CaptchaService.
     */
    public function __construct()
    {
        $config = config('captcha', []);

        foreach ($config ?? [] as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Render HTML captcha.
     *
     * @param  array  $attributes
     *
     * @return string
     */
    public function display(array $attributes = []): string
    {
        $attributes = $this->prepareAttributes($attributes);

        return '<div'.$this->buildAttributes($attributes).'></div>';
    }

    /**
     * Display a Invisible reCAPTCHA by embedding a callback into a form submit button.
     *
     * @param  string  $formIdentifier  the html ID of the form that should be submitted.
     * @param  string  $text  the text inside the form button
     * @param  array  $attributes  array of additional html elements
     *
     * @return string
     */
    public function displaySubmit(string $formIdentifier, string $text = 'submit', array $attributes = []): string
    {
        $javascript = '';

        if (! isset($attributes['data-callback'])) {
            $functionName = 'onSubmit'.str_replace(['-', '=', '\'', '"', '<', '>', '`'], '', $formIdentifier);
            $attributes['data-callback'] = $functionName;

            $javascript = sprintf(
                '<script>function %s(){document.getElementById("%s").submit();}</script>',
                $functionName,
                $formIdentifier
            );
        }

        $attributes = $this->prepareAttributes($attributes);

        $button = sprintf('<button%s><span>%s</span></button>', $this->buildAttributes($attributes), $text);

        return $button.$javascript;
    }

    /**
     * Render js source
     *
     * @param  null  $lang
     * @param  bool  $callback
     * @param  string  $onLoadClass
     *
     * @return string
     */
    public function script($lang = null, bool $callback = false, string $onLoadClass = 'onloadCallBack'): string
    {
        return '<script src="'.$this->getJsLink($lang, $callback, $onLoadClass).'" async defer></script>'."\n";
    }

    /**
     * Verify no-captcha response.
     *
     * @param $response
     * @param  null  $clientIp
     *
     * @return bool
     *
     * @throws GuzzleException
     */
    public function verifyResponse($response, $clientIp = null): bool
    {
        if (empty($response)) {
            return false;
        }

        // Return true if response already verfied before.
        if (in_array($response, $this->verifiedResponses)) {
            return true;
        }

        $verifyResponse = $this->sendRequestVerify([
            'secret' => $this->secret,
            'response' => $response,
            'remoteip' => $clientIp,
        ]);

        if (isset($verifyResponse['success']) && $verifyResponse['success'] === true) {
            // A response can only be verified once from google, so we need to
            // cache it to make it work in case we want to verify it multiple times.
            $this->verifiedResponses[] = $response;
            
            return true;
        } else {
            return false;
        }
    }

    /**
     * Verify no-captcha response by Symfony Request.
     *
     * @param  Request  $request
     *
     * @return bool
     *
     * @throws GuzzleException
     */
    public function verifyRequest(Request $request): bool
    {
        return $this->verifyResponse(
            $request->get('g-recaptcha-response'),
            $request->getClientIp()
        );
    }

    /**
     * Get recaptcha js link.
     *
     * @param  string  $lang
     * @param  bool  $callback
     * @param  string  $onLoadClass
     *
     * @return string
     */
    public function getJsLink($lang = null, bool $callback = false, string $onLoadClass = 'onloadCallBack'): string
    {
        $client_api = static::CLIENT_API;
        $params = [];

        $callback ? $this->setCallBackParams($params, $onLoadClass) : false;
        $lang ? $params['hl'] = $lang : null;

        return $client_api.'?'.http_build_query($params);
    }

    /**
     * @param $params
     * @param $onLoadClass
     */
    protected function setCallBackParams(&$params, $onLoadClass)
    {
        $params['render'] = 'explicit';
        $params['onload'] = $onLoadClass;
    }

    /**
     * Send verify request.
     *
     * @param  array  $query
     *
     * @return array|null
     *
     * @throws GuzzleException
     */
    protected function sendRequestVerify(array $query = []): ?array
    {
        $response = $this->http->request('POST', static::VERIFY_URL, [
            'form_params' => $query,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Prepare HTML attributes and assure that the correct classes and attributes for captcha are inserted.
     *
     * @param  array  $attributes
     *
     * @return array
     */
    protected function prepareAttributes(array $attributes): array
    {
        $attributes['data-sitekey'] = $this->sitekey;

        if (! isset($attributes['class'])) {
            $attributes['class'] = '';
        }

        $attributes['class'] = trim('g-recaptcha '.$attributes['class']);

        return $attributes;
    }

    /**
     * Build HTML attributes.
     *
     * @param  array  $attributes
     *
     * @return string
     */
    protected function buildAttributes(array $attributes): string
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            $html[] = $key.'="'.$value.'"';
        }

        return count($html) ? ' '.implode(' ', $html) : '';
    }
}
