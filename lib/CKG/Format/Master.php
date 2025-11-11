<?php

namespace CKG\Format;

class Converter {
    public static $metode = [
        [
            "value" => "1",
            "text" => "Paralel"
        ],
        [
            "value" => "2",
            "text" => "Sekuensial Negatif"
        ],
        [
            "value" => "3",
            "text" => "Sekuensial Positif"
        ],
        [
            "value" => "99",
            "text" => "Tunggal"
        ]
    ];
    public static $tempat = [
        [
            "value" => "1",
            "text" => "Masyarakat"
        ],
        [
            "value" => "2",
            "text" => "Klinik"
        ],
        [
            "value" => "3",
            "text" => "Puskesmas"
        ],
        [
            "value" => "4",
            "text" => "Rumah Sakit"
        ],
        [
            "value" => "5",
            "text" => "Pondok Pesantren"
        ],
        [
            "value" => "6",
            "text" => "Lapas\/Rutan\/LPKA"
        ],
        [
            "value" => "7",
            "text" => "Sekolah"
        ],
        [
            "value" => "8",
            "text" => "Tempat Kerja"
        ],
        [
            "value" => "9",
            "text" => "Dokter Praktek Mandiri"
        ],
        [
            "value" => "99",
            "text" => "Lain-lain"
        ]
    ];
    public static $jenis_kelamin = [
        [
            "value" => "1",
            "text" => "Laki-laki"
        ],
        [
            "value" => "2",
            "text" => "Perempuan"
        ]
    ];
    public static $pekerjaan = [
        [
            "value" => "1",
            "text" => "TNI\/ Polri"
        ],
        [
            "value" => "2",
            "text" => "PNS"
        ],
        [
            "value" => "3",
            "text" => "Guru\/ Dosen"
        ],
        [
            "value" => "4",
            "text" => "Pegawai Swasta\/ BUMN\/ BUMD"
        ],
        [
            "value" => "5",
            "text" => "Wiraswasta"
        ],
        [
            "value" => "6",
            "text" => "IRT"
        ],
        [
            "value" => "7",
            "text" => "Pelajar\/ Mahasiswa"
        ],
        [
            "value" => "8",
            "text" => "Siswa Berasrama"
        ],
        [
            "value" => "9",
            "text" => "Tidak Bekerja"
        ],
        [
            "value" => "10",
            "text" => "Buruh"
        ],
        [
            "value" => "11",
            "text" => "Warga Binaan Pemasyarakatan"
        ],
        [
            "value" => "12",
            "text" => "Petani\/ Peternak\/Nelayan"
        ],
        [
            "value" => "13",
            "text" => "Sopir"
        ],
        [
            "value" => "14",
            "text" => "Tenaga Profesional Non Medis"
        ],
        [
            "value" => "15",
            "text" => "Tenaga Profesional Medis"
        ],
        [
            "value" => "16",
            "text" => "Tidak Diketahui"
        ],
        [
            "value" => "17",
            "text" => "Kader Kesehatan"
        ],
        [
            "value" => "18",
            "text" => "Lainnya"
        ]
    ];
    public static $jenis_kontak = [
        [
            "value" => "1",
            "text" => "Kontak Serumah"
        ],
        [
            "value" => "2",
            "text" => "Kontak Erat"
        ]
    ];
    public static $jenis_kasus_indeks = [
        [
            "value" => "1",
            "text" => "TBC Paru Bakteriologis"
        ],
        [
            "value" => "2",
            "text" => "TBC Klinis"
        ],
        [
            "value" => "3",
            "text" => "TBC Ekstra Paru"
        ]
    ];
    public static $ya_tidak = [
        [
            "value" => "0",
            "text" => "Tidak"
        ],
        [
            "value" => "1",
            "text" => "Ya"
        ]
    ];
    public static $ya_tidak_diketahui = [
        [
            "value" => "1",
            "text" => "Ya"
        ],
        [
            "value" => "0",
            "text" => "Tidak"
        ],
        [
            "value" => "2",
            "text" => "Tidak Diketahui"
        ]
    ];
    public static $hasil_skrining_gejala = [
        [
            "value" => "0",
            "text" => "Tidak Ada Gejala dan Tanda TBC"
        ],
        [
            "value" => "1",
            "text" => "Ada Gejala dan Tanda TBC"
        ]
    ];
    public static $hasil_radiologi = [
        [
            "value" => "1",
            "text" => "Normal"
        ],
        [
            "value" => "2",
            "text" => "Abnormalitas TBC"
        ],
        [
            "value" => "3",
            "text" => "Abnormalitas Bukan TBC"
        ]
    ];
    public static $hasil_skor_ai = [
        [
            "value" => "1",
            "text" => "Normal"
        ],
        [
            "value" => "2",
            "text" => "Abnormalitas"
        ]
    ];
    public static $tcm_hasil = [
        [
            "value" => "1",
            "text" => "Neg"
        ],
        [
            "value" => "2",
            "text" => "Rif Sen"
        ],
        [
            "value" => "3",
            "text" => "Rif Res"
        ],
        [
            "value" => "4",
            "text" => "Rif Indet"
        ],
        [
            "value" => "5",
            "text" => "INVALID"
        ],
        [
            "value" => "6",
            "text" => "ERROR"
        ],
        [
            "value" => "7",
            "text" => "NO RESULT"
        ],
        [
            "value" => "8",
            "text" => "TDL"
        ]
    ];
    public static $hasil_penegakan_diagnosis = [
        [
            "value" => "1",
            "text" => "Terkonfirmasi Bakteriologis"
        ],
        [
            "value" => "2",
            "text" => "Terdiagnosis klinis"
        ],
        [
            "value" => "3",
            "text" => "Bukan TBC"
        ]
    ];
    public static $kasus_tbc = [
        [
            "value" => "0",
            "text" => "Bukan TBC"
        ],
        [
            "value" => "1",
            "text" => "TBC"
        ]
    ];
    public static $ref_pos_neg = [
        [
            "value" => "2",
            "text" => "Neg"
        ],
        [
            "value" => "1",
            "text" => "Pos"
        ]
    ];
    public static $status_wbp = [
        [
            "value" => "1",
            "text" => "Narapidana"
        ],
        [
            "value" => "2",
            "text" => "Tahanan"
        ],
        [
            "value" => "3",
            "text" => "Anak"
        ]
    ];
    public static $tindak_lanjut_skrining = [
        [
            "value" => "0",
            "text" => "Belum Ada"
        ],
        [
            "value" => "1",
            "text" => "Dirujuk Untuk Pemeriksaan TB"
        ],
        [
            "value" => "2",
            "text" => "Sudah Terdaftar Sebagai Terduga TB"
        ]
    ];
    public static $tindak_lanjut_tpt_skrining = [
        [
            "value" => "0",
            "text" => "Tidak dilakukan pemberian TPT"
        ],
        [
            "value" => "1",
            "text" => "Dirujuk Untuk Pemberian TPT"
        ],
        [
            "value" => "2",
            "text" => "Sudah Terdaftar Sebagai Pasien TPT"
        ],
        [
            "value" => "3",
            "text" => "Koordinasi dengan Program HIV dan input data TPT pada SIHA"
        ]
    ];
    public static $cxr_jenis_rujukan = [
        [
            "value" => 1,
            "text" => "di dalam SITB"
        ],
        [
            "value" => 2,
            "text" => "di luar SITB"
        ]
    ];
}