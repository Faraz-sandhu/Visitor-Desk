@extends('layouts.app') @section('title','Companies') @section('subtitle','Manage companies represented in your office') @section('content')
<div class="actions">
    <form class="search" role="search"><input aria-label="Search records" name="q" value="{{ request('q') }}" placeholder="Search companies…"><button>Search</button></form>
</div>
<div class="panel directory-panel">
    <div class="panel-head">
        <div>
            <h2>Company directory</h2>
            <p>Organizations connected to your workplace.</p>
        </div><span class="record-count">{{ $companies->total() }} records</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Employees</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>@forelse($companies as $company)<tr>
                    <td><b>{{ $company->name }}</b><small>{{ $company->address }}</small></td>
                    <td>{{ $company->email ?: '—' }}<small>{{ $company->phone }}</small></td>
                    <td>{{ $company->employees_count }}</td>
                    <td><span class="badge {{ $company->is_active?'green':'gray' }}">{{ $company->is_active?'Active':'Inactive' }}</span></td>
                    <td class="row-actions"><a class="icon-btn" title="Edit company" aria-label="Edit company" href="{{ route('companies.edit',$company) }}"><i data-icon="edit"></i></a>
                        <form method="POST" action="{{ route('companies.destroy',$company) }}" data-confirm="Delete this company?">@csrf @method('DELETE')<button class="icon-btn danger" title="Delete company" aria-label="Delete company"><i data-icon="trash"></i></button></form>
                    </td>
                </tr>@empty<tr>
                    <td colspan="5" class="empty">No companies found.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
    <div class="pagination">{{ $companies->links() }}</div>
</div>@endsection