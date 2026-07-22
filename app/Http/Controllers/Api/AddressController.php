<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function list(Request $request)
    {
        $customer = $request->user();

        $addresses = Address::where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Addresses fetched successfully.',
            'data' => $addresses,
        ]);
    }

    public function store(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'phone_number' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'zip_code' => 'required|string|max:10',
            'locality' => 'required|string|max:150',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'landmark' => 'nullable|string|max:150',
            'country' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $address = DB::transaction(function () use ($request, $customer) {
                $isFirst = Address::where('customer_id', $customer->id)->doesntExist();
                $address = Address::create([
                    'customer_id' => $customer->id,
                    'name' => $request->name,
                    'phone_number' => $request->phone_number,
                    'alternate_phone' => $request->alternate_phone,
                    'zip_code' => $request->zip_code,
                    'locality' => $request->locality,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'landmark' => $request->landmark,
                    'country' => $request->country ?? 'India',
                    'is_default' => $isFirst ? true : (bool) $request->boolean('is_default'),
                ]);

                if ($address->is_default) {
                    Address::where('customer_id', $customer->id)
                        ->where('id', '!=', $address->id)
                        ->update(['is_default' => false]);
                }

                return $address;
            });

            return response()->json([
                'success' => true,
                'message' => 'Address added successfully.',
                'data' => $address,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not save address.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $customer = $request->user();
        $address = Address::where('customer_id', $customer->id)->where('id', $id)->first();
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Address not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'phone_number' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'zip_code' => 'required|string|max:10',
            'locality' => 'required|string|max:150',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'landmark' => 'nullable|string|max:150',
            'country' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $address->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => $address->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $customer = $request->user();
        $address = Address::where('customer_id', $customer->id)->where('id', $id)->first();
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Address not found.'], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();
        if ($wasDefault) {
            $next = Address::where('customer_id', $customer->id)->orderByDesc('id')->first();
            $next?->update(['is_default' => true]);
        }

        return response()->json(['success' => true, 'message' => 'Address removed successfully.']);
    }

    public function setDefault(Request $request, $id)
    {
        $customer = $request->user();
        $address = Address::where('customer_id', $customer->id)->where('id', $id)->first();
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Address not found.'], 404);
        }

        DB::transaction(function () use ($customer, $address) {
            Address::where('customer_id', $customer->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Default address updated.',
            'data' => $address->fresh(),
        ]);
    }
}
