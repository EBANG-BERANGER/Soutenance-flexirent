<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * Stores the email only; no mailing-list integration yet. Re-submitting
     * an already-subscribed address is treated as a success rather than a
     * validation error, since from the visitor's point of view it is one.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscription::firstOrCreate(['email' => $validated['email']]);

        return back()->with('status', 'Thanks for subscribing! We\'ll keep you posted.');
    }
}
