@extends('backend.layouts.master')
@section('title','Manage Attributes')
@section('main-content')
@push('styles')
<link href="{{asset('backend/assets/plugins/select2/select2.css')}}" rel="stylesheet" type="text/css" media="screen"/>
<link href="{{asset('backend/assets/plugins/multi-select/css/multi-select.css')}}" rel="stylesheet" type="text/css" media="screen"/> 
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="card-title flex-grow-1">
                  Attributes Value List
                  <a href="{{route('attributes')}}" data-title="Go Back to Attributes" data-bs-toggle="tooltip" class="btn btn-sm btn-danger" data-bs-original-title="Go Back to Attributes">
                     << Go Back to Attributes
                        </a>
               </h4>

            </div>
            <div class="card-body">
               @if (isset($data['attributesvalue_list']) && $data['attributesvalue_list']->count() > 0)
               <div class="table-responsive1">
                  <table class="table align-middle mb-0 table-hover table-centered">
                     <thead class="bg-light-subtle">
                        <tr>
                           <th>Sr. No.</th>
                           <th>Name</th>
                           <th>Action</th>
                        </tr>
                     </thead>
                     <tbody>
                        @php
                        $sr_no = 1;
                        @endphp
                        @foreach($data['attributesvalue_list'] as $attributes_value)

                        <tr>
                           <td>
                              {{ $sr_no }}
                           </td>
                           <td>
                              {{ $attributes_value->name }}
                              @if($attributes_value->images)
                                 <br><img src="{{ asset('storage/images/attribute-values/thumb/' . $attributes_value->images) }}" class="img-thumbnail" style="height: 50px;" alt="{{ $attributes_value->name }}">
                              @endif
                           </td>
                           <td>
                              <div class="d-flex gap-2">
                                 <button class="btn btn-sm btn-primary mb1" data-url="{{ route('attributes-value-upload-img') }}" data-size="lg" data-attrivalid="{{ $attributes_value->id }}" data-title="Upload a image file of ({{ $attributes_value->name }})" data-bs-toggle="tooltip" title="Upload a image file" data-atvimg-popup="true">
                                    <i class="ti ti-file"></i>
                                </button>
                                
                                <button class="btn btn-sm btn-info editAttValue mb-1" 
                                data-url="{{ route('attributes-value.edit', $attributes_value->id) }}" data-size="md"
                                 data-title="Edit Attribute Option"
                                 data-bs-toggle="tooltip" title="Edit Attribute Option">
                                <i class="ti ti-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('attributes-value.delete', $attributes_value->id) }}" accept-charset="UTF-8" class="d-inline">
                                    @csrf
                                    <input name="_method" type="hidden" value="DELETE">
                                    <button type="button" class="btn btn-sm btn-danger show_confirm mb-1" data-bs-toggle="tooltip"  data-name="{{ $attributes_value->name }}" title="Delete">
                                    <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                              </div>
                           </td>
                        </tr>
                        @php
                        $sr_no++;
                        @endphp
                        @endforeach
                     </tbody>
                  </table>
               </div>
               <div class="my-pagination" id="pagination-links">
                  {{ $data['attributesvalue_list']->links('vendor.pagination.bootstrap-4') }}
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
<script src="{{asset('backend/assets/plugins/select2/select2.min.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/plugins/multi-select/js/jquery.multi-select.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/js/pages/attributesValueUpladImage.js')}}"></script> 
<script>
   $(document).ready(function() {
      $('.js-example-basic-multiple').select2();
   });
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