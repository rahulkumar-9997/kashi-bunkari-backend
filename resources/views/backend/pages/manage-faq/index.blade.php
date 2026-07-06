@extends('backend.layouts.master')
@section('title','Manage FAQ')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="card-title flex-grow-1">FAQ List</h4>
               <a href="{{ route('manage-faq.create') }}"                   
                  data-title="Add New FAQ" 
                  data-bs-toggle="tooltip" 
                  title="Add New FAQ" 
                  class="btn btn-sm btn-primary">
                  Add New FAQ
               </a>
               
            </div>
            <div class="card-body">
               @if (isset($faqs) && $faqs->count() > 0)
                  <div class="table-responsive1">
                     <table class="table">
                        <thead>
                           <tr>
                                 <th>Sr No</th>
                                 <th>Question</th>
                                 <th>Answer</th>
                                 <th>Image</th>
                                 <th>Status</th>
                                 <th>Action</th>
                           </tr>
                        </thead>
                        <tbody>
                           @php $sr = 1; @endphp
                           @foreach($faqs as $faq)
                           <tr>
                                 <td>{{ $sr++ }}</td>
                                 <td>{{ $faq->question }}</td>
                                 <td>{{ \Str::limit(strip_tags($faq->answer), 50) }}</td>
                                 
                                 <td>
                                    @if($faq->answer_image)
                                       <img src="{{ asset('storage/images/faq/'.$faq->answer_image) }}" width="70">
                                    @endif
                                 </td>

                                 <td>
                                    <span class="badge {{ $faq->status ? 'bg-success' : 'bg-danger' }}">
                                       {{ $faq->status ? 'Active' : 'Inactive' }}
                                    </span>
                                 </td>

                                 <td>
                                    <div class="d-flex gap-2">
                                       <a href="{{ route('manage-faq.edit',$faq->id) }}" class="btn btn-sm btn-primary" data-title="Edit Faq" data-bs-toggle="tooltip" title="Edit Faq">
                                          <i class="ti ti-pencil"></i>
                                       </a>

                                       <form action="{{ route('manage-faq.destroy',$faq->id) }}" method="POST" style="display:inline">
                                          @csrf
                                          @method('DELETE')
                                          <button class="btn btn-sm btn-danger show_confirm" data-title="Delete Faq" data-bs-toggle="tooltip" title="Delete Faq">
                                             <i class="ti ti-trash"></i>
                                          </button>
                                       </form>
                                    </div>
                                 </td>
                           </tr>
                           @endforeach
                        </tbody>
                     </table>
                  </div>
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