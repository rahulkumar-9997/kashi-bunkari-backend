@if (isset($data['customer_list']) && $data['customer_list']->count() > 0)
<table class="table align-middle mb-0 table-hover table-centered">
    <thead class="bg-light-subtle">
        <tr>
            <th style="width: 50px;">#</th>
            <th style="width: 50px;">Image</th> <!-- New column for image -->
            <th>Name & Joined</th>
            <th>Email & Phone</th>
            <th>Google Id</th>
            <th>Total Orders</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @php
        $sr_no = ($data['customer_list']->currentPage() - 1) * $data['customer_list']->perPage() + 1;
        $colors = ['790000', '0d6efd', '198754', '6f42c1', 'd63384', 'fd7e14', '20c997']; // Different colors for avatars
        @endphp
        @foreach($data['customer_list'] as $customer_list_row)
        @php
        $colorIndex = abs(crc32($customer_list_row->name)) % count($colors);
        $bgColor = $colors[$colorIndex];
        $textColor = 'ffffff';
        $nameParts = explode(' ', trim($customer_list_row->name));
        $initials = '';
        if (count($nameParts) >= 2) {
        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts)-1], 0, 1));
        } else {
        $initials = strtoupper(substr($customer_list_row->name, 0, 2));
        }
        @endphp
        <tr>
            <td>
                <span class="text-muted">{{ $sr_no }}</span>
            </td>
            <td style="text-align: center;">
            <div class="position-relative d-inline-block">
                @if($customer_list_row->profile_img)
                    <img src="{{ $customer_list_row->profile_img }}"
                        alt="{{ $customer_list_row->name }}"
                        class="rounded-circle border border-2 border-light"
                        width="55"
                        height="55"
                        style="object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($customer_list_row->name) }}&color=ffffff&background={{ $bgColor }}&size=55&bold=true&length=2"
                        alt="{{ $customer_list_row->name }}"
                        class="rounded-circle border border-2 border-light"
                        width="55"
                        height="55"
                        style="box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                @endif
                @php
                    $statusColor = 'secondary';
                    $statusTitle = 'Never logged in';                    
                    if ($customer_list_row->last_login_at) {
                        $lastLogin = \Carbon\Carbon::parse($customer_list_row->last_login_at);
                        $isOnline = $lastLogin->diffInMinutes(now()) < 60;
                        
                        if ($isOnline) {
                            $statusColor = 'success';
                            $statusTitle = 'Online now';
                        } else {
                            $statusColor = 'secondary';
                            $statusTitle = 'Last seen: ' . $lastLogin->diffForHumans();
                        }
                    }
                @endphp
                <span class="position-absolute rounded-circle bg-{{ $statusColor }} border border-2 border-white"
                    style="width: 16px; height: 16px; bottom: 2px; right: 2px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"
                    title="{{ $statusTitle }}"
                    data-bs-toggle="tooltip">
                </span>
                @if(isset($isOnline) && $isOnline)
                    <span class="position-absolute rounded-circle bg-success"
                        style="width: 16px; height: 16px; bottom: 2px; right: 2px; opacity: 0.6; animation: pulse 2s infinite;">
                    </span>
                @endif
            </div>
        </td>
            <td>
                <div>
                    <h6 class="mb-1 fw-semibold">{{ $customer_list_row->name }}</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-success small">
                            <i class="ti ti-calendar"></i> {{ $customer_list_row->created_at->format('d M Y') }}
                        </span>
                        @if($customer_list_row->status == 1)
                            <span class="badge bg-success-subtle text-success">Active</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">Inactive</span>
                        @endif
                    </div>
                </div>
            </td>
            <td>
                <div>
                    <i class="ti ti-mail text-muted"></i> {{ $customer_list_row->email ?? 'N/A' }}
                    <br>
                    <i class="ti ti-phone text-muted"></i> <strong>Phone:</strong> {{ $customer_list_row->phone_number ?? 'N/A' }}
                </div>
            </td>
            <td>
                @if($customer_list_row->google_id)
                <span class="badge bg-dark-subtle text-dark">
                    <i class="ti ti-brand-google"></i> Connected
                </span>
                @else
                <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                <span class="badge bg-info-subtle text-info fs-6 px-3 py-2">
                    <i class="ti ti-shopping-cart"></i> {{ $customer_list_row->orders_count ?? 0 }}
                </span>
            </td>
            <td>
                <div class="d-flex gap-1">
                    <a href="{{ route('customer-wishlist', ['id' => $customer_list_row->id]) }}"
                        class="btn btn-soft-warning btn-sm"
                        title="View Wishlist"
                        data-bs-toggle="tooltip">
                        <i class="ti ti-heart"></i>
                    </a>

                    <a href="{{ route('manage-customer.show', ['manage_customer' => $customer_list_row->id]) }}"
                        class="btn btn-soft-info btn-sm"
                        title="View Details"
                        data-bs-toggle="tooltip">
                        <i class="ti ti-eye"></i>
                    </a>
                    <a href="{{ route('customer-orders', ['id' => $customer_list_row->id]) }}"
                        class="btn btn-soft-success btn-sm"
                        title="View Orders"
                        data-bs-toggle="tooltip">
                        <i class="ti ti-shopping-cart"></i>
                    </a>
                    <a href="javascript:void(0);"
                        class="btn btn-soft-primary btn-sm"
                        data-customerid="{{$customer_list_row->id}}"
                        data-title="Edit {{ $customer_list_row->name }}"
                        data-editCustomer-popup="true"
                        data-size="lg"
                        title="Edit {{ $customer_list_row->name }}"
                        data-bs-toggle="tooltip"
                        data-url="{{ route('manage-customer.edit', ['manage_customer' => $customer_list_row->id]) }}">
                        <i class="ti ti-edit"></i>
                    </a>

                    <!-- Delete Form -->
                    <form method="POST" action="{{ route('manage-customer.destroy', ['manage_customer' => $customer_list_row->id]) }}"
                        style="display: inline;" class="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="button"
                            title="Delete {{ $customer_list_row->name }}"
                            data-name="{{ $customer_list_row->name }}"
                            class="show_confirm_customer btn btn-soft-danger btn-sm"
                            data-bs-toggle="tooltip">
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

<!-- Pagination -->
<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted">
        Showing {{ $data['customer_list']->firstItem() }} to {{ $data['customer_list']->lastItem() }}
        of {{ $data['customer_list']->total() }} entries
    </div>
    <div>
        {{ $data['customer_list']->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
@else
<div class="text-center py-5">
    <i class="ti ti-users-off" style="font-size: 48px; opacity: 0.5;"></i>
    <h5 class="mt-3">No Customers Found</h5>
    <p class="text-muted">There are no customers to display at the moment.</p>
</div>
@endif