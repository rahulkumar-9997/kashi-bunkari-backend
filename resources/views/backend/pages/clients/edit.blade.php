@extends('backend.layouts.master')
@section('title','Edit Client')
@section('main-content')
@push('styles')
@endpush
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Edit Client</h4>
                    <a href="{{ route('manage-client.index') }}"
                        data-title="Back to Client List"
                        data-bs-toggle="tooltip"
                        title="Back to Client List"
                        class="btn btn-sm btn-info">
                        << Back to Client List
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-client.update', $client->id) }}" enctype="multipart/form-data" id="editClientForm">
                        @csrf
                        @method('PUT')                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Client Title</label>
                                    <input type="text" 
                                        id="title" 
                                        name="title" 
                                        value="{{ old('title', $client->title) }}"
                                        placeholder="Enter client title"
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
                                    <label for="client_image" class="form-label">Client Image</label>
                                    <input type="file"
                                        id="client_image"
                                        name="client_image"
                                        accept="image/jpeg,image/png,image/jpg,image/webp"
                                    class="form-control @error('client_image') is-invalid @enderror">  
                                    @error('client_image')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        Leave empty to keep current image. Allowed types: JPEG, PNG, JPG, WEBP (Max: 6MB)
                                    </small>
                                    @if($client->image)
                                        <div class="current-image-container">
                                            <img src="{{ asset('storage/images/clients/'.$client->image) }}" 
                                                alt="{{ $client->title }}"
                                                class="current-image img-thumbnail" width="120">
                                        </div>
                                    @endif
                                </div>
                            </div>                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" 
                                        id="sort_order" 
                                        name="sort_order" 
                                        value="{{ old('sort_order', $client->sort_order) }}"
                                        placeholder="Enter sort order"
                                        class="form-control @error('sort_order') is-invalid @enderror"
                                        min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" 
                                            type="checkbox" 
                                            id="status" 
                                            name="status" 
                                            value="1"
                                            {{ old('status', $client->status) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="status">Active</label>
                                    </div>
                                </div>
                            </div> 
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('manage-client.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Client
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