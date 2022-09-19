<?php
require_once("config/values.php");
require_once("config/files.php");


function download_zip(string $username) {
    $archive_name = "files.zip";
    // Create the archive
    $archive = new ZipArchive();
    $archive->open($archive_name, ZipArchive::CREATE);
    foreach (get_files_array($username) as $file) {
        $archive->addFile($file);
    }
    $archive->close();
    // Send the headers to download the archive
    ob_clean();
    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=$archive_name");
    header("Content-length: " . filesize($archive_name));
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile("$archive_name");
}

function main(): void
{
    session_start();
    # https://stackoverflow.com/a/15088537
    if (!isset($_SESSION['username'])) {
        header("Location:sign_in.php");
        exit;
    }

    download_zip($_SESSION['username']);
}

main();