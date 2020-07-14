<?php

declare(strict_types=1);

namespace Gitlab\HttpClient\Message;

use Gitlab\Exception\RuntimeException;
use Gitlab\HttpClient\Util\JsonArray;
use Psr\Http\Message\ResponseInterface;

/**
 * Utilities to parse response headers and content.
 */
final class ResponseMediator
{
    /**
     * The JSON content type identifier.
     *
     * @var string
     */
    public const JSON_CONTENT_TYPE = 'application/json';

    /**
     * The octet stream content type identifier.
     *
     * @var string
     */
    public const STREAM_CONTENT_TYPE = 'application/octet-stream';

    /**
     * The multipart form data content type identifier.
     *
     * @var string
     */
    public const MULTIPART_CONTENT_TYPE = 'multipart/form-data';

    /**
     * Return the response body as a string or JSON array if content type is JSON.
     *
     * @param ResponseInterface $response
     *
     * @return array|string
     */
    public static function getContent(ResponseInterface $response)
    {
        $body = (string) $response->getBody();

        if (0 === strpos($response->getHeaderLine('Content-Type'), self::JSON_CONTENT_TYPE)) {
            return JsonArray::decode($body);
        }

        return $body;
    }

    /**
     * Extract pagination URIs from Link header.
     *
     * @param ResponseInterface $response
     *
     * @return array<string,string>
     */
    public static function getPagination(ResponseInterface $response)
    {
        $header = self::getHeader($response, 'Link');

        if (null === $header) {
            return [];
        }

        $pagination = [];
        foreach (explode(',', $header) as $link) {
            preg_match('/<(.*)>; rel="(.*)"/i', trim($link, ','), $match);

            /** @var string[] $match */
            if (3 === count($match)) {
                $pagination[$match[2]] = $match[1];
            }
        }

        return $pagination;
    }

    /**
     * Get the value for a single header.
     *
     * @param ResponseInterface $response
     * @param string            $name
     *
     * @return string|null
     */
    private static function getHeader(ResponseInterface $response, string $name)
    {
        if (!$response->hasHeader('Link')) {
            return null;
        }

        $headers = $response->getHeader($name);

        return array_shift($headers);
    }

    /**
     * Get the error message from the response if present.
     *
     * @param ResponseInterface $response
     *
     * @return string|null
     */
    public static function getErrorMessage(ResponseInterface $response)
    {
        try {
            $content = self::getContent($response);
        } catch (RuntimeException $e) {
            return null;
        }

        if (!is_array($content)) {
            return null;
        }

        if (isset($content['message'])) {
            $message = $content['message'];

            if (is_string($message)) {
                return $message;
            }

            if (is_array($message)) {
                return self::getMessageAsString($content['message']);
            }
        }

        if (isset($content['error_description'])) {
            $error = $content['error_description'];

            if (is_string($error)) {
                return $error;
            }
        }

        if (isset($content['error'])) {
            $error = $content['error'];

            if (is_string($error)) {
                return $error;
            }
        }

        return null;
    }

    /**
     * @param array $message
     *
     * @return string
     */
    private static function getMessageAsString(array $message)
    {
        $format = '"%s" %s';
        $errors = [];

        foreach ($message as $field => $messages) {
            if (is_array($messages)) {
                $messages = array_unique($messages);
                foreach ($messages as $error) {
                    $errors[] = sprintf($format, $field, $error);
                }
            } elseif (is_int($field)) {
                $errors[] = $messages;
            } else {
                $errors[] = sprintf($format, $field, $messages);
            }
        }

        return implode(', ', $errors);
    }
}
