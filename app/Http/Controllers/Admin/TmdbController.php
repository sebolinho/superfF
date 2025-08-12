<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostEpisode;
use App\Models\PostSeason;
use App\Models\PostVideo;
use App\Models\Tag;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Traits\PostTrait;

class TmdbController extends Controller
{
    use PostTrait;

    public function show(Request $request)
    {
        $config = [
            'title' => __('Tool'),
            'nav' => 'tool',
        ];
        if (!config('settings.tmdb_api') || !config('settings.tmdb_language')) {
            return redirect()->route('admin.tmdb.settings');
        }
        return view('admin.tmdb.show', compact('config', 'request'));
    }

    public function fetch(Request $request)
    {
        // Validação dos parâmetros de entrada
        $request->validate([
            'type' => 'required|in:movie,tv',
            'q' => 'nullable|string',
            'sortable' => 'nullable|string',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($request->isMethod('post')) {
            return redirect()->route('admin.tmdb.fetch', [
                'type' => $request->input('type'),
                'q' => $request->input('q'),
                'sortable' => $request->input('sortable'),
                'page' => $request->input('page', 1)
            ]);
        }

        $config = [
            'title' => __('Tool'),
            'nav' => 'tool',
        ];

        $listings = [];
        $result = [];

        if ($request->has('type')) {
            $q = $request->input('q');
            $sortable = $request->input('sortable');
            $page = $request->input('page', 1);

            if ($q) {
                if (preg_match('/^tt\d+$/', $q)) {
                    // Tratando 'q' como IMDb ID
                    $apiUrl = 'https://api.themoviedb.org/3/find/' . $q;
                    $apiParams = [
                        'api_key' => config('settings.tmdb_api'),
                        'language' => config('settings.tmdb_language'),
                        'external_source' => 'imdb_id'
                    ];
                    $response = Http::get($apiUrl, $apiParams);

                    if ($response->successful()) {
                        $apiResult = json_decode($response->getBody(), true);

                        if ($request->type == 'movie') {
                            $results = $apiResult['movie_results'] ?? [];
                        } elseif ($request->type == 'tv') {
                            $results = $apiResult['tv_results'] ?? [];
                        } else {
                            $results = [];
                        }
                    } else {
                        // Em caso de erro na chamada à API
                        $results = [];
                    }
                } elseif (is_numeric($q)) {
                    // Tratando 'q' como TMDB ID
                    $apiUrl = 'https://api.themoviedb.org/3/' . $request->type . '/' . $q;
                    $apiParams = [
                        'api_key' => config('settings.tmdb_api'),
                        'language' => config('settings.tmdb_language'),
                    ];
                    $response = Http::get($apiUrl, $apiParams);

                    if ($response->successful()) {
                        $item = json_decode($response->getBody(), true);
                        $results = [$item];
                    } else {
                        // Em caso de erro na chamada à API
                        $results = [];
                    }
                } else {
                    // Tratando 'q' como uma consulta de pesquisa por nome
                    $apiUrl = 'https://api.themoviedb.org/3/search/' . $request->type;
                    $apiParams = [
                        'query' => $q,
                        'api_key' => config('settings.tmdb_api'),
                        'language' => config('settings.tmdb_language'),
                        'page' => $page
                    ];
                    $response = Http::get($apiUrl, $apiParams);
                    $apiResult = json_decode($response->getBody(), true);
                    $results = $apiResult['results'] ?? [];
                }
            } elseif ($sortable) {
                // Lógica para ordenação usando o endpoint 'discover'
                $apiUrl = 'https://api.themoviedb.org/3/discover/' . $request->type;
                $apiParams = [
                    'sort_by' => $sortable,
                    'api_key' => config('settings.tmdb_api'),
                    'language' => config('settings.tmdb_language'),
                    'page' => $page
                ];
                $response = Http::get($apiUrl, $apiParams);
                $apiResult = json_decode($response->getBody(), true);
                $results = $apiResult['results'] ?? [];
            } else {
                $results = [];
            }

            // Processamento dos resultados
            if (!empty($results)) {
                foreach ($results as $item) {
                    if (isset($item['poster_path'])) {
                        // Verifica se o item já existe no banco de dados
                        $check = Post::where('tmdb_id', $item['id'])->first();
                        if (empty($check)) {
                            // Adiciona à lista de listings para processamento adicional
                            $listings[] = $this->tmdbFetchTrait($item, $request->type);
                        }
                    }
                }
            }

            // Ajuste da variável 'result' para a view
            if (preg_match('/^tt\d+$/', $q) && isset($apiResult)) {
                // Se for um IMDb ID, definimos 'result' como os resultados obtidos
                $result = $results;
            } elseif (is_numeric($q) && isset($item)) {
                // Se for um TMDB ID, definimos 'result' como o item único
                $result = $item;
            } else {
                // Para buscas por nome, 'result' já está definido a partir da resposta da API
                $result = $apiResult['results'] ?? $result;
            }
        }

        return view('admin.tmdb.show', compact('config', 'request', 'listings', 'result'));
    }

    public function settings(Request $request)
    {
        $config = [
            'title' => __('Tool'),
            'nav' => 'tool',
        ];
        return view('admin.tmdb.settings', compact('config', 'request'));
    }

    public function update(Request $request)
    {
        $save_data = [
            'tmdb_api',
            'tmdb_language',
            'tmdb_people_limit',
            'tmdb_image',
            'draft_post',
            'import_season',
            'import_episode',
            'vidsrc',
        ];
        foreach ($save_data as $item) {
            update_settings($item, $request->$item);
        }
        Cache::forget('settings');
        Cache::flush();

        return redirect()->route('admin.tmdb.settings')->with('success', __(':title has been updated', ['title' => 'Tool']));
    }

    public function store(Request $request)
    {
        // Obtém os dados da API do TMDb
        $postArray = $this->tmdbApiTrait($request->type, $request->tmdb_id);

        // Remove as tags da API e adiciona tags personalizadas
        if (isset($postArray['tags'])) {
            unset($postArray['tags']);
        }

        // Adiciona tags personalizadas baseadas no título e outros campos
        $tags = [];
        if (isset($postArray['title']) && !empty($postArray['title'])) {
            $tags[] = 'assistir ' . $postArray['title'];
            $tags[] = 'onde assistir ' . $postArray['title'];
            $tags[] = 'assistir online' . $postArray['title'];
            $tags[] = $postArray['title'] . ' online';
        }

        if (isset($postArray['title_sub']) && !empty($postArray['title_sub'])) {
            $tags[] = 'Ver ' . $postArray['title_sub'];
            $tags[] = 'Ver ' . $postArray['title_sub'] . ' online';
        }

        if (isset($postArray['release_date']) && !empty($postArray['release_date'])) {
            $year = date('Y', strtotime($postArray['release_date']));
            $tags[] = 'assistir ' . $postArray['title'] . ' ' . $year;
        }

        $postArray['tags'] = $tags;

        // Remover prefixos dos campos
        // if (isset($postArray['title']) && !empty($postArray['title'])) {
        //     $postArray['title'] = 'Assistir ' . $postArray['title'];
        // }

        // if (isset($postArray['title_sub']) && !empty($postArray['title_sub'])) {
        //     $postArray['title_sub'] = 'Ver ' . $postArray['title_sub'] . ' online';
        // }

        // Verifica se já existe uma série com o mesmo tmdb_id e tipo 'tv'
        $tmdb_id = $postArray['tmdb_id'];
        $existingPost = Post::where('tmdb_id', $tmdb_id)->where('type', 'tv')->first();

        // Flag para determinar se será uma atualização
        $isUpdate = false;

        if ($existingPost) {
            $isUpdate = true;
            // Inicia uma transação para garantir a integridade dos dados
            \DB::transaction(function () use ($existingPost) {
                // Exclui todas as relações associadas aos episódios
                foreach ($existingPost->episodes as $episode) {
                    $episode->reactions()->delete();
                    $episode->watchlist()->delete();
                    $episode->comments()->delete();
                    $episode->logs()->delete();
                    $episode->report()->delete();
                    $episode->videos()->delete();
                    $episode->subtitles()->delete();
                    $episode->delete();
                }

                // Exclui todas as temporadas associadas
                foreach ($existingPost->seasons as $season) {
                    $season->episodes()->delete(); // Exclui episódios associados à temporada
                    $season->delete();
                }

                // Desassocia gêneros, tags e pessoas
                $existingPost->genres()->detach();
                $existingPost->tags()->detach();
                $existingPost->peoples()->detach();

                // Exclui a série existente
                $existingPost->delete();
            });
        }

        // Cria uma nova instância de Post
        $model = new Post();

        $folderDate = date('m-Y') . '/';

        if (config('settings.tmdb_image') != 'active') {
            // Imagem
            if (isset($postArray['image'])) {
                $imagename = Str::random(10);
                $imageFile = $postArray['image'];
                $uploaded_image = fileUpload($imageFile, config('attr.poster.path') . $folderDate, config('attr.poster.size_x'), config('attr.poster.size_y'), $imagename);
                fileUpload($imageFile, config('attr.poster.path') . $folderDate, config('attr.poster.size_x'), config('attr.poster.size_y'), $imagename, 'webp');
                $model->image = $uploaded_image;
            }

            // Capa
            if (isset($postArray['cover'])) {
                $imagename = Str::random(10);
                $coverFile = $postArray['cover'];
                $uploaded_cover = fileUpload($coverFile, config('attr.poster.path') . $folderDate, config('attr.poster.cover_size_x'), config('attr.poster.cover_size_y'), 'cover-' . $imagename);
                fileUpload($coverFile, config('attr.poster.path') . $folderDate, config('attr.poster.cover_size_x'), config('attr.poster.cover_size_y'), 'cover-' . $imagename, 'webp');
                $model->cover = $uploaded_cover;
            }

            // Slide
            if (isset($postArray['slide'])) {
                $imagename = Str::random(10);
                $slideFile = $postArray['slide'];
                $uploaded_slide = fileUpload($slideFile, config('attr.poster.path') . $folderDate, config('attr.poster.slide_x'), config('attr.poster.slide_y'), 'slide-' . $imagename);
                fileUpload($slideFile, config('attr.poster.path') . $folderDate, config('attr.poster.slide_x'), config('attr.poster.slide_y'), 'slide-' . $imagename, 'webp');
                $model->slide = $uploaded_slide;
            }

            // Story
            if (isset($postArray['story'])) {
                $imagename = Str::random(10);
                $storyFile = $postArray['story'];
                $uploaded_story = fileUpload($storyFile, config('attr.poster.path') . $folderDate, config('attr.poster.story_x'), config('attr.poster.story_y'), 'story-' . $imagename);
                fileUpload($storyFile, config('attr.poster.path') . $folderDate, config('attr.poster.story_x'), config('attr.poster.story_y'), 'story-' . $imagename, 'webp');
                $model->story = $uploaded_story;
            }
        }

        // Preenche os campos da série
        $model->type = $postArray['type'];
        $model->title = $postArray['title'];
        $model->title_sub = $postArray['title_sub'];
        #$model->slug = 'assistir-' . SlugService::createSlug(Post::class, 'slug', $postArray['title']);
        $model->tagline = $postArray['tagline'];
        $model->overview = $postArray['overview'];
        $model->release_date = $postArray['release_date'];
        $model->runtime = $postArray['runtime'];
        $model->vote_average = $postArray['vote_average'];
        $model->country_id = $postArray['country_id'];
        $model->trailer = $postArray['trailer'];
        $model->tmdb_image = $postArray['tmdb_image'];
        $model->imdb_id = $postArray['imdb_id'];
        $model->tmdb_id = $tmdb_id;
        $model->meta_title = $request->input('meta_title');
        $model->meta_description = $request->input('meta_description');
        $model->status = config('settings.draft_post') == 'active' ? 'draft' : 'publish';
        $model->save();

        // Sincroniza os Gêneros
        if (isset($postArray['genres'])) {
            $syncCategories = [];
            foreach ($postArray['genres'] as $genre) {
                $syncCategories[] = $genre['current_id'];
            }
            $model->genres()->sync($syncCategories);
        }

        // Sincroniza as Tags
        if (isset($postArray['tags'])) {
            $tagArray = [];
            foreach ($postArray['tags'] as $tag) {
                if ($tag) {
                    $tagComponent = Tag::where('type', 'post')->firstOrCreate(['tag' => $tag, 'type' => 'post']);
                    $tagArray[$tagComponent->id] = ['post_id' => $model->id, 'tagged_id' => $tagComponent->id];
                }
            }
            $model->tags()->sync($tagArray);
        }

        // Sincroniza as Pessoas
        if (isset($postArray['peoples'])) {
            $syncPeople = [];
            foreach ($postArray['peoples'] as $people) {
                $traitPeople = $this->PeopleTmdb($people);
                if (!empty($traitPeople->id)) {
                    $syncPeople[] = $traitPeople->id;
                }
            }
            $model->peoples()->sync($syncPeople);
        }

        // Adiciona Temporadas e Episódios
        if (isset($postArray['seasons'])) {
            foreach ($postArray['seasons'] as $seasonData) {
                if ($seasonData['season_number']) {
                    $season = new PostSeason();
                    $season->name = $seasonData['name'];
                    $season->season_number = $seasonData['season_number'];
                    $model->seasons()->save($season);

                    $episodes = json_decode($seasonData['episode'], true);
                    foreach ($episodes as $episodeKey) {
                        $episode = new PostEpisode();
                        if (config('settings.tmdb_image') != 'active') {
                            if (isset($episodeKey['image'])) {
                                $imagename = Str::random(10);
                                $imageFile = $episodeKey['image'];
                                $uploaded_image = fileUpload($imageFile, config('attr.poster.episode_path') . $folderDate, config('attr.poster.episode_size_x'), config('attr.poster.episode_size_y'), $imagename);
                                fileUpload($imageFile, config('attr.poster.episode_path') . $folderDate, config('attr.poster.episode_size_x'), config('attr.poster.episode_size_y'), $imagename, 'webp');
                                $episode->image = $uploaded_image;
                            }
                        }
                        $episode->post_id = $model->id;
                        $episode->name = $episodeKey['name'];
                        $episode->season_number = $season->season_number;
                        $episode->episode_number = $episodeKey['episode_number'];
                        $episode->overview = $episodeKey['overview'];
                        $episode->tmdb_image = $episodeKey['tmdb_image'];
                        $episode->runtime = $episodeKey['runtime'] ?? null;
                        $episode->status = config('settings.draft_post') == 'active' ? 'draft' : 'publish';
                        $season->episodes()->save($episode);
                    }
                }
            }
        }

        // Determina a mensagem de sucesso com base na operação realizada
        if ($isUpdate) {
            $message = __(':title updated', ['title' => $postArray['title']]);
        } else {
            $message = __(':title created', ['title' => $postArray['title']]);
        }

        return redirect()->route('admin.tv.show', $model->id)->with('success', $message);
    }

    public function tmdbSingleFetch(Request $request)
    {
        $postArray = $this->tmdbApiTrait($request->type, $request->tmdb_id);

        // Remover as tags da API
        if (isset($postArray['tags'])) {
            unset($postArray['tags']);
        }

        // Inicializar um array para as tags personalizadas
        $tags = [];

        // Adicionar tags personalizadas baseadas no título
        if (isset($postArray['title']) && !empty($postArray['title'])) {
            $tags[] = 'assistir ' . $postArray['title'];
            $tags[] = 'onde assistir ' . $postArray['title'];
            $tags[] = $postArray['title'] . ' online';
            $tags[] = $postArray['title'] . ' completo dublado';
            $tags[] = $postArray['title'] . ' grátis';
            $tags[] = 'filme completo ' . $postArray['title'];
        }

        // Adicionar tags personalizadas baseadas no título alternativo
        if (isset($postArray['title_sub']) && !empty($postArray['title_sub'])) {
            $tags[] = 'Ver ' . $postArray['title_sub'];
            $tags[] = 'Ver ' . $postArray['title_sub'] . ' online';
        }

        // Adicionar tags personalizadas baseadas no ano de lançamento
        if (isset($postArray['title']) && isset($postArray['release_date']) && !empty($postArray['release_date'])) {
            $year = date('Y', strtotime($postArray['release_date']));
            $tags[] = 'assistir ' . $postArray['title'] . ' ' . $year;
        }

        // Atribuir as tags personalizadas ao postArray
        $postArray['tags'] = $tags;

        // Remover prefixos dos campos
        // if (isset($postArray['title']) && !empty($postArray['title'])) {
        //     $postArray['title'] = 'Assistir ' . $postArray['title'];
        // }

        if (isset($postArray['title_sub']) && !empty($postArray['title_sub'])) {
            $postArray['title_sub'] = 'Ver ' . $postArray['title_sub'] . ' online';
         }

        if (isset($postArray['overview']) && !empty($postArray['overview'])) {
             $postArray['overview'] = $postArray['title'] . ' ' . $postArray['overview']. ' ver filme online';
         }

        return response()->json($postArray);
    }

    public function tmdbEpisodeFetch(Request $request)
    {
        $postArray = $this->tmdbEpisodeApiTrait($request);
        return json_encode($postArray);
    }
}
