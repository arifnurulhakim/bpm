<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;

use App\Models\Surat_angkut;

use App\Models\Daftar_muat;

use App\Models\Orderan;

use App\Models\Party as parties;

use League\Csv\Writer;

use Dompdf\Dompdf;

use Dompdf\Options;

use Illuminate\Support\Facades\DB;

use LaravelDaily\Invoices\Invoice;

use LaravelDaily\Invoices\Classes\party;

use LaravelDaily\Invoices\Classes\InvoiceItem;





class SuratAngkutController extends Controller

{

    public function index()

    {

        $orderans = Orderan::all();
        $customer = Orderan::select('nama_customer')
        ->where('sisa_jumlah_barang', '>', 0)
        ->distinct()->get();


        $penerima = Orderan::select('nama_penerima')
    ->where('sisa_jumlah_barang', '>', 0)
    ->distinct()
    ->get();


        // $surat_angkut = Surat_angkut::where('jumlah_barang', 0)->get();

        return view('surat_angkut.index',compact('customer','penerima','orderans'));

    }



    public function data()

    {

        $surat_angkut = DB::table('surat_angkuts')

        ->leftJoin('orderans', 'surat_angkuts.kode_tanda_penerima', '=', 'orderans.kode_tanda_penerima')

        ->select('surat_angkuts.*','orderans.tagihan_by', 'orderans.status as status','orderans.tanggal_pengambilan as tanggal_pengambilan','orderans.tanggal_kirim as tanggal_kirim','orderans.tanggal_terima as tanggal_terima','orderans.tanggal_ditagihkan as tanggal_ditagihkan')

        ->get();

        // dd($surat_angkut);

    

    

        return datatables()

            ->of($surat_angkut)

            ->addIndexColumn()

            ->addColumn('update_status', function ($surat_angkut) {

                $disabled = '';

                if ($surat_angkut->status != 2 ) {

                    $disabled = 'disabled';

                }

                return '

                <button type="button" onclick="updateStatus(`'. route('surat_angkut.update_status', $surat_angkut->id_sa) .'`)" class="btn btn-xs btn-warning btn-flat" '. $disabled .'><i class="fa fa-edit"></i> Update Status</button>';

            })

    

            ->addColumn('aksi', function ($surat_angkut) {

                $disabled = '';

                if ($surat_angkut->status > 2  ) {

                    $disabled = 'disabled';

                }

                return '

                    <div class="btn-group">

                        <button type="button" onclick="editForm(`'. route('surat_angkut.update', $surat_angkut->id_sa) .'`)" class="btn btn-xs btn-info btn-flat" '. $disabled .'><i class="fa fa-pencil"></i></button>

                        <button type="button" onclick="deleteData(`'. route('surat_angkut.destroy', $surat_angkut->id_sa) .'`)" class="btn btn-xs btn-danger btn-flat"'. $disabled .'><i class="fa fa-trash"></i></button>

                        <button type="button" onclick="exportPDF(`'. route('surat_angkut.exportPDF', $surat_angkut->id_sa) .'`)" class="btn btn-xs btn-success btn-flat"><i class="fa fa-book"></i></button>

                    </div>

                ';

            })

            ->rawColumns(['aksi', 'update_status'])

            ->make(true);

    }

    public function update_status($id){

        $surat_angkut = Surat_angkut::find($id);

        $orderan = Orderan::where('kode_tanda_penerima', $surat_angkut->kode_tanda_penerima)->first();

        // dd( $surat_angkut);

            if ($orderan) {

                $orderan->status = 3;

                $orderan->tanggal_terima = now();

                $orderan->update();

                $orderans = Orderan::all();

                $customer = Orderan::select('nama_customer')
                ->where('sisa_jumlah_barang', '>', 0)
                ->distinct()->get();
        
        
                $penerima = Orderan::select('nama_penerima')
                ->where('sisa_jumlah_barang', '>', 0)
                ->distinct()
                ->get();
        
        
                // $surat_angkut = Surat_angkut::where('jumlah_barang', 0)->get();
        
                return view('surat_angkut.index',compact('customer','penerima','orderans'));
        
            }
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

        $get_customer = $request->nama_customer;
        $get_penerima = $request->nama_penerima;
        $get_jumlah = $request->total_jumlah_barang;
        $orderan = Orderan::where('nama_customer', $get_customer)
        ->where('nama_penerima',$get_penerima)
        ->where('jumlah_barang',$get_jumlah) 
        ->where('sisa_jumlah_barang', '>', 0)
        ->first();
        $get_sisa_jumlah_barang = $orderan->sisa_jumlah_barang;

        // dd($orderan);

        if(!empty($orderan)){
            $sisa_jumlah_barang= "";
            if($get_sisa_jumlah_barang == 0){
                $sisa_jumlah_barang = $get_jumlah - $request->jumlah_barang;
            }else{
                $sisa_jumlah_barang = $get_sisa_jumlah_barang - $request->jumlah_barang;
            }

           
            $Surat_angkut = new Surat_angkut();

            $Surat_angkut->nomor_sa = $request->nomor_sa;

            $Surat_angkut->kode_tanda_penerima = $orderan->kode_tanda_penerima;

            $Surat_angkut->nama_customer = $orderan->nama_customer;

            $Surat_angkut->alamat_customer = $orderan->alamat_customer;

            $Surat_angkut->telepon_customer = $orderan->telepon_customer;

            $Surat_angkut->nama_barang = $orderan->nama_barang;

            $Surat_angkut->total_jumlah_barang = $request->total_jumlah_barang;

            $Surat_angkut->jumlah_barang = $request->jumlah_barang;

            $Surat_angkut->sisa_jumlah_barang = $sisa_jumlah_barang;

            // $Surat_angkut->berat_barang = $orderan->berat_barang;

            $Surat_angkut->nama_penerima = $orderan->nama_penerima;

            $Surat_angkut->alamat_penerima = $orderan->alamat_penerima;

            $Surat_angkut->telepon_penerima = $orderan->telepon_penerima;

            // $Surat_angkut->supir = $orderan->supir;

            // $Surat_angkut->no_mobil = $orderan->no_mobil;

            $Surat_angkut->keterangan = $orderan->keterangan;

            $Surat_angkut->tanggal_pengambilan = $orderan->tanggal_pengambilan;

            // $Surat_angkut->harga = $orderan->harga;

       

            $Surat_angkut->save();

            $orderan->sisa_jumlah_barang = $get_jumlah - $request->jumlah_barang;
            $orderan->update();

            

            // if(!empty($Surat_angkut)){

            //     $daftar_muat = new Daftar_muat();

            //     // Memeriksa apakah ada data di database dengan nilai supir dan no_mobil yang sama

            //     $existing_daftar_muat = Daftar_muat::select('kode_daftar_muat')->where('supir', $orderan->supir)

            //     ->where('no_mobil', $orderan->no_mobil)

            //     ->first();



            //     if ($existing_daftar_muat) {

            //     // Jika data sudah ada, gunakan kode_daftar_muat yang sama dengan data yang ada

            //     $daftar_muat->kode_daftar_muat = $existing_daftar_muat->kode_daftar_muat;

            //     } else {

            //     // Jika data tidak ada, jalankan langkah-langkah seperti biasa

              

            //     $max_kode = Daftar_muat::max('kode_daftar_muat');

            //     $new_kode = $max_kode + 1;



            //     $daftar_muat->kode_daftar_muat = $new_kode;

            //     }

            //     $total_harga = ($orderan->jumlah_barang * $orderan->berat_barang) * $orderan->harga;

                

            //     $daftar_muat->nomor_sa = $request->nomor_sa;

            //     $daftar_muat->supir = $orderan->supir;

            //     $daftar_muat->no_mobil = $orderan->no_mobil;

            //     $daftar_muat->nama_customer = $orderan->nama_customer;

            //     $daftar_muat->nama_penerima = $orderan->nama_penerima;

            //     $daftar_muat->jumlah_barang = $orderan->jumlah_barang;

            //     $daftar_muat->berat_barang = $orderan->berat_barang;

            //     $daftar_muat->harga = $orderan->harga;

            //     $daftar_muat->total_harga =$total_harga;





            //     $daftar_muat->keterangan = $orderan->keterangan;

            //     $daftar_muat->save();

            

            // }

            // if (!empty($daftar_muat)){

            //     $parties = new parties();

            //     // Memeriksa apakah ada data di database dengan nilai supir dan no_mobil yang sama

            //     $existing_parties = parties::select('kode_party')

            //     ->where('nama_customer', $orderan->nama_customer)

            //     ->where('nama_penerima', $orderan->nama_penerima)

            //     ->first();



            //     if ($existing_parties) {

            //     // Jika data sudah ada, gunakan kode_daftar_muat yang sama dengan data yang ada

            //     $parties->kode_party = $existing_parties->kode_party;

            //     } else {

            //     // Jika data tidak ada, jalankan langkah-langkah seperti biasa

              

            //     $max_kode = parties::max('kode_party');

            //     $new_kode = $max_kode + 1;



            //     $parties->kode_party =  $new_kode;

            //     }

            //     $total_harga = ($orderan->jumlah_barang * $orderan->berat_barang) * $orderan->harga;



            //     $parties->kode_dm = $daftar_muat->kode_daftar_muat;

            //     $parties->nomor_sa = $request->nomor_sa;

            //     $parties->nama_customer = $orderan->nama_customer;

            //     $parties->alamat_customer = $orderan->alamat_customer;

            //     $parties->telepon_customer = $orderan->telepon_customer;



            //     $parties->jumlah_barang = $orderan->jumlah_barang;

            //     $parties->berat_barang = $orderan->berat_barang;



            //     $parties->nama_penerima = $orderan->nama_penerima;

            //     $parties->alamat_penerima = $orderan->alamat_penerima;

            //     $parties->telepon_penerima = $orderan->telepon_penerima;



            //     $parties->supir = $orderan->supir;

            //     $parties->no_mobil = $orderan->no_mobil;

            //     $parties->keterangan = $orderan->keterangan;

                



            //     $parties->save();





            // }

            return response()->json('berhasil', 200);

        }else{

            return response()->json('gagal', 200);

        }

        

    }



    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function show($id)

    {

        $surat_angkut = Surat_angkut::find($id);



        return response()->json($surat_angkut);

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

    //     $get_orderan = $request->kode_tanda_penerima;

        

    //     // $orderan = Orderan::where('kode_tanda_penerima', $get_orderan)

    //     // ->where('status', 2)->first();



    //     if(!empty($orderan)){

    //         $Surat_angkut = Surat_angkut::find($id);

    //         $berat_barang = $orderan->berat_barang;

    //         $Surat_angkut->nomor_sa = $request->nomor_sa;

    //         $Surat_angkut->kode_tanda_penerima = $request->kode_tanda_penerima;

    //         $Surat_angkut->nama_customer = $request->nama_customer;

    //         $Surat_angkut->alamat_customer = $request->alamat_customer;

    //         $Surat_angkut->telepon_customer = $request->telepon_customer;

    //         $Surat_angkut->nama_barang = $request->nama_barang;

    //         $Surat_angkut->jumlah_barang = $orderan->jumlah_barang;

    //         $Surat_angkut->berat_barang = $orderan->berat_barang;

    //         $Surat_angkut->nama_penerima = $orderan->nama_penerima;

    //         $Surat_angkut->alamat_penerima = $orderan->alamat_penerima;

    //         $Surat_angkut->telepon_penerima = $orderan->telepon_penerima;

    //         $Surat_angkut->supir = $orderan->supir;

    //         $Surat_angkut->no_mobil = $orderan->no_mobil;

    //         $Surat_angkut->keterangan = $orderan->keterangan;

    //         $Surat_angkut->tanggal_pengambilan = $orderan->tanggal_pengambilan;

    //         $Surat_angkut->harga = $orderan->harga;

    //         $Surat_angkut->update();

    //         return response()->json('berhasil', 200);

    //     }else{

    //         return response()->json('gagal', 200);

    // }
    $get_customer = $request->nama_customer;
    $get_penerima = $request->nama_penerima;
    $get_jumlah = $request->total_jumlah_barang;

    $orderan = Orderan::where('nama_customer', $get_customer)
    ->where('nama_penerima',$get_penerima)
    ->where('jumlah_barang',$get_jumlah) 
    ->where('sisa_jumlah_barang', '>', 0)
    ->first();

    if(!empty($orderan)){

        $Surat_angkut = new Surat_angkut();

        $Surat_angkut->nomor_sa = $request->nomor_sa;

        $Surat_angkut->kode_tanda_penerima = $orderan->kode_tanda_terima;

        $Surat_angkut->nama_customer = $orderan->nama_customer;

        $Surat_angkut->alamat_customer = $orderan->alamat_customer;

        $Surat_angkut->telepon_customer = $orderan->telepon_customer;

        $Surat_angkut->nama_barang = $orderan->nama_barang;

        $Surat_angkut->total_jumlah_barang = $request->total_jumlah_barang;

        $Surat_angkut->jumlah_barang = $request->jumlah_barang;

        $Surat_angkut->jumlah_sisa_barang = $get_jumlah - $request->jumlah_barang;

        // $Surat_angkut->berat_barang = $orderan->berat_barang;

        $Surat_angkut->nama_penerima = $orderan->nama_penerima;

        $Surat_angkut->alamat_penerima = $orderan->alamat_penerima;

        $Surat_angkut->telepon_penerima = $orderan->telepon_penerima;

        // $Surat_angkut->supir = $orderan->supir;

        // $Surat_angkut->no_mobil = $orderan->no_mobil;

        $Surat_angkut->keterangan = $orderan->keterangan;

        $Surat_angkut->tanggal_pengambilan = $orderan->tanggal_pengambilan;

        // $Surat_angkut->harga = $orderan->harga;

   

        $Surat_angkut->save();

        $orderan->sisa_jumlah_barang = $get_jumlah - $request->jumlah_barang;
        $orderan->update();

        

        
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

        $surat_angkut = Surat_angkut::find($id);

        $orderan = Orderan::where('kode_tanda_penerima', $surat_angkut->kode_tanda_terima)->first();

        // if($orderan->status == 2){

        //     return response(null, 422);

        // }else{

            $surat_angkut->delete();

            return response(null, 204);

        // }



        

    }



    public function deleteSelected(Request $request)

    {

        foreach ($request->surat_angkut as $id) {

            $surat_angkut = Surat_angkut::find($id);

            $surat_angkut->delete();

        }



        return response(null, 204);

    }



    public function exportCSV()

{

    $surat_angkut = Surat_angkut::get()->toArray();



    $headers = [

        'Content-Type' => 'text/csv',

        'Content-Disposition' => 'attachment; filename="sa_' . date('Ymd_His') . '.csv"',

    ];



    $callback = function () use ($surat_angkut) {

        $file = fopen('php://output', 'w');

        fputcsv($file, array_keys($surat_angkut[0]));

        foreach ($surat_angkut as $row) {

            fputcsv($file, $row);

        }

        fclose($file);

    };



    return response()->stream($callback, 200, $headers);

}


public function exportPDF($id)
{
    $surat_angkut = Surat_angkut::find($id);
    $orderan = Orderan::where('kode_tanda_penerima', $surat_angkut->kode_tanda_penerima)->first();
 
    $orderan->status=2;
    if (!$orderan->status==null){
        $orderan->status = 2;
    }else{
        $orderan->status = '';
    }

    $orderan->tanggal_kirim = now();
    $orderan->update();

    // Membuat file PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml(view('surat_angkut.pdf', compact('surat_angkut'))->render());
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Menghasilkan file PDF dan mengirimkan ke browser
    $pdfFileName = 'surat_angkut' . $surat_angkut->nomor_sa . $surat_angkut->created_at . '.pdf';
    $dompdf->stream($pdfFileName);

    // Return a response or redirect as needed
}


// public function exportPDF(Request $request)

// {

//     // Mendapatkan data dari database atau input form

//     //    $surat_angkut = Surat_angkut::find($id);

//     $id_sa = 1;

//     $nomor_sa = 'SA001';

//     $kode_tanda_penerima = 'KTP001';

//     $nama_customer = 'John Doe';

//     $alamat_customer = 'Jl. Raya No.123';

//     $telepon_customer = '08123456789';

//     $nama_barang = 'Sepatu';

//     $jumlah_barang = 2;

//     $berat_barang = '1 kg';

//     $nama_penerima = 'Jane Doe';

//     $alamat_penerima = 'Jl. Raya No.456';

//     $telepon_penerima = '08987654321';

//     $supir = 'Budi';

//     $no_mobil = 'B 1234 XYZ';

//     $keterangan = 'Pengiriman Cepat';

//     $tanggal_pengambilan = '2023-03-06';

//     $tanggal_kirim = '2023-03-07';

//     $tanggal_terima = '2023-03-08';

//     $harga = 250000;



//     // Membuat instance Invoice

//     $invoice = Invoice::make('surat');



//     // Mengisi data invoice

//     $invoice->series('INV')

//             ->sequence($id_sa)

//             ->getDate(now()->addDays(14))

//             ->currencySymbol('Rp')

//             ->currencyCode('IDR')

//             ->currencyFormat('{VALUE}')

//             ->taxRate(10)

//             ->discount(0)

//             ->shipping(0)

//             ->addItem(new InvoiceItem([

//                 'name' => $nama_barang,

//                 'quantity' => $jumlah_barang,

//                 'price' => $harga,

//                 'discount' => 0,

//                 'tax' => 0

//             ]))

//             ->customer(new Party([

//                 'name' => $nama_customer,

//                 'address' => $alamat_customer,

//                 'city' => '',

//                 'zip' => '',

//                 'country' => '',

//                 'phone' => $telepon_customer,

//                 'custom_fields' => [

//                     'Kode Tanda Penerima' => $kode_tanda_penerima,

//                 ],

//             ]))

//             ->shippingTo(new Party([

//                 'name' => $nama_penerima,

//                 'address' => $alamat_penerima,

//                 'city' => '',

//                 'zip' => '',

//                 'country' => '',

//                 'phone' => $telepon_penerima,

//                 'custom_fields' => [

//                     'Supir' => $supir,

//                     'No. Mobil' => $no_mobil,

//                 ],

//             ]))

//             ->notes($keterangan)

//             ->date($tanggal_terima)

//             ->taxRate($harga * 0.1);



//     // Mendownload invoice dalam format PDF

//     return $invoice->download();

// }



}

