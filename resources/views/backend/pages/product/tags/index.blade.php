@extends('backend.layouts.master')
@section('title','Manage Label')
@section('main-content')
@push('styles')   
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="card-title flex-grow-1">Tags List</h4>
                <a href="{{ route('tags.create') }}"
                  data-bs-toggle="tooltip" 
                  title="Add Tags" 
                  class="btn btn-sm btn-primary">
                  Add Tags
                </a>               
            </div>
            <div class="card-body">
                @include('backend.pages.product.tags.partials.tags-list', ['tags' => $tags])
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