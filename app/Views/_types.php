<?php

declare(strict_types=1);

/**
 * View variable shapes (static analysis reference).
 * Injected by Framework\View::render() via extract().
 *
 * Strings are auto-escaped on output unless the data key is prefixed with unsafe_
 * (e.g. unsafe_content for pre-rendered HTML in the layout, unsafe_scripts for
 * scripts queued via View::script() and emitted before </body>).
 *
 * @phpstan-import-type ItemRow from App\Models\Item
 * @phpstan-import-type ItemAttachmentRow from App\Models\ItemAttachment
 * @phpstan-import-type UserRow from App\Models\User
 * @phpstan-type FormOld array{title: string, description: string}
 */
