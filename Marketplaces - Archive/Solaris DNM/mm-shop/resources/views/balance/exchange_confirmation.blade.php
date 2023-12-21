<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
</head>
<body>
<form action="{{ $formAction }}" method="post">
    <input type="hidden" name="data" value="{{ $data }}">
    <noscript>
        <button type="submit">Нажмите для продолжения</button>
    </noscript>
</form>
<script>
    document.forms[0].submit()
</script>
</body>
</html>

