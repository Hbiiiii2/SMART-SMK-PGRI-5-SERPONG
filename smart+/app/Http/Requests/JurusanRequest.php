<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JurusanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jurusanId = $this->route('jurusan')?->id;

        return [
            'kode' => [
                'required',
                'string',
                'max:20',
                Rule::unique('jurusans', 'kode')->ignore($jurusanId),
            ],
            'singkatan' => 'nullable|string|max:20',
            'nama' => 'required|string|max:150',
            'dapodik_jurusan_id' => [
                'nullable',
                'string',
                'max:36',
                Rule::unique('jurusans', 'dapodik_jurusan_id')->ignore($jurusanId),
            ],
            'deskripsi' => 'nullable|string',
            'is_aktif' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'kode.required' => 'Kode jurusan wajib diisi.',
            'kode.unique' => 'Kode jurusan sudah terdaftar.',
            'nama.required' => 'Nama kompetensi keahlian / jurusan wajib diisi.',
            'dapodik_jurusan_id.unique' => 'UUID Jurusan Dapodik sudah terdaftar.',
        ];
    }
}
