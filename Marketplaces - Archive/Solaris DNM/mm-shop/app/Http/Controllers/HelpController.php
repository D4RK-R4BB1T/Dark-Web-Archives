<?php
/**
 * File: HelpController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function employee(Request $request, $pageName = null)
    {
        $content = null;
        $filePath = app_path() . '/../resources/views/help/contents/' . $pageName . '.md';
        if (\File::exists($filePath)) {
            $content = markdown(file_get_contents($filePath));
        }

        if (view()->exists('help.contents.' . $pageName)) {
            $content = view()->make('help.contents.' . $pageName);
        }

        return view('help.employee', [
            'pageName' => $pageName,
            'content' => $content
        ]);
    }
}