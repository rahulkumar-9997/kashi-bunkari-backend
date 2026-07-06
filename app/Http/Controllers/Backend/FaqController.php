<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Helpers\ImageHelper;
use App\Models\Faq;
class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('id', 'desc')
            ->get();
        return view('backend.pages.manage-faq.index', compact('faqs'));
    }

    public function create()
    {
        return view('backend.pages.manage-faq.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'faq_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:6144',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        try {
            if ($request->hasFile('faq_image')) {
                $timestamp = round(microtime(true) * 1000);
                $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)) . '-' . $timestamp;
                $baseName = ImageHelper::generateFileName($sanitized_title);
                $input['answer_image'] = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('faq_image'),
                    $baseName,
                    'faq'
                );
                Log::info('FAQ image uploaded: ' . $input['answer_image']);
            }
            Faq::create([
                'question' => $request->input('title'),
                'answer' => $request->input('content'),
                'answer_image' => $input['answer_image'] ?? null,
                'status' => $request->input('status'),
            ]);
            Cache::forget('home_faqs');
            return redirect()->route('manage-faq.index')
                ->with('success', 'FAQ created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating FAQ: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $faq = Faq::findOrFail($id);
        return view('backend.pages.manage-faq.edit', compact('faq'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'faq_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:6144',
            'status' => 'required|in:active,inactive',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        try {
            $faq = Faq::findOrFail($id);
            $input = [
                'question' => $request->input('title'),
                'answer' => $request->input('content'),
                'status' => $request->input('status'),
            ];
            if ($request->hasFile('faq_image')) {
                if ($faq->answer_image) {
                    ImageHelper::deleteSingleImage(
                        $faq->answer_image,
                        'faq'
                    );
                }

                $timestamp = round(microtime(true) * 1000);
                $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)) . '-' . $timestamp;
                $baseName = ImageHelper::generateFileName($sanitized_title);
                $input['answer_image'] = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('faq_image'),
                    $baseName,
                    'faq'
                );
                Log::info('FAQ image updated: ' . $input['answer_image']);
            }
            $faq->update($input);
            Cache::forget('home_faqs');
            return redirect()->route('manage-faq.index')->with('success', 'FAQ updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating FAQ: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $faq = Faq::findOrFail($id);
            if ($faq->answer_image) {
                ImageHelper::deleteSingleImage(
                    $faq->answer_image,
                    'faq'
                );
            }
            $faq->delete();
            Cache::forget('home_faqs');
            return redirect()->back()->with('success', 'FAQ deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting FAQ: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}
