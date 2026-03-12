@props(['name', 'id' => null, 'value' => '', 'label' => null, 'required' => false])

@if($label)
    <label for="{{ $id ?? $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>
@endif

<select name="{{ $name }}" id="{{ $id ?? $name }}"
    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
    {{ $required ? 'required' : '' }}>
    <option value="">— Bitte wählen —</option>
    <optgroup label="Häufig">
        <option value="CH" {{ $value === 'CH' ? 'selected' : '' }}>Schweiz</option>
        <option value="DE" {{ $value === 'DE' ? 'selected' : '' }}>Deutschland</option>
        <option value="AT" {{ $value === 'AT' ? 'selected' : '' }}>Österreich</option>
        <option value="FR" {{ $value === 'FR' ? 'selected' : '' }}>Frankreich</option>
        <option value="IT" {{ $value === 'IT' ? 'selected' : '' }}>Italien</option>
    </optgroup>
    <optgroup label="Alle Länder">
        @php
            $countries = [
                'AF' => 'Afghanistan', 'EG' => 'Ägypten', 'AL' => 'Albanien', 'DZ' => 'Algerien',
                'AD' => 'Andorra', 'AO' => 'Angola', 'AG' => 'Antigua und Barbuda', 'GQ' => 'Äquatorialguinea',
                'AR' => 'Argentinien', 'AM' => 'Armenien', 'AZ' => 'Aserbaidschan', 'ET' => 'Äthiopien',
                'AU' => 'Australien', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesch',
                'BB' => 'Barbados', 'BE' => 'Belgien', 'BZ' => 'Belize', 'BJ' => 'Benin',
                'BT' => 'Bhutan', 'BO' => 'Bolivien', 'BA' => 'Bosnien und Herzegowina', 'BW' => 'Botswana',
                'BR' => 'Brasilien', 'BN' => 'Brunei', 'BG' => 'Bulgarien', 'BF' => 'Burkina Faso',
                'BI' => 'Burundi', 'CL' => 'Chile', 'CN' => 'China', 'CR' => 'Costa Rica',
                'CI' => 'Côte d\'Ivoire', 'DK' => 'Dänemark', 'DE' => 'Deutschland', 'DM' => 'Dominica',
                'DO' => 'Dominikanische Republik', 'DJ' => 'Dschibuti', 'EC' => 'Ecuador', 'SV' => 'El Salvador',
                'ER' => 'Eritrea', 'EE' => 'Estland', 'SZ' => 'Eswatini', 'FJ' => 'Fidschi',
                'FI' => 'Finnland', 'FR' => 'Frankreich', 'GA' => 'Gabun', 'GM' => 'Gambia',
                'GE' => 'Georgien', 'GH' => 'Ghana', 'GD' => 'Grenada', 'GR' => 'Griechenland',
                'GT' => 'Guatemala', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana',
                'HT' => 'Haiti', 'HN' => 'Honduras', 'IN' => 'Indien', 'ID' => 'Indonesien',
                'IQ' => 'Irak', 'IR' => 'Iran', 'IE' => 'Irland', 'IS' => 'Island',
                'IL' => 'Israel', 'IT' => 'Italien', 'JM' => 'Jamaika', 'JP' => 'Japan',
                'YE' => 'Jemen', 'JO' => 'Jordanien', 'KH' => 'Kambodscha', 'CM' => 'Kamerun',
                'CA' => 'Kanada', 'CV' => 'Kap Verde', 'KZ' => 'Kasachstan', 'QA' => 'Katar',
                'KE' => 'Kenia', 'KG' => 'Kirgisistan', 'KI' => 'Kiribati', 'CO' => 'Kolumbien',
                'KM' => 'Komoren', 'CD' => 'Kongo (Dem. Rep.)', 'CG' => 'Kongo (Rep.)', 'KP' => 'Korea (Nord)',
                'KR' => 'Korea (Süd)', 'XK' => 'Kosovo', 'HR' => 'Kroatien', 'CU' => 'Kuba',
                'KW' => 'Kuwait', 'LA' => 'Laos', 'LS' => 'Lesotho', 'LV' => 'Lettland',
                'LB' => 'Libanon', 'LR' => 'Liberia', 'LY' => 'Libyen', 'LI' => 'Liechtenstein',
                'LT' => 'Litauen', 'LU' => 'Luxemburg', 'MG' => 'Madagaskar', 'MW' => 'Malawi',
                'MY' => 'Malaysia', 'MV' => 'Malediven', 'ML' => 'Mali', 'MT' => 'Malta',
                'MA' => 'Marokko', 'MH' => 'Marshallinseln', 'MR' => 'Mauretanien', 'MU' => 'Mauritius',
                'MX' => 'Mexiko', 'FM' => 'Mikronesien', 'MD' => 'Moldau', 'MC' => 'Monaco',
                'MN' => 'Mongolei', 'ME' => 'Montenegro', 'MZ' => 'Mosambik', 'MM' => 'Myanmar',
                'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NZ' => 'Neuseeland',
                'NI' => 'Nicaragua', 'NL' => 'Niederlande', 'NE' => 'Niger', 'NG' => 'Nigeria',
                'MK' => 'Nordmazedonien', 'NO' => 'Norwegen', 'OM' => 'Oman', 'AT' => 'Österreich',
                'TL' => 'Osttimor', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palästina',
                'PA' => 'Panama', 'PG' => 'Papua-Neuguinea', 'PY' => 'Paraguay', 'PE' => 'Peru',
                'PH' => 'Philippinen', 'PL' => 'Polen', 'PT' => 'Portugal', 'RW' => 'Ruanda',
                'RO' => 'Rumänien', 'RU' => 'Russland', 'SB' => 'Salomonen', 'ZM' => 'Sambia',
                'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'São Tomé und Príncipe', 'SA' => 'Saudi-Arabien',
                'SE' => 'Schweden', 'CH' => 'Schweiz', 'SN' => 'Senegal', 'RS' => 'Serbien',
                'SC' => 'Seychellen', 'SL' => 'Sierra Leone', 'ZW' => 'Simbabwe', 'SG' => 'Singapur',
                'SK' => 'Slowakei', 'SI' => 'Slowenien', 'SO' => 'Somalia', 'ES' => 'Spanien',
                'LK' => 'Sri Lanka', 'KN' => 'St. Kitts und Nevis', 'LC' => 'St. Lucia', 'VC' => 'St. Vincent und die Grenadinen',
                'ZA' => 'Südafrika', 'SD' => 'Sudan', 'SS' => 'Südsudan', 'SR' => 'Suriname',
                'SY' => 'Syrien', 'TJ' => 'Tadschikistan', 'TW' => 'Taiwan', 'TZ' => 'Tansania',
                'TH' => 'Thailand', 'TG' => 'Togo', 'TO' => 'Tonga', 'TT' => 'Trinidad und Tobago',
                'TD' => 'Tschad', 'CZ' => 'Tschechien', 'TN' => 'Tunesien', 'TR' => 'Türkei',
                'TM' => 'Turkmenistan', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine',
                'HU' => 'Ungarn', 'UY' => 'Uruguay', 'UZ' => 'Usbekistan', 'VU' => 'Vanuatu',
                'VA' => 'Vatikanstadt', 'VE' => 'Venezuela', 'AE' => 'Vereinigte Arabische Emirate',
                'US' => 'Vereinigte Staaten', 'GB' => 'Vereinigtes Königreich', 'VN' => 'Vietnam',
                'BY' => 'Weissrussland', 'CF' => 'Zentralafrikanische Republik', 'CY' => 'Zypern',
            ];
            asort($countries);
        @endphp
        @foreach($countries as $code => $countryName)
            <option value="{{ $code }}" {{ $value === $code ? 'selected' : '' }}>{{ $countryName }}</option>
        @endforeach
    </optgroup>
</select>
