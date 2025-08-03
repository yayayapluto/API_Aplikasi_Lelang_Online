<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cityQuery = City::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $cityQuery->where('nama', 'LIKE', $searchTerm);
        }

        if (request()->filled('provinceCode')) {
            $provinceCode = request()->provinceCode;
            if (is_string($provinceCode) && strlen($provinceCode) == 2 && ctype_digit($provinceCode)) {
                $cityQuery->where('fullCode', 'LIKE', $provinceCode . '%');
            }
        }

        $validColumns = ['nama', "fullCode", "code"];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $cityQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $cities = $cityQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, 'City list retrieved', $cities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:cities,nama',
            'fullCode' => 'required|integer|unique:cities,fullCode',
            'code' => 'nullable|integer|unique:cities,code',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $city = City::create($validated);

        return Formatter::ApiResponse(200, 'City added', City::find($city->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city)
    {
        return Formatter::ApiResponse(200, 'City found', $city);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, City $city)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:cities,nama,' . $city->id,
            'fullCode' => 'sometimes|required|integer|unique:cities,fullCode,' . $city->id,
            'code' => 'nullable|integer|unique:cities,code,' . $city->id,
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $city->update($validated);

        return Formatter::ApiResponse(200, 'City updated', City::find($city->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city)
    {
        $city->delete();
        return Formatter::ApiResponse(200, 'City removed');
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

                if (City::where('nama', $validated['nama'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": City name already exists";
                    continue;
                }

                if (City::where('fullCode', $validated['fullCode'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Full code already exists";
                    continue;
                }

                if (!empty($validated['code']) && City::where('code', $validated['code'])->where('id', '!=', $validated['id'] ?? 0)->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Code already exists";
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $city = City::create($validated);
                    \DB::commit();
                    $inserted[] = $city;
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
