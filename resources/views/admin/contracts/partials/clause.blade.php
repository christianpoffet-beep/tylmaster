{{--
    A heading plus its prose.

    dompdf ignores "page-break-after: avoid" when the block that follows is
    taller than the remaining space — it then pushes the whole block down and
    leaves a third of the page empty. Gluing the heading to its *first
    paragraph* inside a "page-break-inside: avoid" wrapper gives the same
    protection against an orphaned heading, while the rest of the text keeps
    flowing onto the page it was already on.

    Parameters: $title (?string), $number (?int), $titleClass, $body (string)
--}}
@php
    $paragraphs = preg_split('/\n\s*\n/', trim((string) $body));
    $firstParagraph = array_shift($paragraphs);
@endphp
<div class="keep-together">
    @if($title)
        <div class="{{ $titleClass ?? 'clause-title' }}">@if(!empty($number))<span class="clause-number">{{ $number }}.</span> @endif{{ $title }}</div>
    @endif
    @if($firstParagraph !== null && $firstParagraph !== '')
        <div class="terms-content text-para">{{ $firstParagraph }}</div>
    @endif
</div>
@foreach($paragraphs as $paragraph)
    <div class="terms-content text-para">{{ $paragraph }}</div>
@endforeach
