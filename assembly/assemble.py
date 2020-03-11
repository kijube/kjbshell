from os.path import *
from os import mkdir
from re import search, sub
from shutil import rmtree
from sys import argv
from requests import post

minify_css_url = "https://cssminifier.com/raw"
minify_js_url = "https://javascript-minifier.com/raw"

kjb_name = "kjb.php"
src_path = "../web/"
kjb_path = join(src_path, kjb_name)
build_path = "build/"

injection_pattern = "<!-- {(.+?)} -->"
injection_pattern2 = "\\/\\* {(.+?)} \\*\\/"
multispace_pattern = "( {2,})"

do_minify = False

rmtree(build_path)
mkdir(build_path)

password = ""
if len(argv) > 1:
    password = argv[1]


def minify_php(content):
    content = str.join(" ", list(filter(lambda x: not x.strip().startswith("//"), content.splitlines())))
    return content


def minify_remote(url, code):
    if url is None:
        return code
    data = {"input": code}
    r = post(url, data=data)
    return r.text


def minify(file_name, content):
    if not do_minify:
        return content
    print("Minifying %s" % file_name)
    if file_name.endswith(".php"):
        content = minify_php(content)
    elif file_name.endswith(".js"):
        content = minify_remote(minify_js_url, content)
    elif file_name.endswith(".css"):
        content = minify_remote(minify_css_url, content)
    return content


def inject(_src, file_name):
    src = _src
    while True:
        match = search(injection_pattern, src)
        if match is None:
            match = search(injection_pattern2, src)
            if match is None:
                break

        inj_file_name = match.group(1)
        inj_file_path = join(src_path, match.group(1))
        idx = src.index(match.group())
        end_idx = idx + len(match.group())
        print("Injecting file '%s' to file '%s' at index %d" % (inj_file_path, file_name, idx))
        with open(inj_file_path, "r") as inj_file:
            content = minify(inj_file_name, inject(inj_file.read(), inj_file_name))
            new_source = src[:idx]
            new_source += content
            new_source += src[end_idx:]
            src = new_source

    return src


with open(kjb_path, "r") as file:
    source = file.read()
source = minify(kjb_name, source)
source = source.replace("{password}", password)

out_path = join(build_path, kjb_name)

print("Assembling to %s" % out_path)
source = inject(source, kjb_name)

print("Obfuscating %s" % kjb_name)
source = sub(multispace_pattern, " ", source)

with open(out_path, "w") as out_file:
    out_file.write(source)
print("Built to '%s'" % out_path)
