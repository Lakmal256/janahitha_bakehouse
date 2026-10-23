<?php

namespace App\Http\Controllers\Api;

use App\Documents\UserDocuments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * List every account for user managers; everyone else only sees their own.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = $request->user()->can('viewAny', User::class)
            ? User::query()->with('routes:id,source_key')->orderBy('name')->get()
            : collect([$request->user()->load('routes:id,source_key')]);

        return UserResource::collection($users);
    }

    public function update(SaveUserRequest $request, UserDocuments $users, string $key): JsonResponse
    {
        $user = $users->save($key, $request->validated(), $request->target());

        return (new UserResource($user->load('routes:id,source_key')))->response()->setStatusCode($user->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(UserDocuments $users, string $key): Response
    {
        $user = User::query()->where('source_key', $key)->firstOrFail();
        Gate::authorize('delete', $user);

        $users->delete($user);

        return response()->noContent();
    }
}
