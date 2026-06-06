<script setup>
defineProps({
    message: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="page">
        <div class="card">
            <h1 class="title">Midas</h1>
            <p class="message">{{ message }}</p>
        </div>
    </div>
</template>

<style scoped>
.page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background-color: #f5f5f5;
}

.card {
    padding: 2rem;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #111;
}

.message {
    margin: 0.5rem 0 0;
    color: #555;
}
</style>
