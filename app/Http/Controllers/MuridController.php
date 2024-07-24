<?php

namespace App\Http\Controllers;

use App\Models\Murid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class MuridController extends Controller
{
    public function index(Request $request)
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
        
        return view('murid.index', compact('murid','jurusan'));
    }

    public function store(Request $request)
    {
        $nisn = $request->nisn;
        $nama_lengkap = $request->nama_lengkap;
        $kelas = $request->kelas;
        $no_hp = $request->no_hp;
        $kode_jurusan = $request->kode_jurusan;

        if($request->hasFile('foto')){
            $foto = $nisn.".".$request
                ->file('foto')
                ->getClientOriginalExtension();
        } else {
            $foto = null;
        }

        try {
            $password = Hash::make('12345');
            $data = [
                'nisn'=>$nisn,
                'nama_lengkap'=>$nama_lengkap,
                'kelas'=>$kelas,
                'no_hp'=>$no_hp,
                'foto'=>$foto,
                'kode_jurusan'=>$kode_jurusan,
                'password'=>$password
            ];
            $simpan = DB::table('murid')->insert($data);
            if ($simpan){
                if($request->hasFile('foto')){
                    $folderpath = "public/uploads/murid/";
                    $request
                        ->file('foto')
                        ->storeAs($folderpath, $foto);
                }
                return Redirect::back()->with(['success' => 'Data Berhasil Disimpan']);
            }
        } catch (\Exception $e) {
            dd($e);
            //return Redirect::back()->with(['error' => 'Data Gagal Disimpan']);
        }
    }

    public function edit(Request $request)
    {
        $nisn = $request->nisn;
        $jurusan = DB::table('jurusan')->get();
        $murid = DB::table('murid')->where('nisn', $nisn)->first();
        return view('murid.edit',compact('jurusan','murid'));
    }

    public function update($nisn, Request $request)
    {
        $nisn = $request->nisn;
        $nama_lengkap = $request->nama_lengkap;
        $kelas = $request->kelas;
        $no_hp = $request->no_hp;
        $kode_jurusan = $request->kode_jurusan;
        $password = Hash::make('12345');
        $old_foto = $request->old_foto;

        if($request->hasFile('foto')){
            $foto = $nisn.".".$request
                ->file('foto')
                ->getClientOriginalExtension();
        } else {
            $foto = $old_foto;
        }

        try {
            $data = [
                'nama_lengkap'=>$nama_lengkap,
                'kelas'=>$kelas,
                'no_hp'=>$no_hp,
                'foto'=>$foto,
                'kode_jurusan'=>$kode_jurusan,
                'password'=>$password
            ];
            $update = DB::table('murid')->where('nisn', $nisn)->update($data);
            if ($update){
                if($request->hasFile('foto')){
                    $folderpath = "public/uploads/murid/";
                    $folderpathold = "public/uploads/murid/" . $old_foto;
                    Storage::delete($folderpathold);
                    $request
                        ->file('foto')
                        ->storeAs($folderpath, $foto);
                }
                return Redirect::back()->with(['success' => 'Data Berhasil Diupdate']);
            }
        } catch (\Exception $e) {
            //dd($e);
            return Redirect::back()->with(['error' => 'Data Gagal Diupdate']);
        }
    }

    public function delete($nisn)
    {
        $delete = DB::table('murid')->where('nisn', $nisn)->delete();
        if($delete){
            return Redirect::back()->with(['success' => 'Data Berhasil Dihapus']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Berhasil Dihapus']);
        }
    }
}
