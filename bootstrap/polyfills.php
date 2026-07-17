<?php

/*
|--------------------------------------------------------------------------
| Small compatibility fallback
|--------------------------------------------------------------------------
| Laravel normally runs with the PHP mbstring extension enabled. Some local
| Windows installations omit the mbregex function mb_split even while the
| rest of the framework can run. This fallback prevents a generic HTTP 500
| on public pages; installing/enabling mbstring is still recommended.
*/
if (! function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array|false
    {
        $delimiter = '~';
        $escaped = str_replace($delimiter, '\\'.$delimiter, $pattern);
        return preg_split($delimiter.$escaped.$delimiter.'u', $string, $limit);
    }
}
