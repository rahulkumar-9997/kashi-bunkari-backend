@extends('backend.layouts.master')
@section('title','Manage Order')
@section('main-content')
@push('styles')
<style>

.invoice-box {
        max-width: 890px;
        margin: auto;
        padding:10px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15);
        font-size: 14px;
        line-height: 24px;
        font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        color: #555;
    }
    .invoice-box table {
        width: 100%;
    }
    .print-btn{
	margin-top: -5px;
    }
    
@media print {
    @page { margin: 0; }
    body { 
	    margin: 1.0cm!important; 
	    -webkit-print-color-adjust:exact !important;
	    print-color-adjust:exact !important;
    }
    .invoice-box table {
        width: 100%;
	    border-bottom: solid 1px #ccc!important;
    }
    #invoice-header-tr{
	    background-color: #f4f4f4!important;
    }
    #invoice-item-tr{
	    background: #eee!important;
    }
    #invoice-text-center{
	    text-align: center!important;
    }
}
</style>
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Invoice ({{ $order->order_number }})</h4>
                    <button type="button" class="btn btn-warning pull-right print-btn" onclick="printDiv('printable')">Print</button>
                </div>
                <div class="card-body">
                    <div class="invoice-box" id="printable">
                        <table cellpadding="0" cellspacing="0" style="width: 100%;">
                            <tbody>
                                <tr style="background-color: #f4f4f4;" id="invoice-header-tr">
                                    <td colspan="3" style="text-align: center; padding: 5px;vertical-align: middle;">
                                        <h2 style="margin-bottom: 10px; text-align: center; margin-top: 5px;">
                                            <img src="{{asset('frontend/assets/gd-img/footer-img/gd-footer-logo.png')}}" style="width:190px;" alt="">
                                        </h2>
                                        <span>A Unit of GD Sons</span><br>
                                        <span>
                                            W.H.Smith School Road, Sigra, Varanasi 221010 Uttar Pradesh India.
                                        </span><br>
                                        <span>
                                            Ph: +918318894257 | Email: akshat@gdsons.co.in | Website : https://www.gdsons.co.in
                                        </span><br>
                                        <span>GSTIN No. 09AKAPP2530L1Z3</span><br>
                                        <span style="padding-top: 30px; padding-bottom: 10px;">
                                            <strong>Order Id: </strong>
                                            {{ $order->order_id }}
                                            <strong>Order Date: </strong>
                                            {{ $order->created_at->format('d, M Y h:i A') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="padding: 5px; vertical-align: middle;">
                                        <table style="width: 100%; line-height: inherit; text-align: left; border-bottom: solid 1px #ccc;">
                                            <tbody>
                                                <tr>
                                                    <td style="padding-bottom: 20px;">
                                                        <b> Customer Details :</b> <br>
                                                        {{ $order->customer->name }} <br>
                                                        {{ $order->customer->email }}<br>
                                                        {{ $order->customer->phone_number}} <br>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="3">
                                        <table style="width: 100%; line-height: inherit; text-align: left; border-bottom: solid 1px #ccc;">
                                            <tbody>
                                                <tr>
                                                    <td style="padding-bottom: 40px;">
                                                        <b>Delivery Address:</b><br>

                                                        @if($order->orderAddress)

                                                            <p style="margin:3px 0;">
                                                                {{ $order->orderAddress->full_name }}
                                                            </p>

                                                            <p style="margin:3px 0;">
                                                                {{ $order->orderAddress->address }}
                                                            </p>

                                                            @if($order->orderAddress->landmark)
                                                                <p style="margin:3px 0;">
                                                                    Landmark: {{ $order->orderAddress->landmark }}
                                                                </p>
                                                            @endif

                                                            <p style="margin:3px 0;">
                                                                {{ $order->orderAddress->locality }},
                                                                {{ $order->orderAddress->city }},
                                                                {{ $order->orderAddress->state }}
                                                                - {{ $order->orderAddress->pin_code }}
                                                            </p>

                                                            <p style="margin:3px 0;">
                                                                {{ $order->orderAddress->country }}
                                                            </p>

                                                            <p style="margin:3px 0;">
                                                                {{ $order->orderAddress->phone_number }}
                                                            </p>

                                                            @if($order->orderAddress->alternate_phone)
                                                                <p style="margin:3px 0;">
                                                                    Alternate Phone: {{ $order->orderAddress->alternate_phone }}
                                                                </p>
                                                            @endif

                                                        @else

                                                            <p style="margin:3px 0;">Address not available.</p>

                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <table cellspacing="0px" cellpadding="2px">
                                            <tbody>
                                                <tr id="invoice-item-tr" style="background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 12px;">
                                                    <td style="width:25%; padding-left: 2px;">
                                                        ITEM
                                                    </td>
                                                    <td style="width:10%; text-align:center;">
                                                        QTY.
                                                    </td>
                                                    <td style="width:10%; text-align:right;">
                                                        PRICE (INR)
                                                    </td>
                                                    <td style="width:15%; text-align:right;">
                                                        TOTAL AMOUNT (INR)
                                                    </td>
                                                </tr>
                                                @if($order->orderLine->isNotEmpty())
                                                @foreach($order->orderLine as $line)
                                                <tr style="border-bottom: 1px solid #eee;">
                                                    <td style="width:25%; padding-bottom: 10px; padding-top: 10px;">
                                                        {{ ucwords(strtolower($line->product->title)) }}
                                                        <!-- <p style="margin-bottom: 2px;"><strong>HSN Code :</strong> 500790</p> -->
                                                    </td>
                                                    <td style="width:10%; text-align:center; padding-bottom: 10px;  padding-top: 10px;">
                                                        {{ $line->quantity }}
                                                    </td>
                                                    <td style="width:10%; text-align:right; padding-bottom: 10px;  padding-top: 10px;">
                                                        Rs. {{ number_format($line->price, 2) }}
                                                    </td>
                                                    <td style="width:15%; text-align:right; padding-bottom: 10px;  padding-top: 10px;">
                                                        Rs. {{ number_format($line->quantity * $line->price, 2) }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                               
                                @if($order->shiprocketCourier)
                                <tr>
                                    <td colspan="3" align="right" style="padding-top: 5px; padding-bottom: 5px;"> Shipping Charges: <b>Rs. {{ $order->shiprocketCourier->courier_shipping_rate }} </b> </td>
                                </tr>
                                @endif
                                <tr>
                                    <td colspan="3" align="right" style="padding-top: 5px; padding-bottom: 5px;"> Total Amount: <b>Rs. {{ number_format($order->grand_total, 2)	}} </b> </td>
                                </tr>
                                <tr>
                                    <td colspan="3" align="right" style="padding-top: 5px; padding-bottom: 10px;"><b>Total Amount in Words : </b>
                                        Rs. {{ numberToWords($order->grand_total) }} Only.
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <table cellspacing="0px" cellpadding="2px">
                                            <tbody>
                                                <tr>
                                                    <td width="100%" style="text-align: center;" id="invoice-text-center">
                                                        <span>
                                                            * This is a computer generated invoice and does not
                                                            require a physical signature
                                                            <span>
                                                            </span></span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
function printDiv(divName) {
    var printContents = document.getElementById(divName).innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}
</script>
@endpush
