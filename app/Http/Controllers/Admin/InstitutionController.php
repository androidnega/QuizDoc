<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Institution;
use App\Models\Faculty;
use App\Services\CloudinaryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
     * Update institution name and/or logo. Logo uploads to Cloudinary.
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
            $url = CloudinaryService::uploadFromFile($request->file('logo'));
            if ($url) {
                $institution->logo = $url;
            } else {
                return redirect()->route('dashboard.institutions.edit', $institution)
                    ->with('error', 'Logo upload failed. Ensure Cloudinary is configured in Admin Settings.');
            }
        }

        $institution->save();
        return redirect()->route('dashboard.institutions.index')->with('success', 'Institution updated.');
    }
}
