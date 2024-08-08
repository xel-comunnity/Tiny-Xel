<?php 
namespace Tiny\Xel\Context;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Coroutine;

class RequestContext{
    protected static $pool = [];

    public static function setRequest(Request $request)
    {
        self::put('request', $request);
    }

    public static function getRequest(): ?Request
    {
        return self::get('request');
    }

    public static function setResponse(Response $response)
    {
        self::put('response', $response);
    }

    public static function getResponse(): ?Response
    {
        return self::get('response');
    }

    protected static function get($key)
    {
        $cid = Coroutine::getuid();
        if ($cid < 0) {
            return null;
        }
        return self::$pool[$cid][$key] ?? null;
    }

    protected static function put($key, $item)
    {
        $cid = Coroutine::getuid();
        if ($cid > 0) {
            self::$pool[$cid][$key] = $item;
        }
    }

    public static function clear()
    {
        $cid = Coroutine::getuid();
        if ($cid > 0) {
            unset(self::$pool[$cid]);
        }
    }


    public static function json($data, $statusCode = 200)
    {
        $response = self::getResponse();
        if ($response) {
            $response->header('Content-Type', 'application/json');
            $response->status($statusCode);
            $response->end(json_encode($data));
        }
    }

    public static function text($content, $statusCode = 200)
    {
        $response = self::getResponse();
        if ($response) {
            $response->header('Content-Type', 'text/plain');
            $response->status($statusCode);
            $response->end($content);
        }
    }

    public static function download($filePath, $fileName = null)
    {
        $response = self::getResponse();
        if ($response && file_exists($filePath)) {
            $fileName = $fileName ?: basename($filePath);
            $response->header('Content-Type', 'application/octet-stream');
            $response->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            $response->sendfile($filePath);
        }
    }

    public static function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = true)
    {
        $response = self::getResponse();
        if ($response) {
            $response->cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        }
    }


    public static function getQueryParams(): array
    {
        $request = self::getRequest();
        return $request ? $request->get : []; // Return all query parameters or an empty array if not available
    }


}