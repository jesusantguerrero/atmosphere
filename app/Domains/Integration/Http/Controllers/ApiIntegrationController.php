<?php

namespace App\Domains\Integration\Http\Controllers;

use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Services\GoogleService;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiIntegrationController extends BaseController
{
    public function __construct()
    {
        $this->model = new Integration;
        $this->searchable = ['name'];
        $this->validationRules = [];
    }

    public function destroy($id): JsonResponse
    {
        $user = request()->user();

        $integration = Integration::where([
            'id' => $id,
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
        ])->first();

        if (! $integration) {
            throw new NotFoundHttpException('Integration not found.');
        }

        $revoked = false;
        if (strtolower($integration->name) === 'google') {
            $revoked = GoogleService::revokeToken($integration);
        }

        $integration->delete();

        return response()->json([
            'ok' => true,
            'revoked' => $revoked,
        ]);
    }
}
