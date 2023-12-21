function openAuth(name)
{
    $("[id^=auth]").each(function()
    {
        $(this).hide();
    });
    $(name).show();
}