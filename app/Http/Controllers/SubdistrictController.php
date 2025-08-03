<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubdistrictController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subdistrictQuery = Subdistrict::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $subdistrictQuery->where('nama', 'LIKE', $searchTerm);
        }

        if (request()->filled('cityCode')) {
            $cityCode = request()->cityCode;
            if (is_string($cityCode) && strlen($cityCode) == 4 && ctype_digit($cityCode)) {
                $subdistrictQuery->where('fullCode', 'LIKE', $cityCode . '%');
            }
        }

        $validColumns = ['nama', "fullCode", "code"];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $subdistrictQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $subdistricts = $subdistrictQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Subdistrict list retrieved', $subdistricts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:subdistricts,nama',
            'fullCode' => 'required|integer|unique:subdistricts,fullCode',
            'code' => 'nullable|integer|unique:subdistricts,code',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $subdistrict = Subdistrict::create($validated);

        return Formatter::ApiResponse(200, 'Subdistrict added', Subdistrict::find($subdistrict->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(Subdistrict $subdistrict)
    {
        return Formatter::ApiResponse(200, 'Subdistrict found', $subdistrict);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subdistrict $subdistrict)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:subdistricts,nama,' . $subdistrict->id,
            'fullCode' => 'sometimes|required|integer|unique:subdistricts,fullCode,' . $subdistrict->id,
            'code' => 'nullable|integer|unique:subdistricts,code,' . $subdistrict->id,
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $subdistrict->update($validated);

        return Formatter::ApiResponse(200, 'Subdistrict updated', Subdistrict::find($subdistrict->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subdistrict $subdistrict)
    {
        $subdistrict->delete();
        return Formatter::ApiResponse(200, 'Subdistrict removed');
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
                    'fullCode' => 'required|integer',
                    'code' => 'nullable|integer',
                ]);

                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                $validated = $v->validated();

                if (Subdistrict::where('nama', $validated['nama'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Subdistrict name already exists";
                    continue;
                }

                if (Subdistrict::where('fullCode', $validated['fullCode'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Full code already exists";
                    continue;
                }

                if (!empty($validated['code']) && Subdistrict::where('code', $validated['code'])->where('id', '!=', $validated['id'] ?? 0)->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Code already exists";
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $subdistrict = Subdistrict::create($validated);
                    \DB::commit();
                    $inserted[] = $subdistrict;
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
