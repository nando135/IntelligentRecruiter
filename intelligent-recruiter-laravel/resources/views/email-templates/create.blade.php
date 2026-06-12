@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold">Create Email Template</h1>
    <p class="text-slate-600">This template will be used when HR approves candidates.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('email-templates.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Template Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2"
                   placeholder="Approved Candidate Confirmation">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email Subject</label>
            <input type="text" name="subject" value="{{ old('subject', 'Application Update - Next Stage Confirmation') }}"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email Body</label>
            <textarea name="body" rows="14"
                      class="w-full border border-slate-300 rounded-lg px-3 py-2">{{ old('body', "Dear {{candidate_name}},\n\nCongratulations. You have been selected to proceed to the next stage of our recruitment process.\n\nYour application has been reviewed and approved by our HR team.\n\nCategory: {{candidate_category}}\nRank: {{leaderboard_rank}}\nMatch Score: {{match_percentage}}%\n\nOur team will contact you with the next steps.\n\nBest regards,\nHR Team") }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" id="is_active" checked>
            <label for="is_active" class="text-sm text-slate-700">Set as active template</label>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-700 text-white px-5 py-3 rounded-lg font-medium">
                Save Template
            </button>

            <a href="{{ route('email-templates.index') }}" class="bg-slate-200 text-slate-700 px-5 py-3 rounded-lg font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
