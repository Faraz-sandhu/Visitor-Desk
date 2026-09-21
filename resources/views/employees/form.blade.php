@extends('layouts.app') @section('title',$employee->exists?'Edit Employee':'Add Employee') @section('subtitle','Add the person and the company where they work') @section('content')
<form class="panel form-card" method="POST" action="{{ $employee->exists?route('employees.update',$employee):route('employees.store') }}">@csrf @if($employee->exists)@method('PUT')@endif<div class="form-intro"><span class="eyebrow">WORKPLACE DIRECTORY</span>
        <h2>Employee details</h2>
        <p>Connect your team with the people coming to see them. Fields marked * are required.</p>
    </div>
    <div class="form-grid"><label>Employee name *<input name="name" value="{{ old('name',$employee->name) }}" required></label>
        <div class="company-picker" data-company-picker data-can-delete="{{ auth()->user()->isAdmin() ? '1' : '0' }}" data-create-url="{{ route('companies.store') }}">
            <label>Company *<select name="company_id" required data-company-select>
                    <option value="">Select a company</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(old('company_id',$employee->company_id)==$company->id)>{{ $company->name }}{{ $company->is_active ? '' : ' (inactive)' }}</option>@endforeach
                </select></label>
            <div class="company-manager"><label for="company-search">Find or add a company</label><input id="company-search" type="search" maxlength="150" placeholder="Type a company name..." autocomplete="off" data-company-search>
                <div class="company-options" aria-label="Available companies">@foreach($companies as $company)<div class="company-option" data-company-row data-name="{{ $company->name }}" data-id="{{ $company->id }}"><button type="button" class="company-choice" data-select-company="{{ $company->id }}">{{ $company->name }}@unless($company->is_active)<small>Inactive</small>@endunless</button>@can('admin')<button type="button" class="icon-btn danger" data-delete-company="{{ route('companies.destroy',$company) }}" aria-label="Delete {{ $company->name }}" title="Delete company"><i data-icon="trash"></i></button>@endcan</div>@endforeach</div><button type="button" class="btn company-add" data-add-company hidden><i data-icon="plus"></i><span>Add company</span></button>
                <p class="company-feedback" role="status" aria-live="polite" data-company-feedback></p>
            </div>
        </div><label>Designation<input name="designation" value="{{ old('designation',$employee->designation) }}" placeholder="e.g. HR Manager"></label><label>Email<input type="email" name="email" value="{{ old('email',$employee->email) }}"></label><label>Phone<input name="phone" value="{{ old('phone',$employee->phone) }}"></label><label class="check full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$employee->exists?$employee->is_active:true))> Active employee</label>
    </div>
    <div class="form-actions"><a class="btn" href="{{ route('employees.index') }}">Cancel</a><button class="btn primary">{{ $employee->exists?'Save changes':'Create employee' }}</button></div>
</form>@endsection