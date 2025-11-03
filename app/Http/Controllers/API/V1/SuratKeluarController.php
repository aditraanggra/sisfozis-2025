<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;

class SuratKeluarController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        return response()->json(
            SuratKeluar::with('category')->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'date_letter' => ['required', 'date'],
            'to_letter' => ['required', 'string'],
            'no_letter' => ['required', 'string'],
            'subject' => ['nullable', 'string'],
            'file' => ['nullable', 'string'],
        ]);

        $suratKeluar = SuratKeluar::create($validated)->load('category');

        return response()->json([
            'message' => 'Surat keluar created',
            'data' => $suratKeluar,
        ], 201);
    }

    public function show(SuratKeluar $suratKeluar)
    {
        return response()->json($suratKeluar->load('category'));
    }

    public function update(Request $request, SuratKeluar $suratKeluar)
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'date_letter' => ['sometimes', 'required', 'date'],
            'to_letter' => ['sometimes', 'required', 'string'],
            'no_letter' => ['sometimes', 'required', 'string'],
            'subject' => ['sometimes', 'nullable', 'string'],
            'file' => ['sometimes', 'nullable', 'string'],
        ]);

        $suratKeluar->update($validated);

        return response()->json([
            'message' => 'Surat keluar updated',
            'data' => $suratKeluar->load('category'),
        ]);
    }

    public function destroy(SuratKeluar $suratKeluar)
    {
        $suratKeluar->delete();

        return response()->noContent();
    }
}
