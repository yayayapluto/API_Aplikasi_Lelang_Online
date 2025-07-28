<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    public function index()
    {
        $cityQuery = City::query();

        if (request()->filled("search")) {
            $searchTerm = '%' . request()->search . '%';
            $cityQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ["nama", "province_id"];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $cityQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $cities = $cityQuery->with('province')->simplePaginate($size);

        return Formatter::ApiResponse(200, "City list retrieved", $cities);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:cities,nama',
            'province_id' => 'required|exists:provinces,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newCity = City::create($validated);

        return Formatter::ApiResponse(200, "City added", City::with('province')->find($newCity->id));
    }

    public function show(City $city)
    {
        $city = $city->load('province');
        return Formatter::ApiResponse(200, "City found", $city);
    }

    public function update(Request $request, City $city)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:cities,nama,' . $city->id,
            'province_id' => 'sometimes|required|exists:provinces,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $city->update($validated);

        return Formatter::ApiResponse(200, "City updated", City::with('province')->find($city->id));
    }

    public function destroy(City $city)
    {
        $city->delete();
        return Formatter::ApiResponse(200, "City removed");
    }

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
                if (json_last_error() !== JSON_ERROR_NONE) return Formatter::ApiResponse(422, 'Invalid JSON');
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
                    'province_id' => 'required|exists:provinces,id',
                ]);
                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                $validated = $v->validated();
                if (City::where('nama', $validated['nama'])->where('province_id', $validated['province_id'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": City already exists in this province";
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
