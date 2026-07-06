<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Models\Client;
use App\Helpers\ImageHelper;
class OurClientController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('id', 'desc')->paginate('10');
        return view('backend.pages.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('backend.pages.clients.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'client_image' => 'required|array',
            'client_image.*' => 'image|mimes:jpeg,png,jpg,webp|max:6144'
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        DB::beginTransaction();
        try {
            if ($request->hasFile('client_image')) {
                foreach ($request->file('client_image') as $index => $image) {
                    $timestamp = round(microtime(true) * 1000) . '_' . $index;
                    $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title ?? 'client'));
                    $baseName = ImageHelper::generateFileName($sanitized_title . '-' . $timestamp);
                    $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                        $image,
                        $baseName,
                        'clients'
                    );
                    Client::create([
                        'title' => $request->title ?? '',
                        'image' => $imagePath,
                        'status' => true,
                        'sort_order' => $index
                    ]);
                    Log::info('Client image uploaded: ' . $imagePath);
                }
            }
            Cache::forget('home_clients');
            DB::commit();
            return redirect('manage-client')->with('success', 'Client created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        return view('backend.pages.clients.edit', compact('client'));
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);        
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'client_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:6144',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer'
        ]);        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }        
        DB::beginTransaction();
        try {
            $data = [
                'title' => $request->title ?? $client->title,
                'status' => $request->has('status') ? true : false,
                'sort_order' => $request->sort_order ?? $client->sort_order
            ];
            if ($request->hasFile('client_image')) {
                if ($client->image) {
                    $deleteImageFile = ImageHelper::deleteSingleImage(
                        $client->image,
                        'clients'
                    );
                } 
                $timestamp = round(microtime(true) * 1000);
                $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title ?? 'client'));
                $baseName = ImageHelper::generateFileName($sanitized_title . '-' . $timestamp);
                $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('client_image'),
                    $baseName,
                    'clients'
                );                
                $data['image'] = $imagePath;
            }            
            $client->update($data);            
            Cache::forget('home_clients');
            DB::commit();            
            return redirect('manage-client')->with('success', 'Client updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating client: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $client = Client::findOrFail($id);
            if ($client->image) {
                $deleteImageFile = ImageHelper::deleteSingleImage(
                    $client->image,
                    'clients'
                );
            }            
            $client->delete();            
            Cache::forget('home_clients');            
            return redirect('manage-client')->with('success', 'Client deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting client: ' . $e->getMessage());
        }
    }


}
