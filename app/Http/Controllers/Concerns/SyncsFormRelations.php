<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Guards many-to-many syncs against form sections that never rendered.
 *
 * The search fields in this admin (projects, tracks, contacts, credits and so
 * on) build their inputs in the browser. When that does not happen - a script
 * error, or a submit before the page finished setting up - the fields are
 * simply absent from the request. A plain
 *
 *     $model->tracks()->sync($request->input('track_ids', []));
 *
 * then reads the empty default as "the user removed everything" and deletes
 * every link. That is how tracks silently lost their product after an
 * unrelated edit.
 *
 * Each of those sections now submits a marker named after its field, filled in
 * by the same code that renders the inputs. No marker means the section did
 * not report in, and its relation is left alone. A section that did report in
 * still wins - including when the user genuinely emptied it.
 */
trait SyncsFormRelations
{
    /**
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Eloquent\Relations\MorphToMany  $relation
     * @param  string  $field   Request field holding the ids, e.g. "track_ids"
     * @param  mixed   $values  Overrides the request value when the ids need assembling first
     */
    protected function syncSubmitted(Request $request, $relation, string $field, $values = null): void
    {
        if (!$this->sectionSubmitted($request, $field)) {
            return;
        }

        $relation->sync($values ?? $request->input($field, []));
    }

    /**
     * True when at least one of the given fields reported in. Several sections
     * can feed one relation - bands, labels and publishers all end up in a
     * track's organizations.
     */
    protected function sectionSubmitted(Request $request, string ...$fields): bool
    {
        foreach ($fields as $field) {
            if ($request->filled($field . '_submitted')) {
                return true;
            }
        }

        return false;
    }
}
