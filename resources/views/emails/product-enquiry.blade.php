<h2>Product Enquiry</h2>
<p><strong>Name:</strong> {{ $data['name'] }}</p>
<p><strong>Email:</strong> {{ $data['email'] }}</p>
<p><strong>Contact:</strong> {{ $data['contact'] }}</p>
<p><strong>Organization:</strong> {{ $data['org'] }}</p>
<p><strong>Message:</strong><br>
{{ $data['message'] }}</p>
<hr>
<h3>Product Details</h3>
<p><strong>Title:</strong> {{ $data['productData']['title'] }}</p>
<p>
    <strong>View Product:</strong>
    <a href="https://novasec-lac.vercel.app/products/{{ $data['productData']['slug'] }}/{{ $data['productData']['attribute_value_slug'] }}" target="_blank">
        Click Here
    </a>
</p>
@if(!empty($data['productData']['image']))
    <img src="{{ $data['productData']['image'] }}" width="120">
@endif