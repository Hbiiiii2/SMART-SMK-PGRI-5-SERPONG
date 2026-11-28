<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RombelRequest;
use App\Models\ClassHomeroom;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\RiwayatKelasSiswa;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RombelController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $tahunAjarans = TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();
        $activeYear = $tahunAjarans->firstWhere('is_aktif', true);

        $rombels = Rombel::with(['tahunAjaran', 'waliKelas', 'jurusan'])
            ->withCount('siswas')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($query) use ($s) {
                    $query->where('nama', 'like', "%{$s}%")
                        ->orWhere('kompetensi_keahlian', 'like', "%{$s}%")
                        ->orWhere('tingkat', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('tahun_ajaran_id'), fn ($q) => $q->where('tahun_ajaran_id', $request->tahun_ajaran_id))
            ->when($request->filled('tingkat'), fn ($q) => $q->where('tingkat', $request->tingkat))
            ->when($request->filled('jurusan_id'), fn ($q) => $q->where('jurusan_id', $request->jurusan_id))
            ->when($request->filled('is_aktif'), fn ($q) => $q->where('is_aktif', (bool) $request->is_aktif))
            ->orderByDesc('is_aktif')
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        $totalRombel = Rombel::count();
        $aktifRombel = Rombel::where('is_aktif', true)->count();
        $totalSiswaTerpetakan = Siswa::whereNotNull('rombel_id')->count();

        return view('pages.admin.rombel.index', [
            'title' => 'Master Data Kelas / Rombel',
            'rombels' => $rombels,
            'tahunAjarans' => $tahunAjarans,
            'jurusans' => Jurusan::aktif()->orderBy('nama')->get(),
            'activeYear' => $activeYear,
            'tingkatOptions' => ['X', 'XI', 'XII'],
            'totalRombel' => $totalRombel,
            'aktifRombel' => $aktifRombel,
            'totalSiswaTerpetakan' => $totalSiswaTerpetakan,
        ]);
    }

    public function create()
    {
        return view('pages.admin.rombel.create', [
            'title' => 'Tambah Kelas / Rombel',
            'tahunAjarans' => TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get(),
            'gurus' => Guru::orderBy('nama_lengkap')->get(),
            'jurusans' => Jurusan::aktif()->orderBy('nama')->get(),
            'tingkatOptions' => ['X', 'XI', 'XII'],
        ]);
    }

    public function store(RombelRequest $request)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->has('is_aktif') ? $request->boolean('is_aktif') : true;

        $meta = Rombel::inferMetaFromName($data['nama']);
        $data['tingkat'] = ($data['tingkat'] ?? null) ?: $meta['tingkat'];

        if (!empty($data['jurusan_id'])) {
            $jurusan = Jurusan::find($data['jurusan_id']);
            if ($jurusan) {
                $data['kompetensi_keahlian'] = $jurusan->nama;
            }
        } else {
            $jurusan = Jurusan::resolveFromRombelName($data['nama']);
            if ($jurusan) {
                $data['jurusan_id'] = $jurusan->id;
                $data['kompetensi_keahlian'] = ($data['kompetensi_keahlian'] ?? null) ?: $jurusan->nama;
            } else {
                $data['kompetensi_keahlian'] = ($data['kompetensi_keahlian'] ?? null) ?: $meta['kompetensi_keahlian'];
            }
        }

        $rombel = DB::transaction(function () use ($data) {
            $rombel = Rombel::create($data);
            $rombel->syncHomeroom();
            return $rombel;
        });

        CacheService::flushDropdowns();
        $this->logActivity('create_rombel', 'Menambah kelas/rombel: ' . $rombel->nama, $request);

        return redirect()->route('admin.kelas.index')->with('success', "Kelas {$rombel->nama} berhasil ditambahkan.");
    }

    public function edit(Rombel $rombel)
    {
        return view('pages.admin.rombel.edit', [
            'title' => 'Edit Kelas / Rombel',
            'rombel' => $rombel->load('tahunAjaran', 'waliKelas', 'jurusan'),
            'tahunAjarans' => TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get(),
            'gurus' => Guru::orderBy('nama_lengkap')->get(),
            'jurusans' => Jurusan::aktif()->orderBy('nama')->get(),
            'tingkatOptions' => ['X', 'XI', 'XII'],
        ]);
    }

    public function update(RombelRequest $request, Rombel $rombel)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->has('is_aktif') ? $request->boolean('is_aktif') : false;

        if (!empty($data['jurusan_id'])) {
            $jurusan = Jurusan::find($data['jurusan_id']);
            if ($jurusan) {
                $data['kompetensi_keahlian'] = $jurusan->nama;
            }
        } else {
            $meta = Rombel::inferMetaFromName($data['nama']);
            $data['kompetensi_keahlian'] = ($data['kompetensi_keahlian'] ?? null) ?: $meta['kompetensi_keahlian'];
        }

        $oldNama = $rombel->nama;
        $oldTahunAjaranId = $rombel->tahun_ajaran_id;

        DB::transaction(function () use ($rombel, $data, $oldNama, $oldTahunAjaranId) {
            $rombel->update($data);

            if ($oldNama !== $rombel->nama) {
                Siswa::where('rombel_id', $rombel->id)
                    ->orWhere(function ($q) use ($oldNama, $oldTahunAjaranId) {
                        $q->where('kelas', $oldNama)
                            ->where('tahun_ajaran_id', $oldTahunAjaranId);
                    })
                    ->update([
                        'kelas' => $rombel->nama,
                        'rombel_id' => $rombel->id,
                    ]);

                Jadwal::where('kelas', $oldNama)
                    ->where('tahun_ajaran_id', $oldTahunAjaranId)
                    ->update(['kelas' => $rombel->nama]);

                ClassHomeroom::where('kelas', $oldNama)
                    ->where('tahun_ajaran_id', $oldTahunAjaranId)
                    ->update(['kelas' => $rombel->nama]);

                RiwayatKelasSiswa::where('rombel_id', $rombel->id)
                    ->update(['kelas' => $rombel->nama]);
            }

            $rombel->syncHomeroom();
        });

        CacheService::flushDropdowns();
        $this->logActivity('update_rombel', "Mengubah kelas/rombel: {$oldNama} -> {$rombel->nama}", $request);

        return redirect()->route('admin.kelas.index')->with('success', "Kelas {$rombel->nama} berhasil diperbarui.");
    }

    public function destroy(Rombel $rombel)
    {
        $studentCount = Siswa::where('rombel_id', $rombel->id)
            ->orWhere(function ($q) use ($rombel) {
                $q->where('kelas', $rombel->nama)
                    ->where('tahun_ajaran_id', $rombel->tahun_ajaran_id);
            })
            ->count();

        if ($studentCount > 0) {
            return redirect()->route('admin.kelas.index')->with(
                'error',
                "Kelas {$rombel->nama} tidak dapat dihapus karena masih memiliki {$studentCount} siswa terdaftar."
            );
        }

        $nama = $rombel->nama;
        $rombel->delete();

        CacheService::flushDropdowns();
        $this->logActivity('delete_rombel', 'Menghapus kelas/rombel: ' . $nama);

        return redirect()->route('admin.kelas.index')->with('success', "Kelas {$nama} berhasil dihapus.");
    }
}
