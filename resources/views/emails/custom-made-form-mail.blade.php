<h2>Custom Made Form Request</h2>

<p><strong>Company:</strong> {{ $data['companyName'] ?? '-' }}</p>
<p><strong>Name:</strong> {{ $data['name'] }}</p>
<p><strong>Email:</strong> {{ $data['email'] }}</p>
<p><strong>Phone:</strong> {{ $data['phone'] }}</p>
<p><strong>Request For:</strong> {{ $data['requestFor'] }}</p>
<p><strong>Message:</strong></p>
<p>{{ $data['message'] }}</p>