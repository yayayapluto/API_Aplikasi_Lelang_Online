<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countryQuery =Country::query();

        if (\request()->filled("search")) {
            $searchTerm = '%' . \request()->search . '%';
            $countryQuery->where(function ($query) use ($searchTerm) {
                $query->where('nama', 'LIKE', $searchTerm)
                    ->orWhere('kode', 'LIKE', $searchTerm)
                    ->orWhere('nomor', 'LIKE', $searchTerm);
            });
        }

        $validColumns = ["nama", "kode", "nomor"];

        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $countryQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $countries =$countryQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, "Country list retrieved", $countries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(\request()->all(), [
            "nama" => "required|string|unique:countries,nama",
            "kode" => "required|string|unique:countries,kode",
            "nomor" => "required|string|unique:countries,nomor",
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        $newCountry = Country::query()->create($validated);
        return Formatter::ApiResponse(200, "Country added", Country::query()->find($newCountry->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $country = Country::query()->find($id);
        if (is_null($country)) {
            return Formatter::ApiResponse(404, "Country not found");
        }

        return Formatter::ApiResponse(200, "Country found", $country);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $country = Country::query()->find($id);
        if (is_null($country)) {
            return Formatter::ApiResponse(404, "Country not found");
        }

        $validator = Validator::make(\request()->all(), [
            "nama" => "sometimes|string|unique:countries,nama," . $id,
            "kode" => "sometimes|string|unique:countries,kode,". $id,
            "nomor" => "sometimes|string|unique:countries,nomor," . $id
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $country->update($validated);

        return Formatter::ApiResponse(200, "Country updated", Country::query()->find($id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $country = Country::query()->find($id);
        if (is_null($country)) {
            return Formatter::ApiResponse(404, "Country not found");
        }

        $country->delete();
        return Formatter::ApiResponse(200, "Country removed");
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
                $jsonContent = file_get_contents($file->getPathname());
                $decoded = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return Formatter::ApiResponse(422, 'Invalid JSON file');
                }

                $data = $decoded;
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
                $validator = Validator::make($row, [
                    'nama' => 'required|string|max:255',
                    'kode' => 'required|string|max:10',
                    'nomor' => 'required|string|max:20',
                ]);

                if ($validator->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                $validated = $validator->validated();

                $exists = Country::where('nama', $validated['nama'])
                    ->orWhere('kode', $validated['kode'])
                    ->orWhere('nomor', $validated['nomor'])
                    ->exists();

                if ($exists) {
                    $failed[] = "Row " . ($index + 1) . ": Country with same name, code or number already exists";
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $country = Country::create($validated);
                    \DB::commit();
                    $inserted[] = $country;
                } catch (\Exception $e) {
                    \DB::rollBack();
                    $failed[] = "Row " . ($index + 1) . ": Save failed - " . $e->getMessage();
                }
            }

            $message = count($inserted) > 0 ? 'Batch data processed' : 'No valid data to insert';
            return Formatter::ApiResponse(200, $message, compact('inserted', 'failed'));
        } catch (\Exception $e) {
            return Formatter::ApiResponse(500, 'File processing failed', null, [$e->getMessage()]);
        }
    }
}
