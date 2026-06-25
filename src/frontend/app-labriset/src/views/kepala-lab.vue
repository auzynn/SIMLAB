<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container flex-h between">
      <!-- ---------- SIDE MENU ---------- -->
      <SidemenuProfile />
      <!-- ---------- SIDE MENU END ---------- -->

      <div class="flex-v profil-container">
        <div class="flex-h around">
          <div class="dosen-photo-250">
            <img src="../assets/foto-dosen/nur-widiyasono.jpg" />
          </div>
        </div>
        <div class="flex-h around mt-30">
          <h2>Ir. Nur Widiyasono, S.Kom., M.Kom., CEH., CHFI., CITAP., MCE.</h2>
        </div>

        <table style="width: 100%; margin-top: 20px">
          <tbody>
            <tr style="height: 23px">
              <td style="width: 21.8243%; height: 23px">&nbsp;Jenis Kelamin</td>
              <td style="width: 68.1757%; height: 23px">&nbsp;: Laki-laki</td>
            </tr>
            <tr style="height: 23px">
              <td style="width: 21.8243%; height: 23px">&nbsp;Jabatan Fungsional</td>
              <td style="width: 68.1757%; height: 23px">&nbsp;: Lektor</td>
            </tr>
            <tr style="height: 23px">
              <td style="width: 21.8243%; height: 23px">&nbsp;NIDN</td>
              <td style="width: 68.1757%; height: 23px">&nbsp;: 310127203</td>
            </tr>
            <tr style="height: 23px">
              <td style="width: 21.8243%; height: 23px">&nbsp;Tempat dan Tanggal Lahir</td>
              <td style="width: 68.1757%; height: 23px">&nbsp;: Jakarta, 10 Desember 1972</td>
            </tr>
            <tr style="height: 23px">
              <td style="width: 21.8243%; height: 23px">&nbsp;Email</td>
              <td style="width: 68.1757%; height: 23px">&nbsp;: nur.widiyasono@unsil.ac.id, nur.w095@gmail.com</td>
            </tr>
            <tr style="height: 23.5px">
              <td style="width: 21.8243%; height: 23.5px">&nbsp;Nomor Telepon</td>
              <td style="width: 68.1757%; height: 23.5px">&nbsp;: 0819-0968-0432 / 0896-7641-6325</td>
            </tr>
            <tr style="height: 23px">
              <td style="width: 21.8243%; height: 23px">&nbsp;Bidang Minat</td>
              <td style="width: 68.1757%; height: 23px">&nbsp;: Digital Forensik, Network Engineering, System Engineering, Internet of Things, AI, IoT, <br />&nbsp;&nbsp;&nbsp;Cloud Computing, Security Engineering</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman profil Kepala Lab
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuProfile from '@/components/sidemenu-profile.vue'
import FooterComponent from '@/components/footer-component.vue'
</script>

<style scoped>
td {
  vertical-align: top;
}
</style>
