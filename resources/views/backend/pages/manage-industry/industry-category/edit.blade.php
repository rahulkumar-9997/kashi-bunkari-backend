@extends('backend.layouts.master')
@section('title','Edit Industry Category')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Edit Industry Category</h4>
                    <a href="{{ route('industry-category.index') }}"
                        data-title="Back to Industry Category List"
                        data-bs-toggle="tooltip"
                        title="Back to Industry Category List"
                        class="btn btn-sm btn-info">
                        << Back to Industry Category List
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('industry-category.update', $industryCategory->id) }}" 
                          enctype="multipart/form-data" id="industryCategoryEditForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Industry Category Title *</label>
                                    <input type="text" name="title" value="{{ old('title', $industryCategory->title) }}"
                                        class="form-control @error('title') is-invalid @enderror">
                                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Slug</label>
                                    <input type="text" value="{{ $industryCategory->slug }}" 
                                        class="form-control" readonly disabled>
                                    <small class="text-muted">Slug is auto-generated from title</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="status" value="1" 
                                            {{ old('status', $industryCategory->status) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <a href="{{ route('industry-category.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update</button>
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