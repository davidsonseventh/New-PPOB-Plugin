=== WP PPOB Manager ===
Contributors: (ganti dengan username wordpress.org Anda)
Tags: ppob, pulsa, token listrik, pembayaran online, tagihan, woocommerce, digiflazz
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 2.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Solusi lengkap untuk bisnis PPOB (Payment Point Online Bank) di WordPress yang terintegrasi dengan WooCommerce dan API Digiflazz.

== Description ==

WP PPOB Manager adalah plugin WordPress yang memungkinkan Anda untuk menjual produk digital seperti pulsa, paket data, token listrik, voucher game, dan membayar berbagai tagihan langsung dari website Anda. Plugin ini mengubah situs WooCommerce Anda menjadi portal PPOB yang fungsional, terintegrasi langsung dengan penyedia layanan PPOB seperti Digiflazz.

Fitur Utama:
* **Manajemen Produk Otomatis**: Sinkronkan ribuan produk digital dari penyedia API langsung ke WooCommerce.
* **Manajemen Saldo**: Pengguna memiliki dompet saldo sendiri yang bisa diisi ulang (top up) dan digunakan untuk transaksi.
* **Dasbor Pengguna**: Halaman khusus bagi pengguna untuk melihat riwayat transaksi, mutasi saldo, dan melakukan top up.
* **Kategori Fleksibel**: Atur tampilan produk di halaman depan dengan sistem kategori drag-and-drop.
* **Webhook Otomatis**: Menerima pembaruan status transaksi (sukses/gagal) secara real-time dari server PPOB.
* **Pengaturan Keuntungan**: Atur keuntungan Anda secara global, baik dalam bentuk nominal tetap (fixed) atau persentase.

== Installation ==

1.  Unggah folder `wp-ppob-manager` ke direktori `/wp-content/plugins/`, atau instal melalui menu 'Plugins' di WordPress dengan mengunggah file .zip.
2.  Aktifkan plugin melalui menu 'Plugins' di WordPress.
3.  Masuk ke menu **PPOB Manager > Pengaturan** untuk memasukkan kredensial API Digiflazz Anda (Username dan API Key).
4.  Lakukan sinkronisasi produk pertama kali melalui menu **PPOB Manager > Produk**.
5.  Atur kategori tampilan dan produk yang ingin dijual melalui menu **PPOB Manager > Kategori Tampilan**.
6.  Gunakan shortcode `[wppob_form]` untuk menampilkan daftar kategori di halaman manapun.
7.  Gunakan shortcode `[wppob_user_dashboard]` untuk menampilkan halaman dasbor pengguna.

== Frequently Asked Questions ==

= Apakah saya perlu WooCommerce? =

Ya, plugin ini memerlukan WooCommerce yang aktif untuk berfungsi, terutama untuk manajemen produk dan harga.

= Di mana saya bisa mendapatkan API Key? =

Anda perlu mendaftar di penyedia layanan PPOB seperti Digiflazz untuk mendapatkan Production API Key dan Username.

= Apakah plugin ini gratis? =

Plugin inti ini gratis dan akan tersedia di direktori WordPress.org. Namun, fitur premium seperti pengaturan keuntungan dan fitur lanjutan lainnya memerlukan kunci lisensi yang valid.

== Changelog ==

= 2.0.1 =
* Perbaikan minor pada tautan pembuatan PIN keamanan di halaman transaksi.
* Pembaruan versi stabil.

= 2.0.0 =
* REFACTOR: Struktur file dan folder dirombak total agar lebih rapi dan terorganisir.
* FITUR: Penambahan halaman manajemen saldo untuk admin dan pengguna.
* FITUR: Struktur folder baru memisahkan logika `admin`, `frontend`, dan `includes`.
* PENINGKATAN: Logika inti dipisahkan ke dalam kelas-kelas khusus untuk API, database, dan pembayaran.