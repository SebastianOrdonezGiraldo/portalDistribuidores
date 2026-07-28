<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy', [
            'controller' => config('legal.controller'),
            'policyVersion' => config('legal.privacy_policy_version'),
        ]);
    }

    public function treatment(): View
    {
        return view('legal.treatment', [
            'controller' => config('legal.controller'),
            'policyVersion' => config('legal.privacy_policy_version'),
        ]);
    }

    public function terms(): View
    {
        return view('legal.terms', [
            'controller' => config('legal.controller'),
            'policyVersion' => config('legal.privacy_policy_version'),
        ]);
    }
}
