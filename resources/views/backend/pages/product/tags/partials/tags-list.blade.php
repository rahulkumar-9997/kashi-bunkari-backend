@if($tags->count())
<div class="table-responsive">
    <table class="table align-middle mb-0 table-hover table-centered">
        <thead class="bg-light-subtle">
            <tr>
                <th>Sr. No.</th>
                <th>Image</th>
                <th>Title</th>
                <th>Slug</th>
                <th>Status</th>
                <th width="120">Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach($tags as $tag)
            <tr>
                <td>{{ $loop->iteration }}</td>

                <td>
                    @if($tag->image)
                    <img src="{{ asset('storage/images/tags/' . $tag->image) }}"
                        alt="{{ $tag->title }}"
                        class="img-thumbnail"
                        width="60">
                    @else
                    <span class="text-muted">N/A</span>
                    @endif
                </td>
                <td>{{ $tag->title }}</td>
                <td>{{ $tag->slug }}</td>
                <td>
                    <div class="form-check form-switch">
                        <input
                        class="form-check-input tag-status"
                        type="checkbox"
                        data-id="{{ $tag->id }}"
                        {{ $tag->status ? 'checked' : '' }}>
                    </div>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('tags.edit', $tag->id) }}"
                            class="btn btn-soft-primary btn-sm"
                            data-bs-toggle="tooltip"
                            title="Edit Tag">
                            <iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon>
                        </a>
                        <form action="{{ route('tags.destroy', $tag->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="btn btn-soft-danger btn-sm show_confirm"
                                data-name="{{ $tag->title }}">
                                <iconify-icon icon="solar:trash-bin-minimalistic-2-broken"
                                class="align-middle fs-18"></iconify-icon>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-center py-5">
    <h5>No Tags Found.</h5>
</div>
@endif