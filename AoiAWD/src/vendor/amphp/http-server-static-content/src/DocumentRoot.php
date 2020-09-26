<?php

namespace Amp\Http\Server\StaticContent;

use Amp\ByteStream\InMemoryStream;
use Amp\ByteStream\InputStream;
use Amp\ByteStream\IteratorStream;
use Amp\CallableMaker;
use Amp\Coroutine;
use Amp\File;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Server;
use Amp\Http\Server\ServerObserver;
use Amp\Http\Status;
use Amp\Producer;
use Amp\Promise;
use Amp\Success;

final class DocumentRoot implements RequestHandler, ServerObserver
{
    use CallableMaker;

    /** @var string Default mime file path. */
    const DEFAULT_MIME_TYPE_FILE = __DIR__ . "/../resources/mime";

    /** @internal */
    const READ_CHUNK_SIZE = 8192;

    /** @internal */
    const PRECONDITION_NOT_MODIFIED = 1;

    /** @internal */
    const PRECONDITION_FAILED = 2;

    /** @internal */
    const PRECONDITION_IF_RANGE_OK = 3;

    /** @internal */
    const PRECONDITION_IF_RANGE_FAILED = 4;

    /** @internal */
    const PRECONDITION_OK = 5;

    /** @var bool */
    private $running = false;

    /** @var ErrorHandler */
    private $errorHandler;

    /** @var RequestHandler|null */
    private $fallback;

    private $root;
    private $debug;
    private $filesystem;
    private $multipartBoundary;
    private $cache = [];
    private $cacheTimeouts = [];
    private $now;

    private $mimeTypes = [];
    private $mimeFileTypes = [];
    private $indexes = ["index.html", "index.htm"];
    private $useEtagInode = true;
    private $expiresPeriod = 86400 * 7;
    private $defaultMimeType = "text/plain";
    private $defaultCharset = "utf-8";
    private $useAggressiveCacheHeaders = false;
    private $aggressiveCacheMultiplier = 0.9;
    private $cacheEntryTtl = 10;
    private $cacheEntryCount = 0;
    private $cacheEntryLimit = 2048;
    private $bufferedFileCount = 0;
    private $bufferedFileLimit = 50;
    private $bufferedFileSizeLimit = 524288;

    /**
     * @param string      $root Document root
     * @param File\Driver $filesystem Optional filesystem driver
     *
     * @throws \Error On invalid root path
     */
    public function __construct(string $root, File\Driver $filesystem = null)
    {
        $root = \str_replace("\\", "/", $root);


        if (\strncmp($root, "phar://", 7) !== 0) {
            if (!(\is_readable($root) && \is_dir($root))) {
                throw new \Error(
                    "Document root requires a readable directory"
                );
            }
            $root = \realpath($root);
        } else {
            if (!(\is_readable($root))) {
                throw new \Error(
                    "Document root requires a readable directory"
                );
            }
        }

        $this->root = \rtrim($root, "/");
        $this->filesystem = $filesystem ?: File\filesystem();
        $this->multipartBoundary = \strtr(\base64_encode(\random_bytes(16)), '+/', '-_');
    }

    /**
     * Removes expired file information from the cache and updates the current 'now' value.
     *
     * @param int $now
     */
    private function clearExpiredCacheEntries(int $now)
    {
        $this->now = $now;

        foreach ($this->cacheTimeouts as $path => $timeout) {
            if ($now <= $timeout) {
                break;
            }

            $fileInfo = $this->cache[$path];

            unset($this->cache[$path],
            $this->cacheTimeouts[$path]);

            $this->bufferedFileCount -= isset($fileInfo->buffer);
            $this->cacheEntryCount--;
        }
    }

    /**
     * Specifies an instance of RequestHandler that is used if no file exists for the requested path.
     * If no fallback is given, a 404 response is returned from respond() when the file does not exist.
     *
     * @param RequestHandler $requestHandler
     *
     * @throws \Error If the server has started.
     */
    public function setFallback(RequestHandler $requestHandler)
    {
        if ($this->running) {
            throw new \Error("Cannot add fallback request handler after the server has started");
        }

        $this->fallback = $requestHandler;
    }

    /**
     * Respond to HTTP requests for filesystem resources.
     *
     * @param Request $request Request to handle.
     *
     * @return Promise
     */
    public function handleRequest(Request $request): Promise
    {
        $path = removeDotPathSegments($request->getUri()->getPath());

        return new Coroutine(
            ($fileInfo = $this->fetchCachedStat($path, $request))
                ? $this->respondFromFileInfo($fileInfo, $request)
                : $this->respondWithLookup($this->root . $path, $path, $request)
        );
    }

    private function fetchCachedStat(string $reqPath, Request $request)
    {
        // We specifically allow users to bypass cached representations by using their browser's "force refresh"
        // functionality. This lets us avoid the annoyance of stale file representations being served for a few seconds
        // after changes have been written to disk.
        if ($this->debug) {
            return null;
        }

        foreach ($request->getHeaderArray("Cache-Control") as $value) {
            if (strcasecmp($value, "no-cache") === 0) {
                return null;
            }
        }

        foreach ($request->getHeaderArray("Pragma") as $value) {
            if (strcasecmp($value, "no-cache") === 0) {
                return null;
            }
        }

        return $this->cache[$reqPath] ?? null;
    }

    private function shouldBufferContent(Internal\FileInformation $fileInfo): bool
    {
        if ($fileInfo->size > $this->bufferedFileSizeLimit) {
            return false;
        }

        if ($this->bufferedFileCount >= $this->bufferedFileLimit) {
            return false;
        }

        if ($this->cacheEntryCount >= $this->cacheEntryLimit) {
            return false;
        }

        return true;
    }

    private function respondWithLookup(string $realPath, string $reqPath, Request $request): \Generator
    {
        // We don't catch any potential exceptions from this yield because they represent
        // a legitimate error from some sort of disk failure. Just let them bubble up to
        // the server where they'll turn into a 500 response.
        $fileInfo = yield from $this->lookup($realPath);

        // Specifically use the request path to reference this file in the
        // cache because the file entry path may differ if it's reflecting
        // a directory index file.
        if ($this->cacheEntryCount < $this->cacheEntryLimit) {
            $this->cacheEntryCount++;
            $this->cache[$reqPath] = $fileInfo;
            $this->cacheTimeouts[$reqPath] = $this->now + $this->cacheEntryTtl;
        }

        return yield from $this->respondFromFileInfo($fileInfo, $request);
    }

    private function lookup(string $path): \Generator
    {
        $fileInfo = new Internal\FileInformation;

        $fileInfo->exists = false;
        $fileInfo->path = $path;

        File\StatCache::clear($path);
        if (!$stat = yield $this->filesystem->stat($path)) {
            return $fileInfo;
        }
        if (yield $this->filesystem->isdir($path)) {
            if ($indexPathArr = yield from $this->coalesceIndexPath($path)) {
                list($fileInfo->path, $stat) = $indexPathArr;
            } else {
                return $fileInfo;
            }
        }

        $fileInfo->exists = true;
        $fileInfo->size = (int)$stat["size"];
        $fileInfo->mtime = $stat["mtime"] ?? 0;
        $fileInfo->inode = $stat["ino"] ?? 0;
        $inode = $this->useEtagInode ? $fileInfo->inode : "";
        $fileInfo->etag = \md5("{$fileInfo->path}{$fileInfo->mtime}{$fileInfo->size}{$inode}");

        if ($this->shouldBufferContent($fileInfo)) {
            $fileInfo->buffer = yield $this->filesystem->get($fileInfo->path);
            $fileInfo->size = \strlen($fileInfo->buffer); // there's a slight chance for the size to change, be safe
            $this->bufferedFileCount++;
        }

        return $fileInfo;
    }

    private function coalesceIndexPath(string $dirPath): \Generator
    {
        $dirPath = \rtrim($dirPath, "/") . "/";
        foreach ($this->indexes as $indexFile) {
            $coalescedPath = $dirPath . $indexFile;
            if (yield $this->filesystem->isfile($coalescedPath)) {
                $stat = yield $this->filesystem->stat($coalescedPath);
                return [$coalescedPath, $stat];
            }
        }
    }

    private function respondFromFileInfo(Internal\FileInformation $fileInfo, Request $request): \Generator
    {
        if (!$fileInfo->exists) {
            if ($this->fallback !== null) {
                return $this->fallback->handleRequest($request);
            }

            return yield $this->errorHandler->handleError(Status::NOT_FOUND, null, $request);
        }

        switch ($request->getMethod()) {
            case "GET":
            case "HEAD":
                break;

            case "OPTIONS":
                return new Response(Status::NO_CONTENT, [
                    "Allow" => "GET, HEAD, OPTIONS",
                    "Accept-Ranges" => "bytes",
                ]);

            default:
                /** @var \Amp\Http\Server\Response $response */
                $response = yield $this->errorHandler->handleError(Status::METHOD_NOT_ALLOWED, null, $request);
                $response->setHeader("Allow", "GET, HEAD, OPTIONS");
                return $response;
        }

        $precondition = $this->checkPreconditions($request, $fileInfo->mtime, $fileInfo->etag);

        switch ($precondition) {
            case self::PRECONDITION_NOT_MODIFIED:
                $lastModifiedHttpDate = \gmdate('D, d M Y H:i:s', $fileInfo->mtime) . " GMT";
                $response = new Response(Status::NOT_MODIFIED, ["Last-Modified" => $lastModifiedHttpDate]);
                if ($fileInfo->etag) {
                    $response->setHeader("Etag", $fileInfo->etag);
                }
                return $response;

            case self::PRECONDITION_FAILED:
                return yield $this->errorHandler->handleError(Status::PRECONDITION_FAILED, null, $request);

            case self::PRECONDITION_IF_RANGE_FAILED:
                // Return this so the resulting generator will be auto-resolved
                return yield from $this->doNonRangeResponse($fileInfo);
        }

        if (!$rangeHeader = $request->getHeader("Range")) {
            // Return this so the resulting generator will be auto-resolved
            return yield from $this->doNonRangeResponse($fileInfo);
        }

        if ($range = $this->normalizeByteRanges($fileInfo->size, $rangeHeader)) {
            // Return this so the resulting generator will be auto-resolved
            return yield from $this->doRangeResponse($range, $fileInfo);
        }

        // If we're still here this is the only remaining response we can send
        /** @var \Amp\Http\Server\Response $response */
        $response = yield $this->errorHandler->handleError(Status::RANGE_NOT_SATISFIABLE, null, $request);
        $response->setHeader("Content-Range", "*/{$fileInfo->size}");
        return $response;
    }

    private function checkPreconditions(Request $request, int $mtime, string $etag): int
    {
        $ifMatch = $request->getHeader("If-Match");
        if ($ifMatch && \stripos($ifMatch, $etag) === false) {
            return self::PRECONDITION_FAILED;
        }

        $ifNoneMatch = $request->getHeader("If-None-Match");
        if ($ifNoneMatch && \stripos($ifNoneMatch, $etag) !== false) {
            return self::PRECONDITION_NOT_MODIFIED;
        }

        $ifModifiedSince = $request->getHeader("If-Modified-Since");
        $ifModifiedSince = $ifModifiedSince ? @\strtotime($ifModifiedSince) : 0;
        if ($ifModifiedSince && $mtime > $ifModifiedSince) {
            return self::PRECONDITION_NOT_MODIFIED;
        }

        $ifUnmodifiedSince = $request->getHeader("If-Unmodified-Since");
        $ifUnmodifiedSince = $ifUnmodifiedSince ? @\strtotime($ifUnmodifiedSince) : 0;
        if ($ifUnmodifiedSince && $mtime > $ifUnmodifiedSince) {
            return self::PRECONDITION_FAILED;
        }

        $ifRange = $request->getHeader("If-Range");
        if ($ifRange === null || !$request->getHeader("Range")) {
            return self::PRECONDITION_OK;
        }

        /**
         * This is a really stupid feature of HTTP but ...
         * If-Range headers may be either an HTTP timestamp or an Etag:.
         *
         *     If-Range = "If-Range" ":" ( entity-tag | HTTP-date )
         *
         * @link https://tools.ietf.org/html/rfc7233#section-3.2
         */
        if ($httpDate = @\strtotime($ifRange)) {
            return ($httpDate > $mtime) ? self::PRECONDITION_IF_RANGE_OK : self::PRECONDITION_IF_RANGE_FAILED;
        }

        // If the If-Range header was not an HTTP date we assume it's an Etag
        return ($etag === $ifRange) ? self::PRECONDITION_IF_RANGE_OK : self::PRECONDITION_IF_RANGE_FAILED;
    }

    private function doNonRangeResponse(Internal\FileInformation $fileInfo): \Generator
    {
        $headers = $this->makeCommonHeaders($fileInfo);
        $headers["Content-Type"] = $this->selectMimeTypeFromPath($fileInfo->path);

        if ($fileInfo->buffer !== null) {
            $headers["Content-Length"] = (string)$fileInfo->size;

            return new Response(Status::OK, $headers, new InMemoryStream($fileInfo->buffer));
        }

        // Don't use cached size if we don't have buffered file contents,
        // otherwise we get truncated files during development.
        $headers["Content-Length"] = (string)yield $this->filesystem->size($fileInfo->path);

        $handle = yield $this->filesystem->open($fileInfo->path, "r");

        $response = new Response(Status::OK, $headers, $handle);
        $response->onDispose([$handle, "close"]);
        return $response;
    }

    private function makeCommonHeaders($fileInfo): array
    {
        $headers = [
            "Accept-Ranges" => "bytes",
            "Cache-Control" => "public",
            "Etag" => $fileInfo->etag,
            "Last-Modified" => \gmdate('D, d M Y H:i:s', $fileInfo->mtime) . " GMT",
        ];

        $canCache = ($this->expiresPeriod > 0);
        if ($canCache && $this->useAggressiveCacheHeaders) {
            $postCheck = (int)($this->expiresPeriod * $this->aggressiveCacheMultiplier);
            $preCheck = $this->expiresPeriod - $postCheck;
            $expiry = $this->expiresPeriod;
            $value = "post-check={$postCheck}, pre-check={$preCheck}, max-age={$expiry}";
            $headers["Cache-Control"] = $value;
        } elseif ($canCache) {
            $expiry = $this->now + $this->expiresPeriod;
            $headers["Expires"] = \gmdate('D, d M Y H:i:s', $expiry) . " GMT";
        } else {
            $headers["Expires"] = "0";
        }

        return $headers;
    }

    private function selectMimeTypeFromPath(string $path): string
    {
        $ext = \pathinfo($path, PATHINFO_EXTENSION);
        if (empty($ext)) {
            $mimeType = $this->defaultMimeType;
        } else {
            $ext = \strtolower($ext);
            if (isset($this->mimeTypes[$ext])) {
                $mimeType = $this->mimeTypes[$ext];
            } elseif (isset($this->mimeFileTypes[$ext])) {
                $mimeType = $this->mimeFileTypes[$ext];
            } else {
                $mimeType = $this->defaultMimeType;
            }
        }

        if (\stripos($mimeType, "text/") === 0 && \stripos($mimeType, "charset=") === false) {
            $mimeType .= "; charset={$this->defaultCharset}";
        }

        return $mimeType;
    }

    /**
     * @link https://tools.ietf.org/html/rfc7233#section-2.1
     *
     * @param int    $size Total size of the file in bytes.
     * @param string $rawRanges Ranges as provided by the client.
     *
     * @return Internal\ByteRange|null
     */
    private function normalizeByteRanges(int $size, string $rawRanges)
    {
        $rawRanges = \str_ireplace([' ', 'bytes='], '', $rawRanges);

        $ranges = [];

        foreach (\explode(',', $rawRanges) as $range) {
            // If a range is missing the dash separator it's malformed; pull out here.
            if (false === strpos($range, '-')) {
                return null;
            }

            list($startPos, $endPos) = explode('-', rtrim($range), 2);

            if ($startPos === '' && $endPos === '') {
                return null;
            }

            if ($startPos === '' && $endPos !== '') {
                // The -1 is necessary and not a hack because byte ranges are inclusive and start
                // at 0. DO NOT REMOVE THE -1.
                $startPos = $size - $endPos - 1;
                $endPos = $size - 1;
            } elseif ($endPos === '' && $startPos !== '') {
                $startPos = (int)$startPos;
                // The -1 is necessary and not a hack because byte ranges are inclusive and start
                // at 0. DO NOT REMOVE THE -1.
                $endPos = $size - 1;
            } else {
                $startPos = (int)$startPos;
                $endPos = (int)$endPos;
            }

            // If the requested range(s) can't be satisfied we're finished
            if ($startPos >= $size || $endPos < $startPos || $endPos < 0) {
                return null;
            }

            $ranges[] = [$startPos, $endPos];
        }

        $range = new Internal\ByteRange;
        $range->boundary = $this->multipartBoundary;
        $range->ranges = $ranges;

        return $range;
    }

    private function doRangeResponse(Internal\ByteRange $range, Internal\FileInformation $fileInfo): \Generator
    {
        $headers = $this->makeCommonHeaders($fileInfo);
        $range->contentType = $mime = $this->selectMimeTypeFromPath($fileInfo->path);

        if (isset($range->ranges[1])) {
            $headers["Content-Type"] = "multipart/byteranges; boundary={$range->boundary}";
        } else {
            list($startPos, $endPos) = $range->ranges[0];
            $headers["Content-Length"] = (string)($endPos - $startPos + 1);
            $headers["Content-Range"] = "bytes {$startPos}-{$endPos}/{$fileInfo->size}";
            $headers["Content-Type"] = $mime;
        }

        $handle = yield $this->filesystem->open($fileInfo->path, "r");

        if (empty($range->ranges[1])) {
            list($startPos, $endPos) = $range->ranges[0];
            $stream = $this->sendSingleRange($handle, $startPos, $endPos);
        } else {
            $stream = $this->sendMultiRange($handle, $fileInfo, $range);
        }

        $response = new Response(Status::PARTIAL_CONTENT, $headers, $stream);
        $response->onDispose([$handle, "close"]);
        return $response;
    }

    private function sendSingleRange(File\Handle $handle, int $startPos, int $endPos): InputStream
    {
        $iterator = new Producer(function (callable $emit) use ($handle, $startPos, $endPos) {
            return $this->readRangeFromHandle($handle, $emit, $startPos, $endPos);
        });

        return new IteratorStream($iterator);
    }

    private function sendMultiRange($handle, Internal\FileInformation $fileInfo, Internal\ByteRange $range): InputStream
    {
        $iterator = new Producer(function (callable $emit) use ($handle, $range, $fileInfo) {
            foreach ($range->ranges as list($startPos, $endPos)) {
                yield $emit(sprintf(
                    "--%s\r\nContent-Type: %s\r\nContent-Range: bytes %d-%d/%d\r\n\r\n",
                    $range->boundary,
                    $range->contentType,
                    $startPos,
                    $endPos,
                    $fileInfo->size
                ));
                yield from $this->readRangeFromHandle($handle, $emit, $startPos, $endPos);
                yield $emit("\r\n");
            }
            yield $emit("--{$range->boundary}--");
        });

        return new IteratorStream($iterator);
    }

    private function readRangeFromHandle(File\Handle $handle, callable $emit, int $startPos, int $endPos): \Generator
    {
        $bytesRemaining = $endPos - $startPos + 1;
        yield $handle->seek($startPos);

        while ($bytesRemaining) {
            $toBuffer = $bytesRemaining > self::READ_CHUNK_SIZE ? self::READ_CHUNK_SIZE : $bytesRemaining;
            $chunk = yield $handle->read($toBuffer);
            $bytesRemaining -= \strlen($chunk);
            yield $emit($chunk);
        }
    }

    public function setIndexes(array $indexes)
    {
        foreach ($indexes as $index) {
            if (!\is_string($index)) {
                throw new \TypeError(sprintf(
                    "Array of string index filenames required: %s provided",
                    \gettype($index)
                ));
            }
        }

        $this->indexes = \array_filter($indexes);
    }

    public function setUseEtagInode(bool $useInode)
    {
        $this->useEtagInode = $useInode;
    }

    public function setExpiresPeriod(int $seconds)
    {
        $this->expiresPeriod = ($seconds < 0) ? 0 : $seconds;
    }

    public function loadMimeFileTypes(string $mimeFile)
    {
        $mimeFile = str_replace('\\', '/', $mimeFile);
        $mimeStr = @file_get_contents($mimeFile);
        if ($mimeStr === false) {
            throw new \Exception(
                "Failed loading mime associations from file {$mimeFile}"
            );
        }

        /** @var array[] $matches */
        if (!preg_match_all('#\s*([a-z0-9]+)\s+([a-z0-9\-]+/[a-z0-9\-]+(?:\+[a-z0-9\-]+)?)#i', $mimeStr, $matches)) {
            throw new \Exception(
                "No mime associations found in file: {$mimeFile}"
            );
        }

        $mimeTypes = [];

        foreach ($matches[1] as $key => $value) {
            $mimeTypes[strtolower($value)] = $matches[2][$key];
        }

        $this->mimeFileTypes = $mimeTypes;
    }

    public function setMimeTypes(array $mimeTypes)
    {
        foreach ($mimeTypes as $ext => $type) {
            $ext = strtolower(ltrim($ext, '.'));
            $this->mimeTypes[$ext] = $type;
        }
    }

    public function setDefaultMimeType(string $mimeType)
    {
        if (empty($mimeType)) {
            throw new \Error(
                'Default mime type expects a non-empty string'
            );
        }

        $this->defaultMimeType = $mimeType;
    }

    public function setDefaultTextCharset(string $charset)
    {
        if (empty($charset)) {
            throw new \Error(
                'Default charset expects a non-empty string'
            );
        }

        $this->defaultCharset = $charset;
    }

    public function setUseAggressiveCacheHeaders(bool $bool)
    {
        $this->useAggressiveCacheHeaders = $bool;
    }

    public function setAggressiveCacheMultiplier(float $multiplier)
    {
        if ($multiplier > 0.00 && $multiplier < 1.0) {
            $this->aggressiveCacheMultiplier = $multiplier;
        } else {
            throw new \Error(
                "Aggressive cache multiplier expects a float < 1; {$multiplier} specified"
            );
        }
    }

    public function setCacheEntryTtl(int $seconds)
    {
        if ($seconds < 1) {
            $seconds = 10;
        }
        $this->cacheEntryTtl = $seconds;
    }

    public function setCacheEntryLimit(int $count)
    {
        if ($count < 1) {
            $count = 0;
        }
        $this->cacheEntryLimit = $count;
    }

    public function setBufferedFileLimit(int $count)
    {
        if ($count < 1) {
            $count = 0;
        }
        $this->bufferedFileLimit = $count;
    }

    public function setBufferedFileSizeLimit(int $bytes)
    {
        if ($bytes < 1) {
            $bytes = 524288;
        }
        $this->bufferedFileSizeLimit = $bytes;
    }

    public function onStart(Server $server): Promise
    {
        $this->running = true;

        if (empty($this->mimeFileTypes)) {
            $this->loadMimeFileTypes(self::DEFAULT_MIME_TYPE_FILE);
        }

        $this->errorHandler = $server->getErrorHandler();

        $this->debug = $server->getOptions()->isInDebugMode();

        $server->getTimeReference()->onTimeUpdate($this->callableFromInstanceMethod("clearExpiredCacheEntries"));

        if ($this->fallback instanceof ServerObserver) {
            return $this->fallback->onStart($server);
        }

        return new Success;
    }

    public function onStop(Server $server): Promise
    {
        $this->cache = [];
        $this->cacheTimeouts = [];
        $this->cacheEntryCount = 0;
        $this->bufferedFileCount = 0;
        $this->running = false;

        if ($this->fallback instanceof ServerObserver) {
            return $this->fallback->onStop($server);
        }

        return new Success;
    }
}
