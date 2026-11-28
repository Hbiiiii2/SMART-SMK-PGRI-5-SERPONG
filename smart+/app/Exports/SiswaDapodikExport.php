<?php

namespace App\Exports;

use App\Models\Siswa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SiswaDapodikExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private int $rowNumber = 0;

    public function __construct(
        protected ?int $rombelId = null,
        protected ?string $statusSiswa = null,
        protected ?int $tahunAjaranId = null
    ) {}

    public function collection(): Collection
    {
        return Siswa::with(['rombel', 'tahunAjaran', 'profil', 'orangTuas', 'latestPeriodik'])
            ->when($this->rombelId, fn ($q) => $q->where('rombel_id', $this->rombelId))
            ->when($this->statusSiswa, fn ($q) => $q->where('status_siswa', $this->statusSiswa))
            ->when($this->tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $this->tahunAjaranId))
            ->orderBy('kelas')
            ->orderBy('nama_lengkap')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Rombel Saat Ini',
            'Nama',
            'NIPD',
            'JK',
            'NISN',
            'Tempat Lahir',
            'Tanggal Lahir',
            'NIK',
            'Agama',
            'Status Siswa',
            'Alamat',
            'RT',
            'RW',
            'Dusun',
            'Kelurahan',
            'Kecamatan',
            'Kode Pos',
            'Jenis Tinggal',
            'Alat Transportasi',
            'Telepon',
            'HP',
            'WA Aktif',
            'E-Mail Kontak',
            'No KK',
            'No Registrasi Akta Lahir',
            'Sekolah Asal',
            'Anak ke-berapa',
            'Lintang',
            'Bujur',
            'Penerima KPS',
            'No. KPS',
            'Penerima KIP',
            'Nomor KIP',
            'Nama di KIP',
            'Nomor KKS',
            'Layak PIP',
            'Alasan Layak PIP',
            'Bank',
            'Nomor Rekening Bank',
            'Rekening Atas Nama',
            'Nama Ayah',
            'NIK Ayah',
            'Tahun Lahir Ayah',
            'Pendidikan Ayah',
            'Pekerjaan Ayah',
            'Penghasilan Ayah',
            'Nama Ibu',
            'NIK Ibu',
            'Tahun Lahir Ibu',
            'Pendidikan Ibu',
            'Pekerjaan Ibu',
            'Penghasilan Ibu',
            'Nama Wali',
            'NIK Wali',
            'Pekerjaan Wali',
            'Penghasilan Wali',
            'Berat Badan',
            'Tinggi Badan',
            'Lingkar Kepala',
            'Jml. Saudara Kandung',
            'Jarak Rumah ke Sekolah (KM)',
        ];
    }

    public function map($siswa): array
    {
        $this->rowNumber++;
        $p = $siswa->profil;
        $ayah = $siswa->orangTuas->firstWhere('hubungan', 'ayah');
        $ibu = $siswa->orangTuas->firstWhere('hubungan', 'ibu');
        $wali = $siswa->orangTuas->firstWhere('hubungan', 'wali');
        $per = $siswa->latestPeriodik;

        return [
            $this->rowNumber,
            $siswa->rombel?->nama ?? $siswa->kelas ?? '-',
            $siswa->nama_lengkap,
            $siswa->nipd ? "'" . $siswa->nipd : '',
            $siswa->jenis_kelamin ?? '',
            $siswa->nisn ? "'" . $siswa->nisn : '',
            $siswa->tempat_lahir ?? '',
            $siswa->tanggal_lahir?->format('Y-m-d') ?? '',
            $siswa->nik ? "'" . $siswa->nik : '',
            $siswa->agama ?? '',
            $siswa->status_label,
            $p?->alamat_jalan ?? $siswa->alamat ?? '',
            $p?->rt ?? '',
            $p?->rw ?? '',
            $p?->nama_dusun ?? '',
            $p?->kelurahan ?? '',
            $p?->kecamatan ?? '',
            $p?->kode_pos ?? '',
            $p?->jenis_tinggal ?? '',
            $p?->alat_transportasi ?? '',
            $siswa->telepon_rumah ?? '',
            $siswa->no_telepon ?? '',
            $siswa->no_wa_aktif ?? '',
            $siswa->email_pribadi ?? '',
            $siswa->no_kk ? "'" . $siswa->no_kk : '',
            $p?->no_registrasi_akta_lahir ?? '',
            $p?->sekolah_asal ?? '',
            $p?->anak_keberapa ?? '',
            $p?->lintang ?? '',
            $p?->bujur ?? '',
            $p?->penerima_kps ? 'Ya' : 'Tidak',
            $p?->no_kps ?? '',
            $p?->penerima_kip ? 'Ya' : 'Tidak',
            $p?->nomor_kip ?? '',
            $p?->nama_di_kip ?? '',
            $p?->nomor_kks ?? '',
            $p?->layak_pip ? 'Ya' : 'Tidak',
            $p?->alasan_layak_pip ?? '',
            $p?->bank ?? '',
            $p?->nomor_rekening_bank ? "'" . $p->nomor_rekening_bank : '',
            $p?->rekening_atas_nama ?? '',
            $ayah?->nama ?? '',
            $ayah?->nik ? "'" . $ayah->nik : '',
            $ayah?->tahun_lahir ?? '',
            $ayah?->jenjang_pendidikan ?? '',
            $ayah?->pekerjaan ?? '',
            $ayah?->penghasilan ?? '',
            $ibu?->nama ?? '',
            $ibu?->nik ? "'" . $ibu->nik : '',
            $ibu?->tahun_lahir ?? '',
            $ibu?->jenjang_pendidikan ?? '',
            $ibu?->pekerjaan ?? '',
            $ibu?->penghasilan ?? '',
            $wali?->nama ?? '',
            $wali?->nik ? "'" . $wali->nik : '',
            $wali?->pekerjaan ?? '',
            $wali?->penghasilan ?? '',
            $per?->berat_badan ?? '',
            $per?->tinggi_badan ?? '',
            $per?->lingkar_kepala ?? '',
            $per?->jumlah_saudara_kandung ?? '',
            $per?->jarak_rumah_km ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
