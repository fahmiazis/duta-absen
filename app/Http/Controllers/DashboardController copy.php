<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require '../vendor/autoload.php';// Autoload semua library composer
use Carbon\Carbon;
\Carbon\Carbon::setLocale('id');

class DashboardController extends Controller
{
    public function index()
    {
        $hariini = date("Y-m-d");
        $bulanini = date("m") * 1;
        $tahunini = date("Y");
        $jamMasuk = DB::table('jamsekolah')->where('id', 1)->value('jam_masuk');
        $jamMasuk = $jamMasuk ?? '07:00';
        $jamPulangAsli = DB::table('jamsekolah')->where('id', 1)->value('jam_pulang') ?? '16:00';
        $jamPulangBatas = Carbon::parse($jamPulangAsli)->addMinutes(5)->format('H:i:s');
        $nisn = Auth::guard('murid')
            ->user()
            ->nisn;

        $murid = DB::table('murid')
            ->join('jurusan', 'murid.kode_jurusan', '=', 'jurusan.kode_jurusan')
            ->select('murid.*', 'jurusan.nama_jurusan')
            ->where('murid.nisn', $nisn)
            ->first();
        
        $presensihariini = DB::table('presensi')
            ->where('nisn', $nisn)
            ->where('tgl_presensi',$hariini)
            ->first();

        $historibulanini = DB::table('presensi')
            ->where('nisn',$nisn)
            ->whereRaw('MONTH(tgl_presensi)="'.$bulanini.'"')
            ->whereRaw('YEAR(tgl_presensi)="'. $tahunini . '"')
            ->orderBy('tgl_presensi')
            ->get();

        $rekappresensi = DB::table('presensi')
            ->selectRaw('COUNT(nisn) as jmlhadir, SUM(IF(jam_in > ?, 1, 0)) as jmlterlambat', [$jamMasuk])
            ->where('nisn', $nisn)
            ->whereMonth('tgl_presensi', $bulanini)
            ->whereYear('tgl_presensi', $tahunini)
            ->first();

        $leaderboard = DB::table('presensi')
            ->join('murid','presensi.nisn', '=', 'murid.nisn')
            ->where('tgl_presensi',$hariini)
            ->orderBy('jam_in')
            ->get();
        
        $namabulan = [
            "",
            "Januari",
            "Februari",
            "Maret",
            "April",
            "Mei",
            "Juni",
            "Juli",
            "Agustus",
            "September",
            "Oktober",
            "November",
            "Desember"];
        
        $rekapizin = DB::table('pengajuan_izin')
            ->selectRaw('SUM(IF(status="i",1,0)) as jmlizin, SUM(IF(status="s",1,0)) as jmlsakit')
            ->where('nisn',$nisn)
            ->whereRaw('MONTH(tgl_izin)="'.$bulanini.'"')
            ->whereRaw('YEAR(tgl_izin)="'. $tahunini . '"')
            ->where('status_approved', 1)
            ->first();
        return view('dashboard.dashboard', compact(
            'presensihariini',
            'historibulanini',
            'namabulan',
            'bulanini',
            'tahunini',
            'rekappresensi',
            'leaderboard',
            'rekapizin', 
            'murid', 
            'jamMasuk', 
            'jamPulangBatas', 
            'jamPulangAsli'));
    }
}