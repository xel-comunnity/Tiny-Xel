<?php

namespace Tiny\Test\Provider\Composition;

use Tiny\Xel\Context\Context;
use HTMLPurifier;

trait Response {
    protected const CONTENT_TYPE_JSON = 'application/json';
    protected ?\Swoole\Http\Response $response = null;
    protected ?HTMLPurifier $purifier = null;

    public function response(): self
    {

        $this->response = Context::get('response');
        return $this;
    }

    public function Json($data, int $statusCode = 200): void
    {
        $this->response->header('Content-Type', self::CONTENT_TYPE_JSON);
        $this->response->status($statusCode);
        $this->response->end(json_encode($data));
        
        Context::clear();
    }

    public function Plain(string $content, int $statusCode = 200): void
    {
        $this->response->header('Content-Type', 'text/plain');
        $this->response->status($statusCode);
        $this->response->end($content);
    }

    public function Downloadable(string $filePath, string $fileName = null): void
    {
        if (!file_exists($filePath)) {
            $this->response->status(404);
            $this->response->end('File not found');
            return;
        }

        $fileName = $fileName ?? basename($filePath);
        $mimeType = mime_content_type($filePath);

        $this->response->header('Content-Type', $mimeType);
        $this->response->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
        $this->response->sendfile($filePath);
    }

    public function WithCookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): self
    {
        $this->response->cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    public function WithStatic(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $this->response->status(404);
            $this->response->end('File not found');
            return;
        }

        $mimeType = mime_content_type($filePath);
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Explicitly set MIME type for HTML files
        if ($fileExtension === 'html' || $fileExtension === 'htm') {
            $mimeType = 'text/html';
        }

        $this->response->header('Content-Type', $mimeType);

        // Set caching headers for static files
        $this->response->header('Cache-Control', 'public, max-age=31536000');
        $this->response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));

        // Handle range requests for video streaming
        if (strpos($mimeType, 'video/') === 0) {
            $fileSize = filesize($filePath);
            $this->response->header('Accept-Ranges', 'bytes');

            if (isset($_SERVER['HTTP_RANGE'])) {
                $ranges = array_map('intval', explode('-', substr($_SERVER['HTTP_RANGE'], 6)));
                $start = $ranges[0];
                $end = isset($ranges[1]) ? $ranges[1] : $fileSize - 1;

                $this->response->status(206);
                $this->response->header('Content-Range', "bytes $start-$end/$fileSize");
                $this->response->header('Content-Length', $end - $start + 1);
                $this->response->sendfile($filePath, $start, $end - $start + 1);
            } else {
                $this->response->header('Content-Length', $fileSize);
                $this->response->sendfile($filePath);
            }
        } else {
            // For HTML files, we might want to disable caching
            if ($mimeType === 'text/html') {
                $this->response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $this->response->header('Pragma', 'no-cache');
            }
            $this->response->sendfile($filePath);
        }
    }
}