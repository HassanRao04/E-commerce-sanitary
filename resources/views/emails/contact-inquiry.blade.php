<x-mail::message>
# New Customer Inquiry

A customer submitted a message through the contact form.

**Inquiry ID:** {{ $inquiry->referenceId() }}

**Email:** {{ $inquiry->email }}

**Phone Number:** {{ $inquiry->phone ?: 'Not provided' }}

**Subject:** {{ $inquiry->subject }}

**Message:**

{{ $inquiry->message }}

**Submission Date:** {{ $inquiry->created_at?->timezone(config('app.timezone'))->format('F j, Y \a\t g:i A T') ?? now()->timezone(config('app.timezone'))->format('F j, Y \a\t g:i A T') }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
