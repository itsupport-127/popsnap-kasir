# Log Pekerjaan — November 2026 (semua repo POPSNAP)

Catatan serah-terima dan migrasi. Disusun dari pekerjaan sesi bantuan AI (Claude) di empat repo + paket Linux.
Status tiap butir memakai tiga penanda yang JUJUR:

- **TERKIRIM** = sudah dibuat sebagai zip dan diserahkan; dipasang/di-commit oleh pemilik repo.
- **TERUJI-SANDBOX** = ada tes otomatis/uji di lingkungan sandbox. Bukan uji di perangkat/jaringan/kamera asli.
- **BELUM-DIUJI-ASLI** = belum pernah dijalankan di booth, kamera, printer, QRIS, atau server produksi.

> Aturan kerja yang dipakai: sandbox hanya BACA ke GitHub (tidak bisa push). Semua perubahan diserahkan sebagai zip,
> pemilik repo yang mengekstrak, commit, dan push. Selalu `git fetch` dan basis zip = commit terbaru saat itu.
> Pekerjaan dslrBooth/payment-gate TIDAK boleh digabung ke popsnap-app (itu proyek terpisah `popsnap-paygate`).

---

## 1. popsnap-photobox (Electron + Express + React/Vite)

| Perubahan | File utama | Status |
|---|---|---|
| **Alur sesi bisa diatur** (urutan langkah disclaimer / template / payment / consent / reminder; opsi **bayar dulu**). Bayar-dulu hanya aktif kalau semua template yang tampil harganya sama > 0 (harga tetap per sesi); kalau tidak, otomatis kembali ke urutan biasa. | `backend/server.js` (`flatPriceInfo()`, endpoint qris/voucher/discount menerima `flat:true`, `/api/settings/public` mengirim `flow`), `backend/settings.js` (`general.flow.steps`), `frontend/src/state/sessionMachine.js`, `admin/FlowPage.jsx`, `screens/PaymentScreen.jsx`, `screens/TemplateScreen.jsx`, `services/paymentService.js` | TERKIRIM (sudah di upstream), TERUJI-SANDBOX |
| **Input voucher menerima semua karakter** (huruf, angka, spasi, `-`, simbol), maksimal 40 karakter. Keyboard layar punya mode "#+=" dan tombol spasi. | `components/OnScreenKeyboard.jsx`, `hooks/useCodeEntry.js` | TERKIRIM |
| **Pengaturan "Filter pada foto mentah"** (default mati). Kalau nyala, foto mentah ikut kena filter yang dipilih. | `backend/settings.js` (`general.rawFilter`), `admin/settings/SettingsGeneral.jsx`, `services/sessionService.js` (`filterShot`) | TERKIRIM (zip `photobox-filter-mentah`); cek apakah sudah di-commit |
| **Linux**: backend bisa menyajikan frontend sendiri bila `POPSNAP_FRONTEND` di-set (satu proses melayani UI + API di :4000). | `backend/server.js` (blok sebelum error handler) | TERKIRIM |
| **Skrip start/stop lintas OS**: `start-popsnap.sh` / `stop-popsnap.sh` (Linux/macOS), log+pid di `.run/`. | root repo, `.gitignore` (`.run/`) | TERKIRIM |
| **Paket Linux**: `.deb` + `.tar.gz`, layanan systemd, kiosk Chrome, `popsnap-doctor`, aturan udev kamera. Data di `/var/lib/popsnap` tidak pernah dihapus saat update/hapus paket. | `linux/` (`build-linux.sh`, `install.sh`, `bin/*`, `systemd/`, `deb/*`, `README-LINUX.md`) | TERKIRIM; repo terpisah **popsnap-photobox-linux** sekarang jadi sumber build (lihat §6) |
| **Cetak lewat browser (iframe, hormati jumlah salinan)** untuk mode Chrome kiosk (`--kiosk-printing`). | `frontend/src/services/printService.js` | ❗ Hanya di sandbox, **BELUM dikirim**, BELUM-DIUJI-ASLI. Jangan anggap sudah ada. |

Temuan analisis (tidak ada perubahan kode):
- **Sesi PB-1990-261122-0008 tanpa GIF/klip**: penyebab dugaan terkuat = backend restart (±07:36Z) atau layar hasil ditutup sebelum unggahan GIF/klip selesai. Dua perbaikan yang SUDAH diusulkan tapi belum dibuat karena belum disetujui: (a) simpan kunci sesi unggahan di DB agar tahan restart; (b) catat log berstempel waktu untuk unggahan GIF/klip/Live yang ditolak.
- Add Print: alur = pilih jumlah → (harga > 0) QRIS 120 detik → setelah lunas cetak otomatis; harga 0 = langsung cetak. Celah yang belum ditutup: gagal cetak setelah bayar tidak punya tombol cetak ulang; layar ditutup di tengah pembayaran bisa kehilangan cetakan. Lihat PRD §6.

---

## 2. popsnap-kasir (Laravel 12 + Inertia/React)

| Perubahan | File utama | Status |
|---|---|---|
| **Laporan tutup kasir (email)**: sesi photobox bernilai Rp 0 (voucher permanen pembuka booth) **tidak** masuk rekap, karena pendapatannya sudah tercatat manual di transaksi kasir. Label dan sub-baris dibuat jelas: angka "dari Photobox" vs "dari Kasir (manual)". | `app/Services/CashierShiftService.php` (`calculatePhotoboxRecap`, `calculatePhotoboxTotals` hanya `amount > 0`), `Pages/Dashboard/CashierShifts/Show.jsx`, `resources/views/emails/cashier-shifts/closing-report.blade.php` | TERKIRIM, TERUJI-SANDBOX |
| **Promo → tombol Duplikat** sebelumnya 500; diperbaiki. | `app/Http/Controllers/Apps/PromoController.php` (`duplicate`) | TERKIRIM, TERUJI-SANDBOX |
| **Shift terikat studio photobox** (lihat bagian khusus di bawah). | migrasi `2026_11_25_000100_create_cashier_shift_studios_table`, `CashierShiftService`, `CashierShift`, `StoreCashierShiftRequest`, `CashierShiftController`, `TransactionController`, `ShiftStudioPicker.jsx`, Index/Show/Transactions JSX, email | **DISIAPKAN di sandbox (zip), BELUM di-commit**; TERUJI-SANDBOX; BELUM-DIUJI-ASLI |

### 2a. Shift terikat studio — detail & cara migrasi

Masalah: transaksi photobox tidak punya kasir, jadi dikaitkan ke shift lewat waktu bayar. Bila dua shift terbuka bersamaan,
semua transaksi photobox dari SEMUA booth masuk ke shift yang dibuka paling awal.

Solusi: shift boleh memegang satu atau lebih studio.

- Tabel baru `cashier_shift_studios (cashier_shift_id FK cascade, photobox_studio_id TANPA FK, unique(shift, studio))`.
- **Tidak ada baris = semua studio** (perilaku lama). Semua shift lama otomatis begitu → angka laporan lama tidak berubah.
- Shift yang sudah **ditutup** memakai angka tersimpan, jadi tidak berubah.
- `photobox_studio_id` sengaja tanpa FK: bila studio dihapus, shift yang dibatasi tetap terbatas (tidak melebar jadi "semua").
  Namanya tampil "Studio dihapus #id".
- Aturan pemilik tiap transaksi photobox (tepat satu pemilik):
  1. shift hanya memiliki transaksi dari studio yang dipegangnya;
  2. shift yang menyebut studio itu **eksplisit** menang atas shift "semua studio";
  3. sesama tingkat: yang dibuka paling awal menang (seri: id terkecil);
  4. shift lupa-tutup tidak mengklaim di luar `MAX_SHIFT_HOURS` (48 jam).
- Form buka shift (halaman Shift Kasir dan POS) punya pemilih studio; kosong = semua studio. Tidak bisa diubah setelah dibuka
  (salah pilih → tutup lalu buka lagi).
- Daftar transaksi kasir (`SaleController`) dan retur **sudah** dibatasi ke kasir sendiri untuk non-superadmin — tidak diubah.
- Data pelanggan (poin, voucher, kredit, segmen) **sengaja tidak dipisah** per kasir/studio.

**Insiden 26 Nov 2026 (deploy tanpa migrate) & perbaikan:**
- Gejala: halaman Transaksi 500 dan tutup shift gagal. Log: `Unknown column 'other.photobox_store_id'` (kolom dari migrasi `2026_11_26_000100` belum ada di DB server) dan `storeOptions() undefined` (file ter-deploy sebagian).
- Perbaikan: `CashierShiftService::storeSchemaReady()`. Selama migrasi belum jalan, logika toko dilewati (shift = semua studio / studio tertentu) dan tidak ada 500. **Tetap wajib `php artisan migrate`** agar fitur toko aktif. Ada tes simulasi "belum migrate".
- Modal awal 0: `<input type="number">` membuang isian bertitik/berkoma ("205.000" / "205,000") jadi kosong → 0 tanpa peringatan. Diganti `RupiahInput` (hanya angka, tampil "205.000") di form buka shift (halaman Shift & Transaksi) + konfirmasi bila modal Rp 0. Modal tidak bisa diubah setelah shift dibuka (tutup dan buka ulang).

Langkah pasang: ekstrak zip → `php artisan migrate` → `npm run build` → buka satu shift uji dengan studio terpilih.
Rollback: `php artisan migrate:rollback --step=1` menghapus tabel pivot; shift kembali berperilaku "semua studio".

**Risiko yang harus dipahami:** bila Studio B punya penjualan tetapi TIDAK ADA shift terbuka yang memegang Studio B
(dan tidak ada shift "semua studio"), penjualan itu tidak masuk laporan shift siapa pun. Pastikan selalu ada shift yang
memegang tiap studio yang buka, atau pakai "Semua studio" untuk satu shift utama.

Tes: `tests/Feature/CashierShifts/CashierShiftTest.php` (+6 tes studio). Seluruh suite: 64 tes gagal sebelum dan sesudah,
himpunan yang sama (kegagalan lingkungan sandbox: manifes Vite belum dibangun, tabel `pricing_rules` di SQLite, dst.) — bukan dari perubahan ini.

---

## 3. popsnap-gallery (Node/Express, unggah langsung ke S3-kompatibel)

| Perubahan | File utama | Status |
|---|---|---|
| **"Unduh semua" tahan banting**: ZIP dengan retry ke cloud (`s3.getRetry`), aliran dihentikan bila jumlah file tidak cocok; daftar cadangan bertema (hanya file yang belum terunduh) disimpan di localStorage `dl:<token>`. | `src/s3.js`, `src/server.js`, `src/page.js` | TERKIRIM, TERUJI-SANDBOX (Playwright), BELUM-DIUJI-ASLI. Penyebab sebenarnya "hanya beberapa file terunduh" **belum terkonfirmasi** (pengguna belum mengirim jumlah/perangkat/log). |
| **Penanda sumber unggahan**: kolom `files.via_admin` (1 = benar-benar diunggah lewat Unggah Manual admin). Label sesi: "Dari booth" / "Unggah manual" / "Dari booth + unggah manual (N file)". Endpoint baru `GET /admin/api/sessions/:token/files` (hak `link.view`) dan daftar "File di link ini" di dialog Atur link. | `src/db.js`, `src/server.js`, `src/admin.html`, `test/run.js` | Zip `gallery-daftar-file` **terkirim, tapi cek sudah di-commit atau belum** |

Catatan penting (koreksi): versi awal label memasang penanda `via_manual` di endpoint **booth** (`/done`), bukan di unggah manual
admin, sehingga file booth ikut tertandai manual. Diperbaiki dengan kolom baru `via_admin`; `via_manual` dibiarkan ada tapi **tidak dipakai**.
Unggahan manual yang terjadi sebelum perbaikan tidak bisa dipulihkan penandanya.

Duplikasi unggah manual vs otomatis: satu file = pasangan unik `(token, fid)`; bila sudah `ok`, kiriman booth berikutnya dilewati
(tidak dobel). Link kembar hanya terjadi bila folder manual tidak membawa `popsnap-share.json` ataupun nomor transaksi.

---

## 4. popsnap-remote (Express + UI web, VPS Docker)

| Perubahan | File utama | Status |
|---|---|---|
| Editor **Profil Live & Foto** (ISO/Tv/Av/WB terpisah, jadwal siang/malam) — dibuat di sesi sebelumnya (commit `422204c`). | `src/spec.js` (salinan persis `backend/remoteSpec.js` di photobox!), `public/app.js` | TERKIRIM sebagai zip |
| **Kejelasan editor profil**: ringkasan "Tersimpan: …", banner kuning bila periode jadwal sedang aktif (profil dasar diredupkan), teks bantuan "hanya mengubah Foto/Live", catatan beda kode Windows vs Linux. | `public/app.js`, `public/style.css` | TERKIRIM (zip `remote-profil-jelas`), BELUM-DIUJI-ASLI di browser/VPS |
| **Tab Alat › Kamera disederhanakan**: hanya "Cek kamera" + tautan ke Pengaturan › Kamera › Profil Live & Foto (kontrol Terapkan satu-satu dihapus; perintah `camera.exposure.*` di server tidak diubah). | `public/app.js` | TERKIRIM |

Aturan: setiap perubahan `src/spec.js` WAJIB disalin persis ke `backend/remoteSpec.js` di photobox.
Usulan yang belum dikerjakan: setelan `templateChoice`/`singleTemplateId`, `camera.liveView.filterId/fitTemplate`,
`editor.showNoFilter`, `general.rawFilter` di spec; import/export template lewat Remote (butuh endpoint unggah besar — lihat PRD §6).

---

## 5. Diskusi tanpa kode (keputusan/arah)

- **Kamera idle (hemat daya)**: sekarang live view mati otomatis ±2 menit tanpa pemakai, tetapi koneksi SDK/gPhoto2 ke kamera
  tidak pernah diputus. Usulan bertingkat (siaga → tidur/putus → bangun saat layar disentuh → jam operasional), diberi saklar
  admin default **mati**. Belum dibuat. Perlu uji Nikon/Canon asli.
- **Import/export template via Remote**: kanal Remote hanya membawa JSON kecil (512 KB perintah / 2 MB hasil). Template berisi gambar,
  jadi perlu endpoint unggah/unduh terpisah; logika zip template sekarang ada di frontend (`admin/templateIO.js`), bukan backend.
- **Linux + kamera Nikon**: butuh bridge gPhoto2 dan model kamera. PC uji memakai **Canon EOS 1100D**.

---

## 6. Paket Linux (repo popsnap-photobox-linux) — pengalaman build & pasang

- Build `.deb`: `./build-linux.sh <versi>` di Linux (atau WSL Ubuntu). Hasil di `linux/dist/` (`.deb`, `.tar.gz`, `SHA256SUMS`).
  Naikkan angka versi tiap build (downgrade ditolak pemasang).
- **Windows**: `build-linux.bat` lewat Docker/WSL **bermasalah** (dilaporkan "selesai" tapi tidak ada hasil; penyebab belum dipastikan).
  Cara andal: WSL Ubuntu → clone ke `~` (bukan `/mnt/d`) → `sh linux/build-clean.sh <versi>`.
- **`vite build` crash "Illegal instruction"** di PC lama/WSL (Vite 8 memakai binary Rust `rolldown` yang butuh instruksi CPU tertentu).
  Jalan keluar: bangun `frontend/dist` di mesin lain (hasilnya tidak tergantung CPU), taruh di `frontend/dist`, lalu
  `sh linux/build-linux.sh --version X --skip-frontend`. Jangan pakai `frontend/dist` hasil `build-exe.bat` (aset relatif `./`).
- Pemasangan memicu **Chromium snap** di Ubuntu 24.04 (tidak cocok). Pasang **Google Chrome .deb**; kiosk memilih `google-chrome-stable` dulu.
- `popsnap-doctor` melaporkan peringatan wajar (tanpa webcam, tanpa printer bawaan CUPS). Perlu dibereskan di booth: `pkill -f gvfs-gphoto2-volume-monitor`,
  `systemctl --user mask gvfs-gphoto2-volume-monitor.service`, set printer bawaan (`lpoptions -d NAMA`).
- **Kamera dipegang proses lain**: gPhoto2 gagal "Could not claim the USB device (busy)" karena aplikasi lama **CamCoServer (papyrus-camera, dijalankan PM2)**
  menahan USB. Dua aplikasi tidak bisa memakai kamera bersamaan; harus bergantian (`pm2 stop papyrus-camera` lalu start POPSNAP, dan sebaliknya).
  Skrip `ganti-popsnap`/`ganti-papyrus` dirancang tapi `ganti-papyrus` rusak saat ditempel di terminal — **belum diuji**, buat ulang sebagai file.
- Yang BELUM terbukti di Linux asli: live view & jepret Canon/Nikon lewat gPhoto2, systemd saat boot, `--kiosk-printing` ke CUPS, webcam.

### 2b. Shift per TOKO (di atas shift per studio) — detail & cara migrasi

Kebutuhan: studio dikelompokkan per toko (mis. POPSNAP punya beberapa studio, CCM punya beberapa studio), dan shift bisa
memegang satu toko lengkap atau hanya sebagian studio di toko itu.

- Migrasi `2026_11_26_000100_create_photobox_stores_table`: tabel `photobox_stores (id, name unik)`, kolom `photobox_studios.store_id`
  (nullable, FK, `nullOnDelete` → hapus toko membuat studio "tanpa toko"), dan `cashier_shifts.photobox_store_id` (nullable, **tanpa FK**).
- Tiga tingkat cakupan shift (semakin spesifik semakin menang):
  | Rank | Cakupan | Data |
  |---|---|---|
  | 0 | studio tertentu | ada baris di `cashier_shift_studios` (boleh dengan `photobox_store_id`) |
  | 1 | satu toko penuh | `photobox_store_id` terisi, tanpa baris studio → semua studio toko itu, **termasuk yang ditambah belakangan** |
  | 2 | semua studio | tanpa toko, tanpa baris studio (semua shift lama) |
  Sesama rank: `opened_at` paling awal menang (seri: id terkecil). `MAX_SHIFT_HOURS` 48 tetap berlaku. Shift tertutup memakai angka beku.
- `photobox_store_id` sengaja tanpa FK; hapus toko **ditolak** selama ada shift terbuka yang memegangnya. Setelah dihapus, shift lama tampil "Toko dihapus #id".
- Form buka shift: pilih "Semua studio" / satu toko (default semua studio toko itu, bisa centang studio tertentu) / "Studio tanpa toko".
  Bila belum ada toko, tampilan sama seperti sebelumnya (daftar studio datar). Server menolak studio yang bukan milik toko terpilih.
- Kelola toko: halaman **Transaksi Photobox** → panel studio → bagian "Toko" (tambah / ubah nama / hapus) + pilihan toko per studio. Izin `photobox-manage`.
- Label cakupan (`CashierShiftService::scopeLabel`) tampil di detail shift dan email laporan tutup shift.
- File baru/berubah: `PhotoboxStore`, `PhotoboxStudio`, `CashierShift`, `CashierShiftService` (`openShift`, `storeOptions`, `scopeLabel`, `ownedPhotoboxQuery`),
  `StoreCashierShiftRequest`, `CashierShiftController`, `TransactionController`, `PhotoboxTransactionController` (store CRUD + `updateStudio`),
  `routes/web.php`, `ShiftStudioPicker.jsx`, `CashierShifts/Index|Show.jsx`, `Transactions/Index.jsx`, `PhotoboxTransactions/Index.jsx`, email blade.

**Hak lihat transaksi photobox (tambahan 2b):**
- Admin/superadmin (superadmin, izin `cashier-shifts-force-close`, atau `photobox-manage`) melihat semua toko & studio, dengan filter **Toko** dan **Studio** terpisah (termasuk "Tanpa toko").
- Kasir biasa hanya melihat transaksi **cakupan shift aktifnya** (studio/toko yang dipilih) **sejak shift dibuka**; ringkasan, daftar studio, jumlah TEST ikut dibatasi.
  Tanpa shift aktif → kosong dengan pesan. Studio/toko lain di luar cakupan tidak bocor lewat filter.
- Ubah / tandai TEST / hapus / simpan kontak WA pada transaksi di luar cakupan → 403. Hapus massal TEST dibatasi ke cakupan.
- Email & detail shift: rekap photobox sudah hanya memuat cakupan shift (aturan kepemilikan); sekarang dikelompokkan **per toko dengan subtotal**; label "Cakupan Photobox".
- UI kelola: kartu per toko berisi studionya, tambah studio langsung di kartu toko, menu "Kelola" per studio (pindah toko, ubah nama, token baru, nonaktifkan, hapus); responsif 1/2/3 kolom.
- Catatan: dashboard utama (`DashboardController`) masih menampilkan ringkasan photobox semua studio (tidak diubah).

Langkah pasang: ekstrak zip → `php artisan migrate` → `npm run build` → buat toko di Transaksi Photobox → tetapkan studio ke tokonya → buka shift uji.
Rollback: `php artisan migrate:rollback --step=1` (hapus tabel toko + dua kolom); shift kembali "semua studio / studio tertentu".

Risiko tambahan: toko tanpa shift pemegang (dan tanpa shift "semua studio") → penjualannya tidak masuk laporan shift mana pun;
cakupan shift tidak bisa diubah setelah dibuka; memindahkan studio antar toko saat shift terbuka mengubah kepemilikan transaksi yang **belum** ditutup
(shift tertutup tidak terpengaruh).

Tes: +6 tes toko di `CashierShiftTest` (rank, studio baru, isolasi antar toko, validasi, kelola toko + guard hapus, label). `TransactionsIndexTest`
disesuaikan (daftar migrasi manual + tabel `cashier_shifts` minimal). Suite penuh: tetap 64 gagal, himpunan sama dengan baseline.

---

## 7. Daftar terbuka (belum dikerjakan / menunggu keputusan)

1. Penyebab "download semua hanya sebagian" di galeri (butuh data dari perangkat tamu).
2. Simpan kunci sesi unggahan di DB + log unggahan GIF/klip/Live yang ditolak (kasus 0008).
3. Tombol **Cetak ulang** setelah bayar Add Print gagal + pengecekan pembayaran yang tertinggal.
4. Printing mode browser (iframe) — ada di sandbox, belum dikirim.
5. Kamera idle bertingkat; bridge Nikon gPhoto2 (menunggu model).
6. Pencatatan siapa yang melakukan unggah manual di galeri.
7. Uji shift per toko/studio dengan dua shift dan dua booth sungguhan (migrate + build dulu).
8. Penawaran lama: sistem nama tamu, akhiran DOKU `X-EXTERNAL-ID`, kertas default "ikut template", detail error S3 di tes bucket galeri,
   bersihkan `PricingRuleTest` dan tes pull-gallery di kasir, "mode seperti stiker" untuk filter, webhook galeri→kasir, batas booth remote v1,
   baris Add Print sebelum marker `addprint`, force-close shift #29 dan #63.
