<?php

namespace App\Http\Controllers;

use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(private TenantContextResolver $resolver) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        
        $companies = $user->companies()->get();
        $currentCompany = $this->resolver->getCompany();
        $currentBranch = $this->resolver->getBranch();
        $currentDepartment = $this->resolver->getDepartment();

        return view('home', [
            'userCompanies' => $companies,
            'currentCompany' => $currentCompany,
            'currentBranch' => $currentBranch,
            'currentDepartment' => $currentDepartment,
        ])->with('page_title', 'Dashboard');
    }
}
