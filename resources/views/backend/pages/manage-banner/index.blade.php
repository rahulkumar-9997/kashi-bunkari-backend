@extends('backend.layouts.master')
@section('title','Manage Banner')
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
               <h4 class="card-title flex-grow-1">Banner List</h4>
               <a href="{{ route('manage-banner.create') }}"                   
                  data-title="Add New Banner" 
                  data-bs-toggle="tooltip" 
                  title="Add New Banner" 
                  class="btn btn-sm btn-primary">
                  Add New Banner
               </a>
               
            </div>
            <div class="card-body">
               @if (isset($banners) && $banners->count() > 0)
                  <div class="table-responsive1">
                     <table class="table align-middle mb-0 table-hover table-centered">
                        <thead class="bg-light-subtle">
                              <tr>
                                 <th>Sr. No.</th>
                                 <th>Name</th>
                                 <th>Content</th>
                                 <!-- <th>Product</th> -->
                                 <th>Banner IMG Desktop</th>
                                 <th>Banner IMG Mobile</th>
                                 <th>Action</th>
                              </tr>
                        </thead>
                        <tbody>
                              @php
                                 $sr_no = 1;
                              @endphp
                              @foreach($banners as $banner_row)
                                 <tr>
                                    <td>{{ $sr_no }}</td>
                                    <td>
                                       {{ $banner_row->title }}
                                       @if($banner_row->collection_link)
                                          <br><a href="{{ $banner_row->collection_link }}" target="_blank" class="ms-2" data-title="Collection Link" data-bs-toggle="tooltip">
                                             <i class="ti ti-link fs-20"></i>
                                          </a>
                                       @endif
                                       @if($banner_row->buy_now_link)
                                          <a href="{{ $banner_row->buy_now_link }}" target="_blank" class="ms-2" data-title="Buy Now Link" data-bs-toggle="tooltip">
                                             <i class="ti ti-shopping-cart fs-20"></i>
                                          </a>
                                       @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit(strip_tags($banner_row->content), 100) }}</td>
                                    <!-- <td>
                                       @foreach($banner_row->products as $product)
                                          <span class="badge bg-info">
                                             {{ $product->title }}
                                          </span>
                                       @endforeach
                                    </td> -->
                                    <td>
                                        <img src="
                                        {{ asset('storage/images/banner-desktop/' . $banner_row->image_path_desktop) }}"
                                       class="img-thumbnail" style="width: 70px; height: 70px;" alt="{{ $banner_row->title }}">
                                    </td>
                                    <td>
                                        <img src="
                                        {{ asset('storage/images/banner-mobile/' . $banner_row->image_path_mobile) }}"
                                       class="img-thumbnail" style="width: 70px; height: 70px;" alt="{{ $banner_row->title }}">
                                    </td>
                                    <td>
                                          <div class="d-flex gap-2">
                                             <a href="{{ route('manage-banner.edit', $banner_row->id) }}" class="btn btn-soft-primary btn-sm" data-title="Edit Blog Category" data-bs-toggle="tooltip">
                                                <i class="ti ti-pencil"></i>
                                             </a>
                                             <form method="POST" action="{{ route('manage-banner.destroy', $banner_row->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" data-name="{{ $banner_row->title }}" class="btn btn-soft-danger btn-sm show_confirm"><i class="ti ti-trash"></i></button>
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