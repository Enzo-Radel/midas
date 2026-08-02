<script setup>
import { ref } from 'vue';
import Sidebar from '../Components/Sidebar.vue';

const sidebarOpen = ref(true);
</script>

<template>
    <div class="app-container">
        <Sidebar v-model="sidebarOpen" />

        <main class="main-content" :class="{ 'sidebar-closed': !sidebarOpen }">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.app-container {
    display: flex;
    min-height: 100vh;
    background-color: #f9fafb;
}

.main-content {
    flex: 1;
    padding: 2rem;
    margin-left: calc(280px + 2rem);
    transition: margin-left 0.3s ease;
    overflow-y: auto;
}

.main-content.sidebar-closed {
    margin-left: calc(80px + 2rem);
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 1.5rem;
    }
}
</style>
