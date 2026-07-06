@extends('backend.layouts.master')
@section('title','Edit Industry')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Edit Industry</h4>
                    <a href="{{ route('manage-industry.index') }}"
                        data-title="Back to Industry List"
                        data-bs-toggle="tooltip"
                        title="Back to Industry List"
                        class="btn btn-sm btn-info">
                        << Back to Industry List
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-industry.update', $industry->id) }}" 
                        enctype="multipart/form-data" id="industryEditForm">
                        @csrf
                        @method('PUT')                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Industry Category *</label>
                                    <select name="industry_category_id" class="form-control @error('industry_category_id') is-invalid @enderror">
                                        <option value="">Select Category</option>
                                        @foreach($industryCategory as $category)
                                            <option value="{{ $category->id }}" 
                                                {{ old('industry_category_id', $industry->industry_category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('industry_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Industry Title *</label>
                                    <input type="text" name="title" value="{{ old('title', $industry->title) }}"
                                        class="form-control @error('title') is-invalid @enderror">
                                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Industry Image</label>                                    
                                    <input type="file" name="industry_image" 
                                        class="form-control @error('industry_image') is-invalid @enderror">
                                    <small class="text-muted">Leave empty to keep current image. Allowed: jpg, jpeg, png, webp (Max: 4MB)</small>
                                    @if($industry->image_file)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/images/industry/' . $industry->image_file) }}" 
                                                class="img-fluid" style="max-height: 100px; object-fit: cover;">                                            
                                        </div>
                                    @endif
                                    @error('industry_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>                           
                            
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Short Description</label>
                                    <textarea name="short_description" rows="3"
                                        class="form-control @error('short_description') is-invalid @enderror">{{ old('short_description', $industry->short_description) }}</textarea>
                                    @error('short_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Page Url *</label>
                                    <input type="text" name="page_url" value="{{ old('page_url', $industry->page_url) }}"
                                        class="form-control @error('page_url') is-invalid @enderror">
                                    @error('page_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text" name="meta_title" value="{{ old('meta_title', $industry->meta_title) }}"
                                        class="form-control @error('meta_title') is-invalid @enderror">
                                    @error('meta_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Meta Description</label>
                                    <textarea name="meta_description" rows="3"
                                        class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $industry->meta_description) }}</textarea>
                                    @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="status" value="1" 
                                            {{ old('status', $industry->status) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-2 mt-2">
                                    <label class="form-label">Long Description</label>
                                    <textarea class="ckeditorUpdate4" name="long_description">{{ old('long_description', $industry->long_description) }}</textarea>
                                    @error('long_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <a href="{{ route('manage-industry.index') }}" class="btn btn-secondary">Cancel</a>
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
<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script> -->
<script>
$(document).ready(function () {
    $('#industryEditForm').on('submit', function () {
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.text('Processing...');
    });
});
// $(document).ready(function(){
//     $('.product-autocomplete').select2({
//         placeholder: "Search Product",
//         minimumInputLength: 1,
//         ajax: {
//             url: "{{ route('product.autocomplete') }}",
//             dataType: 'json',
//             delay: 250,
//             data: function(params){
//                 return {
//                     search: params.term,
//                     selected_ids: $('.product-autocomplete').val() || []
//                 };
//             },
//             processResults: function(data){
//                 return data;
//             },
//             cache: true
//         }
//     });
// });
</script>

<script src="{{ asset('backend/assets/ckeditor-4/ckeditor.js') }}?v={{ env('ASSET_VERSION', '1.0') }}"></script>
<script>
window.CKEDITOR_ROUTES = {
    upload: "{{ route('ckeditor.upload') }}",
    imagelist: "{{ route('ckeditor.images') }}",
    delete: "{{ route('ckeditor.delete') }}"
};
window.csrfToken = "{{ csrf_token() }}";
</script>
<script src="{{ asset('backend/assets/ckeditor-4/ckeditor-r-create-config.js') }}?v={{ env('ASSET_VERSION', '1.0') }}">
</script>
<script>
    document.querySelectorAll('.ckeditorUpdate4').forEach(function(el) {
        CKEDITOR.replace(el, {
            removePlugins: 'exportpdf'
        });
    });        
</script>
@endpush