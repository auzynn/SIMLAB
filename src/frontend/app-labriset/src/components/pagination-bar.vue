<template>
  <div v-if="totalPages > 1" class="pagination">
    <button class="pg-btn" :disabled="page <= 1" @click="emit('update:page', page - 1)">‹</button>
    <button
      v-for="p in pages"
      :key="p"
      class="pg-btn"
      :class="{ 'pg-active': p === page }"
      @click="emit('update:page', p)"
    >
      {{ p }}
    </button>
    <button class="pg-btn" :disabled="page >= totalPages" @click="emit('update:page', page + 1)">›</button>
  </div>
</template>

<script setup>
// Kontrol pagination: tampil hanya bila lebih dari satu halaman.
import { computed } from 'vue'

const props = defineProps({
  page: { type: Number, required: true },
  totalPages: { type: Number, required: true },
})
const emit = defineEmits(['update:page'])

const pages = computed(() => Array.from({ length: props.totalPages }, (_, i) => i + 1))
</script>

<style scoped>
.pagination {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 20px;
  align-items: center;
}

.pg-btn {
  min-width: 36px;
  height: 34px;
  padding: 0 10px;
  border: 1px solid var(--bs-grey2, #d1d5db);
  border-radius: 5px;
  background: #fff;
  color: var(--bs-navy);
  cursor: pointer;
}

.pg-btn:hover:not(:disabled) {
  border-color: var(--bs-navy);
}

.pg-btn:disabled {
  opacity: 0.5;
  cursor: default;
}

.pg-active {
  background: var(--bs-navy);
  color: #fff;
  border-color: var(--bs-navy);
}
</style>
