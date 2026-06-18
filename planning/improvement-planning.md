# Improvement Planning

## Objective

Dokumen ini merangkum arah pengembangan fitur berikutnya untuk sistem Point of Sale agar lebih relevan untuk kebutuhan operasional toko nyata, bukan hanya transaksi kasir dasar. Fokus utama improvement adalah:

- memperkuat operasional harian toko,
- menutup loop bisnis dari pembelian sampai penjualan,
- meningkatkan kontrol owner/admin,
- meningkatkan kecepatan kerja kasir,
- dan memperbaiki kualitas insight untuk pengambilan keputusan.

---

## Current Product Assessment

### Existing Strengths

Saat ini sistem sudah memiliki fondasi yang cukup baik untuk kebutuhan inti:

- transaksi kasir,
- manajemen produk dan kategori,
- manajemen pelanggan,
- supplier,
- piutang,
- hutang supplier,
- laporan penjualan dan keuntungan,
- payment settings,
- bank account settings,
- barcode,
- PDF/invoice printing,
- notifikasi stok,
- user, role, dan permission.

### Main Gaps

Walaupun fondasinya kuat, ada beberapa gap penting agar sistem benar-benar siap dipakai secara luas oleh user toko:

- inventory control belum matang,
- belum ada alur pembelian/restock formal,
- belum ada retur,
- belum ada shift kasir dan cash closing,
- belum ada audit log yang memadai,
- promosi/harga fleksibel masih terbatas,
- laporan operasional owner masih bisa diperdalam,
- dan pengalaman kasir masih bisa dioptimalkan.

---

## Roadmap Principles

Prioritas roadmap dibagi berdasarkan prinsip berikut:

1. **Operational First**  
   Fitur yang paling dekat dengan pain point toko harian diprioritaskan lebih dulu.

2. **Financial Accuracy**  
   Fitur yang mempengaruhi stok, hutang, piutang, dan kas harus memiliki integritas data yang baik.

3. **Role-Oriented UX**  
   Owner, admin, dan kasir memiliki kebutuhan berbeda. Improvement harus mempertimbangkan role tersebut.

4. **Scalable Foundation**  
   Beberapa fitur awal perlu dirancang agar nanti mendukung multi-outlet, approval workflow, dan reporting lebih lanjut.

---

## Phase Overview

### Phase 1 — Operational Core Stabilization

Fokus phase ini adalah memperkuat operasional dasar toko agar data stok, transaksi, dan aktivitas user lebih akurat.

Module utama:

- Stock Opname
- Stock Mutation
- Sales Return
- Cashier Shift & Cash Closing
- Audit Log
- Frontend/Backend authorization consistency improvement

### Phase 2 — Procurement and Financial Flow

Fokus phase ini adalah menutup loop bisnis pembelian dan hutang/piutang agar operasional back-office lebih lengkap.

Module utama:

- Purchase Order
- Goods Receiving
- Supplier Return
- Payable lifecycle improvement
- Receivable lifecycle improvement
- Aging & reminders

### Phase 3 — Commercial and Growth Features

Fokus phase ini adalah meningkatkan kemampuan bisnis untuk growth, retention, dan efisiensi penjualan.

Module utama:

- Promo & Pricing Engine
- Customer Loyalty
- Customer segmentation
- Campaign & reminder automation
- Advanced sales insights

### Phase 4 — Control, Scale, and Integration

Fokus phase ini adalah memperkuat kontrol bisnis skala besar, kesiapan multi-branch, dan integrasi eksternal.

Module utama:

- Multi outlet / multi warehouse
- Approval workflow
- Tax & pricing rules
- Import/export toolkit
- Backup/restore
- External integrations

---

## Phase 1 — Operational Core Stabilization

## 1. Stock Opname

### Problem

Saat ini sistem sudah memiliki stok produk, tetapi belum ada proses audit stok fisik terhadap stok sistem. Tanpa stock opname, selisih stok akan sulit dilacak.

### Goals

- memungkinkan user menghitung stok fisik,
- membandingkan stok sistem vs stok aktual,
- mencatat selisih dan alasan penyesuaian,
- menjaga integritas inventori.

### Key Features

- buat sesi stock opname,
- filter per kategori / supplier / produk,
- input hasil hitung fisik,
- tampilkan selisih,
- posting adjustment ke stok,
- simpan alasan adjustment,
- support draft dan finalize.

### Main User Roles

- admin,
- owner,
- staff gudang.

### Suggested Data Scope

- stock_opnames
- stock_opname_items
- adjustment_reason
- created_by / approved_by

### UI/UX Scope

- halaman daftar opname,
- halaman detail opname,
- tabel per produk,
- indicator selisih plus/minus,
- summary total item sesuai / tidak sesuai.

### Business Rules

- opname final tidak boleh diubah sembarangan,
- setiap selisih harus punya alasan,
- penyesuaian stok harus membentuk jejak mutasi.

### Priority

Sangat tinggi.

---

## 2. Stock Mutation

### Problem

Stok perlu punya histori perubahan yang bisa diaudit. Saat ini stok berubah akibat transaksi, tetapi mutasi stok operasional belum terlihat sebagai modul yang lengkap.

### Goals

- mencatat semua perubahan stok,
- membedakan sumber mutasi,
- memudahkan audit per produk.

### Key Features

- mutasi masuk,
- mutasi keluar,
- stok rusak,
- stok hilang,
- stok expired,
- adjustment manual,
- histori mutasi per produk.

### Mutation Sources

- pembelian,
- penjualan,
- retur penjualan,
- retur supplier,
- stock opname,
- adjustment manual.

### UI/UX Scope

- halaman histori mutasi stok,
- filter produk / tanggal / jenis mutasi,
- detail asal transaksi atau dokumen.

### Business Rules

- semua perubahan stok non-transaksi wajib tercatat,
- mutasi manual perlu permission khusus,
- jika ada approval workflow, mutasi besar harus menunggu approval.

### Priority

Sangat tinggi.

---

## 3. Sales Return

### Problem

Retur penjualan adalah kebutuhan nyata. Tanpa retur, stok, kas, dan laporan bisa tidak sinkron dengan operasional toko.

### Goals

- memungkinkan retur sebagian atau penuh,
- mengembalikan stok jika relevan,
- mengoreksi laporan penjualan,
- mencatat alasan retur.

### Key Features

- pilih transaksi asal,
- pilih item yang diretur,
- qty retur per item,
- alasan retur,
- metode refund,
- opsi tukar barang,
- jurnal koreksi stok dan transaksi.

### Return Types

- retur dengan refund tunai,
- retur menjadi saldo toko / credit note,
- retur tukar barang,
- retur barang rusak.

### UI/UX Scope

- aksi retur dari halaman history transaksi,
- modal / halaman khusus retur,
- preview dampak stok dan refund,
- histori retur.

### Business Rules

- qty retur tidak boleh melebihi qty beli,
- retur harus terkait transaksi asal,
- transaksi yang sudah diretur sebagian harus menampilkan sisa qty yang masih bisa diretur.

### Priority

Sangat tinggi.

---

## 4. Cashier Shift & Cash Closing

### Problem

Operasional kasir butuh pembukaan shift, kas awal, kas akhir, dan pengecekan selisih. Ini fitur penting untuk toko yang punya lebih dari satu kasir atau lebih dari satu shift.

### Goals

- mencatat pembukaan dan penutupan shift,
- mengontrol modal kas awal,
- memverifikasi uang kas fisik saat tutup shift,
- menghitung selisih.

### Key Features

- buka shift,
- input modal awal,
- tutup shift,
- input kas fisik akhir,
- hitung expected cash,
- selisih lebih / kurang,
- catatan shift,
- histori shift per kasir.

### Suggested Metrics

- total transaksi,
- total tunai,
- total non-tunai,
- total refund,
- kas seharusnya,
- kas aktual,
- selisih.

### UI/UX Scope

- dashboard shift aktif,
- halaman buka/tutup shift,
- ringkasan performa shift,
- filter histori shift.

### Business Rules

- kasir tidak bisa transaksi jika shift belum dibuka,
- shift aktif tunggal per kasir,
- closing shift idealnya tidak bisa diubah sembarangan setelah final.

### Priority

Sangat tinggi.

---

## 5. Audit Log

### Problem

Owner/admin perlu tahu siapa melakukan perubahan penting di sistem. Ini penting untuk keamanan dan akuntabilitas.

### Goals

- mencatat aktivitas penting,
- memudahkan investigasi,
- meningkatkan trust internal.

### Key Features

- log create/update/delete,
- log perubahan harga,
- log perubahan stok,
- log perubahan payment setting,
- log role/permission/user update,
- log konfirmasi pembayaran,
- log void/retur.

### Suggested Audit Events

- product.updated,
- stock.adjusted,
- transaction.voided,
- payment.confirmed,
- user.role_changed,
- role.permission_changed.

### UI/UX Scope

- halaman audit log,
- filter by user / module / tanggal / event,
- detail before vs after untuk field penting.

### Business Rules

- log bersifat append-only,
- akses audit log dibatasi role tertentu,
- event sensitif wajib terekam.

### Priority

Sangat tinggi.

---

## 6. Authorization Consistency Improvement

### Problem

RBAC dasar sudah ada, tetapi konsistensi backend dan frontend masih perlu diperkuat agar action button, page access, dan permission mapping selaras.

### Goals

- memastikan UI hanya menampilkan action yang memang boleh,
- memastikan role super-admin konsisten,
- memastikan permission naming rapi dan tidak ambigu.

### Key Features

- policy review per module,
- action visibility audit di frontend,
- permission map standardization,
- role-permission sync standard,
- exception handling yang konsisten untuk unauthorized access.

### Priority

Tinggi.

---

## Phase 2 — Procurement and Financial Flow

## 1. Purchase Order

### Problem

Sudah ada supplier dan payable, tetapi belum ada proses pembelian formal. Ini membuat alur stok masuk dan hutang supplier belum utuh.

### Goals

- membuat proses pemesanan ke supplier,
- menyediakan draft pembelian,
- mengontrol status pembelian sebelum barang datang.

### Key Features

- buat PO,
- pilih supplier,
- tambah item pembelian,
- qty dan harga beli,
- status draft / ordered / partial / completed / cancelled,
- catatan pembelian,
- nomor dokumen otomatis.

### UI/UX Scope

- list PO,
- create/edit PO,
- detail PO,
- status badge,
- printable purchase document.

### Business Rules

- PO draft belum mempengaruhi stok,
- stok bertambah hanya saat barang diterima,
- PO bisa diterima sebagian.

### Priority

Sangat tinggi.

---

## 2. Goods Receiving

### Problem

Barang datang dari supplier harus dicatat secara formal agar stok masuk akurat dan sesuai dengan PO.

### Goals

- mencatat penerimaan barang,
- menghubungkan barang diterima ke PO,
- menghasilkan mutasi stok masuk,
- menghitung sisa item yang belum datang.

### Key Features

- receive full / partial,
- qty diterima per item,
- catatan selisih,
- upload dokumen pendukung,
- update status PO,
- update stok.

### Business Rules

- receiving tidak boleh melebihi qty PO,
- receiving sebagian harus menyisakan qty outstanding,
- stok masuk harus menghasilkan histori mutasi.

### Priority

Sangat tinggi.

---

## 3. Supplier Return

### Problem

Barang rusak atau tidak sesuai dari supplier harus bisa diretur agar stok dan hutang supplier akurat.

### Goals

- retur barang ke supplier,
- koreksi stok,
- koreksi kewajiban hutang bila perlu.

### Key Features

- pilih supplier / PO,
- pilih item retur,
- qty retur,
- alasan retur,
- status retur,
- pengaruh ke payable.

### Priority

Tinggi.

---

## 4. Payable Lifecycle Improvement

### Problem

Hutang supplier sudah ada, tetapi masih dapat diperdalam menjadi lifecycle yang lebih operasional.

### Goals

- membuat hutang supplier lebih informatif dan terhubung ke pembelian,
- mempermudah pelacakan status pembayaran.

### Key Features

- hutang otomatis dari PO/receiving,
- payment history,
- due reminder,
- partial payment,
- settlement tracking,
- supplier statement.

### Additional Reports

- aging hutang,
- hutang per supplier,
- jatuh tempo dalam 7 hari,
- hutang overdue.

### Priority

Tinggi.

---

## 5. Receivable Lifecycle Improvement

### Problem

Piutang sudah ada, tetapi perlu diperdalam agar lebih berguna untuk operasional dan penagihan.

### Goals

- membuat lifecycle piutang lebih lengkap,
- mempermudah follow-up pembayaran.

### Key Features

- payment history yang lebih jelas,
- due reminder,
- aging piutang,
- customer statement,
- partial payment tracking,
- note penagihan.

### Additional Reports

- aging piutang,
- customer dengan piutang tertinggi,
- piutang overdue,
- collection rate.

### Priority

Tinggi.

---

## 6. Reminder and Aging Engine

### Problem

Owner/admin perlu warning otomatis untuk kewajiban dan tagihan yang mendekati jatuh tempo.

### Goals

- memberi reminder yang actionable,
- mengurangi piutang macet,
- mengontrol hutang yang akan jatuh tempo.

### Key Features

- reminder dashboard,
- filter due soon / overdue,
- notifikasi internal,
- opsi kirim WhatsApp/email di fase lanjutan,
- aging bucket 0-30 / 31-60 / 61-90 / 90+ hari.

### Priority

Tinggi.

---

## Phase 3 — Commercial and Growth Features

## 1. Promo & Pricing Engine

### Problem

Toko sering butuh promo fleksibel yang tidak cukup hanya dengan diskon level transaksi.

### Goals

- meningkatkan fleksibilitas penjualan,
- mendukung promo retail nyata,
- mendukung strategi harga per segmen.

### Key Features

- diskon per item,
- diskon per kategori,
- bundling,
- buy X get Y,
- scheduled promo,
- tier pricing / grosir,
- harga member / reseller.

### UI/UX Scope

- rule builder promo,
- preview promo impact,
- badge promo pada produk,
- audit promo aktif.

### Priority

Tinggi.

---

## 2. Customer Loyalty

### Problem

Setelah transaksi dan pelanggan sudah ada, langkah berikutnya adalah meningkatkan repeat purchase.

### Goals

- menjaga retensi pelanggan,
- memberi insentif belanja ulang,
- meningkatkan customer lifetime value.

### Key Features

- membership level,
- poin reward,
- redeem poin,
- histori reward,
- voucher customer,
- harga khusus member.

### Priority

Tinggi.

---

## 3. Customer Segmentation

### Problem

Semua customer tidak selalu diperlakukan sama. Segmentasi membantu promosi dan kontrol penjualan kredit.

### Goals

- membedakan pelanggan retail, grosir, loyal, dan kredit,
- mendukung strategi harga dan follow-up.

### Key Features

- segment tag,
- auto segment by spending,
- segment by purchase frequency,
- segment by piutang behavior.

### Priority

Menengah.

---

## 4. Campaign and Reminder Automation

### Problem

Komunikasi ke pelanggan dan penagihan masih manual.

### Goals

- mengotomatisasi komunikasi dasar,
- meningkatkan penagihan dan retensi.

### Key Features

- reminder piutang,
- promo broadcast,
- invoice sharing,
- due date reminder,
- repeat order reminder.

### Priority

Menengah.

---

## 5. Advanced Sales Insights

### Problem

Laporan penjualan dan profit sudah ada, tetapi owner biasanya butuh analisa yang lebih operasional.

### Goals

- membantu owner membuat keputusan cepat,
- menemukan produk unggulan dan masalah bisnis.

### Key Features

- top selling products,
- low performing products,
- margin per produk / kategori,
- sales by hour/day,
- cashier performance,
- repeat customer metrics,
- stock coverage analysis.

### Priority

Tinggi.

---

## Phase 4 — Control, Scale, and Integration

## 1. Multi Outlet / Multi Warehouse

### Problem

Jika bisnis berkembang, sistem satu toko akan cepat menjadi bottleneck.

### Goals

- mendukung banyak outlet,
- mendukung stok per lokasi,
- mendukung reporting lintas cabang.

### Key Features

- outlet master,
- warehouse/location stock,
- stock transfer antar lokasi,
- role per outlet,
- laporan per outlet.

### Priority

Strategis.

---

## 2. Approval Workflow

### Problem

Beberapa tindakan sensitif sebaiknya tidak bisa langsung dilakukan oleh semua user.

### Goals

- meningkatkan kontrol internal,
- mencegah fraud atau human error.

### Key Features

- approval stock adjustment,
- approval void transaction,
- approval retur tertentu,
- approval perubahan harga,
- approval penghapusan data sensitif.

### Priority

Strategis.

---

## 3. Tax and Pricing Rules

### Problem

Beberapa bisnis membutuhkan PPN, service charge, atau aturan harga spesifik.

### Goals

- mendukung model bisnis yang lebih luas,
- menjaga akurasi invoice dan laporan.

### Key Features

- tax setting,
- inclusive/exclusive tax,
- service charge,
- custom rounding rules,
- tax report summary.

### Priority

Menengah.

---

## 4. Import / Export Toolkit

### Problem

Onboarding data manual akan lambat jika user punya banyak produk/customer/supplier.

### Goals

- mempercepat adopsi sistem,
- mempermudah maintenance data.

### Key Features

- import produk,
- import customer,
- import supplier,
- export laporan,
- export master data,
- template file standard.

### Priority

Menengah.

---

## 5. Backup / Restore

### Problem

Owner perlu rasa aman terhadap data bisnis.

### Goals

- menurunkan risiko kehilangan data,
- memudahkan recovery.

### Key Features

- backup manual,
- backup terjadwal,
- restore procedure,
- backup metadata log.

### Priority

Menengah.

---

## 6. External Integrations

### Problem

Bisnis berkembang sering membutuhkan koneksi ke tool eksternal.

### Goals

- membuka peluang ekspansi ekosistem,
- mengurangi pekerjaan manual.

### Key Features

- WhatsApp integration,
- email reminder,
- marketplace sync,
- accounting export,
- public/internal API.

### Priority

Strategis.

---

## Cross-Module Improvements

Beberapa improvement bersifat lintas modul dan sebaiknya menjadi standar implementasi.

### 1. Consistent RBAC

- action button harus mengikuti permission,
- route backend dan UI frontend harus konsisten,
- role super-admin harus jelas perilakunya.

### 2. Better Auditability

- semua aksi sensitif harus punya jejak,
- histori perubahan penting harus mudah dibaca.

### 3. Better Data Status Model

- hindari hanya mengandalkan flag sederhana,
- gunakan status lifecycle yang jelas untuk transaksi, PO, piutang, hutang, retur, dan shift.

### 4. Better Notification System

- reminder berbasis role,
- notifikasi stok,
- due soon receivable/payable,
- request approval.

### 5. Better Dashboard Personalization

- dashboard owner berbeda dengan kasir,
- tampilkan widget sesuai role.

---

## Recommended Execution Order

### Immediate Priority

1. Stock Opname
2. Stock Mutation
3. Sales Return
4. Cashier Shift & Cash Closing
5. Audit Log
6. RBAC consistency cleanup

### Near-Term Priority

1. Purchase Order
2. Goods Receiving
3. Payable lifecycle improvement
4. Receivable lifecycle improvement
5. Supplier Return
6. Reminder and aging engine

### Growth Priority

1. Promo & Pricing Engine
2. Customer Loyalty
3. Advanced Sales Insights
4. Customer Segmentation
5. Campaign automation

### Strategic Priority

1. Multi outlet / warehouse
2. Approval workflow
3. Tax rules
4. Import/export
5. Backup/restore
6. External integrations

---

## Suggested Delivery Format Per Module

Untuk implementasi ke depan, setiap module sebaiknya dipecah ke dokumen teknis lanjutan dengan format:

- business objective,
- user roles,
- flow utama,
- UI scope,
- database impact,
- permission impact,
- audit log impact,
- reporting impact,
- edge cases,
- rollout strategy.

---

## Final Recommendation

Jika tujuan utama adalah membuat sistem ini benar-benar siap untuk toko yang aktif dipakai setiap hari, maka urutan paling masuk akal adalah:

1. **stabilkan operasional stok dan kasir**,
2. **lengkapi alur pembelian dan hutang/piutang**,
3. **tambahkan engine promosi dan loyalty**,
4. **baru dorong ke fitur scale dan integrasi**.

Dengan pendekatan ini, setiap phase akan memberi value nyata ke user tanpa membuat sistem terlalu kompleks terlalu cepat.
