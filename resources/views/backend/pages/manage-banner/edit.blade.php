@extends('backend.layouts.master')
@section('title','Edit Banner')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Edit Banner</h4>
                    <a href="{{ route('manage-banner.index') }}" data-title="Back to Banner List"
                        data-bs-toggle="tooltip" title="Back to Banner List" class="btn btn-sm btn-info">
                        << Back to Banner List
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-banner.update',$banner->id) }}"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Banner Title</label>
                                    <input type="text" name="title" class="form-control"
                                    value="{{ old('title',$banner->title) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Banner Content</label>
                                    <textarea name="content"
                                    class="form-control">{{ old('content',$banner->content) }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Desktop Banner</label>
                                    <input type="file" name="desktop_image" class="form-control">
                                    @if($banner->image_path_desktop)
                                    <img src="{{ asset('storage/images/banner-desktop/' . $banner->image_path_desktop) }}" width="120">
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Mobile Banner</label>
                                    <input type="file" name="mobile_image" class="form-control">
                                    @if($banner->image_path_mobile)
                                    <img src="{{ asset('storage/images/banner-mobile/' . $banner->image_path_mobile) }}" width="120" class="img-thumbnail">
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="collection_link" class="form-label">Collection Link</label>
                                    <input type="text" 
                                        id="collection_link" 
                                        name="collection_link" 
                                        value="{{ old('collection_link',$banner->collection_link) }}"
                                        placeholder="https://example.com/collection"
                                        class="form-control @error('collection_link') is-invalid @enderror">
                                    @error('collection_link')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="buy_now_link" class="form-label">Buy Now Link</label>
                                    <input type="text" 
                                        id="buy_now_link" 
                                        name="buy_now_link" 
                                        placeholder="https://example.com/product"
                                        value="{{ old('buy_now_link',$banner->buy_now_link) }}"
                                        class="form-control @error('buy_now_link') is-invalid @enderror">
                                    @error('buy_now_link')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <!-- <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Select Products</label>
                                    <select name="products[]" class="form-control product-autocomplete" multiple>
                                        @foreach($banner->products as $product)
                                        <option value="{{ $product->id }}" selected>
                                            {{ $product->title }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div> -->
                            <div class="text-end">
                                <a href="{{ route('manage-banner.index')}}" class="btn btn-secondary">Cancel</a>
                                <button class="btn btn-primary">Update Banner</button>
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
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.product-autocomplete').select2({
            placeholder: "Search Product",
            minimumInputLength: 1,
            ajax: {
                url: "{{ route('product.autocomplete') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        selected_ids: $('.product-autocomplete').val() || []
                    };
                },
                processResults: function(data) {
                    return data;
                },
                cache: true
            }
        });
    });
</script>

@endpush