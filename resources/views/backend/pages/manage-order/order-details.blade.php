@extends('backend.layouts.master')
@section('title','Order details')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container -->
<div class="container-xxl">
    <div class="row">
        <div class="col-xl-8 col-lg-8">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <h4 class="fw-medium text-dark d-flex align-items-center gap-2">
                                        {{ $order->order_number }}
                                        @if($order->payment_received == 1)
                                        <span class="badge bg-success-subtle text-success  px-2 py-1 fs-13">
                                            Paid
                                        </span>
                                        @else
                                        <span class="badge bg-danger-subtle text-danger  px-2 py-1 fs-13">
                                            Unpaid
                                        </span>
                                        @endif

                                        <span class="border border-warning text-warning fs-13 px-2 py-1 rounded">
                                            {{ $order->orderStatus->name }}
                                        </span>
                                    </h4>
                                    <p class="mb-0">{{ $order->order_id }} -  {{ \Carbon\Carbon::parse($order->order_date)->format('d M Y, h:i:s A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Product</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 table-hover table-centered">
                                    <thead class="bg-light-subtle border-bottom">
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($order->orderLine->isNotEmpty())
                                            @php
                                                $itemsSubTotal = $order->orderLine->sum(function ($line) {
                                                    return $line->quantity * $line->price;
                                                });
                                                $shippingCharge = $order->shiprocketCourier->courier_shipping_rate ?? 0;
                                                $discountAmount = $order->coupon_discount_amount ?? 0;
                                                $finalPayable = ($itemsSubTotal - $discountAmount) + $shippingCharge;
                                            @endphp
                                            @foreach($order->orderLine as $line)
                                            @php
                                                $attributes_value ='na';
                                                if($line->product->ProductAttributesValues->isNotEmpty()){
                                                    $attributes_value = $line->product->ProductAttributesValues->first()->attributeValue->slug;
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a href="{{ url('products/'.$line->product->slug.'/'.$attributes_value) }}" target="_blank" class="text-dark fw-medium">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="rounded bg-light avatar-md d-flex align-items-center justify-content-center">
                                                                @if($line->product->images->first())
                                                                <img src="{{ asset('storage/images/product/thumb/' . $line->product->images->first()->image_path) }}"
                                                                class="img-thumbnail"
                                                                style="width: 70px; height: 70px;" 
                                                                alt="{{ $line->product->title }}">
                                                                @else
                                                                <img src="{{ asset('images/default.png') }}" class="avatar-md" alt="Default Image">
                                                                @endif

                                                            </div>
                                                            <div>
                                                                <span class="text-dark fw-medium fs-16">
                                                                    {{ ucwords(strtolower($line->product->title)) }}
                                                                </span>
                                                                @if($line->product->length && $line->product->breadth &&
                                                                $line->product->height &&
                                                                $line->product->weight)
                                                                    <ul>
                                                                        <li>
                                                                            <strong>
                                                                                Length in CM :
                                                                            </strong>
                                                                            {{ $line->product->length }}
                                                                        </li>
                                                                        <li>
                                                                            <strong>
                                                                                Breadth in CM:
                                                                            </strong>
                                                                            {{ $line->product->breadth }}
                                                                        </li>
                                                                        <li>
                                                                            <strong>
                                                                                Height in CM:
                                                                            </strong>
                                                                            {{ $line->product->height }}
                                                                        </li>
                                                                        <li>
                                                                            <strong>
                                                                                Weight in Kg :
                                                                            </strong>
                                                                            {{ $line->product->weight }}
                                                                        </li>
                                                                        <li>
                                                                            <strong>
                                                                                Volumetric Weight Kg :
                                                                            </strong>
                                                                            {{ $line->product->volumetric_weight_kg }}
                                                                        </li>
                                                                    </ul>

                                                                @endif


                                                            </div>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td>{{ $line->quantity }}</td>
                                                <td>Rs. {{ number_format($line->price, 2) }}</td>
                                                <td>
                                                    Rs. {{ number_format($line->quantity * $line->price, 2) }}
                                                </td>
                                            </tr>
                                            @endforeach
                                            <tr class="bg-light">
                                                <td colspan="3" class="text-end fw-bold">
                                                    Items Sub Total
                                                </td>
                                                <td class="fw-bold">
                                                    Rs. {{ number_format($itemsSubTotal, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">
                                                    Discount
                                                    @if($order->coupon_code)
                                                        <br>
                                                        <small class="text-info">
                                                            Coupon : {{ $order->coupon_code }}
                                                        </small>
                                                    @endif
                                                    
                                                </td>
                                                <td class="fw-bold text-danger">
                                                    - Rs. {{ number_format($discountAmount, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">
                                                    Shipping Charges
                                                    @if($order->shiprocketCourier)
                                                        <br>
                                                        <small class="text-info">
                                                            {{ $order->shiprocketCourier->courier_name }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td class="fw-bold">
                                                    Rs. {{ number_format($shippingCharge, 2) }}
                                                </td>
                                            </tr>
                                            <tr class="table-active">
                                                <td colspan="3" class="text-end fw-bold fs-16">
                                                    Total Payable
                                                </td>
                                                <td class="fw-bold fs-16">
                                                    Rs. {{ number_format($finalPayable, 2) }}
                                                </td>
                                            </tr>
                                        @else
                                        <tr>
                                            <td colspan="6" class="text-center">No order items found</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Order Summary</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <tbody>
                                <tr>
                                    <td class="px-0">
                                        <p class="d-flex mb-0 align-items-center gap-1">
                                            <iconify-icon icon="solar:clipboard-text-broken"></iconify-icon>
                                            Sub Total :
                                        </p>
                                    </td>
                                    <td class="text-end text-dark fw-medium px-0">
                                        Rs. {{
                                            number_format(
                                                $order->orderLine->sum(function ($line) {
                                                    return $line->quantity * $line->price;
                                                }),
                                            2)
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0">
                                        <p class="d-flex mb-0 align-items-center gap-1">
                                            <iconify-icon icon="solar:ticket-broken" class="align-middle"></iconify-icon> 
                                            Discount :
                                        </p>

                                        @if(!empty($order->coupon_code))
                                            <small class="text-success">
                                                Coupon : {{ $order->coupon_code }}
                                            </small>
                                        @endif
                                    </td>

                                    <td class="text-end text-dark fw-medium px-0">
                                        @if(!empty($order->coupon_discount_amount) && $order->coupon_discount_amount > 0)
                                            - Rs. {{ number_format($order->coupon_discount_amount, 2) }}
                                        @else
                                            Rs. 0.00
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0">
                                        <p class="d-flex mb-0 align-items-center gap-1"><iconify-icon icon="solar:kick-scooter-broken" class="align-middle"></iconify-icon> Delivery Charge : </p>
                                    </td>
                                    <td class="text-end text-dark fw-medium px-0">Rs. 00</td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between bg-light-subtle">
                    <div>
                        <p class="fw-medium text-dark mb-0">Total Amount</p>
                    </div>
                    <div>
                        <p class="fw-medium text-dark mb-0">
                            {{ number_format($order->grand_total, 2)	}}
                        </p>
                    </div>

                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Payment Information</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div>
                            <label class="fw-bold">Payment Mode:</label>
                            <span class="badge 
                                {{ $order->payment_mode == 'cod' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}
                                px-2 py-1 fs-13">
                                {{ $order->payment_mode == 'cod' ? 'Cash on Delivery' : 'Razorpay' }}
                            </span>
                        </div>
                        
                    </div>
                    <p class="text-dark mb-1 fw-medium">Razorpay Order ID : <span class="text-muted fw-normal fs-13"> {{ $order->razorpay_order_id }}</span></p>
                    <p class="text-dark mb-1 fw-medium">Razorpay Payment ID : <span class="text-muted fw-normal fs-13"> {{ $order->razorpay_payment_id }}</span></p>
                    <p class="text-dark mb-1 fw-medium">Razorpay Signature ID : <span class="text-muted fw-normal fs-13"> {{ $order->signature_id }}</span></p>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Customer Details</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2">
                        @php
                            $profileImg = $order->customer->profile_img ?? null;
                            $name = $order->customer->name ?? 'User';

                            $words = explode(' ', trim($name));
                            $initials = strtoupper(
                                substr($words[0], 0, 1) .
                                (isset($words[1]) ? substr($words[1], 0, 1) : '')
                            );
                        @endphp

                        <div class="d-flex align-items-center gap-2">
                            @if($profileImg)
                                <img
                                    src="{{ asset('images/customer/' . $profileImg) }}"
                                    alt="Profile Image"
                                    class="rounded-circle"
                                    width="45"
                                    height="45">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                    style="width:45px; height:45px;">
                                    {{ $initials }}
                                </div>
                            @endif

                            <div>
                                <h6 class="mb-0">{{ $order->customer->name }}</h6>
                                <small class="text-muted">{{ $order->customer->email }}</small>
                            </div>
                        </div>                        
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <h5>Contact Number</h5>
                    </div>
                    <p class="mb-1">
                        {{ $order->customer->phone_number ?? 'N/A' }}
                    </p>
                    <div class="mt-3">
                        <h5 class="mb-3">Delivery Address</h5>

                        @if($order->orderAddress)
                            <div class="border rounded p-3 bg-light">

                                <p class="mb-2">
                                    <strong>Name:</strong>
                                    {{ $order->orderAddress->full_name }}
                                </p>

                                <p class="mb-2">
                                    <strong>Address:</strong><br>
                                    {{ $order->orderAddress->address }}
                                </p>

                                @if($order->orderAddress->landmark)
                                    <p class="mb-2">
                                        <strong>Landmark:</strong>
                                        {{ $order->orderAddress->landmark }}
                                    </p>
                                @endif

                                <p class="mb-2">
                                    <strong>Locality:</strong>
                                    {{ $order->orderAddress->locality }}
                                </p>

                                <p class="mb-2">
                                    <strong>City:</strong>
                                    {{ $order->orderAddress->city }}
                                </p>

                                <p class="mb-2">
                                    <strong>State:</strong>
                                    {{ $order->orderAddress->state }}
                                </p>

                                <p class="mb-2">
                                    <strong>PIN Code:</strong>
                                    {{ $order->orderAddress->pin_code }}
                                </p>

                                <p class="mb-2">
                                    <strong>Country:</strong>
                                    {{ $order->orderAddress->country }}
                                </p>

                                <p class="mb-2">
                                    <strong>Phone:</strong>
                                    {{ $order->orderAddress->phone_number }}
                                </p>

                                @if($order->orderAddress->alternate_phone)
                                    <p class="mb-0">
                                        <strong>Alternate Phone:</strong>
                                        {{ $order->orderAddress->alternate_phone }}
                                    </p>
                                @endif

                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                Address not available.
                            </div>
                        @endif
                    </div>


                </div>
            </div>

        </div>
    </div>
</div>

@include('backend.layouts.common-modal-form')
<!-- modal--->
@endsection
@push('scripts')

@endpush