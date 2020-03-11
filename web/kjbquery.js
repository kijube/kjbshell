"use strict";

function KjbElement(element) {
    this.node = element;

    this.getVal = function () {
        return this.node.value;
    };

    this.setVal = function (value) {
        this.node.value = value;
        return this;
    };

    this.click = function () {
        this.node.click();
        return this;
    };

    this.on = function (event, handler) {
        if (event === "click") {
            this.node.onmousedown = handler;
        } else if (event === "keydown") {
            this.node.onkeydown = handler;
        } else if (event === "keypress") {
            this.node.onkeypress = handler;
        } else if (event === "keyup") {
            this.node.onkeyup = handler;
        } else if (event === "change") {
            this.node.onchange = handler;
        } else if (event === "load") {
            this.node.onload = handler;
        }
        return this;
    };

    this.addClass = function (clazz) {
        this.node.classList.add(clazz);
        return this;
    };

    this.removeClass = function (clazz) {
        this.node.classList.remove(clazz);
        return this;
    };

    this.focus = function () {
        this.node.focus();
        return this;
    };

    this.setProp = function (name, value) {
        this.node.setAttribute(name, value);
        if (!value) {
            this.node.removeAttribute(name);
        }
        return this;
    };

    this.getProp = function (name) {
        return this.node.getAttribute(name);
    };

    this.setHtml = function (val) {
        this.node.innerHTML = val;
        return this;
    };

    this.setStyle = function (key, val) {
        this.node.style[key] = val;
        return this;
    };

    this.getHtml = function () {
        return this.node.innerHTML;
    };

    this.setText = function (val) {
        this.node.textContent = val;
        return this;
    };

    this.getText = function () {
        return this.node.textContent;
    };

    this.scrollToBottom = function () {
        this.node.scrollTop = this.node.scrollHeight;
        return this;
    };

    this.append = function (element) {
        this.node.appendChild(element.node);
        return this;
    };

    this.empty = function () {
        while (this.node.firstChild) {
            this.node.removeChild(this.node.firstChild);
        }
        return this;
    };
}

const $ = (element) => {
    if (element === document) {
        return new KjbElement(document);
    } else if (element.indexOf("<") === 0 && element.indexOf(">") === element.length - 1) {
        const textNode = document.createTextNode("");
        const node = document.createElement(element.substring(1, element.length - 1));
        node.appendChild(textNode);
        return new KjbElement(node);
    } else {
        return new KjbElement(document.querySelector(element));
    }
};
