@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4 auth-card">
            <h3 class="mb-4">Create your FlexiRent account</h3>
            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" autocomplete="off">
                @csrf
                <div class="fx-field mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="fx-field mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Profile photo</label>
                    <input type="file" name="profile_photo" class="form-control" accept="image/*">
                </div>
                <div class="fx-field mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="fx-field mb-3">
                    <label class="form-label">Phone (with country code)</label>
                    <input type="text" name="phone" class="form-control">
                    <p class="form-text">e.g. +250 78 000 0000</p>
                </div>
                <div class="fx-field mb-3">
                    <label class="form-label">Physical address</label>
                    <input type="text" name="address" class="form-control">
                    <p class="form-text">e.g. KG 7 Ave, Kigali</p>
                </div>
                <div class="mb-3">
                    <label class="form-label">I am registering as</label>
                    <select name="role" id="role" class="form-select" onchange="fxToggleRoleFields()">
                        <option value="client">Client (renting a vehicle)</option>
                        <option value="partner" @selected(old('role') == 'partner')>Partner (listing vehicles)</option>
                    </select>
                </div>
                <div id="partner-fields" class="mb-3 d-none">
                    <label class="form-label d-block">Partner type</label>
                    <div class="fx-pay-options mb-3">
                        <label class="fx-pay-option">
                            <input type="radio" name="partner_type" value="agency" onchange="fxToggleRoleFields()" {{ old('partner_type', 'agency') == 'agency' ? 'checked' : '' }}>
                            <div><strong>Agency</strong><p class="text-muted small mb-0">A registered rental company. Company name and TIN are required.</p></div>
                        </label>
                        <label class="fx-pay-option">
                            <input type="radio" name="partner_type" value="individual" onchange="fxToggleRoleFields()" {{ old('partner_type') == 'individual' ? 'checked' : '' }}>
                            <div><strong>Individual owner</strong><p class="text-muted small mb-0">You own the vehicle(s) yourself. No company registration needed.</p></div>
                        </label>
                    </div>
                    <div id="agency-fields">
                        <div class="fx-field mb-3">
                            <label class="form-label">Company name</label>
                            <input type="text" name="company_name" class="form-control">
                        </div>
                        <div class="fx-field mb-3">
                            <label class="form-label">TIN</label>
                            <input type="text" name="tin" class="form-control">
                        </div>
                    </div>
                    <div id="individual-fields" class="d-none">
                        <div class="fx-field mb-3">
                            <label class="form-label">National ID number</label>
                            <input type="text" name="national_id_number" class="form-control">
                        </div>
                    </div>
                    <div class="fx-field mb-3">
                        <label class="form-label">Supporting document (business registration or ID/passport scan)</label>
                        <input type="file" name="documents_path" class="form-control" accept=".pdf,image/*">
                        <p class="form-text">Required to register as a partner. Reviewed manually by FlexiRent before your account is validated.</p>
                    </div>
                </div>
                <div class="fx-field mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="fx-register-password" class="form-control" autocomplete="new-password" required>
                    <div class="fx-pwd-strength" data-pwd-strength-for="fx-register-password" style="visibility: hidden;">
                        <div class="fx-pwd-strength-track"><div class="fx-pwd-strength-fill"></div></div>
                        <span class="fx-pwd-strength-label"></span>
                    </div>
                    <div class="form-text">At least 8 characters, with uppercase, lowercase, a number and a symbol.</div>
                </div>
                <div class="fx-field mb-3">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                </div>
                <button class="btn btn-gold w-100">Register</button>
            </form>
        </div>
    </div>
</div>

<script>
function fxToggleRoleFields() {
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
document.addEventListener('DOMContentLoaded', fxToggleRoleFields);
</script>
@endsection
