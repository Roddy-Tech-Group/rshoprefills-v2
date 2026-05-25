<?php

namespace App\Http\Controllers;

use App\Domain\Notification\Services\AdminNotificationService;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Show the Contact page.
     */
    public function index()
    {
        return view('shop.contact');
    }

    /**
     * Store a contact message and notify the admin team.
     */
    public function store(Request $request, AdminNotificationService $adminNotifications)
    {
        // Honeypot: bots fill the hidden "website" field. Accept silently so they
        // do not learn the submission was dropped.
        if (filled($request->input('website'))) {
            return redirect()->route('shop.contact')->with('contact_sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'order_id' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $contact = ContactMessage::create([
            'user_id' => $request->user()?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'message' => $data['message'],
            'ip_address' => $request->ip(),
        ]);

        $adminNotifications->push(
            type: 'contact',
            title: 'New contact message',
            message: $contact->name.': '.($contact->subject ?: 'General enquiry'),
            data: ['contact_message_id' => $contact->id, 'email' => $contact->email],
        );

        return redirect()->route('shop.contact')->with('contact_sent', true);
    }
}
