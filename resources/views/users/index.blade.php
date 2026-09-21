@extends('layouts.app')
@section('title','Users & roles')
@section('subtitle','Manage who can access your reception workspace.')
@section('content')
<div class="role-guide"><div><b>Admin</b><p>Manage users, edit/delete records, and download reports.</p></div><div><b>Desk</b><p>View visits and employees, check visitors in/out, and add employees and companies.</p></div></div>
<div class="panel"><div class="table-wrap"><table><thead><tr><th>User</th><th>Email</th><th>Role</th><th>Manage</th></tr></thead><tbody>@foreach($users as $user)<tr><td><b>{{ $user->name }}</b>@if($user->is(auth()->user()))<small>Your account</small>@endif</td><td>{{ $user->email }}</td><td><span class="badge {{ $user->isAdmin()?'green':'gray' }}">{{ $user->isAdmin()?'Admin':'Desk' }}</span></td><td><a class="icon-btn" href="{{ route('users.edit',$user) }}" aria-label="Edit {{ $user->name }}" title="Edit user"><i data-icon="edit"></i></a></td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $users->links() }}</div></div>
@endsection
