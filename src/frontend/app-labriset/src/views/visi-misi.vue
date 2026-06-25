<template>
  <div>
    <!-- ---------- JUMBOTRON SMALL ---------- -->
    <JumbotronSmall />
    <!-- ---------- JUMBOTRON SMALL END ---------- -->

    <div class="main-container flex-h between">
      <!-- ---------- SIDE MENU ---------- -->
      <SidemenuProfile />
      <!-- ---------- SIDE MENU END ---------- -->

      <div class="profil-container">
        <div>
          <h1>Visi dan Misi</h1>
          <div class="profil-title"></div>
        </div>

        <article class="mt-30" style="text-align: justify">
          <h3>Visi</h3>
          <p>Menjadi Laboratorium Fakultas Teknik yang memiliki karakter wirausaha, menghasilkan sumber daya manusia yang unggul dan berwawasan kebangsaan menuju kampus bahagia di tahun 2026</p>
        </article>

        <article class="mt-30" style="text-align: justify">
          <h3>Misi</h3>
          <ol class="list-misi">
            <li>Membangun ekosistem akademik di lingkungan Laboratorium Fakultas Teknik dengan menjunjung tinggi kaidah-kaidah norma agama dan norma sosial, kebhinekaan dan toleransi antar agama dan pemeluknya.</li>
            <li>Mengembangkan Laboratorium untuk bidang ilmu Jaringan Komputer, Keamanan, Forensika Digital dan Internet of Things (IoT) di lingkungan Fakultas Teknik dengan memanfaatkan teknologi yang berkembang sehingga menghasilkan sumber daya manusia yang tangguh, mandiri dan bertanggung jawab.</li>
            <li>Mengembangkan Ilmu Pengetahuan dan Teknologi melalui kerjasama/kolaborasi penelitian dan pengabdian masyarakat.</li>
            <li>Melaksanakan pengabdian masyarakat sebagai wahana implementasi dari hasil penelitian untuk menumbuhkan kewirausahaan bidang ilmu Jaringan Komputer, Forensika Digital dan Internet of Things (IoT) dalam mendukung prioritas pembangunan nasional.</li>
            <li>Melaksanakan layanan akademik mata kuliah praktikum dan layanan non-akademik berbasis MBKM di lingkungan Fakultas Teknik ataupun Program Studi Informatika secara transparan, akuntabel, kredibel, untuk menghasilkan sumber daya manusia yang memiliki daya saing global.</li>
            <li>Meningkatkan dan mengembangkan harmonisasi kerjasama berbasis MBKM di lingkungan Program Studi Informatika ataupun Fakultas Teknik pada skala nasional dan internasional yang berkualitas untuk meningkatkan daya saing institusi menuju world class-university (WCU).</li>
            <li>Meningkatkan layanan yang dapat mendukung kegiatan kemahasiswaan menuju mahasiswa yang kompetitif pada bidang keilmuan Jaringan Komputer, Forensika Digital dan Internet of Things (IoT).</li>
            <li>Mewujudkan Laboratorium yang dapat mendukung program Green Campus.</li>
          </ol>
        </article>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman visi dan misi laboratorium
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuProfile from '@/components/sidemenu-profile.vue'
import FooterComponent from '@/components/footer-component.vue'
</script>
