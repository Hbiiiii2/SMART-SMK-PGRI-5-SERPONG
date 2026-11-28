<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Materi;
use App\Services\GuruLmsCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MateriController extends Controller
{
    public function index(Request $request)
    {
        $guruId = auth()->user()->guru->id;
        $selectedKelas = trim((string) $request->query('kelas', '')) ?: null;
        $selectedMapelId = $request->filled('mata_pelajaran_id') ? (int) $request->query('mata_pelajaran_id') : null;

        $query = Materi::where('guru_id', $guruId)->with('mataPelajaran');
        if ($selectedKelas) {
            $query->where('kelas', $selectedKelas);
        }
        if ($selectedMapelId) {
            $query->where('mata_pelajaran_id', $selectedMapelId);
        }

        $materi = $query->latest()->get();
        $lmsClassCards = app(GuruLmsCardService::class)->buildClassCards($guruId);

        return view('pages.guru.materi.index', compact('materi', 'lmsClassCards', 'selectedKelas', 'selectedMapelId'));
    }

    public function create(Request $request)
    {
        $guruId = auth()->user()->guru->id;
        // Get unique kelas and mata_pelajaran for this guru from jadwal
        $jadwals = Jadwal::where('guru_id', $guruId)->with('mataPelajaran')->get();

        $kelasOptions = $jadwals->pluck('kelas')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
        $mapelOptions = $jadwals->pluck('mataPelajaran')->unique('id')->values();
        $selectedKelas = $request->query('kelas');
        $selectedMapelId = $request->query('mata_pelajaran_id');
        $redirectTo = $request->query('redirect_to');

        return view('pages.guru.materi.create', compact(
            'kelasOptions',
            'mapelOptions',
            'selectedKelas',
            'selectedMapelId',
            'redirectTo'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kelas' => 'required|string',
            'mata_pelajaran_id' => [
                'required',
                'exists:mata_pelajaran,id',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = \App\Models\Jadwal::where('guru_id', auth()->user()->guru->id)
                        ->where('kelas', $request->kelas)
                        ->where('mata_pelajaran_id', $value)
                        ->exists();

                    if (!$exists) {
                        $fail('Mata pelajaran ini tidak terdaftar untuk kelas yang dipilih pada jadwal Anda.');
                    }
                },
            ],
            'deskripsi' => 'nullable|string',
            'tipe_materi' => 'required|in:pdf,youtube,lainnya',
            'file_materi' => 'nullable|file|mimes:pdf|max:10240', // max 10MB
            'url_materi' => 'nullable|url'
        ]);

        $materi = new Materi($request->except(['file_materi', 'url_materi', 'redirect_to']));
        $materi->guru_id = auth()->user()->guru->id;

        if ($request->tipe_materi === 'pdf' && $request->hasFile('file_materi')) {
            $path = $request->file('file_materi')->store('materi', 'public');
            $materi->file_path_atau_url = $path;
        } elseif ($request->tipe_materi === 'youtube' && $request->filled('url_materi')) {
            $materi->file_path_atau_url = $request->url_materi;
        } elseif ($request->tipe_materi === 'lainnya' && $request->filled('url_materi')) {
            $materi->file_path_atau_url = $request->url_materi;
        }

        $materi->save();

        if ($request->input('redirect_to') === 'kelas') {
            return redirect()->route('guru.kelas.show', [
                'kelas' => $materi->kelas,
                'mata_pelajaran_id' => $materi->mata_pelajaran_id,
                'tab' => 'materi',
            ])->with('success', 'Materi berhasil ditambahkan ke kelas.');
        }

        return redirect()->route('guru.materi.index', [
            'kelas' => $materi->kelas,
            'mata_pelajaran_id' => $materi->mata_pelajaran_id,
        ])->with('success', 'Materi berhasil ditambahkan.');
    }

    public function show(Materi $materi)
    {
        if ($materi->guru_id !== auth()->user()->guru->id) {
            abort(403);
        }
        return view('pages.guru.materi.show', compact('materi'));
    }

    public function edit(Materi $materi)
    {
        if ($materi->guru_id !== auth()->user()->guru->id) {
            abort(403);
        }

        $jadwals = Jadwal::where('guru_id', auth()->user()->guru->id)->with('mataPelajaran')->get();
        $kelasOptions = $jadwals->pluck('kelas')->unique();
        $mapelOptions = $jadwals->pluck('mataPelajaran')->unique('id');

        return view('pages.guru.materi.edit', compact('materi', 'kelasOptions', 'mapelOptions'));
    }

    public function update(Request $request, Materi $materi)
    {
        if ($materi->guru_id !== auth()->user()->guru->id) {
            abort(403);
        }

        $request->validate([
            'judul' => 'required|string|max:255',
            'kelas' => 'required|string',
            'mata_pelajaran_id' => [
                'required',
                'exists:mata_pelajaran,id',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = \App\Models\Jadwal::where('guru_id', auth()->user()->guru->id)
                        ->where('kelas', $request->kelas)
                        ->where('mata_pelajaran_id', $value)
                        ->exists();

                    if (!$exists) {
                        $fail('Mata pelajaran ini tidak terdaftar untuk kelas yang dipilih pada jadwal Anda.');
                    }
                },
            ],
            'deskripsi' => 'nullable|string',
            'tipe_materi' => 'required|in:pdf,youtube,lainnya',
            'file_materi' => 'nullable|file|mimes:pdf|max:10240',
            'url_materi' => 'nullable|url'
        ]);

        $materi->fill($request->except(['file_materi', 'url_materi']));

        if ($request->tipe_materi === 'pdf' && $request->hasFile('file_materi')) {
            if ($materi->file_path_atau_url && Storage::disk('public')->exists($materi->file_path_atau_url)) {
                Storage::disk('public')->delete($materi->file_path_atau_url);
            }
            $path = $request->file('file_materi')->store('materi', 'public');
            $materi->file_path_atau_url = $path;
        } elseif (in_array($request->tipe_materi, ['youtube', 'lainnya'])) {
            $materi->file_path_atau_url = $request->url_materi;
        }

        $materi->save();

        return redirect()->route('guru.materi.index')->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Materi $materi)
    {
        if ($materi->guru_id !== auth()->user()->guru->id) {
            abort(403);
        }

        if ($materi->tipe_materi === 'pdf' && $materi->file_path_atau_url) {
            Storage::disk('public')->delete($materi->file_path_atau_url);
        }

        $materi->delete();

        return redirect()->route('guru.materi.index')->with('success', 'Materi berhasil dihapus.');
    }
}
