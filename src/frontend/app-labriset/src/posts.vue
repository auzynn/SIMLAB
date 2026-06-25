<template>
  <div>
    <h1>Post List</h1>
    <ul>
      <li v-for="post in posts" :key="post.id">{{ post.title }}</li>
    </ul>
    <form @submit.prevent="createPost">
      <input v-model="newPost.title" placeholder="Title" />
      <input v-model="newPost.body" placeholder="Body" />
      <button type="submit">Create Post</button>
    </form>
  </div>
</template>

<script setup>
// Halaman test CRUD post (untuk pengujian koneksi API)
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const posts = ref([])
const newPost = ref({ title: '', body: '' })

async function fetchPosts() {
  try {
    const response = await api.get('/api/posts')
    posts.value = response.data
  } catch (error) {
    console.error(error)
  }
}

async function createPost() {
  try {
    const response = await api.post('/api/posts', newPost.value)
    posts.value.push(response.data)
    newPost.value = { title: '', body: '' }
  } catch (error) {
    console.error(error)
  }
}

onMounted(fetchPosts)
</script>
