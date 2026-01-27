<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function toggleDarkMode(Request $request)
    {
        $request->session()->put('dark_mode', $request->dark_mode);
        
        return response()->json(['success' => true]);
    }
}