<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WorkingHoursResource;
use App\Models\Faq;
use App\Models\About;
use App\Models\Terms;
use App\Models\Banner;
use App\Models\AppPopup;
use App\Models\Contact;
use App\Models\Privacy;
use App\Models\Setting;
use App\Helpers\ApiResponse;
use App\Models\WorkingHour;
use Illuminate\Http\Request;
use App\Http\Resources\FaqResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Http\Resources\TermsResource;
use App\Http\Resources\BannerResource;
use App\Http\Resources\AppPopupResource;
use App\Http\Resources\PrivacyResource;
use App\Http\Resources\SettingsResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function index(){
        $settings = Setting::first();
        if (!$settings) {
            return ApiResponse::sendResponse(200, __('front.somthing-went-wrong'), []);
        }
        return ApiResponse::sendResponse(200, __('front.Settings-retrieved-successfully'), SettingsResource::collection([$settings]));
    }


    public function about()
    {
        $about = About::first();
        if (!$about) {
            return ApiResponse::sendResponse(200, __('dashboard.somthing-went-wrong'), []);
        }
        return ApiResponse::sendResponse(200, __('dashboard.about-retrieved-successfully'), new AboutResource($about));
    }

    public function privacy()
    {
        $privacy = Privacy::first();
        if (!$privacy) {
            return ApiResponse::sendResponse(200, __('dashboard.somthing-went-wrong'), []);
        }
        return ApiResponse::sendResponse(200, __('dashboard.privacy-retrieved-successfully'), new PrivacyResource($privacy));
    }

    public function terms()
    {
        $terms = Terms::first();
        if (!$terms) {
            return ApiResponse::sendResponse(200, __('dashboard.somthing-went-wrong'), []);
        }
        return ApiResponse::sendResponse(200, __('dashboard.privacy-retrieved-successfully'), new TermsResource($terms));
    }

    public function faq()
    {
        $faq = Faq::where('status', 1)->paginate(10);
        if (!$faq) {
            return ApiResponse::sendResponse(200, __('dashboard.somthing-went-wrong'), []);
        }
        return ApiResponse::sendResponse(200, __('dashboard.faq-retrieved-successfully'), FaqResource::collection($faq),             [
            'total'        => $faq->total(),
            'current_page' => $faq->currentPage(),
            'last_page'    => $faq->lastPage(),
            'per_page'     => $faq->perPage(),
        ]);
    }
    public function workingHours()
    {
        $workingHours = WorkingHour::all();
        if (!$workingHours) {
            return ApiResponse::sendResponse(200, __('dashboard.somthing-went-wrong'), []);
        }
        return ApiResponse::sendResponse(200, __('dashboard.workingHours-retrieved-successfully'), WorkingHoursResource::collection($workingHours));
    }
    public function contact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return ApiResponse::sendResponse(404, __('validation.something-valid'), $validator->errors());
        }

        Contact::create([
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return ApiResponse::sendResponse(201, __('front.contact-send-successfully'), []);
    }

    public function banners()
    {
        $banners = Banner::where('status', 1)->get();
        if ($banners->isEmpty()) {
            return ApiResponse::sendResponse(404, __('front.no-banners-found'), []);
        }
        return ApiResponse::sendResponse(200, __('front.retrieved-successfully'), BannerResource::collection($banners));
    }

    public function activePopup()
    {
        $popup = AppPopup::query()
            ->active()
            ->currentlyRunning()
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->latest('id')
            ->first();

        if (! $popup) {
            return ApiResponse::sendResponse(404, __('front.no-active-popup-found'), []);
        }

        return ApiResponse::sendResponse(200, __('front.retrieved-successfully'), new AppPopupResource($popup));
    }

}
