<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKegiatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kegiatan = $this->route('kegiatan');

        return [
            'kode_kegiatan' => [
                'required',
                'string',
                'max:30',
                Rule::unique('kegiatan', 'kode_kegiatan')
                    ->ignore($kegiatan),
            ],

            'nama_kegiatan' => [
                'required',
                'string',
                'max:255',
            ],

            'jenis' => [
                'required',
                'in:se,susenas,sakernas,sensus,podes,lainnya',
            ],

            'tahun' => [
                'required',
                'integer',
                'digits:4',
            ],

            'periode' => [
                'nullable',
                'string',
                'max:50',
            ],

            'deskripsi' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:draft,aktif,selesai',
            ],
        ];
    }
}