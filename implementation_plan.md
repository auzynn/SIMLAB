# Rencana Koreksi Bug & Inkonsistensi Dokumen Proyek

Dokumen ini menjelaskan hasil review dan rencana perbaikan terhadap beberapa bug logis serta inkonsistensi yang ditemukan pada dokumen-dokumen proyek (`1_PRD.md`, `2_SRS.md`, `3_SDD.md`, `4_TASK_BREAKDOWN.md`, `.clinerules/agent.md`, dan `.agents/skills/ponytail/SKILL.md`).

## User Review Required

> [!IMPORTANT]
> Beberapa perubahan kunci pada desain database (`3_SDD.md`) diusulkan untuk mendukung aturan bisnis di SRS yang sebelumnya tidak didukung oleh skema database saat ini (seperti relasi "mahasiswa bimbingan" untuk Dosen).

## Open Questions

> [!NOTE]
> Tidak ada pertanyaan terbuka saat ini, semua perbaikan bersifat klarifikasi teknis dan sinkronisasi antar dokumen agar konsisten secara logis.

## Proposed Changes

Di bawah ini adalah rincian file dokumen yang akan direvisi untuk memperbaiki bug logis dan menjaga konsistensi:

---

### Modul Dokumen Desain & Aturan Bisnis

#### [MODIFY] [1_PRD.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/1_PRD.md)
- **Koreksi**: Menyesuaikan alur "Tambah Dosen" di Bagian 2.5 dan 3.8. Menambahkan keterangan bahwa selain registrasi otomatis via Google OAuth, Admin juga dapat membuat/mendaftarkan akun Dosen/Supervisor secara manual melalui endpoint Kelola User demi kelancaran operasional (sehingga Admin bisa "tambah" data dosen sebelum dosen yang bersangkutan melakukan login Google pertama kali).

#### [MODIFY] [2_SRS.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/2_SRS.md)
- **Koreksi**:
  - Pada **UC-02 (Peminjaman Ruangan Lab)** dan **UC-02a (Kelas Lab)**, menambahkan validasi bahwa pengajuan peminjaman/pembukaan kelas hanya diperbolehkan jika ruangan berstatus `tersedia`. Ruangan yang berstatus `perbaikan` atau `dipakai` secara permanen tidak boleh dipinjam.
  - Pada deskripsi aktor dan relasi, memperjelas bahwa hubungan "mahasiswa bimbingan" didasarkan pada foreign key `dosen_pembimbing_id` di tabel mahasiswa.

#### [MODIFY] [3_SDD.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/3_SDD.md)
- **Koreksi**:
  - **Bagian 3.3 (`mahasiswa`)**: Menambahkan kolom `dosen_pembimbing_id` (bigint, nullable, FK -> `dosen.id`, on delete set null) untuk menampung relasi "mahasiswa bimbingan" yang diwajibkan oleh aturan hak akses di SRS.
  - **Bagian 3.4, 3.5, 3.7 (`ruangan`, `peminjaman_ruangan`, `kelas_lab`)**: Menambahkan aturan validasi yang memeriksa `ruangan.status` harus `'tersedia'` sebelum booking/jadwal disetujui.
  - **Bagian 3.11 (`perpanjangan_peminjaman`)**: Menambahkan detail konsekuensi logis: ketika perpanjangan disetujui (`status = 'disetujui'`), sistem harus memperbarui kolom `tanggal_kembali_rencana` pada tabel induk `peminjaman_perangkat` menjadi `tanggal_kembali_baru`.
  - **Seluruh Skema Database (Bagian 3)**: Menambahkan aturan referential integrity / delete cascade secara eksplisit pada foreign key untuk menghindari error SQL database saat data dihapus (misal: `on delete cascade` saat user/dosen/mahasiswa dihapus).
  - **Bagian 5.2 (User & Role API)**: Menambahkan endpoint `POST /api/users` (Admin only) untuk membuat user baru secara manual (Dosen/Supervisor) guna mensinkronkan hak Admin "tambah dosen/user".
  - **Bagian 5.8 (Perpanjangan API)**: Menambahkan catatan logis efek perubahan status perpanjangan pada tabel peminjaman perangkat.
  - **Bagian 4 (ERD)**: Mengupdate diagram relasi untuk mencantumkan relasi `dosen (1) ─── (M) mahasiswa` (via `dosen_pembimbing_id`).

#### [MODIFY] [4_TASK_BREAKDOWN.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/4_TASK_BREAKDOWN.md)
- **Koreksi**:
  - **T1.3 & T1.4**: Mengupdate deskripsi migrasi/model mahasiswa untuk menyertakan relasi `dosen_pembimbing_id`.
  - **T1.11**: Menambahkan endpoint `POST /api/users` ke daftar task implementasi backend & frontend.
  - **T3.9 & T3.10**: Menambahkan validasi status ruangan harus `'tersedia'` ke dalam tugas validasi bentrok.
  - **T4.9**: Menambahkan tugas pembaruan `tanggal_kembali_rencana` pada tabel induk ketika perpanjangan disetujui.

#### [MODIFY] [agent.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/.clinerules/agent.md)
- **Koreksi**: Menghapus leading slash pada path referensi skill ponytail di baris 119 (`/.agents/skills/ponytail/SKILL.md` menjadi `.agents/skills/ponytail/SKILL.md`) agar konsisten dengan format path relatif Windows/Linux tanpa menyebabkan kebingungan resolusi path.

---

## Verification Plan

### Manual Verification
- Melakukan pemeriksaan ulang dokumen-dokumen yang telah dimodifikasi untuk memastikan semua referensi silang (cross-reference) nama tabel, nama endpoint, dan aturan bisnis sinkron 100%.
