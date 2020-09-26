<?php

namespace Amp\Http\Server\StaticContent\Internal;

use Amp\Struct;

/**
 * Used in Amp\Http\Server\StaticContent\DocumentRoot.
 */
final class FileInformation
{
    use Struct;

    /** @var bool */
    public $exists = false;

    /** @var string */
    public $path;

    /** @var int|null */
    public $size;

    /** @var int|null */
    public $mtime;

    /** @var int|null */
    public $inode;

    /** @var string|null */
    public $buffer;

    /** @var string|null */
    public $etag;
}
