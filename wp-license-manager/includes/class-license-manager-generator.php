<?php
class WPLM_Generator {
    // PENTING: Kunci ini harus sama persis dengan yang ada di plugin klien Anda.
    const ENCRYPTION_KEY = 'ganti-dengan-kunci-rahasia-anda-32'; 
    const ENCRYPTION_IV = 'ganti-dg-16-char';

    /**
     * Membuat kunci lisensi baru berdasarkan domain dan durasi.
     *
     * @param string $domain Domain target.
     * @param int $validity_days Durasi lisensi dalam hari (misal: 365).
     * @return string Kunci lisensi yang sudah di-encode.
     */
    public static function create_license_key($domain, $validity_days = 365) {
        // Bersihkan domain
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./i', '', $domain);
        $domain = rtrim($domain, '/');

        // Tentukan tanggal kadaluwarsa
        $expiry_date = date('Y-m-d', strtotime("+" . $validity_days . " days"));

        // Format data: domain|tanggal_kadaluwarsa
        $plain_text = $domain . '|' . $expiry_date;

        // Enkripsi
        $encrypted = openssl_encrypt(
            $plain_text,
            'aes-256-cbc',
            self::ENCRYPTION_KEY,
            0,
            self::ENCRYPTION_IV
        );

        // Encode ke Base64
        return base64_encode($encrypted);
    }
}
