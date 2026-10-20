
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Anggota</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #1f2937;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1 { margin-bottom: 8px; }

        .subtitle {
            color: #64748b;
            margin-bottom: 24px;
        }

        .panel {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px #0000000a;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .full { grid-column: 1 / -1; }

        button {
            border: 0;
            border-radius: 7px;
            padding: 10px 15px;
            cursor: pointer;
            font-weight: bold;
        }

        .primary { background: #2563eb; color: white; }
        .secondary { background: #e2e8f0; color: #1e293b; }
        .danger { background: #fee2e2; color: #b91c1c; }
        .edit { background: #dbeafe; color: #1d4ed8; }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th { background: #f8fafc; }

        #message {
            margin-bottom: 16px;
            white-space: pre-wrap;
        }

        .error { color: #b91c1c; }
        .success { color: #15803d; }

        @media (max-width: 650px) {
            body { padding: 16px; }
            .form-grid { grid-template-columns: 1fr; }
            .full { grid-column: auto; }
            .panel { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Management Anggota</h1>
    <p class="subtitle">Kelola data anggota inventory dalam satu halaman.</p>

    <div id="message" role="status" aria-live="polite"></div>

    <section class="panel">
        <h2 id="formTitle">Tambah Anggota</h2>

        <form id="memberForm">
            <div class="form-grid">
                <div>
                    <label for="member_code">Kode Anggota *</label>
                    <input id="member_code" name="member_code" required maxlength="255">
                </div>

                <div>
                    <label for="name">Nama *</label>
                    <input id="name" name="name" required maxlength="255">
                </div>

                <div>
                    <label for="email">Email *</label>
                    <input id="email" name="email" type="email" required maxlength="255">
                </div>

                <div>
                    <label for="phone">Telepon</label>
                    <input id="phone" name="phone" maxlength="30">
                </div>

                <div>
                    <label for="position">Jabatan</label>
                    <input id="position" name="position" maxlength="255">
                </div>

                <div>
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address"></textarea>
                </div>

                <div class="full actions">
                    <button class="primary" id="saveButton" type="submit">
                        Simpan Anggota
                    </button>
                    <button class="secondary" id="cancelButton" type="button" hidden>
                        Batal Edit
                    </button>
                    <button class="secondary" id="refreshButton" type="button">
                        Muat Ulang
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Anggota</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th>Jabatan</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody id="memberTable">
                    <tr><td colspan="6">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    const API_URL = '/api/members';
    const form = document.getElementById('memberForm');
    const table = document.getElementById('memberTable');
    const message = document.getElementById('message');

    let editingId = null;
    let members = [];

    function getToken() {
        // Gunakan nama key yang sama dengan penyimpanan token
        // pada halaman login project lu.
        return localStorage.getItem('token');
    }

    function showMessage(text, type = 'success') {
        message.textContent = text;
        message.className = type;
    }

    async function apiRequest(url, options = {}) {
        const token = getToken();

        if (!token) {
            throw new Error(
                'Token login tidak ditemukan. Silakan login terlebih dahulu.'
            );
        }

        const response = await fetch(url, {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                ...options.headers
            }
        });

        const data = await response.json().catch(() => ({}));

        if (response.status === 401 || response.status === 403) {
            throw new Error('Akses ditolak. Periksa token dan hak akses akun.');
        }

        if (!response.ok) {
            const validationErrors = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : '';

            throw new Error(
                validationErrors || data.message || 'Permintaan gagal.'
            );
        }

        return data;
    }

    async function loadMembers() {
        table.innerHTML = '<tr><td colspan="6">Memuat data...</td></tr>';

        try {
            const data = await apiRequest(API_URL);
            members = Array.isArray(data) ? data : (data.data || []);
            renderMembers();
        } catch (error) {
            table.innerHTML =
                '<tr><td colspan="6">Gagal memuat data anggota.</td></tr>';
            showMessage(error.message, 'error');
        }
    }

    function addCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = value ?? '-';
        row.appendChild(cell);
        return cell;
    }

    function renderMembers() {
        table.replaceChildren();

        if (members.length === 0) {
            table.innerHTML =
                '<tr><td colspan="6">Belum ada data anggota.</td></tr>';
            return;
        }

        members.forEach(member => {
            const row = document.createElement('tr');

            addCell(row, member.member_code);
            addCell(row, member.name);
            addCell(row, member.email);
            addCell(row, member.phone);
            addCell(row, member.position);

            const actionCell = document.createElement('td');
            const actions = document.createElement('div');
            actions.className = 'actions';

            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'edit';
            editButton.textContent = 'Edit';
            editButton.addEventListener('click', () => editMember(member.id));

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'danger';
            deleteButton.textContent = 'Hapus';
            deleteButton.addEventListener('click', () => deleteMember(member));

            actions.append(editButton, deleteButton);
            actionCell.appendChild(actions);
            row.appendChild(actionCell);
            table.appendChild(row);
        });
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const payload = Object.fromEntries(new FormData(form).entries());

        for (const key of ['phone', 'address', 'position']) {
            if (!payload[key].trim()) payload[key] = null;
        }

        const url = editingId
            ? `${API_URL}/${editingId}`
            : API_URL;

        const method = editingId ? 'PUT' : 'POST';

        try {
            const result = await apiRequest(url, {
                method,
                body: JSON.stringify(payload)
            });

            showMessage(
                result.message || 'Data anggota berhasil disimpan.'
            );

            resetForm();
            await loadMembers();
        } catch (error) {
            showMessage(error.message, 'error');
        }
    });

    function editMember(id) {
        const member = members.find(item => item.id === id);
        if (!member) return;

        editingId = id;

        for (const key of [
            'member_code', 'name', 'email',
            'phone', 'address', 'position'
        ]) {
            form.elements[key].value = member[key] ?? '';
        }

        document.getElementById('formTitle').textContent = 'Edit Anggota';
        document.getElementById('saveButton').textContent = 'Simpan Perubahan';
        document.getElementById('cancelButton').hidden = false;

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function deleteMember(member) {
        if (!confirm(`Hapus anggota "${member.name}"?`)) return;

        try {
            const result = await apiRequest(`${API_URL}/${member.id}`, {
                method: 'DELETE'
            });

            showMessage(result.message || 'Anggota berhasil dihapus.');
            await loadMembers();
        } catch (error) {
            showMessage(error.message, 'error');
        }
    }

    function resetForm() {
        form.reset();
        editingId = null;

        document.getElementById('formTitle').textContent = 'Tambah Anggota';
        document.getElementById('saveButton').textContent = 'Simpan Anggota';
        document.getElementById('cancelButton').hidden = true;
    }

    document.getElementById('cancelButton').addEventListener('click', resetForm);
    document.getElementById('refreshButton').addEventListener('click', loadMembers);

    loadMembers();
</script>
</body>
</html>
