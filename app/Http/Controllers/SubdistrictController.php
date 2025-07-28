<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubdistrictController extends Controller
{
    public function index()
    {
        $subdistrictQuery = Subdistrict::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $subdistrictQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ['nama', 'city_id'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $subdistrictQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $subdistricts = $subdistrictQuery->with('city')->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Subdistrict list retrieved', $subdistricts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:subdistricts,nama,NULL,id,city_id,' . $request->city_id,
            'city_id' => 'required|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newSubdistrict = Subdistrict::create($validated);

        return Formatter::ApiResponse(200, 'Subdistrict added', Subdistrict::with('city')->find($newSubdistrict->id));
    }

    public function show(Subdistrict $subdistrict)
    {
        $subdistrict = $subdistrict->load('city');
        return Formatter::ApiResponse(200, 'Subdistrict found', $subdistrict);
    }

    public function update(Request $request, Subdistrict $subdistrict)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:subdistricts,nama,' . $subdistrict->id . ',id,city_id,' . $subdistrict->city_id,
            'city_id' => 'sometimes|required|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $subdistrict->update($validated);

        return Formatter::ApiResponse(200, 'Subdistrict updated', Subdistrict::with('city')->find($subdistrict->id));
    }

    public function destroy(Subdistrict $subdistrict)
    {
        $subdistrict->delete();
        return Formatter::ApiResponse(200, 'Subdistrict removed');
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
                    'city_id' => 'required|exists:cities,id',
                ]);
                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                $validated = $v->validated();
                if (Subdistrict::where('nama', $validated['nama'])->where('city_id', $validated['city_id'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Subdistrict already exists in this city";
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $sub = Subdistrict::create($validated);
                    \DB::commit();
                    $inserted[] = $sub;
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
