@extends('backend.layouts.master')
@section('title','User Details Edit')
@section('main-content')
@push('styles')
<style>
    .profile-bg {
        height: 100px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .avatar-xl {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
</style>
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row justify-content-md-center">
        <div class="col-xl-9 col-lg-8">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="bg-primary profile-bg rounded-top position-relative mx-n3 mt-n3">
                        @if(Auth::user()->profile_img)
                            <img id="profileImagePreview" src="{{ asset('storage/images/user-profile/'.Auth::user()->profile_img) }}" alt="" class="avatar-xl border border-light border-3 rounded-circle position-absolute top-100 start-0 translate-middle ms-5">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF" alt="" class="avatar-xl border border-light border-3 rounded-circle position-absolute top-100 start-0 translate-middle ms-5">
                        @endif
                    </div>

                    <div class="mt-5">
                        <form action="{{ route('profile.update', Auth::user()->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('POST')
                            <h4 class="mb-2">{{ auth()->user()->name ?? '' }} <i class="bx bxs-badge-check text-success align-middle"></i></h4>
                            <div class="mb-3">
                                <label for="profile_img" class="form-label">Change Profile Image</label>
                                <input type="file" name="profile_img" class="form-control @error('profile_img') is-invalid @enderror" id="profile_img" accept="image/*" onchange="previewProfileImage(event)">
                                @error('profile_img')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Allowed: JPG, JPEG, PNG (Max: 2MB)</small>
                            </div>
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 150px;">User Role</th>
                                    <td>
                                        <input type="text" name="role" class="form-control" value="{{ Auth::user()->role ?? 'N/A' }}" readonly>
                                        <small class="text-muted">Role cannot be changed</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Last Login</th>
                                    <td>
                                        <input type="text" class="form-control" 
                                            value="{{ Auth::user()->last_login_at ? \Carbon\Carbon::parse(Auth::user()->last_login_at)->timezone('Asia/Kolkata')->format('d-m-Y H:i:s') : 'No login data' }}" 
                                            readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Name <span class="text-danger">*</span></th>
                                    <td>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                            value="{{ old('name', Auth::user()->name) }}" required>
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <th>Email <span class="text-danger">*</span></th>
                                    <td>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                            value="{{ old('email', Auth::user()->email) }}" readonly>
                                        <small class="text-muted">Email cannot be changed</small>
                                        @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <th>Phone Number</th>
                                    <td>
                                        <input type="text" name="phone_number" 
                                            class="form-control @error('phone_number') is-invalid @enderror" 
                                            value="{{ old('phone_number', Auth::user()->phone_number) }}" 
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                                            maxlength="10"
                                            placeholder="Enter 10 digit mobile number">
                                        @error('phone_number')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td>
                                        <input type="date" name="date_of_birth" 
                                            class="form-control @error('date_of_birth') is-invalid @enderror" 
                                            value="{{ old('date_of_birth', Auth::user()->date_of_birth ? \Carbon\Carbon::parse(Auth::user()->date_of_birth)->format('Y-m-d') : '') }}">
                                        @error('date_of_birth')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <th>Gender</th>
                                    <td>
                                        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ (old('gender', Auth::user()->gender) == 'Male') ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ (old('gender', Auth::user()->gender) == 'Female') ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ (old('gender', Auth::user()->gender) == 'Other') ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('gender')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <th>Bio</th>
                                    <td>
                                        <textarea name="bio" class="form-control @error('bio') is-invalid @enderror" 
                                            rows="3" placeholder="Tell something about yourself...">{{ old('bio', Auth::user()->bio) }}</textarea>
                                        @error('bio')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if(Auth::user()->status == 1)
                                            <span class="badge bg-success text-light px-2 py-1 fs-13">Active</span>
                                        @else
                                            <span class="badge bg-danger text-light px-2 py-1 fs-13">Inactive</span>
                                        @endif
                                        <input type="hidden" name="status" value="{{ Auth::user()->status }}">
                                        <small class="text-muted d-block">Status cannot be changed</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Last Login IP</th>
                                    <td>
                                        <input type="text" class="form-control" value="{{ Auth::user()->last_login_ip ?? 'N/A' }}" readonly>
                                    </td>
                                </tr>
                            </table>
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <a href="{{ route('profile') }}" class="btn btn-sm btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Container Fluid -->
@endsection
@push('scripts')
<script>
    function previewProfileImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('profileImagePreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            const forms = document.getElementsByClassName('needs-validation');
            Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>
@endpush