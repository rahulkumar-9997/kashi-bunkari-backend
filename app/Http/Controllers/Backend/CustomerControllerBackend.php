<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Imports\CustomerImport;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class CustomerControllerBackend extends Controller
{
    public function index(Request $request)
    {
        $data['customer_list'] = Customer::withCount('orders')
            ->orderBy('id', 'desc')
            ->paginate(15);

        if ($request->ajax()) {
            return view('backend.pages.manage-customer.partials.customer-list', compact('data'))->render();
        }

        return view('backend.pages.manage-customer.index', compact('data'));
    }
    /**
     * Display customer details
     */
    public function show($customer_id)
    {
        try {
            $customer = Customer::withCount([
                'orders',
                'wishlists',
                'addresses'
            ])->with([
                'addresses' => function ($query) {
                    $query->latest();
                }
            ])->findOrFail($customer_id);
            $totalOrders = $customer->orders_count;
            $orders = Order::with([
                'orderStatus',
                'shippingAddress',
                'billingAddress',
                'orderLines' => function ($query) {
                    $query->with('product');
                }
            ])
                ->where('customer_id', $customer_id)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();

            $totalSpent = Order::where('customer_id', $customer_id)
                ->where('payment_received', true)
                ->sum('grand_total') ?? 0;
            $totalInvoices = Order::where('customer_id', $customer_id)->count() ?? 0;
            $defaultAddress = null;
            if ($customer->addresses && $customer->addresses->isNotEmpty()) {
                $defaultAddress = $customer->addresses->where('is_default', true)->first();
                if (!$defaultAddress) {
                    $defaultAddress = $customer->addresses->first();
                }
            }
            return view('backend.pages.manage-customer.customer-details', compact(
                'customer',
                'totalOrders',
                'orders',
                'totalSpent',
                'totalInvoices',
                'defaultAddress'
            ));
        } catch (\Exception $e) {
            Log::error('Customer details error: ' . $e->getMessage());
            return redirect()->route('manage-customer.index')
                ->with('error', 'Customer not found or an error occurred.');
        }
    }
    /**
     * Show customer orders list
     */
    public function showCustomerOrdersList($customer_id)
    {
        try {
            $customer = Customer::findOrFail($customer_id);
            $orders = Order::with([
                'orderStatus',
                'shippingAddress',
                'billingAddress',
                'orderLines' => function ($query) {
                    $query->with('product');
                }
            ])
                ->where('customer_id', $customer_id)
                ->orderBy('id', 'desc')
                ->paginate(15);
            $orderStats = [
                'total' => $orders->total(),
                'completed' => Order::where('customer_id', $customer_id)
                    ->whereHas('orderStatus', function ($q) {
                        $q->where('name', 'Delivered');
                    })->count(),
                'pending' => Order::where('customer_id', $customer_id)
                    ->whereHas('orderStatus', function ($q) {
                        $q->where('name', 'Pending');
                    })->count(),
                'total_spent' => Order::where('customer_id', $customer_id)
                    ->where('payment_received', true)
                    ->sum('grand_total') ?? 0
            ];

            return view('backend.pages.manage-customer.customer-order-list', compact('customer', 'orders', 'orderStats'));
        } catch (\Exception $e) {
            Log::error('Customer orders error: ' . $e->getMessage());
            return redirect()->route('manage-customer.index')
                ->with('error', 'Error loading customer orders.');
        }
    }
    /**
     * Show customer wishlist
     */
    public function showCustomerWishlist($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $wishlist = Wishlist::with([
                'product' => function ($query) {
                    $query->with([
                        'inventories' => function ($query) {
                            $query->orderBy('mrp', 'asc')->take(1);
                        },
                        'images' => function ($query) {
                            $query->select('id', 'product_id', 'image_path');
                        },
                        'ProductAttributesValues' => function ($query) {
                            $query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                                ->with([
                                    'attributeValue:id,slug'
                                ])
                                ->orderBy('id');
                        }
                    ]);
                },
            ])->where('customer_id', $id)
                ->latest()
                ->paginate(20);

            $wishlistCount = Wishlist::where('customer_id', $id)->count();
            return view('backend.pages.manage-customer.wishlist.index', compact('customer', 'wishlist', 'wishlistCount'));
        } catch (\Exception $e) {
            Log::error('Customer wishlist error: ' . $e->getMessage());
            return redirect()->route('manage-customer.index')
                ->with('error', 'Error loading customer wishlist.');
        }
    }
    /**
     * Edit customer (AJAX modal form)
     */
    public function edit($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $form = '
            <div class="modal-body">
                <form method="POST" action="' . route('manage-customer.update', $id) . '" accept-charset="UTF-8" enctype="multipart/form-data" id="updateCustomer">
                    ' . csrf_field() . '
                    ' . method_field('PUT') . '
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" value="' . htmlspecialchars($customer->name) . '" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" value="' . htmlspecialchars($customer->email) . '" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone_number" class="form-label fw-semibold">Phone Number</label>
                                <input type="text" maxlength="10" id="phone_number" name="phone_number" class="form-control" value="' . htmlspecialchars($customer->phone_number) . '">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="1" ' . ($customer->status == 1 ? 'selected' : '') . '>Active</option>
                                    <option value="0" ' . ($customer->status == 0 ? 'selected' : '') . '>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gender" class="form-label fw-semibold">Gender</label>
                                <select id="gender" name="gender" class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="male" ' . ($customer->gender == 'male' ? 'selected' : '') . '>Male</option>
                                    <option value="female" ' . ($customer->gender == 'female' ? 'selected' : '') . '>Female</option>
                                    <option value="other" ' . ($customer->gender == 'other' ? 'selected' : '') . '>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label fw-semibold">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="' . ($customer->date_of_birth ? $customer->date_of_birth->format('Y-m-d') : '') . '">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="bio" class="form-label fw-semibold">Bio</label>
                                <textarea id="bio" name="bio" class="form-control" rows="2">' . htmlspecialchars($customer->bio) . '</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>';

            return response()->json([
                'success' => true,
                'message' => 'Customer Form created successfully',
                'form' => $form,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update customer
     */
    public function update(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => '|string|max:255',
                'email' => '|email|max:255|unique:customers,email,' . $id,
                'phone_number' => 'nullable|string|max:10|unique:customers,phone_number,' . $id,
                'status' => 'nullable|boolean',
                'gender' => 'nullable|string|in:male,female,other',
                'date_of_birth' => 'nullable|date',
                'bio' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed',
                ], 422);
            }

            $customer->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'status' => $request->status ?? $customer->status,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'bio' => $request->bio,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer updated successfully',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Customer update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete customer
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer deleted successfully',
                ]);
            }

            return redirect()->route('manage-customer.index')
                ->with('success', 'Customer deleted successfully');
        } catch (\Exception $e) {
            Log::error('Customer deletion error: ' . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error deleting customer: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('manage-customer.index')
                ->with('error', 'Error deleting customer');
        }
    }

    /**
     * Add new customer modal form
     */
    public function create(Request $request)
    {
        $form = '
        <div class="modal-body">
            <form method="POST" action="' . route('manage-customer.store') . '" accept-charset="UTF-8" enctype="multipart/form-data" id="addNewCustomer">
                ' . csrf_field() . '
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone_number" class="form-label fw-semibold">Phone Number</label>
                            <input type="text" maxlength="10" id="phone_number" name="phone_number" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="gender" class="form-label fw-semibold">Gender</label>
                            <select id="gender" name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="bio" class="form-label fw-semibold">Bio</label>
                            <textarea id="bio" name="bio" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                </div>
            </form>
        </div>';

        return response()->json([
            'success' => true,
            'message' => 'Customer Form created successfully',
            'form' => $form,
        ]);
    }

    /**
     * Submit new customer
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['', 'string', 'max:255'],
            'email' => ['', 'email', 'unique:customers,email'],
            'phone_number' => ['nullable', 'string', 'max:10', 'unique:customers,phone_number'],
            'status' => ['nullable', 'boolean'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'bio' => ['nullable', 'string', 'max:500'],
        ], $this->customValidationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone_number' => $request->input('phone_number'),
                'customer_id' => Customer::generateCustomerId(),
                'password' => Hash::make($this->generateRandomPassword()),
                'status' => $request->input('status', 1),
                'gender' => $request->input('gender'),
                'date_of_birth' => $request->input('date_of_birth'),
                'bio' => $request->input('bio'),
            ];

            $customer = Customer::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer added successfully!',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Customer creation error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import customers form
     */
    public function importForm()
    {
        return view('backend.pages.manage-customer.import-customer');
    }

    /**
     * Import customers submit
     */
    public function importFormSubmit(Request $request)
    {
        $request->validate([
            'import_file' => '|file|mimes:xlsx,xls,csv|max:2048',
        ], [
            'import_file.' => 'Please upload a file.',
            'import_file.mimes' => 'The file must be in .xlsx, .xls, or .csv format.',
            'import_file.max' => 'The file size must not exceed 2MB.',
        ]);

        try {
            Excel::import(new CustomerImport, $request->file('import_file'));
            return redirect()->route('manage-customer.index')
                ->with('success', 'Customers imported successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
                Log::error('Import Validation Error:', [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ]);
            }
            return back()->withErrors($errors)->with('error', 'Some rows failed validation. Please check the details.');
        } catch (\Exception $e) {
            Log::error('Import General Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', 'An error occurred while importing: ' . $e->getMessage());
        }
    }
    
    private function generateRandomPassword()
    {
        return Str::random(8);
    }

    /**
     * Custom validation messages
     */
    private function customValidationMessages()
    {
        return [
            'name.' => 'The customer name is .',
            'email.' => 'The email is .',
            'email.email' => 'The email format is invalid.',
            'email.unique' => 'The email :input is already taken.',
            'phone_number.string' => 'The phone number must be a valid string.',
            'phone_number.max' => 'The phone number must not exceed 10 characters.',
            'phone_number.unique' => 'The Phone Number :input is already taken.',
        ];
    }
}
