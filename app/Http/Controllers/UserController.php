<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\User;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = User::query();

        if (request()->filled('search')) {
            $search = '%' . request()->search . '%';
            $query->where('nama_lengkap', 'LIKE', $search)
                ->orWhere('nik', 'LIKE', $search)
                ->orWhere('email', 'LIKE', $search);
        }

        $validColumns = ['nama_lengkap', 'nik', 'email', 'kewarganegaraan', 'jenis_kelamin', 'created_at'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $users = $query->with([
            'jobType',
            'province',
            'country',
            'city',
            'subdistrict',
            'village',
        ])->simplePaginate($size);

        return Formatter::ApiResponse(200, 'User list retrieved', $users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_type_id' => 'required|exists:job_types,id',
            'province_id' => 'nullable|exists:provinces,id',
            'kewarganegaraan' => 'required|in:WNA,WNI',
            'nik' => 'required|string|unique:users,nik',
            'nama_lengkap' => 'required|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'jenis_kelamin' => 'required|in:PRIA,WANITA',
            'city_id' => 'nullable|exists:cities,id',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'nomor_telepon' => 'required|string',
            'alamat' => 'required|string',
            'file_ktp' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'email' => 'required|email|unique:users,email',
            'village_id' => 'nullable|exists:villages,id',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        // Handle file upload
        $file = $request->file('file_ktp');
        $path = $file->store('files', 'public'); // e.g. files/abc123.pdf

        // Save file record in database
        $fileRecord = File::query()->create([
            'file_url' => 'storage/' . $path, // accessible URL
        ]);

        $validated['file_ktp'] = $fileRecord->file_url;
        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);

        return Formatter::ApiResponse(200, 'User added', User::with([
            'jobType',
            'province',
            'country',
            'city',
            'subdistrict',
            'village',
        ])->find($user->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user = $user->load([
            'jobType',
            'province',
            'country',
            'city',
            'subdistrict',
            'village',
        ]);
        return Formatter::ApiResponse(200, 'User found', $user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'job_type_id' => 'sometimes|required|exists:job_types,id',
            'province_id' => 'sometimes|required|exists:provinces,id',
            'kewarganegaraan' => 'sometimes|required|in:WNA,WNI',
            'nik' => 'sometimes|required|string|unique:users,nik,' . $user->id,
            'nama_lengkap' => 'sometimes|required|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'jenis_kelamin' => 'sometimes|required|in:PRIA,WANITA',
            'city_id' => 'sometimes|required|exists:cities,id',
            'tempat_lahir' => 'sometimes|required|string',
            'tanggal_lahir' => 'sometimes|required|date',
            'nomor_telepon' => 'sometimes|required|string',
            'alamat' => 'sometimes|required|string',
            'file_ktp' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'subdistrict_id' => 'sometimes|required|exists:subdistricts,id',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'village_id' => 'sometimes|required|exists:villages,id',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        // Handle file replacement
        if ($request->hasFile('file_ktp')) {
            // Delete old file from storage and DB
            if ($user->file) {
                $oldPath = str_replace('storage/', 'public/', $user->file->file_url);
                if (Storage::exists($oldPath)) {
                    Storage::delete($oldPath);
                }
                $user->file->delete();
            }

            // Store new file
            $file = $request->file('file_ktp');
            $path = $file->store('files', 'public');

            $fileRecord = File::query()->create([
                'file_url' => 'storage/' . $path,
            ]);

            $validated['file_ktp'] = $fileRecord->file_url;

        }

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);

        return Formatter::ApiResponse(200, 'User updated', User::with([
            'jobType',
            'province',
            'country',
            'city',
            'subdistrict',
            'village',
        ])->find($user->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Delete associated file
        if ($user->file) {
            $path = str_replace('storage/', 'public/', $user->file->file_url);
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
            $user->file->delete();
        }

        $user->delete();

        return Formatter::ApiResponse(200, 'User removed');
    }
}
