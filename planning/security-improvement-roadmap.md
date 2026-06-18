# Security Improvement Roadmap

## Objective

Dokumen ini merangkum roadmap penguatan keamanan sistem Point of Sale sebelum pengembangan fitur dilanjutkan lebih jauh. Fokus utamanya bukan menambah fitur bisnis baru, tetapi memastikan fondasi keamanan aplikasi cukup kuat untuk dipakai pada operasional toko nyata, terutama untuk:

- autentikasi dan registrasi,
- kontrol akses dashboard,
- keamanan session dan browser,
- perlindungan data sensitif,
- keamanan payment gateway dan webhook,
- audit aktivitas keamanan,
- dan kesiapan deployment production.

---

## Current Security Assessment

### Existing Strengths

Saat ini sistem sudah memiliki beberapa fondasi keamanan yang baik:

- login memakai request validation khusus,
- login sudah memiliki rate limit dasar,
- session diregenerate setelah login,
- logout meng-invalidate session dan regenerate CSRF token,
- route dashboard mayoritas sudah diproteksi middleware `auth` dan `permission:*`,
- custom unauthorized handling untuk web dan JSON sudah ada,
- verifikasi signature/callback token pada webhook Midtrans dan Xendit sudah ada,
- audit log modul bisnis sudah mulai dibangun.

### Main Risks

Namun ada beberapa risiko yang masih perlu diprioritaskan:

- registrasi publik masih terbuka dan langsung memberi role operasional,
- email verification belum benar-benar di-enforce ke seluruh dashboard,
- secret payment gateway masih berisiko tersimpan terlalu terbuka,
- webhook payload masih berpotensi membocorkan data lewat logging,
- route auth publik selain login belum memiliki throttle yang memadai,
- belum ada MFA / step-up auth untuk aksi sensitif,
- belum ada security headers dan hardening browser policy yang jelas,
- belum ada security audit trail yang cukup khusus untuk event autentikasi dan privilege change.

---

## Roadmap Principles

Roadmap keamanan ini disusun berdasarkan prinsip berikut:

1. **Reduce Attack Surface First**  
   Tutup entry point yang tidak perlu atau terlalu permisif sebelum menambah kontrol yang kompleks.

2. **Protect Privileged Operations**  
   Semakin sensitif aksinya, semakin kuat kontrol autentikasi, logging, dan approval-nya.

3. **Secure by Default**  
   Konfigurasi default untuk production harus aman, bukan hanya bisa jalan.

4. **Operationally Maintainable**  
   Kontrol keamanan harus realistis untuk dikelola tim developer dan owner sistem.

5. **Defense in Depth**  
   Jangan bergantung pada satu lapisan saja; auth, session, permission, webhook, logging, dan deployment harus saling memperkuat.

---

## Security Domains

Roadmap ini dibagi ke dalam domain berikut:

- Authentication & Registration
- Authorization & Privilege Control
- Session, CSRF, and Browser Hardening
- Payment & Webhook Security
- Sensitive Data & Secret Management
- Audit, Monitoring, and Incident Visibility
- Infrastructure & Production Configuration

---

## Phase Overview

### Phase 1 — Quick Wins & Exposure Reduction

Fokus pada penurunan risiko tertinggi yang dampaknya besar dan implementasinya relatif cepat.

Module utama:

- Registration policy hardening
- Email verification enforcement
- Auth route throttling
- Webhook logging cleanup
- Production env security baseline
- Security headers minimum

### Phase 2 — Core Security Hardening

Fokus pada penguatan kontrol inti untuk data sensitif, payment surface, dan operasi admin.

Module utama:

- Secret encryption / secret storage strategy
- CAPTCHA / bot protection
- Step-up auth untuk aksi sensitif
- Session policy hardening
- Security audit log
- Permission and privileged action review

### Phase 3 — Advanced Control & Resilience

Fokus pada keamanan tingkat lanjut untuk akun privileged, observability, dan kesiapan scale.

Module utama:

- MFA / 2FA
- Active session management
- Webhook replay protection & idempotency hardening
- CSP ketat
- Security monitoring & alerting
- Security review workflow

---

## Phase 1 — Quick Wins & Exposure Reduction

## 1. Registration Policy Hardening

### Problem

Jika aplikasi ini dipakai sebagai sistem kasir internal toko, registrasi publik yang langsung memberi akses operasional adalah attack surface yang terlalu besar.

### Goals

- membatasi siapa yang boleh membuat akun,
- mencegah akun anonim langsung masuk ke dashboard,
- mengurangi risiko abuse dari internet publik.

### Recommended Direction

- nonaktifkan public self-registration untuk deployment internal,
- atau ubah menjadi invite-only,
- atau ubah menjadi registration dengan status pending approval admin.

### Key Features

- feature flag untuk enable/disable registration,
- opsi invitation-based onboarding,
- status user `pending`, `active`, `disabled`,
- approval admin sebelum akun aktif.

### Security Impact

- menurunkan attack surface paling awal,
- mencegah privilege abuse dari akun baru,
- membatasi abuse bot pada endpoint register.

### Priority

Sangat tinggi.

---

## 2. Email Verification Enforcement

### Problem

Route verifikasi email sudah tersedia, tetapi akun yang baru terdaftar belum tentu benar-benar diwajibkan verify sebelum mengakses area penting.

### Goals

- memastikan identitas email tervalidasi sebelum masuk ke dashboard,
- mengurangi akun disposable / throwaway,
- memperkuat integritas user management.

### Key Features

- implement `MustVerifyEmail`,
- tambahkan middleware `verified` untuk area dashboard yang membutuhkan,
- tampilkan status verifikasi di profil user/admin,
- resend verification dengan throttle.

### Business Rules

- akun belum verified tidak boleh mengakses dashboard inti,
- akun verified tetap tunduk pada role dan permission,
- action sensitif dapat mewajibkan verified account tanpa pengecualian.

### Priority

Sangat tinggi.

---

## 3. Auth Endpoint Throttling Expansion

### Problem

Login sudah punya rate limit, tetapi register, forgot-password, resend verification, dan route sensitif auth lain masih dapat disalahgunakan.

### Goals

- mengurangi brute force dan abuse automasi,
- mencegah spam email reset/verifikasi,
- mengurangi enumeration surface.

### Key Features

- throttle untuk `register`,
- throttle untuk `forgot-password`,
- throttle untuk resend verification,
- rate limit lebih ketat untuk aksi auth publik.

### Suggested Policy

- login: tetap low tolerance,
- register: lebih agresif per IP,
- forgot-password: per email + IP,
- resend verification: limit burst + cooldown.

### Priority

Sangat tinggi.

---

## 4. Webhook Logging Cleanup

### Problem

Logging payload webhook mentah dapat membocorkan data transaksi, payment metadata, dan potentially secret-adjacent information ke log file.

### Goals

- mengurangi kebocoran data di log,
- tetap mempertahankan observability minimum untuk troubleshooting,
- memastikan log aman untuk diakses tim operasional.

### Key Features

- log whitelist fields saja,
- redact field sensitif,
- jangan log signature/token secara mentah,
- gunakan correlation id / invoice id / payment reference untuk tracing.

### Suggested Logging Scope

- provider,
- invoice / external id,
- payment id / transaction id,
- normalized status,
- timestamp,
- verification result,
- error category.

### Priority

Sangat tinggi.

---

## 5. Production Environment Baseline

### Problem

Banyak insiden keamanan terjadi bukan karena bug aplikasi, tetapi karena environment production tidak di-hardening dengan benar.

### Goals

- memastikan konfigurasi deployment aman secara default,
- menutup celah yang berasal dari debug, cookie, dan URL publik.

### Required Baseline

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` valid dan HTTPS
- `SESSION_SECURE_COOKIE=true`
- session driver dan cache driver yang sesuai production
- queue/log storage yang aman

### Security Checklist

- domain publik valid,
- TLS aktif,
- storage tidak expose file sensitif,
- backup `.env` dan key management aman,
- log file tidak world-readable.

### Priority

Sangat tinggi.

---

## 6. Security Headers Minimum

### Problem

Aplikasi belum terlihat memiliki browser hardening policy yang eksplisit.

### Goals

- mengurangi risiko clickjacking, mime sniffing, dan kebocoran referrer,
- menyiapkan fondasi untuk CSP yang lebih ketat.

### Key Features

- `X-Content-Type-Options: nosniff`
- `Referrer-Policy`
- `X-Frame-Options` atau `frame-ancestors`
- baseline `Permissions-Policy`

### Priority

Tinggi.

---

## Phase 2 — Core Security Hardening

## 1. Sensitive Secret Protection

### Problem

Key payment gateway dan callback token adalah aset sensitif. Jika bocor, attacker bisa menyalahgunakan payment flow atau memalsukan callback.

### Goals

- melindungi secret at-rest,
- membatasi siapa yang bisa melihat atau mengubah secret,
- mendukung rotasi secret.

### Key Features

- enkripsi field sensitif di database,
- atau pindahkan secret kritikal ke env / secret manager,
- masking secret di UI admin,
- audit trail perubahan secret,
- rotasi credential terencana.

### Protected Data

- Midtrans server key,
- Midtrans client key bila diperlakukan sensitif,
- Xendit secret key,
- Xendit callback token,
- credential integrasi lain di masa depan.

### Priority

Sangat tinggi.

---

## 2. CAPTCHA / Bot Protection

### Problem

Route auth publik tanpa anti-bot akan mudah di-abuse walau sudah ada throttle dasar.

### Goals

- menurunkan abuse otomatis,
- melindungi register/login/reset flows dari bot,
- menjaga resource server dan email provider.

### Recommended Scope

- login,
- register,
- forgot password,
- resend verification bila tetap publik.

### Priority

Tinggi.

---

## 3. Step-Up Authentication for Sensitive Actions

### Problem

Tidak semua aksi sensitif cukup diamankan hanya dengan session login biasa.

### Goals

- meminta verifikasi tambahan untuk operasi berisiko tinggi,
- mengurangi dampak session hijack atau shoulder surfing pada user admin.

### Candidate Actions

- mengubah payment gateway settings,
- mengubah role / permission user,
- force close cashier shift,
- confirm payment manual,
- reset credential integration,
- delete data penting.

### Possible Controls

- password confirmation ulang,
- OTP / MFA challenge,
- recent-auth freshness window,
- approval second-person untuk aksi tertentu di phase lanjutan.

### Priority

Tinggi.

---

## 4. Session Policy Hardening

### Problem

Session aman tidak cukup hanya dengan regenerate saat login. Perlu kebijakan lifecycle yang jelas.

### Goals

- mengurangi risiko session hijacking,
- membatasi durasi akses jika perangkat ditinggalkan,
- memperjelas perilaku remember-me.

### Key Features

- idle timeout,
- absolute session lifetime,
- revoke session after password change,
- option logout all devices,
- review remember-me policy.

### Priority

Tinggi.

---

## 5. Security Audit Logging

### Problem

Audit log bisnis sudah ada, tetapi event keamanan belum tentu tercatat secara memadai.

### Goals

- memudahkan investigasi insiden,
- memberi jejak yang cukup untuk perubahan privilege dan autentikasi,
- membantu owner/admin mendeteksi penyalahgunaan internal.

### Events to Track

- login sukses,
- login gagal berulang,
- logout,
- reset password request,
- password change,
- role assignment change,
- permission-sensitive setting change,
- payment config change,
- webhook verification failure spike.

### Suggested Metadata

- user id,
- actor role,
- IP address,
- user agent ringkas,
- target resource,
- before/after untuk privilege changes,
- event severity.

### Priority

Tinggi.

---

## 6. Privileged Permission Review

### Problem

Walau RBAC sudah membaik, permission sensitif masih perlu audit berkala agar tidak terlalu lebar.

### Goals

- memastikan least privilege,
- membedakan akses baca vs aksi mutasi,
- mengurangi over-privileged operational accounts.

### Review Targets

- payment settings,
- users/roles/permissions,
- confirm payment,
- stock adjustment/finalize,
- sales return complete,
- cashier force close,
- supplier/payable/receivable payment actions.

### Priority

Tinggi.

---

## Phase 3 — Advanced Control & Resilience

## 1. Multi-Factor Authentication

### Problem

Akun owner/admin adalah target paling menarik. Password saja tidak cukup untuk jangka panjang.

### Goals

- menambah lapisan keamanan untuk akun privileged,
- mengurangi dampak credential compromise.

### Recommended Scope

- wajib untuk owner/admin,
- opsional untuk kasir pada fase awal,
- enforced untuk user yang bisa mengubah konfigurasi sensitif.

### Priority

Menengah ke tinggi.

---

## 2. Active Session Management

### Problem

User privileged perlu visibilitas atas sesi aktif dan kemampuan memutus session yang tidak dikenali.

### Goals

- memberi kontrol atas perangkat yang login,
- membantu respon cepat terhadap dugaan account compromise.

### Key Features

- daftar sesi aktif,
- info device / browser / last activity,
- logout session lain,
- revoke all sessions.

### Priority

Menengah.

---

## 3. Webhook Replay Protection & Idempotency Hardening

### Problem

Verification signature/token saja belum cukup bila webhook valid yang sama bisa diproses berulang.

### Goals

- mencegah replay,
- memastikan update payment idempotent,
- mengurangi inconsistency pada transaksi.

### Key Features

- event fingerprint / webhook event log,
- deduplication key,
- status transition guard,
- only-allow valid state transitions.

### Priority

Menengah.

---

## 4. Content Security Policy

### Problem

CSP ketat belum terlihat, padahal ini penting untuk mitigasi XSS dan asset injection.

### Goals

- membatasi sumber script/style/frame,
- mengurangi dampak XSS jika ada bug lain.

### Implementation Notes

- mulai dari report-only,
- petakan asset internal dan third-party terlebih dahulu,
- kencangkan bertahap hingga enforce mode.

### Priority

Menengah.

---

## 5. Monitoring & Alerting

### Problem

Tanpa monitoring, banyak gejala serangan atau misconfiguration tidak cepat terlihat.

### Goals

- mendeteksi abuse lebih cepat,
- memberi sinyal dini untuk credential atau webhook abuse.

### Alert Candidates

- login gagal beruntun,
- reset password burst,
- invalid webhook signature spike,
- perubahan payment config,
- role/permission change,
- akses admin dari IP tidak biasa.

### Priority

Menengah.

---

## 6. Security Review Workflow

### Problem

Keamanan bukan pekerjaan sekali selesai. Perlu proses rutin.

### Goals

- membuat review keamanan jadi bagian dari lifecycle development,
- mencegah regression saat fitur baru ditambahkan.

### Suggested Workflow

- checklist security untuk PR sensitif,
- dependency audit berkala,
- permission audit bulanan,
- secret rotation berkala,
- review konfigurasi production,
- incident postmortem untuk kasus security.

### Priority

Menengah.

---

## Module-Specific Recommendations

## Authentication & Registration

### Problem

Flow auth publik adalah pintu masuk utama attacker.

### Key Improvements

- disable public registration bila sistem internal,
- enforce verified email,
- throttle register/reset/resend verification,
- add CAPTCHA,
- add MFA for privileged users,
- unify auth error messaging agar tidak bocorkan informasi user.

### Priority

Sangat tinggi.

---

## Authorization & Privilege Control

### Problem

Akun berprivilege tinggi harus punya kontrol lebih kuat daripada sekadar login.

### Key Improvements

- review least privilege,
- step-up auth untuk aksi sensitif,
- audit perubahan role/permission,
- review super-admin usage policy,
- periodic privilege review.

### Priority

Sangat tinggi.

---

## Payment & Webhook Security

### Problem

Payment flow menyentuh uang dan status transaksi, jadi salah satu area paling sensitif.

### Key Improvements

- encrypt secrets,
- redact logs,
- replay protection,
- strict payload validation,
- stronger audit trail,
- alerting untuk invalid callback.

### Priority

Sangat tinggi.

---

## Session & Browser Hardening

### Problem

Session policy lemah dan browser policy longgar akan memperbesar dampak bug lain.

### Key Improvements

- secure cookies,
- HTTPS-only deployment,
- idle timeout,
- security headers,
- CSP bertahap,
- session revocation controls.

### Priority

Tinggi.

---

## Audit & Monitoring

### Problem

Tanpa visibility, penyalahgunaan privilege atau abuse auth sulit didiagnosis.

### Key Improvements

- security event log,
- alerting baseline,
- review dashboard untuk aktivitas privileged,
- suspicious activity indicators.

### Priority

Tinggi.

---

## Suggested Execution Order

### Sprint 1

- disable / restrict public registration
- enforce verified email
- add throttle for register / forgot password
- clean webhook logging
- validate production env baseline

### Sprint 2

- add minimum security headers
- encrypt payment secrets
- review privileged permissions
- define session timeout policy
- add security audit events

### Sprint 3

- add CAPTCHA for public auth flows
- add step-up auth for sensitive actions
- add webhook replay/idempotency hardening
- add alerting baseline

### Sprint 4+

- add MFA
- add active session management
- add CSP report-only then enforce
- institutionalize security review workflow

---

## Top 10 Recommended Actions

Jika hanya ingin menjalankan improvement paling berdampak lebih dulu, urutannya saya sarankan:

1. tutup atau batasi public registration
2. wajibkan verified email untuk dashboard
3. tambahkan throttle pada semua auth public routes
4. hentikan raw webhook payload logging
5. amankan production env baseline
6. encrypt / protect payment gateway secrets
7. tambahkan security headers minimum
8. tambah step-up auth untuk aksi sensitif
9. buat security audit log untuk auth dan privilege changes
10. tambahkan CAPTCHA / anti-bot protection

---

## Acceptance Criteria

Roadmap ini dianggap berhasil jika dalam implementasi berikutnya:

- akun baru tidak lagi otomatis mendapat akses operasional tanpa kontrol,
- dashboard inti hanya bisa diakses akun yang tervalidasi,
- secret payment tidak lagi tersimpan atau terekspos secara lemah,
- event keamanan penting tercatat dan dapat diaudit,
- auth public routes jauh lebih sulit di-abuse,
- privileged actions memiliki kontrol tambahan,
- dan deployment production memiliki baseline keamanan yang jelas dan terdokumentasi.

---

## Notes

- Dokumen ini fokus pada hardening aplikasi dan operasional deployment, bukan compliance formal seperti ISO, PCI DSS, atau SOC2.
- Jika sistem nanti diarahkan menjadi SaaS multi-tenant publik, maka roadmap ini perlu diperluas dengan tenant isolation, abuse prevention yang lebih agresif, dan governance secret yang lebih ketat.
