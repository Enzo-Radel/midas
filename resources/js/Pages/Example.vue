<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faWallet, faChartPie, faArrowTrendUp } from '@fortawesome/free-solid-svg-icons';

defineProps({
    message: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <h1 class="page-title">Welcome to Midas</h1>
            <p class="page-subtitle">Your financial management system</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="card card-primary">
                <div class="card-icon"><FontAwesomeIcon :icon="faWallet" /></div>
                <h2 class="card-title">Total Balance</h2>
                <p class="card-value">$12,345.67</p>
                <p class="card-desc">Across all accounts</p>
            </div>

            <div class="card card-info">
                <div class="card-icon"><FontAwesomeIcon :icon="faChartPie" /></div>
                <h2 class="card-title">Monthly Expenses</h2>
                <p class="card-value">$2,456.80</p>
                <p class="card-desc">This month</p>
            </div>

            <div class="card card-success">
                <div class="card-icon"><FontAwesomeIcon :icon="faArrowTrendUp" /></div>
                <h2 class="card-title">Savings Rate</h2>
                <p class="card-value">32%</p>
                <p class="card-desc">Year to date</p>
            </div>
        </div>

        <div class="card card-large">
            <h2 class="card-title-large">Message</h2>
            <p class="message">{{ message }}</p>
        </div>
    </AppLayout>
</template>

<style scoped>
.page-header {
    margin-bottom: 2rem;
}

.page-title {
    margin: 0 0 0.5rem;
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.page-subtitle {
    margin: 0;
    font-size: 1rem;
    color: #6b7280;
}

.grid {
    display: grid;
    margin-bottom: 2rem;
}

.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.gap-6 {
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) {
    .lg\:grid-cols-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.card {
    background: white;
    border-radius: 14px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    cursor: default;
}

.card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-4px);
    border-color: #d1d5db;
}

.card-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
    background-color: #f3f4f6;
    transition: all 0.2s ease;
}

.card-primary .card-icon {
    background-color: #dbeafe;
    color: #1e40af;
}

.card-info .card-icon {
    background-color: #e0e7ff;
    color: #3730a3;
}

.card-success .card-icon {
    background-color: #dcfce7;
    color: #15803d;
}

.card-title {
    margin: 0 0 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.75px;
}

.card-value {
    margin: 0 0 0.25rem;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}

.card-desc {
    margin: 0;
    font-size: 0.875rem;
    color: #9ca3af;
}

.card-large {
    padding: 2rem;
}

.card-large:hover {
    transform: translateY(-2px);
}

.card-title-large {
    margin: 0 0 1rem;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
}

.message {
    margin: 0;
    font-size: 1rem;
    color: #374151;
    line-height: 1.6;
}
</style>
