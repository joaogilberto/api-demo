<?php

use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\Exception\Error406Exception;
use ByJG\RestServer\Exception\Error415Exception;

require_once __DIR__ . '/../vendor/autoload.php';

const JWT_SERVER="example.com";
const JWT_KEY="secrect_key_for_test";

$server = JWT_SERVER;
$secret = new \ByJG\Util\JwtKeySecret(base64_encode(JWT_KEY));
$jwtWrapper = new \ByJG\Util\JwtWrapper($server, $secret);



$routeDefinition = new \ByJG\RestServer\Route\RouteList();


$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/test/get")
    ->withOutputProcessor(\ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class)
    ->withClosure(function ($response, $request) {
        $response->write([
            "name" => "John",
            "lastname" => "Doe",
            "address" => [
                "street" => "Some Street",
                "number" => 10,
                "zip" => "R00 000"
            ]
        ]);
    })
);

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::post("/test/post")
    ->withOutputProcessor(\ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class)
    ->withClosure(function ($response, $request) {
        $response->write([
            "id" => rand(100, 1000)
        ]);
    })
);

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::post("/test/auth")
    ->withOutputProcessor(\ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class)
    ->withClosure(function ($response, $request) use ($jwtWrapper) {

        $data = json_decode($request->payload(), true);

        if ($data["user"] == "test" && $data["password"] == "test") {
            $token = $jwtWrapper->createJwtData([
                "key" => "value",
                "key2" => "value2"
            ], 1200);

            $response->write([
                "token" => $jwtWrapper->generateToken($token)
            ]);
        } else {
            throw new Error403Exception('Invalid username and password. Try {"user":"test","password":"test"}');
        }
    })
);

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/test/error")
    ->withOutputProcessor(\ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class)
    ->withClosure(function ($response, $request) {
        throw new Error406Exception("Value not valid");
    })
);

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/private")
    ->withOutputProcessor(\ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class)
    ->withClosure(function ($response, $request) {
        $response->write([
            "data" => "you were able to access this"
        ]);
    })
);

$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer
    ->withMiddleware(new \ByJG\RestServer\Middleware\JwtMiddleware($jwtWrapper, ["/test/"]));

$restServer->handle($routeDefinition);