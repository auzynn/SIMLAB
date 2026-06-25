<template>
  <div>
    <JumbotronSmall />

    <div class="main-container flex-h between">
      <div class="form-container">
        <h3>Formulir Peminjaman Lab</h3>

        <div class="mt-30">
          <div class="mb-20">
            <label>Nama</label>
            <input type="text" class="form-ctrl input-border form-pinjam" required>
          </div>
          <div class="mb-20">
            <label>NPM</label>
            <input type="text" class="form-ctrl input-border form-pinjam" required>
          </div>
          <div class="mb-20">
            <label>Angkatan</label>
            <input type="text" class="form-ctrl input-border form-pinjam" required>
          </div>
          <div class="mb-20">
            <label>Email</label>
            <input type="email" class="form-ctrl input-border form-pinjam" required>
          </div>
          <div class="mb-20">
            <label>Tanggal</label>
            <input type="date" class="form-ctrl input-border form-pinjam" required>
          </div>
          <div class="mb-20">
            <label>Waktu</label>
            <input type="time" class="form-ctrl input-border form-pinjam" required>
          </div>
          <div class="mb-20">
            <label>Keperluan</label>
            <textarea></textarea>
          </div>
        </div>
        <div>
          <a href="#" class="btn btn-navy-solid mt-30">Kirim</a>
        </div>
      </div>

      <div class="schedule-container">
        <table class="schedule-table-head">
          <thead>
            <tr align="center">
              <th class="date-time">Tanggal dan Waktu</th>
              <th class="nama-npm">Nama</th>
              <th class="desc">Keperluan</th>
            </tr>
          </thead>
        </table>

        <div class="table-container bg-grey">
          <table class="schedule-table">
            <tbody>
              <tr>
                <td class="date-time">
                  <h3>28 Okt 2022</h3>
                  <p>09:00 - 10:00</p>
                </td>
                <td class="nama-npm">
                  <p style="font-weight: bold">Muhammad Ghibran Al Khamaeni</p>
                  <p>197006056</p>
                </td>
                <td class="desc">
                  <p>Pengujian alat penelitian Tugas Akhir</p>
                </td>
              </tr>
              <tr>
                <td class="date-time">
                  <h3>28 Okt 2022</h3>
                  <p>09:00 - 10:00</p>
                </td>
                <td class="nama-npm">
                  <p style="font-weight: bold">Muhammad Ghibran Al Khamaeni</p>
                  <p>197006056</p>
                </td>
                <td class="desc">
                  <p>Pengerjaan Tugas Akhir</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <FooterComponent />
  </div>
</template>

<script setup>
// Halaman jadwal dan formulir peminjaman lab
import JumbotronSmall from '@/components/jumbotron-small.vue'
import FooterComponent from '@/components/footer-component.vue'
</script>
