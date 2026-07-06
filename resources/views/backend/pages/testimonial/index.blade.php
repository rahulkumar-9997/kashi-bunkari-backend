@extends('backend.layouts.master')
@section('title','Manage Testimonials')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Manage Testimonials</h4>
                    <a href="{{ route('manage-testimonials.create') }}" data-title="Add New Testimonial" data-bs-toggle="tooltip"
                        title="Add New Testimonial" class="btn btn-sm btn-primary">
                        Add New Testimonial
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($testimonials) && $testimonials->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover table-centered">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Name</th>
                                    <th>Image</th>
                                    <th>Content</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $sr_no = 1; @endphp
                                @foreach($testimonials as $testimonial)
                                <tr>
                                    <td>{{ $sr_no }}</td>
                                    <td>{{ $testimonial->name }}</td>
                                    <td>
                                        @if($testimonial->profile_img)
                                        <img src="{{ asset('storage/images/testimonials/'.$testimonial->profile_img) }}"
                                            class="img-thumbnail"
                                            style="width:70px;height:70px;object-fit:cover;"
                                            alt="{{ $testimonial->name }}">
                                        @else
                                        <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ \Illuminate\Support\Str::limit(strip_tags($testimonial->content), 100) }}
                                    </td>
                                    <td>
                                        @if($testimonial->status)
                                        <span class="badge bg-success">Active</span>
                                        @else
                                        <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('manage-testimonials.edit',$testimonial->id) }}"
                                                class="btn btn-soft-primary btn-sm"
                                                data-bs-toggle="tooltip"
                                                title="Edit Testimonial">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            <form method="POST"
                                                action="{{ route('manage-testimonials.destroy',$testimonial->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    data-name="{{ $testimonial->name }}"
                                                    class="btn btn-soft-danger btn-sm show_confirm">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                                @php $sr_no++; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="my-pagination mt-2" style="float:right;">
                        {{ $testimonials->links('vendor.pagination.bootstrap-4') }}
                    </div>
                    @else
                    <p class="text-center mb-0">No Testimonials Found</p>
                    @endif
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