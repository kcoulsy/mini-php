<?php

declare(strict_types=1);

/**
 * View variable shapes (static analysis reference).
 * Injected by Framework\View::render() via extract().
 *
 * Strings are auto-escaped on output unless the data key is prefixed with unsafe_
 * (e.g. unsafe_content for pre-rendered HTML in the layout).
 *
 * @phpstan-type ItemRow array{
 *     id: int|string,
 *     title: string,
 *     description: string,
 *     created_at: string,
 *     updated_at: string
 * }
 * @phpstan-type FormOld array{title: string, description: string}
 */
