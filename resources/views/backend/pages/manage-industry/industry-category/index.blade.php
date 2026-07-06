@extends('backend.layouts.master')
@section('title','Industry Category')
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
                    <h4 class="card-title flex-grow-1">Industry Category</h4>
                    <a href="{{ route('industry-category.create') }}"
                        data-title="Add New Industry Category"
                        data-bs-toggle="tooltip"
                        title="Add New Industry"
                        class="btn btn-sm btn-primary">
                        Add New Industry Category
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($industryCategory) && $industryCategory->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover">
                            <tr>
                                <th>Sr. No.</th>
                                <th style="width:20%;">Title</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            @foreach($industryCategory as $key => $industry_category)
                            <tr>
                                <td>{{ $key + 1 }}</td>

                                <td>
                                    <strong>{{ $industry_category->title }}</strong><br>
                                    <small class="text-muted">{{ $industry_category->slug }}</small>
                                </td>
                                <td>
                                    @if($industry_category->status==1)
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('industry-category.edit', $industry_category->id) }}"
                                            class="btn btn-soft-primary btn-sm">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('industry-category.destroy', $industry_category->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                data-name="{{ $industry_category->title }}"
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
                    @if($industryCategory instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="my-pagination mt-2" style="float:right;">
                        {{ $industryCategory->links('vendor.pagination.bootstrap-4') }}
                    </div>
                    @endif
                    @else
                    <div class="text-center py-4">
                        <p>No industries categories found</p>
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