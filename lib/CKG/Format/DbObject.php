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
    protected function convertYaTidak(?string $value, ?string $default = null): ?string
    {
        if (!isset($value))
            $value = $default;

        if ($value === 'Ya') {
            return '1';
        } elseif ($value === 'Tidak') {
            return '0';
        } elseif ($value === 'Tidak Diketahui') {
            return '2';
        }
        
        return $default;
    }

    protected function convertTindakLanjut(?string $value, ?string $default = null): ?string {
        if (!isset($value))
            $value = $default;
        
        if ($value === 'Belum Ada') {
            return '0';
        } elseif ($value === 'Dirujuk Untuk Pemeriksaan TB') {
            return '1';
        } elseif ($value === 'Sudah Terdaftar Sebagai Terduga TB') {
            return '2';
        }
        
        return $default;
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