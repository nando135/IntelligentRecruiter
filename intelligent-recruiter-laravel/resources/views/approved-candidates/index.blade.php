@extends('layouts.app')

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.75rem;gap:1rem;">
    <div>
        <h1 style="font-size:1.35rem;font-weight:700;color:#0f172a;letter-spacing:-.02em;">Approved Candidates</h1>
        <p style="font-size:13px;color:#94a3b8;margin-top:3px;">Candidates approved by HR appear here.</p>
    </div>
    <a href="{{ route('leaderboard.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#fff;border:1px solid #e4e9f0;border-radius:8px;font-size:13px;font-weight:500;color:#475569;text-decoration:none;white-space:nowrap;transition:border-color .12s,color .12s;" onmouseover="this.style.borderColor='#cbd5e1';this.style.color='#0f172a'" onmouseout="this.style.borderColor='#e4e9f0';this.style.color='#475569'">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="9" width="3" height="5" rx="1"/><rect x="6.5" y="5" width="3" height="9" rx="1"/><rect x="11" y="2" width="3" height="12" rx="1"/></svg>
        Leaderboard
    </a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('approved-candidates.index') }}" style="display:flex;align-items:flex-end;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap;">
    <div style="flex:1;min-width:180px;">
        <label style="display:block;font-size:11px;font-weight:600;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase;margin-bottom:5px;">Search</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="Name or email…"
            style="width:100%;padding:8px 12px;border:1px solid #e4e9f0;border-radius:8px;font-size:13px;color:#0f172a;background:#fff;outline:none;transition:border-color .15s;"
            onfocus="this.style.borderColor='#1d4ed8'" onblur="this.style.borderColor='#e4e9f0'">
    </div>

    <div style="min-width:160px;">
        <label style="display:block;font-size:11px;font-weight:600;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase;margin-bottom:5px;">Category</label>
        <select name="category" style="width:100%;padding:8px 12px;border:1px solid #e4e9f0;border-radius:8px;font-size:13px;color:#0f172a;background:#fff;outline:none;">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" {{ $selectedCategory === $category ? 'selected' : '' }}>{{ $category }}</option>
            @endforeach
        </select>
    </div>

    <div style="display:flex;gap:6px;padding-bottom:1px;">
        <button type="submit" style="padding:8px 16px;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Filter</button>
        <a href="{{ route('approved-candidates.index') }}" style="padding:8px 14px;background:#fff;border:1px solid #e4e9f0;border-radius:8px;font-size:13px;color:#64748b;text-decoration:none;font-weight:500;">Reset</a>
    </div>
</form>

{{-- Table --}}
<div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid #e4e9f0;">
                <th style="text-align:left;padding:11px 16px;font-size:10.5px;font-weight:600;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;">Candidate</th>
                <th style="text-align:left;padding:11px 16px;font-size:10.5px;font-weight:600;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;">Email</th>
                <th style="text-align:left;padding:11px 16px;font-size:10.5px;font-weight:600;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;">Category</th>
                <th style="text-align:left;padding:11px 16px;font-size:10.5px;font-weight:600;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;">Rank</th>
                <th style="text-align:left;padding:11px 16px;font-size:10.5px;font-weight:600;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;">Score</th>
                <th style="text-align:left;padding:11px 16px;font-size:10.5px;font-weight:600;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;">Match</th>
                <th style="text-align:left;padding:11px 16px;font-size:10.5px;font-weight:600;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;">Approved</th>
                <th style="padding:11px 16px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($approvedCandidates as $ac)
            <tr style="border-bottom:1px solid #f1f5f9;transition:background .1s;" onmouseover="this.style.background='#f8f9fc'" onmouseout="this.style.background='transparent'">
                <td style="padding:13px 16px;font-weight:600;color:#0f172a;">{{ $ac->full_name_snapshot ?? 'Unknown' }}</td>
                <td style="padding:13px 16px;color:#64748b;">{{ $ac->email_snapshot ?? '-' }}</td>
                <td style="padding:13px 16px;">
                    @if($ac->candidate_category_snapshot)
                        <span style="background:#f1f5f9;color:#374151;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500;">{{ $ac->candidate_category_snapshot }}</span>
                    @else -
                    @endif
                </td>
                <td style="padding:13px 16px;color:#0f172a;font-weight:500;">{{ $ac->leaderboard_rank_snapshot ? '#'.$ac->leaderboard_rank_snapshot : '-' }}</td>
                <td style="padding:13px 16px;color:#0f172a;">{{ $ac->leaderboard_score_snapshot !== null ? number_format($ac->leaderboard_score_snapshot, 1) : '-' }}</td>
                <td style="padding:13px 16px;color:#0f172a;">{{ $ac->match_percentage_snapshot !== null ? number_format($ac->match_percentage_snapshot, 1).'%' : '-' }}</td>
                <td style="padding:13px 16px;color:#94a3b8;font-size:12px;">{{ $ac->approved_at ? $ac->approved_at->format('d M Y, h:i A') : '-' }}</td>
                <td style="padding:13px 16px;text-align:right;">
                    @if($ac->candidate)
                        <a href="{{ route('candidates.show', $ac->candidate) }}" style="font-size:12.5px;font-weight:600;color:#1d4ed8;text-decoration:none;">View →</a>
                    @else
                        <span style="font-size:12px;color:#cbd5e1;">Deleted</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="padding:3rem;text-align:center;color:#94a3b8;font-size:13px;">No approved candidates yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($approvedCandidates->hasPages())
<div style="margin-top:1.25rem;">{{ $approvedCandidates->links() }}</div>
@endif

@endsection
