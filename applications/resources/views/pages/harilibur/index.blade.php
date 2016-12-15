@extends('layout.master')

@section('title')
  <title>Master Hari Libur & Cuti Bersama</title>
@endsection

@section('breadcrumb')
  <h1>Master Hari Libur & Cuti Bersama</h1>
  <ol class="breadcrumb">
    <li><a href=""><i class="fa fa-dashboard"></i>Dashboard</a></li>
    <li class="active">Hari Libur & Cuti Bersama</li>
  </ol>
@endsection

@section('content')
<script>
  window.setTimeout(function() {
    $(".alert-success").fadeTo(500, 0).slideUp(500, function(){
        $(this).remove();
    });
  }, 2000);
</script>

@if(Session::has('berhasil'))
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-success">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
      <h4><i class="icon fa fa-check"></i> Berhasil!</h4>
      <p>{{ Session::get('berhasil') }}</p>
    </div>
  </div>
</div>
@endif


{{-- Modal Tambah Golongan--}}
<div class="modal modal-default fade" id="modaltambahharilibur" role="dialog">
  <div class="modal-dialog" style="width:600px;">
    <form class="form-horizontal" action="{{ route('harilibur.post') }}" method="post">
      {{ csrf_field() }}
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Tambah Hari Libur & Cuti Bersama</h4>
        </div>
        <div class="modal-body">
          <div class="form-group {{ $errors->has('libur') ? 'has-error' : '' }}">
            <label class="col-sm-3 control-label">Hari Libur</label>
            <div class="col-sm-9">
              <div class="input-group date">
                <div class="input-group-addon">
                  <i class="fa fa-calendar"></i>
                </div>
                <input class="form-control pull-right" id="datepicker1" type="text" name="libur"  value="{{ old('libur') }}" placeholder="@if($errors->has('libur')){{ $errors->first('libur')}}@endif Hari Libur">
              </div>
            </div>
          </div>
          <div class="form-group {{ $errors->has('keterangan') ? 'has-error' : '' }}">
            <label class="col-sm-3 control-label">Keterangan</label>
            <div class="col-sm-9">
              <input type="text" name="keterangan" class="form-control" value="{{ old('keterangan') }}" placeholder="@if($errors->has('keterangan')){{ $errors->first('keterangan')}} @endif Keterangan" required="">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Tidak</button>
          <button type="submit" class="btn btn-danger">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Modal Edit SKPD --}}
<div class="modal modal-default fade" id="modaleditharilibur" role="dialog">
  <div class="modal-dialog" style="width:800px;">
    <form class="form-horizontal" action="{{ route('harilibur.edit') }}" method="post">
      {{ csrf_field() }}
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Edit Data Hari Libur & Cuti Bersama</h4>
        </div>
        <div class="modal-body">
          <div class="form-group {{ $errors->has('libur_edit') ? 'has-error' : '' }}">
            <label class="col-sm-3 control-label">Hari Libur</label>
            <div class="col-sm-9">
              <div class="input-group date">
                <div class="input-group-addon">
                  <i class="fa fa-calendar"></i>
                </div>
                <input class="form-control pull-right datepicker1" id="libur_edit" type="text" name="libur_edit" value="{{ old('libur_edit') }}" placeholder="@if($errors->has('libur_edit')){{ $errors->first('libur_edit')}}@endif Hari Libur">
                <input type="hidden" name="id" id="id">
              </div>
            </div>
          </div>
          <div class="form-group {{ $errors->has('keterangan_edit') ? 'has-error' : '' }}">
            <label class="col-sm-3 control-label">Keterangan</label>
            <div class="col-sm-9">
              <input type="text" name="keterangan_edit" class="form-control" id="keterangan_edit" value="{{ old('keterangan_edit') }}" placeholder="@if($errors->has('keterangan_edit')){{ $errors->first('keterangan_edit')}} @endif Keterangan" required="">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Tidak</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="box box-primary box-solid">
      <div class="box-header">
        <h3 class="box-title">Hari Libur & Cuti Bersama</h3>
        <a href="#" class="btn bg-blue pull-right" data-toggle="modal" data-target="#modaltambahharilibur">Tambah Hari Libur & Cuti Bersama</a>
      </div>
      <div class="box-body">
        <table id="table_jabatan" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal</th>
              <th>Keterangan</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; ?>
            @if ($harilibur->isEmpty())
            <tr>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
            </tr>
            @else
            @foreach ($harilibur as $key)
            <tr>
              <td>{{ $no }}</td>
              <td>{{ $key->libur }}</td>
              <td>{{ $key->keterangan }}</td>
              <td><a href="" data-value="{{ $key->id }}" class="editharilibur" data-toggle="modal" data-target="#modaleditharilibur"><i class="fa fa-edit"></i> Ubah</a></td>
            </tr>
            <?php $no++; ?>
            @endforeach
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script>
$(function () {
  $("#table_harilibur").DataTable();
});
$('#datepicker1').datepicker({
  autoclose: true,
  format: 'yyyy-mm-dd',
  todayHighlight: true,
});
$('.datepicker1').datepicker({
  autoclose: true,
  format: 'yyyy-mm-dd',
  todayHighlight: true,
});
</script>

<script type="text/javascript">
@if ($errors->has('libur') || $errors->has('keterangan'))
  $('#modaltambahharilibur').modal('show');
@endif
@if ($errors->has('libur_edit') || $errors->has('keterangan_edit'))
  $('#modaleditharilibur').modal('show');
@endif
</script>

<script type="text/javascript">
  $(function(){
    $('.editharilibur').click(function(){
      var a = $(this).data('value');
      $.ajax({
        url: "{{ url('/') }}/harilibur/"+a,
        dataType: 'json',
        success: function(data){
          var id = data.id;
          var libur_edit = data.libur;
          var keterangan_edit = data.keterangan;

          // set
          $('#id').attr('value', id);
          $('#libur_edit').attr('value', libur_edit);
          $('#keterangan_edit').attr('value', keterangan_edit);
        }
      });
    });
  });
</script>
@endsection
