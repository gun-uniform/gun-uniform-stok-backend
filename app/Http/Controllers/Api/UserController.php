<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::query()
                ->select('id', 'name', 'email', 'role', 'created_at')
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'gudang', 'pimpinan'])],
        ]);

        $user = User::create($validated);

        return response()->json([
            'message' => 'Akun berhasil ditambahkan.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }


public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user->id),
        ],
        'role' => [
            'required',
            Rule::in(['admin', 'gudang', 'pimpinan']),
        ],
        'password' => ['nullable', 'string', 'min:8'],
    ]);

    $user->name = $validated['name'];
    $user->email = $validated['email'];
    $user->role = $validated['role'];

    if (isset($validated['password']) && $validated['password'] !== '') {
        $user->password = $validated['password'];
    }

    $user->save();

    return response()->json([
        'message' => 'Akun berhasil diperbarui.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ],
    ]);
}

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Kamu tidak dapat menghapus akun sendiri.',
            ], 422);
        }

        if (
            $user->role === 'admin' &&
            User::where('role', 'admin')->count() <= 1
        ) {
            return response()->json([
                'message' => 'Akun admin terakhir tidak dapat dihapus.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Akun berhasil dihapus.',
        ]);
    }
}