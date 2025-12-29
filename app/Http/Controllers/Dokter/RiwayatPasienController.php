<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\Periksa;
use Illuminate\Support\Facades\Auth;

class RiwayatPasienController extends Controller
{
    public function index()
    {
        // ✅ FIX: dokter adalah user yang login
        $dokterId = Auth::id();

        // Ambil hanya pasien yang diperiksa oleh dokter ini
        $riwayatPasien = Periksa::whereHas('daftarPoli.jadwalPeriksa', function ($query) use ($dokterId) {
                $query->where('id_dokter', $dokterId);
            })
            ->with([
                'daftarPoli.pasien',
                'daftarPoli.jadwalPeriksa.dokter',
                'detailPeriksas.obat'
            ])
            ->orderBy('tgl_periksa', 'desc')
            ->get();

        return view('dokter.riwayat-pasien.index', compact('riwayatPasien'));
    }

    public function show($id)
    {
        // ✅ FIX: dokter adalah user yang login
        $dokterId = Auth::id();

        // Ambil detail hanya jika milik dokter yang login
        $periksa = Periksa::where('id', $id)
            ->whereHas('daftarPoli.jadwalPeriksa', function ($query) use ($dokterId) {
                $query->where('id_dokter', $dokterId);
            })
            ->with([
                'daftarPoli.pasien',
                'daftarPoli.jadwalPeriksa.dokter.poli',
                'detailPeriksas.obat'
            ])
            ->firstOrFail(); // otomatis 404 jika bukan miliknya

        return view('dokter.riwayat-pasien.show', compact('periksa'));
    }
}
