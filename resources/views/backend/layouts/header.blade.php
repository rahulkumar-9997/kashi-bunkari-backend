<header class="topbar">
   <div class="container-fluid">
      <div class="navbar-header">
         <div class="d-flex align-items-center">
            <div class="topbar-item">
               <button type="button" class="button-toggle-menu me-2">
                  <iconify-icon icon="solar:hamburger-menu-broken" class="fs-24 align-middle"></iconify-icon>
               </button>
            </div>
         </div>
         <div class="d-flex align-items-center gap-1">
            <a class="btn btn-outline-info" href="{{route('show.tables')}}">Database</a>
            <a class="btn btn-outline-purple" href="{{route('clear-cache')}}">Clear cache</a>

            <div class="dropdown topbar-item">
               <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="d-flex align-items-center">
                     @php
                     $user = Auth::user();
                     $path = 'images/user-profile/' . $user->profile_img;
                     @endphp

                     @if($user->profile_img && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
                     <img
                        src="{{ asset('storage/'.$path) }}"
                        alt="{{ $user->name }}"
                        class="rounded-circle"
                        width="32"
                        height="32"
                        style="object-fit: cover;">
                     @else
                     <img
                        src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=#ffff&background=#790000"
                        alt="{{ $user->name }}"
                        class="rounded-circle"
                        width="32"
                        height="32"
                        style="object-fit: cover;">
                     @endif

                  </span>
               </a>
               <div class="dropdown-menu dropdown-menu-end">
                  <!-- item-->
                  <h6 class="dropdown-header">
                     Welcome {{auth()->user()->name ?? ''}}!
                  </h6>
                  <a class="dropdown-item" href="{{route('profile')}}">
                     <i class="bx bx-user-circle text-muted fs-18 align-middle me-1"></i><span class="align-middle">Profile</span>
                  </a>
                  <a class="dropdown-item" href="{{route('password.change')}}">
                     <i class="bx bx-key text-muted fs-18 align-middle me-1"></i><span class="align-middle">Change Password</span>
                  </a>
                  <div class="dropdown-divider my-1"></div>
                  <a class="dropdown-item text-danger" href="{{route('logout')}}">
                     <i class="bx bx-log-out fs-18 align-middle me-1"></i><span class="align-middle">Logout</span>
                  </a>
               </div>
            </div>

         </div>
      </div>
   </div>
</header>
@push('scripts')
@endpush