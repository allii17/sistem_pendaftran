<?php
require_once 'koneksi.php';
$conn = getKoneksi();

// ── Proses POST (tambah) ──────────────────────────────────
$pesan = '';
$tipe  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    if ($_POST['aksi'] === 'tambah') {
        $gedung  = strtoupper(trim($_POST['kode_gedung']));
        $nomor   = (int)$_POST['nomor_urut'];
        $ket_no  = (int)$_POST['keterangan_no'];
        $kode    = $gedung . str_pad($nomor, 2, '0', STR_PAD_LEFT) . '-' . $ket_no;

        // Cek duplikat
        $cek = $conn->prepare("SELECT id FROM pendaftar WHERE kode_pendaftar = ?");
        $cek->bind_param('s', $kode);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $pesan = "Kode <strong>$kode</strong> sudah terdaftar!";
            $tipe  = 'error';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO pendaftar
                  (kode_pendaftar,nama_pendaftar,jenis_kelamin,ttl,asal_sekolah,
                   kode_gedung,nomor_urut,keterangan_no,nilai_mat,nilai_bindo,nilai_bing)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $nama  = trim($_POST['nama_pendaftar']);
            $jk    = $_POST['jenis_kelamin'];
            $ttl   = trim($_POST['ttl']);
            $asal  = trim($_POST['asal_sekolah']);
            $mat   = (float)$_POST['nilai_mat'];
            $bindo = (float)$_POST['nilai_bindo'];
            $bing  = (float)$_POST['nilai_bing'];
            $stmt->bind_param('ssssssiiddd',
                $kode,$nama,$jk,$ttl,$asal,$gedung,$nomor,$ket_no,$mat,$bindo,$bing);
            if ($stmt->execute()) {
                $pesan = "Pendaftar <strong>$nama</strong> ($kode) berhasil ditambahkan!";
                $tipe  = 'ok';
            } else {
                $pesan = "Gagal menyimpan: " . $stmt->error;
                $tipe  = 'error';
            }
        }
    }

    if ($_POST['aksi'] === 'hapus') {
        $del_id = (int)$_POST['del_id'];
        $stmt   = $conn->prepare("DELETE FROM pendaftar WHERE id = ?");
        $stmt->bind_param('i', $del_id);
        $stmt->execute();
        $pesan = "Data berhasil dihapus.";
        $tipe  = 'ok';
    }
}

// ── Ambil data ────────────────────────────────────────────
$cari   = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

$sql = "SELECT * FROM v_rekap_pendaftar WHERE 1=1";
if ($cari !== '') $sql .= " AND (nama_pendaftar LIKE '%$cari%' OR kode_pendaftar LIKE '%$cari%')";
if ($filter !== '') $sql .= " AND keterangan_lulus = '$filter'";
$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Statistik
$stat = $conn->query("SELECT * FROM v_statistik")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Pendaftaran — Universitas Pamulang</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:       #F5F3EE;
    --surface:  #FFFFFF;
    --border:   #E0DDD6;
    --text:     #1A1916;
    --muted:    #7A7870;
    --accent:   #1D4E89;
    --accent-l: #E8EFF8;
    --green:    #2D6A4F;
    --green-l:  #D8F3DC;
    --amber:    #7D4E00;
    --amber-l:  #FFF3CD;
    --red:      #9B2335;
    --red-l:    #FDECEA;
    --radius:   10px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
    line-height: 1.6;
    min-height: 100vh;
  }

  /* ── Header ── */
  .header {
    background: var(--accent);
    color: #fff;
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
  }
  .header-logo {
    width: 52px; height: 52px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'DM Serif Display', serif;
    font-size: 20px; color: #fff;
    flex-shrink: 0;
  }
  .header-title { font-family: 'DM Serif Display', serif; font-size: 20px; line-height: 1.2; }
  .header-sub   { font-size: 12px; opacity: 0.75; margin-top: 2px; }

  /* ── Layout ── */
  .container { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }

  /* ── Stat cards ── */
  .stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 1.5rem; }
  .stat-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1rem 1.25rem;
  }
  .stat-num  { font-family: 'DM Serif Display', serif; font-size: 28px; color: var(--accent); }
  .stat-lbl  { font-size: 12px; color: var(--muted); margin-top: 2px; }

  /* ── Card ── */
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
  }
  .card-title {
    font-size: 12px; font-weight: 500; letter-spacing: .06em;
    text-transform: uppercase; color: var(--muted);
    margin-bottom: 1rem; padding-bottom: .5rem;
    border-bottom: 1px solid var(--border);
  }

  /* ── Form ── */
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
  .field label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 4px; }
  .field input, .field select {
    width: 100%; padding: 7px 10px; font-family: inherit; font-size: 14px;
    border: 1px solid var(--border); border-radius: 6px;
    background: var(--bg); color: var(--text);
    transition: border-color .15s;
  }
  .field input:focus, .field select:focus {
    outline: none; border-color: var(--accent);
    background: #fff;
  }
  .kode-preview {
    background: var(--accent-l); border: 1px solid #B8CCE4;
    border-radius: 6px; padding: 7px 12px;
    font-family: 'DM Serif Display', serif; font-size: 18px;
    color: var(--accent); text-align: center; letter-spacing: .08em;
  }
  .nilai-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; align-items: end; }
  .rata-preview {
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 6px; padding: 7px 10px; text-align: center;
    font-weight: 500; font-size: 15px;
  }

  /* ── Buttons ── */
  .btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 6px; border: 1px solid var(--border);
    background: var(--surface); color: var(--text);
    font-family: inherit; font-size: 13px; cursor: pointer;
    transition: background .15s;
  }
  .btn:hover { background: var(--bg); }
  .btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
  .btn-primary:hover { background: #163D6E; }
  .btn-sm { padding: 4px 10px; font-size: 12px; }
  .btn-danger { border-color: var(--red); color: var(--red); }
  .btn-danger:hover { background: var(--red-l); }
  .btn-row { display: flex; gap: 8px; margin-top: 1rem; flex-wrap: wrap; }

  /* ── Alert ── */
  .alert {
    border-radius: 6px; padding: 10px 14px; margin-bottom: 1rem;
    font-size: 13px; border: 1px solid;
  }
  .alert-ok    { background: var(--green-l); color: var(--green); border-color: #95D5B2; }
  .alert-error { background: var(--red-l);   color: var(--red);   border-color: #F5A5A5; }

  /* ── Search / filter bar ── */
  .toolbar {
    display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
    margin-bottom: 1rem;
  }
  .toolbar input {
    padding: 7px 10px; border: 1px solid var(--border); border-radius: 6px;
    font-family: inherit; font-size: 13px; background: var(--surface);
    flex: 1; min-width: 160px;
  }
  .toolbar input:focus { outline: none; border-color: var(--accent); }
  .filter-btn {
    padding: 6px 12px; border-radius: 20px; border: 1px solid var(--border);
    background: var(--surface); font-size: 12px; cursor: pointer;
    color: var(--muted); text-decoration: none; transition: all .15s;
  }
  .filter-btn:hover, .filter-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }

  /* ── Table ── */
  .tbl-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th {
    font-size: 11px; font-weight: 500; letter-spacing: .05em;
    text-transform: uppercase; color: var(--muted);
    padding: 8px 10px; border-bottom: 1px solid var(--border);
    text-align: left; white-space: nowrap;
  }
  td { padding: 9px 10px; border-bottom: 1px solid var(--border); vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: var(--bg); }
  .kode-tag {
    font-family: 'DM Serif Display', serif; font-size: 14px;
    color: var(--accent); white-space: nowrap;
  }
  .badge {
    display: inline-block; font-size: 11px; font-weight: 500;
    padding: 3px 10px; border-radius: 20px;
  }
  .badge-lulus    { background: var(--green-l); color: var(--green); }
  .badge-cadangan { background: var(--amber-l); color: var(--amber); }
  .badge-tidak    { background: var(--red-l);   color: var(--red); }
  .empty-row td   { text-align: center; color: var(--muted); padding: 2rem; }

  /* ── Rata preview ── */
  #rataPreview { transition: color .2s; }

  @media(max-width: 700px) {
    .stat-row { grid-template-columns: 1fr 1fr; }
    .form-grid, .form-grid-3, .nilai-row { grid-template-columns: 1fr; }
    .header { flex-direction: column; text-align: center; }
  }
</style>
</head>
<body>

<div class="header">
  <div class="header-logo">UP</div>
  <div>
    <div class="header-title">Universitas Pamulang</div>
    <div class="header-sub">Sistem Input Pendaftaran Mahasiswa Baru — Pemrograman Web II/3 · Semester Genap 2025/2026</div>
  </div>
</div>

<div class="container">

  <!-- Statistik -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-num"><?= $stat['total_pendaftar'] ?? 0 ?></div>
      <div class="stat-lbl">Total Pendaftar</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--green)"><?= $stat['jumlah_lulus'] ?? 0 ?></div>
      <div class="stat-lbl">Lulus (≥ 70)</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--amber)"><?= $stat['jumlah_cadangan'] ?? 0 ?></div>
      <div class="stat-lbl">Cadangan (60–69)</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--red)"><?= $stat['jumlah_tidak_lulus'] ?? 0 ?></div>
      <div class="stat-lbl">Tidak Lulus (&lt; 60)</div>
    </div>
  </div>

  <!-- Alert -->
  <?php if ($pesan): ?>
  <div class="alert alert-<?= $tipe ?>"><?= $pesan ?></div>
  <?php endif; ?>

  <!-- Form Tambah -->
  <div class="card">
    <div class="card-title">Input Pendaftaran</div>
    <form method="POST" action="">
      <input type="hidden" name="aksi" value="tambah">

      <!-- Kode -->
      <div style="margin-bottom:12px">
        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:6px">Kode Pendaftaran (otomatis)</label>
        <div class="kode-preview" id="kodePreview">—</div>
        <div class="form-grid-3" style="margin-top:8px">
          <div class="field">
            <label>Gedung</label>
            <select name="kode_gedung" id="selGedung" required onchange="updateKode()">
              <option value="">-- Pilih --</option>
              <option value="A">A — Gedung A</option>
              <option value="B">B — Gedung B</option>
              <option value="V">V — Viktor</option>
            </select>
          </div>
          <div class="field">
            <label>Nomor Urut (1–99)</label>
            <input type="number" name="nomor_urut" id="inpNomor" min="1" max="99" placeholder="1" required oninput="updateKode()">
          </div>
          <div class="field">
            <label>Keterangan (1 digit)</label>
            <input type="number" name="keterangan_no" id="inpKet" min="1" max="9" placeholder="1" required oninput="updateKode()">
          </div>
        </div>
      </div>

      <!-- Data diri -->
      <div class="form-grid" style="margin-bottom:12px">
        <div class="field">
          <label>Nama Pendaftar</label>
          <input type="text" name="nama_pendaftar" placeholder="Nur Ali Mahpudin" required>
        </div>
        <div class="field">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin" required>
            <option value="">-- Pilih --</option>
            <option>Laki-Laki</option>
            <option>Perempuan</option>
          </select>
        </div>
        <div class="field">
          <label>Tempat, Tanggal Lahir</label>
          <input type="text" name="ttl" placeholder="Tangerang, 15-03-2001" required>
        </div>
        <div class="field">
          <label>Asal Sekolah</label>
          <input type="text" name="asal_sekolah" placeholder="SMA Negeri 3 Pamulang" required>
        </div>
      </div>

      <!-- Nilai -->
      <div class="nilai-row" style="margin-bottom:12px">
        <div class="field">
          <label>Nilai Matematika</label>
          <input type="number" name="nilai_mat" id="nMat" min="0" max="100" placeholder="0" required oninput="hitungRata()">
        </div>
        <div class="field">
          <label>Nilai B. Indonesia</label>
          <input type="number" name="nilai_bindo" id="nBindo" min="0" max="100" placeholder="0" required oninput="hitungRata()">
        </div>
        <div class="field">
          <label>Nilai B. Inggris</label>
          <input type="number" name="nilai_bing" id="nBing" min="0" max="100" placeholder="0" required oninput="hitungRata()">
        </div>
        <div class="field">
          <label>Rata-rata</label>
          <div class="rata-preview" id="rataPreview">—</div>
        </div>
      </div>

      <div class="btn-row">
        <button type="submit" class="btn btn-primary">+ Simpan ke Database</button>
        <button type="reset" class="btn" onclick="resetPreview()">Reset</button>
      </div>
    </form>
  </div>

  <!-- Tabel Data -->
  <div class="card">
    <div class="card-title">Data Pendaftar</div>

    <!-- Toolbar cari & filter -->
    <form method="GET" class="toolbar">
      <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="Cari nama atau kode...">
      <button type="submit" class="btn btn-sm">Cari</button>
      <a href="?" class="filter-btn <?= $filter===''?'active':'' ?>">Semua</a>
      <a href="?filter=Lulus" class="filter-btn <?= $filter==='Lulus'?'active':'' ?>">Lulus</a>
      <a href="?filter=Cadangan" class="filter-btn <?= $filter==='Cadangan'?'active':'' ?>">Cadangan</a>
      <a href="?filter=Tidak Lulus" class="filter-btn <?= $filter==='Tidak Lulus'?'active':'' ?>">Tidak Lulus</a>
    </form>

    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama</th>
            <th>JK</th>
            <th>TTL</th>
            <th>Asal Sekolah</th>
            <th>Mat</th>
            <th>Indo</th>
            <th>Ing</th>
            <th>Rata</th>
            <th>Keterangan</th>
            <th>Hapus</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr class="empty-row"><td colspan="11">Belum ada data pendaftar.</td></tr>
          <?php else: ?>
          <?php foreach ($rows as $r): ?>
          <?php
            $ket   = $r['keterangan_lulus'];
            $cls   = $ket==='Lulus' ? 'lulus' : ($ket==='Cadangan' ? 'cadangan' : 'tidak');
          ?>
          <tr>
            <td><span class="kode-tag"><?= htmlspecialchars($r['kode_pendaftar']) ?></span></td>
            <td><?= htmlspecialchars($r['nama_pendaftar']) ?></td>
            <td><?= htmlspecialchars($r['jenis_kelamin']) ?></td>
            <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($r['ttl']) ?></td>
            <td><?= htmlspecialchars($r['nama_gedung']) ?> — <?= htmlspecialchars($r['asal_sekolah']) ?></td>
            <td style="text-align:center"><?= $r['nilai_mat'] ?></td>
            <td style="text-align:center"><?= $r['nilai_bindo'] ?></td>
            <td style="text-align:center"><?= $r['nilai_bing'] ?></td>
            <td style="text-align:center;font-weight:500"><?= number_format($r['rata_rata'],1) ?></td>
            <td><span class="badge badge-<?= $cls ?>"><?= $ket ?></span></td>
            <td>
              <form method="POST" style="display:inline" onsubmit="return confirm('Hapus data ini?')">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="del_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($rows)): ?>
    <div style="margin-top:10px;font-size:12px;color:var(--muted)">
      Menampilkan <?= count($rows) ?> data
      <?= $cari ? "· hasil pencarian \"<strong>$cari</strong>\"" : '' ?>
      <?= $filter ? "· filter: <strong>$filter</strong>" : '' ?>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /container -->

<script>
function updateKode() {
  const g = document.getElementById('selGedung').value;
  const n = document.getElementById('inpNomor').value;
  const k = document.getElementById('inpKet').value;
  const el = document.getElementById('kodePreview');
  if (!g || !n || !k) { el.textContent = '—'; return; }
  el.textContent = g + String(n).padStart(2,'0') + '-' + k;
}

function hitungRata() {
  const m  = parseFloat(document.getElementById('nMat').value)   || 0;
  const bi = parseFloat(document.getElementById('nBindo').value) || 0;
  const bg = parseFloat(document.getElementById('nBing').value)  || 0;
  const el = document.getElementById('rataPreview');
  if (!document.getElementById('nMat').value) { el.textContent='—'; el.style.color=''; return; }
  const rata = (m + bi + bg) / 3;
  el.textContent = rata.toFixed(1);
  el.style.color = rata >= 70 ? 'var(--green)' : rata >= 60 ? 'var(--amber)' : 'var(--red)';
}

function resetPreview() {
  document.getElementById('kodePreview').textContent = '—';
  const r = document.getElementById('rataPreview');
  r.textContent = '—'; r.style.color = '';
}
</script>
</body>
</html>
