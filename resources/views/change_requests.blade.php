@extends('layouts.header')
@section('css')
<link href="{{ asset('login_css/css/plugins/chosen/bootstrap-chosen.css') }}" rel="stylesheet">
@endsection
@section('content')

<div class="wrapper wrapper-content">
    <div class="row">
    <div class="col-lg-3">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Pending</h5>
            </div>
            <div class="ibox-content">
                <h1 class="no-margins">{{count($requests->where('status','Pending'))}}</h1>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Cancelled</h5>
            </div>
            <div class="ibox-content">
                <h1 class="no-margins">{{count($requests->where('status','Cancelled'))}}</h1>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Declined</h5>
            </div>
            <div class="ibox-content">
                <h1 class="no-margins">{{count($requests->where('status','Declined'))}}</h1>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Approved</h5>
            </div>
            <div class="ibox-content">
                <h1 class="no-margins">{{count($requests->where('status','Approved'))}}</h1>
            </div>
        </div>
    </div>
</div>
    <div class='row'>
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Change Requests
                        @if(auth()->user()->role == "Documents and Records Controller")
                        <button class="btn btn-success "  data-target="#newRequest" data-toggle="modal" type="button"><i class="fa fa-plus"></i>&nbsp;New </button>
                        @endif
                        @if(auth()->user()->role == "Document Control Officer")
                        <button class="btn btn-success "  data-target="#newRequest" data-toggle="modal" type="button"><i class="fa fa-plus"></i>&nbsp;New </button>
                        @endif
                    </h5>
                  
                </div>
                <div class="ibox-content">

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover tables" >
                            <thead>
                                <tr>
                                    
                                    <th>Actions</th>
                                    <th>Reference No.</th>
                                    <th>Request Type</th>
                                    <th>Date Requested</th>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Revision</th>
                                    <th>Type</th>
                                    <th>Requested By</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                        <tbody>
                            @foreach($requests as $request)
                                    <tr>
                                        
                                        <td><a href="#"  data-target="#view_request{{$request->id}}" data-toggle="modal" class='btn btn-sm btn-info'><i class="fa fa-eye"></i></a>
                                            @if((auth()->user()->role == "Document Control Officer") || (auth()->user()->role == "Administrator"))
                                            @if($request->status == "Pending")
                                            @if($request->request_type == "Revision")
                                            <a href="#"  data-target="#edit_request{{$request->id}}" data-toggle="modal" class='btn btn-sm btn-warning'><i class="fa fa-edit"></i></a>
                                            @endif
                                            @endif
                                            @endif
                                        </td>
                                        <td>DICR-{{str_pad($request->id, 5, '0', STR_PAD_LEFT)}}</td>
                                        <td>{{$request->request_type}}</td>
                                        <td>{{date('M d Y',strtotime($request->created_at))}}</td>
                                     
                                            @if($request->document_id != null)
                                            <td>
                                                {{$request->control_code}}
                                            </td>   
                                            <td>
                                                {{$request->title}}
                                            </td>   
                                            <td>
                                                {{$request->revision}}
                                            </td>   
                                           
                                            @else
                                            <td></td>
                                            <td>{{$request->title}}</td>
                                            <td></td>
                                            @endif
                                            <td>
                                                {{$request->type_of_document}}
                                            </td>   
                                        <td>{{$request->user->name}}</td>
                                        <td> @if($request->status == "Pending")
                                            <span class='label label-warning'>
                                        @elseif($request->status ==  "Approved")
                                            <span class='label label-info'>    
                                        @elseif($request->status ==  "Declined")
                                                <span class='label label-danger'>
                                        @else<span class='label label-success'>
                                            @endif
                                            {{$request->status}}</span>  </td>
                                    </tr>
                                    @include('view_change_request')
                                    @include('edit_change_request')
                                @endforeach
                            
                        </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@include('new_change_request')
@endsection
@section('js')
<script src="{{ asset('login_css/js/plugins/dataTables/datatables.min.js')}}"></script>
<script src="{{ asset('login_css/js/plugins/chosen/chosen.jquery.js') }}"></script>
<script>
    $(document).ready(function(){
        

        $('.cat').chosen({width: "100%"});
        $('.tables').DataTable({
            pageLength: 25,
            responsive: true,
            dom: '<"html5buttons"B>lTfgitp',
            buttons: [
                { extend: 'copy'},
                {extend: 'csv'},
                {extend: 'excel', title: 'ExampleFile'},
                {extend: 'pdf', title: 'ExampleFile'},

                {extend: 'print',
                 customize: function (win){
                        $(win.document.body).addClass('white-bg');
                        $(win.document.body).css('font-size', '10px');

                        $(win.document.body).find('table')
                                .addClass('compact')
                                .css('font-size', 'inherit');
                }
                }
            ]

        });

    });

</script>
@endsection
