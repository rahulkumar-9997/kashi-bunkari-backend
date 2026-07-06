@extends('backend.layouts.master')
@section('title','Change Password')
@section('main-content')
@push('styles')
<style>
    .password-strength {
        height: 5px;
        margin-top: 5px;
        transition: all 0.3s ease;
    }
    .strength-weak { width: 25%; background-color: #dc3545; }
    .strength-medium { width: 50%; background-color: #ffc107; }
    .strength-strong { width: 75%; background-color: #28a745; }
    .strength-very-strong { width: 100%; background-color: #20c997; }
</style>
@endpush

<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row justify-content-md-center">
        <div class="col-xl-6 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Change Password</h4>
                    <p class="text-muted mb-0">Ensure your account is using a strong password</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('password.update') }}" method="POST">
                        @csrf
                        
                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-lock"></i></span>
                                <input type="password" 
                                       class="form-control @error('current_password') is-invalid @enderror" 
                                       id="current_password" 
                                       name="current_password" 
                                       placeholder="Enter current password"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)">
                                    <i class="bx bx-show"></i>
                                </button>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-lock-open"></i></span>
                                <input type="password" 
                                       class="form-control @error('new_password') is-invalid @enderror" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Enter new password"
                                       onkeyup="checkPasswordStrength(this.value)"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)">
                                    <i class="bx bx-show"></i>
                                </button>
                                @error('new_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <!-- Password Strength Meter -->
                            <div class="password-strength mt-2 rounded" id="passwordStrength"></div>
                            <small class="text-muted">
                                Password must be at least 8 characters long and contain:
                                <ul class="mt-1">
                                    <li id="length-check" class="text-muted">✓ At least 8 characters</li>
                                    <li id="uppercase-check" class="text-muted">✓ One uppercase letter</li>
                                    <li id="lowercase-check" class="text-muted">✓ One lowercase letter</li>
                                    <li id="number-check" class="text-muted">✓ One number</li>
                                    <li id="special-check" class="text-muted">✓ One special character</li>
                                </ul>
                            </small>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-check-circle"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password_confirmation" 
                                       name="new_password_confirmation" 
                                       placeholder="Confirm new password"
                                       onkeyup="checkPasswordMatch(this.value)"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password_confirmation', this)">
                                    <i class="bx bx-show"></i>
                                </button>
                            </div>
                            <small id="password-match-message" class="text-muted"></small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('profile') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Tips Card -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bx bx-info-circle text-primary"></i> Password Tips</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bx bx-check text-success"></i> Use a mix of letters, numbers, and symbols</li>
                        <li class="mb-2"><i class="bx bx-check text-success"></i> Don't use personal information like your name or birthdate</li>
                        <li class="mb-2"><i class="bx bx-check text-success"></i> Use different passwords for different accounts</li>
                        <li><i class="bx bx-check text-success"></i> Change your password regularly</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bx bx-hide';
    } else {
        input.type = 'password';
        icon.className = 'bx bx-show';
    }
}
function checkPasswordStrength(password) {
    const strengthBar = document.getElementById('passwordStrength');
    const lengthCheck = document.getElementById('length-check');
    const uppercaseCheck = document.getElementById('uppercase-check');
    const lowercaseCheck = document.getElementById('lowercase-check');
    const numberCheck = document.getElementById('number-check');
    const specialCheck = document.getElementById('special-check');
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    lengthCheck.className = hasLength ? 'text-success' : 'text-muted';
    uppercaseCheck.className = hasUppercase ? 'text-success' : 'text-muted';
    lowercaseCheck.className = hasLowercase ? 'text-success' : 'text-muted';
    numberCheck.className = hasNumber ? 'text-success' : 'text-muted';
    specialCheck.className = hasSpecial ? 'text-success' : 'text-muted';
    let strength = 0;
    if (hasLength) strength++;
    if (hasUppercase) strength++;
    if (hasLowercase) strength++;
    if (hasNumber) strength++;
    if (hasSpecial) strength++;
    strengthBar.className = 'password-strength rounded';
    if (password.length === 0) {
        strengthBar.style.width = '0';
        strengthBar.className += ' bg-secondary';
    } else if (strength <= 2) {
        strengthBar.style.width = '25%';
        strengthBar.className += ' strength-weak';
    } else if (strength === 3) {
        strengthBar.style.width = '50%';
        strengthBar.className += ' strength-medium';
    } else if (strength === 4) {
        strengthBar.style.width = '75%';
        strengthBar.className += ' strength-strong';
    } else {
        strengthBar.style.width = '100%';
        strengthBar.className += ' strength-very-strong';
    }
}
function checkPasswordMatch(confirmPassword) {
    const password = document.getElementById('new_password').value;
    const message = document.getElementById('password-match-message');
    const submitBtn = document.getElementById('submitBtn');
    
    if (confirmPassword === '') {
        message.innerHTML = '';
        message.className = 'text-muted';
        submitBtn.disabled = false;
    } else if (password === confirmPassword) {
        message.innerHTML = '✓ Passwords match';
        message.className = 'text-success';
        submitBtn.disabled = false;
    } else {
        message.innerHTML = '✗ Passwords do not match';
        message.className = 'text-danger';
        submitBtn.disabled = true;
    }
}
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('new_password_confirmation').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});
</script>
@endpush