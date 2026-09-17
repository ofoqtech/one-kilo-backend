<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\About;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function genralSetting()
    {
        $settings = Setting::first();
        return view('dashboard.settings.index', compact('settings'));
    } //End genralSetting method


    public function aboutSetting()
    {
        $about = About::first();
        return view('dashboard.settings.about.index', compact('about'));
    } //End genralSetting method


    public function faqs()
    {
        return view('dashboard.settings.faqs.index');
    } //End faqs method

    public function notifications()
    {
        return view('dashboard.notifications.index');
    } //End notifications method

    public function workingHours()
    {
        return view('dashboard.settings.workingHours.index');
    } //End workingHours method

    public function privacy()
    {
        return view('dashboard.settings.privacy.index');
    }

    public function terms()
    {
        return view('dashboard.settings.terms.index');
    }

    public function banners()
    {
        return view('dashboard.settings.banners.index');
    }

    public function popups()
    {
        return view('dashboard.settings.popups.index');
    }

    public function contacts()
    {
        return view('dashboard.contacts.index');
    }

}
