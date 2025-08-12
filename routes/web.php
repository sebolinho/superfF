<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

if (config('settings.language')) {
    App::setLocale(config('settings.language'));
} else { // This is optional as Laravel will automatically set the fallback language if there is none specified
    App::setLocale('pt-br');
}
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
if (config('settings.landing') == 'active') {
    Route::get('/', [\App\Http\Controllers\IndexController::class, 'landing'])->name('landing');
    Route::post('/', [\App\Http\Controllers\IndexController::class, 'search'])->name('landing');
    Route::get('/inicio', [\App\Http\Controllers\IndexController::class, 'index'])->name('index');
} else {
    Route::get('/', [\App\Http\Controllers\IndexController::class, 'index'])->name('index');
}

// Navegar
Route::get(__('navegar'), [App\Http\Controllers\BrowseController::class, 'index'])->name('browse');
Route::post(__('navegar'), [App\Http\Controllers\BrowseController::class, 'index'])->name('browse');
Route::get('/robots.txt', function () {
    // Obtém a URL atual (com protocolo http ou https)
    $siteUrl = (request()->secure() ? 'https' : 'http') . '://' . request()->getHost();

    // Define o conteúdo do robots.txt
    $robotsContent = "User-agent: *
Allow: /
Disallow: /login
Disallow: /atores
Disallow: /colecoes
Disallow: /settings
Disallow: /perfil/
Disallow: /ator
Disallow: /ator/
Disallow: /perfil
Disallow: /pagina/
Disallow: /lang/
Disallow: /solicitar
Disallow: /pais/
Disallow: /forgot-password

Sitemap: {$siteUrl}/sitemap.xml
Sitemap: {$siteUrl}/sitemap_post_1.xml
    ";

    // Retorna o conteúdo com o tipo de resposta como 'text/plain'
    return response($robotsContent)->header('Content-Type', 'text/plain');
});

// Top IMDB
Route::get(__('top-notas'), [App\Http\Controllers\BrowseController::class, 'index'])->name('topimdb');

// Filmes
Route::get(__('filmes'), [App\Http\Controllers\BrowseController::class, 'index'])->name('movies');

// Anime
Route::get(__('animes'), [App\Http\Controllers\BrowseController::class, 'index'])->name('anime');

// Séries de TV
Route::get(__('series'), [App\Http\Controllers\BrowseController::class, 'index'])->name('tvshows');

// Transmissões ao Vivo
Route::get(__('canais-ao-vivo'), [App\Http\Controllers\BrowseController::class, 'broadcasts'])->name('broadcasts');

// Em Alta
Route::get(__('em-alta'), [App\Http\Controllers\BrowseController::class, 'index'])->name('trending');

// Gênero
Route::get(__('categoria') . '/{genre}', [App\Http\Controllers\BrowseController::class, 'index'])->name('genre');

// País
Route::get(__('pais') . '/{country}', [App\Http\Controllers\BrowseController::class, 'index'])->name('country');

// Busca
Route::get(__('buscar') . '/{search}', [App\Http\Controllers\BrowseController::class, 'index'])->name('search');

// Tag
Route::get(__('tag') . '/{tag}', [App\Http\Controllers\BrowseController::class, 'tag'])->name('tag');

// Encontre Agora
Route::get(__('encontre-agora'), [App\Http\Controllers\BrowseController::class, 'find'])->name('browse.find');

// Pessoas
Route::get(__('pessoas'), [App\Http\Controllers\BrowseController::class, 'community'])->name('peoples');

// Solicitar
Route::get(__('solicitar'), [App\Http\Controllers\BrowseController::class, 'request'])->name('request');
Route::post(__('solicitar'), [App\Http\Controllers\BrowseController::class, 'requestPost'])->name('requestPost');


Route::get(__('filme') . '/{slug}', [App\Http\Controllers\WatchController::class, 'movie'])->name('movie');
// Rota para o episódio (mais específica)
Route::get(__('serie') . '/assistir-{slug}-{season}-temporada-{episode}-episodio', [App\Http\Controllers\WatchController::class, 'episode'])
    ->where([
        'slug' => '[a-z0-9\-]+',
        'season' => '[0-9]+',
        'episode' => '[0-9]+',
    ])
    ->name('episode');

// Rota para a série (mais genérica)
Route::get(__('serie') . '/assistir-{slug}', [App\Http\Controllers\WatchController::class, 'tv'])->name('tv');



Route::get(__('canais-ao-vivo') . '/{slug}', [App\Http\Controllers\WatchController::class, 'broadcast'])->name('broadcast');

Route::get(__('embed') . '/{id}', [App\Http\Controllers\WatchController::class, 'embed'])->name('embed')->middleware('hotlink');

// User
Route::get(__('perfil') . '/{username}/liked', [App\Http\Controllers\UserController::class, 'liked'])->name('profile.liked');
Route::get(__('perfil') . '/{username}/watchlist', [App\Http\Controllers\UserController::class, 'watchlist'])->middleware(['auth'])->name('profile.watchlist');
Route::get(__('perfil') . '/{username}/community', [App\Http\Controllers\UserController::class, 'community'])->name('profile.community');
Route::get(__('perfil') . '/{username}/comments', [App\Http\Controllers\UserController::class, 'comments'])->name('profile.comments');
Route::get(__('perfil') . '/{username}/history', [App\Http\Controllers\UserController::class, 'history'])->middleware(['auth'])->name('profile.history');

Route::get(__('perfil') . '/{username}', [App\Http\Controllers\UserController::class, 'index'])->name('profile');
Route::get(__('settings'), [App\Http\Controllers\UserController::class, 'settings'])->middleware(['auth'])->name('settings');
Route::post(__('settings'), [App\Http\Controllers\UserController::class, 'update'])->middleware(['auth', 'demo'])->name('settings.update');
Route::get(__('classificacao'), [App\Http\Controllers\UserController::class, 'leaderboard'])->name('leaderboard');

// Subscription
Route::controller(\App\Http\Controllers\SubscriptionController::class)->middleware(['auth'])->name('subscription.')->group(function () {
    Route::get('subscription', 'index')->name('index');
    Route::get('billing', 'billing')->name('billing');
    Route::get('invoice/{id}', 'invoice')->name('invoice');
    Route::get('payment', 'payment')->name('payment');
    Route::get('payment-pending', 'pending')->name('pending');
    Route::get('payment-cancelled', 'cancelled')->name('cancelled');
    Route::get('payment-completed', 'completed')->name('completed');
    Route::post('payment', 'store');
    Route::post('subscription', 'update')->name('update')->middleware('demo');
    Route::post('billing', 'cancelSubscription')->name('cancelSubscription')->middleware('demo');
});

// Community
Route::get(__('discussions'), [App\Http\Controllers\BrowseController::class, 'discussions'])->name('discussions');
Route::get(__('discussion') . '/{slug}', [App\Http\Controllers\BrowseController::class, 'discussion'])->name('discussion');
Route::post(__('create-discussion'), [App\Http\Controllers\BrowseController::class, 'discussionStore'])->name('discussions.create');
// People
Route::get(__('atores'), [App\Http\Controllers\BrowseController::class, 'peoples'])->name('peoples');
Route::get(__('ator') . '/{slug}', [App\Http\Controllers\BrowseController::class, 'people'])->name('people');

// Collection
Route::get(__('colecoes'), [App\Http\Controllers\BrowseController::class, 'collections'])->name('collections');
Route::get(__('colecao') . '/{slug}', [App\Http\Controllers\BrowseController::class, 'collection'])->name('collection');

// Blog
Route::get(__('blog'), [App\Http\Controllers\ArticleController::class, 'index'])->name('blog');
Route::get(__('artigo') . '/{slug}', [App\Http\Controllers\ArticleController::class, 'show'])->name('article');

// Page
Route::get(__('pagina') . '/{slug}', [App\Http\Controllers\PageController::class, 'show'])->name('page');
Route::get(__('contato'), [App\Http\Controllers\PageController::class, 'contact'])->name('contact');
Route::post(__('contato'), [App\Http\Controllers\PageController::class, 'contactmail'])->name('contact.submit');

// Ajax
Route::prefix('ajax')->name('ajax.')->middleware(['auth'])->group(function () {
    Route::post('reaction', [App\Http\Controllers\AjaxController::class, 'reaction'])->name('reaction');
});

Route::get('lang/{lang}', ['as' => 'lang.switch', 'uses' => 'App\Http\Controllers\AjaxController@switchLang']);

// Sitemap
Route::get('sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('sitemap_main.xml', [App\Http\Controllers\SitemapController::class, 'main'])->name('sitemap.main');
Route::get('sitemap_post_{page}.xml', [App\Http\Controllers\SitemapController::class, 'post'])->name('sitemap.post');
Route::get('sitemap_episode_{page}.xml', [App\Http\Controllers\SitemapController::class, 'episode'])->name('sitemap.episode');
Route::get('sitemap_people_{page}.xml', [App\Http\Controllers\SitemapController::class, 'people'])->name('sitemap.people');
Route::get('sitemap_genre_{page}.xml', [App\Http\Controllers\SitemapController::class, 'genre'])->name('sitemap.genre');

// Webhook routes

Route::post('webhooks/paypal', [\App\Http\Controllers\WebhookController::class, 'paypal'])->name('webhooks.paypal');
Route::post('webhooks/stripe', [\App\Http\Controllers\WebhookController::class, 'stripe'])->name('webhooks.stripe');

// Install
Route::controller(App\Http\Controllers\InstallController::class)->name('install.')->group(function () {
    Route::get('install/index', 'index')->name('index');
    Route::get('install/config', 'config')->name('config');
    Route::get('install/complete', 'complete')->name('complete');
    Route::post('install/config', 'store')->name('store');
});
require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
