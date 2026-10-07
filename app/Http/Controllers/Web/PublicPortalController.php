<?php

namespace App\Http\Controllers\Web;

use App\Enums\ArtCategory;
use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArtistProfile;
use App\Models\Issue;
use App\Models\MediaGallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicPortalController extends Controller
{
    /**
     * Display the cultural portal homepage.
     */
    public function home(): Response
    {
        $latestIssue = Issue::published()
            ->withCount(['articles' => fn ($query) => $query->published()])
            ->orderByDesc('issue_number')
            ->first();

        $featuredArticles = Article::published()
            ->with(['category', 'author:id,name', 'issue:id,title,issue_number,slug'])
            ->orderByDesc('views_count')
            ->take(4)
            ->get();

        $categorizedArticles = [
            'cinema' => Article::published()
                ->whereHas('category', function ($query) {
                    $query->where('slug', 'cinema')
                        ->orWhereHas('parent', fn ($parent) => $parent->where('slug', 'cinema'));
                })
                ->with(['category', 'author:id,name', 'issue:id,title,issue_number,slug'])
                ->latest('published_at')
                ->take(4)
                ->get(),

            'theatre' => Article::published()
                ->whereHas('category', function ($query) {
                    $query->where('slug', 'theatre')
                        ->orWhereHas('parent', fn ($parent) => $parent->where('slug', 'theatre'));
                })
                ->with(['category', 'author:id,name', 'issue:id,title,issue_number,slug'])
                ->latest('published_at')
                ->take(4)
                ->get(),

            'fine_arts' => Article::published()
                ->whereHas('category', function ($query) {
                    $query->whereIn('slug', ['fine-arts', 'fine_arts'])
                        ->orWhereHas('parent', fn ($parent) => $parent->whereIn('slug', ['fine-arts', 'fine_arts']));
                })
                ->with(['category', 'author:id,name', 'issue:id,title,issue_number,slug'])
                ->latest('published_at')
                ->take(4)
                ->get(),

            'music' => Article::published()
                ->whereHas('category', function ($query) {
                    $query->where('slug', 'music')
                        ->orWhereHas('parent', fn ($parent) => $parent->where('slug', 'music'));
                })
                ->with(['category', 'author:id,name', 'issue:id,title,issue_number,slug'])
                ->latest('published_at')
                ->take(4)
                ->get(),
        ];

        $spotlightArtists = ArtistProfile::featured()
            ->withCount(['articles' => fn ($query) => $query->published()])
            ->take(6)
            ->get();

        if ($spotlightArtists->count() < 4) {
            $spotlightArtists = ArtistProfile::withCount(['articles' => fn ($query) => $query->published()])
                ->orderByDesc('is_featured')
                ->take(6)
                ->get();
        }

        return Inertia::render('Public/Home', [
            'latestIssue' => $latestIssue,
            'featuredArticles' => $featuredArticles,
            'categorizedArticles' => $categorizedArticles,
            'spotlightArtists' => $spotlightArtists,
        ]);
    }

    /**
     * Display the paginated archive of magazine issues with year filters.
     */
    public function issues(Request $request): Response
    {
        $year = $request->query('year');

        $availableYears = Issue::published()
            ->whereNotNull('published_at')
            ->pluck('published_at')
            ->map(fn ($date) => (int) $date->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        $issues = Issue::published()
            ->when($year, fn ($query) => $query->whereYear('published_at', $year))
            ->withCount(['articles' => fn ($query) => $query->published()])
            ->orderByDesc('issue_number')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Public/Issues/Index', [
            'issues' => $issues,
            'availableYears' => $availableYears,
            'currentYear' => $year ? (int) $year : null,
        ]);
    }

    /**
     * Display a single issue detail with table of contents and digital flipbook reader integration.
     */
    public function issueShow(string $slug): Response
    {
        $issue = Issue::published()
            ->where('slug', $slug)
            ->with(['articles' => function ($query) {
                $query->published()
                    ->with(['category', 'author:id,name', 'artistProfile'])
                    ->latest('published_at');
            }])
            ->firstOrFail();

        $disk = Storage::disk('public');
        $pagesDir = "issues/{$issue->id}/pages";
        $pages = [];
        $thumbnails = [];

        $count = $issue->pages_count > 0 ? $issue->pages_count : 1;

        for ($i = 1; $i <= $count; $i++) {
            $pagePath = "{$pagesDir}/page_{$i}.webp";
            $pages[] = $disk->url($pagePath);

            $thumbPath = "{$pagesDir}/page_{$i}_thumb.webp";
            $thumbnails[] = $disk->exists($thumbPath) ? $disk->url($thumbPath) : $disk->url($pagePath);
        }

        $readerData = [
            'issue' => [
                'id' => $issue->id,
                'title' => $issue->title,
                'issue_number' => $issue->issue_number,
                'slug' => $issue->slug,
                'pages_count' => count($pages),
                'pdf_url' => $issue->pdf_file_path ? $disk->url($issue->pdf_file_path) : null,
            ],
            'pages' => $pages,
            'thumbnails' => $thumbnails,
        ];

        return Inertia::render('Public/Issues/Show', [
            'issue' => $issue,
            'readerData' => $readerData,
        ]);
    }

    /**
     * Display a single long-form article view with atomic view increment and related articles.
     */
    public function articleShow(string $slug): Response
    {
        $article = Article::published()
            ->where('slug', $slug)
            ->with(['category', 'author:id,name', 'issue:id,title,issue_number,slug,cover_image', 'artistProfile'])
            ->firstOrFail();

        // Increment views count atomically without touching timestamps
        $article->incrementQuietly('views_count');

        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->where('category_id', $article->category_id)
            ->with(['category', 'author:id,name', 'issue:id,title,issue_number,slug'])
            ->latest('published_at')
            ->take(4)
            ->get();

        if ($relatedArticles->count() < 4) {
            $moreArticles = Article::published()
                ->where('id', '!=', $article->id)
                ->whereNotIn('id', $relatedArticles->pluck('id'))
                ->with(['category', 'author:id,name', 'issue:id,title,issue_number,slug'])
                ->latest('published_at')
                ->take(4 - $relatedArticles->count())
                ->get();

            $relatedArticles = $relatedArticles->concat($moreArticles);
        }

        return Inertia::render('Public/Articles/Show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
        ]);
    }

    /**
     * Display directory of Libyan artists and cultural pioneers.
     */
    public function artists(Request $request): Response
    {
        $category = $request->query('category');
        $search = $request->query('search');

        $artists = ArtistProfile::query()
            ->when($category, fn ($query) => $query->where('art_category', $category))
            ->when($search, fn ($query) => $query->where('full_name', 'like', "%{$search}%"))
            ->withCount(['articles' => fn ($query) => $query->published()])
            ->orderByDesc('is_featured')
            ->orderBy('full_name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Public/Artists/Index', [
            'artists' => $artists,
            'categories' => array_map(fn ($cat) => [
                'value' => $cat->value,
                'label' => $cat->label(),
            ], ArtCategory::cases()),
            'filters' => [
                'category' => $category,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display artist profile with linked articles and cataloged works.
     */
    public function artistShow(string $slug): Response
    {
        $artist = ArtistProfile::where('slug', $slug)->firstOrFail();

        $articles = $artist->articles()
            ->published()
            ->with(['category', 'issue:id,title,issue_number,slug'])
            ->latest('published_at')
            ->get();

        return Inertia::render('Public/Artists/Show', [
            'artist' => $artist,
            'articles' => $articles,
        ]);
    }

    /**
     * Display filterable media gallery.
     */
    public function gallery(Request $request): Response
    {
        $type = $request->query('type');

        $media = MediaGallery::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->latest()
            ->paginate(18)
            ->withQueryString();

        return Inertia::render('Public/Gallery/Index', [
            'media' => $media,
            'types' => array_map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], MediaType::cases()),
            'currentType' => $type,
        ]);
    }

    /**
     * Instant search autocomplete endpoint for the header overlay.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'articles' => [],
                'issues' => [],
                'artists' => [],
            ]);
        }

        $articles = Article::published()
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%");
            })
            ->with('category:id,name')
            ->select(['id', 'title', 'slug', 'category_id'])
            ->take(5)
            ->get();

        $issues = Issue::published()
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('issue_number', 'like', "%{$q}%");
            })
            ->select(['id', 'title', 'issue_number', 'slug'])
            ->take(4)
            ->get();

        $artists = ArtistProfile::where('full_name', 'like', "%{$q}%")
            ->select(['id', 'full_name', 'slug', 'art_category'])
            ->take(4)
            ->get();

        return response()->json([
            'articles' => $articles,
            'issues' => $issues,
            'artists' => $artists,
        ]);
    }
}
