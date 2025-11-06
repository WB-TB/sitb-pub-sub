<?php

namespace CKG\Format;

class SkriningCKG extends TbObject
{
    public $pasien_ckg_id;
    public $pasien_nik;
    public $pasien_nama;
    public $pasien_jenis_kelamin;
    public $pasien_tgl_lahir;
    public $pasien_usia;
    public $pasien_provinsi_satusehat;
    public $pasien_kabkota_satusehat;
    public $pasien_kecamatan_satusehat;
    public $pasien_kelurahan_satusehat;
    public $pasien_provinsi_sitb;
    public $pasien_kabkota_sitb;
    public $pasien_kecamatan_sitb;
    public $pasien_kelurahan_sitb;
    public $pasien_no_handphone;
    public $periksa_faskes_satusehat;
    public $periksa_faskes_sitb;
    public $periksa_tgl;
    public $hasil_berat_badan;
    public $hasil_tinggi_badan;
    public $hasil_imt;
    public $hasil_gds;
    public $hasil_gdp;
    public $hasil_gdpp;
    public $risiko_kekurangan_gizi;
    public $risiko_merokok;
    public $risiko_perokok_pasif;
    public $risiko_lansia;
    public $risiko_ibu_hamil;
    public $risiko_dm;
    public $risiko_hipertensi;
    public $risiko_hiv_aids;
    public $gejala_batuk;
    public $gejala_bb_turun;
    public $gejala_demam_hilang_timbul;
    public $gejala_lesu_malaise;
    public $gejala_berkeringat_malam;
    public $gejala_pembesaran_getah_bening;
    public $kontak_pasien_tbc;
    public $hasil_skrining_tbc;
    public $terduga_tb;
    public $pemeriksaan_tb_metode;
    public $pemeriksaan_tb_bta;
    public $pemeriksaan_tb_tcm;
    
    public static function fromArray(array $data): self
    {
        $skriningCKG = parent::fromArray($data);
        
        $skriningCKG->pasien_ckg_id = $data['pasien_ckg_id'] ?? null;
        $skriningCKG->pasien_nik = $data['pasien_nik'] ?? null;
        $skriningCKG->pasien_nama = $data['pasien_nama'] ?? null;
        $skriningCKG->pasien_jenis_kelamin = $data['pasien_jenis_kelamin'] ?? null;
        $skriningCKG->pasien_tgl_lahir = $data['pasien_tgl_lahir'] ?? null;
        $skriningCKG->pasien_usia = $data['pasien_usia'] ?? null;
        $skriningCKG->pasien_provinsi_satusehat = $data['pasien_provinsi_satusehat'] ?? null;
        $skriningCKG->pasien_kabkota_satusehat = $data['pasien_kabkota_satusehat'] ?? null;
        $skriningCKG->pasien_kecamatan_satusehat = $data['pasien_kecamatan_satusehat'] ?? null;
        $skriningCKG->pasien_kelurahan_satusehat = $data['pasien_kelurahan_satusehat'] ?? null;
        $skriningCKG->pasien_provinsi_sitb = $data['pasien_provinsi_sitb'] ?? null;
        $skriningCKG->pasien_kabkota_sitb = $data['pasien_kabkota_sitb'] ?? null;
        $skriningCKG->pasien_kecamatan_sitb = $data['pasien_kecamatan_sitb'] ?? null;
        $skriningCKG->pasien_kelurahan_sitb = $data['pasien_kelurahan_sitb'] ?? null;
        $skriningCKG->pasien_no_handphone = $data['pasien_no_handphone'] ?? null;
        $skriningCKG->periksa_faskes_satusehat = $data['periksa_faskes_satusehat'] ?? null;
        $skriningCKG->periksa_faskes_sitb = $data['periksa_faskes_sitb'] ?? null;
        $skriningCKG->periksa_tgl = $data['periksa_tgl'] ?? null;
        $skriningCKG->hasil_berat_badan = $data['hasil_berat_badan'] ?? null;
        $skriningCKG->hasil_tinggi_badan = $data['hasil_tinggi_badan'] ?? null;
        $skriningCKG->hasil_imt = $data['hasil_imt'] ?? null;
        $skriningCKG->hasil_gds = $data['hasil_gds'] ?? null;
        $skriningCKG->hasil_gdp = $data['hasil_gdp'] ?? null;
        $skriningCKG->hasil_gdpp = $data['hasil_gdpp'] ?? null;
        $skriningCKG->risiko_kekurangan_gizi = $data['risiko_kekurangan_gizi'] ?? null;
        $skriningCKG->risiko_merokok = $data['risiko_merokok'] ?? null;
        $skriningCKG->risiko_perokok_pasif = $data['risiko_perokok_pasif'] ?? null;
        $skriningCKG->risiko_lansia = $data['risiko_lansia'] ?? null;
        $skriningCKG->risiko_ibu_hamil = $data['risiko_ibu_hamil'] ?? null;
        $skriningCKG->risiko_dm = $data['risiko_dm'] ?? null;
        $skriningCKG->risiko_hipertensi = $data['risiko_hipertensi'] ?? null;
        $skriningCKG->risiko_hiv_aids = $data['risiko_hiv_aids'] ?? null;
        $skriningCKG->gejala_batuk = $data['gejala_batuk'] ?? null;
        $skriningCKG->gejala_bb_turun = $data['gejala_bb_turun'] ?? null;
        $skriningCKG->gejala_demam_hilang_timbul = $data['gejala_demam_hilang_timbul'] ?? null;
        $skriningCKG->gejala_lesu_malaise = $data['gejala_lesu_malaise'] ?? null;
        $skriningCKG->gejala_berkeringat_malam = $data['gejala_berkeringat_malam'] ?? null;
        $skriningCKG->gejala_pembesaran_getah_bening = $data['gejala_pembesaran_getah_bening'] ?? null;
        $skriningCKG->kontak_pasien_tbc = $data['kontak_pasien_tbc'] ?? null;
        $skriningCKG->hasil_skrining_tbc = $data['hasil_skrining_tbc'] ?? null;
        $skriningCKG->terduga_tb = $data['terduga_tb'] ?? null;
        $skriningCKG->pemeriksaan_tb_metode = $data['pemeriksaan_tb_metode'] ?? null;
        $skriningCKG->pemeriksaan_tb_bta = $data['pemeriksaan_tb_bta'] ?? null;
        $skriningCKG->pemeriksaan_tb_tcm = $data['pemeriksaan_tb_tcm'] ?? null;
        
        return $skriningCKG;
    }
    
    public function toArray(): array
    {
        return [
            'pasien_ckg_id' => $this->pasien_ckg_id,
            'pasien_nik' => $this->pasien_nik,
            'pasien_nama' => $this->pasien_nama,
            'pasien_jenis_kelamin' => $this->pasien_jenis_kelamin,
            'pasien_tgl_lahir' => $this->pasien_tgl_lahir,
            'pasien_usia' => $this->pasien_usia,
            'pasien_provinsi_satusehat' => $this->pasien_provinsi_satusehat,
            'pasien_kabkota_satusehat' => $this->pasien_kabkota_satusehat,
            'pasien_kecamatan_satusehat' => $this->pasien_kecamatan_satusehat,
            'pasien_kelurahan_satusehat' => $this->pasien_kelurahan_satusehat,
            'pasien_provinsi_sitb' => $this->pasien_provinsi_sitb,
            'pasien_kabkota_sitb' => $this->pasien_kabkota_sitb,
            'pasien_kecamatan_sitb' => $this->pasien_kecamatan_sitb,
            'pasien_kelurahan_sitb' => $this->pasien_kelurahan_sitb,
            'pasien_no_handphone' => $this->pasien_no_handphone,
            'periksa_faskes_satusehat' => $this->periksa_faskes_satusehat,
            'periksa_faskes_sitb' => $this->periksa_faskes_sitb,
            'periksa_tgl' => $this->periksa_tgl,
            'hasil_berat_badan' => $this->hasil_berat_badan,
            'hasil_tinggi_badan' => $this->hasil_tinggi_badan,
            'hasil_imt' => $this->hasil_imt,
            'hasil_gds' => $this->hasil_gds,
            'hasil_gdp' => $this->hasil_gdp,
            'hasil_gdpp' => $this->hasil_gdpp,
            'risiko_kekurangan_gizi' => $this->risiko_kekurangan_gizi,
            'risiko_merokok' => $this->risiko_merokok,
            'risiko_perokok_pasif' => $this->risiko_perokok_pasif,
            'risiko_lansia' => $this->risiko_lansia,
            'risiko_ibu_hamil' => $this->risiko_ibu_hamil,
            'risiko_dm' => $this->risiko_dm,
            'risiko_hipertensi' => $this->risiko_hipertensi,
            'risiko_hiv_aids' => $this->risiko_hiv_aids,
            'gejala_batuk' => $this->gejala_batuk,
            'gejala_bb_turun' => $this->gejala_bb_turun,
            'gejala_demam_hilang_timbul' => $this->gejala_demam_hilang_timbul,
            'gejala_lesu_malaise' => $this->gejala_lesu_malaise,
            'gejala_berkeringat_malam' => $this->gejala_berkeringat_malam,
            'gejala_pembesaran_getah_bening' => $this->gejala_pembesaran_getah_bening,
            'kontak_pasien_tbc' => $this->kontak_pasien_tbc,
            'hasil_skrining_tbc' => $this->hasil_skrining_tbc,
            'terduga_tb' => $this->terduga_tb,
            'pemeriksaan_tb_metode' => $this->pemeriksaan_tb_metode,
            'pemeriksaan_tb_bta' => $this->pemeriksaan_tb_bta,
            'pemeriksaan_tb_tcm' => $this->pemeriksaan_tb_tcm,
        ];
    }
}