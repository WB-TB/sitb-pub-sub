<?php

namespace CKG\Format;

class StatusPasien extends TbObject
{
    public $terduga_id;
    public $pasien_nik;
    public $pasien_tb_id;
    public $status_diagnosa;
    public $diagnosa_lab_metode;
    public $diagnosa_lab_hasil;
    public $tanggal_mulai_pengobatan;
    public $tanggal_selesai_pengobatan;
    public $hasil_akhir;
    
    public function fromArray(array $data): self
    {
        $statusPasien = parent::fromArray($data);
        
        $statusPasien->terduga_id = $data['terduga_id'] ?? null;
        $statusPasien->pasien_nik = $data['pasien_nik'] ?? null;
        $statusPasien->pasien_tb_id = $data['pasien_tb_id'] ?? null;
        $statusPasien->status_diagnosa = $data['status_diagnosa'] ?? null;
        $statusPasien->diagnosa_lab_metode = $data['diagnosa_lab_metode'] ?? null;
        $statusPasien->diagnosa_lab_hasil = $data['diagnosa_lab_hasil'] ?? null;
        $statusPasien->tanggal_mulai_pengobatan = $data['tanggal_mulai_pengobatan'] ?? null;
        $statusPasien->tanggal_selesai_pengobatan = $data['tanggal_selesai_pengobatan'] ?? null;
        $statusPasien->hasil_akhir = $data['hasil_akhir'] ?? null;
        
        return $statusPasien;
    }
    
    public function toArray(): array
    {
        return [
            'terduga_id' => $this->terduga_id,
            'pasien_nik' => $this->pasien_nik,
            'pasien_tb_id' => $this->pasien_tb_id,
            'status_diagnosa' => $this->status_diagnosa,
            'diagnosa_lab_metode' => $this->diagnosa_lab_metode,
            'diagnosa_lab_hasil' => $this->diagnosa_lab_hasil,
            'tanggal_mulai_pengobatan' => $this->tanggal_mulai_pengobatan,
            'tanggal_selesai_pengobatan' => $this->tanggal_selesai_pengobatan,
            'hasil_akhir' => $this->hasil_akhir,
        ];
    }
}