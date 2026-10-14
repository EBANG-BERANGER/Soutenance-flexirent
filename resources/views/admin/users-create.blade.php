@extends('layouts.admin')
@section('title', 'Add User')
@section('content')
<h2 class="mb-4">Add User</h2>

<form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" style="max-width: 900px;">
    @csrf
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Full name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Role</label>
            <select name="role" id="role" class="form-select" onchange="fxTogglePartnerFields()">
                <option value="client" @selected(old('role') == 'client')>Client</option>
                <option value="partner" @selected(old('role') == 'partner')>Partner</option>
                <option value="admin" @selected(old('role') == 'admin')>Admin</option>
            </select>
        </div>
    </div>
    <div id="partner-fields" class="mb-3 d-none">
        <label class="form-label d-block">Partner type</label>
        <div class="fx-pay-options mb-3">
            <label class="fx-pay-option">
                <input type="radio" name="partner_type" value="agency" onchange="fxTogglePartnerFields()" {{ old('partner_type', 'agency') == 'agency' ? 'checked' : '' }}>
                <div><strong>Agency</strong><p class="text-muted small mb-0">Company name and TIN are required.</p></div>
            </label>
            <label class="fx-pay-option">
                <input type="radio" name="partner_type" value="individual" onchange="fxTogglePartnerFields()" {{ old('partner_type') == 'individual' ? 'checked' : '' }}>
                <div><strong>Individual owner</strong><p class="text-muted small mb-0">No company registration needed.</p></div>
            </label>
        </div>
        <div id="agency-fields" class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Company name</label>
                <input type="text" name="company_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">TIN</label>
                <input type="text" name="tin" class="form-control">
            </div>
        </div>
        <div id="individual-fields" class="row d-none">
            <div class="col-md-6 mb-3">
                <label class="form-label">National ID number</label>
                <input type="text" name="national_id_number" class="form-control">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Supporting document (business registration or ID/passport scan)</label>
                <input type="file" name="documents_path" class="form-control" accept=".pdf,image/*">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" autocomplete="new-password" required>
            <div class="form-text">At least 8 characters, with uppercase, lowercase, a number and a symbol.</div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Confirm password</label>
            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
        </div>
    </div>
    <button class="btn btn-gold">Create user</button>
    <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">Cancel</a>
</form>

<script>
function fxTogglePartnerFields() {
    var role = document.getElementById('role').value;
    var partnerFields = document.getElementById('partner-fields');
    var agencyFields = document.getElementById('agency-fields');
    var individualFields = document.getElementById('individual-fields');
    var isPartner = role === 'partner';
    partnerFields.classList.toggle('d-none', !isPartner);

    var typeInput = document.querySelector('input[name="partner_type"]:checked');
    var isAgency = !typeInput || typeInput.value === 'agency';
    var isIndividual = isPartner && !isAgency;
    agencyFields.classList.toggle('d-none', !(isPartner && isAgency));
    individualFields.classList.toggle('d-none', !isIndividual);

    document.querySelector('input[name="company_name"]').required = isPartner && isAgency;
    document.querySelector('input[name="tin"]').required = isPartner && isAgency;
    document.querySelector('input[name="national_id_number"]').required = isIndividual;
    document.querySelector('input[name="documents_path"]').required = isPartner;
}
document.addEventListener('DOMContentLoaded', fxTogglePartnerFields);
</script>
@endsection
