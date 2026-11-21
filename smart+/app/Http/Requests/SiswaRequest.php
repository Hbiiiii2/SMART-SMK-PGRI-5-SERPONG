<?php

namespace App\Http\Requests;

use App\Traits\NormalizesDateInputs;
use Illuminate\Foundation\Http\FormRequest;

class SiswaRequest extends FormRequest
{
    use NormalizesDateInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDateFields(['tanggal_lahir']);
    }

    public function rules(): array
    {
        return [
            'nisn' => 'required|string|max:20|unique:siswa,nisn,' . ($this->route('siswa')?->id ?? ''),
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas' => 'required|string|max:20',
            'tanggal_lahir' => 'nullable|date|before:today',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:20',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'email' => 'required|email|unique:users,email,' . ($this->route('siswa')?->user_id ?? ''),
            'password' => $this->isMethod('POST') ? 'required|min:8|confirmed' : 'nullable|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'kelas.required' => 'Kelas wajib diisi.',
            'tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'tahun_ajaran_id.exists' => 'Tahun ajaran tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'tanggal_lahir.date' => 'Format tanggal lahir harus DD-MM-YYYY yang valid (contoh: 14-05-2008).',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
