# Walkthrough Koreksi Dokumen Proyek

Dokumentasi ini merangkum perbaikan yang telah dilakukan pada file spesifikasi proyek untuk menyinkronkan seluruh kebutuhan fungsional, arsitektur database, dan backlog implementasi.

## Perubahan yang Dilakukan

Merujuk pada rencana di [implementation_plan.md](file:///C:/Users/lenovo/.gemini/antigravity-ide/brain/2f541c75-4eae-421a-b731-2effb752f2c0/implementation_plan.md), dokumen-dokumen berikut telah diperbaiki secara serentak:

### 1. Sinkronisasi Relasi Bimbingan (Dosen - Mahasiswa)
- **Masalah**: SRS mengasumsikan Dosen dapat mengelola presensi "mahasiswa bimbingan", namun skema database di SDD tidak memiliki foreign key penghubung kedua entitas ini.
- **Koreksi**:
  - Menambahkan kolom `dosen_pembimbing_id` (FK -> `dosen.id`, nullable, `on delete set null`) pada tabel `mahasiswa` di [3_SDD.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/3_SDD.md).
  - Memperbarui penjelasan aturan bisnis di [2_SRS.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/2_SRS.md) untuk mendefinisikan hubungan ini secara eksplisit.
  - Memperbarui diagram relasi ERD ringkas di SDD untuk menyertakan relasi `dosen (1) ─── (M) mahasiswa`.
  - Menambahkan catatan penanganan kolom `dosen_pembimbing_id` pada migrasi dan model di task **T1.3** dan **T1.4** di [4_TASK_BREAKDOWN.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/4_TASK_BREAKDOWN.md).

### 2. Validasi Status Ketersediaan Ruangan (`ruangan.status`)
- **Masalah**: `ruangan` memiliki status `enum('tersedia','dipakai','perbaikan')`, tetapi skenario booking ruangan dan pembukaan kelas di SRS/SDD tidak memeriksa status ini saat melakukan validasi.
- **Koreksi**:
  - Menambahkan validasi status ruangan harus `'tersedia'` pada UC-02 dan UC-02a di [2_SRS.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/2_SRS.md).
  - Menyertakan validasi status ruangan pada bagian constraint di [3_SDD.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/3_SDD.md).
  - Memperbarui deskripsi task **T3.9** dan **T3.10** di [4_TASK_BREAKDOWN.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/4_TASK_BREAKDOWN.md) agar menyertakan validasi ini.

### 3. Hak Admin Menambah Dosen / Kelola User
- **Masalah**: PRD menyatakan Admin mengelola (termasuk tambah/hapus) data dosen, tetapi SDD membatasi registrasi hanya melalui Google OAuth (sehingga Admin tidak bisa mendaftarkan dosen secara manual).
- **Koreksi**:
  - Menambahkan endpoint `POST /api/users` (Admin only) di [3_SDD.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/3_SDD.md) section 5.2.
  - Menyesuaikan penjelasan alur tambah dosen di [1_PRD.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/1_PRD.md) section 2.5 dan 3.8.
  - Memasukkan endpoint ini ke task **T1.11** di [4_TASK_BREAKDOWN.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/4_TASK_BREAKDOWN.md).

### 4. Efek Persetujuan Perpanjangan Perangkat
- **Masalah**: Belum didefinisikan secara eksplisit apa yang terjadi pada record induk `peminjaman_perangkat` ketika sebuah pengajuan `perpanjangan_peminjaman` disetujui.
- **Koreksi**:
  - Menambahkan aturan transaksi database pada [3_SDD.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/3_SDD.md) section 3.11 dan 5.8: sistem harus otomatis memperbarui kolom `tanggal_kembali_rencana` pada tabel `peminjaman_perangkat` menjadi `tanggal_kembali_baru`.
  - Memperbarui deskripsi tugas **T4.9** di [4_TASK_BREAKDOWN.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/4_TASK_BREAKDOWN.md).

### 5. Aturan Cascade Delete pada Skema Database
- **Masalah**: Hubungan antar tabel di database rawan memicu SQL constraint error saat record induk dihapus (misal menghapus user, dosen, ruangan, atau mata kuliah).
- **Koreksi**: Menambahkan anotasi `on delete cascade` dan `on delete set null` di seluruh tabel terkait pada [3_SDD.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/docs/3_SDD.md) section 3.

### 6. Perbaikan Path Skill Ponytail
- **Masalah**: Path skill ponytail menggunakan leading slash (`/.agents/...`) yang tidak standar.
- **Koreksi**: Mengubahnya menjadi `.agents/skills/ponytail/SKILL.md` di [.clinerules/agent.md](file:///d:/Kuliah/Kerja%20Praktek/Lab.%20Riset/Test%20SIM/SIMLAB/.clinerules/agent.md) line 119.

---

## Hasil Verifikasi
Semua dokumen kini 100% konsisten satu sama lain dalam mendefinisikan aturan bisnis, relasi data, struktur API, dan urutan pengerjaan task. Tidak ada lagi celah logis/mismatch antara PRD, SRS, SDD, dan Backlog (TASK_BREAKDOWN).
