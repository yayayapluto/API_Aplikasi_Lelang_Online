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
}
