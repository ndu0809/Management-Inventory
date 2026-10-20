<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Item::with('member')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:items,sku'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                Rule::in(['Baik', 'Rusak', 'Tidak dipakai', 'Dilelang']),
            ],
            'assigned' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'specification' => ['nullable', 'string'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
        ]);

        $item = Item::create($validated);

        return response()->json([
            'message' => 'Barang berhasil ditambahkan.',
            'data' => $item->load('member'),
        ], 201);
    }

    public function show(Item $item)
    {
        return response()->json([
            'data' => $item->load('member'),
        ]);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('items', 'sku')->ignore($item->id),
            ],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                Rule::in(['Baik', 'Rusak', 'Tidak dipakai', 'Dilelang']),
            ],
            'assigned' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'specification' => ['nullable', 'string'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Barang berhasil diperbarui.',
            'data' => $item->fresh()->load('member'),
        ]);
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return response()->json([
            'message' => 'Barang berhasil dihapus.',
        ]);
    }
}
