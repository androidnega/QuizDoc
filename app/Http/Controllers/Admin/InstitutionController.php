<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Institution;
use App\Models\Faculty;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    use InteractsWithAdminSession;

    /**
     * List all institutions (Super Admin only). Assign examiners via User management.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $institutions = Institution::withCount('users')->orderBy('name')->get();
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), "doesn't exist")) {
                return redirect()->route('dashboard')
                    ->with('error', 'The institutions table is missing. Please run: php artisan migrate');
            }
            throw $e;
        }
        return view('admin.institutions.index', compact('institutions'));
    }

    /**
     * Edit institution name and logo.
     */
    public function edit(Institution $institution): View
    {
        $institution->load(['faculties.departments']);
        return view('admin.institutions.edit', compact('institution'));
    }

    /**
     * Update institution name and/or logo. Logo is stored on server in storage/logo (not auto-deleted).
     */
    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        $institution->name = trim($request->name);
        $institution->region = $request->filled('region') ? trim($request->region) : null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $filename = $institution->id . '_' . time() . '.' . strtolower($ext);
            $path = 'logo/' . $filename;
            Storage::disk('public')->put($path, $file->get());
            if ($institution->logo && !str_starts_with($institution->logo, 'http')) {
                Storage::disk('public')->delete($institution->logo);
            }
            $institution->logo = $path;
        }

        $institution->save();
        return redirect()->route('dashboard.institutions.index')->with('success', 'Institution updated.');
    }
}
