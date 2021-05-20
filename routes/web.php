<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Cache;

$router->get('/', function () use ($router) {
//    dd(Cache::has('AQDBuP_yy7VKC-BMskOFlErmjzeQG7S_D5YaF-Dzt7EEKsWRbWgUx93IhNmTpil_Ay-N6zTEcXTVOyHuU1M-Uk7GPBh2j7sC2rBcXYgLnCXcwrlFYkf6bcWaXcSYHgI4csQ'));
    return view('login');
});

$router->post('/authorize', 'AuthorizeController@index');
$router->group(['as' => 'callback'], function ($router) {
    $router->get('/callback', 'AuthorizeController@callback');

});
