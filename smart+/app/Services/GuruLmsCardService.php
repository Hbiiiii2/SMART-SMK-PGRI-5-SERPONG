<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Support\Collection;

class GuruLmsCardService
{
    public const DAY_ORDER = [
        'Senin' => 1,
        'Selasa' => 2,
        'Rabu' => 3,
        'Kamis' => 4,
        'Jumat' => 5,
        'Sabtu' => 6,
        'Minggu' => 7,
    ];

    public const KELOMPOK_BADGE = [
        'umum' => 'MAPEL UMUM',
        'kejuruan' => 'MAPEL KEJURUAN',
        'konsentrasi' => 'KONSENTRASI KEAHLIAN',
        'pilihan' => 'MAPEL PILIHAN',
        'mulok' => 'MUATAN LOKAL',
    ];

    /**
     * Build LMS class cards grouped by (kelas, mata_pelajaran_id) for a teacher.
     */
    public function buildClassCards(int $guruId, ?string $todayName = null): Collection
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first();

        $query = Jadwal::where('guru_id', $guruId)
            ->with(['mataPelajaran:id,kode,nama,kelompok', 'lokasi:id,nama']);

        if ($activeYear) {
            $activeYearJadwals = (clone $query)->where('tahun_ajaran_id', $activeYear->id)->get();
            $jadwals = $activeYearJadwals->isNotEmpty()
                ? $activeYearJadwals
                : $query->get();
        } else {
            $jadwals = $query->get();
        }

        if ($jadwals->isEmpty()) {
            return collect();
        }

        $kelasNames = $jadwals->pluck('kelas')->filter()->unique()->values();

        // Batch student counts per class
        $siswaCounts = Siswa::whereIn('kelas', $kelasNames)
            ->where(function ($q) {
                $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
            })
            ->selectRaw('kelas, COUNT(*) as cnt')
            ->groupBy('kelas')
            ->pluck('cnt', 'kelas');

        // Batch Materi counts per (kelas, mata_pelajaran_id)
        $materiCounts = Materi::where('guru_id', $guruId)
            ->selectRaw('kelas, mata_pelajaran_id, COUNT(*) as cnt')
            ->groupBy('kelas', 'mata_pelajaran_id')
            ->get()
            ->keyBy(fn ($r) => "{$r->kelas}::{$r->mata_pelajaran_id}");

        // Batch Tugas & ungraded submissions per (kelas, mata_pelajaran_id)
        $tugasRows = Tugas::where('guru_id', $guruId)
            ->get(['id', 'kelas', 'mata_pelajaran_id']);
        $tugasCounts = $tugasRows->groupBy(fn ($t) => "{$t->kelas}::{$t->mata_pelajaran_id}")
            ->map->count();

        $ungradedByKey = [];
        if ($tugasRows->isNotEmpty()) {
            $tugasToKey = $tugasRows->mapWithKeys(fn ($t) => [$t->id => "{$t->kelas}::{$t->mata_pelajaran_id}"]);
            $ungradedSubs = PengumpulanTugas::whereIn('tugas_id', $tugasRows->pluck('id'))
                ->where(function ($q) {
                    $q->whereNull('nilai')->orWhere('status', '!=', 'dinilai');
                })
                ->selectRaw('tugas_id, COUNT(*) as cnt')
                ->groupBy('tugas_id')
                ->get();

            foreach ($ungradedSubs as $row) {
                $k = $tugasToKey[$row->tugas_id] ?? null;
                if ($k) {
                    $ungradedByKey[$k] = ($ungradedByKey[$k] ?? 0) + (int) $row->cnt;
                }
            }
        }

        $dayOrder = self::DAY_ORDER;

        return $jadwals
            ->groupBy(fn ($j) => "{$j->kelas}::{$j->mata_pelajaran_id}")
            ->map(function (Collection $group, string $key) use ($dayOrder, $todayName, $siswaCounts, $materiCounts, $tugasCounts, $ungradedByKey) {
                $sorted = $group->sortBy(function ($j) use ($dayOrder) {
                    $d = $dayOrder[$j->hari] ?? 99;
                    return sprintf('%02d-%s', $d, $j->jam_mulai ?? '00:00');
                })->values();

                $first = $sorted->first();
                $mapel = $first->mataPelajaran;
                $days = $sorted->pluck('hari')->filter()->unique()->values()->all();

                $sessions = $sorted->map(fn ($j) => [
                    'id' => $j->id,
                    'hari' => $j->hari,
                    'jam' => substr((string) $j->jam_mulai, 0, 5) . '-' . substr((string) $j->jam_selesai, 0, 5),
                    'lokasi' => $j->lokasi?->nama ?? ('Ruang ' . $j->kelas),
                ])->values()->all();

                $hariJamSummary = collect($sessions)
                    ->map(fn ($s) => "{$s['hari']}, {$s['jam']}")
                    ->unique()
                    ->implode(' • ');

                $kelompok = $mapel?->kelompok ?? 'umum';
                $kelompokBadge = self::KELOMPOK_BADGE[$kelompok] ?? 'MATA PELAJARAN';

                return [
                    'key' => $key,
                    'kelas' => $first->kelas,
                    'mapel_id' => (int) $first->mata_pelajaran_id,
                    'mapel_nama' => $mapel?->nama ?? '-',
                    'mapel_kode' => $mapel?->kode ?? '-',
                    'kelompok' => $kelompok,
                    'kelompok_badge' => $kelompokBadge,
                    'days' => $days,
                    'hari_label' => implode(', ', $days) ?: '-',
                    'is_today' => $todayName ? in_array($todayName, $days, true) : false,
                    'earliest_day_order' => $dayOrder[$days[0] ?? ''] ?? 99,
                    'sessions' => $sessions,
                    'hari_jam_summary' => $hariJamSummary ?: '-',
                    'lokasi_nama' => $first->lokasi?->nama ?? ('Ruang ' . $first->kelas),
                    'siswa_count' => (int) ($siswaCounts[$first->kelas] ?? 0),
                    'materi_count' => (int) ($materiCounts[$key]->cnt ?? 0),
                    'tugas_count' => (int) ($tugasCounts[$key] ?? 0),
                    'belum_dinilai_count' => (int) ($ungradedByKey[$key] ?? 0),
                    'first_jadwal_id' => (int) $first->id,
                ];
            })
            ->sort(function ($a, $b) {
                if ($a['earliest_day_order'] !== $b['earliest_day_order']) {
                    return $a['earliest_day_order'] <=> $b['earliest_day_order'];
                }
                $clsCmp = strnatcasecmp($a['kelas'], $b['kelas']);
                return $clsCmp !== 0 ? $clsCmp : strnatcasecmp($a['mapel_nama'], $b['mapel_nama']);
            })
            ->values();
    }
}
