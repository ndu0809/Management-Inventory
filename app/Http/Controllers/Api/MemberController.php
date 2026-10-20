<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index()
    {
        return response()->json(Member::latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        $member = Member::create([
            'name' => $validated['name'],
            'position' => $validated['position'] ?? null,
            'department' => $validated['department'] ?? null,
        ]);

        return response()->json([
            'message' => 'Anggota berhasil ditambahkan.',
            'data' => $member,
        ], 201);
    }

    public function show(Member $member)
    {
        return response()->json($member);
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|nullable|string|max:255',
            'department' => 'sometimes|nullable|string|max:255',
        ]);

        $member->update($validated);

        return response()->json([
            'message' => 'Anggota berhasil diperbarui.',
            'data' => $member->fresh(),
        ]);
    }

    public function destroy(Member $member)
    {
        $member->delete();

        return response()->json([
            'message' => 'Anggota berhasil dihapus.',
        ]);
    }
}
