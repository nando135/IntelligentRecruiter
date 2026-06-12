@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('candidates.index') }}" class="text-blue-700">← Back to Candidates</a>
</div>

{{-- Parser Status Warning --}}
@if($candidate->parser_status === 'partial')
    <div class="mb-6 bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-4 rounded-lg">
        <strong>⚠ Partial Extraction</strong> — Qwen could not fully parse this CV. Some fields may be missing.
        @if($candidate->parser_warning)
            <div class="mt-1 text-sm font-mono">{{ $candidate->parser_warning }}</div>
        @endif
    </div>
@elseif($candidate->parser_status === 'failed')
    <div class="mb-6 bg-red-50 border border-red-300 text-red-800 px-4 py-4 rounded-lg">
        <strong>✗ Extraction Failed</strong> — CV could not be parsed. See raw text below for debugging.
        @if($candidate->parser_warning)
            <div class="mt-1 text-sm font-mono">{{ $candidate->parser_warning }}</div>
        @endif
    </div>
@elseif($candidate->parser_status === 'success')
    <div class="mb-6 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg">
        <strong>✓ Extraction Successful</strong>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left Column --}}
    <div class="lg:col-span-1 space-y-6">

        {{-- Basic Info --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold">{{ $candidate->full_name ?? 'Unknown Candidate' }}</h1>
            <p class="text-slate-600 mt-1">{{ $candidate->current_job_title ?? 'No current role detected' }}</p>

            <div class="mt-6 space-y-2 text-sm">
                <p><strong>Email:</strong> {{ $candidate->email ?? '-' }}</p>
                <p><strong>Phone:</strong> {{ $candidate->phone ?? '-' }}</p>
                <p><strong>Location:</strong> {{ $candidate->location ?? '-' }}</p>
                <p><strong>Latest Company:</strong> {{ $candidate->latest_company ?? '-' }}</p>
                <p><strong>Total Experience:</strong>
                    @if($candidate->total_experience_years)
                        {{ $candidate->total_experience_years }} years
                    @elseif($candidate->total_experience_months)
                        {{ $candidate->total_experience_months }} months
                    @else
                        -
                    @endif
                </p>
                <p><strong>Parser Status:</strong>
                    <span class="font-mono text-xs bg-slate-100 px-2 py-0.5 rounded">
                        {{ $candidate->parser_status ?? 'unknown' }}
                    </span>
                </p>
                <p><strong>Source File:</strong> {{ $candidate->source_filename ?? '-' }}</p>
            </div>
        </div>

        {{-- AI Classification & Ranking --}}
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <h2 class="text-xl font-bold mb-4">AI Classification & Ranking</h2>

    <div class="space-y-2 text-sm">
        <p>
            <strong>Category:</strong>
            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs">
                {{ $candidate->candidate_category ?? '-' }}
            </span>
        </p>

        <p>
            <strong>Classification Confidence:</strong>
            {{ $candidate->classification_confidence !== null ? number_format($candidate->classification_confidence, 2) . '%' : '-' }}
        </p>

        <p>
            <strong>Rank:</strong>
            {{ $candidate->leaderboard_rank ? '#' . $candidate->leaderboard_rank : '-' }}
        </p>

        <p>
            <strong>Score:</strong>
            {{ $candidate->leaderboard_score !== null ? number_format($candidate->leaderboard_score, 2) : '-' }}
        </p>

        <p>
            <strong>Match:</strong>
            {{ $candidate->match_percentage !== null ? number_format($candidate->match_percentage, 2) . '%' : '-' }}
        </p>

        <p>
            <strong>Classification Reason:</strong><br>
            <span class="text-slate-600">{{ $candidate->classification_reason ?? '-' }}</span>
        </p>

        <p>
            <strong>Ranking Reason:</strong><br>
            <span class="text-slate-600">{{ $candidate->ranking_reason ?? '-' }}</span>
        </p>
    </div>
</div>

        {{-- Links --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Links</h2>
            @if($candidate->links)
                <div class="space-y-2 text-sm">
                    @if($candidate->links->linkedin)
                        <p><strong>LinkedIn:</strong> <a href="{{ $candidate->links->linkedin }}" class="text-blue-600" target="_blank">{{ $candidate->links->linkedin }}</a></p>
                    @endif
                    @if($candidate->links->github)
                        <p><strong>GitHub:</strong> <a href="{{ $candidate->links->github }}" class="text-blue-600" target="_blank">{{ $candidate->links->github }}</a></p>
                    @endif
                    @if($candidate->links->portfolio)
                        <p><strong>Portfolio:</strong> <a href="{{ $candidate->links->portfolio }}" class="text-blue-600" target="_blank">{{ $candidate->links->portfolio }}</a></p>
                    @endif
                    @if($candidate->links->website)
                        <p><strong>Website:</strong> <a href="{{ $candidate->links->website }}" class="text-blue-600" target="_blank">{{ $candidate->links->website }}</a></p>
                    @endif
                    @if(!$candidate->links->linkedin && !$candidate->links->github && !$candidate->links->portfolio && !$candidate->links->website)
                        <p class="text-slate-500">No links detected.</p>
                    @endif
                </div>
            @else
                <p class="text-slate-500">No links detected.</p>
            @endif
        </div>

        {{-- Languages --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Languages</h2>
            @forelse($candidate->languages as $lang)
                <div class="flex justify-between text-sm py-1 border-b border-slate-100">
                    <span>{{ $lang->language }}</span>
                    <span class="text-slate-500">{{ $lang->proficiency ?? '-' }}</span>
                </div>
            @empty
                <p class="text-slate-500">No languages detected.</p>
            @endforelse
        </div>

        {{-- Debug API Links --}}
        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-xs space-y-1">
            <p class="font-semibold text-slate-700 mb-2">Debug API Links</p>
            <a href="/api/candidates/{{ $candidate->id }}" class="block text-blue-600" target="_blank">Full JSON from MySQL</a>
            <a href="/api/candidates/{{ $candidate->id }}/parsed-json" class="block text-blue-600" target="_blank">Parsed JSON</a>
            <a href="/api/candidates/{{ $candidate->id }}/raw-text" class="block text-blue-600" target="_blank">Raw Text</a>
            <a href="/api/candidates/{{ $candidate->id }}/experiences" class="block text-blue-600" target="_blank">Experiences</a>
            <a href="/api/candidates/{{ $candidate->id }}/skills" class="block text-blue-600" target="_blank">Skills</a>
        </div>
    </div>

    {{-- Right Column --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Professional Summary --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Professional Summary</h2>
            <p class="text-slate-700">
                {{ $candidate->professional_summary ?? 'No professional summary detected.' }}
            </p>
        </div>

        {{-- Work Experience --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Work Experience
                <span class="text-sm font-normal text-slate-500">({{ $candidate->experiences->count() }} entries)</span>
            </h2>

            @forelse($candidate->experiences as $exp)
                <div class="border-b border-slate-200 pb-5 mb-5">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg">{{ $exp->job_title ?? 'Role not detected' }}</h3>
                            <p class="text-slate-700">{{ $exp->company_name ?? 'Company not detected' }}</p>
                        </div>
                        @if($exp->is_current)
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Current</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ $exp->start_date ?? '-' }} → {{ $exp->end_date ?? '-' }}
                        @if($exp->duration_months)
                            · {{ $exp->duration_months }} months
                        @endif
                        @if($exp->employment_type)
                            · {{ $exp->employment_type }}
                        @endif
                    </p>

                    @if($exp->responsibilities && count($exp->responsibilities) > 0)
                        <h4 class="font-semibold mt-3 text-sm">Responsibilities</h4>
                        <ul class="list-disc ml-6 text-sm text-slate-700 mt-1 space-y-1">
                            @foreach($exp->responsibilities as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if($exp->achievements && count($exp->achievements) > 0)
                        <h4 class="font-semibold mt-3 text-sm">Achievements</h4>
                        <ul class="list-disc ml-6 text-sm text-slate-700 mt-1 space-y-1">
                            @foreach($exp->achievements as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if($exp->tools_used && count($exp->tools_used) > 0)
                        <h4 class="font-semibold mt-3 text-sm">Tools Used</h4>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($exp->tools_used as $tool)
                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs">{{ $tool }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-slate-500">No work experience detected.</p>
            @endforelse
        </div>

        {{-- Education --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Education
                <span class="text-sm font-normal text-slate-500">({{ $candidate->educations->count() }} entries)</span>
            </h2>

            @forelse($candidate->educations as $edu)
                <div class="border-b border-slate-200 pb-4 mb-4">
                    <h3 class="font-bold">{{ $edu->institution ?? 'Institution not detected' }}</h3>
                    <p class="text-slate-700">{{ $edu->degree ?? '-' }}
                        @if($edu->field_of_study) · {{ $edu->field_of_study }} @endif
                    </p>
                    <p class="text-sm text-slate-500">
                        @if($edu->start_year || $edu->end_year)
                            {{ $edu->start_year ?? '-' }} – {{ $edu->end_year ?? '-' }}
                        @endif
                        @if($edu->cgpa) · CGPA: {{ $edu->cgpa }} @endif
                    </p>
                    @if($edu->relevant_coursework && count($edu->relevant_coursework) > 0)
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach($edu->relevant_coursework as $course)
                                <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs">{{ $course }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-slate-500">No education detected.</p>
            @endforelse
        </div>

        {{-- Skills --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Skills
                <span class="text-sm font-normal text-slate-500">({{ $candidate->skills->count() }} total)</span>
            </h2>

            @php
                $grouped = $candidate->skills->groupBy('category');
            @endphp

            @if($grouped->count() > 0)
                @foreach($grouped as $category => $skills)
                    <div class="mb-4">
                        <h3 class="font-semibold text-sm text-slate-700 mb-2 capitalize">
                            {{ str_replace('_', ' ', $category) }}
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($skills as $skill)
                                <span class="bg-slate-100 text-slate-800 px-3 py-1 rounded-full text-sm">
                                    {{ $skill->skill }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-slate-500">No skills detected.</p>
            @endif
        </div>

        {{-- Projects --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Projects</h2>

            @forelse($candidate->projects as $proj)
                <div class="border-b border-slate-200 pb-4 mb-4">
                    <h3 class="font-bold">{{ $proj->project_name ?? 'Project' }}
                        @if($proj->project_type)
                            <span class="text-sm font-normal text-slate-500">({{ $proj->project_type }})</span>
                        @endif
                    </h3>
                    @if($proj->role) <p class="text-sm text-slate-600">Role: {{ $proj->role }}</p> @endif
                    <p class="text-slate-700 text-sm mt-1">{{ $proj->description ?? '-' }}</p>
                    @if($proj->outcome) <p class="text-sm text-slate-600 mt-1">Outcome: {{ $proj->outcome }}</p> @endif
                    @if($proj->technologies && count($proj->technologies) > 0)
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($proj->technologies as $tech)
                                <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs">{{ $tech }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-slate-500">No projects detected.</p>
            @endforelse
        </div>

        {{-- Certifications --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Certifications</h2>

            @forelse($candidate->certifications as $cert)
                <div class="border-b border-slate-200 pb-3 mb-3">
                    <p class="font-semibold">{{ $cert->name ?? '-' }}</p>
                    <p class="text-sm text-slate-500">{{ $cert->issuer ?? '' }}
                        @if($cert->date_issued) · {{ $cert->date_issued }} @endif
                    </p>
                    @if($cert->credential_link)
                        <a href="{{ $cert->credential_link }}" class="text-xs text-blue-600" target="_blank">View credential</a>
                    @endif
                </div>
            @empty
                <p class="text-slate-500">No certifications detected.</p>
            @endforelse
        </div>

        {{-- Achievements --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-4">Achievements</h2>

            @forelse($candidate->achievements as $ach)
                <div class="border-b border-slate-200 pb-3 mb-3">
                    <p class="font-semibold">{{ $ach->title ?? '-' }}
                        @if($ach->year) <span class="text-sm font-normal text-slate-500">({{ $ach->year }})</span> @endif
                    </p>
                    @if($ach->organization) <p class="text-sm text-slate-500">{{ $ach->organization }}</p> @endif
                    @if($ach->description) <p class="text-sm text-slate-700">{{ $ach->description }}</p> @endif
                </div>
            @empty
                <p class="text-slate-500">No achievements detected.</p>
            @endforelse
        </div>

        {{-- Raw Text --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-2">Raw Extracted Text</h2>
            <p class="text-xs text-slate-500 mb-3">This is exactly what Python extracted before sending to Qwen. Use this to debug missing fields.</p>
            <pre class="bg-slate-50 border border-slate-200 p-4 rounded-lg text-xs overflow-auto max-h-96 whitespace-pre-wrap">{{ $candidate->raw_text ?? 'No raw text available.' }}</pre>
        </div>

        {{-- Parsed JSON --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-bold mb-2">Parsed JSON Debug</h2>
            <p class="text-xs text-slate-500 mb-3">This is the full JSON returned by Python and stored in MySQL. If fields appear in JSON but not on this page, it is a Blade display issue.</p>
            <pre class="bg-slate-900 text-green-400 p-4 rounded-lg text-xs overflow-auto max-h-96 whitespace-pre-wrap">{{ json_encode($candidate->parsed_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>

    </div>
</div>
@endsection
