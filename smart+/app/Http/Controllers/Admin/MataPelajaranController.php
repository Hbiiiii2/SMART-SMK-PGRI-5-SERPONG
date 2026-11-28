<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MataPelajaranRequest;
use App\Models\Jurusan;
use App\Models\MataPelajaran;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class MataPelajaranController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $mataPelajarans = MataPelajaran::query()
            ->with('jurusan')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($query) use ($s) {
                    $query->where('kode', 'like', "%{$s}%")
                        ->orWhere('nama', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('kelompok'), fn ($q) => $q->where('kelompok', $request->kelompok))
            ->when($request->filled('jurusan_id'), function ($q) use ($request) {
                if ($request->jurusan_id === 'umum') {
                    $q->whereNull('jurusan_id');
                } else {
                    $q->where('jurusan_id', (int) $request->jurusan_id);
                }
            })
            ->when($request->filled('tingkat'), fn ($q) => $q->where('tingkat', 'like', '%"' . $request->tingkat . '"%'))
            ->when($request->filled('tampil_di_rapor'), fn ($q) => $q->where('tampil_di_rapor', (bool) $request->tampil_di_rapor))
            ->orderByRaw("CASE kelompok WHEN 'umum' THEN 1 WHEN 'kejuruan' THEN 2 WHEN 'konsentrasi' THEN 3 WHEN 'pilihan' THEN 4 WHEN 'mulok' THEN 5 ELSE 99 END")
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.mata-pelajaran.index', [
            'title' => 'Mata Pelajaran',
            'mataPelajarans' => $mataPelajarans,
            'jurusans' => Jurusan::aktif()->orderBy('nama')->get(),
            'kelompokOptions' => MataPelajaran::KELOMPOK_OPTIONS,
            'tingkatOptions' => ['X', 'XI', 'XII'],
        ]);
    }

    public function create()
    {
        return view('pages.admin.mata-pelajaran.create', [
            'title' => 'Tambah Mata Pelajaran',
            'jurusans' => Jurusan::aktif()->orderBy('nama')->get(),
            'kelompokOptions' => MataPelajaran::KELOMPOK_OPTIONS,
            'tingkatOptions' => ['X', 'XI', 'XII'],
        ]);
    }

    public function store(MataPelajaranRequest $request)
    {
        $data = $this->preparePayload($request->validated());

        MataPelajaran::create($data);
        CacheService::flushDropdowns();
        $this->logActivity('create_mapel');

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(MataPelajaran $mata_pelajaran)
    {
        return view('pages.admin.mata-pelajaran.edit', [
            'title' => 'Edit Mata Pelajaran',
            'mataPelajaran' => $mata_pelajaran->load('jurusan'),
            'jurusans' => Jurusan::aktif()->orderBy('nama')->get(),
            'kelompokOptions' => MataPelajaran::KELOMPOK_OPTIONS,
            'tingkatOptions' => ['X', 'XI', 'XII'],
        ]);
    }

    public function update(MataPelajaranRequest $request, MataPelajaran $mata_pelajaran)
    {
        $data = $this->preparePayload($request->validated());

        $mata_pelajaran->update($data);
        CacheService::flushDropdowns();
        $this->logActivity('update_mapel');

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(MataPelajaran $mata_pelajaran)
    {
        CacheService::flushDropdowns();

        $mata_pelajaran->delete();

        $this->logActivity('delete_mapel');

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }

    protected function preparePayload(array $data): array
    {
        $data['kelompok'] = $data['kelompok'] ?? 'umum';
        $data['jurusan_id'] = !empty($data['jurusan_id']) ? (int) $data['jurusan_id'] : null;
        $levels = array_values(array_filter($data['tingkat'] ?? []));
        $data['tingkat'] = empty($levels) ? null : $levels;
        $data['urutan'] = $data['urutan'] ?? 100;
        $data['tampil_di_rapor'] = array_key_exists('tampil_di_rapor', $data) ? (bool) $data['tampil_di_rapor'] : true;

        return $data;
    }
}
