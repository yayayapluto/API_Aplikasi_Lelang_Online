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
}
