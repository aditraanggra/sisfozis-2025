<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;

class SuratMasukController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $suratMasuk = SuratMasuk::with('category')
            ->orderBy('date_agenda', 'desc')
            ->orderByRaw("CAST(REGEXP_REPLACE(no_agenda, '[^0-9]', '', 'g') AS INTEGER) DESC")
            ->orderBy('no_agenda', 'desc')
            ->paginate($perPage);

        return response()->json($suratMasuk);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'no_agenda' => ['required', 'string'],
            'date_agenda' => ['required', 'date'],
            'date_letter' => ['required', 'date'],
            'sender' => ['required', 'string'],
            'no_letter' => ['nullable', 'string'],
            'subject' => ['nullable', 'string'],
            'contact' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'district' => ['nullable', 'string'],
            'village' => ['nullable', 'string'],
            'file' => ['nullable', 'string'],
            'dept_disposition' => ['nullable', 'string'],
            'desc_disposition' => ['nullable', 'string'],
        ]);

        $suratMasuk = SuratMasuk::create($validated)->load('category');

        return response()->json([
            'message' => 'Surat masuk created',
            'data' => $suratMasuk,
        ], 201);
    }

    public function show(SuratMasuk $suratMasuk)
    {
        return response()->json($suratMasuk->load('category'));
    }

    public function update(Request $request, SuratMasuk $suratMasuk)
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'no_agenda' => ['sometimes', 'required', 'string'],
            'date_agenda' => ['sometimes', 'required', 'date'],
            'date_letter' => ['sometimes', 'required', 'date'],
            'sender' => ['sometimes', 'required', 'string'],
            'no_letter' => ['sometimes', 'nullable', 'string'],
            'subject' => ['sometimes', 'nullable', 'string'],
            'contact' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string'],
            'district' => ['sometimes', 'nullable', 'string'],
            'village' => ['sometimes', 'nullable', 'string'],
            'file' => ['sometimes', 'nullable', 'string'],
            'dept_disposition' => ['sometimes', 'nullable', 'string'],
            'desc_disposition' => ['sometimes', 'nullable', 'string'],
        ]);

        $suratMasuk->update($validated);

        return response()->json([
            'message' => 'Surat masuk updated',
            'data' => $suratMasuk->load('category'),
        ]);
    }

    public function destroy(SuratMasuk $suratMasuk)
    {
        $suratMasuk->delete();

        return response()->noContent();
    }
}
