<?php

declare(strict_types=1);

namespace Tiny\Xel\Database;

use InvalidArgumentException;
use Tiny\Xel\Database\Contract\DriverContract;
use Tiny\Xel\Database\Driver\EloquentDriver;
use Tiny\Xel\Database\Driver\SwoolePoolDriver;

final class DriverResolver
{
    /**
     * @var array<string, class-string<DriverContract>>
     */
    private static array $drivers = [
        "swoole-pool" => SwoolePoolDriver::class,
        "eloquent" => EloquentDriver::class,
    ];

    /**
     * @return class-string<DriverContract>
     */
    public static function resolve(string $name): string
    {
        return self::$drivers[$name]
            ?? throw new InvalidArgumentException(
                "Unsupported db driver contract [{$name}]. Available: " .
                    implode(", ", array_keys(self::$drivers))
            );
    }

    public static function make(string $name): DriverContract
    {
        $class = self::resolve($name);
        return new $class();
    }
}
