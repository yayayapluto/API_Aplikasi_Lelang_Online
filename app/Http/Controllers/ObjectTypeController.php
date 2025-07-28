<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\ObjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ObjectTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $objectTypeQuery = ObjectType::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $objectTypeQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ['nama'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $objectTypeQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $objectTypes = $objectTypeQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Object type list retrieved', $objectTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:object_types,nama',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newObjectType = ObjectType::create($validated);

        return Formatter::ApiResponse(200, 'Object type added', ObjectType::find($newObjectType->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(ObjectType $objectType)
    {
        return Formatter::ApiResponse(200, 'Object type found', $objectType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ObjectType $objectType)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:object_types,nama,' . $objectType->id,
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $objectType->update($validated);

        return Formatter::ApiResponse(200, 'Object type updated', ObjectType::find($objectType->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ObjectType $objectType)
    {
        $objectType->delete();
        return Formatter::ApiResponse(200, 'Object type removed');
    }
}
