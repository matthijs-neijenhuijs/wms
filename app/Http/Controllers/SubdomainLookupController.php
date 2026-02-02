<?php

namespace App\Http\Controllers;

use App\Models\Subdomain;
use Illuminate\Http\Request;

class SubdomainLookupController extends Controller
{
    public function show()
    {
        return view('subdomain-lookup');
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'subdomain' => 'required|string|max:255',
        ]);

        $subdomain = Subdomain::where('subdomain', $request->input('subdomain'))->first();

        if (! $subdomain) {
            return back()->withErrors([
                'subdomain' => 'Subdomain not found. Please check and try again.',
            ])->withInput();
        }

        $centralDomain = config('app.central_domain', 'wms.test');
        $url = 'http://'.$subdomain->subdomain.'.'.$centralDomain.'/login';

        return redirect()->away($url);
    }
}
