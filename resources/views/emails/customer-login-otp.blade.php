<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
        <tr>
            <td align="center">
                <table width="400" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.08); overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#ff710a,#ff710a); padding:20px; text-align:center; color:#fff;">
                            <h2 style="margin:0;">OTP Verification</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px; text-align:center; color:#333;">
                            
                            <p style="font-size:16px; margin-bottom:10px;">
                                Use the following OTP to continue
                            </p>
                            <div style="
                                display:inline-block;
                                padding:15px 25px;
                                font-size:28px;
                                letter-spacing:5px;
                                font-weight:bold;
                                color:#333;
                                background:#f1f3f6;
                                border-radius:8px;
                                border:1px dashed #ccc;
                                margin:20px 0;
                            ">
                                {{ $otp }}
                            </div>

                            <p style="font-size:14px; color:#777;">
                                This OTP is valid for <strong>30 minutes</strong>
                            </p>

                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9f9f9; padding:15px; text-align:center; font-size:12px; color:#999;">
                            If you didn’t request this, you can safely ignore this email.
                        </td>
                    </tr>
                </table>
                <p style="font-size:12px; color:#aaa; margin-top:15px;">
                    © {{ date('Y') }} <strong>{{ config('app.name') }}</strong>. All rights reserved.
                </p>
            </td>
        </tr>
    </table>

</body>
</html>