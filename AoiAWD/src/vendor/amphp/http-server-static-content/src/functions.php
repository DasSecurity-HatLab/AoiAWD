<?php

namespace Amp\Http\Server\StaticContent;

/**
 * Normalize paths with relative dot segments in their path.
 *
 * This functionality is critical to avoid malicious URIs attempting to
 * traverse the document root above the allowed base path.
 *
 * @param string $path
 *
 * @return string
 */
function removeDotPathSegments(string $path): string
{
    // IMPORTANT! Do NOT remove this. If this is left in, we'll be able to use /path\..\../outsideDocRoot defeating
    // the removeDotPathSegments() function! (on Windows at least)
    $path = \str_replace("\\", "/", $path);

    if (strpos($path, '/.') === false) {
        return $path;
    }

    $inputBuffer = $path;
    $outputStack = [];

    // 2.  While the input buffer is not empty, loop as follows:
    while ($inputBuffer !== '') {
        // A.  If the input buffer begins with a prefix of "../" or "./",
        //     then remove that prefix from the input buffer; otherwise,
        if (strpos($inputBuffer, "./") === 0) {
            $inputBuffer = substr($inputBuffer, 2);
            continue;
        }
        if (strpos($inputBuffer, "../") === 0) {
            $inputBuffer = substr($inputBuffer, 3);
            continue;
        }

        // B.  if the input buffer begins with a prefix of "/./" or "/.",
        //     where "." is a complete path segment, then replace that
        //     prefix with "/" in the input buffer; otherwise,
        if ($inputBuffer === "/.") {
            $outputStack[] = '/';
            break;
        }
        if (substr($inputBuffer, 0, 3) === "/./") {
            $inputBuffer = substr($inputBuffer, 2);
            continue;
        }

        // C.  if the input buffer begins with a prefix of "/../" or "/..",
        //     where ".." is a complete path segment, then replace that
        //     prefix with "/" in the input buffer and remove the last
        //     segment and its preceding "/" (if any) from the output
        //     buffer; otherwise,
        if ($inputBuffer === "/..") {
            array_pop($outputStack);
            $outputStack[] = '/';
            break;
        }
        if (substr($inputBuffer, 0, 4) === "/../") {
            while (array_pop($outputStack) === "/");
            $inputBuffer = substr($inputBuffer, 3);
            continue;
        }

        // D.  if the input buffer consists only of "." or "..", then remove
        //     that from the input buffer; otherwise,
        if ($inputBuffer === '.' || $inputBuffer === '..') {
            break;
        }

        // E.  move the first path segment in the input buffer to the end of
        //     the output buffer, including the initial "/" character (if
        //     any) and any subsequent characters up to, but not including,
        //     the next "/" character or the end of the input buffer.
        if (($slashPos = \strpos($inputBuffer, '/', 1)) === false) {
            $outputStack[] = $inputBuffer;
            break;
        }
        $outputStack[] = substr($inputBuffer, 0, $slashPos);
        $inputBuffer = substr($inputBuffer, $slashPos);
    }

    return implode($outputStack);
}
