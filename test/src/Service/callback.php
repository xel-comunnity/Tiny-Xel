<?php 

use Tiny\Xel\Context\RequestContext;


function home()
{
    RequestContext::json(["hello world", 200]);
}

function about()
{
    RequestContext::json(["hello view", 200]);
}