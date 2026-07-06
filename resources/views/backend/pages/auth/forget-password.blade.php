<!DOCTYPE html>
<html lang="en" class="h-100">
   <head>
      <meta charset="utf-8" />
      <title>{{ config('app.name') }} || Forgot Password</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta http-equiv="X-UA-Compatible" content="IE=edge" />
      <link rel="shortcut icon" href="{{asset('backend/assets/fav-icon.png')}}">
      <link href="{{asset('backend/assets/css/vendor.min.css')}}" rel="stylesheet" type="text/css" />
      <link href="{{asset('backend/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
      <link href="{{asset('backend/assets/css/app.min.css')}}" rel="stylesheet" type="text/css" />
      <script src="{{asset('backend/assets/js/config.js')}}"></script>
   </head>
   <body class="h-100">
      <div class="d-flex flex-column h-100 p-3">
         <div class="d-flex flex-column flex-grow-1">
            <div class="row h-100">
               <div class="col-xxl-12">
                  <div class="row justify-content-center h-100">
                    <div class="col-lg-4">
                        <div class="py-lg-2"  style="padding: 30px;border-radius: 10px; box-shadow: 0px 0px 10px 0px #87796f;">
                            @if($errors->any())
                                <br>
                                <div class="alert alert-danger">
                                    <p><strong>Opps Something went wrong</strong></p>
                                    <ul>
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <br>
                                <div class="alert alert-danger">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            @if(session()->has('success'))
                                <br>
                                <div class="alert alert-success">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            <div class="d-flex flex-column h-100 justify-content-center">
                                <div class="auth-logo mb-2 mt-2 text-center">
                                    <a href="{{ route('login') }}" class="logo-dark d-block">
                                        <img src="{{ asset('backend/assets/logo.png') }}"
                                            class="img-fluid w-100"
                                            style="max-width: 300px; margin: 0 auto;"
                                            alt="logo dark">
                                    </a>
                                </div>
                            <h2 class="fw-bold fs-24">Forgot Password</h2>
                            <div class="mb-2">
                                <form action="{{ route('forget.password.submit') }}" class="authentication-form" method="post">
                                    @csrf
                                    <div class="col-lg-12 mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" id="email" name="email" class="form-control bg-" placeholder="Enter your email">
                                    </div>
                                    <div class="col-lg-12 mb-3">
                                        <a href="{{route('login')}}" class="float-end text-muted text-unline-dashed mb-3">Go to Login</a>
                                    </div>
                                    
                                    <div class="col-lg-12 mb-3 text-center d-grid">
                                        <button class="btn btn-soft-primary" type="submit">Sign In</button>
                                    </div>
                                </form>
                            </div>
                            </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <script src="{{asset('backend/assets/js/vendor.js')}}"></script>
      <script src="{{asset('backend/assets/js/app.js')}}"></script>
   </body>
</html>