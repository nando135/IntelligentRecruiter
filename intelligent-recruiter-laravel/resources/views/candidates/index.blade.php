@extends('layouts.app')

@section('content')
<style>
.topbar { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; }
.topbar h1 { font-size: 22px; font-weight: 500; margin-bottom: 4px; }
.topbar p { font-size: 13px; color: #64748b; }
.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: #185FA5; color: #fff; border: none; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; }
.btn-primary:hover { background: #0C447C; color: #fff; }
.filter-panel { background: #fff; border: 0.5px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
.filter-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; align-items: end; }
.field label { display: block; font-size: 11px; font-weight: 500; color: #64748b; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 5px; }
.field input, .field select { width: 100%; background: #f8fafc; border: 0.5px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; font-size: 13px; color: #0f172a; outline: none; }
.field input:focus, .field select:focus { border-color: #185FA5; }
.filter-actions { display: flex; gap: 6px; }
.btn-sm { padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; }
.btn-sm-apply { background: #185FA5; color: #fff; border: none; }
.btn-sm-apply:hover { background: #0C447C; }
.btn-sm-reset { background: #f1f5f9; color: #334155; border: 0.5px solid #e2e8f0; text-decoration: none; }
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.875rem; }
.btn-approve { display: inline-flex; align-items: center; gap: 6px; background: transparent; border: 0.5px solid #d1d5db; padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; color: #3B6D11; cursor: pointer; }
.btn-approve:hover { background: #EAF3DE; }
.toolbar-hint { font-size: 12px; color: #94a3b8; }
.table-wrap { background: #fff; border: 0.5px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.table-wrap table { width: 100%; border-collapse: collapse; font-size: 13px; }
.table-wrap thead tr { background: #f8fafc; }
.table-wrap thead th { padding: 9px 10px; text-align: left; font-size: 10px; font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase; color: #94a3b8; border-bottom: 0.5px solid #e2e8f0; white-space: nowrap; }
.table-wrap tbody tr { border-bottom: 0.5px solid #f1f5f9; }
.table-wrap tbody tr:last-child { border-bottom: none; }
.table-wrap tbody tr:hover { background: #f8fafc; }
.table-wrap tbody td { padding: 10px 10px; vertical-align: middle; }
.badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 500; white-space: nowrap; }
.badge-blue  { background: #E6F1FB; color: #0C447C; }
.badge-green { background: #EAF3DE; color: #27500A; }
.badge-red   { background: #FCEBEB; color: #791F1F; }
.badge-amber { background: #FAEEDA; color: #633806; }
.badge-gray  { background: #f1f5f9; color: #64748b; border: 0.5px solid #e2e8f0; }
.match-wrap { display: flex; align-items: center; gap: 6px; }
.match-bar-bg { flex: 1; height: 4px; background: #e2e8f0; border-radius: 2px; min-width: 32px; }
.match-bar-fill { height: 4px; border-radius: 2px; background: #185FA5; }
.match-pct { font-size: 12px; color: #64748b; min-width: 30px; }
.act-link { color: #185FA5; font-weight: 500; text-decoration: none; font-size: 13px; }
.act-link:hover { text-decoration: underline; }
.act-approve-btn { color: #3B6D11; font-weight: 500; font-size: 13px; border: none; background: none; cursor: pointer; padding: 0; }
.act-approve-btn:hover { text-decoration: underline; }
.act-done { font-size: 12px; color: #94a3b8; }
.pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-top: 0.5px solid #e2e8f0; background: #f8fafc; }
.pagination-info { font-size: 12px; color: #64748b; }
</style>

<div class="topbar">
    <div>
        <h1>Candidate database</h1>
        <p>All scanned CVs appear here. HR can approve one or multiple candidates.</p>
    </div>
    <a href="{{ route('candidates.upload') }}" class="btn-primary">
        ↑ Upload new CV
    </a>
</div>

{{-- Filter panel --}}
<div class="filter-panel">
    <form method="GET" action="{{ route('candidates.index') }}" class="filter-grid">
        <div class="field">
            <label>Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Name, email, role, company…">
        </div>
        <div class="field">
            <label>Category</label>
            <select name="category">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ $selectedCategory === $category ? 'selected' : '' }}>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Approval status</label>
            <select name="approval_status">
                <option value="">All statuses</option>
                @foreach($approvalStatuses as $status)
                    <option value="{{ $status }}" {{ $selectedApprovalStatus === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-sm btn-sm-apply">Filter</button>
            <a href="{{ route('candidates.index') }}" class="btn-sm btn-sm-reset">Reset</a>
        </div>
    </form>
</div>

{{-- Hidden bulk form --}}
<form id="bulk-approve-form" method="POST" action="{{ route('candidates.bulk-approve') }}">
    @csrf
    <input type="hidden" name="approval_note" value="Bulk approved by HR from candidate database.">
</form>

{{-- Toolbar --}}
<div class="toolbar">
    <div>
        <button type="submit" form="bulk-approve-form" class="btn-approve"
                onclick="return confirm('Approve selected candidates?')">
            ✓ Approve selected
        </button>
    </div>
    <span class="toolbar-hint">Tick candidates, then click approve selected.</span>
</div>

{{-- Table --}}
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:36px"><input type="checkbox" id="select-all-candidates"></th>
                <th>Name</th>
                <th>Email</th>
                <th>Current role</th>
                <th>Category</th>
                <th>Rank</th>
                <th>Score</th>
                <th>Match</th>
                <th>Approval</th>
                <th>CV status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($candidates as $candidate)
            <tr>
                <td>
                    @if($candidate->approval_status !== 'approved')
                        <input type="checkbox" class="candidate-checkbox"
                               name="candidate_ids[]" value="{{ $candidate->id }}"
                               form="bulk-approve-form">
                    @else
                        <span style="color:#cbd5e1">—</span>
                    @endif
                </td>
                <td style="font-weight:500">{{ $candidate->full_name ?? 'Unknown' }}</td>
                <td style="color:#64748b">{{ $candidate->email ?? '-' }}</td>
                <td style="color:#64748b">{{ $candidate->current_job_title ?? '-' }}</td>
                <td>
                    @if($candidate->candidate_category)
                        <span class="badge badge-blue">{{ $candidate->candidate_category }}</span>
                    @else
                        <span style="color:#cbd5e1">—</span>
                    @endif
                </td>
                <td style="color:#64748b">{{ $candidate->leaderboard_rank ? '#'.$candidate->leaderboard_rank : '—' }}</td>
                <td style="font-weight:500">{{ $candidate->leaderboard_score !== null ? number_format($candidate->leaderboard_score, 2) : '—' }}</td>
                <td>
                    @if($candidate->match_percentage !== null)
                        <div class="match-wrap">
                            <div class="match-bar-bg">
                                <div class="match-bar-fill" style="width:{{ min(100, $candidate->match_percentage) }}%"></div>
                            </div>
                            <span class="match-pct">{{ number_format($candidate->match_percentage, 0) }}%</span>
                        </div>
                    @else
                        <span style="color:#cbd5e1">—</span>
                    @endif
                </td>
                <td>
                    @if($candidate->approval_status === 'approved')
                        <span class="badge badge-green">approved</span>
                    @elseif($candidate->approval_status === 'rejected')
                        <span class="badge badge-red">rejected</span>
                    @else
                        <span class="badge badge-gray">pending</span>
                    @endif
                </td>
                <td>
                    @if($candidate->parser_status === 'success')
                        <span class="badge badge-green">success</span>
                    @elseif($candidate->parser_status === 'partial')
                        <span class="badge badge-amber">partial</span>
                    @elseif($candidate->parser_status === 'failed')
                        <span class="badge badge-red">failed</span>
                    @else
                        <span class="badge badge-gray">unknown</span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:12px">
                        <a href="{{ route('candidates.show', $candidate) }}" class="act-link">View</a>
                        @if($candidate->approval_status !== 'approved')
                            <form method="POST" action="{{ route('candidates.approve', $candidate) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="approval_source" value="hr_preference">
                                <input type="hidden" name="approval_note" value="Approved by HR from candidate database.">
                                <button type="submit" class="act-approve-btn"
                                        onclick="return confirm('Approve this candidate?')">
                                    Approve
                                </button>
                            </form>
                        @else
                            <span class="act-done">Approved</span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="padding:3rem;text-align:center;color:#94a3b8">
                    No candidates yet. Upload a CV to begin.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination-bar">
        <span class="pagination-info">{{ $candidates->total() }} candidates</span>
        {{ $candidates->links() }}
    </div>
</div>

<script>
    const selectAll = document.getElementById('select-all-candidates');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.candidate-checkbox').forEach(cb => cb.checked = selectAll.checked);
        });
    }
</script>
@endsection
