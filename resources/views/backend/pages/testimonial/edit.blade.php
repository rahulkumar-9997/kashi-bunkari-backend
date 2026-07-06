@extends('backend.layouts.master')
@section('title','Edit Testimonial')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Edit Testimonial</h4>
                    <a href="{{ route('manage-testimonials.index') }}"
                        data-title="Back to Testimonials List"
                        data-bs-toggle="tooltip"
                        title="Back to Testimonials List"
                        class="btn btn-sm btn-info">
                        << Back to Testimonials List
                    </a>

                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-testimonials.update',$testimonial->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name *</label>
                                    <input type="text" name="name" value="{{ old('name',$testimonial->name) }}" class="form-control @error('name') is-invalid @enderror">
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Profile Image</label>
                                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-control @error('profile_image') is-invalid @enderror">
                                    @error('profile_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($testimonial->profile_img)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/images/testimonials/'.$testimonial->profile_img) }}" width="80" class="img-thumbnail">
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Designation</label>
                                    <input type="text" name="designation" value="{{ old('designation',$testimonial->designation) }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="status" value="1" {{ $testimonial->status ? 'checked' : '' }}>
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Content *</label>
                                    <textarea name="content" class="form-control" rows="4">{{ old('content',$testimonial->content) }}
                                    </textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('manage-testimonials.index') }}"
                                        class="btn btn-secondary">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        Update Testimonial
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

@endpush