<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomMadeFormMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Models\CustomMadeBagRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Http;
class CustomMadeBagController extends Controller
{
    public function customMadeFormSubmit(Request $request)
    {
        try {
            $request->merge([
                'marketing' => filter_var($request->marketing, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
            $validated = $request->validate([
                'companyName' => 'nullable|string|max:255',
                'name'        => 'required|string|max:255',
                'email'       => 'required|email',
                'phone'       => 'required|string|max:20',
                'message'     => 'required|string',
                'attachment'  => 'nullable|file|mimes:pdf,png,jpg,jpeg,dxf,webp|max:10240',
                'marketing'   => 'nullable|boolean',
            ]);
            Log::info('Custom Made Bag Request Received: ' . json_encode($validated));
            $fileName = null;
            if ($request->hasFile('attachment')) {
                $file      = $request->file('attachment');
                $extension = strtolower($file->getClientOriginalExtension());
                $company = !empty($validated['companyName'])
                ? Str::slug($validated['companyName'])
                : 'file';
                $fileName = $company . '_' . time() . '_' . Str::random(6) . '.' . $extension;
                $path = storage_path('app/public/attachment/custom-bags/');
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }
                $file->move($path, $fileName);
            }
            $customMadeBagRequest = CustomMadeBagRequest::create([
                'company_name' => $validated['companyName'] ?? null,
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'phone'        => $validated['phone'],
                'message'      => $validated['message'],
                'attachment'   => $fileName,
                'marketing'    => $validated['marketing'] ?? false,
            ]);
            $data = [
                'companyName' => $validated['companyName'] ?? null,
                'name'        => $validated['name'],
                'email'       => $validated['email'],
                'phone'       => $validated['phone'],
                'message'     => $validated['message'],
                'attachment'  => $fileName,
                'marketing'   => $validated['marketing'] ?? false,
            ];
            Mail::to('akshat@gdsons.co.in')->send(new CustomMadeFormMail($data));
            Log::info('Custom Made Bag Request Submitted: ' . json_encode($data));
            //Log::info('Email sent to admin for Custom Made Bag Request: ' . 'akshat@gdsons.co.in');
            return response()->json([
                'status'  => true,
                'message' => 'Request form submitted successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Custom Made Form Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again'
            ], 500);
        }
    }


    public function customMadeFormSubmit_msg_91(Request $request)
    {
        try {
            // $response = Http::get('https://api.ipify.org');
            // Log::info('Public IP: ' . $response->body());
            $request->merge([
                'marketing' => filter_var($request->marketing, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
            $validated = $request->validate([
                'companyName' => 'nullable|string|max:255',
                'name'        => 'required|string|max:255',
                'email'       => 'required|email',
                'phone'       => 'required|string|max:20',
                'requestFor'  => 'required|string',
                'message'     => 'required|string',
                'attachment'  => 'nullable|file|mimes:pdf,png,jpg,jpeg,dxf,webp|max:10240',
                'marketing'   => 'nullable|boolean',
            ]);
            Log::info('Custom Made Bag Request Received: ' . json_encode($validated));
            $fileName = null;
            if ($request->hasFile('attachment')) {
                $file      = $request->file('attachment');
                $extension = strtolower($file->getClientOriginalExtension());

                $company = !empty($validated['companyName'])
                    ? Str::slug($validated['companyName'])
                    : 'file';

                $fileName = $company . '_' . time() . '_' . Str::random(6) . '.' . $extension;

                $path = storage_path('app/public/attachment/custom-bags/');

                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }
                $file->move($path, $fileName);
            }
            CustomMadeBagRequest::create([
                'company_name' => $validated['companyName'] ?? null,
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'phone'        => $validated['phone'],
                'request_for'  => $validated['requestFor'],
                'message'      => $validated['message'],
                'attachment'   => $fileName,
                'marketing'    => $validated['marketing'] ?? false,
            ]);
            $data = [
                'companyName' => $validated['companyName'] ?? '',
                'name'        => $validated['name'],
                'email'       => $validated['email'],
                'phone'       => $validated['phone'],
                'requestFor'  => $validated['requestFor'],
                'message'     => $validated['message'],
            ];
            $htmlContent = View::make('emails.custom-made-form-mail', ['data' => $data])->render();
            $fileUrl = null;
            if ($fileName) {
                $fileUrl = url('storage/attachment/custom-bags/' . $fileName);
            }
            $payload = [
                "recipients" => [
                    [
                        "to" => [
                            [
                                "name"  => "Admin",
                                "email" => "rahulkumarmaurya464@gmail.com"
                            ]
                        ]
                    ]
                ],
                "from" => [
                    "name"  => "Nova Sac",
                    "email" => "jfevw7.mailer91.com"
                ],
                
                "subject" => "New Custom Bag Request",
                "body" => [
                    "type" => "text/html",
                    "data" => $htmlContent
                ]
            ];
            if ($fileUrl) {
                $payload['attachments'] = [
                    [
                        "filePath" => $fileUrl,
                        "fileName" => $fileName
                    ]
                ];
            }
            //Log::info('FINAL PAYLOAD: ' . json_encode($payload));
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authkey' => env('MSG91_AUTH_KEY'),
                'content-type' => 'application/json'
            ])->post('https://control.msg91.com/api/v5/email/send', $payload);
            Log::info('MSG91 Response: ' . $response->body());
            return response()->json([
                'status'  => true,
                'message' => 'Request form submitted successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            Log::error('Custom Made Form Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again'
            ], 500);
        }
    }
}
