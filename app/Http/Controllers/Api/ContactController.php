<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'subject' => 'nullable|string|max:150',
            'message' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $request->only(['name', 'email', 'phone', 'subject', 'message']);
        Mail::to('akshat@gdsons.co.in')->send(new ContactMail($data));
        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);
    }
}
