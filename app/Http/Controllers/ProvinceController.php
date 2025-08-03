<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $provinceQuery = Province::query();

        if (request()->filled("search")) {
            $searchTerm = '%' . request()->search . '%';
            $provinceQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ["nama", "fullCode", "code"];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $provinceQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $provinces = $provinceQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, "Province list retrieved", $provinces);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:provinces,nama',
            'code' => 'required|integer|unique:provinces,code',
            'fullCode' => 'required|integer|unique:provinces,fullCode',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $province = Province::create($validator->validated());

        return Formatter::ApiResponse(200, "Province added", Province::find($province->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(Province $province)
    {
        return Formatter::ApiResponse(200, "Province found", $province);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Province $province)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:provinces,nama,' . $province->id,
            'code' => 'sometimes|required|integer|unique:provinces,code,' . $province->id,
            'fullCode' => 'sometimes|required|integer|unique:provinces,fullCode,' . $province->id,
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $province->update($validator->validated());

        return Formatter::ApiResponse(200, "Province updated", Province::find($province->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Province $province)
    {
        $province->delete();
        return Formatter::ApiResponse(200, "Province removed");
    }

    /**
     * Upload batch data from JSON or CSV
     */
    public function uploadBatchData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json,csv|max:10240',
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
                    'code' => 'required|integer',
                    'fullCode' => 'required|integer',
                ]);

                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                $validated = $v->validated();

                // Check uniqueness
                if (Province::where('nama', $validated['nama'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Province name already exists";
                    continue;
                }
                if (Province::where('code', $validated['code'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Code already taken";
                    continue;
                }
                if (Province::where('fullCode', $validated['fullCode'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Full code already taken";
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $province = Province::create($validated);
                    \DB::commit();
                    $inserted[] = $province;
                } catch (\Exception $e) {
                    \DB::rollBack();
                    $failed[] = "Row " . ($index + 1) . ": Save failed";
                }
            }

            return Formatter::ApiResponse(200, count($inserted) ? 'Batch processed' : 'No data inserted', compact('inserted', 'failed'));
        } catch (\Exception $e) {
            return Formatter::ApiResponse(500, 'Processing failed', null, [$e->getMessage()]);
        }
    }
}
