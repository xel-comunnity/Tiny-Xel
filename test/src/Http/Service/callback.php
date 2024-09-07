<?php

use Tiny\Xel\Context\RequestContext;

function home(): void
{
    RequestContext::json(["hello world", 200]);
}

function about(): void
{
    RequestContext::json(["hello view", 200]);
}
