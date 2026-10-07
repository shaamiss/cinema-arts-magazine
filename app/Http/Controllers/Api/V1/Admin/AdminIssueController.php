<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreIssueRequest;
use App\Http\Requests\Api\V1\UpdateIssueRequest;
use App\Http\Resources\IssueDetailResource;
use App\Http\Resources\IssueResource;
use App\Http\Responses\ApiResponse;
use App\Models\Issue;
use App\Services\MagazineCacheService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminIssueController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of all issues.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Issue::class);

        $perPage = min((int) $request->query('per_page', 20), 100);

        $issues = Issue::query()
            ->withCount('articles')
            ->orderByDesc('issue_number')
            ->paginate($perPage);

        return ApiResponse::success(IssueResource::collection($issues));
    }

    /**
     * Store a newly created issue in storage.
     */
    public function store(StoreIssueRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $issue = Issue::create($data);

        MagazineCacheService::flush('issues');

        return ApiResponse::success(
            new IssueDetailResource($issue),
            'Issue created successfully.',
            201
        );
    }

    /**
     * Display the specified issue.
     */
    public function show(Issue $issue): JsonResponse
    {
        $this->authorize('view', $issue);

        return ApiResponse::success(
            new IssueDetailResource($issue->load(['articles' => fn ($q) => $q->latest()]))
        );
    }

    /**
     * Update the specified issue in storage.
     */
    public function update(UpdateIssueRequest $request, Issue $issue): JsonResponse
    {
        $issue->update($request->validated());

        MagazineCacheService::flush('issues');

        return ApiResponse::success(
            new IssueDetailResource($issue->fresh()),
            'Issue updated successfully.'
        );
    }

    /**
     * Publish the specified issue.
     */
    public function publish(Issue $issue): JsonResponse
    {
        $this->authorize('publish', $issue);

        $issue->update([
            'status' => PublicationStatus::Published,
            'published_at' => $issue->published_at ?? now(),
        ]);

        MagazineCacheService::flush(['issues', 'articles']);

        return ApiResponse::success(
            new IssueDetailResource($issue->fresh()),
            'Issue published successfully.'
        );
    }

    /**
     * Remove the specified issue from storage.
     */
    public function destroy(Issue $issue): JsonResponse
    {
        $this->authorize('delete', $issue);

        $issue->delete();

        MagazineCacheService::flush(['issues', 'articles']);

        return ApiResponse::success(null, 'Issue deleted successfully.');
    }
}
