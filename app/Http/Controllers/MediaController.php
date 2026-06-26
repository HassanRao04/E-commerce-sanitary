<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class MediaController extends Controller
{
    public function placeholder(): Response
    {
        $svg = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">
            <rect width="400" height="400" fill="#f3f4f6"/>
            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#9ca3af" font-family="sans-serif" font-size="18">No Image</text>
        </svg>
        SVG;

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
