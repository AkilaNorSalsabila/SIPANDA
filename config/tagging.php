<?php
/* Batas akurasi gps agar sebuah tititk bisa disimpan oleh mitra */
return [
    'akurasi_maks_m' => (float) env('TAGGING_AKURASI_MAKS_M', 100),
];