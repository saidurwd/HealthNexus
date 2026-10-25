<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Lang;

/** One-page printable role cards, Bangla and English side by side (guideline 19.7). Open to every signed-in user. */
class QuickStartController extends Controller
{
    public function index()
    {
        return view('admin.help.quick-start-index', ['roles' => collect(Lang::get('quickstart', [], 'en'))->map(fn ($c) => $c['title'])]);
    }

    public function show(string $role)
    {
        $en = Lang::get("quickstart.$role", [], 'en');
        abort_unless(is_array($en), 404);

        return view('admin.help.quick-start', ['en' => $en, 'bn' => Lang::get("quickstart.$role", [], 'bn'), 'role' => $role]);
    }
}
