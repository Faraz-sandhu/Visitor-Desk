<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $visits=Visit::with('employee.company')->when($request->q,fn($q,$s)=>$q->where(fn($x)=>$x->where('visitor_name','like',"%{$s}%")->orWhere('id_card_number','like',"%{$s}%")->orWhereHas('employee',fn($e)=>$e->where('name','like',"%{$s}%"))))
            ->when($request->status==='inside',fn($q)=>$q->whereNull('check_out_at'))->when($request->status==='completed',fn($q)=>$q->whereNotNull('check_out_at'))
            ->when($request->date,fn($q,$d)=>$q->whereDate('check_in_at',$d))->latest('check_in_at')->paginate(20)->withQueryString();
        return view('visits.index',compact('visits'));
    }
    public function create(): View { return view('visits.form',['visit'=>new Visit,'employees'=>$this->employees()]); }
    public function store(Request $request): RedirectResponse
    {
        $data=$this->validated($request); $data['created_by']=$request->user()->id; $data['check_in_at']=$data['check_in_at'] ?? now();
        if($request->hasFile('photo')) $data['photo_path']=$request->file('photo')->store('visitor-photos','public');
        $visit=Visit::create($data); return to_route('visits.show',$visit)->with('success','Visitor checked in successfully.');
    }
    public function photo(Visit $visit) {
        abort_unless($visit->photo_path && Storage::disk('public')->exists($visit->photo_path),404);
        return Storage::disk('public')->response($visit->photo_path,null,['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
    public function show(Visit $visit): View { $visit->load('employee.company','receptionist'); return view('visits.show',compact('visit')); }
    public function edit(Visit $visit): View { return view('visits.form',['visit'=>$visit,'employees'=>$this->employees()]); }
    public function update(Request $request, Visit $visit): RedirectResponse
    {
        $data=$this->validated($request, $visit);
        if($request->hasFile('photo')) { if($visit->photo_path) Storage::disk('public')->delete($visit->photo_path); $data['photo_path']=$request->file('photo')->store('visitor-photos','public'); }
        $visit->update($data); return to_route('visits.show',$visit)->with('success','Visit updated successfully.');
    }
    public function checkout(Visit $visit): RedirectResponse { if($visit->check_out_at) return back()->with('error','Visitor is already checked out.'); $visit->update(['check_out_at'=>now()]); return back()->with('success','Visitor checked out successfully.'); }
    public function destroy(Visit $visit): RedirectResponse { if($visit->photo_path) Storage::disk('public')->delete($visit->photo_path); $visit->delete(); return to_route('visits.index')->with('success','Visit record deleted.'); }
    private function employees() { return Employee::with('company')->where('is_active',true)->whereHas('company',fn($q)=>$q->where('is_active',true))->orderBy('name')->get(); }
    private function validated(Request $request, ?Visit $visit = null): array { $data = $request->validate(['employee_id'=>['required','exists:employees,id'],'visitor_name'=>['required','string','max:150'],'visitor_phone'=>['nullable','string','max:30'],'visitor_email'=>['nullable','email','max:150'],'id_card_number'=>['required','string','max:50'],'photo'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],'purpose'=>['required','string','max:1000'],'check_in_time'=>['nullable','date_format:H:i'],'notes'=>['nullable','string','max:2000']]);
        $time = $data['check_in_time'] ?? null;
        unset($data['check_in_time']);
        $date = $visit?->check_in_at?->copy() ?? now();
        $data['check_in_at'] = $time ? $date->setTimeFromTimeString($time) : $date;
        if ($visit?->check_out_at && $data['check_in_at']->gt($visit->check_out_at)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['check_in_time' => 'Check-in must be before check-out.']);
        }
        return $data;
    }
}
