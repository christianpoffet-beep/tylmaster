{{--
    Renders contract prose as one block per paragraph.

    dompdf keeps a heading with the block that follows it, and it moves the
    whole block when it does not fit. One block per paragraph therefore means
    only the first paragraph has to fit next to the heading — everything else
    flows on, instead of leaving a third of the page empty.
--}}
@php
    $paragraphs = preg_split('/\n\s*\n/', trim((string) $text));
@endphp
@foreach($paragraphs as $paragraph)
    <div class="terms-content text-para">{{ $paragraph }}</div>
@endforeach
