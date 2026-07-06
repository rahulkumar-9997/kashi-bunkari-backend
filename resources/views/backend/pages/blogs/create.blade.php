@extends('backend.layouts.master')
@section('title','Add Blog')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Add Blog</h4>
                    <a href="{{ route('manage-blog.index') }}" data-title="Back to Blog List" data-bs-toggle="tooltip"
                        title="Back to Blog List" class="btn btn-sm btn-info">
                        << Back to Blog List
                     </a> 
                    </div>
                    <div class="card-body">
                        <form action="{{ route('manage-blog.store') }}" method="POST" enctype="multipart/form-data" id="blogFormAdd">
                            @csrf
                            <div class="row">
                                <div class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="title">
                                            Title <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                                            id="banner_video_title" name="title" value="{{ old('title') }}" />
                                        @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="short_description">
                                            Short Description
                                        </label>
                                        <textarea
                                            class="form-control @error('short_description') is-invalid @enderror"
                                            id="short_description" name="short_description"
                                            rows="2">{{ old('short_description') }}</textarea>
                                        @error('short_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="main_image">
                                            Main Image <span class="text-danger">*</span>
                                        </label>
                                        <input type="file"
                                            class="form-control @error('main_image') is-invalid @enderror"
                                            name="main_image" id="main_image" value="{{ old('main_image') }}" />
                                        @error('main_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="meta_title">Meta title</label>
                                        <input type="text"
                                            class="form-control @error('meta_title') is-invalid @enderror"
                                            name="meta_title" id="meta_title" value="{{ old('meta_title') }}" />
                                        @error('meta_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="meta_description">
                                            Meta Description
                                        </label>
                                        <textarea
                                            class="form-control @error('meta_description') is-invalid @enderror"
                                            id="meta_description" name="meta_description"
                                            rows="2">{{ old('meta_description') }}</textarea>
                                        @error('meta_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="more_image">
                                            Blog more image (Select image multiple)
                                        </label>
                                        <input type="file"
                                            class="form-control @error('more_image') is-invalid @enderror"
                                            name="more_image[]" id="more_image" value="{{ old('more_image') }}"
                                            multiple />
                                        @error('more_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="summer-description-box mb-3">
                                        <label class="form-label">Content <span class="text-danger">*</span></label>
                                        <textarea class="ckeditorUpdate4" name="content"
                                            hidden>{{ old('content') }}</textarea>
                                        @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="summer-description-box mb-3">
                                        <div class="form-check form-check-lg d-flex align-items-center">
                                            <input class="form-check-input" type="checkbox" id="add_paragraphs"
                                                name="add_paragraphs" value="1"
                                                {{ old('add_paragraphs') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="checkebox-lg" style="margin-left: 10px;">
                                                Blog Paragraphs
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row sticky" id="blogParagraphsSection" style="display: none;">
                                <div class="col-md-12">
                                    <div class="bg-indigo pt-1 pb-1 rounded-2">
                                        <h4 class="text-center text-light" style="margin-bottom: 0px;">Blog
                                            Paragraphs</h4>
                                    </div>
                                    <table class="table align-middle mb-3">
                                        <tbody id="paragraphsContainer">
                                            <tr class="paragraph-row">
                                                <td style="width: 25%">
                                                    <input type="text" name="paragraphs_title[]"
                                                        class="form-control" placeholder="Enter Paragraphs Title">
                                                </td>
                                                <td style="width: 25%">
                                                    <input type="file" name="paragraphs_image[]"
                                                        class="form-control">
                                                </td>
                                                <td>
                                                    <textarea name="paragraphs_content[]" id="paragraph_0"
                                                        class="ckeditor4"></textarea>
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm remove-paragraph"
                                                        style="display: none;">Remove</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end">
                                                    <button class="btn btn-primary add-more-blog-paragraphs btn-sm"
                                                        type="button">Add More Blog Paragraphs</button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="d-flex align-items-center justify-content-end mb-4">
                                        <a href="{{ route('manage-blog.index') }}"
                                            class="btn btn-secondary me-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary" id="submitButton">
                                            <span id="submitText">Submit</span>
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
<script>
    $(document).ready(function() {
        $('#add_paragraphs').change(function() {
            if ($(this).is(':checked')) {
                $('#blogParagraphsSection').show();
            } else {
                $('#blogParagraphsSection').hide();
            }
        });
        if ($('#add_paragraphs').is(':checked')) {
            $('#blogParagraphsSection').show();
        }
        let paragraphIndex = 1;
        $('.add-more-blog-paragraphs').click(function() {
            var newRow = $('.paragraph-row:first').clone(false, false);
            newRow.find('.cke').remove();
            newRow.find('input[type="text"]').val('');
            newRow.find('input[type="file"]').val('');
            newRow.find('textarea').val('');
            var newId = 'paragraph_' + paragraphIndex++;
            var textarea = newRow.find('textarea');
            textarea.attr('id', newId);
            newRow.find('.remove-paragraph').show();
            $('#paragraphsContainer').append(newRow);
            CKEDITOR.replace(newId, {
                removePlugins: 'exportpdf'
            });
        });
        $(document).on('click', '.remove-paragraph', function() {
            if ($('.paragraph-row').length > 1) {
                $(this).closest('.paragraph-row').remove();
            }
        });
    });
</script>
@endpush