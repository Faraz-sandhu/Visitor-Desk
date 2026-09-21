@extends('layouts.app') @section('title',$company->exists?'Edit Company':'Add Company') @section('subtitle','Company information used for employee grouping') @section('content')
<form class="panel form-card" method="POST" action="{{ $company->exists?route('companies.update',$company):route('companies.store') }}">@csrf @if($company->exists)@method('PUT')@endif<div class="form-intro"><span class="eyebrow">WORKPLACE DIRECTORY</span>
        <h2>Company details</h2>
        <p>Keep your workplace directory organized and up to date. Fields marked * are required.</p>
    </div>
    <div class="form-grid"><label class="full">Company name *<input name="name" value="{{ old('name',$company->name) }}" required></label><label>Email<input type="email" name="email" value="{{ old('email',$company->email) }}"></label><label>Phone<input name="phone" value="{{ old('phone',$company->phone) }}"></label><label class="full">Address<textarea name="address" rows="3">{{ old('address',$company->address) }}</textarea></label><label class="check full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$company->exists?$company->is_active:true))> Active company</label></div>
    <div class="form-actions"><a class="btn" href="{{ route('companies.index') }}">Cancel</a><button class="btn primary">{{ $company->exists?'Save changes':'Create company' }}</button></div>
</form>@endsection