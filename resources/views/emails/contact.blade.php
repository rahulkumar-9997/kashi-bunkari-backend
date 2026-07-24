<!-- resources/views/emails/enquiry.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f5f0;
            padding: 24px;
            color: #333;
        }

        .card {
            max-width: 560px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .header {
            background: #8b1a34;
            color: #fff;
            padding: 20px 28px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .body {
            padding: 24px 28px;
        }

        .row {
            margin-bottom: 14px;
        }

        .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #999;
            margin-bottom: 3px;
        }

        .value {
            font-size: 14px;
            color: #222;
        }

        .message-box {
            background: #f8f5f0;
            border-radius: 8px;
            padding: 14px 16px;
            margin-top: 6px;
            white-space: pre-wrap;
        }

        .footer {
            padding: 16px 28px;
            font-size: 11px;
            color: #aaa;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="header">
            <h2>New Enquiry — Kasibunkari</h2>
        </div>
        <div class="body">
            <div class="row">
                <div class="label">Name</div>
                <div class="value">{{ $data['name'] }}</div>
            </div>
            <div class="row">
                <div class="label">Email</div>
                <div class="value">{{ $data['email'] }}</div>
            </div>
            @if($enquiryPhone)
            <div class="row">
                <div class="label">Phone</div>
                <div class="value">{{ $data['phone'] }}</div>
            </div>
            @endif
            <div class="row">
                <div class="label">Message</div>
                <div class="message-box">{{ $data['message'] }}</div>
            </div>
        </div>
        <div class="footer">
            Sent from the Contact Us form on kasibunkari.com — reply directly to respond to {{ $enquiryName }}.
        </div>
    </div>
</body>

</html>