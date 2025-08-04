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

        $validColumns = ['nama', 'status', "tipe_ikon", 'created_at'];
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
                    'nama' => 'required|string|unique:categories,nama',
                    'status' => 'sometimes|in:TAYANG,TIDAK_TAYANG',
                    'ikon' => 'required|string',
                    'tipe_ikon' => 'required|string',
                    'nama_ikon' => 'required|string',
                ]);
                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $cat = Category::create($v->validated());
                    \DB::commit();
                    $inserted[] = $cat;
                } catch (\Exception $e) {
                    \DB::rollBack();
                    $failed[] = "Row " . ($index + 1) . ": Save failed " . $e->getMessage();
                }
            }

            return Formatter::ApiResponse(200, count($inserted) ? 'Batch processed' : 'No data inserted', compact('inserted', 'failed'));
        } catch (\Exception $e) {
            return Formatter::ApiResponse(500, 'Processing failed', null, [$e->getMessage()]);
        }
    }
}
