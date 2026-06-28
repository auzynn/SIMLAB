<template>
  <div class="rte input-border">
    <div v-if="editor" class="rte-toolbar">
      <!-- Undo / Redo -->
      <button type="button" class="rte-btn" title="Urungkan" @click="editor.chain().focus().undo().run()">↶</button>
      <button type="button" class="rte-btn" title="Ulangi" @click="editor.chain().focus().redo().run()">↷</button>
      <span class="rte-sep"></span>

      <!-- Gaya blok -->
      <select class="rte-select" title="Gaya teks" :value="blockValue" @change="onBlock">
        <option value="p">Paragraf</option>
        <option value="h2">Judul (H2)</option>
        <option value="h3">Subjudul (H3)</option>
      </select>

      <!-- Jenis huruf -->
      <select class="rte-select" title="Jenis huruf" @change="onFontFamily">
        <option value="">Font</option>
        <option value="Arial, sans-serif">Arial</option>
        <option value="'Times New Roman', serif">Times New Roman</option>
        <option value="Georgia, serif">Georgia</option>
        <option value="'Courier New', monospace">Courier New</option>
        <option value="__unset__">Default</option>
      </select>

      <!-- Ukuran huruf -->
      <select class="rte-select" title="Ukuran huruf" @change="onFontSize">
        <option value="">Ukuran</option>
        <option value="__unset__">Normal</option>
        <option value="12px">12</option>
        <option value="14px">14</option>
        <option value="16px">16</option>
        <option value="18px">18</option>
        <option value="24px">24</option>
        <option value="32px">32</option>
      </select>
      <span class="rte-sep"></span>

      <button type="button" class="rte-btn" :class="{ active: editor.isActive('bold') }" title="Tebal" @click="editor.chain().focus().toggleBold().run()"><b>B</b></button>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive('italic') }" title="Miring" @click="editor.chain().focus().toggleItalic().run()"><i>I</i></button>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive('underline') }" title="Garis bawah" @click="editor.chain().focus().toggleUnderline().run()"><u>U</u></button>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive('strike') }" title="Coret" @click="editor.chain().focus().toggleStrike().run()"><s>S</s></button>

      <!-- Warna teks & sorot -->
      <label class="rte-btn rte-color" title="Warna teks">A
        <input type="color" value="#1f3a5f" @input="onColor" />
      </label>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive('highlight') }" title="Sorot" @click="editor.chain().focus().toggleHighlight({ color: '#fff3a3' }).run()">🖍</button>
      <span class="rte-sep"></span>

      <button type="button" class="rte-btn" :class="{ active: editor.isActive('bulletList') }" title="Daftar berbutir" @click="editor.chain().focus().toggleBulletList().run()">• List</button>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive('orderedList') }" title="Daftar bernomor" @click="editor.chain().focus().toggleOrderedList().run()">1. List</button>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive('blockquote') }" title="Kutipan" @click="editor.chain().focus().toggleBlockquote().run()">❝</button>
      <span class="rte-sep"></span>

      <button type="button" class="rte-btn" :class="{ active: editor.isActive({ textAlign: 'left' }) }" title="Rata kiri" @click="editor.chain().focus().setTextAlign('left').run()">⯇</button>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive({ textAlign: 'center' }) }" title="Rata tengah" @click="editor.chain().focus().setTextAlign('center').run()">≡</button>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive({ textAlign: 'right' }) }" title="Rata kanan" @click="editor.chain().focus().setTextAlign('right').run()">⯈</button>
      <button type="button" class="rte-btn" :class="{ active: editor.isActive({ textAlign: 'justify' }) }" title="Rata kiri-kanan" @click="editor.chain().focus().setTextAlign('justify').run()">☰</button>
      <span class="rte-sep"></span>

      <button type="button" class="rte-btn" title="Tautan" @click="addLink">🔗</button>
      <button type="button" class="rte-btn" title="Hapus tautan" @click="editor.chain().focus().unsetLink().run()">⛓×</button>
      <span class="rte-sep"></span>

      <!-- Tabel -->
      <button type="button" class="rte-btn" title="Sisip tabel" @click="editor.chain().focus().insertTable({ rows: 2, cols: 2, withHeaderRow: false }).run()">⊞</button>
      <button type="button" class="rte-btn" title="Tambah baris" @click="editor.chain().focus().addRowAfter().run()">+↧</button>
      <button type="button" class="rte-btn" title="Tambah kolom" @click="editor.chain().focus().addColumnAfter().run()">+↦</button>
      <button type="button" class="rte-btn" title="Hapus baris" @click="editor.chain().focus().deleteRow().run()">−↥</button>
      <button type="button" class="rte-btn" title="Hapus kolom" @click="editor.chain().focus().deleteColumn().run()">−↤</button>
      <button type="button" class="rte-btn" title="Hapus tabel" @click="editor.chain().focus().deleteTable().run()">⊟</button>
      <span class="rte-sep"></span>

      <button type="button" class="rte-btn" title="Hapus format" @click="editor.chain().focus().unsetAllMarks().clearNodes().run()">⨯</button>
    </div>

    <editor-content :editor="editor" class="rte-content" />
  </div>
</template>

<script setup>
// Editor WYSIWYG berbasis TipTap (ProseMirror). v-model berupa string HTML.
// Keluaran HTML dirender apa adanya di halaman publik (markdown-content via marked).
import { watch, computed } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { TextStyleKit } from '@tiptap/extension-text-style'
import TextAlign from '@tiptap/extension-text-align'
import Highlight from '@tiptap/extension-highlight'
import { TableKit } from '@tiptap/extension-table'

const props = defineProps({
  modelValue: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue || '',
  extensions: [
    // StarterKit v3 sudah memuat Bold/Italic/Strike/Underline/Link/Heading/List/
    // Blockquote/Code/HorizontalRule/UndoRedo.
    StarterKit.configure({ link: { openOnClick: false } }),
    // TextStyleKit = TextStyle + Color + FontSize + FontFamily + LineHeight + BackgroundColor
    TextStyleKit,
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    Highlight.configure({ multicolor: true }),
    TableKit.configure({ table: { resizable: true } }),
  ],
  onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
})

// Sinkron konten dari luar (mis. ganti tab tipe) tanpa memicu loop update
watch(
  () => props.modelValue,
  (val) => {
    if (editor.value && (val || '') !== editor.value.getHTML()) {
      editor.value.commands.setContent(val || '', { emitUpdate: false })
    }
  },
)

const blockValue = computed(() => {
  if (!editor.value) return 'p'
  if (editor.value.isActive('heading', { level: 2 })) return 'h2'
  if (editor.value.isActive('heading', { level: 3 })) return 'h3'
  return 'p'
})

function onBlock(e) {
  const chain = editor.value.chain().focus()
  if (e.target.value === 'h2') chain.toggleHeading({ level: 2 }).run()
  else if (e.target.value === 'h3') chain.toggleHeading({ level: 3 }).run()
  else chain.setParagraph().run()
}

function onFontFamily(e) {
  const v = e.target.value
  if (!v) return
  if (v === '__unset__') editor.value.chain().focus().unsetFontFamily().run()
  else editor.value.chain().focus().setFontFamily(v).run()
  e.target.value = ''
}

function onFontSize(e) {
  const v = e.target.value
  if (!v) return
  if (v === '__unset__') editor.value.chain().focus().unsetFontSize().run()
  else editor.value.chain().focus().setFontSize(v).run()
  e.target.value = ''
}

function onColor(e) {
  editor.value.chain().focus().setColor(e.target.value).run()
}

function addLink() {
  const prev = editor.value.getAttributes('link').href
  const url = window.prompt('URL tautan:', prev || 'https://')
  if (url === null) return
  if (url === '') {
    editor.value.chain().focus().unsetLink().run()
    return
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}
</script>

<style scoped>
.rte {
  border-radius: 6px;
  overflow: hidden;
  background: #fff;
}

.rte-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  padding: 6px 8px;
  background: var(--bs-grey1, #f3f4f6);
  border-bottom: 1px solid var(--bs-grey2, #d1d5db);
}

.rte-btn {
  min-width: 30px;
  height: 30px;
  padding: 0 8px;
  border: 1px solid transparent;
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
  color: var(--bs-navy);
  font-size: 0.9em;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.rte-btn:hover {
  border-color: var(--bs-grey2, #d1d5db);
}

.rte-btn.active {
  background: var(--bs-navy);
  color: #fff;
}

.rte-select {
  height: 30px;
  border: 1px solid var(--bs-grey2, #d1d5db);
  border-radius: 4px;
  background: #fff;
  color: var(--bs-navy);
  font-size: 0.85em;
  cursor: pointer;
}

.rte-color {
  position: relative;
  font-weight: bold;
}

.rte-color input[type='color'] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.rte-sep {
  width: 1px;
  height: 20px;
  background: var(--bs-grey2, #d1d5db);
  margin: 0 4px;
}

/* Area edit (ProseMirror) — aktifkan kembali penanda daftar (reset global: none). */
.rte-content :deep(.ProseMirror) {
  min-height: 220px;
  max-height: 440px;
  overflow-y: auto;
  padding: 12px 14px;
  line-height: 1.6;
  outline: none;
}

.rte-content :deep(.ProseMirror:focus) {
  outline: none;
}

.rte-content :deep(h2) {
  font-size: 1.4em;
  margin: 14px 0 6px;
}

.rte-content :deep(h3) {
  font-size: 1.2em;
  margin: 12px 0 6px;
}

.rte-content :deep(p) {
  margin: 0 0 10px;
}

.rte-content :deep(ul),
.rte-content :deep(ol) {
  padding-left: 1.6em;
  margin: 0 0 10px;
}

.rte-content :deep(ul) > li {
  list-style-type: disc;
}

.rte-content :deep(ol) > li {
  list-style-type: decimal;
}

.rte-content :deep(blockquote) {
  border-left: 3px solid var(--bs-grey2, #d1d5db);
  padding-left: 12px;
  color: #555;
  margin: 0 0 10px;
}

.rte-content :deep(a) {
  color: var(--bs-navy);
  text-decoration: underline;
}

/* Tabel: tampilkan garis di editor agar struktur sel terlihat saat menyunting
   (di halaman publik garis ini tidak ada — diatur markdown-content.vue). */
.rte-content :deep(table) {
  border-collapse: collapse;
  width: 100%;
  margin: 0 0 10px;
  table-layout: fixed;
}

.rte-content :deep(td),
.rte-content :deep(th) {
  border: 1px solid var(--bs-grey2, #d1d5db);
  padding: 4px 8px;
  vertical-align: top;
}

.rte-content :deep(th) {
  background: var(--bs-grey1, #f3f4f6);
  font-weight: bold;
}
</style>
