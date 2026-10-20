<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import api from '../services/api'

type ItemStatus = 'Baik' | 'Rusak' | 'Tidak dipakai' | 'Dilelang'

type Inventory = {
  id: number
  inventory_id: string
  item: string
  type: string
  serial_number: string
  specification: string
  status: ItemStatus
  assigned: string
  member_id: number | null
  department: string
  quantity: number
  price: number
  description: string
}

type ApiMember = {
  id: number
  member_code: string
  name: string
  email: string
  phone?: string | null
  position?: string | null
}

type ApiItem = {
  id: number
  name: string | null
  sku: string
  category: string
  quantity: number
  price: number | string
  description: string | null
  status?: ItemStatus
  assigned?: string | null
  member_id?: number | null
  member?: ApiMember | null
  department?: string | null
  serial_number?: string | null
  specification?: string | null
}

type ItemForm = {
  name: string
  sku: string
  category: string
  description: string
  status: ItemStatus
  assigned: string
  department: string
  serial_number: string
}

const searchQuery = ref('')
const page = ref(1)
const itemsPerPage = ref(5)
const inventory = ref<Inventory[]>([])
const members = ref<ApiMember[]>([])
const selectedMemberId = ref<number | null>(null)
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const editingId = ref<number | null>(null)
const openAction = ref<number | null>(null)
const errorMessage = ref('')
const successMessage = ref('')

const emptyForm = (): ItemForm => ({
  name: '',
  sku: '',
  category: '',
  description: '',
  status: 'Baik',
  assigned: '',
  department: '',
  serial_number: '',
})

const form = ref<ItemForm>(emptyForm())

function mapApiItem(item: ApiItem): Inventory {
  const statuses: ItemStatus[] = ['Baik', 'Rusak', 'Tidak dipakai', 'Dilelang']

  return {
    member_id: item.member_id ?? item.member?.id ?? null,
    id: item.id,
    inventory_id: item.sku || '—',
    item: item.name || '—',
    type: item.category || '—',
    serial_number: item.serial_number || '—',
    specification: item.specification || item.description || '—',
    status: statuses.includes(item.status as ItemStatus) ? (item.status as ItemStatus) : 'Baik',
    assigned: item.member?.name || item.assigned || '—',
    department: item.department || '—',
    quantity: Number(item.quantity) || 0,
    price: Number(item.price) || 0,
    description: item.description || item.specification || '',
  }
}

async function loadInventory() {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/items')
    const data = response.data

    const rows: ApiItem[] = Array.isArray(data)
      ? data
      : Array.isArray(data?.data)
        ? data.data
        : Array.isArray(data?.items)
          ? data.items
          : []

    inventory.value = rows.map(mapApiItem)
  } catch (error: unknown) {
    console.error('Gagal memuat inventaris:', error)

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
      err.response?.data?.message || 'Data gagal dimuat. Periksa server Laravel dan koneksi API.'
  } finally {
    loading.value = false
  }
}

const filteredInventory = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()

  if (!query) return inventory.value

  return inventory.value.filter((item) =>
    [
      item.inventory_id,
      item.item,
      item.type,
      item.serial_number,
      item.specification,
      item.status,
      item.assigned,
      item.department,
    ].some((value) => String(value).toLowerCase().includes(query)),
  )
})

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredInventory.value.length / itemsPerPage.value)),
)

const paginatedInventory = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  return filteredInventory.value.slice(start, start + itemsPerPage.value)
})

watch([searchQuery, itemsPerPage], () => {
  page.value = 1
  openAction.value = null
})

watch(totalPages, (total) => {
  if (page.value > total) page.value = total
})

function statusClass(status: string) {
  return {
    'status-good': status === 'Baik',
    'status-damaged': status === 'Rusak',
    'status-unused': status === 'Tidak dipakai',
    'status-auction': status === 'Dilelang',
  }
}

function openAddForm() {
  editingId.value = null
  form.value = emptyForm()
  selectedMemberId.value = null
  showForm.value = true
  errorMessage.value = ''
  successMessage.value = ''
}

function editItem(item: Inventory) {
  editingId.value = item.id
  selectedMemberId.value = item.member_id

  form.value = {
    name: item.item === '—' ? '' : item.item,
    sku: item.inventory_id === '—' ? '' : item.inventory_id,
    category: item.type === '—' ? '' : item.type,
    description: item.description,
    status: item.status,
    assigned: item.assigned === '—' ? '' : item.assigned,
    department: item.department === '—' ? '' : item.department,
    serial_number: item.serial_number === '—' ? '' : item.serial_number,
  }

  showForm.value = true
  openAction.value = null
  errorMessage.value = ''
  successMessage.value = ''
}

function closeForm() {
  if (saving.value) return

  showForm.value = false
  editingId.value = null
  form.value = emptyForm()
  errorMessage.value = ''
}

async function saveItem() {
  if (saving.value) return

  errorMessage.value = ''
  successMessage.value = ''

  const existingItem = inventory.value.find((item) => item.id === editingId.value)

  const selectedMember = members.value.find((member) => member.id === selectedMemberId.value)

  const payload = {
    name: form.value.name.trim(),
    sku: form.value.sku.trim(),
    category: form.value.category.trim(),
    quantity: existingItem?.quantity ?? 0,
    price: existingItem?.price ?? 0,
    description: form.value.description.trim() || null,
    status: form.value.status,
    assigned: form.value.assigned.trim() || null,
    department: form.value.department.trim() || null,
    serial_number: form.value.serial_number.trim() || null,
    specification: form.value.description.trim() || null,
  }

  if (!payload.name || !payload.sku || !payload.category) {
    errorMessage.value = 'Nama barang, SKU, dan kategori wajib diisi.'
    return
  }

  saving.value = true

  try {
    const isEditing = editingId.value !== null

    if (isEditing) {
      await api.put(`/items/${editingId.value}`, payload)
    } else {
      await api.post('/items', payload)
    }

    await loadInventory()

    if (errorMessage.value) return

    successMessage.value = isEditing
      ? 'Data inventaris berhasil diperbarui.'
      : 'Data inventaris berhasil ditambahkan.'

    showForm.value = false
    editingId.value = null
    form.value = emptyForm()
    openAction.value = null
    page.value = 1
  } catch (error: unknown) {
    console.error('Gagal menyimpan inventaris:', error)

    const err = error as {
      response?: {
        status?: number
        data?: {
          message?: string
          errors?: Record<string, string[]>
        }
      }
    }

    if (err.response?.status === 401 || err.response?.status === 403) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      window.location.href = '/login'
      return
    }

    const validationErrors = err.response?.data?.errors

    errorMessage.value = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || 'Data gagal disimpan. Periksa input dan koneksi Laravel.'
  } finally {
    saving.value = false
  }
}

async function removeItem(id: number) {
  if (!window.confirm('Yakin ingin menghapus data inventaris ini?')) return

  errorMessage.value = ''
  successMessage.value = ''

  try {
    await api.delete(`/items/${id}`)

    inventory.value = inventory.value.filter((item) => item.id !== id)
    openAction.value = null
    successMessage.value = 'Data inventaris berhasil dihapus.'
  } catch (error: unknown) {
    console.error('Gagal menghapus inventaris:', error)

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

    errorMessage.value = err.response?.data?.message || 'Data gagal dihapus dari server.'

    openAction.value = null
  }
}

async function loadMembers() {
  try {
    const response = await api.get('/members')
    const data = response.data

    const rows: ApiMember[] = Array.isArray(data)
      ? data
      : Array.isArray(data?.data)
        ? data.data
        : Array.isArray(data?.members)
          ? data.members
          : []

    members.value = rows
  } catch (error) {
    console.error('Gagal memuat data anggota:', error)
  }
}

onMounted(() => {
  loadInventory()
  loadMembers()
})
</script>

<template>
  <section class="content-section">
    <div class="page-toolbar">
      <h2>Data Inventaris</h2>

      <div class="page-toolbar-actions">
        <label class="search-box">
          <span>⌕</span>
          <input v-model="searchQuery" type="search" placeholder="Cari inventaris..." />
        </label>

        <button
          type="button"
          class="primary-button"
          :disabled="saving"
          @click="showForm ? closeForm() : openAddForm()"
        >
          {{ showForm ? 'Tutup Form' : '+ Tambah Data' }}
        </button>
      </div>
    </div>

    <p v-if="errorMessage" class="notice error-notice" role="alert">
      {{ errorMessage }}
    </p>

    <p v-if="successMessage" class="notice success-notice" role="status">
      {{ successMessage }}
    </p>

    <form v-if="showForm" class="form-panel" @submit.prevent="saveItem">
      <h2>
        {{ editingId !== null ? 'Edit Inventaris' : 'Tambah Inventaris' }}
      </h2>

      <div class="form-grid">
        <label>
          Nama barang *
          <input v-model="form.name" required maxlength="255" placeholder="Contoh: Laptop Lenovo" />
        </label>

        <label>
          Inventaris ID / SKU *
          <input v-model="form.sku" required maxlength="100" placeholder="Contoh: INV-001" />
        </label>

        <label>
          Type / Kategori *
          <input v-model="form.category" required maxlength="100" placeholder="Contoh: Laptop" />
        </label>

        <label>
          Serial Number
          <input v-model="form.serial_number" maxlength="255" placeholder="Nomor seri barang" />
        </label>

        <label>
          Status barang
          <select v-model="form.status" required>
            <option value="Baik">Baik</option>
            <option value="Rusak">Rusak</option>
            <option value="Tidak dipakai">Tidak dipakai</option>
            <option value="Dilelang">Dilelang</option>
          </select>
        </label>

        <label>
          Assign / Penanggung jawab
          <select v-model="selectedMemberId">
            <option :value="null">Belum ditugaskan</option>

            <option v-for="member in members" :key="member.id" :value="member.id">
              {{ member.name }}
            </option>
          </select>
        </label>

        <label>
          Department
          <input v-model="form.department" maxlength="255" placeholder="Contoh: IT" />
        </label>

        <label class="full-field">
          Spesifikasi / Deskripsi
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Keterangan barang (opsional)"
          ></textarea>
        </label>
      </div>

      <div class="form-actions">
        <button type="button" class="secondary-button" :disabled="saving" @click="closeForm">
          Batal
        </button>

        <button type="submit" class="primary-button" :disabled="saving">
          {{ saving ? 'Menyimpan...' : editingId !== null ? 'Simpan Perubahan' : 'Simpan Data' }}
        </button>
      </div>
    </form>

    <div v-if="loading" class="loading-state">Memuat data inventaris dari server...</div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Inventaris ID</th>
            <th>Barang</th>
            <th>Type</th>
            <th>Serial Number</th>
            <th>Spesifikasi</th>
            <th>Status</th>
            <th>Assign</th>
            <th>Department</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="(item, index) in paginatedInventory" :key="item.id">
            <td>{{ (page - 1) * itemsPerPage + index + 1 }}.</td>
            <td>{{ item.inventory_id }}</td>
            <td>{{ item.item }}</td>
            <td>{{ item.type }}</td>
            <td>{{ item.serial_number }}</td>
            <td>{{ item.specification }}</td>

            <td>
              <span class="status-badge" :class="statusClass(item.status)">
                {{ item.status }}
              </span>
            </td>

            <td>{{ item.assigned }}</td>
            <td>{{ item.department }}</td>

            <td>
              <div class="row-actions">
                <button type="button" class="secondary-button" @click="editItem(item)">Edit</button>

                <button type="button" class="danger-button" @click="removeItem(item.id)">
                  Hapus
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="!loading && paginatedInventory.length === 0">
            <td colspan="10" class="no-data">
              {{
                searchQuery
                  ? 'Tidak ada data yang cocok dengan pencarian.'
                  : 'Belum ada data inventaris.'
              }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <footer class="pagination-footer">
      <div>
        Menampilkan
        <select v-model.number="itemsPerPage" aria-label="Jumlah baris">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="20">20</option>
        </select>
        data per halaman
      </div>

      <div class="pagination">
        <button type="button" :disabled="page <= 1" @click="page = Math.max(1, page - 1)">
          Sebelumnya
        </button>

        <button
          v-for="p in totalPages"
          :key="p"
          type="button"
          :class="{ current: page === p }"
          @click="page = p"
        >
          {{ p }}
        </button>

        <button
          type="button"
          :disabled="page >= totalPages"
          @click="page = Math.min(totalPages, page + 1)"
        >
          Berikutnya
        </button>
      </div>
    </footer>
  </section>
</template>
