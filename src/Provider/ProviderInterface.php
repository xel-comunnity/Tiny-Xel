<?php

namespace Tiny\Xel\Provider;

interface ProviderInterface{

    public function provide():array;
    public function config():array;
}
