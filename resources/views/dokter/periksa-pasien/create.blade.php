<x-layouts.app title="Periksa Pasien">
    <div class="container-fluid px-4 mt-4">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <h1 class="mb-4">Periksa Pasien</h1>

                <div class="card">
                    <div class="card-body">
                        <form id="form-periksa" action="{{ route('periksa-pasien.store') }}" method="POST">
                            @csrf

                            <input type="hidden" name="id_daftar_poli" value="{{ $id }}">

                            {{-- PILIH OBAT --}}
                            <div class="mb-3">
                                <label class="form-label">Pilih Obat</label>
                                <select id="select-obat" class="form-select">
                                    <option value="">-- Pilih Obat --</option>
                                    @foreach ($obats as $obat)
                                        <option
                                            value="{{ $obat->id }}"
                                            data-nama="{{ $obat->nama_obat }}"
                                            data-harga="{{ $obat->harga }}"
                                            data-stok="{{ $obat->stok }}"
                                        >
                                            {{ $obat->nama_obat }}
                                            (Stok: {{ $obat->stok }})
                                            - Rp{{ number_format($obat->harga) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- CATATAN --}}
                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="catatan" class="form-control">{{ old('catatan') }}</textarea>
                            </div>

                            {{-- OBAT TERPILIH --}}
                            <div class="mb-3">
                                <label class="form-label">Obat Terpilih</label>
                                <ul id="obat-terpilih" class="list-group mb-2"></ul>

                                <input type="hidden" name="biaya_periksa" id="biaya_periksa" value="0">
                                <input type="hidden" name="obat_json" id="obat_json">
                            </div>

                            {{-- TOTAL --}}
                            <div class="mb-3">
                                <label>Total Harga</label>
                                <p id="total-harga" class="fw-bold">Rp 0</p>
                            </div>

                            <button type="submit" class="btn btn-success">Simpan</button>
                            <a href="{{ route('periksa-pasien.index') }}" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

{{-- SWEETALERT --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const selectObat     = document.getElementById('select-obat');
    const listObat       = document.getElementById('obat-terpilih');
    const inputBiaya     = document.getElementById('biaya_periksa');
    const inputObatJson  = document.getElementById('obat_json');
    const totalHargaEl  = document.getElementById('total-harga');
    const formPeriksa   = document.getElementById('form-periksa');

    let daftarObat = [];

    selectObat.addEventListener('change', () => {
        const option = selectObat.options[selectObat.selectedIndex];

        if (!option.value) return;

        const obat = {
            id: option.value,
            nama: option.dataset.nama,
            harga: parseInt(option.dataset.harga),
            stok: parseInt(option.dataset.stok)
        };

        if (daftarObat.some(o => o.id === obat.id)) {
            Swal.fire('Info', 'Obat sudah dipilih', 'info');
            return;
        }

        daftarObat.push(obat);
        renderObat();
        selectObat.selectedIndex = 0;
    });

    function renderObat() {
        listObat.innerHTML = '';
        let total = 0;

        daftarObat.forEach((obat, index) => {
            total += obat.harga;

            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';

            li.innerHTML = `
                <div>
                    <strong>${obat.nama}</strong>
                    <br>
                    <small>Rp ${obat.harga.toLocaleString()}</small>
                    ${obat.stok <= 0
                        ? '<span class="badge bg-danger ms-2">Stok Habis</span>'
                        : ''}
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="hapusObat(${index})">
                    Hapus
                </button>
            `;

            listObat.appendChild(li);
        });

        inputBiaya.value = total;
        totalHargaEl.textContent = `Rp ${total.toLocaleString()}`;
        inputObatJson.value = JSON.stringify(daftarObat.map(o => o.id));
    }

    function hapusObat(index) {
        daftarObat.splice(index, 1);
        renderObat();
    }

    // VALIDASI SUBMIT
    formPeriksa.addEventListener('submit', function (e) {
        if (daftarObat.length === 0) {
            e.preventDefault();
            Swal.fire('Gagal', 'Minimal pilih 1 obat', 'error');
        }
    });

    // FLASH MESSAGE DARI BACKEND
    @if (session('message'))
        Swal.fire({
            icon: "{{ session('type') }}",
            title: "{{ session('message') }}",
            timer: 3000,
            showConfirmButton: false
        });
    @endif
</script>
