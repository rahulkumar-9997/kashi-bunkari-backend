@extends('backend.layouts.master')
@section('title','Manage Client')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Manage Client</h4>
                    <a href="{{ route('manage-client.create') }}" data-title="Add New Client" data-bs-toggle="tooltip"
                        title="Add New Client" class="btn btn-sm btn-primary">
                        Add New Client
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($clients) && $clients->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover table-centered">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Title</th>
                                    <th>Image</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $sr_no = 1; @endphp
                                @foreach($clients as $client)
                                <tr>
                                    <td>{{ $sr_no }}</td>
                                    <td>{{ $client->title }}</td>
                                    <td>
                                        <img src="{{ asset('storage/images/clients/'.$client->image) }}" class="img-thumbnail"
                                        style="width:70px;height:70px;" alt="{{ $client->title }}">
                                    </td>
                                    <td>
                                        @if($client->status)
                                        <span class="badge bg-success">Active</span>
                                        @else
                                        <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('manage-client.edit',$client->id) }}"
                                                class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip"
                                                title="Edit Client">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            <form method="POST"
                                                action="{{ route('manage-client.destroy',$client->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" data-name="{{ $client->title }}"
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
                    <div class="my-pagination mt-2" style="float: right;">
                        {{ $clients->links('vendor.pagination.bootstrap-4') }}
                    </div>
                    @else
                    <p class="text-center mb-0">No Clients Found</p>
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