<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Auth</title>
    <style>
    </style>
</head>
<form>
    <input type="password" id="pwd">
    <input type="button" id="sbm" value="+">
</form>
<script>
    /* {kjbquery.js} */
    /* {api.js} */

    const auth = (pwd) => {
        document.cookie = "auth=" + pwd;
        location.reload(true);
    };

    $("#sbm").on("click", () => {
        auth($("#pwd").getVal());
    });
</script>
</html>
