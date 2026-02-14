<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CsvImportRequest;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessQuestionImport;
use App\Models\Import;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function importQuestions(CsvImportRequest $request)
    {
        $this->authorize('create', Import::class);

        $file = $request->file('file');
        $path = $file->store('imports');

        $import = Import::create([
            'organization_id' => $request->user()->organization_id,
            'imported_by' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'pending',
            'total_rows' => 0, // will be updated by job
            'processed_rows' => 0,
        ]);

        // Process immediately so MVP works without a separate queue worker.
        ProcessQuestionImport::dispatchSync($import);

        return new ImportResource($import->fresh());
    }

    public function show(Import $import)
    {
        $this->authorize('view', $import);
        return new ImportResource($import->load('importer'));
    }
}
