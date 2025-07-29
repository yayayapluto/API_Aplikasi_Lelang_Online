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

        $validColumns = ["nama"];
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
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newProvince = Province::create($validated);

        return Formatter::ApiResponse(200, "Province added", Province::find($newProvince->id));
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
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $province->update($validated);

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
}
