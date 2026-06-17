<?php

namespace App\Http\Controllers;

use App\Models\ApprovedCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovedCandidateController extends Controller
{
    public function index(Request $request)
    {
        $categories = ['IT', 'Business', 'Data', 'Marketing', 'Finance', 'Operations'];

        $selectedCategory = $request->query('category');
        $search = $request->query('search');

        $query = ApprovedCandidate::with(['candidate'])
            ->whereHas('candidate', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest('approved_at');

        if ($selectedCategory && in_array($selectedCategory, $categories, true)) {
            $query->where('candidate_category_snapshot', $selectedCategory);
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
            'selectedCategory',
            'search'
        ));
    }
}
