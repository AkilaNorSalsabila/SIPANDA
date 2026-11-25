<?php

namespace Tests\Unit;

use App\Models\Bangunan;
use PHPUnit\Framework\TestCase;

/**
 * Aturan impor ulang titik yang sudah dikoreksi di aplikasi:
 * file menang hanya kalau posisinya berbeda dari posisi acuan file DAN dari posisi titik sekarang.
 * Tidak butuh database.
 */
class PosisiFileBerubahTest extends TestCase
{
    private function titik(float $lat, float $lng, ?float $latAsal = null, ?float $lngAsal = null): Bangunan
    {
        return new Bangunan([
            'latitude' => $lat, 'longitude' => $lng,
            'latitude_asal' => $latAsal, 'longitude_asal' => $lngAsal,
        ]);
    }

    public function test_titik_belum_digeser_dan_file_sama_tidak_berubah(): void
    {
        $this->assertFalse($this->titik(-7.4890519, 112.4085964)->posisiFileBerubah(-7.4890519, 112.4085964));
    }

    public function test_pembulatan_6_desimal_dari_qgis_dianggap_sama(): void
    {
        $this->assertFalse($this->titik(-7.4890519, 112.4085964)->posisiFileBerubah(-7.489052, 112.408596));
    }

    public function test_qgis_menggeser_titik_berarti_berubah(): void
    {
        $this->assertTrue($this->titik(-7.4890519, 112.4085964)->posisiFileBerubah(-7.4891419, 112.4085964));
    }

    public function test_titik_digeser_di_aplikasi_file_lama_diimpor_lagi_tidak_berubah(): void
    {
        $t = $this->titik(-7.4900000, 112.4100000, -7.4890519, 112.4085964);

        $this->assertFalse($t->posisiFileBerubah(-7.4890519, 112.4085964));
    }

    public function test_titik_digeser_di_aplikasi_file_hasil_export_tanpa_ubah_tidak_berubah(): void
    {
        $t = $this->titik(-7.4900000, 112.4100000, -7.4890519, 112.4085964);

        $this->assertFalse($t->posisiFileBerubah(-7.4900000, 112.4100000));
    }

    public function test_titik_digeser_di_aplikasi_lalu_qgis_memperbaiki_ke_posisi_lain_berubah(): void
    {
        $t = $this->titik(-7.4900000, 112.4100000, -7.4890519, 112.4085964);

        $this->assertTrue($t->posisiFileBerubah(-7.4895000, 112.4090000));
    }
}
