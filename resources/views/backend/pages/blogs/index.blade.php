@extends('backend.layouts.master')
@section('title','Manage Blog')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Manage Blog</h4>
                    <a href="{{ route('manage-blog.create') }}" data-title="Add New Blog" data-bs-toggle="tooltip"
                        title="Add New Blog" class="btn btn-sm btn-primary">
                        Add New Blog
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($blogs) && $blogs->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover table-centered">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Title</th>
                                    <th>Main Image</th>
                                    <th>Short Description</th>
                                    <th>Content</th>
                                    <th>More Images</th>
                                    <th>Paragraphs</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $sr_no = ($blogs->currentPage() - 1) * $blogs->perPage() + 1; @endphp
                                @foreach($blogs as $blog)
                                <tr>
                                    <td>{{ $sr_no }}</td>
                                    <td>
                                        {{ $blog->title }}
                                        @if($blog->meta_title)
                                        <br><small class="text-muted">Meta: {{ Str::limit($blog->meta_title, 30) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($blog->main_image)
                                        <img src="{{ asset('storage/images/blogs/main/' . $blog->main_image) }}"
                                            class="img-thumbnail"
                                            style="width:70px;height:70px;object-fit:cover;"
                                            alt="{{ $blog->title }}">
                                        @else
                                        <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ Str::limit($blog->short_desc, 50) }}
                                    </td>
                                    <td>
                                        {{ Str::limit(strip_tags($blog->content), 50) }}
                                    </td>
                                    <td>
                                        @if($blog->images && $blog->images->count() > 0)
                                            <span class="badge bg-info">{{ $blog->images->count() }} images</span>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @foreach($blog->images->take(2) as $image)
                                                <img src="{{ asset('storage/images/blogs/more/' . $image->image) }}"
                                                    class="img-thumbnail"
                                                    style="width:40px;height:40px;object-fit:cover;"
                                                    alt="{{ $image->alt_text ?? 'Blog image' }}">
                                                @endforeach
                                                @if($blog->images->count() > 2)
                                                <span class="badge bg-secondary">+{{ $blog->images->count() - 2 }} more</span>
                                                @endif
                                            </div>
                                        @else
                                        <span class="text-muted">No images</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($blog->paragraphs && $blog->paragraphs->count() > 0)
                                            <span class="badge bg-success">{{ $blog->paragraphs->count() }} paragraphs</span>
                                            <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#paragraphsModal{{ $blog->id }}">
                                                View
                                            </button>
                                            <div class="modal fade" id="paragraphsModal{{ $blog->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-xl">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Blog Paragraphs - {{ $blog->title }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="accordion" id="paragraphsAccordion{{ $blog->id }}">
                                                                @foreach($blog->paragraphs as $index => $paragraph)
                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header">
                                                                        <button class="accordion-button {{ $index == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $blog->id }}{{ $index }}">
                                                                            {{ $paragraph->title ?: 'Paragraph ' . ($index + 1) }}
                                                                        </button>
                                                                    </h2>
                                                                    <div id="collapse{{ $blog->id }}{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" data-bs-parent="#paragraphsAccordion{{ $blog->id }}">
                                                                        <div class="accordion-body">
                                                                            @if($paragraph->image)
                                                                            <img src="{{ asset('storage/images/blogs/paragraphs/' . $paragraph->image) }}" 
                                                                                class="img-fluid mb-2" style="max-height:200px;object-fit:cover;">
                                                                            @endif
                                                                            <div>{!! $paragraph->content !!}</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                        <span class="text-muted">No paragraphs</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($blog->status == 'published')
                                        <span class="badge bg-success">Published</span>
                                        @elseif($blog->status == 'draft')
                                        <span class="badge bg-warning">Draft</span>
                                        @else
                                        <span class="badge bg-secondary">{{ $blog->status }}</span>
                                        @endif
                                        <!-- @if($blog->published_at)
                                        <br><small class="text-muted">{{ date('d M Y', strtotime($blog->published_at)) }}</small>
                                        @endif -->
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('manage-blog.edit', $blog->id) }}"
                                                class="btn btn-soft-primary btn-sm"
                                                data-bs-toggle="tooltip"
                                                title="Edit Blog">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            
                                            <form method="POST"
                                                action="{{ route('manage-blog.destroy', $blog->id) }}"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    data-name="{{ $blog->title }}"
                                                    class="btn btn-soft-danger btn-sm show_confirm"
                                                    data-bs-toggle="tooltip"
                                                    title="Delete Blog">
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
                    @if($blogs instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="my-pagination mt-3" style="float:right;">
                        {{ $blogs->links('vendor.pagination.bootstrap-4') }}
                    </div>
                    @endif

                    @else
                    <div class="text-center py-4">
                        <img src="{{ asset('assets/images/no-data.svg') }}" alt="No data" style="width: 120px; opacity: 0.5;">
                        <p class="text-muted mt-2 mb-0">No Blogs Found</p>
                        <a href="{{ route('manage-blog.create') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="ti ti-plus"></i> Add New Blog
                        </a>
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