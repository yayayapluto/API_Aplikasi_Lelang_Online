<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VillageController extends Controller
{
    public function index()
    {
        $villageQuery = Village::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $villageQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ['nama', 'subdistrict_id'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $villageQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $villages = $villageQuery->with('subdistrict')->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Village list retrieved', $villages);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:villages,nama',
            'subdistrict_id' => 'required|exists:subdistricts,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newVillage = Village::create($validated);

        return Formatter::ApiResponse(200, 'Village added', Village::with('subdistrict')->find($newVillage->id));
    }

    public function show(Village $village)
    {
        $village = $village->load('subdistrict');
        return Formatter::ApiResponse(200, 'Village found', $village);
    }

    public function update(Request $request, Village $village)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:villages,nama,' . $village->id,
            'subdistrict_id' => 'sometimes|required|exists:subdistricts,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $village->update($validated);

        return Formatter::ApiResponse(200, 'Village updated', Village::with('subdistrict')->find($village->id));
    }

    public function destroy(Village $village)
    {
        $village->delete();
        return Formatter::ApiResponse(200, 'Village removed');
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
                    'subdistrict_id' => 'required|exists:subdistricts,id',
                ]);
                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                $validated = $v->validated();
                if (Village::where('nama', $validated['nama'])->where('subdistrict_id', $validated['subdistrict_id'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Village already exists in this subdistrict";
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $village = Village::create($validated);
                    \DB::commit();
                    $inserted[] = $village;
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
