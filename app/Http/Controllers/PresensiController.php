<?php

namespace App\Http\Controllers;

use App\Models\Pengajuanizin;
use App\Models\Presensi;
use App\Models\Murid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class PresensiController extends Controller
{
    public function create()
    {
        $harini = date("Y-m-d");
        $nisn = Auth::guard('murid')->user()->nisn;
        $cek = DB::table('presensi')->where('tgl_presensi', $harini)->where('nisn', $nisn)->count();
        $lok_kantor = DB::table('konfigurasi_lokasi')->where('id',1)->first();
        return view('presensi.create', compact('cek','lok_kantor'));
    }

    public function cekAbsen(Request $request)
    {
        $nisn = $request->query('nisn');

        $presensi = Presensi::where('nisn', $nisn)
            ->whereDate('tgl_presensi', Carbon::today()) // Hanya untuk hari ini
            ->first();

        if ($presensi) {
            return response()->json([
                'absen_masuk' => !empty($presensi->jam_in), // True jika jam_in sudah ada
                'absen_pulang' => empty($presensi->jam_out) // True jika jam_out masih NULL
            ]);
        }

        // Jika belum ada absensi, maka belum absen masuk & belum absen pulang
        return response()->json([
            'absen_masuk' => false,
            'absen_pulang' => false
        ]);
    }

    public function cekJarak(Request $request)
    {
        // Ambil lokasi siswa dari parameter request
        $lokasi = $request->lokasi;
        $lokasiuser = explode(',', $lokasi);
        $latitudeuser = $lokasiuser[0];
        $longitudeuser = $lokasiuser[1];

        // Ambil lokasi kantor dari database
        $lok_kantor = DB::table('konfigurasi_lokasi')->where('id',1)->first();
        $lok = explode(",", $lok_kantor->lokasi_kantor);
        $latitudekantor = $lok[0];
        $longitudekantor = $lok[1];
        $radius = $lok_kantor->radius; // dalam meter

        // Hitung jarak menggunakan Haversine formula
        $jarak = $this->distance($latitudekantor, $longitudekantor, $latitudeuser, $longitudeuser);
        $radius_siswa = round($jarak["meters"]);
        //dd($radius_siswa);

        // Bandingkan jarak dengan radius
        if ($jarak["meters"] <= $radius) {
            return response()->json([
                'status' => 'dalam_jangkauan',
                'jarak' => $radius_siswa
            ]);
        } else {
            return response()->json([
                'status' => 'luar_jangkauan',
                'jarak' => $radius_siswa
            ]);
        }
    }



    public function store(Request $request)
    {
        $nisn = Auth::guard('murid')->user()->nisn;

        $murid = DB::table('murid')->where('nisn', $nisn)->first(); // Ambil data murid berdasarkan NISN

        if (!$murid || !$murid->no_hp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor HP orang tua tidak ditemukan untuk murid ini.'
            ], 404);
        }

        $noHpOrangTua = $murid->no_hp; // Nomor HP orang tua murid

        $tgl_presensi = date("Y-m-d");
        $jam = date("H:i:s");
        $lok_kantor = DB::table('konfigurasi_lokasi')->where('id',1)->first();
        $radius_sekolah = DB::table('konfigurasi_lokasi')->where('id', 1)->value('radius');
        //dd($radius_sekolah);
        $lok = explode(",",$lok_kantor->lokasi_kantor);
        $latitudekantor = $lok[0];
        //dd($latitudekantor);
        $longitudekantor = $lok[1];
        $lokasi = $request->lokasi;
        $absen = $request->absen;
        $lokasiuser = explode(',', $lokasi);
        $latitudeuser = $lokasiuser[0];
        //dd($latitudeuser);
        $longitudeuser = $lokasiuser[1];

        $jarak = $this->distance($latitudekantor, $longitudekantor, $latitudeuser, $longitudeuser);
        //dd($jarak);
        $radius_siswa = round($jarak["meters"]);
        //dd($radius_siswa);

        $cek = DB::table('presensi')->where('tgl_presensi', $tgl_presensi)->where('nisn', $nisn)->first();

        if($radius_siswa > $radius_sekolah) {
            echo "error|Maaf Anda berada di luar radius, jarak anda " . $radius_siswa ." meter dari sekolah.|";
            //echo "error|Maaf Anda berada di luar sekolah.|";
        } else {
            if($absen === 'masuk') {
                if(!$cek) {
                    $data = [
                        'nisn' => $nisn,
                        'tgl_presensi' => $tgl_presensi,
                        'jam_in' => $jam,
                        'lokasi_in' => $lokasi
                    ];
                    $simpan = DB::table('presensi')->insert($data);
                    if($simpan){
                        // Kirim notifikasi WhatsApp untuk presensi masuk
                        //dd($this->sendWhatsAppNotification($noHpOrangTua, "Lemak Anda telah hadir di sekolah pada {$jam}. Selamat belajar!"));
                        $this->sendWhatsAppNotification($noHpOrangTua, "Lemak Anda telah hadir di sekolah pada {$jam}. Selamat belajar!");
                        echo "success|Terimakasi, Selamat Belajar Di Kelas|in";
                    } else {
                        echo "error|Maaf Gagal Absen, Hubungi Petugas IT Sekolah|in";
                    }                    
                } else {
                    echo "error|Anda sudah melakukan absen masuk hari ini.|in";
                }
            } elseif($absen === 'pulang') {
                if (!$cek) {
                    return response("error|Anda belum melakukan absen masuk.|out");
                }
        
                if ($cek->jam_out) {
                    return response("error|Anda sudah melakukan absen pulang hari ini.|out");
                }
        
                // Update absen pulang
                $data_pulang = [
                    'jam_out' => $jam,
                    'lokasi_out' => $lokasi
                ];
        
                $update = DB::table('presensi')
                    ->where('tgl_presensi', $tgl_presensi)
                    ->where('nisn', $nisn)
                    ->update($data_pulang);
                
                if($update){
                    // Kirim notifikasi WhatsApp untuk presensi pulang
                    $this->sendWhatsAppNotification($noHpOrangTua, "Murid Anda telah pulang pada {$jam}. Terima kasih!");
                    echo "success|Terimakasi, Hati Hati Di Jalan Pulang|out";
                } else {
                    echo "error|Maaf Gagal Absen, Hubungi Petugas IT Sekolah|out";
                }                 
            } else {
                echo "error|Anda belum melakukan absen masuk.|out";
            }
        }
    }

        /**
     * Fungsi untuk mengirim notifikasi WhatsApp.
     */
    function sendWhatsAppNotification($target, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => '8Xj94HfAUG6LakYdwfN5', // Token API Fonnte Anda
        ])->withOptions([
            'verify' => base_path('storage/app/cacert.pem'), // Lokasi file cacert.pem
        ])->post('https://api.fonnte.com/send', [
            'target' => $target, // Nomor HP tujuan
            'message' => $message, // Pesan notifikasi
        ]);

        return $response->successful();
    }


    // Menghitung Jarak
    function distance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return compact('meters');
    }

    public function editprofile()
    {
        $nisn = Auth::guard('murid')
            ->user()
            ->nisn;

        $murid = DB::table('murid')
            ->where('nisn', $nisn)
            ->first();

        return view('presensi.editprofile', compact('murid'));
    }

    public function updateprofile(Request $request)
    {
        $nisn = Auth::guard('murid')
            ->user()
            ->nisn;

        $nama_lengkap = $request
            ->nama_lengkap;

        $no_hp = $request
            ->no_hp;

        $password = Hash::make($request->password);
        $murid = DB::table('murid')
            ->where('nisn', $nisn)
            ->first();

        // if($request->hasFile('foto')){
        //     $foto = $nisn.".".$request
        //         ->file('foto')
        //         ->getClientOriginalExtension();
        // } else {
        //     $foto = $murid->foto;
        // }

        if (empty($request->password)) {
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_hp' => $no_hp
                // 'foto' => $foto
            ];
        } else {
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_hp' => $no_hp,
                'password' => $password
                // 'foto' => $foto
            ];
        }

        $update = DB::table('murid')
            ->where('nisn', $nisn)
            ->update($data);

        if($update){
            // if($request->hasFile('foto')){
            //     $folderpath = "public/uploads/murid/";
            //     $request
            //         ->file('foto')
            //         ->storeAs($folderpath, $foto);
            // }
            return Redirect::back()
                ->with(['success' => 'Data Berhasil Di Update']);
        }else{
            return Redirect::back()
                ->with(['error' => 'Data Gagal Di Update']);
        }
    }

    public function histori()
    {
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        return view('presensi.histori', compact('namabulan'));
    }

    public function gethistori(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $nisn = Auth::guard('murid')
            ->user()
            ->nisn;

        $histori = DB::table('presensi')
            ->whereRaw('MONTH(tgl_presensi)="' . $bulan . '"')
            ->whereRaw('YEAR(tgl_presensi)="' . $tahun . '"')
            ->where('nisn', $nisn)
            ->orderBy('tgl_presensi')
            ->get();

        return view('presensi.gethistori', compact('histori'));
    }

    public function izin()
    {
        $nisn = Auth::guard('murid')
            ->user()
            ->nisn;
        $dataizin = DB::table('pengajuan_izin')
            ->where('nisn',$nisn)
            ->get();
        return view('presensi.izin', compact('dataizin'));
    }

    public function buatizin()
    {
        return view('presensi.buatizin');
    }

    public function storeizin(Request $request)
    {
        $nisn = Auth::guard('murid')
            ->user()
            ->nisn;
        $tgl_izin = $request->tgl_izin;
        $status = $request->status;
        $keterangan = $request->keterangan;

        $data = [
            'nisn' => $nisn,
            'tgl_izin' => $tgl_izin,
            'status' => $status,
            'keterangan' => $keterangan
        ];

        $simpan = DB::table('pengajuan_izin')
            ->insert($data);

        if($simpan){
            return redirect('/presensi/izin')
                ->with(['success' => 'Data Berhasil Disimpan']);
        } else {
            return redirect('/presensi/izin')
                ->with(['error' => 'Data Gagal Disimpan']);
        }
    }

    public function monitoring()
    {
        return view('presensi.monitoring');
    }

    public function getpresensi(Request $request)
    {
        $tanggal = $request->tanggal;
        $presensi = DB::table('presensi')
            ->select('presensi.*','nama_lengkap','nama_jurusan')
            ->join('murid','presensi.nisn','=','murid.nisn')
            ->join('jurusan','murid.kode_jurusan','=','jurusan.kode_jurusan')
            ->where('tgl_presensi',$tanggal)
            ->get();

        return view('presensi.getpresensi', compact('presensi'));
    }

    public function tampilkanpeta(Request $request)
    {
        $id = $request->id;
        $presensi = DB::table('presensi')
            ->join('murid','presensi.nisn','=','murid.nisn')
            ->where('id', $id)
            ->first();

        return view('presensi.showmap', compact('presensi'));
    }

    public function laporan()
    {
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        $murid = DB::table('murid')
            ->orderBy('nama_lengkap')
            ->get();

        return view('presensi.laporan', compact('namabulan','murid'));
    }

    public function cetaklaporan(Request $request)
    {
        $nisn = $request->nisn;
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        $murid = DB::table('murid')
            ->where('nisn',$nisn)
            ->join('jurusan','murid.kode_jurusan','=','jurusan.kode_jurusan')
            ->first();

        $presensi = DB::table('presensi')
            ->where('nisn', $nisn)
            ->whereRaw('MONTH(tgl_presensi)="'.$bulan.'"')
            ->whereRaw('YEAR(tgl_presensi)="'.$tahun.'"')
            ->orderBy('tgl_presensi')
            ->get();

        return view('presensi.cetaklaporan', compact('bulan','tahun','namabulan','murid','presensi'));
    }

    public function rekap()
    {
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];

        return view('presensi.rekap', compact('namabulan'));
    }

    public function cetakrekap(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        $rekap = DB::table('presensi')
            ->selectRaw('presensi.nisn,nama_lengkap,
                MAX(IF(DAY(tgl_presensi) = 1,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_1,
                MAX(IF(DAY(tgl_presensi) = 2,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_2,
                MAX(IF(DAY(tgl_presensi) = 3,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_3,
                MAX(IF(DAY(tgl_presensi) = 4,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_4,
                MAX(IF(DAY(tgl_presensi) = 5,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_5,
                MAX(IF(DAY(tgl_presensi) = 6,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_6,
                MAX(IF(DAY(tgl_presensi) = 7,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_7,
                MAX(IF(DAY(tgl_presensi) = 8,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_8,
                MAX(IF(DAY(tgl_presensi) = 9,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_9,
                MAX(IF(DAY(tgl_presensi) = 10,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_10,
                MAX(IF(DAY(tgl_presensi) = 11,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_11,
                MAX(IF(DAY(tgl_presensi) = 12,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_12,
                MAX(IF(DAY(tgl_presensi) = 13,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_13,
                MAX(IF(DAY(tgl_presensi) = 14,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_14,
                MAX(IF(DAY(tgl_presensi) = 15,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_15,
                MAX(IF(DAY(tgl_presensi) = 16,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_16,
                MAX(IF(DAY(tgl_presensi) = 17,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_17,
                MAX(IF(DAY(tgl_presensi) = 18,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_18,
                MAX(IF(DAY(tgl_presensi) = 19,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_19,
                MAX(IF(DAY(tgl_presensi) = 20,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_20,
                MAX(IF(DAY(tgl_presensi) = 21,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_21,
                MAX(IF(DAY(tgl_presensi) = 22,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_22,
                MAX(IF(DAY(tgl_presensi) = 23,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_23,
                MAX(IF(DAY(tgl_presensi) = 24,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_24,
                MAX(IF(DAY(tgl_presensi) = 25,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_25,
                MAX(IF(DAY(tgl_presensi) = 26,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_26,
                MAX(IF(DAY(tgl_presensi) = 27,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_27,
                MAX(IF(DAY(tgl_presensi) = 28,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_28,
                MAX(IF(DAY(tgl_presensi) = 29,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_29,
                MAX(IF(DAY(tgl_presensi) = 30,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_30,
                MAX(IF(DAY(tgl_presensi) = 31,CONCAT(jam_in,"-",IFNULL(jam_out,"00:00:00")),"")) as tgl_31
            ')
            ->join('murid','presensi.nisn','=','murid.nisn')
            ->whereRaw('MONTH(tgl_presensi)="'.$bulan.'"')
            ->whereRaw('YEAR(tgl_presensi)="'.$tahun.'"')
            ->groupByRaw('presensi.nisn,nama_lengkap')
            ->get();

            return view('presensi.cetakrekap', compact('bulan','tahun','namabulan','rekap'));
    }

    public function izinsakit(Request $request)
    {
        $query = Pengajuanizin::query();
        $query->select('id','tgl_izin','pengajuan_izin.nisn','nama_lengkap','kelas','status','status_approved','keterangan');
        $query->join('murid','pengajuan_izin.nisn','=','murid.nisn');
        if(!empty($request->dari) && !empty($request->sampai)){
            $query->whereBetween('tgl_izin',[$request->dari, $request->sampai]);
        }
        if(!empty($request->nisn)){
            $query->where('pengajuan_izin.nisn',$request->nisn);
        }
        if(!empty($request->nama_lengkap)){
            $query->where('nama_lengkap','like','%'.$request->nama_lengkap.'%');
        }
        if($request->status_approved === '0' || $request->status_approved === '1' || $request->status_approved === '2'){
            $query->where('status_approved',$request->status_approved);
        }
        $query->orderBy('tgl_izin','desc');
        $izinsakit = $query->paginate(1);
        $izinsakit->appends($request->all());

        //$izinsakit = DB::table('pengajuan_izin')
        //    ->join('murid','pengajuan_izin.nisn','=','murid.nisn')
        //    ->orderBy('tgl_izin','desc')
        //    ->get();

        return view('presensi.izinsakit',compact('izinsakit'));
    }

    public function approveizinsakit(Request $request)
    {
        $status_approved = $request->status_approved;
        $id_izinsakit_form = $request->id_izinsakit_form;
        $update = DB::table('pengajuan_izin')
            ->where('id',$id_izinsakit_form)
            ->update([
                'status_approved' => $status_approved
            ]);

        if($update){
            return Redirect::back()->with(['success'=>'Data Berhasil Di Update']);
        } else {
            return Redirect::back()->with(['warning'=>'Data Gagal Di Update']);
        }

    }

    public function batalkanizinsakit($id)
    {
        $update = DB::table('pengajuan_izin')
            ->where('id',$id)
            ->update([
                'status_approved' => 0
            ]);

        if($update){
            return Redirect::back()->with(['success'=>'Data Berhasil Di Update']);
        } else {
            return Redirect::back()->with(['warning'=>'Data Gagal Di Update']);
        }
    }

    public function cekpengajuanizin(Request $request)
    {
        $tgl_izin = $request->tgl_izin;
        $nisn = Auth::guard('murid')
            ->user()
            ->nisn;

        $cek = DB::table('pengajuan_izin')
            ->where('nisn', $nisn)
            ->where('tgl_izin', $tgl_izin)
            ->count();

        return $cek;
    }
}
