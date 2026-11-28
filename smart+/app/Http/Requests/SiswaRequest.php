<?php

namespace App\Http\Requests;

use App\Traits\NormalizesDateInputs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiswaRequest extends FormRequest
{
    use NormalizesDateInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDateFields(['tanggal_lahir', 'tanggal_masuk', 'tanggal_keluar']);
    }

    public function rules(): array
    {
        $siswa = $this->route('siswa');
        $siswaId = $siswa?->id;
        $userId = $siswa?->user_id;

        $passwordRule = 'nullable|min:8|confirmed';
        if ($this->filled('email') && ($this->isMethod('POST') || $userId === null)) {
            $passwordRule = 'required_with:email|min:8|confirmed';
        }

        return [
            'nama_lengkap' => 'required|string|max:255',
            'rombel_id' => 'nullable|exists:rombels,id|required_without:kelas',
            'kelas' => 'nullable|string|max:50|required_without:rombel_id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',

            // Identitas (semua kolom pelengkap Dapodik opsional)
            'nisn' => ['nullable', 'string', 'max:20', Rule::unique('siswa', 'nisn')->ignore($siswaId)],
            'nipd' => 'nullable|string|max:30',
            'nik' => 'nullable|string|max:20',
            'no_kk' => 'nullable|string|max:20',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date|before:today',
            'agama' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:25',
            'telepon_rumah' => 'nullable|string|max:25',
            'no_wa_aktif' => 'nullable|string|max:25',
            'email_pribadi' => 'nullable|email|max:150',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Status & Dapodik IDs
            'status_siswa' => 'nullable|in:aktif,lulus,mutasi_keluar,drop_out,mengundurkan_diri,wafat',
            'tanggal_masuk' => 'nullable|date',
            'tanggal_keluar' => 'nullable|date',
            'alasan_keluar' => 'nullable|string|max:255',
            'dapodik_peserta_didik_id' => ['nullable', 'string', 'max:36', Rule::unique('siswa', 'dapodik_peserta_didik_id')->ignore($siswaId)],
            'dapodik_registrasi_id' => 'nullable|string|max:36',

            // Akun Login Siswa (terpisah dari email kontak/ortu Dapodik, opsional)
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $passwordRule,

            // Nested Profil Dapodik
            'profil' => 'nullable|array',
            'profil.alamat_jalan' => 'nullable|string',
            'profil.rt' => 'nullable|string|max:10',
            'profil.rw' => 'nullable|string|max:10',
            'profil.nama_dusun' => 'nullable|string|max:100',
            'profil.kelurahan' => 'nullable|string|max:100',
            'profil.kecamatan' => 'nullable|string|max:100',
            'profil.kode_pos' => 'nullable|string|max:15',
            'profil.jenis_tinggal' => 'nullable|string|max:100',
            'profil.alat_transportasi' => 'nullable|string|max:100',
            'profil.lintang' => 'nullable|numeric|between:-90,90',
            'profil.bujur' => 'nullable|numeric|between:-180,180',
            'profil.no_registrasi_akta_lahir' => 'nullable|string|max:100',
            'profil.skhun' => 'nullable|string|max:100',
            'profil.no_peserta_un' => 'nullable|string|max:100',
            'profil.no_seri_ijazah' => 'nullable|string|max:100',
            'profil.sekolah_asal' => 'nullable|string|max:150',
            'profil.kebutuhan_khusus' => 'nullable|string|max:100',
            'profil.anak_keberapa' => 'nullable|integer|min:1|max:30',
            'profil.penerima_kps' => 'nullable|boolean',
            'profil.no_kps' => 'nullable|string|max:50',
            'profil.penerima_kip' => 'nullable|boolean',
            'profil.nomor_kip' => 'nullable|string|max:50',
            'profil.nama_di_kip' => 'nullable|string|max:150',
            'profil.nomor_kks' => 'nullable|string|max:50',
            'profil.layak_pip' => 'nullable|boolean',
            'profil.alasan_layak_pip' => 'nullable|string|max:150',
            'profil.bank' => 'nullable|string|max:100',
            'profil.nomor_rekening_bank' => 'nullable|string|max:50',
            'profil.rekening_atas_nama' => 'nullable|string|max:150',

            // Nested Orang Tua / Wali
            'ortu' => 'nullable|array',
            'ortu.*.nama' => 'nullable|string|max:150',
            'ortu.*.nik' => 'nullable|string|max:20',
            'ortu.*.tahun_lahir' => 'nullable|integer|min:1920|max:' . date('Y'),
            'ortu.*.jenjang_pendidikan' => 'nullable|string|max:50',
            'ortu.*.pekerjaan' => 'nullable|string|max:100',
            'ortu.*.penghasilan' => 'nullable|string|max:100',
            'ortu.*.no_hp' => 'nullable|string|max:25',
            'ortu.*.status_hidup' => 'nullable|in:hidup,meninggal',

            // Nested Periodik
            'periodik' => 'nullable|array',
            'periodik.berat_badan' => 'nullable|numeric|min:0|max:250',
            'periodik.tinggi_badan' => 'nullable|numeric|min:0|max:250',
            'periodik.lingkar_kepala' => 'nullable|numeric|min:0|max:120',
            'periodik.jumlah_saudara_kandung' => 'nullable|integer|min:0|max:30',
            'periodik.jarak_rumah_km' => 'nullable|numeric|min:0|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.unique' => 'NISN sudah terdaftar.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'rombel_id.required_without' => 'Kelas / Rombel wajib dipilih.',
            'kelas.required_without' => 'Kelas / Rombel wajib dipilih.',
            'tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'tahun_ajaran_id.exists' => 'Tahun ajaran tidak valid.',
            'email.unique' => 'Email akun login sudah digunakan.',
            'tanggal_lahir.date' => 'Format tanggal lahir harus DD-MM-YYYY yang valid (contoh: 14-05-2008).',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'password.required_with' => 'Password wajib diisi jika membuat akun login siswa.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
