<?php

namespace CKG\Format;

use CKG\Format\TbObject;

class SkriningCKG extends TbObject
{
    private const DEFAULT_USER = '99999999';

    public $pasien_ckg_id;
    public $pasien_nik;
    public $pasien_nama;
    public $pasien_jenis_kelamin;
    public $pasien_tgl_lahir;
    public $pasien_usia;
    public $pasien_pekerjaan;
    public $pasien_provinsi_satusehat;
    public $pasien_kabkota_satusehat;
    public $pasien_kecamatan_satusehat;
    public $pasien_kelurahan_satusehat;
    public $pasien_provinsi_sitb;
    public $pasien_kabkota_sitb;
    public $pasien_kecamatan_sitb;
    public $pasien_kelurahan_sitb;
    public $pasien_alamat;
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
    public $risiko_pernah_terdiagnosis;
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
    public $tindak_lanjut_tb;
    public $pemeriksaan_tb_metode;
    public $pemeriksaan_tb_bta;
    public $pemeriksaan_tb_tcm;
    
    public function fromArray(array $data)
    {   
        $this->pasien_ckg_id = $data['pasien_ckg_id'] ? $data['pasien_ckg_id'] : null;
        $this->pasien_nik = $data['pasien_nik'] ? $data['pasien_nik'] : null;
        $this->pasien_nama = $data['pasien_nama'] ? $data['pasien_nama'] : null;
        $this->pasien_jenis_kelamin = $data['pasien_jenis_kelamin'] ? $data['pasien_jenis_kelamin'] : null;
        $this->pasien_tgl_lahir = $data['pasien_tgl_lahir'] ? $data['pasien_tgl_lahir'] : null;
        $this->pasien_pekerjaan = $data['pasien_pekerjaan'] ? $data['pasien_pekerjaan'] : null;
        $this->pasien_usia = $data['pasien_usia'] ? $data['pasien_usia'] : null;
        $this->pasien_provinsi_satusehat = $data['pasien_provinsi_satusehat'] ? $data['pasien_provinsi_satusehat'] : null;
        $this->pasien_kabkota_satusehat = $data['pasien_kabkota_satusehat'] ? $data['pasien_kabkota_satusehat'] : null;
        $this->pasien_kecamatan_satusehat = $data['pasien_kecamatan_satusehat'] ? $data['pasien_kecamatan_satusehat'] : null;
        $this->pasien_kelurahan_satusehat = $data['pasien_kelurahan_satusehat'] ? $data['pasien_kelurahan_satusehat'] : null;
        $this->pasien_provinsi_sitb = $data['pasien_provinsi_sitb'] ? $data['pasien_provinsi_sitb'] : null;
        $this->pasien_kabkota_sitb = $data['pasien_kabkota_sitb'] ? $data['pasien_kabkota_sitb'] : null;
        $this->pasien_kecamatan_sitb = $data['pasien_kecamatan_sitb'] ? $data['pasien_kecamatan_sitb'] : null;
        $this->pasien_kelurahan_sitb = $data['pasien_kelurahan_sitb'] ? $data['pasien_kelurahan_sitb'] : null;
        $this->pasien_alamat = $data['pasien_alamat'] ? $data['pasien_alamat'] : null;
        $this->pasien_no_handphone = $data['pasien_no_handphone'] ? $data['pasien_no_handphone'] : null;
        $this->periksa_faskes_satusehat = $data['periksa_faskes_satusehat'] ? $data['periksa_faskes_satusehat'] : null;
        $this->periksa_faskes_sitb = $data['periksa_faskes_sitb'] ? $data['periksa_faskes_sitb'] : null;
        $this->periksa_tgl = $data['periksa_tgl'] ? $data['periksa_tgl'] : null;
        $this->hasil_berat_badan = $data['hasil_berat_badan'] ? $data['hasil_berat_badan'] : null;
        $this->hasil_tinggi_badan = $data['hasil_tinggi_badan'] ? $data['hasil_tinggi_badan'] : null;
        $this->hasil_imt = $data['hasil_imt'] ? $data['hasil_imt'] : null;
        $this->hasil_gds = $data['hasil_gds'] ? $data['hasil_gds'] : null;
        $this->hasil_gdp = $data['hasil_gdp'] ? $data['hasil_gdp'] : null;
        $this->hasil_gdpp = $data['hasil_gdpp'] ? $data['hasil_gdpp'] : null;
        $this->risiko_pernah_terdiagnosis = $data['risiko_pernah_terdiagnosis'] ? $data['risiko_pernah_terdiagnosis'] : null;
        $this->risiko_kekurangan_gizi = $data['risiko_kekurangan_gizi'] ? $data['risiko_kekurangan_gizi'] : null;
        $this->risiko_merokok = $data['risiko_merokok'] ? $data['risiko_merokok'] : null;
        $this->risiko_perokok_pasif = $data['risiko_perokok_pasif'] ? $data['risiko_perokok_pasif'] : null;
        $this->risiko_lansia = $data['risiko_lansia'] ? $data['risiko_lansia'] : null;
        $this->risiko_ibu_hamil = $data['risiko_ibu_hamil'] ? $data['risiko_ibu_hamil'] : null;
        $this->risiko_dm = $data['risiko_dm'] ? $data['risiko_dm'] : null;
        $this->risiko_hipertensi = $data['risiko_hipertensi'] ? $data['risiko_hipertensi'] : null;
        $this->risiko_hiv_aids = $data['risiko_hiv_aids'] ? $data['risiko_hiv_aids'] : null;
        $this->gejala_batuk = $data['gejala_batuk'] ? $data['gejala_batuk'] : null;
        $this->gejala_bb_turun = $data['gejala_bb_turun'] ? $data['gejala_bb_turun'] : null;
        $this->gejala_demam_hilang_timbul = $data['gejala_demam_hilang_timbul'] ? $data['gejala_demam_hilang_timbul'] : null;
        $this->gejala_lesu_malaise = $data['gejala_lesu_malaise'] ? $data['gejala_lesu_malaise'] : null;
        $this->gejala_berkeringat_malam = $data['gejala_berkeringat_malam'] ? $data['gejala_berkeringat_malam'] : null;
        $this->gejala_pembesaran_getah_bening = $data['gejala_pembesaran_getah_bening'] ? $data['gejala_pembesaran_getah_bening'] : null;
        $this->kontak_pasien_tbc = $data['kontak_pasien_tbc'] ? $data['kontak_pasien_tbc'] : null;
        $this->hasil_skrining_tbc = $data['hasil_skrining_tbc'] ? $data['hasil_skrining_tbc'] : null;
        $this->terduga_tb = $data['terduga_tb'] ? $data['terduga_tb'] : null;
        $this->tindak_lanjut_tb = $data['tindak_lanjut_tb'] ? $data['tindak_lanjut_tb'] : null;
        $this->pemeriksaan_tb_metode = $data['pemeriksaan_tb_metode'] ? $data['pemeriksaan_tb_metode'] : null;
        $this->pemeriksaan_tb_bta = $data['pemeriksaan_tb_bta'] ? $data['pemeriksaan_tb_bta'] : null;
        $this->pemeriksaan_tb_tcm = $data['pemeriksaan_tb_tcm'] ? $data['pemeriksaan_tb_tcm'] : null;
    }
    
    public function toArray(): array
    {
        return [
            'pasien_ckg_id' => $this->pasien_ckg_id,
            'pasien_nik' => $this->pasien_nik,
            'pasien_nama' => $this->pasien_nama,
            'pasien_jenis_kelamin' => $this->pasien_jenis_kelamin,
            'pasien_tgl_lahir' => $this->pasien_tgl_lahir,
            'pasien_pekerjaan' => $this->pasien_pekerjaan,
            'pasien_usia' => $this->pasien_usia,
            'pasien_provinsi_satusehat' => $this->pasien_provinsi_satusehat,
            'pasien_kabkota_satusehat' => $this->pasien_kabkota_satusehat,
            'pasien_kecamatan_satusehat' => $this->pasien_kecamatan_satusehat,
            'pasien_kelurahan_satusehat' => $this->pasien_kelurahan_satusehat,
            'pasien_provinsi_sitb' => $this->pasien_provinsi_sitb,
            'pasien_kabkota_sitb' => $this->pasien_kabkota_sitb,
            'pasien_kecamatan_sitb' => $this->pasien_kecamatan_sitb,
            'pasien_kelurahan_sitb' => $this->pasien_kelurahan_sitb,
            'pasien_alamat' => $this->pasien_alamat,
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
            'risiko_pernah_terdiagnosis' => $this->risiko_pernah_terdiagnosis,
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
            'tindak_lanjut_tb' => $this->tindak_lanjut_tb,
            'pemeriksaan_tb_metode' => $this->pemeriksaan_tb_metode,
            'pemeriksaan_tb_bta' => $this->pemeriksaan_tb_bta,
            'pemeriksaan_tb_tcm' => $this->pemeriksaan_tb_tcm,
        ];
    }

    public function fromDbRecord(array $data) {
        // TIDAK PERLU DIIMPLEMENTASIKAN KARENA DATA SKRINING HANYA DISIMPAN TIDAK DIKIRIM BALIK KE CKG
    }

    public function toDbRecord(): array {
        $now = date('Y-m-d H:i:s');
        list($ageYear, $ageMonth) = $this->calculateAge();
        if (empty($ageYear))
            $ageYear = $this->pasien_usia;

        if ($this->terduga_tb == 'Ya') {
            $this->tindak_lanjut_tb = 'Dirujuk Untuk Pemeriksaan TB';
        }

        $return = [
            'tgl_skrining' => $this->periksa_tgl,
            'kegiatan_id' => '99',
            'metode_id' => '99',
            'tempat_skrining_id' => '3',
            'jenis_unit_pelaksana_id' => '4',
            'unit_pelaksana_id' => $this->periksa_faskes_sitb ?: '',
            'warga_negara_id' => '1', // WNI
            'nama_peserta' => $this->pasien_nama,
            'alamat_ktp' => $this->pasien_alamat ?: '',
            'provinsi_ktp_id' => $this->pasien_provinsi_sitb,
            'kabupaten_ktp_id' => $this->pasien_kabkota_sitb,
            'kecamatan_ktp_id' => $this->pasien_kecamatan_sitb,
            'kelurahan_ktp_id' => $this->pasien_kelurahan_sitb,
            'status_domisili_id' => '1', // sama dengan Alamat KTP
            'alamat_domisili' => $this->pasien_alamat ?: '',
            'provinsi_domisili_id' => $this->pasien_provinsi_sitb,
            'kabupaten_domisili_id' => $this->pasien_kabkota_sitb,
            'kecamatan_domisili_id' => $this->pasien_kecamatan_sitb,
            'kelurahan_domisili_id' => $this->pasien_kelurahan_sitb,
            'nik' => $this->pasien_nik,
            'pekerjaan_id' => $this->pasien_pekerjaan ?: '18',
            'tgl_lahir' => $this->pasien_tgl_lahir,
            'umur_th' => $ageYear,
            'umur_bl' => $ageMonth,
            'jenis_kelamin_id' => $this->convertGender($this->pasien_jenis_kelamin),
            'no_telp' => $this->pasien_no_handphone,
            'berat_badan' => $this->hasil_berat_badan,
            'tinggi_badan' => $this->hasil_tinggi_badan,
            'imt' => $this->hasil_imt,
            'status_gizi_id' => $this->convertYaTidak($this->risiko_kekurangan_gizi),
            'riwayat_kontak_tb_id' => $this->convertYaTidak($this->kontak_pasien_tbc, 'Tidak'),
            // 'jenis_kontak_id' => null,
            // 'nama_kasus_indeks' => null,
            // 'nik_kasus_indeks' => null,
            // 'jenis_kasus_indeks_id' => null,
            'risiko_1_id' => $this->convertYaTidak($this->risiko_pernah_terdiagnosis, 'Tidak'),
            'risiko_3_id' => $this->convertYaTidak($this->risiko_kekurangan_gizi, 'Tidak'),
            'risiko_4_id' => $this->convertYaTidak($this->risiko_merokok, 'Tidak'),
            'risiko_5_id' => $this->convertYaTidak($this->risiko_perokok_pasif, 'Tidak'),
            'risiko_6_id' => $this->convertYaTidak($this->risiko_dm, 'Tidak'),
            'risiko_7_id' => $this->convertYaTidak($this->risiko_hiv_aids, 'Tidak'),
            'risiko_8_id' => $this->convertYaTidak($this->risiko_lansia, 'Tidak'),
            'risiko_9_id' => $this->convertYaTidak($this->risiko_ibu_hamil, 'Tidak'),
            'risiko_10_id' => $this->convertYaTidak(null, 'Tidak'),
            'risiko_11_id' => $this->convertYaTidak(null, 'Tidak'),
            // 'gejala_1_1_id' => $this->pasien_usia < 15 ? $this->convertYaTidak($this->gejala_batuk) : null,
            // 'gejala_1_1_durasi' => $this->pasien_usia < 15 ? ($this->gejala_batuk === 'Ya' ? 1 : null) : null,
            // 'gejala_2_1_id' => $this->pasien_usia >= 15 ? $this->convertYaTidak($this->gejala_batuk) : null,
            // 'gejala_2_1_durasi' => $this->pasien_usia >= 15 ? ($this->gejala_batuk === 'Ya' ? 1 : null) : null,
            // 'gejala_2_3_id' => $this->convertYaTidak($this->gejala_bb_turun),
            // 'gejala_2_4_id' => $this->convertYaTidak($this->gejala_demam_hilang_timbul),
            // 'gejala_2_5_id' => $this->convertYaTidak($this->gejala_berkeringat_malam),
            'gejala_6_id' => $this->convertYaTidak($this->gejala_pembesaran_getah_bening, 'Tidak'),
            'hasil_skrining_id' => $this->convertYaTidak($this->hasil_skrining_tbc),
            'cxr_simpulan_id' => $this->convertRadiologyResult($this->hasil_skrining_tbc),
            'terduga_tb_id' => $this->convertYaTidak($this->terduga_tb),
            'tindak_lanjut_id' => $this->convertTindakLanjut($this->tindak_lanjut_tb),
            'dirujuk_ke_id' => $this->periksa_faskes_sitb ?: '',
            'insert_by' => self::DEFAULT_USER,
            'insert_at' => $now,
            'update_by' => self::DEFAULT_USER,
            'update_at' => $now,
            'sumber_id' => '1',
            'ckg_id' => $this->pasien_ckg_id
        ];

        if ($ageYear < 15) {
            $return['gejala_1_1_id'] = $this->convertYaTidak($this->gejala_batuk, 'Tidak');
            if ($this->gejala_batuk = 'Ya')
                $return['gejala_1_1_durasi'] = 15;

            $return['gejala_1_3_id'] = $this->convertYaTidak($this->gejala_bb_turun, 'Tidak');
            $return['gejala_1_4_id'] = $this->convertYaTidak($this->gejala_demam_hilang_timbul, 'Tidak');
            $return['gejala_1_5_id'] = $this->convertYaTidak($this->gejala_berkeringat_malam, 'Tidak');
        }else {
            $return['gejala_2_1_id'] = $this->convertYaTidak($this->gejala_batuk, 'Tidak');
            if ($this->gejala_batuk = 'Ya')
                $return['gejala_2_1_durasi'] = 15;

            $return['gejala_2_3_id'] = $this->convertYaTidak($this->gejala_bb_turun, 'Tidak');
            $return['gejala_2_4_id'] = $this->convertYaTidak($this->gejala_demam_hilang_timbul, 'Tidak');
            $return['gejala_2_5_id'] = $this->convertYaTidak($this->gejala_berkeringat_malam, 'Tidak');
        }

        return $return;
    }

    /**
     * Calculate age in months based on birth date and screening date
     * 
     * @return array
     */
    protected function calculateAge(): array
    {
        if (!$this->pasien_tgl_lahir || !$this->periksa_tgl) {
            return [null, null];
        }
        
        $birthDate = new \DateTime($this->pasien_tgl_lahir);
        $screeningDate = new \DateTime($this->periksa_tgl);
        
        $diff = $birthDate->diff($screeningDate);
        $months = ($diff->y * 12) + $diff->m;
        
        return [$diff->y, $diff->m];
    }
    
}