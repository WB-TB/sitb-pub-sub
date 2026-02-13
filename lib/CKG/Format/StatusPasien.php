<?php

namespace CKG\Format;

use CKG\Format\TbObject;

class StatusPasien extends TbObject
{
    public $terduga_id;
    public $pasien_nik;
    public $pasien_tb_id;
    public $jenis_pasien;
    public $jenis_pasien_tipe;
    public $diagnosa_lab_hasil_tcm;
    public $diagnosa_lab_hasil_bta;
    public $diagnosa_hasil_radiologi;
    public $diagnosa_lab_hasil_poct;
    public $tanggal_mulai_pengobatan;
    public $tanggal_selesai_pengobatan;
    public $hasil_akhir;
    
    public function fromArray(array $data)
    {
        $this->terduga_id = isset($data['terduga_id']) ? $data['terduga_id'] : null;
        $this->pasien_nik = isset($data['pasien_nik']) ? $data['pasien_nik'] : null;
        $this->pasien_tb_id = isset($data['pasien_tb_id']) ? $data['pasien_tb_id'] : null;
        $this->jenis_pasien = isset($data['jenis_pasien']) ? $data['jenis_pasien'] : null;
        $this->jenis_pasien_tipe = isset($data['jenis_pasien_tipe']) ? $data['jenis_pasien_tipe'] : null;
        $this->diagnosa_lab_hasil_tcm = isset($data['diagnosa_lab_hasil_tcm']) ? $data['diagnosa_lab_hasil_tcm'] : null;
        $this->diagnosa_lab_hasil_bta = isset($data['diagnosa_lab_hasil_bta']) ? $data['diagnosa_lab_hasil_bta'] : null;
        $this->diagnosa_hasil_radiologi = isset($data['diagnosa_hasil_radiologi']) ? $data['diagnosa_hasil_radiologi'] : null;
        $this->diagnosa_lab_hasil_poct = isset($data['diagnosa_lab_hasil_poct']) ? $data['diagnosa_lab_hasil_poct'] : null;
        $this->tanggal_mulai_pengobatan = isset($data['tanggal_mulai_pengobatan']) ? $data['tanggal_mulai_pengobatan'] : null;
        $this->tanggal_selesai_pengobatan = isset($data['tanggal_selesai_pengobatan']) ? $data['tanggal_selesai_pengobatan'] : null;
        $this->hasil_akhir = isset($data['hasil_akhir']) ? $data['hasil_akhir'] : null;
    }
    
    public function toArray(): array
    {
        return [
            'terduga_id' => (string) $this->terduga_id,
            'pasien_nik' => $this->pasien_nik,
            'pasien_tb_id' => (string) $this->pasien_tb_id,
            'jenis_pasien' => $this->jenis_pasien,
            'jenis_pasien_tipe' => $this->jenis_pasien_tipe,
            'diagnosa_lab_hasil_tcm' => $this->diagnosa_lab_hasil_tcm,
            'diagnosa_lab_hasil_bta' => $this->diagnosa_lab_hasil_bta,
            'diagnosa_hasil_radiologi' => $this->diagnosa_hasil_radiologi,
            'diagnosa_lab_hasil_poct' => $this->diagnosa_lab_hasil_poct,
            'tanggal_mulai_pengobatan' => $this->tanggal_mulai_pengobatan,
            'tanggal_selesai_pengobatan' => $this->tanggal_selesai_pengobatan,
            'hasil_akhir' => $this->hasil_akhir,
        ];
    }

    public function fromDbRecord(array $data) {
        $this->terduga_id = isset($data['id_reg_terduga']) ? (string) $data['id_reg_terduga'] : null;
        $this->pasien_nik = isset($data['nik']) ? $data['nik'] : null;
        $this->pasien_tb_id = isset($data['person_id']) ? (string) $data['person_id'] : null;
        $this->jenis_pasien = isset($data['jenis_pasien']) ? $this->convertHasilDiagnosis($data['jenis_pasien']) : null;
        $this->jenis_pasien_tipe = isset($data['tipe_diagnosis_tbc']) ? $this->convertTipeHasilDiagnosis($data['tipe_diagnosis_tbc']) : null;
        $this->diagnosa_lab_hasil_tcm = isset($data['hasil_tcm']) ? $data['hasil_tcm'] : null;
        $this->diagnosa_lab_hasil_bta = isset($data['hasil_biakan']) ? $data['hasil_biakan'] : null;
        $this->diagnosa_hasil_radiologi = isset($data['hasil_foto_toraks']) ? $data['hasil_foto_toraks'] : null;
        $this->diagnosa_lab_hasil_poct = isset($data['hasil_poct']) ? $data['hasil_poct'] : null;
        $this->tanggal_mulai_pengobatan = isset($data['tgl_mulai_pengobatan']) ? $data['tgl_mulai_pengobatan'] : null;
        $this->tanggal_selesai_pengobatan = isset($data['tgl_akhir_pengobatan']) ? $data['tgl_akhir_pengobatan'] : null;
        $this->hasil_akhir = isset($data['hasil_akhir_pengobatan']) ? $data['hasil_akhir_pengobatan'] : null;
    }

    public function toDbRecord(): array {
        // TIDAK PERLU DIIMPLEMENTASIKAN KARENA DATA STATUS PENGOBATAN PASIEN TB HANYA DIKIRIM KE CKG TIDAK DATANG DARI CKG
        return [];
    }
}