<?php
/* Batas akurasi gps agar sebuah tititk bisa disimpan oleh mitra */
return [
    'akurasi_maks_m' => (float) env('TAGGING_AKURASI_MAKS_M', 100),

    /* Ambang kategori akurasi GPS untuk ringkasan di dashboard petugas (meter):
       <= baik = "Baik", <= cukup = "Cukup", di atasnya = "Rendah". */
    'akurasi_baik_m'  => (float) env('TAGGING_AKURASI_BAIK_M', 10),
    'akurasi_cukup_m' => (float) env('TAGGING_AKURASI_CUKUP_M', 30),
];