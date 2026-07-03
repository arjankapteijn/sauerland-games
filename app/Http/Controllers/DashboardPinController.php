<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardPinController extends Controller
{
    public function show(): View
    {
        return view('dashboard-pin');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['pin' => ['required', 'string']]);

        $expected = (string) config('services.signal.dashboard_pin');

        if ($expected === '' || ! hash_equals($expected, $validated['pin'])) {
            return back()->withErrors(['pin' => 'Onjuiste pincode.']);
        }

        $request->session()->regenerate();
        $request->session()->put('dashboard_authenticated', true);

        return redirect()->route('dashboard');
    }
}
