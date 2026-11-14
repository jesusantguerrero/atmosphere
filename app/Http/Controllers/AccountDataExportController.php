<?php

namespace App\Http\Controllers;

use App\Domains\AppCore\Services\AccountDataExporter;
use App\Jobs\ExportAccountData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountDataExportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        ExportAccountData::dispatch($request->user());

        return back(303)->with('flash', [
            'banner' => __('We are preparing your data. You will get an email with the download link.'),
        ]);
    }

    public function show(Request $request, string $export, AccountDataExporter $exporter): StreamedResponse
    {
        abort_unless(preg_match('/^[0-9a-f-]{36}$/', $export), 404);

        $path = $exporter->pathFor($request->user(), $export);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, 'loger-data-'.now()->format('Y-m-d').'.zip');
    }
}
