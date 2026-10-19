<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\BarangMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangMasukController extends Controller
{
    /**
     * Menampilkan semua transaksi barang masuk.
     */
    public function index()
    {
        $data = BarangMasuk::with('barang')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'tanggal' => $item->tanggal?->format('Y-m-d'),
                    'nomor_transaksi' => $item->nomor_transaksi,

                    'barang_id' => $item->barang_id,
                    'kode_barang' => $item->barang?->kode_barang,
                    'nama_barang' => $item->barang?->nama_barang,
                    'detail_ukuran' => $item->barang?->detail_ukuran,

                    'satuan' => $item->satuan,
                    'jumlah' => $item->jumlah,
                    'jumlah_dasar' => $item->jumlah_dasar,

                    'satuan_keluar' => $item->barang?->satuan_keluar,
                    'isi_per_satuan' => $item->barang?->isi_per_satuan,

                    'supplier' => $item->supplier,
                    'keterangan' => $item->keterangan,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Data barang masuk berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Menyimpan transaksi Barang Masuk.
     *
     * Jika kode barang belum ada:
     * - Buat master barang.
     * - Buat transaksi barang masuk.
     *
     * Jika kode barang sudah ada:
     * - Gunakan barang yang sudah ada.
     * - Gunakan aturan konversi dari master barang.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',

            'kode_barang' => 'required|string|max:100',
            'nama_barang' => 'required|string|max:255',
            'detail_ukuran' => 'nullable|string|max:255',

            'satuan_masuk' => 'required|string|max:50',

            'isi_per_satuan' => [
                'nullable',
                'integer',
                'min:1',
                'required_unless:satuan_masuk,PCS',
            ],

            'satuan_keluar' => 'required|string|max:50',

            'stok_minimum' => 'nullable|integer|min:0',

            'jumlah' => 'required|integer|min:1',

            'supplier' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        try {
            $result = DB::transaction(function () use ($validated) {
                $kodeBarang = trim($validated['kode_barang']);

                /*
                 * Cari barang berdasarkan kode.
                 */
                $barang = Barang::where(
                    'kode_barang',
                    $kodeBarang
                )->first();

                /*
                 * Kalau barang belum ada,
                 * otomatis buat master barang baru.
                 */
                if (!$barang) {
                    $satuanMasuk = strtoupper(
                        trim($validated['satuan_masuk'])
                    );

                    $isiPerSatuan = $satuanMasuk === 'PCS'
                        ? null
                        : (int) $validated['isi_per_satuan'];

                    $barang = Barang::create([
                        'kode_barang' => $kodeBarang,
                        'nama_barang' => $validated['nama_barang'],
                        'detail_ukuran' => $validated['detail_ukuran'] ?? null,

                        'satuan_masuk' => $validated['satuan_masuk'],
                        'isi_per_satuan' => $isiPerSatuan,
                        'satuan_keluar' => $validated['satuan_keluar'],

                        'stok_minimum' => $validated['stok_minimum'] ?? 0,
                    ]);
                }

                /*
                 * Gunakan satuan dan konversi dari MASTER BARANG.
                 *
                 * Ini penting supaya transaksi selalu mengikuti
                 * aturan konversi barang yang sudah tersimpan.
                 */
                $satuanMasuk = strtoupper(
                    trim($barang->satuan_masuk)
                );

                $jumlah = (int) $validated['jumlah'];

                /*
                 * Jika barang masuk menggunakan PCS,
                 * jumlah dasar sama dengan jumlah.
                 *
                 * Contoh:
                 * 50 PCS = 50 PCS
                 */
                if ($satuanMasuk === 'PCS') {
                    $jumlahDasar = $jumlah;
                } else {
                    /*
                     * Jika menggunakan Pack/Dus/Box,
                     * jumlah dasar = jumlah × isi per satuan.
                     *
                     * Contoh:
                     * 5 Pack × 25 = 125 PCS
                     */
                    if (
                        empty($barang->isi_per_satuan) ||
                        $barang->isi_per_satuan <= 0
                    ) {
                        throw new \Exception(
                            'Konversi isi per satuan untuk barang ini belum diatur.'
                        );
                    }

                    $jumlahDasar = $jumlah * (int) $barang->isi_per_satuan;
                }

                /*
                 * Generate nomor transaksi otomatis.
                 */
                $tanggal = $validated['tanggal'];

                $prefix = 'BM-' .
                    date('Ymd', strtotime($tanggal)) .
                    '-';

                $lastTransaction = BarangMasuk::where(
                    'nomor_transaksi',
                    'like',
                    $prefix . '%'
                )
                    ->orderByDesc('id')
                    ->first();

                if ($lastTransaction) {
                    $lastNumber = (int) substr(
                        $lastTransaction->nomor_transaksi,
                        -3
                    );

                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                $nomorTransaksi = $prefix .
                    str_pad(
                        $nextNumber,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );

                /*
                 * Buat transaksi barang masuk.
                 */
                $barangMasuk = BarangMasuk::create([
                    'nomor_transaksi' => $nomorTransaksi,
                    'tanggal' => $tanggal,
                    'barang_id' => $barang->id,

                    'jumlah' => $jumlah,
                    'satuan' => $barang->satuan_masuk,
                    'jumlah_dasar' => $jumlahDasar,

                    'supplier' => $validated['supplier'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                return [
                    'barang' => $barang,
                    'barang_masuk' => $barangMasuk->load('barang'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Barang masuk berhasil ditambahkan.',
                'data' => [
                    'id' => $result['barang_masuk']->id,
                    'tanggal' => $result['barang_masuk']->tanggal?->format('Y-m-d'),
                    'nomor_transaksi' => $result['barang_masuk']->nomor_transaksi,

                    'barang_id' => $result['barang']->id,
                    'kode_barang' => $result['barang']->kode_barang,
                    'nama_barang' => $result['barang']->nama_barang,
                    'detail_ukuran' => $result['barang']->detail_ukuran,

                    'satuan' => $result['barang_masuk']->satuan,
                    'jumlah' => $result['barang_masuk']->jumlah,
                    'jumlah_dasar' => $result['barang_masuk']->jumlah_dasar,

                    'satuan_keluar' => $result['barang']->satuan_keluar,
                    'isi_per_satuan' => $result['barang']->isi_per_satuan,

                    'supplier' => $result['barang_masuk']->supplier,
                    'keterangan' => $result['barang_masuk']->keterangan,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan barang masuk.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan detail transaksi.
     */
    public function show(BarangMasuk $barangMasuk)
    {
        $barangMasuk->load('barang');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $barangMasuk->id,
                'tanggal' => $barangMasuk->tanggal?->format('Y-m-d'),
                'nomor_transaksi' => $barangMasuk->nomor_transaksi,

                'barang_id' => $barangMasuk->barang_id,
                'kode_barang' => $barangMasuk->barang?->kode_barang,
                'nama_barang' => $barangMasuk->barang?->nama_barang,
                'detail_ukuran' => $barangMasuk->barang?->detail_ukuran,

                'satuan' => $barangMasuk->satuan,
                'jumlah' => $barangMasuk->jumlah,
                'jumlah_dasar' => $barangMasuk->jumlah_dasar,

                'satuan_keluar' => $barangMasuk->barang?->satuan_keluar,
                'isi_per_satuan' => $barangMasuk->barang?->isi_per_satuan,

                'supplier' => $barangMasuk->supplier,
                'keterangan' => $barangMasuk->keterangan,
            ],
        ]);
    }

    /**
     * Update transaksi barang masuk.
     */
    public function update(Request $request, BarangMasuk $barangMasuk)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',

            'kode_barang' => 'required|string|max:100',
            'nama_barang' => 'required|string|max:255',
            'detail_ukuran' => 'nullable|string|max:255',

            'satuan_masuk' => 'required|string|max:50',

            'isi_per_satuan' => [
                'nullable',
                'integer',
                'min:1',
                'required_unless:satuan_masuk,PCS',
            ],

            'satuan_keluar' => 'required|string|max:50',

            'stok_minimum' => 'nullable|integer|min:0',

            'jumlah' => 'required|integer|min:1',

            'supplier' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use (
                $validated,
                $barangMasuk
            ) {
                $kodeBarang = trim($validated['kode_barang']);

                /*
                 * Cari barang berdasarkan kode baru.
                 */
                $barangBaru = Barang::where(
                    'kode_barang',
                    $kodeBarang
                )->first();

                /*
                 * Kalau barang belum ada,
                 * buat master barang baru.
                 */
                if (!$barangBaru) {
                    $satuanMasuk = strtoupper(
                        trim($validated['satuan_masuk'])
                    );

                    $isiPerSatuan = $satuanMasuk === 'PCS'
                        ? null
                        : (int) $validated['isi_per_satuan'];

                    $barangBaru = Barang::create([
                        'kode_barang' => $kodeBarang,
                        'nama_barang' => $validated['nama_barang'],
                        'detail_ukuran' => $validated['detail_ukuran'] ?? null,

                        'satuan_masuk' => $validated['satuan_masuk'],
                        'isi_per_satuan' => $isiPerSatuan,
                        'satuan_keluar' => $validated['satuan_keluar'],

                        'stok_minimum' => $validated['stok_minimum'] ?? 0,
                    ]);
                }

                $satuanMasuk = strtoupper(
                    trim($barangBaru->satuan_masuk)
                );

                $jumlah = (int) $validated['jumlah'];

                /*
                 * Hitung ulang jumlah dasar.
                 */
                if ($satuanMasuk === 'PCS') {
                    $jumlahDasar = $jumlah;
                } else {
                    if (
                        empty($barangBaru->isi_per_satuan) ||
                        $barangBaru->isi_per_satuan <= 0
                    ) {
                        throw new \Exception(
                            'Konversi isi per satuan untuk barang ini belum diatur.'
                        );
                    }

                    $jumlahDasar = $jumlah *
                        (int) $barangBaru->isi_per_satuan;
                }

                /*
                 * Update transaksi.
                 */
                $barangMasuk->update([
                    'tanggal' => $validated['tanggal'],
                    'barang_id' => $barangBaru->id,

                    'jumlah' => $jumlah,
                    'satuan' => $barangBaru->satuan_masuk,
                    'jumlah_dasar' => $jumlahDasar,

                    'supplier' => $validated['supplier'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Barang masuk berhasil diperbarui.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui barang masuk.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus transaksi barang masuk.
     */
    public function destroy(BarangMasuk $barangMasuk)
    {
        $barangMasuk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi barang masuk berhasil dihapus.',
        ]);
    }
}
