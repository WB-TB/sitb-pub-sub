<?php

namespace CKG\Format;

class StatusPasien extends TbObject
{
    public $terduga_id;
    public $pasien_nik;
    public $pasien_tb_id;
    public $hasil_diagnosa;
    public $diagnosa_lab_hasil_tcm;
    public $diagnosa_lab_hasil_bta;
    public $diagnosa_hasil_radiologi;
    public $diagnosa_lab_hasil_poct;
    public $tanggal_mulai_pengobatan;
    public $tanggal_selesai_pengobatan;
    public $hasil_akhir;
    
    public function fromArray(array $data)
    {
        $this->terduga_id = $data['terduga_id'] ?? null;
        $this->pasien_nik = $data['pasien_nik'] ?? null;
        $this->pasien_tb_id = $data['pasien_tb_id'] ?? null;
        $this->hasil_diagnosa = $data['hasil_diagnosa'] ?? null;
        $this->diagnosa_lab_hasil_tcm = $data['diagnosa_lab_hasil_tcm'] ?? null;
        $this->diagnosa_lab_hasil_bta = $data['diagnosa_lab_hasil_bta'] ?? null;
        $this->diagnosa_hasil_radiologi = $data['diagnosa_hasil_radiologi'] ?? null;
        $this->diagnosa_lab_hasil_poct = $data['diagnosa_lab_hasil_poct'] ?? null;
        $this->tanggal_mulai_pengobatan = $data['tanggal_mulai_pengobatan'] ?? null;
        $this->tanggal_selesai_pengobatan = $data['tanggal_selesai_pengobatan'] ?? null;
        $this->hasil_akhir = $data['hasil_akhir'] ?? null;
    }
    
    public function toArray(): array
    {
        return [
            'terduga_id' => $this->terduga_id,
            'pasien_nik' => $this->pasien_nik,
            'pasien_tb_id' => $this->pasien_tb_id,
            'hasil_diagnosa' => $this->hasil_diagnosa,
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
        $this->terduga_id = $data['id_reg_terduga'] ?? null;
        $this->pasien_nik = $data['nik'] ?? null;
        $this->pasien_tb_id = $data['register_id'] ?? null;
        $this->hasil_diagnosa = $data['diagnosis'] ?? null;
        $this->diagnosa_lab_hasil_tcm = $data['hasil_tcm'] ?? null;
        $this->diagnosa_lab_hasil_bta = $data['hasil_biakan'] ?? null;
        $this->diagnosa_hasil_radiologi = $data['hasil_radiologi'] ?? null;
        $this->diagnosa_lab_hasil_poct = $data['hasil_poct'] ?? null;
        $this->tanggal_mulai_pengobatan = $data['tgl_mulai_pengobatan'] ?? null;
        $this->tanggal_selesai_pengobatan = $data['tgl_akhir_pengobatan'] ?? null;
        $this->hasil_akhir = $data['hasil_akhir_pengobatan'] ?? null;
    }

    public function toDbRecord(): array {
        // TIDAK PERLU DIIMPLEMENTASIKAN KARENA DATA STATUS PENGOBATAN PASIEN TB HANYA DIKIRIM KE CKG TIDAK DATANG DARI CKG
        return [];
    }
}