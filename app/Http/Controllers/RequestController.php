<?php

namespace App\Http\Controllers;
use App\Document;
use App\Department;
use App\Company;
use App\DepartmentApprover;
use App\CopyRequest;
use App\ChangeRequest;
use App\DocumentAttachment;
use App\CopyApprover;
use App\RequestApprover;
use App\ObsoleteAttachment;
use App\Obsolete;
use App\DocumentType;
use App\User;
use App\Notifications\ForApproval;
use App\Notifications\NewPolicy;
use App\Notifications\ApprovedRequest;
use App\Notifications\DeclineRequest;
use App\Notifications\ReturnRequest;
use App\Notifications\PendingRequest;



use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function editTile (Request $request,$id)
    {
        $change_request = ChangeRequest::findOrfail($id);
        $change_request->title = $request->title;
        $change_request->type_of_document = $request->document_type;
        $change_request->save();
        Alert::success('Successfully Updated')->persistent('Dismiss');
        return back();
    }
    public function editRequest (Request $request,$id)
    {
        $req = ChangeRequest::findOrfail($id);
        $req->change_request = $request->description;
        $req->indicate_clause = $request->from_clause;
        $req->indicate_changes = $request->to_changes;
        $req->save();

        Alert::success('Successfully Updated')->persistent('Dismiss');
        return back();
    }
    public function test()
    {
          info("START DCO");
        $users = User::where('status',null)->where('role','Document Control Officer')->get();
        foreach($users as $user)
        {
            $change_requests = ChangeRequest::with('approvers')->whereIn('department_id',($user->dco)->pluck('department_id')->toArray())->where('status','Pending')->get();

            $table = "<table style='margin-bottom:10px;' width='100%' border='1' cellspacing=0><tr><th>Date Requested</th><th>Code</th><th>Approver</th></tr>";
            foreach($change_requests as $request)
            {
                $approver = ($request->approvers)->where('level',$request->level)->first();
                $table .= "<tr><td>".date('Y-m-d',strtotime($request->created_at))."</td><td>CR-".str_pad($request->id, 5, '0', STR_PAD_LEFT)."</td><td>".$approver->user->name."</td></tr>";
            }
            $table .= "</table>";
            if(count($change_requests) >0)
            {
                $user->notify(new PendingRequest($table));
            }
           
        }
        $users_d = User::where('status',null)->where('role','Business Process Manager')->orWhere('role','Management Representative')->get();
        foreach($users_d as $user)
        {
            $change_requests = ChangeRequest::with('approvers')->where('status','Pending')->get();

            $table = "<table style='margin-bottom:10px;' width='100%' border='1' cellspacing=0><tr><th>Date Requested</th><th>Code</th><th>Approver</th></tr>";
            foreach($change_requests as $request)
            {
                $approver = ($request->approvers)->where('level',$request->level)->first();
                $table .= "<tr><td>".date('Y-m-d',strtotime($request->created_at))."</td><td>CR-".str_pad($request->id, 5, '0', STR_PAD_LEFT)."</td><td>".$approver->user->name."</td></tr>";
            }
            $table .= "</table>";
            if(count($change_requests) >0)
            {
                $user->notify(new PendingRequest($table));
            }
        }

        $users_approvers = User::where('status',null)->get();
        foreach($users_approvers as $user)
        {
            $change_requests = ChangeRequest::whereHas('approvers',function($q) use($user){
                $q->where('user_id',  $user->id)->where('status','Pending');
            })->where('status','Pending')->get();

            $copy_requests = CopyRequest::whereHas('approvers',function($q) use($user){
                $q->where('user_id',  $user->id)->where('status','Pending');
            })->where('status','Pending')->get();

            $table = "<table style='margin-bottom:10px;' width='100%' border='1' cellspacing=0><tr><th colspan='3'>For Your Approval</th></tr>";
            if(count($change_requests) > 0)
           
            {
                $table .= "<tr><th colspan='3'>Change Requests</th></tr>";
            }
            $table .= "<tr><th>Date Requested</th><th>Code</th><th>Approver</th></tr>";
            foreach($change_requests as $request)
            {
                $approver = ($request->approvers)->where('level',$request->level)->first();
                $table .= "<tr><td>".date('Y-m-d',strtotime($request->created_at))."</td><td>DICR-".str_pad($request->id, 5, '0', STR_PAD_LEFT)."</td><td>".$approver->user->name."</td></tr>";
            }
            if(count($copy_requests) > 0)
            {
                $table .= "<tr><th colspan='3'>Copy Requests</th></tr>";
            }
                foreach($copy_requests as $request)
                {
                    $approver = ($request->approvers)->where('level',$request->level)->first();
                    $table .= "<tr><td>".date('Y-m-d',strtotime($request->created_at))."</td><td>CR-".str_pad($request->id, 5, '0', STR_PAD_LEFT)."</td><td>".$approver->user->name."</td></tr>";
                }
        
            
            $table .= "</table>";

            if((count($change_requests) >0) ||(count($copy_requests) >0))
            {
                $user->notify(new PendingRequest($table));
            }
        }
      
        info("END DCO");
    }
    public function index()
    {
        //
       
        $requests = CopyRequest::with('document')->orderBy('id','desc')->get();
        if(auth()->user()->role == "User")
        {
            $requests = CopyRequest::with('document')->where('user_id',auth()->user()->id)->orderBy('id','desc')->get();
        }
        else if(auth()->user()->role == "Document Control Officer")
        {
            $requests = CopyRequest::with('document')->whereIn('department_id',(auth()->user()->dco)->pluck('department_id')->toArray())->orderBy('id','desc')->get();
        }
        else if(auth()->user()->role == "Department Head")
        {
            $requests = CopyRequest::with('document')->whereIn('department_id',(auth()->user()->department_head)->pluck('id')->toArray())->orderBy('id','desc')->get();
        }
        else if(auth()->user()->role == "Documents and Records Controller")
        {
            $requests = CopyRequest::with('document')->where('user_id',auth()->user()->id)->orderBy('id','desc')->get();
        }
        return view('requests',
        array(
            'requests' =>  $requests,
        ));
    }
    public function changeRequests()
    {
        //
        $departments = Department::where('id',auth()->user()->department_id)->where('status',null)->get();
        $companies = Company::where('status',null)->get();
        $document_types = DocumentType::get();
        $approvers = DepartmentApprover::where('department_id',auth()->user()->department_id)->get();
        $requests = ChangeRequest::orderBy('id','desc')->get();
        if(auth()->user()->role == "User")
        {
            $requests = ChangeRequest::where('user_id',auth()->user()->id)->orderBy('id','desc')->get();
        }
        else if(auth()->user()->role == "Document Control Officer")
        {
            $requests = ChangeRequest::whereIn('department_id',(auth()->user()->dco)->pluck('department_id')->toArray())->orderBy('id','desc')->get();
        }
        else if(auth()->user()->role == "Department Head")
        {
            $requests = ChangeRequest::whereIn('department_id',(auth()->user()->department_head)->pluck('id')->toArray())->orderBy('id','desc')->get();
        }
        else if(auth()->user()->role == "Documents and Records Controller")
        {
            $requests = ChangeRequest::where('user_id',auth()->user()->id)->orderBy('id','desc')->get();
        }
        return view('change_requests',
        
        array(
            'requests' =>  $requests,
            
            'companies' =>  $companies,
            'departments' =>  $departments,
            'approvers' =>  $approvers,
            'document_types' =>  $document_types,
        ));
    }
    public function removeApprover()
    {
        //
       
        $change_for_approvals = RequestApprover::orderBy('id','desc')->get();
       

        return view('for_removals',
        array(
           'change_for_approvals' => $change_for_approvals,
        ));
    }
    public function removeApp(Request $request,$id)
    {
        if($request->approver == null)
        {
            $appro = [];
        }
        else
        {
            $appro = $request->approver;
        }
        $approvers = RequestApprover::orderBy('id','desc')->where('change_request_id',$id)->whereNotIn('id', $appro)->where('status','Waiting')->delete();
        Alert::success('Successfully Updated')->persistent('Dismiss');
        return back();
        
    }
    public function forApproval()
    {
        //
        $document_types = DocumentType::get();
        $copy_for_approvals = CopyApprover::orderBy('id','desc')->where('user_id',auth()->user()->id)->get();
        $change_for_approvals = RequestApprover::orderBy('id','desc')->where('user_id',auth()->user()->id)->get();
        if(auth()->user()->role == "Administrator")
        {
            $copy_for_approvals = CopyApprover::orderBy('id','desc')->get();
            $change_for_approvals = RequestApprover::orderBy('id','desc')->get();
        }
       

        return view('for_approval',
        array(
           'copy_for_approvals' => $copy_for_approvals,
           'change_for_approvals' => $change_for_approvals,
           'document_types' => $document_types,
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $document = Document::findOrfail($request->id);
        $original_pdf = DocumentAttachment::where('document_id',$request->id)->where('type','pdf_copy')->first();
        $original_soft_copy = DocumentAttachment::where('document_id',$request->id)->where('type','soft_copy')->first();

      
        $changeRequest = new ChangeRequest;
        $changeRequest->request_type = $request->request_type;
        $changeRequest->effective_date = $request->effective_date;
        $changeRequest->department_id = auth()->user()->department_id;
        $changeRequest->company_id = auth()->user()->company_id;
        $changeRequest->user_id = auth()->user()->id;
        $changeRequest->type_of_document = $document->category;
        $changeRequest->document_id = $request->id;
        $changeRequest->change_request = $request->description;
        $changeRequest->indicate_clause = $request->from_clause;
        $changeRequest->indicate_changes = $request->to_changes;
        $changeRequest->link_draft = $request->draft_link;
        $changeRequest->status = "Pending";
        $changeRequest->level = 1;
        $changeRequest->control_code = $request->control_code;
        $changeRequest->title = $request->title;
        $changeRequest->revision = $request->revision;
        if($original_pdf != null)
        {
            $changeRequest->original_attachment_pdf = $original_pdf->attachment;
        }
        if($original_soft_copy != null)
        {
            $changeRequest->original_attachment_soft_copy = $original_soft_copy->attachment;
        }
        $changeRequest->save();
    
        $approvers = DepartmentApprover::where('department_id',$document->department_id)->orderBy('level','asc')->get();
        foreach($approvers as $approver)
        {
            $copy_approver = new RequestApprover;
            $copy_approver->change_request_id = $changeRequest->id;
            $copy_approver->user_id = $approver->user_id;
           
            if($approver->level == 1)
            {
                $copy_approver->status = "Pending";
                $copy_approver->start_date = date('Y-m-d');
                $ApproverNotif = User::where('id',$copy_approver->user_id)->first();
                $ApproverNotif->notify(new ForApproval($changeRequest,"DICR-","Change Request"));
            }
            else
            {
                $copy_approver->status = "Waiting";
               
            }
            $copy_approver->level = $approver->level;
            $copy_approver->save();
        }

        Alert::success('Successfully Submitted')->persistent('Dismiss');
        return redirect('/change-requests');


    }
    public function new_request(Request $request)
    {
        //

      
        $changeRequest = new ChangeRequest;
        $changeRequest->request_type = $request->request_type;
        $changeRequest->effective_date = $request->effective_date;
        $changeRequest->department_id = $request->department;
        $changeRequest->company_id = $request->company;
        $changeRequest->title = $request->title;
        $changeRequest->user_id = auth()->user()->id;
        $changeRequest->type_of_document = $request->category;
        $changeRequest->change_request = $request->description;
        $changeRequest->link_draft = $request->draft_link;
        $changeRequest->status = "Pending";
        $changeRequest->level = 1;
        if($request->has('soft_copy'))
        {
            $attachment = $request->file('soft_copy');
        
            $name = time() . '_' . $attachment->getClientOriginalName();
            $attachment->move(public_path() . '/document_attachments/', $name);
            $file_name = '/document_attachments/' . $name;
            $changeRequest->soft_copy = $file_name;
        }
        if($request->has('pdf_copy'))
        {
            $attachment = $request->file('pdf_copy');
            $name = time() . '_' . $attachment->getClientOriginalName();
            $attachment->move(public_path() . '/document_attachments/', $name);
            $file_name = '/document_attachments/' . $name;
            $changeRequest->pdf_copy = $file_name;
        }
        if($request->has('fillable_copy'))
        {
            $attachment = $request->file('fillable_copy');
            $name = time() . '_' . $attachment->getClientOriginalName();
            $attachment->move(public_path() . '/document_attachments/', $name);
            $file_name = '/document_attachments/' . $name;
            $changeRequest->fillable_copy = $file_name;
        }
        
        $changeRequest->save();

        
    
        $approvers = DepartmentApprover::where('department_id',auth()->user()->department_id)->orderBy('level','asc')->get();
        // dd($approvers);
        foreach($approvers as $approver)
        {
            $copy_approver = new RequestApprover;
            $copy_approver->change_request_id = $changeRequest->id;
            $copy_approver->user_id = $approver->user_id;
           
            if($approver->level == 1)
            {
                $copy_approver->status = "Pending";
                $copy_approver->start_date = date('Y-m-d');
                $ApproverNotif= User::where('id',$copy_approver->user_id)->first();
                $ApproverNotif->notify(new ForApproval($changeRequest,"DICR-","Document Information Change Request"));
            }
            else
            {
                $copy_approver->status = "Waiting";
               
            }
            $copy_approver->level = $approver->level;
            $copy_approver->save();
        }

        Alert::success('Successfully Submitted')->persistent('Dismiss');
        return redirect('/change-requests');


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function action(Request $request,$id)
    {
        $copyRequestApprover = RequestApprover::findOrfail($id);
        $copyRequestApprover->status = $request->action;
        $copyRequestApprover->remarks = $request->remarks;
        $copyRequestApprover->save();

        $copyApprover = RequestApprover::where('change_request_id',$copyRequestApprover->change_request_id)->where('status','Waiting')->orderBy('level','asc')->first();
        $copyRequest = ChangeRequest::findOrfail($copyRequestApprover->change_request_id);

        
        if($request->action == "Approved")
        {
            if(auth()->user()->role == "Document Control Officer")
            {
                if($request->has('soft_copy'))
                {
                    $attachment = $request->file('soft_copy');
                
                    $name = time() . '_' . $attachment->getClientOriginalName();
                    $attachment->move(public_path() . '/document_attachments/', $name);
                    $file_name = '/document_attachments/' . $name;
                    $copyRequest->soft_copy = $file_name;
                    $copyRequest->save();
                }
                if($request->has('pdf_copy'))
                {
                    $attachment = $request->file('pdf_copy');
                    $name = time() . '_' . $attachment->getClientOriginalName();
                    $attachment->move(public_path() . '/document_attachments/', $name);
                    $file_name = '/document_attachments/' . $name;
                    $copyRequest->pdf_copy = $file_name;
                    $copyRequest->save();
                }
                if($request->has('fillable_copy'))
                {
                    $attachment = $request->file('fillable_copy');
                    $name = time() . '_' . $attachment->getClientOriginalName();
                    $attachment->move(public_path() . '/document_attachments/', $name);
                    $file_name = '/document_attachments/' . $name;
                    $copyRequest->fillable_copy = $file_name;
                    $copyRequest->save();
                }
                
            }
            if($copyApprover == null)
            {
                if($copyRequest->request_type == "Revision")
                {
                    $document = Document::findOrfail($copyRequest->document_id);
                    $obsolete = new Obsolete;
                    $obsolete->document_id = $document->id;
                    $obsolete->control_code = $document->control_code;
                    $obsolete->company_id = $document->company_id;
                    $obsolete->department_id = $document->department_id;
                    $obsolete->title = $document->title;
                    $obsolete->category = $document->category;
                    $obsolete->other_category = $document->other_category;
                    $obsolete->user_id = $copyRequest->user_id;
                    $obsolete->version = $document->version;
                    $obsolete->save();

                    $attacments = DocumentAttachment::where('document_id',$document->id)->get();
                    foreach($attacments as $attach)
                    {
                        $obsolete_attach = new ObsoleteAttachment;
                        $obsolete_attach->obsolete_id = $obsolete->id;
                        $obsolete_attach->attachment = $attach->attachment;
                        $obsolete_attach->type = $attach->type;
                        $obsolete_attach->save();
                    }

                    $document->version = $document->version + 1;
                    $document->effective_date = $copyRequest->effective_date;
                    $document->user_id = $copyRequest->user_id;
                    $document->save();

 
                    $attac = DocumentAttachment::where('document_id',$document->id)->delete();
                    $new_attach = new DocumentAttachment;
                    $new_attach->document_id = $document->id; 
                    $new_attach->type = "soft_copy"; 
                    $new_attach->attachment = $copyRequest->soft_copy; 
                    $new_attach->save(); 

                    $new_attach = new DocumentAttachment;
                    $new_attach->document_id = $document->id; 
                    $new_attach->type = "pdf_copy"; 
                    $new_attach->attachment = $copyRequest->pdf_copy; 
                    $new_attach->save(); 

                    if($copyRequest->fillable_copy != null)
                    {
                        $new_attach = new DocumentAttachment;
                        $new_attach->document_id = $document->id; 
                        $new_attach->type = "fillable_copy"; 
                        $new_attach->attachment = $copyRequest->fillable_copy; 
                        $new_attach->save(); 
                    }

                }
                if($copyRequest->request_type == "Obsolete")
                {
                    $document = Document::findOrfail($copyRequest->document_id);
                    $document->status = "Obsolete";
                    $document->save();
                }
                if($copyRequest->request_type == "New")
                {
                   $company = Company::findOrFail($copyRequest->company_id);
                   $department = Department::findOrFail($copyRequest->department_id);
                   $type_of_doc = DocumentType::where('name',$copyRequest->type_of_document)->first();
                   $company_code = explode('-',$department->code);
                   
                   $document_get_latest = Document::where('company_id',$copyRequest->company_id)->where('department_id',$copyRequest->department_id)->where('category',$copyRequest->type_of_document)->orderBy('control_code','desc')->first();
                   if($document_get_latest == null)
                   {
                        $code = $company_code[0]."-".$type_of_doc->code."-".$company_code[1]."-001";
                   }
                   else
                   {
                        $c = $document_get_latest->control_code;
                        $c = explode("-", $c);
                        $last_code = ((int)$c[count($c)-1])+1;
                        $code = $company_code[0]."-".$type_of_doc->code."-".$company_code[1]."-".str_pad($last_code, 3, '0', STR_PAD_LEFT);
                   }
                   $new_document = new Document;
                   $new_document->company_id = $copyRequest->company_id;
                   $new_document->department_id = $copyRequest->department_id;
                   $new_document->title = $copyRequest->title;
                   $new_document->category = $copyRequest->type_of_document;
                   $new_document->effective_date = date('Y-m-d');
                   $new_document->user_id = $copyRequest->user_id;
                   $new_document->version = 0;
                   $new_document->control_code = $code;
                   $new_document->save();

                   $copyRequest->document_id = $new_document->id;
                   $copyRequest->control_code = $code;
                   $copyRequest->revision = 0;

                   $new_attach = new DocumentAttachment;
                    $new_attach->document_id = $new_document->id; 
                    $new_attach->type = "soft_copy"; 
                    $new_attach->attachment = $copyRequest->soft_copy; 
                    $new_attach->save(); 

                    $new_attach = new DocumentAttachment;
                    $new_attach->document_id = $new_document->id; 
                    $new_attach->type = "pdf_copy"; 
                    $new_attach->attachment = $copyRequest->pdf_copy; 
                    $new_attach->save(); 

                    if($copyRequest->fillable_copy != null)
                    {
                        $new_attach = new DocumentAttachment;
                        $new_attach->document_id = $new_document->id; 
                        $new_attach->type = "fillable_copy"; 
                        $new_attach->attachment = $copyRequest->fillable_copy; 
                        $new_attach->save(); 
                    }
                }
                $copyRequest->status = "Approved";
                $copyRequest->save();

                $approvedRequestsNotif = User::where('id',$copyRequest->user_id)->first();
                $approvedRequestsNotif->notify(new ApprovedRequest($copyRequest,"DICR-","Document Information Change Request","request"));

                $approvers_all = RequestApprover::where('change_request_id',$copyRequestApprover->change_request_id)->orderBy('level','asc')->get();
                foreach($approvers_all as $user_approver)
                {
                    $app = User::where('id',$user_approver->user_id)->first();
                    $app->notify(new NewPolicy($copyRequest,"DICR-","Document Information Change Request","request"));
                }
            }
            else
            {
                $copyApprover->start_date = date('Y-m-d');
                $copyApprover->status = "Pending";
                $copyApprover->save();
                $copyRequest->level = $copyRequest->level+1;
                $copyRequest->save();

                $nextApproverNotif = User::where('id',$copyApprover->user_id)->first();
                $nextApproverNotif->notify(new ForApproval($copyRequest,"DICR-","Document Information Change Request"));
            }
            Alert::success('Successfully Approved')->persistent('Dismiss');
            return back();
        }
        elseif($request->action == "Returned")
        {
            $copyRequest->status = "Pending";
            $copyRequest->level =1;
            $copyRequest->save(); 
            $copyApproverPending = RequestApprover::where('change_request_id',$copyRequestApprover->change_request_id)->get();
            foreach($copyApproverPending as $key => $app)
            {
                $appr = RequestApprover::findOrfail($app->id);
                if($key == 0)
                {
                    $app->status = "Pending";
                }
                else
                {
                    $app->status = "Waiting";
                }
                $app->save();
            }
            $declinedRequestNotif = User::where('id',$copyRequest->user_id)->first();
            $declinedRequestNotif->notify(new ReturnRequest($copyRequest,"DICR-","Document Information Change Request","change-requests"));

            Alert::success('Successfully Returned')->persistent('Dismiss');
            return back();
        }
        else
        {
            $copyRequest->status = "Declined";
            $copyRequest->save(); 
            
            $declinedRequestNotif = User::where('id',$copyRequest->user_id)->first();
            $declinedRequestNotif->notify(new DeclineRequest($copyRequest,"DICR-","Document Information Change Request","change-requests"));

            Alert::success('Successfully Declined')->persistent('Dismiss');
            return back();
        }
    
    }
    public function changeReports(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        if($from)
        {
        $requests = ChangeRequest::where('created_at', '>=', $from)
        ->where('created_at', '<=', $to )->orderBy('id','desc')->get();
        }
        else
        {
            $requests = ChangeRequest::orderBy('id','desc')->get();
        }
        return view('change_reports',
        array(
            'requests' =>  $requests,
            'from' =>  $from,
            'to' =>  $to,
        ));
    }
    public function docReports(Request $request)
    {
        $dco = $request->dco;
        $dcos = User::where('role','Document Control Officer')->get();
        $requests = ChangeRequest::orderBy('id','desc')->get();
        if($dco != null)
        {
          
            $user = User::where('id',$request->dco)->first();
            $requests = ChangeRequest::whereIn('department_id',($user->dco)->pluck('department_id')->toArray())->orderBy('id','desc')->get();
        }
        

        return view('dcoReports',
        
        array(
            'requests' =>  $requests,
            'dcos' =>  $dcos,
            'dco' =>  $dco,
        ));
        
    }
}
