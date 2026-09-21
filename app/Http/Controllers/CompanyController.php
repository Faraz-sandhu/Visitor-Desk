<?php
namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View { $companies = Company::withCount('employees')->when($request->q, fn($q,$s)=>$q->where('name','like',"%{$s}%"))->latest()->paginate(15)->withQueryString(); return view('companies.index', compact('companies')); }
    public function create(): View { return view('companies.form', ['company' => new Company]); }
    public function store(Request $request): RedirectResponse|JsonResponse { $company = Company::create($this->validated($request)); if ($request->expectsJson()) return response()->json(['company'=>$company],201); return to_route('companies.index')->with('success','Company created successfully.'); }
    public function edit(Company $company): View { return view('companies.form', compact('company')); }
    public function update(Request $request, Company $company): RedirectResponse { $company->update($this->validated($request,$company)); return to_route('companies.index')->with('success','Company updated successfully.'); }
    public function destroy(Request $request, Company $company): RedirectResponse|JsonResponse {
        if ($company->employees()->exists()) {
            $message = 'Company cannot be deleted while it has employees. Reassign its employees first.';
            return $request->expectsJson() ? response()->json(['message'=>$message],409) : back()->with('error',$message);
        }
        $company->delete();
        return $request->expectsJson() ? response()->json(['message'=>'Company deleted.']) : back()->with('success','Company deleted.');
    }
    private function validated(Request $request, ?Company $company=null): array {
        if (is_string($request->input('name'))) $request->merge(['name'=>trim($request->input('name'))]);
        $data=$request->validate(['name'=>['required','string','max:150',Rule::unique('companies')->ignore($company), function ($attribute, $value, $fail) use ($company) {
            if (Company::whereRaw('LOWER(name) = ?', [mb_strtolower($value)])->when($company, fn($q)=>$q->where('id','!=',$company->id))->exists()) $fail('This company already exists. Select it from the list.');
        }],'address'=>['nullable','string','max:500'],'phone'=>['nullable','string','max:30'],'email'=>['nullable','email','max:150'],'is_active'=>['nullable','boolean']]); $data['is_active']=$request->boolean('is_active'); return $data; }
}
