@extends('backend.layouts.master')
@section('title','Create Tags')
@section('main-content')
@push('styles')   
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="card-title flex-grow-1">Create Tags</h4>
                <a href="{{ route('tags.index') }}"
                  data-bs-toggle="tooltip" 
                  title="Add Tags" 
                  class="btn btn-sm btn-pink">
                  Back to Tags List
                </a>               
            </div>
            <div class="card-body">
                <form method="POST"
                    action="{{ isset($tag) ? route('tags.update', $tag->id) : route('tags.store') }}"
                    enctype="multipart/form-data"
                    id="tagForm">
                    @if(isset($tag))
                        @csrf
                        @method('PUT')                    
                    @else
                        @csrf
                    @endif
                    <div class="row">
                        
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Tag Title *</label>
                                <input type="text" name="title"  value="{{ old('title', $tag->title ?? '') }}"
                                    class="form-control @error('title') is-invalid @enderror">
                                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Tag Image</label>
                                <input type="file" name="image"
                                    class="form-control @error('image') is-invalid @enderror">
                                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if(isset($tag) && $tag->image)
                                <img src="{{ asset('storage/images/tags/' . $tag->image) }}"
                                    width="120"
                                    class="img-thumbnail mb-2">
                            @endif
                        </div>                            
                    </div>  
                                                              
                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-2">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" value="{{ old('meta_title', $tag->meta_title ?? '') }}"
                                class="form-control">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-2">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description"
                                class="form-control">{{  old('meta_description', $tag->meta_description ?? '')  }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="status" value="1"  {{ old('status', $tag->status ?? 1) ? 'checked' : '' }}>
                                    <label class="form-check-label">Active</label>
                                </div>
                            </div>
                        </div>  
                    </div>
                       
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-2 mt-2">
                                <label class="form-label">Long Description</label>
                                <textarea class="ckeditorUpdate4" name="content">{{ old('content', $tag->content ?? '') }}</textarea>
                                @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <a href="{{ route('manage-industry.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                    </div>
                </form>
            </div>            
         </div>
      </div>
   </div>
</div>
<!-- End Container Fluid -->
<!-- Modal -->
@include('backend.layouts.common-modal-form')
<!-- modal--->
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
    $('#tagForm').on('submit', function (e) {
        var btn = $('#submitBtn');
        if (btn.prop('disabled')) {
            e.preventDefault();
            return false;
        }
        btn.prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    });    
</script>
@endpush