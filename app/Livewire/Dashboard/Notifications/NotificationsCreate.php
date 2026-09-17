<?php

namespace App\Livewire\Dashboard\Notifications;

use App\Livewire\Dashboard\Notifications\NotificationsData;
use App\Models\AdminNotification;
use App\Models\Faq;
use App\Models\Notification;
use App\Models\User;
use App\Services\Api\Commerce\FirebaseService;
use App\Services\Dashboard\OrderService;
use Livewire\Component;
use CodeZero\UniqueTranslation\UniqueTranslationRule;

class NotificationsCreate extends Component
{
    public $title_ar, $title_en, $message_ar, $message_en;
    public $type = 'all';
    public $selected_users = [];
    public $search = '';

    public function updatedType($value)
    {
        if ($value == 'all') {
            $this->selected_users = [];
            $this->search = '';
        }
    }

    public function rules()
    {
        return [
            'title_ar' => [
                'required',
                'string',
                'min:4',
                'max:200',
                UniqueTranslationRule::for('notifications', 'title')
            ],
            'title_en' => [
                'required',
                'string',
                'min:4',
                'max:200',
                UniqueTranslationRule::for('notifications', 'title')
            ],

            'message_ar'          => 'required|string|min:4|',
            'message_en'          => 'required|string|min:4|',
            'type'                => 'required|in:all,specific',
            'selected_users'      => $this->type == 'specific' ? 'required|array|min:1' : 'nullable',
        ];
    }

    public function submit(FirebaseService $firebaseService)
    {
        $data = $this->validate();
        // save data in DB
        $data['title']  = [
            'ar' => $this->title_ar,
            'en' => $this->title_en,
        ];
        $data['message']   = [
            'ar' => $this->message_ar,
            'en' => $this->message_en,
        ];

        if ($this->type == 'all') {
            Notification::create($data);

            $tokens = User::query()
                ->where('status', 1)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->all();

            $firebaseService->sendMulticast($tokens, $data['title']['ar'], $data['message']['ar']);
        } else {
            $users = User::whereIn('id', $this->selected_users)->get();
            foreach ($users as $user) {
                $user->appNotifications()->create($data);
                if ($user->fcm_token) {
                    $firebaseService->sendNotification($user->fcm_token, $data['title']['ar'], $data['message']['ar']);
                }
            }
        }

        $this->reset('title_ar', 'title_en', 'message_ar', 'message_en', 'type', 'selected_users', 'search');
        $this->dispatch('notificationAddMS');
        //hide modal
        $this->dispatch('createModalToggle');
        //refresh blog data in component
        $this->dispatch('refreshData')->to(NotificationsData::class);
    }
    public function render()
    {
        $users = [];
        if ($this->type == 'specific') {
            $users = User::query()
                ->whereNotNull('fcm_token')
                ->where('status', 1)
                ->when($this->search, function ($q) {
                    $q->where(function($query) {
                        $query->where('name', 'like', '%' . $this->search . '%')
                              ->orWhere('phone', 'like', '%' . $this->search . '%');
                    });
                })
                ->limit(20)
                ->get();
        }

        return view('dashboard.notifications.notifications-create', [
            'users' => $users
        ]);
    }
}
