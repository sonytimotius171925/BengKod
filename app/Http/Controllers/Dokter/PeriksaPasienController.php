<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use App\Models\DetailPeriksa;
use App\Models\Obat;
use App\Models\Periksa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeriksaPasienController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // pastikan role dokter
        if (!$user || $user->role !== 'dokter') {
            abort(403);
        }

        $dokterId = $user->id;

        $daftarPasien = DaftarPoli::with(['pasien', 'jadwalPeriksa', 'periksas'])
            ->whereHas('jadwalPeriksa', function ($query) use ($dokterId) {
                $query->where('id_dokter', $dokterId);
            })
            ->orderBy('no_antrian')
            ->get();

        return view('dokter.periksa-pasien.index', compact('daftarPasien'));
    }

    public function create($id)
    {
        // 🔥 AMBIL SEMUA OBAT (TERMASUK STOK 0)
        $obats = Obat::all();

        return view('dokter.periksa-pasien.create', [
            'obats' => $obats,
            'id'    => $id
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_daftar_poli' => 'required|exists:daftar_poli,id',
            'obat_json'      => 'required',
            'catatan'        => 'nullable|string',
            'biaya_periksa'  => 'required|integer|min:0',
        ]);

        $obatIds = json_decode($request->obat_json, true);

        // 🔥 CEGAH ERROR JIKA JSON KOSONG / INVALID
        if (!is_array($obatIds) || count($obatIds) === 0) {
            return redirect()->back()
                ->with('type', 'danger')
                ->with('message', 'Obat belum dipilih');
        }

        DB::beginTransaction();

        try {
            $obatHabis = [];

            // 🔒 CEK STOK DENGAN LOCK
            foreach ($obatIds as $idObat) {
                $obat = Obat::lockForUpdate()->find($idObat);

                if (!$obat || $obat->stok <= 0) {
                    $obatHabis[] = $obat ? $obat->nama_obat : 'Obat tidak ditemukan';
                }
            }

            // ❌ JIKA ADA OBAT HABIS → BATALKAN
            if (count($obatHabis) > 0) {
                DB::rollBack();

                return redirect()->back()
                    ->withInput()
                    ->with('type', 'danger')
                    ->with('message', 'Stok obat habis: ' . implode(', ', $obatHabis));
            }

            // ✅ SIMPAN PERIKSA
            $periksa = Periksa::create([
                'id_daftar_poli' => $request->id_daftar_poli,
                'tgl_periksa'    => now(),
                'catatan'        => $request->catatan,
                'biaya_periksa'  => $request->biaya_periksa + 150000,
            ]);

            // ✅ SIMPAN DETAIL & KURANGI STOK
            foreach ($obatIds as $idObat) {
                DetailPeriksa::create([
                    'id_periksa' => $periksa->id,
                    'id_obat'    => $idObat,
                ]);

                Obat::where('id', $idObat)->decrement('stok');
            }

            DB::commit();

            return redirect()
                ->route('periksa-pasien.index')
                ->with('type', 'success')
                ->with('message', 'Pemeriksaan berhasil disimpan');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('type', 'danger')
                ->with('message', 'Terjadi kesalahan saat menyimpan data');
        }
    }
}
