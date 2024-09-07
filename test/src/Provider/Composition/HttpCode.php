<?php 
namespace  Tiny\Test\Provider\Composition;

class HttpCode
{
    /**
     * @var int OK - The request has succeeded.
     * Example: Use when a GET request successfully retrieves a resource.
     * CRUD: Successful READ operation.
     */
    public const OK = 200;

    /**
     * @var int Created - The request has been fulfilled and resulted in a new resource being created.
     * Example: Use when a POST request successfully creates a new resource.
     * CRUD: Successful CREATE operation.
     */
    public const CREATED = 201;

    /**
     * @var int Accepted - The request has been accepted for processing, but the processing has not been completed.
     * Example: Use for asynchronous operations, when the request will be processed later.
     * CRUD: Accepted but not yet processed CREATE or UPDATE operation.
     */
    public const ACCEPTED = 202;

    /**
     * @var int No Content - The server has fulfilled the request but does not need to return an entity-body.
     * Example: Use when a DELETE request is successful but no content needs to be returned.
     * CRUD: Successful DELETE operation with no response body.
     */
    public const NO_CONTENT = 204;

    /**
     * @var int Moved Permanently - The requested resource has been assigned a new permanent URI.
     * Example: Use when a resource has been permanently moved to a new URL.
     * Middleware: URL rewriting or permanent redirects.
     */
    public const MOVED_PERMANENTLY = 301;

    /**
     * @var int Found - The requested resource resides temporarily under a different URI.
     * Example: Use for temporary redirects, such as after a successful form submission.
     * Middleware: Temporary redirects after processing.
     */
    public const FOUND = 302;

    /**
     * @var int Bad Request - The request could not be understood by the server due to malformed syntax.
     * Example: Use when the client sends invalid data, like malformed JSON.
     * CRUD: Invalid CREATE or UPDATE request data.
     * Middleware: Request validation failure.
     */
    public const BAD_REQUEST = 400;

    /**
     * @var int Unauthorized - The request requires user authentication.
     * Example: Use when a user tries to access a protected resource without logging in.
     * Middleware: Authentication middleware detects unauthenticated request.
     */
    public const UNAUTHORIZED = 401;

    /**
     * @var int Forbidden - The server understood the request but is refusing to fulfill it.
     * Example: Use when a user doesn't have permission to access a resource, even after authenticating.
     * Middleware: Authorization middleware denies access to a resource.
     */
    public const FORBIDDEN = 403;

    /**
     * @var int Not Found - The server has not found anything matching the Request-URI.
     * Example: Use when a requested resource doesn't exist.
     * CRUD: READ, UPDATE, or DELETE operation on a non-existent resource.
     */
    public const NOT_FOUND = 404;

    /**
     * @var int Method Not Allowed - The method specified in the Request-Line is not allowed for the resource identified by the Request-URI.
     * Example: Use when a POST request is sent to a read-only resource that only accepts GET.
     * CRUD: Attempting an unsupported operation on a resource.
     */
    public const METHOD_NOT_ALLOWED = 405;

    /**
     * @var int Internal Server Error - The server encountered an unexpected condition which prevented it from fulfilling the request.
     * Example: Use when an unhandled exception occurs in your application.
     * CRUD: Unexpected error during any operation.
     * Middleware: Unhandled exceptions in the application pipeline.
     */
    public const INTERNAL_SERVER_ERROR = 500;

    /**
     * @var int Not Implemented - The server does not support the functionality required to fulfill the request.
     * Example: Use when the server doesn't support a specific HTTP method used in the request.
     * CRUD: Attempting an operation that's not implemented yet.
     */
    public const NOT_IMPLEMENTED = 501;

    /**
     * @var int Bad Gateway - The server, while acting as a gateway or proxy, received an invalid response from the upstream server it accessed in attempting to fulfill the request.
     * Example: Use in a microservices architecture when a downstream service returns an invalid response.
     * Middleware: API Gateway receives an invalid response from a backend service.
     */
    public const BAD_GATEWAY = 502;

    /**
     * @var int Service Unavailable - The server is currently unable to handle the request due to a temporary overloading or maintenance of the server.
     * Example: Use during planned maintenance or when the server is overloaded.
     * Middleware: Rate limiting middleware detects too many requests.
     */
    public const SERVICE_UNAVAILABLE = 503;

    /**
     * @var int Partial Content - The server has fulfilled the partial GET request for the resource.
     * Example: Use when responding to range requests, typically for video streaming or large file downloads.
     * CRUD: Partial READ operation, such as pagination or chunked file transfer.
     */
    public const PARTIAL_CONTENT = 206;
}