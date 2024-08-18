<?php

namespace App\Http\Controllers;

use App\Models\Murid;
use App\Models\RiwayatPelanggaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class PoinController extends Controller
{
    public function index(Request $request)
    {
        $query = Murid::query();
        $query->select('murid.*', 'nama_jurusan');
        $query->join('jurusan', 'murid.kode_jurusan', '=', 'jurusan.kode_jurusan');
        $query->orderBy('nama_lengkap');

        if (!empty($request->nama_lengkap)) {
            $query->where('nama_lengkap', 'like', '%' . $request->nama_lengkap . '%');
        }
    
        if (!empty($request->nisn)) {
            if (!empty($request->nama_lengkap)) {
                // Jika keduanya diisi, cari dengan keduanya
                $query->where('nisn', $request->nisn);
            } else {
                // Jika hanya NISN diisi, cari hanya dengan NISN
                $query->where('nisn', $request->nisn);
            }
        }

        $murid = $query->paginate(5);

        return view('poin.poin', compact('murid'));
    }

    
    public function poin(Request $request)
    {
        $query = Murid::query();
        $query->select('murid.*','nama_jurusan');
        $query->join('jurusan','murid.kode_jurusan','=','jurusan.kode_jurusan');
        $query->orderBy('nama_lengkap');
        if(!empty($request->nama_murid)){
            $query->where('nama_lengkap','like','%'.$request->nama_murid . '%');
        }
        if(!empty($request->kode_jurusan)){
            $query->where('murid.kode_jurusan', $request->kode_jurusan);
        }
        $murid = $query->paginate(10);
        $jurusan = DB::table('jurusan')->get();
        
        return view('poin.poin', compact('murid','jurusan'));
    }

    public function riwayatpelanggaran($nisn)
    {
        //$query = RiwayatPelanggaran::query();
        //$murid = $query->paginate(1);

        // Mengambil data murid berdasarkan nisn
        $murid = DB::table('murid')->where('nisn', $nisn)->first();
    
        // Mengambil data dari tabel riwayat_pelanggaran berdasarkan murid_id (jika tersedia)
        $riwayatpelanggaran = DB::table('riwayat_pelanggaran')->where('nisn', $murid->nisn)->paginate(5);

    
        // Mengirim data ke view
        return view('poin.riwayatpelanggaran', compact('riwayatpelanggaran', 'murid'));
    }

    public function store(Request $request)
    {
        $nisn = $request->nisn;
        $pelanggaran = $request->pelanggaran;
        $poin = $request->poin;

        try {
            $data = [
                'nisn'=>$nisn,
                'pelanggaran'=>$pelanggaran,
                'poin'=>$poin
            ];
            $simpan = DB::table('riwayat_pelanggaran')->insert($data);
            if ($simpan){
                return Redirect::back()->with(['success' => 'Data Berhasil Disimpan']);
            }
        } catch (\Exception $e) {
            //dd($e);
            return Redirect::back()->with(['error' => 'Data Gagal Disimpan']);
        }
    }

    public function editpoin(Request $request, $nisn, $id_riwayat)
    {
        // Cari data murid berdasarkan nisn
        $murid = DB::table('murid')->where('nisn', $nisn)->first();

        // Cari data riwayat pelanggaran berdasarkan nisn dan id_riwayat
        $riwayat_pelanggaran = DB::table('riwayat_pelanggaran')
            ->where('id_riwayat', $id_riwayat)
            ->where('nisn', $nisn)
            ->first();

        return view('poin.editpoin', compact('riwayat_pelanggaran', 'murid'));
    }

    public function updatepoin(Request $request, $nisn, $id_riwayat)
    {
        $pelanggaran = $request->pelanggaran;
        $poin = $request->poin;

        try {
            $data = [
                'pelanggaran' => $request->pelanggaran,
                'poin' => $request->poin
            ];
            $update = DB::table('riwayat_pelanggaran')->where('id_riwayat', $id_riwayat)->where('nisn', $nisn)->update($data);
            if ($update){
                return Redirect::back()->with(['success' => 'Data Berhasil Diupdate']);
            }
        } catch (\Exception $e) {
            //dd($e);
            return Redirect::back()->with(['error' => 'Data Gagal Diupdate']);
        }
    }

    public function delete($id_riwayat)
    {
        $delete = DB::table('riwayat_pelanggaran')->where('id_riwayat', $id_riwayat)->delete();
        if($delete){
            return Redirect::back()->with(['success' => 'Data Berhasil Dihapus']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Berhasil Dihapus']);
        }
    }

}
