
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'

type ApiMember = {
  id: number
  member_code?: string | null
  name: string | null
  email?: string | null
  phone?: string | null
  address?: string | null
  position: string | null
  department?: string | null
}

type MemberForm = {
  name: string
  position: string
  department: string
}

const members = ref<ApiMember[]>([])
const searchQuery = ref('')
const membersLoading = ref(false)
const memberSaving = ref(false)
const membersError = ref('')
const memberSuccess = ref('')
const showMemberForm = ref(false)
const memberEditingId = ref<number | null>(null)
const openActionId = ref<number | null>(null)
const page = ref(1)
const itemsPerPage = ref(5)

const emptyForm = (): MemberForm => ({
  name: '',
  position: '',
  department: '',
})

const memberForm = ref<MemberForm>(emptyForm())

const filteredMembers = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()

  if (!query) return members.value

  return members.value.filter((member) =>
    [
      member.name,
      member.position,
      member.department,
    ].some((value) =>
      String(value ?? '').toLowerCase().includes(query),
    ),
  )
})

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredMembers.value.length / itemsPerPage.value)),
)

const paginatedMembers = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  return filteredMembers.value.slice(start, start + itemsPerPage.value)
})

async function loadMembers() {
  membersLoading.value = true
  membersError.value = ''

  try {
    const response = await api.get('/members')
    const data = response.data

    members.value = Array.isArray(data)
      ? data
      : Array.isArray(data?.data)
        ? data.data
        : Array.isArray(data?.members)
          ? data.members
          : []

    openActionId.value = null
    page.value = Math.min(page.value, totalPages.value)
  } catch (error: unknown) {
    console.error('Gagal memuat anggota:', error)

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

    membersError.value =
      err.response?.data?.message || 'Data anggota gagal dimuat.'
  } finally {
    membersLoading.value = false
  }
}

function openMemberForm(member?: ApiMember) {
  memberEditingId.value = member?.id ?? null

  memberForm.value = member
    ? {
        name: member.name ?? '',
        position: member.position ?? '',
        department: member.department ?? '',
      }
    : emptyForm()

  membersError.value = ''
  memberSuccess.value = ''
  openActionId.value = null
  showMemberForm.value = true
}

function closeMemberForm() {
  if (memberSaving.value) return

  showMemberForm.value = false
  memberEditingId.value = null
  memberForm.value = emptyForm()
  membersError.value = ''
}

async function saveMember() {
  if (memberSaving.value) return

  membersError.value = ''
  memberSuccess.value = ''

  const name = memberForm.value.name.trim()
  const position = memberForm.value.position.trim()
  const department = memberForm.value.department.trim()

  if (!name || !position || !department) {
    membersError.value = 'Nama, jabatan, dan department wajib diisi.'
    return
  }

  const existingMember = members.value.find(
    (member) => member.id === memberEditingId.value,
  )

  const payload = {
    member_code: existingMember?.member_code ?? '',
    name,
    email: existingMember?.email ?? '',
    phone: existingMember?.phone ?? null,
    address: existingMember?.address ?? null,
    position,
    department,
  }

  memberSaving.value = true

  try {
    const id = memberEditingId.value

    if (id !== null) {
      await api.put(`/members/${id}`, payload)
    } else {
      await api.post('/members', payload)
    }

    await loadMembers()

    if (membersError.value) return

    memberSuccess.value =
      id !== null
        ? 'Anggota berhasil diperbarui.'
        : 'Anggota berhasil ditambahkan.'

    showMemberForm.value = false
    memberEditingId.value = null
    memberForm.value = emptyForm()
    page.value = 1
  } catch (error: unknown) {
    console.error('Gagal menyimpan anggota:', error)

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

    membersError.value = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message ||
        'Gagal menyimpan anggota. Periksa koneksi backend.'
  } finally {
    memberSaving.value = false
  }
}

async function deleteMember(member: ApiMember) {
  if (memberSaving.value) return

  const confirmed = window.confirm(
    `Yakin ingin menghapus anggota "${member.name ?? 'ini'}"?`,
  )

  if (!confirmed) return

  membersError.value = ''
  memberSuccess.value = ''
  openActionId.value = null

  try {
    await api.delete(`/members/${member.id}`)
    await loadMembers()

    if (!membersError.value) {
      memberSuccess.value = 'Anggota berhasil dihapus.'
    }
  } catch (error: unknown) {
    console.error('Gagal menghapus anggota:', error)

    const err = error as {
      response?: {
        status?: number
        data?: { message?: string }
      }
    }

    membersError.value =
      err.response?.data?.message || 'Gagal menghapus anggota.'
  }
}

function toggleAction(id: number) {
  openActionId.value = openActionId.value === id ? null : id
}

function changePage(nextPage: number) {
  page.value = Math.max(1, Math.min(totalPages.value, nextPage))
}

onMounted(loadMembers)
</script>

<template>
  <section class="content-section members-page">
    <header class="page-toolbar members-header">
      <h2>Management Anggota</h2>

      <div class="page-toolbar-actions members-toolbar">
        <label class="search-box members-search">
          <svg
            viewBox="0 0 24 24"
            width="19"
            height="19"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            aria-hidden="true"
          >
            <circle cx="10.8" cy="10.8" r="6.8" />
            <path d="m16 16 4.2 4.2" />
          </svg>

          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search Anggota"
            aria-label="Cari anggota"
          />

          <button
            type="button"
            class="filter-button"
            aria-label="Reset pencarian"
            @click="searchQuery = ''"
          >
            <svg
              viewBox="0 0 24 24"
              width="19"
              height="19"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              aria-hidden="true"
            >
              <path d="M4 6h16M7 12h10m-7 6h4" />
            </svg>
          </button>
        </label>

        <button
          type="button"
          class="primary-button add-member-button"
          :disabled="memberSaving"
          @click="openMemberForm()"
        >
          <span class="plus-icon">+</span>
          Tambah Anggota
        </button>
      </div>
    </header>

    <div v-if="memberSuccess" class="members-notice success" role="status">
      {{ memberSuccess }}
    </div>

    <div v-if="membersError" class="members-notice error" role="alert">
      {{ membersError }}
    </div>

    <section v-if="showMemberForm" class="member-form-panel">
      <div class="member-form-heading">
        <h3>
          {{ memberEditingId !== null ? 'Edit Anggota' : 'Tambah Anggota' }}
        </h3>

        <button
          type="button"
          class="close-form-button"
          :disabled="memberSaving"
          aria-label="Tutup form"
          @click="closeMemberForm"
        >
          ×
        </button>
      </div>

      <form class="member-form" @submit.prevent="saveMember">
        <label>
          Nama
          <input
            v-model="memberForm.name"
            required
            maxlength="255"
            placeholder="Nama lengkap"
          />
        </label>

        <label>
          Jabatan
          <input
            v-model="memberForm.position"
            required
            maxlength="255"
            placeholder="Contoh: Senior Software Engineer"
          />
        </label>

        <label>
          Department
          <input
            v-model="memberForm.department"
            required
            maxlength="255"
            placeholder="Contoh: Technology"
          />
        </label>

        <div class="member-form-actions">
          <button
            type="button"
            class="cancel-button"
            :disabled="memberSaving"
            @click="closeMemberForm"
          >
            Batal
          </button>

          <button
            type="submit"
            class="primary-button add-member-button"
            :disabled="memberSaving"
          >
            {{ memberSaving ? 'Menyimpan...' : 'Simpan Anggota' }}
          </button>
        </div>
      </form>
    </section>

    <div v-if="membersLoading" class="members-loading">
      Memuat data anggota...
    </div>

    <div class="table-wrapper members-table-wrap">
      <table class="data-table members-table">
        <thead>
          <tr>
            <th class="number-column">No</th>
            <th>Nama</th>
            <th>Jabatan</th>
            <th>Department</th>
            <th class="action-column">Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="(member, index) in paginatedMembers" :key="member.id">
            <td>{{ (page - 1) * itemsPerPage + index + 1 }}.</td>
            <td class="member-name">{{ member.name || '—' }}</td>
            <td>{{ member.position || '—' }}</td>
            <td>{{ member.department || '—' }}</td>

            <td class="action-cell">
              <div class="member-action-wrap">
                <button
                  type="button"
                  class="more-button"
                  :aria-label="`Aksi untuk ${member.name ?? 'anggota'}`"
                  :aria-expanded="openActionId === member.id"
                  @click="toggleAction(member.id)"
                >
                  <span></span>
                  <span></span>
                  <span></span>
                </button>

                <div v-if="openActionId === member.id" class="action-menu">
                  <button
                    type="button"
                    @click="openMemberForm(member)"
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    class="delete-action"
                    @click="deleteMember(member)"
                  >
                    Hapus
                  </button>
                </div>
              </div>
            </td>
          </tr>

          <tr v-if="!membersLoading && paginatedMembers.length === 0">
            <td colspan="5" class="empty-table">
              {{
                membersError
                  ? 'Data anggota belum dapat ditampilkan.'
                  : searchQuery
                    ? 'Tidak ada anggota yang cocok dengan pencarian.'
                    : 'Belum ada data anggota.'
              }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <footer class="pagination-footer members-pagination">
      <div class="items-per-page">
        <span>Showing</span>

        <select
          v-model.number="itemsPerPage"
          aria-label="Jumlah anggota per halaman"
          @change="page = 1"
        >
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="20">20</option>
        </select>

        <span>items per page</span>
      </div>

      <nav class="pagination pagination-controls" aria-label="Navigasi halaman">
        <button
          type="button"
          :disabled="page <= 1"
          @click="changePage(page - 1)"
        >
          Previous
        </button>

        <button
          v-for="p in totalPages"
          :key="p"
          type="button"
          :class="{ active: page === p }"
          @click="changePage(p)"
        >
          {{ p }}
        </button>

        <button
          type="button"
          :disabled="page >= totalPages"
          @click="changePage(page + 1)"
        >
          Next
        </button>
      </nav>
    </footer>
  </section>
</template>

<style scoped>
.members-page {
  width: 100%;
  min-width: 0;
  color: #000;
}

/* HEADER */
.members-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: nowrap;
  min-width: 0;
}

.members-header h2 {
  flex-shrink: 0;
  margin: 0;
  font-size: inherit;
  font-weight: inherit;
}

/* SEARCH DAN TOMBOL TAMBAH SELALU SEJAJAR */
.members-toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-direction: row;
  flex-wrap: nowrap;
  gap: 10px;
  min-width: 0;
}

.members-search {
  display: flex;
  align-items: center;
  flex: 1 1 220px;
  width: 220px;
  min-width: 150px;
  gap: 8px;
}

.members-search input {
  flex: 1;
  width: 100%;
  min-width: 0;
  border: 0;
  outline: none;
  background: transparent;
}

.filter-button {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  padding: 2px;
  border: 0;
  background: transparent;
  color: #183e52;
  cursor: pointer;
}

.add-member-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  white-space: nowrap;
}

.plus-icon {
  font-size: 20px;
  font-weight: 300;
  line-height: 1;
}

/* NOTIFIKASI */
.members-notice {
  margin-bottom: 16px;
  padding: 12px 14px;
  border-radius: 8px;
  font-size: 13px;
}

.members-notice.success {
  background: #eaf8ee;
  color: #176534;
}

.members-notice.error {
  background: #fff0f0;
  color: #b42318;
}

.members-loading {
  padding: 14px 0;
  color: #6b7280;
  font-size: 13px;
}

/* TABEL */
.members-table-wrap {
  width: 100%;
  min-width: 0;
  overflow-x: auto;
}

.members-table {
  width: 100%;
  text-align: left;
}

.members-table .number-column {
  width: 8%;
}

.members-table .action-column {
  width: 100px;
  text-align: center;
}

.members-table td:first-child {
  white-space: nowrap;
}

.members-table .member-name {
  font-weight: 500;
}

.members-table .action-cell {
  text-align: center;
}

.member-action-wrap {
  position: relative;
  display: inline-flex;
  justify-content: center;
}

.more-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 3px;
  width: 35px;
  height: 35px;
  padding: 0;
  border: 1px solid #a4a4a4;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
}

.more-button:hover {
  background: #f6f6f6;
}

.more-button span {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #333;
}

.action-menu {
  position: absolute;
  z-index: 5;
  top: 40px;
  right: 0;
  display: flex;
  flex-direction: column;
  width: 130px;
  padding: 5px;
  border: 1px solid #e7e7e7;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 8px 25px #00000012;
}

.action-menu button {
  padding: 10px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #252525;
  text-align: left;
  font-size: 13px;
  cursor: pointer;
}

.action-menu button:hover {
  background: #f5f5f5;
}

.action-menu .delete-action {
  color: #c62828;
}

.members-table .empty-table {
  padding: 30px 12px;
  color: #777;
  text-align: center;
}

/* PAGINATION */
.members-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  flex-wrap: wrap;
}

.items-per-page {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #505050;
  font-size: 13px;
  white-space: nowrap;
}

.items-per-page select {
  max-width: 100%;
  padding: 6px 10px;
  border: 1px solid #d8d8d8;
  border-radius: 8px;
  background: #fff;
  color: #333;
}

.pagination-controls {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

/* FORM TAMBAH DAN EDIT */
.member-form-panel {
  margin-bottom: 24px;
  padding: 20px;
  border: 1px solid #ececec;
  border-radius: 12px;
  background: #fff;
}

.member-form-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

.member-form-heading h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 650;
}

.close-form-button {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  padding: 0;
  border: 1px solid #e4e4e4;
  border-radius: 8px;
  background: #fff;
  color: #333;
  font-size: 22px;
  cursor: pointer;
}

.member-form {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.member-form label {
  display: flex;
  flex-direction: column;
  gap: 7px;
  min-width: 0;
  color: #333;
  font-size: 13px;
  font-weight: 550;
}

.member-form input {
  box-sizing: border-box;
  width: 100%;
  min-width: 0;
  min-height: 38px;
  padding: 8px 11px;
  border: 1px solid #dedede;
  border-radius: 8px;
  outline: none;
  background: #fff;
  color: #222;
  font-size: 13px;
}

.member-form input:focus {
  border-color: #777;
  box-shadow: 0 0 0 3px #00000008;
}

.member-form-actions {
  display: flex;
  grid-column: 1 / -1;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 4px;
}

.cancel-button {
  padding: 8px 14px;
  border: 1px solid #dedede;
  border-radius: 8px;
  background: #fff;
  color: #333;
  font-size: 13px;
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* TABLET */
@media (max-width: 900px) {
  .members-header {
    align-items: stretch;
    flex-direction: column;
    gap: 12px;
  }

  .members-toolbar {
    display: flex;
    width: 100%;
    flex-direction: row;
    flex-wrap: nowrap;
  }

  .members-search {
    flex: 1 1 auto;
    width: auto;
    min-width: 0;
  }

  .members-toolbar .add-member-button {
    flex: 0 0 auto;
    width: auto;
  }

  .member-form {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* HP */
@media (max-width: 600px) {
  .members-toolbar {
    align-items: center;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 8px;
  }

  .members-search {
    flex: 1 1 0;
    width: auto;
    min-width: 0;
    gap: 6px;
  }

  .members-search input {
    font-size: 12px;
  }

  .members-toolbar .add-member-button {
    flex: 0 0 auto;
    width: auto;
    padding: 0 10px;
    gap: 5px;
    font-size: 12px;
  }

  .plus-icon {
    font-size: 18px;
  }

  .members-pagination {
    align-items: flex-start;
    flex-direction: column;
  }

  .pagination-controls {
    max-width: 100%;
  }

  .member-form {
    grid-template-columns: 1fr;
  }

  .member-form-panel {
    padding: 16px;
  }
}

/* DARK MODE */
:global(.dark-theme) .members-page,
:global(.dark-theme) .members-page * {
  color-scheme: dark;
}

:global(.dark-theme) .members-page {
  color: #e5e7eb;
}

:global(.dark-theme) .members-page h2,
:global(.dark-theme) .members-page h3 {
  color: #f3f4f6;
}

:global(.dark-theme) .members-page p,
:global(.dark-theme) .members-page label,
:global(.dark-theme) .members-page th,
:global(.dark-theme) .members-page td {
  color: #d1d5db;
}

:global(.dark-theme) .members-page input,
:global(.dark-theme) .members-page select,
:global(.dark-theme) .members-page textarea {
  color: #f3f4f6;
  background-color: #252a31;
  border-color: #414752;
}

:global(.dark-theme) .members-page input::placeholder,
:global(.dark-theme) .members-page textarea::placeholder {
  color: #9ca3af;
}

:global(.dark-theme) .members-page .members-search,
:global(.dark-theme) .members-page .member-form-panel,
:global(.dark-theme) .members-page .action-menu {
  background-color: #1c1f24;
  border-color: #343941;
}

:global(.dark-theme) .members-page .members-table th {
  background-color: #252a31;
  border-bottom-color: #414752;
}

:global(.dark-theme) .members-page .members-table td {
  border-bottom-color: #343941;
}

:global(.dark-theme) .members-page .members-table tbody tr:hover {
  background-color: #252a31;
}

:global(.dark-theme) .members-page .more-button,
:global(.dark-theme) .members-page .close-form-button,
:global(.dark-theme) .members-page .cancel-button {
  background-color: #252a31;
  border-color: #414752;
  color: #e5e7eb;
}

:global(.dark-theme) .members-page .more-button span {
  background-color: #e5e7eb;
}

:global(.dark-theme) .members-page .action-menu button {
  color: #e5e7eb;
}

:global(.dark-theme) .members-page .action-menu button:hover,
:global(.dark-theme) .members-page .pagination-controls button:hover:not(:disabled) {
  background-color: #343a43;
}

:global(.dark-theme) .members-page .pagination-controls button.active {
  background-color: #4b5563;
  color: #fff;
}

:global(.dark-theme) .members-page .members-loading,
:global(.dark-theme) .members-page .empty-table {
  color: #9ca3af;
}

:global(.dark-theme) .members-page .members-notice.success {
  background-color: #193525;
  border-color: #285c3b;
  color: #a7f3bd;
}

:global(.dark-theme) .members-page .members-notice.error {
  background-color: #3a2224;
  border-color: #713b3b;
  color: #ffb4b4;
}
</style>
