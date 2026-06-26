<template>
  <div class="markdown-body" v-html="html"></div>
</template>

<script setup>
// Render teks Markdown dari panel admin menjadi HTML (konten info lab).
// Sumber konten hanya dari Admin tepercaya (bukan input publik), jadi v-html aman untuk konteks ini.
import { computed } from 'vue'
import { marked } from 'marked'

const props = defineProps({
  source: { type: String, default: '' },
})

const html = computed(() => marked.parse(props.source || ''))
</script>

<style scoped>
/* Konten v-html tak terkena scoped style biasa → pakai :deep().
   Marker list perlu diaktifkan ulang karena reset global `* { list-style-type: none }`. */
.markdown-body :deep(h2),
.markdown-body :deep(h3) {
  margin-top: 18px;
  margin-bottom: 8px;
}

.markdown-body :deep(p) {
  margin-bottom: 12px;
  line-height: 1.6;
}

.markdown-body :deep(ul) {
  /* Tanpa indentasi agar penanda sejajar dengan judul (mis. "Misi"). */
  padding-left: 0;
  margin-bottom: 12px;
}

.markdown-body :deep(ol) {
  padding-left: 1.5em;
  margin-bottom: 12px;
}

.markdown-body :deep(li) {
  margin-bottom: 6px;
}

/* Penanda daftar tak-bernomor: panah kanan biru (➤) sejajar judul. Pakai ::before
   karena list-style-type tak mendukung warna, dan reset global menyetel none pada <li>. */
.markdown-body :deep(ul) > li {
  list-style-type: none;
  position: relative;
  padding-left: 1.5em;
}

.markdown-body :deep(ul) > li::before {
  content: '\27A4';
  position: absolute;
  left: 0;
  font-weight: bold;
  /* Dua warna: kuning di paruh atas, biru di paruh bawah — gradien di-clip ke glyph. */
  background: linear-gradient(to bottom, var(--bs-yellow) 0 50%, var(--bs-navy) 50% 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
}

/* Daftar bernomor tetap angka (reset global perlu di-set ulang di <li>). */
.markdown-body :deep(ol) > li {
  list-style-type: decimal;
}

.markdown-body :deep(a) {
  display: inline;
  color: var(--bs-navy);
  text-decoration: underline;
}

.markdown-body :deep(img) {
  max-width: 100%;
  height: auto;
}

/* Tabel profil (mis. data Kepala Lab) — sepadan tampilan halaman lama. */
.markdown-body :deep(table) {
  width: 100%;
  margin-top: 20px;
  border-collapse: collapse;
}

.markdown-body :deep(td) {
  vertical-align: top;
  padding: 4px 8px;
}

/* Kolom label kiri lebih ringkas, kolom nilai mengisi sisanya. */
.markdown-body :deep(td:first-child) {
  width: 22%;
  white-space: nowrap;
}

/* Header kosong dari tabel markdown tak perlu tampil. */
.markdown-body :deep(thead) {
  display: none;
}
</style>
