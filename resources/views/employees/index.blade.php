@extends('layouts.app') @section('title','Employees') @section('subtitle','People visitors can select for meetings') @section('content')
<div class="actions">
    <form class="search" role="search"><input aria-label="Search records" name="q" value="{{ request('q') }}" placeholder="Search name or designation…"><button>Search</button></form>
</div>
<div class="panel directory-panel">
    <div class="panel-head">
        <div>
            <h2>Team directory</h2>
            <p>Your people and their workplace connections.</p>
        </div><span class="record-count">{{ $employees->total() }} records</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>@forelse($employees as $employee)<tr>
                    <td>
                        <div class="person"><span class="avatar">{{ strtoupper(substr($employee->name,0,1)) }}</span>
                            <div><b>{{ $employee->name }}</b><small>{{ $employee->designation ?: 'No designation' }}</small></div>
                        </div>
                    </td>
                    <td>{{ $employee->company->name }}</td>
                    <td>{{ $employee->email ?: '—' }}<small>{{ $employee->phone }}</small></td>
                    <td><span class="badge {{ $employee->is_active?'green':'gray' }}">{{ $employee->is_active?'Active':'Inactive' }}</span></td>
                    <td class="row-actions">@can('admin')<a class="icon-btn" title="Edit employee" aria-label="Edit employee" href="{{ route('employees.edit',$employee) }}"><i data-icon="edit"></i></a>
                        <form method="POST" action="{{ route('employees.destroy',$employee) }}" data-confirm="Delete this employee?">@csrf @method('DELETE')<button class="icon-btn danger" title="Delete employee" aria-label="Delete employee"><i data-icon="trash"></i></button></form>@else<span class="muted">Desk access</span>@endcan
                    </td>
                </tr>@empty<tr>
                    <td colspan="5" class="empty">No employees found.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
    <div class="pagination">{{ $employees->links() }}</div>
</div>@endsection