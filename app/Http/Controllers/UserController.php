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

        $file = $request->file('file_ktp');
        $filename = 'uploads/' . uniqid() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
        $file->storeAs('public/' . $filename);
        $fileUrl = 'storage/' . $filename;

        $storedFile = File::create([
            'file_url' => $fileUrl,
        ]);

        $validated['file_id'] = $storedFile->id;
        unset($validated['file_ktp']);
        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);

        return Formatter::ApiResponse(200, 'User added', User::with([
            'jobType',
            'file',
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
            'province_id' => 'sometimes|exists:provinces,id',
            'kewarganegaraan' => 'sometimes|required|in:WNA,WNI',
            'nik' => 'sometimes|required|string|unique:users,nik,' . $user->id,
            'nama_lengkap' => 'sometimes|required|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'jenis_kelamin' => 'sometimes|required|in:PRIA,WANITA',
            'city_id' => 'sometimes|exists:cities,id',
            'tempat_lahir' => 'sometimes|required|string',
            'tanggal_lahir' => 'sometimes|required|date',
            'nomor_telepon' => 'sometimes|required|string',
            'alamat' => 'sometimes|required|string',
            'file_ktp' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'subdistrict_id' => 'sometimes|exists:subdistricts,id',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'village_id' => 'sometimes|exists:villages,id',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        if ($request->hasFile('file_ktp')) {
            $file = $request->file('file_ktp');
            $filename = 'uploads/' . uniqid() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $file->storeAs('public/' . $filename);

            $fileUrl = 'storage/' . $filename;
            $storedFile = File::create([
                'file_url' => $fileUrl,
            ]);

            if ($user->file) {
                $oldPath = str_replace('storage/', 'public/', $user->file->file_url);
                Storage::delete($oldPath);
                $user->file->delete();
            }

            $validated['file_id'] = $storedFile->id;
            unset($validated['file_ktp']);
        }

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);

        return Formatter::ApiResponse(200, 'User updated', User::with([
            'jobType',
            'file',
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
