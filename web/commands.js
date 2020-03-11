const commands = {};

const addCommand = (name, syntax, description, executor) => {
    commands[name.trim().toLowerCase()] = {syntax, description, executor};
};

const printCommands = () => {
    const maxLength = Object.values(commands).map(x => x.syntax.length).reduce((a, b) => a > b ? a : b);
    colorize(log("Available commands:"), "#93a1a1");
    Object.keys(commands).forEach(key => {
        const cmd = commands[key];
        log(cmd.syntax.padEnd(maxLength, " ") + " | " + cmd.description);
    });
};

const initCommands = () => {
    addCommand("kjb", "kjb", "Show the list of kjb commands", commandHelp);
    addCommand("cd", "cd <directory>", "Change directory", commandChangeDirectory);
    addCommand("clear", "clear", "Clear the kjb log", commandClear);
    addCommand("hide", "hide (info|preinput)", "Hide some ui elements", commandHide);
    addCommand("download", "download <file>", "Download a file", commandDownload);
    addCommand("upload", "upload", "Upload a file", commandUpload);

    $("#fileUpload").on("change", commandUploadListener);
};

const getCommandName = (input) => {
    let res;
    if (!input.indexOf(" ") || input.indexOf(" ") === -1) res = input;
    else res = input.slice(0, input.indexOf(" "));
    return res.toLowerCase().trim();
};

const getCommandArgs = (input) => {
    return input.slice(getCommandName(input).length).trim();
};

const printSyntax = (command) => {
    log("Wrong usage! Correct syntax: " + command.syntax);
};

const handleInternally = (input) => {
    const command = commands[getCommandName(input)];

    if (!command) {
        return false;
    }

    if (!command.executor(getCommandArgs(input))) {
        printSyntax(command);
        return true;
    }

    return true;
};

const commandClear = (_) => {
    clearLog();
    return true;
};

const commandDownload = (args) => {
    if (!args || args.length === 0) {
        return false;
    }
    log(`Checking if file is downloadable ...`);
    requestCheckDownloadable(args, data => {
        if (!data.exists) {
            logError("Error while downloading: File does not exist!");
        } else if (!data.is_readable) {
            logError("Error while downloading: File is not readable!");
        } else {
            requestDownload(args);
        }
    }, handleError(err => {
        const {errmsg} = err;
        logError("Error while downloading: " + errmsg);
    }));
    return true;
};

const commandUpload = (args) => {
    $("#fileUpload").click();
    return true;
};

const appendBaseIfNecessary = (path) => {
    let append = cwd;
    if (!append.endsWith("/")) append += "/";
    if (path.startsWith("/") || path.indexOf(":") === 1) append = "";
    return append + path;
};

const commandUploadListener = () => {
    const file = $("#fileUpload").node.files[0];
    if (!file) {
        log("Cancelled upload.");
        return;
    }

    log("Uploading file '" + file.name + "'");
    const path = appendBaseIfNecessary(file.name);

    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = (e) => {
        const content = e.target.result.match(/base64,(.*)$/)[1];

        requestUpload(file.name, content, (data) => {
            log(`Upload successful, path is: ${path}`);
        }, handleError(err => {
            const {errmsg} = err;
            logError(`Error whilst uploading: ${errmsg}`);
        }))
    };
};

const commandHide = (args) => {
    if (args === "info") {
        const inp = $("#targetInfo");
        inp.node.style.display = inp.node.style.display === "none" ? "flex" : "none";
        log("Toggled target information display.");
        return true;
    } else if (args === "preinput") {
        const ih = $("#inputCurrHost");
        const ic = $("#inputCwd");
        ih.node.style.display = ih.node.style.display === "none" ? "inline-block" : "none";
        ic.node.style.display = ic.node.style.display === "none" ? "inline-block" : "none";
        log("Toggled user and cwd information display.");
        return true;
    }
};

const commandChangeDirectory = (args) => {
    if (!args || args.length === 0) return false;
    let res = true;
    requestCd(args, _ => {
    }, handleError((err) => {
        logError("Could not change directory.");
        res = false;
    }));
    return res;
};

const commandHelp = (args) => {
    printCommands();
};

initCommands();
