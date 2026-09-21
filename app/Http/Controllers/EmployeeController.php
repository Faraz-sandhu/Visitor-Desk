<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View { $employees=Employee::with('company')->when($request->q,fn($q,$s)=>$q->where(fn($x)=>$x->where('name','like',"%{$s}%")->orWhere('designation','like',"%{$s}%")))->latest()->paginate(15)->withQueryString(); return view('employees.index',compact('employees')); }
    public function create(): View { return view('employees.form',['employee'=>new Employee,'companies'=>Company::orderBy('name')->get()]); }
    public function store(Request $request): RedirectResponse { Employee::create($this->validated($request)); return to_route('employees.index')->with('success','Employee created successfully.'); }
    public function edit(Employee $employee): View { return view('employees.form',['employee'=>$employee,'companies'=>Company::orderBy('name')->get()]); }
    public function update(Request $request, Employee $employee): RedirectResponse { $employee->update($this->validated($request,$employee)); return to_route('employees.index')->with('success','Employee updated successfully.'); }
    public function destroy(Employee $employee): RedirectResponse { if($employee->visits()->exists()) return back()->with('error','Employee cannot be deleted because visit history exists. Deactivate instead.'); $employee->delete(); return back()->with('success','Employee deleted.'); }
    private function validated(Request $request, ?Employee $employee=null): array { $data=$request->validate(['company_id'=>['required','exists:companies,id'],'name'=>['required','string','max:150'],'email'=>['nullable','email','max:150',Rule::unique('employees')->ignore($employee)],'phone'=>['nullable','string','max:30'],'designation'=>['nullable','string','max:100'],'is_active'=>['nullable','boolean']]); $data['is_active']=$request->boolean('is_active'); return $data; }
}
