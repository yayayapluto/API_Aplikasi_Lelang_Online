<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fileQuery = File::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $fileQuery->where('file_url', 'LIKE', $searchTerm);
        }

        $validColumns = ['file_url'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $fileQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $files = $fileQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, 'File list retrieved', $files);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $file = $request->file('file');

        // Generate a unique filename to avoid collisions
        $filename = 'uploads/' . uniqid() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

        // Store the file in the public disk (storage/app/public)
        $path = $file->storeAs('public/' . $filename);

        // Save only the relative path: e.g., storage/uploads/abc_def.jpg
        // This is automatically accessible via symbolic link
        $fileUrl = 'storage/' . $filename;

        $storedFile = File::create([
            'file_url' => $fileUrl,
        ]);

        return Formatter::ApiResponse(200, 'File uploaded successfully', File::find($storedFile->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(File $file)
    {
        return Formatter::ApiResponse(200, 'File found', $file);
    }

    /**
     * Update the specified resource in storage.
     */
//    public function update(Request $request, File $file)
//    {
//        $validator = Validator::make($request->all(), [
//            'file' => 'required|file|max:10240',
//        ]);
//
//        if ($validator->fails()) {
//            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
//        }
//
//        $newFile = $request->file('file');
//
//        // Delete old file if exists
//        $oldPath = str_replace('storage/', 'public/', $file->file_url);
//        if (Storage::exists($oldPath)) {
//            Storage::delete($oldPath);
//        }
//
//        // Save new file
//        $filename = 'uploads/' . uniqid() . '_' . str_replace(' ', '_', $newFile->getClientOriginalName());
//        $newPath = $newFile->storeAs('public/' . $filename);
//
//        $file->update([
//            'file_url' => 'storage/' . $filename,
//        ]);
//
//        return Formatter::ApiResponse(200, 'File updated successfully', File::find($file->id));
//    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(File $file)
    {
        // Remove the physical file
        $path = str_replace('storage/', 'public/', $file->file_url);
        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        $file->delete();

        return Formatter::ApiResponse(200, 'File deleted successfully');
    }
}
