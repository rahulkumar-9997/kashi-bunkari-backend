@extends('backend.layouts.master')
@section('title','Manage Industry')
@section('main-content')
@push('styles')
<link href="{{asset('backend/assets/plugins/select2/select2.css')}}" rel="stylesheet" type="text/css" media="screen" />
<link href="{{asset('backend/assets/plugins/multi-select/css/multi-select.css')}}" rel="stylesheet" type="text/css" media="screen" />
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Industry List</h4>
                    <a href="{{ route('manage-industry.create') }}"
                        data-title="Add New Industry"
                        data-bs-toggle="tooltip"
                        title="Add New Industry"
                        class="btn btn-sm btn-primary">
                        Add New Industry
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($industries) && $industries->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover">
                            <tr>
                                <th>Sr. No.</th>
                                <th style="width:20%;">Category</th>
                                <th style="width:20%;">Title</th>
                                <th style="width:20%;">Image</th>
                                <th>Short Description</th>
                                <th>Page URL</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            @foreach($industries as $key => $industry)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    @if($industry->category)
                                        <span class="badge bg-info">{{ $industry->category->title }}</span>
                                    @else
                                        <span class="badge bg-secondary">No Category</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $industry->title }}</strong><br>
                                    <small class="text-muted">{{ $industry->slug }}</small>
                                </td>
                                <td>
                                    @if($industry->image_file)
                                        <div class="industry-image-wrapper" style="width: 60px; height: 60px; overflow: hidden; border-radius: 8px;">
                                            <img src="{{ asset('storage/images/industry/' . $industry->image_file) }}" 
                                                alt="{{ $industry->title }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center" 
                                            style="width: 60px; height: 60px; border-radius: 8px;">
                                            <i class="ti ti-building" style="font-size: 40px; color: #adb5bd;"></i>
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    {{ \Illuminate\Support\Str::limit(strip_tags($industry->short_description), 80) }}
                                </td>

                                <td>
                                    <div class="overflow-auto" style="max-width: 200px; max-height: 80px; overflow: auto; white-space: nowrap;">
                                        {{ $industry->page_url }}
                                    </div>
                                </td>
                                <td>
                                    @if($industry->status==1)
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('manage-industry.edit', $industry->id) }}"
                                            class="btn btn-soft-primary btn-sm">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('manage-industry.destroy', $industry->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                data-name="{{ $industry->title }}"
                                                class="btn btn-soft-danger btn-sm show_confirm">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                    @if($industries instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="my-pagination mt-2" style="float:right;">
                        {{ $industries->links('vendor.pagination.bootstrap-4') }}
                    </div>
                    @endif
                    @else
                    <div class="text-center py-4">
                        <p>No industries found</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@include('backend.layouts.common-modal-form')
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        $('.show_confirm').click(function(event) {
            var form = $(this).closest("form");
            var name = $(this).data("name");
            event.preventDefault();

            Swal.fire({
                title: `Are you sure you want to delete this ${name}?`,
                text: "If you delete this, it will be gone forever.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel",
                dangerMode: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush