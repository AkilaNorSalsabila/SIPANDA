<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKegiatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_kegiatan' => [
                'required',
                'string',
                'max:30',
                'unique:kegiatan,kode_kegiatan',
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