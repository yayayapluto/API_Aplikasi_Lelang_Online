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
}
