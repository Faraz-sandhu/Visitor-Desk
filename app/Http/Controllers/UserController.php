<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
class UserController extends Controller {
 public function index() { return view('users.index',['users'=>User::orderBy('name')->paginate(20)]); }
 public function create() { return view('users.form',['user'=>new User]); }
 public function store(Request $request) { User::create($this->validated($request));return to_route('users.index')->with('success','User created. Share their sign-in details privately.'); }
 public function edit(User $user) { return view('users.form',compact('user')); }
 public function update(Request $request,User $user) {
  $data=$this->validated($request,$user);
  DB::transaction(function() use ($data,$user) {
   $admins=User::where('role','admin')->lockForUpdate()->get();
   if($user->isAdmin() && $data['role']!=='admin' && $admins->count()<=1) throw \Illuminate\Validation\ValidationException::withMessages(['role'=>'Keep at least one administrator.']);
   $user->update($data);
  });
  return to_route($request->user()->fresh()->isAdmin()?'users.index':'dashboard')->with('success','User updated.');
 }
 private function validated(Request $request,?User $user=null): array {
  $data=$request->validate(['name'=>['required','string','max:150'],'email'=>['required','email','max:150',Rule::unique('users')->ignore($user)],'role'=>['required',Rule::in(['admin','desk'])],'password'=>[$user?'nullable':'required','string','min:8','max:200','confirmed']]);
  if(empty($data['password'])) unset($data['password']);
  return $data;
 }
}
