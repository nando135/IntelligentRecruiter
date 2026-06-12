<?php

namespace App\Http\Controllers;

use App\Models\ApprovedCandidate;
use Illuminate\Http\Request;

class ApprovedCandidateController extends Controller
{
    public function index(Request $request)
    {
        $categories = ['IT', 'Business', 'Data', 'Marketing', 'Finance', 'Operations'];
        $emailStatuses = ['queued', 'sent', 'failed', 'not_sent'];

        $selectedCategory = $request->query('category');
        $selectedEmailStatus = $request->query('email_status');
        $search = $request->query('search');

        $query = ApprovedCandidate::with(['candidate', 'emailTemplate'])
            ->latest('approved_at');

        if ($selectedCategory && in_array($selectedCategory, $categories, true)) {
            $query->where('candidate_category_snapshot', $selectedCategory);
        }

        if ($selectedEmailStatus && in_array($selectedEmailStatus, $emailStatuses, true)) {
            $query->where('email_status', $selectedEmailStatus);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name_snapshot', 'like', '%' . $search . '%')
                    ->orWhere('email_snapshot', 'like', '%' . $search . '%');
            });
        }

        $approvedCandidates = $query->paginate(10)->withQueryString();

        return view('approved-candidates.index', compact(
            'approvedCandidates',
            'categories',
            'emailStatuses',
            'selectedCategory',
            'selectedEmailStatus',
            'search'
        ));
    }
}
