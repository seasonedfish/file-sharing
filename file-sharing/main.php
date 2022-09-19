<?php
/**
 * The main page for accessing files.
 * A user must first sign in (using sign_in.php) to see this page.
 */
require_once("config/values.php");

class InvalidUsernameException extends Exception {}

class InvalidFilenameException extends Exception {}

class QuotaExceededException extends Exception {}

class FileTooLargeException extends Exception {}

/**
 * Throw an InvalidUsernameException if the file name
 * has characters besides alphanumerics, underscores, dashes.
 * @throws InvalidUsernameException
 */
function assert_valid_username(string $username): void
{
    if( !preg_match('/^[\w\-]+$/', $username) ) {
        throw new InvalidUsernameException;
    }
}
/**
 * Throw an InvalidFilenameException if the file name
 * has characters besides alphanumerics, underscores, dots, dashes, spaces.
 * @throws InvalidFilenameException
 */
function assert_valid_filename(string $filename): void
{
    # Regex modified from the 330 wiki to allow spaces.
    if (!preg_match('/^[\w.\- ]+$/', $filename)) {
        throw new InvalidFilenameException;
    }
}

/**
 * @throws InvalidFilenameException
 * @throws InvalidUsernameException
 * @throws QuotaExceededException
 * @throws FileTooLargeException
 */
function upload(string $username, array $file): bool
{
    assert_valid_username($username);

    $filename = basename($file['name']);
    assert_valid_filename($filename);

    # https://stackoverflow.com/a/29256841
    # Checks if file size exceeds MAX_FILE_SIZE
    # https://www.php.net/manual/en/features.file-upload.errors.php
    if ($file['error'] == 2 || $file['error' == 1]) {
        throw new FileTooLargeException();
    }

    if ($file['size'] + get_disk_usage_bytes($username) > QUOTA_BYTES) {
        throw new QuotaExceededException();
    }

    $upload_dest = sprintf(
        "%s/%s/%s",
        DATA_ROOT,
        $username,
        $filename
    );

    # https://www.php.net/manual/en/features.file-upload.post-method.php
    return move_uploaded_file($file['tmp_name'], $upload_dest);
}

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

function get_files_table(string $username): string
{
    $table = "<table>";
    foreach (get_files_array($username) as $file) {
        $table .= <<<EOD
    <tr>
        <td> $file </td>
        <td><a href="file_actions.php?file=$file&action=view">View</a></td>
        <td><a href="file_actions.php?file=$file&action=delete">Delete</a></td>
    </tr>
EOD;
    }
    $table .= "</table>";
    return $table;
}

function get_disk_usage_bytes(string $username): int
{
    $user_dir = DATA_ROOT . "/" . $username;

    $bytes_total = 0;
    foreach(scandir($user_dir) as $file){
        $bytes_total += filesize($user_dir . "/" . $file);
    }

    return $bytes_total;
}

function get_disk_usage_string(string $username): string
{
    $disk_usage_mb = get_disk_usage_bytes($username) / 1000000;
    $quota_mb = QUOTA_BYTES / 1000000;
    return sprintf("%.2f MB / %.2f MB", $disk_usage_mb, $quota_mb);
}

function main(): void
{
    session_start();
    # https://stackoverflow.com/a/15088537
    if (!isset($_SESSION['username'])) {
        header("Location:sign_in.php");
        exit;
    }
}

main();

include("includes/head.php")
?>

<body>
    <?php
    include "includes/header.php";
    ?>

    <p>
        <?php
        printf("Signed in as %s", htmlspecialchars($_SESSION['username']));
        ?>
        (<a href="sign_out.php">sign out</a>)
    </p>

    <h2>Upload a file</h2>
    <!-- https://www.php.net/manual/en/features.file-upload.post-method.php -->
    <form enctype="multipart/form-data" method="POST">
        <input type="hidden" name="MAX_FILE_SIZE" value="2000000"/> <!-- 2MB -->
        <input name="uploaded-file" type="file"/>
        <input type="submit" value="Upload"/>
    </form>

    <p>
        <?php
        if (isset($_FILES['uploaded-file'])) {
            $escaped_filename = htmlspecialchars($_FILES['uploaded-file']['name']);
            try {
                upload($_SESSION['username'], $_FILES['uploaded-file']);
                printf("%s successfully uploaded.", $escaped_filename);
            } catch (InvalidFilenameException $e) {
                printf("Invalid filename. Not uploaded.");
            } catch (InvalidUsernameException $e) {
                echo "Your username appears to be invalid. Please contact the webmaster for help.";
            } catch (QuotaExceededException $e) {
                printf("Quota exceeded. %s not uploaded.", $escaped_filename);
            } catch (FileTooLargeException $e) {
                printf("File %s exceeds the server's max file size. Not uploaded.", $escaped_filename);
            }
        }
        ?>
    </p>

    <h2>My files</h2>
    <?php
    print(get_files_table($_SESSION['username']))
    ?>

    <small>
        <?php
        print(get_disk_usage_string($_SESSION['username']))
        ?>
    </small>

</body>

<?php
include "includes/tail.php";
?>
