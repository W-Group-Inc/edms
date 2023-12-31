@extends('layouts.header')

@section('content')

<div class="wrapper wrapper-content ">
   
    <div class="row ">
        <div class="col-lg-8 stretch-card">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Search Documents</h5>
                </div>
                <div class="ibox-content stretch-card">
                    <div class="search-form">
                        <form action="" method="get">
                            <div class="input-group">
                                <input type="text" placeholder="Document TItle / Control Code" name="search" value="{{$search}}"  class="form-control input-lg" required>
                                <div class="input-group-btn">
                                    <button class="btn btn-lg btn-primary" type="submit">
                                        Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="hr-line-dashed"></div>
                    @foreach($documents as $document)
                    <div class="search-result">
                        <h3><a href="{{url('view-document/'.$document->id)}}" target="_blank">{{$document->control_code}} Rev. {{$document->version}}</a> @if($document->public == null)<span class="label label-danger">Private</span>@else<span class="label label-primary">Public</span>@endif</h3>
                        Title : {{$document->title}}<br>
                        Process Owner : @if(count($document->department->drc) != 0) @foreach($document->department->drc as $drc) <small class="label label-info"> {{$drc->name}} </small> @endforeach @else <small class="label label-danger">No Process Owner</small>  @endif
                        <p>
                            Date Effective : {{date('M d, Y',strtotime($document->effective_date))}} <br>
                            Company : {{$document->department->name}}
                            
                        </p>
                    </div>
                    <div class="hr-line-dashed"></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Public Documents </h5>
                </div>
                <div class="ibox-content">
                    <table class="table table-striped table-bordered table-hover tables">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($request_documents as $req_doc)
                                <tr>
                                    @php
                                        $attchment = ($req_doc->attachments)->where('type','pdf_copy')->first();
                                    @endphp
                                    <td><a href="{{url($attchment->attachment)}}" target="_blank"><i class="fa fa-file"></i> {{$req_doc->title}}</a></td>
                                    <td>
                                        {{$req_doc->department->code}}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="{{ asset('login_css/js/plugins/dataTables/datatables.min.js')}}"></script>
<script>
   
    $(document).ready(function(){
        

        $('.tables').DataTable({
            pageLength: 10,
            responsive: true,
            dom: '<"html5buttons"B>lTfgitp',
            buttons: [
                
            ]

        });

    });

</script>
@endsection

