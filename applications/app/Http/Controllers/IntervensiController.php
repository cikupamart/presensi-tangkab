<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ManajemenIntervensi;
use App\Models\Intervensi;
use App\Models\Pegawai;
use App\Models\Users;
use App\Models\Skpd;

use Validator;
use Auth;
use DB;
use Image;

class IntervensiController extends Controller
{
    public function index()
    {
      $intervensi = intervensi::where('pegawai_id', Auth::user()->pegawai_id)->get();
      $getmasterintervensi = ManajemenIntervensi::where('flag_old', 0)->get();
      $getunreadintervensi = intervensi::join('preson_pegawais', 'preson_pegawais.id', '=', 'preson_intervensis.pegawai_id')
                                         ->where('preson_intervensis.flag_view', 0)
                                         ->where('preson_pegawais.skpd_id', Auth::user()->skpd_id)
                                         ->where('preson_intervensis.pegawai_id', '!=', Auth::user()->pegawai_id)
                                         ->count();

      return view('pages.intervensi.index', compact('intervensi', 'getmasterintervensi','getunreadintervensi'));
    }

    public function store(Request $request)
    {
      // --- validasi form input
      $message = [
        'jenis_intervensi.required' => 'Wajib di isi',
        'tanggal_mulai.required' => 'Wajib di isi',
        'tanggal_akhir.required' => 'Wajib di isi',
        'jumlah_hari.required' => 'Wajib di isi',
        'keterangan.required' => 'Wajib di isi',
        // 'berkas'  => 'Hanya .jpg, .png, .pdf'
      ];

      $validator = Validator::make($request->all(), [
        'jenis_intervensi' => 'required',
        'tanggal_mulai' => 'required',
        'tanggal_akhir' => 'required',
        'jumlah_hari' => 'required',
        'keterangan' => 'required',
        // 'berkas'  => 'mimes:jpeg,png,pdf,jpg'
      ], $message);

      if($validator->fails())
      {
        return redirect()->route('intervensi.index')->withErrors($validator)->withInput();
      }
      // --- end of validasi form input



      // --- validasi izin tidak masuk kerja 2x sebulan
      if ($request->jenis_intervensi==12) {
        if ($request->jumlah_hari>2) {
          return redirect()->route('intervensi.index')->with('gagal', 'Jumlah izin tidak masuk kerja melebihi batas maksimal.');
        }

        $datenow = date('m-Y');
        $pegawaiid = Auth::user()->pegawai_id;
        $countsum = DB::select("select sum(jumlah_hari) as 'total' from preson_intervensis
                                        where DATE_FORMAT(tanggal_mulai,'%m-%Y') = '$datenow'
                                        and pegawai_id = $pegawaiid and id_intervensi = 12");

        $result = $countsum[0]->total;
        if ($result>=2) {
          return redirect()->route('intervensi.index')->with('gagal', 'Jumlah izin tidak masuk kerja melebihi batas maksimal.');
        }
      }
      // --- end of validasi izin tidak masuk kerja 2x sebulan


      // --- validasi izin datang telat/pulang cepat 2x sebulan
      if ($request->jenis_intervensi==5 || $request->jenis_intervensi==6) {
        if ($request->jumlah_hari>2) {
          return redirect()->route('intervensi.index')->with('gagal', 'Jumlah izin datang telat atau pulang cepat melebihi batas maksimal.');
        }

        $datenow = date('m-Y');
        $pegawaiid = Auth::user()->pegawai_id;
        $countsum = DB::select("select sum(jumlah_hari) as 'total' from preson_intervensis
                                where DATE_FORMAT(tanggal_mulai,'%m-%Y') = '$datenow'
                                and pegawai_id = $pegawaiid and id_intervensi = 5 or id_intervensi = 6");

        $result = $countsum[0]->total;
        if ($result>=2) {
          return redirect()->route('intervensi.index')->with('gagal', 'Jumlah izin datang telat atau pulang cepat melebihi batas maksimal.');
        }
      }
      // --- end of validasi izin datang telat/pulang cepat 2x sebulan


      // --- validasi ketersediaan tanggal intervensi
      $gettanggalintervensi = Intervensi::select('tanggal_mulai', 'tanggal_akhir')
                                          ->where('pegawai_id', Auth::user()->pegawai_id)
                                          ->get();

      $tanggalmulai = $request->tanggal_mulai;
      $tanggalakhir = $request->tanggal_akhir;

      $dateRange=array();
      $iDateFrom=mktime(1,0,0,substr($tanggalmulai,5,2),     substr($tanggalmulai,8,2),substr($tanggalmulai,0,4));
      $iDateTo=mktime(1,0,0,substr($tanggalakhir,5,2),     substr($tanggalakhir,8,2),substr($tanggalakhir,0,4));

      if ($iDateTo>=$iDateFrom)
      {
          array_push($dateRange,date('Y-m-d',$iDateFrom)); // first entry
          while ($iDateFrom<$iDateTo)
          {
              $iDateFrom+=86400; // add 24 hours
              array_push($dateRange,date('Y-m-d',$iDateFrom));
          }
      }

      $flagtanggal = 0;
      foreach ($dateRange as $key) {
        foreach ($gettanggalintervensi as $keys) {
          $start_ts = strtotime($keys->tanggal_mulai);
          $end_ts = strtotime($keys->tanggal_akhir);
          $user_ts = strtotime($key);

          if (($user_ts >= $start_ts) && ($user_ts <= $end_ts)) {
            $flagtanggal=1;
            break;
          }
        }
        if ($flagtanggal==1) break;
      }

      if ($flagtanggal==1) {
        return redirect()->route('intervensi.index')->with('gagal', 'Tanggal intervensi yang anda pilih telah tercatat pada database.');
      }
      // --- end of validasi ketersediaan tanggal intervensi


      // --- proses penyimpanan data ke database
      $file = $request->file('berkas');

      $doc_name = '';
      if($file != null)
      {
        $i = 1;
        foreach ($file as $key) {
          $photo_name = Auth::user()->nip_sapk.'-'.$request->tanggal_mulai.'-'.$request->jenis_intervensi.'-'.$i.'.'. $key->getClientOriginalExtension();
          $key->move('documents/', $photo_name);
          $doc_name .= $photo_name.'//';
          $i++;
        }
      }
      else
      {
        $doc_name = "-";
      }

      $getnamaintervensi = ManajemenIntervensi::find($request->jenis_intervensi);
      $set = new intervensi;
      $set->pegawai_id = Auth::user()->pegawai_id;
      $set->id_intervensi = $request->id_intervensi;
      $set->jenis_intervensi = $getnamaintervensi->nama_intervensi;
      $set->tanggal_mulai = $request->tanggal_mulai;
      $set->tanggal_akhir = $request->tanggal_akhir;
      $set->jumlah_hari = $request->jumlah_hari;
      $set->deskripsi = $request->keterangan;

      if (isset($request->atasan)) {
        $set->nama_atasan = $request->atasan;
      }

      $set->berkas = $doc_name;
      $set->flag_status = 0;
      $set->actor = Auth::user()->pegawai_id;
      $set->save();

      return redirect()->route('intervensi.index')->with('berhasil', 'Berhasil Menambahkan Intervensi');
      // --- end of proses penyimpanan data ke database

    }

    public function bind($id)
    {

      $find = intervensi::find($id);

      return $find;
    }

    public function edit(Request $request)
    {
      $message = [
        'jenis_intervensi_edit.required' => 'Wajib di isi',
        'tanggal_mulai_edit.required' => 'Wajib di isi',
        'tanggal_akhir_edit.required' => 'Wajib di isi',
        'jumlah_hari_edit.required' => 'Wajib di isi',
        'keterangan_edit.required' => 'Wajib di isi',
        'berkas'  => 'Hanya .jpg, .png, .pdf'
      ];

      $validator = Validator::make($request->all(), [
        'jenis_intervensi_edit' => 'required',
        'tanggal_mulai_edit' => 'required',
        'tanggal_akhir_edit' => 'required',
        'jumlah_hari_edit' => 'required',
        'keterangan_edit' => 'required',
        'berkas'  => 'mimes:jpeg,png,pdf,jpg'
      ], $message);

      if($validator->fails())
      {
        return redirect()->route('intervensi.index')->withErrors($validator)->withInput();
      }

      // --- validasi izin tidak masuk kerja 2x sebulan
      if ($request->jenis_intervensi_edit==12) {
        if ($request->jumlah_hari_edit>2) {
          return redirect()->route('intervensi.index')->with('gagal', 'Jumlah izin tidak masuk kerja melebihi batas maksimal.');
        }

        $datenow = date('m-Y');
        $pegawaiid = Auth::user()->pegawai_id;
        $countsum = DB::select("select sum(jumlah_hari) as 'total' from preson_intervensis
                                        where DATE_FORMAT(tanggal_mulai,'%m-%Y') = '$datenow'
                                        and pegawai_id = $pegawaiid and id_intervensi = 12");

        $result = $countsum[0]->total;
        if ($result>=2) {
          return redirect()->route('intervensi.index')->with('gagal', 'Jumlah izin tidak masuk kerja melebihi batas maksimal.');
        }
      }
      // --- end of validasi izin tidak masuk kerja 2x sebulan


      // --- validasi izin datang telat/pulang cepat 2x sebulan
      if ($request->jenis_intervensi_edit==5 || $request->jenis_intervensi_edit==6) {
        if ($request->jumlah_hari_edit>2) {
          return redirect()->route('intervensi.index')->with('gagal', 'Jumlah izin datang telat atau pulang cepat melebihi batas maksimal.');
        }

        $datenow = date('m-Y');
        $pegawaiid = Auth::user()->pegawai_id;
        $countsum = DB::select("select sum(jumlah_hari) as 'total' from preson_intervensis
                                where DATE_FORMAT(tanggal_mulai,'%m-%Y') = '$datenow'
                                and pegawai_id = $pegawaiid and id_intervensi = 5 or id_intervensi = 6");

        $result = $countsum[0]->total;
        if ($result>=2) {
          return redirect()->route('intervensi.index')->with('gagal', 'Jumlah izin datang telat atau pulang cepat melebihi batas maksimal.');
        }
      }
      // --- end of validasi izin datang telat/pulang cepat 2x sebulan


      // --- validasi ketersediaan tanggal intervensi
      $cek = intervensi::find($request->id_edit);
      if ($request->tanggal_mulai_edit!=$cek->tanggal_mulai || $request->tanggal_akhir_edit!=$cek->tanggal_akhir) {
        $gettanggalintervensi = Intervensi::select('tanggal_mulai', 'tanggal_akhir')
                                            ->where('pegawai_id', Auth::user()->pegawai_id)
                                            ->where('id', '!=', $request->id_edit)
                                            ->get();

        $tanggalmulai = $request->tanggal_mulai_edit;
        $tanggalakhir = $request->tanggal_akhir_edit;

        $dateRange=array();
        $iDateFrom=mktime(1,0,0,substr($tanggalmulai,5,2),     substr($tanggalmulai,8,2),substr($tanggalmulai,0,4));
        $iDateTo=mktime(1,0,0,substr($tanggalakhir,5,2),     substr($tanggalakhir,8,2),substr($tanggalakhir,0,4));

        if ($iDateTo>=$iDateFrom)
        {
            array_push($dateRange,date('Y-m-d',$iDateFrom)); // first entry
            while ($iDateFrom<$iDateTo)
            {
                $iDateFrom+=86400; // add 24 hours
                array_push($dateRange,date('Y-m-d',$iDateFrom));
            }
        }

        $flagtanggal = 0;
        foreach ($dateRange as $key) {
          foreach ($gettanggalintervensi as $keys) {
            $start_ts = strtotime($keys->tanggal_mulai);
            $end_ts = strtotime($keys->tanggal_akhir);
            $user_ts = strtotime($key);

            if (($user_ts >= $start_ts) && ($user_ts <= $end_ts)) {
              $flagtanggal=1;
              break;
            }
          }
          if ($flagtanggal==1) break;
        }

        if ($flagtanggal==1) {
          return redirect()->route('intervensi.index')->with('gagal', 'Tanggal intervensi yang anda pilih telah tercatat pada database.');
        }
      }
      // --- end of validasi ketersediaan tanggal intervensi

      $file = $request->file('berkas_edit');

      if($file != null)
      {
        $doc_name="";
        $i = 1;
        foreach ($file as $key) {
          $photo_name = Auth::user()->nip_sapk.'-'.$request->tanggal_mulai_edit.'-'.$request->jenis_intervensi_edit.'-'.$i.'.'. $key->getClientOriginalExtension();
          $key->move('documents/', $photo_name);
          $doc_name .= $photo_name.'//';
          $i++;
        }

        $getnamaintervensi = ManajemenIntervensi::find($request->jenis_intervensi_edit);

        $set = intervensi::find($request->id_edit);
        $set->pegawai_id = Auth::user()->pegawai_id;
        $set->jenis_intervensi = $getnamaintervensi->nama_intervensi;
        $set->id_intervensi = $request->jenis_intervensi_edit;
        $set->tanggal_mulai = $request->tanggal_mulai_edit;
        $set->tanggal_akhir = $request->tanggal_akhir_edit;
        $set->jumlah_hari = $request->jumlah_hari_edit;
        $set->deskripsi = $request->keterangan_edit;
        $set->berkas = $doc_name;
        $set->flag_status = 0;
        $set->actor = Auth::user()->pegawai_id;
        $set->save();
      }else{
        $getnamaintervensi = ManajemenIntervensi::find($request->jenis_intervensi_edit);

        $set = intervensi::find($request->id_edit);
        $set->pegawai_id = Auth::user()->pegawai_id;
        $set->jenis_intervensi = $getnamaintervensi->nama_intervensi;
        $set->id_intervensi = $request->jenis_intervensi_edit;
        $set->tanggal_mulai = $request->tanggal_mulai_edit;
        $set->tanggal_akhir = $request->tanggal_akhir_edit;
        $set->jumlah_hari = $request->jumlah_hari_edit;
        $set->deskripsi = $request->keterangan_edit;
        $set->flag_status = 0;
        $set->actor = Auth::user()->pegawai_id;
        $set->save();
      }

      return redirect()->route('intervensi.index')->with('berhasil', 'Berhasil Mengubah Intervensi');
    }

    public function kelola()
    {
      if(session('status') === 'admin')
      {
        $getmasterintervensi = ManajemenIntervensi::where('flag_old', 0)->get();

        $intervensi = intervensi::join('preson_pegawais', 'preson_pegawais.id', '=', 'preson_intervensis.pegawai_id')
                              ->join('preson_users', 'preson_users.skpd_id', '=', 'preson_pegawais.skpd_id')
                              ->where('preson_users.pegawai_id', Auth::user()->pegawai_id)
                              ->select('preson_intervensis.*', 'preson_pegawais.nama as nama_pegawai', 'preson_pegawais.nip_sapk')
                              ->orderBy('tanggal_mulai', 'desc')
                              ->get();

        $getunreadintervensi = intervensi::join('preson_pegawais', 'preson_pegawais.id', '=', 'preson_intervensis.pegawai_id')
                                           ->where('preson_intervensis.flag_view', 0)
                                           ->where('preson_pegawais.skpd_id', Auth::user()->skpd_id)
                                           ->where('preson_intervensis.pegawai_id', '!=', Auth::user()->pegawai_id)
                                           ->count();

        $pegawai = pegawai::select('id', 'nama')->where('skpd_id', Auth::user()->skpd_id)->get();
      }
      elseif(session('status') === 'administrator' || session('status') == 'superuser')
      {
        $getSKPD = skpd::get();

        $pegawai = pegawai::select('id', 'nama')->get();

        $getmasterintervensi = ManajemenIntervensi::where('flag_old', 0)->get();
      }

      return view('pages.intervensi.kelola', compact('getSKPD', 'pegawai', 'intervensi', 'getmasterintervensi', 'getunreadintervensi'));
    }

    public function kelolaAksi($id)
    {
      $intervensi = intervensi::join('preson_pegawais', 'preson_pegawais.id', '=', 'preson_intervensis.pegawai_id')
                            ->select('preson_pegawais.nama as nama_pegawai', 'preson_intervensis.*')
                            ->where('preson_intervensis.id', $id)->first();

      if($intervensi == null){
        abort(404);
      }

      $set = intervensi::find($id);
      $set->flag_view = 1;
      $set->save();

      $getunreadintervensi = intervensi::join('preson_pegawais', 'preson_pegawais.id', '=', 'preson_intervensis.pegawai_id')
                                         ->where('preson_intervensis.flag_view', 0)
                                         ->where('preson_pegawais.skpd_id', Auth::user()->skpd_id)
                                         ->where('preson_intervensis.pegawai_id', '!=', Auth::user()->pegawai_id)
                                         ->count();

      return view('pages.intervensi.aksi', compact('intervensi', 'getunreadintervensi'));
    }

    public function kelolaApprove($id)
    {
      $approve = intervensi::find($id);
      $approve->flag_status = 1;
      $approve->actor = Auth::user()->pegawai_id;
      $approve->update();

      return redirect()->route('intervensi.kelola')->with('berhasil', 'Berhasil Setujui Intervensi');
    }

    public function kelolaDecline($id)
    {
      $approve = intervensi::find($id);
      $approve->flag_status = 2;
      $approve->actor = Auth::user()->pegawai_id;
      $approve->update();

      return redirect()->route('intervensi.kelola')->with('berhasil', 'Berhasil Tolak Intervensi');
    }

    public function kelolaPost(Request $request)
    {
      $message = [
        'pegawai_id.required' => 'Wajib di isi',
        'jenis_intervensi.required' => 'Wajib di isi',
        'tanggal_mulai.required' => 'Wajib di isi',
        'tanggal_akhir.required' => 'Wajib di isi',
        'jumlah_hari.required' => 'Wajib di isi',
        'keterangan.required' => 'Wajib di isi',
        'berkas'  => 'Hanya .jpg, .png, .pdf'
      ];

      $validator = Validator::make($request->all(), [
        'pegawai_id' => 'required',
        'jenis_intervensi' => 'required',
        'tanggal_mulai' => 'required',
        'tanggal_akhir' => 'required',
        'jumlah_hari' => 'required',
        'keterangan' => 'required',
        'berkas'  => 'mimes:jpeg,png,pdf,jpg'
      ], $message);

      if($validator->fails())
      {
        return redirect()->route('intervensi.kelola')->withErrors($validator)->withInput();
      }

      $file = $request->file('berkas');

      if($file != null)
      {
        $photo_name = Auth::user()->nip_sapk.'-'.$request->tanggal_mulai.'-'.$request->jenis_intervensi.'.' . $file->getClientOriginalExtension();
        $file->move('documents/', $photo_name);
      }else{
        $photo_name = '';
      }

      $getnamaintervensi = ManajemenIntervensi::find($request->jenis_intervensi);

      $set = new intervensi;
      $set->pegawai_id = $request->pegawai_id;
      $set->jenis_intervensi = $getnamaintervensi->nama_intervensi;
      $set->id_intervensi = $request->jenis_intervensi;
      $set->tanggal_mulai = $request->tanggal_mulai;
      $set->tanggal_akhir = $request->tanggal_akhir;
      $set->jumlah_hari = $request->jumlah_hari;
      $set->deskripsi = $request->keterangan;
      $set->berkas = $photo_name;
      $set->flag_status = 0;
      $set->actor = Auth::user()->pegawai_id;
      $set->save();

      return redirect()->route('intervensi.kelola')->with('berhasil', 'Berhasil Menambahkan Intervensi');
    }

    public function skpd($id)
    {
      $id = skpd::find($id);

      if($id == null){
        abort(404);
      }

      $intervensi = intervensi::join('preson_pegawais', 'preson_pegawais.id', '=', 'preson_intervensis.pegawai_id')
                            ->join('preson_skpd', 'preson_skpd.id', '=', 'preson_pegawais.skpd_id')
                            ->select('preson_intervensis.*', 'preson_pegawais.nama as nama_pegawai', 'preson_pegawais.nip_sapk')
                            ->where('preson_skpd.id', '=', $id->id)
                            ->orderBy('tanggal_mulai', 'desc')
                            ->get();

      $pegawai = pegawai::select('id', 'nama')->where('skpd_id', Auth::user()->skpd_id)->get();

      $getmasterintervensi = ManajemenIntervensi::where('flag_old', 0)->get();

      return view('pages.intervensi.detailSKPD', compact('intervensi', 'pegawai', 'getmasterintervensi'));
    }

    public function batal($id)
    {
      $approve = intervensi::find($id);
      $approve->flag_status = 3;
      $approve->actor = Auth::user()->pegawai_id;
      $approve->update();

      return redirect()->route('intervensi.index')->with('berhasil', 'Berhasil Batalkan Intervensi');
    }

    public function resetStatus($id)
    {
      $set = Intervensi::find($id);
      $set->flag_status = 0;
      $set->save();

      return redirect()->route('intervensi.kelola')->with('berhasil', 'Berhasil reset status intervensi');
    }
}
