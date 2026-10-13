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
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\ReviewController;

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




Route::get('contact', [ContactController::class, 'show'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');



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

        // Contact page messages
        Route::patch('messages/{message}/read', [AdminMessageController::class, 'markRead'])->name('messages.read');
        Route::delete('messages/{message}', [AdminMessageController::class, 'destroy'])->name('messages.destroy');
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
Route::get('/profile/{user}', [ProfileController::class, 'index'])->name('profile');
Route::put('/store', [ProfileController::class, 'store'])->name('profile.update');

Route::resource('posts', PostController::class);
Route::resource('comment', CommentController::class);

// Find teammates
Route::get('matches', [MatchController::class, 'index'])->name('matches.index');
Route::post('matches/{reservation}/join', [MatchController::class, 'store'])->name('matches.join');
Route::patch('match-requests/{matchRequest}/accept', [MatchController::class, 'accept'])->name('match-requests.accept');
Route::patch('match-requests/{matchRequest}/decline', [MatchController::class, 'decline'])->name('match-requests.decline');
Route::delete('match-requests/{matchRequest}', [MatchController::class, 'destroy'])->name('match-requests.destroy');
Route::patch('reservation/{reservation}/team', [ReservationController::class, 'updateTeam'])->name('reservation.team');

// Ratings & reviews
Route::post('terrains/{pitche}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

});
