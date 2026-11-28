<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JurusanRequest;
use App\Models\Jurusan;
use App\Models\Rombel;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurusanController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $jurusans = Jurusan::query()
            ->withCount(['rombels', 'mataPelajarans'])
            ->when($request->search, function ($q, $s) {
                $q->where(function ($query) use ($s) {
                    $query->where('kode', 'like', "%{$s}%")
                        ->orWhere('singkatan', 'like', "%{$s}%")
                        ->orWhere('nama', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('is_aktif')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.jurusan.index', [
            'title' => 'Master Data Jurusan / Kejuruan',
            'jurusans' => $jurusans,
        ]);
    }

    public function create()
    {
        return view('pages.admin.jurusan.create', [
            'title' => 'Tambah Jurusan / Kejuruan',
        ]);
    }

    public function store(JurusanRequest $request)
    {
        $data = $request->validated();
        $data['kode'] = strtoupper(trim($data['kode']));
        $data['singkatan'] = !empty($data['singkatan']) ? strtoupper(trim($data['singkatan'])) : null;
        $data['is_aktif'] = $request->has('is_aktif') ? $request->boolean('is_aktif') : true;

        $jurusan = Jurusan::create($data);

        CacheService::flushDropdowns();
        $this->logActivity('create_jurusan', 'Menambah jurusan: ' . $jurusan->nama, $request);

        return redirect()->route('admin.jurusan.index')
            ->with('success', "Jurusan {$jurusan->nama} ({$jurusan->kode}) berhasil ditambahkan.");
    }

    public function edit(Jurusan $jurusan)
    {
        return view('pages.admin.jurusan.edit', [
            'title' => 'Edit Jurusan / Kejuruan',
            'jurusan' => $jurusan->loadCount(['rombels', 'mataPelajarans']),
        ]);
    }

    public function update(JurusanRequest $request, Jurusan $jurusan)
    {
        $data = $request->validated();
        $data['kode'] = strtoupper(trim($data['kode']));
        $data['singkatan'] = !empty($data['singkatan']) ? strtoupper(trim($data['singkatan'])) : null;
        $data['is_aktif'] = $request->has('is_aktif') ? $request->boolean('is_aktif') : false;

        DB::transaction(function () use ($jurusan, $data) {
            $oldNama = $jurusan->nama;
            $jurusan->update($data);

            if ($oldNama !== $jurusan->nama) {
                Rombel::where('jurusan_id', $jurusan->id)
                    ->update(['kompetensi_keahlian' => $jurusan->nama]);
            }
        });

        CacheService::flushDropdowns();
        $this->logActivity('update_jurusan', 'Memperbarui jurusan: ' . $jurusan->nama, $request);

        return redirect()->route('admin.jurusan.index')
            ->with('success', "Jurusan {$jurusan->nama} berhasil diperbarui.");
    }

    public function destroy(Jurusan $jurusan)
    {
        $rombelCount = $jurusan->rombels()->count();
        $mapelCount = $jurusan->mataPelajarans()->count();

        if ($rombelCount > 0 || $mapelCount > 0) {
            return redirect()->route('admin.jurusan.index')
                ->with('error', "Jurusan {$jurusan->nama} tidak dapat dihapus karena masih terhubung ke {$rombelCount} rombel dan {$mapelCount} mata pelajaran.");
        }

        $nama = $jurusan->nama;
        $jurusan->delete();

        CacheService::flushDropdowns();
        $this->logActivity('delete_jurusan', 'Menghapus jurusan: ' . $nama);

        return redirect()->route('admin.jurusan.index')
            ->with('success', "Jurusan {$nama} berhasil dihapus.");
    }
}
