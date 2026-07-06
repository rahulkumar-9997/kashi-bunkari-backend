<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProductBuyNowEnquiryMail;
use App\Mail\ProductEnquiryMail;
use Illuminate\Support\Facades\Log;
class ProductEnquiryController extends Controller
{
    public function buyNowSubmit(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
                'email'       => 'required|email:rfc,dns|max:255',
                'contact'     => 'required',
                'product_id'  => 'required|integer|exists:products,id',
                'qty'         => 'required|integer|min:1|max:1000',
                'note'        => 'nullable|string|max:1000',
            ], [
            'name.required'       => 'Name is required',
            'name.regex'          => 'Name should contain only letters and spaces',
            'email.required'      => 'Email is required',
            'email.email'         => 'Enter a valid email address',
            'contact.required'    => 'Contact number is required',            
            'product_id.required' => 'Product is required',
            'product_id.exists'   => 'Selected product is invalid',
            'qty.required'        => 'Quantity is required',
            'qty.min'             => 'Minimum quantity is 1',
            'qty.max'             => 'Maximum quantity limit exceeded',
            'note.max'            => 'Note should not exceed 1000 characters',
        ]);
        Log::info('Product Enquiry Received: ' . json_encode($validated));
        $product = Product::where('product_status', 1)
                ->where('id', $validated['product_id'])
                ->with([
                    'category:id,title,slug',
                    'firstSortedImage:id,product_id,image_path',
                    'images:id,product_id,image_path,sort_order',
                    'inventories' => function ($query) {
                        $query->select('id','product_id','mrp','offer_rate','purchase_rate','sku')
                            ->orderBy('mrp','asc');
                    },

                    'productAttributesValues.attributeValue:id,slug'
                ])
            ->select('id','title','slug','category_id')
            ->first();
            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            $inventory = $product->inventories->first();
            $attribute_value_slug = optional($product->productAttributesValues->first())->attributeValue->slug ?? null;
            $productData = [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'mrp' => $inventory->mrp ?? null,
                'offer_rate' => $inventory->offer_rate ?? null,
                'sku' => $inventory->sku ?? null,
                'attribute_value_slug' => $attribute_value_slug,
                'category' => [
                    'title' => $product->category->title ?? null,
                    'slug'  => $product->category->slug ?? null,
                ],
                'image' => $product->firstSortedImage ? $product->firstSortedImage->getThumbImages() : null,
                'image_path' => $product->firstSortedImage 
                    ? $product->firstSortedImage->image_path 
                    : null,
            ];
            $data = [
                'name'        => $validated['name'],
                'email'       => $validated['email'],
                'contact'     => $validated['contact'],
                'qty'         => $validated['qty'],
                'note'        => $validated['note'] ?? null,
                'productData' => $productData,
            ];
            $recipients = [
                'akshat@gdsons.co.in',
                'priyeshrai.dev@gmail.com'
            ];
            try {
                foreach ($recipients as $email) {
                    Mail::to($email)->send(new ProductBuyNowEnquiryMail($data, $product));
                    Log::info('BuyNow Mail Sent To: ' . $email);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send BuyNow mail: ' . $e->getMessage());
            }
            return response()->json([
                'status'  => true,
                'message' => 'Product Buy Now enquiry form submitted successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Product Buy Now Enquiry Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function productEnquirySubmit(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
                'email'       => 'required|email:rfc,dns|max:255',
                'contact'     => 'required',
                'org'         => 'required|string|max:255|',
                'message'        => 'nullable|string|max:1000',
                'product_id'  => 'required|integer|exists:products,id',
                
            ], [
            'name.required'       => 'Name is required',
            'name.regex'          => 'Name should contain only letters and spaces',
            'email.required'      => 'Email is required',
            'email.email'         => 'Enter a valid email address',
            'contact.required'    => 'Contact number is required',
            'product_id.required' => 'Product is required',
            'product_id.exists'   => 'Selected product is invalid',
            'org.required'        => 'Organization is required',
            'message.max'         => 'Message should not exceed 1000 characters',
        ]);
        Log::info('Product Enquiry Received: ' . json_encode($validated));
        $product = Product::where('product_status', 1)
                ->where('id', $validated['product_id'])
                ->with([
                    'category:id,title,slug',
                    'firstSortedImage:id,product_id,image_path',
                    'images:id,product_id,image_path,sort_order',
                    'inventories' => function ($query) {
                        $query->select('id','product_id','mrp','offer_rate','purchase_rate','sku')
                            ->orderBy('mrp','asc');
                    },

                    'productAttributesValues.attributeValue:id,slug'
                ])
            ->select('id','title','slug','category_id')
            ->first();
            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            $inventory = $product->inventories->first();
            $attribute_value_slug = optional($product->productAttributesValues->first())->attributeValue->slug ?? null;
            $productData = [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'mrp' => $inventory->mrp ?? null,
                'offer_rate' => $inventory->offer_rate ?? null,
                'sku' => $inventory->sku ?? null,
                'attribute_value_slug' => $attribute_value_slug,
                'category' => [
                    'title' => $product->category->title ?? null,
                    'slug'  => $product->category->slug ?? null,
                ],
                'image' => $product->firstSortedImage ? $product->firstSortedImage->getThumbImages() : null,
                'image_path' => $product->firstSortedImage 
                    ? $product->firstSortedImage->image_path 
                    : null,
            ];
            $data = [
                'name'        => $validated['name'],
                'email'       => $validated['email'],
                'contact'     => $validated['contact'],
                'org'         => $validated['org'],
                'message'     => $validated['message'] ?? null,
                'productData' => $productData,
            ];
            $recipients = [
                'akshat@gdsons.co.in',
                'priyeshrai.dev@gmail.com'
            ];
            try {
                foreach ($recipients as $email) {
                    Mail::to($email)->send(new ProductEnquiryMail($data, $product));
                    Log::info('Enquiry Mail Sent To: ' . $email);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send enquiry mail: ' . $e->getMessage());
            }
            return response()->json([
                'status'  => true,
                'message' => 'Product Enquiry form submitted successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Product Enquiry Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    
}
