<?php

namespace App\Http\Controllers;

use App\Models\Murid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoinController extends Controller
{
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

    public function riwayatpelanggaran(Request $request)
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
        
        return view('poin.riwayatpelanggaran', compact('murid','jurusan'));
    }
}
