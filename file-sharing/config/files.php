<?php
/**
 * Returns true if the given file should be displayed.
 * The "." and ".." files should not be displayed.
 * This function is used as the filter in get_files_array.
 * @param string $file
 * @return bool
 */
function is_displayed_file(string $file): bool
{
    return $file != "." && $file != "..";
}

function get_files_array(string $username): array
{
    $user_dir = sprintf("%s/%s", DATA_ROOT, $username);
    $ls = scandir($user_dir);
    return array_filter($ls, "is_displayed_file");
}