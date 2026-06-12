<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateAchievement;
use App\Models\CandidateCertification;
use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use App\Models\CandidateLanguage;
use App\Models\CandidateLink;
use App\Models\CandidateProject;
use App\Models\CandidateSkill;
use App\Services\CandidateRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CandidateUploadController extends Controller
{
    public function index(Request $request)
{
    $categories = ['IT', 'Business', 'Data', 'Marketing', 'Finance', 'Operations'];
    $approvalStatuses = ['pending', 'approved', 'rejected'];

    $selectedCategory = $request->query('category');
    $selectedApprovalStatus = $request->query('approval_status');
    $search = $request->query('search');

    $query = Candidate::with('skills')
        ->withCount(['experiences', 'educations', 'skills']);

    if ($selectedCategory && in_array($selectedCategory, $categories, true)) {
        $query->where('candidate_category', $selectedCategory);
    }

    if ($selectedApprovalStatus && in_array($selectedApprovalStatus, $approvalStatuses, true)) {
        $query->where('approval_status', $selectedApprovalStatus);
    }

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('current_job_title', 'like', '%' . $search . '%')
                ->orWhere('latest_company', 'like', '%' . $search . '%');
        });
    }

    $candidates = $query->latest()
        ->paginate(10)
        ->withQueryString();

    return view('candidates.index', compact(
        'candidates',
        'categories',
        'approvalStatuses',
        'selectedCategory',
        'selectedApprovalStatus',
        'search'
    ));
}

    public function create()
    {
        return view('candidates.upload');
    }

    public function store(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'resume' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $file     = $request->file('resume');
        $path     = $file->store('resumes');
        $fileHash = hash_file('sha256', $file->getRealPath());

        $pythonUrl = rtrim(env('PYTHON_AI_URL', 'http://127.0.0.1:8001'), '/');

        try {
            $response = Http::timeout(280)
                ->attach('file', Storage::get($path), $file->getClientOriginalName())
                ->post($pythonUrl . '/parse-cv');
        } catch (\Exception $e) {
            return back()->with('error',
                'Python AI service is offline or unreachable. Make sure FastAPI is running on port 8001. Error: ' . $e->getMessage()
            );
        }

        if (! $response->successful()) {
            return back()->with('error',
                'Python AI service returned an error (HTTP ' . $response->status() . '). Check FastAPI logs.'
            );
        }

        $data = $response->json();

        if (($data['status'] ?? null) !== 'success') {
            return back()->with('error', $data['message'] ?? 'CV parsing failed.');
        }

        Log::info('CV Parse Result', [
            'file'                      => $file->getClientOriginalName(),
            'parser_status'             => $data['parser_status'] ?? 'unknown',
            'parser_warning'            => $data['parser_warning'] ?? null,
            'candidate_name'            => $data['candidate']['full_name'] ?? null,
            'candidate_category'        => $data['classification']['category'] ?? ($data['candidate']['candidate_category'] ?? null),
            'classification_confidence' => $data['classification']['confidence'] ?? null,
            'classification_reason'     => $data['classification']['reason'] ?? null,
        ]);

        $candidate = DB::transaction(function () use ($data, $path, $fileHash, $file) {

            $candidateData  = $data['candidate'] ?? [];
            $classification = $data['classification'] ?? [];
            $email          = $candidateData['email'] ?? null;

            $candidate = null;

            if ($fileHash) {
                $candidate = Candidate::where('file_hash', $fileHash)->first();
            }

            if (! $candidate && $email) {
                $candidate = Candidate::where('email', $email)->first();
            }

            $fields = [
                'full_name'                    => $candidateData['full_name'] ?? null,
                'email'                        => $email,
                'phone'                        => $candidateData['phone'] ?? null,
                'location'                     => $candidateData['location'] ?? null,
                'current_job_title'            => $candidateData['current_job_title'] ?? null,
                'latest_company'               => $candidateData['latest_company'] ?? null,
                'professional_summary'         => $candidateData['professional_summary'] ?? null,
                'total_experience_months'      => $candidateData['total_experience_months'] ?? null,
                'total_experience_years'       => $candidateData['total_experience_years'] ?? null,
                'internship_experience_months' => $candidateData['internship_experience_months'] ?? null,
                'full_time_experience_months'  => $candidateData['full_time_experience_months'] ?? null,
                'resume_file'                  => $path,
                'raw_text'                     => $data['raw_text'] ?? null,
                'parsed_json'                  => $data,
                'parser_status'                => $data['parser_status'] ?? 'unknown',
                'parser_warning'               => $data['parser_warning'] ?? null,
                'source_filename'              => $file->getClientOriginalName(),
                'file_hash'                    => $fileHash,
                'candidate_category'           => $classification['category'] ?? ($candidateData['candidate_category'] ?? null),
                'classification_confidence'    => $classification['confidence'] ?? null,
                'classification_reason'        => $classification['reason'] ?? null,
            ];

            if ($candidate) {
                $candidate->update($fields);
            } else {
                $candidate = Candidate::create($fields);
            }

            $candidate->experiences()->delete();
            $candidate->educations()->delete();
            $candidate->skills()->delete();
            $candidate->projects()->delete();
            $candidate->certifications()->delete();
            $candidate->achievements()->delete();
            $candidate->languages()->delete();
            $candidate->links()->delete();

            $this->saveExperiences($candidate, $data['experiences'] ?? []);
            $this->saveEducations($candidate, $data['education'] ?? []);
            $this->saveSkills($candidate, $data['skills'] ?? []);
            $this->saveProjects($candidate, $data['projects'] ?? []);
            $this->saveCertifications($candidate, $data['certifications'] ?? []);
            $this->saveAchievements($candidate, $data['achievements'] ?? []);
            $this->saveLanguages($candidate, $data['languages'] ?? []);
            $this->saveLinks($candidate, $data['links'] ?? []);

            return $candidate;
        });

        if ($candidate->candidate_category) {
    app(CandidateRankingService::class)->rerankCategory($candidate->candidate_category);
    $candidate->refresh();
}

return redirect()
    ->route('candidates.show', $candidate)
    ->with('success', 'CV scanned, classified, ranked, and saved successfully.');
    }

    public function show(Candidate $candidate)
    {
        $candidate->load([
            'experiences',
            'educations',
            'skills',
            'projects',
            'certifications',
            'achievements',
            'languages',
            'links',
        ]);

        return view('candidates.show', compact('candidate'));
    }

    protected function normalizeArray($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
        }
        if (is_string($value) && $value !== '') {
            return [$value];
        }
        return [];
    }

    protected function normalizeSkills($skills): array
    {
        if (! is_array($skills)) {
            return [];
        }
        return $skills;
    }

    protected function saveExperiences(Candidate $candidate, $experiences): void
    {
        foreach ($this->normalizeArray($experiences) as $exp) {
            if (! is_array($exp)) {
                continue;
            }
            CandidateExperience::create([
                'candidate_id'     => $candidate->id,
                'company_name'     => $exp['company_name'] ?? null,
                'job_title'        => $exp['job_title'] ?? null,
                'employment_type'  => $exp['employment_type'] ?? null,
                'department'       => $exp['department'] ?? null,
                'location'         => $exp['location'] ?? null,
                'start_date'       => $exp['start_date'] ?? null,
                'end_date'         => $exp['end_date'] ?? null,
                'is_current'       => (bool) ($exp['is_current'] ?? false),
                'duration_months'  => $exp['duration_months'] ?? null,
                'responsibilities' => $this->normalizeArray($exp['responsibilities'] ?? []),
                'achievements'     => $this->normalizeArray($exp['achievements'] ?? []),
                'tools_used'       => $this->normalizeArray($exp['tools_used'] ?? []),
                'industry'         => $exp['industry'] ?? null,
            ]);
        }
    }

    protected function saveEducations(Candidate $candidate, $education): void
    {
        foreach ($this->normalizeArray($education) as $edu) {
            if (! is_array($edu)) {
                continue;
            }
            CandidateEducation::create([
                'candidate_id'        => $candidate->id,
                'institution'         => $edu['institution'] ?? null,
                'degree'              => $edu['degree'] ?? null,
                'field_of_study'      => $edu['field_of_study'] ?? null,
                'start_year'          => $edu['start_year'] ?? null,
                'end_year'            => $edu['end_year'] ?? null,
                'cgpa'                => $edu['cgpa'] ?? null,
                'relevant_coursework' => $this->normalizeArray($edu['relevant_coursework'] ?? []),
            ]);
        }
    }

    protected function saveSkills(Candidate $candidate, $skills): void
    {
        $skills = $this->normalizeSkills($skills);

        foreach ($skills as $category => $skillList) {
            foreach ($this->normalizeArray($skillList) as $skill) {
                if (is_string($skill) && trim($skill) !== '') {
                    CandidateSkill::create([
                        'candidate_id' => $candidate->id,
                        'category'     => $category,
                        'skill'        => trim($skill),
                    ]);
                }
            }
        }
    }

    protected function saveProjects(Candidate $candidate, $projects): void
    {
        foreach ($this->normalizeArray($projects) as $proj) {
            if (! is_array($proj)) {
                continue;
            }
            CandidateProject::create([
                'candidate_id' => $candidate->id,
                'project_name' => $proj['project_name'] ?? null,
                'project_type' => $proj['project_type'] ?? null,
                'description'  => $proj['description'] ?? null,
                'technologies' => $this->normalizeArray($proj['technologies'] ?? []),
                'role'         => $proj['role'] ?? null,
                'outcome'      => $proj['outcome'] ?? null,
            ]);
        }
    }

    protected function saveCertifications(Candidate $candidate, $certifications): void
    {
        foreach ($this->normalizeArray($certifications) as $cert) {
            if (! is_array($cert)) {
                continue;
            }
            CandidateCertification::create([
                'candidate_id'    => $candidate->id,
                'name'            => $cert['name'] ?? null,
                'issuer'          => $cert['issuer'] ?? null,
                'date_issued'     => $cert['date_issued'] ?? null,
                'expiry_date'     => $cert['expiry_date'] ?? null,
                'credential_link' => $cert['credential_link'] ?? null,
            ]);
        }
    }

    protected function saveAchievements(Candidate $candidate, $achievements): void
    {
        foreach ($this->normalizeArray($achievements) as $ach) {
            if (! is_array($ach)) {
                continue;
            }
            CandidateAchievement::create([
                'candidate_id' => $candidate->id,
                'title'        => $ach['title'] ?? null,
                'description'  => $ach['description'] ?? null,
                'year'         => $ach['year'] ?? null,
                'organization' => $ach['organization'] ?? null,
            ]);
        }
    }

    protected function saveLanguages(Candidate $candidate, $languages): void
    {
        foreach ($this->normalizeArray($languages) as $lang) {
            if (! is_array($lang)) {
                continue;
            }
            CandidateLanguage::create([
                'candidate_id' => $candidate->id,
                'language'     => $lang['language'] ?? null,
                'proficiency'  => $lang['proficiency'] ?? null,
            ]);
        }
    }

    protected function saveLinks(Candidate $candidate, $links): void
    {
        if (! is_array($links)) {
            $links = [];
        }

        CandidateLink::create([
            'candidate_id' => $candidate->id,
            'linkedin'     => $links['linkedin'] ?? null,
            'github'       => $links['github'] ?? null,
            'portfolio'    => $links['portfolio'] ?? null,
            'website'      => $links['website'] ?? null,
        ]);
    }
}
