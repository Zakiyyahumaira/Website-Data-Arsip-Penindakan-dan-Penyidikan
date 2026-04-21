/* ============================================================
   ARSIP KANTOR — Main JavaScript
   ============================================================ */

/* ── Konfirmasi hapus ────────────────────────────────── */
function konfirmasiHapus(id, judul) {
    if (confirm('Hapus arsip "' + judul + '"?\nTindakan ini tidak bisa dibatalkan.')) {
        window.location.href = 'hapus.php?id=' + id;
    }
}

/* ── Konfirmasi hapus log aktivitas ──────────────────── */
function konfirmasiHapusLog(id) {
    console.log('konfirmasiHapusLog called with id:', id);
    if (confirm('Hapus log aktivitas ini?\nTindakan ini tidak bisa dibatalkan.')) {
        console.log('User confirmed, redirecting to:', 'hapus_log.php?action=single&id=' + id);
        window.location.href = 'hapus_log.php?action=single&id=' + id;
    }
}

/* ── Konfirmasi hapus semua log ──────────────────────── */
function konfirmasiHapusSemuaLog() {
    console.log('konfirmasiHapusSemuaLog called');
    if (confirm('Hapus SEMUA log aktivitas?\nTindakan ini tidak bisa dibatalkan dan tidak dapat dikembalikan!')) {
        console.log('User confirmed, redirecting to:', 'hapus_log.php?action=all');
        window.location.href = 'hapus_log.php?action=all';
    }
}

/* ── Live search pada tabel ──────────────────────────── */
function initTableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('input', function () {
        const keyword = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        let visible = 0;
        rows.forEach(function (row) {
            const text = row.textContent.toLowerCase();
            const match = text.includes(keyword);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const empty = document.getElementById('empty-row');
        if (empty) empty.style.display = visible === 0 ? '' : 'none';
    });
}

/* ── Preview nama file yang dipilih ──────────────────── */
function initFileInput(inputId, previewId) {
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const size = (file.size / 1024 / 1024).toFixed(2);
            preview.textContent = file.name + ' (' + size + ' MB)';
            preview.style.color = '#16a34a';
        } else {
            preview.textContent = 'Belum ada file dipilih';
            preview.style.color = '';
        }
    });
}

/* ── Auto-generate nomor arsip ───────────────────────── */
function generateNomor() {
    const now   = new Date();
    const year  = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const rand  = String(Math.floor(Math.random() * 9000) + 1000);
    const field = document.getElementById('nomor_arsip');
    if (field && field.value === '') {
        field.value = 'ARS/' + year + '/' + month + '/' + rand;
    }
}

/* ── Tampilkan/sembunyikan password ──────────────────── */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const btn   = input.nextElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Sembunyikan';
    } else {
        input.type = 'password';
        btn.textContent = 'Tampilkan';
    }
}

/* ── Alert auto-dismiss ──────────────────────────────── */
function initAlerts() {
    document.querySelectorAll('.alert[data-dismiss]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, parseInt(el.dataset.dismiss) || 4000);
    });
}

/* ── Filter tabel berdasarkan kategori ───────────────── */
function filterKategori(select, tableId) {
    const val   = select.value;
    const table = document.getElementById(tableId);
    if (!table) return;
    table.querySelectorAll('tbody tr').forEach(function (row) {
        const cat = row.dataset.kategori || '';
        row.style.display = (!val || cat === val) ? '' : 'none';
    });
}

/* ── Inisialisasi semua fitur ────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    initTableSearch('searchInput', 'arsipTable');
    initFileInput('fileInput', 'filePreview');
    initAlerts();

    const nomorField = document.getElementById('nomor_arsip');
    if (nomorField) generateNomor();
});
