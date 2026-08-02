<script setup>
import { ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faGaugeHigh,
    faSackDollar,
    faChartLine,
    faGear,
    faChevronLeft,
    faUser,
    faCoins,
} from '@fortawesome/free-solid-svg-icons';

defineProps({
    modelValue: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['update:modelValue']);

const isOpen = ref(true);

const toggleSidebar = () => {
    isOpen.value = !isOpen.value;
};

const menuItems = [
    { label: 'Dashboard', icon: faGaugeHigh, href: '#' },
    { label: 'Expenses', icon: faSackDollar, href: '#' },
    { label: 'Reports', icon: faChartLine, href: '#' },
    { label: 'Settings', icon: faGear, href: '#' },
];
</script>

<template>
    <aside class="sidebar" :class="{ 'sidebar-collapsed': !isOpen }">
        <div class="sidebar-header">
            <div class="logo">
                <span class="logo-icon"><FontAwesomeIcon :icon="faCoins" /></span>
                <span v-if="isOpen" class="logo-text">Midas</span>
            </div>
            <button class="toggle-btn" @click="toggleSidebar" :title="isOpen ? 'Collapse' : 'Expand'" aria-label="Toggle sidebar">
                <FontAwesomeIcon class="chevron" :icon="faChevronLeft" />
            </button>
        </div>

        <nav class="sidebar-nav">
            <a v-for="item in menuItems" :key="item.label" :href="item.href" class="nav-item" :title="item.label">
                <span class="nav-icon"><FontAwesomeIcon :icon="item.icon" /></span>
                <span v-if="isOpen" class="nav-label">{{ item.label }}</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <button class="user-btn" title="Open profile menu" aria-label="User profile">
                <span class="user-avatar"><FontAwesomeIcon :icon="faUser" /></span>
                <span v-if="isOpen" class="user-name">Profile</span>
            </button>
        </div>
    </aside>
</template>

<style scoped>
.sidebar {
    position: fixed;
    left: 1rem;
    top: 1rem;
    width: 280px;
    height: calc(100vh - 2rem);
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    padding: 1rem;
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
    z-index: 1000;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    border-radius: 16px;
}

.sidebar-collapsed {
    width: 80px;
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    color: white;
}

.logo-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    font-weight: 700;
    font-size: 1.25rem;
    transition: all 0.2s ease;
}

.logo:hover .logo-icon {
    background-color: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.logo-text {
    font-weight: 700;
    font-size: 1.25rem;
}

.toggle-btn {
    background-color: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

.toggle-btn:active {
    background-color: rgba(255, 255, 255, 0.3);
    transform: scale(0.98);
}

.chevron {
    display: inline-block;
    transition: transform 0.3s ease;
}

.sidebar-collapsed .chevron {
    transform: rotate(180deg);
}

.sidebar-nav {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.2s ease;
    cursor: pointer;
    white-space: nowrap;
    border: 2px solid transparent;
}

.nav-item:hover {
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateX(4px);
}

.nav-item:active {
    background-color: rgba(255, 255, 255, 0.25);
    transform: scale(0.98);
}

.nav-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
    width: 24px;
    text-align: center;
}

.nav-label {
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-footer {
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.user-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background-color: rgba(255, 255, 255, 0.1);
    border: 2px solid transparent;
    border-radius: 10px;
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    font-weight: 500;
}

.user-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.user-btn:active {
    background-color: rgba(255, 255, 255, 0.25);
    transform: translateY(0);
}

.user-avatar {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.user-name {
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(calc(-100% - 1rem));
        position: fixed;
    }

    .sidebar.active {
        transform: translateX(0);
    }
}
</style>
