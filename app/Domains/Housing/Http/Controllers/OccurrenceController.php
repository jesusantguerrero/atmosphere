<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Jobs\RunTeamChecks;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Housing\Models\Occurrence;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Support\Carbon as SupportCarbon;
use App\Domains\Housing\Exports\OccurrenceExport;
use App\Domains\Housing\Imports\OccurrenceImport;
use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Transaction\Actions\SearchTransactions;

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
        $registerOccurrence->add(
            $occurrence->team_id,
            $occurrence->name,
            SupportCarbon::now()->format('Y-m-d')
        );

        return redirect()->back();
    }

    public function removeLastInstance(Occurrence $occurrence, RegisterOccurrence $registerOccurrence)
    {
        $registerOccurrence->remove(
            $occurrence->id,
        );

        return redirect()->back();
    }

    public function automationPreview(Occurrence $occurrence, SearchTransactions $search)
    {
        return $search->handle($occurrence->conditions);
    }

    public function automationLoad(Occurrence $occurrence, RegisterOccurrence $registerer)
    {
        return $registerer->load($occurrence);
    }

    public function sync(Occurrence $occurrence, RegisterOccurrence $registerer)
    {
        return $registerer->sync($occurrence);
    }

    public function syncAll()
    {
        RunTeamChecks::dispatch(auth()->user()->current_team_id);
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
