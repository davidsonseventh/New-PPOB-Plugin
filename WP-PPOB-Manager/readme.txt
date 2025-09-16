=== WP PPOB Manager ===
Contributors: (your name)
Tags: ppob, pulsa, token listrik, pembayaran online
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Solusi lengkap untuk bisnis PPOB (Payment Point Online Bank) di WordPress.

== Description ==

WP PPOB Manager adalah plugin WordPress yang memungkinkan Anda untuk menjual produk digital seperti pulsa, paket data, token listrik, voucher game, dan membayar berbagai tagihan langsung dari website Anda. Plugin ini terintegrasi dengan WooCommerce dan penyedia layanan PPOB untuk otomatisasi transaksi.

== Installation ==

1. Unggah folder `wp-ppob-manager` ke direktori `/wp-content/plugins/`.
2. Aktifkan plugin melalui menu 'Plugins' di WordPress.
3. Masuk ke menu 'PPOB Manager' -> 'Pengaturan' untuk memasukkan kredensial API Anda.
4. Lakukan sinkronisasi produk pertama kali melalui menu 'Produk'.
5. Atur kategori tampilan dan produk Anda melalui menu 'Kategori Tampilan'.
6. Gunakan shortcode `[wppob_form]` untuk menampilkan daftar kategori di halaman depan.

== Frequently Asked Questions ==

= Apakah saya perlu WooCommerce? =
Ya, plugin ini memerlukan WooCommerce untuk manajemen produk dan harga.

= Di mana saya bisa mendapatkan API Key? =
Anda perlu mendaftar di penyedia layanan PPOB seperti Digiflazz untuk mendapatkan API Key dan Username.

== Changelog ==

= 2.0.0 =
* REFACTOR: Struktur file dan folder dirombak total agar lebih rapi dan terorganisir.
* FITUR: Penambahan halaman manajemen saldo untuk admin dan pengguna.
* FITUR: Struktur folder baru memisahkan logika `admin`, `frontend`, dan `includes`.
* PENINGKATAN: Logika inti dipisahkan ke dalam kelas-kelas khusus untuk API, database, dan pembayaran.
