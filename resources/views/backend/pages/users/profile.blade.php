@extends('backend.layouts.master')
@section('title','User Details')
@section('main-content')
@push('styles')
 
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row justify-content-md-center">
        <div class="col-xl-9 col-lg-8">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="bg-primary profile-bg rounded-top position-relative mx-n3 mt-n3">
                        @if(Auth::user()->profile_img)
                            <img src="{{ asset('storage/images/user-profile/'.Auth::user()->profile_img) }}" alt="" class="avatar-xl border border-light border-3 rounded-circle position-absolute top-100 start-0 translate-middle ms-5">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF" alt="" class="avatar-xl border border-light border-3 rounded-circle position-absolute top-100 start-0 translate-middle ms-5">
                        @endif
                    </div>
                    <div class="mt-5">
                        <div>
                            <h4 class="mb-1">{{ auth()->user()->name ?? '' }}
                                 <i class="bx bxs-badge-check text-success align-middle"></i>
                            </h4>
                            <table class="table table-sm">
                                <tr>
                                    <th>User Role</th>
                                    <td>
                                        @if(Auth::user()->role)
                                            {{ Auth::user()->role }}
                                        @else
                                            <span class="text-muted">Not Assigned</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Last Login</th>
                                    <td>
                                        @if(Auth::user()->last_login_at)
                                            {{ \Carbon\Carbon::parse(Auth::user()->last_login_at)->timezone('Asia/Kolkata')->format('d-m-Y H:i:s') }}
                                        @else
                                            <span class="text-muted">First Login</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td>{{ Auth::user()->name }}</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>{{ Auth::user()->email }}</td>
                                </tr>
                                <tr>
                                    <th>Phone Number</th>
                                    <td>{{ Auth::user()->phone_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td>
                                        @if(Auth::user()->date_of_birth)
                                            {{ \Carbon\Carbon::parse(Auth::user()->date_of_birth)->format('d-m-Y') }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Gender</th>
                                    <td>{{ Auth::user()->gender ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Bio</th>
                                    <td>{{ Auth::user()->bio ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if(Auth::user()->status == 1)
                                            <span class="badge bg-success text-light px-2 py-1 fs-13">Active</span>
                                        @else
                                            <span class="badge bg-danger text-light px-2 py-1 fs-13">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Account Verified</th>
                                    <td>
                                        @if(Auth::user()->is_verified == 1)
                                            <span class="badge bg-success text-light px-2 py-1 fs-13">Verified</span>
                                        @else
                                            <span class="badge bg-warning text-dark px-2 py-1 fs-13">Not Verified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Last Login IP</th>
                                    <td>{{ Auth::user()->last_login_ip ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Login Attempts</th>
                                    <td>{{ Auth::user()->login_attempts ?? '0' }}</td>
                                </tr>
                                <tr>
                                    <th>Member Since</th>
                                    <td>{{ Auth::user()->created_at ? Auth::user()->created_at->format('d-m-Y') : 'N/A' }}</td>
                                </tr>
                            </table>
                            <div class="d-flex justify-content-end align-items-center gap-1">
                                <a href="{{ route('profile.edit', Auth::user()->id) }}" 
                                    data-title="Edit Profile" 
                                    data-bs-toggle="tooltip" 
                                    title="Edit Profile" 
                                    class="btn btn-sm btn-primary">
                                    Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Container Fluid -->
<!-- Modal -->
@include('backend.layouts.common-modal-form')
<!-- modal--->
@endsection
@push('scripts')

@endpush