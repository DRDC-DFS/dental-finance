<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TreatmentCategory;

class TreatmentCategoryController extends Controller
{
    public function index()
    {
        $categories = TreatmentCategory::orderBy('name')->get();
        return view('master.treatment_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('master.treatment_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:120|unique:treatment_categories,name',
        ]);

        TreatmentCategory::create([
            'name' => strtoupper($request->name),
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        if ($request->boolean('from_treatments')) {
            return redirect()->route('master.treatments.index')
                ->with('success', 'Kategori berhasil disimpan');
        }

        return redirect()->route('master.treatment_categories.index')
            ->with('success', 'Kategori berhasil disimpan');
    }

    public function edit(TreatmentCategory $category)
    {
        return view('master.treatment_categories.edit', compact('category'));
    }

    public function update(Request $request, TreatmentCategory $category)
    {
        $request->validate([
            'name' => 'required|max:120|unique:treatment_categories,name,' . $category->id,
        ]);

        $category->update([
            'name' => strtoupper($request->name),
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        if ($request->boolean('from_treatments')) {
            return redirect()->route('master.treatments.index')
                ->with('success', 'Kategori berhasil diupdate');
        }

        return redirect()->route('master.treatment_categories.index')
            ->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(Request $request, TreatmentCategory $category)
    {
        $usedCount = DB::table('treatments')
            ->where('category_id', $category->id)
            ->count();

        if ($usedCount > 0) {
            $message = 'Kategori tidak bisa dihapus karena masih dipakai pada treatment.';

            if ($request->boolean('from_treatments')) {
                return redirect()->route('master.treatments.index')
                    ->with('error', $message);
            }

            return redirect()->route('master.treatment_categories.index')
                ->with('error', $message);
        }

        $category->delete();

        if ($request->boolean('from_treatments')) {
            return redirect()->route('master.treatments.index')
                ->with('success', 'Kategori berhasil dihapus');
        }

        return redirect()->route('master.treatment_categories.index')
            ->with('success', 'Kategori berhasil dihapus');
    }
}