@extends('backend.layouts.master')
@section('title', 'Edit Blog')
@section('main-content')
@push('styles')
<style>
    .existing-images-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    .image-item {
        position: relative;
        width: 100px;
        height: 100px;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
    }
    .image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .image-item .remove-image {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 25px;
        height: 25px;
        background: rgba(255,0,0,0.8);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: all 0.3s;
    }
    .image-item .remove-image:hover {
        background: red;
        transform: scale(1.1);
    }
    .bg-indigo {
        background-color: #4e73df;
    }
    .paragraph-row td {
        vertical-align: middle;
    }
    .sticky {
        position: relative;
    }
</style>
@endpush
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Edit Blog</h4>
                    <a href="{{ route('manage-blog.index') }}" data-title="Back to Blog List" data-bs-toggle="tooltip"
                        title="Back to Blog List" class="btn btn-sm btn-info">
                        << Back to Blog List
                    </a> 
                </div>
                <div class="card-body">
                    <form action="{{ route('manage-blog.update', $blog->id) }}" method="POST" enctype="multipart/form-data" id="blogFormEdit">
                        @csrf
                        @method('PUT')                        
                        <div class="row">
                            <div class="col-sm-4 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="title">
                                        Title <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $blog->title) }}" />
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
                                        class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description"
                                        rows="2">{{ old('short_description', $blog->short_desc) }}</textarea>
                                    @error('short_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>                            
                            <div class="col-sm-4 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="main_image">
                                        Main Image
                                    </label>
                                    <input type="file"
                                        class="form-control @error('main_image') is-invalid @enderror" name="main_image" id="main_image" />
                                    @error('main_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    
                                    @if($blog->main_image)
                                    <div class="mt-2" id="currentMainImageContainer">
                                        <div class="image-item" style="width: 150px; height: 150px;">
                                            <img src="{{ asset('storage/images/blogs/main/' . $blog->main_image) }}" alt="{{ $blog->title }}">
                                        </div>
                                        <input type="hidden" name="current_main_image" id="currentMainImage" value="{{ $blog->main_image }}">
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="meta_title">Meta Title</label>
                                    <input type="text"
                                        class="form-control @error('meta_title') is-invalid @enderror"
                                        name="meta_title" id="meta_title" value="{{ old('meta_title', $blog->meta_title) }}" />
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
                                        rows="2">{{ old('meta_description', $blog->meta_description) }}</textarea>
                                    @error('meta_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label" for="status">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="draft" {{ $blog->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="published" {{ $blog->status == 'published' ? 'selected' : '' }}>Published</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">                           
                            <div class="col-sm-12 col-12">
                                <div class="mb-3">
                                    <label class="form-label" for="more_image">
                                        Add More Images (Select multiple)
                                    </label>
                                    <input type="file"
                                        class="form-control @error('more_image') is-invalid @enderror"
                                        name="more_image[]" id="more_image" multiple />
                                    @error('more_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($blog->images && $blog->images->count() > 0)
                                    <div class="mt-2">
                                        <label class="form-label text-muted">Existing More Images:</label>
                                        <div class="existing-images-container" id="existingImagesContainer">
                                            @foreach($blog->images as $image)
                                            <div class="image-item" id="image-row-{{ $image->id }}">
                                                <img src="{{ asset('storage/images/blogs/more/' . $image->image) }}" alt="Blog Image">
                                                <button type="button" class="remove-image" onclick="markImageForDeletion({{ $image->id }})">×</button>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    <div id="deletedImagesContainer"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="summer-description-box mb-3">
                                    <label class="form-label">Content <span class="text-danger">*</span></label>
                                    <textarea class="ckeditorUpdate4" name="content" id="content">{{ old('content', $blog->content) }}</textarea>
                                    @error('content')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
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
                                            {{ old('add_paragraphs', $blog->paragraphs && $blog->paragraphs->count() > 0) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="add_paragraphs" style="margin-left: 10px;">
                                            Blog Paragraphs
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row sticky" id="blogParagraphsSection" style="{{ $blog->paragraphs && $blog->paragraphs->count() > 0 ? '' : 'display: none;' }}">
                            <div class="col-md-12">
                                <div class="bg-indigo pt-1 pb-1 rounded-2 mb-3">
                                    <h4 class="text-center text-light" style="margin-bottom: 0px;">Blog Paragraphs</h4>
                                </div>                                
                                <!-- Container for deleted paragraphs IDs -->
                                <div id="deletedParagraphsContainer"></div>                                
                                <table class="table align-middle mb-3">
                                    <thead>
                                        <tr>
                                            <th style="width: 20%">Title</th>
                                            <th style="width: 20%">Image</th>
                                            <th style="width: 50%">Content</th>
                                            <th style="width: 10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paragraphsContainer">
                                        @if($blog->paragraphs && $blog->paragraphs->count() > 0)
                                            @foreach($blog->paragraphs as $index => $paragraph)
                                            <tr class="paragraph-row" id="paragraph-row-{{ $paragraph->id }}">
                                                <td>
                                                    <input type="hidden" name="existing_paragraphs_id[]" value="{{ $paragraph->id }}">
                                                    <input type="text" name="paragraphs_title[]"
                                                        class="form-control" 
                                                        value="{{ old('paragraphs_title.'.$index, $paragraph->title) }}"
                                                        placeholder="Enter Paragraphs Title">
                                                </td>
                                                <td>
                                                    <div class="mb-3"> 
                                                        <input type="file" name="paragraphs_image[]" class="form-control" accept="image/*">
                                                    </div>
                                                    @if($paragraph->image)
                                                        <div class="mb-2">
                                                            <img src="{{ asset('storage/images/blogs/paragraphs/' . $paragraph->image) }}"
                                                                style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;"
                                                                class="border">
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <textarea name="paragraphs_content[]" 
                                                        id="paragraph_{{ $paragraph->id }}" 
                                                        class="ckeditor4">{{ old('paragraphs_content.'.$index, $paragraph->content) }}</textarea>
                                                </td>
                                                <td>
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm remove-paragraph"
                                                        onclick="markParagraphForDeletion({{ $paragraph->id }})">
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end">
                                                <button class="btn btn-primary add-more-blog-paragraphs btn-sm"
                                                    type="button">
                                                    <i class="ti ti-plus"></i> Add More Blog Paragraphs
                                                </button>
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
                                        <span id="submitText">Update Blog</span>
                                        <span id="submitSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
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
$(document).ready(function() {
    if ($('#content').length) {
        CKEDITOR.replace('content', {
            removePlugins: 'exportpdf'
        });
    }
    @if($blog->paragraphs && $blog->paragraphs->count() > 0)
        @foreach($blog->paragraphs as $paragraph)
            if ($('#paragraph_{{ $paragraph->id }}').length) {
                CKEDITOR.replace('paragraph_{{ $paragraph->id }}', {
                    removePlugins: 'exportpdf'
                });
            }
        @endforeach
    @endif
    $('#add_paragraphs').change(function() {
        if ($(this).is(':checked')) {
            $('#blogParagraphsSection').slideDown();
        } else {
            $('#blogParagraphsSection').slideUp();
        }
    });
    let paragraphIndex = {{ $blog->paragraphs ? $blog->paragraphs->count() + 1 : 1 }};
    $('.add-more-blog-paragraphs').click(function() {
        var newRow = `
            <tr class="paragraph-row">
                <td>
                    <input type="text" name="paragraphs_title[]" class="form-control" placeholder="Enter Paragraphs Title">
                </td>
                <td>
                    <div class="mb-3">
                        <input type="file" name="paragraphs_image[]" class="form-control" accept="image/*">
                    </div>
                </td>
                <td>
                    <textarea name="paragraphs_content[]" id="paragraph_${paragraphIndex}" class="ckeditor4"></textarea>
                </td>
                <td>
                    <button type="button" class="btn btn-warning btn-sm remove-paragraph-new">Remove</button>
                </td>
            </tr>
        `;
        
        $('#paragraphsContainer').append(newRow);
        CKEDITOR.replace('paragraph_' + paragraphIndex, {
            removePlugins: 'exportpdf'
        });
        
        paragraphIndex++;
    });
    $(document).on('click', '.remove-paragraph-new', function() {
        if ($('.paragraph-row').length > 1) {
            $(this).closest('.paragraph-row').remove();
        }
    });
    $('#blogFormEdit').on('submit', function(e) {
        for (var instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }
        $('#submitButton').prop('disabled', true);
        $('#submitText').text('Updating...');
        $('#submitSpinner').removeClass('d-none');
    });
    @if($errors->any())
        $('#submitButton').prop('disabled', false);
        $('#submitText').text('Update Blog');
        $('#submitSpinner').addClass('d-none');
    @endif
});

function markImageForDeletion(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        $('#deletedImagesContainer').append(`
            <input type="hidden" name="delete_images[]" value="${imageId}">
        `);
        $(`#image-row-${imageId}`).fadeOut();
    }
}
function markParagraphForDeletion(paragraphId) {
    if (confirm('Are you sure you want to delete this paragraph?')) {
        $('#deletedParagraphsContainer').append(`
            <input type="hidden" name="delete_paragraphs[]" value="${paragraphId}">
        `);
        $(`#paragraph-row-${paragraphId}`).fadeOut();
    }
}
</script>
@endpush