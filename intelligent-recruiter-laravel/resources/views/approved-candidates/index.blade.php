@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold">Approved Candidates</h1>
        <p class="text-slate-600">Candidates approved by HR appear here.</p>
    </div>

    <a href="{{ route('leaderboard.index') }}" class="bg-blue-700 text-white px-5 py-3 rounded-lg font-medium">
        Back to Leaderboard
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <form method="GET" action="{{ route('approved-candidates.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Search</label>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Name or email"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
            <select name="category" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ $selectedCategory === $category ? 'selected' : '' }}>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email Status</label>
            <select name="email_status" class="w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="">All Statuses</option>
                @foreach($emailStatuses as $status)
                    <option value="{{ $status }}" {{ $selectedEmailStatus === $status ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg">
                Filter
            </button>

            <a href="{{ route('approved-candidates.index') }}" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-lg">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="text-left px-4 py-3">Candidate</th>
                <th class="text-left px-4 py-3">Email</th>
                <th class="text-left px-4 py-3">Category</th>
                <th class="text-left px-4 py-3">Rank Snapshot</th>
                <th class="text-left px-4 py-3">Score Snapshot</th>
                <th class="text-left px-4 py-3">Match Snapshot</th>
                <th class="text-left px-4 py-3">Approved At</th>
                <th class="text-left px-4 py-3">Email Status</th>
                <th class="text-left px-4 py-3">Template</th>
                <th class="text-left px-4 py-3">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($approvedCandidates as $approvedCandidate)
                <tr class="border-t border-slate-200 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">
                        {{ $approvedCandidate->full_name_snapshot ?? 'Unknown' }}
                    </td>

                    <td class="px-4 py-3 text-slate-500">
                        {{ $approvedCandidate->email_snapshot ?? '-' }}
                    </td>

                    <td class="px-4 py-3">
                        @if($approvedCandidate->candidate_category_snapshot)
                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs">
                                {{ $approvedCandidate->candidate_category_snapshot }}
                            </span>
                        @else
                            -
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        {{ $approvedCandidate->leaderboard_rank_snapshot ? '#' . $approvedCandidate->leaderboard_rank_snapshot : '-' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $approvedCandidate->leaderboard_score_snapshot !== null ? number_format($approvedCandidate->leaderboard_score_snapshot, 2) : '-' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $approvedCandidate->match_percentage_snapshot !== null ? number_format($approvedCandidate->match_percentage_snapshot, 2) . '%' : '-' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $approvedCandidate->approved_at ? $approvedCandidate->approved_at->format('d M Y, h:i A') : '-' }}
                    </td>

                    <td class="px-4 py-3">
                        @if($approvedCandidate->email_status === 'sent')
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">sent</span>
                        @elseif($approvedCandidate->email_status === 'queued')
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-xs">queued</span>
                        @elseif($approvedCandidate->email_status === 'failed')
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs">failed</span>
                        @else
                            <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full text-xs">not sent</span>
                        @endif

                        @if($approvedCandidate->email_error)
                            <div class="text-xs text-red-600 mt-1">
                                {{ $approvedCandidate->email_error }}
                            </div>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        {{ $approvedCandidate->emailTemplate->name ?? '-' }}
                    </td>

                    <td class="px-4 py-3">
                        @if($approvedCandidate->candidate)
                            <a href="{{ route('candidates.show', $approvedCandidate->candidate) }}" class="text-blue-700 font-medium">
                                View Candidate
                            </a>
                        @else
                            <span class="text-slate-400">Candidate deleted</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-6 py-8 text-center text-slate-500">
                        No approved candidates yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $approvedCandidates->links() }}</div>
@endsection
