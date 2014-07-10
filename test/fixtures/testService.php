<?php


return array (

    "name" => "TestAPI",
    "baseUrl" => "https://example.com/",
    "description" => "A test service for unit testing ASB",
    "operations" => array(
        "overrideURL" => array(
            "httpMethod" => "GET",
            'uri' => 'https://example.com/overrideURL',
            "summary" => "Check that the uri is overwritten",
        ),
    )
);
