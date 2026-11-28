<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RombelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rombelId = $this->route('rombel')?->id;

        return [
            'nama' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rombels', 'nama')
                    ->where(fn ($q) => $q
                        ->where('tahun_ajaran_id', $this->input('tahun_ajaran_id'))
                        ->where('semester', $this->input('semester', 'Ganjil'))
                    )
                    ->ignore($rombelId),
            ],
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|in:Ganjil,Genap',
            'tingkat' => 'nullable|string|max:15',
            'jurusan_id' => 'nullable|exists:jurusans,id',
            'kompetensi_keahlian' => 'nullable|string|max:120',
            'kurikulum' => 'nullable|string|max:120',
            'wali_kelas_guru_id' => 'nullable|exists:guru,id',
            'kapasitas' => 'nullable|integer|min:1|max:100',
            'is_aktif' => 'nullable|boolean',
            'dapodik_rombongan_belajar_id' => [
                'nullable',
                'string',
                'max:36',
                Rule::unique('rombels', 'dapodik_rombongan_belajar_id')->ignore($rombelId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kelas / rombel wajib diisi.',
            'nama.unique' => 'Nama rombel sudah terdaftar pada tahun ajaran dan semester tersebut.',
            'tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'tahun_ajaran_id.exists' => 'Tahun ajaran tidak valid.',
            'semester.required' => 'Semester wajib dipilih.',
            'wali_kelas_guru_id.exists' => 'Wali kelas tidak ditemukan.',
            'dapodik_rombongan_belajar_id.unique' => 'UUID Rombongan Belajar Dapodik sudah digunakan oleh rombel lain.',
        ];
    }
}
