<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    /**
     * Laporan Stok Barang
     */
    public function stok()
    {
        $barangs = Barang::withSum('barangMasuk', 'jumlah')
            ->withSum('barangKeluar', 'jumlah')
            ->orderBy('nama_barang')
            ->get();

        $data = $barangs->map(function ($barang) {
            $totalMasuk = (int) ($barang->barang_masuk_sum_jumlah ?? 0);
            $totalKeluar = (int) ($barang->barang_keluar_sum_jumlah ?? 0);

            $sisaStok = $totalMasuk - $totalKeluar;

            return [
                'id' => $barang->id,
                'kode_barang' => $barang->kode_barang,
                'nama_barang' => $barang->nama_barang,
                'detail_ukuran' => $barang->detail_ukuran,
                'satuan' => $barang->satuan,
                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'sisa_stok' => $sisaStok,
                'stok_minimum' => $barang->stok_minimum,
                'status_stok' => $this->getStatusStok(
                    $sisaStok,
                    $barang->stok_minimum
                ),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan stok berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Laporan Barang Masuk
     */
    public function barangMasuk(Request $request)
    {
        $query = BarangMasuk::with('barang');

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate(
                'tanggal',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate(
                'tanggal',
                '<=',
                $request->tanggal_akhir
            );
        }

        if ($request->filled('kode_barang')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where(
                    'kode_barang',
                    'like',
                    '%' . $request->kode_barang . '%'
                );
            });
        }

        if ($request->filled('nama_barang')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where(
                    'nama_barang',
                    'like',
                    '%' . $request->nama_barang . '%'
                );
            });
        }

        if ($request->filled('supplier')) {
            $query->where(
                'supplier',
                'like',
                '%' . $request->supplier . '%'
            );
        }

        $barangMasuk = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $data = $barangMasuk->map(function ($item) {
            return [
                'id' => $item->id,
                'nomor_transaksi' => $item->nomor_transaksi,
                'tanggal' => $item->tanggal->format('Y-m-d'),

                'kode_barang' => $item->barang?->kode_barang,
                'nama_barang' => $item->barang?->nama_barang,
                'detail_ukuran' => $item->barang?->detail_ukuran,
                'satuan' => $item->barang?->satuan,

                'jumlah' => $item->jumlah,
                'supplier' => $item->supplier,
                'keterangan' => $item->keterangan,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan barang masuk berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Laporan Barang Keluar
     */
    public function barangKeluar(Request $request)
    {
        $query = BarangKeluar::with('barang');

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate(
                'tanggal',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate(
                'tanggal',
                '<=',
                $request->tanggal_akhir
            );
        }

        if ($request->filled('kode_barang')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where(
                    'kode_barang',
                    'like',
                    '%' . $request->kode_barang . '%'
                );
            });
        }

        if ($request->filled('nama_barang')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where(
                    'nama_barang',
                    'like',
                    '%' . $request->nama_barang . '%'
                );
            });
        }

        if ($request->filled('tujuan')) {
            $query->where(
                'tujuan',
                'like',
                '%' . $request->tujuan . '%'
            );
        }

        $barangKeluar = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $data = $barangKeluar->map(function ($item) {
            return [
                'id' => $item->id,
                'nomor_transaksi' => $item->nomor_transaksi,
                'tanggal' => $item->tanggal->format('Y-m-d'),

                'kode_barang' => $item->barang?->kode_barang,
                'nama_barang' => $item->barang?->nama_barang,
                'detail_ukuran' => $item->barang?->detail_ukuran,
                'satuan' => $item->barang?->satuan,

                'jumlah' => $item->jumlah,
                'tujuan' => $item->tujuan,
                'keterangan' => $item->keterangan,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan barang keluar berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Rekapitulasi Stok
     */
    public function rekapStok()
    {
        $barangs = Barang::withSum('barangMasuk', 'jumlah')
            ->withSum('barangKeluar', 'jumlah')
            ->orderBy('nama_barang')
            ->get();

        $data = $barangs->map(function ($barang) {
            $totalMasuk = (int) ($barang->barang_masuk_sum_jumlah ?? 0);
            $totalKeluar = (int) ($barang->barang_keluar_sum_jumlah ?? 0);

            $stokAkhir = $totalMasuk - $totalKeluar;

            return [
                'id' => $barang->id,
                'kode_barang' => $barang->kode_barang,
                'nama_barang' => $barang->nama_barang,
                'satuan' => $barang->satuan,

                'stok_awal' => 0,
                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'stok_akhir' => $stokAkhir,

                'status_stok' => $this->getStatusStok(
                    $stokAkhir,
                    $barang->stok_minimum
                ),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Rekapitulasi stok berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Status stok.
     */
    private function getStatusStok(
        int $sisaStok,
        int $stokMinimum
    ): string {
        if ($sisaStok <= 0) {
            return 'Habis';
        }

        if ($sisaStok <= $stokMinimum) {
            return 'Menipis';
        }

        return 'Aman';
    }
}
