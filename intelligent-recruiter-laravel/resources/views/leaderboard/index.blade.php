@extends('layouts.app')

@section('content')
<style>
.topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem}
.topbar h1{font-size:22px;font-weight:500;color:#0f172a;margin-bottom:4px}
.topbar p{font-size:13px;color:#64748b}
.btn-primary{display:inline-flex;align-items:center;gap:6px;background:#185FA5;color:#fff;border:none;padding:9px 16px;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none}
.btn-primary:hover{background:#0C447C;color:#fff}
.filter-bar{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:1.5rem;align-items:center}
.filter-pill{padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;text-decoration:none;border:0.5px solid #e2e8f0;background:#fff;color:#334155;white-space:nowrap}
.filter-pill:hover{background:#f1f5f9}
.filter-pill.active{background:#185FA5;color:#fff;border-color:#185FA5}
.filter-pill.active-green{background:#EAF3DE;color:#27500A;border-color:#c6dea6}
.filter-pill.pending-green{background:#3B6D11;color:#fff;border-color:#3B6D11}
.filter-divider{width:1px;height:20px;background:#e2e8f0;margin:0 4px}
.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}
.btn-approve{display:inline-flex;align-items:center;gap:6px;background:transparent;border:0.5px solid #d1d5db;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:500;color:#3B6D11;cursor:pointer}
.btn-approve:hover{background:#EAF3DE}
.toolbar-hint{font-size:12px;color:#94a3b8}
.group-wrap{display:flex;flex-direction:column;gap:1.5rem}
.group-card{background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;overflow:hidden}
.group-header{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;background:#f8fafc;border-bottom:0.5px solid #e2e8f0}
.group-header h2{font-size:14px;font-weight:500;color:#0f172a}
.group-header span{font-size:12px;color:#94a3b8}
table{width:100%;border-collapse:collapse;font-size:13px}
thead tr{background:#f8fafc}
thead th{padding:9px 12px;text-align:left;font-size:10px;font-weight:500;letter-spacing:0.05em;text-transform:uppercase;color:#94a3b8;border-bottom:0.5px solid #e2e8f0;white-space:nowrap}
tbody tr.data-row{border-top:0.5px solid #f1f5f9}
tbody tr.data-row:hover{background:#f8fafc}
tbody tr.reason-row{background:#fafafa;border-top:0.5px dashed #f1f5f9}
tbody td{padding:10px 12px;vertical-align:middle}
td.rank-cell{font-size:15px;font-weight:500;color:#0f172a;width:48px}
.rank-1{color:#B87B17}
.rank-2{color:#888780}
.rank-3{color:#854F0B}
.candidate-name{font-weight:500;color:#0f172a;margin-bottom:2px}
.candidate-sub{font-size:11px;color:#94a3b8;margin-top:1px}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:500;white-space:nowrap}
.badge-blue{background:#E6F1FB;color:#0C447C}
.badge-green{background:#EAF3DE;color:#27500A}
.badge-red{background:#FCEBEB;color:#791F1F}
.badge-amber{background:#FAEEDA;color:#633806}
.badge-gray{background:#f1f5f9;color:#64748b;border:0.5px solid #e2e8f0}
.skill-pill{display:inline-flex;padding:2px 7px;border-radius:20px;font-size:11px;background:#f1f5f9;color:#334155;border:0.5px solid #e2e8f0;margin:1px 2px 1px 0;white-space:nowrap}
.match-wrap{display:flex;align-items:center;gap:6px}
.match-bar-bg{flex:1;height:4px;background:#e2e8f0;border-radius:2px;min-width:32px}
.match-bar-fill{height:4px;border-radius:2px;background:#185FA5}
.match-pct{font-size:12px;color:#64748b;min-width:28px}
.reason-td{font-size:12px;color:#64748b;line-height:1.6;padding:8px 12px 10px}
.reason-label{font-weight:500;color:#334155;margin-right:4px}
.act-link{color:#185FA5;font-weight:500;text-decoration:none;font-size:13px}
.act-link:hover{text-decoration:underline}
.act-approve-btn{color:#3B6D11;font-weight:500;font-size:13px;border:none;background:none;cursor:pointer;padding:0}
.act-approve-btn:hover{text-decoration:underline}
.act-done{font-size:12px;color:#94a3b8}
.empty-state{background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;padding:3rem;text-align:center;color:#94a3b8;font-size:13px}
.chk{width:14px;height:14px;accent-color:#185FA5;cursor:pointer}
.jd-card{background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:1.5rem}
.jd-card h2{font-size:15px;font-weight:600;color:#0f172a;margin-bottom:6px}
.jd-card p{font-size:12px;color:#64748b;margin-bottom:12px}
.jd-textarea{width:100%;min-height:120px;border:0.5px solid #cbd5e1;border-radius:10px;padding:12px;font-size:13px;line-height:1.6;resize:vertical;outline:none}
.jd-textarea:focus{border-color:#185FA5;box-shadow:0 0 0 3px rgba(24,95,165,.12)}
.jd-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px}
.jd-hint{font-size:12px;color:#94a3b8}
.jd-results{background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;margin-bottom:1.5rem;overflow:hidden}
.jd-results-header{padding:14px 16px;background:#f8fafc;border-bottom:0.5px solid #e2e8f0}
.jd-results-header h2{font-size:15px;font-weight:600;color:#0f172a;margin-bottom:4px}
.jd-results-header p{font-size:12px;color:#64748b}
.jd-requirements{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.jd-result-card{padding:16px;border-top:0.5px solid #f1f5f9}
.jd-result-card:first-child{border-top:none}
.jd-result-top{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
.jd-result-name{font-size:15px;font-weight:600;color:#0f172a}
.jd-result-sub{font-size:12px;color:#64748b;margin-top:2px}
.jd-score{font-size:22px;font-weight:700;color:#185FA5;text-align:right}
.jd-score-label{font-size:11px;color:#94a3b8;text-align:right}
.jd-section{margin-top:12px}
.jd-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px}
.jd-evidence{margin:0;padding-left:18px;color:#475569;font-size:12px;line-height:1.6}
.jd-breakdown{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px;margin-top:12px}
.jd-breakdown-box{background:#f8fafc;border:0.5px solid #e2e8f0;border-radius:8px;padding:8px}
.jd-breakdown-label{font-size:11px;color:#64748b}
.jd-breakdown-value{font-size:14px;font-weight:600;color:#0f172a;margin-top:2px}
.alert-error{background:#FCEBEB;color:#791F1F;border:0.5px solid #f3b8b8;border-radius:10px;padding:12px;font-size:13px;margin-bottom:1rem}
</style>

<div class="topbar">
    <div>
        <h1>Candidate leaderboard</h1>
        <p>Rank candidates by category or paste a job description to calculate role-specific match scores.</p>
    </div>
    <a href="{{ route('candidates.upload') }}" class="btn-primary">↑ Upload new CV</a>
</div>

{{-- JD-based ranking form --}}
<div class="jd-card">
    <h2>Rank candidates for a specific job description</h2>
    <p>
        Paste the job description below. The system will calculate a transparent math-based match score
        using required skills, preferred skills, experience, evidence, domain relevance, and education/certification.
    </p>

    @if($errors->has('job_description'))
        <div class="alert-error">{{ $errors->first('job_description') }}</div>
    @endif

    <form method="POST" action="{{ route('leaderboard.rank-by-job') }}">
        @csrf

        <textarea
            name="job_description"
            class="jd-textarea"
            placeholder="Example: We are hiring a Data Analyst with Python, SQL, Power BI, Excel, dashboard reporting, and data cleaning experience. Minimum 1 year experience required."
        >{{ old('job_description', $jobDescription ?? '') }}</textarea>

        <div class="jd-actions">
            <span class="jd-hint">
                Formula: 45% required skills + 10% preferred skills + 15% experience + 15% evidence + 10% domain + 5% education/certification
            </span>

            <button type="submit" class="btn-primary">
                Calculate JD Match
            </button>
        </div>
    </form>
</div>

{{-- Category filter pills --}}
<div class="filter-bar">
    <a href="{{ route('leaderboard.index', ['hide_approved' => $hideApproved ? 1 : null]) }}"
       class="filter-pill {{ ! $selectedCategory ? 'active' : '' }}">All</a>

    @foreach($categories as $category)
        <a href="{{ route('leaderboard.index', ['category' => $category, 'hide_approved' => $hideApproved ? 1 : null]) }}"
           class="filter-pill {{ $selectedCategory === $category ? 'active' : '' }}">{{ $category }}</a>
    @endforeach

    <div class="filter-divider"></div>

    <a href="{{ route('leaderboard.index', ['category' => $selectedCategory, 'hide_approved' => $hideApproved ? null : 1]) }}"
       class="filter-pill {{ $hideApproved ? 'pending-green' : '' }}">
        {{ $hideApproved ? 'Showing pending only' : 'Hide approved' }}
    </a>
</div>

{{-- Bulk form (hidden) --}}
<form id="bulk-approve-form" method="POST" action="{{ route('candidates.bulk-approve') }}">
    @csrf
    <input type="hidden" name="approval_note" value="Bulk approved by HR from leaderboard.">
</form>

<div class="toolbar">
    <button type="submit" form="bulk-approve-form" class="btn-approve"
            onclick="return confirm('Approve selected ranked candidates and queue confirmation emails?')">
        ✓ Approve selected
    </button>
    <span class="toolbar-hint">Use this page to approve candidates based on rank.</span>
</div>

@if(!empty($jobRanking))
    @if(($jobRanking['status'] ?? 'success') === 'error')
        <div class="alert-error">
            {{ $jobRanking['message'] ?? 'JD-based ranking failed.' }}
        </div>
    @else
        @php
            $requirements = $jobRanking['job_requirements'] ?? [];
            $requiredSkills = $requirements['required_skills'] ?? [];
            $preferredSkills = $requirements['preferred_skills'] ?? [];
            $requiredYears = $requirements['required_years'] ?? 0;
            $likelyCategory = $requirements['likely_category'] ?? 'Operations';
            $rankings = collect($jobRanking['rankings'] ?? [])->take(10);
        @endphp

        <div class="jd-results">
            <div class="jd-results-header">
                <h2>JD-based match results</h2>
                <p>
                    Likely role category:
                    <strong>{{ $likelyCategory }}</strong>
                    @if($requiredYears)
                        · Required experience:
                        <strong>{{ $requiredYears }} year{{ $requiredYears == 1 ? '' : 's' }}</strong>
                    @endif
                </p>

                <div class="jd-requirements">
                    @forelse($requiredSkills as $skill)
                        <span class="skill-pill">Required: {{ $skill }}</span>
                    @empty
                        <span class="skill-pill">No specific required skills detected</span>
                    @endforelse

                    @foreach($preferredSkills as $skill)
                        <span class="skill-pill">Preferred: {{ $skill }}</span>
                    @endforeach
                </div>
            </div>

            @forelse($rankings as $result)
                @php
                    $breakdown = $result['score_breakdown'] ?? [];
                    $matchedSkills = $result['matched_skills'] ?? [];
                    $missingSkills = $result['missing_skills'] ?? [];
                    $evidence = $result['evidence'] ?? [];
                @endphp

                <div class="jd-result-card">
                    <div class="jd-result-top">
                        <div>
                            <div class="jd-result-name">
                                #{{ $result['rank'] ?? '—' }} · {{ $result['candidate_name'] ?? 'Unknown candidate' }}
                            </div>
                            <div class="jd-result-sub">
                                {{ $result['current_job_title'] ?? 'No current title detected' }}
                                @if(!empty($result['category']))
                                    · {{ $result['category'] }}
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="jd-score">{{ number_format($result['match_percentage'] ?? 0, 0) }}%</div>
                            <div class="jd-score-label">Match</div>
                        </div>
                    </div>

                    <div class="match-wrap" style="margin-top:10px">
                        <div class="match-bar-bg">
                            <div class="match-bar-fill" style="width:{{ min(100, $result['match_percentage'] ?? 0) }}%"></div>
                        </div>
                        <span class="match-pct">{{ number_format($result['match_percentage'] ?? 0, 0) }}%</span>
                    </div>

                    <div class="jd-section">
                        <div class="jd-section-title">Matched skills</div>
                        @forelse($matchedSkills as $skill)
                            <span class="skill-pill">{{ $skill }}</span>
                        @empty
                            <span style="font-size:12px;color:#94a3b8">No direct skill match detected.</span>
                        @endforelse
                    </div>

                    <div class="jd-section">
                        <div class="jd-section-title">Missing required skills</div>
                        @forelse($missingSkills as $skill)
                            <span class="badge badge-red">{{ $skill }}</span>
                        @empty
                            <span class="badge badge-green">No major required skill gap detected</span>
                        @endforelse
                    </div>

                    <div class="jd-section">
                        <div class="jd-section-title">Resume evidence</div>
                        @if(!empty($evidence))
                            <ul class="jd-evidence">
                                @foreach($evidence as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span style="font-size:12px;color:#94a3b8">
                                No strong evidence line detected. Manual review recommended.
                            </span>
                        @endif
                    </div>

                    <div class="jd-section">
                        <div class="jd-section-title">Recommendation reason</div>
                        <div style="font-size:12px;color:#475569;line-height:1.6">
                            {{ $result['reason'] ?? 'No reason generated.' }}
                        </div>
                    </div>

                    <div class="jd-breakdown">
                        <div class="jd-breakdown-box">
                            <div class="jd-breakdown-label">Required skills</div>
                            <div class="jd-breakdown-value">{{ number_format($breakdown['required_skill_score'] ?? 0, 0) }}%</div>
                        </div>

                        <div class="jd-breakdown-box">
                            <div class="jd-breakdown-label">Preferred skills</div>
                            <div class="jd-breakdown-value">{{ number_format($breakdown['preferred_skill_score'] ?? 0, 0) }}%</div>
                        </div>

                        <div class="jd-breakdown-box">
                            <div class="jd-breakdown-label">Experience</div>
                            <div class="jd-breakdown-value">{{ number_format($breakdown['experience_score'] ?? 0, 0) }}%</div>
                        </div>

                        <div class="jd-breakdown-box">
                            <div class="jd-breakdown-label">Evidence</div>
                            <div class="jd-breakdown-value">{{ number_format($breakdown['evidence_score'] ?? 0, 0) }}%</div>
                        </div>

                        <div class="jd-breakdown-box">
                            <div class="jd-breakdown-label">Domain</div>
                            <div class="jd-breakdown-value">{{ number_format($breakdown['domain_score'] ?? 0, 0) }}%</div>
                        </div>

                        <div class="jd-breakdown-box">
                            <div class="jd-breakdown-label">Education / Cert</div>
                            <div class="jd-breakdown-value">{{ number_format($breakdown['education_certification_score'] ?? 0, 0) }}%</div>
                        </div>
                    </div>

                    <div class="jd-section">
                        <a href="{{ route('candidates.show', $result['candidate_id']) }}" class="act-link">
                            View candidate profile
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    No JD ranking result returned.
                </div>
            @endforelse
        </div>
    @endif
@endif

@if($groupedCandidates->isEmpty())
    <div class="empty-state">
        No ranked candidates yet. Upload CVs first — the system will classify and rank them.
    </div>
@else
    <div class="group-wrap">
        @foreach($groupedCandidates as $category => $candidates)
            <div class="group-card">
                <div class="group-header">
                    <h2>{{ $category }}</h2>
                    <span>{{ $candidates->count() }} candidate{{ $candidates->count() !== 1 ? 's' : '' }}</span>
                </div>

                <div style="overflow-x:auto">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:36px"><input type="checkbox" class="chk select-category-candidates"></th>
                                <th style="width:48px">Rank</th>
                                <th>Candidate</th>
                                <th>Category</th>
                                <th style="width:68px">Score</th>
                                <th style="width:110px">Match</th>
                                <th style="width:80px">Experience</th>
                                <th>Key skills</th>
                                <th style="width:76px">Approval</th>
                                <th style="width:68px">Email</th>
                                <th style="width:90px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $candidate)
                                @php
                                    $keySkills = $candidate->skills->pluck('skill')->filter()->unique()->take(5)->values();
                                    $rank = $candidate->leaderboard_rank;
                                    $rankClass = $rank === 1 ? 'rank-1' : ($rank === 2 ? 'rank-2' : ($rank === 3 ? 'rank-3' : ''));
                                @endphp

                                <tr class="data-row">
                                    <td>
                                        @if($candidate->approval_status !== 'approved')
                                            <input type="checkbox" class="chk candidate-checkbox"
                                                   name="candidate_ids[]" value="{{ $candidate->id }}"
                                                   form="bulk-approve-form">
                                        @else
                                            <span style="color:#cbd5e1">—</span>
                                        @endif
                                    </td>

                                    <td class="rank-cell {{ $rankClass }}">
                                        {{ $rank ? '#'.$rank : '—' }}
                                    </td>

                                    <td>
                                        <div class="candidate-name">{{ $candidate->full_name ?? 'Unknown' }}</div>
                                        <div class="candidate-sub">{{ $candidate->current_job_title ?? '' }}</div>
                                        <div class="candidate-sub">{{ $candidate->email ?? '' }}</div>
                                    </td>

                                    <td>
                                        @if($candidate->candidate_category)
                                            <span class="badge badge-blue">{{ $candidate->candidate_category }}</span>
                                        @else
                                            <span style="color:#cbd5e1">—</span>
                                        @endif
                                    </td>

                                    <td style="font-weight:500;color:#0f172a">
                                        {{ $candidate->leaderboard_score !== null ? number_format($candidate->leaderboard_score, 2) : '—' }}
                                    </td>

                                    <td>
                                        @if($candidate->match_percentage !== null)
                                            <div class="match-wrap">
                                                <div class="match-bar-bg">
                                                    <div class="match-bar-fill" style="width:{{ min(100,$candidate->match_percentage) }}%"></div>
                                                </div>
                                                <span class="match-pct">{{ number_format($candidate->match_percentage,0) }}%</span>
                                            </div>
                                        @else
                                            <span style="color:#cbd5e1">—</span>
                                        @endif
                                    </td>

                                    <td style="color:#64748b">
                                        {{ $candidate->total_experience_years ? $candidate->total_experience_years.' yrs' : '—' }}
                                    </td>

                                    <td style="max-width:180px">
                                        @forelse($keySkills as $skill)
                                            <span class="skill-pill">{{ $skill }}</span>
                                        @empty
                                            <span style="color:#cbd5e1">—</span>
                                        @endforelse
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
                                        @if($candidate->email_status === 'sent')
                                            <span class="badge badge-green">sent</span>
                                        @elseif($candidate->email_status === 'queued')
                                            <span class="badge badge-amber">queued</span>
                                        @elseif($candidate->email_status === 'failed')
                                            <span class="badge badge-red">failed</span>
                                        @else
                                            <span class="badge badge-gray">not sent</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px">
                                            <a href="{{ route('candidates.show', $candidate) }}" class="act-link">View</a>
                                            @if($candidate->approval_status !== 'approved')
                                                <form method="POST" action="{{ route('candidates.approve', $candidate) }}" style="display:inline">
                                                    @csrf
                                                    <input type="hidden" name="approval_source" value="rank_based">
                                                    <input type="hidden" name="approval_note" value="Approved by HR from leaderboard ranking.">
                                                    <button type="submit" class="act-approve-btn"
                                                            onclick="return confirm('Approve this candidate and queue confirmation email?')">
                                                        Approve
                                                    </button>
                                                </form>
                                            @else
                                                <span class="act-done">Approved</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Reason row --}}
                                <tr class="reason-row">
                                    <td></td>
                                    <td colspan="10" class="reason-td">
                                        <span class="reason-label">Reason:</span>{{ $candidate->ranking_reason ?? $candidate->classification_reason ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endif

<script>
document.querySelectorAll('.select-category-candidates').forEach(function(selectAll) {
    selectAll.addEventListener('change', function() {
        const table = selectAll.closest('table');
        table.querySelectorAll('.candidate-checkbox').forEach(cb => cb.checked = selectAll.checked);
    });
});
</script>
@endsection
