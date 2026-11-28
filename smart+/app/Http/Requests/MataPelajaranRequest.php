<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MataPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('mata_pelajaran')?->id;

        return [
            'kode' => 'required|string|max:20|unique:mata_pelajaran,kode,' . $id,
            'nama' => 'required|string|max:255',
            'kelompok' => 'nullable|in:umum,kejuruan,konsentrasi,pilihan,mulok',
            'jurusan_id' => 'nullable|exists:jurusans,id',
            'tingkat' => 'nullable|array',
            'tingkat.*' => 'in:X,XI,XII',
            'urutan' => 'nullable|integer|min:1|max:999',
            'tampil_di_rapor' => 'nullable|boolean',
            'kkm' => 'nullable|numeric|min:0|max:100',
            'deskripsi' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'kode.required' => 'Kode mata pelajaran wajib diisi.',
            'kode.unique' => 'Kode mata pelajaran sudah ada.',
            'nama.required' => 'Nama mata pelajaran wajib diisi.',
            'kelompok.in' => 'Kelompok mata pelajaran tidak valid.',
            'jurusan_id.exists' => 'Jurusan tidak ditemukan.',
        ];
    }
}
