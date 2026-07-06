@extends('backend.layouts.master')
@section('title','Add Client')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Add Client</h4>
                    <a href="{{ route('manage-client.index') }}"
                        data-title="Back to Banner List"
                        data-bs-toggle="tooltip"
                        title="Back to Banner List"
                        class="btn btn-sm btn-info">
                        << Back to Client List
                    </a>

                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-client.store') }}" enctype="multipart/form-data" id="addNewClient">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Client Title</label>
                                    <input type="text" 
                                        id="title" 
                                        name="title" 
                                        value="{{ old('title') }}"
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
                                    <label for="client_image" class="form-label">Client Image <span class="text-danger">*</span></label>
                                    <input type="file"
                                        id="client_image"
                                        name="client_image[]"
                                        multiple
                                        accept="image/jpeg,image/png,image/jpg,image/webp"
                                        class="form-control @error('client_image') is-invalid @enderror @error('client_image.*') is-invalid @enderror">
                                    
                                    {{-- Display validation errors for individual images --}}
                                    @error('client_image')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    
                                    @error('client_image.*')
                                        <div class="invalid-feedback">
                                            @foreach($errors->get('client_image.*') as $messages)
                                                @foreach($messages as $message)
                                                    {{ $message }}<br>
                                                @endforeach
                                            @endforeach
                                        </div>
                                    @enderror
                                    
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        You can select multiple images (JPEG, PNG, JPG, WEBP, Max: 6MB each)
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('manage-client.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Client
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