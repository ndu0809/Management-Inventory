<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from './components/Sidebar.vue'
import InventoryPage from './pages/InventoryPage.vue'
import MembersPage from './pages/MembersPage.vue'
import AnalyticsPage from './pages/AnalyticsPage.vue'
import { logout as logoutApi } from './services/auth'

const router = useRouter()
const activePage = ref('Data Inventaris')
const darkTheme = ref(false)
const currentUser = ref<{ name?: string; email?: string } | null>(null)

const pageTitle = computed(() => activePage.value)

function readCurrentUser() {
  try {
    const savedUser = localStorage.getItem('auth_user')
    currentUser.value = savedUser ? JSON.parse(savedUser) : null
  } catch {
    currentUser.value = null
  }
}

function selectPage(page: string) {
  activePage.value = page
}

function toggleTheme() {
  darkTheme.value = !darkTheme.value
}

async function logout() {
  try {
    await logoutApi()
  } catch (error) {
    console.error('Logout API gagal:', error)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    await router.replace('/login')
  }
}

onMounted(readCurrentUser)
</script>

<template>
  <div class="app-layout" :class="{ 'dark-theme': darkTheme }">
    <Sidebar
      :active-page="activePage"
      :current-user="currentUser"
      :dark-theme="darkTheme"
      @navigate="selectPage"
      @toggle-theme="toggleTheme"
      @logout="logout"
    />

    <main class="main-content">
      <header class="page-header">
        <h1>{{ pageTitle }}</h1>
      </header>

      <InventoryPage v-if="activePage === 'Data Inventaris'" />
      <MembersPage v-else-if="activePage === 'Management Anggota'" />
      <AnalyticsPage v-else />
      
      <footer class="app-footer">Management Inventaris</footer>
    </main>
  </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

*,
*::before,
*::after {
  box-sizing: border-box;
}

:root {
  font-family: 'Inter', Arial, sans-serif;
  color: #202020;
  background: #f7f8fa;
  font-synthesis: none;
  text-rendering: optimizeLegibility;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

html,
body,
#app {
  width: 100%;
  min-width: 0;
  min-height: 100%;
  margin: 0;
  padding: 0;
}

body {
  min-height: 100vh;
  min-height: 100dvh;
  overflow-x: hidden;
}

button,
input,
select,
textarea {
  font: inherit;
}

button {
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.app-layout {
  --page: #fff;
  --text: #202020;
  --muted: #777d87;
  --line: #e8eaf0;
  --hover: #f1f3f6;
  --surface: #fff;

  display: flex;
  align-items: stretch;
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  min-height: 100dvh;
  color: var(--text);
  background: var(--page);
  overflow-x: clip;
}

.app-layout.dark-theme {
  --page: #17191d;
  --text: #f0f1f3;
  --muted: #a0a5af;
  --line: #30343b;
  --hover: #282c32;
  --surface: #1c1f24;

  color-scheme: dark;
}

.main-content {
  display: flex;
  flex: 1 1 0;
  flex-direction: column;
  width: calc(100% - 260px);
  max-width: none;
  min-width: 0;
  min-height: 100vh;
  min-height: 100dvh;
  margin-left: 260px;
  padding: clamp(20px, 2.4vw, 40px);
  overflow-x: hidden;
  background: var(--page);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  min-width: 0;
  margin-bottom: 22px;
}

.page-header h1 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.app-footer {
  width: 100%;
  margin-top: auto;
  padding: 35px 0 5px;
  color: #a0a4ac;
  font-size: 10px;
  text-align: center;
}

.content-section {
  flex: 1 0 auto;
  width: 100%;
  min-width: 0;
}

.page-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  width: 100%;
  margin-bottom: 20px;
}

.page-toolbar h2 {
  margin: 0;
  font-size: 15px;
}

.page-toolbar-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  min-width: 0;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 9px;
  width: clamp(180px, 28vw, 290px);
  min-width: 0;
  height: 40px;
  padding: 0 12px;
  border-radius: 22px;
  background: #eff3f5;
}

.search-box input {
  width: 100%;
  min-width: 0;
  border: 0;
  outline: none;
  color: #333;
  background: transparent;
  font-size: 12px;
}

.primary-button,
.secondary-button,
.danger-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 38px;
  padding: 0 14px;
  border: 1px solid transparent;
  border-radius: 9px;
  font-size: 11px;
  font-weight: 600;
}

.primary-button {
  color: #d8f69a;
  background: #050505;
}

.secondary-button {
  border-color: var(--line);
  color: var(--text);
  background: var(--page);
}

.danger-button {
  border-color: #ffd1d1;
  color: #b42323;
  background: #fff0f0;
}

.table-wrapper {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow-x: auto;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: var(--page);
  scrollbar-width: thin;
  overscroll-behavior-x: contain;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  min-width: 900px;
  border-collapse: separate;
  border-spacing: 0;
  background: var(--page);
  font-size: 12px;
}

.data-table th,
.data-table td {
  padding: 13px 14px;
  text-align: left;
  vertical-align: middle;
  border-bottom: 1px solid var(--line);
}

.data-table th {
  height: 48px;
  color: var(--muted);
  background: var(--surface);
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.data-table td {
  min-height: 52px;
  color: var(--text);
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.data-table tbody tr:last-child td {
  border-bottom: 0;
}

.no-data {
  padding: 28px !important;
  color: var(--muted) !important;
  text-align: center !important;
}

.form-panel {
  margin-bottom: 20px;
  padding: 20px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--page);
}

.form-panel h2 {
  margin: 0 0 18px;
  font-size: 15px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 15px;
}

.form-grid label {
  display: grid;
  align-content: start;
  gap: 7px;
  min-width: 0;
  font-size: 11px;
  font-weight: 500;
}

.form-grid input,
.form-grid select,
.form-grid textarea {
  width: 100%;
  min-width: 0;
  min-height: 42px;
  padding: 10px 11px;
  border: 1px solid #dfe2e8;
  border-radius: 8px;
  outline: none;
  color: var(--text);
  background: var(--page);
  font-size: 12px;
}

.form-grid textarea {
  resize: vertical;
}

.full-field {
  grid-column: 1 / -1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.notice {
  margin: 0 0 16px;
  padding: 12px 14px;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.error-notice {
  border: 1px solid #ffd3cd;
  color: #a72d27;
  background: #ffefed;
}

.success-notice {
  border: 1px solid #c6f5d0;
  color: #087b30;
  background: #e8ffed;
}

.loading-state {
  padding: 15px 0;
  color: var(--muted);
  font-size: 12px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  max-width: 100%;
  padding: 6px 9px;
  border-radius: 7px;
  font-size: 10px;
  line-height: 1.35;
  white-space: normal;
}

.status-good {
  color: #087c30;
  background: #d7fbe1;
}

.status-damaged {
  color: #a72d27;
  background: #ffe0dc;
}

.status-unused {
  color: #555;
  background: #e9e9e9;
}

.status-auction {
  color: #766019;
  background: #fff0c8;
}

.row-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.pagination-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-top: 16px;
  font-size: 11px;
}

.pagination-footer select,
.pagination button {
  min-height: 34px;
  padding: 0 10px;
  border: 1px solid var(--line);
  border-radius: 8px;
  color: var(--text);
  background: var(--page);
}

.pagination {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.pagination button.current {
  background: var(--hover);
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 15px;
  width: 100%;
}

.stat-card {
  display: grid;
  align-content: start;
  gap: 12px;
  min-width: 0;
  padding: 20px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--page);
}

.stat-card span {
  color: var(--muted);
  font-size: 12px;
}

.stat-card strong {
  font-size: clamp(22px, 2.2vw, 30px);
  font-weight: 600;
  overflow-wrap: anywhere;
}

.stat-card .stat-price {
  font-size: clamp(16px, 1.8vw, 22px);
}

@media (max-width: 1024px) {
  .main-content {
    width: calc(100% - 220px);
    margin-left: 220px;
    padding: 24px 20px;
  }

  .analytics-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}


@media (max-width: 760px) {
  .app-layout {
    flex-direction: column;
    width: 100%;
    min-width: 0;
  }

  .main-content {
    flex: 1 1 auto;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 0;
    margin-left: 0;
    padding: 20px 16px 24px;
    overflow-x: hidden;
  }

  .page-header {
    width: 100%;
    min-width: 0;
  }

  .page-header h1 {
    font-size: 18px;
  }

  .content-section {
    width: 100%;
    min-width: 0;
  }

  .page-toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .page-toolbar-actions {
    width: 100%;
    min-width: 0;
  }

  .search-box {
    flex: 1 1 180px;
    width: auto;
  }

  .form-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .full-field {
    grid-column: auto;
  }

  .analytics-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .main-content {
    width: 100%;
    padding: 16px 12px 22px;
  }

  .page-toolbar-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .search-box {
    width: 100%;
    min-height: 42px;
  }

  .form-panel {
    padding: 14px;
  }

  .form-grid input,
  .form-grid select,
  .form-grid textarea {
    font-size: 16px;
  }

  .form-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .analytics-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 480px) {
  .main-content {
    padding: 16px 12px 22px;
  }

  .page-toolbar-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .search-box {
    width: 100%;
    min-height: 42px;
  }

  .form-panel {
    padding: 14px;
  }

  .form-grid input,
  .form-grid select,
  .form-grid textarea {
    font-size: 16px;
  }

  .form-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .analytics-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
