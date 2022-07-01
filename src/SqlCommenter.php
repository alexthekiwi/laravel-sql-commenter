<?php

namespace Spatie\SqlCommenter;

use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\SqlCommenter\Commenters\Commenter;

class SqlCommenter
{
    /** @var array<Comment> */
    protected static array $extraComments = [];

    public static function addComment(string $key, ?string $value): void
    {
        static::$extraComments[$key] = new Comment($key, $value);
    }

    public function commentQuery(string $query, Connection $connection, array $commenters): string
    {
        if (str_contains($query, '/*')) {
            return $query;
        }

        $comments = $this->getCommentsFromCommenters($commenters, $connection, $query);

        $comments->push(...self::$extraComments);
        self::$extraComments = [];

        return $this->addCommentsToQuery($query, $comments);
    }

    /**
     * @param array<Commenter> $commenters
     * @param \Illuminate\Database\Connection $connection
     * @param string $query
     *
     * @return Collection<Comment>
     */
    protected function getCommentsFromCommenters(
        array $commenters,
        Connection $connection,
        string $query,
    ): Collection {
        return collect($commenters)
            ->flatMap(function (Commenter $commenter) use ($connection, $query) {
                $comments = $commenter->comments($query, $connection) ?? [];

                return Arr::wrap($comments);
            })
            ->filter();
    }

    /**
     * @param string $query
     * @param Collection<Comment> $comments
     *
     * @return string
     */
    protected function addCommentsToQuery(string $query, Collection $comments): string
    {
        $query = Str::finish(trim($query), ';');

        if (Str::endsWith($query, ';')) {
            return rtrim($query, ";") . Comment::formatCollection($comments) . ';';
        }

        return $query . Comment::formatCollection($comments);
    }
}
