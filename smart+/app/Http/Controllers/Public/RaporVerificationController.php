<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\RaporArchive;

class RaporVerificationController extends Controller
{
    public function show(string $token)
    {
        $archive = RaporArchive::where('verification_token', $token)->first();

        if (!$archive) {
            return response()->view('pages.public.rapor-verify', [
                'found' => false,
                'archive' => null,
                'integrityValid' => false,
                'payload' => [],
            ], 404);
        }

        $integrityValid = $archive->verifyIntegrity();

        return view('pages.public.rapor-verify', [
            'found' => true,
            'archive' => $archive,
            'integrityValid' => $integrityValid,
            'payload' => $archive->payload ?? [],
        ]);
    }
}
