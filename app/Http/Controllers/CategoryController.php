<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        $categoryQuery = Category::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $categoryQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ['nama', 'status', 'created_at'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $categoryQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $categories = $categoryQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Category list retrieved', $categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:categories,nama',
            'status' => 'required|in:TAYANG,TIDAK_TAYANG',
            'ikon' => 'required|string',
            'tipe_ikon' => 'required|string',
            'nama_ikon' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newCategory = Category::create($validated);

        return Formatter::ApiResponse(200, 'Category added', Category::find($newCategory->id));
    }

    public function show(Category $category)
    {
        return Formatter::ApiResponse(200, 'Category found', $category);
    }

    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:categories,nama,' . $category->id,
            'status' => 'sometimes|required|in:TAYANG,TIDAK_TAYANG',
            'ikon' => 'sometimes|required|string',
            'tipe_ikon' => 'sometimes|required|string',
            'nama_ikon' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $category->update($validated);

        return Formatter::ApiResponse(200, 'Category updated', Category::find($category->id));
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return Formatter::ApiResponse(200, 'Category removed');
    }
}
