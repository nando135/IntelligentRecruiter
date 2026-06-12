<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::latest()->paginate(10);

        return view('email-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('email-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $isActive = $request->boolean('is_active');

            if ($isActive) {
                EmailTemplate::query()->update(['is_active' => false]);
            }

            EmailTemplate::create([
                'name' => $validated['name'],
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'is_active' => $isActive,
            ]);
        });

        return redirect()
            ->route('email-templates.index')
            ->with('success', 'Email template created successfully.');
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        return view('email-templates.edit', compact('emailTemplate'));
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request, $emailTemplate) {
            $isActive = $request->boolean('is_active');

            if ($isActive) {
                EmailTemplate::where('id', '!=', $emailTemplate->id)
                    ->update(['is_active' => false]);
            }

            $emailTemplate->update([
                'name' => $validated['name'],
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'is_active' => $isActive,
            ]);
        });

        return redirect()
            ->route('email-templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    public function setActive(EmailTemplate $emailTemplate)
    {
        DB::transaction(function () use ($emailTemplate) {
            EmailTemplate::query()->update(['is_active' => false]);

            $emailTemplate->update([
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Active email template updated successfully.');
    }
}
