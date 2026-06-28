<template>
  <div ref="root" class="ms-dropdown">
    <button type="button" class="ms-toggle form-ctrl input-border" @click="open = !open">
      <span>{{ label }}</span>
      <span class="ms-caret" :class="{ 'ms-open': open }">▾</span>
    </button>

    <div v-if="open" class="ms-panel">
      <label v-for="opt in options" :key="opt.id" class="ms-option">
        <input
          type="checkbox"
          :value="opt.id"
          :checked="modelValue.includes(opt.id)"
          @change="toggle(opt.id)"
        />
        <span>{{ opt.nama }}</span>
      </label>
      <div v-if="!options.length" class="ms-empty">Tidak ada pilihan</div>
    </div>

    <!-- Ringkasan item yang dipilih (chip, bisa dihapus) -->
    <div v-if="selectedOptions.length" class="ms-chips">
      <span v-for="opt in selectedOptions" :key="opt.id" class="ms-chip">
        {{ opt.nama }}
        <button type="button" class="ms-chip-x" :aria-label="`Hapus ${opt.nama}`" @click="toggle(opt.id)">×</button>
      </span>
    </div>
  </div>
</template>

<script setup>
// Dropdown multi-select sederhana (v-model = array of id).
// Tombol menampilkan ringkasan jumlah terpilih; panel checkbox muncul saat diklik,
// menutup saat klik di luar. Dipakai mis. untuk Bidang Minat di Edit Profil.
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Pilih...' },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref(null)

const label = computed(() => {
  const n = props.modelValue.length
  return n ? `${n} item dipilih` : props.placeholder
})

// Opsi yang sedang terpilih (untuk ditampilkan sebagai chip)
const selectedOptions = computed(() =>
  props.options.filter((o) => props.modelValue.includes(o.id)),
)

function toggle(id) {
  const set = new Set(props.modelValue)
  set.has(id) ? set.delete(id) : set.add(id)
  emit('update:modelValue', [...set])
}

function onClickOutside(e) {
  if (open.value && root.value && !root.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<style scoped>
.ms-dropdown {
  position: relative;
}

.ms-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  cursor: pointer;
  background: #fff;
  text-align: left;
}

.ms-caret {
  color: var(--bs-navy);
  transition: transform 0.15s ease;
}

.ms-caret.ms-open {
  transform: rotate(180deg);
}

.ms-panel {
  position: absolute;
  z-index: 30;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid var(--bs-grey2, #d1d5db);
  border-radius: 8px;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
  padding: 6px;
  max-height: 240px;
  overflow-y: auto;
}

.ms-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 8px;
  font-weight: 400;
  cursor: pointer;
  border-radius: 4px;
}

.ms-option:hover {
  background: var(--bs-grey1, #f3f4f6);
}

.ms-option input {
  margin: 0;
}

.ms-empty {
  color: #6b7280;
  padding: 8px;
}

.ms-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.ms-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--bs-grey1, #eef2f7);
  color: var(--bs-navy);
  border: 1px solid var(--bs-grey2, #d1d5db);
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 0.85em;
}

.ms-chip-x {
  border: none;
  background: none;
  cursor: pointer;
  color: var(--bs-navy);
  font-size: 1.1em;
  line-height: 1;
  padding: 0;
}

.ms-chip-x:hover {
  color: #c0392b;
}
</style>
