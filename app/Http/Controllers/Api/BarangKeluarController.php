<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangKeluarController extends Controller
{
    /**
     * Menampilkan semua data barang keluar.
     */
    public function index()
    {
        $data = BarangKeluar::with('barang')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Menyimpan transaksi barang keluar.
     *
     * Stok dihitung berdasarkan:
     * total jumlah_dasar barang masuk
     * dikurangi
     * total jumlah_dasar barang keluar.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'barang_id' => ['required', 'integer', 'exists:barangs,id'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'satuan' => ['required', 'string', 'max:50'],
            'tujuan' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        try {
            $result = DB::transaction(function () use ($validated) {

                // Ambil barang
                $barang = Barang::lockForUpdate()->findOrFail(
                    $validated['barang_id']
                );

                // Jumlah dasar barang keluar
                // Sesuai konsep saat ini: jumlah_dasar = jumlah
                $jumlah = (float) $validated['jumlah'];
                $jumlahDasar = $jumlah;

                // Total barang masuk
                $totalMasuk = (float) $barang
                    ->barangMasuk()
                    ->sum('jumlah_dasar');

                // Total barang keluar
                $totalKeluar = (float) $barang
                    ->barangKeluar()
                    ->sum('jumlah_dasar');

                // Stok tersedia
                $stokTersedia = $totalMasuk - $totalKeluar;

                // Cek stok
                if ($jumlahDasar > $stokTersedia) {
                    abort(
                        response()->json([
                            'success' => false,
                            'message' => 'Stok barang tidak mencukupi.',
                            'stok_tersedia' => $stokTersedia,
                            'jumlah_dibutuhkan' => $jumlahDasar,
                        ], 422)
                    );
                }

                // Generate nomor transaksi
                // Contoh:
                // BK-20260918-0001
                // BK-20260918-0002
                // BK-20260918-0003

                $tanggal = $validated['tanggal'];

                $prefix = 'BK-' . date(
                    'Ymd',
                    strtotime($tanggal)
                ) . '-';

                $transaksiTerakhir = BarangKeluar::where(
                    'nomor_transaksi',
                    'like',
                    $prefix . '%'
                )
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($transaksiTerakhir) {
                    $nomorTerakhir = (int) substr(
                        $transaksiTerakhir->nomor_transaksi,
                        -4
                    );

                    $nomorUrut = $nomorTerakhir + 1;
                } else {
                    $nomorUrut = 1;
                }

                $nomorTransaksi = $prefix . str_pad(
                    $nomorUrut,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                // Simpan barang keluar
                $barangKeluar = BarangKeluar::create([
                    'nomor_transaksi' => $nomorTransaksi,
                    'tanggal' => $tanggal,
                    'barang_id' => $barang->id,
                    'jumlah' => $jumlah,
                    'satuan' => trim($validated['satuan']),
                    'jumlah_dasar' => $jumlahDasar,
                    'tujuan' => trim($validated['tujuan']),
                    'keterangan' => isset($validated['keterangan'])
                        ? trim($validated['keterangan'])
                        : null,
                ]);

                return $barangKeluar->load('barang');
            });

            return response()->json([
                'success' => true,
                'message' => 'Barang keluar berhasil disimpan.',
                'data' => $result,
            ], 201);

        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan barang keluar.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan satu data barang keluar.
     */
    public function show($id)
    {
        $data = BarangKeluar::with('barang')->find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang keluar tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Mengubah data barang keluar.
     *
     * Nomor transaksi tidak diubah.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'barang_id' => ['required', 'integer', 'exists:barangs,id'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'satuan' => ['required', 'string', 'max:50'],
            'tujuan' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        try {
            $result = DB::transaction(function () use ($validated, $id) {

                // Ambil transaksi lama
                $barangKeluar = BarangKeluar::lockForUpdate()->find($id);

                if (!$barangKeluar) {
                    abort(
                        response()->json([
                            'success' => false,
                            'message' => 'Data barang keluar tidak ditemukan.',
                        ], 404)
                    );
                }

                // Ambil barang baru
                $barangBaru = Barang::lockForUpdate()->findOrFail(
                    $validated['barang_id']
                );

                // Jumlah baru
                // Sesuai konsep saat ini: jumlah_dasar = jumlah
                $jumlah = (float) $validated['jumlah'];
                $jumlahDasarBaru = $jumlah;

                // Total barang masuk
                $totalMasuk = (float) $barangBaru
                    ->barangMasuk()
                    ->sum('jumlah_dasar');

                // Total barang keluar selain transaksi yang sedang diedit
                $totalKeluar = (float) $barangBaru
                    ->barangKeluar()
                    ->where('id', '!=', $barangKeluar->id)
                    ->sum('jumlah_dasar');

                // Stok tersedia
                $stokTersedia = $totalMasuk - $totalKeluar;

                // Cek stok
                if ($jumlahDasarBaru > $stokTersedia) {
                    abort(
                        response()->json([
                            'success' => false,
                            'message' => 'Stok barang tidak mencukupi.',
                            'stok_tersedia' => $stokTersedia,
                            'jumlah_dibutuhkan' => $jumlahDasarBaru,
                        ], 422)
                    );
                }

                // Update transaksi
                $barangKeluar->update([
                    'tanggal' => $validated['tanggal'],
                    'barang_id' => $barangBaru->id,
                    'jumlah' => $jumlah,
                    'satuan' => trim($validated['satuan']),
                    'jumlah_dasar' => $jumlahDasarBaru,
                    'tujuan' => trim($validated['tujuan']),
                    'keterangan' => isset($validated['keterangan'])
                        ? trim($validated['keterangan'])
                        : null,
                ]);

                return $barangKeluar->load('barang');
            });

            return response()->json([
                'success' => true,
                'message' => 'Barang keluar berhasil diperbarui.',
                'data' => $result,
            ]);

        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui barang keluar.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menghapus transaksi barang keluar.
     *
     * Tidak perlu update stok di tabel barangs.
     * Stok otomatis dihitung ulang dari transaksi.
     */
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {

                // Ambil transaksi
                $barangKeluar = BarangKeluar::lockForUpdate()->find($id);

                if (!$barangKeluar) {
                    abort(
                        response()->json([
                            'success' => false,
                            'message' => 'Data barang keluar tidak ditemukan.',
                        ], 404)
                    );
                }

                // Hapus transaksi
                $barangKeluar->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Barang keluar berhasil dihapus.',
            ]);

        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus barang keluar.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
