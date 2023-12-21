<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

/**
 * Response represents an HTTP response in JSON format.
 *
 * Note that this class does not force the returned JSON content to be an
 * object. It is however recommended that you do return an object as it
 * protects yourself against XSSI and JSON-JavaScript Hijacking.
 *
 * @see https://www.owasp.org/index.php/OWASP_AJAX_Security_Guidelines#Always_return_JSON_with_an_Object_on_the_outside
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class JsonResponse extends Response
{
    protected $data;
    protected $callback;

    // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be embedded into HTML.
    // 15 === JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    const DEFAULT_ENCODING_OPTIONS = 15;

    protected $encodingOptions = self::DEFAULT_ENCODING_OPTIONS;

    /**
     * @param mixed $data    The response data
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     * @param bool  $json    If the data is already a JSON string
     */
    public function __construct($data = null, $status = 200, $headers = array(), $json = false)
    {
        parent::__construct('', $status, $headers);

        if (null === $data) {
            $data = new \ArrayObject();
        }

        $json ? $this->setJson($data) : $this->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public static function create($data = null, $status = 200, $headers = array())
    {
        return new static($data, $status, $headers);
    }

    /**
     * Sets the JSONP callback.
     *
     * @param string|null $callback The JSONP callback or null to use none
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException When the callback name is not valid
     */
    public function setCallback($callback = null)
    {
        if (null !== $callback) {
            // taken from http://www.geekality.net/2011/08/03/valid-javascript-identifier/
            $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
            $parts = explode('.', $callback);
            foreach ($parts as $part) {
                if (!preg_match($pattern, $part)) {
                    throw new \InvalidArgumentException('The callback name is not valid.');
                }
            }
        }

        $this->callback = $callback;

        return $this->update();
    }

    /**
     * Sets a raw string containing a JSON document to be sent.
     *
     * @param string $json
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     */
    public function setJson($json)
    {
        $this->data = $json;

        return $this->update();
    }

    /**
     * Sets the data to be sent as JSON.
     *
     * @param mixed $data
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     */
    public function setData($data = array())
    {
        if (defined('HHVM_VERSION')) {
            // HHVM does not trigger any warnings and let exceptions
            // thrown from a JsonSerializable object pass through.
            // If only PHP did the same...
            $data = json_encode($data, $this->encodingOptions);
        } else {
            try {
                // PHP 5.4 and up wrap exceptions thrown by JsonSerializable
                // objects in a new exception that needs to be removed.
                // Fortunately, PHP 5.5 and up do not trigger any warning anymore.
                $data = json_encode($data, $this->encodingOptions);
            } catch (\Exception $e) {
                if ('Exception' === get_class($e) && 0 === strpos($e->getMessage(), 'Failed calling ')) {
                    throw $e->getPrevious() ?: $e;
                }
                throw $e;
            }
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        return $this->setJson($data);
    }

    /**
     * Returns options used while encoding data to JSON.
     *
     * @return int
     */
    public function getEncodingOptions()
    {
        return $this->encodingOptions;
    }

    /**
     * Sets options used while encoding data to JSON.
     *
     * @param int $encodingOptions
     *
     * @return JsonResponse
     */
    public function setEncodingOptions($encodingOptions)
    {
        $this->encodingOptions = (int) $encodingOptions;

        return $this->setData(json_decode($this->data));
    }

    /**
     * Updates the content and headers according to the JSON data and callback.
     *
     * @return JsonResponse
     */
    protected function update()
    {
        if (null !== $this->callback) {
            // Not using application/javascript for compatibility reasons with older browsers.
            $this->headers->set('Content-Type', 'text/javascript');

            return $this->setContent(sprintf('/**/%s(%s);', $this->callback, $this->data));
        }

        // Only set the header when there is none or when it equals 'text/javascript' (from a previous update with callback)
        // in order to not overwrite a custom definition.
        if (!$this->headers->has('Content-Type') || 'text/javascript' === $this->headers->get('Content-Type')) {
            $this->headers->set('Content-Type', 'application/json');
        }

        return $this->setContent($this->data);
    }
}
