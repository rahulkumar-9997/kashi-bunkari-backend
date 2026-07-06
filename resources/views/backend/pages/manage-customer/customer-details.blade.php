@extends('backend.layouts.master')
@section('title', 'Customer Details - ' . $customer->name)
@section('main-content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Customer Details</h4>
        <a href="{{ route('manage-customer.index') }}" class="btn btn-soft-secondary btn-sm">
            <i class="ti ti-arrow-left"></i> Back to List
        </a>
    </div>
    <div class="row">
        <div class="col-lg-4">
            <div class="card overflow-hidden">
                <div class="card-body p-0">
                    <div class="bg-primary profile-bg rounded-top p-4 position-relative" style="height: 100px;">
                        <div class="position-absolute" style="bottom: -40px; left: 30px;">
                            @if(!empty($customer->profile_img))
                                <img src="{{ $customer->profile_img }}" 
                                     alt="{{ $customer->name }}" 
                                     class="avatar-lg border border-light border-3 rounded-circle bg-white"
                                     width="90" 
                                     height="90"
                                     style="object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                            @else
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($customer->name) }}&color=ffffff&background=0d6efd&size=90&bold=true&length=2" 
                                     alt="{{ $customer->name }}" 
                                     class="avatar-lg border border-light border-3 rounded-circle bg-white"
                                     width="90" 
                                     height="90"
                                     style="box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                            @endif
                        </div>
                    </div>
                    <div class="mt-5 p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-1 fw-bold">
                                    {{ $customer->name }}
                                    @if($customer->status == 1)
                                        <i class="ti ti-circle-check-filled text-success align-middle fs-5"></i>
                                    @endif
                                </h4>
                                <p class="text-muted mb-0">
                                    <i class="ti ti-id"></i> Customer ID: {{ $customer->customer_id ?? 'N/A' }}
                                </p>
                            </div>
                            <span class="badge {{ $customer->status == 1 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                {{ $customer->status == 1 ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="mt-3 pt-2 border-top">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti ti-mail text-primary fs-5"></i>
                                        <div>
                                            <small class="text-muted d-block">Email Address</small>
                                            <span class="fw-medium">{{ $customer->email ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti ti-phone text-primary fs-5"></i>
                                        <div>
                                            <small class="text-muted d-block">Phone Number</small>
                                            <span class="fw-medium">{{ $customer->phone_number ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                @if($customer->google_id)
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti ti-brand-google text-primary fs-5"></i>
                                        <div>
                                            <small class="text-muted d-block">Google Account</small>
                                            <span class="fw-medium text-truncate" style="max-width: 200px;">Connected</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light border-top p-3">
                    <div class="d-flex gap-2">
                        <a href="javascript:void(0)" class="btn btn-primary flex-fill" onclick="sendMessage()">
                            <i class="ti ti-message"></i> Message
                        </a>
                        <a href="{{ route('customer-orders', ['id' => $customer->id]) }}" class="btn btn-light flex-fill">
                            <i class="ti ti-shopping-cart"></i> Orders
                        </a>
                        <a href="javascript:void(0)" 
                           class="btn btn-soft-dark" 
                           data-customerid="{{ $customer->id }}"
                           data-editCustomer-popup="true"
                           data-title="Edit {{ $customer->name }}"
                           data-url="{{ route('manage-customer.edit', ['manage_customer' => $customer->id]) }}">
                            <i class="ti ti-edit"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-info-circle"></i> Additional Information
                    </h5>
                    <span class="badge bg-info-subtle text-info px-2 py-1">
                        <i class="ti ti-calendar"></i> 
                        Joined: {{ $customer->created_at->format('d M, Y') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-0 text-muted" width="40%">Account ID</td>
                                    <td class="fw-medium">#{{ $customer->customer_id ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-0 text-muted">Email Verified</td>
                                    <td>
                                        @if($customer->email_verified_at)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="ti ti-circle-check"></i> Verified
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="ti ti-alert-circle"></i> Unverified
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-0 text-muted">Last Login</td>
                                    <td>
                                        @if($customer->last_login_at)
                                            {{ \Carbon\Carbon::parse($customer->last_login_at)->format('d M Y H:i') }}
                                            <br>
                                            <small class="text-muted">
                                                ({{ \Carbon\Carbon::parse($customer->last_login_at)->diffForHumans() }})
                                            </small>
                                        @else
                                            Never
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-0 text-muted">Date of Birth</td>
                                    <td>{{ $customer->date_of_birth ? \Carbon\Carbon::parse($customer->date_of_birth)->format('d M Y') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-0 text-muted">Gender</td>
                                    <td>{{ ucfirst($customer->gender ?? 'N/A') }}</td>
                                </tr>
                                @if($defaultAddress)
                                <tr>
                                    <td class="ps-0 text-muted">Default Address</td>
                                    <td>
                                        {{ $defaultAddress->address }}, 
                                        @if($defaultAddress->apartment) {{ $defaultAddress->apartment }}, @endif
                                        {{ $defaultAddress->city }}, 
                                        {{ $defaultAddress->state }} - 
                                        {{ $defaultAddress->zip_code }}
                                        <br>
                                        <small class="text-muted">
                                            <i class="ti ti-phone"></i> {{ $defaultAddress->phone_number }}
                                        </small>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="row">
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Total Orders</p>
                                    <h3 class="mb-0 fw-bold">{{ $totalOrders }}</h3>
                                </div>
                                <div class="avatar-md bg-primary bg-opacity-10 rounded">
                                    <i class="ti ti-shopping-cart fs-32 text-primary-tw avatar-title"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('customer-orders', ['id' => $customer->id]) }}" class="text-primary text-decoration-none">
                                    View All Orders <i class="ti ti-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Total Spent</p>
                                    <h3 class="mb-0 fw-bold">₹{{ number_format($totalSpent ?? 0, 2) }}</h3>
                                </div>
                                <div class="avatar-md bg-success bg-opacity-10 rounded">
                                    <i class="ti ti-currency-rupee fs-32 text-success avatar-title"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-muted">
                                    <i class="ti ti-receipt"></i> {{ $totalInvoices ?? 0 }} Invoices
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Recent Orders (Last 5)</h5>
                    @if($orders->count() > 0)
                        <a href="{{ route('customer-orders', ['id' => $customer->id]) }}" class="btn btn-sm btn-soft-primary">
                            View All <i class="ti ti-arrow-right"></i>
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Items</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('order.details', $order->id) }}" class="fw-semibold text-primary">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="ti ti-calendar"></i> 
                                            {{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColor = match(strtolower($order->orderStatus->name ?? 'pending')) {
                                                'delivered', 'completed' => 'success',
                                                'processing', 'confirmed' => 'info',
                                                'cancelled' => 'danger',
                                                'refunded' => 'warning',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} px-3 py-2">
                                            {{ $order->orderStatus->name ?? 'Pending' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">₹{{ number_format($order->grand_total, 2) }}</span>
                                    </td>
                                    <td>
                                        @if($order->payment_received)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="ti ti-circle-check"></i> Paid
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="ti ti-clock"></i> Pending
                                            </span>
                                        @endif
                                        <br>
                                        <small class="text-muted">{{ $order->payment_mode }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">
                                            {{ $order->orderLines->count() }} items
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('order.details', $order->id) }}" class="btn btn-sm btn-soft-info" title="View Order">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-center">
                                            <i class="ti ti-shopping-cart-off" style="font-size: 48px; opacity: 0.3;"></i>
                                            <h6 class="mt-3">No orders found</h6>
                                            <p class="text-muted mb-0">This customer hasn't placed any orders yet.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @if($customer->wishlists_count > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-heart text-danger"></i> Wishlist ({{ $customer->wishlists_count }})
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($customer->wishlists()->with('product')->latest()->take(3)->get() as $wishlist)
                        <div class="col-md-4">
                            <div class="border rounded p-2">
                                <div class="d-flex align-items-center gap-2">
                                    @if($wishlist->product && $wishlist->product->images->first())
                                        <img src="{{ $wishlist->product->images->first()->image_url }}" 
                                             alt="{{ $wishlist->product->title }}" 
                                             width="40" height="40" 
                                             style="object-fit: cover;" 
                                             class="rounded">
                                    @else
                                        <div class="bg-light rounded" style="width: 40px; height: 40px;"></div>
                                    @endif
                                    <div class="small">
                                        <span class="d-block text-truncate" style="max-width: 120px;">
                                            {{ $wishlist->product->title ?? 'Product' }}
                                        </span>
                                        <span class="text-muted">₹{{ number_format($wishlist->product->price ?? 0, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($customer->wishlists_count > 3)
                    <div class="mt-2 text-center">
                        <a href="{{ route('customer-wishlist', ['id' => $customer->id]) }}" class="btn btn-sm btn-link">
                            View all {{ $customer->wishlists_count }} items
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Message to {{ $customer->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="messageForm">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendMessageSubmit()">Send Message</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function sendMessage() {
        $('#messageModal').modal('show');
    }
        function sendMessageSubmit() {
        var formData = $('#messageForm').serialize();
        alert('Message sent successfully!');
        $('#messageModal').modal('hide');
        $('#messageForm')[0].reset();
    }
    $(document).ready(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>
@endpush