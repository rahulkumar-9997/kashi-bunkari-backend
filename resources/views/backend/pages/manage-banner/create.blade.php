@extends('backend.layouts.master')
@section('title','Add Banner')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Add Banner</h4>
                    <a href="{{ route('manage-banner.index') }}"
                        data-title="Back to Banner List"
                        data-bs-toggle="tooltip"
                        title="Back to Banner List"
                        class="btn btn-sm btn-info">
                        << Back to Banner List
                    </a>

                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-banner.store') }}" enctype="multipart/form-data" id="addNewBanner">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Banner Title *</label>
                                    <input type="text" 
                                        id="title" 
                                        name="title" 
                                        value="{{ old('title') }}"
                                        class="form-control @error('title') is-invalid @enderror">
                                    @error('title')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="content" class="form-label">Banner Content</label>
                                    <textarea id="content"
                                            name="content"
                                            class="form-control @error('content') is-invalid @enderror">{{ old('content') }}</textarea>
                                    @error('content')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="desktop_image" class="form-label">Desktop Banner Image</label>
                                    <input type="file"
                                        id="desktop_image"
                                        name="desktop_image"
                                        class="form-control @error('desktop_image') is-invalid @enderror">
                                    @error('desktop_image')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mobile_image" class="form-label">Mobile Banner Image</label>
                                    <input type="file"
                                        id="mobile_image"
                                        name="mobile_image"
                                        class="form-control @error('mobile_image') is-invalid @enderror">
                                    @error('mobile_image')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="collection_link" class="form-label">Collection Link</label>
                                    <input type="text" 
                                        id="collection_link" 
                                        name="collection_link" 
                                        value="{{ old('collection_link') }}"
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
                                        value="{{ old('buy_now_link') }}"
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
                                    <label for="products" class="form-label">Select Products</label>
                                    <select name="products[]" 
                                            id="products" 
                                            class="form-control product-autocomplete @error('products') is-invalid @enderror" 
                                            multiple>
                                    </select>
                                    @error('products')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div> -->
                            <div class="text-end">
                                <a href="{{ route('manage-banner.index')}}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Banner</button>
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
$(document).ready(function(){
    $('.product-autocomplete').select2({
        placeholder: "Search Product",
        minimumInputLength: 1,
        ajax: {
            url: "{{ route('product.autocomplete') }}",
            dataType: 'json',
            delay: 250,
            data: function(params){
                return {
                    search: params.term,
                    selected_ids: $('.product-autocomplete').val() || []
                };
            },
            processResults: function(data){
                return data;
            },
            cache: true
        }
    });
});
</script>

@endpush