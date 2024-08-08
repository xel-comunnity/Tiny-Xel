<?php 

// ? router provider
$router = require __DIR__ ."/../src/Router/router.php";

return [
    "router"=> [
        "dispatcher" => $router ,  
        "middleware" => [ /** for future cutomisation of middleware handler */]
    ],

    // "router" => $router,

    "scheduler"=> [ /** For Cron Based Handler */  ],
    
    "websocket" =>[ /**Put Socket Handler (available soon) */ ],    

    "custom"=> [
        "custom-logic" => [ /** Register to provider for custom library from external or your own code */ ] ,
    ]
];