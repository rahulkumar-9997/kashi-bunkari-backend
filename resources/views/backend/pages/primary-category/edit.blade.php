@extends('backend.layouts.master')
@section('title','Edit Primary Category')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Edit Primary Category</h4>
                    <a href="{{ route('primary-category.index') }}" data-title="Back to Blog List" data-bs-toggle="tooltip"
                        title="Back to Blog List" class="btn btn-sm btn-info">
                        << Back to Primary Category List
                            </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('primary-category.update', $primaryCategory->id) }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="primaryCategoryEdit">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-sm-4 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="title">
                                        Title <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                        class="form-control @error('title') is-invalid @enderror"
                                        id="title"
                                        name="title"
                                        value="{{ old('title', $primaryCategory->title) }}"
                                        required />
                                    @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="short_heading_name">
                                            Short Heading Name
                                        </label>
                                        <textarea
                                            class="form-control @error('short_heading_name') is-invalid @enderror"
                                            id="short_heading_name" name="short_heading_name"
                                            rows="2">{{ old('short_heading_name', $primaryCategory->short_heading) }}</textarea>
                                        @error('short_heading_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>  

                            <div class="col-sm-4 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="page_url">
                                        Page URL <span class="text-danger">*</span>
                                    </label>
                                    <input type="url"
                                        class="form-control @error('page_url') is-invalid @enderror"
                                        id="page_url"
                                        name="page_url"
                                        value="{{ old('page_url', $primaryCategory->link) }}"
                                        required />
                                    @error('page_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-sm-6 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="slug">
                                        Slug (Auto-generated)
                                    </label>
                                    <input type="text"
                                        class="form-control"
                                        id="slug"
                                        value="{{ $primaryCategory->slug }}"
                                        readonly disabled />
                                    <small class="text-muted">Slug is automatically generated from the title</small>
                                </div>
                            </div>

                            <div class="col-sm-6 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="status">
                                        Status
                                    </label>
                                    <select class="form-select @error('status') is-invalid @enderror"
                                        id="status"
                                        name="status">
                                        <option value="1" {{ old('status', $primaryCategory->status) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('status', $primaryCategory->status) == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-sm-6 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="meta_title">Meta Title</label>
                                    <input type="text"
                                        class="form-control @error('meta_title') is-invalid @enderror"
                                        name="meta_title"
                                        id="meta_title"
                                        value="{{ old('meta_title', $primaryCategory->meta_title) }}" />
                                    <small class="text-muted">Recommended length: 50-60 characters</small>
                                    @error('meta_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-sm-6 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="meta_description">
                                        Meta Description
                                    </label>
                                    <textarea
                                        class="form-control @error('meta_description') is-invalid @enderror"
                                        id="meta_description"
                                        name="meta_description"
                                        rows="3">{{ old('meta_description', $primaryCategory->meta_description) }}</textarea>
                                    <small class="text-muted">Recommended length: 150-160 characters</small>
                                    @error('meta_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">Content <span class="text-danger">*</span></label>
                                    <textarea class="ckeditorUpdate4"
                                        name="content"
                                        id="content">{{ old('content', $primaryCategory->primary_category_description) }}</textarea>
                                    @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <a href="{{ route('primary-category.index') }}" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitButton">
                                        Update
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
</div>
@include('backend.layouts.common-modal-form')
@endsection
@push('scripts')
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
    $(document).ready(function() {
        $('#blogFormAdd').on('submit', function(e) {
            $('#submitButton').prop('disabled', true);
            $('#submitText').text('Submitting...');
            $(this).find('button[type="submit"]').prop('disabled', true);
        });
        @if($errors->any())
        $('#submitButton').prop('disabled', false);
        $('#submitText').text('Submit');
        @endif
    });
</script>

@endpush