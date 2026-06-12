@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold">Email Templates</h1>
        <p class="text-slate-600">Manage the confirmation email sent to approved candidates.</p>
    </div>

    <a href="{{ route('email-templates.create') }}" class="bg-blue-700 text-white px-5 py-3 rounded-lg font-medium">
        Create Template
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <h2 class="font-bold mb-2">Available Variables</h2>

    @verbatim
<div class="flex flex-wrap gap-2 text-xs">
    <span class="bg-slate-100 px-2 py-1 rounded">{{candidate_name}}</span>
    <span class="bg-slate-100 px-2 py-1 rounded">{{candidate_email}}</span>
    <span class="bg-slate-100 px-2 py-1 rounded">{{candidate_category}}</span>
    <span class="bg-slate-100 px-2 py-1 rounded">{{leaderboard_rank}}</span>
    <span class="bg-slate-100 px-2 py-1 rounded">{{leaderboard_score}}</span>
    <span class="bg-slate-100 px-2 py-1 rounded">{{match_percentage}}</span>
    <span class="bg-slate-100 px-2 py-1 rounded">{{current_job_title}}</span>
    <span class="bg-slate-100 px-2 py-1 rounded">{{latest_company}}</span>
    <span class="bg-slate-100 px-2 py-1 rounded">{{company_name}}</span>
</div>
@endverbatim
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="text-left px-4 py-3">Name</th>
                <th class="text-left px-4 py-3">Subject</th>
                <th class="text-left px-4 py-3">Body Preview</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Updated</th>
                <th class="text-left px-4 py-3">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($templates as $template)
                <tr class="border-t border-slate-200 hover:bg-slate-50 align-top">
                    <td class="px-4 py-3 font-medium">
                        {{ $template->name }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $template->subject }}
                    </td>

                    <td class="px-4 py-3 max-w-xl text-slate-600">
                        {{ \Illuminate\Support\Str::limit($template->body, 140) }}
                    </td>

                    <td class="px-4 py-3">
                        @if($template->is_active)
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">
                                active
                            </span>
                        @else
                            <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full text-xs">
                                inactive
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        {{ $template->updated_at ? $template->updated_at->format('d M Y, h:i A') : '-' }}
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('email-templates.edit', $template) }}" class="text-blue-700 font-medium">
                                Edit
                            </a>

                            @if(! $template->is_active)
                                <form method="POST" action="{{ route('email-templates.set-active', $template) }}">
                                    @csrf
                                    <button type="submit" class="text-green-700 font-medium">
                                        Set Active
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                        No templates yet. Create one, or the system will automatically use a default template.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $templates->links() }}</div>
@endsection
