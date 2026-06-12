@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold">Edit Email Template</h1>
    <p class="text-slate-600">Update the confirmation email template.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('email-templates.update', $emailTemplate) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Template Name</label>
            <input type="text" name="name" value="{{ old('name', $emailTemplate->name) }}"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email Subject</label>
            <input type="text" name="subject" value="{{ old('subject', $emailTemplate->subject) }}"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email Body</label>
            <textarea name="body" rows="14"
                      class="w-full border border-slate-300 rounded-lg px-3 py-2">{{ old('body', $emailTemplate->body) }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $emailTemplate->is_active) ? 'checked' : '' }}>
            <label for="is_active" class="text-sm text-slate-700">Set as active template</label>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-700 text-white px-5 py-3 rounded-lg font-medium">
                Update Template
            </button>

            <a href="{{ route('email-templates.index') }}" class="bg-slate-200 text-slate-700 px-5 py-3 rounded-lg font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
