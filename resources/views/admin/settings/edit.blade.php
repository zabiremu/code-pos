{{--
    Admin > Settings. Shop details and receipt on the left, outgoing email
    (used for password reset) on the right. Values override .env at runtime
    via AppServiceProvider::applySettings().
--}}
@php
    $v = fn (string $key) => old($key, $values[$key] ?? null);
    $text = fn (string $name, string $type = 'text', array $attrs = []) =>
        '<input id="'.$name.'" type="'.$type.'" name="'.$name.'" value="'.e($v($name)).'" class="w-full input" '
        .collect($attrs)->map(fn ($val, $k) => is_int($k) ? $val : $k.'="'.e($val).'"')->implode(' ').'>';
@endphp
<x-layouts.admin title="Settings">
    <form method="POST" action="{{ route('admin.settings.update') }}"
          x-data="{ mailer: @js($v('mail_mailer')) }">
        @csrf
        @method('PUT')

        <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            {{-- Shop --}}
            <div class="card">
                <div class="card-body !pb-0">
                    <h2 class="text-base font-semibold">Shop</h2>
                    <p class="text-sm text-zinc-500 mt-0.5">Shown on the sign-in page, receipts and emails.</p>
                </div>
                <div class="px-5">
                    @include('admin.settings._row', ['for' => 'shop_name', 'label' => 'Shop name', 'field' => $text('shop_name', 'text', ['required', 'maxlength' => 100])])
                    @include('admin.settings._row', ['for' => 'shop_phone', 'label' => 'Phone', 'field' => $text('shop_phone', 'tel')])
                    @include('admin.settings._row', ['for' => 'shop_email', 'label' => 'Contact email', 'hint' => 'Printed on receipts.', 'field' => $text('shop_email', 'email')])
                    @include('admin.settings._row', ['for' => 'shop_address', 'label' => 'Address', 'field' => '<textarea id="shop_address" name="shop_address" rows="3" class="w-full input">'.e($v('shop_address')).'</textarea>'])
                    @include('admin.settings._row', ['for' => 'currency', 'label' => 'Currency code', 'hint' => '3 letters, e.g. BDT, USD.', 'field' => $text('currency', 'text', ['required', 'maxlength' => 3, 'style' => 'text-transform:uppercase;max-width:8rem'])])
                    @include('admin.settings._row', ['for' => 'default_tax_rate', 'label' => 'Default tax %', 'hint' => 'Used when a product has no tax rate of its own.', 'field' => $text('default_tax_rate', 'number', ['required', 'step' => '0.01', 'min' => 0, 'max' => 100, 'style' => 'max-width:8rem'])])
                    @include('admin.settings._row', [
                        'for' => 'timezone', 'label' => 'Timezone',
                        'hint' => 'Set this once when you start. Changing it later shifts the times shown on older records.',
                        'field' => '<select id="timezone" name="timezone" class="w-full input">'
                            .collect($timezones)->map(fn ($tz) => '<option value="'.e($tz).'"'.($v('timezone') === $tz ? ' selected' : '').'>'.e($tz).'</option>')->implode('')
                            .'</select>',
                    ])
                    @include('admin.settings._row', ['for' => 'receipt_footer', 'label' => 'Receipt footer', 'hint' => 'e.g. "Thank you for shopping with us".', 'field' => '<textarea id="receipt_footer" name="receipt_footer" rows="2" maxlength="300" class="w-full input">'.e($v('receipt_footer')).'</textarea>'])
                </div>
            </div>

            {{-- Email --}}
            <div class="card">
                <div class="card-body !pb-0">
                    <h2 class="text-base font-semibold">Email</h2>
                    <p class="text-sm text-zinc-500 mt-0.5">Used to send password reset links. Use the SMTP details of an email account from your hosting panel.</p>
                </div>
                <div class="px-5">
                    @include('admin.settings._row', [
                        'for' => 'mail_mailer', 'label' => 'Sending',
                        'field' => '<select id="mail_mailer" name="mail_mailer" class="w-full input" x-model="mailer">'
                            .'<option value="smtp"'.($v('mail_mailer') === 'smtp' ? ' selected' : '').'>Send with SMTP</option>'
                            .'<option value="log"'.($v('mail_mailer') !== 'smtp' ? ' selected' : '').'>Don\'t send (write to log file)</option>'
                            .'</select>',
                    ])

                    <div x-show="mailer === 'smtp'">
                        @include('admin.settings._row', ['for' => 'mail_host', 'label' => 'SMTP host', 'hint' => 'e.g. mail.yourdomain.com', 'field' => $text('mail_host')])
                        @include('admin.settings._row', [
                            'for' => 'mail_encryption', 'label' => 'Encryption',
                            'hint' => 'SSL uses port 465, TLS uses 587.',
                            'field' => '<select id="mail_encryption" name="mail_encryption" class="w-full input" '
                                .'x-on:change="const p={ssl:465,tls:587,none:25}[$event.target.value]; if(p) document.getElementById(\'mail_port\').value=p">'
                                .collect(['ssl' => 'SSL', 'tls' => 'TLS (STARTTLS)', 'none' => 'None'])->map(fn ($l, $k) => '<option value="'.$k.'"'.($v('mail_encryption') === $k ? ' selected' : '').'>'.$l.'</option>')->implode('')
                                .'</select>',
                        ])
                        @include('admin.settings._row', ['for' => 'mail_port', 'label' => 'Port', 'field' => $text('mail_port', 'number', ['min' => 1, 'max' => 65535, 'style' => 'max-width:8rem'])])
                        @include('admin.settings._row', ['for' => 'mail_username', 'label' => 'Username', 'hint' => 'Usually the full email address.', 'field' => $text('mail_username', 'text', ['autocomplete' => 'off'])])
                        @include('admin.settings._row', [
                            'for' => 'mail_password', 'label' => 'Password',
                            'hint' => $hasMailPassword ? 'Saved. Leave blank to keep it.' : null,
                            'field' => '<input id="mail_password" type="password" name="mail_password" class="w-full input" autocomplete="new-password" placeholder="'.($hasMailPassword ? '••••••••' : '').'">'
                                .($hasMailPassword ? '<label class="flex items-center gap-2 text-xs text-zinc-500 mt-2"><input type="checkbox" name="clear_mail_password" value="1" class="rounded border-zinc-300"> Remove saved password</label>' : ''),
                        ])
                        @include('admin.settings._row', ['for' => 'mail_from_address', 'label' => 'From address', 'hint' => 'Usually the same as the username.', 'field' => $text('mail_from_address', 'email')])
                        @include('admin.settings._row', ['for' => 'mail_from_name', 'label' => 'From name', 'hint' => 'Leave blank to use the shop name.', 'field' => $text('mail_from_name')])
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <button class="btn-primary">Save settings</button>
        </div>
    </form>

    {{-- Test email: separate form so it uses the saved settings. --}}
    <div class="card mt-6" style="max-width:36rem">
        <div class="card-body">
            <h2 class="text-base font-semibold">Send a test email</h2>
            <p class="text-sm text-zinc-500 mt-0.5">Save your email settings first, then check they work.</p>
            <form method="POST" action="{{ route('admin.settings.test-email') }}" class="mt-4 flex gap-2">
                @csrf
                <input type="email" name="test_to" value="{{ old('test_to', auth()->user()->email) }}" required class="w-full input" aria-label="Send test email to">
                <button class="btn-secondary shrink-0">Send test</button>
            </form>
            @error('test_to')
                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>
    </div>
</x-layouts.admin>
