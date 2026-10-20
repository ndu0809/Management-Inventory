<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'

type ItemStatus = 'Baik' | 'Rusak' | 'Tidak dipakai' | 'Dilelang'

type ApiItem = {
  id: number
  name: string | null
  sku: string
  category: string
  quantity: number
  price: number | string
  description: string | null
  status?: ItemStatus
}

const inventory = ref<ApiItem[]>([])
const loading = ref(false)
const errorMessage = ref('')
const selectedStatus = ref<ItemStatus | null>(null)

const statusCards: {
  status: ItemStatus
  label: string
  icon: string
  color: string
  description: string
}[] = [
  {
    status: 'Baik',
    label: 'Barang Baik',
    icon: '✓',
    color: 'good',
    description: 'Barang dalam kondisi baik dan siap digunakan.',
  },
  {
    status: 'Rusak',
    label: 'Barang Rusak',
    icon: '⚠',
    color: 'damaged',
    description: 'Barang yang mengalami kerusakan.',
  },
  {
    status: 'Dilelang',
    label: 'Dilelang',
    icon: '↗',
    color: 'auction',
    description: 'Barang yang berstatus dilelang.',
  },
  {
    status: 'Tidak dipakai',
    label: 'Tidak Dipakai',
    icon: '○',
    color: 'unused',
    description: 'Barang yang sedang tidak digunakan.',
  },
]

async function loadInventory() {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/items')
    const data = response.data

    inventory.value = Array.isArray(data)
      ? data
      : Array.isArray(data?.data)
        ? data.data
        : Array.isArray(data?.items)
          ? data.items
          : []
  } catch (error: unknown) {
    console.error('Gagal memuat analytics:', error)

    const err = error as {
      response?: {
        status?: number
        data?: { message?: string }
      }
    }

    if (err.response?.status === 401 || err.response?.status === 403) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      window.location.href = '/login'
      return
    }

    errorMessage.value =
      err.response?.data?.message || 'Data analytics gagal dimuat. Periksa koneksi backend.'
  } finally {
    loading.value = false
  }
}

function countByStatus(status: ItemStatus): number {
  return inventory.value.filter((item) => item.status === status).length
}

function unitsByStatus(status: ItemStatus): number {
  return inventory.value.filter((item) => item.status?.trim() === status).length
}

const totalUnits = computed(() =>
  inventory.value.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0),
)

const totalInventoryValue = computed(() =>
  inventory.value.reduce(
    (sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.price) || 0),
    0,
  ),
)

const maxChartValue = computed(() =>
  Math.max(0, ...statusCards.map((card) => unitsByStatus(card.status))),
)

const chartData = computed(() =>
  statusCards.map((card) => {
    const units = unitsByStatus(card.status)

    return {
      ...card,
      units,
      percentage: maxChartValue.value > 0 ? (units / maxChartValue.value) * 100 : 0,
    }
  }),
)

const selectedItems = computed(() => {
  if (!selectedStatus.value) return []

  return inventory.value.filter((item) => item.status === selectedStatus.value)
})

const selectedLabel = computed(
  () => statusCards.find((card) => card.status === selectedStatus.value)?.label ?? '',
)

function formatPrice(price: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(price)
}

function openDetails(status: ItemStatus) {
  selectedStatus.value = status
}

function closeDetails() {
  selectedStatus.value = null
}

onMounted(loadInventory)
</script>

<template>
  <section class="analytics-page">
    <header class="analytics-header">
      <div>
        <p class="eyebrow">INVENTORY MANAGEMENT</p>
        <h2>Analytics Inventaris</h2>
        <p class="header-description">Pantau kondisi dan distribusi barang dalam inventaris.</p>
      </div>

      <button type="button" class="refresh-button" :disabled="loading" @click="loadInventory">
        <span>{{ loading ? '↻' : '⟳' }}</span>
        {{ loading ? 'Memuat...' : 'Muat Ulang' }}
      </button>
    </header>

    <p v-if="errorMessage" class="error-message" role="alert">
      {{ errorMessage }}
    </p>

    <div v-if="loading && inventory.length === 0" class="empty-state">
      <span class="loading-spinner"></span>
      <p>Memuat data inventaris...</p>
    </div>

    <template v-else>
      <div class="summary-row">
        <div>
          <span class="summary-label">Total jenis barang</span>
          <strong>{{ inventory.length }}</strong>
          <span class="summary-caption">Entri inventaris</span>
        </div>

        <div>
          <span class="summary-label">Total unit barang</span>
          <strong>{{ totalUnits.toLocaleString('id-ID') }}</strong>
          <span class="summary-caption">Seluruh unit inventaris</span>
        </div>

        <div>
          <span class="summary-label">Nilai inventaris</span>
          <strong class="summary-value">
            {{ formatPrice(totalInventoryValue) }}
          </strong>
          <span class="summary-caption">Estimasi nilai seluruh unit</span>
        </div>
      </div>

      <section class="status-section">
        <div class="section-heading">
          <div>
            <h3>Status Barang</h3>
            <p>Ringkasan kondisi barang berdasarkan data inventaris.</p>
          </div>
        </div>

        <div class="status-grid">
          <article
            v-for="card in statusCards"
            :key="card.status"
            class="status-card"
            :class="`status-${card.color}`"
          >
            <div class="status-card-top">
              <div class="status-icon" aria-hidden="true">
                {{ card.icon }}
              </div>
              <span class="status-indicator"></span>
            </div>

            <p class="status-label">{{ card.label }}</p>
            <strong class="status-number">
              {{ countByStatus(card.status).toLocaleString('id-ID') }}
              <span>jenis</span>
            </strong>

            <p class="status-description">{{ card.description }}</p>

            <button type="button" class="detail-button" @click="openDetails(card.status)">
              Lihat Detail
              <span aria-hidden="true">→</span>
            </button>
          </article>
        </div>
      </section>

      <section class="chart-section">
        <div class="section-heading chart-heading">
          <div>
            <h3>Diagram Batang Status Barang</h3>
            <p>Perbandingan jumlah unit berdasarkan status inventaris.</p>
          </div>

          <span class="chart-badge">Total {{ totalUnits }} unit</span>
        </div>

        <div v-if="inventory.length === 0" class="chart-empty">
          Belum ada data inventaris untuk ditampilkan.
        </div>

        <div v-else class="bar-chart">
          <div class="bar-chart-area">
            <div v-for="item in chartData" :key="item.status" class="bar-column">
              <strong class="bar-value">{{ item.units }}</strong>

              <div class="bar-track-vertical">
                <div
                  class="bar-vertical"
                  :class="`bar-${item.color}`"
                  :style="{
                    height: `${item.percentage}%`,
                    minHeight: item.units > 0 ? '4px' : '0',
                  }"
                ></div>
              </div>

              <span class="bar-label">{{ item.label }}</span>
            </div>
          </div>
        </div>

        <p class="chart-footnote">Data menunjukkan total unit barang pada setiap status.</p>
      </section>
    </template>

    <div
      v-if="selectedStatus"
      class="modal-backdrop"
      @click.self="closeDetails"
      @keydown.esc="closeDetails"
    >
      <section
        class="detail-modal"
        role="dialog"
        aria-modal="true"
        :aria-label="`Detail ${selectedLabel}`"
      >
        <header class="modal-header">
          <div>
            <p class="eyebrow">DETAIL INVENTARIS</p>
            <h3>{{ selectedLabel }}</h3>
            <p class="modal-description">{{ selectedItems.length }} jenis barang ditemukan.</p>
          </div>

          <button
            type="button"
            class="close-button"
            aria-label="Tutup detail"
            @click="closeDetails"
          >
            ×
          </button>
        </header>

        <div v-if="selectedItems.length === 0" class="modal-empty">
          Belum ada barang dengan status ini.
        </div>

        <div v-else class="detail-table-wrapper">
          <table class="detail-table">
            <thead>
              <tr>
                <th>Nama Barang</th>
                <th>SKU</th>
                <th>Kategori</th>
                <th>Jumlah Unit</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="item in selectedItems" :key="item.id">
                <td>{{ item.name || 'Tanpa nama' }}</td>
                <td>{{ item.sku || '-' }}</td>
                <td>{{ item.category || '-' }}</td>
                <td>{{ Number(item.quantity) || 0 }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <footer class="modal-footer">
          <span>
            Total unit:
            <strong>
              {{ selectedItems.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0) }}
            </strong>
          </span>

          <button type="button" class="refresh-button" @click="closeDetails">Tutup</button>
        </footer>
      </section>
    </div>
  </section>
</template>

<style scoped>
.analytics-page {
  color: #202938;
  display: flex;
  flex-direction: column;
  gap: 28px;
  padding: 4px 0 28px;
}

.analytics-header,
.section-heading,
.status-card-top,
.modal-header,
.modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.analytics-header h2 {
  margin: 5px 0 8px;
  font-size: clamp(23px, 3vw, 30px);
  font-weight: 750;
  letter-spacing: -0.8px;
}

.eyebrow {
  margin: 0;
  color: #78869a;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1.5px;
}

.header-description,
.section-heading p,
.modal-description {
  margin: 0;
  color: #8290a2;
  font-size: 13px;
  line-height: 1.6;
}

.refresh-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  min-height: 40px;
  padding: 0 16px;
  border: 1px solid #dce3ec;
  border-radius: 10px;
  background: #fff;
  color: #334155;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition:
    background 0.2s,
    border-color 0.2s;
}

.refresh-button:hover:not(:disabled) {
  background: #f5f8fc;
  border-color: #b9c8da;
}

.refresh-button:disabled {
  cursor: wait;
  opacity: 0.65;
}

.summary-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.summary-row > div {
  display: flex;
  min-width: 0;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  padding: 22px;
  border: 1px solid #e8edf3;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 3px 12px rgb(30 50 80 / 3%);
}

.summary-label {
  color: #758398;
  font-size: 12px;
  font-weight: 600;
}

.summary-row strong {
  color: #202938;
  font-size: clamp(22px, 3vw, 29px);
  font-weight: 750;
  letter-spacing: -0.7px;
  overflow-wrap: anywhere;
}

.summary-row .summary-value {
  font-size: clamp(16px, 2vw, 23px);
}

.summary-caption {
  color: #9aa5b4;
  font-size: 11px;
}

.status-section,
.chart-section {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.section-heading h3,
.modal-header h3 {
  margin: 0 0 6px;
  color: #202938;
  font-size: 17px;
  font-weight: 750;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.status-card {
  display: flex;
  min-width: 0;
  flex-direction: column;
  align-items: flex-start;
  padding: 20px;
  border: 1px solid #e8edf3;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 3px 12px rgb(30 50 80 / 3%);
  transition:
    transform 0.2s,
    box-shadow 0.2s;
}

.status-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgb(30 50 80 / 7%);
}

.status-card-top {
  width: 100%;
  margin-bottom: 20px;
}

.status-icon {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border-radius: 11px;
  font-size: 19px;
  font-weight: 800;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
}

.status-good {
  color: #159568;
}

.status-good .status-icon {
  background: #e4f7ee;
}

.status-damaged {
  color: #d94e4e;
}

.status-damaged .status-icon {
  background: #fff0ed;
}

.status-auction {
  color: #8a61d5;
}

.status-auction .status-icon {
  background: #f1eaff;
}

.status-unused {
  color: #c08a27;
}

.status-unused .status-icon {
  background: #fff5dc;
}

.status-label {
  margin: 0 0 8px;
  color: #69778a;
  font-size: 12px;
  font-weight: 650;
}

.status-number {
  color: #202938;
  font-size: 30px;
  font-weight: 800;
  letter-spacing: -0.8px;
}

.status-number span {
  color: #8c98a8;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0;
}

.status-description {
  min-height: 34px;
  margin: 8px 0 18px;
  color: #8b97a7;
  font-size: 11px;
  line-height: 1.6;
}

.detail-button {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: auto;
  padding: 10px 0 0;
  border: 0;
  border-top: 1px solid #edf0f5;
  background: transparent;
  color: #536a88;
  font-size: 11px;
  font-weight: 750;
  cursor: pointer;
}

.detail-button:hover {
  color: #245ac2;
}

.chart-section {
  padding: 24px;
  border: 1px solid #e8edf3;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 3px 12px rgb(30 50 80 / 3%);
}

.chart-badge {
  flex-shrink: 0;
  padding: 7px 10px;
  border-radius: 8px;
  background: #f1f5fb;
  color: #61748f;
  font-size: 11px;
  font-weight: 750;
}

.bar-chart {
  width: 100%;
  padding: 20px 8px 0;
}

.bar-chart-area {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  align-items: end;
  gap: 24px;
  min-height: 280px;
  padding: 0 12px;
  border-bottom: 1px solid #dfe5ed;
}

.bar-column {
  display: flex;
  min-width: 0;
  height: 280px;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.bar-value {
  color: #344155;
  font-size: 12px;
  font-weight: 750;
}

.bar-track-vertical {
  display: flex;
  width: min(58px, 75%);
  height: 200px;
  align-items: flex-end;
  overflow: hidden;
  border-radius: 8px 8px 0 0;
  background: #f0eaff;
}

.bar-vertical {
  width: 100%;
  border-radius: 8px 8px 0 0;
  background: #8b5cf6;
  transition: height 0.35s ease;
}

.bar-label {
  min-height: 32px;
  color: #647287;
  font-size: 11px;
  text-align: center;
  overflow-wrap: anywhere;
}

.bar-good {
  background: #24ae7b;
}

.bar-damaged {
  background: #ec6868;
}

.bar-auction {
  background: #9270df;
}

.bar-unused {
  background: #e5ad48;
}

@media (max-width: 600px) {
  .bar-chart {
    padding-right: 0;
    padding-left: 0;
  }

  .bar-chart-area {
    gap: 10px;
    padding: 0 2px;
  }

  .bar-track-vertical {
    width: min(42px, 80%);
  }

  .bar-label {
    font-size: 10px;
  }
}

.chart-footnote {
  margin: 4px 0 0;
  color: #9aa5b4;
  font-size: 11px;
  line-height: 1.6;
}

.chart-empty,
.empty-state,
.modal-empty {
  padding: 28px 12px;
  color: #8b97a7;
  font-size: 13px;
  text-align: center;
}

.empty-state {
  display: flex;
  min-height: 160px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.empty-state p {
  margin: 0;
}

.loading-spinner {
  width: 25px;
  height: 25px;
  border: 3px solid #e4eaf2;
  border-top-color: #5279bd;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-message {
  margin: 0;
  padding: 13px 16px;
  border: 1px solid #f4d2d2;
  border-radius: 10px;
  background: #fff4f4;
  color: #b83f3f;
  font-size: 13px;
}

.modal-backdrop {
  position: fixed;
  z-index: 1000;
  inset: 0;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgb(15 23 42 / 48%);
}

.detail-modal {
  width: min(850px, 100%);
  max-height: min(80vh, 700px);
  overflow: auto;
  padding: 24px;
  border: 1px solid #e8edf3;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 20px 70px rgb(15 23 42 / 20%);
}

.modal-header {
  align-items: flex-start;
  margin-bottom: 20px;
}

.modal-header h3 {
  margin-top: 7px;
  font-size: 21px;
}

.close-button {
  display: grid;
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  place-items: center;
  border: 1px solid #e5eaf0;
  border-radius: 9px;
  background: #fff;
  color: #647287;
  font-size: 24px;
  cursor: pointer;
}

.close-button:hover {
  background: #f4f6fa;
}

.detail-table-wrapper {
  width: 100%;
  overflow-x: auto;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  text-align: left;
}

.detail-table th {
  padding: 13px 12px;
  background: #f5f7fb;
  color: #69778a;
  font-weight: 750;
  white-space: nowrap;
}

.detail-table td {
  padding: 14px 12px;
  border-bottom: 1px solid #edf0f5;
  color: #3c485a;
}

.modal-footer {
  margin-top: 22px;
  padding-top: 16px;
  border-top: 1px solid #edf0f5;
  color: #69778a;
  font-size: 12px;
}

.modal-footer strong {
  color: #202938;
}

@media (max-width: 1100px) {
  .status-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .analytics-page {
    gap: 22px;
  }

  .analytics-header {
    align-items: flex-start;
  }

  .analytics-header .refresh-button {
    flex-shrink: 0;
  }

  .summary-row {
    grid-template-columns: 1fr;
  }

  .summary-row > div {
    padding: 18px;
  }

  .status-grid {
    grid-template-columns: 1fr;
  }

  .status-card {
    padding: 18px;
  }

  .status-description {
    min-height: 0;
  }

  .chart-section {
    padding: 18px;
  }

  .chart-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .chart-row {
    grid-template-columns: minmax(90px, 115px) minmax(20px, 1fr) 35px;
    gap: 9px;
  }

  .chart-label {
    font-size: 10px;
  }

  .detail-modal {
    padding: 18px;
  }

  .modal-footer {
    align-items: flex-start;
    flex-wrap: wrap;
  }
}

/* ===== DARK MODE: ANALYTICS ===== */
:global(.dark-theme) .analytics-page,
:global(.dark-theme) .analytics-page * {
  color-scheme: dark;
}

:global(.dark-theme) .analytics-page {
  color: #e5e7eb;
}

:global(.dark-theme) .analytics-page h1,
:global(.dark-theme) .analytics-page h2,
:global(.dark-theme) .analytics-page h3,
:global(.dark-theme) .analytics-page strong {
  color: #f3f4f6;
}

:global(.dark-theme) .analytics-page p,
:global(.dark-theme) .analytics-page label,
:global(.dark-theme) .analytics-page th,
:global(.dark-theme) .analytics-page td {
  color: #d1d5db;
}

:global(.dark-theme) .analytics-page .summary-row > div,
:global(.dark-theme) .analytics-page .status-card,
:global(.dark-theme) .analytics-page .chart-section,
:global(.dark-theme) .analytics-page .detail-modal {
  background-color: #1c1f24;
  border-color: #343941;
  box-shadow: none;
}

:global(.dark-theme) .analytics-page .status-card:hover {
  background-color: #252a31;
  border-color: #4b5563;
}

:global(.dark-theme) .analytics-page input,
:global(.dark-theme) .analytics-page select,
:global(.dark-theme) .analytics-page textarea {
  background-color: #252a31;
  border-color: #414752;
  color: #f3f4f6;
}

:global(.dark-theme) .analytics-page input::placeholder,
:global(.dark-theme) .analytics-page textarea::placeholder {
  color: #9ca3af;
}

:global(.dark-theme) .analytics-page .refresh-button,
:global(.dark-theme) .analytics-page .close-button {
  background-color: #252a31;
  border-color: #414752;
  color: #e5e7eb;
}

:global(.dark-theme) .analytics-page .refresh-button:hover,
:global(.dark-theme) .analytics-page .close-button:hover {
  background-color: #343a43;
}

:global(.dark-theme) .analytics-page .detail-table th {
  background-color: #252a31;
  color: #e5e7eb;
}

:global(.dark-theme) .analytics-page .detail-table td {
  border-bottom-color: #343941;
}

:global(.dark-theme) .analytics-page .bar-chart-area {
  border-bottom-color: #414752;
}

:global(.dark-theme) .analytics-page .bar-value {
  color: #f3f4f6;
}

:global(.dark-theme) .analytics-page .bar-track-vertical {
  background-color: #302844;
}

:global(.dark-theme) .analytics-page .bar-vertical {
  background-color: #a78bfa;
}

:global(.dark-theme) .analytics-page .bar-label,
:global(.dark-theme) .analytics-page .chart-footnote,
:global(.dark-theme) .analytics-page .summary-caption,
:global(.dark-theme) .analytics-page .status-description,
:global(.dark-theme) .analytics-page .chart-empty,
:global(.dark-theme) .analytics-page .empty-state {
  color: #a0a8b6;
}

:global(.dark-theme) .analytics-page .error-message {
  background-color: #3a2224;
  border-color: #713b3b;
  color: #ffb4b4;
}

:global(.dark-theme) .analytics-page .modal-backdrop {
  background-color: rgb(0 0 0 / 70%);
}

:global(.dark-theme) .analytics-page .modal-footer {
  border-top-color: #343941;
  color: #aab2bf;
}
</style>
