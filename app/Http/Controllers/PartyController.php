<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Surat_angkut;
use App\Models\Orderan;
use App\Models\Customer;
use App\Models\Penerima;
use App\Models\Daftar_muat;
use App\Models\Party;
use Illuminate\Support\Facades\DB;
class PartyController extends Controller
{
    public function index()
    {
        $surat_angkut = Surat_angkut::all();

        return view('party.index',compact('surat_angkut'));

    }

    public function data()
    {
    $party = DB::table('parties')
    ->select('parties.*', 'surat_angkuts.*','orderans.id_harga', 'orderans.tagihan_by', 'orderans.status as status', 'orderans.tanggal_pengambilan as tanggal_pengambilan', 'orderans.tanggal_kirim as tanggal_kirim', 'orderans.tanggal_terima as tanggal_terima', 'orderans.tanggal_ditagihkan as tanggal_ditagihkan', 'hargas.*')
    ->leftJoin('surat_angkuts', 'parties.nomor_sa', '=', 'surat_angkuts.nomor_sa')
    ->leftJoin('orderans', 'surat_angkuts.kode_tanda_penerima', '=', 'orderans.kode_tanda_penerima')
    ->leftJoin('hargas', 'orderans.id_harga', '=', 'hargas.id_harga')
    ->distinct()
    ->addSelect(DB::raw('
        CASE
            WHEN orderans.jenis_berat = "roll" THEN
                CASE
                    WHEN surat_angkuts.jumlah_barang < hargas.syarat_jumlah THEN surat_angkuts.jumlah_barang * hargas.diskon_roll
                    ELSE surat_angkuts.jumlah_barang * hargas.harga_roll
                END
            WHEN orderans.jenis_berat = "ball" THEN
                CASE
                    WHEN surat_angkuts.jumlah_barang < hargas.syarat_jumlah THEN surat_angkuts.jumlah_barang * hargas.diskon_ball
                    ELSE surat_angkuts.jumlah_barang * hargas.harga_ball
                END
            WHEN orderans.jenis_berat = "tonase" THEN
                CASE
                    WHEN parties.berat_barang < hargas.main_syarat_berat THEN
                        CASE
                            WHEN hargas.sub_syarat_berat IS NOT NULL THEN
                                    
                                (hargas.sub_syarat_berat * hargas.diskon_tonase_sub) + ((parties.berat_barang-hargas.sub_syarat_berat) * hargas.diskon_tonase)
                                    
                            END
                       
                    ELSE
                        parties.berat_barang * hargas.harga_tonase
                END
            ELSE 0
        END AS total_harga
    '))
    ->get();

        return datatables()
            ->of($party)
            ->addIndexColumn()
            ->addColumn('aksi', function ($party) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="deleteData(`'. route('party.destroy', $party->id_party) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   
    public function store(Request $request)
    {
        $nomor_sa = $request->nomor_sa;
        // dd($nomor_sa);
        $surat_angkut = Surat_angkut::where('nomor_sa', $nomor_sa)->first();
        // dd($surat_angkut);
        $customer = Customer::where('nama_customer', $surat_angkut->nama_customer)->first();
        $penerima = Penerima::where('nama_penerima', $surat_angkut->nama_penerima)->first();

                $parties = new party();

                // Memeriksa apakah ada data di database dengan nilai supir dan no_mobil yang sama
                $parties->nomor_party = $request->nomor_party;

                $parties->nomor_dm = $request->nomor_party;

                $parties->nomor_sa = $surat_angkut->nomor_sa;

                $parties->nama_customer = $surat_angkut->nama_customer;

                $parties->alamat_customer = $surat_angkut->alamat_customer;

                $parties->telepon_customer = $surat_angkut->telepon_customer;

                $parties->total_jumlah_barang = $surat_angkut->total_jumlah_barang;

                $parties->jumlah_barang = $surat_angkut->jumlah_barang;

                $parties->berat_barang = $request->berat_barang;



                $parties->nama_penerima = $surat_angkut->nama_penerima;

                $parties->alamat_penerima = $surat_angkut->alamat_penerima;

                $parties->telepon_penerima = $surat_angkut->telepon_penerima;

                $parties->supir = $request->supir;

                $parties->no_mobil = $request->no_mobil;
                
                $parties->keterangan = $surat_angkut->keterangan;
                
                $parties->tanggal_pembuatan = $request->tanggal_pembuatan;
                $parties->save();
                return response()->json('Data berhasil disimpan', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $party = Party::find($id);

        return response()->json($party);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $get_party = $request->kode_party;
        
        $party = Party::where('kode_party', $get_party)->first();
        if(!empty($party)){
            $party = Party::find($id);

            $party->nomor_sa = $request->nomor_sa;

            $party->nama_customer = $request->nama_customer;
            $party->alamat_customer = $request->alamat_customer;
            $party->telepon_customer = $request->telepon_customer;

            $party->total_jumlah_barang = $request->total_jumlah_barang;
            $party->total_berat_barang = $request->total_berat_barang;

            $party->nama_penerima = $request->nama_penerima;
            $party->alamat_penerima = $request->alamat_penerima;
            $party->telepon_penerima = $request->telepon_penerima;

            $party->supir = $orderan->supir;
            $party->no_mobil = $orderan->no_mobil;
            $party->keterangan = $request->keterangan;
            $party->update();
            return response()->json('berhasil', 200);
        }else{
            return response()->json('gagal', 200);
    }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $party = Party::find($id)->delete();

        return response(null, 204);
    }

    public function deleteSelected(Request $request)
    {
        foreach ($request->party as $id) {
            $party = Party::find($id);
            $party->delete();
        }

        return response(null, 204);
    }

    public function exportCSV()
{
    $party = Party::get()->toArray();

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="party_' . date('Ymd_His') . '.csv"',
    ];

    $callback = function () use ($party) {
        $file = fopen('php://output', 'w');
        fputcsv($file, array_keys($party[0]));
        foreach ($party as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
public function exportfilter(Request $request)
{
    $kode_party = $request->kode_party;
    $kode_dm = $request->kode_dm;
    $nomor_sa = $request->nomor_sa;
    $nama_customer = $request->nama_customer;
    $nama_penerima = $request->nama_penerima;
    $supir = $request->supir;
    $no_mobil = $request->no_mobil;
    $tanggal_awal = $request->tanggal_awal;
    $tanggal_akhir = $request->tanggal_akhir;

    $party = DB::table('parties')
        ->select(
            'parties.tanggal_pembuatan',
            'parties.nomor_dm', 
            'parties.nomor_sa', 
            'parties.nama_customer', 
            'parties.nama_penerima',
            'parties.jumlah_barang', 
            'parties.berat_barang',
            'orderans.id_harga',  
            'orderans.jenis_berat', 

            )
        ->leftJoin('surat_angkuts', 'parties.nomor_sa', '=', 'surat_angkuts.nomor_sa')
        ->leftJoin('orderans', 'surat_angkuts.kode_tanda_penerima', '=', 'orderans.kode_tanda_penerima')
        ->leftJoin('hargas', 'orderans.id_harga', '=', 'hargas.id_harga')
        ->distinct();
        if('orderans.jenis_berat'=='roll'){
            if('surat_angkuts.jumlah_barang < hargas.syarat_jumlah'){
                $party->addSelect(DB::raw('hargas.diskon_roll as harga'));
            }else{
            $party->addSelect(DB::raw('hargas.harga_roll as harga'));
            }
        }
        else if('orderans.jenis_berat'=='ball'){
            if('surat_angkuts.jumlah_barang < hargas.syarat_jumlah'){
                $party->addSelect(DB::raw('hargas.diskon_ball as harga'));
            }else{
            $party->addSelect(DB::raw('hargas.harga_ball as harga'));
            }
        }
        else{
            if('parties.berat_barang < hargas.main_syarat_berat'){
                if('hargas.sub_syarat_berat IS NOT NULL'){
                    $party->addSelect(DB::raw("CONCAT(hargas.diskon_tonase, ',', diskon_tonase_sub) as harga"));
                }else{
                    $party->addSelect(DB::raw('hargas.diskon_tonase as harga'));
                }
            }else{
            $party->addSelect(DB::raw('hargas.harga_tonase as harga'));
            }
        }
        $party->addSelect(DB::raw('
            CASE
                WHEN orderans.jenis_berat = "roll" THEN
                    CASE
                        WHEN surat_angkuts.jumlah_barang < hargas.syarat_jumlah THEN surat_angkuts.jumlah_barang * hargas.diskon_roll
                        ELSE surat_angkuts.jumlah_barang * hargas.harga_roll
                    END
                WHEN orderans.jenis_berat = "ball" THEN
                    CASE
                        WHEN surat_angkuts.jumlah_barang < hargas.syarat_jumlah THEN surat_angkuts.jumlah_barang * hargas.diskon_ball
                        ELSE surat_angkuts.jumlah_barang * hargas.harga_ball
                    END
                WHEN orderans.jenis_berat = "tonase" THEN
                    CASE
                        WHEN parties.berat_barang < hargas.main_syarat_berat THEN
                            CASE
                                WHEN hargas.sub_syarat_berat IS NOT NULL THEN
                                    (hargas.sub_syarat_berat * hargas.diskon_tonase_sub) + ((parties.berat_barang-hargas.sub_syarat_berat) * hargas.diskon_tonase)
                                END
                        ELSE
                            parties.berat_barang * hargas.harga_tonase
                    END
                ELSE 0
            END AS total_harga
        '));
    
    if ($kode_party) {
        $party->where('nomor_party', $kode_party);
    }

    if ($kode_dm) {
        $party->where('nomor_dm', $kode_dm);
    }

    if ($nomor_sa) {
        $party->where('nomor_sa', $nomor_sa);
    }

    if ($nama_customer) {
        $party->where('nama_customer', $nama_customer);
    }

    if ($nama_penerima) {
        $party->where('nama_penerima', $nama_penerima);
    }

    if ($supir) {
        $party->where('supir', $supir);
    }

    if ($no_mobil) {
        $party->where('no_mobil', $no_mobil);
    }

    if ($tanggal_awal && $tanggal_akhir) {
        $party->whereBetween('tanggal_pembuatan', [$tanggal_awal, $tanggal_akhir]);
    }
    else if ($tanggal_awal) {
        $party->where('tanggal_pembuatan', $tanggal_awal);
    }


    $results = $party->get();

    

    // dd($results);

    $total_berat = 0;
    $total_semua_harga = 0;
    $total_jumlah_barang = 0;
    foreach ($results as $pt) {
        $total_berat += $pt->berat_barang;
        $total_semua_harga += $pt->total_harga;
        $total_jumlah_barang += $pt->jumlah_barang;
    }

    $party = $results->toArray(); // Convert the results to an array


    $party[] = ['Total Berat Barang', $total_berat, '', '', '', ''];
    $party[] = ['Total Semua Harga', $total_semua_harga, '', '', '', ''];
    $party[] = ['Total Jumlah Barang', $total_jumlah_barang, '', '', '', ''];
    $party[] = ['', '', '', '', '', ''];
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="party_' . date('Ymd_His') . '.csv"',
    ];

    $callback = function () use ($party) {
        $file = fopen('php://output', 'w');
        fputcsv($file, array_keys((array) $party[0])); // Convert the first object to an array for headers
        foreach ($party as $row) {
            fputcsv($file, (array) $row); // Convert each object to an array for rows
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers,);

    $party = $results->toArray(); // Convert the results to an array
    
    $party[] = ['Total Berat Barang', $total_berat, '', '', '', ''];
    $party[] = ['Total Semua Harga', $total_semua_harga, '', '', '', ''];
    $party[] = ['Total Jumlah Barang', $total_jumlah_barang, '', '', '', ''];
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="party_' . date('Ymd_His') . '.csv"',
    ];
    
    $callback = function () use ($party) {
        $file = fopen('php://output', 'w');
        fputcsv($file, array_keys($party[0])); // Use $party[0] directly since it's already an array
        foreach ($party as $row) {
            fputcsv($file, $row); // Use $row directly since it's already an array
        }
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}
}
