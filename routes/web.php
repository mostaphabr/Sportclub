<?php

use App\Models\Post;
use App\Models\User;
use App\Models\Pitche;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\AdminDashbordController;
use App\Http\Controllers\MyReservationsController;
use GuzzleHttp\Middleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {

    $posts = Post::inRandomOrder()->take(3)->get();
    return view('SportClub/index', compact('posts'));
})->name('home');
Route::redirect('home', '/');

Auth::routes();



Route::get('blog-details', function () {
    return view('SportClub/blog-details');
})->name('blog-details');




Route::get('contact', function () {
    return view('SportClub/contact');
})->name('contact');



Route::resource('reservation', ReservationController::class);


Route::resource('AdminDashbord', AdminDashbordController::class)->middleware('auth');



Route::get('ProviderDashboard', [MyReservationsController::class, 'ProviderDashboard'])->name('ProviderDashboard');
// ------------------------------------------------------------------------------------

Route::controller(MyReservationsController::class)->middleware('auth')->group(function () {
    Route::get('MyTerrainReservations/{id}',  'MyTerrainReservations')->name('MyTerrainReservations');

    // ------------------------------------------------------------------------------------
    Route::get('MyReservations',  'Myreservation')->name('MyReservations');
});

Route::middleware(['auth'])->group(function(){
Route::get('/profile/{id}', [ProfileController::class, 'index'])->name('profile');
Route::put('/store', [ProfileController::class, 'store']);

Route::resource('posts', PostController::class);
Route::resource('comment', CommentController::class);

});