<?php declare(strict_types = 1);

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

use App\Http\Controllers\Export\ExportController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\IndexController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

Route::get('/', [IndexController::class, 'index']);

Route::group(['middleware' => ['auth', 'organization']], static function (Router $router): void {
    $router->post('/download/csv', [ExportController::class, 'csv']);

    $router->post('/download/excel', [ExportController::class, 'excel']);

    $router->post('/download/pdf', [ExportController::class, 'pdf']);

    $router->get('/files/{id}', FilesController::class)->name('files');
});

// This route required to be able to translate 404 page (without it the error
// will be shown before the session start and actual locale will not available).
Route::fallback(static function (): void {
    throw new NotFoundHttpException();
});
