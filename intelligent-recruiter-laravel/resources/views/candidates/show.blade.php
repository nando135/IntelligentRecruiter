@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;gap:1rem;">
    <a href="{{ route('candidates.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b;text-decoration:none;font-weight:500;transition:color .12s;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3L5 8L10 13"/></svg>
        Back to Candidates
    </a>

    @if($candidate->approval_status !== 'approved')
        <form method="POST" action="{{ route('candidates.approve', $candidate) }}">
            @csrf
            <input type="hidden" name="approval_source" value="single">
            <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#059669;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 8L6 11.5L13.5 4"/></svg>
                Approve Candidate
            </button>
        </form>
    @else
        <span style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;border-radius:8px;font-size:12.5px;font-weight:600;">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 8L6 11.5L13.5 4"/></svg>
            Approved
        </span>
    @endif
</div>

{{-- Parser Status --}}
@if($candidate->parser_status === 'partial')
    <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;font-size:13px;color:#92400e;margin-bottom:1.25rem;">
        <span>⚠</span>
        <div><strong>Partial Extraction</strong> — Some fields may be missing.
            @if($candidate->parser_warning)<div style="margin-top:4px;font-family:monospace;font-size:11px;">{{ $candidate->parser_warning }}</div>@endif
        </div>
    </div>
@elseif($candidate->parser_status === 'failed')
    <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:13px;color:#991b1b;margin-bottom:1.25rem;">
        <span>✗</span>
        <div><strong>Extraction Failed</strong>
            @if($candidate->parser_warning)<div style="margin-top:4px;font-family:monospace;font-size:11px;">{{ $candidate->parser_warning }}</div>@endif
        </div>
    </div>
@elseif($candidate->parser_status === 'success')
    <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;font-size:13px;color:#065f46;font-weight:500;margin-bottom:1.25rem;">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6.5"/><path d="M5 8L7 10L11 6"/></svg>
        Extraction Successful
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT COLUMN --}}
    <div class="lg:col-span-1 space-y-4">

        {{-- Profile Card --}}
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.5rem;">
            {{-- Avatar --}}
            <div style="width:52px;height:52px;border-radius:50%;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;margin-bottom:1rem;">
                {{ strtoupper(substr($candidate->full_name ?? 'U', 0, 1)) }}
            </div>
            <h1 style="font-size:1.2rem;font-weight:700;color:#0f172a;line-height:1.25;margin-bottom:2px;">{{ $candidate->full_name ?? 'Unknown Candidate' }}</h1>
            <p style="font-size:13px;color:#64748b;margin-bottom:1.25rem;">{{ $candidate->current_job_title ?? 'No current role detected' }}</p>

            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                @if($candidate->email)
                <div style="display:flex;gap:8px;">
                    <span style="color:#94a3b8;width:80px;flex-shrink:0;">Email</span>
                    <span style="color:#0f172a;word-break:break-all;">{{ $candidate->email }}</span>
                </div>
                @endif
                @if($candidate->phone)
                <div style="display:flex;gap:8px;">
                    <span style="color:#94a3b8;width:80px;flex-shrink:0;">Phone</span>
                    <span style="color:#0f172a;">{{ $candidate->phone }}</span>
                </div>
                @endif
                @if($candidate->location)
                <div style="display:flex;gap:8px;">
                    <span style="color:#94a3b8;width:80px;flex-shrink:0;">Location</span>
                    <span style="color:#0f172a;">{{ $candidate->location }}</span>
                </div>
                @endif
                @if($candidate->latest_company)
                <div style="display:flex;gap:8px;">
                    <span style="color:#94a3b8;width:80px;flex-shrink:0;">Company</span>
                    <span style="color:#0f172a;">{{ $candidate->latest_company }}</span>
                </div>
                @endif
                <div style="display:flex;gap:8px;">
                    <span style="color:#94a3b8;width:80px;flex-shrink:0;">Experience</span>
                    <span style="color:#0f172a;">
                        @if($candidate->total_experience_years) {{ $candidate->total_experience_years }} yrs
                        @elseif($candidate->total_experience_months) {{ $candidate->total_experience_months }} mo
                        @else -
                        @endif
                    </span>
                </div>
                @if($candidate->source_filename)
                <div style="display:flex;gap:8px;">
                    <span style="color:#94a3b8;width:80px;flex-shrink:0;">File</span>
                    <span style="color:#64748b;font-size:12px;">{{ $candidate->source_filename }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- AI Classification --}}
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.25rem;">
            <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:12px;">AI Classification</p>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-size:13px;color:#64748b;">Category</span>
                <span style="background:#f1f5f9;color:#0f172a;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:600;">{{ $candidate->candidate_category ?? '-' }}</span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px;">
                <div style="text-align:center;padding:10px 6px;background:#f8f9fc;border-radius:8px;">
                    <div style="font-size:17px;font-weight:700;color:#0f172a;">{{ $candidate->leaderboard_rank ? '#'.$candidate->leaderboard_rank : '—' }}</div>
                    <div style="font-size:10px;color:#94a3b8;margin-top:2px;">Rank</div>
                </div>
                <div style="text-align:center;padding:10px 6px;background:#f8f9fc;border-radius:8px;">
                    <div style="font-size:17px;font-weight:700;color:#0f172a;">{{ $candidate->leaderboard_score !== null ? number_format($candidate->leaderboard_score, 1) : '—' }}</div>
                    <div style="font-size:10px;color:#94a3b8;margin-top:2px;">Score</div>
                </div>
                <div style="text-align:center;padding:10px 6px;background:#f8f9fc;border-radius:8px;">
                    <div style="font-size:17px;font-weight:700;color:#0f172a;">{{ $candidate->match_percentage !== null ? number_format($candidate->match_percentage, 0).'%' : '—' }}</div>
                    <div style="font-size:10px;color:#94a3b8;margin-top:2px;">Match</div>
                </div>
            </div>

            @if($candidate->classification_confidence !== null)
            <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;font-size:12px;color:#64748b;margin-bottom:4px;">
                    <span>Confidence</span><span>{{ number_format($candidate->classification_confidence, 1) }}%</span>
                </div>
                <div style="background:#f1f5f9;border-radius:99px;height:5px;">
                    <div style="background:#1d4ed8;height:5px;border-radius:99px;width:{{ min($candidate->classification_confidence, 100) }}%;transition:width .4s;"></div>
                </div>
            </div>
            @endif

            @if($candidate->classification_reason)
            <p style="font-size:11.5px;color:#64748b;line-height:1.5;padding-top:8px;border-top:1px solid #f1f5f9;">{{ $candidate->classification_reason }}</p>
            @endif
        </div>

        {{-- Links --}}
        @if($candidate->links && ($candidate->links->linkedin || $candidate->links->github || $candidate->links->portfolio || $candidate->links->website))
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.25rem;">
            <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:12px;">Links</p>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                @if($candidate->links->linkedin)
                <a href="{{ $candidate->links->linkedin }}" target="_blank" style="display:flex;align-items:center;gap:8px;color:#1d4ed8;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    LinkedIn
                </a>
                @endif
                @if($candidate->links->github)
                <a href="{{ $candidate->links->github }}" target="_blank" style="display:flex;align-items:center;gap:8px;color:#1d4ed8;text-decoration:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22"/></svg>
                    GitHub
                </a>
                @endif
                @if($candidate->links->portfolio)
                <a href="{{ $candidate->links->portfolio }}" target="_blank" style="display:flex;align-items:center;gap:8px;color:#1d4ed8;text-decoration:none;">Portfolio</a>
                @endif
            </div>
        </div>
        @endif

        {{-- Languages --}}
        @if($candidate->languages->count())
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.25rem;">
            <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:12px;">Languages</p>
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($candidate->languages as $lang)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#0f172a;">{{ $lang->language }}</span>
                    <span style="color:#94a3b8;font-size:12px;">{{ $lang->proficiency ?? '-' }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Summary --}}
        @if($candidate->professional_summary)
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.5rem;">
            <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:10px;">Professional Summary</p>
            <p style="font-size:13.5px;color:#374151;line-height:1.7;">{{ $candidate->professional_summary }}</p>
        </div>
        @endif

        {{-- Work Experience --}}
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
                <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;">Work Experience</p>
                <span style="font-size:11px;color:#94a3b8;">{{ $candidate->experiences->count() }} entries</span>
            </div>

            @forelse($candidate->experiences as $exp)
            <div style="padding-bottom:1.25rem;margin-bottom:1.25rem;border-bottom:1px solid #f1f5f9;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                    <div>
                        <h3 style="font-size:14px;font-weight:600;color:#0f172a;">{{ $exp->job_title ?? 'Role not detected' }}</h3>
                        <p style="font-size:13px;color:#475569;margin-top:1px;">{{ $exp->company_name ?? 'Company not detected' }}</p>
                    </div>
                    @if($exp->is_current)
                    <span style="background:#ecfdf5;color:#059669;padding:2px 9px;border-radius:99px;font-size:11px;font-weight:600;flex-shrink:0;">Current</span>
                    @endif
                </div>
                <p style="font-size:12px;color:#94a3b8;margin-top:5px;">
                    {{ $exp->start_date ?? '-' }} → {{ $exp->end_date ?? '-' }}
                    @if($exp->duration_months) · {{ $exp->duration_months }} months @endif
                    @if($exp->employment_type) · {{ $exp->employment_type }} @endif
                </p>

                @if($exp->responsibilities && count($exp->responsibilities) > 0)
                <p style="font-size:11.5px;font-weight:600;color:#475569;margin:10px 0 5px;">Responsibilities</p>
                <ul style="margin:0;padding-left:1.1rem;display:flex;flex-direction:column;gap:3px;">
                    @foreach($exp->responsibilities as $item)
                    <li style="font-size:13px;color:#475569;line-height:1.55;">{{ $item }}</li>
                    @endforeach
                </ul>
                @endif

                @if($exp->tools_used && count($exp->tools_used) > 0)
                <p style="font-size:11.5px;font-weight:600;color:#475569;margin:10px 0 6px;">Tools Used</p>
                <div style="display:flex;flex-wrap:wrap;gap:5px;">
                    @foreach($exp->tools_used as $tool)
                    <span style="background:#f1f5f9;color:#374151;padding:3px 9px;border-radius:99px;font-size:11.5px;">{{ $tool }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @empty
            <p style="font-size:13px;color:#94a3b8;">No work experience detected.</p>
            @endforelse
        </div>

        {{-- Education --}}
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
                <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;">Education</p>
                <span style="font-size:11px;color:#94a3b8;">{{ $candidate->educations->count() }} entries</span>
            </div>

            @forelse($candidate->educations as $edu)
            <div style="padding-bottom:1rem;margin-bottom:1rem;border-bottom:1px solid #f1f5f9;">
                <h3 style="font-size:14px;font-weight:600;color:#0f172a;">{{ $edu->institution ?? 'Institution not detected' }}</h3>
                <p style="font-size:13px;color:#475569;margin-top:2px;">{{ $edu->degree ?? '' }}@if($edu->field_of_study) · {{ $edu->field_of_study }}@endif</p>
                <p style="font-size:12px;color:#94a3b8;margin-top:3px;">
                    @if($edu->start_year || $edu->end_year){{ $edu->start_year ?? '-' }} – {{ $edu->end_year ?? '-' }}@endif
                    @if($edu->cgpa) · CGPA: {{ $edu->cgpa }}@endif
                </p>
                @if($edu->relevant_coursework && count($edu->relevant_coursework) > 0)
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
                    @foreach($edu->relevant_coursework as $course)
                    <span style="background:#f5f3ff;color:#5b21b6;padding:2px 8px;border-radius:6px;font-size:11.5px;">{{ $course }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @empty
            <p style="font-size:13px;color:#94a3b8;">No education detected.</p>
            @endforelse
        </div>

        {{-- Skills --}}
        @if($candidate->skills->count())
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
                <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;">Skills</p>
                <span style="font-size:11px;color:#94a3b8;">{{ $candidate->skills->count() }} total</span>
            </div>
            @php $grouped = $candidate->skills->groupBy('category'); @endphp
            @foreach($grouped as $category => $skills)
            <div style="margin-bottom:12px;">
                <p style="font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:7px;text-transform:capitalize;">{{ str_replace('_', ' ', $category) }}</p>
                <div style="display:flex;flex-wrap:wrap;gap:5px;">
                    @foreach($skills as $skill)
                    <span style="background:#f1f5f9;color:#374151;padding:4px 10px;border-radius:99px;font-size:12.5px;">{{ $skill->skill }}</span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Projects --}}
        @if($candidate->projects->count())
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.5rem;">
            <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:1.25rem;">Projects</p>
            @foreach($candidate->projects as $proj)
            <div style="padding-bottom:1rem;margin-bottom:1rem;border-bottom:1px solid #f1f5f9;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <h3 style="font-size:14px;font-weight:600;color:#0f172a;">{{ $proj->project_name ?? 'Project' }}</h3>
                    @if($proj->project_type)<span style="font-size:11px;color:#94a3b8;">{{ $proj->project_type }}</span>@endif
                </div>
                @if($proj->role)<p style="font-size:12px;color:#64748b;margin-top:2px;">Role: {{ $proj->role }}</p>@endif
                @if($proj->description)<p style="font-size:13px;color:#475569;margin-top:5px;line-height:1.55;">{{ $proj->description }}</p>@endif
                @if($proj->technologies && count($proj->technologies) > 0)
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
                    @foreach($proj->technologies as $tech)
                    <span style="background:#f0fdf4;color:#166534;padding:2px 8px;border-radius:99px;font-size:11.5px;">{{ $tech }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- Certifications --}}
        @if($candidate->certifications->count())
        <div style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:1.5rem;">
            <p style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:1.25rem;">Certifications</p>
            @foreach($candidate->certifications as $cert)
            <div style="padding-bottom:.875rem;margin-bottom:.875rem;border-bottom:1px solid #f1f5f9;">
                <p style="font-size:13.5px;font-weight:600;color:#0f172a;">{{ $cert->name ?? '-' }}</p>
                <p style="font-size:12px;color:#94a3b8;margin-top:2px;">{{ $cert->issuer ?? '' }}@if($cert->date_issued) · {{ $cert->date_issued }}@endif</p>
                @if($cert->credential_link)<a href="{{ $cert->credential_link }}" target="_blank" style="font-size:12px;color:#1d4ed8;text-decoration:none;">View credential →</a>@endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- Raw Text (collapsible) --}}
        <details style="background:#fff;border:1px solid #e4e9f0;border-radius:12px;overflow:hidden;">
            <summary style="padding:1rem 1.5rem;font-size:12px;font-weight:600;color:#64748b;cursor:pointer;letter-spacing:.04em;list-style:none;display:flex;align-items:center;justify-content:space-between;">
                <span>Raw Extracted Text</span>
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 4L6 8L10 4"/></svg>
            </summary>
            <div style="padding:0 1.5rem 1.5rem;">
                <pre style="background:#f8f9fc;border:1px solid #e4e9f0;padding:14px;border-radius:8px;font-size:11.5px;overflow:auto;max-height:20rem;white-space:pre-wrap;color:#374151;">{{ $candidate->raw_text ?? 'No raw text available.' }}</pre>
            </div>
        </details>

        {{-- Debug (collapsible) --}}
        <details style="background:#f8f9fc;border:1px solid #e4e9f0;border-radius:12px;overflow:hidden;">
            <summary style="padding:.875rem 1.25rem;font-size:11.5px;font-weight:600;color:#94a3b8;cursor:pointer;list-style:none;">Debug API Links</summary>
            <div style="padding:.5rem 1.25rem 1rem;display:flex;flex-direction:column;gap:5px;">
                <a href="/api/candidates/{{ $candidate->id }}" target="_blank" style="font-size:12px;color:#1d4ed8;text-decoration:none;">Full JSON</a>
                <a href="/api/candidates/{{ $candidate->id }}/parsed-json" target="_blank" style="font-size:12px;color:#1d4ed8;text-decoration:none;">Parsed JSON</a>
                <a href="/api/candidates/{{ $candidate->id }}/raw-text" target="_blank" style="font-size:12px;color:#1d4ed8;text-decoration:none;">Raw Text</a>
                <a href="/api/candidates/{{ $candidate->id }}/experiences" target="_blank" style="font-size:12px;color:#1d4ed8;text-decoration:none;">Experiences</a>
                <a href="/api/candidates/{{ $candidate->id }}/skills" target="_blank" style="font-size:12px;color:#1d4ed8;text-decoration:none;">Skills</a>
            </div>
        </details>

    </div>
</div>
@endsection
