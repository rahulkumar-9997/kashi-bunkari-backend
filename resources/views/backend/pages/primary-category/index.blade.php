@extends('backend.layouts.master')
@section('title','Manage Primary Category')
@section('main-content')
@push('styles')

@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="card-title flex-grow-1">Primary Category</h4>
               <a href="{{ route('primary-category.create') }}"
                  data-bs-toggle="tooltip"
                  title="Add Primary Category"
                  class="btn btn-sm btn-primary">
                  Add Primary Category
               </a>

            </div>
            <div class="card-body">
               @if (isset($primaryCategory) && $primaryCategory->count() > 0)
               <div class="table-responsive1">
                  <table class="table align-middle mb-0 table-hover table-centered">
                     <thead class="bg-light-subtle">
                        <tr>
                           <th>Sr. No.</th>
                           <th>Name</th>
                           <th>Status</th>
                           <th>Url</th>
                           <th>Description</th>
                           <th>Action</th>
                        </tr>
                     </thead>
                     <tbody>
                        @php $sr_no = 1; @endphp
                        @foreach($primaryCategory as $primaryCategoryRow)
                        <tr>
                           <td>{{ $sr_no }}</td>
                           <td>
                              {{ $primaryCategoryRow->title }}
                              @if($primaryCategoryRow->additional_slug)
                                 <br>
                                 <div style="max-width:200px; overflow-x:auto; white-space:nowrap;">
                                    <span style="font-size:12px; color:#888;">
                                          {{ $primaryCategoryRow->additional_slug }}
                                    </span>
                                 </div>
                              @endif
                           </td>
                           <td>
                              <div class="form-check form-switch">
                                 <input class="form-check-input primaryCategoryStatus"
                                    data-pid="{{ $primaryCategoryRow->id }}"
                                    data-url="{{ route('primary-category.update-status', $primaryCategoryRow->id) }}"
                                    type="checkbox" role="switch"
                                    @if($primaryCategoryRow->status == 1) checked @endif>
                              </div>
                           </td>
                           <td>
                              <div style="max-width:300px; overflow-x:auto; white-space:nowrap;">
                                 <span style="font-size:16px; color:black;">
                                    {{ $primaryCategoryRow->link }}
                                 </span>
                              </div>
                           </td>
                           <td>
                              <div class="overflow-auto" style="max-width: 250px; max-height: 100px; overflow: auto;">
                                 {!! Str::limit($primaryCategoryRow->primary_category_description, 100) !!}
                              </div>
                           </td>
                           <td>
                              <div class="d-flex gap-2">
                                 <a href="{{ route('primary-category.edit', $primaryCategoryRow->id) }}"
                                    class="btn btn-soft-primary btn-sm"
                                    data-title="Edit Primary Category"
                                    data-bs-toggle="tooltip"
                                    data-url="">
                                    <i class="ti ti-pencil"></i>
                                 </a>
                                 <form method="POST" action="{{ route('primary-category.destroy', $primaryCategoryRow->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-name="{{ $primaryCategoryRow->title }}" class="btn btn-soft-danger btn-sm show_confirm">
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
                @if($primaryCategory instanceof \Illuminate\Pagination\LengthAwarePaginator)
                  <div class="my-pagination mt-3" style="float:right;">
                     {{ $primaryCategory->links('vendor.pagination.bootstrap-4') }}
                  </div>
                  @endif
               @else
               <div class="text-center">
                  <p>No primary categories found.</p>
               </div>
               @endif

            </div>
         </div>
      </div>
   </div>
</div>
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

      $(document).on('change', '.primaryCategoryStatus', function() {
         var $checkbox = $(this);
         var primaryCategoryId = $checkbox.data('pid');
         var updateUrl = $checkbox.data('url');
         var isActive = $checkbox.is(':checked') ? 1 : 0;
         $('#loader').fadeIn();
         $checkbox.prop('disabled', true);
         $.ajax({
            url: updateUrl,
            method: 'put',
            data: {
               status: isActive,
               _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success: function(response) {
               if (response.status === 'success') {
                  Toastify({
                     text: response.message,
                     duration: 10000,
                     gravity: "top",
                     position: "right",
                     className: "bg-success",
                     close: true,
                  }).showToast();
               }
            },
            error: function(xhr, status, error) {
               $checkbox.prop('checked', !isActive);
               Toastify({
                  text: 'Failed to update status. Please try again.',
                  duration: 10000,
                  gravity: "top",
                  position: "right",
                  className: "bg-danger",
                  close: true,
               }).showToast();
            },
            complete: function() {
               $('#loader').fadeOut();
               $checkbox.prop('disabled', false);
            }
         });
      });

   });
</script>
@endpush