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
          <h1>Profil Dosen</h1>
          <div class="profil-title"></div>
        </div>

        <div class="flex-v mt-30">
          <div class="profil-dosen">
            <div class="dosen-photo-120">
              <img src="../assets/foto-dosen/nur-widiyasono.jpg">
            </div>

            <div class="profil-desc flex-v between ml-30">
              <div>
                <h2>Ir. Nur Widiyasono, S.Kom., M.Kom., CEH., CHFI., CITAP., MCE.</h2>
                <p>Dosen Program Studi Informatika — bidang Jaringan, Keamanan, dan Forensik Digital.</p>
              </div>
              <div class="flex-h between">
                <div class="publish-icon flex-h">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                </div>
                <div class="detail">
                  <router-link to="/detaildosen">Detail</router-link>
                </div>
              </div>
            </div>
          </div>

          <div class="profil-dosen">
            <div class="dosen-photo-120">
              <img src="../assets/foto-dosen/nur-widiyasono.jpg">
            </div>

            <div class="profil-desc flex-v between ml-30">
              <div>
                <h2>Ir. Nur Widiyasono, S.Kom., M.Kom., CEH., CHFI., CITAP., MCE.</h2>
                <p>Dosen Program Studi Informatika — bidang Jaringan, Keamanan, dan Forensik Digital.</p>
              </div>
              <div class="flex-h between">
                <div class="publish-icon flex-h">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                </div>
                <div class="detail">
                  <router-link to="/detaildosen">Detail</router-link>
                </div>
              </div>
            </div>
          </div>

          <div class="profil-dosen">
            <div class="dosen-photo-120">
              <img src="../assets/foto-dosen/nur-widiyasono.jpg">
            </div>

            <div class="profil-desc flex-v between ml-30">
              <div>
                <h2>Ir. Nur Widiyasono, S.Kom., M.Kom., CEH., CHFI., CITAP., MCE.</h2>
                <p>Dosen Program Studi Informatika — bidang Jaringan, Keamanan, dan Forensik Digital.</p>
              </div>
              <div class="flex-h between">
                <div class="publish-icon flex-h">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                </div>
                <div class="detail">
                  <router-link to="/detaildosen">Detail</router-link>
                </div>
              </div>
            </div>
          </div>

          <div class="profil-dosen">
            <div class="dosen-photo-120">
              <img src="../assets/foto-dosen/nur-widiyasono.jpg">
            </div>

            <div class="profil-desc flex-v between ml-30">
              <div>
                <h2>Ir. Nur Widiyasono, S.Kom., M.Kom., CEH., CHFI., CITAP., MCE.</h2>
                <p>Dosen Program Studi Informatika — bidang Jaringan, Keamanan, dan Forensik Digital.</p>
              </div>
              <div class="flex-h between">
                <div class="publish-icon flex-h">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                </div>
                <div class="detail">
                  <router-link to="/detaildosen">Detail</router-link>
                </div>
              </div>
            </div>
          </div>

          <div class="profil-dosen">
            <div class="dosen-photo-120">
              <img src="../assets/foto-dosen/nur-widiyasono.jpg">
            </div>

            <div class="profil-desc flex-v between ml-30">
              <div>
                <h2>Ir. Nur Widiyasono, S.Kom., M.Kom., CEH., CHFI., CITAP., MCE.</h2>
                <p>Dosen Program Studi Informatika — bidang Jaringan, Keamanan, dan Forensik Digital.</p>
              </div>
              <div class="flex-h between">
                <div class="publish-icon flex-h">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                  <img src="../assets/logo-publikasi/google-scholar.png" class="logo-publikasi">
                </div>
                <div class="detail">
                  <router-link to="/detaildosen">Detail</router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ---------- FOOTER ---------- -->
    <FooterComponent />
    <!-- ---------- FOOTER END ---------- -->
  </div>
</template>

<script setup>
// Halaman daftar profil dosen
import JumbotronSmall from '@/components/jumbotron-small.vue'
import SidemenuProfile from '@/components/sidemenu-profile.vue'
import FooterComponent from '@/components/footer-component.vue'
</script>
