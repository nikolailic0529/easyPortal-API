<?php declare(strict_types = 1);

use App\Http\Controllers\AuthController;
use Auth0\Login\Auth0Controller;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([
    'middleware' => 'guest',
    'prefix'     => 'auth',
], static function (Router $router): void {
    $router->post('signup', [AuthController::class, 'signup']);

    $router->get('signin', [AuthController::class, 'signin']);

    // FIXME [auth0] This should be processed by AuthController::class
    $router->get('callback', [Auth0Controller::class, 'callback']);
});

Route::group([
    'middleware' => 'auth',
    'prefix'     => 'auth',
], static function (Router $router): void {
    $router->get('signout', [AuthController::class, 'signout']);
});

Route::group([
    'prefix' => 'auth',
], static function (Router $router): void {
    $router->get('info', [AuthController::class, 'info']);
});
