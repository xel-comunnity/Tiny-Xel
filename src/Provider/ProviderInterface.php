<?php

namespace Tiny\Xel\Provider;

interface ProviderInterface
{
    /**
     *@return array<int, mixed>
     */
    public function provide(): array;

    /**
     *@return array<int, mixed>
     */
    public function config(): array;

    /**
     *@return void
     */
    public function boot(): void;
}
