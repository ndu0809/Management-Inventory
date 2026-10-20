
<script setup lang="ts">
defineProps<{
  activePage: string
  currentUser: { name?: string; email?: string } | null
  darkTheme: boolean
}>()

const emit = defineEmits<{
  navigate: [page: string]
  'toggle-theme': []
  logout: []
}>()

const menus = [
  { name: 'Data Inventaris', icon: '▤' },
  { name: 'Management Anggota', icon: '♙' },
  { name: 'Analytics', icon: '▧' },
]
</script>

<template>
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-icon">m</div>
      <div class="brand-name">
        <span>management</span>
        <strong>inventaris.</strong>
      </div>
    </div>

    <nav class="menu">
      <button
        v-for="menu in menus"
        :key="menu.name"
        type="button"
        class="menu-item"
        :class="{ active: activePage === menu.name }"
        @click="emit('navigate', menu.name)"
      >
        <span class="menu-icon">{{ menu.icon }}</span>
        <span>{{ menu.name }}</span>
        <span v-if="activePage === menu.name" class="active-mark"></span>
      </button>
    </nav>

    <div class="sidebar-bottom">
      <div class="profile">
        <div class="avatar">
          {{ (currentUser?.name || 'A').charAt(0).toUpperCase() }}
        </div>
        <div class="profile-info">
          <strong>{{ currentUser?.name || 'Administrator' }}</strong>
          <span>{{ currentUser?.email || 'admin@gmail.com' }}</span>
        </div>
      </div>

      <div class="sidebar-options">
        <button
          type="button"
          class="option-button"
          @click="emit('toggle-theme')"
        >
          <span>◐</span>
          <span>Dark Theme</span>
          <span class="toggle" :class="{ enabled: darkTheme }">
            <span></span>
          </span>
        </button>

        <button
          type="button"
          class="option-button"
          @click="emit('logout')"
        >
          <span>↪</span>
          <span>Logout</span>
        </button>
      </div>
    </div>
  </aside>
</template>

<style>
.sidebar {
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 50;
  display: flex;
  flex: 0 0 260px;
  flex-direction: column;
  width: 260px;
  height: 100vh;
  height: 100dvh;
  padding: 25px 16px 15px;
  overflow-x: hidden;
  overflow-y: auto;
  background: var(--page, #fff);
  border-right: 1px solid var(--line, #e8eaf0);
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.brand-icon {
  display: grid;
  place-items: center;
  flex: 0 0 30px;
  width: 30px;
  height: 30px;
  border-radius: 9px;
  color: #d7f58b;
  background: #050505;
  font-size: 19px;
  font-weight: 700;
}

.brand-name,
.profile-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.brand-name {
  line-height: 1.4;
}

.brand-name span {
  color: #969ba5;
  font-size: 11px;
}

.brand-name strong {
  font-size: 13px;
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-top: 32px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  min-height: 42px;
  padding: 9px 10px;
  border: 0;
  border-radius: 10px;
  color: var(--text, #202020);
  background: transparent;
  text-align: left;
  font-size: 12px;
}

.menu-item:hover,
.menu-item.active {
  background: var(--hover, #f1f3f6);
}

.menu-item.active {
  font-weight: 600;
}

.menu-icon {
  flex: 0 0 17px;
  color: #64708c;
  text-align: center;
  font-size: 16px;
}

.active-mark {
  flex: 0 0 3px;
  width: 3px;
  height: 10px;
  margin-left: auto;
  border-radius: 4px;
  background: var(--text, #111);
}

.sidebar-bottom {
  margin-top: auto;
  padding-top: 20px;
}

.profile {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 12px 9px;
  border: 1px solid #e7e9ef;
  border-radius: 13px 13px 0 0;
  background: #f5f6f9;
}

.avatar {
  display: grid;
  place-items: center;
  flex: 0 0 34px;
  width: 34px;
  height: 34px;
  border-radius: 9px;
  color: #276f8d;
  background: #d7f0fa;
  font-size: 13px;
}

.profile-info {
  gap: 5px;
}

.profile-info strong,
.profile-info span {
  overflow: hidden;
  color: #287a9b;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-info strong {
  font-size: 11px;
}

.profile-info span {
  font-size: 9px;
}

.sidebar-options {
  padding: 5px 10px 7px;
  border: 1px solid #e7e9ef;
  border-top: 0;
  border-radius: 0 0 13px 13px;
  background: #f5f6f9;
}

.option-button {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  min-height: 34px;
  padding: 8px 0;
  border: 0;
  color: var(--text, #202020);
  background: transparent;
  text-align: left;
  font-size: 10px;
}

.toggle {
  display: flex;
  align-items: center;
  flex: 0 0 28px;
  width: 28px;
  height: 16px;
  margin-left: auto;
  padding: 2px;
  border-radius: 20px;
  background: #c8cbd1;
}

.toggle > span {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: white;
  transition: transform 0.2s;
}

.toggle.enabled {
  background: #222;
}

.toggle.enabled > span {
  transform: translateX(12px);
}

.dark-theme .profile,
.dark-theme .sidebar-options {
  border-color: #333740;
  background: #22252a;
}

.dark-theme .profile-info strong,
.dark-theme .profile-info span {
  color: #a7d9eb;
}

@media (max-width: 1024px) {
  .sidebar {
    width: 220px;
    padding-right: 12px;
    padding-left: 12px;
  }
}

@media (max-width: 760px) {
  .sidebar {
    position: relative;
    inset: auto;
    z-index: 20;
    flex: 0 0 auto;
    width: 100%;
    height: auto;
    padding: 14px 16px 12px;
    overflow: visible;
    border-right: 0;
    border-bottom: 1px solid var(--line, #e8eaf0);
  }

  .menu {
    flex-direction: row;
    gap: 7px;
    margin-top: 14px;
    padding-bottom: 2px;
    overflow-x: auto;
  }

  .menu-item {
    flex: 0 0 auto;
    width: auto;
    white-space: nowrap;
  }

  .sidebar-bottom {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    margin-top: 12px;
    padding-top: 0;
  }

  .profile {
    min-width: 0;
    padding: 7px;
    border: 1px solid var(--line, #e8eaf0);
    border-radius: 10px;
    background: var(--surface, #fff);
  }

  .sidebar-options {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 12px;
    padding: 0;
    border: 0;
    background: transparent;
  }

  .option-button {
    width: auto;
    white-space: nowrap;
  }
}

@media (max-width: 480px) {
  .sidebar {
    padding: 12px;
  }

  .sidebar-bottom {
    grid-template-columns: minmax(0, 1fr);
  }

  .sidebar-options {
    justify-content: space-between;
  }
}
</style>
