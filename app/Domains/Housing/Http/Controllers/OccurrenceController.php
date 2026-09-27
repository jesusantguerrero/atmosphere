<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Exports\OccurrenceExport;
use App\Domains\Housing\Imports\OccurrenceImport;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Transaction\Actions\SearchTransactions;
use App\Jobs\RunTeamChecks;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon as SupportCarbon;
use Maatwebsite\Excel\Facades\Excel;

class OccurrenceController extends InertiaController
{
    public function __construct(Occurrence $occurrence)
    {
        $this->model = $occurrence;
        $this->templates = [
            'index' => 'Housing/Occurrence',
        ];
        $this->searchable = ['id', 'name'];
        $this->includes = [];
        $this->appends = [];
        $this->resourceName = 'occurrences';
        $this->authorizedTeam = true;
    }

    protected function getIndexProps(Request $request, $resources = null): array
    {
        return [
            'linkedTypes' => Occurrence::getLinkedModels(),
        ];
    }

    public function addInstance(Occurrence $occurrence, RegisterOccurrence $registerOccurrence)
    {
        $this->ensureTeamOccurrence($occurrence);

        $registerOccurrence->add(
            $occurrence->team_id,
            $occurrence->name,
            SupportCarbon::now()->format('Y-m-d')
        );

        return redirect()->back();
    }

    public function removeLastInstance(Occurrence $occurrence, RegisterOccurrence $registerOccurrence)
    {
        $this->ensureTeamOccurrence($occurrence);

        $registerOccurrence->remove(
            $occurrence->id,
        );

        return redirect()->back();
    }

    public function automationPreview(Occurrence $occurrence, SearchTransactions $search)
    {
        $this->ensureTeamOccurrence($occurrence);

        return $search->handle($occurrence->conditions, $occurrence->team_id);
    }

    public function automationLoad(Occurrence $occurrence, RegisterOccurrence $registerer)
    {
        $this->ensureTeamOccurrence($occurrence);

        return $registerer->load($occurrence);
    }

    public function sync(Occurrence $occurrence, RegisterOccurrence $registerer)
    {
        $this->ensureTeamOccurrence($occurrence);

        return $registerer->sync($occurrence);
    }

    public function remind(Occurrence $occurrence, RegisterOccurrence $registerer)
    {
        $this->ensureTeamOccurrence($occurrence);

        return $registerer->remind($occurrence);
    }

    private function ensureTeamOccurrence(Occurrence $occurrence): void
    {
        abort_unless((int) $occurrence->team_id === (int) request()->user()->current_team_id, 404);
    }

    public function syncAll()
    {
        RunTeamChecks::dispatch(request()->user()->current_team_id);
    }

    public function export()
    {
        $dataToExport = new OccurrenceExport(Occurrence::where('team_id', request()->user()->current_team_id)->get()->toArray());

        return Excel::download($dataToExport, 'occurrences.xlsx');
    }

    public function import(Request $request)
    {
        Excel::import(new OccurrenceImport($request->user()), $request->file('file'));

        return redirect()->back();
    }
}
