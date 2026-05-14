{{--
    Country & language modal — custom Alpine dropdowns with search.
    State lives on the wrapper x-data: localeModalOpen, country, countryFlag, language.
--}}
@php
    $countries = [
        'Afghanistan' => '🇦🇫', 'Albania' => '🇦🇱', 'Algeria' => '🇩🇿', 'Andorra' => '🇦🇩', 'Angola' => '🇦🇴', 'Antigua and Barbuda' => '🇦🇬',
        'Argentina' => '🇦🇷', 'Armenia' => '🇦🇲', 'Australia' => '🇦🇺', 'Austria' => '🇦🇹', 'Azerbaijan' => '🇦🇿', 'Bahamas' => '🇧🇸',
        'Bahrain' => '🇧🇭', 'Bangladesh' => '🇧🇩', 'Barbados' => '🇧🇧', 'Belarus' => '🇧🇾', 'Belgium' => '🇧🇪', 'Belize' => '🇧🇿',
        'Benin' => '🇧🇯', 'Bhutan' => '🇧🇹', 'Bolivia' => '🇧🇴', 'Bosnia and Herzegovina' => '🇧🇦', 'Botswana' => '🇧🇼', 'Brazil' => '🇧🇷',
        'Brunei' => '🇧🇳', 'Bulgaria' => '🇧🇬', 'Burkina Faso' => '🇧🇫', 'Burundi' => '🇧🇮', 'Cambodia' => '🇰🇭', 'Cameroon' => '🇨🇲',
        'Canada' => '🇨🇦', 'Cape Verde' => '🇨🇻', 'Central African Republic' => '🇨🇫', 'Chad' => '🇹🇩', 'Chile' => '🇨🇱', 'China' => '🇨🇳',
        'Colombia' => '🇨🇴', 'Comoros' => '🇰🇲', 'Congo (Brazzaville)' => '🇨🇬', 'Congo (Kinshasa)' => '🇨🇩', 'Costa Rica' => '🇨🇷', 'Côte d\'Ivoire' => '🇨🇮',
        'Croatia' => '🇭🇷', 'Cuba' => '🇨🇺', 'Cyprus' => '🇨🇾', 'Czechia' => '🇨🇿', 'Denmark' => '🇩🇰', 'Djibouti' => '🇩🇯',
        'Dominica' => '🇩🇲', 'Dominican Republic' => '🇩🇴', 'Ecuador' => '🇪🇨', 'Egypt' => '🇪🇬', 'El Salvador' => '🇸🇻', 'Equatorial Guinea' => '🇬🇶',
        'Eritrea' => '🇪🇷', 'Estonia' => '🇪🇪', 'Eswatini' => '🇸🇿', 'Ethiopia' => '🇪🇹', 'Fiji' => '🇫🇯', 'Finland' => '🇫🇮',
        'France' => '🇫🇷', 'Gabon' => '🇬🇦', 'Gambia' => '🇬🇲', 'Georgia' => '🇬🇪', 'Germany' => '🇩🇪', 'Ghana' => '🇬🇭',
        'Greece' => '🇬🇷', 'Grenada' => '🇬🇩', 'Guatemala' => '🇬🇹', 'Guinea' => '🇬🇳', 'Guinea-Bissau' => '🇬🇼', 'Guyana' => '🇬🇾',
        'Haiti' => '🇭🇹', 'Honduras' => '🇭🇳', 'Hungary' => '🇭🇺', 'Iceland' => '🇮🇸', 'India' => '🇮🇳', 'Indonesia' => '🇮🇩',
        'Iran' => '🇮🇷', 'Iraq' => '🇮🇶', 'Ireland' => '🇮🇪', 'Israel' => '🇮🇱', 'Italy' => '🇮🇹', 'Jamaica' => '🇯🇲',
        'Japan' => '🇯🇵', 'Jordan' => '🇯🇴', 'Kazakhstan' => '🇰🇿', 'Kenya' => '🇰🇪', 'Kiribati' => '🇰🇮', 'Kuwait' => '🇰🇼',
        'Kyrgyzstan' => '🇰🇬', 'Laos' => '🇱🇦', 'Latvia' => '🇱🇻', 'Lebanon' => '🇱🇧', 'Lesotho' => '🇱🇸', 'Liberia' => '🇱🇷',
        'Libya' => '🇱🇾', 'Liechtenstein' => '🇱🇮', 'Lithuania' => '🇱🇹', 'Luxembourg' => '🇱🇺', 'Madagascar' => '🇲🇬', 'Malawi' => '🇲🇼',
        'Malaysia' => '🇲🇾', 'Maldives' => '🇲🇻', 'Mali' => '🇲🇱', 'Malta' => '🇲🇹', 'Marshall Islands' => '🇲🇭', 'Mauritania' => '🇲🇷',
        'Mauritius' => '🇲🇺', 'Mexico' => '🇲🇽', 'Micronesia' => '🇫🇲', 'Moldova' => '🇲🇩', 'Monaco' => '🇲🇨', 'Mongolia' => '🇲🇳',
        'Montenegro' => '🇲🇪', 'Morocco' => '🇲🇦', 'Mozambique' => '🇲🇿', 'Myanmar' => '🇲🇲', 'Namibia' => '🇳🇦', 'Nauru' => '🇳🇷',
        'Nepal' => '🇳🇵', 'Netherlands' => '🇳🇱', 'New Zealand' => '🇳🇿', 'Nicaragua' => '🇳🇮', 'Niger' => '🇳🇪', 'Nigeria' => '🇳🇬',
        'North Korea' => '🇰🇵', 'North Macedonia' => '🇲🇰', 'Norway' => '🇳🇴', 'Oman' => '🇴🇲', 'Pakistan' => '🇵🇰', 'Palau' => '🇵🇼',
        'Palestine' => '🇵🇸', 'Panama' => '🇵🇦', 'Papua New Guinea' => '🇵🇬', 'Paraguay' => '🇵🇾', 'Peru' => '🇵🇪', 'Philippines' => '🇵🇭',
        'Poland' => '🇵🇱', 'Portugal' => '🇵🇹', 'Qatar' => '🇶🇦', 'Romania' => '🇷🇴', 'Russia' => '🇷🇺', 'Rwanda' => '🇷🇼',
        'Saint Kitts and Nevis' => '🇰🇳', 'Saint Lucia' => '🇱🇨', 'Saint Vincent and the Grenadines' => '🇻🇨', 'Samoa' => '🇼🇸', 'San Marino' => '🇸🇲', 'São Tomé and Príncipe' => '🇸🇹',
        'Saudi Arabia' => '🇸🇦', 'Senegal' => '🇸🇳', 'Serbia' => '🇷🇸', 'Seychelles' => '🇸🇨', 'Sierra Leone' => '🇸🇱', 'Singapore' => '🇸🇬',
        'Slovakia' => '🇸🇰', 'Slovenia' => '🇸🇮', 'Solomon Islands' => '🇸🇧', 'Somalia' => '🇸🇴', 'South Africa' => '🇿🇦', 'South Korea' => '🇰🇷',
        'South Sudan' => '🇸🇸', 'Spain' => '🇪🇸', 'Sri Lanka' => '🇱🇰', 'Sudan' => '🇸🇩', 'Suriname' => '🇸🇷', 'Sweden' => '🇸🇪',
        'Switzerland' => '🇨🇭', 'Syria' => '🇸🇾', 'Taiwan' => '🇹🇼', 'Tajikistan' => '🇹🇯', 'Tanzania' => '🇹🇿', 'Thailand' => '🇹🇭',
        'Timor-Leste' => '🇹🇱', 'Togo' => '🇹🇬', 'Tonga' => '🇹🇴', 'Trinidad and Tobago' => '🇹🇹', 'Tunisia' => '🇹🇳', 'Turkey' => '🇹🇷',
        'Turkmenistan' => '🇹🇲', 'Tuvalu' => '🇹🇻', 'Uganda' => '🇺🇬', 'Ukraine' => '🇺🇦', 'United Arab Emirates' => '🇦🇪', 'United Kingdom' => '🇬🇧',
        'United States' => '🇺🇸', 'Uruguay' => '🇺🇾', 'Uzbekistan' => '🇺🇿', 'Vanuatu' => '🇻🇺', 'Vatican City' => '🇻🇦', 'Venezuela' => '🇻🇪',
        'Vietnam' => '🇻🇳', 'Yemen' => '🇾🇪', 'Zambia' => '🇿🇲', 'Zimbabwe' => '🇿🇼',
    ];
    $languages = [
        'English', 'Mandarin Chinese', 'Hindi', 'Spanish', 'French', 'Standard Arabic', 'Bengali', 'Russian', 'Portuguese', 'Indonesian',
        'Urdu', 'German', 'Japanese', 'Swahili', 'Marathi', 'Telugu', 'Turkish', 'Tamil', 'Cantonese', 'Vietnamese',
        'Korean', 'Italian', 'Hausa', 'Thai', 'Gujarati', 'Persian', 'Polish', 'Pashto', 'Kannada', 'Malayalam',
        'Sundanese', 'Hebrew', 'Burmese', 'Amharic', 'Oromo', 'Yoruba', 'Igbo', 'Ukrainian', 'Dutch', 'Romanian',
        'Filipino', 'Greek', 'Czech', 'Swedish', 'Hungarian', 'Serbian', 'Croatian', 'Bulgarian', 'Danish', 'Finnish',
        'Norwegian', 'Slovak', 'Afrikaans', 'Albanian', 'Armenian', 'Azerbaijani', 'Basque', 'Belarusian', 'Bosnian', 'Catalan',
        'Estonian', 'Galician', 'Georgian', 'Hawaiian', 'Icelandic', 'Irish', 'Kazakh', 'Khmer', 'Lao', 'Latvian',
        'Lithuanian', 'Macedonian', 'Malay', 'Maltese', 'Maori', 'Mongolian', 'Nepali', 'Punjabi', 'Sinhala', 'Slovenian',
        'Somali', 'Tajik', 'Turkmen', 'Uzbek', 'Welsh', 'Xhosa', 'Zulu', 'Esperanto',
    ];
@endphp

{{-- Backdrop — sits BELOW the nav (z-40 < nav z-50) so the nav's glassmorphism stays visible over it --}}
<div
    x-show="localeModalOpen"
    x-transition:enter="transition-opacity ease-out duration-500"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-500"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="localeModalOpen = false"
    style="display:none;"
    class="fixed inset-0 z-40 bg-zinc-900/40"
></div>

{{-- Card container — sits ABOVE the nav (z-[60] > nav z-50) so the modal floats on top --}}
<div
    x-show="localeModalOpen"
    x-transition:enter="transition-opacity ease-out duration-500"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-500"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display:none;"
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 pointer-events-none"
    role="dialog"
    aria-modal="true"
    aria-labelledby="locale-modal-title"
>
    {{-- Card --}}
    <div class="pointer-events-auto relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl shadow-zinc-900/25 px-[15px] pt-[30px] pb-[30px]">
        {{-- Close button (positioned outside the card's top-right corner) --}}
        <button
            type="button"
            @click="localeModalOpen = false"
            class="absolute -top-3 -right-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white text-zinc-700 shadow-lg shadow-zinc-900/20 transition-colors duration-150 hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
            aria-label="Close"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Header --}}
        <h2 id="locale-modal-title" class="mb-5 text-lg font-bold text-zinc-900">Country and language</h2>

        {{-- Body --}}
        <div class="flex flex-col gap-6">

            {{-- Country picker --}}
            <div
                x-data="{ open: false, search: '', options: @js($countries) }"
                @click.outside="open = false"
                class="relative"
            >
                <label class="mb-1.5 block text-[13px] font-medium text-zinc-500">Country</label>

                <button
                    type="button"
                    @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
                    :aria-expanded="open.toString()"
                    aria-haspopup="listbox"
                    :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-300 hover:border-zinc-400'"
                    class="flex w-full items-center gap-2 rounded-lg border bg-white px-3 py-2.5 text-base font-medium text-zinc-900 outline-none transition-colors"
                >
                    <span class="text-base leading-none" x-text="countryFlag">🇨🇲</span>
                    <span class="flex-1 text-left" x-text="country">Cameroon</span>
                    <svg class="h-4 w-4 text-zinc-400 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    style="display:none;"
                    class="absolute left-0 right-0 top-full z-20 mt-2 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-xl shadow-zinc-900/10"
                    role="listbox"
                >
                    {{-- Search --}}
                    <div class="border-b border-zinc-100 p-2">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                x-ref="search"
                                x-model="search"
                                type="text"
                                placeholder="Search countries"
                                aria-label="Search countries"
                                class="w-full rounded-md border border-zinc-200 bg-zinc-50 py-2 pl-8 pr-3 text-base text-zinc-800 placeholder:text-zinc-400 outline-none transition-colors focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/15"
                            />
                        </div>
                    </div>

                    {{-- Options --}}
                    <div class="max-h-64 overflow-y-auto p-1">
                        <template x-for="[name, flag] in Object.entries(options).filter(([n]) => n.toLowerCase().includes(search.toLowerCase()))" :key="name">
                            <button
                                type="button"
                                role="option"
                                :aria-selected="country === name ? 'true' : 'false'"
                                @click="country = name; countryFlag = flag; open = false; search = ''"
                                :class="country === name ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-zinc-50'"
                                class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-base font-medium transition-colors"
                            >
                                <span class="text-base leading-none" x-text="flag"></span>
                                <span class="flex-1" x-text="name"></span>
                                <svg x-show="country === name" class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>
                        </template>
                        <div
                            x-show="Object.entries(options).filter(([n]) => n.toLowerCase().includes(search.toLowerCase())).length === 0"
                            class="px-3 py-6 text-center text-base text-zinc-400"
                        >
                            No matches
                        </div>
                    </div>
                </div>
            </div>

            {{-- Language picker --}}
            <div
                x-data="{ open: false, search: '', options: @js($languages) }"
                @click.outside="open = false"
                class="relative"
            >
                <label class="mb-1.5 flex items-center gap-1.5 text-[13px] font-medium text-zinc-500">
                    <img src="{{ asset('assets/' . rawurlencode('global svg.svg')) }}" alt="" class="h-3.5 w-3.5 opacity-70" />
                    Language
                </label>

                <button
                    type="button"
                    @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
                    :aria-expanded="open.toString()"
                    aria-haspopup="listbox"
                    :class="open ? 'border-blue-500 ring-2 ring-blue-500/15' : 'border-zinc-300 hover:border-zinc-400'"
                    class="flex w-full items-center gap-2 rounded-lg border bg-white px-3 py-2.5 text-base font-medium text-zinc-900 outline-none transition-colors"
                >
                    <span class="flex-1 text-left" x-text="language">English</span>
                    <svg class="h-4 w-4 text-zinc-400 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    style="display:none;"
                    class="absolute left-0 right-0 top-full z-20 mt-2 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-xl shadow-zinc-900/10"
                    role="listbox"
                >
                    {{-- Search --}}
                    <div class="border-b border-zinc-100 p-2">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                x-ref="search"
                                x-model="search"
                                type="text"
                                placeholder="Search languages"
                                aria-label="Search languages"
                                class="w-full rounded-md border border-zinc-200 bg-zinc-50 py-2 pl-8 pr-3 text-base text-zinc-800 placeholder:text-zinc-400 outline-none transition-colors focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/15"
                            />
                        </div>
                    </div>

                    {{-- Options --}}
                    <div class="max-h-64 overflow-y-auto p-1">
                        <template x-for="lang in options.filter(l => l.toLowerCase().includes(search.toLowerCase()))" :key="lang">
                            <button
                                type="button"
                                role="option"
                                :aria-selected="language === lang ? 'true' : 'false'"
                                @click="language = lang; open = false; search = ''"
                                :class="language === lang ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-zinc-50'"
                                class="flex w-full items-center rounded-md px-3 py-2 text-left text-base font-medium transition-colors"
                            >
                                <span class="flex-1" x-text="lang"></span>
                                <svg x-show="language === lang" class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>
                        </template>
                        <div
                            x-show="options.filter(l => l.toLowerCase().includes(search.toLowerCase())).length === 0"
                            class="px-3 py-6 text-center text-base text-zinc-400"
                        >
                            No matches
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Save --}}
        <button
            type="button"
            @click="localeModalOpen = false"
            class="mt-7 w-full rounded-lg bg-blue-600 px-4 py-3 text-base font-semibold text-white transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50"
        >
            Save
        </button>
    </div>
</div>
