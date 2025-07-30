<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VillageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $villageQuery = Village::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $villageQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ['nama'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $villageQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $villages = $villageQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Village list retrieved', $villages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:villages,nama',
            'fullCode' => 'required|numeric|unique:villages,fullCode',
            'code' => 'nullable|integer|unique:villages,code',
            'kode_pos' => 'required|integer|unique:villages,kode_pos',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $village = Village::create($validated);

        return Formatter::ApiResponse(200, 'Village added', Village::find($village->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(Village $village)
    {
        return Formatter::ApiResponse(200, 'Village found', $village);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Village $village)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:villages,nama,' . $village->id,
            'fullCode' => 'sometimes|required|numeric|unique:villages,fullCode,' . $village->id,
            'code' => 'nullable|integer|unique:villages,code,' . $village->id,
            'kode_pos' => 'sometimes|required|integer|unique:villages,kode_pos,' . $village->id,
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $village->update($validated);

        return Formatter::ApiResponse(200, 'Village updated', Village::find($village->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Village $village)
    {
        $village->delete();
        return Formatter::ApiResponse(200, 'Village removed');
    }

    /**
     * Upload batch data from JSON or CSV
     */
    public function uploadBatchData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $file = $request->file('file');
        $data = [];

        try {
            if ($file->getClientOriginalExtension() === 'json') {
                $json = json_decode(file_get_contents($file), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return Formatter::ApiResponse(422, 'Invalid JSON file');
                }
                $data = $json;
            } elseif ($file->getClientOriginalExtension() === 'csv') {
                $csv = array_map('str_getcsv', file($file->getPathname()));
                $header = array_shift($csv);
                foreach ($csv as $row) {
                    if (count($row) === count($header)) {
                        $data[] = array_combine($header, $row);
                    }
                }
            }

            $inserted = [];
            $failed = [];

            foreach ($data as $index => $row) {
                $v = Validator::make($row, [
                    'nama' => 'required|string|max:255',
                    'fullCode' => 'required|numeric',
                    'code' => 'nullable|integer',
                    'kode_pos' => 'required|integer',
                ]);

                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                $validated = $v->validated();

                // Check uniqueness
                $nameExists = Village::where('nama', $validated['nama'])->exists();
                $fullCodeExists = Village::where('fullCode', $validated['fullCode'])->exists();
                $codeExists = !empty($validated['code']) && Village::where('code', $validated['code'])->where('id', '!=', $validated['id'] ?? 0)->exists();
                $kodePosExists = Village::where('kode_pos', $validated['kode_pos'])->exists();

                if ($nameExists || $fullCodeExists || $codeExists || $kodePosExists) {
                    $errors = [];
                    if ($nameExists) $errors[] = 'Name already exists';
                    if ($fullCodeExists) $errors[] = 'Full code already exists';
                    if ($codeExists) $errors[] = 'Code already exists';
                    if ($kodePosExists) $errors[] = 'Kode pos already exists';
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $errors);
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $village = Village::create($validated);
                    \DB::commit();
                    $inserted[] = $village;
                } catch (\Exception $e) {
                    \DB::rollBack();
                    $failed[] = "Row " . ($index + 1) . ": Save failed - " . $e->getMessage();
                }
            }

            return Formatter::ApiResponse(200, count($inserted) ? 'Batch processed' : 'No data inserted', compact('inserted', 'failed'));
        } catch (\Exception $e) {
            return Formatter::ApiResponse(500, 'Processing failed', null, [$e->getMessage()]);
        }
    }
}
