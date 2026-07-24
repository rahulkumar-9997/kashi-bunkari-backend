<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Mail\BulkMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EnquiryController extends Controller
{
    public function contactEnquiryStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|digits:10',
            'message' => 'nullable|string|min:10|max:2000',
            'website' => 'nullable|string|max:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (!empty($data['website'])) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your enquiry has been received.',
            ]);
        }
        $mail = new ContactMail($data);
        try {
            Mail::to('rahulkumarmaurya464@gmail.com')->send($mail);
            return response()->json([
                'success' => true,
                'message' => "Thank you, {$data['name']}! We've received your enquiry and will get back to you shortly.",
            ]);
        } catch (\Throwable $e) {
            Log::error('Enquiry mail failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not send your enquiry right now. Please try again or WhatsApp us directly.',
            ], 500);
        }
    }
    
    public function bulkOrderEnquiryStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|digits:10',
            'message' => 'nullable|string|min:10|max:2000',
            'website' => 'nullable|string|max:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (!empty($data['website'])) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your enquiry has been received.',
            ]);
        }
        $mail = new BulkMail($data);
        try {
            Mail::to('rahulkumarmaurya464@gmail.com')->send($mail);
            return response()->json([
                'success' => true,
                'message' => "Thank you, {$data['name']}! We've received your enquiry and will get back to you shortly.",
            ]);
        } catch (\Throwable $e) {
            Log::error('Enquiry mail failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not send your enquiry right now. Please try again or WhatsApp us directly.',
            ], 500);
        }
    }
}
