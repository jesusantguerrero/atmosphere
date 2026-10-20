<?php

namespace Database\Seeders;

use App\Domains\AppCore\Models\Category;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use Illuminate\Database\Seeder;
use Modules\Plan\Entities\PlanTypes;

class ProfileWorkspaceDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function seedFor(LogerProfile $source): LogerProfile
    {
        abort_unless(app()->environment('local', 'testing'), 403);

        return $source->getConnection()->transaction(function () use ($source) {
            $profile = LogerProfile::firstOrCreate(['team_id' => $source->team_id, 'name' => $source->name.' · Demo'], ['user_id' => $source->user_id]);
            $service = app(ProfileWorkspaceService::class);
            if ($service->links($profile)->isNotEmpty()) {
                return $profile;
            }
            $boards = [
                ['Salud', 'notebook', '#9b8be8', 'Consultas, notas y seguimiento, juntos.', [
                    'Terapia' => [
                        ['Notas de la sesión', 'note', 'Ejemplo: un lugar para anotar recomendaciones, preguntas y acuerdos de cada sesión. No es un historial médico real.'],
                        ['Próxima sesión', 'event', 'Ejemplo: preparar las preguntas y confirmar la ubicación.', now()->addDays(2)->setTime(16, 0)->format('Y-m-d\TH:i')],
                        ['Preparar preguntas para la próxima sesión', 'task', 'Revisar las notas anteriores y reunir las preguntas pendientes.'],
                    ],
                    'Consultas y documentos' => [['Preguntas para la consulta', 'note', 'Ejemplo: dudas y observaciones para la próxima consulta.']],
                ]],
                ['Colegio', 'kanban', '#6da8d9', 'Lo que hay que preparar y lo que viene.', [
                    'Por preparar' => [
                        ['Preparar materiales de la semana', 'task', 'Revisar la lista y separar lo que hace falta comprar.'],
                        ['Reunión del colegio', 'event', 'Ejemplo: preguntas, notas y acuerdos.', now()->addDays(4)->setTime(9, 0)->format('Y-m-d\TH:i')],
                    ],
                    'En seguimiento' => [['Notas del colegio', 'note', 'Ejemplo: comunicaciones e ideas para conversar con el colegio.']],
                    'Listo' => [['Organizar los útiles', 'task', '', null, true]],
                ]],
                ['Ideas y recuerdos', 'list', '#d7a369', 'Esas pequeñas cosas que quieres guardar.', [
                    'Para recordar' => [
                        ['Ideas para el fin de semana', 'note', 'Un paseo, un libro o una actividad para compartir.'],
                        ['Libros que queremos leer', 'note', 'Una página para ir reuniendo lecturas.'],
                    ],
                ]],
            ];
            foreach ($boards as [$name, $view, $color, $description, $sections]) {
                $board = $service->createBoard($profile, compact('name', 'view', 'color', 'description'), $source->user_id);
                foreach ($sections as $sectionName => $pages) {
                    $stage = $board->stages()->first();
                    if ($stage->name === 'Primer cuaderno') {
                        $stage->update(['name' => $sectionName]);
                    } else {
                        $stage = $service->createStage($profile, $board, $sectionName, $source->user_id);
                    }
                    foreach ($pages as $page) {
                        $service->createPage($profile, $board, $stage, ['title' => $page[0], 'kind' => $page[1], 'body' => $page[2], 'when' => $page[3] ?? null, 'done' => $page[4] ?? false], $source->user_id);
                    }
                }
            }
            $shopping = $service->createBoard($profile, ['name' => 'Compras · '.$profile->name, 'view' => 'list'], $source->user_id);
            $shopping->forceFill(['plan_type_name' => PlanTypes::SHOPPING_LIST])->save();
            foreach ([['Leche', '1 unidad', false], ['Pañales', '1 paquete', false], ['Materiales para el colegio', 'Revisar la lista', true]] as [$title, $body, $done]) {
                $service->createPage($profile, $shopping, $shopping->stages()->first(), compact('title', 'body', 'done'), $source->user_id);
            }
            foreach (['Leche', 'Pañales'] as $name) {
                $occurrence = Occurrence::withoutEvents(fn () => Occurrence::create(['team_id' => $source->team_id, 'user_id' => $source->user_id, 'name' => 'Demo · '.$name, 'log' => [], 'occurrence_count' => 0, 'notify_on_avg' => false, 'notify_on_last_count' => false, 'is_active' => true]));
                $service->link($profile, $occurrence, 'occurrence');
            }
            $group = Category::create(['team_id' => $source->team_id, 'user_id' => $source->user_id, 'name' => 'Demo · '.$source->name, 'resource_type' => 'transactions', 'depth' => 0, 'index' => 999]);
            foreach (['Colegio', 'Salud y cuidado', 'Actividades'] as $index => $name) {
                Category::create(['team_id' => $source->team_id, 'user_id' => $source->user_id, 'name' => $name.' · Demo', 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => $index]);
            }
            $service->link($profile, $group, 'category');

            return $profile;
        });
    }
}
