@extends('backend.layouts.master')
@section('title','Add Testimonial')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Add Testimonial</h4>
                    <a href="{{ route('manage-testimonials.index') }}"
                        data-title="Back to Testimonials List"
                        data-bs-toggle="tooltip"
                        title="Back to Testimonials List"
                        class="btn btn-sm btn-info">
                        << Back to Testimonials List
                    </a>

                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-testimonials.store') }}" enctype="multipart/form-data" id="addNewTestimonials">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" 
                                        id="name" 
                                        name="name" 
                                        value="{{ old('name') }}"
                                        placeholder="Enter testimonial name"
                                        class="form-control @error('name') is-invalid @enderror">
                                    @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div> 
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text"
                                        id="city"
                                        name="city"
                                        value="{{ old('city') }}"
                                        placeholder="Enter city"
                                        class="form-control @error('city') is-invalid @enderror">

                                    @error('city')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>                           
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Profile Image </label>
                                    <input type="file"
                                        id="profile_image"
                                        name="profile_image"
                                        accept="image/jpeg,image/png,image/jpg,image/webp"
                                        class="form-control @error('profile_image') is-invalid @enderror">
                                    @error('profile_image')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        You can select profile images (JPEG, PNG, JPG, WEBP, Max: 6MB each)
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="content" class="form-label">Testimonials  Content *</label>
                                    <textarea type="text" name="content" id="content" class="form-control @error('content') is-invalid @enderror"></textarea>
                                    @error('content')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <select name="rating" id="rating" class="form-select @error('rating') is-invalid @enderror">
                                        @for($i = 5; $i >= 1; $i--)
                                            <option value="{{ $i }}" {{ old('rating',5) == $i ? 'selected' : '' }}>
                                                {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                            </option>
                                        @endfor
                                    </select>

                                    @error('rating')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="designation" class="form-label">Designation</label>
                                    <input type="text" 
                                        id="designation" 
                                        name="designation" 
                                        value="{{ old('designation') }}"
                                        placeholder="Enter testimonial designation"
                                        class="form-control @error('designation') is-invalid @enderror">
                                    @error('designation')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1" checked="">
                                        <label class="form-check-label" for="status">Active</label>
                                    </div>
                                </div>
                            </div>  
                            
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('manage-testimonials.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-save"></i>
                                        <span class="btn-text">Submit</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@include('backend.layouts.common-modal-form')
@endsection
@push('scripts')
<script>
$('#addNewTestimonials').on('submit', function () {
    $('#submitBtn')
        .prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
});
</script>
@endpush