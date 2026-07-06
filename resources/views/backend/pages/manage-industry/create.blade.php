@extends('backend.layouts.master')
@section('title','Add Industry')
@section('main-content')
@push('styles')
<link href="{{asset('backend/assets/plugins/select2/select2.css')}}" rel="stylesheet" type="text/css" media="screen"/>
<link href="{{asset('backend/assets/plugins/multi-select/css/multi-select.css')}}" rel="stylesheet" type="text/css" media="screen"/> 
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Add Industry</h4>
                    <a href="{{ route('manage-industry.index') }}"
                        data-title="Back to Industry List"
                        data-bs-toggle="tooltip"
                        title="Back to Industry List"
                        class="btn btn-sm btn-info">
                        << Back to Industry List
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-industry.store') }}" enctype="multipart/form-data" id="industryAddForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Industry Category *</label>
                                    <select name="industry_category_id" class="form-control @error('industry_category_id') is-invalid @enderror">
                                        <option value="">Select Category</option>
                                        @foreach($industryCategory as $category)
                                            <option value="{{ $category->id }}" {{ old('industry_category_id') == $category->id ? 'selected' : '' }}>
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
                                    <input type="text" name="title" value="{{ old('title') }}"
                                        class="form-control @error('title') is-invalid @enderror">
                                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Industry Image *</label>
                                    <input type="file" name="industry_image" value="{{ old('title') }}"
                                        class="form-control @error('industry_image') is-invalid @enderror">
                                    @error('industry_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>                            
                        </div>  
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Short Description</label>
                                    <textarea name="short_description"
                                        class="form-control @error('short_description') is-invalid @enderror">{{ old('short_description') }}</textarea>
                                    @error('short_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Page Url *</label>
                                    <input type="text" name="page_url" value="{{ old('page_url') }}"
                                     class="form-control">
                                </div>
                            </div>
                        </div>                                           
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Meta Description</label>
                                    <textarea name="meta_description"
                                    class="form-control">{{ old('meta_description') }}</textarea>
                                </div>
                            </div> 
                        </div>
                        <div class="row">                            
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="status" value="1" checked>
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                            </div>                         
                        </div>   
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-2 mt-2">
                                    <label class="form-label">Long Description</label>
                                    <textarea class="ckeditorUpdate4" name="long_description">{{ old('long_description') }}</textarea>
                                    @error('long_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('manage-industry.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Submit</button>
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
<!-- <script>
    window.productAutocompleteUrl = "{{ route('autocomplete.products') }}";
</script> -->
<!-- <script src="{{asset('backend/assets/plugins/select2/select2.min.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/plugins/multi-select/js/jquery.multi-select.js')}}" type="text/javascript"></script> -->
<!-- <script type="text/javascript" src="{{asset('backend/assets/js/pages/product-industry.js')}}?v={{ env('ASSET_VERSION', '1.0.0') }}"></script> -->
<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script> -->
<script>
$(document).ready(function () {
    $('#industryAddForm').on('submit', function () {
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.text('Processing...');
    });
});
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