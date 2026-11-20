<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanSekolahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_sekolah' => 'required|string|max:255',
            'alamat_sekolah' => 'nullable|string',
            'no_telepon_sekolah' => 'nullable|string|max:20',
            'email_sekolah' => 'nullable|email',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'jam_masuk' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'jam_pulang' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/|after:jam_masuk',
            'toleransi_terlambat_menit' => 'required|integer|min:0|max:60',
            'toleransi_alpha_menit' => 'required|integer|min:0|max:120',
            'radius_absensi_meter' => 'required|numeric|min:1',
            'metode_absensi_siswa' => 'required|in:RFID,Hybrid,Zonasi',
            'metode_absensi_guru' => 'required|in:RFID,Hybrid,Zonasi',
            'metode_absensi_staff' => 'required|in:RFID,Hybrid,Zonasi',
            'mode_lokasi_gps' => 'sometimes|required|in:semua_lokasi,khusus_pengguna',
            'validasi_radius_gps' => 'nullable|boolean',
            'wajib_foto_gps' => 'nullable|boolean',
            'izinkan_absen_tanpa_jadwal' => 'nullable|boolean',
            'absensi_hari_sabtu' => 'nullable|boolean',
            'absensi_hari_minggu' => 'nullable|boolean',
            'mode_anti_mock_gps' => 'sometimes|required|in:nonaktif,peringatan,ketat',
            'maks_akurasi_gps_meter' => 'sometimes|required|integer|min:10|max:1000',
            'mode_deteksi_perangkat_bersama' => 'sometimes|required|in:nonaktif,tandai,blokir',
            'batas_akun_per_perangkat' => 'sometimes|required|integer|min:2|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_sekolah.required' => 'Nama sekolah wajib diisi.',
            'jam_masuk.required' => 'Jam masuk wajib diisi.',
            'jam_pulang.required' => 'Jam pulang wajib diisi.',
            'jam_pulang.after' => 'Jam pulang harus setelah jam masuk.',
            'toleransi_terlambat_menit.required' => 'Toleransi keterlambatan wajib diisi.',
            'radius_absensi_meter.required' => 'Radius absensi wajib diisi.',
            'metode_absensi_siswa.required' => 'Metode absensi siswa wajib dipilih.',
            'metode_absensi_guru.required' => 'Metode absensi guru wajib dipilih.',
            'metode_absensi_staff.required' => 'Metode absensi staff wajib dipilih.',
            'mode_lokasi_gps.in' => 'Mode izin lokasi GPS tidak valid.',
        ];
    }
}
