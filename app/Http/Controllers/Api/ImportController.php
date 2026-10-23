<?php

namespace App\Http\Controllers\Api;

use App\Documents\BrowserDataImporter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ImportBrowserDataRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    /**
     * Import a backup of the browser app's data. The upload is kept exactly as received in
     * storage/app/private/imports before anything is written.
     */
    public function store(ImportBrowserDataRequest $request, BrowserDataImporter $importer): JsonResponse
    {
        Storage::disk('local')->put(
            'imports/'.now()->format('Y-m-d_His').'_'.Str::random(6).'.json',
            $request->getContent(),
        );

        $data = $request->validated('data');

        if (function_exists('set_time_limit')) {
            set_time_limit(300);
        }

        return response()->json(['data' => $importer->import($data, $request->user())]);
    }
}
