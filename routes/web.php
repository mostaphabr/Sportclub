<?php

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\AdminDashbordController;
use App\Http\Controllers\MyReservationsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

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
    return redirect()->route('posts.index');
})->name('blog-details');




Route::get('contact', function () {
    return view('SportClub/contact');
})->name('contact');



// Pitches list (public) + booking / cancelling (logged-in, see ReservationController)
Route::resource('reservation', ReservationController::class);


// ---------------------------------------------------------------- Admin only
Route::middleware(['auth', 'admin'])->group(function () {

    // Dashboard (tabs) + pitches management
    Route::resource('AdminDashbord', AdminDashbordController::class);

    // Users management
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::patch('users/{user}/password', [AdminUserController::class, 'resetPassword'])->name('users.password');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });
});
// ------------------------------------------------------------------------------------



Route::controller(MyReservationsController::class)->middleware('auth')->group(function () {
    // Provider
    Route::get('ProviderDashboard', 'ProviderDashboard')->name('ProviderDashboard');
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
