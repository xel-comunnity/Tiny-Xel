<?php 

namespace Tiny\Test\Provider\Composition;
use Tiny\Xel\Context\Context;
use HTMLPurifier;

trait Request
{
    protected const CONTENT_TYPE_JSON = 'application/json';
    protected \Swoole\Http\Request $request;
    protected ?HTMLPurifier $purifier = null;

    public function request(): self
    {
        $this->request =  $this->response = Context::get('request');
        return $this;
    }

    public function all(): array
    {
        return $this->purifyData($this->dataParser());
    }

    protected function dataParser(): array
    {
        $contentType = $this->request->header['content-type'] ?? '';

        if ($contentType !== self::CONTENT_TYPE_JSON) {
            return $this->request->post ?? [];
        }
        return $this->JsonParser();
    }

    protected function JsonParser(): array
    {
        $json = $this->request->rawContent();
        $data = json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Invalid JSON data');
        }
        return $data ?? [];
    }

    protected function purifyData(array $data): array
    {
        $sanitizedData = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitizedData[$key] = $this->getPurifier()->purify($value);
            } elseif (is_array($value)) {
                $sanitizedData[$key] = $this->purifyData($value);
            } else {
                $sanitizedData[$key] = $value;
            }
        }
        return $sanitizedData;
    }

    protected function getPurifier(): HTMLPurifier
    {
        if ($this->purifier === null) {
            $config = \HTMLPurifier_Config::createDefault();
            $this->purifier = new HTMLPurifier($config);
        }
        return $this->purifier;
    }
}