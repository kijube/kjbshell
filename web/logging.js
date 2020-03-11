"use strict";
const log = (msg) => {
    const line = $("<pre>").addClass("logElement").setText(msg);
    buffer.append(line);
    bufferWrapper.scrollToBottom();
    bufferWrapper.node.scrollTo(0, bufferWrapper.node.scrollHeight);
    return line;
};

const logMultiline = (msg) => {
    const res = [];
    msg.split("\n").forEach(msg => {
        res.push(log(msg));
    });
    return res;
};

const logCommand = (cmd) => {
    return log(cmd).addClass("logCommand");
};

const colorize = (line, color) => {
    line.setStyle("color", color);
    return line;
};

const logSpace = () => {
    log("").addClass("logSpace");
};

const logError = (msg) => {
    log(msg).addClass("logError");
};

const escapeHtml = (html) => {
    const node = document.createTextNode(html);
    const el = document.createElement("div");
    el.appendChild(node);
    return el.innerHTML;
};

const encodeWhiteSpaces = (s) => {
    return s.split('').map(function (c) {
        if (c === ' ')
            return '&nbsp;';
        else
            return c;
    }).join('');
};


const clearLog = () => {
    buffer.empty();
};
