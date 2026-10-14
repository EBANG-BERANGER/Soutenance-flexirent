<?php

namespace App\Http\Controllers;

/**
 * Static marketing/legal pages linked from the footer. Content is
 * placeholder copy for now, meant to be refined later rather than leaving
 * dead links in the footer.
 */
class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function services()
    {
        return view('pages.services');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function legalNotice()
    {
        return view('pages.legal-notice');
    }
}
