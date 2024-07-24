<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class PresensiController extends Controller
{
    public function create()
    {
        $harini = date("Y-m-d");
        $nisn = Auth::guard('murid')->user()->nisn;
        $cek = DB::table('presensi')->where('tgl_presensi', $harini)->where('nisn', $nisn)->count();
        return view('presensi.create', compact('cek'));
    }

    public function store(Request $request)
    {
        $nisn = Auth::guard('murid')->user()->nisn;
        $tgl_presensi = date("Y-m-d");
        $jam = date("H:i:s");
        //$latitudekantor = -5.3968896;
        //$longitudekantor = 105.2540928;
        $latitudekantor = -6.1944491;
        $longitudekantor = 106.8229198;
        $lokasi = $request->lokasi;
        $lokasiuser = explode(',', $lokasi);
        $latitudeuser = $lokasiuser[0];
        $longitudeuser = $lokasiuser[1];
        

        $jarak = $this->distance($latitudekantor, $longitudekantor, $latitudeuser, $longitudeuser);
        $radius = round($jarak["meters"]);
        //dd($radius);
        
        $cek = DB::table('presensi')->where('tgl_presensi', $tgl_presensi)->where('nisn', $nisn)->count();
        
        if($cek > 0) {
            $ket = "out";
        } else {
            $ket = "in";
        }
        $image = $request->image;
        $folderpath = "public/uploads/absensi/";
        $formatName = $nisn."-".$tgl_presensi . "-" . $ket;
        $image_parts = explode(";base64",$image);
        $image_base64 = base64_decode($image_parts[1]);
        $fileName = $formatName . ".png";
        $file = $folderpath . $fileName;
        if($radius > 10) {
            echo "error|Maaf Anda berada di luar radius, jarak anda" . $radius ." meter dari sekolah.|";
        } else {
            if($cek > 0){
                $data_pulang = [
                    'jam_out' => $jam,
                    'foto_out' => $fileName,
                    'lokasi_out' => $lokasi
                ];
                $update = DB::table('presensi')->where('tgl_presensi', $tgl_presensi)->where('nisn', $nisn)->update($data_pulang);
                if($update){
                    echo "success|Terimakasi, Hati Hati Di Jalan Pulang|out";
                    Storage::put($file,$image_base64);
                } else {
                    echo "error|Maaf Gagal Absen, Hubungi Petugas IT Sekolah|out";
                }
            } else {
                $data = [
                    'nisn' => $nisn,
                    'tgl_presensi' => $tgl_presensi,
                    'jam_in' => $jam,
                    'foto_in' => $fileName,
                    'lokasi_in' => $lokasi
                ];
                $simpan = DB::table('presensi')->insert($data);
                if($simpan){
                    echo "success|Terimakasi, Selamat Belajar Di Kelas|in";
                    Storage::put($file,$image_base64);
                } else {
                    echo "error|Maaf Gagal Absen, Hubungi Petugas IT Sekolah|in";
                }
            }   
        }
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

        if($request->hasFile('foto')){
            $foto = $nisn.".".$request
                ->file('foto')
                ->getClientOriginalExtension();
        } else {
            $foto = $murid->foto;
        }
        
        if (empty($request->password)) {
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_hp' => $no_hp,
                'foto' => $foto
            ];
        } else {
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_hp' => $no_hp,
                'password' => $password,
                'foto' => $foto
            ];
        }
        
        $update = DB::table('murid')
            ->where('nisn', $nisn)
            ->update($data);
        
        if($update){
            if($request->hasFile('foto')){
                $folderpath = "public/uploads/murid/";
                $request
                    ->file('foto')
                    ->storeAs($folderpath, $foto);
            }
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
}
