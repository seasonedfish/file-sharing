<?php
/**
 * The sign-in page.
 */

require_once "config/values.php";

$error = null;

function sign_in(string $username): void
{
    global $error;

    $usernames_file = sprintf("%s/users.txt", DATA_ROOT);
    $usernames_array = file($usernames_file, FILE_IGNORE_NEW_LINES);

    if (in_array($username, $usernames_array)) {
        $_SESSION['username'] = $username;
        header("Location: main.php");
        exit();
    } else {
        $error = "Account not found";
    }
}

function main(): void
{
    session_start();
    if (isset($_POST["username"])) {
        sign_in($_POST["username"]);
    }
}

main();

include "includes/head.php";
?>

<body>
    <?php
    include "includes/header.php";
    ?>

    <h2>Sign in</h2>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <p>
            <label for="username">Username: </label>
            <input type="text" name="username" id="username">
        </p>

        <p>
            <input type="submit" value="Submit">
        </p>
    </form>

    <p>
        <?php
        if (isset($error)) {
            print($error);
        }
        ?>
    </p>
</body>

<?php
include "includes/tail.php";
?>
