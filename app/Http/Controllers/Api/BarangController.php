<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{
    /**
     * Menampilkan semua barang.
     */
    public function index()
    {
        $barangs = Barang::query()
            ->withSum('barangMasuk', 'jumlah_dasar')
            ->withSum('barangKeluar', 'jumlah_dasar')
            ->latest()
            ->get();

        $data = $barangs->map(function ($barang) {
            $totalMasuk = (int) ($barang->barang_masuk_sum_jumlah_dasar ?? 0);
            $totalKeluar = (int) ($barang->barang_keluar_sum_jumlah_dasar ?? 0);

            $sisaStok = $totalMasuk - $totalKeluar;

            return [
                'id' => $barang->id,
                'kode_barang' => $barang->kode_barang,
                'nama_barang' => $barang->nama_barang,
                'detail_ukuran' => $barang->detail_ukuran,

                'satuan_masuk' => $barang->satuan_masuk,
                'isi_per_satuan' => $barang->isi_per_satuan,
                'satuan_keluar' => $barang->satuan_keluar,

                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'sisa_stok' => $sisaStok,

                'stok_minimum' => $barang->stok_minimum,

                'status_stok' => $this->getStatusStok(
                    $sisaStok,
                    $barang->stok_minimum
                ),

                'created_at' => $barang->created_at,
                'updated_at' => $barang->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data barang berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Menyimpan barang baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => [
                'required',
                'string',
                'max:100',
                'unique:barangs,kode_barang',
            ],

            'nama_barang' => [
                'required',
                'string',
                'max:255',
            ],

            'detail_ukuran' => [
                'nullable',
                'string',
                'max:255',
            ],

            'satuan_masuk' => [
                'required',
                'string',
                'max:50',
            ],

            'isi_per_satuan' => [
                'nullable',
                'integer',
                'min:1',
                'required_unless:satuan_masuk,PCS',
            ],

            'satuan_keluar' => [
                'required',
                'string',
                'max:50',
            ],

            'stok_minimum' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        /*
         * Kalau barang masuk langsung dalam PCS,
         * isi_per_satuan tidak diperlukan.
         */
        if (strtoupper($validated['satuan_masuk']) === 'PCS') {
            $validated['isi_per_satuan'] = null;
        }

        $barang = Barang::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil ditambahkan.',
            'data' => $barang,
        ], 201);
    }

    /**
     * Menampilkan detail satu barang.
     */
    public function show(string $id)
    {
        $barang = Barang::with([
            'barangMasuk',
            'barangKeluar',
        ])->find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan.',
            ], 404);
        }

        $totalMasuk = (int) $barang->barangMasuk->sum('jumlah_dasar');
        $totalKeluar = (int) $barang->barangKeluar->sum('jumlah_dasar');

        $sisaStok = $totalMasuk - $totalKeluar;

        return response()->json([
            'success' => true,
            'message' => 'Detail barang berhasil diambil.',
            'data' => [
                'id' => $barang->id,
                'kode_barang' => $barang->kode_barang,
                'nama_barang' => $barang->nama_barang,
                'detail_ukuran' => $barang->detail_ukuran,

                'satuan_masuk' => $barang->satuan_masuk,
                'isi_per_satuan' => $barang->isi_per_satuan,
                'satuan_keluar' => $barang->satuan_keluar,

                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'sisa_stok' => $sisaStok,

                'stok_minimum' => $barang->stok_minimum,

                'status_stok' => $this->getStatusStok(
                    $sisaStok,
                    $barang->stok_minimum
                ),

                'barang_masuk' => $barang->barangMasuk,
                'barang_keluar' => $barang->barangKeluar,
            ],
        ]);
    }

    /**
     * Mengubah data barang.
     */
    public function update(Request $request, string $id)
    {
        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'kode_barang' => [
                'required',
                'string',
                'max:100',
                Rule::unique('barangs', 'kode_barang')
                    ->ignore($barang->id),
            ],

            'nama_barang' => [
                'required',
                'string',
                'max:255',
            ],

            'detail_ukuran' => [
                'nullable',
                'string',
                'max:255',
            ],

            'satuan_masuk' => [
                'required',
                'string',
                'max:50',
            ],

            'isi_per_satuan' => [
                'nullable',
                'integer',
                'min:1',
                'required_unless:satuan_masuk,PCS',
            ],

            'satuan_keluar' => [
                'required',
                'string',
                'max:50',
            ],

            'stok_minimum' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        /*
         * Kalau satuan masuk adalah PCS,
         * konversi tidak diperlukan.
         */
        if (strtoupper($validated['satuan_masuk']) === 'PCS') {
            $validated['isi_per_satuan'] = null;
        }

        $barang->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil diperbarui.',
            'data' => $barang,
        ]);
    }

    /**
     * Menghapus barang.
     */
    public function destroy(string $id)
    {
        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan.',
            ], 404);
        }

        if (
            $barang->barangMasuk()->exists() ||
            $barang->barangKeluar()->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak dapat dihapus karena sudah memiliki transaksi.',
            ], 422);
        }

        $barang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dihapus.',
        ]);
    }

    /**
     * Menentukan status stok.
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
