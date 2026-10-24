<?php

namespace App\Http\Controllers\Api;

use App\Domains\AppCore\Data\CoreModuleTypeEnum;
use App\Domains\AppCore\Models\CoreModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\UpdateModulesRequest;
use App\Http\Requests\Mobile\UpdateNotificationPreferencesRequest;
use App\Models\User;
use App\Notifications\LogerNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePreferencesController extends Controller
{
    public function notificationPreferences(Request $request): JsonResponse
    {
        return response()->json($this->notificationPreferencesPayload($request->user()));
    }

    public function updateNotificationPreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->notificationPrefs();
        $channels = $request->validated('channels');
        $types = $request->validated('types');

        $prefs = [
            'email' => (bool) ($channels['email'] ?? $current['email']),
            'push' => (bool) ($channels['push'] ?? $current['push']),
        ];

        if (is_array($types)) {
            $knownKeys = array_keys(LogerNotification::NOTIFICATION_TYPES);
            $prefs['types'] = [];
            foreach ($types as $key => $channelPrefs) {
                if (in_array($key, $knownKeys, true) && is_array($channelPrefs)) {
                    $prefs['types'][$key] = array_filter(
                        $channelPrefs,
                        fn ($v, $k) => in_array($k, ['email', 'push'], true) && is_bool($v),
                        ARRAY_FILTER_USE_BOTH,
                    );
                }
            }
        } elseif (isset($current['types'])) {
            $prefs['types'] = $current['types'];
        }

        $user->notification_prefs = $prefs;
        $user->save();

        return response()->json($this->notificationPreferencesPayload($user));
    }

    public function modules(Request $request): JsonResponse
    {
        return response()->json($this->modulesPayload($request->user()));
    }

    public function updateModules(UpdateModulesRequest $request): JsonResponse
    {
        $user = $request->user();
        $enabledKeys = array_merge($request->validated('modules'), [CoreModuleTypeEnum::Finance->value]);

        foreach (CoreModuleTypeEnum::cases() as $module) {
            CoreModule::updateOrCreate(
                ['team_id' => $user->current_team_id, 'name' => $module->name],
                [
                    'enabled' => in_array($module->value, $enabledKeys, true),
                    'alias' => ucfirst($module->name),
                    'user_id' => $user->id,
                    'created_from' => 'mobile',
                ],
            );
        }

        return response()->json($this->modulesPayload($user));
    }

    /**
     * @return array{channels: array{email: bool, push: bool}, types: array<int, array{key: string, label: string, email: bool, push: bool}>}
     */
    private function notificationPreferencesPayload(User $user): array
    {
        $prefs = $user->notificationPrefs();
        $storedTypes = $prefs['types'] ?? [];

        $types = [];
        foreach (LogerNotification::NOTIFICATION_TYPES as $key => $label) {
            $typePrefs = $storedTypes[$key] ?? [];
            $types[] = [
                'key' => $key,
                'label' => $label,
                'email' => (bool) ($typePrefs['email'] ?? $prefs['email']),
                'push' => (bool) ($typePrefs['push'] ?? $prefs['push']),
            ];
        }

        return [
            'channels' => [
                'email' => (bool) $prefs['email'],
                'push' => (bool) $prefs['push'],
            ],
            'types' => $types,
        ];
    }

    /**
     * Finance is the app's always-on module (see CreateTeamSettings), so it is reported enabled regardless of stored state.
     *
     * @return array<int, array{key: string, name: string, enabled: bool}>
     */
    private function modulesPayload(User $user): array
    {
        $enabledByKey = CoreModule::where('team_id', $user->current_team_id)
            ->pluck('enabled', 'name')
            ->mapWithKeys(fn ($enabled, $name) => [strtolower($name) => (bool) $enabled]);

        return array_map(fn (CoreModuleTypeEnum $module) => [
            'key' => $module->value,
            'name' => ucfirst($module->name),
            'enabled' => $module === CoreModuleTypeEnum::Finance || ($enabledByKey[$module->value] ?? false),
        ], CoreModuleTypeEnum::cases());
    }
}
