<?php

namespace CKG\Format;

abstract class DbObject {
    public abstract function toDbRecord(): array;
    public abstract function fromDbRecord(array $data);

    /**
     * Convert gender to ID format
     * 
     * @param string|null $gender
     * @return int|null
     */
    protected function convertGender(?string $gender): ?string
    {
        if ($gender === 'Laki-laki' || $gender === 'L') {
            return '1'; // Male
        } elseif ($gender === 'Perempuan' || $gender === 'P') {
            return '2'; // Female
        }
        
        return null;
    }
    
    /**
     * Convert boolean value to ID format (1 for true, 0 for false, null for null)
     * 
     * @param string|null $value
     * @return int|null
     */
    protected function convertYaTidak($value, $default = null): ?string
    {
        if ($value === 'Ya' || $value === '1' || intval($value) === 1) {
            return '1';
        } elseif ($value === 'Tidak' || $value === '0' || intval($value) === 0) {
            return '0';
        } elseif ($value === 'Tidak Diketahui' || $value === '2' || intval($value) === 2) {
            return '2';
        }
        
        return isset($default) ? $this->convertYaTidak($default) : null;
    }

    protected function convertTindakLanjut($value, $default = null): ?string {
        if ($value === 'Belum Ada') {
            return '0';
        } elseif ($value === 'Dirujuk Untuk Pemeriksaan TB') {
            return '1';
        } elseif ($value === 'Sudah Terdaftar Sebagai Terduga TB') {
            return '2';
        }
        
        return isset($default) ? $this->convertTindakLanjut($default) : null;
    }
    
    /**
     * Convert radiology result to ID format
     * 
     * @param string|null $result
     * @return int|null
     */
    protected function convertRadiologyResult(?string $value): ?string
    {
        if ($value === 'Normal') {
            return '1'; // Normal
        } elseif ($value === 'Abnormalitas TBC') {
            return '2'; // Abnormalitas TBC
        } elseif ($value === 'Abnormalitas Bukan TBC') {
            return '3'; // Abnormalitas Bukan TBC
        }
        
        return null;
    }
}