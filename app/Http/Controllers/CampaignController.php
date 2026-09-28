<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use App\Models\Recipient;
use App\Models\RecipientGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        return view('campaigns.index', ['campaigns' => Campaign::latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('campaigns.create', [
            'recipients' => Recipient::where('status', 'active')->orderBy('name')->get(),
            'groups' => RecipientGroup::orderBy('name')->get(),
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $recipientIds = collect($data['recipients'] ?? []);
        if (! empty($data['group_id'])) {
            $recipientIds = $recipientIds->merge(RecipientGroup::findOrFail($data['group_id'])->recipients()->where('status', 'active')->pluck('recipients.id'));
        }
        $recipients = Recipient::whereIn('id', $recipientIds->unique())->where('status', 'active')->get();
        if ($data['action'] === 'send' && $recipients->isEmpty()) {
            return back()->withErrors(['recipients' => 'Please select at least one active recipient.'])->withInput();
        }
        $campaign = DB::transaction(function () use ($data, $request, $recipients): Campaign {
            $campaign = Campaign::create([
                'campaign_name' => $data['campaign_name'], 'subject' => $data['subject'] ?? '', 'message' => $data['message'] ?? '',
                'attachment_path' => $request->file('attachment')?->store('attachments'), 'status' => $data['action'] === 'send' ? 'pending' : 'draft', 'created_by' => $request->user('admin')->id,
            ]);
            foreach ($recipients as $recipient) {
                $campaign->recipients()->create(['recipient_id' => $recipient->id, 'email' => $recipient->email]);
            }
            $campaign->update(['total_recipients' => $recipients->count(), 'total_pending' => $data['action'] === 'send' ? $recipients->count() : 0]);

            return $campaign;
        });

        return redirect()->route('campaigns.index')->with('success', $data['action'] === 'send' ? 'Campaign saved and queued for delivery.' : 'Draft saved successfully.');
    }
}
